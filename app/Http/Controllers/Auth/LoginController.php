<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'name'  => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt([
            'name' => $request->name,
            'password' => $request->password
        ])) {
            return redirect()->to($this->redirectTo);
        }

        alert()->error('Error de acceso', 'El usuario y/o la contraseña no son correctos.');
        return redirect()->back()->withInput();
    }
}
