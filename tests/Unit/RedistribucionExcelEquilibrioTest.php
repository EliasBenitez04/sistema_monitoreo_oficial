<?php

namespace Tests\Unit;

use App\Http\Controllers\RedistribucionExcelController;
use ReflectionMethod;
use Tests\TestCase;

class RedistribucionExcelEquilibrioTest extends TestCase
{
    private function proponer(array $stocks, array $origenes, array $destinos, $minimo = 1, $porcentaje = 20)
    {
        $metodo = new ReflectionMethod(RedistribucionExcelController::class, 'equilibrarTalle');
        $metodo->setAccessible(true);
        $controlador = new RedistribucionExcelController();

        return $metodo->invoke(
            $controlador,
            [
                'codigo' => '080617588CE06',
                'talle' => '06',
                'descripcion' => 'ARTICULO DE PRUEBA',
                'stock' => $stocks,
            ],
            '080617588CE',
            $origenes,
            $destinos,
            $minimo,
            $porcentaje
        );
    }

    public function test_reparte_dos_destinos_sin_agotar_el_origen()
    {
        $movimientos = $this->proponer(
            ['CENTRAL' => 20, 'SL' => 0, 'SHOPP' => 0],
            ['CENTRAL'], ['SL', 'SHOPP']
        );

        $this->assertNotEmpty($movimientos);
        $total = array_sum(array_column($movimientos, 'cantidad'));
        $saldoOrigen = $movimientos[0]['origen_final'];
        $destinosFinal = [];
        foreach ($movimientos as $movimiento) {
            $destinosFinal[$movimiento['destino']] = $movimiento['destino_final'];
            $this->assertEquals('CENTRAL', $movimiento['origen']);
            $this->assertGreaterThanOrEqual(4, $movimiento['origen_final']);
        }

        $this->assertEquals(20, $total + $saldoOrigen);
        $this->assertCount(2, $destinosFinal);
        $this->assertLessThanOrEqual(1, abs($destinosFinal['SL'] - $destinosFinal['SHOPP']));
        $this->assertLessThanOrEqual(1, abs($saldoOrigen - $destinosFinal['SL']));
    }

    public function test_no_mueve_stock_si_solo_queda_la_reserva()
    {
        $movimientos = $this->proponer(
            ['CENTRAL' => 1, 'SL' => 0],
            ['CENTRAL'], ['SL']
        );

        $this->assertSame([], $movimientos);
    }

    public function test_no_envia_cuando_el_destino_tiene_mas_existencias()
    {
        $movimientos = $this->proponer(
            ['CENTRAL' => 3, 'SL' => 9],
            ['CENTRAL'], ['SL']
        );

        $this->assertSame([], $movimientos);
    }

    public function test_respeta_la_reserva_porcentual_en_cada_origen()
    {
        $movimientos = $this->proponer(
            ['CENTRAL' => 50, 'PINEDO' => 12, 'SL' => 0, 'SHOPP' => 0],
            ['CENTRAL', 'PINEDO'], ['SL', 'SHOPP'],
            1, 50
        );

        $this->assertNotEmpty($movimientos);
        foreach ($movimientos as $movimiento) {
            $this->assertGreaterThanOrEqual(
                $movimiento['reserva'],
                $movimiento['origen_final']
            );
        }
    }
}
