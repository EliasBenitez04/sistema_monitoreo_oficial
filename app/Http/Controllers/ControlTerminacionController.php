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
         * TERMINACION - TERMINACION:
         *     ingreso de la prenda al área de Terminación.
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
                (int) $resumen->planificado;

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
         * TERMINACION - TERMINACION representa lo que ingresa físicamente al área.
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

        $nombre = 'faltantes_destino_terminacion_'
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
         * MISMA REGLA DEL DASHBOARD OT
         * ------------------------------------------------------------
         * 1. El rango selecciona OTs que tuvieron PRODUCTO TERMINADO.
         * 2. Producto Terminado de referencia = ÚLTIMO movimiento
         *    TERMINACION - PRODUCTO TERMINADO de la OT.
         * 3. Total distribuido = SUM(ot_logistica_detalle.cantidad)
         *    de TODOS los movimientos LOGISTICA - LOGISTICA Y DISTRIBUCION.
         * 4. Faltante real = Producto Terminado - Total distribuido.
         *
         * Ejemplo OT 30518:
         * PT 360 - distribuido 358 = faltan 2.
         */

        $otsPeriodo = DB::table('ot_trazabilidad as pt')
            ->join('ot as o', 'o.id_ot', '=', 'pt.id_ot')
            ->where('pt.proceso', 'TERMINACION - PRODUCTO TERMINADO')
            ->whereBetween('pt.fecha_proceso', [$fechaDesde, $fechaHasta])
            ->when($buscar !== '', function ($q) use ($buscar) {
                $q->where(function ($sub) use ($buscar) {
                    if (is_numeric($buscar)) {
                        $sub->where('o.nro_ot', (int) $buscar)
                            ->orWhere('o.codigo', 'ILIKE', '%' . $buscar . '%')
                            ->orWhere('o.descripcion', 'ILIKE', '%' . $buscar . '%');
                    } else {
                        $sub->where('o.codigo', 'ILIKE', '%' . $buscar . '%')
                            ->orWhere('o.descripcion', 'ILIKE', '%' . $buscar . '%');
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
                'o.estado',
                DB::raw('MIN(pt.fecha_proceso) as primera_fecha_pt_periodo'),
                DB::raw('MAX(pt.fecha_proceso) as ultima_fecha_pt_periodo')
            )
            ->get();

        $ids = $otsPeriodo
            ->pluck('id_ot')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $resumenDashboardPorOt = $this->resumenDistribucionComoDashboardOt(
            $ids
        );

        $reporteCompleto = $otsPeriodo
            ->map(function ($ot) use ($resumenDashboardPorOt) {
                $resumenDashboard = $resumenDashboardPorOt->get($ot->id_ot);

                $ot->producto_terminado = max(
                    0,
                    (int) ($resumenDashboard->producto_terminado ?? 0)
                );

                $ot->detalle_logistico = max(
                    0,
                    (int) ($resumenDashboard->total_distribuido ?? 0)
                );

                $ot->remitido_original = max(
                    0,
                    (int) ($resumenDashboard->remitido_original ?? 0)
                );

                $ot->asignado_efectivo = max(
                    0,
                    (int) ($resumenDashboard->asignado_efectivo ?? 0)
                );

                // Compatibilidad con la vista/export existente.
                $ot->total_distribuido = $ot->asignado_efectivo;
                $ot->destino_asignado = $ot->asignado_efectivo;
                $ot->destino_detalle = $ot->detalle_logistico;

                $ot->hueco_detalle = max(
                    0,
                    (int) ($resumenDashboard->hueco_detalle ?? 0)
                );

                $ot->destinos = (int) (
                    $resumenDashboard->destinos ?? 0
                );

                $ot->faltante_destino = max(
                    0,
                    (int) ($resumenDashboard->faltante ?? 0)
                );

                $ot->ultima_fecha_pt =
                    $resumenDashboard->ultima_fecha_pt
                    ?? $ot->ultima_fecha_pt_periodo;

                $ot->ultima_fecha_logistica =
                    $resumenDashboard->ultima_fecha_logistica ?? null;

                $ot->solicitud = $ot->faltante_destino > 0
                    ? 'SOLICITAR ' . $ot->faltante_destino
                        . ($ot->faltante_destino === 1
                            ? ' PRENDA'
                            : ' PRENDAS')
                    : 'COMPLETO';

                return $ot;
            })
            ->values();

        $reportePendiente = $reporteCompleto
            ->filter(function ($ot) {
                return $ot->faltante_destino > 0;
            })
            ->sort(function ($a, $b) {
                if ($a->faltante_destino !== $b->faltante_destino) {
                    return $b->faltante_destino <=> $a->faltante_destino;
                }

                return ((int) $b->nro_ot) <=> ((int) $a->nro_ot);
            })
            ->values();

        $resumen = (object) [
            'ots_periodo' => $reporteCompleto->count(),
            'ots_pendientes' => $reportePendiente->count(),
            'prendas_pendientes' => (int) $reportePendiente
                ->sum('faltante_destino'),
            'producto_terminado' => (int) $reporteCompleto
                ->sum('producto_terminado'),
            'asignado_efectivo' => (int) $reporteCompleto
                ->sum('asignado_efectivo'),
            'detalle_logistico' => (int) $reporteCompleto
                ->sum('detalle_logistico'),
            'hueco_detalle' => (int) $reporteCompleto
                ->sum('hueco_detalle'),
        ];

        return compact(
            'fechaDesde',
            'fechaHasta',
            'buscar',
            'reporteCompleto',
            'reportePendiente',
            'resumen'
        );
    }

    public function detalle(Request $request, $idOt)
    {
        $fechaPt = $request->input('fecha_pt');
        $procesoLogistica = 'LOGISTICA - LOGISTICA Y DISTRIBUCION';

        $ot = DB::table('ot')
            ->where('id_ot', $idOt)
            ->select('id_ot', 'nro_ot', 'codigo', 'descripcion', 'cantidad_orden')
            ->first();

        abort_if(!$ot, 404);

        $queryDetalles = DB::table('ot_trazabilidad as tl')
            ->join('ot_logistica_detalle as d', 'd.id_trazabilidad', '=', 'tl.id_trazabilidad')
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

        $remisionesPorDetalle = collect();

        if (Schema::hasTable('ot_logistica_remisiones') && $detalles->isNotEmpty()) {
            $remisionesOriginales = DB::table('ot_logistica_remisiones')
                ->where('id_ot', $idOt)
                ->where(function ($q) {
                    $q->where('cod_sucursal_salida', 1)
                        ->orWhere('sucursal_salida', 'ILIKE', 'CASA CENTRAL');
                })
                ->whereNotNull('id_logistica_detalle')
                ->orderByRaw('COALESCE(fecha_remision, fecha_creacion) ASC')
                ->orderBy('serie')
                ->orderBy('numero_remision')
                ->orderBy('id')
                ->get();

            // Una remisión puede tener varias líneas del mismo artículo (talles/variantes).
            // Para AYALA / MODELO la unidad de decisión es el DOCUMENTO completo:
            // serie + número + destino. El mismo documento nunca puede repartirse
            // entre ambos planes.
            $documentos = $remisionesOriginales->groupBy(function ($remision) {
                return implode('|', [
                    (string) $remision->serie,
                    (string) $remision->numero_remision,
                    (string) $remision->cod_sucursal_salida,
                    (string) $remision->cod_sucursal_destino,
                ]);
            });

            /*
             * Una misma línea física hacia COMERCIAL MATRIZ no puede aparecer
             * simultáneamente en AYALA y MODELO MUESTRA. Primero respetamos el
             * vínculo persistido; después, para MATRIZ, consumimos cada remisión
             * una sola vez hasta completar la capacidad de AYALA/MODELO.
             */
            $detallesPorId = $detalles->keyBy('id');
            $capacidad = $detalles->mapWithKeys(function ($detalle) {
                return [(int) $detalle->id => max(0, (int) $detalle->cantidad)];
            })->all();
            $asignado = array_fill_keys(array_keys($capacidad), 0);
            $asignadas = [];

            foreach ($documentos as $lineasDocumento) {
                $primera = $lineasDocumento->first();
                $destinoReal = $primera->sucursal_destino ?: $primera->sucursal_logistica;
                $destinoNormalizado = $this->normalizarDestinoMovimiento($destinoReal);
                $cantidadDocumento = (int) $lineasDocumento->sum('cantidad');
                $idAsignado = null;

                // Para destinos normales, conservar el detalle persistido si es compatible.
                // Para COMERCIAL MATRIZ, decidir el documento completo entre AYALA/MODELO.
                if ($destinoNormalizado !== 'MATRIZ') {
                    $idDetalleActual = (int) $primera->id_logistica_detalle;
                    $detalleActual = $detallesPorId->get($idDetalleActual);

                    if ($detalleActual) {
                        $planActual = $this->normalizarDestinoMovimiento($detalleActual->sucursal);

                        if ($destinoNormalizado === $planActual
                            && (($asignado[$idDetalleActual] ?? 0) + $cantidadDocumento)
                                <= ($capacidad[$idDetalleActual] ?? 0)) {
                            $idAsignado = $idDetalleActual;
                        }
                    }
                } else {
                    // Prioridad: completar AYALA con documentos completos; una vez lleno,
                    // los documentos siguientes de MATRIZ pasan a MODELO MUESTRA.
                    foreach (['AYALA', 'MODELO'] as $planBuscado) {
                        foreach ($detalles as $candidato) {
                            $idCandidato = (int) $candidato->id;
                            $planCandidato = $this->normalizarDestinoMovimiento($candidato->sucursal);

                            if ($planCandidato !== $planBuscado) {
                                continue;
                            }

                            if ((($asignado[$idCandidato] ?? 0) + $cantidadDocumento)
                                <= ($capacidad[$idCandidato] ?? 0)) {
                                $idAsignado = $idCandidato;
                                break 2;
                            }
                        }
                    }
                }

                if (!$idAsignado) {
                    continue;
                }

                $asignado[$idAsignado] = ($asignado[$idAsignado] ?? 0) + $cantidadDocumento;

                foreach ($lineasDocumento as $remision) {
                    $remision->id_detalle_visual = $idAsignado;
                    $asignadas[] = $remision;
                }
            }

            $remisionesPorDetalle = collect($asignadas)->groupBy('id_detalle_visual');
        }

        foreach ($detalles as $detalle) {
            $planNormalizado = $this->normalizarDestinoMovimiento($detalle->sucursal);

            $detalle->remisiones = collect(
                $remisionesPorDetalle->get((int) $detalle->id, collect())
            )->map(function ($remision) use ($detalle, $planNormalizado) {
                $destinoReal = $remision->sucursal_destino ?: $remision->sucursal_logistica;
                $destinoNormalizado = $this->normalizarDestinoMovimiento($destinoReal);

                $remision->destino_planificado = $detalle->sucursal;
                $remision->destino_real = $destinoReal;
                $remision->es_redireccion = $destinoNormalizado !== $planNormalizado;

                return $remision;
            })->values();

            $detalle->cantidad_remitida = (int) $detalle->remisiones->sum('cantidad');
            $detalle->cantidad_recibida = (int) $detalle->remisiones
                ->filter(function ($remision) {
                    return !empty($remision->fecha_recepcion);
                })
                ->sum('cantidad');
            $detalle->cantidad_en_transito = max(
                0,
                $detalle->cantidad_remitida - $detalle->cantidad_recibida
            );
            $detalle->pendiente_remitir = max(
                0,
                (int) $detalle->cantidad - $detalle->cantidad_remitida
            );
        }

        $remisionesSinDetalle = collect();

        if (Schema::hasTable('ot_logistica_remisiones')) {
            $remisionesSinDetalle = DB::table('ot_logistica_remisiones')
                ->where('id_ot', $idOt)
                ->whereNull('id_logistica_detalle')
                ->orderByRaw('COALESCE(fecha_remision, fecha_creacion) ASC')
                ->orderBy('serie')
                ->orderBy('numero_remision')
                ->get();
        }

        $totalPlan = (int) $detalles->sum('cantidad');
        $totalRemitido = (int) $detalles->sum('cantidad_remitida');
        $totalRecibido = (int) $detalles->sum('cantidad_recibida');
        $totalEnTransito = (int) $detalles->sum('cantidad_en_transito');
        $totalPendiente = (int) $detalles->sum('pendiente_remitir');

        return view('control._terminacion_detalle', compact(
            'ot',
            'detalles',
            'remisionesSinDetalle',
            'totalPlan',
            'totalRemitido',
            'totalRecibido',
            'totalEnTransito',
            'totalPendiente'
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