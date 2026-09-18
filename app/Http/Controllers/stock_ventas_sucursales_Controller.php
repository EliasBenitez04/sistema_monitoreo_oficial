<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use RealRashid\SweetAlert\Facades\Alert;
use App\Imports\VentasStockImport;

class stock_ventas_sucursales_Controller extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index()
    {

        $stocks = DB::table('stock_ventas_sucursales')
            ->select(
                'stock_ventas_sucursales.*'
            )
            ->get();

        return view('stock_ventas_sucursales.index', compact('stocks'));
    }



    public function importStock(Request $request)
    {

        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');
        
        $request->validate([

            'archivo' => [
                'required',
                'file',
                'mimes:xlsx,xls'
            ]

        ]);



        DB::beginTransaction();


        try {


            Excel::import(

                new VentasStockImport(),

                $request->file('archivo')

            );



            DB::commit();



            Alert::success(
                'Éxito',
                'Stock importado correctamente!'
            );



            return redirect()
                ->route('stock_ventas_sucursales.index');
        } catch (\Exception $e) {


            DB::rollBack();



            Log::error(
                'Error import stock: ' . $e->getMessage()
            );



            Alert::error(
                'Error',
                'Error al importar el stock: ' . $e->getMessage()
            );



            return back();
        }
    }
}
