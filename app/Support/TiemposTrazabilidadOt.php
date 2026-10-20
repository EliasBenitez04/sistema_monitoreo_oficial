<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Intervalos calculados desde la trazabilidad ACTUAL de una OT.
 *
 * El orden de operaciones de producción no siempre coincide con la fecha
 * ingresada: para medir tiempo no se puede ordenar por avance del flujo.
 * Ante fechas iguales se utiliza id_trazabilidad para un orden estable.
 *
 * Duración de una etapa = su fecha -> próxima fecha cronológica,
 * o fecha actual si es la última. Distancia desde proceso anterior
 * se informa separadamente para no confundir dos métricas distintas.
 */
class TiemposTrazabilidadOt
{
    public static function calcular(Collection $trazas, ?Carbon $ahora = null): Collection
    {
        $corte = $ahora ? $ahora->copy() : Carbon::now();

        $ordenadas = $trazas->sort(function ($a, $b) {
            $fechaA = Carbon::parse($a->fecha_proceso);
            $fechaB = Carbon::parse($b->fecha_proceso);
            $cmp = $fechaA->getTimestamp() <=> $fechaB->getTimestamp();

            if ($cmp !== 0) {
                return $cmp;
            }

            return ((int) $a->id_trazabilidad) <=> ((int) $b->id_trazabilidad);
        })->values();

        return $ordenadas->map(function ($traza, $indice) use ($ordenadas, $corte) {
            $fecha = Carbon::parse($traza->fecha_proceso);
            $anterior = $indice > 0
                ? Carbon::parse($ordenadas[$indice - 1]->fecha_proceso)
                : null;
            $siguiente = $ordenadas->get($indice + 1);
            $esUltimo = $siguiente === null;
            $hasta = $esUltimo ? $corte : Carbon::parse($siguiente->fecha_proceso);

            // Una fecha futura no produce tiempos negativos.
            $horas = $hasta->greaterThan($fecha)
                ? round($fecha->diffInMinutes($hasta) / 60, 2)
                : 0.0;
            $horasDesdeAnterior = $anterior && $fecha->greaterThan($anterior)
                ? round($anterior->diffInMinutes($fecha) / 60, 2)
                : 0.0;

            return [
                'traza' => $traza,
                'fecha' => $fecha,
                'fecha_hasta' => $hasta->copy(),
                'es_ultimo' => $esUltimo,
                'fecha_futura' => $fecha->greaterThan($corte),
                'duracion_horas' => $horas,
                'duracion_dias' => round($horas / 24, 2),
                'desde_anterior_horas' => $horasDesdeAnterior,
                'desde_anterior_dias' => round($horasDesdeAnterior / 24, 2),
            ];
        });
    }
}
