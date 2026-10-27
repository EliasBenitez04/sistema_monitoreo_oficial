<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccesoriosFacturasTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::connection('sqlite')->create('accesorios_articulos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('codigo', 50)->unique();
            $table->string('descripcion', 200);
            $table->string('linea', 100)->nullable();
            $table->timestamps();
        });
        Schema::connection('sqlite')->create('accesorios_compras', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('articulo_id');
            $table->string('fco_nro', 50);
            $table->date('fecha_envio');
            $table->decimal('costo_unitario', 15, 2);
            $table->decimal('precio_venta', 15, 2);
            $table->integer('cantidad_compra');
            $table->timestamps();
            $table->unique(['articulo_id', 'fco_nro', 'fecha_envio']);
        });
        Schema::connection('sqlite')->create('ventas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('codigo', 50);
            $table->integer('cantidad');
        });
        Schema::connection('sqlite')->create('ot_logistica_remisiones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('codigo', 50);
            $table->unsignedBigInteger('accesorio_articulo_id')->nullable();
        });

        DB::table('accesorios_articulos')->insert([
            ['id'=>1, 'codigo'=>'MEDNI810', 'descripcion'=>'MEDIAS NIÑOS 8/10', 'linea'=>'MEDIAS'],
            ['id'=>2, 'codigo'=>'MEDNI68', 'descripcion'=>'MEDIAS NIÑOS 6/8', 'linea'=>'MEDIAS'],
        ]);
        DB::table('accesorios_compras')->insert([
            ['id'=>10,'articulo_id'=>1,'fco_nro'=>'104','fecha_envio'=>'2026-04-28','cantidad_compra'=>35,'costo_unitario'=>12233,'precio_venta'=>20000],
            ['id'=>11,'articulo_id'=>2,'fco_nro'=>'104','fecha_envio'=>'2026-04-28','cantidad_compra'=>11,'costo_unitario'=>9000,'precio_venta'=>17000],
            ['id'=>12,'articulo_id'=>1,'fco_nro'=>'105','fecha_envio'=>'2026-05-01','cantidad_compra'=>4,'costo_unitario'=>13000,'precio_venta'=>21000],
        ]);
        DB::table('ventas')->insert(['codigo'=>'MEDNI810','cantidad'=>3]);
        DB::table('ot_logistica_remisiones')->insert(['codigo'=>'MEDNI810','accesorio_articulo_id'=>1]);

        $this->withoutMiddleware();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'codigo'=>'MEDNI810',
            'descripcion'=>'MEDIAS NIÑOS 8/10',
            'linea'=>'MEDIAS',
            'fco_nro'=>'104',
            'fecha_envio'=>'2026-04-28',
            'cantidad_compra'=>'35',
            'costo_unitario'=>'12233.00',
            'precio_venta'=>'20000.00',
        ], $overrides);
    }

    public function test_lista_facturas_por_fco_con_codigos_reales(): void
    {
        $this->get(route('accesorios.facturas.index'))
            ->assertOk()
            ->assertSee('Compras · Facturas FCO')
            ->assertSee('MEDNI810')
            ->assertSee('MEDNI68')
            ->assertSee('428.155', false);
    }

    public function test_edita_cantidad_costo_precio_fco_y_fecha_sin_tocar_movimientos(): void
    {
        $this->from(route('accesorios.facturas.index'))
            ->patch(route('accesorios.facturas.actualizar', 10), $this->payload([
                'fco_nro'=>'200', 'fecha_envio'=>'2026-10-27',
                'cantidad_compra'=>'40','costo_unitario'=>'13000.50','precio_venta'=>'24000',
            ]))
            ->assertRedirect(route('accesorios.facturas.index'))
            ->assertSessionHas('success');

        $fila = DB::table('accesorios_compras')->find(10);
        $this->assertSame('200', $fila->fco_nro);
        $this->assertSame('2026-10-27', $fila->fecha_envio);
        $this->assertSame(40, (int) $fila->cantidad_compra);
        $this->assertSame(13000.5, (float) $fila->costo_unitario);
        $this->assertSame(24000.0, (float) $fila->precio_venta);
        $this->assertEqualsWithDelta(520020, $fila->cantidad_compra * $fila->costo_unitario, 0.001);
        $this->assertSame(3, (int) DB::table('ventas')->first()->cantidad);
        $this->assertSame(1, (int) DB::table('ot_logistica_remisiones')->first()->accesorio_articulo_id);
    }

    public function test_reasigna_solo_la_compra_al_cambiar_codigo(): void
    {
        $this->from(route('accesorios.facturas.index'))
            ->patch(route('accesorios.facturas.actualizar', 10),
                $this->payload(['codigo'=>'MEDNINUEVO', 'descripcion'=>'MEDIAS NUEVO MODELO']))
            ->assertSessionHas('success');

        $idNuevo = DB::table('accesorios_articulos')->where('codigo', 'MEDNINUEVO')->value('id');
        $this->assertNotNull($idNuevo);
        $this->assertSame((int) $idNuevo, (int) DB::table('accesorios_compras')->find(10)->articulo_id);
        $this->assertSame(1, (int) DB::table('accesorios_compras')->find(12)->articulo_id);
        $this->assertSame('MEDNI810', DB::table('ventas')->first()->codigo);
        $this->assertSame(1, (int) DB::table('ot_logistica_remisiones')->first()->accesorio_articulo_id);
    }

    public function test_no_duplicar_mismo_codigo_fco_fecha(): void
    {
        $this->from(route('accesorios.facturas.index'))
            ->patch(route('accesorios.facturas.actualizar', 12),
                $this->payload(['fco_nro'=>'104','fecha_envio'=>'2026-04-28']))
            ->assertSessionHasErrors('fco_nro');

        $this->assertSame('105', DB::table('accesorios_compras')->find(12)->fco_nro);
    }

    public function test_rechaza_codigo_existente_con_descripcion_distinta_y_cantidad_invalida(): void
    {
        $this->from(route('accesorios.facturas.index'))
            ->patch(route('accesorios.facturas.actualizar', 10),
                $this->payload(['codigo'=>'MEDNI68']))
            ->assertSessionHasErrors('codigo');

        $this->from(route('accesorios.facturas.index'))
            ->patch(route('accesorios.facturas.actualizar', 10),
                $this->payload(['cantidad_compra'=>'0','costo_unitario'=>'-9']))
            ->assertSessionHasErrors(['cantidad_compra','costo_unitario']);

        $this->assertSame(35, (int) DB::table('accesorios_compras')->find(10)->cantidad_compra);
    }

    public function test_modificar_descripcion_del_mismo_codigo_actualiza_su_catalogo(): void
    {
        $this->from(route('accesorios.facturas.index'))
            ->patch(route('accesorios.facturas.actualizar', 10),
                $this->payload(['descripcion'=>'MEDIAS NIÑOS CORREGIDO']))
            ->assertSessionHas('success');

        $this->assertSame('MEDIAS NIÑOS CORREGIDO',
            DB::table('accesorios_articulos')->where('id',1)->value('descripcion'));
        $this->assertSame(1, (int) DB::table('accesorios_compras')->find(12)->articulo_id);
    }
}
