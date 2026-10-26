<?php

namespace App\Http\Controllers;

use App\Imports\AccesoriosCompraImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class AccesoriosController extends Controller
{
    public function __construct()
    {
        // Acceso exclusivo al rol ADMIN (Spatie Permission).
        // Se aplica también a solicitudes directas fuera del menú.
        $this->middleware(['auth', 'role:ADMIN']);
    }

    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));
        $linea = trim((string) $request->input('linea', ''));
        $orden = (string) $request->input('orden', 'codigo');
        $ordenesPermitidos = ['codigo', 'descripcion', 'linea'];
        if (!in_array($orden, $ordenesPermitidos, true)) {
            $orden = 'codigo';
        }

        $lineas = DB::table('accesorios_articulos')
            ->whereNotNull('linea')
            ->where('linea', '<>', '')
            ->distinct()
            ->orderBy('linea')
            ->pluck('linea');

        $articulos = DB::table('accesorios_articulos')
            ->when($buscar !== '', function ($consulta) use ($buscar) {
                $consulta->where(function ($q) use ($buscar) {
                    $q->where('codigo', 'ilike', '%' . $buscar . '%')
                        ->orWhere('descripcion', 'ilike', '%' . $buscar . '%')
                        ->orWhere('linea', 'ilike', '%' . $buscar . '%');
                });
            })
            ->when($linea !== '', function ($consulta) use ($linea) {
                $consulta->where('linea', $linea);
            })
            ->orderBy($orden)
            ->orderBy('codigo')
            ->paginate(30)
            ->appends($request->query());

        $ids = $articulos->getCollection()->pluck('id')->all();
        $codigos = $articulos->getCollection()->pluck('codigo')->all();

        $compras = DB::table('accesorios_compras')->whereIn('articulo_id', $ids)
            ->selectRaw('articulo_id, SUM(cantidad_compra) AS cantidad')
            ->groupBy('articulo_id')->pluck('cantidad', 'articulo_id');

        $remisiones = DB::table('ot_logistica_remisiones')
            ->whereIn('accesorio_articulo_id', $ids)
            ->selectRaw('accesorio_articulo_id, SUM(cantidad) AS remitido, SUM(CASE WHEN fecha_recepcion IS NOT NULL THEN cantidad ELSE 0 END) AS recibido')
            ->groupBy('accesorio_articulo_id')->get()->keyBy('accesorio_articulo_id');

        $ventas = Schema::hasTable('ventas')
            ? DB::table('ventas')->whereIn('codigo', $codigos)
                ->selectRaw('codigo, SUM(cantidad) AS cantidad')
                ->groupBy('codigo')->pluck('cantidad', 'codigo')
            : collect();

        $calculoStock = (new \App\Services\AccesoriosStockService())
            ->calcular($ids, now()->toDateString());
        $stocks = $calculoStock['porCodigo'];

        $articulos->getCollection()->transform(function ($articulo) use ($compras, $remisiones, $ventas, $stocks) {
            $articulo->comprado = (int) ($compras[$articulo->id] ?? 0);
            $articulo->remitido = (int) ($remisiones[$articulo->id]->remitido ?? 0);
            $articulo->recibido = (int) ($remisiones[$articulo->id]->recibido ?? 0);
            $articulo->vendido = (int) ($ventas[$articulo->codigo] ?? 0);
            $articulo->stock_calculado = isset($stocks[$articulo->id])
                ? (int) $stocks[$articulo->id]->saldo : null;
            return $articulo;
        });

        $resumen = DB::table('accesorios_compras')
            ->selectRaw('COUNT(*) AS compras, COALESCE(SUM(cantidad_compra), 0) AS unidades')
            ->first();
        $totalArticulos = DB::table('accesorios_articulos')->count();

        // Indicadores del bloque visible: no se confunden con totales generales.
        $filasVisibles = $articulos->getCollection();
        $indicadoresPagina = [
            'unidades_compradas' => (int) $filasVisibles->sum('comprado'),
            'ventas_netas' => (int) $filasVisibles->sum('vendido'),
            'stock_conocido' => (int) $filasVisibles->filter(function ($item) {
                return $item->stock_calculado !== null;
            })->sum('stock_calculado'),
            'sin_datos' => $filasVisibles->filter(function ($item) {
                return $item->stock_calculado === null;
            })->count(),
            'saldo_negativo' => $filasVisibles->filter(function ($item) {
                return $item->stock_calculado !== null && $item->stock_calculado < 0;
            })->count(),
            'sin_stock' => $filasVisibles->filter(function ($item) {
                return $item->stock_calculado !== null && $item->stock_calculado === 0;
            })->count(),
        ];

        return view('accesorios.index', compact(
            'articulos', 'buscar', 'linea', 'lineas', 'orden',
            'resumen', 'totalArticulos', 'indicadoresPagina'
        ));
    }

    public function importarForm()
    {
        $ultimas = DB::table('accesorios_compras as c')
            ->join('accesorios_articulos as a', 'a.id', '=', 'c.articulo_id')
            ->select('a.codigo', 'c.fco_nro', 'c.fecha_envio', 'c.cantidad_compra')
            ->orderByDesc('c.id')->limit(10)->get();
        return view('accesorios.importar', compact('ultimas'));
    }

    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        $importador = new AccesoriosCompraImport();
        try {
            Excel::import($importador, $request->file('archivo'));

            // Reconcilia documentos históricos sin asignar remisiones a un FCO arbitrario.
            $vinculadas = DB::update(
                'UPDATE ot_logistica_remisiones AS r
                 SET accesorio_articulo_id = a.id
                 FROM accesorios_articulos AS a
                 WHERE UPPER(BTRIM(r.codigo)) = a.codigo
                   AND r.accesorio_articulo_id IS NULL'
            );

            return redirect()->route('accesorios.index')->with(
                'success',
                'Importación terminada: ' . $importador->articulosNuevos . ' artículos nuevos; '
                . $importador->comprasNuevas . ' compras nuevas; '
                . $importador->comprasActualizadas . ' actualizadas; '
                . $importador->sinCambios . ' sin cambios; '
                . $vinculadas . ' remisiones históricas vinculadas.'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('IMPORTACION ACCESORIOS', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'No se pudo importar: ' . $e->getMessage());
        }
    }

    public function show(int $id)
    {
        $articulo = DB::table('accesorios_articulos')->find($id);
        abort_unless($articulo, 404);

        $compras = DB::table('accesorios_compras')
            ->where('articulo_id', $id)
            ->orderBy('fecha_envio')->orderBy('fco_nro')->get();

        // Orden del circuito documental:
        // 1) depósito de insumos/tejidos -> Casa Central (ingreso);
        // 2) Casa Central -> sucursales (distribución);
        // 3) otros movimientos.
        // Dentro de cada etapa, primero las fechas y remisiones más antiguas.
        $remisiones = DB::table('ot_logistica_remisiones')
            ->where('accesorio_articulo_id', $id)
            ->orderByRaw("
                CASE
                    WHEN (
                        COALESCE(sucursal_salida, '') ILIKE '%DEPOSITO%'
                        OR COALESCE(sucursal_salida, '') ILIKE '%DEPÓSITO%'
                        OR COALESCE(sucursal_salida, '') ILIKE '%INSUMOS%'
                    )
                    AND COALESCE(sucursal_destino, '') ILIKE '%CASA CENTRAL%'
                    THEN 0
                    WHEN COALESCE(sucursal_salida, '') ILIKE '%CASA CENTRAL%'
                    THEN 1
                    ELSE 2
                END
            ")
            ->orderByRaw('fecha_remision ASC NULLS LAST')
            ->orderBy('numero_remision')
            ->orderBy('id')
            ->paginate(25, ['*'], 'pagina_remision');

        $resumenRemisiones = DB::table('ot_logistica_remisiones')
            ->where('accesorio_articulo_id', $id)
            ->selectRaw('COALESCE(SUM(cantidad),0) AS remitido,
                COALESCE(SUM(CASE WHEN fecha_recepcion IS NOT NULL THEN cantidad ELSE 0 END),0) AS recibido')
            ->first();

        $ventasPorLocal = Schema::hasTable('ventas')
            ? DB::table('ventas')->where('codigo', $articulo->codigo)
                ->selectRaw('local, SUM(cantidad) AS cantidad')
                ->groupBy('local')->orderBy('local')->get()
            : collect();

        $totalComprado = $compras->sum('cantidad_compra');
        $totalVendido = $ventasPorLocal->sum('cantidad');

        // Cada sucursal tiene saldo propio. Transferencias recibidas desde
        // cualquier origen vuelven a sumar; salidas reducen el origen.
        $calculoStock = (new \App\Services\AccesoriosStockService())
            ->calcular([$id], now()->toDateString());
        $saldosStock = collect($calculoStock['detalle']->get($id, []));
        $stockCalculado = $calculoStock['porCodigo']->has($id)
            ? $calculoStock['porCodigo']->get($id)->saldo : null;

        return view('accesorios.show', compact(
            'articulo', 'compras', 'remisiones', 'resumenRemisiones',
            'ventasPorLocal', 'totalComprado', 'totalVendido',
            'saldosStock', 'stockCalculado'
        ));
    }
}
