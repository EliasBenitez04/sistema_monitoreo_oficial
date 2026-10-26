<?php

namespace Tests\Unit;

use App\Support\MayoristaTopClientes;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class MayoristaTopClientesTest extends TestCase
{
    private function factura(
        string $cliente,
        string $codigo,
        string $fecha,
        string $comprobante,
        int $brutas,
        int $netas,
        float $importe,
        string $tipo = 'FCR'
    ): object {
        return (object) [
            'cliente' => $cliente,
            'cli_cod' => $codigo,
            'fecha' => $fecha,
            'comprobante' => $comprobante,
            'tipo_comprobante' => $tipo,
            'brutas' => $brutas,
            'devoluciones' => max(0, $brutas - $netas),
            'netas' => $netas,
            'pvta' => $importe,
        ];
    }

    public function test_ranking_cuenta_facturas_unicas_no_lineas_y_separa_nc(): void
    {
        $facturas = collect([
            $this->factura('CLIENTE A', '10', '2026-10-05', 'FCR F01 001', 3, 3, 120000),
            // Si el reporte produce dos filas del mismo comprobante,
            // nunca se debe contar dos veces el número de tickets.
            $this->factura('CLIENTE A', '10', '2026-10-05', 'FCR F01 001', 2, 2, 80000),
            $this->factura('CLIENTE A', '10', '2026-10-08', 'FCR F01 002', 5, 5, 300000),
            $this->factura('CLIENTE A', '10', '2026-10-09', 'NCR F01 009', 0, -1, -50000, 'NCR'),
            $this->factura('CLIENTE B', '11', '2026-10-05', 'FCR F01 001', 1, 1, 100000),
        ]);

        $ranking = MayoristaTopClientes::ranking($facturas);
        $this->assertCount(2, $ranking);
        $a = $ranking->first();
        $this->assertSame('CLIENTE A', $a->nombre);
        $this->assertSame(2, $a->tickets);
        $this->assertSame(1, $a->notas_credito);
        $this->assertSame(9, $a->unidades);
        $this->assertEquals(450000, $a->venta_neta);
    }

    public function test_clientes_con_igual_codigo_pero_nombre_distinto_no_se_fusionan(): void
    {
        $ranking = MayoristaTopClientes::ranking(collect([
            $this->factura('ANA', '20', '2026-10-01', 'FCR 1', 1, 1, 10000),
            $this->factura('MARIA', '20', '2026-10-01', 'FCR 2', 2, 2, 20000),
        ]));

        $this->assertCount(2, $ranking);
        $this->assertSame('MARIA', $ranking->first()->nombre);
        $this->assertSame('ANA', $ranking->last()->nombre);
    }

    public function test_token_y_filtro_identifican_al_cliente_correcto(): void
    {
        $token = MayoristaTopClientes::token(' 25 ', 'COMERCIAL ÑANDUTÍ');
        $this->assertSame(
            ['25', 'COMERCIAL ÑANDUTÍ'],
            MayoristaTopClientes::identidad($token)
        );
        $this->assertNull(MayoristaTopClientes::identidad('CLIENTE:%%INVALIDO%%'));

        $items = collect([
            $this->factura('COMERCIAL ÑANDUTÍ', '25', '2026-10-01', 'FCR 100', 2, 2, 50000),
            $this->factura('OTRO', '25', '2026-10-01', 'FCR 101', 8, 8, 200000),
        ]);
        $filtradas = MayoristaTopClientes::seleccionar($items, '25', 'COMERCIAL ÑANDUTÍ');
        $this->assertCount(1, $filtradas);
    }

    public function test_nota_de_credito_no_cuenta_como_ticket_nuevo(): void
    {
        $nota = $this->factura('A', '', '2026-10-02', 'NCR F01 90', 0, -3, -25000, 'NCR');
        $this->assertTrue(MayoristaTopClientes::esNotaCredito($nota));
        $this->assertFalse(MayoristaTopClientes::esTicket($nota));
        $totales = MayoristaTopClientes::resumen(collect([$nota]));
        $this->assertSame(0, $totales->tickets);
        $this->assertSame(1, $totales->notas_credito);
    }
}
