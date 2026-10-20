<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MayoristaRemisionesNavegacionTest extends TestCase
{
    public function test_existen_rutas_de_sucursal_y_remision(): void
    {
        $this->assertTrue(Route::has('ventas.mayoristas.sucursal'));
        $this->assertTrue(Route::has('ventas.mayoristas.remision'));
    }

    public function test_url_sucursal_multiplaza_preserva_el_periodo(): void
    {
        $url = route('ventas.mayoristas.sucursal', [
            'codigo' => 9,
            'desde' => '2026-10-01',
            'hasta' => '2026-10-20',
        ]);

        $this->assertStringContainsString('/ventas/mayoristas/sucursales?', $url);
        $this->assertStringContainsString('codigo=9', $url);
        $this->assertStringContainsString('desde=2026-10-01', $url);
        $this->assertStringContainsString('hasta=2026-10-20', $url);
    }

    public function test_url_ver_remision_apunta_al_documento_por_id(): void
    {
        $url = route('ventas.mayoristas.remision', [
            'id' => 12345,
            'desde' => '2026-10-01',
            'hasta' => '2026-10-20',
        ]);

        $this->assertStringContainsString('/ventas/mayoristas/remisiones/12345?', $url);
        $this->assertStringContainsString('hasta=2026-10-20', $url);
    }

    public function test_consultas_exigen_autenticacion(): void
    {
        $this->assertContains('auth',
            Route::getRoutes()->getByName('ventas.mayoristas.sucursal')->gatherMiddleware());
        $this->assertContains('auth',
            Route::getRoutes()->getByName('ventas.mayoristas.remision')->gatherMiddleware());
    }
}
