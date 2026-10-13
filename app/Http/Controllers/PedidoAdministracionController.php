<?php

namespace App\Http\Controllers;

use App\Models\SeguimientoPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PedidoAdministracionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ot dashboard');
    }

    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));
        $pedidos = DB::table('seguimiento_pedido as p')
            ->leftJoin('seguimiento_pedido_detalle as d', 'd.seguimiento_pedido_id', '=', 'p.id')
            ->select('p.id', 'p.nro_pedido', 'p.fecha_pedido')
            ->selectRaw('COUNT(DISTINCT d.id_ot) as total_ot')
            ->when($buscar !== '', function ($q) use ($buscar) {
                $q->where(function ($f) use ($buscar) {
                    $f->where('p.nro_pedido', 'ILIKE', '%' . $buscar . '%')
                        ->orWhereExists(function ($sub) use ($buscar) {
                            $sub->select(DB::raw(1))
                                ->from('seguimiento_pedido_detalle as x')
                                ->join('ot as o', 'o.id_ot', '=', 'x.id_ot')
                                ->whereColumn('x.seguimiento_pedido_id', 'p.id')
                                ->where('o.nro_ot', 'ILIKE', '%' . $buscar . '%');
                        });
                });
            })
            ->groupBy('p.id', 'p.nro_pedido', 'p.fecha_pedido')
            ->orderByDesc('p.id')
            ->paginate(30)->appends($request->query());

        return view('pedidos.administrar.index', compact('pedidos', 'buscar'));
    }

    public function show($id)
    {
        $pedido = SeguimientoPedido::findOrFail($id);
        $ots = DB::table('seguimiento_pedido_detalle as d')
            ->join('ot as o', 'o.id_ot', '=', 'd.id_ot')
            ->where('d.seguimiento_pedido_id', $pedido->id)
            ->select('d.id', 'o.id_ot', 'o.nro_ot', 'o.codigo', 'o.descripcion', 'o.cantidad_orden')
            ->orderBy('o.nro_ot')->get();
        $historial = DB::table('pedido_ot_cambios')
            ->where('pedido_id', $pedido->id)
            ->orderByDesc('id')->limit(50)->get();

        return view('pedidos.administrar.show', compact('pedido', 'ots', 'historial'));
    }

    public function renombrar(Request $request, $id)
    {
        $datos = $request->validate([
            'nro_pedido' => 'required|string|max:100|regex:/^(IT|P|T)[0-9]+$/i',
            'motivo' => 'required|string|min:5|max:1000',
        ]);
        $nuevo = strtoupper(trim($datos['nro_pedido']));
        DB::transaction(function () use ($id, $nuevo, $datos) {
            $pedido = SeguimientoPedido::whereKey($id)->lockForUpdate()->firstOrFail();
            if (strcasecmp($pedido->nro_pedido, $nuevo) === 0) {
                throw ValidationException::withMessages(['nro_pedido' => 'Ingresá un número de pedido distinto.']);
            }
            if (SeguimientoPedido::whereRaw('UPPER(nro_pedido) = ?', [$nuevo])->where('id', '<>', $id)->exists()) {
                throw ValidationException::withMessages(['nro_pedido' => 'Ese número de pedido ya existe.']);
            }
            $anterior = $pedido->nro_pedido;
            $pedido->nro_pedido = $nuevo;
            $pedido->save();
            $this->registrar($pedido->id, 'RENOMBRAR_PEDIDO', $datos['motivo'], $anterior, $nuevo);
        });
        return redirect()->route('pedidos.administrar.show', $id)->with('success', 'Número de pedido actualizado.');
    }

    public function cambiarOt(Request $request, $id, $detalle)
    {
        $datos = $request->validate([
            'accion' => 'required|in:REEMPLAZAR,ANULAR',
            'nro_ot' => 'required_if:accion,REEMPLAZAR|nullable|string|max:100',
            'motivo' => 'required|string|min:5|max:1000',
        ]);
        DB::transaction(function () use ($id, $detalle, $datos) {
            $pedido = SeguimientoPedido::whereKey($id)->lockForUpdate()->firstOrFail();
            $vinculo = DB::table('seguimiento_pedido_detalle')
                ->where('id', $detalle)->where('seguimiento_pedido_id', $pedido->id)
                ->lockForUpdate()->first();
            if (!$vinculo) {
                throw ValidationException::withMessages(['accion' => 'La OT ya no está vinculada a este pedido.']);
            }
            $anterior = DB::table('ot')->where('id_ot', $vinculo->id_ot)->first();
            if (!$anterior) {
                throw ValidationException::withMessages(['accion' => 'No se encontró la OT original.']);
            }
            $nueva = null;
            if ($datos['accion'] === 'REEMPLAZAR') {
                $nueva = DB::table('ot')->where('nro_ot', trim($datos['nro_ot']))->first();
                if (!$nueva) {
                    throw ValidationException::withMessages(['nro_ot' => 'La OT de reemplazo no existe en el maestro de OT.']);
                }
                if ((int) $nueva->id_ot === (int) $anterior->id_ot) {
                    throw ValidationException::withMessages(['nro_ot' => 'La OT nueva debe ser distinta de la anterior.']);
                }
                if (DB::table('seguimiento_pedido_detalle')
                    ->where('seguimiento_pedido_id', $pedido->id)
                    ->where('id_ot', $nueva->id_ot)->exists()) {
                    throw ValidationException::withMessages(['nro_ot' => 'La OT de reemplazo ya está en este pedido.']);
                }
                DB::table('seguimiento_pedido_detalle')
                    ->where('id', $detalle)->update(['id_ot' => $nueva->id_ot]);
            } else {
                DB::table('seguimiento_pedido_detalle')->where('id', $detalle)->delete();
            }
            $this->registrar($pedido->id, $datos['accion'], $datos['motivo'],
                $pedido->nro_pedido, $pedido->nro_pedido,
                (string) $anterior->nro_ot, $nueva ? (string) $nueva->nro_ot : null);
        });
        return redirect()->route('pedidos.administrar.show', $id)
            ->with('success', $datos['accion'] === 'ANULAR' ? 'OT desvinculada del pedido; historial conservado.' : 'OT reemplazada correctamente; historial conservado.');
    }

    private function registrar($id, $accion, $motivo, $pedidoAnterior, $pedidoNuevo, $otAnterior = null, $otNueva = null)
    {
        DB::table('pedido_ot_cambios')->insert([
            'pedido_id' => $id,
            'accion' => $accion,
            'motivo' => $motivo,
            'pedido_anterior' => $pedidoAnterior,
            'pedido_nuevo' => $pedidoNuevo,
            'ot_anterior' => $otAnterior,
            'ot_nueva' => $otNueva,
            'usuario_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
