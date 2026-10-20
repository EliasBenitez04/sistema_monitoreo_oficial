<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Recalcula únicamente las duraciones, SIN cambiar el orden del flujo de
 * producción definido por el dashboard. El controlador entrega las filas
 * ya ordenadas por proceso y fecha (y por ID en caso de empate).
 *
 * Cada fila mide desde su fecha hasta la fecha de la siguiente fila del
 * flujo; la última llega hasta hoy. Nunca se guarda el tiempo en BD.
 */
class TiemposTrazabilidadOt
{
    public static function calcular(Collection $trazas, ?Carbon $ahora = null): Collection
    {
        $corte = $ahora ? $ahora->copy() : Carbon::now();
        $ordenadas = $trazas->values(); // Conservar exactamente el flujo recibido.

        return $ordenadas->map(function ($traza, $indice) use ($ordenadas, $corte) {
            $fecha = Carbon::parse($traza->fecha_proceso);
            $anterior = $indice > 0
                ? Carbon::parse($ordenadas[$indice - 1]->fecha_proceso)
                : null;
            $siguiente = $ordenadas->get($indice + 1);
            $esUltimo = $siguiente === null;
            $hasta = $esUltimo ? $corte : Carbon::parse($siguiente->fecha_proceso);

            // Puede existir una fecha cargada fuera de secuencia, por
            // ejemplo DISEÑO GRÁFICO 02/09 y TIZADAS 31/08 en la misma OT.
            // Eso no representa -2 ni +2 días reales: se debe revisar el
            // dato y NO inventar una duración positiva tomando el absoluto.
            $fechaInvertida = $hasta->lessThan($fecha);
            $horas = !$fechaInvertida
                ? round($fecha->diffInMinutes($hasta) / 60, 2)
                : 0.0;
            $horasAnterior = $anterior && !$fecha->lessThan($anterior)
                ? round($anterior->diffInMinutes($fecha) / 60, 2)
                : 0.0;

            return [
                'traza' => $traza,
                'fecha' => $fecha,
                'fecha_hasta' => $hasta->copy(),
                'es_ultimo' => $esUltimo,
                'fecha_invertida' => $fechaInvertida,
                'fecha_futura' => $fecha->greaterThan($corte),
                'duracion_horas' => $horas,
                'duracion_dias' => round($horas / 24, 2),
                'desde_anterior_horas' => $horasAnterior,
                'desde_anterior_dias' => round($horasAnterior / 24, 2),
            ];
        });
    }
}
