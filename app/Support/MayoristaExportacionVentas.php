<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Archivo interoperable con App\Imports\VentasImport::importarCsv.
 * Mantiene exactamente los doce encabezados que valida ese importador.
 */
class MayoristaExportacionVentas
{
    public const ENCABEZADOS = [
        'LOCAL', 'CODIGO', 'DESCRIPCION', 'CLI COD', 'CLIENTE',
        'VENDEDOR', 'PLISTA', 'DTO', 'PVTA', 'FECHA', 'CANTIDAD', 'COMPROBANTE',
    ];

    public static function fila($venta): array
    {
        return [
            self::texto($venta->local),
            self::texto($venta->codigo),
            self::texto($venta->descripcion),
            self::texto($venta->cli_cod),
            self::texto($venta->cliente),
            self::texto($venta->vendedor),
            self::numero($venta->p_lista),
            self::numero($venta->descuento),
            self::numero($venta->p_venta),
            $venta->fecha ? Carbon::parse($venta->fecha)->format('d/m/Y') : '',
            (int) $venta->cantidad,
            self::texto($venta->comprobante),
        ];
    }

    /**
     * No cambiar el signo de NCR ni calcular de nuevo PLISTA/DTO/PVTA.
     * PostgreSQL almacena estos importes DECIMAL(18,2).
     */
    private static function numero($valor): string
    {
        $numero = number_format((float) $valor, 2, '.', '');
        $numero = rtrim(rtrim($numero, '0'), '.');
        return $numero === '' || $numero === '-0' ? '0' : $numero;
    }

    private static function texto($valor): string
    {
        $texto = (string) ($valor ?? '');
        // Evitar ejecutar fórmulas al abrir CSV en Excel. El importador
        // existente elimina la comilla de protección al volver a importar.
        if (preg_match('/^[=+@-]/', ltrim($texto))) {
            return "'" . $texto;
        }
        return $texto;
    }
}
