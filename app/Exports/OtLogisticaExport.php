<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class OtLogisticaExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    ShouldAutoSize
{
    protected $fecha_desde;
    protected $fecha_hasta;
    protected $sucursal;
    protected $nro_ot;

    public function __construct(
        $fecha_desde = null,
        $fecha_hasta = null,
        $sucursal = null,
        $nro_ot = null
    ) {
        $this->fecha_desde = $fecha_desde;
        $this->fecha_hasta = $fecha_hasta;
        $this->sucursal = $sucursal;
        $this->nro_ot = $nro_ot;
    }


    public function query()
    {
        $query = DB::table(
            'ot_logistica_detalle as d'
        )

            ->leftJoin(
                'ot_trazabilidad as t',
                't.id_trazabilidad',
                '=',
                'd.id_trazabilidad'
            )

            ->leftJoin(
                'ot as o',
                'o.id_ot',
                '=',
                'd.id_ot'
            )

            ->select([
                't.fecha_proceso',

                'o.nro_ot',

                'o.codigo',

                'o.descripcion',

                'd.sucursal',

                'd.cantidad',

                't.proceso',

                't.resultado',

                'd.created_at',

                'd.id',
            ]);


        /*
        |--------------------------------------------------------------------------
        | FECHA DESDE
        |--------------------------------------------------------------------------
        */

        if (!empty($this->fecha_desde)) {

            $query->whereDate(
                't.fecha_proceso',
                '>=',
                $this->fecha_desde
            );
        }


        /*
        |--------------------------------------------------------------------------
        | FECHA HASTA
        |--------------------------------------------------------------------------
        */

        if (!empty($this->fecha_hasta)) {

            $query->whereDate(
                't.fecha_proceso',
                '<=',
                $this->fecha_hasta
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SUCURSAL
        |--------------------------------------------------------------------------
        */

        if (!empty($this->sucursal)) {

            $sucursales = is_array($this->sucursal)
                ? $this->sucursal
                : [$this->sucursal];

            $sucursales = array_filter(
                array_map('trim', $sucursales),
                fn($valor) => $valor !== ''
            );

            if (!empty($sucursales)) {
                $query->whereIn('d.sucursal', $sucursales);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | N° OT
        |--------------------------------------------------------------------------
        */

        if (!empty($this->nro_ot)) {

            $query->where(
                'o.nro_ot',
                $this->nro_ot
            );
        }


        /*
        |--------------------------------------------------------------------------
        | ORDEN PRINCIPAL
        |--------------------------------------------------------------------------
        |
        | FECHA
        |   ↓
        | OT
        |   ↓
        | CÓDIGO
        |   ↓
        | SUCURSAL
        |
        */

        return $query

            ->orderByRaw(
                't.fecha_proceso IS NULL ASC'
            )

            ->orderByDesc(
                't.fecha_proceso'
            )

            ->orderBy(
                'o.nro_ot',
                'asc'
            )

            ->orderBy(
                'o.codigo',
                'asc'
            )

            ->orderBy(
                'd.sucursal',
                'asc'
            )

            ->orderByDesc(
                'd.created_at'
            )

            ->orderByDesc(
                'd.id'
            );
    }


    public function headings(): array
    {
        return [
            'Fecha',
            'N° OT',
            'Código',
            'Artículo',
            'Sucursal',
            'Cantidad',
            'Proceso',
            'Resultado',
        ];
    }


    public function map($row): array
    {
        return [

            $row->fecha_proceso
                ? date(
                    'd/m/Y',
                    strtotime($row->fecha_proceso)
                )
                : '',

            $row->nro_ot ?? '',

            $row->codigo ?? '',

            $row->descripcion ?? '',

            $row->sucursal ?? '',

            $row->cantidad ?? 0,

            $row->proceso ?? '',

            $row->resultado ?? '',
        ];
    }
}
