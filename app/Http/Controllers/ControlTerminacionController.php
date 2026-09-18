<?php

namespace App\Http\Controllers;

use App\Imports\ControlTerminacionRemisionImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class ControlTerminacionController extends Controller
{
    public function index(Request $request)
    {
        $fechaDesde = $request->input('fecha_desde', now()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));

        $procesoProductoTerminado = 'TERMINACION - PRODUCTO TERMINADO';
        $procesoLogistica = 'LOGISTICA - LOGISTICA Y DISTRIBUCION';

        $tablaRemisionesDisponible = Schema::hasTable('ot_logistica_remisiones');

        $produccionTerminada = DB::table('ot_trazabilidad as tp')
            ->join('ot as o', 'o.id_ot', '=', 'tp.id_ot')
            ->where('tp.proceso', $procesoProductoTerminado)
            ->whereBetween('tp.fecha_proceso', [$fechaDesde, $fechaHasta])
            ->select(
                'tp.id_trazabilidad as id_trazabilidad_producto',
                'tp.id_ot',
                'tp.fecha_proceso as fecha_producto_terminado',
                'tp.resultado as cantidad_terminada',
                'o.nro_ot',
                'o.codigo',
                'o.descripcion',
                'o.cantidad_orden',
                'o.estado'
            )
            ->orderBy('tp.fecha_proceso')
            ->orderBy('o.nro_ot')
            ->get();

        foreach ($produccionTerminada as $item) {
            $logistica = DB::table('ot_trazabilidad as tl')
                ->where('tl.id_ot', $item->id_ot)
                ->where('tl.proceso', $procesoLogistica)
                ->whereDate('tl.fecha_proceso', '>=', $item->fecha_producto_terminado)
                ->select('tl.id_trazabilidad', 'tl.resultado', 'tl.fecha_proceso')
                ->orderBy('tl.fecha_proceso')
                ->orderBy('tl.id_trazabilidad')
                ->get();

            $item->logistica = $logistica;
            $item->cantidad_enviada = (int) $logistica->sum('resultado');
            $item->diferencia = (int) $item->cantidad_terminada - $item->cantidad_enviada;
            $item->primera_salida = optional($logistica->first())->fecha_proceso;
            $item->ultima_salida = optional($logistica->last())->fecha_proceso;

            $detalle = collect();

            foreach ($logistica as $movimiento) {
                $locales = DB::table('ot_logistica_detalle')
                    ->where('id_trazabilidad', $movimiento->id_trazabilidad)
                    ->select('id', 'id_ot', 'id_trazabilidad', 'sucursal', 'cantidad', 'created_at')
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->get();

                foreach ($locales as $local) {
                    $local->fecha_logistica = $movimiento->fecha_proceso;

                    $remisiones = collect();

                    if ($tablaRemisionesDisponible) {
                        $remisiones = DB::table('ot_logistica_remisiones')
                            ->where('id_logistica_detalle', $local->id)
                            ->orderByRaw('COALESCE(fecha_remision, fecha_creacion) ASC')
                            ->orderBy('serie')
                            ->orderBy('numero_remision')
                            ->get();
                    }

                    $local->remisiones = $remisiones;
                    $local->cantidad_remitida = (int) $remisiones->sum('cantidad');
                    $local->cantidad_recibida = (int) $remisiones
                        ->filter(function ($remision) {
                            return !empty($remision->fecha_recepcion);
                        })
                        ->sum('cantidad');

                    if ($remisiones->isEmpty()) {
                        $local->estado_confirmacion = 'SIN REMISION';
                    } elseif ($remisiones->every(function ($remision) {
                        return !empty($remision->fecha_recepcion);
                    })) {
                        $local->estado_confirmacion = 'RECIBIDO';
                    } else {
                        $local->estado_confirmacion = 'EN TRANSITO';
                    }
                }

                $detalle = $detalle->merge($locales);
            }

            $item->detalle_logistica = $detalle;

            if ($detalle->isEmpty()) {
                $item->confirmacion_local = 'SIN ENVIOS';
            } elseif ($detalle->every(function ($local) {
                return $local->estado_confirmacion === 'RECIBIDO';
            })) {
                $item->confirmacion_local = 'RECIBIDO';
            } elseif ($detalle->contains(function ($local) {
                return $local->estado_confirmacion === 'RECIBIDO';
            })) {
                $item->confirmacion_local = 'PARCIAL';
            } elseif ($detalle->contains(function ($local) {
                return $local->estado_confirmacion === 'EN TRANSITO';
            })) {
                $item->confirmacion_local = 'EN TRANSITO';
            } else {
                $item->confirmacion_local = 'SIN REMISION';
            }
        }

        $estados = $request->input('estado', []);

        if (!is_array($estados)) {
            $estados = [$estados];
        }

        $produccionTerminada = $produccionTerminada->map(function ($item) {
            if ($item->diferencia == 0) {
                $item->estado_control = 'FINALIZADO';
            } elseif ($item->cantidad_enviada == 0) {
                $item->estado_control = 'NO ENVIADO';
            } else {
                $item->estado_control = 'PARCIAL';
            }

            return $item;
        });

        if (count($estados) > 0) {
            $produccionTerminada = $produccionTerminada
                ->filter(function ($item) use ($estados) {
                    return in_array($item->estado_control, $estados);
                })
                ->values();
        }

        $detalleLogistica = collect();

        foreach ($produccionTerminada as $item) {
            foreach ($item->detalle_logistica as $detalle) {
                $detalle->nro_ot = $item->nro_ot;
                $detalle->codigo = $item->codigo;
                $detalle->descripcion = $item->descripcion;
                $detalleLogistica->push($detalle);
            }
        }

        $totalTerminado = (int) $produccionTerminada->sum('cantidad_terminada');
        $totalEnviado = (int) $produccionTerminada->sum('cantidad_enviada');
        $totalDiferencia = $totalTerminado - $totalEnviado;
        $totalOTs = $produccionTerminada->count();

        $otsCompletas = $produccionTerminada
            ->filter(function ($item) {
                return $item->diferencia == 0;
            })
            ->count();

        $otsPendientes = $produccionTerminada
            ->filter(function ($item) {
                return $item->diferencia > 0;
            })
            ->count();

        $otsNoEnviadas = $produccionTerminada
            ->filter(function ($item) {
                return $item->cantidad_enviada == 0;
            })
            ->count();

        $porcentajeEnviado = $totalTerminado > 0
            ? round(($totalEnviado / $totalTerminado) * 100, 2)
            : 0;

        $totalRemisiones = 0;
        $remisionesRecibidas = 0;
        $remisionesEnTransito = 0;
        $detallesSinRemision = 0;

        foreach ($detalleLogistica as $detalle) {
            if ($detalle->remisiones->isEmpty()) {
                $detallesSinRemision++;
                continue;
            }

            foreach ($detalle->remisiones as $remision) {
                $totalRemisiones++;

                if (!empty($remision->fecha_recepcion)) {
                    $remisionesRecibidas++;
                } else {
                    $remisionesEnTransito++;
                }
            }
        }

        $remisionesSinVincular = 0;

        if ($tablaRemisionesDisponible) {
            $remisionesSinVincular = DB::table('ot_logistica_remisiones')
                ->whereNull('id_logistica_detalle')
                ->count();
        }

        return view('control.terminacion', compact(
            'fechaDesde',
            'fechaHasta',
            'produccionTerminada',
            'detalleLogistica',
            'totalTerminado',
            'totalEnviado',
            'totalDiferencia',
            'totalOTs',
            'otsCompletas',
            'otsPendientes',
            'otsNoEnviadas',
            'porcentajeEnviado',
            'tablaRemisionesDisponible',
            'totalRemisiones',
            'remisionesRecibidas',
            'remisionesEnTransito',
            'detallesSinRemision',
            'remisionesSinVincular'
        ));
    }

    public function importarRemisiones(Request $request)
    {
        $request->validate([
            'archivo_envios' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        if (!Schema::hasTable('ot_logistica_remisiones')) {
            return redirect()
                ->route('control.terminacion', $request->only('fecha_desde', 'fecha_hasta'))
                ->with('error', 'Primero ejecutá php artisan migrate para crear la tabla de remisiones.');
        }

        try {
            $import = new ControlTerminacionRemisionImport();

            Excel::import($import, $request->file('archivo_envios'));

            $mensaje = 'Importación finalizada. '
                . 'Nuevas: ' . $import->getInsertadas()
                . ' | Actualizadas: ' . $import->getActualizadas()
                . ' | Sin vínculo logístico: ' . $import->getSinVincular()
                . ' | Omitidas: ' . $import->getOmitidas();

            return redirect()
                ->route('control.terminacion', $request->only('fecha_desde', 'fecha_hasta'))
                ->with('success', $mensaje);
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('control.terminacion', $request->only('fecha_desde', 'fecha_hasta'))
                ->with('error', 'No se pudo importar el archivo: ' . $e->getMessage());
        }
    }
}
