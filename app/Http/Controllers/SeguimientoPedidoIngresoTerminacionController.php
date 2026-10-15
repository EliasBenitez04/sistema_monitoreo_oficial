<?php

namespace App\Http\Controllers;

use App\Models\SeguimientoPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        // Misma fecha de corte del informe gerencial y del análisis IT diario.
        $fechaCorte = Carbon::today()->startOfDay();
        $fechaCorteTexto = $fechaCorte->format('Y-m-d');

        $query = SeguimientoPedido::query()
            ->where('nro_pedido', 'ILIKE', self::PREFIJO . '%')
            ->whereNotNull('fecha_pedido')
            ->whereDate('fecha_pedido', '<=', $fechaCorteTexto)
            ->orderByDesc('id');

        if ($buscar !== '') {
            $query->where('nro_pedido', 'ILIKE', '%' . $buscar . '%');
        }

        // La tabla continúa paginada, pero los KPI se calculan con TODOS
        // los pedidos filtrados, no sólo los de la página visible.
        $pedidos = (clone $query)->paginate(30)->appends($request->query());
        $todosLosPedidos = (clone $query)->get(['id', 'nro_pedido', 'fecha_pedido']);

        $vinculos = collect();
        if ($todosLosPedidos->isNotEmpty()) {
            $vinculos = DB::table('seguimiento_pedido_detalle as spd')
                ->join('seguimiento_pedido as sp', 'sp.id', '=', 'spd.seguimiento_pedido_id')
                ->join('ot as o', 'o.id_ot', '=', 'spd.id_ot')
                ->whereIn('spd.seguimiento_pedido_id', $todosLosPedidos->pluck('id')->all())
                // Igual al informe gerencial y al calendario: sólo vínculos vigentes.
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('pedido_ot_cambios as c')
                        ->whereColumn('c.pedido_id', 'sp.id')
                        ->whereRaw('c.ot_anterior = CAST(o.nro_ot AS TEXT)')
                        ->whereIn('c.accion', ['ANULAR', 'REEMPLAZAR']);
                })
                ->select(
                    'spd.seguimiento_pedido_id',
                    'o.id_ot',
                    'o.cantidad_orden'
                )
                ->get();
        }

        // El mismo número de OT sólo se contabiliza una vez en el KPI
        // global, aunque esté vinculado a varios pedidos.
        $otsUnicas = $vinculos->unique('id_ot')->values();
        $idsOt = $otsUnicas->pluck('id_ot')->all();

        $hitosPorOt = collect();
        if (!empty($idsOt)) {
            $hitosPorOt = DB::table('ot_trazabilidad as tr')
                ->whereIn('tr.id_ot', $idsOt)
                ->whereRaw('UPPER(TRIM(tr.proceso)) = ?', [self::PROCESO_CIERRE])
                ->whereDate('tr.fecha_proceso', '<=', $fechaCorteTexto)
                ->select(
                    'tr.id_ot',
                    DB::raw('SUM(COALESCE(tr.resultado, 0)) as cantidad_ingresada'),
                    DB::raw('MIN(tr.fecha_proceso) as primera_fecha'),
                    DB::raw('MAX(tr.fecha_proceso) as ultima_fecha')
                )
                ->groupBy('tr.id_ot')
                ->get()
                ->keyBy('id_ot');
        }

        $vinculosPorPedido = $vinculos->groupBy('seguimiento_pedido_id');

        $pedidos->getCollection()->transform(function ($pedido) use (
            $vinculosPorPedido,
            $hitosPorOt,
            $fechaCorte
        ) {
            $otsPedido = collect($vinculosPorPedido->get($pedido->id, collect()))
                ->unique('id_ot')->values();

            $pedido->ots_total = $otsPedido->count();
            $pedido->ots_completas = $otsPedido
                ->filter(function ($ot) use ($hitosPorOt) {
                    return $hitosPorOt->has($ot->id_ot);
                })->count();
            $pedido->cantidad_total = (int) $otsPedido->sum('cantidad_orden');
            $pedido->cantidad_ingreso = (int) $otsPedido->sum(
                function ($ot) use ($hitosPorOt) {
                    $hito = $hitosPorOt->get($ot->id_ot);
                    return $hito ? min(
                        max(0, (int) $ot->cantidad_orden),
                        max(0, (int) $hito->cantidad_ingresada)
                    ) : 0;
                }
            );

            $pedido->porcentaje = $pedido->ots_total > 0
                ? min(100, (int) round(
                    $pedido->ots_completas * 100 / $pedido->ots_total
                ))
                : 0;
            $pedido->completo = $pedido->ots_total > 0
                && $pedido->ots_completas >= $pedido->ots_total;

            $primerIngreso = $otsPedido->map(function ($ot) use ($hitosPorOt) {
                return $hitosPorOt->get($ot->id_ot)->primera_fecha ?? null;
            })->filter()->min();
            $ultimoIngreso = $otsPedido->map(function ($ot) use ($hitosPorOt) {
                return $hitosPorOt->get($ot->id_ot)->ultima_fecha ?? null;
            })->filter()->max();

            $pedido->primer_ingreso = $primerIngreso;
            $pedido->ultimo_ingreso = $ultimoIngreso;
            $pedido->dias = null;
            $pedido->dias_en_curso = null;

            if ($pedido->fecha_pedido) {
                $inicio = Carbon::parse($pedido->fecha_pedido)->startOfDay();
                if ($pedido->completo && $ultimoIngreso) {
                    $fin = Carbon::parse($ultimoIngreso)->startOfDay();
                    if ($fin->gte($inicio)) {
                        $pedido->dias = $inicio->diffInDays($fin, false);
                    }
                } elseif (!$pedido->completo) {
                    $pedido->dias_en_curso = max(
                        0, $inicio->diffInDays($fechaCorte, false)
                    );
                }
            }

            return $pedido;
        });

        $otAtendidas = $otsUnicas->filter(function ($ot) use ($hitosPorOt) {
            return $hitosPorOt->has($ot->id_ot);
        })->count();
        $prendasIngresadas = (int) $otsUnicas->sum(function ($ot) use ($hitosPorOt) {
            $hito = $hitosPorOt->get($ot->id_ot);
            return $hito ? min(
                max(0, (int) $ot->cantidad_orden),
                max(0, (int) $hito->cantidad_ingresada)
            ) : 0;
        });

        // Compleción global por pedido independiente de la paginación.
        $pedidosCompletos = $todosLosPedidos->filter(
            function ($pedido) use ($vinculosPorPedido, $hitosPorOt) {
                $otsPedido = collect($vinculosPorPedido->get($pedido->id, collect()))
                    ->unique('id_ot');
                return $otsPedido->isNotEmpty()
                    && $otsPedido->every(function ($ot) use ($hitosPorOt) {
                        return $hitosPorOt->has($ot->id_ot);
                    });
            }
        )->count();

        $resumen = (object) [
            'fecha_corte' => $fechaCorte,
            'pedidos' => $todosLosPedidos->count(),
            'ots' => $otsUnicas->count(),
            'ots_completas' => $otAtendidas,
            'completos' => $pedidosCompletos,
            'prendas' => (int) $otsUnicas->sum('cantidad_orden'),
            'prendas_ingresadas' => $prendasIngresadas,
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

        /*
         * Dashboard mensual.
         *
         * Se toma exactamente la misma fuente que el control diario:
         * TERMINACION - TERMINACION. Cada combinación OT/día se cruza contra
         * los pedidos IT que ya existían en la fecha de esa salida.
         */
        $mesInput = trim((string) $request->input('mes', ''));

        try {
            $mesControl = $mesInput !== ''
                ? Carbon::createFromFormat('Y-m', $mesInput)->startOfMonth()
                : $fechaControl->copy()->startOfMonth();
        } catch (\Exception $e) {
            $mesControl = $fechaControl->copy()->startOfMonth();
        }

        $inicioMes = $mesControl->copy()->startOfMonth();
        $finMes = $mesControl->copy()->endOfMonth();

        $salidasMes = DB::table('ot_trazabilidad as tr')
            ->join('ot as o', 'o.id_ot', '=', 'tr.id_ot')
            ->whereRaw(
                "UPPER(TRIM(tr.proceso)) = ?",
                [self::PROCESO_CIERRE]
            )
            ->whereBetween(
                'tr.fecha_proceso',
                [
                    $inicioMes->copy()->startOfDay()->format('Y-m-d H:i:s'),
                    $finMes->copy()->endOfDay()->format('Y-m-d H:i:s'),
                ]
            )
            ->select(
                DB::raw('DATE(tr.fecha_proceso) as fecha_salida'),
                'o.id_ot',
                'o.nro_ot',
                'o.codigo',
                'o.descripcion',
                'o.cantidad_orden',
                DB::raw(
                    'SUM(COALESCE(tr.resultado, 0)) as resultado_dia'
                )
            )
            ->groupBy(
                DB::raw('DATE(tr.fecha_proceso)'),
                'o.id_ot',
                'o.nro_ot',
                'o.codigo',
                'o.descripcion',
                'o.cantidad_orden'
            )
            ->orderBy(
                DB::raw('DATE(tr.fecha_proceso)')
            )
            ->orderBy('o.nro_ot')
            ->get();

        $idsSalidaMes = $salidasMes
            ->pluck('id_ot')
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values();

        $pedidosMesPorOt = collect();

        if ($idsSalidaMes->isNotEmpty()) {
            $pedidosMesPorOt = DB::table(
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
                    $idsSalidaMes->all()
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

        $salidasMes = $salidasMes
            ->map(function ($salida) use ($pedidosMesPorOt) {
                $fechaSalida = date(
                    'Y-m-d',
                    strtotime($salida->fecha_salida)
                );

                $pedidosVigentes = collect(
                    $pedidosMesPorOt->get(
                        (int) $salida->id_ot,
                        collect()
                    )
                )
                    ->filter(function ($item) use ($fechaSalida) {
                        if (!$item->fecha_pedido) {
                            return false;
                        }

                        return date(
                            'Y-m-d',
                            strtotime($item->fecha_pedido)
                        ) <= $fechaSalida;
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

                $salida->fecha_salida = $fechaSalida;
                $salida->pedidos_vigentes = $pedidosVigentes;
                $salida->tiene_pedido = $pedidosVigentes->isNotEmpty();

                return $salida;
            })
            ->values();

        $salidasMesPorFecha = $salidasMes->groupBy('fecha_salida');

        $resumenMensual = (object) [
            'dias_con_salida' => $salidasMesPorFecha->count(),
            'registros_ot_dia' => $salidasMes->count(),
            'ots_unicas' => $salidasMes
                ->pluck('id_ot')
                ->unique()
                ->count(),
            'salidas_con_pedido' => $salidasMes
                ->where('tiene_pedido', true)
                ->count(),
            'salidas_sin_pedido' => $salidasMes
                ->where('tiene_pedido', false)
                ->count(),
            'prendas_salida' => (int) $salidasMes->sum('resultado_dia'),
            'prendas_con_pedido' => (int) $salidasMes
                ->where('tiene_pedido', true)
                ->sum('resultado_dia'),
            'prendas_sin_pedido' => (int) $salidasMes
                ->where('tiene_pedido', false)
                ->sum('resultado_dia'),
            'porcentaje_justificado' => $salidasMes->count() > 0
                ? round(
                    (
                        $salidasMes
                            ->where('tiene_pedido', true)
                            ->count()
                        / $salidasMes->count()
                    ) * 100,
                    1
                )
                : 0,
        ];

        // Efectividad del volumen: prendas con pedido IT / prendas procesadas.
        $resumenMensual->efectividad_prendas = $resumenMensual->prendas_salida > 0
            ? round(100 * $resumenMensual->prendas_con_pedido / $resumenMensual->prendas_salida, 1)
            : 0;

        /*
         * Proyección de prendas T-T para el mes seleccionado.
         * Promedio por días hábiles (lunes-viernes) transcurridos hasta
         * la última fecha importada del mes; días sin salida cuentan como 0.
         * Los movimientos del fin de semana sí se incluyen en el volumen
         * registrado, pero los días futuros proyectados son solo L-V.
         */
        $ultimaSalidaMes = $salidasMes->max('fecha_salida');
        $fechaCorteMes = $ultimaSalidaMes
            ? Carbon::parse($ultimaSalidaMes)->startOfDay()
            : null;
        $esMesActual = $mesControl->format('Y-m') === Carbon::today()->format('Y-m');
        $diasHabilesTranscurridos = 0;
        $diasHabilesRestantes = 0;

        if ($fechaCorteMes) {
            $cursorHabil = $inicioMes->copy()->startOfDay();
            while ($cursorHabil->lte($fechaCorteMes)) {
                if ($cursorHabil->isWeekday()) {
                    $diasHabilesTranscurridos++;
                }
                $cursorHabil->addDay();
            }

            $cursorHabil = $fechaCorteMes->copy()->addDay();
            while ($cursorHabil->lte($finMes)) {
                if ($cursorHabil->isWeekday()) {
                    $diasHabilesRestantes++;
                }
                $cursorHabil->addDay();
            }
        }

        $promedioHabil = $diasHabilesTranscurridos > 0
            ? $resumenMensual->prendas_salida / $diasHabilesTranscurridos
            : 0;

        $proyeccionDiasHabiles = (object) [
            'disponible' => $esMesActual && $fechaCorteMes !== null
                && $diasHabilesTranscurridos > 0,
            'fecha_corte' => $fechaCorteMes,
            'cantidad_real' => $resumenMensual->prendas_salida,
            'dias_transcurridos' => $diasHabilesTranscurridos,
            'dias_restantes' => $diasHabilesRestantes,
            'promedio_diario' => round($promedioHabil, 1),
            'cantidad_restante' => (int) round($promedioHabil * $diasHabilesRestantes),
            'total_proyectado' => (int) round(
                $resumenMensual->prendas_salida + $promedioHabil * $diasHabilesRestantes
            ),
        ];

        /*
         * Oportunidad de atención de pedidos IT.
         * Corte para mes actual = última fecha importada T-T; para meses
         * cerrados = último día del mes. No se incluyen pedidos futuros.
         * Una OT en varios pedidos cuenta una sola vez.
         * Una salida previa al pedido no se trata como OT pendiente.
         */
        // Los pedidos IT se evalúan hasta hoy si el mes seleccionado es el actual.
// El corte de movimientos T-T sigue independiente para el calendario y la proyección.
        $hoyPedidos = Carbon::today()->startOfDay();
        $cortePendientes = $mesControl->format('Y-m') === $hoyPedidos->format('Y-m')
            ? $hoyPedidos
            : ($inicioMes->gt($hoyPedidos) ? null : $finMes->copy()->startOfDay());
        $otsPendientesIt = collect();
        $analisisPendientes = (object) [
            'disponible' => $cortePendientes !== null,
            'fecha_corte' => $cortePendientes,
            'solicitadas' => 0,
            'atendidas' => 0,
            'pendientes' => 0,
            'sin_pedido_mes' => (int) $resumenMensual->salidas_sin_pedido,
            'oportunidad' => 0,
            'porcentaje_atencion' => 0,
        ];

        if ($cortePendientes) {
            $fechaLimite = $cortePendientes->format('Y-m-d');
            $otPedidos = DB::table('seguimiento_pedido_detalle as d')
                ->join('seguimiento_pedido as p', 'p.id', '=', 'd.seguimiento_pedido_id')
                ->join('ot as o', 'o.id_ot', '=', 'd.id_ot')
                ->where('p.nro_pedido', 'ILIKE', self::PREFIJO . '%')
                ->whereNotNull('p.fecha_pedido')
                ->whereDate('p.fecha_pedido', '<=', $fechaLimite)
                // Mostrar únicamente vínculos activos: una importación anterior
                // pudo haber recreado una OT anulada o reemplazada.
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('pedido_ot_cambios as c')
                        ->whereColumn('c.pedido_id', 'p.id')
                        ->whereRaw('c.ot_anterior = CAST(o.nro_ot AS TEXT)')
                        ->whereIn('c.accion', ['ANULAR', 'REEMPLAZAR']);
                })
                ->select('o.id_ot', 'o.nro_ot', 'o.descripcion', 'o.cantidad_orden',
                    'p.nro_pedido', 'p.fecha_pedido')
                ->get()->groupBy('id_ot');

            $idsPedidosActivos = $otPedidos->keys()->all();
            $primerHito = empty($idsPedidosActivos) ? collect()
                : DB::table('ot_trazabilidad')
                    ->whereIn('id_ot', $idsPedidosActivos)
                    ->whereRaw("UPPER(TRIM(proceso)) = ?", [self::PROCESO_CIERRE])
                    ->whereDate('fecha_proceso', '<=', $fechaLimite)
                    ->select('id_ot', DB::raw('MIN(fecha_proceso) as primera_fecha'))
                    ->groupBy('id_ot')
                    ->pluck('primera_fecha', 'id_ot');

            $otsPendientesIt = $otPedidos->map(function ($pedidosOt, $idOt) use ($primerHito) {
                $primeraFecha = $primerHito->get($idOt);
                $base = $pedidosOt->first();
                return (object) [
                    'nro_ot' => $base->nro_ot,
                    'descripcion' => $base->descripcion,
                    'cantidad_orden' => (int) $base->cantidad_orden,
                    'pedidos' => $pedidosOt->pluck('nro_pedido')->unique()->implode(', '),
                    'atendida' => $primeraFecha !== null,
                ];
            })->values();

            $otsPendientes = $otsPendientesIt->where('atendida', false)->values();
            $analisisPendientes->solicitadas = $otsPendientesIt->count();
            $analisisPendientes->atendidas = $otsPendientesIt->where('atendida', true)->count();
            $analisisPendientes->pendientes = $otsPendientes->count();
            $analisisPendientes->oportunidad = min(
                $analisisPendientes->pendientes,
                $analisisPendientes->sin_pedido_mes
            );
            $analisisPendientes->porcentaje_atencion = $analisisPendientes->solicitadas > 0
                ? round($analisisPendientes->atendidas * 100 / $analisisPendientes->solicitadas, 1)
                : 0;
            $otsPendientesIt = $otsPendientes->sortBy('nro_ot')->values();
        }

        $inicioCalendario = $inicioMes
            ->copy()
            ->startOfWeek(Carbon::MONDAY);

        $finCalendario = $finMes
            ->copy()
            ->endOfWeek(Carbon::SUNDAY);

        $semanasCalendario = collect();
        $semanaActual = collect();
        $cursor = $inicioCalendario->copy();

        while ($cursor->lte($finCalendario)) {
            $fechaDia = $cursor->format('Y-m-d');
            $itemsDia = collect(
                $salidasMesPorFecha->get(
                    $fechaDia,
                    collect()
                )
            );

            $conPedidoDia = $itemsDia
                ->where('tiene_pedido', true)
                ->count();

            $sinPedidoDia = $itemsDia
                ->where('tiene_pedido', false)
                ->count();

            $pedidosDia = $itemsDia
                ->flatMap(function ($item) {
                    return $item->pedidos_vigentes
                        ->pluck('nro')
                        ->all();
                })
                ->unique()
                ->sort()
                ->values();

            $semanaActual->push((object) [
                'fecha' => $fechaDia,
                'numero' => (int) $cursor->format('j'),
                'es_mes' => $cursor->month === $mesControl->month,
                'es_hoy' => $fechaDia === Carbon::today()->format('Y-m-d'),
                'seleccionado' => $fechaDia === $fechaControlTexto,
                'ot_total' => $itemsDia->count(),
                'con_pedido' => $conPedidoDia,
                'sin_pedido' => $sinPedidoDia,
                'prendas_salida' => (int) $itemsDia->sum('resultado_dia'),
                'pedidos' => $pedidosDia,
            ]);

            if ($semanaActual->count() === 7) {
                $semanasCalendario->push($semanaActual);
                $semanaActual = collect();
            }

            $cursor->addDay();
        }

        // Consulta por OT independiente de la fecha y mes del calendario.
        $numeroOtBuscado = trim((string) $request->query('ot', ''));
        $resultadoOt = null;
        $errorOt = null;
        if ($numeroOtBuscado !== '') {
            if (!ctype_digit($numeroOtBuscado) || strlen($numeroOtBuscado) > 12) {
                $errorOt = 'Ingresá un número de OT válido (solo números).';
            } else {
                $resultadoOt = $this->buscarOtConMovimientos((int) $numeroOtBuscado);
                if (!$resultadoOt) {
                    $errorOt = 'No se encontró la OT ' . $numeroOtBuscado . '.';
                }
            }
        }

        return view(
            'seguimiento_pedidos_ingreso_terminacion.seguimiento_diario',
            compact(
                'numeroOtBuscado', 'resultadoOt', 'errorOt',
                'fechaControl',
                'salidas',
                'resumen',
                'mesControl',
                'resumenMensual',
                'proyeccionDiasHabiles',
                'analisisPendientes',
                'otsPendientesIt',
                'semanasCalendario',
                'salidasMesPorFecha'
            )
        );
    }

    /**
     * Busca el recorrido histórico completo de una OT por número.
     * No cambia las reglas ni los KPI del seguimiento diario/mensual.
     */
    private function buscarOtConMovimientos(int $numeroOt): ?object
    {
        $ot = DB::table('ot')
            ->where('nro_ot', $numeroOt)
            ->first(['id_ot','nro_ot','codigo','descripcion','cantidad_orden','estado']);

        if (!$ot) {
            return null;
        }

        $idOt = (int) $ot->id_ot;
        $trazabilidad = DB::table('ot_trazabilidad')
            ->where('id_ot', $idOt)
            ->orderBy('fecha_proceso')
            ->orderBy('id_trazabilidad')
            ->get(['id_trazabilidad','proceso','resultado','fecha_proceso']);

        $ultimoProceso = $trazabilidad->last();
        $primeraTerminacion = $trazabilidad->first(function ($item) {
            return strtoupper(trim((string) $item->proceso)) === self::PROCESO_CIERRE;
        });

        $pedidos = DB::table('seguimiento_pedido_detalle as d')
            ->join('seguimiento_pedido as p', 'p.id', '=', 'd.seguimiento_pedido_id')
            ->where('d.id_ot', $idOt)
            ->where('p.nro_pedido', 'ILIKE', self::PREFIJO . '%')
            ->select('p.id','p.nro_pedido','p.fecha_pedido')
            ->distinct()
            ->orderBy('p.fecha_pedido')
            ->orderBy('p.id')
            ->get();

        $idsAnulados = collect();
        if ($pedidos->isNotEmpty() && Schema::hasTable('pedido_ot_cambios')) {
            $idsAnulados = DB::table('pedido_ot_cambios')
                ->whereIn('pedido_id', $pedidos->pluck('id')->all())
                ->where('ot_anterior', (string) $ot->nro_ot)
                ->whereIn('accion', ['ANULAR','REEMPLAZAR'])
                ->pluck('pedido_id')->map(function ($id) { return (int) $id; });
        }
        foreach ($pedidos as $pedido) {
            $pedido->vigente = !$idsAnulados->contains((int) $pedido->id);
        }

        $plan = Schema::hasTable('ot_logistica_detalle')
            ? DB::table('ot_logistica_detalle as d')
                ->leftJoin('ot_trazabilidad as t',
                    't.id_trazabilidad', '=', 'd.id_trazabilidad')
                ->where('d.id_ot', $idOt)
                ->orderBy('t.fecha_proceso')
                ->orderBy('d.id')
                ->get(['d.id','d.sucursal','d.cantidad','t.fecha_proceso'])
            : collect();

        // Solamente movimientos asociados a la OT de forma segura.
        // Remisiones sin id_ot no se atribuyen a una OT arbitraria.
        $remisiones = Schema::hasTable('ot_logistica_remisiones')
            ? DB::table('ot_logistica_remisiones')
                ->where('id_ot', $idOt)
                ->orderByRaw('COALESCE(fecha_remision, fecha_creacion) ASC')
                ->orderBy('serie')
                ->orderBy('numero_remision')
                ->orderBy('id')
                ->get(['id','id_ot','serie','numero_remision','codigo',
                    'cantidad','fecha_remision','fecha_creacion','fecha_recepcion',
                    'cod_sucursal_salida','sucursal_salida','cod_sucursal_destino',
                    'sucursal_destino'])
            : collect();

        // Destinos documentados por despachos desde Casa Central.
        // No es stock físico del local: puede haber ventas y redistribuciones.
        $destinosRemitidos = $remisiones
            ->filter(function ($r) {
                $origen = strtoupper(trim((string) $r->sucursal_salida));
                return (int) $r->cod_sucursal_destino !== 1
                    && ((int) $r->cod_sucursal_salida === 1
                        || in_array($origen, ['CASA CENTRAL', 'MATRIZ'], true));
            })
            ->groupBy('cod_sucursal_destino')
            ->map(function ($lineas) {
                $primera = $lineas->first();
                $emitidas = (int) $lineas->sum('cantidad');
                $recibidas = (int) $lineas
                    ->filter(function ($r) { return !empty($r->fecha_recepcion); })
                    ->sum('cantidad');
                return (object) [
                    'sucursal' => $primera->sucursal_destino
                        ?: 'Sucursal ' . $primera->cod_sucursal_destino,
                    'emitidas' => $emitidas,
                    'recibidas' => $recibidas,
                    'en_transito' => max(0, $emitidas - $recibidas)
                ];
            })->sortBy('sucursal')->values();

        $conciliacion = app(\App\Services\LogisticaConciliacionService::class)
            ->conciliarPorIds([$idOt])->get($idOt);

        $movimientos = collect();
        foreach ($trazabilidad as $traza) {
            $movimientos->push((object) [
                'fecha' => $traza->fecha_proceso,
                'tipo' => 'PROCESO',
                'detalle' => $traza->proceso,
                'informacion' => 'Registro de trazabilidad',
                'cantidad' => (int) $traza->resultado,
                'documento' => null,
                'estado' => null
            ]);
        }
        foreach ($plan as $asignacion) {
            $movimientos->push((object) [
                'fecha' => $asignacion->fecha_proceso,
                'tipo' => 'PLAN LOGÍSTICO',
                'detalle' => 'Asignación a ' . $asignacion->sucursal,
                'informacion' => 'Plan por sucursal; no es salida física',
                'cantidad' => (int) $asignacion->cantidad,
                'documento' => null,
                'estado' => null
            ]);
        }
        foreach ($remisiones as $remision) {
            $origen = $remision->sucursal_salida ?: 'Suc. ' . $remision->cod_sucursal_salida;
            $destino = $remision->sucursal_destino ?: 'Suc. ' . $remision->cod_sucursal_destino;
            $retorno = (int) $remision->cod_sucursal_destino === 1
                && (int) $remision->cod_sucursal_salida !== 1;
            $central = (int) $remision->cod_sucursal_salida === 1
                && (int) $remision->cod_sucursal_destino !== 1;
            $tipo = $retorno ? 'DEVOLUCIÓN' : ($central ? 'REMISIÓN CENTRAL' : 'TRASLADO');
            $documento = $remision->serie . '-' . $remision->numero_remision;
            $info = $origen . ' → ' . $destino . ' · Código ' . $remision->codigo;

            $movimientos->push((object) [
                'fecha' => $remision->fecha_remision ?: $remision->fecha_creacion,
                'tipo' => $tipo,
                'detalle' => $origen . ' → ' . $destino,
                'informacion' => $info,
                'cantidad' => (int) $remision->cantidad,
                'documento' => $documento,
                'estado' => $remision->fecha_recepcion ? 'RECIBIDO' : 'EN TRÁNSITO'
            ]);
            if ($remision->fecha_recepcion) {
                $movimientos->push((object) [
                    'fecha' => $remision->fecha_recepcion,
                    'tipo' => 'RECEPCIÓN',
                    'detalle' => 'Recepción en ' . $destino,
                    'informacion' => $info,
                    'cantidad' => (int) $remision->cantidad,
                    'documento' => $documento,
                    'estado' => 'RECIBIDO'
                ]);
            }
        }

        $movimientos = $movimientos->sortBy(function ($item) {
            return (string) $item->fecha . '|'
                . ($item->tipo === 'RECEPCIÓN' ? '2' : '1');
        })->values();

        return (object) [
            'ot' => $ot,
            'pedidos' => $pedidos,
            'trazabilidad' => $trazabilidad,
            'plan' => $plan,
            'remisiones' => $remisiones,
            'destinos_remitidos' => $destinosRemitidos,
            'movimientos' => $movimientos,
            'ultimo_proceso' => $ultimoProceso,
            'primera_terminacion' => $primeraTerminacion,
            'ultimo_movimiento' => $movimientos->last(),
            'ultima_remision' => $remisiones->last(),
            'conciliacion' => $conciliacion,
            'sin_vinculo_recuperado' => 0
        ];
    }

    public function informeGerencial(Request $request)
    {
        // Mismo universo que el análisis de atención del calendario IT:
        // pedidos solicitados al corte, vínculos activos y OT únicas.
        $hoy = Carbon::today()->startOfDay();
        $fechaCorte = $hoy->format('Y-m-d');

        $pedidos = SeguimientoPedido::query()
            ->where('nro_pedido', 'ILIKE', self::PREFIJO . '%')
            ->whereNotNull('fecha_pedido')
            ->whereDate('fecha_pedido', '<=', $fechaCorte)
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
                // Una OT anulada o reemplazada ya no constituye carga activa.
                // Exactamente la misma regla de seguimientoDiario().
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('pedido_ot_cambios as c')
                        ->whereColumn('c.pedido_id', 'sp.id')
                        ->whereRaw('c.ot_anterior = CAST(o.nro_ot AS TEXT)')
                        ->whereIn('c.accion', ['ANULAR', 'REEMPLAZAR']);
                })
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
                ->orderBy('sp.fecha_pedido')
                ->orderBy('sp.id')
                ->get()
                // La misma OT puede aparecer en más de un pedido IT.
                // El informe muestra todos sus pedidos, pero cuenta la OT una sola vez.
                ->groupBy('id_ot')
                ->map(function ($vinculos) {
                    $ot = clone $vinculos->first();
                    $ot->nro_pedido = $vinculos
                        ->pluck('nro_pedido')->unique()->implode(', ');
                    $ot->pedido_ids = $vinculos
                        ->pluck('seguimiento_pedido_id')->unique()->values();
                    return $ot;
                })
                ->values();
        }

        $idsOt = $filas->pluck('id_ot')->unique()->values()->all();
        $trazas = collect();

        if (!empty($idsOt)) {
            $trazas = DB::table('ot_trazabilidad')
                ->whereIn('id_ot', $idsOt)
                // No anticipar trazas posteriores al corte del informe.
                ->whereDate('fecha_proceso', '<=', $fechaCorte)
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
            ->flatMap(function ($ot) {
                return $ot->pedido_ids->all();
            })
            ->unique()
            ->count();

        $resumen = (object) [
            'fecha_corte' => $hoy,
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
                    'pedidos' => $grupo->flatMap(function ($ot) {
                        return $ot->pedido_ids->all();
                    })->unique()->count(),
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
