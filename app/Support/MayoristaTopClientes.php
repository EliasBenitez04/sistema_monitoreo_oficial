<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Agrupación gerencial de clientes mayoristas basada en facturas reales,
 * no en líneas importadas. La identidad conserva código Y nombre porque
 * existen códigos reutilizados/vacíos entre clientes distintos.
 */
class MayoristaTopClientes
{
    public static function token(string $codigo, string $nombre): string
    {
        return 'CLIENTE:' . base64_encode(json_encode([trim($codigo), trim($nombre)]));
    }

    public static function identidad(string $token): ?array
    {
        if (strpos($token, 'CLIENTE:') !== 0 || strlen($token) > 1000) {
            return null;
        }
        $json = base64_decode(substr($token, 8), true);
        $datos = $json === false ? null : json_decode($json, true);
        if (!is_array($datos) || count($datos) !== 2
            || !array_key_exists(0, $datos) || !array_key_exists(1, $datos)
            || !is_string($datos[0]) || !is_string($datos[1])) {
            return null;
        }
        $codigo = trim($datos[0]);
        $nombre = trim($datos[1]);
        if (strlen($codigo) > 50 || strlen($nombre) > 180) {
            return null;
        }
        return [$codigo, $nombre];
    }

    public static function esNotaCredito($factura): bool
    {
        $tipo = strtoupper(trim((string) ($factura->tipo_comprobante ?? '')));
        $comp = strtoupper(trim((string) ($factura->comprobante ?? '')));
        return preg_match('/^(NCR?|N\/C|NOTA[\s_-]*DE[\s_-]*CR)/', $tipo) === 1
            || preg_match('/^(NCR?|N\/C|NOTA[\s_-]*DE[\s_-]*CR)/', $comp) === 1;
    }

    public static function esTicket($factura): bool
    {
        // Una nota de crédito no representa un ticket nuevo.
        return !self::esNotaCredito($factura) && (int) ($factura->brutas ?? 0) > 0;
    }

    public static function seleccionar(Collection $facturas, string $codigo, string $nombre): Collection
    {
        return $facturas->filter(function ($factura) use ($codigo, $nombre) {
            return trim((string) ($factura->cli_cod ?? '')) === $codigo
                && trim((string) ($factura->cliente ?? '')) === $nombre;
        })->values();
    }

    public static function resumen(Collection $facturas): object
    {
        $tickets = [];
        $creditos = [];
        foreach ($facturas as $factura) {
            // Número de comprobante por fecha y tipo: evita contar por
            // línea de artículo y diferencia documentos emitidos en otros días.
            $identidad = substr((string) $factura->fecha, 0, 10) . '|'
                . strtoupper(trim((string) $factura->comprobante)) . '|'
                . strtoupper(trim((string) ($factura->tipo_comprobante ?? '')));

            if (self::esTicket($factura)) {
                $tickets[$identidad] = true;
            } elseif (self::esNotaCredito($factura)) {
                $creditos[$identidad] = true;
            }
        }

        return (object) [
            'unidades' => (int) $facturas->sum('netas'),
            'brutas' => (int) $facturas->sum('brutas'),
            'devoluciones' => (int) $facturas->sum('devoluciones'),
            'venta_neta' => (float) $facturas->sum('pvta'),
            'tickets' => count($tickets),
            'notas_credito' => count($creditos),
            'documentos' => count($tickets) + count($creditos),
        ];
    }

    /**
     * Última factura de venta y frecuencia calculadas al cierre consultado.
     * Los documentos NCR no representan compras ni reinician la antigüedad.
     *
     * Estado orientativo, no predicción de abandono:
     * - Recurrente: compra en 2+ fechas distintas en los últimos 90 días.
     * - Ocasional: compra reciente, pero sin esa recurrencia.
     * - Sin compras recientes: ningún ticket de venta en los últimos 90 días.
     */
    public static function ranking(Collection $facturas, ?string $hasta = null): Collection
    {
        $corte = CarbonImmutable::parse($hasta ?: ($facturas->max('fecha') ?: 'today'))->startOfDay();
        $inicioReciente = $corte->subDays(89)->format('Y-m-d');

        return $facturas->groupBy(function ($factura) {
            return self::token(
                (string) ($factura->cli_cod ?? ''),
                (string) ($factura->cliente ?? '')
            );
        })->map(function (Collection $grupo, string $token) use ($corte, $inicioReciente) {
            $primera = $grupo->first();
            $resumen = self::resumen($grupo);
            $tickets = [];
            foreach ($grupo as $factura) {
                if (!self::esTicket($factura)) {
                    continue;
                }
                $fecha = substr((string) $factura->fecha, 0, 10);
                $identidad = $fecha . '|'
                    . strtoupper(trim((string) $factura->comprobante)) . '|'
                    . strtoupper(trim((string) ($factura->tipo_comprobante ?? '')));
                if (!isset($tickets[$identidad])) {
                    $tickets[$identidad] = $factura;
                }
            }

            $tickets = collect(array_values($tickets))->sort(function ($a, $b) {
                return strcmp(
                    (string) $b->fecha . '|' . (string) $b->comprobante,
                    (string) $a->fecha . '|' . (string) $a->comprobante
                );
            })->values();

            $ultima = $tickets->first();
            $fechaUltima = $ultima ? substr((string) $ultima->fecha, 0, 10) : null;
            $diasSinCompra = $fechaUltima === null ? null : max(
                0, (int) CarbonImmutable::parse($fechaUltima)->diffInDays($corte)
            );
            $fechasRecientes = $tickets->filter(function ($factura) use ($inicioReciente, $corte) {
                $fecha = substr((string) $factura->fecha, 0, 10);
                return $fecha >= $inicioReciente && $fecha <= $corte->format('Y-m-d');
            })->map(function ($factura) {
                return substr((string) $factura->fecha, 0, 10);
            })->unique()->count();

            $estado = $fechaUltima === null ? 'SIN COMPRAS' :
                ($diasSinCompra >= 90 ? 'SIN COMPRAS RECIENTES' :
                    ($fechasRecientes >= 2 ? 'RECURRENTE' : 'OCASIONAL'));

            return (object) [
                'token' => $token,
                'codigo' => trim((string) ($primera->cli_cod ?? '')),
                'nombre' => trim((string) ($primera->cliente ?? '')),
                'unidades' => $resumen->unidades,
                'brutas' => $resumen->brutas,
                'devoluciones' => $resumen->devoluciones,
                'tickets' => $resumen->tickets,
                'notas_credito' => $resumen->notas_credito,
                'venta_neta' => $resumen->venta_neta,
                'ultima_fecha' => $fechaUltima,
                'ultimo_comprobante' => $ultima ? (string) $ultima->comprobante : null,
                'ultima_clave' => $ultima ? (string) ($ultima->clave ?? '') : null,
                'dias_sin_compra' => $diasSinCompra,
                'fechas_compra_recientes' => $fechasRecientes,
                'estado_frecuencia' => $estado,
            ];
        })->sort(function ($a, $b) {
            return ($b->venta_neta <=> $a->venta_neta)
                ?: ($b->tickets <=> $a->tickets)
                ?: strcmp($a->nombre, $b->nombre);
        })->values();
    }
}
