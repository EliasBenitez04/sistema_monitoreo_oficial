<?php

namespace App\Support;

/**
 * Saldo físico de una OT: solo documentos originales desde Central
 * y devoluciones efectivamente recibidas. Una redistribución entre
 * sucursales no crea una nueva prenda ni una nueva salida desde Central.
 */
class SaldoLogisticoOt
{
    public static function calcular(
        int $productoTerminado,
        int $remitidoOriginal,
        int $confirmadoOriginal,
        int $devolucionesRecibidas
    ): array {
        $pt = max(0, $productoTerminado);
        $remitido = max(0, $remitidoOriginal);
        $confirmado = min($remitido, max(0, $confirmadoOriginal));
        $retornos = min($remitido, max(0, $devolucionesRecibidas));

        $remitidoNeto = $remitido - $retornos;
        $confirmadoNeto = max(0, $confirmado - min($confirmado, $retornos));

        $remitidoEfectivo = min($pt, $remitidoNeto);
        $recibidoEfectivo = min($remitidoEfectivo, $confirmadoNeto);

        return [
            'retorno_aplicado' => $retornos,
            'remitido_neto' => $remitidoNeto,
            'confirmado_neto' => $confirmadoNeto,
            'remitido_efectivo' => $remitidoEfectivo,
            'recibido_efectivo' => $recibidoEfectivo,
            'pendiente_remitir' => max(0, $pt - $remitidoEfectivo),
            'en_transito' => max(0, $remitidoEfectivo - $recibidoEfectivo),
        ];
    }
}
