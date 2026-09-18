<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class VentasStockImport implements ToCollection
{

    public function collection(Collection $rows)
    {

        foreach ($rows->skip(1) as $row) {


            /*
            |--------------------------------------------------------------------------
            | COLUMNAS DEL EXCEL
            |--------------------------------------------------------------------------
            |
            | A = SUCURSAL
            | B = LINEA
            | C = GRUPO PLAN
            | D = TALLE
            | E = COLOR
            | F = TEJIDO
            | G = CODIGO
            | H = TEMPORADA
            | I = CANT VTA
            | J = STOCK ACTUAL
            | K = PERIODO
            |
            */


            $sucursal_id = (int) ($row[0] ?? 0);

            $linea = trim((string) ($row[1] ?? ''));

            $grupo_plan = trim((string) ($row[2] ?? ''));

            $talle = trim((string) ($row[3] ?? ''));

            $color = trim((string) ($row[4] ?? ''));

            $tejido = trim((string) ($row[5] ?? ''));

            $codigo = trim((string) ($row[6] ?? ''));

            $temporada = trim((string) ($row[7] ?? ''));


            // CANTIDAD VENDIDA

            $cant_vta = is_numeric($row[8] ?? null)
                ? (int) $row[8]
                : 0;


            // STOCK ACTUAL

            $stock_actual = is_numeric($row[9] ?? null)
                ? (int) $row[9]
                : 0;


            // PERIODO

            $periodo = null;

            if (!empty($row[10])) {

                // Si Excel envía la fecha como número
                if (is_numeric($row[10])) {

                    $periodo = Date::excelToDateTimeObject($row[10])
                        ->format('Y-m-d');
                } else {

                    // Si ya viene como texto
                    $periodo = date(
                        'Y-m-d',
                        strtotime($row[10])
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | VALIDACIONES
            |--------------------------------------------------------------------------
            */

            if (

                $sucursal_id == 0 ||

                $codigo == '' ||

                $periodo == null

            ) {

                continue;
            }



            /*
            |--------------------------------------------------------------------------
            | INSERTAR O ACTUALIZAR
            |--------------------------------------------------------------------------
            */

            DB::statement(

                "

                INSERT INTO stock_ventas_sucursales
                (

                    sucursal_id,
                    linea,
                    grupo_plan,
                    talle,
                    color,
                    tejido,
                    codigo,
                    temporada,
                    cant_vta,
                    stock_actual,
                    periodo,
                    fecha_importacion

                )

                VALUES
                (

                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    NOW()

                )


                ON CONFLICT
                (

                    sucursal_id,
                    codigo,
                    periodo

                )

                DO UPDATE SET

                    linea = EXCLUDED.linea,

                    grupo_plan = EXCLUDED.grupo_plan,

                    talle = EXCLUDED.talle,

                    color = EXCLUDED.color,

                    tejido = EXCLUDED.tejido,

                    temporada = EXCLUDED.temporada,

                    cant_vta = EXCLUDED.cant_vta,

                    stock_actual = EXCLUDED.stock_actual,

                    fecha_importacion = NOW()

                ",

                [

                    $sucursal_id,
                    $linea,
                    $grupo_plan,
                    $talle,
                    $color,
                    $tejido,
                    $codigo,
                    $temporada,
                    $cant_vta,
                    $stock_actual,
                    $periodo

                ]

            );
        }
    }
}
