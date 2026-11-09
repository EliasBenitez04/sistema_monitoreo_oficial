<?php

namespace Tests\Feature;

use App\Services\AccesoriosStockService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccesoriosRetencionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-09 12:00:00');
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::connection()->getPdo()->sqliteCreateFunction('BTRIM', 'trim', 1);

        Schema::create('sucursal', function (Blueprint $t) {
            $t->integer('cod_suc');
            $t->string('suc_descri');
        });
        Schema::create('accesorios_articulos', function (Blueprint $t) {
            $t->increments('id');
            $t->string('codigo');
            $t->string('descripcion');
            $t->string('linea')->nullable();
        });
        Schema::create('accesorios_compras', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('articulo_id');
            $t->string('fco_nro');
            $t->date('fecha_envio');
        });
        Schema::create('ot_logistica_remisiones', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('accesorio_articulo_id');
            $t->integer('cod_sucursal_salida');
            $t->string('sucursal_salida');
            $t->integer('cod_sucursal_destino');
            $t->string('sucursal_destino');
            $t->integer('cantidad');
            $t->date('fecha_remision');
            $t->date('fecha_creacion')->nullable();
            $t->date('fecha_recepcion')->nullable();
        });
        Schema::create('ventas', function (Blueprint $t) {
            $t->increments('id');
            $t->string('codigo');
            $t->string('local');
            $t->date('fecha');
            $t->string('comprobante');
            $t->integer('cantidad');
        });
        Schema::create('accesorios_stock_ajustes', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('accesorio_articulo_id');
            $t->string('sucursal_key');
            $t->string('sucursal');
            $t->integer('diferencia');
            $t->date('fecha_ajuste');
        });
        DB::table('sucursal')->insert([
            ['cod_suc' => 1, 'suc_descri' => 'CASA CENTRAL'],
            ['cod_suc' => 7, 'suc_descri' => 'BONANZA'],
            ['cod_suc' => 16, 'suc_descri' => 'SHOPPING MARIANO'],
        ]);
        foreach (range(1, 6) as $id) {
            DB::table('accesorios_articulos')->insert([
                'id' => $id, 'codigo' => 'ACC' . $id, 'descripcion' => 'Accesorio ' . $id, 'linea' => 'MEDIAS',
            ]);
        }
        DB::table('accesorios_compras')->insert([
            ['articulo_id' => 1, 'fco_nro' => '1', 'fecha_envio' => '2026-09-01'],
            ['articulo_id' => 2, 'fco_nro' => '2', 'fecha_envio' => '2026-08-01'],
        ]);
        $this->recibir(1, 16, 20, '2026-10-01');
        $this->recibir(1, 16, 2, '2026-11-08');
        $this->recibir(1, 7, 10, '2026-09-10');
        $this->recibir(2, 7, 4, '2026-09-01');
        $this->recibir(3, 16, 2, '2026-10-01');
        $this->recibir(4, 16, 7, null); // En tránsito: no hay stock disponible.
        $this->recibir(5, 16, 7, '2026-11-10'); // Fuera del corte.
        $this->vender(1, 'MARIANO', '2026-10-15', 'FCR 100', 2);
        $this->vender(1, 'SHOPPING MARIANO', '2026-11-01', 'FCR 102', 3);
        $this->vender(1, 'MARIANO', '2026-11-08', 'NCR 103', -1);
        $this->vender(1, 'MARIANO', '2026-11-10', 'FCR FUTURA', 1);
        $this->vender(1, 'BONANZA', '2026-11-08', 'FCR BONANZA', 1);
        $this->vender(3, 'MARIANO', '2026-10-02', 'FCR AGOTADO', 2);
        DB::table('accesorios_stock_ajustes')->insert([
            'accesorio_articulo_id' => 6, 'sucursal_key' => 'S:16',
            'sucursal' => 'SHOPPING MARIANO', 'diferencia' => 3, 'fecha_ajuste' => '2026-11-05',
        ]);
        $this->withoutMiddleware();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function recibir(int $articulo, int $sucursal, int $cantidad, ?string $fecha): void
    {
        DB::table('ot_logistica_remisiones')->insert([
            'accesorio_articulo_id' => $articulo, 'cod_sucursal_salida' => 1,
            'sucursal_salida' => 'CASA CENTRAL', 'cod_sucursal_destino' => $sucursal,
            'sucursal_destino' => $sucursal === 16 ? 'SHOPPING MARIANO' : 'BONANZA',
            'cantidad' => $cantidad, 'fecha_remision' => $fecha ?: '2026-10-01',
            'fecha_recepcion' => $fecha,
        ]);
    }

    private function vender(int $articulo, string $local, string $fecha, string $comprobante, int $cantidad): void
    {
        DB::table('ventas')->insert([
            'codigo' => 'ACC' . $articulo, 'local' => $local, 'fecha' => $fecha,
            'comprobante' => $comprobante, 'cantidad' => $cantidad,
        ]);
    }

    private function filas(string $corte = '2026-11-09', string $sucursal = '', string $orden = 'dias')
    {
        $servicio = new AccesoriosStockService();
        $catalogo = DB::table('accesorios_articulos')->get();
        return $servicio->retencion($catalogo,
            $servicio->calcular($catalogo->pluck('id')->all(), $corte, $sucursal), $corte, $orden);
    }

    public function test_retencion_por_local_unifica_alias_y_no_reinicia_con_devoluciones(): void
    {
        $filas = $this->filas();
        $this->assertCount(4, $filas); // Dos sucursales de ACC1, ACC2 y ACC6.
        $this->assertSame('ACC2', $filas->first()->codigo);
        $this->assertSame(69, $filas->first()->dias_retenidos);
        $this->assertNull($filas->first()->ultima_venta);
        $mariano = $filas->first(function ($f) { return $f->codigo === 'ACC1' && $f->sucursal === 'SHOPPING MARIANO'; });
        $this->assertSame(18, $mariano->cantidad);
        $this->assertSame('2026-10-01', $mariano->fecha_llegada);
        $this->assertSame('2026-11-08', $mariano->ultima_recepcion);
        $this->assertSame(39, $mariano->dias_local);
        $this->assertSame('FCR 102', $mariano->ultima_factura);
        $this->assertSame('2026-11-01', $mariano->ultima_venta);
        $this->assertSame(8, $mariano->dias_retenidos);
        $bonanza = $filas->first(function ($f) { return $f->codigo === 'ACC1' && $f->sucursal === 'BONANZA'; });
        $this->assertSame('FCR BONANZA', $bonanza->ultima_factura);
        $this->assertSame(1, $bonanza->dias_retenidos);
        $this->assertSame('ACC6', $filas->last()->codigo);
        $this->assertNull($filas->last()->dias_retenidos);
        $this->assertNull($filas->last()->fecha_llegada);
    }

    public function test_corte_historico_excluye_recepciones_y_facturas_posteriores(): void
    {
        $fila = $this->filas('2026-10-20', 'MARIANO')->sole();
        $this->assertSame(18, $fila->cantidad);
        $this->assertSame('FCR 100', $fila->ultima_factura);
        $this->assertSame(5, $fila->dias_retenidos);
        $this->assertSame(19, $fila->dias_local);
    }

    public function test_factura_anterior_a_llegada_no_aumenta_retencion(): void
    {
        $this->vender(2, 'BONANZA', '2026-08-01', 'FCR ANTIGUA', 1);
        $fila = $this->filas()->firstWhere('codigo', 'ACC2');
        $this->assertSame(69, $fila->dias_retenidos);
        $this->assertTrue($fila->desde_llegada);
    }

    public function test_ultima_factura_del_mismo_dia_tiene_desempate_estable_y_descarta_ncr(): void
    {
        $this->vender(1, 'MARIANO', '2026-11-01', 'FCR 104', 1);
        $this->vender(1, 'MARIANO', '2026-11-08', 'NCR POSITIVA', 1);
        $fila = $this->filas('2026-11-09', 'MARIANO')->firstWhere('codigo', 'ACC1');
        $this->assertSame('FCR 104', $fila->ultima_factura);
        $this->assertSame(8, $fila->dias_retenidos);
    }

    public function test_modal_respeta_fco_sucursal_y_consulta_historial_anterior_a_desde(): void
    {
        $respuesta = $this->get(route('accesorios.detalle.resumen', [
            'tipo' => 'retencion', 'desde' => '2026-11-05', 'hasta' => '2026-11-09',
            'fco' => '1', 'sucursal' => 'MARIANO',
        ]));
        $respuesta->assertOk()->assertViewIs('accesorios.detalle_retencion')
            ->assertSee('Última factura / venta')->assertSee('Días retenidos')
            ->assertSee('FCR 102')->assertSee('01/11/2026')->assertDontSee('FCR BONANZA');
        $fila = $respuesta->viewData('retencion')->sole();
        $this->assertSame('ACC1', $fila->codigo);
        $this->assertSame(8, $fila->dias_retenidos);
    }

    public function test_modal_ordena_por_dias_cantidad_y_antiguedad_con_fechas_desconocidas_al_final(): void
    {
        $base = ['tipo' => 'retencion', 'hasta' => '2026-11-09'];
        foreach (['dias' => 'ACC2', 'cantidad' => 'ACC1', 'antiguedad' => 'ACC2'] as $orden => $primero) {
            $respuesta = $this->get(route('accesorios.detalle.resumen', $base + ['orden_detalle' => $orden]));
            $respuesta->assertOk();
            $this->assertSame($primero, $respuesta->viewData('retencion')->first()->codigo);
        }
    }

    public function test_modal_sin_existencias_y_orden_invalido(): void
    {
        $this->get(route('accesorios.detalle.resumen', ['tipo' => 'retencion', 'fco' => 'INEXISTENTE']))
            ->assertOk()->assertSee('No hay artículos con stock positivo');
        $this->getJson(route('accesorios.detalle.resumen', ['tipo' => 'retencion', 'orden_detalle' => 'invalido']))
            ->assertStatus(422);
    }
}
