<?php

namespace Tests\Unit;

use App\Http\Controllers\RedistribucionExcelController;
use ReflectionMethod;
use Tests\TestCase;

class RedistribucionExcelEquilibrioTest extends TestCase
{
    private function proponer(array $stocks, array $origenes, array $destinos)
    {
        $metodo = new ReflectionMethod(RedistribucionExcelController::class, 'repartirTalle');
        $metodo->setAccessible(true);
        $controlador = new RedistribucionExcelController();

        return $metodo->invoke($controlador, [
            'codigo' => '080617588CE06',
            'talle' => '06',
            'descripcion' => 'ARTICULO DE PRUEBA',
            'stock' => $stocks,
        ], '080617588CE', $origenes, $destinos);
    }

    public function test_con_siete_deja_uno_y_reparte_dos_a_cada_destino()
    {
        $movimientos = $this->proponer(
            ['CENTRAL' => 7, 'SL' => 0, 'SHOPP' => 0, 'MARIANO' => 0],
            ['CENTRAL'], ['SL', 'SHOPP', 'MARIANO']
        );

        $porDestino = array_column($movimientos, 'cantidad', 'destino');
        $this->assertSame(2, $porDestino['SL']);
        $this->assertSame(2, $porDestino['SHOPP']);
        $this->assertSame(2, $porDestino['MARIANO']);
        $this->assertSame(1, $movimientos[0]['origen_final']);
        $this->assertSame(6, array_sum(array_column($movimientos, 'cantidad')));
    }

    public function test_shopp_recibe_primero_el_sobrante()
    {
        $movimientos = $this->proponer(
            ['CENTRAL' => 8, 'SL' => 0, 'SHOPP' => 0, 'MARIANO' => 0],
            ['CENTRAL'], ['SL', 'SHOPP', 'MARIANO']
        );

        $porDestino = array_column($movimientos, 'cantidad', 'destino');
        $this->assertSame(3, $porDestino['SHOPP']);
        $this->assertSame(2, $porDestino['SL']);
        $this->assertSame(2, $porDestino['MARIANO']);
    }

    public function test_no_mueve_si_el_origen_solo_tiene_una_unidad()
    {
        $this->assertSame([], $this->proponer(
            ['CENTRAL' => 1, 'SHOPP' => 0], ['CENTRAL'], ['SHOPP']
        ));
    }

    public function test_cada_origen_conserva_una_unidad()
    {
        $movimientos = $this->proponer(
            ['CENTRAL' => 5, 'PINEDO' => 4, 'SL' => 0, 'SHOPP' => 0],
            ['CENTRAL', 'PINEDO'], ['SL', 'SHOPP']
        );

        $saldos = [];
        foreach ($movimientos as $movimiento) {
            $saldos[$movimiento['origen']] = $movimiento['origen_final'];
        }
        $this->assertSame(1, $saldos['CENTRAL']);
        $this->assertSame(1, $saldos['PINEDO']);
    }
}
