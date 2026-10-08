<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class VentasImport
{
    private int $importacionId;
    private string $token;

    private int $procesadas = 0;
    private int $insertadas = 0;
    private int $omitidas = 0;
    private int $duplicadas = 0;
    private int $invalidas = 0;
    private ?int $total = null;

    private ?string $fechaDesde = null;
    private ?string $fechaHasta = null;

    private int $tamanoLote = 1000;

    /**
     * Conteo de ocurrencias por firma.
     * Permite conservar dos líneas idénticas reales dentro del mismo archivo
     * y, al mismo tiempo, evitar duplicarlas al reimportar el export.
     */
    private array $ocurrencias = [];

    public function __construct(
        int $importacionId,
        string $token
    ) {
        $this->importacionId = $importacionId;
        $this->token = preg_replace(
            '/[^A-Za-z0-9_-]/',
            '',
            $token
        ) ?: uniqid('vta_', true);

        $this->actualizarProgreso([
            'estado' => 'PREPARANDO',
            'mensaje' => 'Preparando archivo de ventas...',
            'porcentaje' => 0,
        ]);
    }

    public function importarXlsx(string $ruta): void
    {
        @set_time_limit(0);
        DB::disableQueryLog();

        if (!class_exists(\ZipArchive::class)
            || !class_exists(\XMLReader::class)) {
            throw new \RuntimeException(
                'El servidor necesita las extensiones PHP zip y xmlreader para importar XLSX.'
            );
        }

        $zip = new \ZipArchive();

        if ($zip->open($ruta) !== true) {
            throw new \RuntimeException(
                'No se pudo abrir el archivo XLSX.'
            );
        }

        $temporalBase = tempnam(
            sys_get_temp_dir(),
            'ventas_'
        );

        if ($temporalBase === false) {
            $zip->close();
            throw new \RuntimeException(
                'No se pudo preparar el archivo temporal.'
            );
        }

        @unlink($temporalBase);
        $directorioTemporal = $temporalBase . '_dir';

        if (!@mkdir($directorioTemporal, 0777, true)
            && !is_dir($directorioTemporal)) {
            $zip->close();
            throw new \RuntimeException(
                'No se pudo preparar el XLSX.'
            );
        }

        try {
            $sharedStrings = $this->leerSharedStrings($zip);
            $hoja = 'xl/worksheets/sheet1.xml';

            if ($zip->locateName($hoja) === false) {
                throw new \RuntimeException(
                    'No se encontró la primera hoja del XLSX.'
                );
            }

            if (!$zip->extractTo($directorioTemporal, [$hoja])) {
                throw new \RuntimeException(
                    'No se pudo extraer la hoja del XLSX.'
                );
            }

            $rutaHoja = $directorioTemporal
                . DIRECTORY_SEPARATOR . 'xl'
                . DIRECTORY_SEPARATOR . 'worksheets'
                . DIRECTORY_SEPARATOR . 'sheet1.xml';

            $this->total = $this->detectarTotalFilasXlsx(
                $rutaHoja
            );

            $this->actualizarProgreso([
                'estado' => 'PROCESANDO',
                'mensaje' => 'Procesando ventas...',
                'total' => $this->total,
                'porcentaje' => 0,
            ]);

            $reader = new \XMLReader();

            if (!$reader->open(
                $rutaHoja,
                null,
                LIBXML_NONET | LIBXML_COMPACT
            )) {
                throw new \RuntimeException(
                    'No se pudo iniciar la lectura incremental.'
                );
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

                $valores = $this->leerFilaXml(
                    $xmlFila,
                    $sharedStrings
                );

                $numeroFila = null;

                if (preg_match(
                    '/<row\\b[^>]*\\br="(\\d+)"/i',
                    $xmlFila,
                    $mFila
                )) {
                    $numeroFila = (int) $mFila[1];
                }

                if (empty($encabezados)) {
                    $encabezados =
                        $this->normalizarEncabezados($valores);
                    $this->validarEncabezados($encabezados);
                    continue;
                }

                $fila = [];

                foreach ($encabezados as $indice => $encabezado) {
                    if ($encabezado === '') {
                        continue;
                    }

                    $fila[$encabezado] =
                        $valores[$indice] ?? null;
                }

                $registro = $this->normalizarRegistro(
                    $fila,
                    $numeroFila
                );

                $this->procesadas++;

                if (!$registro) {
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
            $this->eliminarDirectorioTemporal(
                $directorioTemporal
            );
        }
    }

    public function importarCsv(string $ruta): void
    {
        @set_time_limit(0);
        DB::disableQueryLog();

        $archivoLectura = new \SplFileObject($ruta, 'r');
        $primeraLinea = $archivoLectura->fgets();
        $delimitador = $this->detectarDelimitador(
            $primeraLinea
        );

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
            'mensaje' => 'Procesando ventas...',
            'total' => $this->total,
            'porcentaje' => 0,
        ]);

        $encabezados = [];
        $lote = [];

        foreach ($archivo as $valores) {
            $numeroFila = $archivo->key() + 1;
            if (!is_array($valores)
                || $this->filaVacia($valores)) {
                continue;
            }

            $valores = array_map(function ($valor) {
                return $this->normalizarUtf8($valor);
            }, $valores);

            if (empty($encabezados)) {
                $encabezados =
                    $this->normalizarEncabezados($valores);
                $this->validarEncabezados($encabezados);
                continue;
            }

            $fila = [];

            foreach ($encabezados as $indice => $encabezado) {
                if ($encabezado === '') {
                    continue;
                }

                $fila[$encabezado] =
                    $valores[$indice] ?? null;
            }

            $registro = $this->normalizarRegistro(
                $fila,
                $numeroFila
            );

            $this->procesadas++;

            if (!$registro) {
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

    public function marcarError(string $mensaje): void
    {
        DB::table('ventas_importaciones')
            ->where('id', $this->importacionId)
            ->update([
                'filas_procesadas' => $this->procesadas,
                'filas_insertadas' => $this->insertadas,
                'filas_omitidas' => $this->omitidas,
                'filas_duplicadas' => $this->duplicadas,
                'filas_invalidas' => $this->invalidas,
                'estado' => 'ERROR',
                'mensaje' => mb_substr($mensaje, 0, 5000),
                'updated_at' => now(),
            ]);

        $this->actualizarProgreso([
            'estado' => 'ERROR',
            'mensaje' => $mensaje,
            'procesadas' => $this->procesadas,
            'insertadas' => $this->insertadas,
            'omitidas' => $this->omitidas,
            'duplicadas' => $this->duplicadas,
            'invalidas' => $this->invalidas,
            'total' => $this->total,
        ]);
    }

    public function resumen(): array
    {
        return [
            'procesadas' => $this->procesadas,
            'insertadas' => $this->insertadas,
            'omitidas' => $this->omitidas,
            'duplicadas' => $this->duplicadas,
            'invalidas' => $this->invalidas,
            'total' => $this->total,
            'fecha_desde' => $this->fechaDesde,
            'fecha_hasta' => $this->fechaHasta,
        ];
    }

    private function guardarLote(array $lote): void
    {
        if (empty($lote)) {
            return;
        }

        $hashes = array_column($lote, 'hash_linea');

        $existentes = DB::table('ventas')
            ->whereIn('hash_linea', $hashes)
            ->pluck('hash_linea')
            ->flip();

        $nuevos = [];

        foreach ($lote as $registro) {
            $filaArchivo = $registro['_fila_importacion'] ?? null;
            unset($registro['_fila_importacion']);

            if ($existentes->has($registro['hash_linea'])) {
                $this->duplicadas++;
                $this->omitidas++;

                $this->registrarOmitida(
                    'DUPLICADA',
                    $filaArchivo,
                    $registro['codigo'] ?? null,
                    $registro['comprobante'] ?? null,
                    $registro['local'] ?? null,
                    'La misma línea de venta ya existe en la base de datos.'
                );

                continue;
            }

            $nuevos[] = $registro;
        }

        if (!empty($nuevos)) {
            DB::table('ventas')->insert($nuevos);
            $this->insertadas += count($nuevos);
        }

        DB::table('ventas_importaciones')
            ->where('id', $this->importacionId)
            ->update([
                'fecha_desde' => $this->fechaDesde,
                'fecha_hasta' => $this->fechaHasta,
                'filas_procesadas' => $this->procesadas,
                'filas_insertadas' => $this->insertadas,
                'filas_omitidas' => $this->omitidas,
                'filas_duplicadas' => $this->duplicadas,
                'filas_invalidas' => $this->invalidas,
                'updated_at' => now(),
            ]);

        $porcentaje = $this->total && $this->total > 0
            ? min(
                99,
                round(
                    ($this->procesadas / $this->total) * 100,
                    1
                )
            )
            : null;

        $this->actualizarProgreso([
            'estado' => 'PROCESANDO',
            'mensaje' => 'Guardando ventas en la base de datos...',
            'procesadas' => $this->procesadas,
            'insertadas' => $this->insertadas,
            'omitidas' => $this->omitidas,
            'duplicadas' => $this->duplicadas,
            'invalidas' => $this->invalidas,
            'total' => $this->total,
            'porcentaje' => $porcentaje,
        ]);

        gc_collect_cycles();
    }

    private function finalizar(): void
    {
        DB::table('ventas_importaciones')
            ->where('id', $this->importacionId)
            ->update([
                'fecha_desde' => $this->fechaDesde,
                'fecha_hasta' => $this->fechaHasta,
                'filas_procesadas' => $this->procesadas,
                'filas_insertadas' => $this->insertadas,
                'filas_omitidas' => $this->omitidas,
                'filas_duplicadas' => $this->duplicadas,
                'filas_invalidas' => $this->invalidas,
                'estado' => 'COMPLETADO',
                'mensaje' => 'Importación completada.',
                'updated_at' => now(),
            ]);

        $this->actualizarProgreso([
            'estado' => 'COMPLETADO',
            'mensaje' => 'Importación finalizada correctamente.',
            'procesadas' => $this->procesadas,
            'insertadas' => $this->insertadas,
            'omitidas' => $this->omitidas,
            'duplicadas' => $this->duplicadas,
            'invalidas' => $this->invalidas,
            'total' => $this->total,
            'porcentaje' => 100,
        ]);

        Log::info('IMPORTACION VENTAS COMPLETADA', [
            'importacion_id' => $this->importacionId,
            'procesadas' => $this->procesadas,
            'insertadas' => $this->insertadas,
            'duplicadas' => $this->duplicadas,
            'invalidas' => $this->invalidas,
            'omitidas_total' => $this->omitidas,
            'fecha_desde' => $this->fechaDesde,
            'fecha_hasta' => $this->fechaHasta,
        ]);
    }

    private function normalizarRegistro(
        array $fila,
        ?int $numeroFila = null
    ): ?array
    {
        $local = $this->limpiarTexto(
            $fila['local'] ?? null
        );

        $codigo = $this->limpiarTexto(
            $fila['codigo'] ?? null
        );

        $fecha = $this->normalizarFecha(
            $fila['fecha'] ?? null
        );

        $cantidad = $this->normalizarEntero(
            $fila['cantidad'] ?? null
        );

        $comprobante = $this->limpiarTexto(
            $fila['comprobante'] ?? null
        );

        $errores = [];

        if (!$local) {
            $errores[] = 'LOCAL vacío';
        }

        if (!$codigo) {
            $errores[] = 'CODIGO vacío';
        }

        if (!$fecha) {
            $errores[] = 'FECHA vacía o inválida';
        }

        if ($cantidad === null) {
            $errores[] = 'CANTIDAD vacía o inválida';
        }

        if (!$comprobante) {
            $errores[] = 'COMPROBANTE vacío';
        }

        if (!empty($errores)) {
            $this->invalidas++;
            $this->omitidas++;

            $this->registrarOmitida(
                'INVALIDA',
                $numeroFila,
                $codigo,
                $comprobante,
                $local,
                implode(' · ', $errores)
            );

            return null;
        }

        $descripcion = $this->limpiarTexto(
            $fila['descripcion'] ?? null
        );

        $cliCod = $this->limpiarTexto(
            $fila['cli_cod'] ?? null
        );

        $cliente = $this->limpiarTexto(
            $fila['cliente'] ?? null
        );

        $vendedor = $this->limpiarTexto(
            $fila['vendedor'] ?? null
        );

        $pLista = $this->normalizarDecimal(
            $fila['plista'] ?? null
        );

        $descuento = $this->normalizarDecimal(
            $fila['dto'] ?? null
        );

        $pVenta = $this->normalizarDecimal(
            $fila['pvta'] ?? null
        );

        $tipoComprobante = strtoupper(
            trim(
                explode(' ', $comprobante)[0] ?? ''
            )
        );

        $baseFirma = implode('|', [
            strtoupper($local),
            strtoupper($codigo),
            strtoupper((string) $descripcion),
            strtoupper((string) $cliCod),
            strtoupper((string) $cliente),
            strtoupper((string) $vendedor),
            number_format($pLista, 2, '.', ''),
            number_format($descuento, 2, '.', ''),
            number_format($pVenta, 2, '.', ''),
            $fecha,
            (string) $cantidad,
            strtoupper($comprobante),
        ]);

        $firma = sha1($baseFirma);

        $this->ocurrencias[$firma] =
            ($this->ocurrencias[$firma] ?? 0) + 1;

        $hashLinea = sha1(
            $firma . '|' . $this->ocurrencias[$firma]
        );

        if (!$this->fechaDesde
            || $fecha < $this->fechaDesde) {
            $this->fechaDesde = $fecha;
        }

        if (!$this->fechaHasta
            || $fecha > $this->fechaHasta) {
            $this->fechaHasta = $fecha;
        }

        $ahora = now();

        return [
            '_fila_importacion' => $numeroFila,
            'importacion_id' => $this->importacionId,
            'local' => $local,
            'codigo' => $codigo,
            'descripcion' => $descripcion,
            'cli_cod' => $cliCod,
            'cliente' => $cliente,
            'vendedor' => $vendedor,
            'p_lista' => $pLista,
            'descuento' => $descuento,
            'p_venta' => $pVenta,
            'fecha' => $fecha,
            'cantidad' => $cantidad,
            'comprobante' => $comprobante,
            'tipo_comprobante' => $tipoComprobante,
            'hash_linea' => $hashLinea,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ];
    }

    /**
     * Persiste y registra en laravel.log cada fila que no se inserta.
     */
    private function registrarOmitida(
        string $tipo,
        ?int $fila,
        ?string $codigo,
        ?string $comprobante,
        ?string $local,
        string $motivo
    ): void {
        DB::table('ventas_importacion_omitidas')->insert([
            'importacion_id' => $this->importacionId,
            'fila' => $fila,
            'tipo' => $tipo,
            'codigo' => $codigo,
            'comprobante' => $comprobante,
            'local' => $local,
            'motivo' => $motivo,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::warning('IMPORTACION VENTAS - FILA OMITIDA', [
            'importacion_id' => $this->importacionId,
            'tipo' => $tipo,
            'fila' => $fila,
            'codigo' => $codigo,
            'comprobante' => $comprobante,
            'local' => $local,
            'motivo' => $motivo,
        ]);
    }

    private function validarEncabezados(
        array $encabezados
    ): void {
        $requeridos = [
            'local',
            'codigo',
            'descripcion',
            'cli_cod',
            'cliente',
            'vendedor',
            'plista',
            'dto',
            'pvta',
            'fecha',
            'cantidad',
            'comprobante',
        ];

        $faltantes = array_values(
            array_diff($requeridos, $encabezados)
        );

        if (!empty($faltantes)) {
            throw new \RuntimeException(
                'Faltan columnas requeridas: '
                . implode(', ', $faltantes)
            );
        }
    }

    private function normalizarEncabezados(
        array $valores
    ): array {
        $resultado = [];

        foreach ($valores as $indice => $valor) {
            $texto = trim(
                $this->normalizarUtf8((string) $valor)
            );

            $texto = preg_replace(
                '/^\xEF\xBB\xBF/',
                '',
                $texto
            );

            $texto = strtolower(Str::ascii($texto));
            $texto = preg_replace(
                '/[^a-z0-9]+/',
                '_',
                $texto
            );
            $texto = trim($texto, '_');

            $resultado[$indice] = $texto;
        }

        return $resultado;
    }

    private function limpiarTexto($valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim(
            $this->normalizarUtf8((string) $valor)
        );

        $texto = str_replace(
            ["'", "’", "‘", "`"],
            '',
            $texto
        );

        $texto = trim($texto);

        return $texto === '' ? null : $texto;
    }

    private function normalizarEntero($valor): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_numeric($valor)) {
            return (int) round((float) $valor);
        }

        $texto = preg_replace(
            '/[^0-9\-]/',
            '',
            (string) $valor
        );

        if ($texto === '' || $texto === '-') {
            return null;
        }

        return (int) $texto;
    }

    private function normalizarDecimal($valor): float
    {
        if ($valor === null || $valor === '') {
            return 0.0;
        }

        if (is_numeric($valor)) {
            return round((float) $valor, 2);
        }

        $texto = preg_replace(
            '/[^0-9,.\-]/',
            '',
            (string) $valor
        );

        if ($texto === '' || $texto === '-') {
            return 0.0;
        }

        if (strpos($texto, ',') !== false
            && strpos($texto, '.') !== false) {
            if (strrpos($texto, ',')
                > strrpos($texto, '.')) {
                $texto = str_replace('.', '', $texto);
                $texto = str_replace(',', '.', $texto);
            } else {
                $texto = str_replace(',', '', $texto);
            }
        } elseif (strpos($texto, ',') !== false) {
            $texto = str_replace(',', '.', $texto);
        }

        return is_numeric($texto)
            ? round((float) $texto, 2)
            : 0.0;
    }

    private function normalizarFecha($valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_numeric($valor)
            && (float) $valor > 20000) {
            try {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject(
                        (float) $valor
                    )
                )->format('Y-m-d');
            } catch (\Throwable $e) {
                // seguir
            }
        }

        $texto = trim((string) $valor);

        foreach (
            ['d/m/Y', 'd/m/y', 'd-m-Y', 'Y-m-d']
            as $formato
        ) {
            try {
                $fecha = Carbon::createFromFormat(
                    $formato,
                    $texto
                );

                if ($fecha !== false) {
                    return $fecha->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                // probar siguiente formato
            }
        }

        return null;
    }

    private function normalizarUtf8($valor): string
    {
        $texto = (string) $valor;

        if (function_exists('mb_check_encoding')
            && !mb_check_encoding($texto, 'UTF-8')) {
            $texto = mb_convert_encoding(
                $texto,
                'UTF-8',
                'Windows-1252'
            );
        }

        return $texto;
    }

    private function leerSharedStrings(
        \ZipArchive $zip
    ): array {
        $indice = $zip->locateName(
            'xl/sharedStrings.xml'
        );

        if ($indice === false) {
            return [];
        }

        $contenido = $zip->getFromIndex($indice);

        if ($contenido === false || $contenido === '') {
            return [];
        }

        $reader = new \XMLReader();

        if (!$reader->XML(
            $contenido,
            null,
            LIBXML_NONET | LIBXML_COMPACT
        )) {
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

    private function leerFilaXml(
        string $xmlFila,
        array $sharedStrings
    ): array {
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

            if (!preg_match(
                '/\\br="([A-Z]+)(\\d+)"/i',
                $atributos,
                $mRef
            )) {
                continue;
            }

            $tipo = null;

            if (preg_match(
                '/\\bt="([^"]+)"/i',
                $atributos,
                $mTipo
            )) {
                $tipo = $mTipo[1];
            }

            $indiceColumna =
                $this->indiceColumnaExcel(
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
            } elseif (preg_match(
                '/<v>(.*?)<\\/v>/s',
                $contenido,
                $mValor
            )) {
                $valorCrudo = html_entity_decode(
                    strip_tags($mValor[1]),
                    ENT_QUOTES | ENT_XML1,
                    'UTF-8'
                );

                if ($tipo === 's') {
                    $valor =
                        $sharedStrings[(int) $valorCrudo]
                        ?? '';
                } elseif ($tipo === 'b') {
                    $valor =
                        $valorCrudo === '1' ? 1 : 0;
                } else {
                    $valor = $valorCrudo;
                }
            }

            $valores[$indiceColumna] = $valor;
        }

        return $valores;
    }

    private function indiceColumnaExcel(
        string $letras
    ): int {
        $resultado = 0;

        foreach (str_split($letras) as $letra) {
            $resultado =
                ($resultado * 26)
                + (ord($letra) - 64);
        }

        return $resultado - 1;
    }

    private function detectarTotalFilasXlsx(
        string $rutaHoja
    ): ?int {
        $reader = new \XMLReader();

        if (!$reader->open(
            $rutaHoja,
            null,
            LIBXML_NONET | LIBXML_COMPACT
        )) {
            return null;
        }

        $total = null;

        while ($reader->read()) {
            if (
                $reader->nodeType === \XMLReader::ELEMENT
                && $reader->localName === 'dimension'
            ) {
                $ref = $reader->getAttribute('ref');

                if ($ref
                    && preg_match(
                        '/:[A-Z]+(\\d+)$/i',
                        $ref,
                        $m
                    )) {
                    $total = max(
                        0,
                        ((int) $m[1]) - 1
                    );
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

    private function detectarDelimitador(
        string $linea
    ): string {
        $candidatos = [
            ';' => 0,
            ',' => 0,
            "\t" => 0,
        ];

        foreach (array_keys($candidatos) as $delimitador) {
            $candidatos[$delimitador] = count(
                str_getcsv(
                    $linea,
                    $delimitador
                )
            );
        }

        arsort($candidatos);

        return (string) array_key_first(
            $candidatos
        );
    }

    private function contarFilasCsv(
        string $ruta
    ): ?int {
        $archivo = new \SplFileObject(
            $ruta,
            'r'
        );

        $archivo->seek(PHP_INT_MAX);

        return max(
            0,
            ($archivo->key() + 1) - 1
        );
    }

    private function filaVacia(
        array $valores
    ): bool {
        foreach ($valores as $valor) {
            if (trim((string) $valor) !== '') {
                return false;
            }
        }

        return true;
    }

    private function eliminarDirectorioTemporal(
        string $directorio
    ): void {
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

    private function actualizarProgreso(
        array $datos
    ): void {
        $base = [
            'estado' => 'PROCESANDO',
            'mensaje' => 'Procesando...',
            'procesadas' => $this->procesadas,
            'insertadas' => $this->insertadas,
            'omitidas' => $this->omitidas,
            'duplicadas' => $this->duplicadas,
            'invalidas' => $this->invalidas,
            'total' => $this->total,
            'porcentaje' => null,
            'actualizado_en' => now()->format('H:i:s'),
        ];

        Cache::put(
            'ventas_import_' . $this->token,
            array_merge($base, $datos),
            3600
        );
    }
}
