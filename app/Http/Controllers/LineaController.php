<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

class LineaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:lineas index')->only('index');
        $this->middleware('permission:lineas create')->only('create', 'store');
        $this->middleware('permission:lineas edit')->only('edit', 'update');
        $this->middleware('permission:lineas destroy')->only('destroy');
    }

    public function index(Request $request)
    {
        $lineas = DB::table('linea')
            ->orderBy('linea_desc', 'asc')
            ->paginate(10);

        return view('lineas.index')->with('linea', $lineas);
    }

    public function create()
    {
        return view('lineas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'linea_desc' => 'required|string|max:255'
        ]);

        $lineaDesc = strtoupper(trim($request->linea_desc));

        $existe = DB::table('linea')
            ->where('linea_desc', $lineaDesc)
            ->exists();

        if ($existe) {
            Alert::info('Atención', 'La línea ya existe!');
            return redirect()->back()->withInput();
        }

        DB::table('linea')->insert([
            'linea_desc' => $lineaDesc
        ]);

        Alert::success('Éxito', 'Línea creada correctamente!');
        return redirect()->route('lineas.index');
    }

    public function edit($id)
    {
        $linea = DB::table('linea')
            ->where('linea_cod', $id)
            ->first();

        if (!$linea) {
            Alert::error('Error', 'La línea no existe!');
            return redirect()->route('lineas.index');
        }

        return view('lineas.edit', compact('linea'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'linea_desc' => 'required|string|max:255'
        ]);

        $linea = DB::table('linea')
            ->where('linea_cod', $id)
            ->first();

        if (!$linea) {
            Alert::error('Error', 'La línea no existe!');
            return redirect()->route('lineas.index');
        }

        $lineaDesc = strtoupper(trim($request->linea_desc));

        $existe = DB::table('linea')
            ->where('linea_desc', $lineaDesc)
            ->where('linea_cod', '!=', $id)
            ->exists();

        if ($existe) {
            Alert::info('Atención', 'La línea ya existe!');
            return redirect()->back()->withInput();
        }

        DB::table('linea')
            ->where('linea_cod', $id)
            ->update([
                'linea_desc' => $lineaDesc
            ]);

        Alert::success('Éxito', 'Línea actualizada correctamente!');
        return redirect()->route('lineas.index');
    }

    public function destroy($id)
    {
        $linea = DB::table('linea')
            ->where('linea_cod', $id)
            ->first();

        if (!$linea) {
            Alert::error('Error', 'La línea no existe!');
            return redirect()->route('lineas.index');
        }

        DB::table('linea')
            ->where('linea_cod', $id)
            ->delete();

        Alert::success('Éxito', "Línea '{$linea->linea_desc}' eliminada correctamente!");
        return redirect()->route('lineas.index');
    }
}
