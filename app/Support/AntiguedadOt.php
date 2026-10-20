<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Antigüedad documental de una OT, relativa a un corte explícito.
 *
 * "Sin movimiento" se mide SOLO desde la fecha del último registro.
 * "Espera P" comienza en la fecha más reciente entre el pedido y el
 * movimiento, para no contar como incumplimiento días anteriores al pedido.
 *
 * No supone que una fecha de hito IT equivale a la fecha actual.
 */
class AntiguedadOt
{
    public static function cortePendientesDelMes(Carbon $mes, ?Carbon $ahora = null): ?Carbon
    {
        $actual = $ahora ? $ahora->copy() : Carbon::now();
        $primeroDelMes = $mes->copy()->startOfMonth();
        $primeroActual = $actual->copy()->startOfMonth();

        if ($primeroDelMes->greaterThan($primeroActual)) {
            return null;
        }
        if ($primeroDelMes->equalTo($primeroActual)) {
            return $actual;
        }

        return $primeroDelMes->endOfMonth()->endOfDay();
    }

    public static function calcular($fechaUltimoMovimiento, $fechaPedido, Carbon $fechaCorte): array
    {
        $ultimo = $fechaUltimoMovimiento
            ? Carbon::parse($fechaUltimoMovimiento)
            : null;
        $pedido = $fechaPedido
            ? Carbon::parse($fechaPedido)->startOfDay()
            : null;

        $inicioEspera = $ultimo ? $ultimo->copy() : null;
        if ($pedido && (!$inicioEspera || $pedido->greaterThan($inicioEspera))) {
            $inicioEspera = $pedido->copy();
        }

        return [
            'dias_sin_movimiento' => self::dias($ultimo, $fechaCorte),
            'dias_espera_pedido' => self::dias($inicioEspera, $fechaCorte),
            'inicio_espera' => $inicioEspera,
            'tiene_movimiento' => $ultimo !== null,
        ];
    }

    public static function dias(?Carbon $desde, Carbon $hasta): ?float
    {
        if ($desde === null) {
            return null;
        }

        // En BD fecha_proceso puede ser DATE (00:00) o TIMESTAMP.
        // Misma precisión de minutos / 24 h que el detalle de la OT.
        return round(max(0, $desde->diffInMinutes($hasta, false)) / 1440, 2);
    }
}
