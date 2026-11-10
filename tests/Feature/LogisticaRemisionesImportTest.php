<?php

namespace Tests\Feature;

use App\Imports\ControlTerminacionRemisionImport;
use App\Models\User;
use App\Services\InviernoTrazabilidadService;
use Carbon\Carbon;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class LogisticaRemisionesImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        Carbon::setTestNow('2026-11-10 09:00:00');
        Schema::create('sucursal', function (Blueprint $t) {
            $t->integer('cod_suc');
            $t->string('suc_descri');
        });
        DB::table('sucursal')->insert([
            ['cod_suc' => 1, 'suc_descri' => 'CASA CENTRAL'],
            ['cod_suc' => 7, 'suc_descri' => 'BONANZA'],
            ['cod_suc' => 16, 'suc_descri' => 'SHOPPING MARIANO'],
        ]);
        Schema::create('ot', function (Blueprint $t) {
            $t->bigIncrements('id_ot');
            $t->integer('nro_ot');
            $t->string('codigo');
            $t->string('descripcion')->nullable();
        });
        Schema::create('ot_trazabilidad', function (Blueprint $t) {
            $t->bigIncrements('id_trazabilidad');
            $t->bigInteger('id_ot');
            $t->string('proceso');
            $t->date('fecha_proceso');
            $t->integer('resultado')->default(10);
        });
        (require database_path('migrations/2026_07_31_161758_create_ot_logistica_detalle_table.php'))->up();
        (require database_path('migrations/2026_09_18_141900_create_ot_logistica_remisiones_table.php'))->up();
        DB::table('ot')->insert(['id_ot' => 1, 'nro_ot' => 30000, 'codigo' => '050617629']);
        DB::table('ot_trazabilidad')->insert(['id_ot' => 1,
            'proceso' => 'LOGISTICA - LOGISTICA Y DISTRIBUCION', 'fecha_proceso' => '2026-10-01']);
        DB::table('ot_logistica_detalle')->insert(['id_ot' => 1, 'id_trazabilidad' => 1,
            'sucursal' => 'BONANZA', 'cantidad' => 10]);
        $this->withoutMiddleware();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function fila(array $cambios = []): array
    {
        return array_merge([
            'cod_articulo' => '050617629AZ01', 'serie' => '1', 'numero_remision' => '1188',
            'cod_sucursal_salida' => 1, 'sucursal_salida' => 'CASA CENTRAL',
            'cod_sucursal' => 7, 'sucursal' => 'BONANZA', 'cantidad' => 5,
            'fecha_remision' => '01/10/2026', 'fecha_creacion' => '01/10/2026',
            'fecha_recepcion' => null, 'artiuclo' => 'CAMPERA',
            'precioventa' => 39000, 'costounitario' => 18000,
        ], $cambios);
    }

    private function importar(array $filas): ControlTerminacionRemisionImport
    {
        $import = new ControlTerminacionRemisionImport();
        $import->collection(collect($filas));
        return $import;
    }

    private function archivo(array $filas, string $tipo = 'Csv'): UploadedFile
    {
        $libro = new Spreadsheet();
        $libro->getActiveSheet()->fromArray(array_merge([array_keys($filas[0])], array_map('array_values', $filas)), null, 'A1', true);
        $ruta = tempnam(sys_get_temp_dir(), 'logistica_test_');
        IOFactory::createWriter($libro, $tipo)->save($ruta);
        $libro->disconnectWorksheets();
        return new UploadedFile($ruta, 'envios.' . strtolower($tipo), null, null, true);
    }

    public function test_corrige_cantidad_de_cinco_a_cuatro_sin_duplicar_ni_perder_vinculos(): void
    {
        $this->importar([$this->fila()]);
        $antes = DB::table('ot_logistica_remisiones')->first();
        $this->assertSame(1, (int) $antes->id_logistica_detalle);
        Carbon::setTestNow('2026-11-10 10:00:00');
        $import = $this->importar([$this->fila(['cantidad' => 4, 'fecha_recepcion' => '02/10/2026'])]);
        $despues = DB::table('ot_logistica_remisiones')->first();
        $this->assertDatabaseCount('ot_logistica_remisiones', 1);
        $this->assertSame($antes->id, $despues->id);
        $this->assertSame(4, (int) $despues->cantidad);
        $this->assertSame($antes->id_ot, $despues->id_ot);
        $this->assertSame($antes->id_trazabilidad, $despues->id_trazabilidad);
        $this->assertSame($antes->id_logistica_detalle, $despues->id_logistica_detalle);
        $this->assertSame($antes->created_at, $despues->created_at);
        $this->assertSame('RECIBIDO', $despues->estado);
        $this->assertSame(1, $import->getActualizadas());
        $this->assertSame(1, $import->getCantidadesCorregidas());
        $this->assertSame(0, $import->getInsertadas());
    }

    public function test_reimportar_sin_cambios_no_escribe_ni_vuelve_a_leer_historial_de_ot(): void
    {
        $this->importar([$this->fila(['fecha_recepcion' => '02/10/2026'])]);
        $fecha = DB::table('ot_logistica_remisiones')->value('updated_at');
        Carbon::setTestNow('2026-11-10 10:00:00');
        $consultas = [];
        DB::listen(function (QueryExecuted $evento) use (&$consultas) { $consultas[] = $evento->sql; });
        $import = $this->importar([$this->fila(['fecha_recepcion' => '02/10/2026'])]);
        $this->assertSame(1, $import->getSinCambios());
        $this->assertSame(0, $import->getActualizadas());
        $this->assertSame($fecha, DB::table('ot_logistica_remisiones')->value('updated_at'));
        $this->assertEmpty(array_filter($consultas, fn ($sql) => preg_match('/^(insert|update|delete)\b/i', $sql)));
        $this->assertEmpty(array_filter($consultas, fn ($sql) => str_contains($sql, '"ot" as "o"')));
    }

    public function test_repara_vinculos_pendientes_aunque_los_datos_no_cambien(): void
    {
        $this->importar([$this->fila()]);
        DB::table('ot_logistica_remisiones')->update(['id_ot' => null,
            'id_trazabilidad' => null, 'id_logistica_detalle' => null]);
        $import = $this->importar([$this->fila()]);
        $this->assertSame(1, $import->getActualizadas());
        $this->assertSame(0, $import->getCantidadesCorregidas());
        $this->assertDatabaseHas('ot_logistica_remisiones', ['id_logistica_detalle' => 1, 'id_ot' => 1]);
    }

    public function test_no_borra_recepcion_si_el_archivo_viene_sin_fecha_y_cuenta_el_estado_persistido(): void
    {
        $this->importar([$this->fila(['fecha_recepcion' => '02/10/2026'])]);
        $import = $this->importar([$this->fila(['cantidad' => 4, 'fecha_creacion' => null, 'fecha_remision' => null])]);
        $this->assertSame(1, $import->getDocumentosRecibidos());
        $this->assertSame(0, $import->getDocumentosEnTransito());
        $this->assertDatabaseHas('ot_logistica_remisiones', ['cantidad' => 4, 'estado' => 'RECIBIDO',
            'fecha_remision' => '2026-10-01', 'fecha_creacion' => '2026-10-01', 'fecha_recepcion' => '2026-10-02']);
    }

    public function test_acepta_cero_explicito_para_corregir_pero_omite_cantidad_vacia_o_invalida(): void
    {
        $this->importar([$this->fila()]);
        $import = $this->importar([$this->fila(['cantidad' => 0]),
            $this->fila(['numero_remision' => 'nueva', 'cantidad' => 0])]);
        $this->assertSame(1, $import->getCantidadesCorregidas());
        $this->assertSame(1, $import->getOmitidas());
        $this->assertDatabaseCount('ot_logistica_remisiones', 1);
        $this->assertDatabaseHas('ot_logistica_remisiones', ['cantidad' => 0]);
        foreach ([null, '', 'sin dato', -1] as $cantidad) {
            $invalido = $this->importar([$this->fila(['cantidad' => $cantidad])]);
            $this->assertSame(1, $invalido->getOmitidas());
            $this->assertSame(0, $invalido->getActualizadas());
        }
    }

    public function test_ultima_fila_repetida_prevalece_en_el_bloque_y_entre_bloques(): void
    {
        $import = $this->importar([$this->fila(), $this->fila(['cantidad' => 4])]);
        $this->assertSame(1, $import->resumen()['duplicadas']);
        $this->assertSame(2, $import->resumen()['leidas']);
        $this->assertDatabaseHas('ot_logistica_remisiones', ['cantidad' => 4]);
        $import->collection(collect([$this->fila(['cantidad' => 3])]));
        $this->assertDatabaseCount('ot_logistica_remisiones', 1);
        $this->assertDatabaseHas('ot_logistica_remisiones', ['cantidad' => 3]);
    }

    public function test_la_clave_incluye_codigo_completo_origen_y_destino_y_no_borra_filas_ausentes(): void
    {
        $this->importar([$this->fila(),
            $this->fila(['cod_articulo' => '050617629AZ02']),
            $this->fila(['cod_sucursal_salida' => 16, 'sucursal_salida' => 'MARIANO']),
            $this->fila(['cod_sucursal' => 16, 'sucursal' => 'MARIANO']),
        ]);
        $this->importar([$this->fila(['cantidad' => 4])]);
        $this->assertDatabaseCount('ot_logistica_remisiones', 4);
        $this->assertSame(19, (int) DB::table('ot_logistica_remisiones')->sum('cantidad'));
    }

    public function test_las_correcciones_liberan_saldo_antes_de_asociar_sin_depender_del_orden(): void
    {
        DB::table('ot_logistica_detalle')->where('id', 1)->update(['cantidad' => 5]);
        DB::table('ot_trazabilidad')->insert(['id_ot' => 1,
            'proceso' => 'LOGISTICA - LOGISTICA Y DISTRIBUCION', 'fecha_proceso' => '2026-10-02']);
        DB::table('ot_logistica_detalle')->insert(['id_ot' => 1, 'id_trazabilidad' => 2,
            'sucursal' => 'BONANZA', 'cantidad' => 1]);
        $this->importar([$this->fila()]);
        $import = $this->importar([
            $this->fila(['numero_remision' => 'nueva', 'cantidad' => 1]),
            $this->fila(['cantidad' => 4]),
        ]);
        $this->assertSame(1, $import->getCantidadesCorregidas());
        $this->assertDatabaseHas('ot_logistica_remisiones', ['numero_remision' => 'nueva', 'id_logistica_detalle' => 1]);
    }

    public function test_excel_streaming_y_csv_actualizan_y_publican_progreso_real(): void
    {
        foreach (['Xlsx', 'Csv', 'Xls'] as $tipo) {
            $archivo = $this->archivo([$this->fila(['cantidad' => $tipo === 'Xlsx' ? 5 : 4])], $tipo);
            $eventos = [];
            $import = new ControlTerminacionRemisionImport(function ($datos) use (&$eventos) { $eventos[] = $datos; });
            try {
                if ($tipo === 'Xlsx') $import->importarXlsxStreaming($archivo->getRealPath());
                elseif ($tipo === 'Csv') $import->importarCsvStreaming($archivo->getRealPath());
                else Excel::import($import, $archivo);
                $resumen = $import->finalizar();
                $this->assertSame('PREPARANDO', $eventos[0]['estado']);
                $this->assertSame('COMPLETADO', end($eventos)['estado']);
                $this->assertSame(100, end($eventos)['porcentaje']);
                $this->assertSame(1, $resumen['leidas']);
                $this->assertSame(1, $resumen['total']);
            } finally {
                @unlink($archivo->getRealPath());
            }
        }
        $this->assertDatabaseCount('ot_logistica_remisiones', 1);
        $this->assertDatabaseHas('ot_logistica_remisiones', ['cantidad' => 4]);
    }

    public function test_ajax_devuelve_resumen_y_progreso_por_usuario_y_conserva_post_tradicional(): void
    {
        $usuario = new User();
        $usuario->id = 100;
        $this->actingAs($usuario);
        $archivo = $this->archivo([$this->fila()]);
        try {
            $this->post(route('control.terminacion.importar-remisiones'), [
                'archivo_envios' => $archivo, 'import_token' => 'prueba_100', 'origen' => 'dashboard-logistica',
                'fecha_desde' => '2026-10-01', 'fecha_hasta' => '2026-10-31',
            ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('success', true)
                ->assertJsonPath('resumen.insertadas', 1)->assertJsonPath('resumen.actualizadas', 0)
                ->assertJsonPath('redirect', route('dashboard.ot-logistica', ['fecha_desde' => '2026-10-01', 'fecha_hasta' => '2026-10-31']));
            $this->getJson(route('control.terminacion.remisiones.progreso', 'prueba_100'))
                ->assertOk()->assertJsonPath('estado', 'COMPLETADO')->assertJsonPath('porcentaje', 100);
            $otro = new User();
            $otro->id = 101;
            $this->actingAs($otro)->getJson(route('control.terminacion.remisiones.progreso', 'prueba_100'))
                ->assertOk()->assertJsonPath('estado', 'ESPERANDO');
            $this->post(route('control.terminacion.importar-remisiones'), ['archivo_envios' => $archivo])
                ->assertRedirect(route('control.terminacion'))->assertSessionHas('success');
        } finally {
            @unlink($archivo->getRealPath());
        }
    }

    public function test_admite_csv_detectado_como_texto_plano_y_rechaza_extensiones_no_permitidas(): void
    {
        $contenido = "COD_ARTICULO;SERIE;NUMERO_REMISION;COD_SUCURSAL_SALIDA;COD_SUCURSAL;CANTIDAD\n050617629AZ01;1;1188;1;7;4\n";
        $csv = UploadedFile::fake()->createWithContent('envios.csv', $contenido)->mimeType('text/plain');
        $this->post(route('control.terminacion.importar-remisiones'), ['archivo_envios' => $csv],
            ['Accept' => 'application/json'])->assertOk()->assertJsonPath('resumen.insertadas', 1);
        $this->assertDatabaseHas('ot_logistica_remisiones', ['cantidad' => 4]);
        foreach (['txt', 'php'] as $extension) {
            $archivo = UploadedFile::fake()->createWithContent('envios.' . $extension, $contenido)->mimeType('text/plain');
            $this->post(route('control.terminacion.importar-remisiones'), ['archivo_envios' => $archivo],
                ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('archivo_envios');
        }
        $this->assertDatabaseCount('ot_logistica_remisiones', 1);
    }

    public function test_xlsx_grande_conserva_contadores_y_corrige_una_linea_repetida_entre_bloques(): void
    {
        $filas = [];
        for ($n = 1; $n <= 2001; $n++) $filas[] = $this->fila(['numero_remision' => (string) $n]);
        $filas[] = $this->fila(['numero_remision' => '1', 'cantidad' => 4]);
        $archivo = $this->archivo($filas, 'Xlsx');
        $eventos = [];
        $import = new ControlTerminacionRemisionImport(function ($datos) use (&$eventos) {
            if ($datos['estado'] === 'PROCESANDO' && $datos['leidas'] > 0) {
                $eventos[] = $datos;
                $this->assertSame($datos['insertadas'], DB::table('ot_logistica_remisiones')->count());
            }
        });
        try {
            $import->importarXlsxStreaming($archivo->getRealPath());
            $resumen = $import->finalizar();
            $this->assertSame(2001, $resumen['insertadas']);
            $this->assertSame(1, $resumen['actualizadas']);
            $this->assertSame(1, $resumen['cantidades_corregidas']);
            $this->assertSame(2002, $resumen['leidas']);
            $this->assertCount(2, $eventos);
            $this->assertSame(2000, $eventos[0]['leidas']);
            $this->assertLessThan(100, end($eventos)['porcentaje']);
            $this->assertDatabaseHas('ot_logistica_remisiones', ['numero_remision' => '1', 'cantidad' => 4]);
        } finally {
            @unlink($archivo->getRealPath());
        }
    }

    public function test_csv_streaming_admite_bom_punto_y_coma_comillas_y_texto_de_excel_windows(): void
    {
        foreach ([false, true] as $windows) {
            $contenido = 'COD_ARTICULO;SERIE;NUMERO_REMISION;COD_SUCURSAL_SALIDA;COD_SUCURSAL;CANTIDAD;ARTIUCLO'
                . "\r\n050617629AZ01;1;1188;1;7;4;\"CAMPERA NIÑA; INVIERNO\nDOS LÍNEAS\"\r\n";
            $contenido = $windows ? mb_convert_encoding($contenido, 'Windows-1252', 'UTF-8') : "\xEF\xBB\xBF" . $contenido;
            $archivo = UploadedFile::fake()->createWithContent('envios.csv', $contenido);
            $import = new ControlTerminacionRemisionImport();
            $import->importarCsvStreaming($archivo->getRealPath());
            $this->assertSame(1, $import->getProcesadas());
            $this->assertDatabaseHas('ot_logistica_remisiones', ['cantidad' => 4,
                'descripcion' => "CAMPERA NIÑA; INVIERNO\nDOS LÍNEAS"]);
        }
        $this->assertDatabaseCount('ot_logistica_remisiones', 1);
    }

    public function test_error_de_encabezado_y_error_de_escritura_no_dejan_el_bloque_a_medias(): void
    {
        $this->postJson(route('control.terminacion.importar-remisiones'), [])
            ->assertUnprocessable()->assertJsonValidationErrors('archivo_envios');
        $archivo = UploadedFile::fake()->createWithContent('incorrecto.csv', "LOCAL,SERIE,NRO\nBONANZA,F,1188\n");
        $this->post(route('control.terminacion.importar-remisiones'), ['archivo_envios' => $archivo, 'import_token' => 'error_test'],
            ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonPath('success', false);
        $this->assertSame('ERROR', Cache::get('logistica_import_invitado_error_test')['estado']);
        $import = new ControlTerminacionRemisionImport();
        $filas = [];
        for ($n = 1; $n <= 201; $n++) $filas[] = $this->fila(['numero_remision' => (string) $n]);
        DB::statement("CREATE TRIGGER fallar_ultimo BEFORE INSERT ON ot_logistica_remisiones WHEN NEW.numero_remision = '201' BEGIN SELECT RAISE(ABORT, 'fallo simulado'); END");
        try {
            $import->collection(collect($filas));
            $this->fail('Debía fallar el segundo lote de escritura.');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertDatabaseCount('ot_logistica_remisiones', 0);
            $this->assertSame(0, $import->getInsertadas());
            $this->assertSame(0, $import->getProcesadas());
        }
    }

    public function test_correccion_en_remision_de_invierno_actualiza_saldo_y_detecta_vinculos_que_superan_cantidad(): void
    {
        (require database_path('migrations/2026_11_10_090000_create_invierno_trazabilidad_tables.php'))->up();
        $service = app(InviernoTrazabilidadService::class);
        $service->registrar(['local' => 'BONANZA', 'serie' => 'F', 'nro' => '1188'], null);
        $fila = $this->fila(['serie' => 'F', 'cod_sucursal_salida' => 7, 'sucursal_salida' => 'BONANZA',
            'cod_sucursal' => 1, 'sucursal' => 'CASA CENTRAL', 'fecha_recepcion' => '02/10/2026']);
        $this->importar([$fila]);
        $idEntrada = DB::table('ot_logistica_remisiones')->value('id');
        $this->importar([$this->fila(['numero_remision' => '900', 'fecha_remision' => '03/10/2026'])]);
        $idSalida = DB::table('ot_logistica_remisiones')->where('numero_remision', '900')->value('id');
        $service->asociar(1, $idEntrada, $idSalida, 5, null, null);
        $this->importar([array_merge($fila, ['cantidad' => 4])]);
        $this->assertDatabaseHas('ot_logistica_remisiones', ['id' => $idEntrada, 'cantidad' => 4]);
        $this->assertDatabaseCount('invierno_asignaciones', 1);
        $documento = $service->panorama()->first();
        $this->assertSame('REVISAR', $documento->estado);
        $this->assertNull($documento->saldo_central);
        $this->assertSame(5, $documento->por_revisar);
    }
}
