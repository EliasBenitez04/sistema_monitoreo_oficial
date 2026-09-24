<?php

namespace App\Imports;

use App\Models\RedistribucionProcesoDetalle;
use App\Models\RedistribucionRemision;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RedistribucionRemisionImport implements ToCollection, WithHeadingRow, WithChunkReading, SkipsEmptyRows
{
    private $inicializado = false;
    private $detallesPorClave;
    private $remisionesPorDocumento = [];
    private $transferidoPorDetalle = [];

    private $procesadas = 0;
    private $coincidentes = 0;
    private $insertadas = 0;
    private $actualizadas = 0;
    private $sinCoincidencia = 0;
    private $sinSaldo = 0;
    private $omitidas = 0;
    private $errores = 0;

    public function importarXlsxStreaming(string $ruta): void
    {
        @set_time_limit(0);
        DB::disableQueryLog();

        if (!class_exists(\ZipArchive::class) || !class_exists(\XMLReader::class)) {
            throw new \RuntimeException(
                'El servidor necesita las extensiones PHP zip y xmlreader para importar XLSX grandes.'
            );
        }

        $zip = new \ZipArchive();

        if ($zip->open($ruta) !== true) {
            throw new \RuntimeException('No se pudo abrir el archivo XLSX.');
        }

        $temporalBase = tempnam(sys_get_temp_dir(), 'redis_envios_');

        if ($temporalBase === false) {
            $zip->close();
            throw new \RuntimeException('No se pudo crear el archivo temporal para importar remisiones.');
        }

        @unlink($temporalBase);
        $directorioTemporal = $temporalBase . '_dir';

        if (!@mkdir($directorioTemporal, 0777, true) && !is_dir($directorioTemporal)) {
            $zip->close();
            throw new \RuntimeException('No se pudo crear el directorio temporal.');
        }

        try {
            $sharedStrings = $this->leerSharedStrings($zip);
            $hojaInterna = 'xl/worksheets/sheet1.xml';

            if ($zip->locateName($hojaInterna) === false) {
                throw new \RuntimeException('El XLSX no contiene la hoja principal esperada.');
            }

            if (!$zip->extractTo($directorioTemporal, [$hojaInterna])) {
                throw new \RuntimeException('No se pudo extraer la hoja del XLSX.');
            }

            $rutaHoja = $directorioTemporal . DIRECTORY_SEPARATOR
                . 'xl' . DIRECTORY_SEPARATOR
                . 'worksheets' . DIRECTORY_SEPARATOR
                . 'sheet1.xml';

            $reader = new \XMLReader();

            if (!$reader->open($rutaHoja, null, LIBXML_NONET | LIBXML_COMPACT)) {
                throw new \RuntimeException('No se pudo iniciar la lectura incremental del XLSX.');
            }

            $encabezados = [];
            $lote = [];
            $tamanoLote = 2500;

            while ($reader->read()) {
                if ($reader->nodeType !== \XMLReader::ELEMENT || $reader->localName !== 'row') {
                    continue;
                }

                $xmlFila = $reader->readOuterXML();

                if ($xmlFila === '') {
                    continue;
                }

                $valores = $this->leerFilaXml($xmlFila, $sharedStrings);

                if (empty($encabezados)) {
                    foreach ($valores as $indice => $valor) {
                        $encabezados[$indice] = Str::slug(
                            trim((string) $valor),
                            '_'
                        );
                    }

                    continue;
                }

                $fila = [];

                foreach ($encabezados as $indice => $encabezado) {
                    if ($encabezado === '') {
                        continue;
                    }

                    $fila[$encabezado] = $valores[$indice] ?? null;
                }

                $lote[] = $fila;

                if (count($lote) >= $tamanoLote) {
                    $this->collection(collect($lote));
                    $lote = [];
                    gc_collect_cycles();
                }
            }

            $reader->close();

            if (!empty($lote)) {
                $this->collection(collect($lote));
                $lote = [];
                gc_collect_cycles();
            }
        } finally {
            $zip->close();
            $this->eliminarDirectorioTemporal($directorioTemporal);
        }
    }

    private function leerSharedStrings(\ZipArchive $zip): array
    {
        $indice = $zip->locateName('xl/sharedStrings.xml');

        if ($indice === false) {
            return [];
        }

        $contenido = $zip->getFromIndex($indice);

        if ($contenido === false || $contenido === '') {
            return [];
        }

        $reader = new \XMLReader();

        if (!$reader->XML($contenido, null, LIBXML_NONET | LIBXML_COMPACT)) {
            return [];
        }

        $strings = [];

        while ($reader->read()) {
            if ($reader->nodeType !== \XMLReader::ELEMENT || $reader->localName !== 'si') {
                continue;
            }

            $siXml = $reader->readOuterXML();

            if ($siXml === '') {
                $strings[] = '';
                continue;
            }

            $texto = '';

            if (preg_match_all('/<t(?:\\s[^>]*)?>(.*?)<\\/t>/s', $siXml, $coincidencias)) {
                foreach ($coincidencias[1] as $fragmento) {
                    $texto .= html_entity_decode(
                        strip_tags($fragmento),
                        ENT_QUOTES | ENT_XML1,
                        'UTF-8'
                    );
                }
            }

            $strings[] = $texto;
        }

        $reader->close();

        return $strings;
    }

    private function leerFilaXml(string $xmlFila, array $sharedStrings): array
    {
        $valores = [];

        if (!preg_match_all('/<c\\b([^>]*)>(.*?)<\\/c>/s', $xmlFila, $celdas, PREG_SET_ORDER)) {
            return $valores;
        }

        foreach ($celdas as $celda) {
            $atributos = $celda[1] ?? '';
            $contenido = $celda[2] ?? '';

            if (!preg_match('/\\br="([A-Z]+)(\\d+)"/i', $atributos, $mReferencia)) {
                continue;
            }

            $indiceColumna = $this->indiceColumnaExcel(strtoupper($mReferencia[1]));
            $tipo = null;

            if (preg_match('/\\bt="([^"]+)"/i', $atributos, $mTipo)) {
                $tipo = $mTipo[1];
            }

            $valor = null;

            if ($tipo === 'inlineStr') {
                if (preg_match_all('/<t(?:\\s[^>]*)?>(.*?)<\\/t>/s', $contenido, $mTextos)) {
                    $valor = '';

                    foreach ($mTextos[1] as $fragmento) {
                        $valor .= html_entity_decode(
                            strip_tags($fragmento),
                            ENT_QUOTES | ENT_XML1,
                            'UTF-8'
                        );
                    }
                }
            } elseif (preg_match('/<v>(.*?)<\\/v>/s', $contenido, $mValor)) {
                $valorCrudo = html_entity_decode(
                    strip_tags($mValor[1]),
                    ENT_QUOTES | ENT_XML1,
                    'UTF-8'
                );

                if ($tipo === 's') {
                    $valor = $sharedStrings[(int) $valorCrudo] ?? '';
                } elseif ($tipo === 'b') {
                    $valor = $valorCrudo === '1' ? 1 : 0;
                } else {
                    $valor = $valorCrudo;
                }
            }

            $valores[$indiceColumna] = $valor;
        }

        return $valores;
    }

    private function indiceColumnaExcel(string $letras): int
    {
        $resultado = 0;

        foreach (str_split(strtoupper($letras)) as $letra) {
            $resultado = ($resultado * 26) + (ord($letra) - 64);
        }

        return $resultado - 1;
    }

    private function eliminarDirectorioTemporal(string $directorio): void
    {
        if (!is_dir($directorio)) {
            return;
        }

        $archivos = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $directorio,
                \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($archivos as $archivo) {
            if ($archivo->isDir()) {
                @rmdir($archivo->getPathname());
            } else {
                @unlink($archivo->getPathname());
            }
        }

        @rmdir($directorio);
    }

    public function collection(Collection $rows)
    {
        @set_time_limit(0);
        DB::disableQueryLog();

        $this->inicializarIndices();

        foreach ($rows as $index => $row) {
            $this->procesadas++;

            try {
                // Primero hacemos la comparación más barata. En un ENVIOS de
                // decenas de miles de filas, la mayoría no pertenece a una
                // redistribución activa y se descarta sin convertir fechas.
                $origen = (int) $this->get($row, ['cod_sucursal_salida', 'cod sucursal salida']);
                $destino = (int) $this->get($row, ['cod_sucursal', 'cod sucursal']);
                $codigo = $this->normalizarCodigo($this->get($row, ['cod_articulo', 'cod articulo']));

                if (!$origen || !$destino || $codigo === '') {
                    $this->omitidas++;
                    continue;
                }

                $clave = $this->claveDetalle($origen, $destino, $codigo);
                $candidatos = $this->detallesPorClave->get($clave, collect());

                if ($candidatos->isEmpty()) {
                    $this->sinCoincidencia++;
                    continue;
                }

                $serie = trim((string) $this->get($row, ['serie']));
                $numero = (int) $this->get($row, ['numero_remision', 'numero remision']);
                $cantidad = $this->parseQuantity($this->get($row, ['cantidad']));

                if ($serie === '' || !$numero || $cantidad <= 0) {
                    $this->omitidas++;
                    continue;
                }

                $fechaRemision = $this->parseDate($this->get($row, ['fecha_remision', 'fecha remision']));
                $fechaCreacion = $this->parseDate($this->get($row, ['fecha_creacion', 'fecha creacion']));
                $fechaRecepcion = $this->parseDate($this->get($row, ['fecha_recepcion', 'fecha recepcion']));

                $this->coincidentes++;

                /*
                 * MISMA REGLA DEL IMPORTADOR ORIGINAL:
                 * 1) primero localizar la misma remisión existente;
                 * 2) si no existe, primer detalle con saldo suficiente;
                 * 3) si no, primer detalle con cualquier saldo pendiente.
                 */
                $detalle = null;
                $remisionExistente = null;

                foreach ($candidatos as $candidato) {
                    $docKey = $this->claveDocumento(
                        (int) $candidato->id,
                        $serie,
                        $numero
                    );

                    if (isset($this->remisionesPorDocumento[$docKey])) {
                        $detalle = $candidato;
                        $remisionExistente = $this->remisionesPorDocumento[$docKey];
                        break;
                    }
                }

                if (!$detalle) {
                    foreach ($candidatos as $candidato) {
                        $pendiente = (int) $candidato->cantidad
                            - (int) ($this->transferidoPorDetalle[(int) $candidato->id] ?? 0);

                        if ($pendiente >= $cantidad) {
                            $detalle = $candidato;
                            break;
                        }
                    }
                }

                if (!$detalle) {
                    foreach ($candidatos as $candidato) {
                        $pendiente = (int) $candidato->cantidad
                            - (int) ($this->transferidoPorDetalle[(int) $candidato->id] ?? 0);

                        if ($pendiente > 0) {
                            $detalle = $candidato;
                            break;
                        }
                    }
                }

                if (!$detalle) {
                    $this->sinSaldo++;
                    continue;
                }

                DB::transaction(function () use (
                    $detalle,
                    $remisionExistente,
                    $serie,
                    $numero,
                    $cantidad,
                    $fechaRemision,
                    $fechaCreacion,
                    $fechaRecepcion
                ) {
                    $idDetalle = (int) $detalle->id;
                    $docKey = $this->claveDocumento($idDetalle, $serie, $numero);

                    if ($remisionExistente) {
                        $cambios = [];

                        if ($fechaRemision) {
                            $cambios['fecha_remision'] = $fechaRemision;
                        }

                        if ($fechaCreacion) {
                            $cambios['fecha_creacion'] = $fechaCreacion;
                        }

                        if ($fechaRecepcion) {
                            $cambios['fecha_recepcion'] = $fechaRecepcion;
                        }

                        if (!empty($cambios)) {
                            $cambios['updated_at'] = now();

                            DB::table('redistribucion_remision')
                                ->where('id', $remisionExistente->id)
                                ->update($cambios);

                            foreach ($cambios as $campo => $valor) {
                                if ($campo !== 'updated_at') {
                                    $remisionExistente->{$campo} = $valor;
                                }
                            }
                        }

                        $this->actualizadas++;
                    } else {
                        $idRemision = DB::table('redistribucion_remision')->insertGetId([
                            'detalle_id' => $idDetalle,
                            'serie' => $serie,
                            'numero_remision' => $numero,
                            'fecha_remision' => $fechaRemision,
                            'fecha_creacion' => $fechaCreacion,
                            'fecha_recepcion' => $fechaRecepcion,
                            'cantidad_transferida' => $cantidad,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $nueva = (object) [
                            'id' => $idRemision,
                            'detalle_id' => $idDetalle,
                            'serie' => $serie,
                            'numero_remision' => $numero,
                            'cantidad_transferida' => $cantidad,
                            'fecha_remision' => $fechaRemision,
                            'fecha_creacion' => $fechaCreacion,
                            'fecha_recepcion' => $fechaRecepcion,
                        ];

                        $this->remisionesPorDocumento[$docKey] = $nueva;
                        $this->transferidoPorDetalle[$idDetalle] =
                            (int) ($this->transferidoPorDetalle[$idDetalle] ?? 0) + $cantidad;

                        $this->insertadas++;
                    }

                    $totalTransferido = (int) ($this->transferidoPorDetalle[$idDetalle] ?? 0);
                    $cantidadPedida = (int) $detalle->cantidad;
                    $diferencia = $cantidadPedida - $totalTransferido;

                    if ($totalTransferido > $cantidadPedida) {
                        $resultado = 'EXCEDENTE';
                    } elseif ($totalTransferido === $cantidadPedida) {
                        $resultado = 'COMPLETO';
                    } elseif ($totalTransferido > 0) {
                        $resultado = 'PARCIAL';
                    } else {
                        $resultado = 'PENDIENTE';
                    }

                    $fechaRemisionFinal = $fechaRemision ?: $detalle->fecha_remision;
                    $fechaRecepcionFinal = $fechaRecepcion ?: $detalle->fecha_recepcion;

                    $datosDetalle = [
                        'observacion' => 'Pedido: ' . $cantidadPedida
                            . ' | Transferido: ' . $totalTransferido
                            . ' | Diferencia: ' . $diferencia
                            . ' | Resultado: ' . $resultado,
                    ];

                    if ($fechaRemisionFinal) {
                        $datosDetalle['fecha_remision'] = $fechaRemisionFinal;
                    }

                    if ($fechaRecepcionFinal) {
                        $datosDetalle['fecha_recepcion'] = $fechaRecepcionFinal;
                    }

                    if ($totalTransferido > 0) {
                        $datosDetalle['estado'] = $fechaRecepcionFinal
                            ? 'FINALIZADO'
                            : 'REALIZADO';
                    }

                    DB::table('redistribucion_proceso_detalle')
                        ->where('id', $idDetalle)
                        ->update($datosDetalle);

                    foreach ($datosDetalle as $campo => $valor) {
                        $detalle->{$campo} = $valor;
                    }
                });
            } catch (\Throwable $e) {
                $this->errores++;

                Log::error('ERROR IMPORT REDISTRIBUCION', [
                    'fila_chunk' => $index,
                    'error' => $e->getMessage(),
                    'archivo' => $e->getFile(),
                    'linea' => $e->getLine(),
                ]);
            }
        }
    }

    private function inicializarIndices(): void
    {
        if ($this->inicializado) {
            return;
        }

        /*
         * El importador anterior consultaba la BD por cada línea del Excel.
         * Con ENVIOS de 80.000+ líneas eso hace decenas de miles de SELECT.
         *
         * Precargamos los mismos datos una sola vez, conservando exactamente
         * el orden por id usado por el flujo original.
         */
        $detalles = RedistribucionProcesoDetalle::query()
            ->select(
                'id',
                'codigo',
                'sucursal_origen',
                'sucursal_destino',
                'cantidad',
                'estado',
                'fecha_remision',
                'fecha_recepcion'
            )
            ->orderBy('id')
            ->get();

        $this->detallesPorClave = $detalles->groupBy(function ($detalle) {
            return $this->claveDetalle(
                (int) $detalle->sucursal_origen,
                (int) $detalle->sucursal_destino,
                $this->normalizarCodigo($detalle->codigo)
            );
        });

        $ids = $detalles->pluck('id')->values();
        $remisiones = collect();

        foreach ($ids->chunk(1000) as $bloque) {
            $remisiones = $remisiones->concat(
                RedistribucionRemision::whereIn('detalle_id', $bloque->all())
                    ->orderBy('id')
                    ->get()
            );
        }

        foreach ($remisiones as $remision) {
            $idDetalle = (int) $remision->detalle_id;

            $this->transferidoPorDetalle[$idDetalle] =
                (int) ($this->transferidoPorDetalle[$idDetalle] ?? 0)
                + (int) $remision->cantidad_transferida;

            $this->remisionesPorDocumento[
                $this->claveDocumento(
                    $idDetalle,
                    (string) $remision->serie,
                    (int) $remision->numero_remision
                )
            ] = $remision;
        }

        $this->inicializado = true;
    }

    private function claveDetalle(int $origen, int $destino, string $codigo): string
    {
        return $origen . '|' . $destino . '|' . $codigo;
    }

    private function claveDocumento(int $detalleId, string $serie, int $numero): string
    {
        return $detalleId . '|' . trim($serie) . '|' . $numero;
    }

    private function normalizarCodigo($valor): string
    {
        $codigo = strtoupper(trim((string) $valor));
        $codigo = ltrim($codigo, "'’\`");
        $codigo = preg_replace('/[\\s\\x{00A0}\\x{2007}\\x{202F}]+/u', '', $codigo);

        return $codigo ?: '';
    }

    private function get($row, array $keys)
    {
        if ($row instanceof Collection) {
            foreach ($keys as $key) {
                if ($row->has($key) && trim((string) $row->get($key)) !== '') {
                    return $row->get($key);
                }
            }

            return null;
        }

        if (is_object($row) && method_exists($row, 'toArray')) {
            $row = $row->toArray();
        }

        foreach ($keys as $key) {
            if (
                is_array($row)
                && array_key_exists($key, $row)
                && trim((string) $row[$key]) !== ''
            ) {
                return $row[$key];
            }
        }

        return null;
    }

    private function parseQuantity($valor): int
    {
        if ($valor === null || $valor === '') {
            return 0;
        }

        if (is_numeric($valor)) {
            return (int) round((float) $valor);
        }

        $texto = str_replace(' ', '', trim((string) $valor));

        if (strpos($texto, ',') !== false && strpos($texto, '.') !== false) {
            $texto = str_replace('.', '', $texto);
            $texto = str_replace(',', '.', $texto);
        } elseif (strpos($texto, ',') !== false) {
            $texto = str_replace(',', '.', $texto);
        }

        return is_numeric($texto)
            ? (int) round((float) $texto)
            : 0;
    }

    private function parseDate($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if ($valor instanceof \DateTimeInterface) {
            return Carbon::instance($valor)->format('Y-m-d');
        }

        if (is_numeric($valor)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valor)
                    ->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $texto = trim((string) $valor);

        $meses = [
            'ene' => 'Jan', 'enero' => 'January',
            'feb' => 'Feb', 'febrero' => 'February',
            'mar' => 'Mar', 'marzo' => 'March',
            'abr' => 'Apr', 'abril' => 'April',
            'may' => 'May', 'mayo' => 'May',
            'jun' => 'Jun', 'junio' => 'June',
            'jul' => 'Jul', 'julio' => 'July',
            'ago' => 'Aug', 'agosto' => 'August',
            'sep' => 'Sep', 'sept' => 'Sep', 'septiembre' => 'September',
            'oct' => 'Oct', 'octubre' => 'October',
            'nov' => 'Nov', 'noviembre' => 'November',
            'dic' => 'Dec', 'diciembre' => 'December',
        ];

        $normalizado = strtolower($texto);

        foreach ($meses as $es => $en) {
            $normalizado = preg_replace(
                '/\\b' . preg_quote($es, '/') . '\\b/u',
                $en,
                $normalizado
            );
        }

        foreach (['d-M-y', 'd/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y'] as $formato) {
            try {
                $fecha = Carbon::createFromFormat($formato, $normalizado);

                if ($fecha !== false) {
                    return $fecha->format('Y-m-d');
                }
            } catch (\Throwable $e) {
            }
        }

        try {
            return Carbon::parse($normalizado)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getProcesadas(): int
    {
        return $this->procesadas;
    }

    public function getCoincidentes(): int
    {
        return $this->coincidentes;
    }

    public function getInsertadas(): int
    {
        return $this->insertadas;
    }

    public function getActualizadas(): int
    {
        return $this->actualizadas;
    }

    public function getSinCoincidencia(): int
    {
        return $this->sinCoincidencia;
    }

    public function getSinSaldo(): int
    {
        return $this->sinSaldo;
    }

    public function getOmitidas(): int
    {
        return $this->omitidas;
    }

    public function getErrores(): int
    {
        return $this->errores;
    }

    public function chunkSize(): int
    {
        return 1500;
    }
}
