<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


//este es el que no funciona

class UserController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('permission:users index')->only('index');
    //     $this->middleware('permission:users create')->only('create', 'store');
    //     $this->middleware('permission:users edit')->only('edit', 'update');
    //     $this->middleware('permission:users destroy')->only('destroy');
    //     $this->middleware('auth');
    // }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }

    public function index(Request $request)
    {
        $buscar = $request->get('buscar', null);

        $users = DB::table('users')
            ->select(
                'users.*',
                'roles.name as rol'
            )
            ->leftJoin('roles', 'roles.id', 'users.role_id')
            ->whereNull('deleted_at');

        if (!empty($buscar)) { //si la varibale buscar no esta vacio procedo con la consulta utilizando like
            $users = $users->whereRaw("(users.email iLIKE '%{$buscar}%'
                or users.nro_documento iLIKE '%{$buscar}%'
                or users.name iLIKE '%{$buscar}%'
                or roles.name iLIKE '%{$buscar}%')");
        }

        $users = $users->paginate(10);

        ##alerta para borrar confirmDelete
        confirmDelete("Atención", "Desea borrar el usuario?");

        ##si la accion es buscardor entonces significa que se debe recargar mediante ajax la tabla
        if ($request->ajax()) {
            return view('users.table', compact('users')); //solo llmamamos a table.blade.php y mediante compact pasamos la variable users
        }

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $role = Role::all()->pluck('name', 'id');

        return view('users.create')->with('roles', $role);
    }

    public function store(Request $request)
    {
        $input = $request->all();

        if (!empty($input['nro_documento'])) {
            $existeDoc = DB::table('users')->where('nro_documento', $input['nro_documento'])->exists();

            if ($existeDoc) {
                alert()->error('Error', 'Número de documento ya existe.!');

                return redirect()->back()->withInput($input);
            }
        }

        ##insertar DAtos utilizando ORM
        $user = new User;
        $user->name = $input['name'];
        $user->email = $input['email'];
        $user->password = Hash::make($input['password']); ##encriptar contraseña
        $user->direccion = $input['direccion'];
        $user->celular = $input['celular'];
        $user->nro_documento = $input['nro_documento'];
        $user->role_id = $input['role_id'];
        $user->save();

        $roles = $request->get('role_id'); ##capturamos el dato de role_id

        ##guardamos los permisos para el usuario creado, y se asgina los permisos en la tabla model_has_roles
        $user->roles()->sync([$roles]);

        alert()->success("Atención", "Usuario creado correctamente");

        return redirect(route('users.index'));
    }

    public function edit($id, Request $request)
    {
        $user = DB::table('users')->where('id', $id)->first();

        if (empty($user)) {
            alert()->error('Atención', 'Registro no encontrado.!');

            redirect(route('users.index'));
        }

        $role = Role::all()->pluck('name', 'id');

        return view('users.edit')->with('roles', $role)->with('user', $user);
    }

    public function update($id, Request $request)
    {
        $user = User::where('id', $id)->first();

        if (empty($user)) {
            alert()->error('Atención', 'Registro no encontrado.!');

            redirect(route('users.index'));
        }

        ##recuperar datos
        $input = $request->all();

        ##actualizar datos de usuarios utilizando ORM
        $user->name = $input['name'];
        $user->email = $input['email'];
        if (!empty($input['password'])) { ##solo actualizo la contraseña si es distinto a vacio
            $user->password = Hash::make($input['password']);
        }
        $user->direccion = $input['direccion'];
        $user->celular = $input['celular'];
        $user->nro_documento = $input['nro_documento'];
        $user->role_id = $input['role_id'];
        $user->save();

        $roles = $request->get('role_id'); ##capturamos el dato de role_id

        ##guardamos los permisos para el usuario creado, y se asgina los permisos en la tabla model_has_roles
        $user->roles()->sync([$roles]);

        alert()->success("Atención", "Usuario actualizado correctamente");

        return redirect(route('users.index'));
    }


    public function destroy($id)
    {
        $user = User::where('id', $id)->first(); ###consultar si existe datos

        if (empty($user)) {
            alert()->error('Atención', 'Registro no encontrado.!');

            redirect(route('users.index'));
        }

        ##borrar usuario de manera logica
        $user->delete();

        alert()->success("Exíto", "Usuario borrado correctamente");

        return redirect(route('users.index'));
    }

    public function perfil()
    {
        return view('users.profile');
    }

    public function cambiarPassword(Request $request)
    {
        $input = $request->all();

        ##validar datos de contraseña utilizando validate de laravel
        $validator = Validator::make(
            $request->all(),
            [
                'password' => 'required|min:6',
                'confirm-password' => 'required|same:password',
            ],
            [
                'password.required'         => 'La contraseña es requerida',
                'password.min'              => 'Debe tener al menos 6 digítos la contraseña',
                'confirm-password.required' => 'La confirmación de contraseña es requerida',
                'confirm-password.same'     => 'Las contraseñas no coinciden',
            ]
        );

        if ($validator->fails()) {

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        #Actualizar la contraseña del usuario en session
        DB::table('users')->where('id', auth()->user()->id)
            ->update(['password' =>  Hash::make($input['password'])]);

        alert()->success('Exíto', 'Contraseña actualizada correctamente.!');

        return redirect()->back();
    }
}
