<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Fotografía documental del último proceso por OT y su asociación de pedido.
 *
 * Universo A: toda OT con una etapa vigente registrada.
 * Universo P: subconjunto de OT que siguen pendientes de los pedidos P.
 * Universo B: OT con asociación activa a algún pedido (de cualquier prefijo).
 * Universo S: OT sin ninguna asociación activa a pedido al mismo corte.
 *
 * A = P + (B - P) + S. Los resultados NULL nunca se inventan como prendas.
 * Cada OT cuenta una vez; "prendas" = resultado de su última etapa.
 */
class ConteoEtapasPedido
{
    public static function comparar(Collection $pendientesP, Collection $totalesPorProceso): Collection
    {
        $consolidados = $totalesPorProceso
            ->groupBy(function ($item) {
                return UltimoProcesoOt::normalizar($item->proceso);
            })
            ->map(function ($registros) {
                $sumar = function ($campo) use ($registros): int {
                    return (int) $registros->sum($campo);
                };

                return (object) [
                    'etapa_muestra' => $registros->first()->proceso,
                    'ot_total' => $sumar('ots'),
                    'prendas_total' => $sumar('prendas_registradas'),
                    'faltan_total' => $sumar('sin_cantidad'),
                    'ot_con_pedido' => $sumar('ots_con_pedido'),
                    'prendas_con_pedido' => $sumar('prendas_con_pedido'),
                    'faltan_con_pedido' => $sumar('con_pedido_sin_cantidad'),
                    'ot_sin_pedido' => $sumar('ots_sin_pedido'),
                    'prendas_sin_pedido' => $sumar('prendas_sin_pedido'),
                    'faltan_sin_pedido' => $sumar('sin_pedido_sin_cantidad'),
                ];
            });

        $pendientesPorArea = $pendientesP->groupBy(function ($fila) {
            return UltimoProcesoOt::normalizar($fila->etapa);
        });
        // Reportar todas las áreas del flujo de PRODUCCIÓN, incluso si
        // hoy tienen 0 OT P. Excluir estados finales y Logística con
        // cientos de OT históricas, que no son áreas productivas activas.
        // Mantener aparte cualquier etapa que sí tenga OT pendiente P.
        $claves = $consolidados->keys()
            ->filter(function ($area) use ($pendientesPorArea) {
                $nivel = UltimoProcesoOt::prioridad($area);
                return ($nivel >= 10 && $nivel <= 85)
                    || $pendientesPorArea->has($area);
            })
            ->merge($pendientesPorArea->keys())
            ->unique()
            ->values();

        return $claves->map(function ($area) use ($pendientesPorArea, $consolidados) {
            $items = collect($pendientesPorArea->get($area, collect()))->unique('nro_ot');
            $base = $consolidados->get($area);
            $cantidadP = $items->count();
            $faltanP = $items->filter(function ($fila) {
                return isset($fila->cantidad_etapa_disponible) && !$fila->cantidad_etapa_disponible;
            })->count();

            $total = $base ? $base->ot_total : 0;
            $conPedido = $base ? $base->ot_con_pedido : 0;
            $sinPedido = $base ? $base->ot_sin_pedido : 0;
            $sinProceso = $area === UltimoProcesoOt::normalizar('SIN MOVIMIENTO REGISTRADO');

            $inconsistente = !$sinProceso && (
                $total < $cantidadP ||
                $conPedido < $cantidadP ||
                $conPedido + $sinPedido !== $total
            );

            $otrPedido = $conPedido - $cantidadP;
            $prendasP = $faltanP ? null : (int) $items->sum('cantidad_pendiente');
            $prendasOtras = $base && !$faltanP && !$base->faltan_con_pedido
                ? $base->prendas_con_pedido - (int) $items->sum('cantidad_pendiente')
                : null;

            if ($prendasOtras !== null && $prendasOtras < 0) {
                $inconsistente = true;
            }

            $faltanSinPedido = $base ? $base->faltan_sin_pedido : 0;
            $faltanArea = $base ? $base->faltan_total : 0;

            return (object) [
                'etapa' => $items->isNotEmpty()
                    ? $items->first()->etapa
                    : ($base ? $base->etapa_muestra : $area),
                'ots' => $cantidadP,
                'prendas' => $prendasP,
                'prendas_p_faltantes' => $faltanP,
                'ots_en_area' => $sinProceso || $inconsistente ? null : $total,
                'prendas_en_area' => $faltanArea || $inconsistente ? null : ($base ? $base->prendas_total : null),
                'sin_cantidad_area' => $faltanArea,
                'ots_con_otro_pedido' => $sinProceso || $inconsistente ? null : $otrPedido,
                'prendas_con_otro_pedido' => $inconsistente ? null : $prendasOtras,
                'ots_sin_pedido' => $sinProceso || $inconsistente ? null : $sinPedido,
                'prendas_sin_pedido' => $faltanSinPedido || $inconsistente ? null : ($base ? $base->prendas_sin_pedido : null),
                'sin_cantidad_sin_pedido' => $faltanSinPedido,
                'conteo_inconsistente' => $inconsistente,
            ];
        })->sort(function ($a, $b) {
            // Orden lógico de producción, con las primeras áreas al inicio.
            $aOrden = UltimoProcesoOt::prioridad($a->etapa);
            $bOrden = UltimoProcesoOt::prioridad($b->etapa);
            return ($aOrden <=> $bOrden) ?: strcmp($a->etapa, $b->etapa);
        })->values();
    }
}
