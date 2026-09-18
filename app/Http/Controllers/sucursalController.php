<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class sucursalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:sucursal index')->only('index');
        $this->middleware('permission:sucursal create')->only('create', 'store');
        $this->middleware('permission:sucursal edit')->only('edit', 'update');
        $this->middleware('permission:sucursal destroy')->only('destroy');
    }
    public function index()
    {
        $sucursal  = DB::table('sucursal')->paginate(10);

        confirmDelete("Atención", "Desea borrar el registro?");

        return view('sucursals.index')->with('sucursal', $sucursal);
    }
    public function create()
    {
        return view('sucursals.create');
    }

    public function store(Request $request)
    {
        $input = $request->all();

        ##validar que no exista el mismo nro de ci guardado..
        $validarSuc = DB::table('sucursal')->where('suc_descri', $input['suc_descri'])->first();

        ##verificar la variable
        if (!empty($validarSuc)) {

            alert()->error('Atención', 'La Sucursal Ya Existe!');

            return redirect()->back()->withInput();
        }

        ##grabar los datos
        DB::insert(
            "INSERT INTO sucursal(suc_descri, suc_direccion, suc_telefono)
                VALUES(?, ?, ?)",
            [
                strtoupper($input['suc_descri']),
                strtoupper($input['suc_direccion']),
                $input['suc_telefono']
            ]
        );

        alert()->success('Éxito', 'Registro guardado correctamente.!');

        return redirect(route('sucursal.index'));
    }
    public function edit($id_suc)
    {
        $sucursales = DB::table('sucursal')->where('cod_suc', $id_suc)->first();

        if (empty($sucursales)) {

            alert()->error('Error', 'Registro no encontrado!');

            return redirect(route('sucursal.index'));
        }
        return view('sucursals.edit')->with('sucursal', $sucursales);
    }

    public function update(Request $request, $cod_suc)
    {
        $input = $request->all();

        $sucursales = DB::table('sucursal')->where('cod_suc', $cod_suc)->first();

        if (empty($sucursales)) {

            alert()->error('Error', 'Registro no encontrado.!');

            return redirect(route('sucursals.index'));
        }

        ##validar que no exista el mismo nro de ci guardado..
        $validarSuc = DB::table('sucursal')->where('suc_descri', $input['suc_descri'])->first();

        ##verificar la variable
        if (!empty($validarSuc)) {

            alert()->error('Atención', 'La Sucursal Ya Existe!');

            return redirect()->back()->withInput();
        }

        DB::update(
            'UPDATE sucursal SET
            suc_descri = ?,
            suc_direccion = ?,
            suc_telefono = ?
        WHERE cod_suc = ?',
            [
                strtoupper($input['suc_descri']),
                strtoupper($input['suc_direccion']),
                $input['suc_telefono'],
                $cod_suc
            ]
        );
        alert()->success('Exíto', 'Registro actualizado correctamente.!');

        return redirect(route('sucursal.index'));
    }

    public function destroy($id_suc)
    {
        $sucursales = DB::table('sucursal')->where('cod_suc', $id_suc)->first();

        if (empty($sucursales)) {
            alert()->error('Error', 'Registro no encontrado.!');
            return redirect(route('sucursal.index'));
        }
        DB::table('sucursal')->where('cod_suc', $id_suc)->delete();

        alert()->success('Exíto', 'Registro Borrado correctamente.!');

        return redirect(route('sucursal.index'));
    }
}
