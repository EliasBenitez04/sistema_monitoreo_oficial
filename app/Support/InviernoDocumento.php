<?php

namespace App\Support;

use Illuminate\Support\Str;

class InviernoDocumento
{
    // Equivalencias del catálogo y del importador general de remisiones.
    // Coincidencias exactas: no usar similitud ni la serie para adivinar el local.
    private const ALIAS_LOCALES = [
        'CENTRAL' => 'CASA CENTRAL',
        'SL' => 'SAN LORENZO',
        'SLG' => 'SAN LORENZO',
        'MMALL' => 'MALL',
        'L06 SAN LO2' => 'L06',
        'L06 SAN LO 2' => 'L06',
        'LA RURAL' => 'RURAL',
        'SHOP SAN LO3' => 'SHOPP',
        'SHOP SAN LO 3' => 'SHOPP',
        'SHOP SAN LORENZO' => 'SHOPP',
        'SHOPPING SAN LORENZO' => 'SHOPP',
        'MULTI' => 'MULTIPLAZA',
        'MULTI PLAZA' => 'MULTIPLAZA',
        'PINEDO SHOPPING' => 'PINEDO',
        'SHOPPING PINEDO' => 'PINEDO',
        'SHOPPING MARIANO' => 'MARIANO',
        'SHOP MARIANO' => 'MARIANO',
        'LOS JARDINES' => 'JARDINES',
        'JARDINES LUQUE' => 'JARDINES',
        'MATRIZ' => 'COMERCIAL MATRIZ',
    ];

    public static function texto($valor): string
    {
        return Str::upper(trim(str_replace("\xc2\xa0", ' ', (string) $valor)));
    }

    public static function local($valor): string
    {
        $nombre = Str::ascii(self::texto($valor));
        $nombre = trim(preg_replace('/[\p{Z}\s]+/u', ' ', str_replace(['-', '_'], ' ', $nombre)));
        if ($nombre !== '' && ctype_digit($nombre)) {
            return ltrim($nombre, '0') ?: '0';
        }
        return self::ALIAS_LOCALES[$nombre] ?? $nombre;
    }

    public static function numero($valor): ?string
    {
        $numero = trim((string) $valor);
        if ($numero === '' || strlen($numero) > 30 || !ctype_digit($numero)) {
            return null;
        }
        return ltrim($numero, '0') ?: '0';
    }

    public static function numeroSql(?string $alias = null): string
    {
        // LTRIM con dos argumentos funciona en PostgreSQL 9.5 y SQLite.
        $columna = $alias === null ? 'numero_remision' : $alias.'.numero_remision';
        return "COALESCE(NULLIF(LTRIM(TRIM($columna), '0'), ''), '0')";
    }

    public static function coincideEntrada(object $documento, object $entrada): bool
    {
        return (int) $entrada->cod_sucursal_salida === (int) $documento->cod_sucursal_origen
            && (int) $entrada->cod_sucursal_destino === 1
            && self::texto($entrada->serie) === $documento->serie
            && self::numero($entrada->numero_remision) === $documento->numero_remision;
    }

    public static function fechaSalida(object $movimiento): ?string
    {
        return $movimiento->fecha_remision ?: ($movimiento->fecha_creacion ?? null);
    }

    public static function cantidadesEntrada(object $entrada, bool $revisar): array
    {
        $prendas = max(0, (int) $entrada->cantidad);
        $central = $entrada->recibida ? $prendas : 0;
        $remitido = $entrada->remitido_valido ?? 0;
        $recibido = $entrada->recibido_local_valido ?? 0;
        $saldo = max(0, $central - $remitido);

        return [
            'prendas' => $prendas, 'central' => $central, 'pendiente' => $prendas - $central,
            'remitido' => $remitido, 'recibido_local' => $recibido, 'transito_local' => $remitido - $recibido,
            'disponible' => $revisar ? null : $saldo, 'por_revisar' => $revisar ? $saldo : 0,
        ];
    }

    public static function recibido(object $movimiento, ?string $hoy = null): bool
    {
        return self::texto($movimiento->estado) === 'RECIBIDO'
            && !empty($movimiento->fecha_recepcion)
            && $movimiento->fecha_recepcion <= ($hoy ?? now()->toDateString())
            && (!self::fechaSalida($movimiento) || $movimiento->fecha_recepcion >= self::fechaSalida($movimiento));
    }

    public static function problema(object $movimiento, ?string $hoy = null): ?string
    {
        if (self::texto($movimiento->serie) === '' || self::numero($movimiento->numero_remision) === null) {
            return 'Serie o número de remisión inválido.';
        }
        if ((int) $movimiento->cantidad <= 0 || self::texto($movimiento->codigo) === '') {
            return 'Cantidad o código de artículo inválido.';
        }
        $hoy = $hoy ?? now()->toDateString();
        $salida = self::fechaSalida($movimiento);
        if (!$salida || $salida > $hoy) {
            return 'Fecha de remisión ausente o futura.';
        }
        $estado = self::texto($movimiento->estado);
        if (!in_array($estado, ['EN_TRANSITO', 'RECIBIDO'], true)) {
            return 'Estado de remisión no válido para el seguimiento.';
        }
        if ($estado === 'RECIBIDO' && !self::recibido($movimiento, $hoy)) {
            return 'Recepción sin fecha válida o anterior a la remisión.';
        }
        if ($estado === 'EN_TRANSITO' && !empty($movimiento->fecha_recepcion)) {
            return 'La fecha de recepción contradice el estado en tránsito.';
        }
        return null;
    }
}
