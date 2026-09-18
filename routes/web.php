<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;

use App\Http\Controllers\auditoriaController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\PedidoComprasController;
use App\Http\Controllers\stock_ventas_sucursales_Controller;
use App\Http\Controllers\StockController;
use App\Http\Controllers\OtController;
use App\Http\Controllers\ProcesadorImagenController;
use App\Http\Controllers\RedistribucionSugeridaController;


/*
|--------------------------------------------------------------------------
| HOME / AUTH
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/home', [
    App\Http\Controllers\HomeController::class,
    'index'
])->name('home');

Auth::routes();

Route::post('/login', [
    App\Http\Controllers\Auth\Logincontroller::class,
    'login'
]);


/*
|--------------------------------------------------------------------------
| CRUD PRINCIPALES
|--------------------------------------------------------------------------
*/

Route::resource(
    'ciudades',
    App\Http\Controllers\Ciudadcontroller::class
);

Route::resource(
    'Departamentos',
    App\Http\Controllers\DepartamentoController::class
);

Route::resource(
    'clientes',
    App\Http\Controllers\ClienteController::class
);

Route::resource(
    'articulos',
    App\Http\Controllers\ArticuloController::class
);

Route::resource(
    'sucursal',
    App\Http\Controllers\sucursalController::class
);

Route::resource(
    'lineas',
    App\Http\Controllers\LineaController::class
);

Route::resource(
    'carga_fotos',
    App\Http\Controllers\CargaFotosController::class
);

Route::resource(
    'usuarios',
    App\Http\Controllers\UsuarioController::class
);

Route::resource(
    'auditoria',
    App\Http\Controllers\auditoriaController::class
);

Route::resource(
    'permissions',
    App\Http\Controllers\PermissionController::class
);

Route::resource(
    'roles',
    App\Http\Controllers\RoleController::class
);

Route::resource(
    'pedido_compras',
    App\Http\Controllers\PedidoComprasController::class
);

Route::resource(
    'stocks',
    App\Http\Controllers\StockController::class
);

Route::resource(
    'stock_ventas_sucursales',
    App\Http\Controllers\stock_ventas_sucursales_Controller::class
);


/*
|--------------------------------------------------------------------------
| REDISTRIBUCIÓN - RESOURCE
|--------------------------------------------------------------------------
*/

Route::resource(
    'RedistribucionSugeridas',
    RedistribucionSugeridaController::class
);


/*
|--------------------------------------------------------------------------
| REDISTRIBUCIÓN - ANALIZAR
|--------------------------------------------------------------------------
*/

Route::post(
    '/RedistribucionSugeridas/analizar',
    [
        RedistribucionSugeridaController::class,
        'analizar'
    ]
)->name('RedistribucionSugeridas.analizar');


/*
|--------------------------------------------------------------------------
| REDISTRIBUCIÓN - APROBAR
|--------------------------------------------------------------------------
*/

Route::post(
    '/RedistribucionSugeridas/aprobar',
    [
        RedistribucionSugeridaController::class,
        'aprobar'
    ]
)->name('RedistribucionSugeridas.aprobar');


/*
|--------------------------------------------------------------------------
| REDISTRIBUCIÓN - RECHAZAR
|--------------------------------------------------------------------------
*/

Route::post(
    '/RedistribucionSugeridas/rechazar',
    [
        RedistribucionSugeridaController::class,
        'rechazar'
    ]
)->name('RedistribucionSugeridas.rechazar');


/*
|--------------------------------------------------------------------------
| REDISTRIBUCIÓN - VER PROCESO
|--------------------------------------------------------------------------
*/

Route::get(
    '/redistribucion-sugeridas/proceso/{id}',
    [
        RedistribucionSugeridaController::class,
        'proceso'
    ]
)->name('RedistribucionSugeridas.proceso');


/*
|--------------------------------------------------------------------------
| REDISTRIBUCIÓN - VER LOTE
|--------------------------------------------------------------------------
*/

Route::get(
    '/redistribucion-sugeridas/lote/{id}',
    [
        RedistribucionSugeridaController::class,
        'lote'
    ]
)->name('RedistribucionSugeridas.lote');


/*
|--------------------------------------------------------------------------
| USUARIO / PERFIL
|--------------------------------------------------------------------------
*/

Route::get(
    'users/detail/perfil',
    [
        App\Http\Controllers\UsuarioController::class,
        'perfil'
    ]
);

Route::post(
    'users/perfil/cambiar-password',
    [
        App\Http\Controllers\UsuarioController::class,
        'cambiarPassword'
    ]
);


/*
|--------------------------------------------------------------------------
| PEDIDOS DE COMPRA
|--------------------------------------------------------------------------
*/

Route::patch(
    '/pedido_compras/confirm/{id}',
    [
        PedidoComprasController::class,
        'confirm'
    ]
)->name('pedido_compras.confirm');

Route::get(
    'pedido_compras/{id}/edit',
    [
        PedidoComprasController::class,
        'edit'
    ]
)->name('pedido_compras.edit');

Route::put(
    'pedido_compras/{id}',
    [
        PedidoComprasController::class,
        'update'
    ]
)->name('pedido_compras.update');

Route::get(
    'pedido_compras/{id}/imprimir',
    [
        PedidoComprasController::class,
        'imprimir'
    ]
)->name('pedido_compras.imprimir')
    ->middleware('auth');

Route::get(
    '/pedido/export/{id}',
    [
        PedidoComprasController::class,
        'export'
    ]
)->name('pedido.export');

Route::get(
    '/pedido_compras/{id}/detalle',
    [
        PedidoComprasController::class,
        'detalle'
    ]
)->name('pedido_compras.detalle');


/*
|--------------------------------------------------------------------------
| ARTÍCULOS
|--------------------------------------------------------------------------
*/

Route::get(
    '/buscar-productos',
    [
        App\Http\Controllers\ArticuloController::class,
        'buscarProductos'
    ]
)->name('buscar.productos');

Route::get(
    '/articulos/importar',
    [
        App\Http\Controllers\ArticuloController::class,
        'showImportForm'
    ]
)->name('articulos.importar.form');

Route::post(
    '/articulos/importar',
    [
        App\Http\Controllers\ArticuloController::class,
        'import'
    ]
)->name('articulos.importar');


/*
|--------------------------------------------------------------------------
| PEDIDO COMPRA - BÚSQUEDA
|--------------------------------------------------------------------------
*/

Route::get(
    'buscar-productos-ped',
    [
        App\Http\Controllers\PedidoComprasController::class,
        'buscarProductoPed'
    ]
)->name('buscar-productos-ped');


/*
|--------------------------------------------------------------------------
| STOCK
|--------------------------------------------------------------------------
*/

Route::post(
    '/import-stock',
    [
        StockController::class,
        'importStock'
    ]
)->name('import.stock');

Route::post(
    '/import-stock-ventas-sucursales',
    [
        stock_ventas_sucursales_Controller::class,
        'importStock'
    ]
)->name('import.stock.ventas.sucursales');


/*
|--------------------------------------------------------------------------
| AUDITORÍA
|--------------------------------------------------------------------------
*/

Route::get(
    '/auditoria',
    [
        auditoriaController::class,
        'index'
    ]
)->name('auditoria.index');


/*
|--------------------------------------------------------------------------
| IMPORT PROGRESS
|--------------------------------------------------------------------------
*/

Route::get(
    '/import-progress',
    function () {
        return response()->json([
            'progress' => Cache::get(
                'import_progress',
                0
            )
        ]);
    }
)->name('import.progress');


/*
|--------------------------------------------------------------------------
| PASSWORD RESET
|--------------------------------------------------------------------------
*/

Route::get(
    'password/reset',
    [
        ForgotPasswordController::class,
        'showLinkRequestForm'
    ]
)->name('password.request');

Route::get(
    'password/reset/{token}',
    [
        ResetPasswordController::class,
        'showResetForm'
    ]
)->name('password.reset');

Route::post(
    'password/reset',
    [
        ResetPasswordController::class,
        'reset'
    ]
)->name('password.update');


/*
|--------------------------------------------------------------------------
| OT
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| OT - INDEX
|--------------------------------------------------------------------------
*/

Route::get(
    '/ot',
    [
        OtController::class,
        'index'
    ]
)->name('ot.index');


/*
|--------------------------------------------------------------------------
| OT - IMPORTACIÓN
|--------------------------------------------------------------------------
*/

Route::post(
    '/ot/importar',
    [
        OtController::class,
        'importar'
    ]
)->name('ot.importar');


/*
|--------------------------------------------------------------------------
| OT - BÚSQUEDA
|--------------------------------------------------------------------------
*/

Route::get(
    '/ot/buscar',
    [
        OtController::class,
        'buscar'
    ]
)->name('ot.buscar');


/*
|--------------------------------------------------------------------------
| OT - DASHBOARD
|--------------------------------------------------------------------------
*/

Route::get(
    '/dashboard/ot',
    [
        OtController::class,
        'dashboard'
    ]
)->name('dashboard.ot');


/*
|--------------------------------------------------------------------------
| OT - IMPORTACIÓN LOGÍSTICA
|--------------------------------------------------------------------------
*/

Route::post(
    '/ot/importar-logistica',
    [
        OtController::class,
        'importarLogistica'
    ]
)->name('ot.importar.logistica');


/*
|--------------------------------------------------------------------------
| OT - BÚSQUEDA PARA EDITAR
|--------------------------------------------------------------------------
|
| IMPORTANTE:
| Esta ruta debe estar ANTES de Route::resource('ots', ...)
| para que "buscar-editar" no sea interpretado como {ot}.
|
|--------------------------------------------------------------------------
*/

Route::get(
    '/ots/buscar-editar',
    [
        OtController::class,
        'buscarEditar'
    ]
)->name('ots.buscarEditar');


/*
|--------------------------------------------------------------------------
| OT - RESOURCE
|--------------------------------------------------------------------------
|
| Esta ruta genera automáticamente:
|
| GET       /ots
| GET       /ots/create
| POST      /ots
| GET       /ots/{ot}
| GET       /ots/{ot}/edit
| PUT/PATCH /ots/{ot}
| DELETE    /ots/{ot}
|
|--------------------------------------------------------------------------
*/

Route::resource(
    'ots',
    App\Http\Controllers\OtController::class
);


/*
|--------------------------------------------------------------------------
| OT - PROCESOS
|--------------------------------------------------------------------------
*/

Route::get(
    'ots/{id}/proceso/crear',
    [
        OtController::class,
        'nuevoProceso'
    ]
)->name('ots.proceso.create');

Route::post(
    'ots/{id}/proceso',
    [
        OtController::class,
        'guardarProceso'
    ]
)->name('ots.proceso.store');


/*
|--------------------------------------------------------------------------
| OT - DETALLES
|--------------------------------------------------------------------------
*/

Route::get(
    '/get-ot-details/{id}',
    [
        OtController::class,
        'getOtDetails'
    ]
);


/*
|--------------------------------------------------------------------------
| OT - TRAZABILIDAD
|--------------------------------------------------------------------------
*/

Route::delete(
    '/ot/trazabilidad/{id}',
    [
        OtController::class,
        'destroyTrazabilidad'
    ]
)->name('ot.trazabilidad.destroy');


/*
|--------------------------------------------------------------------------
| OT - DASHBOARD ATRASADAS
|--------------------------------------------------------------------------
*/

Route::get(
    '/dashboard/ot-atrasadas',
    [
        OtController::class,
        'otAtrasadas'
    ]
)->name('dashboard.ot-atrasadas');


/*
|--------------------------------------------------------------------------
| OT - DASHBOARD ESTADO
|--------------------------------------------------------------------------
*/

Route::get(
    '/dashboard/ot-estado',
    [
        OtController::class,
        'otEstado'
    ]
)->name('dashboard.ot-estado');


/*
|--------------------------------------------------------------------------
| IA PRENDAS
|--------------------------------------------------------------------------
*/

Route::get(
    '/ia-prendas',
    [
        ProcesadorImagenController::class,
        'index'
    ]
)->name('ia.index');

Route::post(
    '/ia-prendas/subir',
    [
        ProcesadorImagenController::class,
        'subir'
    ]
)->name('ia.subir');

Route::get(
    '/ia-prendas/descargar',
    [
        ProcesadorImagenController::class,
        'descargarZip'
    ]
)->name('ia.descargar');


/*
|--------------------------------------------------------------------------
| REDISTRIBUCIÓN - PROCESAR LOTE
|--------------------------------------------------------------------------
*/

Route::post(
    '/redistribucion-sugeridas/lote/{lote}/procesar',
    [
        RedistribucionSugeridaController::class,
        'procesarLote'
    ]
)->name('RedistribucionSugeridas.procesarLote');


/*
|--------------------------------------------------------------------------
| REDISTRIBUCIÓN - FINALIZAR LOTE
|--------------------------------------------------------------------------
*/

Route::post(
    '/redistribucion-sugeridas/lote/{id}/finalizar',
    [
        RedistribucionSugeridaController::class,
        'finalizarLote'
    ]
)->name('RedistribucionSugeridas.finalizarLote');


/*
|--------------------------------------------------------------------------
| REDISTRIBUCIÓN - GENERAR LOTE
|--------------------------------------------------------------------------
*/

Route::post(
    '/redistribucion-sugeridas/generar-lote',
    [
        RedistribucionSugeridaController::class,
        'generarLote'
    ]
)->name('RedistribucionSugeridas.generarLote');


/*
|--------------------------------------------------------------------------
| REDISTRIBUCIÓN - GESTIÓN DE LOTES
|--------------------------------------------------------------------------
*/

Route::get(
    '/redistribucion-sugeridas/lotes',
    [
        RedistribucionSugeridaController::class,
        'lotes'
    ]
)->name('RedistribucionSugeridas.lotes');


/*
|--------------------------------------------------------------------------
| REDISTRIBUCIÓN - VER LOTE INDIVIDUAL
|--------------------------------------------------------------------------
*/

Route::get(
    '/redistribucion-sugeridas/lote/{id}',
    [
        RedistribucionSugeridaController::class,
        'lote'
    ]
)->name('RedistribucionSugeridas.lote');


/*
|--------------------------------------------------------------------------
| REDISTRIBUCIÓN - PDF LOTE
|--------------------------------------------------------------------------
*/

Route::get(
    '/redistribucion-sugeridas/lote/{id}/pdf',
    [
        RedistribucionSugeridaController::class,
        'exportarLotePdf'
    ]
)->name('RedistribucionSugeridas.lote.pdf');


/*
|--------------------------------------------------------------------------
| REDISTRIBUCIÓN - EXCEL LOTE
|--------------------------------------------------------------------------
*/

Route::get(
    '/redistribucion-sugeridas/lote/{id}/excel',
    [
        RedistribucionSugeridaController::class,
        'exportarLoteExcel'
    ]
)->name('RedistribucionSugeridas.lote.excel');


/*
|--------------------------------------------------------------------------
| REDISTRIBUCIÓN - FIN
|--------------------------------------------------------------------------
*/

Route::get(
    '/dashboard/ot-analisis',
    [OtController::class, 'dashboardOT']
)->name('dashboard.otAnalisis');



Route::post(
    '/redistribucion-sugeridas/importar-remisiones',
    [RedistribucionSugeridaController::class, 'importarRemisiones']
)->name('RedistribucionSugeridas.importarRemisiones');

Route::get(
    '/dashboard/ot-logistica',
    [OtController::class, 'dashboardlogistica']
)->name('dashboard.ot-logistica');

Route::get(
    '/dashboard/logistica/exportar',
    [OtController::class, 'exportarDashboardLogistica']
)->name('dashboard.logistica.exportar');

Route::get('/clientes/ciudades', [ClienteController::class, 'getCiudades'])
    ->name('clientes.ciudades');

Route::get('/ot/historia-general', [OtController::class, 'historiaGeneral'])
    ->name('ots.historia-general');

use App\Http\Controllers\ControlTerminacionController;

Route::get(
    '/control/terminacion',
    [ControlTerminacionController::class, 'index']
)->name('control.terminacion');

Route::resource('redistribucion-configs', App\Http\Controllers\RedistribucionConfigController::class);