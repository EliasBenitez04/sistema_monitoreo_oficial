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

        $filas = collect();

        foreach ($rows as $row) {
            $codigo = $this->normalizarCodigo($row['cod_articulo'] ?? null);
            $serie = trim((string) ($row['serie'] ?? ''));
            $numeroRemision = trim((string) ($row['numero_remision'] ?? ''));
            $codSalida = $this->enteroONull($row['cod_sucursal_salida'] ?? null);
            $codDestino = $this->enteroONull($row['cod_sucursal'] ?? null);
            $cantidad = (int) ($row['cantidad'] ?? 0);

            if ($codigo === '' || $serie === '' || $numeroRemision === '' || !$codDestino || $cantidad <= 0) {
                $this->omitidas++;
                continue;
            }

            $fechaRemision = $this->parseDate($row['fecha_remision'] ?? null);
            $fechaCreacion = $this->parseDate($row['fecha_creacion'] ?? null);
            $fechaRecepcion = $this->parseDate($row['fecha_recepcion'] ?? null);

            $sucursalSalida = trim((string) ($row['sucursal_salida'] ?? ''));
            $sucursalDestino = trim((string) ($row['sucursal'] ?? ''));
            $sucursalLogistica = $this->resolverSucursalLogistica($codDestino, $sucursalDestino);

            $filas->push([
                'codigo' => $codigo,
                'serie' => $serie,
                'numero_remision' => $numeroRemision,
                'cod_sucursal_salida' => $codSalida,
                'cod_sucursal_destino' => $codDestino,
                'cantidad' => $cantidad,
                'fecha_remision' => $fechaRemision,
                'fecha_creacion' => $fechaCreacion,
                'fecha_recepcion' => $fechaRecepcion,
                'sucursal_salida' => $sucursalSalida !== '' ? $sucursalSalida : null,
                'sucursal_destino' => $sucursalDestino !== '' ? $sucursalDestino : null,
                'sucursal_logistica' => $sucursalLogistica,
                'descripcion' => trim((string) ($row['artiuclo'] ?? $row['articulo'] ?? '')) ?: null,
                'precio_venta' => $this->decimalONull($row['precioventa'] ?? null),
                'costo_unitario' => $this->decimalONull($row['costounitario'] ?? null),
            ]);
        }

        if ($filas->isEmpty()) {
            return;
        }

        $existentes = DB::table('ot_logistica_remisiones')
            ->whereIn('serie', $filas->pluck('serie')->unique()->values()->all())
            ->whereIn('numero_remision', $filas->pluck('numero_remision')->unique()->values()->all())
            ->whereIn('codigo', $filas->pluck('codigo')->unique()->values()->all())
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

        $nuevos = [];
        $ahora = now();

        DB::beginTransaction();

        try {
            foreach ($filas as $fila) {
                $clave = $this->clave(
                    $fila['serie'],
                    $fila['numero_remision'],
                    $fila['codigo'],
                    $fila['cod_sucursal_salida'],
                    $fila['cod_sucursal_destino']
                );

                $existente = $existentes->get($clave);

                $vinculo = $this->buscarVinculo(
                    $fila['codigo'],
                    $fila['sucursal_logistica'],
                    $fila['cantidad'],
                    $fila['fecha_remision'] ?: $fila['fecha_creacion']
                );

                $datos = [
                    'id_ot' => $vinculo ? $vinculo->id_ot : null,
                    'id_trazabilidad' => $vinculo ? $vinculo->id_trazabilidad : null,
                    'id_logistica_detalle' => $vinculo ? $vinculo->id_logistica_detalle : null,
                    'fecha_remision' => $fila['fecha_remision'],
                    'fecha_creacion' => $fila['fecha_creacion'],
                    'fecha_recepcion' => $fila['fecha_recepcion'],
                    'sucursal_salida' => $fila['sucursal_salida'],
                    'sucursal_destino' => $fila['sucursal_destino'],
                    'sucursal_logistica' => $fila['sucursal_logistica'],
                    'descripcion' => $fila['descripcion'],
                    'cantidad' => $fila['cantidad'],
                    'precio_venta' => $fila['precio_venta'],
                    'costo_unitario' => $fila['costo_unitario'],
                    'estado' => $fila['fecha_recepcion'] ? 'RECIBIDO' : 'EN_TRANSITO',
                    'updated_at' => $ahora,
                ];

                if ($existente) {
                    if (!$datos['fecha_remision']) {
                        unset($datos['fecha_remision']);
                    }

                    if (!$datos['fecha_creacion']) {
                        unset($datos['fecha_creacion']);
                    }

                    if (!$datos['fecha_recepcion']) {
                        unset($datos['fecha_recepcion']);

                        if (!empty($existente->fecha_recepcion)) {
                            $datos['estado'] = 'RECIBIDO';
                        }
                    }

                    if (!$vinculo && !empty($existente->id_logistica_detalle)) {
                        unset(
                            $datos['id_ot'],
                            $datos['id_trazabilidad'],
                            $datos['id_logistica_detalle']
                        );
                    }

                    DB::table('ot_logistica_remisiones')
                        ->where('id', $existente->id)
                        ->update($datos);

                    $this->actualizadas++;
                } else {
                    $nuevos[] = array_merge([
                        'serie' => $fila['serie'],
                        'numero_remision' => $fila['numero_remision'],
                        'codigo' => $fila['codigo'],
                        'cod_sucursal_salida' => $fila['cod_sucursal_salida'],
                        'cod_sucursal_destino' => $fila['cod_sucursal_destino'],
                        'created_at' => $ahora,
                    ], $datos);

                    $this->insertadas++;
                }

                if (!$vinculo && (!$existente || empty($existente->id_logistica_detalle))) {
                    $this->sinVincular++;
                }
            }

            foreach (array_chunk($nuevos, 100) as $lote) {
                DB::table('ot_logistica_remisiones')->insert($lote);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function chunkSize(): int
    {
        return 250;
    }

    private function buscarVinculo($codigo, $sucursalLogistica, $cantidad, $fechaReferencia)
    {
        if (!$sucursalLogistica) {
            return null;
        }

        $query = DB::table('ot_logistica_detalle as d')
            ->join('ot as o', 'o.id_ot', '=', 'd.id_ot')
            ->join('ot_trazabilidad as t', 't.id_trazabilidad', '=', 'd.id_trazabilidad')
            ->whereRaw("UPPER(REPLACE(TRIM(o.codigo), '''', '')) = ?", [strtoupper($codigo)])
            ->where('d.sucursal', $sucursalLogistica)
            ->where('t.proceso', 'LOGISTICA - LOGISTICA Y DISTRIBUCION')
            ->select(
                'd.id as id_logistica_detalle',
                'd.id_ot',
                'd.id_trazabilidad',
                'd.cantidad',
                't.fecha_proceso'
            );

        if ($fechaReferencia) {
            $query->whereDate('t.fecha_proceso', '<=', $fechaReferencia);
        }

        return $query
            ->orderByRaw('CASE WHEN d.cantidad = ? THEN 0 ELSE 1 END', [$cantidad])
            ->orderBy('t.fecha_proceso', 'desc')
            ->orderBy('d.id', 'desc')
            ->first();
    }

    private function resolverSucursalLogistica($codigo, $nombre)
    {
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
            'LUQUE' => 'Luque',
            'MALL' => 'Mall',
            'L06' => 'L06',
            'RURAL' => 'Rural',
            'BONANZA' => 'Bonanza',
            'SHOP SAN LO' => 'Shopp',
            'MULTIPLAZA' => 'Multi',
            'PINEDO' => 'Pinedo',
            'NEMBY' => 'Ñemby',
            'MARIANO' => 'Mariano',
            'JARDINES' => 'Los Jardines',
            'AYALA' => 'Ayala',
            'MODELO' => 'Modelo Muestra',
        ];

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

        return is_numeric($limpio) ? (float) $limpio : null;
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
                return Carbon::instance(ExcelDate::excelToDateTimeObject($valor))->format('Y-m-d');
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
                // Probar el siguiente formato.
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
