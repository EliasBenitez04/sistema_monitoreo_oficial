<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AccesoriosStockService;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
            '2026_11_18_110000_create_maria_pirelli_stock_tables.php',
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

        $ruta = route('maria-pirelli.index');
        foreach (['Cartera', 'Cartera negra'] as $descripcion) {
            $this->from($ruta)->post(route('maria-pirelli.importar'), [
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
        $this->get(route('maria-pirelli.index', $rango))
            ->assertOk()
            ->assertSee('Octubre 2026')
            ->assertSee('Noviembre 2026')
            ->assertSee('Cartera negra')
            ->assertSee('Último envío (remisión)')
            ->assertSee('04/11/2026')
            ->assertViewHas('porArticulo', fn ($movimientos) => reset($movimientos)['ultimo_envio'] === '2026-11-04')
            ->assertViewHas('meses', [
                '2026-11' => ['enviadas' => 7, 'vendidas' => 2, 'venta_neta' => 60000.0],
                '2026-10' => ['enviadas' => 15, 'vendidas' => 4, 'venta_neta' => 115000.0],
            ]);

        $this->ingresarComo('visualizador_accesorios');
        $this->get(route('maria-pirelli.index', $rango + ['sucursal' => 'PINEDO SHOPPING']))
            ->assertOk()
            ->assertDontSee('Importar catálogo')
            ->assertViewHas('totales', ['enviadas' => 18, 'vendidas' => 5, 'venta_neta' => 150000.0]);
        $this->get(route('maria-pirelli.index', [
            'desde' => '2026-10-01', 'hasta' => '2026-10-31', 'sucursal' => 'PINEDO SHOPPING',
        ]))
            ->assertOk()
            ->assertSee('07/10/2026')
            ->assertDontSee('08/10/2026')
            ->assertViewHas('totales', ['enviadas' => 11, 'vendidas' => 3, 'venta_neta' => 90000.0]);
        $this->post(route('maria-pirelli.importar'))->assertForbidden();
        $this->get(route('maria-pirelli.exportar', $rango))->assertForbidden();
    }

    public function test_muestra_remisiones_del_mes_y_sus_confirmaciones_por_articulo(): void
    {
        $this->ingresarComo('visualizador_accesorios');
        foreach (['00042', '00043'] as $codigo) {
            DB::table('maria_pirelli_articulos')->insert([
                'codigo' => $codigo, 'descripcion' => 'Cartera ' . $codigo, 'linea' => 'CARTERAS',
                'costo_unitario' => 10000, 'precio_venta' => 30000, 'fecha_envio' => '2024-11-01',
            ]);
        }
        $central = [1, 'CASA CENTRAL'];
        $pinedo = [14, 'PINEDO'];
        $parcial = $this->remitir('00120', '00042', '2026-10-31', $central, $pinedo, 5, fechaRecepcion: '2026-11-03');
        $this->remitir('00120', '00043', '2026-10-31', [0, 'CENTRAL'], [0, 'SHOPPING PINEDO'], 2);
        $bonanza = $this->remitir('00120', '00042', '2026-10-31', $central, [7, 'BONANZA'], 10);
        $this->remitir('00120', '00043', '2026-11-01', $central, $pinedo, 8);
        $this->remitir('00120', 'OTRO-CODIGO', '2026-10-31', $central, $pinedo, 99);
        $this->remitir('00121', '00042', '2026-10-30', $central, $pinedo, 3);
        $this->remitir('00122', '00042', '2026-10-29', $central, $pinedo, 4, fechaRecepcion: '2026-10-30');

        $filtros = [
            'desde' => '2026-10-01', 'hasta' => '2026-10-31',
            'sucursal' => 'PINEDO SHOPPING', 'linea' => 'CARTERAS', 'buscar' => 'Cartera',
        ];
        $panel = $this->get(route('maria-pirelli.index', $filtros));
        $panel->assertOk()->assertSee('Remisiones y confirmaciones')
            ->assertSee(route('maria-pirelli.index', $filtros) . '#remisiones')
            ->assertSee('MP-00120')->assertSee('03/11/2026')->assertSee('Confirmada')
            ->assertSee('Parcial')->assertSee('En tránsito')
            ->assertViewHas('confirmadas', 9)->assertViewHas('pendientes', 5);

        $remisiones = $panel->viewData('remisiones');
        $this->assertSame(3, $remisiones->total());
        $this->assertSame($parcial, $remisiones->first()->id);
        $this->assertSame(7, $remisiones->first()->enviadas);
        $this->assertSame(5, $remisiones->first()->confirmadas);

        $detalle = $this->get(route('maria-pirelli.index', $filtros + ['remision' => $parcial]));
        $detalle->assertOk()->assertSee('Remisión MP-00120')->assertSee('Volver a remisiones');
        $this->assertSame([
            ['00042', 5, '2026-11-03'],
            ['00043', 2, null],
        ], $detalle->viewData('lineasRemision')
            ->map(fn ($fila) => [$fila->codigo, (int) $fila->cantidad, $fila->fecha_recepcion])->all());

        $this->get(route('maria-pirelli.index', $filtros + ['remision' => $bonanza]))
            ->assertOk()->assertViewHas('remisionActiva', fn ($documento) => $documento === null)
            ->assertSee('La remisión seleccionada no está disponible con estos filtros.');
    }

    public function test_pagina_las_remisiones_independientemente_del_catalogo(): void
    {
        $this->ingresarComo('ADMIN');
        DB::table('maria_pirelli_articulos')->insert([
            'codigo' => '00042', 'descripcion' => 'Cartera', 'linea' => 'CARTERAS',
            'costo_unitario' => 10000, 'precio_venta' => 30000, 'fecha_envio' => '2024-11-01',
        ]);
        foreach (range(1, 16) as $numero) {
            $this->remitir((string) $numero, '00042', '2026-10-01', [1, 'CASA CENTRAL'], [14, 'PINEDO'], 1);
        }

        $respuesta = $this->get(route('maria-pirelli.index', [
            'desde' => '2026-10-01', 'hasta' => '2026-10-31', 'pagina_remisiones' => 2, 'page' => 53,
        ]));
        $respuesta->assertOk()->assertSee('MP-1')->assertSee('pagina_remisiones=1#remisiones');
        $this->assertSame(53, $respuesta->viewData('articulos')->currentPage());
        $this->assertSame(2, $respuesta->viewData('remisiones')->currentPage());
        $this->assertSame(16, $respuesta->viewData('remisiones')->total());
        $this->assertCount(1, $respuesta->viewData('remisiones'));
    }

    public function test_exporta_todos_los_codigos_con_movimiento_en_el_periodo_filtrado(): void
    {
        $this->ingresarComo('ADMIN');
        foreach (range(1, 30) as $i) {
            $codigo = sprintf('%05d', $i);
            DB::table('maria_pirelli_articulos')->insert([
                'codigo' => $codigo,
                'descripcion' => $i === 30 ? 'Bolso' : ($i === 1 ? '=CARTERA' : 'Cartera'),
                'linea' => $i === 29 ? 'BOLSOS' : 'CARTERAS',
                'costo_unitario' => 9881.50,
                'precio_venta' => 30000,
                'fecha_envio' => '2024-11-01',
            ]);
            if ($i !== 26) {
                $this->vender('FCR ' . $i, $codigo, $i === 28 ? 'BONANZA' : 'PINEDO',
                    $i === 27 ? '2026-11-01' : '2026-10-16', 3, 90000);
            }
        }
        $this->vender('NCR 31', '00001', 'SHOPPING PINEDO', '2026-10-22', -1, -30000);
        $this->remitir('101', '00001', '2026-10-08', [1, 'CENTRAL'], [14, 'PINEDO'], 5);
        $this->remitir('102', '00001', '2026-10-29', [1, 'CENTRAL'], [7, 'BONANZA'], 6);
        $this->remitir('103', '00026', '2026-10-10', [1, 'CENTRAL'], [14, 'PINEDO'], 4);

        $filtros = [
            'desde' => '2026-10-01', 'hasta' => '2026-10-31', 'sucursal' => 'PINEDO SHOPPING',
            'linea' => 'CARTERAS', 'buscar' => 'Cartera',
        ];
        $url = route('maria-pirelli.exportar', $filtros);
        $this->get(route('maria-pirelli.index', $filtros + ['page' => 2]))
            ->assertOk()->assertSee($url)->assertSee('Exportar Excel');

        $respuesta = $this->get($url . '&page=2');
        $respuesta->assertOk()->assertDownload('maria_pirelli_2026-10-01_2026-10-31.xlsx');
        $ruta = $respuesta->baseResponse->getFile()->getPathname();

        try {
            Cell::setValueBinder(new DefaultValueBinder());
            $hoja = IOFactory::load($ruta)->getActiveSheet();
            $this->assertSame(27, $hoja->getHighestDataRow());
            $this->assertSame('00001', $hoja->getCell('A2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $hoja->getCell('A2')->getDataType());
            $this->assertSame(DataType::TYPE_STRING, $hoja->getCell('B2')->getDataType());
            $this->assertSame('2026-10-08', Date::excelToDateTimeObject($hoja->getCell('D2')->getValue())->format('Y-m-d'));
            $this->assertSame(9881.50, $hoja->getCell('E2')->getValue());
            $this->assertSame([5, 2, 60000.0], $hoja->rangeToArray('G2:I2', null, false, false)[0]);
            $this->assertSame('00026', $hoja->getCell('A27')->getValue());
            $this->assertSame(0, $hoja->getCell('H27')->getValue());
            $this->assertNull($hoja->getCell('D3')->getValue());
        } finally {
            unlink($ruta);
        }
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
            $this->post(route('maria-pirelli.importar'), [
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
        $this->post(route('maria-pirelli.importar'), [
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
        $this->post(route('maria-pirelli.importar'), [
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

    public function test_importa_stock_del_excel_y_muestra_la_valorizacion_de_cada_sucursal(): void
    {
        $this->ingresarComo('ADMIN');
        $this->crearCatalogoStock();
        $this->subirStock('STOCK 16112026.xlsx', [
            ['PINEDO', '00042', 5, '=20000/2', 20000, 'STOCK 16/11'],
            ['PINEDO SHOPPING', '00043', -2, 'Gs 12.000', 25000, 'STOCK 16/11'],
            ['CASA CENTRAL', '00042', 4, 10000, 20000, 'STOCK 16/11'],
            ['OTRO LOCAL', '00042-XL', 100, 10000, 20000, 'STOCK 16/11'],
        ])->assertRedirect(route('maria-pirelli.stock'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Stock del 16/11/2026 importado con 3 filas. Se omitió una fila con un código fuera del catálogo de María Pirelli.');

        $this->assertDatabaseCount('maria_pirelli_stock', 3);
        $this->assertDatabaseHas('maria_pirelli_stock', [
            'sucursal_clave' => 'S:14', 'cantidad' => 5, 'costo_unitario' => 10000, 'precio_venta' => 20000,
        ]);

        $this->get(route('maria-pirelli.index', ['desde' => '2026-01-01', 'hasta' => '2026-01-31']))
            ->assertOk()->assertSee(route('maria-pirelli.stock'))->assertSee('7 unidades')
            ->assertSee('Gs 66.000')->assertSee('Gs 180.000')->assertSee('Neto Gs 130.000');
        $this->get(route('accesorios.maria-pirelli.index'))->assertOk()->assertSee(route('maria-pirelli.stock'));

        $this->ingresarComo('visualizador_accesorios');
        $this->get(route('maria-pirelli.stock', ['sucursal' => 'S:14', 'buscar' => '00043']))
            ->assertOk()->assertDontSee('Importar stock')
            ->assertViewHas('sucursal', fn ($local) => $local->sucursal === 'PINEDO SHOPPING'
                && (int) $local->unidades === 3 && (int) $local->costo === 26000
                && (int) $local->venta_potencial === 100000)
            ->assertViewHas('articulos', fn ($articulos) => $articulos->total() === 1
                && $articulos->first()->codigo === '00043' && (int) $articulos->first()->valor_neto === -50000);
        $this->post(route('maria-pirelli.stock.importar'))->assertForbidden();
    }

    public function test_reemplaza_el_corte_de_stock_sin_duplicar_y_publica_el_mas_reciente(): void
    {
        $this->ingresarComo('ADMIN');
        $this->crearCatalogoStock();

        $this->subirStock('STOCK.xlsx', [['MALL', '00042', 3, 10000, 20000, '16/11/2026']])
            ->assertSessionHasNoErrors();
        $this->subirStock('STOCK.xlsx', [['LUQUE', '00042', 2, 10000, 20000, '15/11']], '2026-11-15')
            ->assertSessionHasNoErrors();
        $this->subirStock('STOCK 16112026.xlsx', [['CASA CENTRAL', '00042', 11, 10000, 20000, null]])
            ->assertSessionHasNoErrors();
        $this->subirStock('STOCK 16112026.xlsx', [['MALL', '00042', 1.5, 10000, 20000, null]])
            ->assertSessionHasErrors(['archivo' => 'Fila 2. STOCK ACTUAL debe ser una cantidad entera.']);

        $this->assertDatabaseCount('maria_pirelli_stock_cortes', 2);
        $this->assertDatabaseCount('maria_pirelli_stock', 2);
        $this->get(route('maria-pirelli.stock'))->assertOk()->assertViewHas('stockActual', fn ($stock) =>
            $stock['corte']->fecha_corte === '2026-11-16'
            && $stock['sucursales']->keys()->all() === ['S:1']
            && (int) $stock['totales']['unidades'] === 11
        );
    }

    public function test_cruza_ventas_del_mes_con_el_ultimo_stock_disponible_por_sucursal(): void
    {
        $this->ingresarComo('visualizador_accesorios');
        $this->crearCatalogoStock();
        $this->guardarCorte('2026-10-31', [['PINEDO', '00042', 99, 1000, 2000]]);
        $this->guardarCorte('2026-11-16', [
            ['PINEDO', '00042', 5, 10000, 20000],
            ['PINEDO SHOPPING', '00043', -2, 12000, 25000],
            ['BONANZA', '00042', 0, 10000, 20000],
            ['MALL', '00042', 4, 20000, 40000],
        ]);
        $this->guardarCorte('2026-11-20', [['PINEDO', '00042', 500, 99000, 150000]]);
        $this->vender('FCR 100', '00042', 'PINEDO', '2026-11-03', 4, 120000);
        $this->vender('NCR 101', '00042', 'SHOPPING PINEDO', '2026-11-04', -1, -30000);
        $this->vender('FCR 102', '00042', 'BONANZA', '2026-11-05', 1, 25000);
        $this->vender('FCR 103', '00042', 'LUQUE', '2026-11-06', 2, 50000);
        $this->vender('FCR 104', '00042-XL', 'PINEDO', '2026-11-07', 100, 3000000);
        $this->vender('FCR 105', '00042', 'PINEDO', '2026-10-31', 10, 300000);
        $this->vender('FCR 106', '00042', 'PINEDO', '2026-11-20', 10, 300000);
        $this->remitir('301', '00042', '2026-11-05', [1, 'CASA CENTRAL'], [14, 'PINEDO'], 99);

        $rango = ['desde' => '2026-11-01', 'hasta' => '2026-11-19'];
        $respuesta = $this->get(route('maria-pirelli.index', $rango));
        $respuesta->assertOk()->assertSee('Ventas y stock por sucursal')
            ->assertSee('Stock del 16/11/2026')->assertSee('Sin dato')
            ->assertViewHas('mesSeleccionado', 'Noviembre 2026')
            ->assertViewHas('sucursalesSinStock', 1)
            ->assertViewHas('stockMes', fn ($stock) => $stock['corte']->fecha_corte === '2026-11-16');
        $locales = $respuesta->viewData('sucursalesDelMes')->keyBy('sucursal');
        $this->assertCount(4, $locales);
        $pinedo = $locales->get('PINEDO SHOPPING');
        $this->assertSame(99, (int) $pinedo['enviadas']);
        $this->assertSame(3, (int) $pinedo['vendidas']);
        $this->assertSame(90000, (int) $pinedo['venta_neta']);
        $this->assertSame(3, (int) $pinedo['unidades']);
        $this->assertSame(26000, (int) $pinedo['costo']);
        $this->assertSame(100000, (int) $pinedo['venta_potencial']);
        $this->assertSame(2, (int) $pinedo['negativas']);
        $this->assertSame(0, (int) $locales->get('BONANZA')['unidades']);
        $this->assertNull($locales->get('LUQUE')['unidades']);
        $this->assertSame(0, (int) $locales->get('MALL')['vendidas']);

        $totales = $respuesta->viewData('totalesMes');
        $this->assertSame(6, (int) $totales['vendidas']);
        $this->assertSame(165000, (int) $totales['venta_neta']);
        $this->assertSame(7, (int) $totales['unidades']);
        $this->assertSame(106000, (int) $totales['costo']);
        $this->assertSame(260000, (int) $totales['venta_potencial']);

        $filtrado = $this->get(route('maria-pirelli.index', $rango + ['sucursal' => 'PINEDO']));
        $filtrado->assertOk()->assertViewHas('sucursalesSinStock', 0);
        $this->assertCount(1, $filtrado->viewData('sucursalesDelMes'));
        $this->assertSame(26000, (int) $filtrado->viewData('totalesMes')['costo']);

        $anterior = $this->get(route('maria-pirelli.index', [
            'desde' => '2026-11-01', 'hasta' => '2026-11-10', 'sucursal' => 'PINEDO',
        ]));
        $anterior->assertOk()->assertViewHas('stockMes', fn ($stock) => $stock['corte']->fecha_corte === '2026-10-31');
        $this->assertSame(99, (int) $anterior->viewData('totalesMes')['unidades']);
    }

    public function test_distingue_el_stock_cero_del_stock_ausente_y_no_usa_inventarios_futuros(): void
    {
        $this->ingresarComo('ADMIN');
        $this->crearCatalogoStock();
        $this->guardarCorte('2026-11-16', [['PINEDO', '00042', 0, 10000, 20000]]);
        $this->vender('FCR 201', '00042', 'PINEDO', '2026-11-02', 1, 30000);

        $sinStock = $this->get(route('maria-pirelli.index', [
            'desde' => '2026-11-01', 'hasta' => '2026-11-10',
        ]));
        $sinStock->assertOk()->assertSee('No hay inventario importado hasta el 10/11/2026.')
            ->assertViewHas('stockMes', fn ($stock) => $stock['corte'] === null)
            ->assertViewHas('sucursalesSinStock', 1);
        $this->assertNull($sinStock->viewData('totalesMes')['unidades']);
        $this->assertNull($sinStock->viewData('sucursalesDelMes')->first()['costo']);

        $conCero = $this->get(route('maria-pirelli.index', [
            'desde' => '2026-11-01', 'hasta' => '2026-11-16',
        ]));
        $conCero->assertOk()->assertViewHas('sucursalesSinStock', 0);
        $this->assertSame(0, (int) $conCero->viewData('totalesMes')['unidades']);
        $this->assertSame(0, (int) $conCero->viewData('totalesMes')['costo']);
    }

    public function test_permite_abrir_meses_sin_ventas_y_respeta_linea_codigo_y_sucursal_en_el_stock(): void
    {
        $this->ingresarComo('visualizador_accesorios');
        $this->crearCatalogoStock();
        DB::table('maria_pirelli_articulos')->where('codigo', '00042')->update(['linea' => 'CARTERAS']);
        DB::table('maria_pirelli_articulos')->where('codigo', '00043')->update(['linea' => 'BOLSOS']);
        $this->guardarCorte('2026-11-16', [
            ['PINEDO', '00042', 5, 10000, 20000],
            ['PINEDO', '00043', 8, 12000, 25000],
            ['MALL', '00043', 4, 12000, 25000],
        ]);
        $this->guardarCorte('2026-12-01', [['MALL', '00043', 2, 12000, 25000]]);

        $this->get(route('maria-pirelli.index', ['desde' => '2026-11-01', 'hasta' => '2026-12-19']))
            ->assertOk()->assertSee('Noviembre 2026')->assertSee('Diciembre 2026')
            ->assertViewHas('meses', [
                '2026-12' => ['enviadas' => 0, 'vendidas' => 0, 'venta_neta' => 0.0],
                '2026-11' => ['enviadas' => 0, 'vendidas' => 0, 'venta_neta' => 0.0],
            ])->assertViewHas('mesSeleccionado', fn ($mes) => $mes === null);

        $filtros = [
            'desde' => '2026-11-01', 'hasta' => '2026-11-19',
            'sucursal' => 'PINEDO', 'linea' => 'CARTERAS', 'buscar' => '00042',
        ];
        $respuesta = $this->get(route('maria-pirelli.index', $filtros));
        $respuesta->assertOk()->assertSee('#sucursales-mes')
            ->assertViewHas('sucursal', 'PINEDO SHOPPING');
        $this->assertCount(1, $respuesta->viewData('sucursalesDelMes'));
        $this->assertSame(0, (int) $respuesta->viewData('totalesMes')['vendidas']);
        $this->assertSame(5, (int) $respuesta->viewData('totalesMes')['unidades']);
        $this->assertSame(50000, (int) $respuesta->viewData('totalesMes')['costo']);
        $this->assertSame(100000, (int) $respuesta->viewData('totalesMes')['venta_potencial']);
    }

    private function guardarCorte(string $fecha, array $filas): void
    {
        $corte = DB::table('maria_pirelli_stock_cortes')->insertGetId(['fecha_corte' => $fecha]);
        $sucursales = new AccesoriosStockService();
        foreach ($filas as [$nombre, $codigo, $cantidad, $costo, $precio]) {
            $clave = $sucursales->identificar(null, $nombre);
            DB::table('maria_pirelli_stock')->insert([
                'corte_id' => $corte,
                'articulo_id' => DB::table('maria_pirelli_articulos')->where('codigo', $codigo)->value('id'),
                'sucursal_clave' => $clave,
                'sucursal' => $sucursales->nombre($clave, $nombre),
                'cantidad' => $cantidad, 'costo_unitario' => $costo, 'precio_venta' => $precio,
            ]);
        }
    }

    private function crearCatalogoStock(): void
    {
        foreach (['00042', '00043'] as $codigo) {
            DB::table('maria_pirelli_articulos')->insert([
                'codigo' => $codigo, 'descripcion' => 'Cartera ' . $codigo,
                'costo_unitario' => 50000, 'precio_venta' => 90000, 'fecha_envio' => '2026-11-01',
            ]);
        }
    }

    private function subirStock(string $nombre, array $filas, ?string $fecha = null)
    {
        $excel = new Spreadsheet();
        $hoja = $excel->getActiveSheet();
        $hoja->fromArray([['SUCURSAL', 'CÓDIGO', 'STOCK ACTUAL', 'COSTO UNITARIO', 'PVTA UNI', 'FECHA STOCK'], ...$filas]);
        $temporal = tempnam(sys_get_temp_dir(), 'maria-pirelli-stock-');
        try {
            (new Xlsx($excel))->save($temporal);
            return $this->from(route('maria-pirelli.stock'))->post(route('maria-pirelli.stock.importar'), [
                'archivo' => new UploadedFile($temporal, $nombre, null, null, true),
                'fecha_corte' => $fecha,
            ]);
        } finally {
            unlink($temporal);
            $excel->disconnectWorksheets();
        }
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
        ?string $fechaCreacion = null, ?string $fechaRecepcion = null
    ): int {
        return DB::table('ot_logistica_remisiones')->insertGetId([
            'serie' => 'MP',
            'numero_remision' => $numero,
            'codigo' => $codigo,
            'fecha_remision' => $fecha,
            'fecha_creacion' => $fechaCreacion ?? $fecha,
            'fecha_recepcion' => $fechaRecepcion,
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
