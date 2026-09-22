<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
    private $vinculadas = 0;
    private $vinculadasOt = 0;
    private $sinVincular = 0;
    private $sinOt = 0;
    private $omitidas = 0;

    private $documentosEstado = [];

    private $usoDetalle = [];
    private $asignadoImportacion = [];

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
            $tamanoLote = 750;

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

        /*
        |--------------------------------------------------------------------------
        | PROCESAMIENTO POR BLOQUES
        |--------------------------------------------------------------------------
        |
        | WithChunkReading evita cargar un XLSX grande completo en memoria.
        | Cada llamada procesa como máximo 1.500 filas y conserva los contadores
        | del objeto importador entre bloques.
        |
        */
        $filas = $this->normalizarFilas($rows);

        if ($filas->isEmpty()) {
            return;
        }

        $this->procesadas += $filas->count();
        $this->calcularResumenDocumentos($filas);

        /*
        |--------------------------------------------------------------------------
        | CANDIDATOS LOGÍSTICOS EN UNA SOLA CONSULTA
        |--------------------------------------------------------------------------
        |
        | Traemos todos los movimientos de los códigos presentes en el Excel.
        | La fecha NO se usa como filtro excluyente: se usa después para escoger
        | el movimiento más cercano. No filtramos por nombre exacto de sucursal
        | en SQL porque históricamente
        | pueden existir nombres como:
        |
        | SL / SAN LORENZO
        | L06 / L06 SAN LO 2
        | Shopp / SHOP SAN LO3
        |
        | La sucursal se normaliza después en PHP.
        |
        */
        $vinculos = $this->precargarVinculos($filas);
        $otsPorCodigo = $this->precargarOtsPorCodigo($filas);

        /*
        |--------------------------------------------------------------------------
        | REMISIONES YA EXISTENTES
        |--------------------------------------------------------------------------
        */
        $existentes = $this->precargarExistentes($filas);
        $this->usoDetalle = $this->precargarUsoDetalles($vinculos);

        // El uso ya persistido se leyó de BD. Este acumulador solo corresponde
        // a las filas todavía no grabadas del bloque actual.
        $this->asignadoImportacion = [];

        $ahora = now();
        $registros = [];

        foreach ($filas as $fila) {
            $clave = $this->clave(
                $fila['serie'],
                $fila['numero_remision'],
                $fila['codigo'],
                $fila['cod_sucursal_salida'],
                $fila['cod_sucursal_destino']
            );

            $existente = $existentes->get($clave);

            /*
             * Un vínculo ya resuelto se conserva. Solo intentamos reparar filas
             * nuevas o registros históricos que quedaron sin id_logistica_detalle.
             */
            $idOt = $existente->id_ot ?? null;
            $idTrazabilidad = $existente->id_trazabilidad ?? null;
            $idLogisticaDetalle = $existente->id_logistica_detalle ?? null;

            if (!$idLogisticaDetalle) {
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
            } elseif ($existente && (int) $existente->cantidad !== (int) $fila['cantidad']) {
                $diferenciaCantidad = (int) $fila['cantidad'] - (int) $existente->cantidad;
                $this->usoDetalle[$idLogisticaDetalle] =
                    ($this->usoDetalle[$idLogisticaDetalle] ?? 0) + $diferenciaCantidad;
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

            if ($existente) {
                $this->actualizadas++;
            } else {
                $this->insertadas++;
            }

            $registros[] = [
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
                [
                    'id_ot',
                    'id_trazabilidad',
                    'id_logistica_detalle',
                    'fecha_remision',
                    'fecha_creacion',
                    'fecha_recepcion',
                    'sucursal_salida',
                    'sucursal_destino',
                    'sucursal_logistica',
                    'descripcion',
                    'cantidad',
                    'precio_venta',
                    'costo_unitario',
                    'estado',
                    'updated_at',
                ]
            );
        }
    }

    private function normalizarFilas(Collection $rows)
    {
        $filas = collect();

        $codigosDestino = $rows
            ->map(function ($row) {
                return $this->enteroONull($row['cod_sucursal'] ?? null);
            })
            ->filter()
            ->unique()
            ->values();

        $catalogoSucursales = collect();

        if ($codigosDestino->isNotEmpty()) {
            $catalogoSucursales = DB::table('sucursal')
                ->whereIn('cod_suc', $codigosDestino->all())
                ->pluck('suc_descri', 'cod_suc');
        }

        foreach ($rows as $row) {
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
                $cantidad <= 0
            ) {
                $this->omitidas++;
                continue;
            }

            $sucursalSalida = trim((string) ($row['sucursal_salida'] ?? ''));
            $sucursalDestino = trim((string) ($row['sucursal'] ?? ''));
            $nombreCatalogo = $catalogoSucursales->get($codDestino);

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

        /*
         * Protección adicional: si por algún motivo el Excel trae la misma
         * línea repetida, procesamos una sola vez la clave de la remisión.
         */
        return $filas
            ->unique(function ($fila) {
                return $this->clave(
                    $fila['serie'],
                    $fila['numero_remision'],
                    $fila['codigo'],
                    $fila['cod_sucursal_salida'],
                    $fila['cod_sucursal_destino']
                );
            })
            ->values();
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

        if ($candidatos->count() === 1) {
            return $candidatos->first();
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

        $clave = $this->claveVinculo(
            $this->normalizarCodigoBase($codigo),
            $this->normalizarSucursalBase($sucursal)
        );

        $candidatos = collect($vinculos->get($clave, collect()));

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

                if ($a->_cantidad_exacta !== $b->_cantidad_exacta) {
                    return $a->_cantidad_exacta > $b->_cantidad_exacta ? -1 : 1;
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

    private function calcularResumenDocumentos(Collection $filas)
    {
        foreach ($filas as $fila) {
            $claveDocumento = implode('|', [
                $fila['serie'],
                $fila['numero_remision'],
                $fila['cod_sucursal_salida'],
                $fila['cod_sucursal_destino'],
            ]);

            $recibido = !empty($fila['fecha_recepcion']);

            $this->documentosEstado[$claveDocumento] =
                ($this->documentosEstado[$claveDocumento] ?? false) || $recibido;
        }
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
