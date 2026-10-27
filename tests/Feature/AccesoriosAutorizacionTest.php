<?php

namespace Tests\Feature;

use Tests\TestCase;

class AccesoriosAutorizacionTest extends TestCase
{
    /**
     * Un usuario no ADMIN no puede entrar por URL, descargar archivos
     * ni importar datos aunque tenga permisos generales de otros módulos.
     * El test solo inspecciona las rutas (no modifica la base de datos).
     */
    public function test_todas_las_rutas_de_accesorios_requieren_rol_admin(): void
    {
        $nombres = [
            'accesorios.index',
            'accesorios.exportar',
            'accesorios.stock.exportar',
            'accesorios.listado',
            'accesorios.importar.form',
            'accesorios.importar',
            'accesorios.show',
            'accesorios.compras.actualizar',
        ];

        foreach ($nombres as $nombre) {
            $ruta = app('router')->getRoutes()->getByName($nombre);
            $this->assertNotNull($ruta, "No existe la ruta: {$nombre}");

            $middlewares = $ruta->gatherMiddleware();
            $this->assertContains('auth', $middlewares, "La ruta {$nombre} requiere autenticación.");
            $this->assertContains('role:ADMIN', $middlewares, "La ruta {$nombre} debe exigir rol ADMIN.");
        }
    }

    public function test_controladores_de_accesorios_tambien_exigen_rol_admin(): void
    {
        foreach ([
            \App\Http\Controllers\AccesoriosController::class,
            \App\Http\Controllers\AccesoriosDashboardController::class,
        ] as $clase) {
            $middle = (new $clase())->getMiddleware();
            $names = array_column($middle, 'middleware');
            $this->assertContains('role:ADMIN', $names, "El controlador {$clase} no protege el rol.");
        }
    }
}
