<?php

namespace App\Http\Controllers;

use App\Imports\SeguimientoPedidoProduccionImport;
use App\Models\SeguimientoPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class SeguimientoPedidoProduccionController extends Controller
{
    private const PROCESO_CIERRE = 'TERMINACION - INGRESO TERMINACION';

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ot dashboard');
    }

    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $query = SeguimientoPedido::query()
            ->where('seguimiento_pedido.nro_pedido', 'ILIKE', 'P%')
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
        ];

        return view('seguimiento_pedidos_produccion.index', compact(
            'pedidos',
            'buscar',
            'resumen'
        ));
    }

    /**
     * Avance diario de los pedidos P.
     *
     * Una OT se cuenta una sola vez por día, aunque tenga varios registros
     * de trazabilidad en la misma fecha. Para representar hasta dónde avanzó
     * ese día se toma el proceso de mayor orden dentro del flujo productivo.
     */
    public function avanceDiario(Request $request)
    {
        $ordenProcesos = [
            'DISEÑO - ORDEN DE TRABAJO'            => 10,
            'DISEÑO - MOLDERIA'                    => 15,
            'DISEÑO - PROTOTIPO'                   => 20,
            'DISEÑO - DISEÑO GRAFICO'              => 25,

            'PRODUCCION - TIZADAS'                 => 30,
            'PRODUCCION - CORTE'                   => 40,
            'PRODUCCION - LOTEO Y DISTRIBUCION'    => 45,
            'PRODUCCION - REVELADO'                => 50,
            'PRODUCCION - SERIGRAFIA'              => 55,
            'PRODUCCION - BORDADO'                 => 60,
            'PRODUCCION - COSTURA INTERNA'         => 70,
            'PRODUCCION - ATRAQUES'                => 75,
            'PRODUCCION - LAVANDERIA'              => 80,
            'PRODUCCION - PRETERMINACION'          => 85,

            'TERMINACION - INGRESO TERMINACION'    => 90,
            'TERMINACION - TERMINACION'            => 95,
            'TERMINACION - PRODUCTO TERMINADO'     => 100,

            'LOGISTICA - LOGISTICA Y DISTRIBUCION' => 110,
        ];

        $pedidoId = $request->filled('pedido')
            ? (int) $request->input('pedido')
            : null;

        $pedidosDisponibles = SeguimientoPedido::query()
            ->where('nro_pedido', 'ILIKE', 'P%')
            ->select('id', 'nro_pedido', 'fecha_pedido')
            ->orderBy('fecha_pedido')
            ->orderBy('id')
            ->get();

        $relacionesQuery = DB::table('seguimiento_pedido_detalle as spd')
            ->join(
                'seguimiento_pedido as sp',
                'sp.id',
                '=',
                'spd.seguimiento_pedido_id'
            )
            ->join('ot as o', 'o.id_ot', '=', 'spd.id_ot')
            ->where('sp.nro_pedido', 'ILIKE', 'P%')
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

        $relaciones = $relacionesQuery
            ->orderBy('sp.fecha_pedido')
            ->orderBy('o.nro_ot')
            ->get();

        $idsOt = $relaciones
            ->pluck('id_ot')
            ->unique()
            ->values();

        $otInfo = $relaciones
            ->groupBy('id_ot')
            ->map(function ($grupo) {
                $primera = $grupo->first();

                return (object) [
                    'id_ot' => (int) $primera->id_ot,
                    'nro_ot' => $primera->nro_ot,
                    'codigo' => $primera->codigo,
                    'descripcion' => $primera->descripcion,
                    'cantidad_orden' => (int) $primera->cantidad_orden,
                    'pedidos' => $grupo
                        ->pluck('nro_pedido')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),
                    'pedido_ids' => $grupo
                        ->pluck('pedido_id')
                        ->map(function ($id) {
                            return (int) $id;
                        })
                        ->unique()
                        ->values()
                        ->all(),
                ];
            });

        $fechaMaxima = null;

        if ($idsOt->isNotEmpty()) {
            $fechaMaxima = DB::table('ot_trazabilidad')
                ->whereIn('id_ot', $idsOt->all())
                ->max('fecha_proceso');
        }

        $referencia = $fechaMaxima
            ? Carbon::parse($fechaMaxima)->startOfDay()
            : Carbon::today();

        try {
            $fechaHasta = $request->filled('hasta')
                ? Carbon::parse($request->input('hasta'))->startOfDay()
                : $referencia->copy();
        } catch (\Throwable $e) {
            $fechaHasta = $referencia->copy();
        }

        try {
            $fechaDesde = $request->filled('desde')
                ? Carbon::parse($request->input('desde'))->startOfDay()
                : $fechaHasta->copy()->subDays(29);
        } catch (\Throwable $e) {
            $fechaDesde = $fechaHasta->copy()->subDays(29);
        }

        if ($fechaDesde->gt($fechaHasta)) {
            $tmp = $fechaDesde->copy();
            $fechaDesde = $fechaHasta->copy();
            $fechaHasta = $tmp;
        }

        /*
         * Para evitar una vista inmanejable, el análisis interactivo se
         * limita a 180 días por consulta.
         */
        if ($fechaDesde->diffInDays($fechaHasta) > 179) {
            $fechaDesde = $fechaHasta->copy()->subDays(179);
        }

        $cierresPorOt = collect();

        if ($idsOt->isNotEmpty()) {
            $cierresPorOt = DB::table('ot_trazabilidad')
                ->whereIn('id_ot', $idsOt->all())
                ->whereRaw(
                    "UPPER(TRIM(proceso)) = ?",
                    [self::PROCESO_CIERRE]
                )
                ->select(
                    'id_ot',
                    DB::raw('MIN(fecha_proceso) as fecha_cierre')
                )
                ->groupBy('id_ot')
                ->get()
                ->keyBy('id_ot');
        }

        $trazasPeriodo = collect();

        if ($idsOt->isNotEmpty()) {
            $trazasPeriodo = DB::table('ot_trazabilidad')
                ->whereIn('id_ot', $idsOt->all())
                ->whereDate(
                    'fecha_proceso',
                    '>=',
                    $fechaDesde->format('Y-m-d')
                )
                ->whereDate(
                    'fecha_proceso',
                    '<=',
                    $fechaHasta->format('Y-m-d')
                )
                ->select(
                    'id_trazabilidad',
                    'id_ot',
                    'proceso',
                    'resultado',
                    'fecha_proceso'
                )
                ->orderBy('fecha_proceso')
                ->orderBy('id_trazabilidad')
                ->get();
        }

        /*
         * Colapsar por OT + día. Si la misma OT pasa por varios procesos
         * durante la jornada, mostramos el de mayor avance del flujo.
         */
        $movimientosPorDia = $trazasPeriodo
            ->groupBy(function ($traza) {
                return Carbon::parse(
                    $traza->fecha_proceso
                )->format('Y-m-d');
            })
            ->map(function ($trazasDia) use (
                $ordenProcesos,
                $otInfo,
                $cierresPorOt
            ) {
                return collect($trazasDia)
                    ->groupBy('id_ot')
                    ->map(function ($trazasOt, $idOt) use (
                        $ordenProcesos,
                        $otInfo,
                        $cierresPorOt
                    ) {
                        $info = $otInfo->get((int) $idOt);

                        if (!$info) {
                            return null;
                        }

                        $mejor = collect($trazasOt)
                            ->sort(function ($a, $b) use ($ordenProcesos) {
                                $pa = strtoupper(
                                    trim((string) $a->proceso)
                                );

                                $pb = strtoupper(
                                    trim((string) $b->proceso)
                                );

                                $oa = $ordenProcesos[$pa] ?? 0;
                                $ob = $ordenProcesos[$pb] ?? 0;

                                if ($oa !== $ob) {
                                    return $ob <=> $oa;
                                }

                                return ((int) $b->id_trazabilidad)
                                    <=> ((int) $a->id_trazabilidad);
                            })
                            ->first();

                        $fecha = Carbon::parse(
                            $mejor->fecha_proceso
                        )->format('Y-m-d');

                        $cierre = $cierresPorOt->get((int) $idOt);

                        $fechaCierre = $cierre && $cierre->fecha_cierre
                            ? Carbon::parse(
                                $cierre->fecha_cierre
                            )->format('Y-m-d')
                            : null;

                        return (object) [
                            'id_ot' => (int) $idOt,
                            'nro_ot' => $info->nro_ot,
                            'codigo' => $info->codigo,
                            'descripcion' => $info->descripcion,
                            'cantidad_orden' =>
                                (int) $info->cantidad_orden,
                            'pedidos' => $info->pedidos,
                            'proceso' => trim(
                                (string) $mejor->proceso
                            ),
                            'orden_proceso' =>
                                $ordenProcesos[
                                    strtoupper(
                                        trim((string) $mejor->proceso)
                                    )
                                ] ?? 0,
                            'resultado' =>
                                (int) ($mejor->resultado ?? 0),
                            'fecha' => $fecha,
                            'cerro_hoy' =>
                                $fechaCierre === $fecha,
                            'eventos' => collect($trazasOt)->count(),
                        ];
                    })
                    ->filter()
                    ->values();
            });

        $totalOt = $otInfo->count();

        $cierresAntes = $cierresPorOt
            ->filter(function ($cierre) use ($fechaDesde) {
                return $cierre->fecha_cierre
                    && Carbon::parse($cierre->fecha_cierre)
                        ->startOfDay()
                        ->lt($fechaDesde);
            })
            ->count();

        $acumulado = $cierresAntes;
        $dias = collect();

        $cursor = $fechaDesde->copy();

        while ($cursor->lte($fechaHasta)) {
            $clave = $cursor->format('Y-m-d');
            $movimientos = collect(
                $movimientosPorDia->get($clave, collect())
            );

            $cierresHoy = $cierresPorOt
                ->filter(function ($cierre) use ($clave) {
                    return $cierre->fecha_cierre
                        && Carbon::parse($cierre->fecha_cierre)
                            ->format('Y-m-d') === $clave;
                });

            $idsCierreHoy = $cierresHoy
                ->keys()
                ->map(function ($id) {
                    return (int) $id;
                })
                ->values();

            $acumulado += $cierresHoy->count();

            $porProceso = $movimientos
                ->groupBy(function ($item) {
                    $proceso = trim((string) $item->proceso);

                    return $proceso !== ''
                        ? $proceso
                        : 'SIN PROCESO';
                })
                ->map(function ($grupo, $proceso) {
                    return (object) [
                        'proceso' => $proceso,
                        'ots' => $grupo->count(),
                        'prendas' => (int) $grupo->sum(
                            'cantidad_orden'
                        ),
                        'orden' => (int) $grupo->max(
                            'orden_proceso'
                        ),
                    ];
                })
                ->sortByDesc('ots')
                ->values();

            $procesoPrincipal = $porProceso->first();

            $pedidosDia = $movimientos
                ->flatMap(function ($item) {
                    return $item->pedidos;
                })
                ->filter()
                ->unique()
                ->values();

            $prendasCierre = $idsCierreHoy
                ->sum(function ($idOt) use ($otInfo) {
                    $info = $otInfo->get($idOt);

                    return $info
                        ? (int) $info->cantidad_orden
                        : 0;
                });

            $dias->push((object) [
                'fecha' => $clave,
                'fecha_label' => $cursor->format('d/m/Y'),
                'dia_semana' => ucfirst(
                    $cursor->locale('es')->isoFormat('dddd')
                ),
                'ots_movimiento' => $movimientos->count(),
                'prendas_movimiento' =>
                    (int) $movimientos->sum('cantidad_orden'),
                'pedidos_movimiento' => $pedidosDia->count(),
                'eventos' =>
                    (int) $movimientos->sum('eventos'),
                'ots_cierre' => $cierresHoy->count(),
                'prendas_cierre' => $prendasCierre,
                'ots_acumuladas' => min(
                    $totalOt,
                    $acumulado
                ),
                'avance_acumulado' => $totalOt > 0
                    ? round(
                        (
                            min($totalOt, $acumulado)
                            / $totalOt
                        ) * 100,
                        1
                    )
                    : 0,
                'proceso_principal' =>
                    $procesoPrincipal->proceso ?? null,
                'por_proceso' => $porProceso,
                'movimientos' => $movimientos
                    ->sortByDesc('orden_proceso')
                    ->values(),
            ]);

            $cursor->addDay();
        }

        /*
         * Mostrar primero el día más reciente.
         */
        $dias = $dias
            ->sortByDesc('fecha')
            ->values();

        $diasConMovimiento = $dias
            ->where('ots_movimiento', '>', 0);

        $resumen = (object) [
            'dias_consultados' => $dias->count(),
            'dias_con_movimiento' => $diasConMovimiento->count(),
            'ots_con_movimiento' => $diasConMovimiento
                ->flatMap(function ($dia) {
                    return $dia->movimientos->pluck('id_ot');
                })
                ->unique()
                ->count(),
            'eventos' => (int) $dias->sum('eventos'),
            'ots_cerradas' => (int) $dias->sum('ots_cierre'),
            'prendas_cerradas' =>
                (int) $dias->sum('prendas_cierre'),
            'promedio_ot_dia' =>
                $diasConMovimiento->count() > 0
                    ? round(
                        $diasConMovimiento->avg('ots_movimiento'),
                        1
                    )
                    : 0,
            'total_ot_base' => $totalOt,
        ];

        return view(
            'seguimiento_pedidos_produccion.avance_diario',
            compact(
                'dias',
                'resumen',
                'pedidosDisponibles',
                'pedidoId',
                'fechaDesde',
                'fechaHasta'
            )
        );
    }

    public function informeGerencial(Request $request)
    {
        $hoy = Carbon::today();

        $pedidos = SeguimientoPedido::query()
            ->where('nro_pedido', 'ILIKE', 'P%')
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
                ->select(
                    'id_trazabilidad',
                    'id_ot',
                    'proceso',
                    'resultado',
                    'fecha_proceso'
                )
                ->orderBy('fecha_proceso')
                ->orderBy('id_trazabilidad')
                ->get()
                ->groupBy('id_ot');
        }

        $filas = $filas->map(function ($ot) use ($trazas, $hoy) {
            $historial = collect($trazas->get($ot->id_ot, collect()));

            $ingresoTerminacion = $historial
                ->filter(function ($t) {
                    return strtoupper(trim((string) $t->proceso)) === self::PROCESO_CIERRE;
                })
                ->sortBy('fecha_proceso')
                ->first();

            $ultimoProceso = $historial
                ->sortBy(function ($t) {
                    return sprintf(
                        '%s-%010d',
                        (string) $t->fecha_proceso,
                        (int) $t->id_trazabilidad
                    );
                })
                ->last();

            $completo = $ingresoTerminacion !== null;
            $fechaPedido = $ot->fecha_pedido
                ? Carbon::parse($ot->fecha_pedido)->startOfDay()
                : null;

            $dias = (!$completo && $fechaPedido)
                ? max(0, $fechaPedido->diffInDays($hoy, false))
                : 0;

            // Para Producción, una OT se considera atrasada/urgente
            // recién a partir de 30 días desde la fecha del pedido.
            $urgente = !$completo && $dias >= 30;

            $ot->completo = $completo;
            $ot->fecha_ingreso_terminacion = $ingresoTerminacion->fecha_proceso ?? null;
            $ot->proceso_actual = $ultimoProceso->proceso ?? 'SIN PROCESO';
            $ot->fecha_proceso_actual = $ultimoProceso->fecha_proceso ?? null;
            $ot->cantidad_proceso_actual = (int) ($ultimoProceso->resultado ?? 0);
            $ot->dias_pendiente = $dias;
            $ot->urgente = $urgente;

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

                // Dentro del mismo proceso, mostrar primero la OT con más días pendiente.
                return ((int) $b->dias_pendiente) <=> ((int) $a->dias_pendiente);
            })
            ->values();

        $completas = $filas->where('completo', true)->values();

        $totalOt = $filas->count();
        $totalCompletas = $completas->count();
        $totalPendientes = $pendientes->count();
        $avance = $totalOt > 0
            ? round(($totalCompletas / $totalOt) * 100, 1)
            : 0;

        $pedidosConPendiente = $pendientes
            ->pluck('seguimiento_pedido_id')
            ->unique()
            ->count();

        $pedidoCompletos = $pedidos->count() - $pedidosConPendiente;

        $diasPendientes = $pendientes->pluck('dias_pendiente')->filter(function ($d) {
            return $d !== null;
        });

        $resumen = (object) [
            'pedidos' => $pedidos->count(),
            'pedidos_completos' => max(0, $pedidoCompletos),
            'pedidos_con_pendiente' => $pedidosConPendiente,
            'ots' => $totalOt,
            'ots_completas' => $totalCompletas,
            'ots_pendientes' => $totalPendientes,
            'prendas_pendientes' => (int) $pendientes->sum('cantidad_orden'),
            'urgentes' => $pendientes->where('urgente', true)->count(),
            'avance' => $avance,
            'antiguedad_maxima' => $diasPendientes->isNotEmpty() ? (int) $diasPendientes->max() : 0,
            'promedio_pendiente' => $diasPendientes->isNotEmpty() ? round($diasPendientes->avg(), 1) : 0,
            'sin_proceso' => $pendientes->filter(function ($fila) {
                return strtoupper(trim((string) $fila->proceso_actual)) === 'SIN PROCESO';
            })->count(),
        ];

        /*
         * Resumen por proceso actual para lectura gerencial.
         * Se agrupan únicamente las OT todavía pendientes de llegar a
         * TERMINACION - INGRESO TERMINACION.
         */
        $porProcesos = $pendientes
            ->groupBy(function ($fila) {
                $proceso = trim((string) $fila->proceso_actual);
                return $proceso !== '' ? $proceso : 'SIN PROCESO';
            })
            ->map(function ($grupo, $proceso) use ($totalPendientes) {
                $otsProceso = $grupo->count();
                $prendasProceso = (int) $grupo->sum('cantidad_orden');

                return (object) [
                    'proceso' => $proceso,
                    'ots' => $otsProceso,
                    'prendas' => $prendasProceso,
                    'pedidos' => $grupo->pluck('seguimiento_pedido_id')->unique()->count(),
                    'porcentaje' => $totalPendientes > 0
                        ? round(($otsProceso / $totalPendientes) * 100, 1)
                        : 0,
                ];
            })
            ->sortByDesc('ots')
            ->values();

        return view('seguimiento_pedidos_produccion.informe_gerencial', compact(
            'pendientes',
            'resumen',
            'porProcesos'
        ));
    }


    public function show($id)
    {
        $pedido = SeguimientoPedido::query()
            ->where('nro_pedido', 'ILIKE', 'P%')
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

            $ot->cantidad_ingreso = (int) ($ingreso->cantidad ?? 0);
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
            'cantidad' => $cantidad,
            'cantidad_ingreso' => $cantidadIngreso,
            'porcentaje' => $totalOt > 0 ? min(100, (int) round(($completas / $totalOt) * 100)) : 0,
            'completo' => $totalOt > 0 && $completas >= $totalOt,
            'ultima_fecha' => $ultimaFecha,
            'dias' => ($fechaPedido && $ultimaFecha)
                ? $fechaPedido->diffInDays(Carbon::parse($ultimaFecha)->startOfDay(), false)
                : null,
            'dias_en_curso' => ($fechaPedido && $completas < $totalOt)
                ? max(0, $fechaPedido->diffInDays(Carbon::today(), false))
                : null,
        ];

        return view('seguimiento_pedidos_produccion.show', compact(
            'pedido',
            'ots',
            'resumen'
        ));
    }

    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        $import = new SeguimientoPedidoProduccionImport();
        Excel::import($import, $request->file('archivo'));

        $mensaje = 'Importación P finalizada. Filas: ' . $import->procesadas
            . ' | OT vinculadas: ' . $import->vinculadas
            . ' | OT no encontradas: ' . count($import->noEncontradas)
            . ' | Omitidas: ' . count($import->omitidas) . '.';

        if (!empty($import->noEncontradas)) {
            $mensaje .= ' No encontradas: '
                . implode(', ', array_slice(array_unique($import->noEncontradas), 0, 20));
        }

        return back()->with('success', $mensaje);
    }
}
