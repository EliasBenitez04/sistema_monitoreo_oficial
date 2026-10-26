<?php

namespace App\Support;

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

    public static function ranking(Collection $facturas): Collection
    {
        return $facturas->groupBy(function ($factura) {
            return self::token(
                (string) ($factura->cli_cod ?? ''),
                (string) ($factura->cliente ?? '')
            );
        })->map(function (Collection $grupo, string $token) {
            $primera = $grupo->first();
            $resumen = self::resumen($grupo);
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
            ];
        })->sort(function ($a, $b) {
            return ($b->venta_neta <=> $a->venta_neta)
                ?: ($b->tickets <=> $a->tickets)
                ?: strcmp($a->nombre, $b->nombre);
        })->values();
    }
}
