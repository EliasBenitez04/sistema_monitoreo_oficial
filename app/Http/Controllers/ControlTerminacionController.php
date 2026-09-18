<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControlTerminacionController extends Controller
{
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | FECHA SELECCIONADA
        |--------------------------------------------------------------------------
        |
        | Esta fecha representa SOLAMENTE la fecha en que la mercadería
        | pasó a PRODUCTO TERMINADO.
        |
        */

        $fechaDesde = $request->input(
            'fecha_desde',
            now()->format('Y-m-d')
        );

        $fechaHasta = $request->input(
            'fecha_hasta',
            now()->format('Y-m-d')
        );


        /*
        |--------------------------------------------------------------------------
        | PROCESOS
        |--------------------------------------------------------------------------
        */

        $procesoProductoTerminado =
            'TERMINACION - PRODUCTO TERMINADO';

        $procesoLogistica =
            'LOGISTICA - LOGISTICA Y DISTRIBUCION';


        /*
        |--------------------------------------------------------------------------
        | PRODUCCIÓN TERMINADA
        |--------------------------------------------------------------------------
        |
        | Primero obtenemos las OTs que terminaron en la fecha seleccionada.
        |
        | IMPORTANTE:
        | Acá NO buscamos todavía logística.
        |
        */

        $produccionTerminada = DB::table('ot_trazabilidad as tp')

            ->join(
                'ot as o',
                'o.id_ot',
                '=',
                'tp.id_ot'
            )

            ->where(
                'tp.proceso',
                $procesoProductoTerminado
            )

            ->whereBetween(
                'tp.fecha_proceso',
                [$fechaDesde, $fechaHasta]
            )

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

            ->orderBy('tp.fecha_proceso', 'asc')
            ->orderBy('o.nro_ot', 'asc')

            ->get();


        /*
        |--------------------------------------------------------------------------
        | BUSCAR TODA LA LOGÍSTICA DE CADA OT
        |--------------------------------------------------------------------------
        |
        | Una OT puede tener:
        |
        | 12/08 → 357
        | 26/08 →   1
        | 30/08 →   2
        |
        | Si produjo 360:
        |
        | 357 + 1 + 2 = 360
        |
        | Por eso NO buscamos una sola trazabilidad.
        |
        */

        foreach ($produccionTerminada as $item) {

            /*
            |--------------------------------------------------------------------------
            | TODAS LAS TRAZAS DE LOGÍSTICA DE LA OT
            |--------------------------------------------------------------------------
            */

            $logistica = DB::table('ot_trazabilidad as tl')

                ->where(
                    'tl.id_ot',
                    $item->id_ot
                )

                ->where(
                    'tl.proceso',
                    $procesoLogistica
                )

                /*
                |--------------------------------------------------------------------------
                | Solo logística posterior o igual a Producto Terminado.
                |--------------------------------------------------------------------------
                */

                ->whereDate(
                    'tl.fecha_proceso',
                    '>=',
                    $item->fecha_producto_terminado
                )

                ->select(
                    'tl.id_trazabilidad',
                    'tl.resultado',
                    'tl.fecha_proceso'
                )

                ->orderBy(
                    'tl.fecha_proceso'
                )

                ->orderBy(
                    'tl.id_trazabilidad'
                )

                ->get();


            /*
            |--------------------------------------------------------------------------
            | GUARDAMOS LAS TRAZAS
            |--------------------------------------------------------------------------
            */

            $item->logistica = $logistica;


            /*
            |--------------------------------------------------------------------------
            | SUMA TOTAL ENVIADA
            |--------------------------------------------------------------------------
            |
            | Acá está la parte importante.
            |
            | SUMAMOS TODOS LOS RESULTADOS DE LOGÍSTICA.
            |
            */

            $item->cantidad_enviada = $logistica->sum(
                'resultado'
            );


            /*
            |--------------------------------------------------------------------------
            | DIFERENCIA
            |--------------------------------------------------------------------------
            */

            $item->diferencia =
                $item->cantidad_terminada
                -
                $item->cantidad_enviada;


            /*
            |--------------------------------------------------------------------------
            | PRIMERA SALIDA
            |--------------------------------------------------------------------------
            */

            $item->primera_salida =
                $logistica->first()?->fecha_proceso;


            /*
            |--------------------------------------------------------------------------
            | ÚLTIMA SALIDA
            |--------------------------------------------------------------------------
            */

            $item->ultima_salida =
                $logistica->last()?->fecha_proceso;


            /*
            |--------------------------------------------------------------------------
            | DETALLE DE LOCALES
            |--------------------------------------------------------------------------
            |
            | Para cada trazabilidad de logística buscamos
            | sus locales.
            |
            */

            $detalle = collect();


            foreach ($logistica as $movimiento) {

                $locales = DB::table(
                    'ot_logistica_detalle'
                )

                    ->where(
                        'id_trazabilidad',
                        $movimiento->id_trazabilidad
                    )

                    ->select(
                        'id',
                        'id_ot',
                        'id_trazabilidad',
                        'sucursal',
                        'cantidad',
                        'created_at'
                    )

                    ->orderBy('created_at')
                    ->orderBy('id')

                    ->get();


                /*
                |--------------------------------------------------------------------------
                | AGREGAMOS LA FECHA DE LA TRAZA DE LOGÍSTICA
                |--------------------------------------------------------------------------
                */

                foreach ($locales as $local) {

                    $local->fecha_logistica =
                        $movimiento->fecha_proceso;
                }


                $detalle = $detalle->merge(
                    $locales
                );
            }


            /*
            |--------------------------------------------------------------------------
            | GUARDAMOS DETALLE
            |--------------------------------------------------------------------------
            */

            $item->detalle_logistica = $detalle;
        }


        /*
|--------------------------------------------------------------------------
| FILTRO POR ESTADO
|--------------------------------------------------------------------------
*/

        $estados = $request->input('estado', []);

        /*
| Normalizamos por si viene un solo valor
*/
        if (!is_array($estados)) {
            $estados = [$estados];
        }

        /*
| Calculamos el estado de cada OT
*/
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

        /*
|--------------------------------------------------------------------------
| APLICAR FILTRO
|--------------------------------------------------------------------------
*/

        if (count($estados) > 0) {

            $produccionTerminada = $produccionTerminada
                ->filter(function ($item) use ($estados) {

                    return in_array(
                        $item->estado_control,
                        $estados
                    );
                })
                ->values();
        }

        /*
        |--------------------------------------------------------------------------
        | DETALLE GENERAL DE LOGÍSTICA
        |--------------------------------------------------------------------------
        |
        | Unimos todos los detalles para poder mostrarlos
        | en la tabla inferior.
        |
        */

        $detalleLogistica = collect();


        foreach ($produccionTerminada as $item) {

            foreach ($item->detalle_logistica as $detalle) {

                $detalle->nro_ot =
                    $item->nro_ot;

                $detalle->codigo =
                    $item->codigo;

                $detalleLogistica->push(
                    $detalle
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | TOTALES
        |--------------------------------------------------------------------------
        */

        $totalTerminado =
            $produccionTerminada->sum(
                'cantidad_terminada'
            );


        $totalEnviado =
            $produccionTerminada->sum(
                'cantidad_enviada'
            );


        $totalDiferencia =
            $totalTerminado -
            $totalEnviado;


        /*
        |--------------------------------------------------------------------------
        | TOTAL OTs
        |--------------------------------------------------------------------------
        */

        $totalOTs =
            $produccionTerminada->count();


        /*
        |--------------------------------------------------------------------------
        | OTs COMPLETAS
        |--------------------------------------------------------------------------
        */

        $otsCompletas =
            $produccionTerminada
            ->filter(function ($item) {

                return $item->diferencia == 0;
            })
            ->count();


        /*
        |--------------------------------------------------------------------------
        | OTs PENDIENTES
        |--------------------------------------------------------------------------
        */

        $otsPendientes =
            $produccionTerminada
            ->filter(function ($item) {

                return $item->diferencia > 0;
            })
            ->count();


        /*
        |--------------------------------------------------------------------------
        | OTs NO ENVIADAS
        |--------------------------------------------------------------------------
        */

        $otsNoEnviadas =
            $produccionTerminada
            ->filter(function ($item) {

                return $item->cantidad_enviada == 0;
            })
            ->count();


        /*
        |--------------------------------------------------------------------------
        | PORCENTAJE ENVIADO
        |--------------------------------------------------------------------------
        */

        $porcentajeEnviado =
            $totalTerminado > 0

            ? round(
                (
                    $totalEnviado /
                    $totalTerminado
                ) * 100,
                2
            )

            : 0;


        /*
        |--------------------------------------------------------------------------
        | VISTA
        |--------------------------------------------------------------------------
        */

        return view(
            'control.terminacion',
            compact(
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
                'porcentajeEnviado'
            )
        );
    }
}
