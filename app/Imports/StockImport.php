<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;

class StockImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        // Guardar combinaciones importadas
        $importados = [];

        foreach ($rows->skip(1) as $row) {

            $sucursal   = trim($row[0] ?? '');
            $cantidad   = (float) ($row[1] ?? 0);
            $descripcion = trim($row[2] ?? '');
            $codigo     = trim($row[3] ?? '');

            // Validar datos mínimos
            if ($sucursal === '' || $codigo === '') {
                continue;
            }

            // Guardar combinación para luego comparar
            $clave = $sucursal . '|' . $codigo;
            $importados[] = $clave;

            // Insertar o actualizar
            DB::statement("
                INSERT INTO stock_sucursales
                    (sucursal, codigo, descripcion, cantidad, updated_at)
                VALUES (?, ?, ?, ?, NOW())
                ON CONFLICT (sucursal, codigo)
                DO UPDATE SET
                    cantidad = EXCLUDED.cantidad,
                    descripcion = EXCLUDED.descripcion,
                    updated_at = NOW()
            ", [
                $sucursal,
                $codigo,
                $descripcion,
                $cantidad
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | PONER EN 0 LOS QUE YA NO EXISTEN EN EL EXCEL
        |--------------------------------------------------------------------------
        */

        $stocks = DB::table('stock_sucursales')
            ->select('sucursal', 'codigo')
            ->get();

        foreach ($stocks as $stock) {

            $claveBD = $stock->sucursal . '|' . $stock->codigo;

            // Si no vino en el Excel → stock 0
            if (!in_array($claveBD, $importados)) {

                DB::table('stock_sucursales')
                    ->where('sucursal', $stock->sucursal)
                    ->where('codigo', $stock->codigo)
                    ->update([
                        'cantidad' => 0,
                        'updated_at' => now()
                    ]);
            }
        }
    }
}
