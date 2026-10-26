<?php

namespace Tests\Unit;

use App\Services\AccesoriosStockService;
use PHPUnit\Framework\TestCase;

class AccesoriosStockServiceTest extends TestCase
{
    public function test_stock_de_una_remision_de_deposito_repartida_a_locales(): void
    {
        // Depósito remite 26 a Casa Central, que despacha 8+6+6+6.
        $this->assertSame(0, AccesoriosStockService::saldo(26, 26, 0));
        $this->assertSame(8, AccesoriosStockService::saldo(8, 0, 0));
        $this->assertSame(6, AccesoriosStockService::saldo(6, 0, 0));
    }

    public function test_una_compra_de_siete_no_se_duplica_al_distribuir_desde_central(): void
    {
        // I-52569: Depósito -> Casa Central, 7 unidades efectivamente recibidas.
        $ingreso = AccesoriosStockService::clasificarFlujo(null, 'S:1', true, true, 7);
        // 1-43889: Casa Central -> local, las MISMAS 7 unidades.
        $envio = AccesoriosStockService::clasificarFlujo('S:1', 'S:16', false, true, 7);

        $this->assertSame(7, $ingreso['ingreso_deposito']);
        $this->assertSame(0, $ingreso['distribuido_central']);
        $this->assertSame(0, $ingreso['recibido_locales']);

        $this->assertSame(0, $envio['ingreso_deposito']);
        $this->assertSame(7, $envio['distribuido_central']);
        $this->assertSame(7, $envio['recibido_locales']);

        // En lugar de presentar 7+7=14 como "remitidas", mostramos
        // 7 por etapa. Luego descontar 4 ventas produce un stock de 3.
        $this->assertSame(3, AccesoriosStockService::saldo(7, 0, 4));
        $this->assertSame(0, AccesoriosStockService::saldo(7, 7, 0));
    }

    public function test_remision_sin_recepcion_aun_no_suma_en_recibidas_del_local(): void
    {
        $envio = AccesoriosStockService::clasificarFlujo('S:1', 'S:16', false, false, 7);
        $this->assertSame(7, $envio['distribuido_central']);
        $this->assertSame(0, $envio['recibido_locales']);
    }

    public function test_traslado_entre_locales_es_otro_movimiento_y_no_otra_compra(): void
    {
        $mov = AccesoriosStockService::clasificarFlujo('S:16', 'S:4', false, true, 2);
        $this->assertSame(2, $mov['traslado_locales']);
        $this->assertSame(0, $mov['ingreso_deposito']);
        $this->assertSame(0, $mov['distribuido_central']);
    }

    public function test_venta_y_transferencia_recibida_afectan_el_saldo_una_vez(): void
    {
        $this->assertSame(2, AccesoriosStockService::saldo(6, 0, 4));
        // Llegaron 3 más desde otro local: 6 + 3 - 4 = 5.
        $this->assertSame(5, AccesoriosStockService::saldo(9, 0, 4));
        // El local origen descuenta el envío incluso si sigue en tránsito.
        $this->assertSame(3, AccesoriosStockService::saldo(8, 3, 2));
    }

    public function test_nota_de_credito_negativa_devuelve_existencias(): void
    {
        // Venta neta es -1 después de una devolución.
        $this->assertSame(7, AccesoriosStockService::saldo(6, 0, -1));
    }

    public function test_saldo_negativo_se_mantiene_como_incidencia(): void
    {
        $this->assertSame(-2, AccesoriosStockService::saldo(6, 0, 8));
    }

    public function test_proveedor_de_insumos_no_es_sucursal_de_venta(): void
    {
        $this->assertTrue(AccesoriosStockService::esDeposito('DEPOSITO DE INSUMOS / TEJIDOS'));
        $this->assertTrue(AccesoriosStockService::esDeposito('DEPÓSITO DE INSUMOS'));
        $this->assertFalse(AccesoriosStockService::esDeposito('SHOPPING MARIANO'));
    }
}
