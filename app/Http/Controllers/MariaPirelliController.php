<?php

namespace App\Http\Controllers;

use App\Exports\MariaPirelliExport;
use App\Imports\MariaPirelliCatalogoImport;
use App\Services\AccesoriosStockService;
use App\Services\MariaPirelliStockService;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class MariaPirelliController extends Controller
{
    public function index(Request $request, MariaPirelliStockService $stock)
    {
        $configurado = Schema::hasTable('maria_pirelli_articulos');
        if (!$configurado) {
            return view('maria_pirelli.index', compact('configurado'));
        }

        $request->validate(['remision' => 'nullable|integer|min:1']);
        $sucursalesAccesorios = new AccesoriosStockService();
        $datos = $this->datos($request, $sucursalesAccesorios);
        // Los meses y locales con inventario también se pueden consultar aunque no tengan ventas.
        foreach ($stock->fechas($datos['catalogo'], $datos['desde'], $datos['hasta']) as $fila) {
            $clave = AccesoriosStockService::normalizar($fila->sucursal);
            $datos['sucursales'][$clave] ??= $fila->sucursal;
            if ($datos['sucursal'] !== '' && $clave !== AccesoriosStockService::normalizar($datos['sucursal'])) {
                continue;
            }
            $datos['meses'][substr($fila->fecha_corte, 0, 7)] ??= [
                'enviadas' => 0, 'vendidas' => 0, 'venta_neta' => 0.0,
            ];
        }
        krsort($datos['meses']);
        $detalleSucursales = $this->detalleSucursales($datos, $stock);
        foreach ($detalleSucursales['sucursalesDelMes'] as $local) {
            if ($local['filtrable']) {
                $datos['sucursales'][AccesoriosStockService::normalizar($local['sucursal'])] ??= $local['sucursal'];
            }
        }
        ksort($datos['sucursales'], SORT_NATURAL | SORT_FLAG_CASE);
        $articulos = (clone $datos['catalogo'])->orderBy('a.codigo')->paginate(25)->withQueryString();
        $lineas = DB::table('maria_pirelli_articulos')->whereNotNull('linea')
            ->distinct()->orderBy('linea')->pluck('linea');
        $hayCatalogo = DB::table('maria_pirelli_articulos')->exists();
        $detalleRemisiones = $this->detalleRemisiones($request, $datos, $sucursalesAccesorios);
        $stockActual = $stock->actual();
        unset($datos['catalogo']);

        return view('maria_pirelli.index', $datos + $detalleRemisiones + $detalleSucursales
            + compact('configurado', 'articulos', 'lineas', 'hayCatalogo', 'stockActual'));
    }

    public function exportar(Request $request)
    {
        if (!Schema::hasTable('maria_pirelli_articulos')) {
            return redirect()->route('maria-pirelli.index')
                ->with('error', 'Ejecutá la migración de MARIA PIRELLI antes de exportar.');
        }

        $datos = $this->datos($request);
        $articulos = $datos['catalogo']->whereIntegerInRaw('a.id', array_keys($datos['porArticulo']))
            ->orderBy('a.codigo')->get();

        return Excel::download(
            new MariaPirelliExport($articulos, $datos['porArticulo'], $datos['conRemisiones'], $datos['conVentas']),
            "maria_pirelli_{$datos['desde']}_{$datos['hasta']}.xlsx"
        );
    }

    private function datos(Request $request, ?AccesoriosStockService $sucursalesAccesorios = null): array
    {
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
        $sucursalesAccesorios ??= new AccesoriosStockService();
        if ($sucursal !== '') {
            $clave = $sucursalesAccesorios->identificar(null, $sucursal);
            $sucursal = $clave !== null ? $sucursalesAccesorios->nombre($clave, $sucursal) : $sucursal;
        }

        $catalogo = DB::table('maria_pirelli_articulos as a')
            ->when($linea !== '', fn ($q) => $q->where('a.linea', $linea))
            ->when($buscar !== '', function ($q) use ($buscar) {
                $patron = '%' . mb_strtoupper($buscar) . '%';
                $q->where(function ($q) use ($patron) {
                    $q->whereRaw('UPPER(a.codigo) LIKE ?', [$patron])
                        ->orWhereRaw('UPPER(a.descripcion) LIKE ?', [$patron]);
                });
            });
        $conRemisiones = Schema::hasTable('ot_logistica_remisiones');
        $conVentas = Schema::hasTable('ventas');
        $fuentes = [];
        if ($catalogo->exists()) {
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
        $meses = $porArticulo = $sucursales = $porMesSucursal = [];
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

                $porMesSucursal[$fila->mes][$claveLocal] ??= $ceros + [
                    'sucursal' => $fila->sucursal ?: 'Sin sucursal identificada',
                    'filtrable' => $claveLocal !== '',
                ];
                $meses[$fila->mes] ??= $ceros;
                $porArticulo[$fila->articulo_id] ??= $ceros;
                foreach ($campos as $campo) {
                    $porMesSucursal[$fila->mes][$claveLocal][$campo] += $fila->{$campo};
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

        return compact(
            'catalogo', 'conRemisiones', 'conVentas', 'desde', 'hasta',
            'linea', 'buscar', 'sucursal', 'sucursales',
            'totales', 'meses', 'porArticulo', 'porMesSucursal'
        );
    }

    private function detalleSucursales(array $datos, MariaPirelliStockService $stock): array
    {
        $mesSeleccionado = substr($datos['desde'], 0, 7) === substr($datos['hasta'], 0, 7)
            ? substr($datos['desde'], 0, 7) : null;
        $sucursalesDelMes = collect();
        $stockMes = null;
        $totalesMes = null;
        $sucursalesSinStock = 0;
        if ($mesSeleccionado !== null) {
            $stockMes = $stock->alCorte($datos['catalogo'], $datos['hasta']);
            $filas = $datos['porMesSucursal'][$mesSeleccionado] ?? [];
            $sinStock = ['unidades' => null, 'costo' => null, 'venta_potencial' => null, 'negativas' => 0];
            $filtro = AccesoriosStockService::normalizar($datos['sucursal']);
            foreach ($stockMes['sucursales'] as $local) {
                $clave = AccesoriosStockService::normalizar($local->sucursal);
                if ($filtro !== '' && $clave !== $filtro) {
                    continue;
                }
                $filas[$clave] ??= [
                    'sucursal' => $local->sucursal, 'filtrable' => true,
                    'enviadas' => 0, 'vendidas' => 0, 'venta_neta' => 0.0,
                ];
                foreach (array_keys($sinStock) as $campo) {
                    $filas[$clave][$campo] = $local->{$campo};
                }
            }

            $sucursalesDelMes = collect($filas)->map(fn ($fila) => $fila + $sinStock)
                ->sortBy('sucursal', SORT_NATURAL | SORT_FLAG_CASE)->values();
            $informadas = $sucursalesDelMes->filter(fn ($fila) => $fila['unidades'] !== null);
            $sucursalesSinStock = $sucursalesDelMes->count() - $informadas->count();
            $totalesMes = $datos['totales'];
            foreach (['unidades', 'costo', 'venta_potencial', 'negativas'] as $campo) {
                $totalesMes[$campo] = $informadas->isNotEmpty() ? $informadas->sum($campo) : null;
            }
            $mesSeleccionado = ucfirst(Carbon::parse($mesSeleccionado . '-01')->locale('es')->isoFormat('MMMM YYYY'));
        }

        return compact('mesSeleccionado', 'sucursalesDelMes', 'stockMes', 'totalesMes', 'sucursalesSinStock');
    }

    private function detalleRemisiones(Request $request, array $datos, AccesoriosStockService $sucursales): array
    {
        $documentos = collect();
        $remisionActiva = null;
        $lineasRemision = collect();
        if ($datos['totales']['enviadas'] > 0) {
            $base = $this->remisionesBase($datos['catalogo'], $datos['desde'], $datos['hasta']);
            $documentos = $this->documentos($base, $sucursales, $datos['sucursal']);
            $remisionActiva = $documentos->firstWhere('id', $request->integer('remision'));
            if ($remisionActiva !== null) {
                $lineasRemision = $this->lineasDeRemision($base, $remisionActiva, $sucursales);
            }
        }

        $confirmadas = (int) $documentos->sum('confirmadas');
        $pendientes = (int) $documentos->sum('enviadas') - $confirmadas;
        $porPagina = 15;
        $pagina = min(LengthAwarePaginator::resolveCurrentPage('pagina_remisiones'),
            max(1, (int) ceil($documentos->count() / $porPagina)));
        $remisiones = (new LengthAwarePaginator(
            $documentos->forPage($pagina, $porPagina)->values(),
            $documentos->count(),
            $porPagina,
            $pagina,
            ['path' => $request->url(), 'pageName' => 'pagina_remisiones']
        ))->appends($request->only(['desde', 'hasta', 'sucursal', 'linea', 'buscar', 'page']))
            ->fragment('remisiones');

        return compact('remisiones', 'remisionActiva', 'lineasRemision', 'confirmadas', 'pendientes');
    }

    private function documentos(Builder $base, AccesoriosStockService $sucursales, string $sucursal): Collection
    {
        $campos = [
            'r.serie', 'r.numero_remision', 'r.fecha_remision', 'r.cod_sucursal_salida',
            'r.sucursal_salida', 'r.cod_sucursal_destino', 'r.sucursal_destino',
        ];
        $filas = (clone $base)->select($campos)
            ->selectRaw($this->fechaIso('r.fecha_remision') . ' AS fecha_envio')
            ->selectRaw($this->fechaIso('MAX(r.fecha_recepcion)') . ' AS ultima_confirmacion')
            ->selectRaw('MIN(r.id) AS id, SUM(r.cantidad) AS enviadas,
                SUM(CASE WHEN r.fecha_recepcion IS NOT NULL THEN r.cantidad ELSE 0 END) AS confirmadas')
            ->groupBy($campos)->cursor();

        $filtro = AccesoriosStockService::normalizar($sucursal);
        $documentos = [];
        foreach ($filas as $fila) {
            $destino = $this->destinoDistribucion($fila, $sucursales);
            if ($destino === null) {
                continue;
            }
            $local = $sucursales->nombre($destino, (string) $fila->sucursal_destino);
            if ($filtro !== '' && AccesoriosStockService::normalizar($local) !== $filtro) {
                continue;
            }

            // Cada documento se identifica por serie, número, fecha y destino.
            // El origen de estas filas es Casa Central. Los alias del mismo local se unifican.
            $clave = serialize([$fila->serie, $fila->numero_remision, $fila->fecha_envio, $destino]);
            $documento = $documentos[$clave] ??= (object) [
                'id' => (int) $fila->id,
                'serie' => $fila->serie,
                'numero_remision' => $fila->numero_remision,
                'fecha_envio' => $fila->fecha_envio,
                'local' => $local,
                'destino' => $destino,
                'enviadas' => 0,
                'confirmadas' => 0,
                'ultima_confirmacion' => null,
            ];
            $documento->id = min($documento->id, (int) $fila->id);
            $documento->enviadas += (int) $fila->enviadas;
            $documento->confirmadas += (int) $fila->confirmadas;
            $documento->ultima_confirmacion = max($documento->ultima_confirmacion, $fila->ultima_confirmacion);
        }

        return collect($documentos)->sortBy([['fecha_envio', 'desc'], ['id', 'desc']])->values();
    }

    private function lineasDeRemision(Builder $base, object $documento, AccesoriosStockService $sucursales): Collection
    {
        return (clone $base)
            ->where('r.serie', $documento->serie)
            ->where('r.numero_remision', $documento->numero_remision)
            ->where('r.fecha_remision', $documento->fecha_envio)
            ->select('a.codigo', 'a.descripcion', 'r.cantidad', 'r.cod_sucursal_salida', 'r.sucursal_salida',
                'r.cod_sucursal_destino', 'r.sucursal_destino')
            ->selectRaw($this->fechaIso('r.fecha_recepcion') . ' AS fecha_recepcion')
            ->orderBy('a.codigo')->orderBy('r.id')->get()
            ->filter(fn ($fila) => $this->destinoDistribucion($fila, $sucursales) === $documento->destino)
            ->values();
    }

    public function importar(Request $request)
    {
        $request->validate(['archivo' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240']);
        if (!Schema::hasTable('maria_pirelli_articulos')) {
            return redirect()->route('maria-pirelli.index')
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

        return redirect()->route('maria-pirelli.index')->with(
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
        $ultimoEnvio = $this->fechaIso('MAX(r.fecha_remision)');
        $filas = $this->remisionesBase($catalogo, $desde, $hasta)
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
            $destino = $this->destinoDistribucion($fila, $sucursales);
            if ($destino !== null) {
                $fila->sucursal = $sucursales->nombre($destino, (string) $fila->sucursal_destino);
                yield $fila;
            }
        }
    }

    private function remisionesBase(Builder $catalogo, string $desde, string $hasta): Builder
    {
        return DB::table('ot_logistica_remisiones as r')
            ->joinSub($catalogo, 'a', DB::raw('UPPER(TRIM(r.codigo))'), '=', 'a.codigo')
            ->whereBetween('r.fecha_remision', [$desde, $hasta])
            ->where('r.cantidad', '>', 0);
    }

    private function destinoDistribucion(object $fila, AccesoriosStockService $sucursales): ?string
    {
        $origen = $sucursales->identificar($fila->cod_sucursal_salida, $fila->sucursal_salida);
        $destino = $sucursales->identificar($fila->cod_sucursal_destino, $fila->sucursal_destino);
        $etapas = AccesoriosStockService::clasificarFlujo($origen, $destino, false, false, 1);

        return $etapas['distribuido_central'] > 0 ? $destino : null;
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

    private function fechaIso(string $fecha): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? "TO_CHAR({$fecha}, 'YYYY-MM-DD')" : $fecha;
    }
}
