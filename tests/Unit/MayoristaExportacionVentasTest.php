<?php

namespace Tests\Unit;

use App\Support\MayoristaExportacionVentas;
use PHPUnit\Framework\TestCase;

class MayoristaExportacionVentasTest extends TestCase
{
    public function test_columnas_coinciden_exactamente_con_el_importador(): void
    {
        $this->assertSame([
            'LOCAL', 'CODIGO', 'DESCRIPCION', 'CLI COD', 'CLIENTE',
            'VENDEDOR', 'PLISTA', 'DTO', 'PVTA', 'FECHA', 'CANTIDAD', 'COMPROBANTE',
        ], MayoristaExportacionVentas::ENCABEZADOS);
    }

    public function test_preserva_codigo_con_cero_inicial_y_valores_de_nota_de_credito(): void
    {
        $fila = MayoristaExportacionVentas::fila((object) [
            'local' => 'COMERCIAL MATRIZ',
            'codigo' => '060616164VD16',
            'descripcion' => 'REMERA T.P M/C JUV VARON',
            'cli_cod' => '25',
            'cliente' => 'TUPI RAMOS GENERALES S.A',
            'vendedor' => 'ANGELES MARITTE LEDESMA PATIÑO',
            'p_lista' => '-68000.00',
            'descuento' => '0.00',
            'p_venta' => '-68000.00',
            'fecha' => '2026-01-04',
            'cantidad' => -1,
            'comprobante' => 'NCR F01 17',
        ]);

        $this->assertSame(12, count($fila));
        $this->assertSame('060616164VD16', $fila[1]);
        $this->assertSame('-68000', $fila[6]);
        $this->assertSame('0', $fila[7]);
        $this->assertSame('-68000', $fila[8]);
        $this->assertSame('04/01/2026', $fila[9]);
        $this->assertSame(-1, $fila[10]);
        $this->assertSame('NCR F01 17', $fila[11]);
    }

    public function test_exportacion_mantiene_descuentos_y_montos_originales(): void
    {
        $fila = MayoristaExportacionVentas::fila((object) [
            'local' => 'COMERCIAL MATRIZ',
            'codigo' => '110615192GR10',
            'descripcion' => 'PANTALON TERRI',
            'cli_cod' => '25',
            'cliente' => 'TUPI RAMOS GENERALES S.A',
            'vendedor' => 'GOTITAS',
            'p_lista' => '69000.00',
            'descuento' => '20700.00',
            'p_venta' => '48300.00',
            'fecha' => '2026-01-06',
            'cantidad' => 1,
            'comprobante' => 'FCR F01 85',
        ]);

        $this->assertSame('69000', $fila[6]);
        $this->assertSame('20700', $fila[7]);
        $this->assertSame('48300', $fila[8]);
        $this->assertSame('06/01/2026', $fila[9]);
        $this->assertSame(1, $fila[10]);
    }

    public function test_texto_con_riesgo_de_formula_se_exporta_como_texto(): void
    {
        $fila = MayoristaExportacionVentas::fila((object) [
            'local' => 'COMERCIAL MATRIZ',
            'codigo' => 'TEST',
            'descripcion' => '=SUM(1+2)',
            'cli_cod' => null,
            'cliente' => null,
            'vendedor' => null,
            'p_lista' => 0,
            'descuento' => 0,
            'p_venta' => 0,
            'fecha' => '2026-01-01',
            'cantidad' => 1,
            'comprobante' => 'FCR 1',
        ]);
        $this->assertSame("'=SUM(1+2)", $fila[2]);
        $this->assertSame('', $fila[3]);
    }
}
