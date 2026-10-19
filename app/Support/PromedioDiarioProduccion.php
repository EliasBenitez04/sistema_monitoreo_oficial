<?php

namespace App\Support;

/**
 * Proyección a fin de mes basada en días hábiles (lunes a viernes).
 *
 * Día 1: prendas acumuladas / 1 * días hábiles del mes.
 * Día 2: prendas acumuladas / 2 * días hábiles del mes.
 * Una redistribución de producción en sábado o domingo suma al acumulado,
 * pero no aumenta el número de días hábiles transcurridos.
 */
class PromedioDiarioProduccion
{
    /**
     * @param array<int, array{fecha: string, prendas: int|float, habil: bool}> $dias
     * @return array<string, array<string, int|float|bool|null>>
     */
    public static function calcular(array $dias, ?string $fechaCorte, int $diasHabilesMes): array
    {
        $resultados = [];
        $acumulado = 0;
        $habilesTranscurridos = 0;
        $promedioAnterior = null;
        $proyeccionAnterior = null;
        $proyeccionAnteriorExacta = null;
        $prendasAnterior = null;
        $habilesMes = max(0, $diasHabilesMes);

        foreach ($dias as $dia) {
            $fecha = (string) $dia['fecha'];
            $prendas = (int) $dia['prendas'];
            $habil = (bool) $dia['habil'];
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
                    'proyeccion' => null,
                    'proyeccion_anterior' => null,
                    'variacion_proyeccion' => null,
                    'variacion_proyeccion_porcentaje' => null,
                ];
                continue;
            }

            $acumulado += $prendas;
            if ($habil) {
                $habilesTranscurridos++;
            }

            // No existe promedio válido antes del primer día hábil.
            $promedioExacto = $habilesTranscurridos > 0
                ? $acumulado / $habilesTranscurridos : null;
            $promedio = $promedioExacto !== null
                ? round($promedioExacto, 1) : null;
            $proyeccionExacta = $promedioExacto !== null
                ? $promedioExacto * $habilesMes : null;
            $proyeccion = $proyeccionExacta !== null
                ? (int) round($proyeccionExacta) : null;

            $variacionPromedio = $promedioExacto !== null && $promedioAnterior !== null
                ? round($promedioExacto - $promedioAnterior, 1) : null;
            $variacionPromedioPct = $promedioExacto !== null && $promedioAnterior !== null
                && $promedioAnterior > 0
                ? round(($promedioExacto / $promedioAnterior - 1) * 100, 1) : null;
            $variacionProyeccion = $proyeccion !== null && $proyeccionAnterior !== null
                ? $proyeccion - $proyeccionAnterior : null;
            $variacionProyeccionPct = $proyeccionExacta !== null
                && $proyeccionAnteriorExacta !== null
                && $proyeccionAnteriorExacta > 0
                ? round(($proyeccionExacta / $proyeccionAnteriorExacta - 1) * 100, 1)
                : null;

            $resultados[$fecha] = [
                'registrado' => true,
                'acumulado' => $acumulado,
                'dias_transcurridos' => $habilesTranscurridos,
                'promedio' => $promedio,
                'promedio_anterior' => $promedioAnterior === null
                    ? null : round($promedioAnterior, 1),
                'variacion' => $variacionPromedio,
                'variacion_porcentaje' => $variacionPromedioPct,
                'prendas_anterior' => $prendasAnterior,
                'variacion_prendas' => $prendasAnterior === null
                    ? null : $prendas - $prendasAnterior,
                'proyeccion' => $proyeccion,
                'proyeccion_anterior' => $proyeccionAnterior,
                'variacion_proyeccion' => $variacionProyeccion,
                'variacion_proyeccion_porcentaje' => $variacionProyeccionPct,
            ];

            $promedioAnterior = $promedioExacto;
            $proyeccionAnterior = $proyeccion;
            $proyeccionAnteriorExacta = $proyeccionExacta;
            $prendasAnterior = $prendas;
        }

        return $resultados;
    }
}
