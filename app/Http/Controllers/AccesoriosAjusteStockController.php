<?php

namespace App\Http\Controllers;

use App\Services\AccesoriosStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccesoriosAjusteStockController extends Controller
{
    public function __construct()
    {
        // La ruta y el controlador requieren el rol ADMIN de Spatie.
        $this->middleware(['auth', 'role:ADMIN']);
    }

    public function guardar(Request $request, int $id)
    {
        $datos = $request->validate([
            'sucursal_key' => 'required|string|max:130',
            'saldo_anterior' => 'required|integer',
            'saldo_nuevo' => 'required|integer|min:0|max:100000000',
            'motivo' => 'nullable|string|max:250',
        ], [
            'saldo_nuevo.required' => 'Ingresá el nuevo stock.',
            'saldo_nuevo.integer' => 'La cantidad debe ser un número entero.',
            'saldo_nuevo.min' => 'El nuevo stock no puede ser negativo.',
            'saldo_nuevo.max' => 'La cantidad ingresada supera el máximo permitido.',
        ]);

        abort_unless(Schema::hasTable('accesorios_stock_ajustes'), 503,
            'Primero ejecutá php artisan migrate para habilitar ajustes de stock.');

        // El bloqueo impide editar simultáneamente dos saldos del mismo artículo
        // basándose en cantidades desactualizadas.
        $resultado = DB::transaction(function () use ($id, $datos) {
            $articulo = DB::table('accesorios_articulos')
                ->where('id', $id)->lockForUpdate()->first(['id', 'codigo']);
            abort_unless($articulo, 404, 'No se encontró el artículo.');

            $calculo = (new AccesoriosStockService())->calcular(
                [$id], now()->toDateString()
            );
            $local = collect($calculo['detalle']->get($id, []))
                ->first(function ($item) use ($datos) {
                    return $item->sucursal_key === $datos['sucursal_key'];
                });
            abort_unless($local, 422, 'Esta sucursal no tiene saldo registrado para este código.');

            $anterior = (int) $local->saldo;
            if ($anterior !== (int) $datos['saldo_anterior']) {
                abort(409, 'El saldo cambió desde que abriste el detalle. Actualizá la vista e intentá de nuevo.');
            }

            $nuevo = (int) $datos['saldo_nuevo'];
            $diferencia = $nuevo - $anterior;
            if ($diferencia !== 0) {
                // Registro incremental, no modifica ventas ni remisiones.
                // Los movimientos posteriores se seguirán sumando al saldo.
                DB::table('accesorios_stock_ajustes')->insert([
                    'accesorio_articulo_id' => $id,
                    'sucursal_key' => (string) $local->sucursal_key,
                    'sucursal' => (string) $local->sucursal,
                    'saldo_anterior' => $anterior,
                    'saldo_nuevo' => $nuevo,
                    'diferencia' => $diferencia,
                    'motivo' => trim((string) ($datos['motivo'] ?? '')) ?: 'Corrección manual de stock',
                    'usuario_id' => auth()->id(),
                    'fecha_ajuste' => now()->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return [
                'codigo' => $articulo->codigo,
                'sucursal' => $local->sucursal,
                'anterior' => $anterior,
                'nuevo' => $nuevo,
                'diferencia' => $diferencia,
                'cambiado' => $diferencia !== 0,
            ];
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $resultado['cambiado']
                    ? 'Stock actualizado y registrado en auditoría.'
                    : 'El stock no cambió.',
                'ajuste' => $resultado,
            ]);
        }

        return redirect()->route('accesorios.index')
            ->with('success', $resultado['cambiado']
                ? 'Stock actualizado y registrado en auditoría.'
                : 'El stock no cambió.');
    }
}
