<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Identifica la etapa vigente para una OT.
 *
 * Fecha calendario más reciente > jerarquía del proceso del mismo día
 * > hora dentro de la misma etapa > id_trazabilidad.
 *
 * NO cambia el orden visual del historial ni modifica registros.
 */
class UltimoProcesoOt
{
    private const PRIORIDADES = [
        'PEDIDO A PRODUCCION' => 5,
        'ORDEN DE TRABAJO' => 10,
        'MOLDERIA' => 15,
        'PROTOTIPO' => 20,
        'DISEÑO GRAFICO' => 25,
        'TIZADAS' => 30,
        'CORTE' => 40,
        'LOTEO Y DISTRIBUCION' => 45,
        'REVELADO' => 50,
        'SERIGRAFIA' => 55,
        'BORDADO' => 60,
        'COSTURA INTERNA' => 70,
        'ATRAQUES' => 75,
        'LAVANDERIA' => 80,
        'PRETERMINACION' => 85,
        'INGRESO TERMINACION' => 90,
        'TERMINACION' => 95,
        'PRODUCTO TERMINADO' => 100,
        'LOGISTICA Y DISTRIBUCION' => 110,
        'REVISION' => 120,
        // Estado especial: si se suspende el mismo día debe verse suspendida.
        'PEDIDO SUSPENDIDO' => 130,
    ];

    public static function normalizar(?string $proceso): string
    {
        $nombre = str_replace(
            ["\xC2\xA0", "\xE2\x80\x8B", "\t", "\r", "\n"],
            ' ',
            (string) $proceso
        );
        $nombre = mb_strtoupper(trim($nombre), 'UTF-8');
        // En BD puede venir «PRODUCCION - COSTURA INTERNA» o «COSTURA INTERNA».
        if (strpos($nombre, ' - ') !== false) {
            $partes = explode(' - ', $nombre, 2);
            $nombre = $partes[1];
        }
        $nombre = preg_replace('/[^A-ZÁÉÍÓÚÑ\s]/u', '', $nombre);
        return trim(preg_replace('/\s+/u', ' ', $nombre));
    }

    public static function prioridad(?string $proceso): int
    {
        return self::PRIORIDADES[self::normalizar($proceso)] ?? 0;
    }

    /**
     * Expresión PostgreSQL 9.5 equivalente a prioridad() para seleccionar
     * la etapa vigente de CADA OT por fecha calendario y orden de proceso.
     * La columna es un identificador SQL fijo definido por el servidor,
     * nunca una entrada del usuario.
     */
    public static function prioridadSql(string $columna = 't.proceso'): string
    {
        $nombre = "UPPER(BTRIM(CASE"
            . " WHEN STRPOS(COALESCE({$columna}, ''), ' - ') > 0"
            . " THEN SPLIT_PART({$columna}, ' - ', 2)"
            . " ELSE COALESCE({$columna}, '') END))";
        $casos = [];
        foreach (self::PRIORIDADES as $proceso => $prioridad) {
            $casos[] = "WHEN '" . str_replace("'", "''", $proceso)
                . "' THEN " . (int) $prioridad;
        }

        return '(CASE ' . $nombre . ' ' . implode(' ', $casos) . ' ELSE 0 END)';
    }

    /**
     * Recibe ot_trazabilidad, sin requerir orden previo de consulta.
     * Si coinciden fecha y proceso, decide hora y luego ID.
     *
     * @return object|null
     */
    public static function ultimo(Collection $trazas)
    {
        $actual = null;
        $claveActual = null;

        foreach ($trazas as $traza) {
            if (!$traza->fecha_proceso) {
                continue;
            }

            $fecha = Carbon::parse($traza->fecha_proceso);
            $clave = [
                $fecha->format('Ymd'),
                self::prioridad($traza->proceso),
                $fecha->getTimestamp(),
                (int) ($traza->id_trazabilidad ?? 0),
            ];

            if ($actual === null || self::comparar($clave, $claveActual) > 0) {
                $actual = $traza;
                $claveActual = $clave;
            }
        }

        return $actual;
    }

    private static function comparar(array $a, array $b): int
    {
        foreach ([0, 1, 2, 3] as $indice) {
            $cmp = $a[$indice] <=> $b[$indice];
            if ($cmp !== 0) {
                return $cmp;
            }
        }
        return 0;
    }
}
