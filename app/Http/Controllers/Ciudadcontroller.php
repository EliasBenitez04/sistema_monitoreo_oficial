<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;

class Ciudadcontroller extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ciudades index')->only('index');
        $this->middleware('permission:ciudades create')->only('create', 'store');
        $this->middleware('permission:ciudades edit')->only('edit', 'update');
        $this->middleware('permission:ciudades destroy')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = DB::table('ciudad')->orderBy('ciu_descripcion', 'asc');

        $ciudades = $query->paginate(10);

        $ciudades->appends($request->all());

        return view('ciudads.index')->with('ciudad', $ciudades);
    }

    public function create()
    {
        return view('ciudads.create');
    }

    public function store(Request $request)
    {
        $input = $request->all();
        $descripcion = strtoupper(trim($input['ciu_descripcion']));

        $ciudadExistente = DB::table('ciudad')
            ->whereRaw('TRIM(UPPER(ciu_descripcion)) = ?', [$descripcion])
            ->first();

        // DEBUG: para ver si lo encuentra
        // dd($ciudadExistente);

        if ($ciudadExistente) {
            Log::info('Intento de crear ciudad duplicada', [
                'usuario_id' => auth()->id(),
                'ciu_descripcion' => $descripcion
            ]);

            // Flash manual por si Alert no funciona
            session()->flash('alert-type', 'info');
            session()->flash('alert-message', 'La ciudad ya existe!');

            Alert::info('Atención', 'La ciudad ya existe!');
            return redirect()->route('ciudades.create');
        }

        DB::table('ciudad')->insert([
            'ciu_descripcion' => $descripcion
        ]);

        Log::info('Ciudad creada correctamente', [
            'usuario_id' => auth()->id(),
            'ciu_descripcion' => $descripcion
        ]);

        Alert::success('Éxito', 'Ciudad creada correctamente!');
        return redirect()->route('ciudades.index');
    }

    public function edit($id)
    {
        $ciudad = DB::table('ciudad')->where('id_ciudad', $id)->first();

        if (!$ciudad) {
            Alert::error('Error', 'La ciudad no existe!');
            return redirect()->route('ciudades.index');
        }

        return view('ciudads.edit')->with('ciudades', $ciudad);
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();

        $ciudad = DB::table('ciudad')->where('id_ciudad', $id)->first();
        if (!$ciudad) {
            Alert::error('Error', 'La ciudad no existe!');
            return redirect()->route('ciudades.index');
        }

        $ciudadExistente = DB::table('ciudad')
            ->where('ciu_descripcion', '=', strtoupper($input['ciu_descripcion']))
            ->where('id_ciudad', '!=', $id)
            ->first();

        if ($ciudadExistente) {
            Alert::info('Atención', 'La ciudad ya existe!');
            return redirect()->route('ciudades.edit', $id);
        }

        DB::table('ciudad')->where('id_ciudad', $id)->update([
            'ciu_descripcion' => strtoupper($input['ciu_descripcion'])
        ]);

        Alert::success('Éxito', 'Ciudad actualizada correctamente!');
        return redirect()->route('ciudades.index');
    }

    public function destroy($id)
    {
        $ciudad = DB::table('ciudad')->where('id_ciudad', $id)->first();

        if (!$ciudad) {
            Alert::error('Error', 'La ciudad no existe!');
            return redirect()->route('ciudades.index');
        }

        DB::table('ciudad')->where('id_ciudad', $id)->delete();

        Alert::success('Éxito', "Ciudad '{$ciudad->ciu_descripcion}' eliminada correctamente!");
        return redirect()->route('ciudades.index');
    }
}
