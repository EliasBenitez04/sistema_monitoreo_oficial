<?php

namespace Tests\Feature;

use App\Models\User;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MariaPirelliTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        foreach ([
            '2023_12_10_202658_create_permission_tables.php',
            '2026_09_18_141900_create_ot_logistica_remisiones_table.php',
            '2026_10_08_140000_create_ventas_tables.php',
            '2026_11_18_090000_create_maria_pirelli_articulos_table.php',
        ] as $migracion) {
            (require database_path('migrations/' . $migracion))->up();
        }
    }

    public function test_vincula_historial_por_codigo_completo_y_resume_los_movimientos_por_mes(): void
    {
        $this->ingresarComo('ADMIN');
        $this->remitir('A', '00042', '2026-10-06', [1, 'CASA CENTRAL'], [14, 'PINEDO'], 5);
        $this->remitir('B', '00042', '2026-10-07', [0, 'CENTRAL'], [14, 'PINEDO'], 6);
        $this->remitir('C', '00042', '2026-10-08', [1, 'CASA CENTRAL'], [7, 'BONANZA'], 4);
        $this->remitir('D', '00042', '2026-10-01', [99, 'DEPÓSITO'], [1, 'CASA CENTRAL'], 20);
        $this->remitir('E', '00042', '2026-10-10', [14, 'PINEDO'], [7, 'BONANZA'], 2);
        $this->remitir('F', '00042-XL', '2026-10-11', [1, 'CASA CENTRAL'], [14, 'PINEDO'], 100);
        $this->remitir('G', ' 00042 ', '2026-11-04', [1, 'CASA CENTRAL'], [14, 'PINEDO'], 7, '2026-10-31');
        $this->remitir('H', '00042', null, [1, 'CASA CENTRAL'], [14, 'PINEDO'], 9, '2026-11-05');

        $this->vender('FCR 1', '00042', 'PINEDO', '2026-10-15', 4, 120000);
        $this->vender('NCR 2', '00042', 'PINEDO SHOPPING', '2026-10-17', -1, -30000);
        $this->vender('FCR 3', '00042', 'BONANZA', '2026-10-18', 1, 25000);
        $this->vender('FCR 4', '00042-XL', 'PINEDO', '2026-10-20', 10, 300000);
        $this->vender('FCR 5', '00042', 'SHOPPING PINEDO', '2026-11-07', 2, 60000);

        $ruta = route('accesorios.maria-pirelli.index');
        foreach (['Cartera', 'Cartera negra'] as $descripcion) {
            $this->from($ruta)->post(route('accesorios.maria-pirelli.importar'), [
                'archivo' => UploadedFile::fake()->createWithContent(
                    'catalogo.csv',
                    "CODIGO;DESCRIPCION;COSTO;PREC. VENT.;FECHA ENVIO;LINEA\n00042;{$descripcion};0;30.000;01/10/2026;CARTERAS\n"
                ),
            ])->assertSessionHasNoErrors()->assertSessionHas('success')->assertRedirect($ruta);
        }
        $this->assertDatabaseCount('maria_pirelli_articulos', 1);
        $this->assertDatabaseHas('maria_pirelli_articulos', [
            'codigo' => '00042',
            'descripcion' => 'Cartera negra',
            'costo_unitario' => 0,
        ]);

        $rango = ['desde' => '2026-10-01', 'hasta' => '2026-11-30'];
        $this->get(route('accesorios.maria-pirelli.index', $rango))
            ->assertOk()
            ->assertSee('Octubre 2026')
            ->assertSee('Noviembre 2026')
            ->assertSee('Cartera negra')
            ->assertSee('Último envío (remisión)')
            ->assertSee('04/11/2026')
            ->assertDontSee('01/10/2026')
            ->assertViewHas('meses', [
                '2026-11' => ['enviadas' => 7, 'vendidas' => 2, 'venta_neta' => 60000.0],
                '2026-10' => ['enviadas' => 15, 'vendidas' => 4, 'venta_neta' => 115000.0],
            ]);

        $this->ingresarComo('visualizador_accesorios');
        $this->get(route('accesorios.maria-pirelli.index', $rango + ['sucursal' => 'PINEDO SHOPPING']))
            ->assertOk()
            ->assertDontSee('Importar catálogo')
            ->assertViewHas('totales', ['enviadas' => 18, 'vendidas' => 5, 'venta_neta' => 150000.0]);
        $this->get(route('accesorios.maria-pirelli.index', [
            'desde' => '2026-10-01', 'hasta' => '2026-10-31', 'sucursal' => 'PINEDO SHOPPING',
        ]))
            ->assertOk()
            ->assertSee('07/10/2026')
            ->assertDontSee('08/10/2026')
            ->assertViewHas('totales', ['enviadas' => 11, 'vendidas' => 3, 'venta_neta' => 90000.0]);
        $this->post(route('accesorios.maria-pirelli.importar'))->assertForbidden();
    }

    public function test_importa_excel_con_ceros_en_el_codigo_y_fecha_numerica(): void
    {
        $this->ingresarComo('ADMIN');
        $excel = new Spreadsheet();
        $hoja = $excel->getActiveSheet();
        $hoja->fromArray([
            ['CÓDIGO', 'DESCRIPCIÓN', 'COSTO', 'PREC. VENT.', 'FECHA ENVÍO', 'LÍNEA'],
            [null, 'Cartera', '9.881,50', 30000, Date::PHPToExcel(new DateTimeImmutable('2026-11-01')), 'CARTERAS'],
        ]);
        $hoja->setCellValueExplicit('A2', '00042', DataType::TYPE_STRING);
        $temporal = tempnam(sys_get_temp_dir(), 'maria-pirelli-');
        try {
            (new Xlsx($excel))->save($temporal);
            $this->post(route('accesorios.maria-pirelli.importar'), [
                'archivo' => new UploadedFile(
                    $temporal, 'catalogo.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true
                ),
            ])->assertSessionHasNoErrors()->assertSessionHas('success');
        } finally {
            unlink($temporal);
        }

        $this->assertDatabaseHas('maria_pirelli_articulos', [
            'codigo' => '00042',
            'fecha_envio' => '2026-11-01',
            'costo_unitario' => 9881.50,
            'precio_venta' => 30000,
        ]);
    }

    public function test_identifica_un_encabezado_faltante(): void
    {
        $this->ingresarComo('ADMIN');
        $this->post(route('accesorios.maria-pirelli.importar'), [
            'archivo' => UploadedFile::fake()->createWithContent('catalogo.csv',
                "CODIGO;DESCRIPCION;COSTO;PREC. VENT.;LINEA\n00042;Cartera;10000;30000;CARTERAS\n"
            ),
        ])->assertSessionHasErrors([
            'archivo' => 'Falta el encabezado FECHA ENVIO en la primera fila de la primera hoja.',
        ]);
        $this->assertDatabaseCount('maria_pirelli_articulos', 0);
    }

    public function test_identifica_la_columna_invalida_y_rechaza_toda_la_carga(): void
    {
        $this->ingresarComo('ADMIN');
        $this->post(route('accesorios.maria-pirelli.importar'), [
            'archivo' => UploadedFile::fake()->createWithContent('catalogo.csv',
                "CODIGO;DESCRIPCION;COSTO;PRECIO VENTA;FECHA ENVIO\n"
                . "00042;Cartera;10000;30000;18/11/2026\n"
                . ";;;;\n"
                . "00043;Bolso;15000;45000;31/02/2026\n"
            ),
        ])->assertSessionHasErrors([
            'archivo' => 'Fila 4. FECHA ENVIO debe ser una fecha válida, por ejemplo 18/11/2026.',
        ]);
        $this->assertDatabaseCount('maria_pirelli_articulos', 0);
    }

    private function ingresarComo(string $rol): void
    {
        $usuario = new User(['name' => 'Prueba', 'email' => 'prueba@example.test']);
        $usuario->id = 1;
        $usuario->setRelation('roles', collect([Role::create(['name' => $rol, 'guard_name' => 'web'])]));
        $this->actingAs($usuario);
    }

    private function remitir(
        string $numero, string $codigo, ?string $fecha, array $origen, array $destino, int $cantidad,
        ?string $fechaCreacion = null
    ): void {
        DB::table('ot_logistica_remisiones')->insert([
            'serie' => 'MP',
            'numero_remision' => $numero,
            'codigo' => $codigo,
            'fecha_remision' => $fecha,
            'fecha_creacion' => $fechaCreacion ?? $fecha,
            'cod_sucursal_salida' => $origen[0],
            'sucursal_salida' => $origen[1],
            'cod_sucursal_destino' => $destino[0],
            'sucursal_destino' => $destino[1],
            'cantidad' => $cantidad,
        ]);
    }

    private function vender(
        string $documento, string $codigo, string $local, string $fecha, int $cantidad, int $importe
    ): void {
        DB::table('ventas')->insert([
            'comprobante' => $documento,
            'codigo' => $codigo,
            'local' => $local,
            'fecha' => $fecha,
            'cantidad' => $cantidad,
            'p_venta' => $importe,
            'hash_linea' => sha1($documento),
        ]);
    }
}
