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

    public function test_ultima_factura_es_la_compra_mas_reciente_sin_contar_notas_de_credito(): void
    {
        $primera = $this->factura('FAMILY GROUP', '25', '2026-07-02', 'FCR 100', 10, 10, 500000);
        $primera->clave = 'factura-vieja';
        $ultima = $this->factura('FAMILY GROUP', '25', '2026-08-19', 'FCR 110', 12, 12, 600000);
        $ultima->clave = 'factura-nueva';
        $credito = $this->factura('FAMILY GROUP', '25', '2026-10-26', 'NCR 10', 0, -2, -100000, 'NCR');
        $result = MayoristaTopClientes::ranking(collect([$credito, $primera, $ultima]), '2026-10-26')->first();

        $this->assertSame('2026-08-19', $result->ultima_fecha);
        $this->assertSame('FCR 110', $result->ultimo_comprobante);
        $this->assertSame('factura-nueva', $result->ultima_clave);
        $this->assertSame(68, $result->dias_sin_compra);
        $this->assertSame('RECURRENTE', $result->estado_frecuencia);
        $this->assertSame(2, $result->fechas_compra_recientes);
        $this->assertSame(2, $result->tickets);
        $this->assertSame(1, $result->notas_credito);
    }

    public function test_facturas_repetidas_en_el_mismo_dia_no_hacen_cliente_recurrente(): void
    {
        $facturas = collect([
            $this->factura('CLIENTE DIA', '25', '2026-10-20', 'FCR 123', 3, 3, 90000),
            $this->factura('CLIENTE DIA', '25', '2026-10-20', 'FCR 123', 1, 1, 30000),
            $this->factura('CLIENTE DIA', '25', '2026-10-20', 'FCR 124', 2, 2, 60000),
        ]);
        $top = MayoristaTopClientes::ranking($facturas, '2026-10-26')->first();

        $this->assertSame(2, $top->tickets);
        $this->assertSame(1, $top->fechas_compra_recientes);
        $this->assertSame('OCASIONAL', $top->estado_frecuencia);
        $this->assertSame(6, $top->dias_sin_compra);
    }

    public function test_sin_compra_90_dias_o_mas_queda_como_sin_compras_recientes(): void
    {
        $facturas = collect([
            $this->factura('SIN MOVIMIENTO', '25', '2026-04-01', 'FCR 1', 1, 1, 10000),
            $this->factura('SIN MOVIMIENTO', '25', '2026-05-01', 'FCR 2', 1, 1, 10000),
            $this->factura('SIN MOVIMIENTO', '25', '2026-10-24', 'NCR 1', 0, -1, -5000, 'NCR'),
        ]);
        $top = MayoristaTopClientes::ranking($facturas, '2026-10-26')->first();

        $this->assertSame('2026-05-01', $top->ultima_fecha);
        $this->assertSame('SIN COMPRAS RECIENTES', $top->estado_frecuencia);
        $this->assertSame(0, $top->fechas_compra_recientes);
    }

    public function test_el_corte_del_filtro_define_la_antiguedad_y_no_la_fecha_del_servidor(): void
    {
        $ventas = collect([
            $this->factura('CLIENTE', '25', '2026-08-01', 'FCR 1', 1, 1, 1000),
            $this->factura('CLIENTE', '25', '2026-08-05', 'FCR 2', 1, 1, 1000),
        ]);
        $top = MayoristaTopClientes::ranking($ventas, '2026-08-31')->first();

        $this->assertSame(26, $top->dias_sin_compra);
        $this->assertSame('RECURRENTE', $top->estado_frecuencia);
    }

    public function test_periodo_octubre_con_historial_completo_no_marca_ocasional_por_error(): void
    {
        $periodo = collect([
            $this->factura('FAMILY GROUP SRL','25','2026-10-26','FCO F01 177',10,10,400000),
            $this->factura('TUPI','25','2026-10-23','FCR F01 175',5,5,200000),
        ]);
        $historial = $periodo->concat([
            $this->factura('FAMILY GROUP SRL','25','2026-09-10','FCR F01 122',6,6,180000),
            $this->factura('FAMILY GROUP SRL','25','2026-08-15','FCR F01 100',4,4,120000),
            $this->factura('TUPI','25','2026-06-10','FCR 8',1,1,10000),
            $this->factura('CLIENTE SIN MOVIMIENTO','25','2026-04-12','FCR 1',10,10,200000),
        ]);
        $ranking = MayoristaTopClientes::ranking($periodo,'2026-10-26',$historial);

        $this->assertCount(3,$ranking);
        $family = $ranking->first(function ($c) {return $c->nombre==='FAMILY GROUP SRL';});
        $this->assertSame(1,$family->tickets);
        $this->assertSame(3,$family->tickets_historicos);
        $this->assertSame('2026-10-26',$family->ultima_fecha);
        $this->assertSame('RECURRENTE',$family->estado_frecuencia);
        $this->assertSame(3,$family->fechas_compra_recientes);
        $this->assertSame(400000.0,$family->venta_neta);

        $sinPeriodo = $ranking->first(function ($c) {return $c->nombre==='CLIENTE SIN MOVIMIENTO';});
        $this->assertSame('SIN COMPRAS RECIENTES',$sinPeriodo->estado_frecuencia);
        $this->assertFalse($sinPeriodo->con_movimientos_periodo);
        $this->assertSame('2026-04-12',$sinPeriodo->ultima_fecha);
        $this->assertSame(0,$sinPeriodo->tickets);
        $this->assertSame(1,$sinPeriodo->tickets_historicos);
        $this->assertSame(0.0,$sinPeriodo->venta_neta);
    }

    public function test_nota_de_credito_actual_no_cambia_ultima_compra_ni_hace_recurrente(): void
    {
        $credito = $this->factura('FAMILY GROUP','25','2026-10-26','NCR F01 70',0,-68,-8493000,'NCR');
        $periodo = collect([$credito]);
        $historial = $periodo->concat([
            $this->factura('FAMILY GROUP','25','2026-05-15','FCR F01 11',100,100,2000000),
        ]);
        $top = MayoristaTopClientes::ranking($periodo,'2026-10-26',$historial)->first();
        $this->assertTrue($top->con_movimientos_periodo);
        $this->assertSame(-8493000.0,$top->venta_neta);
        $this->assertSame(0,$top->tickets);
        $this->assertSame(1,$top->notas_credito);
        $this->assertSame('2026-05-15',$top->ultima_fecha);
        $this->assertSame('SIN COMPRAS RECIENTES',$top->estado_frecuencia);
    }

    public function test_historicos_sin_movimiento_no_ocultan_un_cliente_con_saldo_negativo_del_periodo(): void
    {
        $credito = $this->factura('DEVOLUCION','25','2026-10-26','NCR F01 99',0,-3,-50000,'NCR');
        $ventas = collect([$credito]);
        $historial = $ventas->concat([
            $this->factura('INACTIVO','25','2026-01-10','FCR 1',100,100,1000000),
        ]);
        $ranking = MayoristaTopClientes::ranking($ventas,'2026-10-26',$historial);
        $this->assertSame('DEVOLUCION',$ranking->first()->nombre);
        $this->assertSame('INACTIVO',$ranking->last()->nombre);
    }

    public function test_no_usa_documentos_futuros_para_ultima_factura(): void
    {
        $periodo = collect([
            $this->factura('CLIENTE','25','2026-09-25','FCR 1',1,1,10000),
        ]);
        $historial = $periodo->concat([
            $this->factura('CLIENTE','25','2026-11-01','FCR 2',1,1,10000),
        ]);
        $c = MayoristaTopClientes::ranking($periodo,'2026-10-26',$historial)->first();
        $this->assertSame('2026-09-25',$c->ultima_fecha);
        $this->assertSame(1,$c->tickets_historicos);
    }
}
