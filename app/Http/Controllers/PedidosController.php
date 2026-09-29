<?php

namespace App\Http\Controllers;

class PedidosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ot dashboard');
    }

    public function index()
    {
        return view('pedidos.index');
    }

    public function importar()
    {
        return view('pedidos.importar');
    }
}
