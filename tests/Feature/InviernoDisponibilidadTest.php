<?php

namespace Tests\Feature;

use App\Exports\InviernoDisponibilidadExport;
use App\Exports\InviernoArticulosExport;
use App\Models\User;
use App\Services\InviernoDisponibilidadService;
use App\Services\InviernoTrazabilidadService;
use Carbon\Carbon;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class InviernoDisponibilidadTest extends TestCase
{
    private InviernoDisponibilidadService $service;
    private InviernoTrazabilidadService $trazabilidad;
    private int $numero = 900;

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
            ['cod_suc' => 16, 'suc_descri' => 'SHOPPING MARIANO'],
        ]);
        (require database_path('migrations/2026_09_18_141900_create_ot_logistica_remisiones_table.php'))->up();
        (require database_path('migrations/2026_11_10_090000_create_invierno_trazabilidad_tables.php'))->up();
        (require database_path('migrations/2026_10_07_160000_create_maestro_codigos_table.php'))->up();
        $this->service = app(InviernoDisponibilidadService::class);
        $this->trazabilidad = app(InviernoTrazabilidadService::class);
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
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function documento(int $local = 7, string $serie = 'F', string $numero = '1188'): int
    {
        $this->trazabilidad->registrar(['local' => (string) $local, 'serie' => $serie, 'nro' => $numero], null);
        return (int) DB::table('invierno_remisiones')->orderByDesc('id')->value('id');
    }

    private function entrada(int $documento, string $codigo = '060617629AZ01', int $cantidad = 10, array $cambios = []): int
    {
        $d = DB::table('invierno_remisiones')->find($documento);
        return (int) DB::table('ot_logistica_remisiones')->insertGetId(array_merge([
            'cod_sucursal_salida' => $d->cod_sucursal_origen, 'sucursal_salida' => $d->local,
            'cod_sucursal_destino' => 1, 'sucursal_destino' => 'CASA CENTRAL',
            'serie' => $d->serie, 'numero_remision' => $d->numero_remision,
            'codigo' => $codigo, 'descripcion' => 'CAMPERA INVIERNO', 'cantidad' => $cantidad,
            'fecha_remision' => '2026-10-01', 'fecha_creacion' => '2026-10-01',
            'fecha_recepcion' => '2026-10-02', 'estado' => 'RECIBIDO', 'created_at' => now(), 'updated_at' => now(),
        ], $cambios));
    }

    private function salida(int $documento, string $codigo = '060617629AZ01', int $cantidad = 6, bool $recibida = false): int
    {
        return $this->entrada($documento, $codigo, $cantidad, [
            'cod_sucursal_salida' => 1, 'sucursal_salida' => 'CASA CENTRAL',
            'cod_sucursal_destino' => 16, 'sucursal_destino' => 'SHOPPING MARIANO',
            'serie' => 'S1', 'numero_remision' => (string) ++$this->numero,
            'fecha_remision' => '2026-10-03', 'fecha_creacion' => '2026-10-03',
            'fecha_recepcion' => $recibida ? '2026-10-04' : null, 'estado' => $recibida ? 'RECIBIDO' : 'EN_TRANSITO',
        ]);
    }

    private function maestro(string $codigo, ?string $grupo, ?string $plan = null): void
    {
        DB::table('maestro_codigos')->insert(['cod_articulo' => $codigo, 'grupo' => $grupo, 'grupo_plan' => $plan]);
    }

    public function test_acumula_todos_los_origenes_por_grupo_y_ordena_mayor_disponibilidad_sin_remisiones_ajenas(): void
    {
        $a = $this->documento(); $b = $this->documento(16, 'S', '1303');
        $this->maestro('060617629AZ01', ' Camperas '); $this->maestro('060617629AZ02', 'CAMPERAS');
        $this->maestro('050617500AZ01', 'BUZOS');
        $this->entrada($a); $this->entrada($a, '060617629AZ02', 15);
        $this->entrada($b, '060617629AZ01', 20); $this->entrada($b, '050617500AZ01', 12);
        $this->entrada($a, '060617629AZ01', 999, ['numero_remision' => '9999']);
        $this->salida($a, '060617629AZ01', 100);
        $datos = $this->service->consultar();
        $this->assertSame(['CAMPERAS', 'BUZOS'], $datos['grupos']->pluck('grupo')->all());
        $this->assertSame(45, $datos['grupos']->first()->disponible);
        $this->assertSame(2, $datos['grupos']->first()->documentos);
        $this->assertSame(2, $datos['grupos']->first()->codigos);
        $this->assertSame(57, $datos['resumen']['disponible']);
        $this->assertSame(2, $datos['resumen']['documentos']);
        $this->assertSame(3, $datos['resumen']['codigos']);
        $this->assertCount(4, $datos['lineas']);
    }

    public function test_descontar_solo_cantidad_asociada_y_separar_recibido_local_de_transito(): void
    {
        $d = $this->documento(); $entrada = $this->entrada($d);
        $salidaA = $this->salida($d, '060617629AZ01', 20, true);
        $salidaB = $this->salida($d, '060617629AZ01', 20);
        $this->trazabilidad->asociar($d, $entrada, $salidaA, 2, null, null);
        $this->trazabilidad->asociar($d, $entrada, $salidaB, 3, null, null);
        $r = $this->service->consultar()['resumen'];
        $this->assertSame(10, $r['central']); $this->assertSame(5, $r['remitido']);
        $this->assertSame(2, $r['recibido_local']); $this->assertSame(3, $r['transito_local']);
        $this->assertSame(5, $r['disponible']);
    }

    public function test_pendientes_y_recepciones_futuras_no_se_cuentan_disponibles_y_faltantes_no_inventan_prendas(): void
    {
        $a = $this->documento(); $b = $this->documento(16, 'S', '1303'); $this->documento(7, 'F', '1190');
        $this->entrada($a, '060617629AZ01', 8, ['fecha_recepcion' => null, 'estado' => 'EN_TRANSITO']);
        $this->entrada($b, '060617629AZ01', 5, ['fecha_recepcion' => '2026-12-01']);
        $r = $this->service->consultar()['resumen'];
        $this->assertSame(13, $r['prendas']); $this->assertSame(13, $r['pendiente']);
        $this->assertSame(0, $r['central']); $this->assertSame(0, $r['disponible']);
        $this->assertSame(1, $r['sin_detalle']); $this->assertSame(1, $r['lineas_revision']);
    }

    public function test_correccion_que_invalida_un_documento_retiene_su_saldo_sin_bloquear_otros_documentos_del_grupo(): void
    {
        $a = $this->documento(); $b = $this->documento(16, 'S', '1303');
        $entrada = $this->entrada($a, '060617629AZ01', 5); $salida = $this->salida($a);
        $this->trazabilidad->asociar($a, $entrada, $salida, 5, null, null);
        $this->entrada($b, '060617629AZ01', 7);
        DB::table('ot_logistica_remisiones')->where('id', $entrada)->update(['cantidad' => 4]);
        $datos = $this->service->consultar(); $r = $datos['resumen'];
        $this->assertSame(11, $r['central']); $this->assertSame(7, $r['disponible']);
        $this->assertSame(4, $r['por_revisar']); $this->assertSame(0, $r['remitido']);
        $this->assertNull($datos['lineas']->firstWhere('entrada_id', $entrada)->disponible);
        $this->assertSame((int) $this->trazabilidad->panorama()->sum('saldo_central'), $r['disponible']);
    }

    public function test_reimportaciones_y_anulaciones_actualizan_saldo_sin_guardar_nuevas_asociaciones(): void
    {
        $d = $this->documento(); $entrada = $this->entrada($d); $salida = $this->salida($d);
        $this->trazabilidad->asociar($d, $entrada, $salida, 6, null, null);
        $this->assertSame(4, $this->service->consultar()['resumen']['disponible']);
        DB::table('ot_logistica_remisiones')->where('id', $entrada)->update(['cantidad' => 12]);
        $this->assertSame(6, $this->service->consultar()['resumen']['disponible']);
        $this->trazabilidad->anular($d, (int) DB::table('invierno_asignaciones')->value('id'), null, 'Anulación de prueba');
        $this->assertSame(12, $this->service->consultar()['resumen']['disponible']);
        $this->assertDatabaseCount('invierno_asignaciones', 1);
        $this->assertDatabaseCount('ot_logistica_remisiones', 2);
    }

    public function test_grupo_plan_y_descripcion_son_respaldo_y_no_se_toma_grupo_de_otra_variante(): void
    {
        $d = $this->documento();
        $this->maestro('060617629AZ01', null, 'BUZOS PLAN');
        $this->maestro('060617629', 'GRUPO DE OTRO CÓDIGO');
        $this->entrada($d); $this->entrada($d, '060617629AZ02', 3, ['descripcion' => 'CAMPERAS NENA']);
        $this->entrada($d, '060617629AZ03', 2, ['descripcion' => null]);
        $datos = $this->service->consultar();
        $this->assertSame(['BUZOS PLAN', 'CAMPERAS NENA', 'SIN CLASIFICAR'], $datos['grupos']->pluck('grupo')->all());
        Schema::drop('maestro_codigos');
        $sinMaestro = $this->service->consultar();
        $this->assertSame(15, $sinMaestro['resumen']['disponible']);
        $this->assertTrue($sinMaestro['grupos']->contains('grupo', 'CAMPERA INVIERNO'));
    }

    public function test_vistas_filtros_y_detalle_por_codigo_corresponden_al_mismo_acumulado(): void
    {
        $a = $this->documento(); $b = $this->documento(16, 'S', '1303');
        $this->maestro('060617629AZ01', 'CAMPERAS'); $this->maestro('060617629AZ02', 'CAMPERAS');
        $this->entrada($a); $this->entrada($b, '060617629AZ02', 7);
        $this->get(route('invierno.disponibilidad'))->assertOk()->assertSee('Disponibilidad de invierno')
            ->assertViewHas('resumen', fn ($r) => $r['disponible'] === 17);
        $this->get(route('invierno.disponibilidad.detalle', ['grupo' => 'CAMPERAS', 'codigo' => '060617629AZ02']))
            ->assertOk()->assertSee('Ver remisión')->assertViewHas('resumen', fn ($r) => $r['disponible'] === 7)
            ->assertViewHas('paginados', fn ($p) => $p->total() === 1)
            ->assertViewHas('codigos', fn ($c) => $c->count() === 2);
        $this->get(route('invierno.disponibilidad', ['local' => 7]))->assertOk()
            ->assertViewHas('resumen', fn ($r) => $r['disponible'] === 10);
        $this->get(route('invierno.disponibilidad', ['buscar' => 'S 1303']))->assertOk()
            ->assertViewHas('resumen', fn ($r) => $r['disponible'] === 7);
        $this->getJson(route('invierno.disponibilidad', ['orden' => 'campo_invalido']))->assertUnprocessable();
        foreach (['disponibilidad', 'disponibilidad.detalle', 'disponibilidad.exportar'] as $ruta) {
            $middleware = app('router')->getRoutes()->getByName('invierno.'.$ruta)->gatherMiddleware();
            $this->assertContains('auth', $middleware); $this->assertContains('permission:ot index', $middleware);
        }
        $this->assertDatabaseCount('invierno_asignaciones', 0);
    }

    public function test_situacion_se_filtra_por_grupo_completo_y_totales_incluyen_todas_las_paginas(): void
    {
        $d = $this->documento();
        for ($i = 1; $i <= 26; $i++) {
            $codigo = '060617629AZ'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $this->entrada($d, $codigo, $i, ['descripcion' => 'GRUPO '.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
        }
        $this->get(route('invierno.disponibilidad', ['page' => 2]))->assertOk()
            ->assertViewHas('resumen', fn ($r) => $r['disponible'] === 351 && $r['grupos'] === 26)
            ->assertViewHas('resumenImagenes', [
                'prendas_con_imagen' => 0, 'prendas_sin_imagen' => 351,
                'codigos_con_imagen' => 0, 'codigos_sin_imagen' => 26,
            ])
            ->assertViewHas('paginados', fn ($p) => $p->count() === 1 && $p->total() === 26);
        $this->entrada($d, '060617629AZ99', 4, ['descripcion' => 'GRUPO 26', 'fecha_recepcion' => null, 'estado' => 'EN_TRANSITO']);
        $datos = $this->service->consultar(['situacion' => 'pendientes']);
        $this->assertSame(1, $datos['resumen']['grupos']);
        $this->assertSame(30, $datos['resumen']['prendas']);
        $this->assertSame(26, $datos['resumen']['disponible']);
        $this->assertSame(4, $datos['resumen']['pendiente']);
    }

    public function test_resume_unidades_y_codigos_distintos_con_y_sin_imagen_segun_filtros(): void
    {
        (require database_path('migrations/2026_11_10_160000_create_articulo_imagenes_table.php'))->up();
        $bonanza = $this->documento();
        $mariano = $this->documento(16, 'S', '1303');
        $this->maestro('060617629AZ01', 'CAMPERAS');
        $this->maestro('060617629AZ02', 'CAMPERAS');
        $this->maestro('060617629GR01', 'CAMPERAS');
        $this->maestro('050617500AZ01', 'BUZOS');
        $this->entrada($bonanza, '060617629AZ01', 10);
        $this->entrada($bonanza, '060617629AZ02', 6);
        $this->entrada($mariano, '060617629AZ01', 20);
        $this->entrada($mariano, '060617629GR01', 8, ['fecha_recepcion' => null, 'estado' => 'EN_TRANSITO']);
        $this->entrada($mariano, '050617500AZ01', 12);
        DB::table('articulo_imagenes')->insert([
            'codigo_imagen' => '060617629AZ',
            'imagen' => 'articulos-imagenes/060617629AZ_0000000000000000.png',
            'nombre_archivo' => '060617629AZ.png',
        ]);

        $this->get(route('invierno.disponibilidad'))->assertOk()
            ->assertSee('Prendas con imagen asociada')->assertSee('Prendas sin imagen asociada')
            ->assertViewHas('resumenImagenes', [
                'prendas_con_imagen' => 36, 'prendas_sin_imagen' => 20,
                'codigos_con_imagen' => 2, 'codigos_sin_imagen' => 2,
            ]);
        $this->get(route('invierno.disponibilidad', ['local' => 7]))->assertOk()
            ->assertViewHas('resumenImagenes', [
                'prendas_con_imagen' => 16, 'prendas_sin_imagen' => 0,
                'codigos_con_imagen' => 2, 'codigos_sin_imagen' => 0,
            ]);
        $this->get(route('invierno.disponibilidad', ['situacion' => 'pendientes']))->assertOk()
            ->assertViewHas('resumenImagenes', [
                'prendas_con_imagen' => 36, 'prendas_sin_imagen' => 8,
                'codigos_con_imagen' => 2, 'codigos_sin_imagen' => 1,
            ]);
    }

    public function test_excel_resumen_y_detalle_respetan_filtros_y_preservan_codigos_como_texto(): void
    {
        $d = $this->documento(); $this->maestro('060617629AZ01', '=1+1');
        $this->entrada($d); $this->entrada($d, 'OTRO01', 100, ['descripcion' => 'OTRO GRUPO']);
        $export = new InviernoDisponibilidadExport($this->service->consultar(['grupo' => '=1+1']));
        $ruta = tempnam(sys_get_temp_dir(), 'inv_grupos_');
        file_put_contents($ruta, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));
        try {
            // El lector no debe heredar el binder de escritura del exportador.
            \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new \PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder());
            $libro = IOFactory::load($ruta);
            $this->assertSame(2, $libro->getSheetCount());
            $this->assertSame(10, $libro->getSheet(0)->getCell('J2')->getValue());
            $this->assertSame(DataType::TYPE_NUMERIC, $libro->getSheet(0)->getCell('J2')->getDataType());
            $this->assertSame(0, $libro->getSheet(0)->getCell('F2')->getValue());
            $this->assertSame('=1+1', $libro->getSheet(0)->getCell('A2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $libro->getSheet(0)->getCell('A2')->getDataType());
            $this->assertSame('060617629AZ01', $libro->getSheet(1)->getCell('E2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $libro->getSheet(1)->getCell('E2')->getDataType());
            $this->assertSame(2, $libro->getSheet(1)->getHighestDataRow());
            $libro->disconnectWorksheets();
        } finally {
            @unlink($ruta);
        }
        $this->get(route('invierno.disponibilidad.exportar', ['grupo' => '=1+1']))->assertOk()->assertDownload();
    }

    public function test_exporta_bases_unicas_de_prendas_sin_imagen_con_los_filtros_del_panel(): void
    {
        (require database_path('migrations/2026_11_10_160000_create_articulo_imagenes_table.php'))->up();
        $bonanza = $this->documento();
        $mariano = $this->documento(16, 'S', '1303');
        foreach (['060617629AZ01', '060617629GR01', '060617629GR02', '050617500AZ01'] as $codigo) {
            $this->maestro($codigo, 'CAMPERAS');
            $this->entrada($bonanza, $codigo);
        }
        DB::table('maestro_codigos')->insert([
            ['cod_articulo' => 'CODIGO-FIJO', 'grupo' => 'CAMPERAS', 'cod_base' => '001234567', 'cod_imagen' => '001999999'],
            ['cod_articulo' => 'SOLO-COLOR', 'grupo' => 'CAMPERAS', 'cod_base' => null, 'cod_imagen' => '001987654AZ'],
        ]);
        $this->entrada($bonanza, 'CODIGO-FIJO');
        $this->entrada($bonanza, 'SOLO-COLOR');
        $this->entrada($bonanza, '030617651AZ01', 10, ['descripcion' => 'CAMPERAS']);
        $this->maestro('020617651AZ01', 'CAMPERAS');
        $this->entrada($mariano, '020617651AZ01');
        foreach (['060617629AZ', '050617500'] as $codigo) {
            DB::table('articulo_imagenes')->insert([
                'codigo_imagen' => $codigo,
                'imagen' => 'articulos-imagenes/'.$codigo.'_0000000000000000.png',
                'nombre_archivo' => $codigo.'.png',
            ]);
        }

        $filtros = ['local' => 7, 'grupo' => 'CAMPERAS'];
        $rutaExportar = 'invierno.disponibilidad.codigos-sin-imagen.exportar';
        $this->get(route('invierno.disponibilidad', $filtros + ['page' => 2]))->assertOk()
            ->assertSee(route($rutaExportar, $filtros));
        $respuesta = $this->get(route($rutaExportar, $filtros + ['page' => 2]));
        $respuesta->assertOk()->assertDownload('codigos_base_sin_imagen_20261110_120000.xlsx');
        $ruta = $respuesta->baseResponse->getFile()->getPathname();
        try {
            \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new \PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder());
            $libro = IOFactory::load($ruta);
            $this->assertSame(['Códigos base', 'Revisar maestro'], $libro->getSheetNames());
            $this->assertSame([
                ['CÓDIGO BASE PARA IMAGEN'], ['001234567'], ['001987654AZ'], ['060617629'],
            ], $libro->getSheet(0)->toArray());
            $this->assertSame(DataType::TYPE_STRING, $libro->getSheet(0)->getCell('A2')->getDataType());
            $this->assertSame([['CÓDIGO COMPLETO'], ['030617651AZ01']], $libro->getSheet(1)->toArray());
            $libro->disconnectWorksheets();
        } finally {
            @unlink($ruta);
        }

        $middleware = app('router')->getRoutes()->getByName($rutaExportar)->gatherMiddleware();
        $this->assertContains('auth', $middleware);
        $this->assertContains('permission:ot index', $middleware);
    }

    public function test_consultas_por_conjunto_y_no_por_cada_remision(): void
    {
        for ($i = 1; $i <= 40; $i++) $this->entrada($this->documento(7, 'F', (string) $i));
        $consultas = [];
        DB::listen(function (QueryExecuted $e) use (&$consultas) { $consultas[] = $e->sql; });
        $datos = $this->service->consultar();
        $this->assertSame(400, $datos['resumen']['disponible']);
        $this->assertLessThanOrEqual(8, count($consultas), 'La lectura no debe hacer consultas por documento.');
    }

    public function test_listado_reutiliza_el_maestro_y_lee_el_origen_registrado(): void
    {
        (require database_path('migrations/2026_11_10_160000_create_articulo_imagenes_table.php'))->up();
        (require database_path('migrations/2026_11_13_090000_index_invierno_disponibilidad.php'))->up();
        $documento = $this->documento();
        DB::table('sucursal')->where('cod_suc', 7)->delete();
        DB::table('maestro_codigos')->insert([
            'cod_articulo' => ' codigo-fijo ', 'grupo' => 'CAMPERAS', 'cod_imagen' => '001987654AZ',
        ]);
        $this->entrada($documento, 'CODIGO-FIJO', 10, ['serie' => ' f ', 'numero_remision' => '001188', 'sucursal_salida' => null]);
        $this->entrada($documento, 'CODIGO-FIJO', 100, ['cod_sucursal_destino' => 16]);

        $consultasMaestro = 0;
        DB::listen(function (QueryExecuted $consulta) use (&$consultasMaestro) {
            if (str_contains($consulta->sql, 'from "maestro_codigos"')) {
                $consultasMaestro++;
            }
        });
        $ruta = route('invierno.disponibilidad', ['local' => 7, 'grupo' => 'CAMPERAS']);
        $this->get($ruta)->assertOk()
            ->assertViewHas('locales', fn ($locales) => $locales->get(7) === 'BONANZA')
            ->assertViewHas('resumenImagenes', fn ($r) => $r['prendas_sin_imagen'] === 10);
        $this->assertSame(1, $consultasMaestro);

        DB::table('articulo_imagenes')->insert([
            'codigo_imagen' => '001987654AZ',
            'imagen' => 'articulos-imagenes/001987654AZ_0000000000000000.png',
            'nombre_archivo' => '001987654AZ.png',
        ]);
        $this->get($ruta)->assertOk()
            ->assertViewHas('resumenImagenes', fn ($r) => $r['prendas_con_imagen'] === 10 && $r['prendas_sin_imagen'] === 0);
        $this->assertSame(2, $consultasMaestro);
    }

    public function test_catalogo_con_espacios_y_minusculas_resuelve_solo_el_codigo_completo_correcto(): void
    {
        $d = $this->documento();
        $this->entrada($d);
        $this->maestro(' 060617629az01 ', '  Camperas   de invierno  ');
        $this->maestro('060617629AZ02', 'OTRO GRUPO');
        $datos = $this->service->consultar();
        $this->assertSame('CAMPERAS DE INVIERNO', $datos['grupos']->first()->grupo);
        $this->assertSame(10, $datos['resumen']['disponible']);
        $this->assertCount(1, $datos['grupos']);
    }

    public function test_articulos_generales_reunen_un_codigo_en_varias_remisiones_y_descripciones(): void
    {
        $a = $this->documento(); $b = $this->documento(16, 'S', '1303');
        $this->entrada($a);
        $this->entrada($b, '060617629AZ01', 7, ['descripcion' => 'CAMPERA OTRA DESCRIPCIÓN']);
        $datos = $this->service->consultar();
        $articulos = $this->service->articulos($datos['lineas']);
        $this->assertCount(1, $articulos);
        $this->assertSame(17, $articulos->first()->prendas);
        $this->assertSame(2, $articulos->first()->documentos);
        $this->get(route('invierno.articulos', ['codigo' => '060617629AZ01']))->assertOk()
            ->assertViewHas('resumen', fn ($r) => $r['prendas'] === 17)
            ->assertViewHas('paginados', fn ($p) => $p->total() === 1)
            ->assertViewHas('lineasPaginadas', fn ($p) => $p->total() === 2)
            ->assertSee('F 1188')->assertSee('S 1303')->assertSee('Ver remisiones');
    }

    private function leerExportArticulos(array $filtros = []): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $datos = $this->service->consultar($filtros);
        $export = new InviernoArticulosExport($datos, $this->service->articulos($datos['lineas']));
        $ruta = tempnam(sys_get_temp_dir(), 'inv_art_');
        file_put_contents($ruta, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));
        try {
            \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new \PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder());
            return IOFactory::load($ruta);
        } finally {
            @unlink($ruta);
        }
    }

    public function test_excel_completo_asocia_cada_prenda_a_su_local_serie_numero_e_incluye_pendientes_y_sin_detalle(): void
    {
        $a = $this->documento(); $b = $this->documento(16, 'S', '1303');
        $this->documento(7, 'J1', '465');
        $entrada = $this->entrada($a); $salida = $this->salida($a);
        $this->trazabilidad->asociar($a, $entrada, $salida, 3, null, null);
        $this->entrada($b, '060617629AZ01', 7);
        $this->entrada($b, 'OTRO01', 4, ['estado' => 'EN_TRANSITO', 'fecha_recepcion' => null]);
        $this->entrada($a, '060617629AZ01', 999, ['numero_remision' => '9999']);
        $libro = $this->leerExportArticulos();
        try {
            $this->assertSame(['Prendas por remisión', 'Artículos acumulados', 'Remisiones sin detalle'], $libro->getSheetNames());
            $detalle = $libro->getSheet(0);
            $this->assertSame(4, $detalle->getHighestDataRow());
            $cantidades = [];
            for ($fila = 2; $fila <= 4; $fila++) {
                $clave = $detalle->getCell('A'.$fila)->getValue().'|'.$detalle->getCell('C'.$fila)->getValue()
                    .'|'.$detalle->getCell('D'.$fila)->getValue().'|'.$detalle->getCell('H'.$fila)->getValue();
                $cantidades[$clave] = $detalle->getCell('N'.$fila)->getValue();
                $this->assertSame(DataType::TYPE_STRING, $detalle->getCell('D'.$fila)->getDataType());
                $this->assertSame(DataType::TYPE_STRING, $detalle->getCell('H'.$fila)->getDataType());
                $this->assertSame(DataType::TYPE_NUMERIC, $detalle->getCell('N'.$fila)->getDataType());
                $this->assertSame('CASA CENTRAL', $detalle->getCell('G'.$fila)->getValue());
            }
            $this->assertSame(['16|S|1303|060617629AZ01' => 7, '7|F|1188|060617629AZ01' => 10,
                '16|S|1303|OTRO01' => 4], $cantidades);
            $this->assertSame(2, $libro->getSheet(2)->getHighestDataRow());
            $this->assertSame('465', $libro->getSheet(2)->getCell('D2')->getValue());
            $this->assertSame(3, $libro->getSheet(1)->getHighestDataRow());
        } finally {
            $libro->disconnectWorksheets();
        }
        $this->get(route('invierno.index'))->assertOk()->assertSee('Exportar todas las prendas');
        $this->get(route('invierno.articulos.exportar'))->assertOk()->assertDownload();
        $this->assertDatabaseCount('invierno_asignaciones', 1);
    }

    public function test_exportar_articulos_no_se_limita_a_la_pagina_y_resumen_suma_todos_los_codigos(): void
    {
        $d = $this->documento();
        for ($i = 1; $i <= 51; $i++) $this->entrada($d, '060617629AZ'.str_pad((string) $i, 3, '0', STR_PAD_LEFT), $i);
        $this->get(route('invierno.articulos', ['page' => 2]))->assertOk()
            ->assertViewHas('paginados', fn ($p) => $p->total() === 51 && $p->count() === 1)
            ->assertViewHas('resumen', fn ($r) => $r['prendas'] === 1326);
        $libro = $this->leerExportArticulos(['page' => 2]);
        try {
            $this->assertSame(52, $libro->getSheet(0)->getHighestDataRow());
            $this->assertSame(52, $libro->getSheet(1)->getHighestDataRow());
        } finally {
            $libro->disconnectWorksheets();
        }
    }

    public function test_filtros_de_articulos_y_exportacion_conservan_el_mismo_alcance_y_permisos(): void
    {
        $a = $this->documento(); $b = $this->documento(16, 'S', '1303');
        $this->entrada($a); $this->entrada($b, 'OTRO01', 4, ['estado' => 'EN_TRANSITO', 'fecha_recepcion' => null]);
        $this->get(route('invierno.articulos', ['estado' => 'PENDIENTE_RECEPCION']))->assertOk()
            ->assertViewHas('resumen', fn ($r) => $r['prendas'] === 4 && $r['codigos'] === 1);
        $libro = $this->leerExportArticulos(['local' => 16]);
        try {
            $this->assertSame(2, $libro->getSheet(0)->getHighestDataRow());
            $this->assertSame('OTRO01', $libro->getSheet(0)->getCell('H2')->getValue());
            $this->assertSame(0, $libro->getSheet(0)->getCell('O2')->getValue());
        } finally {
            $libro->disconnectWorksheets();
        }
        $this->getJson(route('invierno.articulos', ['orden_articulos' => 'invalido']))->assertUnprocessable();
        foreach (['articulos', 'articulos.exportar'] as $nombre) {
            $middleware = app('router')->getRoutes()->getByName('invierno.'.$nombre)->gatherMiddleware();
            $this->assertContains('auth', $middleware); $this->assertContains('permission:ot index', $middleware);
        }
    }

    public function test_exportacion_de_prendas_con_diferencias_conserva_cantidad_y_deja_disponible_pendiente_de_revision(): void
    {
        $d = $this->documento(); $entrada = $this->entrada($d, '060617629AZ01', 5); $salida = $this->salida($d);
        $this->trazabilidad->asociar($d, $entrada, $salida, 5, null, null);
        DB::table('ot_logistica_remisiones')->where('id', $entrada)->update(['cantidad' => 4]);
        $libro = $this->leerExportArticulos();
        try {
            $this->assertSame(4, $libro->getSheet(0)->getCell('N2')->getValue());
            $this->assertNull($libro->getSheet(0)->getCell('T2')->getValue());
            $this->assertSame(4, $libro->getSheet(0)->getCell('U2')->getValue());
            $this->assertNotEmpty($libro->getSheet(0)->getCell('W2')->getValue());
            $this->assertSame(0, $libro->getSheet(1)->getCell('K2')->getValue());
        } finally {
            $libro->disconnectWorksheets();
        }
    }
}
