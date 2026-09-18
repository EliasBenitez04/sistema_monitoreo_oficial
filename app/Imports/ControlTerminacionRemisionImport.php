<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ControlTerminacionRemisionImport implements ToCollection, WithHeadingRow
{
    private $procesadas = 0;
    private $insertadas = 0;
    private $actualizadas = 0;
    private $vinculadas = 0;
    private $sinVincular = 0;
    private $omitidas = 0;

    public function collection(Collection $rows)
    {
        set_time_limit(0);
        DB::disableQueryLog();

        /*
        |--------------------------------------------------------------------------
        | IMPORTANTE
        |--------------------------------------------------------------------------
        |
        | El archivo ENVIOS actual tiene aproximadamente 1.075 registros.
        | NO usamos WithChunkReading porque en este proyecto/versión estaba
        | provocando que la misma hoja se procesara repetidas veces.
        |
        */
        $filas = $this->normalizarFilas($rows);

        if ($filas->isEmpty()) {
            return;
        }

        $this->procesadas += $filas->count();

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

        /*
        |--------------------------------------------------------------------------
        | REMISIONES YA EXISTENTES
        |--------------------------------------------------------------------------
        */
        $existentes = $this->precargarExistentes($filas);

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

            $vinculo = $this->resolverVinculoEnMemoria(
                $vinculos,
                $fila['codigo'],
                $fila['sucursal_logistica'],
                $fila['cantidad'],
                $fila['fecha_remision'] ?: $fila['fecha_creacion']
            );

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

            /*
            |--------------------------------------------------------------------------
            | CONSERVAR / CORREGIR VÍNCULO
            |--------------------------------------------------------------------------
            |
            | Si ahora encontramos un vínculo mejor, reemplaza el vínculo anterior.
            | Si no encontramos ninguno, conservamos uno previo que ya fuera válido.
            |
            */
            $idOt = $vinculo
                ? $vinculo->id_ot
                : ($existente->id_ot ?? null);

            $idTrazabilidad = $vinculo
                ? $vinculo->id_trazabilidad
                : ($existente->id_trazabilidad ?? null);

            $idLogisticaDetalle = $vinculo
                ? $vinculo->id_logistica_detalle
                : ($existente->id_logistica_detalle ?? null);

            if ($idLogisticaDetalle) {
                $this->vinculadas++;
            } else {
                $this->sinVincular++;
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

        foreach ($rows as $row) {
            $codigo = $this->normalizarCodigo($row['cod_articulo'] ?? null);
            $serie = trim((string) ($row['serie'] ?? ''));
            $numeroRemision = trim((string) ($row['numero_remision'] ?? ''));
            $codSalida = $this->enteroONull($row['cod_sucursal_salida'] ?? null);
            $codDestino = $this->enteroONull($row['cod_sucursal'] ?? null);
            $cantidad = (int) ($row['cantidad'] ?? 0);

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
                    $sucursalDestino
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
                return strtoupper($this->normalizarCodigo($codigo));
            })
            ->unique()
            ->values();

        if ($codigos->isEmpty()) {
            return collect();
        }

        /*
         * Normalizamos el código directamente en SQL:
         * - trim de espacios
         * - elimina apóstrofe inicial
         * - mayúsculas
         */
        $query = DB::table('ot_logistica_detalle as d')
            ->join('ot as o', 'o.id_ot', '=', 'd.id_ot')
            ->join('ot_trazabilidad as t', 't.id_trazabilidad', '=', 'd.id_trazabilidad')
            ->whereIn(
                DB::raw("UPPER(REPLACE(TRIM(o.codigo), '''', ''))"),
                $codigos->all()
            )
            ->where('t.proceso', 'LOGISTICA - LOGISTICA Y DISTRIBUCION')
            ->select(
                'd.id as id_logistica_detalle',
                'd.id_ot',
                'd.id_trazabilidad',
                'd.sucursal',
                'd.cantidad',
                'o.codigo',
                't.fecha_proceso'
            );

        $candidatos = $query
            ->orderBy('t.fecha_proceso', 'desc')
            ->orderBy('d.id', 'desc')
            ->get();

        return $candidatos
            ->map(function ($item) {
                $item->codigo_normalizado = strtoupper(
                    $this->normalizarCodigo($item->codigo)
                );

                $item->sucursal_normalizada =
                    $this->normalizarSucursalBase($item->sucursal);

                return $item;
            })
            ->filter(function ($item) {
                return !empty($item->sucursal_normalizada);
            })
            ->groupBy(function ($item) {
                return $this->claveVinculo(
                    $item->codigo_normalizado,
                    $item->sucursal_normalizada
                );
            });
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
            strtoupper($this->normalizarCodigo($codigo)),
            $this->normalizarSucursalBase($sucursal)
        );

        $candidatos = collect(
            $vinculos->get($clave, collect())
        );

        if ($candidatos->isEmpty()) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | CRITERIO DE ASOCIACIÓN
        |--------------------------------------------------------------------------
        |
        | CONDICIONES OBLIGATORIAS:
        |   1) mismo código;
        |   2) mismo local destino normalizado.
        |
        | PRIORIDAD:
        |   A) misma cantidad del detalle logístico;
        |   B) fecha logística más cercana a la fecha de remisión/creación;
        |   C) ante empate, preferimos logística del mismo día o anterior;
        |   D) ante otro empate, el movimiento más reciente.
        |
        | No exigimos que logística sea <= remisión porque en la operación real
        | las cargas de ambos archivos pueden registrarse en días diferentes.
        |
        */

        $fecha = null;

        if ($fechaReferencia) {
            try {
                $fecha = Carbon::parse($fechaReferencia)->startOfDay();
            } catch (\Throwable $e) {
                $fecha = null;
            }
        }

        $evaluados = $candidatos->map(function ($item) use ($cantidad, $fecha) {
            $item->_cantidad_exacta =
                ((int) $item->cantidad === (int) $cantidad) ? 1 : 0;

            $item->_distancia_dias = 999999;
            $item->_posterior = 1;

            if ($fecha && !empty($item->fecha_proceso)) {
                try {
                    $fechaLogistica = Carbon::parse($item->fecha_proceso)->startOfDay();

                    $item->_distancia_dias =
                        abs($fechaLogistica->diffInDays($fecha, false));

                    /*
                     * 0 = mismo día o anterior a la remisión.
                     * 1 = posterior.
                     *
                     * Esto solo desempata; no excluye fechas posteriores.
                     */
                    $item->_posterior =
                        $fechaLogistica->gt($fecha) ? 1 : 0;
                } catch (\Throwable $e) {
                    // Se mantiene distancia alta.
                }
            }

            return $item;
        });

        /*
         * Evitamos asociaciones absurdamente alejadas.
         *
         * Si existe fecha de referencia, permitimos hasta 45 días de distancia.
         * Si no hay ningún candidato dentro de esa ventana, dejamos la remisión
         * sin vínculo para no asociarla a una OT histórica incorrecta.
         */
        if ($fecha) {
            $cercanos = $evaluados
                ->filter(function ($item) {
                    return $item->_distancia_dias <= 45;
                })
                ->values();

            if ($cercanos->isNotEmpty()) {
                $evaluados = $cercanos;
            } else {
                return null;
            }
        }

        return $evaluados
            ->sort(function ($a, $b) {
                // 1. Cantidad exacta primero.
                if ($a->_cantidad_exacta !== $b->_cantidad_exacta) {
                    return $a->_cantidad_exacta > $b->_cantidad_exacta ? -1 : 1;
                }

                // 2. Menor distancia de fecha.
                if ($a->_distancia_dias !== $b->_distancia_dias) {
                    return $a->_distancia_dias < $b->_distancia_dias ? -1 : 1;
                }

                // 3. Mismo día/anterior antes que posterior.
                if ($a->_posterior !== $b->_posterior) {
                    return $a->_posterior < $b->_posterior ? -1 : 1;
                }

                // 4. Movimiento logístico más reciente.
                if ($a->fecha_proceso !== $b->fecha_proceso) {
                    return strcmp((string) $b->fecha_proceso, (string) $a->fecha_proceso);
                }

                // 5. Último detalle como desempate final.
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

    private function resolverSucursalImportada($codigo, $nombre)
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
        ];

        if ($codigo && isset($porCodigo[$codigo])) {
            return $porCodigo[$codigo];
        }

        return $this->normalizarSucursalBase($nombre);
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

        return $valor;
    }

    private function clave($serie, $numero, $codigo, $origen, $destino)
    {
        return implode('|', [
            (string) $serie,
            (string) $numero,
            strtoupper($this->normalizarCodigo($codigo)),
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

    private function normalizarCodigo($valor)
    {
        $codigo = trim((string) $valor);
        return ltrim($codigo, "'");
    }

    private function enteroONull($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return (int) $valor;
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

    public function getOmitidas()
    {
        return $this->omitidas;
    }
}
