<?php

namespace App\Support;

final class InviernoValorizacion
{
    public const ETAPAS = [
        'prendas' => 'documentado',
        'central' => 'recibido en Central',
        'pendiente' => 'pendiente en Central',
        'remitido' => 'enviado a locales',
        'recibido_local' => 'recibido en locales',
        'transito_local' => 'en tránsito a locales',
        'disponible' => 'disponible en Central',
        'por_revisar' => 'saldo por revisar',
    ];

    private const VACIO = ['cantidad' => 0, 'costo' => 0, 'venta' => 0, 'sin_costo' => 0, 'sin_precio' => 0];

    private array $acumulados = [];

    public static function centavos($valor): ?int
    {
        return is_numeric($valor) ? (int) round((float) $valor * 100) : null;
    }

    public static function guaranies(int|float|null $centavos): int|float|null
    {
        return $centavos === null ? null : $centavos / 100;
    }

    public static function mostrar(int|float|null $centavos): string
    {
        return $centavos === null ? '—' : 'Gs '.number_format($centavos / 100,
            fmod($centavos, 100) === 0.0 ? 0 : 2, ',', '.');
    }

    public static function linea(?int $cantidad, ?int $costo, ?int $precio): array
    {
        if ($cantidad === null) {
            return ['costo' => null, 'venta' => null, 'sin_costo' => 0, 'sin_precio' => 0];
        }
        return [
            'costo' => $costo === null && $cantidad > 0 ? null : $cantidad * ($costo ?? 0),
            'venta' => $precio === null && $cantidad > 0 ? null : $cantidad * ($precio ?? 0),
            'sin_costo' => $costo === null ? $cantidad : 0,
            'sin_precio' => $precio === null ? $cantidad : 0,
        ];
    }

    public function sumar(string $etapa, int $cantidad, ?int $costo, ?int $precio): void
    {
        $total = $this->acumulados[$etapa] ?? self::VACIO;
        $total['cantidad'] += $cantidad;
        $total['costo'] += $cantidad * ($costo ?? 0);
        $total['venta'] += $cantidad * ($precio ?? 0);
        $total['sin_costo'] += $costo === null ? $cantidad : 0;
        $total['sin_precio'] += $precio === null ? $cantidad : 0;
        $this->acumulados[$etapa] = $total;
    }

    public function totales(): array
    {
        $totales = [];
        foreach (array_keys(self::ETAPAS) as $etapa) {
            $total = $this->acumulados[$etapa] ?? self::VACIO;
            if ($total['cantidad'] > 0 && $total['cantidad'] === $total['sin_costo']) {
                $total['costo'] = null;
            }
            if ($total['cantidad'] > 0 && $total['cantidad'] === $total['sin_precio']) {
                $total['venta'] = null;
            }
            $totales[$etapa] = $total;
        }
        return $totales;
    }

    public function sumarRemision(object $documento): void
    {
        $revisar = !empty($documento->incidencias);
        foreach ($documento->entradas as $entrada) {
            $costo = self::centavos($entrada->costo_unitario);
            $precio = self::centavos($entrada->precio_venta);
            foreach (InviernoDocumento::cantidadesEntrada($entrada, $revisar) as $etapa => $cantidad) {
                if ($cantidad > 0) {
                    $this->sumar($etapa, $cantidad, $costo, $precio);
                }
            }
        }
    }
}
