<?php

namespace App\Http\Controllers;

use App\Imports\ControlTerminacionRemisionImport;
use App\Services\LogisticaConciliacionService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class ControlTerminacionController extends Controller
{
    private LogisticaConciliacionService $conciliacionService;

    public function __construct(
        LogisticaConciliacionService $conciliacionService
    ) {
        $this->conciliacionService = $conciliacionService;
    }

    /**
     * Alias interno temporal para no romper métodos existentes.
     * La lógica real vive en LogisticaConciliacionService.
     */
    private function resumenDistribucionComoDashboardOt(
        array $idsOt
    ): \Illuminate\Support\Collection {
        return $this->conciliacionService->conciliarPorIds($idsOt);
    }

    public function index(Request $request)
    {
        $fechaDesde = $request->input('fecha_desde', now()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));
        $buscar = trim((string) $request->input('buscar', ''));
        $estados = $request->input('estado', []);

        if (!is_array($estados)) {
            $estados = [$estados];
        }

        $procesoEntradaTerminacion = 'TERMINACION - TERMINACION';
        $procesoProductoTerminado = 'TERMINACION - PRODUCTO TERMINADO';
        $hoy = now()->startOfDay();

        /*
         * REGLA DEL FLUJO
         *
         * TERMINACION - INGRESO TERMINACION:
         *     etapa previa del flujo.
         *
         * TERMINACION - TERMINACION:
         *     ingreso físico real a Terminación y referencia para comparar
         *     contra Producto Terminado.
         *
         * TERMINACION - PRODUCTO TERMINADO:
         *     salida de Terminación y entrega/entrada a Logística.
         *
         * Por lo tanto Producto Terminado NO se compara contra
         * LOGISTICA - LOGISTICA Y DISTRIBUCION para decidir si fue entregado.
         * Ese último proceso pertenece al Dashboard Logística.
         */
        $queryProduccion = DB::table('ot_trazabilidad as pt')
            ->join('ot as o', 'o.id_ot', '=', 'pt.id_ot')
            ->where('pt.proceso', $procesoProductoTerminado)
            ->whereBetween('pt.fecha_proceso', [$fechaDesde, $fechaHasta]);

        if ($buscar !== '') {
            $queryProduccion->where(function ($q) use ($buscar) {
                if (is_numeric($buscar)) {
                    $q->where('o.nro_ot', (int) $buscar)
                        ->orWhere('o.codigo', 'ILIKE', '%' . $buscar . '%')
                        ->orWhere('o.descripcion', 'ILIKE', '%' . $buscar . '%');
                } else {
                    $q->where('o.codigo', 'ILIKE', '%' . $buscar . '%')
                        ->orWhere('o.descripcion', 'ILIKE', '%' . $buscar . '%');
                }
            });
        }

        $produccionTerminada = $queryProduccion
            ->groupBy(
                'o.id_ot',
                'o.nro_ot',
                'o.codigo',
                'o.descripcion',
                'o.cantidad_orden',
                'o.estado'
            )
            ->select(
                'o.id_ot',
                'o.nro_ot',
                'o.codigo',
                'o.descripcion',
                'o.cantidad_orden',
                'o.estado',
                DB::raw('SUM(pt.resultado) as cantidad_terminada'),
                DB::raw('MIN(pt.fecha_proceso) as fecha_producto_terminado'),
                DB::raw('MAX(pt.fecha_proceso) as ultima_fecha_producto_terminado')
            )
            ->get();

        $idsOt = $produccionTerminada
            ->pluck('id_ot')
            ->filter()
            ->unique()
            ->values();

        /*
         * VALORIZACIÓN ECONÓMICA
         *
         * La fuente monetaria es ot_logistica_remisiones porque allí se
         * importan costo_unitario y precio_venta por código/variante.
         * Se consideran únicamente remisiones originales que salen de
         * Casa Central / Matriz hacia un destino real, exactamente igual
         * que en la conciliación física del Control de Terminación.
         */
        $valorizacionPorOt = collect();

        if ($idsOt->isNotEmpty()
            && Schema::hasTable('ot_logistica_remisiones')) {
            $valorizacionPorOt = DB::table('ot_logistica_remisiones')
                ->whereIn('id_ot', $idsOt->all())
                ->where(function ($q) {
                    $q->where('cod_sucursal_salida', 1)
                        ->orWhereRaw(
                            "UPPER(TRIM(COALESCE(sucursal_salida, ''))) = 'CASA CENTRAL'"
                        )
                        ->orWhereRaw(
                            "UPPER(TRIM(COALESCE(sucursal_salida, ''))) = 'MATRIZ'"
                        );
                })
                ->where(function ($q) {
                    $q->whereNull('cod_sucursal_destino')
                        ->orWhere('cod_sucursal_destino', '<>', 1);
                })
                ->whereRaw(
                    "UPPER(COALESCE(NULLIF(TRIM(sucursal_destino), ''), NULLIF(TRIM(sucursal_logistica), ''), '')) NOT IN ('', 'CASA CENTRAL', 'MATRIZ')"
                )
                ->groupBy('id_ot')
                ->select(
                    'id_ot',
                    DB::raw(
                        'SUM(COALESCE(cantidad, 0) * COALESCE(costo_unitario, 0)) as costo_remitido'
                    ),
                    DB::raw(
                        'SUM(COALESCE(cantidad, 0) * COALESCE(precio_venta, 0)) as venta_remitida'
                    ),
                    DB::raw(
                        'SUM(CASE WHEN fecha_recepcion IS NOT NULL THEN COALESCE(cantidad, 0) * COALESCE(costo_unitario, 0) ELSE 0 END) as costo_recibido'
                    ),
                    DB::raw(
                        'SUM(CASE WHEN fecha_recepcion IS NOT NULL THEN COALESCE(cantidad, 0) * COALESCE(precio_venta, 0) ELSE 0 END) as venta_recibida'
                    )
                )
                ->get()
                ->keyBy('id_ot');
        }

        /*
         * Una sola fuente de cálculo para Producción + Plan + ENVIOS.
         * El rango selecciona las OTs por fecha de PT; la conciliación toma
         * el estado completo acumulado de cada OT.
         */
        $resumenDashboardPorOt = $this->resumenDistribucionComoDashboardOt(
            $idsOt->all()
        );

        foreach ($produccionTerminada as $item) {
            $resumen = $resumenDashboardPorOt->get($item->id_ot);

            if (!$resumen) {
                continue;
            }

            /*
             * Todas las cantidades operativas provienen ahora del servicio
             * central de conciliación. El rango solo selecciona las OTs por PT;
             * una vez seleccionada, se muestra su estado completo acumulado.
             */
            $item->cantidad_ingreso_terminacion =
                (int) $resumen->ingreso_terminacion;
            $item->cantidad_terminada =
                (int) $resumen->producto_terminado;
            $item->cantidad_entregada_logistica =
                $item->cantidad_terminada;

            $item->producto_terminado_referencia =
                (int) $resumen->producto_terminado;

            $item->cantidad_logistica =
                (int) $resumen->planificado;

            $item->cantidad_destino_detalle =
                (int) $resumen->plan_detallado;

            $item->cantidad_destino_asignado =
                (int) $resumen->asignado_efectivo;

            $item->cantidad_destinos =
                (int) $resumen->destinos;

            $item->falta_terminacion =
                (int) $resumen->falta_terminacion;

            $item->sin_destino =
                (int) $resumen->sin_destino;

            $item->sin_destino_plan =
                (int) $resumen->sin_destino_plan;

            $item->pendiente_remitir_plan =
                (int) $resumen->pendiente_remitir;

            $item->pendiente_real_salida =
                (int) $resumen->pendiente_real_salida;

            $item->en_transito =
                (int) $resumen->en_transito;

            $item->pendiente_completar_destino =
                $item->sin_destino;

            $item->hueco_detalle_logistico =
                (int) $resumen->hueco_plan_vs_real;

            $item->diferencia_fuente_destino =
                $item->hueco_detalle_logistico;

            $item->detalle_destino_incompleto =
                $item->hueco_detalle_logistico > 0;

            $item->movimiento_fisico =
                (int) $resumen->remitido_original;

            $item->movimiento_recibido =
                (int) $resumen->recibido_original;

            $item->remitido_efectivo =
                $item->movimiento_fisico;

            $item->recibido_efectivo =
                $item->movimiento_recibido;

            $valorizacion = $valorizacionPorOt->get(
                $item->id_ot
            );

            $item->costo_remitido = $valorizacion
                ? (float) $valorizacion->costo_remitido
                : 0.0;

            $item->venta_remitida = $valorizacion
                ? (float) $valorizacion->venta_remitida
                : 0.0;

            $item->costo_recibido = $valorizacion
                ? (float) $valorizacion->costo_recibido
                : 0.0;

            $item->venta_recibida = $valorizacion
                ? (float) $valorizacion->venta_recibida
                : 0.0;

            $item->margen_bruto_remitido =
                $item->venta_remitida
                - $item->costo_remitido;

            $item->movimientos_adicionales = max(
                0,
                (int) $resumen->remitido_original_raw
                    - (int) $resumen->producto_terminado
            );

            $item->primera_fecha_ingreso =
                $resumen->primera_fecha_ingreso;
            $item->ultima_fecha_ingreso =
                $resumen->ultima_fecha_ingreso;

            $item->primera_fecha_logistica =
                $resumen->primera_fecha_plan;
            $item->ultima_fecha_logistica =
                $resumen->ultima_fecha_plan;

            $item->primera_remision =
                $resumen->primera_remision;
            $item->ultima_remision =
                $resumen->ultima_remision;
            $item->ultima_recepcion =
                $resumen->ultima_recepcion;

            $item->estado_conciliacion =
                $resumen->estado_conciliacion;

            switch ($item->estado_conciliacion) {
                case 'FALTA TERMINACION':
                    $item->etapa_actual = 'TERMINACION';
                    $item->etapa_numero = 1;
                    break;

                case 'SIN DESTINO':
                    $item->etapa_actual = 'SIN DESTINO';
                    $item->etapa_numero = 2;
                    break;

                case 'PENDIENTE REMITIR':
                case 'PENDIENTE SALIDA':
                    $item->etapa_actual = 'LOGISTICA';
                    $item->etapa_numero = 3;
                    break;

                case 'EN TRANSITO':
                    $item->etapa_actual = 'REMISION';
                    $item->etapa_numero = 4;
                    break;

                case 'CONFIRMADO':
                    $item->etapa_actual = 'RECEPCION LOCAL';
                    $item->etapa_numero = 5;
                    break;

                default:
                    $item->etapa_actual = 'PRODUCTO TERMINADO';
                    $item->etapa_numero = 2;
                    break;
            }

            $item->porcentaje_flujo = (int) round(
                ($item->etapa_numero / 5) * 100
            );

            /*
             * Estado propio de Terminación: mantiene los filtros actuales,
             * pero usa objetivo/PT reconciliados.
             */
            $item->pendiente_terminar =
                (int) $resumen->falta_terminacion;

            $item->exceso_producto_terminado =
                (int) $resumen->exceso_producto_terminado;

            $item->dias_en_terminacion = null;

            if ($item->primera_fecha_ingreso) {
                $fechaIngreso = \Carbon\Carbon::parse(
                    $item->primera_fecha_ingreso
                )->startOfDay();

                if ($item->pendiente_terminar > 0) {
                    $item->dias_en_terminacion = max(
                        0,
                        $fechaIngreso->diffInDays($hoy, false)
                    );
                } elseif ($resumen->ultima_fecha_pt) {
                    $fechaSalida = \Carbon\Carbon::parse(
                        $resumen->ultima_fecha_pt
                    )->startOfDay();

                    $item->dias_en_terminacion = max(
                        0,
                        $fechaIngreso->diffInDays(
                            $fechaSalida,
                            false
                        )
                    );
                }
            }

            if ($item->cantidad_ingreso_terminacion <= 0) {
                $item->estado_control = 'SIN INGRESO';
            } elseif ($item->falta_terminacion > 0) {
                $item->estado_control = 'PARCIAL';
            } elseif ($item->exceso_producto_terminado > 0) {
                $item->estado_control = 'EXCEDENTE';
            } else {
                $item->estado_control = 'COMPLETO';
            }
        }

        if (!empty($estados)) {
            $produccionTerminada = $produccionTerminada
                ->filter(function ($item) use ($estados) {
                    return in_array($item->estado_control, $estados, true);
                })
                ->values();
        }

        $prioridadEstado = [
            'PARCIAL' => 1,
            'SIN INGRESO' => 2,
            'EXCEDENTE' => 3,
            'COMPLETO' => 4,
        ];

        $produccionTerminada = $produccionTerminada
            ->sort(function ($a, $b) use ($prioridadEstado) {
                $pa = $prioridadEstado[$a->estado_control] ?? 99;
                $pb = $prioridadEstado[$b->estado_control] ?? 99;

                if ($pa !== $pb) {
                    return $pa <=> $pb;
                }

                $diasA = $a->dias_en_terminacion ?? -1;
                $diasB = $b->dias_en_terminacion ?? -1;

                if ($a->pendiente_terminar > 0 || $b->pendiente_terminar > 0) {
                    if ($diasA !== $diasB) {
                        return $diasB <=> $diasA;
                    }
                }

                return ((int) $b->nro_ot) <=> ((int) $a->nro_ot);
            })
            ->values();

        $totalIngresoTerminacion = (int) $produccionTerminada
            ->sum('cantidad_ingreso_terminacion');

        $totalTerminado = (int) $produccionTerminada
            ->sum('cantidad_terminada');

        /*
         * KPI conciliados. Cada diferencia tiene una causa y un responsable.
         */
        $totalFaltaTerminacion = (int) $produccionTerminada
            ->sum('falta_terminacion');

        $otsFaltaTerminacion = $produccionTerminada
            ->filter(function ($item) {
                return (int) $item->falta_terminacion > 0;
            })
            ->count();

        $totalSinDestino = (int) $produccionTerminada
            ->sum('sin_destino');

        $otsSinDestino = $produccionTerminada
            ->filter(function ($item) {
                return (int) $item->sin_destino > 0;
            })
            ->count();

        $totalPendienteRemitirPlan = (int) $produccionTerminada
            ->sum('pendiente_remitir_plan');

        $otsPendienteRemitirPlan = $produccionTerminada
            ->filter(function ($item) {
                return (int) $item->pendiente_remitir_plan > 0;
            })
            ->count();

        $totalRemitidoReal = (int) $produccionTerminada
            ->sum('remitido_efectivo');

        $totalEnTransito = (int) $produccionTerminada
            ->sum('en_transito');

        $otsEnTransito = $produccionTerminada
            ->filter(function ($item) {
                return (int) $item->en_transito > 0;
            })
            ->count();

        $totalRecepcionLocal = (int) $produccionTerminada
            ->sum('recibido_efectivo');

        $totalCostoRemitido = (float) $produccionTerminada
            ->sum('costo_remitido');

        $totalVentaRemitida = (float) $produccionTerminada
            ->sum('venta_remitida');

        $totalCostoRecibido = (float) $produccionTerminada
            ->sum('costo_recibido');

        $totalVentaRecibida = (float) $produccionTerminada
            ->sum('venta_recibida');

        $totalMargenBrutoRemitido =
            $totalVentaRemitida - $totalCostoRemitido;

        $porcentajeMargenBrutoRemitido =
            $totalVentaRemitida > 0
                ? round(
                    ($totalMargenBrutoRemitido
                        / $totalVentaRemitida) * 100,
                    1
                )
                : 0;

        /*
         * Diagnóstico de calidad: remisiones reales que superan lo explicado
         * por el plan. No son faltantes físicos.
         */
        $totalHuecoDetalleLogistico = (int) $produccionTerminada
            ->sum('hueco_detalle_logistico');

        $otsHuecoDetalleLogistico = $produccionTerminada
            ->filter(function ($item) {
                return (int) $item->hueco_detalle_logistico > 0;
            })
            ->count();

        /*
         * Alias temporal para el reporte existente. Ahora representa únicamente
         * PT realmente sin destino, no prendas pendientes de remisión.
         */
        $totalPendienteEnvio = $totalSinDestino;
        $otsPendientesEnvio = $otsSinDestino;

        // Producto Terminado ya representa entrada física a Logística.
        $totalEntregadoLogistica = $totalTerminado;

        $totalPendienteTerminar = $totalFaltaTerminacion;

        $totalExcesoProductoTerminado = (int) $produccionTerminada
            ->sum('exceso_producto_terminado');

        $totalOTs = $produccionTerminada->count();

        $otsParciales = $produccionTerminada
            ->where('estado_control', 'PARCIAL')
            ->count();

        $otsCompletas = $produccionTerminada
            ->where('estado_control', 'COMPLETO')
            ->count();

        $otsSinIngreso = $produccionTerminada
            ->where('estado_control', 'SIN INGRESO')
            ->count();

        $otsExcedidas = $produccionTerminada
            ->where('estado_control', 'EXCEDENTE')
            ->count();

        $porcentajeEntregadoLogistica = $totalTerminado > 0
            ? 100
            : 0;

        $porcentajeTerminado = $totalIngresoTerminacion > 0
            ? round(($totalTerminado / $totalIngresoTerminacion) * 100, 2)
            : 0;

        $pendientes = $produccionTerminada
            ->filter(function ($item) {
                return $item->pendiente_terminar > 0;
            });

        $antiguedadMaximaPendiente = $pendientes->isNotEmpty()
            ? (int) $pendientes->max('dias_en_terminacion')
            : 0;

        $otMasAntiguaPendiente = $pendientes
            ->sortByDesc('dias_en_terminacion')
            ->first();

        $conTiempo = $produccionTerminada
            ->filter(function ($item) {
                return $item->dias_en_terminacion !== null
                    && $item->estado_control !== 'SIN INGRESO';
            });

        $promedioDiasTerminacion = $conTiempo->isNotEmpty()
            ? round($conTiempo->avg('dias_en_terminacion'), 1)
            : 0;

        $porPagina = 50;
        $paginaActual = max(1, (int) $request->input('page', 1));
        $itemsPagina = $produccionTerminada
            ->slice(($paginaActual - 1) * $porPagina, $porPagina)
            ->values();

        $produccionPaginada = new LengthAwarePaginator(
            $itemsPagina,
            $produccionTerminada->count(),
            $porPagina,
            $paginaActual,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('control.terminacion', compact(
            'fechaDesde',
            'fechaHasta',
            'buscar',
            'estados',
            'produccionPaginada',
            'totalIngresoTerminacion',
            'totalTerminado',
            'totalEntregadoLogistica',
            'totalRecepcionLocal',
            'totalCostoRemitido',
            'totalVentaRemitida',
            'totalCostoRecibido',
            'totalVentaRecibida',
            'totalMargenBrutoRemitido',
            'porcentajeMargenBrutoRemitido',
            'totalFaltaTerminacion',
            'otsFaltaTerminacion',
            'totalSinDestino',
            'otsSinDestino',
            'totalPendienteRemitirPlan',
            'otsPendienteRemitirPlan',
            'totalRemitidoReal',
            'totalEnTransito',
            'otsEnTransito',
            'totalPendienteEnvio',
            'otsPendientesEnvio',
            'totalHuecoDetalleLogistico',
            'otsHuecoDetalleLogistico',
            'totalPendienteTerminar',
            'totalExcesoProductoTerminado',
            'totalOTs',
            'otsParciales',
            'otsCompletas',
            'otsSinIngreso',
            'otsExcedidas',
            'porcentajeEntregadoLogistica',
            'porcentajeTerminado',
            'antiguedadMaximaPendiente',
            'otMasAntiguaPendiente',
            'promedioDiasTerminacion'
        ));
    }

    public function flujoDiario(Request $request)
    {
        $fechaDesde = $request->input('fecha_desde', now()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));
        $buscar = trim((string) $request->input('buscar', ''));

        $aplicarBusquedaOt = function ($query) use ($buscar) {
            if ($buscar === '') {
                return;
            }

            $query->where(function ($q) use ($buscar) {
                if (is_numeric($buscar)) {
                    $q->where('o.nro_ot', (int) $buscar)
                        ->orWhere('o.codigo', 'ILIKE', '%' . $buscar . '%')
                        ->orWhere('o.descripcion', 'ILIKE', '%' . $buscar . '%');
                } else {
                    $q->where('o.codigo', 'ILIKE', '%' . $buscar . '%')
                        ->orWhere('o.descripcion', 'ILIKE', '%' . $buscar . '%');
                }
            });
        };

        /*
         * 1) ENTRADA A TERMINACIÓN
         * TERMINACION - TERMINACION representa lo que ingresa
         * físicamente al área para esta conciliación.
         */
        $entradaQuery = DB::table('ot_trazabilidad as t')
            ->join('ot as o', 'o.id_ot', '=', 't.id_ot')
            ->where('t.proceso', 'TERMINACION - TERMINACION')
            ->whereBetween('t.fecha_proceso', [$fechaDesde, $fechaHasta]);

        $aplicarBusquedaOt($entradaQuery);

        $entradasTerminacion = $entradaQuery
            ->groupBy(
                'o.id_ot',
                'o.nro_ot',
                'o.codigo',
                'o.descripcion',
                'o.cantidad_orden',
                't.fecha_proceso'
            )
            ->select(
                'o.id_ot',
                'o.nro_ot',
                'o.codigo',
                'o.descripcion',
                'o.cantidad_orden',
                't.fecha_proceso as fecha',
                DB::raw('SUM(t.resultado) as cantidad')
            )
            ->orderBy('t.fecha_proceso')
            ->orderBy('o.nro_ot')
            ->get();

        /*
         * 2) PRODUCTO TERMINADO = ENTRADA A LOGÍSTICA
         */
        $ptQuery = DB::table('ot_trazabilidad as t')
            ->join('ot as o', 'o.id_ot', '=', 't.id_ot')
            ->where('t.proceso', 'TERMINACION - PRODUCTO TERMINADO')
            ->whereBetween('t.fecha_proceso', [$fechaDesde, $fechaHasta]);

        $aplicarBusquedaOt($ptQuery);

        $salidasProductoTerminado = $ptQuery
            ->groupBy(
                'o.id_ot',
                'o.nro_ot',
                'o.codigo',
                'o.descripcion',
                'o.cantidad_orden',
                't.fecha_proceso'
            )
            ->select(
                'o.id_ot',
                'o.nro_ot',
                'o.codigo',
                'o.descripcion',
                'o.cantidad_orden',
                't.fecha_proceso as fecha',
                DB::raw('SUM(t.resultado) as cantidad')
            )
            ->orderBy('t.fecha_proceso')
            ->orderBy('o.nro_ot')
            ->get();

        /*
         * 3) ENVÍOS DE LAS OTs QUE TUVIERON PT EN EL PERÍODO.
         * Se siguen aunque la remisión haya ocurrido después del rango seleccionado.
         * Solo distribución original CASA CENTRAL -> destino.
         */
        $idsOtPt = $salidasProductoTerminado
            ->pluck('id_ot')
            ->filter()
            ->unique()
            ->values();

        $fechaPtPorOt = $salidasProductoTerminado
            ->groupBy('id_ot')
            ->map(function ($items) {
                return $items->min('fecha');
            });

        $envios = collect();

        if ($idsOtPt->isNotEmpty() && Schema::hasTable('ot_logistica_remisiones')) {
            $enviosQuery = DB::table('ot_logistica_remisiones as r')
                ->join('ot as o', 'o.id_ot', '=', 'r.id_ot')
                ->whereIn('r.id_ot', $idsOtPt->all())
                ->where(function ($q) {
                    $q->where('r.cod_sucursal_salida', 1)
                        ->orWhere('r.sucursal_salida', 'ILIKE', 'CASA CENTRAL');
                });

            $aplicarBusquedaOt($enviosQuery);

            $envios = $enviosQuery
                ->select(
                    'r.id',
                    'r.id_ot',
                    'o.nro_ot',
                    'o.codigo as codigo_ot',
                    'o.descripcion',
                    'r.codigo as codigo_variante',
                    'r.fecha_remision',
                    'r.fecha_recepcion',
                    'r.serie',
                    'r.numero_remision',
                    'r.cod_sucursal_destino',
                    'r.sucursal_destino',
                    'r.sucursal_logistica',
                    'r.cantidad'
                )
                ->orderBy('o.nro_ot')
                ->orderBy('r.fecha_remision')
                ->orderBy('r.serie')
                ->orderBy('r.numero_remision')
                ->orderBy('r.codigo')
                ->get()
                ->filter(function ($item) use ($fechaPtPorOt) {
                    $fechaPt = $fechaPtPorOt->get($item->id_ot);

                    if (!$fechaPt || !$item->fecha_remision) {
                        return true;
                    }

                    return \Carbon\Carbon::parse($item->fecha_remision)
                        ->startOfDay()
                        ->gte(\Carbon\Carbon::parse($fechaPt)->startOfDay());
                })
                ->map(function ($item) {
                    $variante = $this->descomponerCodigoVariante(
                        $item->codigo_variante ?: $item->codigo_ot
                    );

                    $item->codigo_base = $variante['codigo_base'];
                    $item->color = $variante['color'];
                    $item->talle = $variante['talle'];
                    $item->destino = $item->sucursal_destino
                        ?: ($item->sucursal_logistica ?: 'SIN DESTINO');
                    $item->cantidad = (int) $item->cantidad;
                    $item->cantidad_recepcionada = $item->fecha_recepcion
                        ? $item->cantidad
                        : 0;
                    $item->cantidad_transito = $item->fecha_recepcion
                        ? 0
                        : $item->cantidad;

                    return $item;
                })
                ->groupBy(function ($item) {
                    return implode('|', [
                        $item->id_ot,
                        $item->serie,
                        $item->numero_remision,
                        $item->codigo_variante,
                        $item->cod_sucursal_destino,
                        $item->fecha_remision,
                        $item->fecha_recepcion,
                    ]);
                })
                ->map(function ($lineas) {
                    $base = clone $lineas->first();
                    $base->cantidad = (int) $lineas->sum('cantidad');
                    $base->cantidad_recepcionada = (int) $lineas->sum('cantidad_recepcionada');
                    $base->cantidad_transito = (int) $lineas->sum('cantidad_transito');
                    return $base;
                })
                ->values();
        }

        $totalEntradaTerminacion = (int) $entradasTerminacion->sum('cantidad');
        $totalProductoTerminado = (int) $salidasProductoTerminado->sum('cantidad');
        $totalEnviado = (int) $envios->sum('cantidad');
        $totalRecepcionado = (int) $envios->sum('cantidad_recepcionada');
        $totalEnTransito = (int) $envios->sum('cantidad_transito');

        $resumen = (object) [
            'entrada_terminacion' => $totalEntradaTerminacion,
            'ots_entrada' => $entradasTerminacion->pluck('id_ot')->unique()->count(),
            'producto_terminado' => $totalProductoTerminado,
            'ots_pt' => $salidasProductoTerminado->pluck('id_ot')->unique()->count(),
            'enviado' => $totalEnviado,
            'recepcionado' => $totalRecepcionado,
            'transito' => $totalEnTransito,
            'documentos' => $envios->map(function ($item) {
                return $item->serie . '|' . $item->numero_remision;
            })->unique()->count(),
        ];

        return view('control.flujo_diario', compact(
            'fechaDesde',
            'fechaHasta',
            'buscar',
            'entradasTerminacion',
            'salidasProductoTerminado',
            'envios',
            'resumen'
        ));
    }

    public function reportePendientesEnvio(Request $request)
    {
        $datos = $this->construirReportePendientesEnvio($request);

        return view('control.reporte_pendientes_envio', $datos);
    }

    public function exportarReportePendientesEnvioExcel(Request $request)
    {
        $datos = $this->construirReportePendientesEnvio($request);

        $nombre = 'conciliacion_ot_'
            . $datos['fechaDesde'] . '_'
            . $datos['fechaHasta'] . '.xlsx';

        return Excel::download(
            new \App\Exports\ReporteFaltanteDestinoExport($datos['reportePendiente']),
            $nombre
        );
    }

    private function construirReportePendientesEnvio(Request $request): array
    {
        $fechaDesde = $request->input(
            'fecha_desde',
            now()->startOfMonth()->format('Y-m-d')
        );

        $fechaHasta = $request->input(
            'fecha_hasta',
            now()->format('Y-m-d')
        );

        $buscar = trim((string) $request->input('buscar', ''));

        /*
         * TEMPORADA
         *
         * Fuente: maestro_codigos.temporada.
         * Filtro multiselección. Cada valor es independiente: VERANO,
         * INVIERNO, AMBOS u otra temporada existente. Sin selección = TODAS.
         */
        $temporadas = collect((array) $request->input('temporada', []))
            ->map(function ($temporada) {
                return strtoupper(trim((string) $temporada));
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $temporadasDisponibles = collect();
        $codigosTemporada = collect();

        /*
         * FUENTE OFICIAL DE TEMPORADA: maestro_codigos
         *
         * Ya no dependemos de stock_ventas_sucursales.temporada.
         * Para OT usamos cod_imagen como vínculo natural con el código
         * de 9 dígitos; cod_base/cod_articulo quedan como respaldo.
         */
        if (Schema::hasTable('maestro_codigos')) {
            $temporadasDisponibles = DB::table('maestro_codigos')
                ->whereNotNull('temporada')
                ->whereRaw("TRIM(COALESCE(temporada, '')) <> ''")
                ->selectRaw('UPPER(TRIM(temporada)) as temporada')
                ->distinct()
                ->orderBy('temporada')
                ->pluck('temporada')
                ->filter()
                ->values();

            if (!empty($temporadas)) {
                $codigosTemporada = DB::table('maestro_codigos')
                    ->whereIn(
                        DB::raw('UPPER(TRIM(temporada))'),
                        $temporadas
                    )
                    ->selectRaw(
                        "COALESCE(
                            NULLIF(TRIM(cod_imagen), ''),
                            NULLIF(TRIM(cod_base), ''),
                            NULLIF(TRIM(cod_articulo), '')
                        ) as codigo"
                    )
                    ->pluck('codigo')
                    ->map(function ($codigo) {
                        return $this->normalizarCodigoBaseTemporada(
                            $codigo
                        );
                    })
                    ->filter()
                    ->unique()
                    ->flip();
            }
        }

        /*
         * El rango selecciona OTs que tuvieron Producto Terminado en el período.
         * La conciliación posterior toma el estado completo acumulado de cada OT.
         */
        $otsPeriodo = DB::table('ot_trazabilidad as pt')
            ->join('ot as o', 'o.id_ot', '=', 'pt.id_ot')
            ->where('pt.proceso', 'TERMINACION - PRODUCTO TERMINADO')
            ->whereBetween('pt.fecha_proceso', [$fechaDesde, $fechaHasta])
            ->when($buscar !== '', function ($q) use ($buscar) {
                $q->where(function ($sub) use ($buscar) {
                    if (is_numeric($buscar)) {
                        $sub->where('o.nro_ot', (int) $buscar)
                            ->orWhere(
                                'o.codigo',
                                'ILIKE',
                                '%' . $buscar . '%'
                            )
                            ->orWhere(
                                'o.descripcion',
                                'ILIKE',
                                '%' . $buscar . '%'
                            );
                    } else {
                        $sub->where(
                            'o.codigo',
                            'ILIKE',
                            '%' . $buscar . '%'
                        )->orWhere(
                            'o.descripcion',
                            'ILIKE',
                            '%' . $buscar . '%'
                        );
                    }
                });
            })
            ->groupBy(
                'o.id_ot',
                'o.nro_ot',
                'o.codigo',
                'o.descripcion',
                'o.cantidad_orden',
                'o.estado'
            )
            ->select(
                'o.id_ot',
                'o.nro_ot',
                'o.codigo',
                'o.descripcion',
                'o.cantidad_orden',
                'o.estado'
            )
            ->get();

        if (!empty($temporadas)) {
            $otsPeriodo = $otsPeriodo
                ->filter(function ($ot) use ($codigosTemporada) {
                    $codigoBase = $this->normalizarCodigoBaseTemporada(
                        $ot->codigo
                    );

                    return $codigoBase !== ''
                        && $codigosTemporada->has($codigoBase);
                })
                ->values();
        }

        $ids = $otsPeriodo
            ->pluck('id_ot')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $conciliacionPorOt = $this->resumenDistribucionComoDashboardOt(
            $ids
        );

        $reporteCompleto = $otsPeriodo
            ->map(function ($ot) use ($conciliacionPorOt) {
                $c = $conciliacionPorOt->get($ot->id_ot);

                if (!$c) {
                    return null;
                }

                $ot->objetivo = (int) $c->objetivo;
                $ot->ingreso_terminacion =
                    (int) $c->ingreso_terminacion;
                $ot->producto_terminado =
                    (int) $c->producto_terminado;
                // Objetivo logístico = todo el PT.
                $ot->planificado =
                    (int) $c->planificado;

                // Plan realmente asignado por sucursal/destino.
                $ot->plan_detallado =
                    (int) $c->plan_detallado;
                $ot->plan_disponible =
                    (int) $c->plan_disponible;

                $ot->remitido_original =
                    (int) $c->remitido_original;
                $ot->recibido_original =
                    (int) $c->recibido_original;

                $ot->falta_terminacion =
                    (int) $c->falta_terminacion;
                $ot->sin_destino =
                    (int) $c->sin_destino;
                $ot->sin_destino_plan =
                    (int) $c->sin_destino_plan;
                // Pendiente con destino ya asignado.
                $ot->pendiente_remitir_plan =
                    (int) $c->pendiente_remitir_plan;

                // Pendiente total de salida = incluye lo todavía sin destino.
                $ot->pendiente_remitir =
                    (int) $c->pendiente_remitir;
                $ot->pendiente_real_salida =
                    (int) $c->pendiente_real_salida;

                $ot->en_transito =
                    (int) $c->en_transito;

                $ot->estado_conciliacion =
                    $c->estado_conciliacion;

                $ot->hueco_plan_vs_real =
                    (int) $c->hueco_plan_vs_real;

                /*
                 * Diagnósticos explícitos para detectar de dónde salen las
                 * diferencias del resumen.
                 */
                $ot->diferencia_pt_ingreso =
                    $ot->producto_terminado
                    - $ot->ingreso_terminacion;

                $ot->exceso_pt_sobre_ingreso = max(
                    0,
                    $ot->diferencia_pt_ingreso
                );

                $ot->faltante_pt_vs_ingreso = max(
                    0,
                    -$ot->diferencia_pt_ingreso
                );

                $ot->hueco_plan_bruto =
                    (int) $c->sin_destino_plan;

                $ot->cubierto_sin_plan_por_remision = max(
                    0,
                    $ot->hueco_plan_bruto
                    - $ot->sin_destino
                );

                $ot->exceso_plan_sobre_pt = max(
                    0,
                    $ot->plan_detallado
                    - $ot->producto_terminado
                );

                $ot->destinos = (int) $c->destinos;

                $ot->ultima_fecha_pt =
                    $c->ultima_fecha_pt;
                $ot->ultima_fecha_logistica =
                    $c->ultima_fecha_plan;
                $ot->ultima_remision =
                    $c->ultima_remision;
                $ot->ultima_recepcion =
                    $c->ultima_recepcion;

                $ot->requiere_atencion =
                    $ot->falta_terminacion > 0
                    || $ot->sin_destino > 0
                    || $ot->pendiente_remitir > 0
                    || $ot->en_transito > 0;

                if ($ot->falta_terminacion > 0) {
                    $ot->solicitud = 'TERMINACION: COMPLETAR '
                        . $ot->falta_terminacion;
                } elseif ($ot->sin_destino > 0) {
                    $ot->solicitud = 'LOGISTICA: ASIGNAR DESTINO '
                        . $ot->sin_destino;
                } elseif ($ot->pendiente_remitir > 0) {
                    $ot->solicitud = 'LOGISTICA: REMITIR '
                        . $ot->pendiente_remitir;
                } elseif ($ot->en_transito > 0) {
                    $ot->solicitud = 'LOCAL: CONFIRMAR '
                        . $ot->en_transito;
                } else {
                    $ot->solicitud = 'COMPLETO';
                }

                return $ot;
            })
            ->filter()
            ->values();

        /*
         * DIAGNÓSTICO DE DIFERENCIAS
         *
         * Producción:
         *   PT > Ingreso  => revisar trazabilidad/importación de Terminación.
         *   Ingreso > PT  => producción todavía no completada.
         *
         * Logística:
         *   PT > Plan     => detalle logístico insuficiente.
         *   Parte del hueco puede quedar explicada por una remisión real.
         */
        $diagnosticoProduccion = $reporteCompleto
            ->filter(function ($ot) {
                return $ot->diferencia_pt_ingreso !== 0;
            })
            ->sortByDesc(function ($ot) {
                return abs($ot->diferencia_pt_ingreso);
            })
            ->values();

        $diagnosticoPlan = $reporteCompleto
            ->filter(function ($ot) {
                return $ot->hueco_plan_bruto > 0
                    || $ot->exceso_plan_sobre_pt > 0;
            })
            ->sortByDesc(function ($ot) {
                return max(
                    $ot->hueco_plan_bruto,
                    $ot->exceso_plan_sobre_pt
                );
            })
            ->values();

        $reportePendiente = $reporteCompleto
            ->filter(function ($ot) {
                return $ot->requiere_atencion;
            })
            ->sort(function ($a, $b) {
                $prioridad = [
                    'FALTA TERMINACION' => 1,
                    'SIN DESTINO' => 2,
                    'PENDIENTE REMITIR' => 3,
                    'PENDIENTE SALIDA' => 3,
                    'EN TRANSITO' => 4,
                    'CONFIRMADO' => 5,
                ];

                $pa = $prioridad[$a->estado_conciliacion] ?? 99;
                $pb = $prioridad[$b->estado_conciliacion] ?? 99;

                if ($pa !== $pb) {
                    return $pa <=> $pb;
                }

                return ((int) $b->nro_ot)
                    <=> ((int) $a->nro_ot);
            })
            ->values();

        $resumen = (object) [
            'ots_periodo' => $reporteCompleto->count(),
            'ots_pendientes' => $reportePendiente->count(),

            'objetivo' => (int) $reporteCompleto
                ->sum('objetivo'),
            'ingreso_terminacion' => (int) $reporteCompleto
                ->sum('ingreso_terminacion'),
            'producto_terminado' => (int) $reporteCompleto
                ->sum('producto_terminado'),
            // Objetivo de salida = todo el PT.
            'planificado' => (int) $reporteCompleto
                ->sum('planificado'),

            // Distribución/destino realmente cargado.
            'plan_detallado' => (int) $reporteCompleto
                ->sum('plan_detallado'),
            'plan_disponible' => (int) $reporteCompleto
                ->sum('plan_disponible'),

            'remitido' => (int) $reporteCompleto
                ->sum('remitido_original'),
            'recibido' => (int) $reporteCompleto
                ->sum('recibido_original'),

            'falta_terminacion' => (int) $reporteCompleto
                ->sum('falta_terminacion'),
            'sin_destino' => (int) $reporteCompleto
                ->sum('sin_destino'),

            /*
             * Hueco bruto del plan antes de considerar que una remisión real
             * también demuestra destino aunque falte detalle logístico.
             */
            'sin_destino_plan' => (int) $reporteCompleto
                ->sum('sin_destino_plan'),

            // Con destino asignado pero todavía sin remisión.
            'pendiente_remitir_plan' => (int) $reporteCompleto
                ->sum('pendiente_remitir_plan'),

            // Todo PT aún sin salida, incluyendo sin destino.
            'pendiente_remitir' => (int) $reporteCompleto
                ->sum('pendiente_remitir'),
            'pendiente_real_salida' => (int) $reporteCompleto
                ->sum('pendiente_real_salida'),

            'en_transito' => (int) $reporteCompleto
                ->sum('en_transito'),
            'hueco_plan_vs_real' => (int) $reporteCompleto
                ->sum('hueco_plan_vs_real'),
        ];

        $resumen->cubierto_sin_plan_por_remision = max(
            0,
            (int) $resumen->sin_destino_plan
                - (int) $resumen->sin_destino
        );

        $resumen->diferencia_pt_ingreso =
            (int) $resumen->producto_terminado
            - (int) $resumen->ingreso_terminacion;

        $resumen->ots_diferencia_produccion =
            $diagnosticoProduccion->count();

        $resumen->ots_diferencia_plan =
            $diagnosticoPlan->count();

        $resumen->exceso_pt_sobre_ingreso = (int)
            $diagnosticoProduccion->sum(
                'exceso_pt_sobre_ingreso'
            );

        $resumen->faltante_pt_vs_ingreso = (int)
            $diagnosticoProduccion->sum(
                'faltante_pt_vs_ingreso'
            );

        /*
         * Control de conservación física:
         * PT = Remitido + Pendiente con destino + Sin destino.
         */
        $resumen->cuadre_fisico =
            (int) $resumen->remitido
            + (int) $resumen->pendiente_remitir_plan
            + (int) $resumen->sin_destino;

        $resumen->diferencia_cuadre =
            (int) $resumen->producto_terminado
            - (int) $resumen->cuadre_fisico;

        return compact(
            'fechaDesde',
            'fechaHasta',
            'buscar',
            'temporadas',
            'temporadasDisponibles',
            'reporteCompleto',
            'reportePendiente',
            'diagnosticoProduccion',
            'diagnosticoPlan',
            'resumen'
        );
    }

    public function detalle(Request $request, $idOt)
    {
        $fechaPt = $request->input('fecha_pt');
        $procesoLogistica = 'LOGISTICA - LOGISTICA Y DISTRIBUCION';

        $ot = DB::table('ot')
            ->where('id_ot', $idOt)
            ->select(
                'id_ot',
                'nro_ot',
                'codigo',
                'descripcion',
                'cantidad_orden'
            )
            ->first();

        abort_if(!$ot, 404);

        $conciliacion = $this->conciliacionService
            ->conciliarPorIds([(int) $idOt])
            ->get((int) $idOt);

        $queryDetalles = DB::table('ot_trazabilidad as tl')
            ->join(
                'ot_logistica_detalle as d',
                'd.id_trazabilidad',
                '=',
                'tl.id_trazabilidad'
            )
            ->where('tl.id_ot', $idOt)
            ->where('tl.proceso', $procesoLogistica);

        if ($fechaPt) {
            $queryDetalles->where('tl.fecha_proceso', '>=', $fechaPt);
        }

        $detalles = $queryDetalles
            ->select(
                'd.id',
                'd.sucursal',
                'd.cantidad',
                'tl.fecha_proceso as fecha_logistica'
            )
            ->orderBy('tl.fecha_proceso')
            ->orderBy('d.id')
            ->get();

        $remisionesOriginales = collect();
        $remisionesPorDetalle = collect();
        $remisionesSinDetalle = collect();

        if (Schema::hasTable('ot_logistica_remisiones')) {
            /*
             * Tomamos TODAS las remisiones originales de la OT, tengan o no
             * id_logistica_detalle. El vínculo al plan es una conciliación
             * visual; la remisión física no deja de existir por falta de FK.
             */
            $queryRemisiones = DB::table('ot_logistica_remisiones')
                ->where('id_ot', $idOt)
                ->where(function ($q) {
                    $q->where('cod_sucursal_salida', 1)
                        ->orWhereRaw(
                            "UPPER(TRIM(COALESCE(sucursal_salida, ''))) = 'CASA CENTRAL'"
                        )
                        ->orWhereRaw(
                            "UPPER(TRIM(COALESCE(sucursal_salida, ''))) = 'MATRIZ'"
                        );
                })
                ->where(function ($q) {
                    $q->whereNull('cod_sucursal_destino')
                        ->orWhere('cod_sucursal_destino', '<>', 1);
                })
                ->whereRaw(
                    "UPPER(COALESCE(NULLIF(TRIM(sucursal_destino), ''), NULLIF(TRIM(sucursal_logistica), ''), '')) NOT IN ('', 'CASA CENTRAL', 'MATRIZ')"
                );

            if ($fechaPt) {
                $queryRemisiones->whereRaw(
                    'COALESCE(fecha_remision, fecha_creacion) >= ?',
                    [$fechaPt]
                );
            }

            $remisionesOriginales = $queryRemisiones
                ->orderByRaw(
                    'COALESCE(fecha_remision, fecha_creacion) ASC'
                )
                ->orderBy('serie')
                ->orderBy('numero_remision')
                ->orderBy('id')
                ->get();
        }

        if ($detalles->isNotEmpty() && $remisionesOriginales->isNotEmpty()) {
            $detallesPorId = $detalles->keyBy('id');

            $capacidad = $detalles->mapWithKeys(function ($detalle) {
                return [
                    (int) $detalle->id =>
                        max(0, (int) $detalle->cantidad),
                ];
            })->all();

            $asignado = array_fill_keys(
                array_keys($capacidad),
                0
            );

            $asignadas = [];
            $idsRemisionAsignada = [];

            /*
             * AYALA y MODELO MUESTRA comparten destino físico
             * COMERCIAL MATRIZ. Se concilian por LÍNEA, no por documento
             * completo:
             *
             * Ayala 9 + Modelo 4 y líneas 3,3,3,3 =>
             * Ayala recibe las primeras 3 líneas (9) y Modelo la siguiente (3).
             *
             * Así una misma remisión puede contener líneas de ambos planes,
             * pero una misma línea física nunca se duplica.
             */
            $detallesMatriz = $detalles
                ->filter(function ($detalle) {
                    return in_array(
                        $this->normalizarDestinoMovimiento(
                            $detalle->sucursal
                        ),
                        ['AYALA', 'MODELO'],
                        true
                    );
                })
                ->sortBy(function ($detalle) {
                    return $this->normalizarDestinoMovimiento(
                        $detalle->sucursal
                    ) === 'AYALA'
                        ? 1
                        : 2;
                })
                ->values();

            foreach ($remisionesOriginales as $remisionOriginal) {
                $destinoReal = $remisionOriginal->sucursal_destino
                    ?: $remisionOriginal->sucursal_logistica;

                $destinoNormalizado =
                    $this->normalizarDestinoMovimiento($destinoReal);

                $cantidadOriginal = max(
                    0,
                    (int) $remisionOriginal->cantidad
                );

                if ($cantidadOriginal <= 0) {
                    continue;
                }

                /*
                 * COMERCIAL MATRIZ: repartir la línea contra AYALA y luego
                 * MODELO MUESTRA respetando las capacidades del plan.
                 */
                if ($destinoNormalizado === 'MATRIZ'
                    && $detallesMatriz->isNotEmpty()) {
                    $cantidadRestante = $cantidadOriginal;
                    $asignoAlgo = false;

                    foreach ($detallesMatriz as $candidato) {
                        if ($cantidadRestante <= 0) {
                            break;
                        }

                        $idCandidato = (int) $candidato->id;
                        $capacidadRestante = max(
                            0,
                            ($capacidad[$idCandidato] ?? 0)
                                - ($asignado[$idCandidato] ?? 0)
                        );

                        if ($capacidadRestante <= 0) {
                            continue;
                        }

                        $cantidadAsignar = min(
                            $cantidadRestante,
                            $capacidadRestante
                        );

                        $remisionVisual = clone $remisionOriginal;
                        $remisionVisual->cantidad = $cantidadAsignar;
                        $remisionVisual->id_detalle_visual =
                            $idCandidato;

                        $asignadas[] = $remisionVisual;

                        $asignado[$idCandidato] =
                            ($asignado[$idCandidato] ?? 0)
                            + $cantidadAsignar;

                        $cantidadRestante -= $cantidadAsignar;
                        $asignoAlgo = true;
                    }

                    /*
                     * Si MATRIZ ya superó el plan conjunto AYALA+MODELO,
                     * conservar el exceso en el último plan para que siga
                     * visible y no desaparezca del modal.
                     */
                    if ($cantidadRestante > 0) {
                        $candidatoExceso = $detallesMatriz->last();
                        $idCandidato = (int) $candidatoExceso->id;

                        $remisionVisual = clone $remisionOriginal;
                        $remisionVisual->cantidad = $cantidadRestante;
                        $remisionVisual->id_detalle_visual =
                            $idCandidato;
                        $remisionVisual->exceso_plan = true;

                        $asignadas[] = $remisionVisual;

                        $asignado[$idCandidato] =
                            ($asignado[$idCandidato] ?? 0)
                            + $cantidadRestante;

                        $cantidadRestante = 0;
                        $asignoAlgo = true;
                    }

                    if ($asignoAlgo) {
                        $idsRemisionAsignada[(int) $remisionOriginal->id]
                            = true;
                    }

                    continue;
                }

                /*
                 * Destinos normales: primero respetar un vínculo persistido
                 * compatible; si no existe, buscar el plan por nombre.
                 */
                $idAsignado = null;
                $idDetalleActual =
                    (int) $remisionOriginal->id_logistica_detalle;

                if ($idDetalleActual > 0) {
                    $detalleActual = $detallesPorId->get(
                        $idDetalleActual
                    );

                    if ($detalleActual
                        && $this->normalizarDestinoMovimiento(
                            $detalleActual->sucursal
                        ) === $destinoNormalizado) {
                        $idAsignado = $idDetalleActual;
                    }
                }

                if (!$idAsignado) {
                    $candidato = $detalles->first(
                        function ($detalle) use ($destinoNormalizado) {
                            return $this->normalizarDestinoMovimiento(
                                $detalle->sucursal
                            ) === $destinoNormalizado;
                        }
                    );

                    if ($candidato) {
                        $idAsignado = (int) $candidato->id;
                    }
                }

                if (!$idAsignado) {
                    continue;
                }

                $remisionVisual = clone $remisionOriginal;
                $remisionVisual->id_detalle_visual = $idAsignado;
                $asignadas[] = $remisionVisual;

                $asignado[$idAsignado] =
                    ($asignado[$idAsignado] ?? 0)
                    + $cantidadOriginal;

                $idsRemisionAsignada[(int) $remisionOriginal->id]
                    = true;
            }

            $remisionesPorDetalle = collect($asignadas)
                ->groupBy('id_detalle_visual');

            $remisionesSinDetalle = $remisionesOriginales
                ->reject(function ($remision) use (
                    $idsRemisionAsignada
                ) {
                    return isset(
                        $idsRemisionAsignada[(int) $remision->id]
                    );
                })
                ->values();
        } else {
            $remisionesSinDetalle = $remisionesOriginales;
        }

        foreach ($detalles as $detalle) {
            $planNormalizado =
                $this->normalizarDestinoMovimiento(
                    $detalle->sucursal
                );

            $detalle->remisiones = collect(
                $remisionesPorDetalle->get(
                    (int) $detalle->id,
                    collect()
                )
            )->map(function (
                $remision
            ) use ($detalle, $planNormalizado) {
                $destinoReal = $remision->sucursal_destino
                    ?: $remision->sucursal_logistica;

                $destinoNormalizado =
                    $this->normalizarDestinoMovimiento(
                        $destinoReal
                    );

                $remision->destino_planificado =
                    $detalle->sucursal;

                $remision->destino_real = $destinoReal;

                /*
                 * AYALA y MODELO son planes internos que físicamente pueden
                 * salir a COMERCIAL MATRIZ. Eso no se marca como error:
                 * se muestra como REDIRIGIDO para explicar el destino real.
                 */
                $remision->es_redireccion =
                    $destinoNormalizado !== $planNormalizado;

                return $remision;
            })->values();

            $detalle->cantidad_remitida =
                (int) $detalle->remisiones->sum('cantidad');

            $detalle->cantidad_recibida =
                (int) $detalle->remisiones
                    ->filter(function ($remision) {
                        return !empty(
                            $remision->fecha_recepcion
                        );
                    })
                    ->sum('cantidad');

            $detalle->cantidad_en_transito = max(
                0,
                $detalle->cantidad_remitida
                    - $detalle->cantidad_recibida
            );

            $detalle->pendiente_remitir = max(
                0,
                (int) $detalle->cantidad
                    - $detalle->cantidad_remitida
            );

            $detalle->exceso_remitido = max(
                0,
                $detalle->cantidad_remitida
                    - (int) $detalle->cantidad
            );

            $detalle->costo_remitido = (float)
                $detalle->remisiones->sum(
                    function ($remision) {
                        return (float) $remision->cantidad
                            * (float) $remision->costo_unitario;
                    }
                );

            $detalle->venta_remitida = (float)
                $detalle->remisiones->sum(
                    function ($remision) {
                        return (float) $remision->cantidad
                            * (float) $remision->precio_venta;
                    }
                );

            $detalle->costo_recibido = (float)
                $detalle->remisiones
                    ->filter(function ($remision) {
                        return !empty(
                            $remision->fecha_recepcion
                        );
                    })
                    ->sum(function ($remision) {
                        return (float) $remision->cantidad
                            * (float) $remision->costo_unitario;
                    });

            $detalle->venta_recibida = (float)
                $detalle->remisiones
                    ->filter(function ($remision) {
                        return !empty(
                            $remision->fecha_recepcion
                        );
                    })
                    ->sum(function ($remision) {
                        return (float) $remision->cantidad
                            * (float) $remision->precio_venta;
                    });

            $detalle->margen_bruto =
                $detalle->venta_remitida
                - $detalle->costo_remitido;
        }

        /*
         * Encabezado del modal:
         * - Plan logística = TODO PT que debe salir.
         * - Plan detallado = suma distribuida por sucursales.
         * - Sin asignar = PT - plan detallado.
         */
        $totalPlanDetalladoRaw = (int) $detalles->sum('cantidad');

        /*
         * PRODUCTO TERMINADO manda.
         * El plan efectivo jamás puede superar el PT.
         */
        $totalPlan = $conciliacion
            ? (int) $conciliacion->producto_terminado
            : (int) $ot->cantidad_orden;

        $totalPlanDetallado = min(
            $totalPlan,
            $totalPlanDetalladoRaw
        );

        $totalSinAsignar = max(
            0,
            $totalPlan - $totalPlanDetallado
        );

        /*
         * Todo lo que quede por encima del PT es movimiento/auditoría,
         * nunca plan efectivo.
         */
        $totalExcesoPlan = max(
            0,
            $totalPlanDetalladoRaw - $totalPlan
        );

        $totalRemitido = $conciliacion
            ? (int) $conciliacion->remitido_original
            : (int) $detalles->sum('cantidad_remitida');

        $totalRecibido = $conciliacion
            ? (int) $conciliacion->recibido_original
            : (int) $detalles->sum('cantidad_recibida');

        /*
         * Movimiento físico bruto para auditoría. Puede superar la OT por
         * reenvíos/re-movimientos. El avance efectivo de arriba permanece
         * limitado al PT.
         */
        $totalRemitidoFisico = (int) $remisionesOriginales
            ->sum('cantidad');

        $totalRecibidoFisico = (int) $remisionesOriginales
            ->filter(function ($remision) {
                return !empty($remision->fecha_recepcion);
            })
            ->sum('cantidad');

        $totalRemovido = max(
            0,
            $totalRemitidoFisico - $totalRemitido
        );

        $totalConfirmadoExtra = max(
            0,
            $totalRecibidoFisico - $totalRecibido
        );

        /*
         * Pendiente del PLAN por destino. Es distinto del pendiente físico
         * efectivo de la OT: una reasignación extra puede completar el volumen
         * total aunque un destino histórico todavía figure pendiente.
         */
        $totalPendientePlanDestino = (int) $detalles
            ->sum('pendiente_remitir');

        $totalEnTransito = max(
            0,
            $totalRemitido - $totalRecibido
        );

        $totalPendiente = max(
            0,
            $totalPlan - $totalRemitido
        );

        /*
         * Total económico general de la OT.
         * Se calcula sobre las líneas físicas originales para no duplicar
         * importes cuando COMERCIAL MATRIZ se reparte visualmente entre
         * AYALA y MODELO MUESTRA.
         */
        $totalCostoRemitido = (float)
            $remisionesOriginales->sum(
                function ($remision) {
                    return (float) $remision->cantidad
                        * (float) $remision->costo_unitario;
                }
            );

        $totalVentaRemitida = (float)
            $remisionesOriginales->sum(
                function ($remision) {
                    return (float) $remision->cantidad
                        * (float) $remision->precio_venta;
                }
            );

        $remisionesRecibidas = $remisionesOriginales
            ->filter(function ($remision) {
                return !empty($remision->fecha_recepcion);
            });

        $totalCostoRecibido = (float)
            $remisionesRecibidas->sum(
                function ($remision) {
                    return (float) $remision->cantidad
                        * (float) $remision->costo_unitario;
                }
            );

        $totalVentaRecibida = (float)
            $remisionesRecibidas->sum(
                function ($remision) {
                    return (float) $remision->cantidad
                        * (float) $remision->precio_venta;
                }
            );

        $totalMargenBrutoRemitido =
            $totalVentaRemitida - $totalCostoRemitido;

        $porcentajeMargenBrutoRemitido =
            $totalVentaRemitida > 0
                ? round(
                    ($totalMargenBrutoRemitido
                        / $totalVentaRemitida) * 100,
                    1
                )
                : 0;

        return view('control._terminacion_detalle', compact(
            'ot',
            'detalles',
            'remisionesSinDetalle',
            'totalPlan',
            'totalPlanDetallado',
            'totalPlanDetalladoRaw',
            'totalSinAsignar',
            'totalExcesoPlan',
            'totalRemitido',
            'totalRemitidoFisico',
            'totalRecibidoFisico',
            'totalRemovido',
            'totalConfirmadoExtra',
            'totalPendientePlanDestino',
            'totalRecibido',
            'totalEnTransito',
            'totalPendiente',
            'totalCostoRemitido',
            'totalVentaRemitida',
            'totalCostoRecibido',
            'totalVentaRecibida',
            'totalMargenBrutoRemitido',
            'porcentajeMargenBrutoRemitido'
        ));
    }

    /**
     * Normaliza el código de OT/variante para relacionarlo con temporada.
     * 050617617 -> 050617617
     * 050617617GR04 -> 050617617
     * 50617617 -> 050617617
     */
    private function normalizarCodigoBaseTemporada($codigo): string
    {
        $valor = strtoupper(trim((string) $codigo));
        $valor = ltrim($valor, "'’");
        $valor = preg_replace('/\\s+/u', '', $valor);

        if ($valor === '') {
            return '';
        }

        if (preg_match('/^\\d{1,9}$/', $valor)) {
            return str_pad($valor, 9, '0', STR_PAD_LEFT);
        }

        if (preg_match('/^(\\d{9})/', $valor, $m)) {
            return $m[1];
        }

        $variante = $this->descomponerCodigoVariante($valor);

        return strtoupper(trim(
            (string) ($variante['codigo_base'] ?? '')
        ));
    }

    private function descomponerCodigoVariante($codigo): array
    {
        $valor = strtoupper(trim((string) $codigo));
        $valor = ltrim($valor, "'’\`");
        $valor = preg_replace('/\\s+/u', '', $valor);

        $codigoBase = $valor;
        $color = null;
        $talle = null;

        if (preg_match('/^(\\d{9})([A-Z]+)(\\d+)$/', $valor, $m)) {
            $codigoBase = $m[1];
            $color = $m[2];
            $talle = $m[3];
        } elseif (preg_match('/^(\\d{9})([A-Z0-9]+)$/', $valor, $m)) {
            $codigoBase = $m[1];
            $sufijo = $m[2];

            if (preg_match('/^([A-Z]+)(\\d+)$/', $sufijo, $v)) {
                $color = $v[1];
                $talle = $v[2];
            } else {
                $color = $sufijo;
            }
        } elseif (preg_match('/^(\\d{9})/', $valor, $m)) {
            $codigoBase = $m[1];
        }

        return [
            'codigo_base' => $codigoBase ?: null,
            'color' => $color,
            'talle' => $talle,
        ];
    }

    private function normalizarDestinoMovimiento($valor): string
    {
        $texto = strtoupper(trim((string) $valor));

        $texto = strtr($texto, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ñ' => 'N',
        ]);

        if (strpos($texto, 'MODELO') !== false) {
            return 'MODELO';
        }

        if (strpos($texto, 'MATRIZ') !== false) {
            return 'MATRIZ';
        }

        if (strpos($texto, 'AYALA') !== false) {
            return 'AYALA';
        }

        if (strpos($texto, 'SAN LORENZO') !== false || $texto === 'SL') {
            return 'SL';
        }

        if (strpos($texto, 'SHOP SAN LO') !== false || $texto === 'SHOPP') {
            return 'SHOPP';
        }

        if (strpos($texto, 'MULTIPLAZA') !== false || $texto === 'MULTI') {
            return 'MULTI';
        }

        if (strpos($texto, 'JARDINES') !== false) {
            return 'JARDINES';
        }

        if (strpos($texto, 'MARIANO') !== false) {
            return 'MARIANO';
        }

        if (strpos($texto, 'PINEDO') !== false) {
            return 'PINEDO';
        }

        if (strpos($texto, 'BONANZA') !== false) {
            return 'BONANZA';
        }

        if (strpos($texto, 'RURAL') !== false) {
            return 'RURAL';
        }

        if (strpos($texto, 'NEMBY') !== false) {
            return 'NEMBY';
        }

        if (strpos($texto, 'LUQUE') !== false) {
            return 'LUQUE';
        }

        if (strpos($texto, 'MALL') !== false) {
            return 'MALL';
        }

        if (strpos($texto, 'L06') !== false) {
            return 'L06';
        }

        return $texto;
    }


    public function importarRemisiones(Request $request)
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('max_input_time', '-1');
        @ini_set('memory_limit', '1536M');
        @ignore_user_abort(true);

        DB::disableQueryLog();

        $rutaRetorno = $request->input('origen') === 'dashboard-logistica'
            ? 'dashboard.ot-logistica'
            : 'control.terminacion';

        $parametrosRetorno = array_filter([
            'fecha_desde' => $request->input('fecha_desde'),
            'fecha_hasta' => $request->input('fecha_hasta'),
        ]);

        $request->validate([
            'archivo_envios' => 'required|file|mimes:xlsx,xls,csv|max:102400',
        ]);

        if (!Schema::hasTable('ot_logistica_remisiones')) {
            return redirect()
                ->route($rutaRetorno, $parametrosRetorno)
                ->with('error', 'Primero creá la tabla ot_logistica_remisiones antes de importar ENVIOS.');
        }

        try {
            $archivo = $request->file('archivo_envios');
            $inicio = microtime(true);
            $import = new ControlTerminacionRemisionImport();
            $extension = strtolower($archivo->getClientOriginalExtension());

            if ($extension === 'xlsx') {
                $import->importarXlsxStreaming($archivo->getRealPath());
            } else {
                Excel::import($import, $archivo);
            }

            $segundos = round(microtime(true) - $inicio, 2);

            $mensaje = 'Logística actualizada. '
                . 'Documentos: ' . $import->getDocumentosArchivo()
                . ' | Recibidos: ' . $import->getDocumentosRecibidos()
                . ' | En tránsito: ' . $import->getDocumentosEnTransito()
                . ' | Líneas: ' . $import->getProcesadas()
                . ' | Nuevas: ' . $import->getInsertadas()
                . ' | Actualizadas: ' . $import->getActualizadas()
                . ' | Vinculadas a OT: ' . $import->getVinculadasOt()
                . ' | Vinculadas a logística: ' . $import->getVinculadas()
                . ' | Sin detalle logístico: ' . $import->getSinVincular()
                . ' | Sin OT: ' . $import->getSinOt()
                . ' | Omitidas: ' . $import->getOmitidas()
                . ' | Tiempo: ' . $segundos . ' s.';

            return redirect()
                ->route($rutaRetorno, $parametrosRetorno)
                ->with('success', $mensaje);
        } catch (\Throwable $e) {
            Log::error('ERROR IMPORTACION ENVIOS LOGISTICA', [
                'error' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
            ]);

            return redirect()
                ->route($rutaRetorno, $parametrosRetorno)
                ->with('error', 'No se pudo importar ENVIOS para logística: ' . $e->getMessage());
        }
    }

}