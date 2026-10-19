<?php

namespace App\Support;

class ConfirmacionTupi
{
    /**
     * Para TUPI, la fecha de emisión equivale a la confirmación.
     * Los demás destinos requieren fecha_recepcion.
     * No se alteran las fechas de los documentos importados.
     */
    public static function esTupi($remision): bool
    {
        return strtoupper(trim((string) ($remision->sucursal_logistica ?? ''))) === 'TUPI'
            || strtoupper(trim((string) ($remision->sucursal_destino ?? ''))) === 'TUPI';
    }

    public static function fecha($remision)
    {
        if (self::esTupi($remision) && !empty($remision->fecha_remision)) {
            return $remision->fecha_remision;
        }

        return $remision->fecha_recepcion ?? null;
    }

    public static function sql(string $alias = 'r'): string
    {
        return "(CASE WHEN (
            UPPER(TRIM(COALESCE({$alias}.sucursal_logistica, ''))) = 'TUPI'
            OR UPPER(TRIM(COALESCE({$alias}.sucursal_destino, ''))) = 'TUPI'
        ) AND {$alias}.fecha_remision IS NOT NULL
        THEN {$alias}.fecha_remision ELSE {$alias}.fecha_recepcion END)";
    }
}
