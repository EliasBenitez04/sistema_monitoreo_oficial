<?php

namespace App\Support;

/**
 * Lecturas gerenciales derivadas; no alteran movimientos ni clasifican
 * una OT como cumplida dentro del plazo contractual.
 */
class ResumenGerencialProduccion
{
    public static function desviacionDiaria(?int $prendasDia, ?float $promedioAcumulado): ?float
    {
        if ($prendasDia === null || $promedioAcumulado === null || $promedioAcumulado <= 0) {
            return null;
        }
        return round(($prendasDia / $promedioAcumulado - 1) * 100, 1);
    }

    public static function avanceOtP(int $asociadas, int $pendientes): array
    {
        // Evitar porcentajes engañosos ante universos incompletos.
        if ($asociadas < 0 || $pendientes < 0 || $pendientes > $asociadas) {
            return ['con_hito' => null, 'porcentaje' => null];
        }
        $conHito = $asociadas - $pendientes;
        return [
            'con_hito' => $conHito,
            'porcentaje' => $asociadas > 0
                ? round(100 * $conHito / $asociadas, 1)
                : null,
        ];
    }
}
