<?php

namespace Tests\Unit;

use App\Support\MayoristaConciliacion as Conciliador;
use PHPUnit\Framework\TestCase;

class MayoristaDescripcionFacturaTest extends TestCase
{
    public function test_descripciones_corresponden_a_codigos_completos_con_talle(): void
    {
        $lineas = collect([
            (object) ['codigo' => '040617574CEGG', 'descripcion' => 'REMERA MODELO TALLE GG', 'cantidad' => 3],
            (object) ['codigo' => '040617574CERN', 'descripcion' => 'REMERA MODELO TALLE RN', 'cantidad' => 2],
            (object) ['codigo' => '050617434AZ06', 'descripcion' => 'PANTALÓN AZUL 06', 'cantidad' => 1],
        ]);

        $mapa = Conciliador::descripcionesPorCodigo($lineas);

        $this->assertSame('REMERA MODELO TALLE GG', $mapa['040617574CEGG']);
        $this->assertSame('REMERA MODELO TALLE RN', $mapa['040617574CERN']);
        $this->assertSame('PANTALÓN AZUL 06', $mapa['050617434AZ06']);
        $this->assertCount(3, $mapa);
    }

    public function test_mantiene_primera_descripcion_no_vacia_del_codigo_y_no_usa_notas_de_credito(): void
    {
        $lineas = collect([
            (object) ['codigo' => '060617426vd04 ', 'descripcion' => '', 'cantidad' => 1],
            (object) ['codigo' => ' 060617426VD04', 'descripcion' => ' REMERA VERDE 04 ', 'cantidad' => 2],
            (object) ['codigo' => '060617426VD04', 'descripcion' => 'OTRO TEXTO', 'cantidad' => 1],
            (object) ['codigo' => '060617426VD06', 'descripcion' => 'DEVOLUCIÓN', 'cantidad' => -1],
            (object) ['codigo' => '060617426VD08', 'descripcion' => null, 'cantidad' => 1],
        ]);

        $mapa = Conciliador::descripcionesPorCodigo($lineas);

        $this->assertSame('REMERA VERDE 04', $mapa['060617426VD04']);
        $this->assertArrayNotHasKey('060617426VD06', $mapa);
        $this->assertArrayNotHasKey('060617426VD08', $mapa);
    }
}
