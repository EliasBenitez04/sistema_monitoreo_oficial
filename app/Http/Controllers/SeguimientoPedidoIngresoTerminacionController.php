<?php

namespace App\Http\Controllers;

use App\Models\SeguimientoPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SeguimientoPedidoIngresoTerminacionController extends Controller
{
    private const PREFIJO = 'IT';
    private const PROCESO_CIERRE = 'TERMINACION - TERMINACION';

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ot dashboard');
    }

    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $query = SeguimientoPedido::query()
            ->where('seguimiento_pedido.nro_pedido', 'ILIKE', self::PREFIJO . '%')
            ->select('seguimiento_pedido.*')
            ->selectSub(function ($q) {
                $q->from('seguimiento_pedido_detalle as spd')
                    ->whereColumn('spd.seguimiento_pedido_id', 'seguimiento_pedido.id')
                    ->selectRaw('COUNT(DISTINCT spd.id_ot)');
            }, 'ots_total')
            ->selectSub(function ($q) {
                $q->from('seguimiento_pedido_detalle as spd')
                    ->join('ot as o', 'o.id_ot', '=', 'spd.id_ot')
                    ->whereColumn('spd.seguimiento_pedido_id', 'seguimiento_pedido.id')
                    ->selectRaw('COALESCE(SUM(o.cantidad_orden), 0)');
            }, 'cantidad_total')
            ->selectSub(function ($q) {
                $q->from('seguimiento_pedido_detalle as spd')
                    ->whereColumn('spd.seguimiento_pedido_id', 'seguimiento_pedido.id')
                    ->whereExists(function ($t) {
                        $t->select(DB::raw(1))
                            ->from('ot_trazabilidad as tr')
                            ->whereColumn('tr.id_ot', 'spd.id_ot')
                            ->whereRaw("UPPER(TRIM(tr.proceso)) = ?", [self::PROCESO_CIERRE]);
                    })
                    ->selectRaw('COUNT(DISTINCT spd.id_ot)');
            }, 'ots_completas')
            ->selectSub(function ($q) {
                $q->from('seguimiento_pedido_detalle as spd')
                    ->join('ot_trazabilidad as tr', 'tr.id_ot', '=', 'spd.id_ot')
                    ->whereColumn('spd.seguimiento_pedido_id', 'seguimiento_pedido.id')
                    ->whereRaw("UPPER(TRIM(tr.proceso)) = ?", [self::PROCESO_CIERRE])
                    ->selectRaw('COALESCE(SUM(tr.resultado), 0)');
            }, 'cantidad_ingreso')
            ->selectSub(function ($q) {
                $q->from('seguimiento_pedido_detalle as spd')
                    ->join('ot_trazabilidad as tr', 'tr.id_ot', '=', 'spd.id_ot')
                    ->whereColumn('spd.seguimiento_pedido_id', 'seguimiento_pedido.id')
                    ->whereRaw("UPPER(TRIM(tr.proceso)) = ?", [self::PROCESO_CIERRE])
                    ->selectRaw('MIN(tr.fecha_proceso)');
            }, 'primer_ingreso')
            ->selectSub(function ($q) {
                $q->from('seguimiento_pedido_detalle as spd')
                    ->join('ot_trazabilidad as tr', 'tr.id_ot', '=', 'spd.id_ot')
                    ->whereColumn('spd.seguimiento_pedido_id', 'seguimiento_pedido.id')
                    ->whereRaw("UPPER(TRIM(tr.proceso)) = ?", [self::PROCESO_CIERRE])
                    ->selectRaw('MAX(tr.fecha_proceso)');
            }, 'ultimo_ingreso')
            ->orderByDesc('seguimiento_pedido.id');

        if ($buscar !== '') {
            $query->where('seguimiento_pedido.nro_pedido', 'ILIKE', '%' . $buscar . '%');
        }

        $pedidos = $query->paginate(30)->appends($request->query());

        $pedidos->getCollection()->transform(function ($pedido) {
            $pedido->ots_total = (int) $pedido->ots_total;
            $pedido->ots_completas = (int) $pedido->ots_completas;
            $pedido->cantidad_total = (int) $pedido->cantidad_total;
            $pedido->cantidad_ingreso = min(
                $pedido->cantidad_total,
                (int) $pedido->cantidad_ingreso
            );

            $pedido->porcentaje = $pedido->ots_total > 0
                ? min(100, (int) round(($pedido->ots_completas / $pedido->ots_total) * 100))
                : 0;

            $pedido->completo = $pedido->ots_total > 0
                && $pedido->ots_completas >= $pedido->ots_total;

            $pedido->dias = null;
            $pedido->dias_en_curso = null;

            if ($pedido->fecha_pedido) {
                $inicio = Carbon::parse($pedido->fecha_pedido)->startOfDay();

                if ($pedido->completo && $pedido->ultimo_ingreso) {
                    $fin = Carbon::parse($pedido->ultimo_ingreso)->startOfDay();

                    if ($fin->gte($inicio)) {
                        $pedido->dias = $inicio->diffInDays($fin, false);
                    }
                } elseif (!$pedido->completo) {
                    $pedido->dias_en_curso = max(0, $inicio->diffInDays(Carbon::today(), false));
                }
            }

            return $pedido;
        });

        $resumen = (object) [
            'pedidos' => $pedidos->getCollection()->count(),
            'ots' => (int) $pedidos->getCollection()->sum('ots_total'),
            'ots_completas' => (int) $pedidos->getCollection()->sum('ots_completas'),
            'completos' => $pedidos->getCollection()->where('completo', true)->count(),
            'prendas' => (int) $pedidos->getCollection()->sum('cantidad_total'),
            'prendas_ingresadas' => (int) $pedidos->getCollection()->sum('cantidad_ingreso'),
        ];

        return view('seguimiento_pedidos_ingreso_terminacion.index', compact(
            'pedidos',
            'buscar',
            'resumen'
        ));
    }

    /**
     * Control diario de las OT que realmente alcanzaron
     * TERMINACION - TERMINACION.
     *
     * La fecha se elige directamente desde un calendario. Para esa fecha se
     * muestran todas las OT registradas en el proceso objetivo y se cruza cada
     * una contra los pedidos IT existentes hasta ese día.
     */
    public function seguimientoDiario(Request $request)
    {
        $ultimaFechaProceso = DB::table('ot_trazabilidad')
            ->whereRaw(
                "UPPER(TRIM(proceso)) = ?",
                [self::PROCESO_CIERRE]
            )
            ->max('fecha_proceso');

        $fechaInput = trim((string) $request->input('fecha', ''));

        try {
            if ($fechaInput !== '') {
                $fechaControl = Carbon::createFromFormat(
                    'Y-m-d',
                    $fechaInput
                )->startOfDay();
            } elseif ($ultimaFechaProceso) {
                $fechaControl = Carbon::parse(
                    $ultimaFechaProceso
                )->startOfDay();
            } else {
                $fechaControl = Carbon::today();
            }
        } catch (\Exception $e) {
            $fechaControl = $ultimaFechaProceso
                ? Carbon::parse($ultimaFechaProceso)->startOfDay()
                : Carbon::today();
        }

        $fechaControlTexto = $fechaControl->format('Y-m-d');

        /*
         * Estas son las OT que realmente figuran en el sistema original como
         * TERMINACION - TERMINACION en la fecha elegida.
         *
         * No filtramos por pedido: primero tomamos la salida real del día y
         * después verificamos si cada OT estaba solicitada.
         */
        $salidas = DB::table('ot_trazabilidad as tr')
            ->join('ot as o', 'o.id_ot', '=', 'tr.id_ot')
            ->whereRaw(
                "UPPER(TRIM(tr.proceso)) = ?",
                [self::PROCESO_CIERRE]
            )
            ->whereDate(
                'tr.fecha_proceso',
                $fechaControlTexto
            )
            ->select(
                'o.id_ot',
                'o.nro_ot',
                'o.codigo',
                'o.descripcion',
                'o.cantidad_orden',
                DB::raw(
                    'SUM(COALESCE(tr.resultado, 0)) as resultado_dia'
                ),
                DB::raw(
                    'MIN(tr.fecha_proceso) as primera_salida_dia'
                ),
                DB::raw(
                    'COUNT(*) as eventos_dia'
                )
            )
            ->groupBy(
                'o.id_ot',
                'o.nro_ot',
                'o.codigo',
                'o.descripcion',
                'o.cantidad_orden'
            )
            ->orderBy('o.nro_ot')
            ->get();

        $idsSalida = $salidas
            ->pluck('id_ot')
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values();

        /*
         * Buscar a qué pedido IT pertenece cada OT.
         *
         * Para justificar una salida, el pedido debe existir en la fecha
         * seleccionada. Si la OT fue incluida recién en un pedido posterior,
         * para este control sigue siendo una salida SIN PEDIDO.
         */
        $pedidosPorOt = collect();

        if ($idsSalida->isNotEmpty()) {
            $pedidosPorOt = DB::table(
                'seguimiento_pedido_detalle as spd'
            )
                ->join(
                    'seguimiento_pedido as sp',
                    'sp.id',
                    '=',
                    'spd.seguimiento_pedido_id'
                )
                ->whereIn(
                    'spd.id_ot',
                    $idsSalida->all()
                )
                ->where(
                    'sp.nro_pedido',
                    'ILIKE',
                    self::PREFIJO . '%'
                )
                ->select(
                    'spd.id_ot',
                    'sp.id as pedido_id',
                    'sp.nro_pedido',
                    'sp.fecha_pedido'
                )
                ->orderBy('sp.fecha_pedido')
                ->orderBy('sp.id')
                ->get()
                ->groupBy('id_ot');
        }

        $salidas = $salidas
            ->map(function ($salida) use (
                $pedidosPorOt,
                $fechaControlTexto
            ) {
                $pedidosVigentes = collect(
                    $pedidosPorOt->get(
                        (int) $salida->id_ot,
                        collect()
                    )
                )
                    ->filter(function ($item) use (
                        $fechaControlTexto
                    ) {
                        if (!$item->fecha_pedido) {
                            return false;
                        }

                        return date(
                            'Y-m-d',
                            strtotime($item->fecha_pedido)
                        ) <= $fechaControlTexto;
                    })
                    ->map(function ($item) {
                        return (object) [
                            'id' => (int) $item->pedido_id,
                            'nro' => $item->nro_pedido,
                            'fecha' => date(
                                'Y-m-d',
                                strtotime($item->fecha_pedido)
                            ),
                        ];
                    })
                    ->unique(function ($item) {
                        return $item->id;
                    })
                    ->values();

                $salida->pedidos_vigentes = $pedidosVigentes;
                $salida->tiene_pedido = $pedidosVigentes->isNotEmpty();

                return $salida;
            })
            ->values();

        $salidasConPedido = $salidas
            ->where('tiene_pedido', true)
            ->values();

        $salidasSinPedido = $salidas
            ->where('tiene_pedido', false)
            ->values();

        $resumen = (object) [
            'salidas_reales' => $salidas->count(),
            'salidas_con_pedido' => $salidasConPedido->count(),
            'salidas_sin_pedido' => $salidasSinPedido->count(),
            'prendas_programadas' => (int) $salidas->sum(
                'cantidad_orden'
            ),
            'prendas_salida' => (int) $salidas->sum(
                'resultado_dia'
            ),
            'porcentaje_justificado' => $salidas->count() > 0
                ? round(
                    (
                        $salidasConPedido->count()
                        / $salidas->count()
                    ) * 100,
                    1
                )
                : 0,
        ];

        return view(
            'seguimiento_pedidos_ingreso_terminacion.seguimiento_diario',
            compact(
                'fechaControl',
                'salidas',
                'resumen'
            )
        );
    }

    public function informeGerencial(Request $request)
    {
        $hoy = Carbon::today();

        $pedidos = SeguimientoPedido::query()
            ->where('nro_pedido', 'ILIKE', self::PREFIJO . '%')
            ->select('id', 'nro_pedido', 'fecha_pedido')
            ->orderBy('fecha_pedido')
            ->get();

        $idsPedido = $pedidos->pluck('id')->all();
        $filas = collect();

        if (!empty($idsPedido)) {
            $filas = DB::table('seguimiento_pedido_detalle as spd')
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

        $idsOt = $filas->pluck('id_ot')->unique()->values()->all();
        $trazas = collect();

        if (!empty($idsOt)) {
            $trazas = DB::table('ot_trazabilidad')
                ->whereIn('id_ot', $idsOt)
                ->select('id_trazabilidad', 'id_ot', 'proceso', 'resultado', 'fecha_proceso')
                ->orderBy('fecha_proceso')
                ->orderBy('id_trazabilidad')
                ->get()
                ->groupBy('id_ot');
        }

        $filas = $filas->map(function ($ot) use ($trazas, $hoy) {
            $historial = collect($trazas->get($ot->id_ot, collect()));

            $cierre = $historial
                ->filter(function ($t) {
                    return strtoupper(trim((string) $t->proceso)) === self::PROCESO_CIERRE;
                })
                ->sortBy('fecha_proceso')
                ->first();

            $ultimoProceso = $historial
                ->sortBy(function ($t) {
                    return sprintf('%s-%010d', (string) $t->fecha_proceso, (int) $t->id_trazabilidad);
                })
                ->last();

            $completo = $cierre !== null;
            $fechaPedido = $ot->fecha_pedido
                ? Carbon::parse($ot->fecha_pedido)->startOfDay()
                : null;

            $ot->completo = $completo;
            $ot->fecha_cierre = $cierre->fecha_proceso ?? null;
            $ot->proceso_actual = $ultimoProceso->proceso ?? 'SIN PROCESO';
            $ot->fecha_proceso_actual = $ultimoProceso->fecha_proceso ?? null;
            $ot->cantidad_proceso_actual = (int) ($ultimoProceso->resultado ?? 0);
            $ot->dias_pendiente = (!$completo && $fechaPedido)
                ? max(0, $fechaPedido->diffInDays($hoy, false))
                : 0;

            return $ot;
        });

        $pendientes = $filas
            ->where('completo', false)
            ->sort(function ($a, $b) {
                $procesoA = strtoupper(trim((string) $a->proceso_actual));
                $procesoB = strtoupper(trim((string) $b->proceso_actual));

                $cmp = strcmp($procesoA, $procesoB);
                if ($cmp !== 0) {
                    return $cmp;
                }

                return ((int) $b->dias_pendiente) <=> ((int) $a->dias_pendiente);
            })
            ->values();

        $completas = $filas->where('completo', true)->values();

        $totalOt = $filas->count();
        $totalCompletas = $completas->count();
        $totalPendientes = $pendientes->count();
        $avance = $totalOt > 0 ? round(($totalCompletas / $totalOt) * 100, 1) : 0;

        $pedidosConPendiente = $pendientes
            ->pluck('seguimiento_pedido_id')
            ->unique()
            ->count();

        $resumen = (object) [
            'pedidos' => $pedidos->count(),
            'pedidos_completos' => max(0, $pedidos->count() - $pedidosConPendiente),
            'pedidos_con_pendiente' => $pedidosConPendiente,
            'ots' => $totalOt,
            'ots_completas' => $totalCompletas,
            'ots_pendientes' => $totalPendientes,
            'prendas_pendientes' => (int) $pendientes->sum('cantidad_orden'),
            'avance' => $avance,
        ];

        $porProcesos = $pendientes
            ->groupBy(function ($fila) {
                $proceso = trim((string) $fila->proceso_actual);
                return $proceso !== '' ? $proceso : 'SIN PROCESO';
            })
            ->map(function ($grupo, $proceso) use ($totalPendientes) {
                $otsProceso = $grupo->count();

                return (object) [
                    'proceso' => $proceso,
                    'ots' => $otsProceso,
                    'prendas' => (int) $grupo->sum('cantidad_orden'),
                    'pedidos' => $grupo->pluck('seguimiento_pedido_id')->unique()->count(),
                    'porcentaje' => $totalPendientes > 0
                        ? round(($otsProceso / $totalPendientes) * 100, 1)
                        : 0,
                ];
            })
            ->sortByDesc('ots')
            ->values();

        return view('seguimiento_pedidos_ingreso_terminacion.informe_gerencial', compact(
            'pendientes',
            'resumen',
            'porProcesos'
        ));
    }


    public function show($id)
    {
        $pedido = SeguimientoPedido::query()
            ->where('nro_pedido', 'ILIKE', self::PREFIJO . '%')
            ->findOrFail($id);

        $ots = DB::table('seguimiento_pedido_detalle as spd')
            ->join('ot as o', 'o.id_ot', '=', 'spd.id_ot')
            ->where('spd.seguimiento_pedido_id', $pedido->id)
            ->select(
                'o.id_ot',
                'o.nro_ot',
                'o.codigo',
                'o.descripcion',
                'o.cantidad_orden'
            )
            ->orderBy('o.nro_ot')
            ->get();

        $idsOt = $ots->pluck('id_ot')->all();
        $ingresos = collect();

        if (!empty($idsOt)) {
            $ingresos = DB::table('ot_trazabilidad')
                ->whereIn('id_ot', $idsOt)
                ->whereRaw("UPPER(TRIM(proceso)) = ?", [self::PROCESO_CIERRE])
                ->select(
                    'id_ot',
                    DB::raw('SUM(resultado) as cantidad'),
                    DB::raw('MIN(fecha_proceso) as primera_fecha'),
                    DB::raw('MAX(fecha_proceso) as ultima_fecha')
                )
                ->groupBy('id_ot')
                ->get()
                ->keyBy('id_ot');
        }

        $fechaPedido = $pedido->fecha_pedido
            ? Carbon::parse($pedido->fecha_pedido)->startOfDay()
            : null;

        foreach ($ots as $ot) {
            $ingreso = $ingresos->get($ot->id_ot);

            $ot->cantidad_ingreso = min(
                (int) $ot->cantidad_orden,
                (int) ($ingreso->cantidad ?? 0)
            );
            $ot->fecha_ingreso = $ingreso->primera_fecha ?? null;
            $ot->fecha_ingreso_ultima = $ingreso->ultima_fecha ?? null;
            $ot->completo = $ingreso !== null;
            $ot->dias = null;
            $ot->ingreso_previo = false;

            if ($ot->completo && $fechaPedido && $ot->fecha_ingreso) {
                $fechaIngreso = Carbon::parse($ot->fecha_ingreso)->startOfDay();

                if ($fechaIngreso->lt($fechaPedido)) {
                    $ot->ingreso_previo = true;
                } else {
                    $ot->dias = $fechaPedido->diffInDays($fechaIngreso, false);
                }
            }
        }

        $totalOt = $ots->count();
        $completas = $ots->where('completo', true)->count();
        $cantidad = (int) $ots->sum('cantidad_orden');
        $cantidadIngreso = min($cantidad, (int) $ots->sum('cantidad_ingreso'));

        $fechasValidas = $ots->filter(function ($ot) {
            return $ot->completo && !$ot->ingreso_previo && !empty($ot->fecha_ingreso);
        })->pluck('fecha_ingreso')->filter();

        $ultimaFecha = $fechasValidas->max();

        $resumen = (object) [
            'ots' => $totalOt,
            'completas' => $completas,
            'pendientes' => max(0, $totalOt - $completas),
            'cantidad' => $cantidad,
            'cantidad_ingreso' => $cantidadIngreso,
            'porcentaje' => $totalOt > 0
                ? min(100, (int) round(($completas / $totalOt) * 100))
                : 0,
            'completo' => $totalOt > 0 && $completas >= $totalOt,
            'ultima_fecha' => $ultimaFecha,
            'dias' => ($fechaPedido && $ultimaFecha)
                ? $fechaPedido->diffInDays(Carbon::parse($ultimaFecha)->startOfDay(), false)
                : null,
            'dias_en_curso' => ($fechaPedido && $completas < $totalOt)
                ? max(0, $fechaPedido->diffInDays(Carbon::today(), false))
                : null,
        ];

        return view('seguimiento_pedidos_ingreso_terminacion.show', compact(
            'pedido',
            'ots',
            'resumen'
        ));
    }
}
