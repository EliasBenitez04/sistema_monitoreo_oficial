<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UsuarioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:usuarios index')->only('index');
        $this->middleware('permission:usuarios create')->only('create', 'store');
        $this->middleware('permission:usuarios edit')->only('edit', 'update');
        $this->middleware('permission:usuarios destroy')->only('destroy');
    }

    public function index(Request $request)
    {
        $buscar = $request->get('buscar');

        $usuario = User::with('roles')
            ->when($buscar, function ($query, $buscar) {
                $query->where('name', 'ilike', "%{$buscar}%")
                    ->orWhere('email', 'ilike', "%{$buscar}%")
                    ->orWhere('ci', 'ilike', "%{$buscar}%")
                    ->orWhereHas('roles', function ($q) use ($buscar) {
                        $q->where('name', 'ilike', "%{$buscar}%");
                    });
            })
            ->paginate(10);

        if ($request->ajax()) {
            return view('usuarios.table')->with('usuarios', $usuario);
        }

        return view('usuarios.index')->with('usuarios', $usuario);
    }

    public function create()
    {
        $estado = ["ACTIVO" => "ACTIVO", "INACTIVO" => "INACTIVO"];
        $roles = DB::table('roles')->pluck('name', 'id');
        $sucursal = DB::table('sucursal')->pluck('suc_descri', 'cod_suc');

        return view('usuarios.create')
            ->with('estado', $estado)
            ->with('sucursal', $sucursal)
            ->with('roles', $roles);
    }

    public function store(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make(
            $input,
            [
                'name' => 'required',
                'email' => 'required',
                'ci' => 'required|numeric',
                'password' => 'required',
            ],
            [
                'name.required' => 'El nombre es requerido',
                'email.required' => 'El email es requerido',
                'ci.required' => 'El número de cédula es requerido',
                'ci.numeric' => 'El número de cédula debe ser un número',
                'password.required' => 'La contraseña es requerida',
            ]
        );

        if ($validator->fails()) {
            alert()->error('Error', 'Verifique los datos ingresados');
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validarCi = User::where('ci', $input['ci'])->first();
        if ($validarCi) {
            alert()->error('Error', 'Número de Cédula de Identidad ya existe');
            return redirect(route('usuarios.create'))->withInput();
        }

        $validarUserName = User::where('email', $input['email'])->first();
        if ($validarUserName) {
            alert()->error('Error', 'El nombre de usuario ya existe');
            return redirect(route('usuarios.create'))->withInput();
        }

        $user = new User;
        $user->name = strtoupper($input['name']);
        $user->email = $input['email'];
        $user->password = Hash::make($input['password']);
        $user->ci = $input['ci'];
        $user->direccion = !empty($input['direccion']) ? strtoupper($input['direccion']) : null;
        $user->telefono = $input['telefono'];
        $user->estado = $input['estado'];
        $user->role_id = $input['role_id'];
        $user->cod_suc = $input['cod_suc'];
        $user->save();

        $user->roles()->sync([$input['role_id']]);

        alert()->success('Éxito', 'Registro creado correctamente');
        return redirect(route('usuarios.index'));
    }

    public function edit($id)
    {
        $usuario = User::find($id);

        if (!$usuario) {
            alert()->error('Error', 'El registro consultado no existe');
            return redirect(route('usuarios.index'));
        }

        $roles = DB::table('roles')->pluck('name', 'id');
        $estado = ["ACTIVO" => "ACTIVO", "INACTIVO" => "INACTIVO"];
        $sucursal = DB::table('sucursal')->pluck('suc_descri', 'cod_suc');

        return view('usuarios.edit')
            ->with('usuario', $usuario)
            ->with('roles', $roles)
            ->with('sucursal', $sucursal)
            ->with('estado', $estado);
    }

    public function update(Request $request, $id)
    {
        $usuario = User::find($id);

        if (!$usuario) {
            alert()->error('Error', 'El registro consultado no existe');
            return redirect(route('usuarios.index'));
        }

        $input = $request->all();

        $validator = Validator::make(
            $input,
            [
                'name' => 'required',
                'email' => 'required',
                'ci' => 'required|numeric',
            ]
        );

        if ($validator->fails()) {
            alert()->error('Error', 'Verifique los datos ingresados');
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validarCi = User::where('ci', $input['ci'])
            ->where('id', '!=', $id)
            ->first();

        if ($validarCi) {
            alert()->error('Error', 'Número de Cédula de Identidad ya existe');
            return redirect(route('usuarios.edit', [$id]))->withInput();
        }

        $validarUserName = User::where('email', $input['email'])
            ->where('id', '!=', $id)
            ->first();

        if ($validarUserName) {
            alert()->error('Error', 'El nombre de usuario ya existe');
            return redirect(route('usuarios.edit', [$id]))->withInput();
        }

        if (!empty($input['password'])) {
            $input['password'] = Hash::make($input['password']);
        } else {
            unset($input['password']);
        }

        $dataUpdate = [
            'name'       => strtoupper($input['name']),
            'email'      => $input['email'],
            'ci'         => $input['ci'],
            'password'   => !empty($input['password']) ? Hash::make($input['password']) : $usuario->password,
            'direccion'  => array_key_exists('direccion', $input) ? strtoupper($input['direccion']) : $usuario->direccion,
            'telefono'   => array_key_exists('telefono', $input) ? $input['telefono'] : $usuario->telefono,
            'estado'     => array_key_exists('estado', $input) ? $input['estado'] : $usuario->estado,
            'role_id'    => array_key_exists('role_id', $input) ? $input['role_id'] : $usuario->role_id,
            'cod_suc'    => array_key_exists('cod_suc', $input) ? $input['cod_suc'] : $usuario->cod_suc,
        ];

        $usuario->update($dataUpdate);

        $usuario->roles()->sync([$input['role_id']]);

        alert()->success('Éxito', 'Registro actualizado correctamente');
        return redirect(route('usuarios.index'));
    }

    public function destroy($id)
    {
        $usuario = User::find($id);

        if (!$usuario) {
            alert()->error('Error', 'El registro consultado no existe');
            return redirect(route('usuarios.index'));
        }

        $usuario->update(['estado' => 'INACTIVO']);

        alert()->success('Éxito', 'El registro se ha desactivado correctamente');
        return redirect(route('usuarios.index'));
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

    public function perfil()
    {
        return view('users.profile');
    }
}
