<?php

namespace App\Http\Controllers;

use App\Imports\SeguimientoPedidoImport;
use App\Models\SeguimientoPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class SeguimientoPedidoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ot dashboard');
    }

    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $query = SeguimientoPedido::query()
            ->withCount('detalles')
            ->select('seguimiento_pedido.*')
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
                    ->whereNotNull('r.fecha_recepcion')
                    ->whereRaw("UPPER(TRIM(COALESCE(r.sucursal_logistica, r.sucursal_destino, ''))) NOT IN ('CASA CENTRAL', 'MATRIZ')")
                    ->selectRaw('MIN(r.fecha_recepcion)');
            }, 'primera_confirmacion')
            ->selectSub(function ($q) {
                $q->from('seguimiento_pedido_detalle as spd')
                    ->join('ot_logistica_remisiones as r', 'r.id_ot', '=', 'spd.id_ot')
                    ->whereColumn('spd.seguimiento_pedido_id', 'seguimiento_pedido.id')
                    ->whereNotNull('r.fecha_recepcion')
                    ->whereRaw("UPPER(TRIM(COALESCE(r.sucursal_logistica, r.sucursal_destino, ''))) NOT IN ('CASA CENTRAL', 'MATRIZ')")
                    ->selectRaw('MIN(r.fecha_recepcion)');
            }, 'ultima_confirmacion')
            ->orderByDesc('id');

        if ($buscar !== '') {
            $query->where('nro_pedido', 'ILIKE', '%' . $buscar . '%');
        }

        $pedidos = $query->paginate(30)->appends($request->query());

        $pedidos->getCollection()->transform(function ($pedido) {
            $inicio = $pedido->fecha_pedido ? Carbon::parse($pedido->fecha_pedido)->startOfDay() : null;
            $fin = $pedido->ultima_confirmacion ? Carbon::parse($pedido->ultima_confirmacion)->startOfDay() : null;

            $completo = (int) $pedido->cantidad_total > 0
                && (int) $pedido->confirmado >= (int) $pedido->cantidad_total;

            $pedido->dias_confirmacion = ($inicio && $fin && $completo)
                ? $inicio->diffInDays($fin, false)
                : null;
            $pedido->dias_transcurridos = ($inicio && !$completo)
                ? $inicio->diffInDays(Carbon::today(), false)
                : null;

            return $pedido;
        });

        $resumenBase = (clone $query)->get();

        $totalPedidos = $resumenBase->count();
        $totalPrendas = (int) $resumenBase->sum('cantidad_total');
        $totalPt = (int) $resumenBase->sum(function ($p) {
            return min((int) $p->cantidad_total, (int) $p->producto_terminado);
        });
        $totalConfirmado = (int) $resumenBase->sum(function ($p) {
            return min((int) $p->cantidad_total, (int) $p->confirmado);
        });
        $completos = $resumenBase->filter(function ($p) {
            return (int) $p->cantidad_total > 0 && (int) $p->confirmado >= (int) $p->cantidad_total;
        });
        $enCurso = $resumenBase->reject(function ($p) {
            return (int) $p->cantidad_total > 0 && (int) $p->confirmado >= (int) $p->cantidad_total;
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
            'ots' => (int) $resumenBase->sum('detalles_count'),
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

    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        $import = new SeguimientoPedidoImport();
        Excel::import($import, $request->file('archivo'));

        $mensaje = 'Importación finalizada. Filas: ' . $import->procesadas
            . ' | OT vinculadas: ' . $import->vinculadas
            . ' | OT no encontradas: ' . count($import->noEncontradas) . '.';

        if (!empty($import->noEncontradas)) {
            $mensaje .= ' No encontradas: ' . implode(', ', array_slice(array_unique($import->noEncontradas), 0, 20));
        }

        return back()->with('success', $mensaje);
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
                $movimientosRemision = DB::table('ot_logistica_remisiones')
                    ->whereIn('id_ot', $idsOt)
                    ->select(
                        'id_ot', 'fecha_remision', 'fecha_recepcion',
                        'sucursal_salida', 'sucursal_destino', 'sucursal_logistica',
                        'cod_sucursal_salida', 'cod_sucursal_destino',
                        'serie', 'numero_remision', 'cantidad'
                    )
                    ->orderBy('fecha_remision')
                    ->orderBy('id')
                    ->get()
                    ->groupBy('id_ot');

                $remisiones = DB::table('ot_logistica_remisiones')
                    ->whereIn('id_ot', $idsOt)
                    ->select(
                        'id_ot',
                        'sucursal_logistica',
                        'sucursal_destino',
                        'cod_sucursal_destino',
                        DB::raw('SUM(cantidad) as enviado'),
                        DB::raw('SUM(CASE WHEN fecha_recepcion IS NOT NULL THEN cantidad ELSE 0 END) as recibido'),
                        DB::raw('MIN(fecha_remision) as primera_remision'),
                        DB::raw('MAX(fecha_remision) as ultima_remision'),
                        DB::raw('MIN(fecha_recepcion) as primera_recepcion'),
                        DB::raw('MAX(fecha_recepcion) as ultima_recepcion')
                    )
                    ->groupBy('id_ot', 'sucursal_logistica', 'sucursal_destino', 'cod_sucursal_destino')
                    ->get()
                    ->groupBy('id_ot');
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
                $m->tipo_movimiento = in_array($origenNorm, ['CASA CENTRAL', 'MATRIZ'], true)
                    ? 'DESPACHO CENTRAL'
                    : 'REDISTRIBUCION';
                return $m;
            })->values();
            $ot->cantidad_movimientos = $ot->movimientos_detalle->count();

            // Los 12 locales comerciales se controlan separados del canal Mayorista/Depósito.
            // CASA CENTRAL y MATRIZ son nodos del canal mayorista y no deben inflar el contador de locales.
            $esMayorista = function ($local) {
                $nombre = strtoupper(trim((string) $local->local));
                return in_array($nombre, ['CASA CENTRAL', 'MATRIZ'], true);
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
            } elseif ($ot->distribuido <= 0) {
                $ot->etapa_actual = 'PRODUCTO TERMINADO';
                $ot->etapa_numero = 2;
                $ot->estado_seguimiento = 'TERMINADO';
            } elseif ($ot->movimientos_fisicos <= 0) {
                $ot->etapa_actual = 'LOGISTICA';
                $ot->etapa_numero = 3;
                $ot->estado_seguimiento = 'EN LOGISTICA';
            } elseif ($ot->recibido < $ot->enviado) {
                $ot->etapa_actual = 'REMISION';
                $ot->etapa_numero = 4;
                $ot->estado_seguimiento = $ot->recibido > 0 ? 'RECEPCION PARCIAL' : 'EN TRANSITO';
            } else {
                $ot->etapa_actual = 'RECEPCION LOCAL';
                $ot->etapa_numero = 5;
                $ot->estado_seguimiento = 'COMPLETO';
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
                $destino = strtoupper(trim((string) (($mov->sucursal_logistica ?? null) ?: ($mov->sucursal_destino ?? null))));

                return in_array($origen, ['CASA CENTRAL', 'MATRIZ'], true)
                    && !in_array($destino, ['CASA CENTRAL', 'MATRIZ', ''], true);
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
                    $destino = strtoupper(trim((string) (($mov->sucursal_logistica ?? null) ?: ($mov->sucursal_destino ?? null))));

                    return in_array($origen, ['CASA CENTRAL', 'MATRIZ'], true)
                        && !in_array($destino, ['CASA CENTRAL', 'MATRIZ', ''], true)
                        && !empty($mov->fecha_remision)
                        && Carbon::parse($mov->fecha_remision)->startOfDay()->lt($fechaPedido);
                });

                $ot->kpi_estado = ($otDisponiblePreviamente && $tuvoDespachoAnterior)
                    ? 'DISTRIBUIDA ANTES DEL PEDIDO'
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
                    $ot->kpi_estado = 'DESPACHADO SIN CONFIRMAR';
                } else {
                    $ot->kpi_estado = $otDisponiblePreviamente
                        ? 'CONFIRMADO - OT DISPONIBLE'
                        : 'CONFIRMADO';

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
            'ots_confirmadas_kpi' => $recepcionesValidas->count(),
            'ots_distribuidas_antes_pedido' => $kpisOt->where('estado', 'DISTRIBUIDA ANTES DEL PEDIDO')->count(),
            'movimientos_anteriores_omitidos' => $movimientosAnteriores,
            'dias_transcurridos' => ($fechaPedido && $recepcionesValidas->isEmpty())
                ? $fechaPedido->diffInDays(Carbon::today(), false)
                : null,
        ];

        return view('seguimiento_pedidos.show', compact('pedido', 'ots', 'resumen'));
    }
}
