<?php

namespace App\Http\Controllers;

use App\Imports\MariaPirelliStockImport;
use App\Services\MariaPirelliStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

class MariaPirelliStockController extends Controller
{
    public function index(Request $request, MariaPirelliStockService $stock)
    {
        $filtros = $request->validate([
            'sucursal' => 'nullable|string|max:122',
            'buscar' => 'nullable|string|max:100',
        ]);
        $stockActual = $stock->actual();
        $sucursal = $stockActual['sucursales']->get($filtros['sucursal'] ?? '');
        $buscar = $filtros['buscar'] ?? '';
        $articulos = $sucursal ? $stock->articulosPorSucursal(
            $stockActual['corte']->id,
            $sucursal->sucursal_clave,
            $buscar
        ) : null;

        return view('maria_pirelli.stock', compact('stockActual', 'sucursal', 'articulos', 'buscar'));
    }

    public function importar(Request $request, MariaPirelliStockImport $stock)
    {
        $filtros = $request->validate([
            'archivo' => 'required|file|max:10240',
            'fecha_corte' => 'nullable|date_format:Y-m-d',
        ]);
        if (!Schema::hasTable('maria_pirelli_stock_cortes')) {
            return redirect()->route('maria-pirelli.stock')->with('error', 'Ejecutá la migración de stock de María Pirelli.');
        }

        try {
            $resultado = $stock->importar($request->file('archivo'), $filtros['fecha_corte'] ?? null);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            return back()->with('error', 'La carga de stock falló. Revisá el archivo y volvé a intentar.');
        }

        $fecha = \Carbon\Carbon::parse($resultado['fecha'])->format('d/m/Y');
        $filas = number_format($resultado['filas'], 0, ',', '.');
        $mensaje = "Stock del {$fecha} importado con {$filas} " . ($resultado['filas'] === 1 ? 'fila.' : 'filas.');
        if ($resultado['omitidas'] > 0) {
            $omitidas = number_format($resultado['omitidas'], 0, ',', '.');
            $mensaje .= $resultado['omitidas'] === 1
                ? ' Se omitió una fila con un código fuera del catálogo de María Pirelli.'
                : " Se omitieron {$omitidas} filas con códigos fuera del catálogo de María Pirelli.";
        }

        return redirect()->route('maria-pirelli.stock')->with('success', $mensaje);
    }
}
