<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccesoriosCompraEdicionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Base de datos aislada para no tocar operaciones reales.
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::connection('sqlite')->create('accesorios_articulos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('codigo');
        });
        Schema::connection('sqlite')->create('accesorios_compras', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('articulo_id');
            $table->string('fco_nro');
            $table->date('fecha_envio');
            $table->integer('cantidad_compra');
            $table->decimal('costo_unitario', 15, 2);
            $table->decimal('precio_venta', 15, 2);
            $table->timestamps();
        });

        DB::table('accesorios_articulos')->insert([
            ['id' => 1, 'codigo' => 'BOMJUV001'],
            ['id' => 2, 'codigo' => 'OTRO001'],
        ]);
        DB::table('accesorios_compras')->insert([
            'id' => 10,
            'articulo_id' => 1,
            'fco_nro' => '104',
            'fecha_envio' => '2026-04-28',
            'cantidad_compra' => 35,
            'costo_unitario' => 12233,
            'precio_venta' => 20000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Las autorizaciones se prueban por separado en AccesoriosAutorizacionTest.
        $this->withoutMiddleware();
    }

    public function test_guarda_la_compra_correcta_y_los_resumenes_usan_los_nuevos_valores(): void
    {
        $this->patch(route('accesorios.compras.actualizar', [1, 10]), [
            'cantidad_compra' => '40',
            'costo_unitario' => '13000.50',
            'precio_venta' => '22000.00',
        ])->assertRedirect(route('accesorios.show', 1) . '#historial-compras')
            ->assertSessionHas('success');

        $compra = DB::table('accesorios_compras')->find(10);
        $this->assertSame(40, (int) $compra->cantidad_compra);
        $this->assertSame(13000.5, (float) $compra->costo_unitario);
        $this->assertSame(22000.0, (float) $compra->precio_venta);

        $resumen = DB::table('accesorios_compras')
            ->selectRaw('SUM(cantidad_compra) AS unidades, SUM(cantidad_compra * costo_unitario) AS inversion')
            ->first();
        $this->assertSame(40, (int) $resumen->unidades);
        $this->assertEqualsWithDelta(520020, (float) $resumen->inversion, 0.001);
        $this->assertEqualsWithDelta(13000.5, (float) $resumen->inversion / $resumen->unidades, 0.001);
    }

    public function test_rechaza_cantidad_o_importes_invalidos_sin_modificar_la_compra(): void
    {
        $this->from(route('accesorios.show', 1))->patch(route('accesorios.compras.actualizar', [1, 10]), [
            'cantidad_compra' => '0',
            'costo_unitario' => '-25',
            'precio_venta' => '1.234',
        ])->assertRedirect(route('accesorios.show', 1))
            ->assertSessionHasErrors(['cantidad_compra', 'costo_unitario', 'precio_venta']);

        $compra = DB::table('accesorios_compras')->find(10);
        $this->assertSame(35, (int) $compra->cantidad_compra);
        $this->assertSame(12233.0, (float) $compra->costo_unitario);
    }

    public function test_impide_editar_una_compra_que_pertenece_a_otro_articulo(): void
    {
        $this->patch(route('accesorios.compras.actualizar', [2, 10]), [
            'cantidad_compra' => '1',
            'costo_unitario' => '1',
            'precio_venta' => '1',
        ])->assertNotFound();

        $this->assertSame(35, (int) DB::table('accesorios_compras')->find(10)->cantidad_compra);
    }
}
