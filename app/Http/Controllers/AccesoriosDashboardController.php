<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccesoriosDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:pedido_compras index']);
    }

    public function index(Request $request)
    {
        if (!Schema::hasTable('accesorios_articulos')
            || !Schema::hasTable('accesorios_compras')
            || !Schema::hasTable('ot_logistica_remisiones')
            || !Schema::hasColumn('ot_logistica_remisiones', 'accesorio_articulo_id')) {
            return view('accesorios.configurar');
        }

        return view('accesorios.dashboard', $this->datos($request));
    }

    public function exportar(Request $request)
    {
        if (!Schema::hasTable('accesorios_articulos')
            || !Schema::hasTable('accesorios_compras')) {
            return redirect()->route('accesorios.index')
                ->with('error', 'Primero hay que ejecutar la migración de Accesorios.');
        }

        $datos = $this->datos($request);
        $filas = $datos['rankingCompleto'];
        $nombre = 'monitoreo_accesorios_' . $datos['desde'] . '_' . $datos['hasta'] . '.csv';

        return response()->streamDownload(function () use ($filas) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, [
                'CODIGO', 'DESCRIPCION', 'LINEA', 'VENTAS NETAS UN',
                'INGRESOS NETOS Gs', 'DESCUENTOS Gs', 'COSTO PROMEDIO Gs',
                'COSTO VENDIDO ESTIMADO Gs', 'MARGEN BRUTO ESTIMADO %',
                'STOCK CALCULADO', 'VENTAS ULTIMOS 30 DIAS',
                'COBERTURA ESTIMADA DIAS', 'REPOSICION SUGERIDA 30 DIAS'
            ], ';');
            foreach ($filas as $fila) {
                fputcsv($salida, [
                    $fila->codigo, $fila->descripcion, $fila->linea,
                    $fila->unidades, $fila->importe, $fila->descuentos,
                    $fila->costo_promedio, $fila->costo_estimado, $fila->margen,
                    $fila->stock, $fila->velocidad30, $fila->cobertura_dias,
                    $fila->reposicion,
                ], ';');
            }
            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportarStock(Request $request)
    {
        if (!Schema::hasTable('accesorios_articulos')
            || !Schema::hasTable('accesorios_compras')) {
            return redirect()->route('accesorios.index')->with(
                'error', 'Primero completá la migración de Accesorios.'
            );
        }

        $datos = $this->datos($request);
        $catalogo = $datos['catalogo']->keyBy('id');
        $filas = $datos['stockDatos']['detalle'];
        $corte = $datos['hasta'];
        return response()->streamDownload(function () use ($catalogo, $filas, $corte) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'CODIGO', 'DESCRIPCION', 'SUCURSAL', 'ENTRADAS RECIBIDAS',
                'SALIDAS REMITIDAS', 'VENTAS NETAS ACUMULADAS',
                'EN TRANSITO AL DESTINO', 'STOCK CALCULADO', 'CORTE'
            ], ';');
            foreach ($filas as $articuloId => $locales) {
                $articulo = $catalogo->get((int) $articuloId);
                if (!$articulo) {
                    continue;
                }
                foreach ($locales as $local) {
                    fputcsv($out, [
                        $articulo->codigo, $articulo->descripcion, $local->sucursal,
                        $local->entradas, $local->salidas, $local->ventas,
                        $local->en_transito, $local->saldo, $corte,
                    ], ';');
                }
            }
            fclose($out);
        }, 'stock_accesorios_' . $corte . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function datos(Request $request): array
    {
        $request->validate([
            'desde' => 'nullable|date_format:Y-m-d',
            'hasta' => 'nullable|date_format:Y-m-d',
            'sucursal' => 'nullable|string|max:120',
            'fco' => 'nullable|string|max:50',
            'fecha_envio' => 'nullable|date_format:Y-m-d',
            'linea' => 'nullable|string|max:100',
            'buscar' => 'nullable|string|max:100',
            'orden' => 'nullable|in:ventas,importe,stock,margen,cobertura',
        ]);

        $conVentas = Schema::hasTable('ventas');
        $conRemisiones = Schema::hasTable('ot_logistica_remisiones');
        $ultimaVenta = $conVentas ? DB::table('ventas as v')
            ->join('accesorios_articulos as a', function ($join) {
                $join->on(DB::raw('UPPER(BTRIM(v.codigo))'), '=', 'a.codigo');
            })->max('v.fecha') : null;

        // El corte del stock corresponde a "hasta"; por defecto, hoy.
        $fechaReferencia = now()->toDateString();
        $desde = $request->input('desde') ?: Carbon::parse($fechaReferencia)->startOfYear()->toDateString();
        $hasta = $request->input('hasta') ?: $fechaReferencia;
        if ($desde > $hasta) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'hasta' => 'La fecha de inicio no puede ser posterior a la fecha final.',
            ]);
        }

        $sucursal = trim((string) $request->input('sucursal', ''));
        $stockServicio = new \App\Services\AccesoriosStockService();
        $aliasesSucursal = $sucursal !== '' ? $stockServicio->aliasesPara($sucursal) : [];
        $codigoSucursalFiltro = $sucursal !== '' ? $stockServicio->codigoPara($sucursal) : null;
        $fco = trim((string) $request->input('fco', ''));
        $fechaEnvio = (string) $request->input('fecha_envio', '');
        $linea = trim((string) $request->input('linea', ''));
        $buscar = trim((string) $request->input('buscar', ''));
        $orden = (string) $request->input('orden', 'ventas');
        $diasPeriodo = max(1, Carbon::parse($desde)->diffInDays(Carbon::parse($hasta)) + 1);

        $opciones = [
            'sucursales' => $conVentas
                ? DB::table('ventas as v')->join('accesorios_articulos as a', function ($join) {
                    $join->on(DB::raw('UPPER(BTRIM(v.codigo))'), '=', 'a.codigo');
                })->whereNotNull('v.local')->where('v.local', '<>', '')
                    ->distinct()->orderBy('v.local')->pluck('v.local')->all()
                : [],
            'fcos' => DB::table('accesorios_compras')->distinct()->orderByDesc('fco_nro')->pluck('fco_nro')->all(),
            'fechasEnvio' => DB::table('accesorios_compras')->distinct()->orderByDesc('fecha_envio')->pluck('fecha_envio')->all(),
            'lineas' => DB::table('accesorios_articulos')->whereNotNull('linea')->distinct()->orderBy('linea')->pluck('linea')->all(),
        ];

        $catalogo = DB::table('accesorios_articulos as a')
            ->when($linea !== '', function ($q) use ($linea) {
                $q->where('a.linea', $linea);
            })
            ->when($buscar !== '', function ($q) use ($buscar) {
                $q->where(function ($b) use ($buscar) {
                    $b->where('a.codigo', 'ilike', '%' . $buscar . '%')
                        ->orWhere('a.descripcion', 'ilike', '%' . $buscar . '%');
                });
            })
            ->when($fco !== '' || $fechaEnvio !== '', function ($q) use ($fco, $fechaEnvio) {
                $q->whereIn('a.id', DB::table('accesorios_compras')
                    ->select('articulo_id')
                    ->when($fco !== '', function ($b) use ($fco) {
                        $b->where('fco_nro', $fco);
                    })
                    ->when($fechaEnvio !== '', function ($b) use ($fechaEnvio) {
                        $b->where('fecha_envio', $fechaEnvio);
                    }));
            })
            ->orderBy('a.codigo')
            ->get(['a.id', 'a.codigo', 'a.descripcion', 'a.linea']);

        $ids = $catalogo->pluck('id')->all();
        $codigos = $catalogo->pluck('codigo')->all();

        // Costo de referencia de TODAS las compras del artículo; el FCO no existe en ventas.
        $costos = DB::table('accesorios_compras')
            ->whereIn('articulo_id', $ids)
            ->select('articulo_id')
            ->selectRaw('SUM(cantidad_compra) AS unidades')
            ->selectRaw('SUM(cantidad_compra * costo_unitario) AS inversion')
            ->selectRaw('SUM(cantidad_compra * precio_venta) AS venta_potencial')
            ->groupBy('articulo_id')->get()->keyBy('articulo_id');

        $comprasFiltradas = DB::table('accesorios_compras')
            ->whereIn('articulo_id', $ids)
            ->when($fco !== '', function ($q) use ($fco) {
                $q->where('fco_nro', $fco);
            })
            ->when($fechaEnvio !== '', function ($q) use ($fechaEnvio) {
                $q->where('fecha_envio', $fechaEnvio);
            })
            ->selectRaw('COUNT(*) AS documentos, COALESCE(SUM(cantidad_compra),0) AS unidades,
                COALESCE(SUM(cantidad_compra*costo_unitario),0) AS inversion')->first();

        $baseVentas = null;
        $ventaPorCodigo = collect();
        $sucursales = collect();
        $meses = collect();
        $velocidad = collect();
        $kpi = (object) [
            'importe' => 0, 'lista' => 0, 'descuento' => 0,
            'unidades' => 0, 'brutas' => 0, 'devoluciones' => 0, 'tickets' => 0,
        ];

        if ($conVentas && count($ids)) {
            $baseVentas = DB::table('ventas as v')
                ->join('accesorios_articulos as a', function ($join) {
                    $join->on(DB::raw('UPPER(BTRIM(v.codigo))'), '=', 'a.codigo');
                })
                ->whereIn('a.id', $ids)
                ->when($sucursal !== '', function ($q) use ($aliasesSucursal) {
                    $q->whereIn(DB::raw('UPPER(BTRIM(v.local))'), $aliasesSucursal);
                });

            $periodoVentas = (clone $baseVentas)->whereBetween('v.fecha', [$desde, $hasta]);
            $kpi = (clone $periodoVentas)->selectRaw("
                COALESCE(SUM(v.p_venta),0) AS importe,
                COALESCE(SUM(v.p_lista),0) AS lista,
                COALESCE(SUM(v.descuento),0) AS descuento,
                COALESCE(SUM(v.cantidad),0) AS unidades,
                COALESCE(SUM(CASE WHEN v.cantidad>0 THEN v.cantidad ELSE 0 END),0) AS brutas,
                COALESCE(SUM(CASE WHEN v.cantidad<0 THEN -v.cantidad ELSE 0 END),0) AS devoluciones,
                COUNT(DISTINCT CASE WHEN v.cantidad>0
                    THEN COALESCE(v.local,'') || '|' || COALESCE(v.comprobante,'') END) AS tickets
            ")->first();

            $ventaPorCodigo = (clone $periodoVentas)
                ->select('a.id')
                ->selectRaw('COALESCE(SUM(v.cantidad),0) AS unidades')
                ->selectRaw('COALESCE(SUM(v.p_venta),0) AS importe')
                ->selectRaw('COALESCE(SUM(v.descuento),0) AS descuento')
                ->groupBy('a.id')->get()->keyBy('id');

            $sucursales = (clone $periodoVentas)
                ->select('v.local')
                ->selectRaw('SUM(v.p_venta) AS importe, SUM(v.cantidad) AS unidades')
                ->groupBy('v.local')->orderByRaw('SUM(v.p_venta) DESC')->limit(12)->get();

            $meses = (clone $periodoVentas)
                ->selectRaw("TO_CHAR(v.fecha, 'YYYY-MM') AS mes")
                ->selectRaw('SUM(v.p_venta) AS importe, SUM(v.cantidad) AS unidades')
                ->groupByRaw("TO_CHAR(v.fecha, 'YYYY-MM')")
                ->orderBy('mes')->get();

            $inicio30 = Carbon::parse($hasta)->subDays(29)->toDateString();
            $velocidad = (clone $baseVentas)->whereBetween('v.fecha', [$inicio30, $hasta])
                ->select('a.id')->selectRaw('GREATEST(0, SUM(v.cantidad)) AS unidades')
                ->groupBy('a.id')->get()->keyBy('id');
        }

        // Saldo de accesorios por local: recepciones - salidas - ventas netas.
        // Se acumula desde el inicio histórico hasta "hasta"; "desde" filtra
        // las estadísticas comerciales, no recorta el saldo del inventario.
        $stockDatos = $stockServicio->calcular($ids, $hasta, $sucursal);
        $stockPorCodigo = $stockDatos['porCodigo'];
        $saldosSucursal = $stockDatos['porSucursal'];
        $stockFuente = 'Cálculo documental: entradas recibidas - salidas - ventas netas';
        $stockCorte = $hasta;

        $opciones['sucursales'] = collect($opciones['sucursales'])
            ->merge($stockDatos['sucursalesDisponibles'])
            ->unique()->sort()->values()->all();

        $remisiones = (object) [
            'movimientos' => 0, 'recibidas' => 0, 'transito' => 0,
            'deposito_central' => 0, 'central_locales' => 0, 'documentos' => 0,
        ];
        $pendientes = collect();

        if ($conRemisiones && count($ids)) {
            $baseRemisiones = DB::table('ot_logistica_remisiones as r')
                ->whereIn('r.accesorio_articulo_id', $ids)
                ->whereBetween('r.fecha_remision', [$desde, $hasta]);

            if ($sucursal !== '') {
                $baseRemisiones->where(function ($q) use ($aliasesSucursal, $codigoSucursalFiltro) {
                    $q->whereIn(DB::raw('UPPER(BTRIM(r.sucursal_destino))'), $aliasesSucursal);
                    if ($codigoSucursalFiltro !== null) {
                        $q->orWhere('r.cod_sucursal_destino', $codigoSucursalFiltro);
                    }
                });
            }

            $remisiones = (clone $baseRemisiones)->selectRaw("
                COALESCE(SUM(r.cantidad),0) AS movimientos,
                COALESCE(SUM(CASE WHEN r.fecha_recepcion <= ? THEN r.cantidad ELSE 0 END),0) AS recibidas,
                COALESCE(SUM(CASE WHEN r.fecha_recepcion IS NULL OR r.fecha_recepcion > ? THEN r.cantidad ELSE 0 END),0) AS transito,
                COALESCE(SUM(CASE WHEN (
                    UPPER(COALESCE(r.sucursal_salida,'')) LIKE '%DEPOSITO%'
                    OR UPPER(COALESCE(r.sucursal_salida,'')) LIKE '%DEPÓSITO%'
                    OR UPPER(COALESCE(r.sucursal_salida,'')) LIKE '%INSUMOS%'
                ) AND UPPER(COALESCE(r.sucursal_destino,'')) LIKE '%CASA CENTRAL%'
                    THEN r.cantidad ELSE 0 END),0) AS deposito_central,
                COALESCE(SUM(CASE WHEN UPPER(COALESCE(r.sucursal_salida,'')) LIKE '%CASA CENTRAL%'
                    THEN r.cantidad ELSE 0 END),0) AS central_locales,
                COUNT(DISTINCT COALESCE(r.serie,'') || '|' || COALESCE(r.numero_remision,'')
                    || '|' || COALESCE(CAST(r.cod_sucursal_destino AS TEXT),'')) AS documentos
            ", [$hasta, $hasta])->first();

            $pendientes = (clone $baseRemisiones)->where(function ($q) use ($hasta) {
                $q->whereNull('r.fecha_recepcion')->orWhere('r.fecha_recepcion', '>', $hasta);
            })
                ->orderBy('r.fecha_remision')->orderBy('r.id')
                ->limit(8)
                ->get(['r.serie', 'r.numero_remision', 'r.codigo', 'r.cantidad',
                    'r.fecha_remision', 'r.sucursal_destino']);
        }

        $ranking = collect();
        $conStock = $stockPorCodigo->isNotEmpty();
        $productosConStock = 0;
        $productosCriticos = 0;
        $productosReponer = 0;
        $gananciaEstimacion = 0;
        $costoEstimacion = 0;
        $valorCatalogo = 0;

        foreach ($catalogo as $articulo) {
            $c = $costos->get($articulo->id);
            $v = $ventaPorCodigo->get($articulo->id);
            $st = $stockPorCodigo->get($articulo->id);
            $p30 = $velocidad->get($articulo->id);
            $compradas = (int) ($c->unidades ?? 0);
            $unidades = (int) ($v->unidades ?? 0);
            $importe = (float) ($v->importe ?? 0);
            $costoPromedio = $compradas > 0 ? (float) $c->inversion / $compradas : null;
            $estimacion = $costoPromedio !== null ? $unidades * $costoPromedio : null;
            $margen = $estimacion !== null && $importe > 0
                ? 100 * ($importe - $estimacion) / $importe : null;
            $saldo = $st !== null ? (int) round((float) $st->saldo) : null;
            $unidades30 = max(0, (int) ($p30->unidades ?? 0));
            $diario = $unidades30 / 30;
            $cobertura = ($saldo !== null && $diario > 0)
                ? max(0, $saldo / $diario) : null;
            $reposicion = $saldo !== null && $diario > 0
                ? max(0, (int) ceil(30 * $diario - $saldo)) : null;
            if ($saldo !== null) {
                $productosConStock++;
                if ($cobertura !== null && $cobertura < 7) {
                    $productosCriticos++;
                }
            }
            if ($reposicion !== null && $reposicion > 0) {
                $productosReponer++;
            }
            if ($estimacion !== null) {
                $costoEstimacion += $estimacion;
                $gananciaEstimacion += $importe - $estimacion;
            }
            $valorCatalogo += (float) ($c->inversion ?? 0);

            $fila = (object) [
                'id' => (int) $articulo->id,
                'codigo' => $articulo->codigo,
                'descripcion' => $articulo->descripcion,
                'linea' => $articulo->linea,
                'unidades' => $unidades,
                'importe' => round($importe, 2),
                'descuentos' => round((float) ($v->descuento ?? 0), 2),
                'compradas' => $compradas,
                'costo_promedio' => $costoPromedio !== null ? round($costoPromedio, 2) : null,
                'costo_estimado' => $estimacion !== null ? round($estimacion, 2) : null,
                'margen' => $margen !== null ? round($margen, 2) : null,
                'stock' => $saldo,
                'velocidad30' => $unidades30,
                'cobertura_dias' => $cobertura !== null ? round($cobertura, 1) : null,
                'reposicion' => $reposicion,
            ];
            $ranking->push($fila);
        }

        $ranking = $ranking->sort(function ($a, $b) use ($orden) {
            if ($orden === 'stock') {
                return ($a->stock === null ? 1 : ($b->stock === null ? -1 : $a->stock <=> $b->stock))
                    ?: strcmp($a->codigo, $b->codigo);
            }
            if ($orden === 'margen') {
                return ($a->margen === null ? 1 : ($b->margen === null ? -1 : $a->margen <=> $b->margen))
                    ?: strcmp($a->codigo, $b->codigo);
            }
            if ($orden === 'cobertura') {
                return ($a->cobertura_dias === null ? 1 : ($b->cobertura_dias === null ? -1 : $a->cobertura_dias <=> $b->cobertura_dias))
                    ?: strcmp($a->codigo, $b->codigo);
            }
            $campo = $orden === 'importe' ? 'importe' : 'unidades';
            return ($b->$campo <=> $a->$campo) ?: strcmp($a->codigo, $b->codigo);
        })->values();

        $rankingCompleto = $ranking;
        $pagina = max(1, (int) $request->query('page', 1));
        $rankingPagina = new LengthAwarePaginator(
            $ranking->forPage($pagina, 20)->values(),
            $ranking->count(), 20, $pagina, [
                'path' => $request->url(),
                'query' => $request->except('page'),
            ]
        );

        $alertas = [];
        if (!$conStock) {
            $alertas[] = ['tipo' => 'warning', 'titulo' => 'Sin movimientos para calcular stock',
                'texto' => 'No hay remisiones ni ventas enlazadas a los códigos seleccionados hasta el corte indicado.'];
        } else {
            // El stock documental se recalcula al abrir, sin depender del último Excel de stock.
            if ($productosCriticos) {
                $alertas[] = ['tipo' => 'danger', 'titulo' => $productosCriticos . ' códigos con cobertura crítica',
                    'texto' => 'Menos de 7 días estimados, usando ventas netas de los últimos 30 días.'];
            }
            if ($productosReponer) {
                $alertas[] = ['tipo' => 'warning', 'titulo' => $productosReponer . ' códigos con reposición sugerida',
                    'texto' => 'Cantidad orientativa para alcanzar 30 días de cobertura; verificá stock y pedidos antes de comprar.'];
            }
            if ($productosConStock < $catalogo->count()) {
                $alertas[] = ['tipo' => 'info', 'titulo' => 'Stock parcialmente informado',
                    'texto' => ($catalogo->count() - $productosConStock) . ' códigos no tienen remisiones ni ventas históricas registradas hasta el corte; no se asumen como stock cero.'];
            }
        }
        if ($stockDatos['sinOrigen'] > 0 || $stockDatos['sinDestino'] > 0 || $stockDatos['ventasSinLocal'] > 0) {
            $alertas[] = ['tipo' => 'warning', 'titulo' => 'Movimientos pendientes de identificar',
                'texto' => 'Remisiones sin origen: ' . $stockDatos['sinOrigen']
                    . ' · Sin destino: ' . $stockDatos['sinDestino']
                    . ' · Unidades vendidas sin sucursal: ' . $stockDatos['ventasSinLocal']
                    . '. Revisar antes de cerrar saldos.'];
        }
        $negativos = $saldosSucursal->filter(function ($s) { return $s->saldo < 0; });
        if ($negativos->isNotEmpty()) {
            $alertas[] = ['tipo' => 'danger', 'titulo' => $negativos->count() . ' sucursales con saldo negativo',
                'texto' => 'Hay más salidas y ventas registradas que entradas recibidas. No se ocultan ni corrigen automáticamente.'];
        }
        if ((int) $remisiones->transito > 0) {
            $alertas[] = ['tipo' => 'info', 'titulo' => number_format((int) $remisiones->transito, 0, ',', '.') . ' unidades en tránsito documental',
                'texto' => 'Remisiones sin fecha de recepción dentro del período seleccionado.'];
        }
        if (!$ultimaVenta) {
            $alertas[] = ['tipo' => 'info', 'titulo' => 'Sin ventas de accesorios importadas',
                'texto' => 'Importá ventas y comprobá que el código coincida con el catálogo de accesorios.'];
        }

        $stockTotal = $conStock ? (int) $stockPorCodigo->sum('saldo') : null;
        $demanda30 = (int) $velocidad->sum('unidades');
        // Un stock parcial no puede convertirse en una cobertura global creíble.
        $coberturaGlobal = $conStock && $productosConStock === $catalogo->count()
            && $demanda30 > 0
            ? round(max(0, $stockTotal / ($demanda30 / 30)), 1)
            : null;
        $facturacion = (float) ($kpi->importe ?? 0);
        $margenEstimado = $facturacion > 0 && $ranking->every(function ($f) {
            return $f->unidades === 0 || $f->costo_promedio !== null;
        }) ? 100 * $gananciaEstimacion / $facturacion : null;
        $costoEstimado = $margenEstimado !== null ? $costoEstimacion : null;
        $gananciaEstimada = $margenEstimado !== null ? $gananciaEstimacion : null;
        $ticketPromedio = (int) ($kpi->tickets ?? 0) > 0
            ? $facturacion / (int) $kpi->tickets : null;

        return compact(
            'desde', 'hasta', 'sucursal', 'fco', 'fechaEnvio', 'linea', 'buscar',
            'orden', 'diasPeriodo', 'opciones', 'catalogo', 'kpi', 'comprasFiltradas',
            'remisiones', 'pendientes', 'sucursales', 'meses', 'rankingCompleto',
            'rankingPagina', 'alertas', 'stockFuente', 'stockCorte', 'stockTotal',
            'saldosSucursal', 'stockDatos',
            'productosCriticos', 'productosReponer', 'productosConStock',
            'gananciaEstimada', 'costoEstimado', 'margenEstimado', 'ticketPromedio',
            'coberturaGlobal', 'ultimaVenta', 'conVentas', 'conStock', 'conRemisiones'
        );
    }
}
