<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

class DepartamentoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:departamentos index')->only('index');
        $this->middleware('permission:departamentos create')->only('create', 'store');
        $this->middleware('permission:departamentos edit')->only('edit', 'update');
        $this->middleware('permission:departamentos destroy')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = Departamento::query()->orderBy('dep_descripcion', 'asc');

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where('dep_descripcion', 'LIKE', "%{$searchTerm}%");
        }

        $departamento = $query->paginate(10);

        $departamento->appends($request->all());

        return view('departamentos.index', compact('departamento'));
    }


    public function create()
    {
        return view('departamentos.create');
    }

    public function store(Request $request)
    {
        $input = $request->all();

        $Departamento = DB::table('departamento')
            ->where('dep_descripcion', '=', strtoupper($input['dep_descripcion']))
            ->first();

        if ($Departamento) {
            alert()->info('Atención', 'El departamento ya existe!');
            return redirect()->route('Departamentos.create')->withInput();
        }

        DB::table('departamento')->insert([
            'dep_descripcion' => strtoupper($input['dep_descripcion'])
        ]);

        alert()->success('Éxito', 'Departamento creado correctamente.');
        return redirect()->route('Departamentos.index');
    }

    public function edit($id)
    {
        $Departamento = DB::table('departamento')->where('id_departamento', $id)->first();

        if (!$Departamento) {
            alert()->error('Error', 'El departamento no existe.');
            return redirect()->route('Departamentos.index');
        }

        return view('departamentos.edit')->with('departamento', $Departamento);
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();

        $departamento = DB::table('departamento')->where('id_departamento', $id)->first();

        if (!$departamento) {
            alert()->error('Error', 'Registro no encontrado.');
            return redirect()->route('Departamentos.index');
        }

        $validarDepartamento = DB::table('departamento')
            ->where('dep_descripcion', '=', strtoupper($input['dep_descripcion']))
            ->where('id_departamento', '!=', $id)
            ->first();

        if ($validarDepartamento) {
            alert()->info('Atención', 'El departamento ya existe!');
            return redirect()->back()->withInput();
        }

        DB::table('departamento')->where('id_departamento', $id)
            ->update([
                'dep_descripcion' => strtoupper($input['dep_descripcion'])
            ]);

        alert()->success('Éxito', 'Departamento actualizado correctamente.');
        return redirect()->route('Departamentos.index');
    }

    public function destroy($id)
    {
        $Departamento = DB::table('departamento')->where('id_departamento', $id)->first();

        if (!$Departamento) {
            alert()->error('Error', 'El departamento no existe.');
            return redirect()->route('Departamentos.index');
        }

        DB::table('departamento')->where('id_departamento', $id)->delete();

        alert()->success('Éxito', 'Departamento eliminado correctamente.');
        return redirect()->route('Departamentos.index');
    }
}
