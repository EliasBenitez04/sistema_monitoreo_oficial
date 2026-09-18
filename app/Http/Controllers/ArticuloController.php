<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache as FacadesCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Attribute\Cache;

class ArticuloController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:articulos index')->only('index');
        $this->middleware('permission:articulos create')->only('create', 'store');
        $this->middleware('permission:articulos edit')->only('edit', 'update');
        $this->middleware('permission:articulos destroy')->only('destroy');
    }

    public function showImportForm()
    {
        return view('articulos.importar');
    }

    public function import(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,csv,xls',
        ]);

        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $file = $request->file('archivo');

        // 🔄 reiniciar progreso
        \Illuminate\Support\Facades\Cache::put('import_progress', 0, 600);
        \Illuminate\Support\Facades\Cache::put('import_progress_count', 0, 600);

        try {

            // 📊 contar filas
            $collection = \Maatwebsite\Excel\Facades\Excel::toCollection(
                new \App\Imports\ArticulosImport,
                $file
            );

            $totalRows = $collection->sum(fn($sheet) => $sheet->count());

            \Illuminate\Support\Facades\Cache::put('import_total', $totalRows, 600);

            // 🚀 importar
            \Maatwebsite\Excel\Facades\Excel::import(
                new \App\Imports\ArticulosImport,
                $file
            );

            // ✅ marcar final 100%
            \Illuminate\Support\Facades\Cache::put('import_progress', 100, 600);

            return response()->json([
                'success' => true,
                'message' => 'Artículos importados correctamente!'
            ]);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {

            $failures = $e->failures();
            $msg = '';

            foreach ($failures as $failure) {
                $msg .= 'Fila ' . $failure->row() . ': ' . implode(', ', $failure->errors()) . '<br>';
            }

            // 🔥 ALERTA ERROR
            alert()->error('Error de Validación', strip_tags($msg));

            return response()->json([
                'success' => false,
                'message' => $msg
            ], 422);
        } catch (\Exception $e) {

            // 🔥 ALERTA ERROR GENERAL
            alert()->error('Error', $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        // Consulta base
        $query = DB::table('articulos')
            ->select('articulos.*');

        // BUSCADOR PRODUCTO
        if ($request->has('buscar') && !empty($request->buscar)) {
            $buscar = $request->buscar;

            $query->where(function ($q) use ($buscar) {
                $q->where('articulos.art_codigo', 'like', "%{$buscar}%")
                    ->orWhere('articulos.art_descripcion', 'like', "%{$buscar}%");
            });
        }

        // ORDENAR PRECIO
        if ($request->has('ordenar') && $request->ordenar == 'asc') {
            $query->orderBy('articulos.prec_vent', 'asc');
        } elseif ($request->has('ordenar') && $request->ordenar == 'desc') {
            $query->orderBy('articulos.prec_vent', 'desc');
        } else {
            $query->orderBy('articulos.id_articulo');
        }

        // PAGINAR
        $articulos = $query->paginate(15)->appends($request->all());

        return view('articulos.index')
            ->with('articulos', $articulos);
    }

    public function create()
    {
        ## Armar array de ivas
        $iva = ['0' => 'Exenta', '5' => 'Gravada 5%', '10' => 'Gravada 10%'];

        return view('articulos.create')
            ->with('iva', $iva);
    }

    public function store(Request $request)
    {
        $input = $request->all();

        // VALIDACIÓN
        $validator = Validator::make(
            $input,
            [
                'art_codigo' => 'required',
                'art_descripcion' => 'required',
                'art_precio' => 'required',
                'art_iva' => 'required|numeric',
                'prec_vent' => 'required|numeric'
            ]
        );

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            alert()->error('Error', 'Datos inválidos');
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();

        try {

            $codBase = strtoupper($input['art_codigo']);
            $descripcion = strtoupper($input['art_descripcion']);

            $talles = $request->talles;

            // =========================
            // 🔥 CON TALLES
            // =========================
            if (!empty($talles)) {

                foreach ($talles as $talle) {

                    $codigo = $codBase . $talle;

                    $existe = DB::table('articulos')
                        ->where('art_codigo', $codigo)
                        ->exists();

                    if (!$existe) {

                        $idArticulo = DB::table('articulos')->insertGetId([
                            'art_codigo' => $codigo,
                            'art_descripcion' => $descripcion,
                            'art_precio' => $input['art_precio'],
                            'art_iva' => $input['art_iva'],
                            'prec_vent' => $input['prec_vent']
                        ], 'id_articulo');

                        // STOCK AUTOMÁTICO
                        DB::table('stock')->insert([
                            'id_articulo' => $idArticulo,
                            'cod_suc' => 1,
                            'cantidad' => 1
                        ]);
                    }
                }
            } else {

                // =========================
                // 🔥 SIN TALLES
                // =========================

                $existe = DB::table('articulos')
                    ->where('art_codigo', $codBase)
                    ->exists();

                if (!$existe) {

                    $idArticulo = DB::table('articulos')->insertGetId([
                        'art_codigo' => $codBase,
                        'art_descripcion' => $descripcion,
                        'art_precio' => $input['art_precio'],
                        'art_iva' => $input['art_iva'],
                        'prec_vent' => $input['prec_vent']
                    ], 'id_articulo');

                    // STOCK AUTOMÁTICO
                    DB::table('stock')->insert([
                        'id_articulo' => $idArticulo,
                        'cod_suc' => 1,
                        'cantidad' => 1
                    ]);
                }
            }

            DB::commit();

            alert()->success('Éxito', 'Artículo guardado correctamente');

            return redirect()->route('articulos.index');
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error($e->getMessage());

            alert()->error('Error', 'Error al guardar artículo');

            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $articulos = DB::table('articulos')->where('id_articulo', $id)->first();

        ##validad
        if (empty($articulos)) {

            alert()->error('Error', 'Registro No Encontrado!!!');

            return redirect(route('Articulos.index'));
        }

        $iva = ['0' => 'Exenta', '5' => 'Gravada 5%', '10' => 'Gravada 10%'];

        return view('articulos.edit')
            ->with('articulo', $articulos)
            ->with('iva', $iva);
    }

    public function update($id, Request $request)
    {
        // Buscar artículo
        $articulos = DB::table('articulos')
            ->where('id_articulo', $id)
            ->first();

        // Validar existencia
        if (empty($articulos)) {
            alert()->error('Error', 'Registro No Encontrado!');
            return redirect(route('articulos.index'));
        }

        $input = $request->all();

        $input = $request->all();

        ##validar que el archivo sea una imagen
        $validator = Validator::make(
            $input,
            [
                'art_descripcion' => 'required',
                'art_codigo' => 'required',
                'art_precio' => 'required',
                'art_iva' => 'required|numeric',
                'prec_vent' => 'required|numeric'
            ],
            [
                'art_codigo.required' => 'El código del artículo es requerido',
                'art_codigo.unique' => 'El código del artículo ya existe',
                'art_descripcion.required' => 'La descripción del articulo es requerido',
                'art_precio.required' => 'El precio del articulo es requerido',
                'art_iva.required' => 'El iva del articulo es requerido',
                'art_iva.numeric' => 'El iva del articulo debe ser un número',
                'prec_vent.required' => 'El precio de venta es requerido',
                'prec_vent.numeric' => 'El precio de venta debe ser un número'
            ]
        );

        ##imprimir el error
        if ($validator->fails()) {
            alert()->error('Error', 'Error al subir la imagen. Verifica los requisitos.');
            return back()->withErrors($validator)->withInput();
        }

        ##quitar separador de miles a precio antes de grabar
        $input['art_precio'] = str_replace(".", "", $input['art_precio']);

        // Limpiar el campo prec_vent
        $input['prec_vent'] = str_replace(['.', ','], '', $input['prec_vent']);  // Limpiar el formato del precio de venta

        DB::update(
            'UPDATE articulos SET
        art_codigo = ?,
        art_descripcion = ?,
        art_precio = ?,
        art_iva = ?,
        prec_vent = ?
        WHERE id_articulo = ?',
            [
                strtoupper($input['art_codigo']),
                strtoupper($input['art_descripcion']),
                $input['art_precio'],
                $input['art_iva'],
                $input['prec_vent'],
                $id
            ]
        );

        alert()->success('Exíto', 'Registro Guardado correctamente.!');

        return redirect(route('articulos.index'));
    }

    public function destroy($id)
    {
        $articulo = DB::table('articulos')->where('id_articulo', $id)->first();

        // validar existencia
        if (empty($articulo)) {
            alert()->error('Error', 'Registro No Encontrado!');
            return back();
        }

        // 🔥 VALIDAR SI ESTÁ EN USO
        $enUso = DB::table('detalle_pedido')
            ->where('id_articulo', $id)
            ->exists();

        if ($enUso) {
            alert()->error(
                'No se puede eliminar',
                'El artículo está siendo utilizado en pedidos...'
            );

            return back();
        }

        // eliminar stock primero
        DB::table('stock')->where('id_articulo', $id)->delete();

        // eliminar artículo
        DB::delete('DELETE FROM articulos WHERE id_articulo = ?', [$id]);

        alert()->success('Éxito', 'Artículo borrado correctamente.');

        return redirect(route('articulos.index'));
    }
    // Método para mostrar productos según la sucursal del usuario
    public function mostrarProductos()
    {
        // Obtén la sucursal del usuario autenticado
        $usuarioSucursal = Auth::user()->suc_cod;

        // Filtra los productos por la sucursal del usuario
        $productos = DB::table('articulos')
            ->where('sucursal_id', $usuarioSucursal)
            ->get();

        // Retorna la vista con los productos filtrados
        return view('productos.index', compact('productos'));
    }
}
