<?php

namespace Tests\Feature;

use App\Exports\InviernoTrazabilidadExport;
use App\Imports\InviernoRemisionesImport;
use App\Models\User;
use App\Services\InviernoTrazabilidadService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class InviernoTrazabilidadTest extends TestCase
{
    private InviernoTrazabilidadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('app.timezone', 'America/Asuncion');
        Carbon::setTestNow('2026-11-10 12:00:00');
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        Schema::create('sucursal', function (Blueprint $t) {
            $t->integer('cod_suc');
            $t->string('suc_descri');
        });
        DB::table('sucursal')->insert([
            ['cod_suc' => 1, 'suc_descri' => 'CASA CENTRAL'],
            ['cod_suc' => 2, 'suc_descri' => 'SAN LORENZO'],
            ['cod_suc' => 3, 'suc_descri' => 'LUQUE'],
            ['cod_suc' => 4, 'suc_descri' => 'MALL'],
            ['cod_suc' => 5, 'suc_descri' => 'L06 SAN LO2'],
            ['cod_suc' => 6, 'suc_descri' => 'LA RURAL'],
            ['cod_suc' => 7, 'suc_descri' => 'BONANZA'],
            ['cod_suc' => 8, 'suc_descri' => 'SHOP SAN LO3'],
            ['cod_suc' => 9, 'suc_descri' => 'MULTIPLAZA'],
            ['cod_suc' => 14, 'suc_descri' => 'PINEDO SHOPPING'],
            ['cod_suc' => 15, 'suc_descri' => 'ÑEMBY'],
            ['cod_suc' => 16, 'suc_descri' => 'SHOPPING MARIANO'],
            ['cod_suc' => 22, 'suc_descri' => 'JARDINES LUQUE'],
        ]);
        (require database_path('migrations/2026_09_18_141900_create_ot_logistica_remisiones_table.php'))->up();
        (require database_path('migrations/2026_11_10_090000_create_invierno_trazabilidad_tables.php'))->up();
        $this->service = app(InviernoTrazabilidadService::class);
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        $this->withoutMiddleware();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function documento(string $local = 'BONANZA', string $serie = 'F', string $nro = '1188'): int
    {
        $this->service->registrar(['local' => $local, 'serie' => $serie, 'nro' => $nro], null);
        return (int) DB::table('invierno_remisiones')->orderByDesc('id')->value('id');
    }

    private function movimiento(array $datos = []): int
    {
        return (int) DB::table('ot_logistica_remisiones')->insertGetId(array_merge([
            'cod_sucursal_salida' => 7, 'sucursal_salida' => 'BONANZA',
            'cod_sucursal_destino' => 1, 'sucursal_destino' => 'CASA CENTRAL',
            'serie' => 'F', 'numero_remision' => '1188',
            'codigo' => '060617629AZ01', 'descripcion' => 'CAMPERA INVIERNO', 'cantidad' => 10,
            'fecha_remision' => '2026-10-01', 'fecha_creacion' => '2026-10-01',
            'fecha_recepcion' => '2026-10-02', 'estado' => 'RECIBIDO',
            'created_at' => now(), 'updated_at' => now(),
        ], $datos));
    }

    private function salida(array $datos = []): int
    {
        return $this->movimiento(array_merge([
            'cod_sucursal_salida' => 1, 'sucursal_salida' => 'CASA CENTRAL',
            'cod_sucursal_destino' => 16, 'sucursal_destino' => 'SHOPPING MARIANO',
            'serie' => 'S1', 'numero_remision' => '900', 'cantidad' => 6,
            'fecha_remision' => '2026-10-03', 'fecha_creacion' => '2026-10-03',
            'fecha_recepcion' => null, 'estado' => 'EN_TRANSITO',
        ], $datos));
    }

    public function test_importa_local_serie_numero_sin_duplicar_y_explica_filas_invalidas(): void
    {
        $import = new InviernoRemisionesImport($this->service);
        $import->collection(collect([
            collect(['local' => 'BONANZA', 'serie' => 'f', 'nro' => '001188']),
            collect(['local' => 'JARDINES', 'serie' => 'J1', 'nro' => 465]),
            collect(['local' => ' bonanza ', 'serie' => ' F ', 'nro' => '1188']),
            collect(['local' => 'LOCAL DESCONOCIDO', 'serie' => 'F', 'nro' => 1]),
            collect(['local' => 'BONANZA', 'serie' => 'F', 'nro' => '1.5']),
        ]));
        $this->assertSame(2, $import->nuevas);
        $this->assertSame(1, $import->existentes);
        $this->assertSame(2, $import->rechazadas);
        $this->assertStringContainsString('Fila 5', $import->errores[0]);
        $this->assertDatabaseHas('invierno_remisiones', ['cod_sucursal_origen' => 22, 'numero_remision' => '465']);
    }

    public function test_importacion_http_excel_y_csv_con_punto_y_coma(): void
    {
        $libro = new Spreadsheet();
        $libro->getActiveSheet()->fromArray([['LOCAL', 'SERIE', 'NRO'], ['BONANZA', 'F', '1188']]);
        $ruta = tempnam(sys_get_temp_dir(), 'invierno_');
        IOFactory::createWriter($libro, 'Xlsx')->save($ruta);
        try {
            $archivo = new UploadedFile($ruta, 'invierno.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
            $this->post(route('invierno.importar'), ['archivo' => $archivo])->assertSessionHasNoErrors()->assertRedirect(route('invierno.index'));
        } finally {
            @unlink($ruta);
            $libro->disconnectWorksheets();
        }
        $csv = UploadedFile::fake()->createWithContent('invierno.csv', "\xEF\xBB\xBFLOCAL;SERIE;NRO\nJARDINES;J1;465\n");
        $this->post(route('invierno.importar'), ['archivo' => $csv])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('invierno_remisiones', 2);
    }

    public function test_importa_los_alias_de_invierno_y_espacios_del_excel_sin_duplicar(): void
    {
        $casos = [
            ['BONANZA', 'F', 7], ['JARDINES', 'J1', 22], ['LUQUE', 'B', 3],
            ['MARIANO', 'S', 16], ['MULTIPLAZA', 'H', 9], ['ÑEMBY', 'Ñ', 15],
            ['PINEDO', 'J', 14], ['RURAL', 'E', 6], ['SAN LORENZO', 'A', 2],
            ['SHOPP', 'G', 8], ['L06', 'D', 5], ['SLG', 'A', 2],
            ['MALL', 'C', 4], ['MMALL', 'C', 4], ['MALL', 'V', 4],
            ["MARIANO\u{00A0}", 'S', 16], ["PINEDO\u{202F}", 'J', 14],
        ];
        $csv = "LOCAL,SERIE,NRO\n";
        foreach ($casos as $indice => [$local, $serie]) {
            $csv .= $local.','.$serie.','.(10000 + $indice)."\n";
        }
        $archivo = UploadedFile::fake()->createWithContent('alias_invierno.csv', $csv);
        $import = new InviernoRemisionesImport($this->service);
        Excel::import($import, $archivo);
        $this->assertSame([], $import->errores);
        $this->assertSame(0, $import->rechazadas);
        $this->assertSame(0, $import->existentes);
        $this->assertSame(count($casos), $import->nuevas);
        foreach ($casos as $indice => [$local, $serie, $codigo]) {
            $this->assertDatabaseHas('invierno_remisiones', ['cod_sucursal_origen' => $codigo, 'serie' => $serie, 'numero_remision' => (string) (10000 + $indice)]);
        }
        $reimport = new InviernoRemisionesImport($this->service);
        Excel::import($reimport, $archivo);
        $this->assertSame(0, $reimport->nuevas);
        $this->assertSame($import->nuevas, $reimport->existentes);
        $this->assertSame(0, $reimport->rechazadas);
    }

    public function test_etiqueta_erronea_en_remision_no_vuelve_ambiguo_el_nombre_del_catalogo(): void
    {
        $this->movimiento(['cod_sucursal_salida' => 9, 'sucursal_salida' => 'MARIANO']);
        $this->movimiento(['cod_sucursal_salida' => 3, 'sucursal_salida' => 'JARDINES']);
        $this->documento('MARIANO');
        $this->documento('JARDINES', 'J1', '465');
        $this->assertDatabaseHas('invierno_remisiones', ['cod_sucursal_origen' => 16, 'local' => 'SHOPPING MARIANO']);
        $this->assertDatabaseHas('invierno_remisiones', ['cod_sucursal_origen' => 22, 'local' => 'JARDINES LUQUE']);
    }

    public function test_ambiguedad_real_indica_nombre_y_codigos_y_admite_codigo_explicito(): void
    {
        DB::table('sucursal')->insert(['cod_suc' => 99, 'suc_descri' => 'SHOP MARIANO']);
        try {
            $this->documento('MARIANO');
            $this->fail('Dos sucursales del catálogo con el mismo alias deben requerir un código.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('MARIANO', $e->getMessage());
            $this->assertStringContainsString('16 · SHOPPING MARIANO', $e->getMessage());
            $this->assertStringContainsString('99 · SHOP MARIANO', $e->getMessage());
        }
        $this->documento('016');
        $this->assertDatabaseHas('invierno_remisiones', ['cod_sucursal_origen' => 16]);
    }

    public function test_error_de_local_desconocido_muestra_el_valor_importado(): void
    {
        $import = new InviernoRemisionesImport($this->service);
        $import->collection(collect([collect(['local' => 'LOCAL SIN IDENTIFICAR', 'serie' => 'F', 'nro' => '1'])]));
        $this->assertSame(1, $import->rechazadas);
        $this->assertStringContainsString('Fila 2', $import->errores[0]);
        $this->assertStringContainsString('LOCAL SIN IDENTIFICAR', $import->errores[0]);
    }

    public function test_rechaza_encabezados_incorrectos_sin_crear_documentos(): void
    {
        $this->expectException(ValidationException::class);
        (new InviernoRemisionesImport($this->service))->collection(collect([collect(['local' => 'BONANZA', 'numero' => 1188])]));
    }

    public function test_no_confunde_origen_destino_ni_series_y_se_actualiza_al_importar_remision_despues(): void
    {
        $id = $this->documento();
        $this->assertSame('SIN_REMISION', $this->service->panorama()->first()->estado);
        $this->movimiento(['cod_sucursal_salida' => 22]);
        $this->movimiento(['cod_sucursal_destino' => 25]);
        $this->movimiento(['serie' => 'F2']);
        $this->movimiento(['serie' => ' f ', 'numero_remision' => '001188', 'fecha_recepcion' => null, 'estado' => 'EN_TRANSITO']);
        $d = $this->service->panorama()->firstWhere('id', $id);
        $this->assertSame(10, $d->total);
        $this->assertSame(0, $d->confirmado_central);
        $this->assertSame('PENDIENTE_RECEPCION', $d->estado);
    }

    public function test_recepcion_y_envio_parciales_sin_inferir_salidas_del_mismo_codigo(): void
    {
        $id = $this->documento();
        $entrada = $this->movimiento();
        $this->movimiento(['codigo' => '060617629AZ02', 'cantidad' => 4, 'fecha_recepcion' => null, 'estado' => 'EN_TRANSITO']);
        $salida = $this->salida();
        $d = $this->service->panorama()->first();
        $this->assertSame(0, $d->remitido); // Un candidato no es una asociación.
        $this->assertSame('RECEPCION_PARCIAL', $d->estado);
        $this->service->asociar($id, $entrada, $salida, 6, 99, 'Bulto identificado');
        $d = $this->service->panorama()->first();
        $this->assertSame(14, $d->total);
        $this->assertSame(10, $d->confirmado_central);
        $this->assertSame(4, $d->pendiente_central);
        $this->assertSame(6, $d->remitido);
        $this->assertSame(0, $d->recibido_local);
        $this->assertSame(4, $d->saldo_central);
    }

    public function test_confirmacion_local_y_cierre_solo_cuando_todo_esta_recibido(): void
    {
        $id = $this->documento();
        $entrada = $this->movimiento();
        $salida = $this->salida(['cantidad' => 10]);
        $this->service->asociar($id, $entrada, $salida, 10, null, null);
        $this->assertSame('EN_TRANSITO_LOCALES', $this->service->panorama()->first()->estado);
        DB::table('ot_logistica_remisiones')->where('id', $salida)->update(['estado' => 'RECIBIDO', 'fecha_recepcion' => '2026-10-05']);
        $d = $this->service->panorama()->first();
        $this->assertSame('COMPLETO', $d->estado);
        $this->assertSame(10, $d->recibido_local);
        $this->assertSame(0, $d->saldo_central);
    }

    /** @dataProvider vinculosInvalidos */
    public function test_rechaza_vinculos_que_no_acreditan_esta_devolucion(array $entradaDatos, array $salidaDatos, int $cantidad): void
    {
        $id = $this->documento();
        $entrada = $this->movimiento($entradaDatos);
        $salida = $this->salida($salidaDatos);
        $this->expectException(ValidationException::class);
        $this->service->asociar($id, $entrada, $salida, $cantidad, null, null);
    }

    public static function vinculosInvalidos(): array
    {
        return [
            'sin confirmar central' => [['fecha_recepcion' => null, 'estado' => 'EN_TRANSITO'], [], 1],
            'recibido sin fecha' => [['fecha_recepcion' => null], [], 1],
            'recepcion futura' => [['fecha_recepcion' => '2026-11-11'], [], 1],
            'local distinto' => [['cod_sucursal_salida' => 22], [], 1],
            'salida no central' => [[], ['cod_sucursal_salida' => 7], 1],
            'destino central' => [[], ['cod_sucursal_destino' => 1], 1],
            'talle distinto' => [[], ['codigo' => '060617629AZ02'], 1],
            'antes de recepcion' => [[], ['fecha_remision' => '2026-10-01'], 1],
            'salida futura' => [[], ['fecha_remision' => '2026-11-11'], 1],
            'estado anulado' => [[], ['estado' => 'ANULADO'], 1],
            'sin saldo' => [[], [], 7],
            'cantidad cero' => [[], [], 0],
        ];
    }

    public function test_saldo_de_una_salida_compartido_entre_devoluciones_y_anulacion_conserva_historial(): void
    {
        $docA = $this->documento();
        $docB = $this->documento('JARDINES', 'J1', '465');
        $entradaA = $this->movimiento();
        $entradaB = $this->movimiento(['cod_sucursal_salida' => 22, 'sucursal_salida' => 'LOS JARDINES', 'serie' => 'J1', 'numero_remision' => '465']);
        $salida = $this->salida();
        $this->service->asociar($docA, $entradaA, $salida, 4, null, null);
        try {
            $this->service->asociar($docB, $entradaB, $salida, 3, null, null);
            $this->fail('No debe reutilizar las cantidades de otra devolución.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('máximo 2', $e->getMessage());
        }
        $this->service->asociar($docB, $entradaB, $salida, 2, null, null);
        $vinculo = (int) DB::table('invierno_asignaciones')->where('invierno_remision_id', $docA)->value('id');
        $this->service->anular($docA, $vinculo, 99, 'Asignación corregida');
        $this->assertDatabaseHas('invierno_asignaciones', ['id' => $vinculo, 'anulada_por' => 99, 'motivo_anulacion' => 'Asignación corregida']);
        $this->service->asociar($docA, $entradaA, $salida, 4, null, null);
        $this->assertSame(3, DB::table('invierno_asignaciones')->count());
    }

    public function test_reimportacion_que_reduce_cantidad_observa_todos_los_vinculos_afectados(): void
    {
        $id = $this->documento();
        $entrada = $this->movimiento();
        $salida = $this->salida();
        $this->service->asociar($id, $entrada, $salida, 6, null, null);
        DB::table('ot_logistica_remisiones')->where('id', $salida)->update(['cantidad' => 4]);
        $d = $this->service->panorama()->first();
        $this->assertSame('REVISAR', $d->estado);
        $this->assertNull($d->saldo_central);
        $this->assertSame(0, $d->remitido);
        $this->assertSame(6, $d->por_revisar);
    }

    public function test_impide_duplicar_un_vinculo_y_sobregirar_una_entrada_en_salidas_distintas(): void
    {
        $id = $this->documento();
        $entrada = $this->movimiento();
        $salida = $this->salida();
        $otraSalida = $this->salida(['numero_remision' => '902', 'cantidad' => 10]);
        $this->service->asociar($id, $entrada, $salida, 6, null, null);
        foreach ([[$salida, 1], [$otraSalida, 5]] as [$salidaId, $cantidad]) {
            try {
                $this->service->asociar($id, $entrada, $salidaId, $cantidad, null, null);
                $this->fail('No debe duplicar el vínculo ni sobregirar el saldo de entrada.');
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }
        $this->assertDatabaseCount('invierno_asignaciones', 1);
    }

    public function test_quitar_confirmacion_central_observa_el_vinculo_sin_borrarlo(): void
    {
        $id = $this->documento();
        $entrada = $this->movimiento();
        $salida = $this->salida();
        $this->service->asociar($id, $entrada, $salida, 6, null, null);
        DB::table('ot_logistica_remisiones')->where('id', $entrada)->update(['fecha_recepcion' => null, 'estado' => 'EN_TRANSITO']);
        $d = $this->service->panorama()->first();
        $this->assertSame('REVISAR', $d->estado);
        $this->assertSame(0, $d->confirmado_central);
        $this->assertSame(0, $d->remitido);
        $this->assertDatabaseCount('invierno_asignaciones', 1);
    }

    public function test_cambio_simultaneo_de_codigo_no_reescribe_la_identidad_del_vinculo(): void
    {
        $id = $this->documento();
        $entrada = $this->movimiento();
        $salida = $this->salida();
        $this->service->asociar($id, $entrada, $salida, 6, null, null);
        DB::table('ot_logistica_remisiones')->whereIn('id', [$entrada, $salida])->update(['codigo' => 'OTRO_CODIGO']);
        $this->assertSame('REVISAR', $this->service->panorama()->first()->estado);
        $this->assertDatabaseHas('invierno_asignaciones', ['codigo' => '060617629AZ01']);
    }

    public function test_vistas_filtros_candidatos_y_descarga_excel(): void
    {
        $usuario = \Mockery::mock(User::class)->makePartial();
        $usuario->shouldReceive('hasRole')->andReturn(false);
        $usuario->shouldReceive('checkPermissionTo')->andReturn(true);
        $usuario->id = 99;
        $usuario->name = 'Operador de prueba';
        $this->actingAs($usuario);
        \Illuminate\Support\Facades\Gate::before(fn () => true);
        $id = $this->documento();
        $entrada = $this->movimiento();
        $salida = $this->salida();
        $this->salida(['numero_remision' => '901', 'fecha_remision' => '2026-09-01']);
        $this->documento('JARDINES', 'J1', '465');
        $this->get(route('invierno.index', ['local' => 7]))->assertOk()->assertSee('Prendas de invierno')
            ->assertViewHas('resumen', fn ($r) => $r['documentos'] === 1 && $r['central'] === 10);
        $this->get(route('invierno.show', ['id' => $id, 'entrada' => $entrada]))->assertOk()
            ->assertViewHas('salidas', fn ($p) => $p->total() === 1 && (int) $p->first()->id === $salida);
        $export = new InviernoTrazabilidadExport($this->service->panorama());
        $raw = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        $ruta = tempnam(sys_get_temp_dir(), 'inv_exp_');
        file_put_contents($ruta, $raw);
        try {
            $sheet = IOFactory::load($ruta)->getActiveSheet();
            $this->assertSame('LOCAL', $sheet->getCell('A1')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('C2')->getDataType());
        } finally {
            @unlink($ruta);
        }
        $this->get(route('invierno.exportar'))->assertOk()->assertDownload();
    }

    public function test_rutas_protegidas_y_usuario_sin_permiso_no_puede_asociar(): void
    {
        foreach (['index', 'show', 'exportar', 'plantilla', 'importar', 'registrar', 'asociar', 'anular', 'retirar'] as $nombre) {
            $ruta = app('router')->getRoutes()->getByName('invierno.'.$nombre);
            $this->assertContains('auth', $ruta->gatherMiddleware());
            $this->assertContains('permission:ot index', $ruta->gatherMiddleware());
        }
        $this->withMiddleware();
        $this->get(route('invierno.index'))->assertRedirect(route('login'));
        $usuario = \Mockery::mock(User::class)->makePartial();
        $usuario->shouldReceive('canAny')->andReturnUsing(fn ($permisos) => $permisos === ['ot index']);
        $usuario->shouldReceive('hasRole')->andReturn(false);
        $usuario->id = 99;
        $this->actingAs($usuario)->postJson(route('invierno.asociar', 1), [
            'entrada_id' => 1, 'salida_id' => 2, 'cantidad' => 100,
        ])->assertForbidden();
        $this->assertDatabaseCount('invierno_asignaciones', 0);
    }
}
