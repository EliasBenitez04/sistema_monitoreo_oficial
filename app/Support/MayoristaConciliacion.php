<?php

namespace App\Support;

class MayoristaConciliacion
{
    public static function codigo($codigo): string
    {
        return strtoupper(preg_replace('/\\s+/u', '', trim((string) $codigo)));
    }

    public static function claveFactura($fecha, $comprobante, $clienteCodigo, $cliente): string
    {
        return sha1(implode('|', [
            'COMERCIAL MATRIZ',
            substr((string) $fecha, 0, 10),
            strtoupper(trim((string) $comprobante)),
            strtoupper(trim((string) $clienteCodigo)),
            strtoupper(trim((string) $cliente)),
        ]));
    }

    /**
     * Los candidatos no se convierten en vínculos automáticamente.
     * Una cantidad global coincidente no prueba la procedencia por código.
     *
     * @param array<string,int> $necesidad
     * @param array<string,int> $confirmada
     * @param array<int,array{codigo:string,disponible:int}> $candidatas
     */
    public static function estado(array $necesidad, array $confirmada, array $candidatas): string
    {
        $total = array_sum($necesidad);
        if ($total <= 0) {
            return 'NOTA DE CRÉDITO / SIN UNIDADES POSITIVAS';
        }

        $asignadas = 0;
        $restantes = [];
        foreach ($necesidad as $codigo => $cantidad) {
            $cantidad = max(0, $cantidad);
            $v = min($cantidad, max(0, (int) ($confirmada[$codigo] ?? 0)));
            $asignadas += $v;
            $restantes[$codigo] = $cantidad - $v;
        }
        if ($asignadas === $total) {
            return 'CONFIRMADA DOCUMENTALMENTE';
        }
        if ($asignadas > 0) {
            return 'PARCIAL';
        }

        $porCodigo = [];
        foreach ($candidatas as $fila) {
            $codigo = self::codigo($fila['codigo']);
            if (($restantes[$codigo] ?? 0) > 0 && $fila['disponible'] > 0) {
                $porCodigo[$codigo][] = $fila['disponible'];
            }
        }
        foreach ($restantes as $codigo => $requerido) {
            if ($requerido <= 0) continue;
            $opciones = $porCodigo[$codigo] ?? [];
            if (array_sum($opciones) < $requerido) {
                return 'SIN COINCIDENCIA';
            }
            if (count($opciones) > 1) {
                return 'AMBIGUA';
            }
        }
        return 'PROBABLE';
    }

    /** La factura y cada remisión conservan su saldo de unidades sin doble imputación. */
    public static function capacidad(int $facturaPendiente, int $remisionDisponible): int
    {
        return max(0, min($facturaPendiente, $remisionDisponible));
    }

    public static function saldoDocumental(?int $inicial, int $recibido, int $facturadoNeto): ?int
    {
        if ($inicial === null) {
            return null;
        }
        return $inicial + $recibido - $facturadoNeto;
    }
}
