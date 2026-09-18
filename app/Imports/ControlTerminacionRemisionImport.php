<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ControlTerminacionRemisionImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private $insertadas = 0;
    private $actualizadas = 0;
    private $sinVincular = 0;
    private $omitidas = 0;

    public function collection(Collection $rows)
    {
        set_time_limit(0);
        DB::disableQueryLog();

        $filas = $this->normalizarFilas($rows);

        if ($filas->isEmpty()) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | PRE-CARGA DE VÍNCULOS LOGÍSTICOS
        |--------------------------------------------------------------------------
        |
        | Antes se consultaba PostgreSQL por CADA fila del Excel.
        | Ahora traemos todos los candidatos del bloque en UNA consulta
        | y resolvemos el vínculo en memoria.
        |
        */
        $vinculos = $this->precargarVinculos($filas);

        /*
        |--------------------------------------------------------------------------
        | PRE-CARGA DE REMISIONES YA EXISTENTES
        |--------------------------------------------------------------------------
        |
        | También se hace una sola consulta para saber qué filas son nuevas
        | y cuáles deben actualizarse.
        |
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
            | CONSERVAR DATOS YA CONFIRMADOS
            |--------------------------------------------------------------------------
            |
            | Si una segunda importación no trae fecha de recepción pero la
            | remisión ya estaba recibida, NO borramos esa confirmación.
            |
            */
            $fechaRemision = $fila['fecha_remision']
                ?: ($existente->fecha_remision ?? null);

            $fechaCreacion = $fila['fecha_creacion']
                ?: ($existente->fecha_creacion ?? null);

            $fechaRecepcion = $fila['fecha_recepcion']
                ?: ($existente->fecha_recepcion ?? null);

            /*
            |--------------------------------------------------------------------------
            | CONSERVAR VÍNCULO YA CORRECTO
            |--------------------------------------------------------------------------
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

            if ($existente) {
                $this->actualizadas++;
            } else {
                $this->insertadas++;
            }

            if (!$idLogisticaDetalle) {
                $this->sinVincular++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | UPSERT MASIVO
        |--------------------------------------------------------------------------
        |
        | Un solo comando SQL inserta/actualiza todo el bloque.
        | PostgreSQL 9.5 soporta ON CONFLICT, utilizado por Laravel upsert().
        |
        */
        DB::table('ot_logistica_remisiones')->upsert(
            $registros,
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

    public function chunkSize(): int
    {
        return 250;
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
                'sucursal_logistica' => $this->resolverSucursalLogistica(
                    $codDestino,
                    $sucursalDestino
                ),

                // El Excel actual tiene el encabezado "Artiuclo".
                'descripcion' => trim((string) (
                    $row['artiuclo']
                    ?? $row['articulo']
                    ?? ''
                )) ?: null,

                'precio_venta' => $this->decimalONull($row['precioventa'] ?? null),
                'costo_unitario' => $this->decimalONull($row['costounitario'] ?? null),
            ]);
        }

        return $filas;
    }

    private function precargarVinculos(Collection $filas)
    {
        $sucursales = $filas
            ->pluck('sucursal_logistica')
            ->filter()
            ->unique()
            ->values();

        if ($sucursales->isEmpty()) {
            return collect();
        }

        $codigos = $filas
            ->pluck('codigo')
            ->filter()
            ->unique()
            ->values();

        if ($codigos->isEmpty()) {
            return collect();
        }

        /*
         * Compatibilidad por si algún código de OT quedó guardado
         * con apóstrofe inicial.
         */
        $codigosConsulta = $codigos
            ->flatMap(function ($codigo) {
                return [$codigo, "'" . $codigo];
            })
            ->unique()
            ->values();

        $fechaMaxima = $filas
            ->map(function ($fila) {
                return $fila['fecha_remision'] ?: $fila['fecha_creacion'];
            })
            ->filter()
            ->max();

        $query = DB::table('ot_logistica_detalle as d')
            ->join('ot as o', 'o.id_ot', '=', 'd.id_ot')
            ->join('ot_trazabilidad as t', 't.id_trazabilidad', '=', 'd.id_trazabilidad')
            ->whereIn('o.codigo', $codigosConsulta->all())
            ->whereIn('d.sucursal', $sucursales->all())
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

        if ($fechaMaxima) {
            $query->whereDate('t.fecha_proceso', '<=', $fechaMaxima);
        }

        return $query
            ->orderBy('t.fecha_proceso', 'desc')
            ->orderBy('d.id', 'desc')
            ->get()
            ->groupBy(function ($item) {
                return $this->claveVinculo(
                    $this->normalizarCodigo($item->codigo),
                    $item->sucursal
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

        $candidatos = collect(
            $vinculos->get(
                $this->claveVinculo($codigo, $sucursal),
                collect()
            )
        );

        if ($fechaReferencia) {
            $candidatos = $candidatos->filter(function ($item) use ($fechaReferencia) {
                return $item->fecha_proceso <= $fechaReferencia;
            })->values();
        }

        if ($candidatos->isEmpty()) {
            return null;
        }

        /*
         * Primero intentamos cantidad exacta.
         * Si no existe, tomamos la distribución más reciente anterior
         * o igual a la remisión.
         */
        $exacto = $candidatos->first(function ($item) use ($cantidad) {
            return (int) $item->cantidad === (int) $cantidad;
        });

        return $exacto ?: $candidatos->first();
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

    private function resolverSucursalLogistica($codigo, $nombre)
    {
        /*
         * Códigos usados actualmente por los locales del sistema.
         * Si aparece uno nuevo, abajo existe además resolución por nombre.
         */
        $porCodigo = [
            2 => 'SL',
            3 => 'Luque',
            4 => 'Mall',
            5 => 'L06',
            6 => 'Rural',
            7 => 'Bonanza',
            8 => 'Shopp',
            9 => 'Multi',
            14 => 'Pinedo',
            15 => 'Ñemby',
            16 => 'Mariano',
            22 => 'Los Jardines',
        ];

        if ($codigo && isset($porCodigo[$codigo])) {
            return $porCodigo[$codigo];
        }

        $normalizado = $this->sinAcentos(strtoupper(trim((string) $nombre)));

        $reglas = [
            'SAN LORENZO' => 'SL',
            'SAN LO' => 'SL',
            'LUQUE' => 'Luque',
            'MALL' => 'Mall',
            'L06' => 'L06',
            'RURAL' => 'Rural',
            'BONANZA' => 'Bonanza',
            'SHOP SAN LO' => 'Shopp',
            'SHOPP' => 'Shopp',
            'MULTIPLAZA' => 'Multi',
            'MULTI' => 'Multi',
            'PINEDO' => 'Pinedo',
            'NEMBY' => 'Ñemby',
            'MARIANO' => 'Mariano',
            'JARDINES' => 'Los Jardines',
            'AYALA' => 'Ayala',
            'MODELO' => 'Modelo Muestra',
        ];

        /*
         * Primero reglas más específicas para evitar que "SHOP SAN LO3"
         * sea interpretado como "SL".
         */
        if (strpos($normalizado, 'SHOP SAN LO') !== false) {
            return 'Shopp';
        }

        if (strpos($normalizado, 'L06') !== false) {
            return 'L06';
        }

        foreach ($reglas as $texto => $alias) {
            if (strpos($normalizado, $texto) !== false) {
                return $alias;
            }
        }

        return null;
    }

    private function clave($serie, $numero, $codigo, $origen, $destino)
    {
        return implode('|', [
            (string) $serie,
            (string) $numero,
            (string) $codigo,
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

    public function getInsertadas()
    {
        return $this->insertadas;
    }

    public function getActualizadas()
    {
        return $this->actualizadas;
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
