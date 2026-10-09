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
     * Control diario de cumplimiento de pedidos IT.
     *
     * Separa:
     * - lo pedido en cada fecha;
     * - lo que ya estaba disponible antes del pedido;
     * - lo que realmente alcanzó TERMINACION - TERMINACION ese mismo día;
     * - lo que quedó pendiente al cierre del día;
     * - la salida real del día, incluso si correspondía a pedidos anteriores.
     */
    public function seguimientoDiario(Request $request)
    {
        $pedidoId = $request->filled('pedido')
            ? (int) $request->input('pedido')
            : null;

        $pedidosIt = SeguimientoPedido::query()
            ->where('nro_pedido', 'ILIKE', self::PREFIJO . '%')
            ->select('id', 'nro_pedido', 'fecha_pedido')
            ->whereNotNull('fecha_pedido');

        if ($pedidoId) {
            $pedidosIt->where('id', $pedidoId);
        }

        $ultimaFechaPedido = (clone $pedidosIt)->max('fecha_pedido');

        $idsOtItQuery = DB::table(
            'seguimiento_pedido_detalle as spd'
        )
            ->join(
                'seguimiento_pedido as sp',
                'sp.id',
                '=',
                'spd.seguimiento_pedido_id'
            )
            ->where('sp.nro_pedido', 'ILIKE', self::PREFIJO . '%');

        if ($pedidoId) {
            $idsOtItQuery->where('sp.id', $pedidoId);
        }

        $idsOtIt = $idsOtItQuery
            ->pluck('spd.id_ot')
            ->unique()
            ->values();

        $ultimaFechaSalida = null;

        if ($idsOtIt->isNotEmpty()) {
            $ultimaFechaSalida = DB::table('ot_trazabilidad')
                ->whereIn('id_ot', $idsOtIt->all())
                ->whereRaw(
                    "UPPER(TRIM(proceso)) = ?",
                    [self::PROCESO_CIERRE]
                )
                ->max('fecha_proceso');
        }

        $ultimaActividad = collect([
            $ultimaFechaPedido,
            $ultimaFechaSalida,
        ])->filter()->max();

        $referencia = $ultimaActividad
            ? Carbon::parse($ultimaActividad)->startOfDay()
            : Carbon::today();

        try {
            $hasta = $request->filled('hasta')
                ? Carbon::parse(
                    $request->input('hasta')
                )->startOfDay()
                : $referencia->copy();
        } catch (\Throwable $e) {
            $hasta = $referencia->copy();
        }

        try {
            if ($request->filled('desde')) {
                $desde = Carbon::parse(
                    $request->input('desde')
                )->startOfDay();
            } elseif ($pedidoId && $ultimaFechaPedido) {
                /*
                 * Al elegir un pedido concreto, mostrar por defecto toda su
                 * ventana operativa desde la fecha del pedido.
                 */
                $desde = Carbon::parse(
                    $ultimaFechaPedido
                )->startOfDay();
            } else {
                $desde = $hasta->copy()->subDays(13);
            }
        } catch (\Throwable $e) {
            $desde = $hasta->copy()->subDays(13);
        }

        if ($desde->gt($hasta)) {
            $tmp = $desde->copy();
            $desde = $hasta->copy();
            $hasta = $tmp;
        }

        /*
         * Mantener el reporte manejable.
         */
        if ($desde->diffInDays($hasta) > 89) {
            $desde = $hasta->copy()->subDays(89);
        }

        $pedidosDisponibles = SeguimientoPedido::query()
            ->where('nro_pedido', 'ILIKE', self::PREFIJO . '%')
            ->select('id', 'nro_pedido', 'fecha_pedido')
            ->orderByDesc('fecha_pedido')
            ->orderByDesc('id')
            ->get();

        /*
         * Relaciones IT completas. Se necesitan también pedidos anteriores
         * para identificar cuándo una salida del día corresponde a atraso.
         */
        $relacionesQuery = DB::table(
            'seguimiento_pedido_detalle as spd'
        )
            ->join(
                'seguimiento_pedido as sp',
                'sp.id',
                '=',
                'spd.seguimiento_pedido_id'
            )
            ->join('ot as o', 'o.id_ot', '=', 'spd.id_ot')
            ->where(
                'sp.nro_pedido',
                'ILIKE',
                self::PREFIJO . '%'
            )
            ->select(
                'sp.id as pedido_id',
                'sp.nro_pedido',
                'sp.fecha_pedido',
                'o.id_ot',
                'o.nro_ot',
                'o.codigo',
                'o.descripcion',
                'o.cantidad_orden'
            );

        if ($pedidoId) {
            $relacionesQuery->where('sp.id', $pedidoId);
        }

        $relaciones = $relacionesQuery->get();

        $idsOt = $relaciones
            ->pluck('id_ot')
            ->unique()
            ->values();

        /*
         * Primera fecha real en la que cada OT alcanzó el proceso objetivo.
         * Una OT cuenta una sola vez como salida.
         */
        $cierres = collect();

        if ($idsOt->isNotEmpty()) {
            $cierres = DB::table('ot_trazabilidad')
                ->whereIn('id_ot', $idsOt->all())
                ->whereRaw(
                    "UPPER(TRIM(proceso)) = ?",
                    [self::PROCESO_CIERRE]
                )
                ->select(
                    'id_ot',
                    DB::raw('MIN(fecha_proceso) as fecha_cierre'),
                    DB::raw('SUM(resultado) as cantidad_cierre')
                )
                ->groupBy('id_ot')
                ->get()
                ->keyBy('id_ot');
        }

        /*
         * Datos únicos de cada OT para calcular salidas físicas sin
         * duplicarlas aunque aparezcan en más de un pedido IT.
         */
        $otInfo = $relaciones
            ->groupBy('id_ot')
            ->map(function ($grupo) {
                $fila = $grupo->first();

                return (object) [
                    'id_ot' => (int) $fila->id_ot,
                    'nro_ot' => $fila->nro_ot,
                    'codigo' => $fila->codigo,
                    'descripcion' => $fila->descripcion,
                    'cantidad_orden' =>
                        (int) $fila->cantidad_orden,
                    'pedidos' => $grupo
                        ->map(function ($item) {
                            return (object) [
                                'id' => (int) $item->pedido_id,
                                'nro' => $item->nro_pedido,
                                'fecha' => $item->fecha_pedido
                                    ? Carbon::parse(
                                        $item->fecha_pedido
                                    )->format('Y-m-d')
                                    : null,
                            ];
                        })
                        ->unique(function ($item) {
                            return $item->id;
                        })
                        ->values(),
                ];
            });

        $dias = collect();
        $cursor = $desde->copy();

        while ($cursor->lte($hasta)) {
            $fecha = $cursor->format('Y-m-d');

            /*
             * Pedido del día: OT físicas únicas solicitadas en pedidos IT de
             * esa fecha.
             */
            $relacionesDia = $relaciones
                ->filter(function ($fila) use ($fecha) {
                    if (!$fila->fecha_pedido) {
                        return false;
                    }

                    return Carbon::parse(
                        $fila->fecha_pedido
                    )->format('Y-m-d') === $fecha;
                });

            $pedidosDia = $relacionesDia
                ->pluck('pedido_id')
                ->unique()
                ->values();

            $otsPedidas = $relacionesDia
                ->groupBy('id_ot')
                ->map(function ($grupo) use ($cierres, $fecha) {
                    $fila = $grupo->first();
                    $cierre = $cierres->get(
                        (int) $fila->id_ot
                    );

                    $fechaCierre = $cierre
                        && $cierre->fecha_cierre
                        ? Carbon::parse(
                            $cierre->fecha_cierre
                        )->format('Y-m-d')
                        : null;

                    if ($fechaCierre === null) {
                        $estado = 'PENDIENTE';
                    } elseif ($fechaCierre < $fecha) {
                        $estado = 'YA DISPONIBLE';
                    } elseif ($fechaCierre === $fecha) {
                        $estado = 'SALIO HOY';
                    } else {
                        $estado = 'SALIO DESPUES';
                    }

                    return (object) [
                        'id_ot' => (int) $fila->id_ot,
                        'nro_ot' => $fila->nro_ot,
                        'codigo' => $fila->codigo,
                        'descripcion' => $fila->descripcion,
                        'cantidad_orden' =>
                            (int) $fila->cantidad_orden,
                        'pedidos' => $grupo
                            ->pluck('nro_pedido')
                            ->filter()
                            ->unique()
                            ->values(),
                        'fecha_cierre' => $fechaCierre,
                        'estado' => $estado,
                    ];
                })
                ->values();

            $yaDisponibles = $otsPedidas
                ->where('estado', 'YA DISPONIBLE');

            $salieronHoyPedido = $otsPedidas
                ->where('estado', 'SALIO HOY');

            $salieronDespues = $otsPedidas
                ->where('estado', 'SALIO DESPUES');

            $pendientesActuales = $otsPedidas
                ->where('estado', 'PENDIENTE');

            /*
             * Pendiente al cierre de ese día:
             * - no estaba disponible antes;
             * - no salió ese mismo día.
             *
             * Incluye OT que posteriormente sí pudieron salir.
             */
            $pendientesAlCierre = $otsPedidas
                ->filter(function ($ot) {
                    return in_array(
                        $ot->estado,
                        ['PENDIENTE', 'SALIO DESPUES'],
                        true
                    );
                });

            /*
             * Salida física real del día: todas las OT IT cuya primera
             * TERMINACION - TERMINACION ocurrió ese día, aunque el pedido
             * haya sido de otra fecha.
             */
            $salidasDia = $cierres
                ->filter(function ($cierre) use ($fecha) {
                    return $cierre->fecha_cierre
                        && Carbon::parse(
                            $cierre->fecha_cierre
                        )->format('Y-m-d') === $fecha;
                })
                ->map(function ($cierre, $idOt) use (
                    $otInfo,
                    $fecha
                ) {
                    $info = $otInfo->get((int) $idOt);

                    if (!$info) {
                        return null;
                    }

                    $pedidosMismoDia = collect(
                        $info->pedidos
                    )->filter(function ($pedido) use ($fecha) {
                        return $pedido->fecha === $fecha;
                    });

                    return (object) [
                        'id_ot' => (int) $idOt,
                        'nro_ot' => $info->nro_ot,
                        'codigo' => $info->codigo,
                        'descripcion' => $info->descripcion,
                        'cantidad_orden' =>
                            (int) $info->cantidad_orden,
                        'pedidos' => collect(
                            $info->pedidos
                        )->pluck('nro')->values(),
                        'corresponde_hoy' =>
                            $pedidosMismoDia->isNotEmpty(),
                    ];
                })
                ->filter()
                ->values();

            $salidaPedidoDia = $salidasDia
                ->where('corresponde_hoy', true);

            $salidaOtrasFechas = $salidasDia
                ->where('corresponde_hoy', false);

            $totalPedidas = $otsPedidas->count();

            $cubiertasAlCierre =
                $yaDisponibles->count()
                + $salieronHoyPedido->count();

            $cobertura = $totalPedidas > 0
                ? round(
                    ($cubiertasAlCierre / $totalPedidas) * 100,
                    1
                )
                : null;

            $dias->push((object) [
                'fecha' => $fecha,
                'fecha_label' => $cursor->format('d/m/Y'),
                'dia_semana' => ucfirst(
                    $cursor->locale('es')->isoFormat('dddd')
                ),
                'pedidos' => $pedidosDia->count(),
                'ots_pedidas' => $totalPedidas,
                'prendas_pedidas' =>
                    (int) $otsPedidas->sum(
                        'cantidad_orden'
                    ),
                'ya_disponibles' =>
                    $yaDisponibles->count(),
                'prendas_ya_disponibles' =>
                    (int) $yaDisponibles->sum(
                        'cantidad_orden'
                    ),
                'salieron_hoy_pedido' =>
                    $salieronHoyPedido->count(),
                'prendas_salieron_hoy_pedido' =>
                    (int) $salieronHoyPedido->sum(
                        'cantidad_orden'
                    ),
                'pendientes_al_cierre' =>
                    $pendientesAlCierre->count(),
                'prendas_pendientes_al_cierre' =>
                    (int) $pendientesAlCierre->sum(
                        'cantidad_orden'
                    ),
                'salieron_despues' =>
                    $salieronDespues->count(),
                'pendientes_actuales' =>
                    $pendientesActuales->count(),
                'cobertura' => $cobertura,
                'salidas_reales' => $salidasDia->count(),
                'prendas_salidas_reales' =>
                    (int) $salidasDia->sum(
                        'cantidad_orden'
                    ),
                'salidas_del_pedido' =>
                    $salidaPedidoDia->count(),
                'salidas_otras_fechas' =>
                    $salidaOtrasFechas->count(),
                'ots_pedido' => $otsPedidas,
                'ots_salida' => $salidasDia,
            ]);

            $cursor->addDay();
        }

        /*
         * Primero la fecha más reciente.
         */
        $dias = $dias
            ->sortByDesc('fecha')
            ->values();

        $diasConPedido = $dias
            ->filter(function ($dia) {
                return $dia->ots_pedidas > 0;
            });

        $diasCumplidos = $diasConPedido
            ->filter(function ($dia) {
                return $dia->cobertura !== null
                    && $dia->cobertura >= 100;
            });

        $resumen = (object) [
            'dias' => $dias->count(),
            'dias_con_pedido' => $diasConPedido->count(),
            'dias_cumplidos' => $diasCumplidos->count(),
            'ots_pedidas' =>
                (int) $dias->sum('ots_pedidas'),
            'prendas_pedidas' =>
                (int) $dias->sum('prendas_pedidas'),
            'ots_mismo_dia' =>
                (int) $dias->sum(
                    'salieron_hoy_pedido'
                ),
            'ots_ya_disponibles' =>
                (int) $dias->sum('ya_disponibles'),
            'ots_pendientes_cierre' =>
                (int) $dias->sum(
                    'pendientes_al_cierre'
                ),
            'salidas_reales' =>
                (int) $dias->sum('salidas_reales'),
            'salidas_del_pedido' =>
                (int) $dias->sum(
                    'salidas_del_pedido'
                ),
            'salidas_otras_fechas' =>
                (int) $dias->sum(
                    'salidas_otras_fechas'
                ),
            'cumplimiento_dias' =>
                $diasConPedido->count() > 0
                    ? round(
                        (
                            $diasCumplidos->count()
                            / $diasConPedido->count()
                        ) * 100,
                        1
                    )
                    : 0,
        ];

        return view(
            'seguimiento_pedidos_ingreso_terminacion.seguimiento_diario',
            compact(
                'dias',
                'resumen',
                'desde',
                'hasta',
                'pedidoId',
                'pedidosDisponibles'
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
