<?php

namespace App\Http\Controllers;

use App\Imports\SeguimientoPedidoProduccionImport;
use App\Models\SeguimientoPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Support\PromedioDiarioProduccion;
use App\Support\UltimoProcesoOt;
use App\Support\AntiguedadOt;

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

    public function dashboardMensual(Request $request)
    {
        $ultimoDato = DB::table('ot_trazabilidad')
            ->whereRaw("UPPER(TRIM(proceso)) = ?", [self::PROCESO_CIERRE])
            ->max('fecha_proceso');
        $corte = $ultimoDato ? Carbon::parse($ultimoDato)->startOfDay() : Carbon::today();
        try {
            $mes = $request->filled('mes')
                ? Carbon::createFromFormat('Y-m', $request->input('mes'))->startOfMonth()
                : $corte->copy()->startOfMonth();
        } catch (\Exception $e) {
            $mes = $corte->copy()->startOfMonth();
        }
        $inicio = $mes->copy()->startOfMonth();
        $fin = $mes->copy()->endOfMonth();
        $esActual = $mes->format('Y-m') === $corte->format('Y-m');
        $corteMes = $mes->lt($corte->copy()->startOfMonth())
            ? $fin->copy()->startOfDay()
            : ($esActual ? $corte->copy() : null);

        // Se toma TODA la salida registrada del hito, incluso OT sin pedido P.
        $salidas = DB::table('ot_trazabilidad as t')
            ->join('ot as o', 'o.id_ot', '=', 't.id_ot')
            ->whereRaw("UPPER(TRIM(t.proceso)) = ?", [self::PROCESO_CIERRE])
            ->whereBetween('t.fecha_proceso', [
                $inicio->format('Y-m-d 00:00:00'),
                $fin->format('Y-m-d 23:59:59'),
            ])
            ->select('o.id_ot', 'o.nro_ot', 'o.descripcion', 'o.cantidad_orden',
                DB::raw('DATE(t.fecha_proceso) as fecha'),
                DB::raw('SUM(COALESCE(t.resultado,0)) as prendas'))
            ->groupBy('o.id_ot', 'o.nro_ot', 'o.descripcion', 'o.cantidad_orden', DB::raw('DATE(t.fecha_proceso)'))
            ->orderBy('fecha')->get();

        $idsSalida = $salidas->pluck('id_ot')->unique()->all();
        $pedidosSalida = empty($idsSalida) ? collect()
            : DB::table('seguimiento_pedido_detalle as d')
                ->join('seguimiento_pedido as p', 'p.id', '=', 'd.seguimiento_pedido_id')
                ->whereIn('d.id_ot', $idsSalida)
                ->where('p.nro_pedido', 'ILIKE', 'P%')
                ->select('d.id_ot', 'p.id as pedido_id', 'p.nro_pedido', 'p.fecha_pedido')
                ->get()->groupBy('id_ot');

        // La asociación vigente con P cuenta aunque la OT haya llegado
        // al hito antes de la fecha del pedido. Se muestra esa diferencia.
        $salidas = $salidas->map(function ($fila) use ($pedidosSalida) {
            $pedidos = collect($pedidosSalida->get($fila->id_ot, collect()));
            $fila->pedidos = $pedidos->pluck('nro_pedido')->unique()->implode(', ');
            $fila->justificada = $pedidos->isNotEmpty();
            $fila->pedido_posterior = $pedidos->isNotEmpty()
                && !$pedidos->contains(function ($p) use ($fila) {
                    return !$p->fecha_pedido
                        || substr((string) $p->fecha_pedido, 0, 10) <= $fila->fecha;
                });
            return $fila;
        });

        $dias = collect();
        $cursor = $inicio->copy();
        while ($cursor->lte($fin)) {
            $fecha = $cursor->format('Y-m-d');
            $filas = $salidas->where('fecha', $fecha);
            $dias->push((object) [
                'fecha' => $fecha, 'numero' => $cursor->day,
                'habil' => $cursor->isWeekday(),
                'total' => $filas->count(),
                'con' => $filas->where('justificada', true)->count(),
                'sin' => $filas->where('justificada', false)->count(),
                'prendas' => (int) $filas->sum('prendas'),
            ]);
            $cursor->addDay();
        }
        $total = (int) $salidas->sum('prendas');
        $con = (int) $salidas->where('justificada', true)->sum('prendas');
        $sin = (int) $salidas->where('justificada', false)->sum('prendas');
        // Prendas y OT distintas por asociación P del movimiento.
        // Si una OT figura varios días, no inflar el número de OT.
        $salidasConPedido = $salidas->where('justificada', true);
        $salidasSinPedido = $salidas->where('justificada', false);
        $otsConPedido = $salidasConPedido->pluck('id_ot')->unique()->count();
        $otsSinPedido = $salidasSinPedido->pluck('id_ot')->unique()->count();

        /*
         * Proyección día a día:
         * (prendas acumuladas hasta la fecha / días hábiles transcurridos)
         * * días hábiles totales del mes seleccionado.
         *
         * Los sábados y domingos no aumentan el divisor; los movimientos
         * que eventualmente ocurran esos días sí se suman a la producción.
         * Los días posteriores al corte no se consideran días en cero.
         */
        $diasHabilesMes = $dias->where('habil', true)->count();
        $promediosPorFecha = PromedioDiarioProduccion::calcular(
            $dias->map(function ($dia) {
                return [
                    'fecha' => $dia->fecha,
                    'prendas' => $dia->prendas,
                    'habil' => $dia->habil,
                ];
            })->all(),
            $corteMes ? $corteMes->format('Y-m-d') : null,
            $diasHabilesMes
        );

        $dias = $dias->map(function ($dia) use ($promediosPorFecha) {
            $medidas = $promediosPorFecha[$dia->fecha];
            foreach ($medidas as $campo => $valor) {
                $dia->{$campo} = $valor;
            }
            return $dia;
        });
        $diasProcesados = $dias->where('registrado', true);
        $ultimoDia = $diasProcesados->last();
        $escalaProyecciones = max(1, (float) $diasProcesados->max('proyeccion'));

        /*
         * Son dos cortes diferentes y no deben confundirse:
         *
         * - $corteMes: último día procesado en el módulo del hito IT.
         *   Solo se usa para estadísticas, proyecciones y calendario
         *   de producción (no inventar días sin salidas).
         *
         * - $cortePendientes: foto documental de los pendientes P.
         *   Para el mes vigente es AHORA, aunque el último hito
         *   se haya importado hace varios días. Para meses anteriores
         *   es el cierre de aquel mes y para meses futuros no hay corte.
         */
        $ahoraPendientes = Carbon::now();
        $mesVigente = $mes->format('Y-m') === $ahoraPendientes->format('Y-m');
        $cortePendientes = AntiguedadOt::cortePendientesDelMes($mes, $ahoraPendientes);

        // Pedidos P activos y trazabilidad de su etapa actual.
        $vinculos = DB::table('seguimiento_pedido_detalle as d')
            ->join('seguimiento_pedido as p', 'p.id', '=', 'd.seguimiento_pedido_id')
            ->join('ot as o', 'o.id_ot', '=', 'd.id_ot')
            ->where('p.nro_pedido', 'ILIKE', 'P%')
            ->when($cortePendientes, function ($q) use ($cortePendientes) {
                $q->whereDate('p.fecha_pedido', '<=', $cortePendientes->format('Y-m-d'));
            })
            ->select('p.id as pedido_id', 'p.nro_pedido', 'p.fecha_pedido',
                'o.id_ot', 'o.nro_ot', 'o.descripcion', 'o.cantidad_orden')->get();
        if (!$cortePendientes) {
            $vinculos = collect();
        }
        // Aplicar anulaciones únicamente si ya existían en la fecha de corte.
        $cambiosAnulados = $cortePendientes
            ? DB::table('pedido_ot_cambios')
                ->whereIn('accion', ['ANULAR', 'REEMPLAZAR'])
                ->where('created_at', '<=', $cortePendientes->format('Y-m-d H:i:s'))
                ->select('pedido_id', 'ot_anterior')
                ->get()
                ->mapWithKeys(function ($c) {
                    return [$c->pedido_id . ':' . $c->ot_anterior => true];
                })
            : collect();
        $vinculos = $vinculos->reject(function ($v) use ($cambiosAnulados) {
            return $cambiosAnulados->has($v->pedido_id . ':' . $v->nro_ot);
        })->values();
        // Conciliar trazabilidad por número de OT: el mismo identificador que
        // se muestra en pendientes. Incluir el día completo del corte.
        $numerosOt = $vinculos->pluck('nro_ot')->filter()->unique()->values()->all();
        $movimientos = empty($numerosOt) ? collect() : DB::table('ot_trazabilidad as t')
            ->join('ot as o', 'o.id_ot', '=', 't.id_ot')
            ->whereIn('o.nro_ot', $numerosOt)
            ->where('t.fecha_proceso', '<=', $cortePendientes->format('Y-m-d H:i:s'))
            ->select('o.nro_ot', 't.proceso', 't.resultado', 't.fecha_proceso', 't.id_trazabilidad')
            ->orderBy('t.fecha_proceso')->orderBy('t.id_trazabilidad')
            ->get()->groupBy('nro_ot');

        // Corte histórico: una anulación posterior no elimina una OT del
        // análisis de una fecha anterior. La asociación P del pedido sí
        // debe estar solicitada hasta la fecha de corte.
        $pendientes = $vinculos->groupBy('nro_ot')->map(function ($registros, $nroOt) use ($movimientos, $cortePendientes) {
            $historial = collect($movimientos->get($nroOt, collect()));
            $base = $registros->first();
            $cantidadOrden = max(0, (int) $base->cantidad_orden);

            $movimientosHito = $historial->filter(function ($tr) {
                return strtoupper(trim((string) $tr->proceso)) === self::PROCESO_CIERRE;
            });
            // Una OT se considera atendida en cuanto llega al hito.
            // No comparar el resultado del hito contra la cantidad original:
            // las cantidades pueden variar entre procesos.
            if ($movimientosHito->isNotEmpty()) {
                return null;
            }

            // El último movimiento del día se determina por el orden del
            // flujo, no por id/importación ni por hora cuando son etapas
            // distintas. Ej.: SERIGRAFIA (55) → COSTURA INTERNA (70).
            $ultimo = UltimoProcesoOt::ultimo($historial);
            // Etapa vigente y última ACTIVIDAD no tienen por qué ser la
            // misma fila: con igual día se elige la etapa más avanzada,
            // pero la antigüedad usa el timestamp más reciente de todos
            // los registros reales de la OT (no la fila elegida por rango).
            $fechaUltimaActividad = $historial->pluck('fecha_proceso')->filter()->max();

            // Para OT todavía pendientes, tomar el resultado del proceso
            // vigente seleccionado por fecha y jerarquía de producción.
            $cantidadIngresada = 0;
            $cantidadPendiente = $ultimo && $ultimo->resultado !== null
                ? max(0, (int) $ultimo->resultado)
                : $cantidadOrden;
            // Los días SIN movimiento miden desde el movimiento real,
            // con hora y minuto. La espera P nunca empieza antes de
            // pedir las prendas y puede tener otra fecha de inicio.
            $fechaPedido = $registros
                ->pluck('fecha_pedido')
                ->filter()
                ->map(function ($fecha) { return Carbon::parse($fecha)->startOfDay(); })
                ->sort()
                ->first();
            $antiguedad = AntiguedadOt::calcular(
                $fechaUltimaActividad,
                $fechaPedido,
                $cortePendientes
            );
            $diasQuieto = $antiguedad['dias_espera_pedido'];
            $diasSinMovimiento = $antiguedad['dias_sin_movimiento'];
            $etapa = $ultimo ? trim((string) $ultimo->proceso) : 'SIN MOVIMIENTO REGISTRADO';

            return (object) [
                'nro_ot' => $base->nro_ot, 'descripcion' => $base->descripcion,
                'cantidad' => $cantidadOrden,
                'cantidad_ingresada' => $cantidadIngresada,
                'cantidad_pendiente' => $cantidadPendiente,
                'pedidos' => $registros->pluck('nro_pedido')->unique()->implode(', '),
                'etapa' => $etapa,
                'ultima_fecha' => $fechaUltimaActividad,
                'fecha_etapa_actual' => $ultimo ? $ultimo->fecha_proceso : null,
                'dias_quieto' => $diasQuieto,
                'dias_sin_movimiento' => $diasSinMovimiento,
                'prioridad' => $diasQuieto === null ? 'SIN DATOS'
                    : ($diasQuieto >= 15 ? 'URGENTE' : ($diasQuieto >= 8 ? 'REVISAR' : 'NORMAL')),
            ];
        })->filter()->values();

        $porEtapa = $pendientes->groupBy('etapa')->map(function ($items, $etapa) {
            return (object) ['etapa' => $etapa, 'ots' => $items->count(), 'prendas' => (int) $items->sum('cantidad_pendiente')];
        })->sortByDesc('ots')->values();

        $habilesTranscurridos = $ultimoDia->dias_transcurridos ?? 0;
        $habilesRestantes = max(0, $diasHabilesMes - $habilesTranscurridos);
        $promedio = $ultimoDia->promedio ?? null;
        $proyeccion = $ultimoDia->proyeccion ?? null;

        $indicadores = (object) [
            'fecha_corte' => $corteMes, // Corte de producción por último hito
            'fecha_corte_pendientes' => $cortePendientes,
            'pendientes_en_vivo' => $mesVigente && $cortePendientes !== null,
            'ot_dia' => $salidas->count(),
            'prendas' => $total, 'con' => $con, 'sin' => $sin,
            'ots_con_pedido' => $otsConPedido,
            'ots_sin_pedido' => $otsSinPedido,
            'registros_con_pedido' => $salidasConPedido->count(),
            'registros_sin_pedido' => $salidasSinPedido->count(),
            'efectividad' => $total > 0 ? round($con * 100 / $total, 1) : 0,
            'pendientes' => $pendientes->count(),
            'prendas_pendientes' => (int) $pendientes->sum('cantidad_pendiente'),
            'urgentes' => $pendientes->where('prioridad', 'URGENTE')->count(),
            'promedio' => $promedio,
            'promedio_ot' => $habilesTranscurridos > 0
                ? round($salidas->count() / $habilesTranscurridos, 1) : null,
            'promedio_ayer' => $ultimoDia->promedio_anterior ?? null,
            'variacion_promedio' => $ultimoDia->variacion ?? null,
            'variacion_promedio_pct' => $ultimoDia->variacion_porcentaje ?? null,
            'proyeccion_ayer' => $ultimoDia->proyeccion_anterior ?? null,
            'variacion_proyeccion' => $ultimoDia->variacion_proyeccion ?? null,
            'variacion_proyeccion_pct' => $ultimoDia->variacion_proyeccion_porcentaje ?? null,
            'dias_transcurridos' => $habilesTranscurridos,
            'dias_restantes' => $habilesRestantes,
            'dias_habiles_mes' => $diasHabilesMes,
            'proyeccion_adicional' => $proyeccion !== null
                ? $proyeccion - $total : null,
            'proyeccion_cierre' => $proyeccion,
        ];
        return view('seguimiento_pedidos_produccion.dashboard_mensual', compact(
            'mes', 'dias', 'salidas', 'pendientes', 'porEtapa', 'indicadores',
            'escalaProyecciones'
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

            $ultimoProceso = UltimoProcesoOt::ultimo($historial);

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
