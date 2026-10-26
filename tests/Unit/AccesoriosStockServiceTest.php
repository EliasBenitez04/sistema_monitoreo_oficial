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
