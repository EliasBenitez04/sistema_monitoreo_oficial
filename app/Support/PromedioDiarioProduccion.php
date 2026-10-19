<?php

namespace App\Support;

/**
 * Evolución del promedio de prendas diarias desde el primer día del mes
 * hasta cada fecha del período. Los días sin movimientos valen cero; una
 * fecha posterior al corte todavía no tiene promedio registrado.
 */
class PromedioDiarioProduccion
{
    /**
     * @param array<int, array{fecha: string, prendas: int|float}> $dias
     * @return array<string, array<string, int|float|bool|null>>
     */
    public static function calcular(array $dias, ?string $fechaCorte): array
    {
        $resultados = [];
        $acumulado = 0;
        $transcurridos = 0;
        $promedioAnterior = null;
        $prendasAnterior = null;

        foreach ($dias as $dia) {
            $fecha = (string) $dia['fecha'];
            $prendas = (int) $dia['prendas'];
            $registrado = $fechaCorte !== null && $fecha <= $fechaCorte;

            if (!$registrado) {
                $resultados[$fecha] = [
                    'registrado' => false,
                    'acumulado' => null,
                    'dias_transcurridos' => null,
                    'promedio' => null,
                    'promedio_anterior' => null,
                    'variacion' => null,
                    'variacion_porcentaje' => null,
                    'prendas_anterior' => null,
                    'variacion_prendas' => null,
                ];
                continue;
            }

            $acumulado += $prendas;
            $transcurridos++;
            $promedioExacto = $acumulado / $transcurridos;
            $variacion = $promedioAnterior === null
                ? null : round($promedioExacto - $promedioAnterior, 1);
            $variacionPorcentaje = $promedioAnterior !== null
                && $promedioAnterior > 0
                ? round(($promedioExacto / $promedioAnterior - 1) * 100, 1)
                : null;

            $resultados[$fecha] = [
                'registrado' => true,
                'acumulado' => $acumulado,
                'dias_transcurridos' => $transcurridos,
                'promedio' => round($promedioExacto, 1),
                'promedio_anterior' => $promedioAnterior === null
                    ? null : round($promedioAnterior, 1),
                'variacion' => $variacion,
                'variacion_porcentaje' => $variacionPorcentaje,
                'prendas_anterior' => $prendasAnterior,
                'variacion_prendas' => $prendasAnterior === null
                    ? null : $prendas - $prendasAnterior,
            ];

            $promedioAnterior = $promedioExacto;
            $prendasAnterior = $prendas;
        }

        return $resultados;
    }
}
