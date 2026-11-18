<?php

namespace App\Http\Controllers;

use App\Imports\MariaPirelliCatalogoImport;
use App\Services\AccesoriosStockService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class MariaPirelliController extends Controller
{
    public function index(Request $request)
    {
        $configurado = Schema::hasTable('maria_pirelli_articulos');
        if (!$configurado) {
            return view('accesorios.maria_pirelli', compact('configurado'));
        }

        $filtros = $request->validate([
            'desde' => 'nullable|date_format:Y-m-d',
            'hasta' => 'nullable|date_format:Y-m-d',
            'linea' => 'nullable|string|max:100',
            'buscar' => 'nullable|string|max:100',
            'sucursal' => 'nullable|string|max:120',
        ]);
        $hoy = now();
        $desde = $filtros['desde'] ?? $hoy->copy()->startOfYear()->toDateString();
        $hasta = $filtros['hasta'] ?? $hoy->toDateString();
        if ($desde > $hasta) {
            throw ValidationException::withMessages(['hasta' => 'La fecha final debe ser igual o posterior a la inicial.']);
        }

        $linea = $filtros['linea'] ?? '';
        $buscar = $filtros['buscar'] ?? '';
        $sucursal = $filtros['sucursal'] ?? '';

        $catalogo = DB::table('maria_pirelli_articulos as a')
            ->when($linea !== '', fn ($q) => $q->where('a.linea', $linea))
            ->when($buscar !== '', function ($q) use ($buscar) {
                $patron = '%' . mb_strtoupper($buscar) . '%';
                $q->where(function ($q) use ($patron) {
                    $q->whereRaw('UPPER(a.codigo) LIKE ?', [$patron])
                        ->orWhereRaw('UPPER(a.descripcion) LIKE ?', [$patron]);
                });
            });
        $articulos = (clone $catalogo)->orderBy('a.codigo')->paginate(25)->withQueryString();
        $lineas = DB::table('maria_pirelli_articulos')->whereNotNull('linea')
            ->distinct()->orderBy('linea')->pluck('linea');
        $hayCatalogo = DB::table('maria_pirelli_articulos')->exists();

        $conRemisiones = Schema::hasTable('ot_logistica_remisiones');
        $conVentas = Schema::hasTable('ventas');
        $fuentes = [];
        if ($articulos->total() > 0) {
            $sucursalesAccesorios = new AccesoriosStockService();
            if ($conRemisiones) {
                $fuentes[] = $this->envios($catalogo, $desde, $hasta, $sucursalesAccesorios);
            }
            if ($conVentas) {
                $fuentes[] = $this->ventas($catalogo, $desde, $hasta, $sucursalesAccesorios);
            }
        }

        // Agrupar cada fuente por separado evita multiplicar las líneas de venta y remisión.
        $ceros = ['enviadas' => 0, 'vendidas' => 0, 'venta_neta' => 0.0];
        $campos = array_keys($ceros);
        $totales = $ceros;
        $meses = $porArticulo = $sucursales = [];
        $filtroSucursal = AccesoriosStockService::normalizar($sucursal);
        foreach ($fuentes as $fuente) {
            foreach ($fuente as $fila) {
                $claveLocal = AccesoriosStockService::normalizar($fila->sucursal);
                if ($claveLocal !== '') {
                    $sucursales[$claveLocal] ??= $fila->sucursal;
                }
                if ($filtroSucursal !== '' && $claveLocal !== $filtroSucursal) {
                    continue;
                }

                $meses[$fila->mes] ??= $ceros;
                $porArticulo[$fila->articulo_id] ??= $ceros;
                foreach ($campos as $campo) {
                    $totales[$campo] += $fila->{$campo};
                    $meses[$fila->mes][$campo] += $fila->{$campo};
                    $porArticulo[$fila->articulo_id][$campo] += $fila->{$campo};
                }
                if (isset($fila->ultimo_envio)) {
                    $envioAnterior = $porArticulo[$fila->articulo_id]['ultimo_envio'] ?? '';
                    $porArticulo[$fila->articulo_id]['ultimo_envio'] = max($envioAnterior, $fila->ultimo_envio);
                }
            }
        }
        if ($filtroSucursal !== '') {
            $sucursales[$filtroSucursal] ??= $sucursal;
            $sucursal = $sucursales[$filtroSucursal];
        }
        ksort($sucursales, SORT_NATURAL | SORT_FLAG_CASE);
        krsort($meses);

        return view('accesorios.maria_pirelli', compact(
            'configurado', 'hayCatalogo', 'conRemisiones', 'conVentas', 'desde', 'hasta',
            'linea', 'buscar', 'sucursal', 'lineas', 'sucursales', 'articulos',
            'totales', 'meses', 'porArticulo'
        ));
    }

    public function importar(Request $request)
    {
        $request->validate(['archivo' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240']);
        if (!Schema::hasTable('maria_pirelli_articulos')) {
            return redirect()->route('accesorios.maria-pirelli.index')
                ->with('error', 'Ejecutá la migración de MARIA PIRELLI antes de importar.');
        }

        $importador = new MariaPirelliCatalogoImport();
        try {
            Excel::import($importador, $request->file('archivo'));
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            return back()->with('error', 'No se pudo leer el archivo. Revisá el formato e intentá de nuevo.');
        }

        return redirect()->route('accesorios.maria-pirelli.index')->with(
            'success',
            'Importación completada. Se guardaron ' . number_format($importador->articulos, 0, ',', '.') . ' códigos.'
        );
    }

    private function envios(
        Builder $catalogo,
        string $desde,
        string $hasta,
        AccesoriosStockService $sucursales
    ): iterable {
        $mes = $this->expresionMes('r.fecha_remision');
        $ultimoEnvio = DB::connection()->getDriverName() === 'pgsql'
            ? "TO_CHAR(MAX(r.fecha_remision), 'YYYY-MM-DD')"
            : 'MAX(r.fecha_remision)';
        $filas = DB::table('ot_logistica_remisiones as r')
            ->joinSub($catalogo, 'a', DB::raw('UPPER(TRIM(r.codigo))'), '=', 'a.codigo')
            ->whereBetween('r.fecha_remision', [$desde, $hasta])
            ->where('r.cantidad', '>', 0)
            ->select(
                'a.id as articulo_id', 'r.cod_sucursal_salida', 'r.sucursal_salida',
                'r.cod_sucursal_destino', 'r.sucursal_destino'
            )
            ->selectRaw("{$mes} AS mes, {$ultimoEnvio} AS ultimo_envio,
                SUM(r.cantidad) AS enviadas, 0 AS vendidas, 0 AS venta_neta")
            ->groupBy('a.id', 'mes', 'r.cod_sucursal_salida', 'r.sucursal_salida',
                'r.cod_sucursal_destino', 'r.sucursal_destino')
            ->cursor();

        foreach ($filas as $fila) {
            $origen = $sucursales->identificar($fila->cod_sucursal_salida, $fila->sucursal_salida);
            $destino = $sucursales->identificar($fila->cod_sucursal_destino, $fila->sucursal_destino);
            $etapas = AccesoriosStockService::clasificarFlujo($origen, $destino, false, false, (int) $fila->enviadas);
            if ($etapas['distribuido_central'] > 0) {
                $fila->sucursal = $sucursales->nombre($destino, (string) $fila->sucursal_destino);
                yield $fila;
            }
        }
    }

    private function ventas(
        Builder $catalogo,
        string $desde,
        string $hasta,
        AccesoriosStockService $sucursales
    ): iterable {
        $mes = $this->expresionMes('v.fecha');
        $filas = DB::table('ventas as v')
            ->joinSub($catalogo, 'a', DB::raw('UPPER(TRIM(v.codigo))'), '=', 'a.codigo')
            ->whereBetween('v.fecha', [$desde, $hasta])
            ->select('a.id as articulo_id', 'v.local')
            ->selectRaw("{$mes} AS mes,
                0 AS enviadas, SUM(v.cantidad) AS vendidas, SUM(v.p_venta) AS venta_neta")
            ->groupBy('a.id', 'mes', 'v.local')
            ->cursor();

        foreach ($filas as $fila) {
            $clave = $sucursales->identificar(null, $fila->local);
            $fila->sucursal = $clave !== null ? $sucursales->nombre($clave, (string) $fila->local) : null;
            yield $fila;
        }
    }

    private function expresionMes(string $fecha): string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? "TO_CHAR({$fecha}, 'YYYY-MM')"
            : "SUBSTR(CAST({$fecha} AS TEXT), 1, 7)";
    }
}
