<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Exports\PedidoExport;
use Illuminate\Console\View\Components\Alert;
use Maatwebsite\Excel\Facades\Excel;

class PedidoComprasController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:pedido_compras index')->only('index');
        $this->middleware('permission:pedido_compras create')->only('create', 'store');
        $this->middleware('permission:pedido_compras destroy')->only('destroy');
        $this->middleware('permission:pedido_compras confirm')->only('confirm');
    }

    public function index()
    {
        // Consulta de datos para pedido_compras
        $pedidoQuery = DB::table('pedido_compras')
            ->select(
                'pedido_compras.*',
                'sucursal.suc_descri as suc_descri',
                'users.name as usuario',
                'u2.name as confirmado_por',
                DB::raw("COALESCE(SUM(detalle_pedido.det_cantidad), 0) as total_cantidad"),
                DB::raw("CONCAT(clientes.cli_nombre, ' ', clientes.cli_apellido) as cliente") // <-- cliente concatenado
            )
            ->join('users', 'users.id', '=', 'pedido_compras.user_id')
            ->join('sucursal', 'sucursal.cod_suc', '=', 'pedido_compras.cod_suc')
            ->leftJoin('detalle_pedido', 'detalle_pedido.id_pedido_compras', '=', 'pedido_compras.id_pedido')
            ->leftJoin('users as u2', 'u2.id', '=', 'pedido_compras.confirmado_por')
            ->leftJoin('clientes', 'clientes.id_cliente', '=', 'pedido_compras.id_cliente') // <-- join con clientes
            ->groupBy(
                'pedido_compras.id_pedido',
                'pedido_compras.ped_fecha',
                'pedido_compras.ped_estado',
                'sucursal.suc_descri',
                'users.name',
                'u2.name',
                'clientes.cli_nombre',
                'clientes.cli_apellido'
            )
            ->orderByDesc('pedido_compras.id_pedido');

        $pedido = $pedidoQuery->paginate(10);

        return view('pedido_compras.index')->with('pedido_compras', $pedido);
    }

    public function create()
    {
        $condicion = [
            "CONTADO" => "CONTADO",
            "CREDITO" => "CREDITO"
        ];

        $sucursal = DB::table('sucursal')
            ->select(DB::raw("suc_descri, cod_suc"))
            ->pluck('suc_descri', 'cod_suc');

        $clientes = DB::table('clientes')
            ->select(
                'id_cliente',
                DB::raw("cli_ci || ' - ' || cli_nombre || ' ' || cli_apellido AS nombre")
            )
            ->orderBy('cli_ci')
            ->pluck('nombre', 'id_cliente');

        // =========================================================
        // DEPARTAMENTOS
        // =========================================================

        $departamento = DB::table('departamento')
            ->pluck('dep_descripcion', 'id_departamento');

        $ciudad = DB::table('ciudad')
            ->pluck('ciu_descripcion', 'id_ciudad');

        // =========================================================
        // PRODUCTOS
        // =========================================================

        $productos = DB::table('articulos')
            ->join('stock', 'articulos.id_articulo', '=', 'stock.id_articulo')
            ->select(
                'articulos.id_articulo',
                'articulos.art_codigo',
                'articulos.art_descripcion',
                'articulos.prec_vent',
                'stock.cantidad'
            )
            ->orderBy('articulos.art_codigo', 'asc')
            ->take(20)
            ->get();

        $detalles = [];

        // =========================================================
        // BUSCAR NÚMEROS DE PEDIDO UTILIZADOS
        // =========================================================

        $usados = DB::table('pedido_compras')
            ->where('ped_estado', '!=', 'ANULADO')
            ->selectRaw("CAST(split_part(nro_pedido, '-', 2) AS INTEGER) as num")
            ->get()
            ->pluck('num')
            ->toArray();

        $numero = 1;

        while (in_array($numero, $usados)) {
            $numero++;
        }

        $nroPedidoPreview = "PED-" . $numero;

        // =========================================================
        // VISTA
        // =========================================================

        return view('pedido_compras.create')
            ->with('condicion', $condicion)
            ->with('sucursal', $sucursal)
            ->with('productos', $productos)
            ->with('clientes', $clientes)
            ->with('departamento', $departamento)
            ->with('ciudad', $ciudad)
            ->with('detalles', $detalles)
            ->with('nroPedidoPreview', $nroPedidoPreview);
    }


    public function store(Request $request)
    {
        Log::info("==== INICIO STORE PEDIDO ====");

        $input = $request->all();

        Log::info("Total inputs recibidos", [
            'total_inputs' => count($input),
            'codigos_count' => isset($input['codigo']) ? count($input['codigo']) : 0,
            'cantidades_count' => isset($input['cantidad']) ? count($input['cantidad']) : 0,
        ]);

        $fecha  = Carbon::parse($input['ped_fecha'])->format('Y-m-d');
        $actual = Carbon::now()->format('Y-m-d');

        // Validar fecha
        if ($fecha > $actual) {
            Log::warning("Fecha inválida", ['fecha' => $fecha, 'actual' => $actual]);

            alert()->info('Error', 'La fecha del pedido no puede ser mayor a la fecha actual.');
            return redirect(route('pedido_compras.create'))->withInput();
        }

        // Validar que haya artículos
        if (!$request->has('codigo') || count($request->codigo) === 0) {
            Log::warning("No hay artículos en el request");

            alert()->warning('Atención', 'Debe agregar al menos un artículo al pedido.');
            return redirect()->back()->withInput();
        }

        DB::beginTransaction();

        try {
            Log::info("Iniciando transacción");

            // Buscar número de pedido libre
            $usados = DB::table('pedido_compras')
                ->where('ped_estado', '!=', 'ANULADO')
                ->selectRaw("CAST(split_part(nro_pedido, '-', 2) AS INTEGER) as num")
                ->get()
                ->pluck('num')
                ->toArray();

            $numero = 1;
            while (in_array($numero, $usados)) {
                $numero++;
            }
            $nroPedido = "PED-" . $numero;

            Log::info("Número de pedido generado", ['nroPedido' => $nroPedido]);

            // Insertar pedido cabecera
            $insertCompra = DB::table('pedido_compras')->insertGetId([
                'nro_pedido'  => $nroPedido,
                'user_id'     => auth()->user()->id,
                'id_cliente'  => $input['id_cliente'],
                'condicion'   => $input['condicion'],
                'intervalo'   => $input['intervalo'] ?? null,
                'cant_cuotas' => $input['cant_cuotas'] ?? null,
                'ped_fecha'   => $input['ped_fecha'],
                'ped_estado'  => "PENDIENTE",
                'cod_suc'     => $input['cod_suc'],
                'obs'         => $input['obs'] ?? null,
            ], 'id_pedido');

            Log::info("Cabecera insertada", ['id_pedido' => $insertCompra]);

            // Verificar si se aplica descuento general
            $aplicaDescuento = $input['aplica_descuento'] ?? 'NO';
            $descuentoGeneral = ($aplicaDescuento === 'SI' && isset($input['descuento']))
                ? floatval($input['descuento'])
                : 0;

            if ($descuentoGeneral > 0 && $descuentoGeneral < 1) {
                $descuentoGeneral = $descuentoGeneral * 100;
            }

            Log::info("Descuento aplicado", [
                'aplica' => $aplicaDescuento,
                'descuento' => $descuentoGeneral
            ]);

            $totalSinDescuento = 0;
            $contador = 0;

            // Insertar detalles
            foreach ($input['codigo'] as $key => $value) {

                $contador++;

                // Log cada 50 registros para no saturar
                if ($contador % 50 == 0) {
                    Log::info("Procesando detalle", [
                        'iteracion' => $contador,
                        'codigo' => $value
                    ]);
                }

                $articulo = DB::table('articulos')
                    ->where('art_codigo', $value)
                    ->select('id_articulo', 'prec_vent')
                    ->first();

                if (!$articulo) {
                    Log::error("Artículo no encontrado", ['codigo' => $value]);
                    throw new \Exception("El artículo con código {$value} no existe.");
                }

                $cantidad = $input['cantidad'][$key];
                $precio   = $articulo->prec_vent;

                $subtotal = $cantidad * $precio;
                $subtotalConDescuento = $subtotal * (1 - $descuentoGeneral / 100);

                $totalSinDescuento += $subtotal;

                DB::insert(
                    "INSERT INTO detalle_pedido(id_articulo, id_pedido_compras, det_cantidad, det_subtotal, det_descuento)
                 VALUES(?, ?, ?, ?, ?)",
                    [
                        $articulo->id_articulo,
                        $insertCompra,
                        $cantidad,
                        $subtotalConDescuento,
                        $descuentoGeneral
                    ]
                );
            }

            Log::info("Detalles insertados", [
                'total_procesados' => $contador
            ]);

            // Actualizar cabecera con total y descuento
            DB::table('pedido_compras')
                ->where('id_pedido', $insertCompra)
                ->update([
                    'ped_total' => $totalSinDescuento * (1 - $descuentoGeneral / 100),
                    'descuento' => $descuentoGeneral
                ]);

            Log::info("Cabecera actualizada con totales", [
                'total' => $totalSinDescuento
            ]);

            DB::commit();

            Log::info("==== FIN STORE OK ====");

            alert()->success("Éxito", "Pedido generado correctamente!!!");
            return redirect(route('pedido_compras.index'));
        } catch (\Exception $ex) {
            DB::rollBack();

            Log::error("ERROR DE CREACION DE PEDIDOS:::::::::", [
                'mensaje' => $ex->getMessage(),
                'linea' => $ex->getLine(),
                'archivo' => $ex->getFile()
            ]);

            alert()->error("Error", "Error en la creación de Pedido.");
            return redirect()->back()->withInput($input);
        }
    }

    public function buscarProductoPed(Request $request)
    {
        $query = trim($request->get('query'));
        $cod_suc = $request->get('cod_suc');

        $productosQuery = DB::table('stock as s')
            ->join('articulos as a', 'a.id_articulo', '=', 's.id_articulo')

            // Vista que contiene el stock de todas las sucursales
            ->leftJoin('v_stock_sucursales as v', function ($join) {
                $join->on('v.codigo', '=', 'a.art_codigo');
            })

            ->select(
                'a.art_codigo',
                'a.art_descripcion',
                'a.prec_vent',
                's.cantidad',
                's.cod_suc',

                // Stock total de todas las sucursales
                DB::raw('COALESCE(MAX(v.stock_general), 0) as stock_general'),

                // Stock disponible en OTRAS sucursales
                DB::raw('
                GREATEST(
                    COALESCE(MAX(v.stock_general), 0) - COALESCE(s.cantidad, 0),
                    0
                ) as stock_disponible_pedir
            ')
            )

            ->when($cod_suc, function ($q) use ($cod_suc) {
                $q->where('s.cod_suc', $cod_suc);
            })

            ->when($query, function ($q) use ($query) {
                $q->where(function ($q2) use ($query) {

                    $q2->where(
                        'a.art_codigo',
                        'ILIKE',
                        $query . '%'
                    )

                        ->orWhere(
                            'a.art_descripcion',
                            'ILIKE',
                            $query . '%'
                        );
                });
            })

            ->groupBy(
                'a.art_codigo',
                'a.art_descripcion',
                'a.prec_vent',
                's.cantidad',
                's.cod_suc'
            )

            ->orderBy('a.art_codigo')
            ->limit(15)
            ->get();

        return view('pedido_compras.buscar_producto', [
            'productos' => $productosQuery
        ]);
    }

    public function show($id)
    {
        // Consulta de cabecera del pedido con datos del usuario, sucursal y cliente
        $pedido = DB::table('pedido_compras')
            ->select(
                'pedido_compras.*',
                'users.name as usuario',
                'sucursal.suc_descri as sucursal',
                DB::raw("CONCAT(clientes.cli_nombre, ' ', clientes.cli_apellido) as cliente"),
                'clientes.cli_ci as cli_ci',
                'clientes.cli_telefono as cli_telefono',
                'clientes.cli_direccion as cli_direccion'
            )
            ->join('users', 'users.id', 'pedido_compras.user_id')
            ->join('sucursal', 'sucursal.cod_suc', 'pedido_compras.cod_suc')
            ->join('clientes', 'clientes.id_cliente', 'pedido_compras.id_cliente')
            ->where('pedido_compras.id_pedido', $id)
            ->first();

        if (empty($pedido)) {
            alert()->error('Error', 'El Pedido no existe.');
            return redirect(route('pedido_compras.index'));
        }

        // Consulta de detalle del pedido
        $detalle = DB::table('detalle_pedido')
            ->select(
                'detalle_pedido.id_articulo',
                'detalle_pedido.det_cantidad',
                'detalle_pedido.det_subtotal',
                DB::raw('COALESCE(detalle_pedido.det_descuento, 0) as det_descuento'),
                'articulos.art_codigo',
                'articulos.art_descripcion'
            )
            ->join('articulos', 'articulos.id_articulo', '=', 'detalle_pedido.id_articulo')
            ->where('detalle_pedido.id_pedido_compras', $id)
            ->orderByRaw("
    regexp_replace(articulos.art_codigo, '(TP|TM|RN|TG|GG|RR|[0-9]+)$', '') ASC,

    CASE
        WHEN articulos.art_codigo ~ 'TP$' THEN 1
        WHEN articulos.art_codigo ~ 'TM$' THEN 2
        WHEN articulos.art_codigo ~ 'RN$' THEN 3
        WHEN articulos.art_codigo ~ 'TG$' THEN 4
        WHEN articulos.art_codigo ~ 'GG$' THEN 5
        WHEN articulos.art_codigo ~ 'RR$' THEN 6
        ELSE 99
    END ASC,

    COALESCE(
        NULLIF(regexp_replace(articulos.art_codigo, '\\D', '', 'g'), ''),
        '0'
    )::BIGINT ASC
")
            ->get();

        // Calcular totales generales
        $totalSinDescuento = $detalle->sum(function ($d) {
            if ($d->det_descuento >= 100) {
                return 0; // o podés devolver det_subtotal según tu lógica
            }
            return $d->det_subtotal / (1 - $d->det_descuento / 100);
        });

        $totalDescuento = $detalle->sum(function ($d) {
            if ($d->det_descuento >= 100) {
                return $d->det_subtotal; // todo es descuento
            }
            return ($d->det_subtotal / (1 - $d->det_descuento / 100)) - $d->det_subtotal;
        });
        $totalConDescuento = $detalle->sum('det_subtotal');

        return view('pedido_compras.show', compact(
            'pedido',
            'detalle',
            'totalSinDescuento',
            'totalDescuento',
            'totalConDescuento'
        ));
    }

    public function destroy($id)
    {
        $pedido = DB::table('pedido_compras')->where('id_pedido', $id)->first();

        if (empty($pedido)) {
            alert()->error('Error', 'El Pedido no existe.');
            return redirect(route('pedido_compras.index'));
        }

        DB::table('pedido_compras')->where('id_pedido', $id)->update([
            'ped_estado' => "ANULADO"
        ]);

        alert()->success('Éxito', 'El Pedido se anuló correctamente.');
        return redirect(route('pedido_compras.index'));
    }

    public function getDetallePedido($id)
    {
        $detalles = DB::table('detalle_pedido')
            ->join('articulos', 'detalle_pedido.id_articulo', '=', 'articulos.id_articulo')
            ->where('id_pedido_compras', $id)
            ->select('articulos.art_descripcion', 'detalle_pedido.det_cantidad')
            ->get();

        return response()->json($detalles);
    }

    public function confirm(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $pedido = DB::table('pedido_compras')->where('id_pedido', $id)->first();

            if (!$pedido || $pedido->ped_estado !== 'PENDIENTE') {
                DB::rollBack();
                alert()->error('Error', 'Pedido no válido o ya confirmado!!!');
                return redirect()->back();
            }

            DB::table('pedido_compras')
                ->where('id_pedido', $id)
                ->update([
                    'ped_estado'     => 'CONFIRMADO',
                    'confirmado_por' => auth()->user()->id,
                ]);

            DB::commit();

            alert()->success('Éxito', 'Pedido confirmado correctamente!!!');
            return redirect()->route('pedido_compras.index');
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error("ERROR DE CONFIRMACION DE PEDIDO: " . $ex->getMessage());

            alert()->error('Error', 'Error al confirmar el pedido!!!');
            return redirect()->back();
        }
    }

    public function imprimir($id)
    {
        $pedido = DB::table('pedido_compras as p')
            ->join('clientes as c', 'c.id_cliente', '=', 'p.id_cliente')
            ->join('sucursal as s', 's.cod_suc', '=', 'p.cod_suc')
            ->select(
                'p.*',
                's.suc_descri',
                'c.cli_nombre',
                'c.cli_apellido',
                'c.cli_ci',
                'c.cli_telefono',
                'c.cli_direccion',
                DB::raw("CONCAT(c.cli_nombre, ' ', c.cli_apellido) as cliente")
            )
            ->where('p.id_pedido', $id)
            ->first();

        if (!$pedido) {
            alert()->error('Error', 'Pedido no encontrado.');
            return redirect()->route('pedido_compras.index');
        }

        $detalle = DB::table('detalle_pedido as d')
            ->join('articulos as a', 'a.id_articulo', '=', 'd.id_articulo')
            ->where('d.id_pedido_compras', $id)
            ->select('d.*', 'a.art_codigo', 'a.art_descripcion')
            ->orderByRaw("
    regexp_replace(a.art_codigo, '(TP|TM|RN|TG|GG|RR|[0-9]+)$', '') ASC,

    CASE
        WHEN a.art_codigo ~ 'TP$' THEN 1
        WHEN a.art_codigo ~ 'TM$' THEN 2
        WHEN a.art_codigo ~ 'RN$' THEN 3
        WHEN a.art_codigo ~ 'TG$' THEN 4
        WHEN a.art_codigo ~ 'GG$' THEN 5
        WHEN a.art_codigo ~ 'RR$' THEN 6
        ELSE 99
    END ASC,

    COALESCE(
        NULLIF(regexp_replace(a.art_codigo, '\\D', '', 'g'), ''),
        '0'
    )::BIGINT ASC
")
            ->get();

        return view('pedido_compras.imprimir', compact('pedido', 'detalle'));
    }

    public function export($id)
    {
        $pedido = DB::table('pedido_compras')
            ->where('id_pedido', $id)
            ->first();

        $nombreArchivo = 'export_pedido_' . $pedido->nro_pedido . '.xlsx';

        return Excel::download(
            new PedidoExport($id),
            $nombreArchivo
        );
    }

    public function edit($id)
    {
        Log::info("EDIT PEDIDO INICIO", [
            'pedido_id' => $id
        ]);

        $pedido = DB::table('pedido_compras')
            ->where('id_pedido', $id)
            ->first();

        if (!$pedido) {

            Log::warning("PEDIDO NO ENCONTRADO EN EDIT", [
                'pedido_id' => $id
            ]);

            alert()->error('Error', 'Pedido no encontrado');

            return redirect()->route('pedido_compras.index');
        }

        Log::info("PEDIDO ENCONTRADO", [
            'pedido' => $pedido
        ]);

        $detalles = DB::table('detalle_pedido')
            ->join('articulos', 'articulos.id_articulo', '=', 'detalle_pedido.id_articulo')
            ->where('detalle_pedido.id_pedido_compras', $id)
            ->select(
                'detalle_pedido.id_det_pedido',
                'detalle_pedido.id_articulo',
                'detalle_pedido.det_cantidad',
                'detalle_pedido.det_subtotal',
                'detalle_pedido.det_descuento',
                'articulos.art_codigo',
                'articulos.art_descripcion',
                'articulos.prec_vent as det_precio'
            )
            ->get();

        Log::info("DETALLES CARGADOS EN EDIT", [
            'pedido_id' => $id,
            'total_detalles' => count($detalles),
            'detalles' => $detalles
        ]);

        $condicion = ["CONTADO" => "CONTADO", "CREDITO" => "CREDITO"];

        $clientes = DB::table('clientes')
            ->select(
                'id_cliente',
                DB::raw("cli_ci || ' - ' || cli_nombre || ' ' || cli_apellido AS nombre")
            )
            ->pluck('nombre', 'id_cliente');

        $sucursal = DB::table('sucursal')
            ->pluck('suc_descri', 'cod_suc');

        Log::info("CATALOGOS CARGADOS EN EDIT", [
            'clientes' => count($clientes),
            'sucursales' => count($sucursal)
        ]);

        return view('pedido_compras.edit', [
            'pedido_compras' => $pedido,
            'detalle' => $detalles,
            'condicion' => $condicion,
            'clientes' => $clientes,
            'sucursal' => $sucursal
        ]);
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            $input = $request->all();

            Log::info("UPDATE PEDIDO INICIO", [
                'pedido_id' => $id,
                'request' => $input
            ]);

            $fecha  = Carbon::parse($input['ped_fecha'])->format('Y-m-d');
            $actual = Carbon::now()->format('Y-m-d');

            if ($fecha > $actual) {
                Log::warning("Fecha inválida", ['fecha' => $fecha, 'actual' => $actual]);

                alert()->info('Atención!!!', 'La fecha del pedido no puede ser mayor a la fecha actual.');
                return redirect(route('pedido_compras.edit', ['id' => $id]))->withInput();
            }

            // =====================
            // 1. ACTUALIZAR CABECERA
            // =====================
            DB::table('pedido_compras')
                ->where('id_pedido', $id)
                ->update([
                    'id_cliente'  => $input['id_cliente'],
                    'condicion'   => $input['condicion'],
                    'intervalo'   => $input['intervalo'] ?? null,
                    'cant_cuotas' => $input['cant_cuotas'] ?? null,
                    'ped_fecha'   => $input['ped_fecha'],
                    'obs'         => $input['obs'] ?? null,
                ]);

            // =====================
            // 2. BORRAR DETALLES
            // =====================
            DB::table('detalle_pedido')
                ->where('id_pedido_compras', $id)
                ->delete();

            // =====================
            // 3. VALIDAR DETALLE (🔥 NUEVO)
            // =====================
            if (!isset($input['codigo']) || !is_array($input['codigo']) || count($input['codigo']) === 0) {

                Log::warning("UPDATE PEDIDO SIN DETALLE", [
                    'pedido_id' => $id,
                    'input' => $input
                ]);

                alert()->error('Error', 'No se puede guardar sin productos en el detalle');

                DB::rollBack();
                return redirect()->back()->withInput();
            }

            // =====================
            // 4. REINSERTAR DETALLES
            // =====================
            $aplicaDescuento = $input['aplica_descuento'] ?? 'NO';
            $descuentoGeneral = ($aplicaDescuento === 'SI') ? floatval($input['descuento'] ?? 0) : 0;

            $totalSinDescuento = 0;

            foreach ($input['codigo'] ?? [] as $key => $value) {

                $articulo = DB::table('articulos')
                    ->where('art_codigo', $value)
                    ->first();

                if (!$articulo) {

                    Log::warning("ARTICULO NO ENCONTRADO", [
                        'codigo' => $value
                    ]);

                    throw new \Exception("Artículo no encontrado: $value");
                }

                $cantidad = $input['cantidad'][$key] ?? 0;
                $precio   = $articulo->prec_vent;

                $subtotal = $cantidad * $precio;
                $subtotalConDesc = $subtotal * (1 - $descuentoGeneral / 100);

                $totalSinDescuento += $subtotal;

                DB::table('detalle_pedido')->insert([
                    'id_articulo'        => $articulo->id_articulo,
                    'id_pedido_compras'  => $id,
                    'det_cantidad'       => $cantidad,
                    'det_subtotal'       => $subtotalConDesc,
                    'det_descuento'      => $descuentoGeneral
                ]);
            }

            // =====================
            // 5. ACTUALIZAR TOTAL
            // =====================
            DB::table('pedido_compras')
                ->where('id_pedido', $id)
                ->update([
                    'ped_total' => $totalSinDescuento * (1 - $descuentoGeneral / 100),
                    'descuento' => $descuentoGeneral
                ]);

            DB::commit();

            alert()->success('Éxito', 'Pedido actualizado correctamente');

            return redirect()->route('pedido_compras.index');
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error("ERROR UPDATE PEDIDO", [
                'pedido_id' => $id,
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Alert()->error('Error', 'No se pudo actualizar el pedido');

            return redirect()->back()->withInput();
        }
    }
}
