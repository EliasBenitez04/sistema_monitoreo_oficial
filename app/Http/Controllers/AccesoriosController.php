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
        // Panel de lectura compartido; carga y edición exclusivas de ADMIN.
        $this->middleware(['auth', 'role:ADMIN|visualizador_accesorios'])
            ->only(['index', 'show']);
        $this->middleware(['auth', 'role:ADMIN'])
            ->except(['index', 'show']);
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

        $ventas = Schema::hasTable('ventas')
            ? DB::table('ventas')->whereIn('codigo', $codigos)
                ->selectRaw('codigo, SUM(cantidad) AS cantidad')
                ->groupBy('codigo')->pluck('cantidad', 'codigo')
            : collect();

        $calculoStock = (new \App\Services\AccesoriosStockService())
            ->calcular($ids, now()->toDateString());
        $stocks = $calculoStock['porCodigo'];
        $flujos = $calculoStock['flujoPorCodigo'];

        $articulos->getCollection()->transform(function ($articulo) use ($compras, $flujos, $ventas, $stocks) {
            $articulo->comprado = (int) ($compras[$articulo->id] ?? 0);
            $flujo = $flujos->get((int) $articulo->id, []);
            // La recepción inicial y la distribución son etapas, no stock
            // adicional. Por ejemplo: 7 Depósito→Central + 7 Central→locales
            // significa 7 distribuidas, no 14 prendas diferentes.
            $articulo->ingreso_deposito = (int) ($flujo['ingreso_deposito'] ?? 0);
            $articulo->remitido = (int) ($flujo['distribuido_central'] ?? 0);
            $articulo->recibido = (int) ($flujo['recibido_locales'] ?? 0);
            $articulo->traslado_locales = (int) ($flujo['traslado_locales'] ?? 0);
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


    /**
     * Ajuste manual de una línea FCO, aislado por artículo e identificador de compra.
     * Los indicadores monetarios leen accesorios_compras al abrir el dashboard;
     * no se alteran remisiones ni ventas reales.
     */
    public function actualizarCompra(Request $request, int $id, int $compraId)
    {
        $datos = $request->validate([
            'cantidad_compra' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'costo_unitario' => ['required', 'numeric', 'min:0', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/'],
            'precio_venta' => ['required', 'numeric', 'min:0', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/'],
        ], [
            'cantidad_compra.required' => 'Ingresá la cantidad comprada.',
            'cantidad_compra.integer' => 'La cantidad debe ser un número entero.',
            'cantidad_compra.min' => 'La cantidad debe ser mayor que cero.',
            'cantidad_compra.max' => 'La cantidad supera el máximo permitido.',
            'costo_unitario.required' => 'Ingresá el costo unitario.',
            'costo_unitario.numeric' => 'El costo unitario debe ser numérico.',
            'costo_unitario.min' => 'El costo unitario no puede ser negativo.',
            'costo_unitario.regex' => 'El costo unitario admite como máximo dos decimales.',
            'precio_venta.required' => 'Ingresá el precio de venta.',
            'precio_venta.numeric' => 'El precio de venta debe ser numérico.',
            'precio_venta.min' => 'El precio de venta no puede ser negativo.',
            'precio_venta.regex' => 'El precio de venta admite como máximo dos decimales.',
        ]);

        $modificado = DB::transaction(function () use ($id, $compraId, $datos) {
            $compra = DB::table('accesorios_compras')
                ->where('id', $compraId)
                ->where('articulo_id', $id)
                ->lockForUpdate()
                ->first();
            abort_unless($compra, 404, 'La compra no corresponde al accesorio indicado.');

            $antes = [
                'cantidad_compra' => (int) $compra->cantidad_compra,
                'costo_unitario' => number_format((float) $compra->costo_unitario, 2, '.', ''),
                'precio_venta' => number_format((float) $compra->precio_venta, 2, '.', ''),
            ];
            $despues = [
                'cantidad_compra' => (int) $datos['cantidad_compra'],
                'costo_unitario' => number_format((float) $datos['costo_unitario'], 2, '.', ''),
                'precio_venta' => number_format((float) $datos['precio_venta'], 2, '.', ''),
            ];

            if ($antes === $despues) {
                return false;
            }

            DB::table('accesorios_compras')
                ->where('id', $compra->id)
                ->where('articulo_id', $id)
                ->update(array_merge($despues, ['updated_at' => now()]));

            Log::info('ACCESORIOS_FCO_EDICION_MANUAL', [
                'usuario_id' => auth()->id(),
                'articulo_id' => $id,
                'compra_id' => $compra->id,
                'fco_nro' => $compra->fco_nro,
                'fecha_envio' => $compra->fecha_envio,
                'antes' => $antes,
                'despues' => $despues,
            ]);

            return true;
        });

        return redirect()->to(route('accesorios.show', $id) . '#historial-compras')
            ->with('success', $modificado
                ? 'Compra FCO actualizada. Totales e indicadores recalculados con los nuevos valores.'
                : 'La compra FCO no tenía cambios.');
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
        $etapasRemision = $calculoStock['flujoPorCodigo']->get($id, [
            'ingreso_deposito' => 0,
            'distribuido_central' => 0,
            'recibido_locales' => 0,
            'traslado_locales' => 0,
            'otros_movimientos' => 0,
        ]);
        $stockCalculado = $calculoStock['porCodigo']->has($id)
            ? $calculoStock['porCodigo']->get($id)->saldo : null;

        // Historial inalterable de correcciones hechas por ADMIN.
        $ajustesStock = Schema::hasTable('accesorios_stock_ajustes')
            ? DB::table('accesorios_stock_ajustes as aj')
                ->leftJoin('users as u', 'u.id', '=', 'aj.usuario_id')
                ->where('aj.accesorio_articulo_id', $id)
                ->orderByDesc('aj.id')
                ->limit(30)
                ->get([
                    'aj.sucursal', 'aj.saldo_anterior', 'aj.saldo_nuevo',
                    'aj.diferencia', 'aj.motivo', 'aj.created_at',
                    'u.name as usuario',
                ])
            : collect();

        return view('accesorios.show', compact(
            'articulo', 'compras', 'remisiones', 'etapasRemision',
            'ventasPorLocal', 'totalComprado', 'totalVendido',
            'saldosStock', 'stockCalculado', 'ajustesStock'
        ));
    }
}
