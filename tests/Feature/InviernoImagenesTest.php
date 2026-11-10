<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ArticuloImagenService;
use App\Services\InviernoTrazabilidadService;
use Carbon\Carbon;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InviernoImagenesTest extends TestCase
{
    private array $temporales = [];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-10 12:00:00');
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        Schema::create('sucursal', function (Blueprint $t) {
            $t->integer('cod_suc'); $t->string('suc_descri');
        });
        DB::table('sucursal')->insert([
            ['cod_suc' => 1, 'suc_descri' => 'CASA CENTRAL'],
            ['cod_suc' => 7, 'suc_descri' => 'BONANZA'],
        ]);
        foreach (['2026_09_18_141900_create_ot_logistica_remisiones_table.php',
            '2026_11_10_090000_create_invierno_trazabilidad_tables.php',
            '2026_10_07_160000_create_maestro_codigos_table.php',
            '2026_11_10_160000_create_articulo_imagenes_table.php'] as $migracion) {
            (require database_path('migrations/'.$migracion))->up();
        }
        Storage::fake('public');
        $this->withoutMiddleware();
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        $usuario = \Mockery::mock(User::class)->makePartial();
        $usuario->shouldReceive('hasRole')->andReturn(false);
        $usuario->shouldReceive('checkPermissionTo')->andReturn(true);
        $usuario->id = 99; $usuario->name = 'Operador';
        $this->actingAs($usuario);
        Gate::before(fn () => true);
    }

    protected function tearDown(): void
    {
        foreach ($this->temporales as $ruta) @unlink($ruta);
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a4N8AAAAASUVORK5CYII=');
    }

    private function maestro(string $codigo = '060617629AZ01', array $campos = []): void
    {
        DB::table('maestro_codigos')->insert(array_merge([
            'cod_articulo' => $codigo, 'grupo' => 'CAMPERAS', 'articulo' => 'CAMPERA INVIERNO',
        ], $campos));
    }

    private function imagen(string $nombre): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nombre, $this->png());
    }

    private function zip(array $archivos): UploadedFile
    {
        $ruta = tempnam(sys_get_temp_dir(), 'inv_fotos_');
        $this->temporales[] = $ruta;
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($ruta, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));
        foreach ($archivos as $nombre => $contenido) $zip->addFromString($nombre, $contenido);
        $zip->close();
        return new UploadedFile($ruta, 'prendas.zip', 'application/zip', null, true);
    }

    private function importar(array $datos)
    {
        return $this->post(route('invierno.imagenes.importar'), $datos);
    }

    private function documento(): int
    {
        app(InviernoTrazabilidadService::class)->registrar(['local' => '7', 'serie' => 'F', 'nro' => '1188'], null);
        $id = (int) DB::table('invierno_remisiones')->value('id');
        DB::table('ot_logistica_remisiones')->insert([
            'cod_sucursal_salida' => 7, 'sucursal_salida' => 'BONANZA',
            'cod_sucursal_destino' => 1, 'sucursal_destino' => 'CASA CENTRAL',
            'serie' => 'F', 'numero_remision' => '1188', 'codigo' => '060617629AZ01',
            'descripcion' => 'CAMPERA INVIERNO', 'cantidad' => 10,
            'fecha_remision' => '2026-10-01', 'fecha_creacion' => '2026-10-01',
            'fecha_recepcion' => '2026-10-02', 'estado' => 'RECIBIDO',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return $id;
    }

    public function test_zip_con_carpetas_asigna_el_listado_fijo_e_informa_desconocidas_y_danadas(): void
    {
        $this->maestro(); $this->maestro('060617629AZ02'); $this->maestro('060617629GR01');
        $this->maestro('CODIGO-FIJO', ['cod_imagen' => '100613678CV', 'cod_base' => '100613678']);
        $this->importar(['paquete_zip' => $this->zip([
            'Fotos/060617629 V49 C23142 11-11-25.jpeg' => $this->png(),
            'Fotos/colores/060617629AZ01.jpg' => $this->png(),
            '100613678CV V35 C13.388 OT26697 BERMUDA ECO VARÓN 08-10-26.jpg' => $this->png(),
            '999999999.jpg' => $this->png(), '060617629GR.png' => 'NO ES UNA IMAGEN',
            'notas.txt' => 'Texto', '__MACOSX/._060617629.png' => 'Ignorar',
        ])])->assertRedirect(route('invierno.imagenes.index'))
            ->assertSessionHas('imagenes_resultado', ['nuevas' => 3, 'reemplazadas' => 0, 'omitidas' => 3])
            ->assertSessionHas('imagenes_errores', fn ($e) => count($e) === 3);
        $this->assertDatabaseCount('articulo_imagenes', 3);
        $this->assertDatabaseCount('maestro_codigos', 4);
        $this->assertDatabaseCount('ot_logistica_remisiones', 0);
        $this->assertCount(3, Storage::disk('public')->allFiles());
        $fotos = app(ArticuloImagenService::class)->paraCodigos(collect(['060617629AZ01', '060617629AZ02', '060617629GR01', 'CODIGO-FIJO']));
        $this->assertSame('060617629AZ', $fotos['060617629AZ01']->codigo_imagen);
        $this->assertSame('060617629AZ', $fotos['060617629AZ02']->codigo_imagen);
        $this->assertSame('060617629', $fotos['060617629GR01']->codigo_imagen);
        $this->assertSame('100613678CV', $fotos['CODIGO-FIJO']->codigo_imagen);
        $this->assertDatabaseHas('articulo_imagenes', ['codigo_imagen' => '100613678CV',
            'valor_v' => 'V35', 'valor_c' => 'C13.388', 'ot' => '26697', 'fecha_archivo' => '2026-10-08']);
    }

    public function test_reimportar_talles_del_mismo_color_reemplaza_sin_duplicar_y_retira_archivo_anterior(): void
    {
        $this->maestro(); $this->maestro('060617629AZ02');
        $this->importar(['archivos' => [$this->imagen('060617629AZ01.png')]])->assertSessionHasNoErrors();
        $antes = DB::table('articulo_imagenes')->first();
        Carbon::setTestNow('2026-11-10 12:05:00');
        $this->importar(['archivos' => [$this->imagen('060617629AZ02.png')]])
            ->assertSessionHas('imagenes_resultado', ['nuevas' => 0, 'reemplazadas' => 1, 'omitidas' => 0]);
        $despues = DB::table('articulo_imagenes')->first();
        $this->assertDatabaseCount('articulo_imagenes', 1);
        $this->assertSame($antes->id, $despues->id);
        $this->assertSame($antes->created_at, $despues->created_at);
        $this->assertNotSame($antes->imagen, $despues->imagen);
        Storage::disk('public')->assertMissing($antes->imagen);
        Storage::disk('public')->assertExists($despues->imagen);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_no_comparte_foto_de_otro_color_y_prioriza_color_sobre_generica(): void
    {
        $this->maestro(); $this->maestro('060617629AZ02'); $this->maestro('060617629GR01');
        $this->importar(['archivos' => [$this->imagen('060617629AZ.png')]]);
        $fotos = app(ArticuloImagenService::class)->paraCodigos(collect(['060617629AZ02', '060617629GR01']));
        $this->assertSame('060617629AZ', $fotos['060617629AZ02']->codigo_imagen);
        $this->assertFalse($fotos->has('060617629GR01'));
        $this->importar(['archivos' => [$this->imagen('060617629.png')]]);
        $fotos = app(ArticuloImagenService::class)->paraCodigos(collect(['060617629AZ02', '060617629GR01']));
        $this->assertSame('060617629AZ', $fotos['060617629AZ02']->codigo_imagen);
        $this->assertSame('060617629', $fotos['060617629GR01']->codigo_imagen);
    }

    public function test_reconoce_alias_del_maestro_y_no_acepta_prefijos_de_codigos_invalidos(): void
    {
        $this->maestro(' 060617629az01 ', ['cod_imagen' => ' 100613678cv ']);
        $this->maestro('CODIGO-2', ['cod_base' => '050617500']);
        $this->maestro('123456789ZZZZEXTRA');
        $this->importar(['archivos' => [$this->imagen('100613678CV.png'), $this->imagen('050617500.png'), $this->imagen('123456789.png')]])
            ->assertSessionHas('imagenes_resultado', ['nuevas' => 2, 'reemplazadas' => 0, 'omitidas' => 1]);
        $fotos = app(ArticuloImagenService::class)->paraCodigos(collect(['060617629AZ01', 'CODIGO-2']));
        $this->assertSame('100613678CV', $fotos['060617629AZ01']->codigo_imagen);
        $this->assertSame('050617500', $fotos['CODIGO-2']->codigo_imagen);
        $this->assertDatabaseMissing('articulo_imagenes', ['codigo_imagen' => '123456789']);
    }

    public function test_foto_se_entrega_con_mime_real_sin_enlace_simbolico_y_rechaza_rutas_ajenas(): void
    {
        $this->maestro();
        $this->importar(['archivos' => [$this->imagen('060617629.jpg')]]);
        $respuesta = $this->get(route('invierno.imagenes.foto', '060617629'));
        $respuesta->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame($this->png(), $respuesta->streamedContent());
        DB::table('articulo_imagenes')->update(['imagen' => '../secretos.png']);
        $this->get(route('invierno.imagenes.foto', '060617629'))->assertNotFound();
        $this->get(route('invierno.imagenes.foto', 'CODIGO-MAL'))->assertNotFound();
    }

    public function test_muestra_imagenes_en_articulos_grupo_y_remision_sin_modificar_movimientos(): void
    {
        $this->maestro(); $documento = $this->documento();
        $antes = DB::table('ot_logistica_remisiones')->get()->toJson();
        $this->importar(['archivos' => [$this->imagen('060617629.png')]]);
        $this->get(route('invierno.imagenes.index'))->assertOk()->assertSee('Importar imágenes')->assertSee('imágenes nuevas');
        foreach ([route('invierno.articulos'), route('invierno.disponibilidad.detalle', ['grupo' => 'CAMPERAS']),
            route('invierno.show', $documento)] as $url) {
            $this->get($url)->assertOk()->assertSee('Prenda 060617629AZ01')->assertSee('loading="lazy"', false)
                ->assertSee('/prendas-invierno/imagenes/060617629/foto', false);
        }
        $this->assertSame($antes, DB::table('ot_logistica_remisiones')->get()->toJson());
        $this->assertDatabaseCount('invierno_asignaciones', 0);
    }

    public function test_resuelve_50_codigos_por_conjuntos_sin_consultas_por_prenda(): void
    {
        $codigos = collect();
        for ($t = 1; $t <= 50; $t++) {
            $codigo = '060617629AZ'.str_pad((string) $t, 2, '0', STR_PAD_LEFT);
            $this->maestro($codigo); $codigos->push($codigo);
        }
        $this->importar(['archivos' => [$this->imagen('060617629AZ.png')]]);
        $consultas = [];
        DB::listen(function (QueryExecuted $q) use (&$consultas) { $consultas[] = $q->sql; });
        $fotos = app(ArticuloImagenService::class)->paraCodigos($codigos);
        $this->assertCount(50, $fotos);
        $this->assertLessThanOrEqual(4, count($consultas));
    }

    public function test_rechaza_zip_con_mas_de_500_entradas_sin_importar_parcialmente(): void
    {
        $this->maestro();
        $archivos = ['060617629.png' => $this->png()];
        for ($i = 1; $i <= 500; $i++) $archivos['otra'.$i.'.txt'] = 'a';
        $this->postJson(route('invierno.imagenes.importar'), ['paquete_zip' => $this->zip($archivos)])
            ->assertUnprocessable()->assertJsonValidationErrors('paquete_zip');
        $this->assertDatabaseCount('articulo_imagenes', 0);
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_sin_maestro_o_sin_archivo_no_inicia_una_importacion(): void
    {
        $this->postJson(route('invierno.imagenes.importar'), ['archivos' => [$this->imagen('060617629.png')]])
            ->assertUnprocessable()->assertJsonValidationErrors('archivos');
        $this->maestro();
        $this->postJson(route('invierno.imagenes.importar'), [])->assertUnprocessable()->assertJsonValidationErrors('archivos');
        $this->assertDatabaseCount('articulo_imagenes', 0);
    }

    public function test_sin_migracion_las_pantallas_existentes_siguen_funcionando_y_se_informa_al_importar(): void
    {
        $this->maestro(); $documento = $this->documento();
        Schema::drop('articulo_imagenes');
        $this->get(route('invierno.articulos'))->assertOk()->assertSee('Sin foto');
        $this->get(route('invierno.show', $documento))->assertOk()->assertSee('Sin foto');
        $this->get(route('invierno.imagenes.index'))->assertOk()->assertSee('Falta habilitar las imágenes');
        $this->postJson(route('invierno.imagenes.importar'), ['archivos' => [$this->imagen('060617629.png')]])
            ->assertUnprocessable()->assertJsonValidationErrors('archivos');
    }

    public function test_nombres_sin_codigo_y_fecha_imposible_se_omiten_con_motivo(): void
    {
        $this->maestro();
        $this->importar(['paquete_zip' => $this->zip([
            'foto.png' => $this->png(), '060617629 31-02-25.png' => $this->png(),
        ])])->assertSessionHas('imagenes_resultado', ['nuevas' => 0, 'reemplazadas' => 0, 'omitidas' => 2]);
        $this->assertDatabaseCount('articulo_imagenes', 0);
    }

    public function test_rutas_exigen_autenticacion_y_permiso_de_importacion(): void
    {
        foreach (['imagenes.index', 'imagenes.importar', 'imagenes.foto'] as $nombre) {
            $middleware = app('router')->getRoutes()->getByName('invierno.'.$nombre)->gatherMiddleware();
            $this->assertContains('auth', $middleware);
            $this->assertContains('permission:ot index', $middleware);
            if ($nombre !== 'imagenes.foto') $this->assertContains('permission:ot importar', $middleware);
        }
        $this->withMiddleware(); auth()->logout();
        $this->get(route('invierno.imagenes.foto', '060617629'))->assertRedirect(route('login'));
        $consulta = \Mockery::mock(User::class)->makePartial();
        $consulta->shouldReceive('canAny')->andReturnUsing(fn ($p) => $p === ['ot index']);
        $consulta->shouldReceive('hasRole')->andReturn(false);
        $consulta->id = 98;
        $this->actingAs($consulta)->postJson(route('invierno.imagenes.importar'), [])->assertForbidden();
        $this->get(route('invierno.imagenes.index'))->assertRedirect(route('home'));
        $this->getJson(route('invierno.imagenes.index'))->assertForbidden();
        $this->assertDatabaseCount('articulo_imagenes', 0);
    }
}
