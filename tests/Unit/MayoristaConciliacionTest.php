<?php

namespace Tests\Unit;

use App\Support\MayoristaConciliacion as C;
use PHPUnit\Framework\TestCase;

class MayoristaConciliacionTest extends TestCase
{
    public function test_720_prendas_una_factura_una_remision_es_probable_no_confirmada(): void
    {
        $this->assertSame('PROBABLE',C::estado(
            ['060617424AZ01'=>720],[],
            [['codigo'=>'060617424AZ01','disponible'=>720]]
        ));
    }

    public function test_720_prendas_tres_remisiones_es_ambigua_hasta_validar(): void
    {
        $r=[
            ['codigo'=>'060617424AZ01','disponible'=>300],
            ['codigo'=>'060617424AZ01','disponible'=>220],
            ['codigo'=>'060617424AZ01','disponible'=>200]
        ];
        $this->assertSame('AMBIGUA',C::estado(['060617424AZ01'=>720],[],$r));
        $this->assertSame('PARCIAL',C::estado(['060617424AZ01'=>720],
            ['060617424AZ01'=>300],$r));
        $this->assertSame('CONFIRMADA DOCUMENTALMENTE',C::estado(
            ['060617424AZ01'=>720],['060617424AZ01'=>720],$r));
    }

    public function test_varias_facturas_comparten_remision_sin_sobreasignar(): void
    {
        $this->assertSame(300,C::capacidad(300,720));
        $this->assertSame(420,C::capacidad(500,420));
        $this->assertSame(0,C::capacidad(200,0));
        $this->assertSame(0,C::capacidad(0,100));
        $this->assertSame(0,C::capacidad(-2,100));
    }

    public function test_codigos_de_articulo_distintos_no_son_coincidencia(): void
    {
        $this->assertSame('SIN COINCIDENCIA',C::estado(
            ['060617424AZ01'=>720],[],
            [['codigo'=>'060617424AZ02','disponible'=>720]]
        ));
    }

    public function test_nota_de_credito_no_se_asigna_a_remisiones_como_venta(): void
    {
        $this->assertSame('NOTA DE CRÉDITO / SIN UNIDADES POSITIVAS',
            C::estado([],[],[]));
    }

    public function test_stock_anterior_sin_respaldo_no_se_supone_cero(): void
    {
        $this->assertNull(C::saldoDocumental(null,720,600));
        $this->assertSame(120,C::saldoDocumental(0,720,600));
        $this->assertSame(320,C::saldoDocumental(200,720,600));
    }

    public function test_clave_factura_separa_clientes_y_fechas(): void
    {
        $clave=C::claveFactura('2026-10-15','FAC-001','100','CLIENTE A');
        $this->assertSame(40,strlen($clave));
        $this->assertNotSame($clave,C::claveFactura('2026-10-15','FAC-001','101','CLIENTE B'));
        $this->assertNotSame($clave,C::claveFactura('2026-09-15','FAC-001','100','CLIENTE A'));
    }

    public function test_coincidencia_usa_codigo_completo_no_base_de_ot(): void
    {
        $this->assertSame('060617424AZ01',C::codigo(' 060617424az01 '));
        $this->assertNotSame(C::codigo('060617424AZ01'),C::codigo('060617424AZ02'));
    }
}
