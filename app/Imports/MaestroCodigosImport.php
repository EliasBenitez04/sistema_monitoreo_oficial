<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class MaestroCodigosImport
{
    private string $token;
    private int $procesadas = 0;
    private int $guardadas = 0;
    private int $omitidas = 0;
    private ?int $total = null;
    private int $tamanoLote = 1000;

    public function __construct(string $token)
    {
        $this->token = preg_replace('/[^A-Za-z0-9_-]/', '', $token) ?: uniqid('cod_', true);

        $this->actualizarProgreso([
            'estado' => 'PREPARANDO',
            'mensaje' => 'Preparando archivo...',
            'porcentaje' => 0,
        ]);
    }

    public function importarXlsx(string $ruta): void
    {
        @set_time_limit(0);
        DB::disableQueryLog();

        if (!class_exists(\ZipArchive::class) || !class_exists(\XMLReader::class)) {
            throw new \RuntimeException(
                'El servidor necesita las extensiones PHP zip y xmlreader para procesar XLSX grandes.'
            );
        }

        $zip = new \ZipArchive();

        if ($zip->open($ruta) !== true) {
            throw new \RuntimeException('No se pudo abrir el archivo XLSX.');
        }

        $temporalBase = tempnam(sys_get_temp_dir(), 'maestro_codigos_');

        if ($temporalBase === false) {
            $zip->close();
            throw new \RuntimeException('No se pudo crear el directorio temporal.');
        }

        @unlink($temporalBase);
        $directorioTemporal = $temporalBase . '_dir';

        if (!@mkdir($directorioTemporal, 0777, true) && !is_dir($directorioTemporal)) {
            $zip->close();
            throw new \RuntimeException('No se pudo preparar el XLSX.');
        }

        try {
            $sharedStrings = $this->leerSharedStrings($zip);

            $hojaInterna = 'xl/worksheets/sheet1.xml';

            if ($zip->locateName($hojaInterna) === false) {
                throw new \RuntimeException('No se encontró la primera hoja del XLSX.');
            }

            if (!$zip->extractTo($directorioTemporal, [$hojaInterna])) {
                throw new \RuntimeException('No se pudo extraer la hoja del XLSX.');
            }

            $rutaHoja = $directorioTemporal
                . DIRECTORY_SEPARATOR . 'xl'
                . DIRECTORY_SEPARATOR . 'worksheets'
                . DIRECTORY_SEPARATOR . 'sheet1.xml';

            $this->total = $this->detectarTotalFilasXlsx($rutaHoja);

            $this->actualizarProgreso([
                'estado' => 'PROCESANDO',
                'mensaje' => 'Procesando códigos...',
                'total' => $this->total,
                'porcentaje' => 0,
            ]);

            $reader = new \XMLReader();

            if (!$reader->open($rutaHoja, null, LIBXML_NONET | LIBXML_COMPACT)) {
                throw new \RuntimeException('No se pudo iniciar la lectura incremental.');
            }

            $encabezados = [];
            $lote = [];

            while ($reader->read()) {
                if (
                    $reader->nodeType !== \XMLReader::ELEMENT
                    || $reader->localName !== 'row'
                ) {
                    continue;
                }

                $xmlFila = $reader->readOuterXML();

                if ($xmlFila === '') {
                    continue;
                }

                $valores = $this->leerFilaXml($xmlFila, $sharedStrings);

                if (empty($encabezados)) {
                    $encabezados = $this->normalizarEncabezados($valores);
                    continue;
                }

                $fila = [];

                foreach ($encabezados as $indice => $encabezado) {
                    if ($encabezado === '') {
                        continue;
                    }

                    $fila[$encabezado] = $valores[$indice] ?? null;
                }

                $registro = $this->normalizarRegistro($fila);

                if (!$registro) {
                    $this->omitidas++;
                    $this->procesadas++;
                    continue;
                }

                $lote[] = $registro;

                if (count($lote) >= $this->tamanoLote) {
                    $this->guardarLote($lote);
                    $lote = [];
                }
            }

            $reader->close();

            if (!empty($lote)) {
                $this->guardarLote($lote);
            }

            $this->finalizar();
        } finally {
            $zip->close();
            $this->eliminarDirectorioTemporal($directorioTemporal);
        }
    }

    public function importarCsv(string $ruta): void
    {
        @set_time_limit(0);
        DB::disableQueryLog();

        $archivo = new \SplFileObject($ruta, 'r');
        $archivo->setFlags(
            \SplFileObject::READ_CSV
            | \SplFileObject::SKIP_EMPTY
            | \SplFileObject::DROP_NEW_LINE
        );

        $primeraLinea = $archivo->fgets();
        $delimitador = $this->detectarDelimitador($primeraLinea);

        $archivo = new \SplFileObject($ruta, 'r');
        $archivo->setCsvControl($delimitador);
        $archivo->setFlags(
            \SplFileObject::READ_CSV
            | \SplFileObject::SKIP_EMPTY
            | \SplFileObject::DROP_NEW_LINE
        );

        $this->total = $this->contarFilasCsv($ruta);

        $this->actualizarProgreso([
            'estado' => 'PROCESANDO',
            'mensaje' => 'Procesando códigos...',
            'total' => $this->total,
            'porcentaje' => 0,
        ]);

        $encabezados = [];
        $lote = [];

        foreach ($archivo as $valores) {
            if (!is_array($valores) || $this->filaVacia($valores)) {
                continue;
            }

            $valores = array_map(function ($valor) {
                return $this->normalizarUtf8($valor);
            }, $valores);

            if (empty($encabezados)) {
                $encabezados = $this->normalizarEncabezados($valores);
                continue;
            }

            $fila = [];

            foreach ($encabezados as $indice => $encabezado) {
                if ($encabezado === '') {
                    continue;
                }

                $fila[$encabezado] = $valores[$indice] ?? null;
            }

            $registro = $this->normalizarRegistro($fila);

            if (!$registro) {
                $this->omitidas++;
                $this->procesadas++;
                continue;
            }

            $lote[] = $registro;

            if (count($lote) >= $this->tamanoLote) {
                $this->guardarLote($lote);
                $lote = [];
            }
        }

        if (!empty($lote)) {
            $this->guardarLote($lote);
        }

        $this->finalizar();
    }

    private function guardarLote(array $lote): void
    {
        if (empty($lote)) {
            return;
        }

        /*
         * 1000 registros por sentencia = pocas consultas y un número de
         * parámetros seguro para PostgreSQL 9.5.
         */
        DB::table('maestro_codigos')->upsert(
            $lote,
            ['cod_articulo'],
            [
                'cod_proveedor',
                'proveedor',
                'cod_articulo_origen',
                'cod_base',
                'cod_imagen',
                'color',
                'talle',
                'articulo',
                'grupo_precio',
                'familia',
                'marca',
                'motivo',
                'linea',
                'grupo',
                'grupo_plan',
                'generico',
                'tejido',
                'temporada',
                'estado',
                'fecha_creacion',
                'temporada_codigo',
                'anio',
                'tipo_stock',
                'precio_venta',
                'costo_unitario',
                'temp',
                'complejidad',
                'tipo_codigo',
                'updated_at',
            ]
        );

        $cantidad = count($lote);
        $this->procesadas += $cantidad;
        $this->guardadas += $cantidad;

        $porcentaje = $this->total && $this->total > 0
            ? min(99, round(($this->procesadas / $this->total) * 100, 1))
            : null;

        $this->actualizarProgreso([
            'estado' => 'PROCESANDO',
            'mensaje' => 'Guardando códigos en la base de datos...',
            'procesadas' => $this->procesadas,
            'guardadas' => $this->guardadas,
            'omitidas' => $this->omitidas,
            'total' => $this->total,
            'porcentaje' => $porcentaje,
        ]);

        unset($lote);
        gc_collect_cycles();
    }

    private function finalizar(): void
    {
        $this->actualizarProgreso([
            'estado' => 'COMPLETADO',
            'mensaje' => 'Importación finalizada correctamente.',
            'procesadas' => $this->procesadas,
            'guardadas' => $this->guardadas,
            'omitidas' => $this->omitidas,
            'total' => $this->total,
            'porcentaje' => 100,
        ]);
    }

    public function marcarError(string $mensaje): void
    {
        $this->actualizarProgreso([
            'estado' => 'ERROR',
            'mensaje' => $mensaje,
            'procesadas' => $this->procesadas,
            'guardadas' => $this->guardadas,
            'omitidas' => $this->omitidas,
            'total' => $this->total,
        ]);
    }

    public function resumen(): array
    {
        return [
            'procesadas' => $this->procesadas,
            'guardadas' => $this->guardadas,
            'omitidas' => $this->omitidas,
            'total' => $this->total,
        ];
    }

    private function normalizarRegistro(array $fila): ?array
    {
        $get = function (array $claves) use ($fila) {
            foreach ($claves as $clave) {
                if (array_key_exists($clave, $fila)) {
                    return $fila[$clave];
                }
            }

            return null;
        };

        $codArticuloOrigen = $this->limpiarTexto(
            $get(['cod_articulo'])
        );

        $codArticulo = $this->limpiarTexto(
            $get(['cod_articulo_2', 'codarticulo', 'cod_articulo'])
        );

        if (!$codArticulo) {
            return null;
        }

        $ahora = now();

        return [
            'cod_proveedor' => $this->limpiarTexto(
                $get(['cod_proveedor'])
            ),
            'proveedor' => $this->limpiarTexto(
                $get(['proveedor'])
            ),
            'cod_articulo_origen' => $codArticuloOrigen,
            'cod_articulo' => $codArticulo,
            'cod_base' => $this->limpiarTexto(
                $get(['cod_base', 'codbase'])
            ),
            'cod_imagen' => $this->limpiarTexto(
                $get(['cod_imagen', 'codimagen'])
            ),
            'color' => $this->limpiarTexto($get(['color'])),
            'talle' => $this->limpiarTexto($get(['talle'])),
            'articulo' => $this->limpiarTexto(
                $get(['artiuclo', 'articulo'])
            ),
            'grupo_precio' => $this->limpiarTexto(
                $get(['grupo_precio', 'grupoprecio'])
            ),
            'familia' => $this->limpiarTexto($get(['familia'])),
            'marca' => $this->limpiarTexto($get(['marca'])),
            'motivo' => $this->limpiarTexto($get(['motivo'])),
            'linea' => $this->limpiarTexto($get(['linea'])),
            'grupo' => $this->limpiarTexto($get(['grupo'])),
            'grupo_plan' => $this->limpiarTexto(
                $get(['grupo_plan', 'grupoplan'])
            ),
            'generico' => $this->limpiarTexto($get(['generico'])),
            'tejido' => $this->limpiarTexto($get(['tejido'])),
            'temporada' => $this->limpiarTexto($get(['temporada'])),
            'estado' => $this->limpiarTexto($get(['estado'])),
            'fecha_creacion' => $this->normalizarFecha(
                $get(['fecha_creacion', 'fechacreacion'])
            ),
            'temporada_codigo' => $this->limpiarTexto(
                $get(['temporada_2'])
            ),
            'anio' => $this->normalizarEntero(
                $get(['ano', 'anio'])
            ),
            'tipo_stock' => $this->limpiarTexto(
                $get(['tipo_stock', 'tipostock'])
            ),
            'precio_venta' => $this->normalizarDecimal(
                $get(['precio_venta', 'precioventa'])
            ),
            'costo_unitario' => $this->normalizarDecimal(
                $get(['costo_unitario', 'costounitario'])
            ),
            'temp' => $this->limpiarTexto($get(['temp'])),
            'complejidad' => $this->limpiarTexto(
                $get(['complejidad'])
            ),
            'tipo_codigo' => $this->limpiarTexto(
                $get(['tipo_codigo', 'tipocodigo'])
            ),
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ];
    }

    private function limpiarTexto($valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = $this->normalizarUtf8((string) $valor);
        $texto = trim($texto);

        // Requisito: eliminar apóstrofes simples/tipográficos y backticks.
        $texto = str_replace(["'", "’", "‘", "`"], '', $texto);

        $texto = trim($texto);

        return $texto === '' ? null : $texto;
    }

    private function normalizarUtf8($valor): string
    {
        $texto = (string) $valor;

        if (
            function_exists('mb_check_encoding')
            && !mb_check_encoding($texto, 'UTF-8')
        ) {
            $texto = mb_convert_encoding(
                $texto,
                'UTF-8',
                'Windows-1252'
            );
        }

        return $texto;
    }

    private function normalizarEntero($valor): ?int
    {
        $texto = $this->limpiarTexto($valor);

        if ($texto === null) {
            return null;
        }

        $numero = preg_replace('/[^0-9-]/', '', $texto);

        return $numero === '' ? null : (int) $numero;
    }

    private function normalizarDecimal($valor): ?float
    {
        $texto = $this->limpiarTexto($valor);

        if ($texto === null) {
            return null;
        }

        $texto = preg_replace('/[^0-9,.\-]/', '', $texto);

        if ($texto === '' || $texto === '-') {
            return null;
        }

        $tieneComa = strpos($texto, ',') !== false;
        $tienePunto = strpos($texto, '.') !== false;

        if ($tieneComa && $tienePunto) {
            $ultimaComa = strrpos($texto, ',');
            $ultimoPunto = strrpos($texto, '.');

            if ($ultimaComa > $ultimoPunto) {
                $texto = str_replace('.', '', $texto);
                $texto = str_replace(',', '.', $texto);
            } else {
                $texto = str_replace(',', '', $texto);
            }
        } elseif ($tieneComa) {
            $partes = explode(',', $texto);
            $decimales = end($partes);

            $texto = strlen($decimales) <= 2
                ? str_replace(',', '.', $texto)
                : str_replace(',', '', $texto);
        } elseif ($tienePunto) {
            $partes = explode('.', $texto);
            $decimales = end($partes);

            if (strlen($decimales) > 2) {
                $texto = str_replace('.', '', $texto);
            }
        }

        return is_numeric($texto) ? (float) $texto : null;
    }

    private function normalizarFecha($valor): ?string
    {
        $texto = $this->limpiarTexto($valor);

        if ($texto === null) {
            return null;
        }

        if (is_numeric($texto) && (float) $texto > 20000) {
            try {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject((float) $texto)
                )->format('Y-m-d');
            } catch (\Throwable $e) {
                // seguir con formatos de texto
            }
        }

        foreach (['d/m/Y', 'd/m/y', 'd-m-Y', 'Y-m-d'] as $formato) {
            try {
                $fecha = Carbon::createFromFormat($formato, $texto);

                if ($fecha !== false) {
                    return $fecha->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                // probar siguiente formato
            }
        }

        return null;
    }

    private function normalizarEncabezados(array $valores): array
    {
        $resultado = [];
        $usados = [];

        foreach ($valores as $indice => $valor) {
            $texto = trim($this->normalizarUtf8((string) $valor));

            if ($texto === '') {
                $resultado[$indice] = '';
                continue;
            }

            $base = strtolower(Str::ascii($texto));
            $base = preg_replace('/[^a-z0-9]+/', '_', $base);
            $base = trim($base, '_');

            if ($base === '') {
                $resultado[$indice] = '';
                continue;
            }

            $usados[$base] = ($usados[$base] ?? 0) + 1;

            $resultado[$indice] = $usados[$base] > 1
                ? $base . '_' . $usados[$base]
                : $base;
        }

        return $resultado;
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
            if (
                $reader->nodeType !== \XMLReader::ELEMENT
                || $reader->localName !== 'si'
            ) {
                continue;
            }

            $siXml = $reader->readOuterXML();

            if ($siXml === '') {
                $strings[] = '';
                continue;
            }

            $texto = '';

            if (
                preg_match_all(
                    '/<t(?:\\s[^>]*)?>(.*?)<\\/t>/s',
                    $siXml,
                    $coincidencias
                )
            ) {
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

        if (
            !preg_match_all(
                '/<c\\b([^>]*)>(.*?)<\\/c>/s',
                $xmlFila,
                $celdas,
                PREG_SET_ORDER
            )
        ) {
            return $valores;
        }

        foreach ($celdas as $celda) {
            $atributos = $celda[1] ?? '';
            $contenido = $celda[2] ?? '';

            if (!preg_match('/\\br="([A-Z]+)(\\d+)"/i', $atributos, $mRef)) {
                continue;
            }

            $tipo = null;

            if (preg_match('/\\bt="([^"]+)"/i', $atributos, $mTipo)) {
                $tipo = $mTipo[1];
            }

            $indiceColumna = $this->indiceColumnaExcel(
                strtoupper($mRef[1])
            );

            $valor = null;

            if ($tipo === 'inlineStr') {
                if (
                    preg_match_all(
                        '/<t(?:\\s[^>]*)?>(.*?)<\\/t>/s',
                        $contenido,
                        $mTextos
                    )
                ) {
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

        foreach (str_split($letras) as $letra) {
            $resultado = ($resultado * 26) + (ord($letra) - 64);
        }

        return $resultado - 1;
    }

    private function detectarTotalFilasXlsx(string $rutaHoja): ?int
    {
        $reader = new \XMLReader();

        if (!$reader->open($rutaHoja, null, LIBXML_NONET | LIBXML_COMPACT)) {
            return null;
        }

        $total = null;

        while ($reader->read()) {
            if (
                $reader->nodeType === \XMLReader::ELEMENT
                && $reader->localName === 'dimension'
            ) {
                $ref = $reader->getAttribute('ref');

                if ($ref && preg_match('/:[A-Z]+(\\d+)$/i', $ref, $m)) {
                    $total = max(0, ((int) $m[1]) - 1);
                }

                break;
            }

            if (
                $reader->nodeType === \XMLReader::ELEMENT
                && $reader->localName === 'sheetData'
            ) {
                break;
            }
        }

        $reader->close();

        return $total;
    }

    private function detectarDelimitador(string $linea): string
    {
        $candidatos = [';' => 0, ',' => 0, "\t" => 0];

        foreach (array_keys($candidatos) as $delimitador) {
            $candidatos[$delimitador] = count(
                str_getcsv($linea, $delimitador)
            );
        }

        arsort($candidatos);

        return (string) array_key_first($candidatos);
    }

    private function contarFilasCsv(string $ruta): ?int
    {
        $archivo = new \SplFileObject($ruta, 'r');
        $archivo->seek(PHP_INT_MAX);

        $lineas = $archivo->key() + 1;

        return max(0, $lineas - 1);
    }

    private function filaVacia(array $valores): bool
    {
        foreach ($valores as $valor) {
            if (trim((string) $valor) !== '') {
                return false;
            }
        }

        return true;
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

    private function actualizarProgreso(array $datos): void
    {
        $base = [
            'estado' => 'PROCESANDO',
            'mensaje' => 'Procesando...',
            'procesadas' => $this->procesadas,
            'guardadas' => $this->guardadas,
            'omitidas' => $this->omitidas,
            'total' => $this->total,
            'porcentaje' => null,
            'actualizado_en' => now()->format('H:i:s'),
        ];

        Cache::put(
            'maestro_codigos_import_' . $this->token,
            array_merge($base, $datos),
            3600
        );
    }
}
