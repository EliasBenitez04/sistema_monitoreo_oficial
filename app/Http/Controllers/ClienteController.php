<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClienteController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:clientes index')->only('index');
        $this->middleware('permission:clientes create')->only('create', 'store');
        $this->middleware('permission:clientes edit')->only('edit', 'update');
        $this->middleware('permission:clientes destroy')->only('destroy');
    }
    public function index()
    {
        $clientes = DB::table('clientes')
            ->select(
                'clientes.*',
                'departamento.dep_descripcion',
                'ciudad.ciu_descripcion'
            )
            ->leftJoin('departamento', 'clientes.id_departamento', '=', 'departamento.id_departamento')
            ->leftJoin('ciudad', 'clientes.id_ciudad', '=', 'ciudad.id_ciudad')
            ->paginate(10);

        return view('clientes.index')->with('clientes', $clientes);
    }

    public function create(Request $request)
    {
        // Recuperar los departamentos para nuestro select en la vista create
        $departamento = DB::table('departamento')->pluck('dep_descripcion', 'id_departamento');

        // Recuperar todas las ciudades para nuestro select
        $ciudad = DB::table('ciudad')->pluck('ciu_descripcion', 'id_ciudad');

        // Detectar si se accede desde el formulario de ventas
        $from = $request->get('from', null);

        // Retornar la vista con los datos necesarios
        return view('clientes.create', compact('ciudad', 'departamento', 'from'));
    }

    public function getCiudades()
    {
        return DB::table('ciudad')
            ->select(
                'id_ciudad',
                'ciu_descripcion'
            )
            ->orderBy('ciu_descripcion')
            ->get();
    }

    public function store(Request $request)
    {
        $input = $request->all();

        /*
    |--------------------------------------------------------------------------
    | CAMPOS OBLIGATORIOS
    |--------------------------------------------------------------------------
    */

        $camposObligatorios = [
            'cli_ci' => 'Nro de CI',
            'cli_nombre' => 'Nombres',
            'cli_direccion' => 'Dirección',
            'cli_telefono' => 'Teléfono',
            'id_departamento' => 'Departamento',
            'id_ciudad' => 'Ciudad'
        ];


        /*
    |--------------------------------------------------------------------------
    | VALIDAR CAMPOS OBLIGATORIOS
    |--------------------------------------------------------------------------
    */

        foreach ($camposObligatorios as $campo => $nombre) {

            if (
                !isset($input[$campo]) ||
                trim($input[$campo]) === ''
            ) {

                /*
            |--------------------------------------------------------------------------
            | SI VIENE DESDE EL MODAL / AJAX
            |--------------------------------------------------------------------------
            */

                if ($request->expectsJson()) {

                    return response()->json([
                        'success' => false,
                        'message' => "Debe completar el campo: $nombre"
                    ], 422);
                }


                /*
            |--------------------------------------------------------------------------
            | SI VIENE DESDE CLIENTES NORMAL
            |--------------------------------------------------------------------------
            */

                alert()->info(
                    'Atención',
                    "Debe completar el campo: $nombre"
                );

                return redirect()
                    ->back()
                    ->withInput();
            }
        }


        /*
    |--------------------------------------------------------------------------
    | VALIDAR CÉDULA / RUC DUPLICADO
    |--------------------------------------------------------------------------
    */

        $validarCi = DB::table('clientes')
            ->where('cli_ci', trim($input['cli_ci']))
            ->first();


        if (!empty($validarCi)) {

            /*
        |--------------------------------------------------------------------------
        | SI VIENE DESDE EL MODAL
        |--------------------------------------------------------------------------
        */

            if ($request->expectsJson()) {

                return response()->json([
                    'success' => false,
                    'message' => 'La Cédula Del Cliente Ya Existe!!!'
                ], 422);
            }


            /*
        |--------------------------------------------------------------------------
        | SI VIENE DESDE CLIENTES NORMAL
        |--------------------------------------------------------------------------
        */

            alert()->info(
                'Atención',
                'La Cédula Del Cliente Ya Existe!!!'
            );

            return redirect()
                ->back()
                ->withInput();
        }


        /*
    |--------------------------------------------------------------------------
    | GUARDAR CLIENTE
    |--------------------------------------------------------------------------
    */

        $idCliente = DB::table('clientes')->insertGetId([

            'id_ciudad' => $input['id_ciudad'],

            'id_departamento' => $input['id_departamento'],

            'cli_ci' => trim($input['cli_ci']),

            'cli_nombre' => strtoupper(
                trim($input['cli_nombre'])
            ),

            'cli_apellido' => strtoupper(
                trim($input['cli_apellido'] ?? '')
            ),

            'cli_direccion' => strtoupper(
                trim($input['cli_direccion'])
            ),

            'cli_telefono' => trim($input['cli_telefono'])

        ], 'id_cliente');


        /*
    |--------------------------------------------------------------------------
    | OBTENER CLIENTE RECIÉN CREADO
    |--------------------------------------------------------------------------
    */

        $cliente = DB::table('clientes')
            ->where('id_cliente', $idCliente)
            ->first();


        /*
    |--------------------------------------------------------------------------
    | SI VIENE DESDE EL MODAL / AJAX
    |--------------------------------------------------------------------------
    |
    | IMPORTANTE:
    | NO HACEMOS REDIRECT.
    | Devolvemos JSON al JavaScript del modal.
    |
    */

        if ($request->expectsJson()) {

            return response()->json([

                'success' => true,

                'message' => 'Cliente registrado correctamente.',

                'cliente' => $cliente

            ]);
        }


        /*
    |--------------------------------------------------------------------------
    | SI VIENE DESDE EL FORMULARIO NORMAL DE CLIENTES
    |--------------------------------------------------------------------------
    */

        alert()->success(
            'Éxito',
            'Registro Guardado Correctamente!'
        );


        return redirect()
            ->route('clientes.index');
    }

    public function edit($cliente_id)
    {
        ##verificar si existe el clienteif ($request->ajax()) {
        $clientes = DB::table('clientes')->where('id_cliente', $cliente_id)->first();

        ##validad
        if (empty($clientes)) {
            alert()->error('Atención', 'El dato consultado no Existe!');
            return redirect(route('clientes.index'));
        }

        ##recuperar los departamentos para nuestro select en la vista create
        $departamento = DB::table('departamento')->pluck('dep_descripcion', 'id_departamento');

        ##recuperar todoas las ciudades para nuestro select
        $ciudad = DB::table('ciudad')->pluck('ciu_descripcion', 'id_ciudad');

        return view('clientes.edit')
            ->with('cliente', $clientes)
            ->with('ciudad', $ciudad)
            ->with('departamento', $departamento);
    }

    public function update($cliente_id, Request $request)
    {
        $cliente = \App\Models\Cliente::find($cliente_id);

        if (!$cliente) {
            alert()->info('Atención', 'El Cliente no Existe!!!');
            return redirect()->route('clientes.index');
        }

        $input = $request->all();

        // Validar que la CI no exista en otro registro
        $validarCi = DB::table('clientes')
            ->where('cli_ci', $input['cli_ci'])
            ->where('id_cliente', '<>', $cliente_id)
            ->first();

        if (!empty($validarCi)) {
            alert()->info('Atención', 'La Cédula / R.U.C. Del Cliente Ya Existe!!!');
            return redirect()->back()->withInput();
        }

        // Actualizar los datos
        $cliente->update([
            'id_ciudad' => $input['id_ciudad'],
            'id_departamento' => $input['id_departamento'],
            'cli_ci' => $input['cli_ci'],
            'cli_nombre' => strtoupper($input['cli_nombre']),
            'cli_apellido' => strtoupper($input['cli_apellido']),
            'cli_direccion' => strtoupper($input['cli_direccion']),
            'cli_telefono' => $input['cli_telefono']
        ]);

        alert()->success('Éxito', 'Registro Actualizado correctamente.!');

        return redirect()->route('clientes.index');
    }

    public function destroy($cliente_id)
    {
        $clientes = DB::table('clientes')->where('id_cliente', $cliente_id)->first();

        if (empty($clientes)) {
            alert()->error('Atención', 'El registro No existe!!!');

            return redirect(route('clientes.index'));
        }

        DB::delete('DELETE FROM clientes WHERE id_cliente = ?', [$cliente_id]);

        alert()->success('Exito', 'Cliente Borrado Con Exito!!!');

        return redirect(route('clientes.index'));
    }
}
