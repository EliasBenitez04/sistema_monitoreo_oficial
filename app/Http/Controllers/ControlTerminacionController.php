<?php

namespace App\Http\Controllers;

use App\Imports\ControlTerminacionRemisionImport;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class ControlTerminacionController extends Controller
{
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

        $entradaPorOt = collect();

        if ($idsOt->isNotEmpty()) {
            $entradaPorOt = DB::table('ot_trazabilidad')
                ->whereIn('id_ot', $idsOt->all())
                ->where('proceso', $procesoEntradaTerminacion)
                ->groupBy('id_ot')
                ->select(
                    'id_ot',
                    DB::raw('SUM(resultado) as cantidad_ingreso_terminacion'),
                    DB::raw('MIN(fecha_proceso) as primera_fecha_ingreso'),
                    DB::raw('MAX(fecha_proceso) as ultima_fecha_ingreso')
                )
                ->get()
                ->keyBy('id_ot');
        }

        // Seguimiento general posterior al PT. El rango de fechas selecciona las OTs por
        // fecha de PRODUCTO TERMINADO; sus movimientos posteriores se siguen completos,
        // aunque hayan ocurrido fuera del rango seleccionado.
        $logisticaPorOt = collect();
        $remisionesPorOt = collect();

        if ($idsOt->isNotEmpty()) {
            $logisticaPorOt = DB::table('ot_trazabilidad')
                ->whereIn('id_ot', $idsOt->all())
                ->where('proceso', 'LOGISTICA - LOGISTICA Y DISTRIBUCION')
                ->groupBy('id_ot')
                ->select(
                    'id_ot',
                    DB::raw('SUM(resultado) as cantidad_logistica'),
                    DB::raw('MIN(fecha_proceso) as primera_fecha_logistica'),
                    DB::raw('MAX(fecha_proceso) as ultima_fecha_logistica')
                )
                ->get()->keyBy('id_ot');

            if (Schema::hasTable('ot_logistica_remisiones')) {
                $remisionesPorOt = DB::table('ot_logistica_remisiones')
                    ->whereIn('id_ot', $idsOt->all())
                    ->groupBy('id_ot')
                    ->select(
                        'id_ot',
                        DB::raw('SUM(cantidad) as movimiento_fisico'),
                        DB::raw('SUM(CASE WHEN fecha_recepcion IS NOT NULL THEN cantidad ELSE 0 END) as movimiento_recibido'),
                        DB::raw('MIN(fecha_remision) as primera_remision'),
                        DB::raw('MAX(fecha_remision) as ultima_remision'),
                        DB::raw('MAX(fecha_recepcion) as ultima_recepcion')
                    )
                    ->get()->keyBy('id_ot');
            }
        }

        foreach ($produccionTerminada as $item) {
            $entrada = $entradaPorOt->get($item->id_ot);

            $item->cantidad_terminada = (int) $item->cantidad_terminada;
            $item->cantidad_ingreso_terminacion = (int) ($entrada->cantidad_ingreso_terminacion ?? 0);
            $item->primera_fecha_ingreso = $entrada->primera_fecha_ingreso ?? null;
            $item->ultima_fecha_ingreso = $entrada->ultima_fecha_ingreso ?? null;

            // Producto Terminado ya es la entrega/entrada a Logística.
            $item->cantidad_entregada_logistica = $item->cantidad_terminada;

            $log = $logisticaPorOt->get($item->id_ot);
            $rem = $remisionesPorOt->get($item->id_ot);
            $tope = max(0, (int) $item->cantidad_orden);

            $item->cantidad_logistica = (int) ($log->cantidad_logistica ?? 0);
            $item->primera_fecha_logistica = $log->primera_fecha_logistica ?? null;
            $item->ultima_fecha_logistica = $log->ultima_fecha_logistica ?? null;
            $item->movimiento_fisico = (int) ($rem->movimiento_fisico ?? 0);
            $item->movimiento_recibido = (int) ($rem->movimiento_recibido ?? 0);
            $item->remitido_efectivo = min($tope, $item->movimiento_fisico);
            $item->recibido_efectivo = min($tope, $item->movimiento_recibido);
            $item->movimientos_adicionales = max(0, $item->movimiento_fisico - $tope);
            $item->primera_remision = $rem->primera_remision ?? null;
            $item->ultima_remision = $rem->ultima_remision ?? null;
            $item->ultima_recepcion = $rem->ultima_recepcion ?? null;

            if ($item->cantidad_terminada < max(1, $item->cantidad_ingreso_terminacion)) {
                $item->etapa_actual = 'TERMINACION';
                $item->etapa_numero = 1;
            } elseif ($item->cantidad_logistica <= 0) {
                $item->etapa_actual = 'PRODUCTO TERMINADO';
                $item->etapa_numero = 2;
            } elseif ($item->movimiento_fisico <= 0) {
                $item->etapa_actual = 'LOGISTICA';
                $item->etapa_numero = 3;
            } elseif ($item->recibido_efectivo < $item->remitido_efectivo) {
                $item->etapa_actual = 'REMISION';
                $item->etapa_numero = 4;
            } else {
                $item->etapa_actual = 'RECEPCION LOCAL';
                $item->etapa_numero = 5;
            }
            $item->porcentaje_flujo = (int) round(($item->etapa_numero / 5) * 100);

            $item->pendiente_terminar = max(
                0,
                $item->cantidad_ingreso_terminacion - $item->cantidad_terminada
            );

            $item->exceso_producto_terminado = max(
                0,
                $item->cantidad_terminada - $item->cantidad_ingreso_terminacion
            );

            $item->dias_en_terminacion = null;

            if ($item->primera_fecha_ingreso) {
                $fechaIngreso = \Carbon\Carbon::parse($item->primera_fecha_ingreso)->startOfDay();

                if ($item->pendiente_terminar > 0) {
                    $item->dias_en_terminacion = max(
                        0,
                        $fechaIngreso->diffInDays($hoy, false)
                    );
                } else {
                    $fechaSalida = \Carbon\Carbon::parse(
                        $item->ultima_fecha_producto_terminado
                    )->startOfDay();

                    $item->dias_en_terminacion = max(
                        0,
                        $fechaIngreso->diffInDays($fechaSalida, false)
                    );
                }
            }

            if ($item->cantidad_ingreso_terminacion <= 0) {
                $item->estado_control = 'SIN INGRESO';
            } elseif ($item->cantidad_terminada < $item->cantidad_ingreso_terminacion) {
                $item->estado_control = 'PARCIAL';
            } elseif ($item->cantidad_terminada > $item->cantidad_ingreso_terminacion) {
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

        $totalIngresoTerminacion = (int) $produccionTerminada->sum('cantidad_ingreso_terminacion');
        $totalTerminado = (int) $produccionTerminada->sum('cantidad_terminada');

        /*
         * KPIs del flujo físico, sin alterar la lógica de seguimiento:
         * Terminación = ingreso al área.
         * Producto Terminado = salida de Terminación / entrada a Logística.
         * Recepción Local = unidades efectivamente confirmadas por fecha_recepcion.
         */
        $totalRecepcionLocal = (int) $produccionTerminada->sum('recibido_efectivo');
        $totalPendienteEnvio = (int) $produccionTerminada->sum(function ($item) {
            return max(0, (int) $item->cantidad_terminada - (int) $item->remitido_efectivo);
        });
        $otsPendientesEnvio = $produccionTerminada->filter(function ($item) {
            return (int) $item->cantidad_terminada > (int) $item->remitido_efectivo;
        })->count();

        // Mismo valor por definición del flujo.
        $totalEntregadoLogistica = $totalTerminado;

        $totalPendienteTerminar = (int) $produccionTerminada->sum('pendiente_terminar');
        $totalExcesoProductoTerminado = (int) $produccionTerminada->sum('exceso_producto_terminado');
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
            'totalPendienteEnvio',
            'otsPendientesEnvio',
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

    public function reportePendientesEnvio(Request $request)
    {
        $fechaDesde = $request->input('fecha_desde', now()->startOfMonth()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));
        $buscar = trim((string) $request->input('buscar', ''));

        $ots = DB::table('ot_trazabilidad as pt')
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
            ->groupBy('o.id_ot', 'o.nro_ot', 'o.codigo', 'o.descripcion', 'o.cantidad_orden')
            ->select(
                'o.id_ot', 'o.nro_ot', 'o.codigo', 'o.descripcion', 'o.cantidad_orden',
                DB::raw('SUM(pt.resultado) as cantidad_pt'),
                DB::raw('MIN(pt.fecha_proceso) as fecha_pt')
            )
            ->get();

        $ids = $ots->pluck('id_ot')->all();
        $remisiones = collect();

        if (!empty($ids) && Schema::hasTable('ot_logistica_remisiones')) {
            $remisiones = DB::table('ot_logistica_remisiones')
                ->whereIn('id_ot', $ids)
                ->where(function ($q) {
                    $q->where('cod_sucursal_salida', 1)
                        ->orWhereRaw("UPPER(TRIM(COALESCE(sucursal_salida, ''))) = 'CASA CENTRAL'");
                })
                ->groupBy('id_ot')
                ->select(
                    'id_ot',
                    DB::raw('SUM(cantidad) as enviado'),
                    DB::raw('MIN(COALESCE(fecha_remision, fecha_creacion)) as primer_envio'),
                    DB::raw('MAX(COALESCE(fecha_remision, fecha_creacion)) as ultimo_envio')
                )
                ->get()->keyBy('id_ot');
        }

        $reporte = $ots->map(function ($ot) use ($remisiones) {
            $mov = $remisiones->get($ot->id_ot);
            $ot->cantidad_pt = (int) $ot->cantidad_pt;
            $ot->enviado = min($ot->cantidad_pt, (int) ($mov->enviado ?? 0));
            $ot->pendiente_envio = max(0, $ot->cantidad_pt - $ot->enviado);
            $ot->primer_envio = $mov->primer_envio ?? null;
            $ot->ultimo_envio = $mov->ultimo_envio ?? null;
            $ot->dias_desde_pt = $ot->pendiente_envio > 0
                ? max(0, \Carbon\Carbon::parse($ot->fecha_pt)->startOfDay()->diffInDays(now()->startOfDay(), false))
                : 0;
            $ot->dias_desde_ultimo_envio = $ot->pendiente_envio > 0 && $ot->ultimo_envio
                ? max(0, \Carbon\Carbon::parse($ot->ultimo_envio)->startOfDay()->diffInDays(now()->startOfDay(), false))
                : null;
            return $ot;
        })->filter(function ($ot) {
            return $ot->pendiente_envio > 0;
        })->sortByDesc('dias_desde_ultimo_envio')->values();

        return view('control.reporte_pendientes_envio', compact(
            'fechaDesde', 'fechaHasta', 'buscar', 'reporte'
        ));
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