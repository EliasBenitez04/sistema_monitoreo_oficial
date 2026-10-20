<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Propone cruces por codigo completo sin escribir en BD.
 * Una coincidencia documental NO demuestra que esa remision financio la venta.
 * La asignacion requiere confirmacion humana y se valida otra vez al guardar.
 */
class MayoristaPropuestaFactura
{
    public static function generar(
        Collection $candidatas,
        Collection $porCodigo,
        Collection $enlaces,
        string $fechaFactura,
        int $limite = 50
    ): array {
        $pendientes = [];
        foreach ($porCodigo as $codigo => $item) {
            $pendientes[$codigo] = max(0, (int) $item->pendiente);
        }
        $yaVinculadas = $enlaces->pluck('remision_id')->map(function ($id) {
            return (int) $id;
        })->all();

        // La lista es solo una propuesta: excluir Modelo Muestra, fechas
        // posteriores, origen discordante y lineas ya ligadas a la factura.
        $validas = $candidatas->filter(function ($r) use ($pendientes, $yaVinculadas, $fechaFactura) {
            $codigo = (string) ($r->codigo_normalizado ?? '');
            if (($pendientes[$codigo] ?? 0) <= 0 || (int) ($r->disponible ?? 0) <= 0) {
                return false;
            }
            if (in_array((int) $r->id, $yaVinculadas, true)
                || empty($r->es_destino_matriz) || !empty($r->modelo)
                || !empty($r->origen_discordante) || empty($r->numero_remision)) {
                return false;
            }
            $emision = substr((string) ($r->fecha_remision ?? ''), 0, 10);
            $recepcion = substr((string) ($r->fecha_recepcion ?? ''), 0, 10);
            return $emision !== '' && $recepcion !== ''
                && $emision <= $fechaFactura && $recepcion <= $fechaFactura;
        })->sortBy(function ($r) {
            // FIFO para sugerir, no como prueba de procedencia.
            return sprintf('%s|%s|%012d', (string) $r->fecha_recepcion,
                (string) $r->fecha_remision, (int) $r->id);
        })->values();

        $conteos = $validas->groupBy(function ($r) {
            return $r->codigo_normalizado;
        })->map(function ($lineas) {
            return $lineas->count();
        });

        $filas = collect();
        foreach ($validas as $r) {
            $codigo = (string) $r->codigo_normalizado;
            $cantidad = min((int) ($pendientes[$codigo] ?? 0), (int) $r->disponible);
            if ($cantidad <= 0) {
                continue;
            }
            $filas->push((object) [
                'remision_id' => (int) $r->id,
                'serie' => $r->serie,
                'numero' => $r->numero_remision,
                'origen' => $r->origen_real,
                'codigo' => $codigo,
                'fecha_remision' => $r->fecha_remision,
                'fecha_recepcion' => $r->fecha_recepcion,
                'disponible' => (int) $r->disponible,
                'cantidad' => $cantidad,
                'ambigua' => (int) ($conteos[$codigo] ?? 0) > 1,
            ]);
            $pendientes[$codigo] -= $cantidad;
        }

        $visibles = $filas->take(max(1, $limite))->values();
        return [
            'filas' => $visibles,
            'total' => $filas->sum('cantidad'),
            'visibles' => $visibles->sum('cantidad'),
            'unicas' => $visibles->filter(function ($fila) {
                return !$fila->ambigua;
            })->sum('cantidad'),
            'ambiguas' => $visibles->filter(function ($fila) {
                return $fila->ambigua;
            })->count(),
            'truncado' => $filas->count() > $visibles->count(),
        ];
    }
}
