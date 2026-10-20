<?php

namespace App\Support;

/**
 * Fuente de verdad del origen y destino de las remisiones importadas.
 *
 * ControlTerminacionRemisionImport usa destino 25 = MATRIZ, aunque las
 * etiquetas pueden estar guardadas como "MATRIZ" o "COMERCIAL MATRIZ".
 * El ORIGEN siempre se determina por cod_sucursal_salida y
 * sucursal_salida, nunca por la planificación/alias del destino.
 */
class MayoristaOrigenRemision
{
    public const CODIGO_MATRIZ = 25;
    public const CODIGO_CENTRAL = 1;

    private const SUCURSALES = [
        1 => 'CASA CENTRAL',
        2 => 'SAN LORENZO',
        3 => 'LUQUE',
        4 => 'MALL',
        5 => 'L06',
        6 => 'RURAL',
        7 => 'BONANZA',
        8 => 'SHOP SAN LORENZO',
        9 => 'MULTIPLAZA',
        14 => 'PINEDO',
        15 => 'ÑEMBY',
        16 => 'SHOPPING MARIANO',
        22 => 'LOS JARDINES',
        25 => 'COMERCIAL MATRIZ',
    ];

    public static function expresionDestinoMatriz(string $alias = 'r'): string
    {
        return "(COALESCE({$alias}.cod_sucursal_destino,0) = 25"
            . " OR UPPER(BTRIM(COALESCE({$alias}.sucursal_destino,''))) IN ('MATRIZ','COMERCIAL MATRIZ')"
            . " OR UPPER(BTRIM(COALESCE({$alias}.sucursal_logistica,''))) IN ('MATRIZ','COMERCIAL MATRIZ'))";
    }

    public static function esOrigenMatrizSql(string $alias = 'r'): string
    {
        // El código prevalece sobre un nombre erróneo: solo consultar
        // el nombre cuando el código de origen no está informado.
        return "({$alias}.cod_sucursal_salida = 25"
            . " OR (COALESCE({$alias}.cod_sucursal_salida,0) = 0"
            . " AND UPPER(BTRIM(COALESCE({$alias}.sucursal_salida,''))) IN ('MATRIZ','COMERCIAL MATRIZ')))";
    }

    public static function nombreOrigen($fila): string
    {
        $codigo = isset($fila->cod_sucursal_salida) ? (int) $fila->cod_sucursal_salida : 0;
        $nombre = trim((string) ($fila->sucursal_salida ?? ''));
        // El código de origen importado es más fiable que una etiqueta
        // faltante o heredada del plan del destino.
        if (isset(self::SUCURSALES[$codigo])) {
            return self::SUCURSALES[$codigo];
        }
        if ($nombre !== '') {
            return $nombre;
        }
        return $codigo > 0 ? 'SUCURSAL '.$codigo : 'ORIGEN NO IDENTIFICADO';
    }

    public static function tipoOrigen($fila): string
    {
        $codigo = isset($fila->cod_sucursal_salida) ? (int) $fila->cod_sucursal_salida : 0;
        if ($codigo === self::CODIGO_CENTRAL) {
            return 'CENTRAL';
        }
        if ($codigo > 0 && $codigo !== self::CODIGO_MATRIZ) {
            return 'LOCAL';
        }
        $nombre = strtoupper(trim((string) ($fila->sucursal_salida ?? '')));
        if (in_array($nombre, ['CASA CENTRAL', 'CENTRAL'], true)) {
            return 'CENTRAL';
        }
        if ($nombre === '' || in_array($nombre, ['COMERCIAL MATRIZ', 'MATRIZ'], true)) {
            return 'NO IDENTIFICADO';
        }
        return 'LOCAL';
    }

    public static function clasificar($fila): string
    {
        $tipo = self::tipoOrigen($fila);
        $modelo = (int) ($fila->es_modelo ?? 0) === 1;
        if ($modelo) {
            return 'MODELO MUESTRA · '.($tipo === 'CENTRAL' ? 'CENTRAL' : ($tipo === 'LOCAL' ? 'LOCAL' : 'ORIGEN POR REVISAR'));
        }
        if ($tipo === 'CENTRAL') {
            return 'CASA CENTRAL → MATRIZ';
        }
        if ($tipo === 'LOCAL') {
            return 'LOCAL → MATRIZ';
        }
        return 'ORIGEN POR REVISAR → MATRIZ';
    }
}
