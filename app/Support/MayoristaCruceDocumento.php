<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Agrupa remisiones por número de documento físico, nunca por candidato
 * global de código, y conserva el saldo real de CADA línea/size.
 *
 * El documento completo significa que contiene los códigos solicitados;
 * NO significa que esté confirmado para la factura.
 */
class MayoristaCruceDocumento
{
    public static function agrupar(Collection $candidatas, array $requeridas, Collection $porCodigo, ?string $fechaFactura = null): Collection
    {
        $documentos = [];
        foreach ($candidatas as $r) {
            $codigo = MayoristaConciliacion::codigo($r->codigo);
            $disponible = max(0, (int) $r->cantidad - (int) ($r->usadas ?? 0));
            $r->codigo_normalizado = $codigo;
            $r->disponible = $disponible;
            $r->pendiente_factura = $porCodigo[$codigo]->pendiente ?? 0;
            $r->maximo = min($disponible, $r->pendiente_factura);
            $r->modelo = (int) ($r->es_modelo ?? 0) === 1;
            $r->origen_real = MayoristaOrigenRemision::nombreOrigen($r);
            $r->tipo_origen = MayoristaOrigenRemision::tipoOrigen($r);
            $r->origen_discordante = MayoristaOrigenRemision::origenDiscordante($r);
            $r->canal = MayoristaOrigenRemision::clasificar($r);
            $r->posterior = $fechaFactura !== null
                && $r->fecha_remision > $fechaFactura;
            $r->recepcion_posterior = $fechaFactura !== null
                && !empty($r->fecha_recepcion)
                && $r->fecha_recepcion > $fechaFactura;
            // Documento posterior a factura: sólo consulta. Para traslados
            // a Matriz sin Modelo Muestra, recepción tardía tampoco habilita
            // asignación; el backend valida nuevamente la misma regla.
            if ($r->posterior || ($r->recepcion_posterior && !$r->modelo)) {
                $r->maximo = 0;
            }

            $r->es_destino_matriz = (int) ($r->cod_sucursal_destino ?? 0) === 25
                || in_array(strtoupper(trim((string) $r->sucursal_destino)), ['MATRIZ','COMERCIAL MATRIZ'], true)
                || in_array(strtoupper(trim((string) ($r->sucursal_logistica ?? ''))), ['MATRIZ','COMERCIAL MATRIZ'], true);

            $clave = implode('|', [
                $r->serie, $r->numero_remision,
                $r->cod_sucursal_salida, $r->cod_sucursal_destino,
                $r->fecha_remision,
            ]);
            if (!$r->numero_remision) {
                $clave .= '|LINEA-'.$r->id;
            }
            if (!isset($documentos[$clave])) {
                $documentos[$clave] = (object) [
                    'serie' => $r->serie, 'numero' => $r->numero_remision,
                    'fecha' => $r->fecha_remision,
                    'origen' => $r->origen_real,
                    'tipo_origen' => $r->tipo_origen,
                    'origen_discordante' => $r->origen_discordante,
                    'origen_importado' => $r->sucursal_salida,
                    'canal' => $r->canal,
                    'destino' => $r->es_destino_matriz ? 'COMERCIAL MATRIZ'
                        : ($r->sucursal_destino ?: $r->sucursal_logistica),
                    'codigos' => [], 'lineas' => collect(),
                    'remitidas' => 0, 'usadas' => 0, 'disponibles' => 0,
                    'posible' => 0, 'coincidencias' => 0, 'completo' => false,
                    'codigos_por_ot' => [], 'grupos_de_ot' => [],
                ];
            }
            $doc = $documentos[$clave];
            $doc->lineas->push($r);
            $doc->codigos[$codigo] = true;
            if (!empty($r->id_ot)) {
                $doc->codigos_por_ot[$r->id_ot][$codigo] = true;
            }
            $doc->remitidas += (int) $r->cantidad;
            $doc->usadas += (int) ($r->usadas ?? 0);
            $doc->disponibles += $disponible;
            $doc->posible += $r->maximo;
        }
        foreach ($documentos as $doc) {
            $doc->coincidencias = count($doc->codigos);
            $doc->completo = count($requeridas) > 0
                && count(array_diff(array_keys($requeridas), array_keys($doc->codigos))) === 0;
            // Orientación cuando un mismo documento contiene varios
            // códigos/talles de la MISMA OT. No asumir que 4 códigos
            // son exactamente los 4 talles de un artículo.
            foreach ($doc->codigos_por_ot as $idOt => $codigos) {
                if (count($codigos) >= 4) {
                    $doc->grupos_de_ot[] = (object)[
                        'id_ot' => $idOt, 'codigos' => count($codigos)
                    ];
                }
            }
        }

        return collect(array_values($documentos))
            ->sortByDesc(function ($doc) {
                return ($doc->completo ? 10000000 : 0) + $doc->posible;
            })->values();
    }
}
