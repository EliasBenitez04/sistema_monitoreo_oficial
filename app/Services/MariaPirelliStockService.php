<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MariaPirelliStockService
{
    public function actual(): array
    {
        return $this->resumen();
    }

    public function alCorte(Builder $catalogo, string $hasta): array
    {
        return $this->resumen($hasta, $catalogo);
    }

    public function fechas(Builder $catalogo, string $desde, string $hasta): Collection
    {
        if (!Schema::hasTable('maria_pirelli_stock_cortes') || !Schema::hasTable('maria_pirelli_stock')) {
            return collect();
        }

        return DB::table('maria_pirelli_stock_cortes as c')
            ->join('maria_pirelli_stock as s', 's.corte_id', '=', 'c.id')
            ->joinSub($catalogo, 'a', 'a.id', '=', 's.articulo_id')
            ->whereBetween('c.fecha_corte', [$desde, $hasta])
            ->distinct()->get(['c.fecha_corte', 's.sucursal']);
    }

    private function resumen(?string $hasta = null, ?Builder $catalogo = null): array
    {
        $configurado = Schema::hasTable('maria_pirelli_stock_cortes') && Schema::hasTable('maria_pirelli_stock');
        $corte = $configurado ? DB::table('maria_pirelli_stock_cortes')
            ->when($hasta !== null, fn ($q) => $q->where('fecha_corte', '<=', $hasta))
            ->latest('fecha_corte')->first() : null;
        $sucursales = collect();
        if ($corte) {
            $consulta = DB::table('maria_pirelli_stock as s')->where('s.corte_id', $corte->id);
            if ($catalogo !== null) {
                $consulta->joinSub($catalogo, 'a', 'a.id', '=', 's.articulo_id');
            }
            $sucursales = $consulta->select('s.sucursal_clave', 's.sucursal')
                ->selectRaw('COUNT(*) AS codigos, SUM(s.cantidad) AS unidades')
                ->selectRaw('SUM(CASE WHEN s.cantidad > 0 THEN s.cantidad ELSE 0 END) AS positivas')
                ->selectRaw('SUM(CASE WHEN s.cantidad < 0 THEN -CAST(s.cantidad AS bigint) ELSE 0 END) AS negativas')
                ->selectRaw('SUM(s.cantidad * s.costo_unitario) AS costo, SUM(s.cantidad * s.precio_venta) AS valor_neto')
                ->selectRaw('SUM(CASE WHEN s.cantidad > 0 THEN s.cantidad * s.precio_venta ELSE 0 END) AS venta_potencial')
                ->groupBy('s.sucursal_clave', 's.sucursal')->orderBy('s.sucursal')->orderBy('s.sucursal_clave')
                ->get()->keyBy('sucursal_clave');
        }

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
