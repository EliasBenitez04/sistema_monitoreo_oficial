<?php

namespace Tests\Unit;

use App\Support\MayoristaPropuestaFactura;
use PHPUnit\Framework\TestCase;

class MayoristaPropuestaFacturaTest extends TestCase
{
    private function fila(int $id, int $cantidad, array $cambios = []): object
    {
        return (object) array_merge([
            'id' => $id,
            'codigo_normalizado' => 'ART01AZ01',
            'disponible' => $cantidad,
            'es_destino_matriz' => true,
            'modelo' => false,
            'origen_discordante' => false,
            'numero_remision' => (string) $id,
            'serie' => 'A',
            'fecha_remision' => '2026-09-10',
            'fecha_recepcion' => '2026-09-11',
            'origen_real' => 'PINEDO',
        ], $cambios);
    }

    private function pendientes(int $cantidad)
    {
        return collect(['ART01AZ01' => (object) ['pendiente' => $cantidad]]);
    }

    public function test_propone_una_linea_unica_sin_exceder_factura(): void
    {
        $p = MayoristaPropuestaFactura::generar(
            collect([$this->fila(10, 15)]), $this->pendientes(6), collect(), '2026-09-15'
        );
        $this->assertSame(6, $p['visibles']);
        $this->assertSame(6, $p['unicas']);
        $this->assertFalse($p['filas']->first()->ambigua);
        $this->assertSame(10, $p['filas']->first()->remision_id);
    }

    public function test_multiples_fuentes_quedan_marcadas_como_ambiguas(): void
    {
        $p = MayoristaPropuestaFactura::generar(
            collect([$this->fila(10, 4), $this->fila(11, 7)]),
            $this->pendientes(8), collect(), '2026-09-15'
        );
        $this->assertSame(8, $p['visibles']);
        $this->assertSame(2, $p['ambiguas']);
        $this->assertTrue($p['filas']->every(function ($fila) {
            return $fila->ambigua;
        }));
        $this->assertSame([4, 4], $p['filas']->pluck('cantidad')->all());
    }

    public function test_excluye_fechas_tardias_modelos_y_lineas_ya_vinculadas(): void
    {
        $p = MayoristaPropuestaFactura::generar(
            collect([
                $this->fila(10, 6, ['fecha_recepcion' => '2026-09-18']),
                $this->fila(11, 6, ['modelo' => true]),
                $this->fila(12, 6),
                $this->fila(13, 6, ['origen_discordante' => true]),
            ]),
            $this->pendientes(6),
            collect([(object) ['remision_id' => 12]]),
            '2026-09-15'
        );
        $this->assertSame(0, $p['visibles']);
        $this->assertCount(0, $p['filas']);
    }
}
