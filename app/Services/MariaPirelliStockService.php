<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MariaPirelliStockService
{
    public function actual(): array
    {
        $configurado = Schema::hasTable('maria_pirelli_stock_cortes');
        $corte = $configurado ? DB::table('maria_pirelli_stock_cortes')->latest('fecha_corte')->first() : null;
        $sucursales = $corte ? DB::table('maria_pirelli_stock')->where('corte_id', $corte->id)
            ->select('sucursal_clave', 'sucursal')
            ->selectRaw('COUNT(*) AS codigos, SUM(cantidad) AS unidades')
            ->selectRaw('SUM(CASE WHEN cantidad > 0 THEN cantidad ELSE 0 END) AS positivas')
            ->selectRaw('SUM(CASE WHEN cantidad < 0 THEN -CAST(cantidad AS bigint) ELSE 0 END) AS negativas')
            ->selectRaw('SUM(cantidad * costo_unitario) AS costo, SUM(cantidad * precio_venta) AS valor_neto')
            ->selectRaw('SUM(CASE WHEN cantidad > 0 THEN cantidad * precio_venta ELSE 0 END) AS venta_potencial')
            ->groupBy('sucursal_clave', 'sucursal')->orderBy('sucursal')->orderBy('sucursal_clave')
            ->get()->keyBy('sucursal_clave') : collect();

        $totales = [];
        foreach (['unidades', 'positivas', 'negativas', 'costo', 'valor_neto', 'venta_potencial'] as $campo) {
            $totales[$campo] = $sucursales->sum($campo);
        }

        return compact('configurado', 'corte', 'sucursales', 'totales');
    }

    public function articulosPorSucursal(int $corte, string $sucursal, string $buscar): LengthAwarePaginator
    {
        return DB::table('maria_pirelli_stock as s')->join('maria_pirelli_articulos as a', 'a.id', '=', 's.articulo_id')
            ->where('s.corte_id', $corte)->where('s.sucursal_clave', $sucursal)
            ->when($buscar !== '', function ($consulta) use ($buscar) {
                $patron = '%' . mb_strtoupper($buscar) . '%';
                $consulta->where(function ($consulta) use ($patron) {
                    $consulta->whereRaw('UPPER(a.codigo) LIKE ?', [$patron])
                        ->orWhereRaw('UPPER(a.descripcion) LIKE ?', [$patron]);
                });
            })
            ->select('a.codigo', 'a.descripcion', 's.cantidad', 's.costo_unitario', 's.precio_venta')
            ->selectRaw('s.cantidad * s.costo_unitario AS costo, s.cantidad * s.precio_venta AS valor_neto')
            ->orderBy('a.codigo')->paginate(25)->withQueryString()->fragment('stock-sucursal');
    }
}
