<?php

namespace App\Http\Controllers;

use App\Imports\SeguimientoPedidoImport;
use App\Exports\InformeGerencialTerminacionExport;
use App\Models\SeguimientoPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Services\LogisticaConciliacionService;

class SeguimientoPedidoController extends Controller
{
    private LogisticaConciliacionService $conciliacionService;

    public function __construct(
        LogisticaConciliacionService $conciliacionService
    ) {
        $this->conciliacionService = $conciliacionService;
        $this->middleware('auth');
        $this->middleware('permission:ot dashboard');
    }

    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $query = SeguimientoPedido::query()
            ->where('seguimiento_pedido.nro_pedido', 'ILIKE', 'T%')
            ->select('seguimiento_pedido.*')
            ->selectSub(function ($q) {
                $q->from('seguimiento_pedido_detalle as spd_total')
                    ->whereColumn('spd_total.seguimiento_pedido_id', 'seguimiento_pedido.id')
                    ->selectRaw('COUNT(DISTINCT spd_total.id_ot)');
            }, 'ots_total')
            ->selectSub(function ($q) {
                $q->from('seguimiento_pedido_detalle as spd')
                    ->join('ot as o', 'o.id_ot', '=', 'spd.id_ot')
                    ->whereColumn('spd.seguimiento_pedido_id', 'seguimiento_pedido.id')
                    ->selectRaw('COALESCE(SUM(o.cantidad_orden), 0)');
            }, 'cantidad_total')
            ->selectSub(function ($q) {
                $q->from('seguimiento_pedido_detalle as spd')
                    ->join('ot_trazabilidad as t', 't.id_ot', '=', 'spd.id_ot')
                    ->whereColumn('spd.seguimiento_pedido_id', 'seguimiento_pedido.id')
                    ->where('t.proceso', 'TERMINACION - PRODUCTO TERMINADO')
                    ->selectRaw('COALESCE(SUM(t.resultado), 0)');
            }, 'producto_terminado')
            ->selectSub(function ($q) {
                $q->from('seguimiento_pedido_detalle as spd')
                    ->join('ot_logistica_remisiones as r', 'r.id_ot', '=', 'spd.id_ot')
                    ->whereColumn('spd.seguimiento_pedido_id', 'seguimiento_pedido.id')
                    ->selectRaw('COALESCE(SUM(r.cantidad), 0)');
            }, 'movimientos')
            ->selectSub(function ($q) {
                $q->from('seguimiento_pedido_detalle as spd')
                    ->join('ot_logistica_remisiones as r', 'r.id_ot', '=', 'spd.id_ot')
                    ->whereColumn('spd.seguimiento_pedido_id', 'seguimiento_pedido.id')
                    ->whereNotNull('r.fecha_recepcion')
                    ->selectRaw('COALESCE(SUM(r.cantidad), 0)');
            }, 'confirmado')
            ->selectSub(function ($q) {
                $q->from('seguimiento_pedido_detalle as spd')
                    ->join('ot_logistica_remisiones as r', 'r.id_ot', '=', 'spd.id_ot')
                    ->whereColumn('spd.seguimiento_pedido_id', 'seguimiento_pedido.id')
                    ->whereRaw("UPPER(TRIM(COALESCE(r.sucursal_logistica, r.sucursal_destino, ''))) NOT IN ('CASA CENTRAL', 'MATRIZ', 'COMERCIAL MATRIZ')")
                    ->selectRaw('COALESCE(SUM(r.cantidad), 0)');
            }, 'movimientos_locales')
            ->selectSub(function ($q) {
                $q->from('seguimiento_pedido_detalle as spd')
                    ->join('ot_logistica_remisiones as r', 'r.id_ot', '=', 'spd.id_ot')
                    ->whereColumn('spd.seguimiento_pedido_id', 'seguimiento_pedido.id')
                    ->whereNotNull('r.fecha_recepcion')
                    ->whereRaw("UPPER(TRIM(COALESCE(r.sucursal_logistica, r.sucursal_destino, ''))) NOT IN ('CASA CENTRAL', 'MATRIZ', 'COMERCIAL MATRIZ')")
                    ->selectRaw('COALESCE(SUM(r.cantidad), 0)');
            }, 'confirmado_locales')
            ->selectSub(function ($q) {
                $q->from('seguimiento_pedido_detalle as spd_conf')
                    ->whereColumn('spd_conf.seguimiento_pedido_id', 'seguimiento_pedido.id')
                    ->where(function ($estadoOt) {
                        // Caso normal: despacho original posterior al pedido con recepción local.
                        $estadoOt->whereExists(function ($r) {
                            $r->select(DB::raw(1))
                                ->from('ot_logistica_remisiones as rc')
                                ->whereColumn('rc.id_ot', 'spd_conf.id_ot')
                                ->whereNotNull('rc.fecha_recepcion')
                                ->where(function ($origen) {
                                    $origen->whereRaw("UPPER(TRIM(COALESCE(rc.sucursal_salida, ''))) IN ('CASA CENTRAL', 'MATRIZ')")
                                        ->orWhere('rc.cod_sucursal_salida', 1);
                                })
                                ->whereRaw("UPPER(TRIM(COALESCE(rc.sucursal_logistica, rc.sucursal_destino, ''))) NOT IN ('CASA CENTRAL', 'MATRIZ', 'COMERCIAL MATRIZ', '')")
                                ->where(function ($fecha) {
                                    $fecha->whereNull('seguimiento_pedido.fecha_pedido')
                                        ->orWhereColumn('rc.fecha_remision', '>=', 'seguimiento_pedido.fecha_pedido');
                                })
                                ->where(function ($fecha) {
                                    $fecha->whereNull('seguimiento_pedido.fecha_pedido')
                                        ->orWhereColumn('rc.fecha_recepcion', '>=', 'seguimiento_pedido.fecha_pedido');
                                });
                        })
                        // Caso histórico: la OT ya había sido despachada antes del pedido.
                        // Se considera atendida, pero no participa del cálculo de días.
                        ->orWhereExists(function ($r) {
                            $r->select(DB::raw(1))
                                ->from('ot_logistica_remisiones as rh')
                                ->whereColumn('rh.id_ot', 'spd_conf.id_ot')
                                ->whereNotNull('seguimiento_pedido.fecha_pedido')
                                ->whereNotNull('rh.fecha_remision')
                                ->where(function ($origen) {
                                    $origen->whereRaw("UPPER(TRIM(COALESCE(rh.sucursal_salida, ''))) IN ('CASA CENTRAL', 'MATRIZ')")
                                        ->orWhere('rh.cod_sucursal_salida', 1);
                                })
                                ->whereRaw("UPPER(TRIM(COALESCE(rh.sucursal_logistica, rh.sucursal_destino, ''))) NOT IN ('CASA CENTRAL', 'MATRIZ', 'COMERCIAL MATRIZ', '')")
                                ->whereColumn('rh.fecha_remision', '<', 'seguimiento_pedido.fecha_pedido');
                        });
                    })
                    ->selectRaw('COUNT(DISTINCT spd_conf.id_ot)');
            }, 'ots_confirmadas')
            ->selectSub(function ($q) {
                $q->from('seguimiento_pedido_detalle as spd')
                    ->join('ot_logistica_remisiones as r', 'r.id_ot', '=', 'spd.id_ot')
                    ->whereColumn('spd.seguimiento_pedido_id', 'seguimiento_pedido.id')
                    ->whereNotNull('r.fecha_recepcion')
                    ->where(function ($origen) {
                        $origen->whereRaw("UPPER(TRIM(COALESCE(r.sucursal_salida, ''))) IN ('CASA CENTRAL', 'MATRIZ')")
                            ->orWhere('r.cod_sucursal_salida', 1);
                    })
                    ->whereRaw("UPPER(TRIM(COALESCE(r.sucursal_logistica, r.sucursal_destino, ''))) NOT IN ('CASA CENTRAL', 'MATRIZ', 'COMERCIAL MATRIZ', '')")
                    ->where(function ($fecha) {
                        $fecha->whereNull('seguimiento_pedido.fecha_pedido')
                            ->orWhereColumn('r.fecha_remision', '>=', 'seguimiento_pedido.fecha_pedido');
                    })
                    ->where(function ($fecha) {
                        $fecha->whereNull('seguimiento_pedido.fecha_pedido')
                            ->orWhereColumn('r.fecha_recepcion', '>=', 'seguimiento_pedido.fecha_pedido');
                    })
                    ->selectRaw('MIN(r.fecha_recepcion)');
            }, 'primera_confirmacion')
            ->selectSub(function ($q) {
                $q->from('seguimiento_pedido_detalle as spd')
                    ->join('ot_logistica_remisiones as r', 'r.id_ot', '=', 'spd.id_ot')
                    ->whereColumn('spd.seguimiento_pedido_id', 'seguimiento_pedido.id')
                    ->whereNotNull('r.fecha_recepcion')
                    ->where(function ($origen) {
                        $origen->whereRaw("UPPER(TRIM(COALESCE(r.sucursal_salida, ''))) IN ('CASA CENTRAL', 'MATRIZ')")
                            ->orWhere('r.cod_sucursal_salida', 1);
                    })
                    ->whereRaw("UPPER(TRIM(COALESCE(r.sucursal_logistica, r.sucursal_destino, ''))) NOT IN ('CASA CENTRAL', 'MATRIZ', 'COMERCIAL MATRIZ', '')")
                    ->where(function ($fecha) {
                        $fecha->whereNull('seguimiento_pedido.fecha_pedido')
                            ->orWhereColumn('r.fecha_remision', '>=', 'seguimiento_pedido.fecha_pedido');
                    })
                    ->where(function ($fecha) {
                        $fecha->whereNull('seguimiento_pedido.fecha_pedido')
                            ->orWhereColumn('r.fecha_recepcion', '>=', 'seguimiento_pedido.fecha_pedido');
                    })
                    ->selectRaw('MAX(r.fecha_recepcion)');
            }, 'ultima_confirmacion')
            ->orderByDesc('id');

        if ($buscar !== '') {
            $query->where('nro_pedido', 'ILIKE', '%' . $buscar . '%');
        }

        $pedidos = $query->paginate(30)->appends($request->query());

        $conciliadoPagina = $this->resumenConciliadoPedidos(
            $pedidos->getCollection()
        );

        $pedidos->getCollection()->transform(function ($pedido) use (
            $conciliadoPagina
        ) {
            $efectivo = $conciliadoPagina->get((int) $pedido->id);

            $inicio = $pedido->fecha_pedido
                ? Carbon::parse($pedido->fecha_pedido)->startOfDay()
                : null;

            $finEfectivo = $efectivo
                && $efectivo->ultima_primera_confirmacion
                ? Carbon::parse(
                    $efectivo->ultima_primera_confirmacion
                )->startOfDay()
                : null;

            $cantidad = $efectivo
                ? (int) $efectivo->cantidad
                : (int) $pedido->cantidad_total;

            $movLocales = (int) $pedido->movimientos_locales;

            $otsTotal = $efectivo
                ? (int) $efectivo->ots
                : (int) $pedido->ots_total;

            $otsConfirmadas = $efectivo
                ? (int) $efectivo->completas
                : 0;

            $completo =
                $otsTotal > 0
                && $otsConfirmadas >= $otsTotal;

            $pedido->detalles_count = $otsTotal;
            $pedido->movimientos_locales = $movLocales;

            $pedido->producto_terminado_efectivo = $efectivo
                ? (int) $efectivo->producto_terminado
                : (int) $pedido->producto_terminado;

            $pedido->remitido_efectivo = $efectivo
                ? (int) $efectivo->remitido
                : 0;

            $pedido->confirmado_efectivo = $efectivo
                ? (int) $efectivo->recibido
                : 0;

            $pedido->ots_confirmadas = $otsConfirmadas;
            $pedido->completo_locales = $completo;
            $pedido->ultima_confirmacion_efectiva =
                $efectivo->ultima_primera_confirmacion ?? null;

            /*
             * El tiempo del pedido termina en la PRIMERA recepción de la
             * última OT que logró recepción, no en una redistribución tardía.
             */
            $pedido->dias_confirmacion =
                ($inicio && $finEfectivo && $completo)
                    ? $inicio->diffInDays($finEfectivo, false)
                    : null;

            $pedido->dias_transcurridos =
                ($inicio && !$completo)
                    ? $inicio->diffInDays(Carbon::today(), false)
                    : null;

            return $pedido;
        });

        $resumenBase = (clone $query)->get();

        $conciliadoGeneral = $this->resumenConciliadoPedidos(
            $resumenBase
        );

        $resumenBase->transform(function ($pedido) use (
            $conciliadoGeneral
        ) {
            $efectivo = $conciliadoGeneral->get((int) $pedido->id);

            if (!$efectivo) {
                return $pedido;
            }

            $pedido->cantidad_total = (int) $efectivo->cantidad;
            $pedido->producto_terminado =
                (int) $efectivo->producto_terminado;
            $pedido->confirmado =
                (int) $efectivo->recibido;
            $pedido->ots_total =
                (int) $efectivo->ots;
            $pedido->ots_confirmadas =
                (int) $efectivo->completas;

            return $pedido;
        });

        $totalPedidos = $resumenBase->count();
        $totalPrendas = (int) $resumenBase->sum('cantidad_total');
        $totalPt = (int) $resumenBase->sum(function ($p) {
            return min((int) $p->cantidad_total, (int) $p->producto_terminado);
        });
        $totalConfirmado = (int) $resumenBase->sum(function ($p) {
            return min((int) $p->cantidad_total, (int) $p->confirmado);
        });
        $completos = $resumenBase->filter(function ($p) {
            return (int) $p->ots_total > 0
                && (int) $p->ots_confirmadas >= (int) $p->ots_total;
        });
        $enCurso = $resumenBase->reject(function ($p) {
            return (int) $p->ots_total > 0
                && (int) $p->ots_confirmadas >= (int) $p->ots_total;
        });
        $dias = $completos->map(function ($p) {
            if (!$p->fecha_pedido || !$p->primera_confirmacion) return null;
            return Carbon::parse($p->fecha_pedido)->startOfDay()
                ->diffInDays(Carbon::parse($p->primera_confirmacion)->startOfDay(), false);
        })->filter(function ($d) {
            return $d !== null && $d >= 0;
        });

        $resumenGerencial = (object) [
            'pedidos' => $totalPedidos,
            'ots' => (int) $resumenBase->sum('ots_total'),
            'prendas' => $totalPrendas,
            'pt' => $totalPt,
            'confirmado' => $totalConfirmado,
            'pendiente_pt' => max(0, $totalPrendas - $totalPt),
            'pendiente_confirmar' => max(0, $totalPrendas - $totalConfirmado),
            'cobertura_pt' => $totalPrendas > 0 ? round(($totalPt / $totalPrendas) * 100, 1) : 0,
            'cobertura_confirmada' => $totalPrendas > 0 ? round(($totalConfirmado / $totalPrendas) * 100, 1) : 0,
            'completos' => $completos->count(),
            'en_curso' => $enCurso->count(),
            'sin_movimiento' => $enCurso->filter(function ($p) { return (int) $p->movimientos <= 0; })->count(),
            'sin_confirmar' => $enCurso->filter(function ($p) { return (int) $p->movimientos > 0 && (int) $p->confirmado <= 0; })->count(),
            'recepcion_parcial' => $enCurso->filter(function ($p) { return (int) $p->confirmado > 0; })->count(),
            'promedio_dias' => $dias->count() ? round($dias->avg(), 1) : null,
        ];

        return view('seguimiento_pedidos.index', compact('pedidos', 'buscar', 'resumenGerencial'));
    }


    public function informeGerencial(Request $request)
    {
        $hoy = Carbon::today();

        $pedidos = SeguimientoPedido::query()
            ->where('nro_pedido', 'ILIKE', 'T%')
            ->select('seguimiento_pedido.*')
            ->orderBy('fecha_pedido')
            ->get();

        $idsPedido = $pedidos->pluck('id')->all();

        $ots = collect();
        if (!empty($idsPedido)) {
            $ots = DB::table('seguimiento_pedido_detalle as spd')
                ->join('seguimiento_pedido as sp', 'sp.id', '=', 'spd.seguimiento_pedido_id')
                ->join('ot as o', 'o.id_ot', '=', 'spd.id_ot')
                ->whereIn('spd.seguimiento_pedido_id', $idsPedido)
                ->select(
                    'spd.seguimiento_pedido_id',
                    'sp.nro_pedido',
                    'sp.fecha_pedido',
                    'o.id_ot',
                    'o.nro_ot',
                    'o.codigo',
                    'o.descripcion',
                    'o.cantidad_orden'
                )
                ->get();
        }

        $idsOt = $ots->pluck('id_ot')->unique()->values()->all();

        $conciliacionPorOt = $this->conciliacionService
            ->conciliarPorIds($idsOt);

        $trazas = collect();
        $remisiones = collect();

        if (!empty($idsOt)) {
            $trazas = DB::table('ot_trazabilidad')
                ->whereIn('id_ot', $idsOt)
                ->whereIn('proceso', [
                    'TERMINACION - TERMINACION',
                    'TERMINACION - PRODUCTO TERMINADO',
                ])
                ->select(
                    'id_ot',
                    'proceso',
                    DB::raw('MIN(fecha_proceso) as primera_fecha'),
                    DB::raw('MAX(fecha_proceso) as ultima_fecha')
                )
                ->groupBy('id_ot', 'proceso')
                ->get()
                ->groupBy('id_ot');

            $remisiones = DB::table('ot_logistica_remisiones')
                ->whereIn('id_ot', $idsOt)
                ->whereNotNull('fecha_recepcion')
                ->select('id_ot', DB::raw('MIN(fecha_recepcion) as primera_recepcion'))
                ->groupBy('id_ot')
                ->get()
                ->keyBy('id_ot');
        }

        $filas = $ots->map(function ($ot) use (
            $trazas,
            $remisiones,
            $conciliacionPorOt,
            $hoy
        ) {
            $porProceso = collect(
                $trazas->get($ot->id_ot, collect())
            )->keyBy('proceso');

            $terminacion = $porProceso->get(
                'TERMINACION - TERMINACION'
            );

            $pt = $porProceso->get(
                'TERMINACION - PRODUCTO TERMINADO'
            );

            $recepcion = $remisiones->get($ot->id_ot);
            $conciliacion = $conciliacionPorOt->get($ot->id_ot);

            $fechaTerminacion = $terminacion->primera_fecha ?? null;
            $fechaLogistica = $pt->ultima_fecha ?? null;
            $fechaRecepcion =
                $conciliacion->ultima_recepcion
                ?? $recepcion->primera_recepcion
                ?? null;

            $estadoConciliacion =
                $conciliacion->estado_conciliacion ?? null;

            if ($estadoConciliacion === 'CONFIRMADO') {
                $etapa = 'CONFIRMADO';
            } elseif ($estadoConciliacion === 'EN TRANSITO') {
                $etapa = 'RECEPCION PARCIAL';
            } elseif (in_array(
                $estadoConciliacion,
                [
                    'SIN DESTINO',
                    'PENDIENTE REMITIR',
                    'PENDIENTE SALIDA',
                ],
                true
            )) {
                $etapa = 'LOGISTICA';
            } elseif ($estadoConciliacion === 'FALTA TERMINACION') {
                $etapa = 'TERMINACION';
            } elseif ($fechaLogistica) {
                $etapa = 'LOGISTICA';
            } elseif ($fechaTerminacion) {
                $etapa = 'TERMINACION';
            } else {
                $etapa = 'SIN INICIAR';
            }

            // En el informe gerencial la antigüedad siempre se mide desde la FECHA DEL PEDIDO.
            // Las fechas de proceso sirven solamente para identificar la etapa actual.
            $desde = $ot->fecha_pedido ? Carbon::parse($ot->fecha_pedido)->startOfDay() : null;
            $dias = ($etapa !== 'CONFIRMADO' && $desde)
                ? $desde->diffInDays($hoy, false)
                : 0;

            $urgente = $etapa !== 'CONFIRMADO' && $dias !== null && $dias >= 2;

            $ot->fecha_terminacion = $fechaTerminacion;
            $ot->fecha_logistica = $fechaLogistica;
            $ot->fecha_recepcion = $fechaRecepcion;
            $ot->etapa_gerencial = $etapa;
            $ot->dias_etapa = $dias;
            $ot->urgente = $urgente;

            return $ot;
        });

        $pendientes = $filas->where('etapa_gerencial', '!=', 'CONFIRMADO')
            ->sortByDesc(function ($fila) {
                return ($fila->urgente ? 100000 : 0) + (int) ($fila->dias_etapa ?? 0);
            })
            ->values();

        $resumen = (object) [
            'pedidos' => $pendientes->pluck('seguimiento_pedido_id')->unique()->count(),
            'ots' => $pendientes->count(),
            'prendas' => (int) $pendientes->sum('cantidad_orden'),
            'urgentes' => $pendientes->where('urgente', true)->count(),
            'en_terminacion' => $pendientes->where('etapa_gerencial', 'TERMINACION')->count(),
            'en_logistica' => $pendientes->where('etapa_gerencial', 'LOGISTICA')->count(),
        ];

        return view('seguimiento_pedidos.informe_gerencial', compact('pendientes', 'resumen'));
    }


    public function exportarInformeGerencialExcel(Request $request)
    {
        $vista = $this->informeGerencial($request);
        $datos = method_exists($vista, 'getData') ? $vista->getData() : [];
        $pendientes = collect($datos['pendientes'] ?? []);

        $nombre = 'informe_gerencial_terminacion_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new InformeGerencialTerminacionExport($pendientes),
            $nombre
        );
    }


    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        try {
            $import = new SeguimientoPedidoImport();
            Excel::import($import, $request->file('archivo'));

            if ($import->procesadas <= 0) {
                return back()->with(
                    'error',
                    'El archivo se abrió, pero no se procesó ninguna fila. Verificá que los encabezados sean NRO OT, PEDIDO y FECHA PEDIDO.'
                );
            }

            $mensaje = 'Importación finalizada. Filas: ' . $import->procesadas
                . ' | OT vinculadas: ' . $import->vinculadas
                . ' | OT no encontradas: ' . count($import->noEncontradas) . '.';

            if (!empty($import->noEncontradas)) {
                $mensaje .= ' No encontradas: '
                    . implode(', ', array_slice(array_unique($import->noEncontradas), 0, 20));
            }

            return back()->with('success', $mensaje);
        } catch (\Throwable $e) {
            \Log::error('ERROR IMPORT SEGUIMIENTO PEDIDOS T/P', [
                'archivo' => $request->file('archivo')
                    ? $request->file('archivo')->getClientOriginalName()
                    : null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with(
                'error',
                'No se pudo importar el archivo: ' . $e->getMessage()
            );
        }
    }

    public function show($id)
    {
        $pedido = SeguimientoPedido::findOrFail($id);

        $ots = DB::table('seguimiento_pedido_detalle as spd')
            ->join('ot as o', 'o.id_ot', '=', 'spd.id_ot')
            ->where('spd.seguimiento_pedido_id', $pedido->id)
            ->select('o.id_ot', 'o.nro_ot', 'o.codigo', 'o.descripcion', 'o.cantidad_orden')
            ->orderBy('o.nro_ot')
            ->get();

        $idsOt = $ots->pluck('id_ot')->all();

        $conciliacionPorOt = $this->conciliacionService
            ->conciliarPorIds($idsOt);

        $trazas = collect();
        $logistica = collect();
        $remisiones = collect();
        $movimientosRemision = collect();

        if (!empty($idsOt)) {
            $trazas = DB::table('ot_trazabilidad')
                ->whereIn('id_ot', $idsOt)
                ->whereIn('proceso', [
                    'TERMINACION - TERMINACION',
                    'TERMINACION - PRODUCTO TERMINADO',
                    'LOGISTICA - LOGISTICA Y DISTRIBUCION',
                ])
                ->select(
                    'id_ot',
                    'proceso',
                    DB::raw('SUM(resultado) as cantidad'),
                    DB::raw('MIN(fecha_proceso) as primera_fecha'),
                    DB::raw('MAX(fecha_proceso) as ultima_fecha')
                )
                ->groupBy('id_ot', 'proceso')
                ->get()
                ->groupBy('id_ot');

            $logistica = DB::table('ot_logistica_detalle')
                ->whereIn('id_ot', $idsOt)
                ->select('id_ot', 'sucursal', DB::raw('SUM(cantidad) as cantidad'))
                ->groupBy('id_ot', 'sucursal')
                ->get()
                ->groupBy('id_ot');

            if (Schema::hasTable('ot_logistica_remisiones')) {
                /*
                 * La REMISIÓN es evidencia suficiente de despacho aunque todavía
                 * no exista id_logistica_detalle. El seguimiento T no debe quedar
                 * bloqueado esperando la asignación del plan logístico.
                 */
                $movimientos = DB::table('ot_logistica_remisiones')
                    ->whereIn('id_ot', $idsOt)
                    ->select(
                        'id',
                        'id_ot',
                        'codigo',
                        'fecha_remision',
                        'fecha_recepcion',
                        'sucursal_salida',
                        'sucursal_destino',
                        'sucursal_logistica',
                        'cod_sucursal_salida',
                        'cod_sucursal_destino',
                        'serie',
                        'numero_remision',
                        'cantidad'
                    )
                    ->get();

                /*
                 * Compatibilidad con remisiones históricas importadas antes de
                 * que existiera la Logística: si quedaron con id_ot = NULL,
                 * se usan en memoria cuando el código base identifica de forma
                 * inequívoca una OT del pedido actual.
                 *
                 * No se actualiza la BD desde un GET; el próximo reimport reparará
                 * persistentemente el id_ot con la misma lógica del importador.
                 */
                $otsPorCodigoPedido = $ots
                    ->groupBy(function ($ot) {
                        return $this->normalizarCodigoBaseSeguimiento($ot->codigo);
                    });

                $codigosPedido = $otsPorCodigoPedido
                    ->keys()
                    ->filter()
                    ->values();

                if ($codigosPedido->isNotEmpty()) {
                    $huerfanasQuery = DB::table('ot_logistica_remisiones')
                        ->whereNull('id_ot')
                        ->whereNotNull('fecha_remision');

                    if ($pedido->fecha_pedido) {
                        $huerfanasQuery->where(
                            'fecha_remision',
                            '>=',
                            $pedido->fecha_pedido->format('Y-m-d')
                        );
                    }

                    $huerfanasQuery->where(function ($q) use ($codigosPedido) {
                        foreach ($codigosPedido as $codigoBase) {
                            $q->orWhere('codigo', 'ILIKE', $codigoBase . '%')
                                ->orWhere('codigo', 'ILIKE', "'" . $codigoBase . '%');
                        }
                    });

                    $huerfanas = $huerfanasQuery
                        ->select(
                            'id',
                            'id_ot',
                            'codigo',
                            'fecha_remision',
                            'fecha_recepcion',
                            'sucursal_salida',
                            'sucursal_destino',
                            'sucursal_logistica',
                            'cod_sucursal_salida',
                            'cod_sucursal_destino',
                            'serie',
                            'numero_remision',
                            'cantidad'
                        )
                        ->get();

                    foreach ($huerfanas as $movimiento) {
                        $codigoBase = $this->normalizarCodigoBaseSeguimiento($movimiento->codigo);
                        $candidatas = collect($otsPorCodigoPedido->get($codigoBase, collect()));
                        $idsCandidatos = $candidatas
                            ->pluck('id_ot')
                            ->filter()
                            ->unique()
                            ->values();

                        if ($idsCandidatos->count() !== 1) {
                            continue;
                        }

                        $movimiento->id_ot = (int) $idsCandidatos->first();
                        $movimientos->push($movimiento);
                    }
                }

                $movimientos = $movimientos
                    ->filter(function ($movimiento) use ($idsOt) {
                        return in_array((int) $movimiento->id_ot, array_map('intval', $idsOt), true);
                    })
                    ->sortBy(function ($movimiento) {
                        return sprintf(
                            '%s-%010d',
                            (string) ($movimiento->fecha_remision ?? ''),
                            (int) ($movimiento->id ?? 0)
                        );
                    })
                    ->values();

                $movimientosRemision = $movimientos->groupBy('id_ot');

                /*
                 * Se resume desde la colección unificada para que también
                 * participen las remisiones sin detalle logístico.
                 */
                $remisiones = $movimientos
                    ->groupBy('id_ot')
                    ->map(function ($movimientosOt) {
                        return collect($movimientosOt)
                            ->groupBy(function ($r) {
                                $destino = trim((string) (($r->sucursal_logistica ?? null)
                                    ?: ($r->sucursal_destino ?? null)));

                                return strtoupper($destino)
                                    . '|'
                                    . (string) ($r->cod_sucursal_destino ?? '');
                            })
                            ->map(function ($grupo) {
                                $primero = $grupo->first();
                                $fechasRemision = $grupo->pluck('fecha_remision')->filter();
                                $fechasRecepcion = $grupo->pluck('fecha_recepcion')->filter();

                                return (object) [
                                    'id_ot' => (int) $primero->id_ot,
                                    'sucursal_logistica' => $primero->sucursal_logistica,
                                    'sucursal_destino' => $primero->sucursal_destino,
                                    'cod_sucursal_destino' => $primero->cod_sucursal_destino,
                                    'enviado' => (int) $grupo->sum('cantidad'),
                                    'recibido' => (int) $grupo
                                        ->filter(function ($r) {
                                            return !empty($r->fecha_recepcion);
                                        })
                                        ->sum('cantidad'),
                                    'primera_remision' => $fechasRemision->isNotEmpty()
                                        ? $fechasRemision->min()
                                        : null,
                                    'ultima_remision' => $fechasRemision->isNotEmpty()
                                        ? $fechasRemision->max()
                                        : null,
                                    'primera_recepcion' => $fechasRecepcion->isNotEmpty()
                                        ? $fechasRecepcion->min()
                                        : null,
                                    'ultima_recepcion' => $fechasRecepcion->isNotEmpty()
                                        ? $fechasRecepcion->max()
                                        : null,
                                ];
                            })
                            ->values();
                    });
            }
        }

        foreach ($ots as $ot) {
            $porProceso = collect($trazas->get($ot->id_ot, collect()))->keyBy('proceso');

            $entrada = $porProceso->get('TERMINACION - TERMINACION');
            $pt = $porProceso->get('TERMINACION - PRODUCTO TERMINADO');
            $salidaLogistica = $porProceso->get('LOGISTICA - LOGISTICA Y DISTRIBUCION');

            $ot->ingreso_terminacion = (int) ($entrada->cantidad ?? 0);
            $ot->producto_terminado = (int) ($pt->cantidad ?? 0);
            $ot->distribuido = (int) ($salidaLogistica->cantidad ?? 0);
            $ot->fecha_ingreso = $entrada->primera_fecha ?? null;
            $ot->fecha_pt = $pt->ultima_fecha ?? null;
            $ot->fecha_logistica = $salidaLogistica->primera_fecha ?? null;
            $ot->fecha_logistica_primera = $salidaLogistica->primera_fecha ?? null;
            $ot->fecha_logistica_ultima = $salidaLogistica->ultima_fecha ?? null;

            $ot->locales = collect($remisiones->get($ot->id_ot, collect()))->map(function ($r) {
                $r->local = $r->sucursal_logistica ?: $r->sucursal_destino ?: ('Sucursal ' . $r->cod_sucursal_destino);
                $r->enviado = (int) $r->enviado;
                $r->recibido = (int) $r->recibido;
                $r->pendiente = max(0, $r->enviado - $r->recibido);
                $r->estado_local = $r->enviado > 0 && $r->recibido >= $r->enviado
                    ? 'RECIBIDO'
                    : ($r->recibido > 0 ? 'PARCIAL' : 'EN TRANSITO');
                return $r;
            })->values();

            // Auditoría de movimientos: conserva origen -> destino. No se suma como prendas nuevas.
            $movsOt = collect($movimientosRemision->get($ot->id_ot, collect()));
            $ot->movimientos_detalle = $movsOt->map(function ($m) {
                $origen = trim((string) ($m->sucursal_salida ?? ''));
                $destino = trim((string) ($m->sucursal_logistica ?: $m->sucursal_destino));
                $m->origen_mostrar = $origen !== '' ? $origen : ('Sucursal ' . ($m->cod_sucursal_salida ?? '-'));
                $m->destino_mostrar = $destino !== '' ? $destino : ('Sucursal ' . ($m->cod_sucursal_destino ?? '-'));
                $m->cantidad = (int) $m->cantidad;
                $origenNorm = strtoupper($m->origen_mostrar);
                $esCentral = (int) ($m->cod_sucursal_salida ?? 0) === 1
                    || in_array($origenNorm, ['CASA CENTRAL', 'MATRIZ'], true);

                $m->tipo_movimiento = $esCentral
                    ? 'DESPACHO CENTRAL'
                    : 'REDISTRIBUCION';
                return $m;
            })->values();
            $ot->cantidad_movimientos = $ot->movimientos_detalle->count();

            // Los 12 locales comerciales se controlan separados del canal Mayorista/Depósito.
            // CASA CENTRAL y MATRIZ son nodos del canal mayorista y no deben inflar el contador de locales.
            $esMayorista = function ($local) {
                $nombre = strtoupper(trim((string) $local->local));

                return in_array(
                    $nombre,
                    ['CASA CENTRAL', 'MATRIZ', 'COMERCIAL MATRIZ'],
                    true
                );
            };

            $ot->locales_comerciales = $ot->locales->reject($esMayorista)->values();
            $ot->canal_mayorista = $ot->locales->filter($esMayorista)->values();

            // Movimientos físicos: auditoría. Pueden superar la cantidad de la OT por retornos/reenvíos.
            $ot->movimientos_fisicos = (int) $ot->locales->sum('enviado');
            $ot->movimientos_confirmados = (int) $ot->locales->sum('recibido');

            // Avance efectivo: nunca supera las prendas reales de la OT.
            $topeOt = max(0, (int) $ot->cantidad_orden);
            $ot->enviado = min($topeOt, $ot->movimientos_fisicos);
            $ot->recibido = min($topeOt, $ot->movimientos_confirmados);
            $ot->movimientos_adicionales = max(0, $ot->movimientos_fisicos - $topeOt);
            $ot->movimientos_confirmados_adicionales = max(0, $ot->movimientos_confirmados - $topeOt);

            $ot->locales_enviados = $ot->locales_comerciales->count();
            $ot->locales_confirmados = $ot->locales_comerciales->where('estado_local', 'RECIBIDO')->count();
            $ot->pendiente_recepcion = max(0, $topeOt - $ot->recibido);

            // Etapa real de punta a punta. No se infiere por una etiqueta manual:
            // se determina por la evidencia existente en trazabilidad/remisiones.
            if ($ot->ingreso_terminacion <= 0) {
                $ot->etapa_actual = 'PENDIENTE TERMINACION';
                $ot->etapa_numero = 0;
                $ot->estado_seguimiento = 'SIN TERMINACION';
            } elseif ($ot->producto_terminado < $ot->ingreso_terminacion) {
                $ot->etapa_actual = 'TERMINACION';
                $ot->etapa_numero = 1;
                $ot->estado_seguimiento = 'EN TERMINACION';
            } elseif ($ot->movimientos_fisicos > 0) {
                /*
                 * Una remisión real tiene más peso que la falta de
                 * LOGISTICA - LOGISTICA Y DISTRIBUCION. Si ya existe documento
                 * de salida, la OT debe avanzar a REMISION aunque el plan todavía
                 * no haya sido asociado.
                 */
                if ($ot->recibido < $ot->enviado) {
                    $ot->etapa_actual = 'REMISION';
                    $ot->etapa_numero = 4;
                    $ot->estado_seguimiento = $ot->recibido > 0
                        ? 'RECEPCION PARCIAL'
                        : 'REMISIONADO';
                } else {
                    $ot->etapa_actual = 'RECEPCION LOCAL';
                    $ot->etapa_numero = 5;
                    $ot->estado_seguimiento = 'COMPLETO';
                }
            } elseif ($ot->distribuido > 0) {
                $ot->etapa_actual = 'LOGISTICA';
                $ot->etapa_numero = 3;
                $ot->estado_seguimiento = 'EN LOGISTICA';
            } else {
                $ot->etapa_actual = 'PRODUCTO TERMINADO';
                $ot->etapa_numero = 2;
                $ot->estado_seguimiento = 'TERMINADO';
            }

            /*
             * Los saldos cuantitativos y el estado principal salen del mismo
             * servicio usado por Control Terminación y Dashboard Logística.
             * El detalle de movimientos se conserva para auditoría.
             */
            $conciliacion = $conciliacionPorOt->get($ot->id_ot);

            if ($conciliacion) {
                $ot->ingreso_terminacion =
                    (int) $conciliacion->ingreso_terminacion;
                $ot->producto_terminado =
                    (int) $conciliacion->producto_terminado;
                $ot->distribuido =
                    (int) $conciliacion->planificado;
                $ot->enviado =
                    (int) $conciliacion->remitido_original;
                $ot->recibido =
                    (int) $conciliacion->recibido_original;

                $ot->cierre_remitido_reconocido =
                    (int) ($conciliacion->remitido_cierre ?? 0);

                $ot->cierre_recibido_reconocido =
                    (int) ($conciliacion->recibido_cierre ?? 0);

                $ot->pendiente_recepcion =
                    (int) $conciliacion->en_transito;
                $ot->estado_conciliacion =
                    $conciliacion->estado_conciliacion;

                switch ($conciliacion->estado_conciliacion) {
                    case 'FALTA TERMINACION':
                        $ot->etapa_actual = 'TERMINACION';
                        $ot->etapa_numero = 1;
                        $ot->estado_seguimiento = 'EN TERMINACION';
                        break;

                    case 'SIN DESTINO':
                        $ot->etapa_actual = 'PRODUCTO TERMINADO';
                        $ot->etapa_numero = 2;
                        $ot->estado_seguimiento = 'SIN DESTINO';
                        break;

                    case 'PENDIENTE REMITIR':
                    case 'PENDIENTE SALIDA':
                        $ot->etapa_actual = 'LOGISTICA';
                        $ot->etapa_numero = 3;
                        $ot->estado_seguimiento = 'EN LOGISTICA';
                        break;

                    case 'EN TRANSITO':
                        $ot->etapa_actual = 'REMISION';
                        $ot->etapa_numero = 4;
                        $ot->estado_seguimiento = 'REMISIONADO';
                        break;

                    case 'CONFIRMADO':
                        $ot->etapa_actual = 'RECEPCION LOCAL';
                        $ot->etapa_numero = 5;
                        $ot->estado_seguimiento = 'COMPLETO';
                        break;
                }
            }

            $ot->porcentaje_seguimiento = $ot->etapa_numero > 0
                ? (int) round(($ot->etapa_numero / 5) * 100)
                : 0;
        }

        /*
         * KPI de atención por OT:
         * FECHA PEDIDO -> PRIMER DESPACHO CENTRAL POSTERIOR AL PEDIDO -> RECEPCION.
         *
         * La fecha LOGISTICA - LOGISTICA Y DISTRIBUCION se conserva como antecedente
         * operativo de la OT. Si es anterior al pedido significa que la OT ya estaba
         * disponible; no es un error y no debe impedir medir la atención del pedido.
         */
        $fechaPedido = $pedido->fecha_pedido ? Carbon::parse($pedido->fecha_pedido)->startOfDay() : null;
        $kpisOt = collect();
        $movimientosAnteriores = 0;

        foreach ($ots as $ot) {
            $fechaLogisticaHistorica = !empty($ot->fecha_logistica_primera)
                ? Carbon::parse($ot->fecha_logistica_primera)->startOfDay()
                : null;

            $otDisponiblePreviamente = $fechaPedido
                && $fechaLogisticaHistorica
                && $fechaLogisticaHistorica->lt($fechaPedido);

            $movsOt = collect($movimientosRemision->get($ot->id_ot, collect()));

            // Para atender el pedido sólo cuenta el despacho original desde Central/Matriz.
            // Las redistribuciones entre locales quedan fuera del KPI.
            $despachosCentral = $movsOt->filter(function ($mov) {
                $origen = strtoupper(trim((string) ($mov->sucursal_salida ?? '')));
                $destino = strtoupper(trim((string) (($mov->sucursal_logistica ?? null)
                    ?: ($mov->sucursal_destino ?? null))));

                $origenCentral = (int) ($mov->cod_sucursal_salida ?? 0) === 1
                    || in_array($origen, ['CASA CENTRAL', 'MATRIZ'], true);

                $destinoCentral = (int) ($mov->cod_sucursal_destino ?? 0) === 1
                    || in_array($destino, ['CASA CENTRAL', 'MATRIZ', ''], true);

                return $origenCentral && !$destinoCentral;
            });

            if ($fechaPedido) {
                $movimientosAnteriores += $despachosCentral->filter(function ($mov) use ($fechaPedido) {
                    return !empty($mov->fecha_remision)
                        && Carbon::parse($mov->fecha_remision)->startOfDay()->lt($fechaPedido);
                })->count();

                // El despacho que atiende ESTE pedido nunca puede ser anterior al pedido.
                $despachosCentral = $despachosCentral->filter(function ($mov) use ($fechaPedido) {
                    return !empty($mov->fecha_remision)
                        && Carbon::parse($mov->fecha_remision)->startOfDay()->gte($fechaPedido);
                });
            } else {
                $despachosCentral = $despachosCentral->filter(function ($mov) {
                    return !empty($mov->fecha_remision);
                });
            }

            /*
             * KPI por OT:
             * - El despacho mostrado es la primera remisión Central -> Local posterior al pedido.
             * - La confirmación es la PRIMERA recepción válida de cualquiera de esos despachos,
             *   no la recepción del mismo documento del primer despacho. Así evitamos que una
             *   sucursal con confirmación tardía (p. ej. SL) distorsione el tiempo de atención
             *   cuando otro local ya confirmó antes.
             */
            $primerDespacho = $despachosCentral
                ->sortBy(function ($mov) {
                    return $mov->fecha_remision . ' '
                        . str_pad((string) ($mov->numero_remision ?? ''), 20, '0', STR_PAD_LEFT);
                })
                ->first();

            // Para el seguimiento del pedido, "Entrada a Logística" comienza cuando
            // la OT queda como PRODUCTO TERMINADO. La trazabilidad de LOGISTICA se
            // conserva aparte como antecedente operativo, pero no define esta columna.
            $ot->kpi_fecha_logistica = !empty($ot->fecha_pt)
                ? Carbon::parse($ot->fecha_pt)->startOfDay()->format('Y-m-d')
                : null;
            $ot->kpi_ot_disponible_previamente = $otDisponiblePreviamente;
            $ot->kpi_fecha_envio = $primerDespacho->fecha_remision ?? null;
            $ot->kpi_fecha_recepcion = null;
            $ot->kpi_dias = null;
            $ot->kpi_dias_logistica = null;

            if (!$primerDespacho) {
                $tuvoDespachoAnterior = $fechaPedido && $movsOt->contains(function ($mov) use ($fechaPedido) {
                    $origen = strtoupper(trim((string) ($mov->sucursal_salida ?? '')));
                    $destino = strtoupper(trim((string) (($mov->sucursal_logistica ?? null)
                        ?: ($mov->sucursal_destino ?? null))));

                    $origenCentral = (int) ($mov->cod_sucursal_salida ?? 0) === 1
                        || in_array($origen, ['CASA CENTRAL', 'MATRIZ'], true);

                    $destinoCentral = (int) ($mov->cod_sucursal_destino ?? 0) === 1
                        || in_array($destino, ['CASA CENTRAL', 'MATRIZ', ''], true);

                    return $origenCentral
                        && !$destinoCentral
                        && !empty($mov->fecha_remision)
                        && Carbon::parse($mov->fecha_remision)->startOfDay()->lt($fechaPedido);
                });

                $ot->kpi_estado = ($otDisponiblePreviamente && $tuvoDespachoAnterior)
                    ? 'CONFIRMADO'
                    : 'SIN DESPACHO DEL PEDIDO';
            } else {
                $recepcionValida = $despachosCentral
                    ->pluck('fecha_recepcion')
                    ->filter()
                    ->filter(function ($fecha) use ($fechaPedido) {
                        $recepcion = Carbon::parse($fecha)->startOfDay();

                        return !$fechaPedido || $recepcion->gte($fechaPedido);
                    })
                    ->map(function ($fecha) {
                        return Carbon::parse($fecha)->startOfDay()->format('Y-m-d');
                    })
                    ->min();

                $ot->kpi_fecha_recepcion = $recepcionValida;

                if (!$recepcionValida) {
                    $ot->kpi_estado = 'REMISIONADO';
                } else {
                    $ot->kpi_estado = 'CONFIRMADO';

                    if ($fechaPedido) {
                        $ot->kpi_dias = $fechaPedido->diffInDays(
                            Carbon::parse($recepcionValida)->startOfDay(),
                            false
                        );
                    }

                    $ot->kpi_dias_logistica = Carbon::parse($primerDespacho->fecha_remision)
                        ->startOfDay()
                        ->diffInDays(Carbon::parse($recepcionValida)->startOfDay(), false);
                }
            }

            $kpisOt->push((object) [
                'id_ot' => $ot->id_ot,
                'nro_ot' => $ot->nro_ot,
                'fecha_terminacion' => $ot->fecha_ingreso,
                'fecha_logistica' => $ot->kpi_fecha_logistica,
                'ot_disponible_previamente' => $ot->kpi_ot_disponible_previamente,
                'fecha_envio' => $ot->kpi_fecha_envio,
                'fecha_recepcion' => $ot->kpi_fecha_recepcion,
                'dias' => $ot->kpi_dias,
                'dias_logistica' => $ot->kpi_dias_logistica,
                'estado' => $ot->kpi_estado,
            ]);
        }

        $salidasLogisticaValidas = $kpisOt->pluck('fecha_envio')->filter();
        $recepcionesValidas = $kpisOt->pluck('fecha_recepcion')->filter();
        $diasValidos = $kpisOt->pluck('dias')->filter(function ($dias) {
            return $dias !== null && $dias >= 0;
        });

        $primerEnvioLogistica = $salidasLogisticaValidas->min();
        $ultimoEnvioLogistica = $salidasLogisticaValidas->max();
        $primeraConfirmacion = $recepcionesValidas->min();
        $ultimaConfirmacion = $recepcionesValidas->max();

        $resumen = (object) [
            'ots' => $ots->count(),
            'cantidad' => (int) $ots->sum('cantidad_orden'),
            'terminado' => (int) $ots->sum('producto_terminado'),
            'enviado' => (int) $ots->sum('enviado'),
            'recibido' => (int) $ots->sum('recibido'),
            'completas' => $ots->where('estado_seguimiento', 'COMPLETO')->count(),
            'fecha_pedido' => $pedido->fecha_pedido,
            'primer_envio_logistica' => $primerEnvioLogistica,
            'ultimo_envio_logistica' => $ultimoEnvioLogistica,
            'primera_confirmacion' => $primeraConfirmacion,
            'ultima_confirmacion' => $ultimaConfirmacion,
            'dias_primera_confirmacion' => ($fechaPedido && $primeraConfirmacion)
                ? $fechaPedido->diffInDays(Carbon::parse($primeraConfirmacion)->startOfDay(), false)
                : null,
            'dias_confirmacion_total' => ($fechaPedido && $ultimaConfirmacion)
                ? $fechaPedido->diffInDays(Carbon::parse($ultimaConfirmacion)->startOfDay(), false)
                : null,
            'dias_promedio_confirmacion' => $diasValidos->isNotEmpty()
                ? round($diasValidos->avg(), 1)
                : null,
            'ots_con_envio_valido' => $salidasLogisticaValidas->count(),
            'ots_confirmadas_kpi' => $kpisOt->where('estado', 'CONFIRMADO')->count(),
            'ots_distribuidas_antes_pedido' => 0,
            'movimientos_anteriores_omitidos' => $movimientosAnteriores,
            'dias_transcurridos' => ($fechaPedido && $recepcionesValidas->isEmpty())
                ? $fechaPedido->diffInDays(Carbon::today(), false)
                : null,
        ];

        return view('seguimiento_pedidos.show', compact('pedido', 'ots', 'resumen'));
    }

    /**
     * Resume cada pedido usando la misma conciliación cuantitativa de
     * Control Terminación y Dashboard Logística.
     *
     * "completas" exige estado CONFIRMADO de la OT; una primera recepción
     * aislada ya no alcanza para declarar completo el pedido.
     */
    private function resumenConciliadoPedidos($pedidos)
    {
        $pedidos = collect($pedidos);

        $idsPedido = $pedidos
            ->pluck('id')
            ->filter()
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values();

        if ($idsPedido->isEmpty()) {
            return collect();
        }

        $detalles = DB::table('seguimiento_pedido_detalle as spd')
            ->join('ot as o', 'o.id_ot', '=', 'spd.id_ot')
            ->whereIn(
                'spd.seguimiento_pedido_id',
                $idsPedido->all()
            )
            ->select(
                'spd.seguimiento_pedido_id',
                'o.id_ot',
                'o.cantidad_orden'
            )
            ->get();

        $idsOt = $detalles
            ->pluck('id_ot')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $conciliaciones = $this->conciliacionService
            ->conciliarPorIds($idsOt);

        /*
         * KPI temporal: para cada OT buscamos su PRIMERA recepción válida
         * posterior al pedido. Luego, por pedido, tomamos la más tardía de
         * esas primeras recepciones.
         */
        $primerasRecepciones = DB::table(
            'seguimiento_pedido_detalle as spd'
        )
            ->join(
                'seguimiento_pedido as sp',
                'sp.id',
                '=',
                'spd.seguimiento_pedido_id'
            )
            ->join(
                'ot_logistica_remisiones as r',
                'r.id_ot',
                '=',
                'spd.id_ot'
            )
            ->whereIn(
                'spd.seguimiento_pedido_id',
                $idsPedido->all()
            )
            ->whereNotNull('r.fecha_recepcion')
            ->where(function ($origen) {
                $origen->whereRaw(
                    "UPPER(TRIM(COALESCE(r.sucursal_salida, ''))) IN ('CASA CENTRAL', 'MATRIZ')"
                )->orWhere('r.cod_sucursal_salida', 1);
            })
            ->whereRaw(
                "UPPER(TRIM(COALESCE(r.sucursal_logistica, r.sucursal_destino, ''))) NOT IN ('CASA CENTRAL', 'MATRIZ', 'COMERCIAL MATRIZ', '')"
            )
            ->where(function ($fecha) {
                $fecha->whereNull('sp.fecha_pedido')
                    ->orWhereColumn(
                        'r.fecha_remision',
                        '>=',
                        'sp.fecha_pedido'
                    );
            })
            ->where(function ($fecha) {
                $fecha->whereNull('sp.fecha_pedido')
                    ->orWhereColumn(
                        'r.fecha_recepcion',
                        '>=',
                        'sp.fecha_pedido'
                    );
            })
            ->groupBy(
                'spd.seguimiento_pedido_id',
                'r.id_ot'
            )
            ->select(
                'spd.seguimiento_pedido_id',
                'r.id_ot',
                DB::raw(
                    'MIN(r.fecha_recepcion) as primera_recepcion'
                )
            )
            ->get()
            ->groupBy('seguimiento_pedido_id');

        return $detalles
            ->groupBy('seguimiento_pedido_id')
            ->map(function ($filas, $idPedido) use (
                $conciliaciones,
                $primerasRecepciones
            ) {
                $cantidad = 0;
                $productoTerminado = 0;
                $remitido = 0;
                $recibido = 0;
                $completas = 0;

                foreach ($filas as $fila) {
                    $cantidad += (int) $fila->cantidad_orden;

                    $c = $conciliaciones->get(
                        (int) $fila->id_ot
                    );

                    if (!$c) {
                        continue;
                    }

                    $productoTerminado +=
                        (int) $c->producto_terminado;
                    $remitido +=
                        (int) $c->remitido_original;
                    $recibido +=
                        (int) $c->recibido_original;

                    if ($c->estado_conciliacion === 'CONFIRMADO') {
                        $completas++;
                    }
                }

                $recepciones = collect(
                    $primerasRecepciones->get(
                        $idPedido,
                        collect()
                    )
                )
                    ->pluck('primera_recepcion')
                    ->filter();

                return (object) [
                    'ots' => $filas
                        ->pluck('id_ot')
                        ->unique()
                        ->count(),
                    'cantidad' => $cantidad,
                    'producto_terminado' => $productoTerminado,
                    'remitido' => $remitido,
                    'recibido' => $recibido,
                    'completas' => $completas,
                    'ultima_primera_confirmacion' =>
                        $recepciones->isNotEmpty()
                            ? $recepciones->max()
                            : null,
                ];
            });
    }

    /**
     * Normaliza códigos de variantes de remisión al código base de la OT.
     * Compatible con los códigos actuales: 050617789GRRN -> 050617789.
     */
    private function normalizarCodigoBaseSeguimiento($valor): string
    {
        $codigo = strtoupper(trim((string) $valor));
        $codigo = ltrim($codigo, "'’`");
        $codigo = preg_replace('/\\s+/u', '', $codigo);

        if ($codigo === '') {
            return '';
        }

        if (preg_match('/^(\\d{1,9})$/', $codigo)) {
            return str_pad($codigo, 9, '0', STR_PAD_LEFT);
        }

        if (preg_match('/^(\\d{9})/', $codigo, $coincidencia)) {
            return $coincidencia[1];
        }

        return $codigo;
    }

}
