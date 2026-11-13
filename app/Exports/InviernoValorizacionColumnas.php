<?php

namespace App\Exports;

use App\Support\InviernoValorizacion as Valorizacion;

final class InviernoValorizacionColumnas
{
    private const CAMPOS = [
        'costo' => 'COSTO %s (GS)',
        'venta' => 'VALOR A PRECIO DE VENTA %s (GS)',
    ];

    public static function encabezados(bool $unitarios = false): array
    {
        $encabezados = $unitarios ? ['COSTO UNITARIO (GS)', 'PRECIO DE VENTA UNITARIO (GS)'] : [];
        foreach (Valorizacion::ETAPAS as $etapa) {
            foreach (self::CAMPOS as $formato) {
                $encabezados[] = sprintf($formato, mb_strtoupper($etapa));
            }
        }
        return [...$encabezados, 'DOCUMENTADAS SIN COSTO', 'DOCUMENTADAS SIN PRECIO', 'DISPONIBLES SIN COSTO', 'DISPONIBLES SIN PRECIO'];
    }

    public static function totales(array $valorizacion): array
    {
        $fila = [];
        foreach (array_keys(Valorizacion::ETAPAS) as $etapa) {
            $valor = $valorizacion[$etapa];
            $fila[] = Valorizacion::guaranies($valor['costo']);
            $fila[] = Valorizacion::guaranies($valor['venta']);
        }
        return [...$fila, $valorizacion['prendas']['sin_costo'], $valorizacion['prendas']['sin_precio'],
            $valorizacion['disponible']['sin_costo'], $valorizacion['disponible']['sin_precio']];
    }

    public static function linea(object $linea): array
    {
        $valorizacion = [];
        foreach (array_keys(Valorizacion::ETAPAS) as $etapa) {
            $valorizacion[$etapa] = Valorizacion::linea($linea->{$etapa}, $linea->costo_centavos, $linea->precio_centavos);
        }
        return [Valorizacion::guaranies($linea->costo_centavos), Valorizacion::guaranies($linea->precio_centavos),
            ...self::totales($valorizacion)];
    }
}
