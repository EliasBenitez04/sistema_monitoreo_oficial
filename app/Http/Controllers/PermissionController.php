<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:permissions index')->only('index');
        $this->middleware('permission:permissions create')->only('create', 'store');
        $this->middleware('permission:permissions edit')->only('edit', 'update');
        $this->middleware('permission:permissions destroy')->only('destroy');
        $this->middleware('auth');
    }

    public function index()
    {
        $permissions = DB::table('permissions')->paginate(10);

        return view('permissions.index')->with('permissions', $permissions);
    }

    public function create()
    {
        return view('permissions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $name = trim($request->name);
        $guardName = 'web';

        $permission = DB::table('permissions')
            ->where('name', $name)
            ->where('guard_name', $guardName)
            ->first();

        if (!$permission) {
            DB::table('permissions')->insert([
                'name' => $name,
                'guard_name' => $guardName
            ]);

            alert()->success("Éxito", "Permiso creado y asignado al Administrador.");
        } else {
            alert()->info("Información", "El permiso ya existe.");
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $roleAdmin = Role::find(4);

        if ($roleAdmin) {
            $roleAdmin->givePermissionTo($name);
        }

        return redirect()->route('permissions.index');
    }

    public function edit($id)
    {
        $permissions = DB::table('permissions')->where('id', $id)->first();

        if (empty($permissions)) {
            alert()->error("Atención", "Registro no encontrado.!");

            return redirect(route('permissions.index'));
        }

        return view('permissions.edit')->with('permissions', $permissions);
    }

    public function update($id, Request $request)
    {
        $permissions = DB::table('permissions')->where('id', $id)->first();
        $input = $request->all();

        if (empty($permissions)) {
            alert()->error("Atención", "Registro no encontrado.!");
            return redirect(route('permissions.index'));
        }

        DB::update(
            "UPDATE permissions SET name = ? Where id = ?",
            [
                $input['name'],
                $permissions->id
            ]
        );

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        alert()->success("Éxito", "Permiso actualizado correctamente.");

        return redirect(route('permissions.index'));
    }

    public function destroy($id)
    {
        $permissions = DB::table('permissions')->where('id', $id)->first();

        if (empty($permissions)) {
            alert()->error('Atención', 'El registro no existe!!!');

            return redirect(route('permissions.index'));
        }

        DB::table('permissions')->where('id', $id)->delete();

        alert()->success('Éxito', '¡Borrado con éxito!');

        return redirect(route('permissions.index'));
    }
}
