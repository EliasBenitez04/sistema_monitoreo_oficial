<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Compara dos universos al mismo corte:
 *   - todas las OT cuya última etapa REGISTRADA es el área;
 *   - las OT todavía pendientes de pedidos P en esa misma etapa.
 *
 * Fuera de P no se rotula "sin pedido": también podría ser otro tipo
 * de pedido o una asociación anulada. Tampoco afirma presencia física.
 */
class ConteoEtapasPedido
{
    public static function comparar(Collection $pendientesP, Collection $totalesPorProceso): Collection
    {
        $totales = $totalesPorProceso
            ->groupBy(function ($item) {
                return UltimoProcesoOt::normalizar($item->proceso);
            })
            ->map(function ($items) {
                return (int) $items->sum('ots');
            });

        return $pendientesP->groupBy('etapa')->map(function ($items, $etapa) use ($totales) {
            $otsUnicas = $items->unique('nro_ot');
            $cantidadP = $otsUnicas->count();
            $clave = UltimoProcesoOt::normalizar($etapa);
            $sinProceso = $clave === UltimoProcesoOt::normalizar('SIN MOVIMIENTO REGISTRADO');
            $total = $sinProceso ? null : (int) $totales->get($clave, 0);
            $inconsistencia = $total !== null && $total < $cantidadP;

            return (object) [
                'etapa' => $etapa,
                'ots' => $cantidadP,
                'prendas' => (int) $otsUnicas->sum('cantidad_pendiente'),
                // null indica que el total NO puede respaldarse.
                'ots_en_area' => $inconsistencia ? null : $total,
                'ots_fuera_p' => $total !== null && !$inconsistencia
                    ? $total - $cantidadP : null,
                'conteo_inconsistente' => $inconsistencia,
            ];
        })->sortByDesc('ots')->values();
    }
}
