<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ControlTerminacionRemisionImport implements ToCollection, WithHeadingRow, WithChunkReading, SkipsEmptyRows
{
    private $procesadas = 0;
    private $insertadas = 0;
    private $actualizadas = 0;
    private $sinCambios = 0;
    private $cantidadesCorregidas = 0;
    private $duplicadas = 0;
    private $leidas = 0;
    private $total = null;
    private $vinculadas = 0;
    private $vinculadasOt = 0;
    private $sinVincular = 0;
    private $sinOt = 0;
    private $omitidas = 0;

    private $documentosEstado = [];

    private $usoDetalle = [];
    private $asignadoImportacion = [];
    private $catalogoSucursales;
    private $conAccesorios;
    private $onProgreso;
    private $inicio;

    public function __construct(?callable $onProgreso = null)
    {
        $this->onProgreso = $onProgreso;
        $this->inicio = microtime(true);
        $this->notificar('PREPARANDO', 'Preparando archivo de remisiones...');
    }

    /** Lee CSV en una sola pasada, sin recargar el archivo por cada bloque. */
    public function importarCsvStreaming(string $ruta): void
    {
        $archivo = fopen($ruta, 'rb');
        if (!$archivo) {
            throw new \RuntimeException('No se pudo abrir el archivo CSV.');
        }
        try {
            $primera = '';
            while (($linea = fgets($archivo)) !== false) {
                $primera = preg_replace('/^\xEF\xBB\xBF/', '', $this->textoCsv($linea));
                if (trim($primera) !== '') break;
            }
            $delimitador = ',';
            if (preg_match('/^sep=([;,|\t])\s*$/i', trim($primera), $configuracion)) {
                $delimitador = $configuracion[1];
                $primera = $this->textoCsv((string) fgets($archivo));
            } else {
                $columnas = 0;
                foreach ([',', ';', "\t", '|'] as $candidato) {
                    $cantidad = count(str_getcsv($primera, $candidato, '"', ''));
                    if ($cantidad > $columnas) {
                        $columnas = $cantidad;
                        $delimitador = $candidato;
                    }
                }
            }
            $encabezados = array_map(fn ($valor) => Str::slug(trim((string) $valor), '_'),
                str_getcsv($primera, $delimitador, '"', ''));
            $this->validarEncabezados($encabezados);
            $this->notificar('PROCESANDO', 'Leyendo remisiones del CSV...');
            $lote = [];
            while (($valores = fgetcsv($archivo, 0, $delimitador, '"', '')) !== false) {
                $fila = [];
                foreach ($encabezados as $indice => $encabezado) {
                    if ($encabezado !== '') {
                        $fila[$encabezado] = isset($valores[$indice]) ? $this->textoCsv($valores[$indice]) : null;
                    }
                }
                $lote[] = $fila;
                if (count($lote) >= 2000) {
                    $this->collection(collect($lote));
                    $lote = [];
                }
            }
            if ($lote) $this->collection(collect($lote));
        } finally {
            fclose($archivo);
        }
    }

    private function textoCsv(string $texto): string
    {
        return mb_check_encoding($texto, 'UTF-8') ? $texto : mb_convert_encoding($texto, 'UTF-8', 'Windows-1252');
    }

    /**
     * Importador XLSX de bajo consumo.
     *
     * PhpSpreadsheet crea un objeto por celda y un archivo con decenas de miles
     * de filas puede superar fácilmente 512 MB. Para ENVIOS leemos directamente
     * el XML del XLSX y entregamos bloques pequeños al mismo motor de negocio.
     */
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

        $temporalBase = tempnam(sys_get_temp_dir(), 'envios_xlsx_');

        if ($temporalBase === false) {
            $zip->close();
            throw new \RuntimeException('No se pudo crear el archivo temporal para procesar el XLSX.');
        }

        @unlink($temporalBase);
        $directorioTemporal = $temporalBase . '_dir';

        if (!@mkdir($directorioTemporal, 0777, true) && !is_dir($directorioTemporal)) {
            $zip->close();
            throw new \RuntimeException('No se pudo crear el directorio temporal para procesar el XLSX.');
        }

        $reader = null;

        try {
            $sharedStrings = $this->leerSharedStrings($zip);

            $hojaInterna = 'xl/worksheets/sheet1.xml';

            if ($zip->locateName($hojaInterna) === false) {
                throw new \RuntimeException('El XLSX no contiene la hoja principal esperada.');
            }

            if (!$zip->extractTo($directorioTemporal, [$hojaInterna])) {
                throw new \RuntimeException('No se pudo extraer la hoja del XLSX para lectura incremental.');
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
            $tamanoLote = 2000;

            while ($reader->read()) {
                if ($reader->nodeType === \XMLReader::ELEMENT && $reader->localName === 'dimension') {
                    if (preg_match('/[A-Z]+(\d+)$/i', (string) $reader->getAttribute('ref'), $dimension)) {
                        $this->total = max(0, (int) $dimension[1] - 1);
                    }
                    $this->notificar('PROCESANDO', 'Leyendo remisiones del Excel...');
                }

                if ($reader->nodeType !== \XMLReader::ELEMENT || $reader->localName !== 'row') {
                    continue;
                }

                $xmlFila = $reader->readOuterXML();

                if ($xmlFila === '') {
                    continue;
                }

                $valores = $this->leerFilaXml($xmlFila, $sharedStrings);

                if (empty($encabezados)) {
                    if (empty(array_filter($valores, fn ($valor) => trim((string) $valor) !== ''))) {
                        continue;
                    }
                    foreach ($valores as $indice => $valor) {
                        $encabezados[$indice] = Str::slug(
                            trim((string) $valor),
                            '_'
                        );
                    }

                    $this->validarEncabezados($encabezados);

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
                    $filasLote = collect($lote);

                    $this->collection($filasLote);

                    $lote = [];
                    unset($filasLote);
                    gc_collect_cycles();
                }
            }

            $reader->close();
            $reader = null;

            if (!empty($lote)) {
                $filasLote = collect($lote);

                $this->collection($filasLote);

                $lote = [];
                unset($filasLote);
                gc_collect_cycles();
            }
        } finally {
            if ($reader) {
                $reader->close();
            }
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

            $referencia = null;
            $tipo = null;

            if (preg_match('/\\br="([A-Z]+)(\\d+)"/i', $atributos, $mReferencia)) {
                $referencia = strtoupper($mReferencia[1]);
            }

            if ($referencia === null) {
                continue;
            }

            if (preg_match('/\\bt="([^"]+)"/i', $atributos, $mTipo)) {
                $tipo = $mTipo[1];
            }

            $indiceColumna = $this->indiceColumnaExcel($referencia);
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
                    $indiceString = (int) $valorCrudo;
                    $valor = $sharedStrings[$indiceString] ?? '';
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
        set_time_limit(0);
        DB::disableQueryLog();

        if ($rows->isEmpty()) {
            return;
        }
        $this->validarEncabezados(array_keys((array) ($rows->first() instanceof Collection
            ? $rows->first()->all() : $rows->first())));

        // Cada bloque se confirma completo. Si falla un lote de escritura,
        // ni los datos ni los contadores de ese bloque quedan a medias.
        $contadores = [];
        foreach (['procesadas', 'insertadas', 'actualizadas', 'sinCambios', 'cantidadesCorregidas',
            'duplicadas', 'leidas', 'vinculadas', 'vinculadasOt', 'sinVincular', 'sinOt',
            'omitidas', 'documentosEstado'] as $propiedad) {
            $contadores[$propiedad] = $this->{$propiedad};
        }
        try {
            DB::transaction(function () use ($rows) {
                $this->procesarBloque($rows);
            });
        } catch (\Throwable $e) {
            foreach ($contadores as $propiedad => $valor) {
                $this->{$propiedad} = $valor;
            }
            throw $e;
        }
        $this->notificar('PROCESANDO', 'Actualizando cantidades y recepción de remisiones...');
    }

    private function procesarBloque(Collection $rows): void
    {
        $filas = $this->normalizarFilas($rows);

        if ($filas->isEmpty()) {
            return;
        }

        $existentes = $this->precargarExistentes($filas);

        // En una reimportación no volvemos a consultar todo el historial de
        // las OT que ya tienen vínculo. Las pendientes sí pueden repararse.
        $porVincular = $filas->filter(function ($fila) use ($existentes) {
            $existente = $existentes->get($this->claveFila($fila));
            return $fila['cantidad'] > 0 && empty($existente->id_logistica_detalle);
        });
        $sinOt = $filas->filter(function ($fila) use ($existentes) {
            $existente = $existentes->get($this->claveFila($fila));
            return $fila['cantidad'] > 0 && empty($existente->id_ot);
        });
        $vinculos = $this->precargarVinculos($porVincular);
        $otsPorCodigo = $this->precargarOtsPorCodigo($sinOt);

        // Un accesorio se vincula por código, sin inventar el FCO de origen.
        if ($this->conAccesorios === null) {
            $this->conAccesorios = Schema::hasTable('accesorios_articulos')
                && Schema::hasColumn('ot_logistica_remisiones', 'accesorio_articulo_id');
        }
        $conAccesorios = $this->conAccesorios;
        $idsAccesorios = $conAccesorios
            ? DB::table('accesorios_articulos')
                ->whereIn('codigo', $filas->pluck('codigo')->unique()->all())
                ->pluck('id', 'codigo')
            : collect();
        $this->usoDetalle = $this->precargarUsoDetalles($vinculos);

        // Aplicar primero TODAS las correcciones de cantidades ya vinculadas.
        // Así el saldo liberado de 5 a 4 está disponible para las demás filas
        // del bloque, independientemente del orden del archivo.
        foreach ($filas as $fila) {
            $existente = $existentes->get($this->claveFila($fila));
            if ($existente && $existente->id_logistica_detalle) {
                $id = $existente->id_logistica_detalle;
                $this->usoDetalle[$id] = ($this->usoDetalle[$id] ?? 0)
                    + (int) $fila['cantidad'] - (int) $existente->cantidad;
            }
        }

        // El uso ya persistido se leyó de BD. Este acumulador solo corresponde
        // a las filas todavía no grabadas del bloque actual.
        $this->asignadoImportacion = [];

        $ahora = now();
        $registros = [];

        foreach ($filas as $fila) {
            $clave = $this->claveFila($fila);
            $existente = $existentes->get($clave);

            // Un cero explícito puede corregir una línea existente. No se
            // crea un movimiento nuevo sin prendas ni se interpreta vacío
            // o texto inválido como una cantidad cero.
            if (!$existente && $fila['cantidad'] === 0) {
                $this->omitidas++;
                continue;
            }
            $this->procesadas++;

            /*
             * Un vínculo ya resuelto se conserva. Solo intentamos reparar filas
             * nuevas o registros históricos que quedaron sin id_logistica_detalle.
             */
            $idOt = $existente->id_ot ?? null;
            $idTrazabilidad = $existente->id_trazabilidad ?? null;
            $idLogisticaDetalle = $existente->id_logistica_detalle ?? null;

            if (!$idLogisticaDetalle && $fila['cantidad'] > 0) {
                $vinculo = $this->resolverVinculoEnMemoria(
                    $vinculos,
                    $fila['codigo'],
                    $fila['sucursal_logistica'],
                    $fila['cantidad'],
                    $fila['fecha_remision'] ?: $fila['fecha_creacion']
                );

                if ($vinculo) {
                    $idOt = $vinculo->id_ot;
                    $idTrazabilidad = $vinculo->id_trazabilidad;
                    $idLogisticaDetalle = $vinculo->id_logistica_detalle;

                    $this->asignadoImportacion[$idLogisticaDetalle] =
                        ($this->asignadoImportacion[$idLogisticaDetalle] ?? 0)
                        + (int) $fila['cantidad'];
                }
            }

            /*
            |--------------------------------------------------------------------------
            | CONSERVAR FECHAS YA CARGADAS
            |--------------------------------------------------------------------------
            */
            $fechaRemision = $fila['fecha_remision']
                ?: ($existente->fecha_remision ?? null);

            $fechaCreacion = $fila['fecha_creacion']
                ?: ($existente->fecha_creacion ?? null);

            $fechaRecepcion = $fila['fecha_recepcion']
                ?: ($existente->fecha_recepcion ?? null);
            $this->registrarDocumento($fila, $fechaRecepcion);

            if (!$idOt) {
                $otFallback = $this->resolverOtEnMemoria(
                    $otsPorCodigo,
                    $fila['codigo'],
                    $fila['fecha_remision'] ?: $fila['fecha_creacion']
                );

                if ($otFallback) {
                    $idOt = $otFallback->id_ot;
                }
            }

            if ($idLogisticaDetalle) {
                $this->vinculadas++;
            } else {
                $this->sinVincular++;
            }

            if ($idOt) {
                $this->vinculadasOt++;
            } else {
                $this->sinOt++;
            }

            $registro = [
                'id_ot' => $idOt,
                'id_trazabilidad' => $idTrazabilidad,
                'id_logistica_detalle' => $idLogisticaDetalle,

                'fecha_remision' => $fechaRemision,
                'fecha_creacion' => $fechaCreacion,
                'fecha_recepcion' => $fechaRecepcion,

                'cod_sucursal_salida' => $fila['cod_sucursal_salida'],
                'sucursal_salida' => $fila['sucursal_salida'],

                'cod_sucursal_destino' => $fila['cod_sucursal_destino'],
                'sucursal_destino' => $fila['sucursal_destino'],
                'sucursal_logistica' => $fila['sucursal_logistica'],

                'serie' => $fila['serie'],
                'numero_remision' => $fila['numero_remision'],

                'codigo' => $fila['codigo'],
                'descripcion' => $fila['descripcion'],

                'cantidad' => $fila['cantidad'],
                'precio_venta' => $fila['precio_venta'],
                'costo_unitario' => $fila['costo_unitario'],

                'estado' => $fechaRecepcion ? 'RECIBIDO' : 'EN_TRANSITO',

                'created_at' => $existente->created_at ?? $ahora,
                'updated_at' => $ahora,
            ];

            if ($conAccesorios) {
                $registro['accesorio_articulo_id'] = $idsAccesorios->get(
                    strtoupper(trim($fila['codigo']))
                ) ?: ($existente->accesorio_articulo_id ?? null);
            }

            if (!$existente) {
                $this->insertadas++;
                $registros[] = $registro;
            } elseif ($this->tieneCambios($existente, $registro)) {
                $this->actualizadas++;
                if ((int) $existente->cantidad !== $fila['cantidad']) {
                    $this->cantidadesCorregidas++;
                }
                $registros[] = $registro;
            } else {
                $this->sinCambios++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | UPSERT POR LOTES
        |--------------------------------------------------------------------------
        |
        | 200 filas por sentencia mantiene bajo el número de parámetros
        | enviados a PostgreSQL 9.5 y sigue siendo muy rápido.
        |
        */
        $columnasUpdate = [
            'id_ot', 'id_trazabilidad', 'id_logistica_detalle',
            'fecha_remision', 'fecha_creacion', 'fecha_recepcion',
            'sucursal_salida', 'sucursal_destino', 'sucursal_logistica',
            'descripcion', 'cantidad', 'precio_venta', 'costo_unitario',
            'estado', 'updated_at',
        ];
        if ($conAccesorios) {
            $columnasUpdate[] = 'accesorio_articulo_id';
        }

        foreach (array_chunk($registros, 200) as $lote) {
            DB::table('ot_logistica_remisiones')->upsert(
                $lote,
                [
                    'serie',
                    'numero_remision',
                    'codigo',
                    'cod_sucursal_salida',
                    'cod_sucursal_destino',
                ],
                $columnasUpdate
            );
        }
    }

    private function normalizarFilas(Collection $rows)
    {
        $filas = collect();
        if ($this->catalogoSucursales === null) {
            $this->catalogoSucursales = DB::table('sucursal')->pluck('suc_descri', 'cod_suc');
        }

        foreach ($rows as $row) {
            if (collect($row)->every(fn ($valor) => trim((string) $valor) === '')) {
                continue;
            }
            $this->leidas++;
            $codigo = $this->normalizarCodigoCompleto($row['cod_articulo'] ?? null);
            $serie = trim((string) ($row['serie'] ?? ''));
            $numeroRemision = trim((string) ($row['numero_remision'] ?? ''));
            $codSalida = $this->enteroONull($row['cod_sucursal_salida'] ?? null);
            $codDestino = $this->enteroONull($row['cod_sucursal'] ?? null);
            $cantidad = $this->parseQuantity($row['cantidad'] ?? null);

            if (
                $codigo === '' ||
                $serie === '' ||
                $numeroRemision === '' ||
                !$codSalida ||
                !$codDestino ||
                $cantidad === null || $cantidad < 0
            ) {
                $this->omitidas++;
                continue;
            }

            $sucursalSalida = trim((string) ($row['sucursal_salida'] ?? ''));
            $sucursalDestino = trim((string) ($row['sucursal'] ?? ''));
            $nombreCatalogo = $this->catalogoSucursales->get($codDestino);

            $filas->push([
                'codigo' => $codigo,
                'serie' => $serie,
                'numero_remision' => $numeroRemision,
                'cod_sucursal_salida' => $codSalida,
                'cod_sucursal_destino' => $codDestino,
                'cantidad' => $cantidad,

                'fecha_remision' => $this->parseDate($row['fecha_remision'] ?? null),
                'fecha_creacion' => $this->parseDate($row['fecha_creacion'] ?? null),
                'fecha_recepcion' => $this->parseDate($row['fecha_recepcion'] ?? null),

                'sucursal_salida' => $sucursalSalida !== '' ? $sucursalSalida : null,
                'sucursal_destino' => $sucursalDestino !== '' ? $sucursalDestino : null,
                'sucursal_logistica' => $this->resolverSucursalImportada(
                    $codDestino,
                    $sucursalDestino,
                    $nombreCatalogo
                ),

                // El archivo actual tiene el encabezado "Artiuclo".
                'descripcion' => trim((string) (
                    $row['artiuclo']
                    ?? $row['articulo']
                    ?? ''
                )) ?: null,

                'precio_venta' => $this->decimalONull($row['precioventa'] ?? null),
                'costo_unitario' => $this->decimalONull($row['costounitario'] ?? null),
            ]);
        }

        // Ante una línea repetida prevalece el último valor del bloque.
        // Evita tanto ignorar una corrección como repetir una clave en upsert.
        $unicas = $filas->keyBy(fn ($fila) => $this->claveFila($fila));
        $this->duplicadas += $filas->count() - $unicas->count();
        return $unicas->values();
    }

    private function validarEncabezados(array $encabezados): void
    {
        $faltantes = array_diff([
            'cod_articulo', 'serie', 'numero_remision', 'cod_sucursal_salida', 'cod_sucursal', 'cantidad',
        ], $encabezados);
        if ($faltantes) {
            throw new \InvalidArgumentException('El archivo ENVIOS no tiene las columnas requeridas: '
                . implode(', ', $faltantes) . '.');
        }
    }

    private function claveFila(array $fila): string
    {
        return $this->clave($fila['serie'], $fila['numero_remision'], $fila['codigo'],
            $fila['cod_sucursal_salida'], $fila['cod_sucursal_destino']);
    }

    private function tieneCambios(object $existente, array $registro): bool
    {
        foreach ($registro as $columna => $nuevo) {
            if (in_array($columna, ['created_at', 'updated_at'], true)) {
                continue;
            }
            $anterior = $existente->{$columna} ?? null;
            if ($anterior === null || $nuevo === null) {
                if ($anterior !== $nuevo) {
                    return true;
                }
            } elseif (in_array($columna, ['precio_venta', 'costo_unitario'], true)) {
                if (round((float) $anterior, 2) !== round((float) $nuevo, 2)) {
                    return true;
                }
            } elseif ((string) $anterior !== (string) $nuevo) {
                return true;
            }
        }
        return false;
    }

    private function precargarVinculos(Collection $filas)
    {
        $codigos = $filas
            ->pluck('codigo')
            ->filter()
            ->map(function ($codigo) {
                return $this->normalizarCodigoBase($codigo);
            })
            ->unique()
            ->values();

        if ($codigos->isEmpty()) {
            return collect();
        }

        /*
         * Consultamos únicamente los códigos base presentes en ESTE bloque.
         * Ejemplo: 050616220VD04 (ENVIOS) -> 050616220 (OT).
         *
         * Se hacen lotes de 400 códigos para no generar un IN gigante en
         * PostgreSQL 9.5.
         */
        $candidatos = collect();

        foreach ($codigos->chunk(400) as $bloqueCodigos) {
            $parcial = DB::table('ot_logistica_detalle as d')
                ->join('ot as o', 'o.id_ot', '=', 'd.id_ot')
                ->join('ot_trazabilidad as t', 't.id_trazabilidad', '=', 'd.id_trazabilidad')
                ->whereIn(
                    DB::raw("UPPER(REPLACE(TRIM(o.codigo), '''', ''))"),
                    $bloqueCodigos->all()
                )
                ->whereRaw("UPPER(TRIM(t.proceso)) LIKE '%LOGISTICA%'")
                ->whereRaw("UPPER(TRIM(t.proceso)) LIKE '%DISTRIBUCION%'")
                ->select(
                    'd.id as id_logistica_detalle',
                    'd.id_ot',
                    'd.id_trazabilidad',
                    'd.sucursal',
                    'd.cantidad',
                    'o.nro_ot',
                    'o.codigo',
                    'o.descripcion',
                    't.fecha_proceso'
                )
                ->orderBy('t.fecha_proceso', 'desc')
                ->orderBy('d.id', 'desc')
                ->get();

            $candidatos = $candidatos->concat($parcial);
        }

        return $candidatos
            ->map(function ($item) {
                $item->codigo_normalizado = $this->normalizarCodigoBase($item->codigo);
                $item->sucursal_normalizada = $this->normalizarSucursalBase($item->sucursal);
                return $item;
            })
            ->filter(function ($item) {
                return $item->codigo_normalizado !== ''
                    && !empty($item->sucursal_normalizada);
            })
            ->groupBy(function ($item) {
                return $this->claveVinculo(
                    $item->codigo_normalizado,
                    $item->sucursal_normalizada
                );
            });
    }

    private function precargarOtsPorCodigo(Collection $filas)
    {
        $codigos = $filas
            ->pluck('codigo')
            ->filter()
            ->map(function ($codigo) {
                return $this->normalizarCodigoBase($codigo);
            })
            ->unique()
            ->values();

        if ($codigos->isEmpty()) {
            return collect();
        }

        $ots = collect();

        foreach ($codigos->chunk(400) as $bloqueCodigos) {
            $parcial = DB::table('ot as o')
                ->leftJoin('ot_trazabilidad as pt', function ($join) {
                    $join->on('pt.id_ot', '=', 'o.id_ot')
                        ->where('pt.proceso', '=', 'TERMINACION - PRODUCTO TERMINADO');
                })
                ->whereIn(
                    DB::raw("UPPER(REPLACE(TRIM(o.codigo), '''', ''))"),
                    $bloqueCodigos->all()
                )
                ->select(
                    'o.id_ot',
                    'o.nro_ot',
                    'o.codigo',
                    'o.descripcion',
                    'pt.id_trazabilidad as id_producto_terminado',
                    'pt.fecha_proceso as fecha_producto_terminado',
                    'pt.resultado as cantidad_producto_terminado'
                )
                ->orderBy('pt.fecha_proceso', 'desc')
                ->orderBy('o.id_ot', 'desc')
                ->get();

            $ots = $ots->concat($parcial);
        }

        return $ots
            ->map(function ($item) {
                $item->codigo_normalizado = $this->normalizarCodigoBase($item->codigo);
                return $item;
            })
            ->filter(function ($item) {
                return $item->codigo_normalizado !== '';
            })
            ->groupBy('codigo_normalizado');
    }

    private function resolverOtEnMemoria(
        Collection $otsPorCodigo,
        $codigo,
        $fechaReferencia
    ) {
        $codigoNormalizado = $this->normalizarCodigoBase($codigo);
        $candidatos = collect($otsPorCodigo->get($codigoNormalizado, collect()));

        if ($candidatos->isEmpty()) {
            return null;
        }

        /*
         * Un mismo id_ot puede aparecer varias veces porque una OT puede tener
         * más de un movimiento PRODUCTO TERMINADO. Si todas las filas
         * candidatas pertenecen a la misma OT, la asociación es inequívoca y
         * no debe depender de que exista Logística.
         */
        $idsOtUnicos = $candidatos
            ->pluck('id_ot')
            ->filter()
            ->unique()
            ->values();

        if ($idsOtUnicos->count() === 1) {
            return $candidatos
                ->sortByDesc(function ($item) {
                    return (string) ($item->fecha_producto_terminado ?? '');
                })
                ->first();
        }

        $fecha = null;

        if ($fechaReferencia) {
            try {
                $fecha = Carbon::parse($fechaReferencia)->startOfDay();
            } catch (\Throwable $e) {
                $fecha = null;
            }
        }

        if (!$fecha) {
            return $candidatos
                ->sortByDesc(function ($item) {
                    return (int) $item->id_ot;
                })
                ->first();
        }

        $anteriores = $candidatos
            ->filter(function ($item) use ($fecha) {
                if (empty($item->fecha_producto_terminado)) {
                    return false;
                }

                try {
                    return Carbon::parse($item->fecha_producto_terminado)
                        ->startOfDay()
                        ->lte($fecha);
                } catch (\Throwable $e) {
                    return false;
                }
            })
            ->sort(function ($a, $b) use ($fecha) {
                $fa = Carbon::parse($a->fecha_producto_terminado)->startOfDay();
                $fb = Carbon::parse($b->fecha_producto_terminado)->startOfDay();

                $da = $fa->diffInDays($fecha);
                $db = $fb->diffInDays($fecha);

                if ($da !== $db) {
                    return $da < $db ? -1 : 1;
                }

                return ((int) $b->id_ot) <=> ((int) $a->id_ot);
            })
            ->values();

        if ($anteriores->isNotEmpty()) {
            return $anteriores->first();
        }

        return null;
    }

    private function precargarUsoDetalles(Collection $vinculos)
    {
        $ids = $vinculos
            ->flatten(1)
            ->pluck('id_logistica_detalle')
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $uso = [];

        foreach ($ids->chunk(1000) as $bloque) {
            $totales = DB::table('ot_logistica_remisiones')
                ->whereIn('id_logistica_detalle', $bloque->all())
                ->select('id_logistica_detalle', DB::raw('SUM(cantidad) as total'))
                ->groupBy('id_logistica_detalle')
                ->get();

            foreach ($totales as $total) {
                $uso[(int) $total->id_logistica_detalle] = (int) $total->total;
            }
        }

        return $uso;
    }

    private function resolverVinculoEnMemoria(
        Collection $vinculos,
        $codigo,
        $sucursal,
        $cantidad,
        $fechaReferencia
    ) {
        if (!$sucursal) {
            return null;
        }

        $codigoBase = $this->normalizarCodigoBase($codigo);
        $sucursalesCandidatas = $this->resolverSucursalesCandidatas($sucursal);

        $candidatos = collect();

        foreach ($sucursalesCandidatas as $indice => $sucursalCandidata) {
            $clave = $this->claveVinculo(
                $codigoBase,
                $sucursalCandidata
            );

            $grupo = collect($vinculos->get($clave, collect()))
                ->map(function ($item) use ($indice, $sucursalCandidata) {
                    $item->_prioridad_destino = $indice;
                    $item->_destino_planificado_normalizado = $sucursalCandidata;
                    return $item;
                });

            $candidatos = $candidatos->concat($grupo);
        }

        if ($candidatos->isEmpty()) {
            return null;
        }

        $fecha = null;

        if ($fechaReferencia) {
            try {
                $fecha = Carbon::parse($fechaReferencia)->startOfDay();
            } catch (\Throwable $e) {
                $fecha = null;
            }
        }

        $evaluados = $candidatos->map(function ($item) use ($cantidad, $fecha) {
            $idDetalle = (int) $item->id_logistica_detalle;
            $usado = (int) ($this->usoDetalle[$idDetalle] ?? 0)
                + (int) ($this->asignadoImportacion[$idDetalle] ?? 0);

            $item->_disponible = max(0, (int) $item->cantidad - $usado);
            $item->_tiene_saldo = $item->_disponible >= (int) $cantidad ? 1 : 0;
            $item->_saldo_exacto = $item->_disponible === (int) $cantidad ? 1 : 0;
            $item->_cantidad_exacta = (int) $item->cantidad === (int) $cantidad ? 1 : 0;
            $item->_sobrante_despues = $item->_tiene_saldo
                ? $item->_disponible - (int) $cantidad
                : 999999;
            $item->_distancia_dias = 999999;
            $item->_posterior = 1;

            if ($fecha && !empty($item->fecha_proceso)) {
                try {
                    $fechaLogistica = Carbon::parse($item->fecha_proceso)->startOfDay();
                    $item->_distancia_dias = abs($fechaLogistica->diffInDays($fecha, false));
                    $item->_posterior = $fechaLogistica->gt($fecha) ? 1 : 0;
                } catch (\Throwable $e) {
                    // Mantener distancia alta.
                }
            }

            return $item;
        });

        if ($fecha) {
            $cercanos = $evaluados
                ->filter(function ($item) {
                    return $item->_distancia_dias <= 365;
                })
                ->values();

            if ($cercanos->isEmpty()) {
                return null;
            }

            $evaluados = $cercanos;
        }

        // Cuando COMERCIAL MATRIZ representa AYALA / MODELO MUESTRA,
        // respetamos primero la capacidad pendiente de cada detalle. Una línea
        // no puede seguir cargándose sobre AYALA después de completar su plan;
        // a partir de allí debe competir con MODELO u otro detalle con saldo.
        $conSaldo = $evaluados
            ->filter(function ($item) {
                return $item->_tiene_saldo === 1;
            })
            ->values();

        if ($conSaldo->isNotEmpty()) {
            $evaluados = $conSaldo;
        }

        return $evaluados
            ->sort(function ($a, $b) {
                if ($a->_distancia_dias !== $b->_distancia_dias) {
                    return $a->_distancia_dias < $b->_distancia_dias ? -1 : 1;
                }

                if ($a->_posterior !== $b->_posterior) {
                    return $a->_posterior < $b->_posterior ? -1 : 1;
                }

                if ($a->_tiene_saldo !== $b->_tiene_saldo) {
                    return $a->_tiene_saldo > $b->_tiene_saldo ? -1 : 1;
                }

                if ($a->_saldo_exacto !== $b->_saldo_exacto) {
                    return $a->_saldo_exacto > $b->_saldo_exacto ? -1 : 1;
                }

                // Para COMERCIAL MATRIZ pueden competir AYALA y MODELO.
                // Elegimos el detalle donde la línea deje el menor sobrante,
                // aprovechando mejor la cantidad planificada sin excederla.
                if ($a->_sobrante_despues !== $b->_sobrante_despues) {
                    return $a->_sobrante_despues < $b->_sobrante_despues ? -1 : 1;
                }

                if ($a->_cantidad_exacta !== $b->_cantidad_exacta) {
                    return $a->_cantidad_exacta > $b->_cantidad_exacta ? -1 : 1;
                }

                if (($a->_prioridad_destino ?? 999) !== ($b->_prioridad_destino ?? 999)) {
                    return ($a->_prioridad_destino ?? 999) < ($b->_prioridad_destino ?? 999) ? -1 : 1;
                }

                if ((string) $a->fecha_proceso !== (string) $b->fecha_proceso) {
                    return strcmp((string) $b->fecha_proceso, (string) $a->fecha_proceso);
                }

                return ((int) $b->id_logistica_detalle) <=> ((int) $a->id_logistica_detalle);
            })
            ->first();
    }

    private function precargarExistentes(Collection $filas)
    {
        $series = $filas->pluck('serie')->unique()->values();
        $numeros = $filas->pluck('numero_remision')->unique()->values();
        $codigos = $filas->pluck('codigo')->unique()->values();

        return DB::table('ot_logistica_remisiones')
            ->whereIn('serie', $series->all())
            ->whereIn('numero_remision', $numeros->all())
            ->whereIn('codigo', $codigos->all())
            ->get()
            ->keyBy(function ($item) {
                return $this->clave(
                    $item->serie,
                    $item->numero_remision,
                    $item->codigo,
                    $item->cod_sucursal_salida,
                    $item->cod_sucursal_destino
                );
            });
    }

    private function registrarDocumento(array $fila, $fechaRecepcion): void
    {
        $claveDocumento = implode('|', [$fila['serie'], $fila['numero_remision'],
            $fila['cod_sucursal_salida'], $fila['cod_sucursal_destino']]);
        $this->documentosEstado[$claveDocumento] =
            ($this->documentosEstado[$claveDocumento] ?? false) || !empty($fechaRecepcion);
    }

    /**
     * Destinos físicos que pueden representar una asignación logística.
     *
     * COMERCIAL MATRIZ no existe como columna en el Excel de planificación:
     * operativamente puede recibir mercadería originalmente asignada a AYALA
     * o a MODELO MUESTRA. Para el resto de los locales exigimos coincidencia
     * exacta y no hacemos equivalencias.
     */
    private function resolverSucursalesCandidatas($sucursal): array
    {
        $normalizada = $this->normalizarSucursalBase($sucursal);

        if (!$normalizada) {
            return [];
        }

        if ($normalizada === 'MATRIZ') {
            return ['MATRIZ', 'AYALA', 'MODELO'];
        }

        return [$normalizada];
    }

    private function resolverSucursalImportada($codigo, $nombreExcel, $nombreCatalogo = null)
    {
        $porCodigo = [
            2 => 'SL',
            3 => 'LUQUE',
            4 => 'MALL',
            5 => 'L06',
            6 => 'RURAL',
            7 => 'BONANZA',
            8 => 'SHOPP',
            9 => 'MULTI',
            14 => 'PINEDO',
            15 => 'NEMBY',
            16 => 'MARIANO',
            22 => 'JARDINES',
            25 => 'MATRIZ',
        ];

        if ($codigo && isset($porCodigo[$codigo])) {
            return $porCodigo[$codigo];
        }

        if ($nombreCatalogo) {
            $normalizado = $this->normalizarSucursalBase($nombreCatalogo);
            if ($normalizado) {
                return $normalizado;
            }
        }

        return $this->normalizarSucursalBase($nombreExcel);
    }

    private function normalizarSucursalBase($nombre)
    {
        $valor = $this->sinAcentos(
            strtoupper(
                trim((string) $nombre)
            )
        );

        if ($valor === '') {
            return null;
        }

        // Valores cortos que ya pueden venir guardados en ot_logistica_detalle.
        $aliasExactos = [
            'SL' => 'SL',
            'LUQUE' => 'LUQUE',
            'MALL' => 'MALL',
            'L06' => 'L06',
            'RURAL' => 'RURAL',
            'BONANZA' => 'BONANZA',
            'SHOPP' => 'SHOPP',
            'MULTI' => 'MULTI',
            'PINEDO' => 'PINEDO',
            'NEMBY' => 'NEMBY',
            'MARIANO' => 'MARIANO',
            'LOS JARDINES' => 'JARDINES',
            'JARDINES' => 'JARDINES',
            'AYALA' => 'AYALA',
            'MODELO MUESTRA' => 'MODELO',
            'MODELO' => 'MODELO',
            'COMERCIAL MATRIZ' => 'MATRIZ',
            'MATRIZ' => 'MATRIZ',
        ];

        if (isset($aliasExactos[$valor])) {
            return $aliasExactos[$valor];
        }

        // Primero los nombres más específicos.
        if (strpos($valor, 'SHOP SAN LO') !== false) {
            return 'SHOPP';
        }

        if (strpos($valor, 'L06') !== false) {
            return 'L06';
        }

        if (strpos($valor, 'SAN LORENZO') !== false) {
            return 'SL';
        }

        if (strpos($valor, 'MULTIPLAZA') !== false) {
            return 'MULTI';
        }

        if (strpos($valor, 'JARDINES') !== false) {
            return 'JARDINES';
        }

        if (strpos($valor, 'MARIANO') !== false) {
            return 'MARIANO';
        }

        if (strpos($valor, 'PINEDO') !== false) {
            return 'PINEDO';
        }

        if (strpos($valor, 'BONANZA') !== false) {
            return 'BONANZA';
        }

        if (strpos($valor, 'RURAL') !== false) {
            return 'RURAL';
        }

        if (strpos($valor, 'NEMBY') !== false) {
            return 'NEMBY';
        }

        if (strpos($valor, 'LUQUE') !== false) {
            return 'LUQUE';
        }

        if (strpos($valor, 'MALL') !== false) {
            return 'MALL';
        }

        if (strpos($valor, 'AYALA') !== false) {
            return 'AYALA';
        }

        if (strpos($valor, 'MODELO') !== false) {
            return 'MODELO';
        }

        if (strpos($valor, 'MATRIZ') !== false) {
            return 'MATRIZ';
        }

        return $valor;
    }

    private function clave($serie, $numero, $codigo, $origen, $destino)
    {
        return implode('|', [
            (string) $serie,
            (string) $numero,
            $this->normalizarCodigoCompleto($codigo),
            (string) $origen,
            (string) $destino,
        ]);
    }

    private function claveVinculo($codigo, $sucursal)
    {
        return strtoupper(trim((string) $codigo))
            . '|'
            . strtoupper(trim((string) $sucursal));
    }

    /**
     * Código completo tal como identifica la variante en ENVIOS.
     * Ej.: '050616220VD04 -> 050616220VD04
     *
     * Se usa para guardar la remisión y para su clave única.
     */
    private function normalizarCodigoCompleto($valor)
    {
        $codigo = strtoupper(trim((string) $valor));
        $codigo = ltrim($codigo, "'’`");
        $codigo = preg_replace('/\\s+/u', '', $codigo);

        return $codigo ?: '';
    }

    /**
     * Código base usado únicamente para relacionar ENVIOS con OT/Logística.
     *
     * En la OT se guarda, por ejemplo:
     *   050616220
     *
     * Mientras que ENVIOS puede traer:
     *   050616220VD04
     *   460615895COTP
     *
     * Si el código comienza con 9 dígitos, esos 9 dígitos son el código base.
     * Para códigos históricos que no sigan ese patrón conservamos el código
     * completo para no inventar una asociación.
     */
    private function normalizarCodigoBase($valor)
    {
        $codigo = $this->normalizarCodigoCompleto($valor);

        /*
         * Si Excel convirtió 050617600 en 50617600, recuperamos el cero
         * inicial porque los códigos base de OT son de 9 dígitos.
         */
        if (preg_match('/^(\\d{1,9})$/', $codigo)) {
            return str_pad($codigo, 9, '0', STR_PAD_LEFT);
        }

        if (preg_match('/^(\\d{9})/', $codigo, $coincidencia)) {
            return $coincidencia[1];
        }

        return $codigo;
    }

    private function enteroONull($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return (int) $valor;
    }

    private function parseQuantity($valor): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
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
            : null;
    }

    private function decimalONull($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        $limpio = str_replace([' ', '.'], '', (string) $valor);
        $limpio = str_replace(',', '.', $limpio);

        return is_numeric($limpio)
            ? (float) $limpio
            : null;
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
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject($valor)
                )->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $texto = trim((string) $valor);

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'] as $formato) {
            try {
                $fecha = Carbon::createFromFormat($formato, $texto);

                if ($fecha !== false) {
                    return $fecha->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                // Probar siguiente formato.
            }
        }

        try {
            return Carbon::parse($texto)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function sinAcentos($texto)
    {
        return strtr($texto, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ñ' => 'N',
        ]);
    }

    public function getProcesadas()
    {
        return $this->procesadas;
    }

    public function getInsertadas()
    {
        return $this->insertadas;
    }

    public function getActualizadas()
    {
        return $this->actualizadas;
    }

    public function getSinCambios()
    {
        return $this->sinCambios;
    }

    public function getCantidadesCorregidas()
    {
        return $this->cantidadesCorregidas;
    }

    public function resumen(): array
    {
        return [
            'leidas' => $this->leidas,
            'procesadas' => $this->procesadas,
            'insertadas' => $this->insertadas,
            'actualizadas' => $this->actualizadas,
            'sin_cambios' => $this->sinCambios,
            'cantidades_corregidas' => $this->cantidadesCorregidas,
            'duplicadas' => $this->duplicadas,
            'omitidas' => $this->omitidas,
            'vinculadas_ot' => $this->vinculadasOt,
            'vinculadas_logistica' => $this->vinculadas,
            'sin_ot' => $this->sinOt,
            'sin_vincular' => $this->sinVincular,
            'documentos' => $this->getDocumentosArchivo(),
            'recibidos' => $this->getDocumentosRecibidos(),
            'en_transito' => $this->getDocumentosEnTransito(),
            'total' => $this->total,
            'segundos' => round(microtime(true) - $this->inicio, 2),
        ];
    }

    public function finalizar(): array
    {
        $this->total = $this->leidas;
        $this->notificar('COMPLETADO', 'Remisiones importadas correctamente.');
        return $this->resumen();
    }

    public function marcarError(string $mensaje): void
    {
        $this->notificar('ERROR', $mensaje);
    }

    private function notificar(string $estado, string $mensaje): void
    {
        if (!$this->onProgreso) {
            return;
        }
        ($this->onProgreso)(array_merge($this->resumen(), [
            'estado' => $estado,
            'mensaje' => $mensaje,
            // El total del XLSX puede incluir filas con formato pero vacías.
            // El 100% solo se publica cuando terminó de guardarse el archivo.
            'porcentaje' => $estado === 'COMPLETADO' ? 100 : ($this->total
                ? min(99, round($this->leidas * 100 / $this->total, 1)) : null),
        ]));
    }

    public function getVinculadas()
    {
        return $this->vinculadas;
    }

    public function getSinVincular()
    {
        return $this->sinVincular;
    }

    public function getVinculadasOt()
    {
        return $this->vinculadasOt;
    }

    public function getSinOt()
    {
        return $this->sinOt;
    }

    public function getOmitidas()
    {
        return $this->omitidas;
    }

    public function getDocumentosArchivo()
    {
        return count($this->documentosEstado);
    }

    public function getDocumentosRecibidos()
    {
        return count(array_filter($this->documentosEstado));
    }

    public function getDocumentosEnTransito()
    {
        return $this->getDocumentosArchivo() - $this->getDocumentosRecibidos();
    }

    public function chunkSize(): int
    {
        return 1500;
    }
}
