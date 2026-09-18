<?php

namespace App\Http\Controllers;

use App\Imports\StockImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;

class StockController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:stocks importar')->only('importar');
    }

    public function index()
    {
        $stocks = DB::table('stock_sucursales')
            ->select('stock_sucursales.*')
            ->get();

        return view('stocks.index')->with('stocks', $stocks);
    }

    public function importStock(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls'
        ]);

        try {

            DB::beginTransaction();

            Excel::import(new StockImport, $request->file('archivo'));

            DB::commit();

            Alert::success('Éxito', 'Stock importado correctamente!');
            return redirect()->route('stocks.index');
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Error import stock: ' . $e->getMessage());

            Alert::error('Error', 'Error al importar el stock');
            return back();
        }
    }
}
