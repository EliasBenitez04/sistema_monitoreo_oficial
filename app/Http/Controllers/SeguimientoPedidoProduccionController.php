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

        $todosPedidos = (clone $query)->get();
        $pedidos = $query->paginate(30)->appends($request->query());

        $normalizarPedido = function ($pedido) {
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
        };

        $todosPedidos = $todosPedidos->map($normalizarPedido);
        $pedidos->getCollection()->transform($normalizarPedido);

        $resumen = (object) [
            'pedidos' => $todosPedidos->count(),
            'ots' => (int) $todosPedidos->sum('ots_total'),
            'ots_completas' => (int) $todosPedidos->sum('ots_completas'),
            'completos' => $todosPedidos->where('completo', true)->count(),
        ];

        /*
         * Panel de decisiones: corte basado en la última trazabilidad importada.
         * La actividad mide cualquier movimiento de la OT, mientras que el
         * avance mide exclusivamente el hito de Ingreso Terminación.
         */
        $ultimaCarga = DB::table('ot_trazabilidad')->max('fecha_proceso');
        $corte = $ultimaCarga
            ? Carbon::parse($ultimaCarga)->startOfDay()
            : Carbon::today();

        $idsPedidos = $todosPedidos->pluck('id')->all();
        $detalles = empty($idsPedidos) ? collect() : DB::table('seguimiento_pedido_detalle as d')
            ->join('ot as o', 'o.id_ot', '=', 'd.id_ot')
            ->whereIn('d.seguimiento_pedido_id', $idsPedidos)
            ->select('d.seguimiento_pedido_id', 'd.id_ot', 'o.cantidad_orden')
            ->distinct()
            ->get();
        $idsOt = $detalles->pluck('id_ot')->unique()->all();

        $movimientos = empty($idsOt) ? collect() : DB::table('ot_trazabilidad')
            ->whereIn('id_ot', $idsOt)
            ->where('fecha_proceso', '<', $corte->copy()->addDay()->format('Y-m-d'))
            ->select('id_ot', 'fecha_proceso', 'proceso', 'resultado')
            ->orderBy('fecha_proceso')
            ->get()
            ->groupBy('id_ot');

        $detallesPorPedido = $detalles->groupBy('seguimiento_pedido_id');
        $serieAvance = collect();
        $fechasSerie = collect();
        for ($i = 14; $i >= 0; $i--) {
            $fechasSerie->push($corte->copy()->subDays($i)->format('Y-m-d'));
        }
        $fechasHito = collect();
        $cantidadesHito = collect();

        foreach ($detalles as $detalle) {
            $trazas = $movimientos->get($detalle->id_ot, collect());
            $hitos = $trazas->filter(function ($tr) {
                return strtoupper(trim((string) $tr->proceso)) === self::PROCESO_CIERRE;
            });
            $primero = $hitos->first();
            $fechasHito->put($detalle->seguimiento_pedido_id . ':' . $detalle->id_ot, $primero ? date('Y-m-d', strtotime($primero->fecha_proceso)) : null);
            $cantidadesHito->put($detalle->seguimiento_pedido_id . ':' . $detalle->id_ot, min((int) $detalle->cantidad_orden, max(0, (int) $hitos->sum('resultado'))));
        }

        foreach ($fechasSerie as $fechaSerie) {
            $completasAlDia = $fechasHito->filter(function ($fecha) use ($fechaSerie) {
                return $fecha !== null && $fecha <= $fechaSerie;
            })->count();
            $serieAvance->push((object) [
                'fecha' => date('d/m', strtotime($fechaSerie)),
                'porcentaje' => $detalles->count() > 0
                    ? round($completasAlDia * 100 / $detalles->count(), 1)
                    : 0,
                'ot_completas' => $completasAlDia,
            ]);
        }

        $pedidosDecision = $todosPedidos->map(function ($pedido) use ($detallesPorPedido, $movimientos, $fechasHito, $cantidadesHito, $corte) {
            $filas = $detallesPorPedido->get($pedido->id, collect());
            $ultimas = $filas->flatMap(function ($fila) use ($movimientos) {
                return $movimientos->get($fila->id_ot, collect())->pluck('fecha_proceso');
            });
            $ultimaActividad = $ultimas->max();
            $diasSinMovimiento = $ultimaActividad
                ? Carbon::parse($ultimaActividad)->startOfDay()->diffInDays($corte)
                : null;
            $inicio = $pedido->fecha_pedido ? Carbon::parse($pedido->fecha_pedido)->startOfDay() : null;
            $antiguedad = $inicio ? max(0, $inicio->diffInDays($corte, false)) : null;
            $prendasIngresadas = (int) $filas->sum(function ($fila) use ($cantidadesHito, $pedido) {
                return $cantidadesHito->get($pedido->id . ':' . $fila->id_ot, 0);
            });
            $pedido->prendas_ingresadas_control = $prendasIngresadas;
            $pedido->porcentaje_prendas = $pedido->cantidad_total > 0
                ? round($prendasIngresadas * 100 / $pedido->cantidad_total, 1)
                : 0;
            $pedido->ultima_actividad = $ultimaActividad;
            $pedido->dias_sin_movimiento = $diasSinMovimiento;
            $pedido->antiguedad = $antiguedad;
            $pedido->pendiente_prendas = max(0, $pedido->cantidad_total - $prendasIngresadas);
            $pedido->prioridad = $pedido->completo
                ? 'COMPLETO'
                : ($diasSinMovimiento === null || $diasSinMovimiento >= 7 || ($antiguedad !== null && $antiguedad >= 30)
                    ? 'URGENTE'
                    : ($diasSinMovimiento >= 3 ? 'REVISAR' : 'AVANZANDO'));
            return $pedido;
        });

        $rankingPrioridad = $pedidosDecision->where('completo', false)
            ->sort(function ($a, $b) {
                $peso = ['URGENTE' => 3, 'REVISAR' => 2, 'AVANZANDO' => 1];
                $resultado = ($peso[$b->prioridad] ?? 0) <=> ($peso[$a->prioridad] ?? 0);
                return $resultado !== 0 ? $resultado : ($b->pendiente_prendas <=> $a->pendiente_prendas);
            })->take(15)->values();

        $resumenDecision = (object) [
            'fecha_corte' => $corte,
            'urgentes' => $pedidosDecision->where('prioridad', 'URGENTE')->count(),
            'revisar' => $pedidosDecision->where('prioridad', 'REVISAR')->count(),
            'avanzando' => $pedidosDecision->where('prioridad', 'AVANZANDO')->count(),
            'prendas_pendientes' => (int) $pedidosDecision->sum('pendiente_prendas'),
            'prendas_ingresadas' => (int) $pedidosDecision->sum('prendas_ingresadas_control'),
            'prendas_pedidas' => (int) $pedidosDecision->sum('cantidad_total'),
            'variacion_7_dias' => $serieAvance->isNotEmpty()
                ? round($serieAvance->last()->porcentaje - $serieAvance->get(7)->porcentaje, 1)
                : 0,
        ];

        // Los indicadores históricos son globales, sin limitarse a la página 1.
        $resumenDecision->porcentaje_prendas = $resumenDecision->prendas_pedidas > 0
            ? round(100 * $resumenDecision->prendas_ingresadas / $resumenDecision->prendas_pedidas, 1)
            : 0;

        return view('seguimiento_pedidos_produccion.index', compact(
            'pedidos',
            'buscar',
            'resumen',
            'resumenDecision',
            'serieAvance',
            'rankingPrioridad'
        ));
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
