<?php

namespace Tests\Unit;

use App\Imports\AccesoriosCompraImport;
use PHPUnit\Framework\TestCase;

class AccesoriosCompraImportTest extends TestCase
{
    public function test_importes_en_guaranies(): void
    {
        $this->assertSame(9881.0, AccesoriosCompraImport::importe('PYG 9.881'));
        $this->assertSame(25000.0, AccesoriosCompraImport::importe('PYG         25.000'));
        $this->assertSame(9881.50, AccesoriosCompraImport::importe('9.881,50'));
        $this->assertSame(12.0, AccesoriosCompraImport::importe(12));
        $this->assertNull(AccesoriosCompraImport::importe('sin monto'));
    }

    public function test_fecha_de_envio(): void
    {
        $this->assertSame('2026-04-28', AccesoriosCompraImport::fecha('28/04/2026'));
        $this->assertSame('2026-07-16', AccesoriosCompraImport::fecha('2026-07-16'));
        $this->assertNull(AccesoriosCompraImport::fecha('31/02/2026'));
    }
}
