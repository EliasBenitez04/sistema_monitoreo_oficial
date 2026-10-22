<?php

namespace App\Http\Controllers;

use App\Support\MayoristaConciliacion as Conciliador;
use App\Support\MayoristaOrigenRemision as OrigenRemision;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MayoristaFifoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:pedido_compras index');
    }

    private function rango(Request $request): array
    {
        $datos = $request->validate([
            'desde' => 'required|date_format:Y-m-d',
            'hasta' => 'required|date_format:Y-m-d|after_or_equal:desde',
        ]);
        if (Carbon::parse($datos['desde'])->diffInDays(Carbon::parse($datos['hasta'])) > 92) {
            throw ValidationException::withMessages(['hasta' => 'Procesá períodos de hasta 93 días.']);
        }
        return [$datos['desde'], $datos['hasta']];
    }

    private function facturas(string $desde, string $hasta)
    {
        $facturas = collect();
        $cursor = Carbon::parse($desde)->startOfMonth();
        $fin = Carbon::parse($hasta)->startOfMonth();
        while ($cursor->lte($fin)) {
            $facturas = $facturas->concat(VentaMayoristaController::facturas($cursor->format('Y-m')));
            $cursor->addMonth();
        }
        return $facturas->filter(function ($f) use ($desde, $hasta) {
            return $f->fecha >= $desde && $f->fecha <= $hasta && (int) $f->brutas > 0;
        })->sortBy(function ($f) {
            return $f->fecha . '|' . $f->comprobante . '|' . $f->clave;
        })->values();
    }

    private function propuesta(string $desde, string $hasta, bool $bloquear = false): array
    {
        $facturas = $this->facturas($desde, $hasta);
        $claves = $facturas->pluck('clave')->all();
        if (!$claves) return ['filas' => collect(), 'excepciones' => collect(), 'facturas' => 0];

        $vinculos = DB::table('mayorista_vinculos')->whereIn('factura_clave', $claves)
            ->select('factura_clave', 'codigo')->selectRaw('SUM(cantidad) as cantidad')
            ->groupBy('factura_clave', 'codigo')->get();
        $paresExistentes = DB::table('mayorista_vinculos')
            ->whereIn('factura_clave', $claves)
            ->get(['factura_clave', 'remision_id'])
            ->groupBy('factura_clave')
            ->map(function ($grupo) { return $grupo->pluck('remision_id')->flip(); });
        $ocupacionFactura = [];
        foreach ($vinculos as $v) {
            $codigo = Conciliador::codigo($v->codigo);
            $ocupacionFactura[$v->factura_clave][$codigo] =
                ($ocupacionFactura[$v->factura_clave][$codigo] ?? 0) + (int) $v->cantidad;
        }

        $ventas = VentaMayoristaController::ventas()->whereBetween('v.fecha', [$desde, $hasta])
            ->where('v.cantidad', '>', 0)
            ->select('v.fecha', 'v.comprobante', 'v.cli_cod', 'v.cliente', 'v.codigo')
            ->selectRaw('SUM(v.cantidad) as cantidad')
            ->groupBy('v.fecha', 'v.comprobante', 'v.cli_cod', 'v.cliente', 'v.codigo')
            ->orderBy('v.fecha')->orderBy('v.comprobante')->get();

        $necesidades = [];
        $codigos = [];
        foreach ($ventas as $v) {
            $clave = Conciliador::claveFactura($v->fecha, $v->comprobante, $v->cli_cod, $v->cliente);
            if (!in_array($clave, $claves, true)) continue;
            $codigo = Conciliador::codigo($v->codigo);
            if ($codigo === '') continue;
            $necesidades[$clave][$codigo] = ($necesidades[$clave][$codigo] ?? 0) + (int)$v->cantidad;
            $codigos[$codigo] = true;
        }

        $remisiones = collect();
        foreach (array_chunk(array_keys($codigos), 200) as $grupo) {
            $query = VentaMayoristaController::entradas()
                ->whereRaw(OrigenRemision::expresionDestinoMatriz('r'))
                ->whereIn(DB::raw('UPPER(BTRIM(r.codigo))'), $grupo)
                ->where('r.fecha_remision', '<=', $hasta)
                ->whereNotNull('r.fecha_recepcion')
                ->where('r.fecha_recepcion', '<=', $hasta)
                ->where('r.cantidad', '>', 0)
                ->select('r.id', 'r.codigo', 'r.cantidad', 'r.fecha_remision', 'r.fecha_recepcion',
                    'r.numero_remision', 'r.serie', 'r.sucursal_salida', 'r.cod_sucursal_salida');
            if ($bloquear) $query->orderBy('r.id')->lockForUpdate();
            $remisiones = $remisiones->concat($query->get());
        }

        $usos = DB::table('mayorista_vinculos')->whereIn('remision_id', $remisiones->pluck('id')->all())
            ->select('remision_id')->selectRaw('SUM(cantidad) as usadas')
            ->groupBy('remision_id')->pluck('usadas', 'remision_id');
        $pool = [];
        foreach ($remisiones as $r) {
            $r->saldo = max(0, (int)$r->cantidad - (int)($usos[$r->id] ?? 0));
            if (!$r->saldo) continue;
            $pool[Conciliador::codigo($r->codigo)][] = $r;
        }
        foreach ($pool as &$items) {
            usort($items, function ($a, $b) {
                return strcmp($a->fecha_recepcion . '|' . $a->fecha_remision . '|' . sprintf('%012d', $a->id),
                    $b->fecha_recepcion . '|' . $b->fecha_remision . '|' . sprintf('%012d', $b->id));
            });
        }
        unset($items);

        $filas = collect();
        $excepciones = collect();
        foreach ($facturas as $f) {
            foreach ($necesidades[$f->clave] ?? [] as $codigo => $cantidad) {
                $pendiente = max(0, $cantidad - ($ocupacionFactura[$f->clave][$codigo] ?? 0));
                if (!$pendiente) continue;
                foreach ($pool[$codigo] ?? [] as $r) {
                    if (!$pendiente) break;
                    if (isset($paresExistentes[$f->clave]) && $paresExistentes[$f->clave]->has($r->id)) continue;
                    if ($r->saldo <= 0 || $r->fecha_remision > $f->fecha
                        || substr((string)$r->fecha_recepcion, 0, 10) > $f->fecha) continue;
                    $usar = min($r->saldo, $pendiente);
                    $filas->push((object)[
                        'clave' => $f->clave, 'comprobante' => $f->comprobante,
                        'fecha_factura' => $f->fecha, 'codigo' => $codigo,
                        'remision_id' => $r->id, 'serie' => $r->serie, 'numero' => $r->numero_remision,
                        'origen' => $r->sucursal_salida, 'fecha_remision' => $r->fecha_remision,
                        'fecha_recepcion' => $r->fecha_recepcion, 'cantidad' => $usar,
                    ]);
                    $r->saldo -= $usar;
                    $pendiente -= $usar;
                }
                if ($pendiente) $excepciones->push((object)[
                    'comprobante' => $f->comprobante, 'fecha' => $f->fecha,
                    'codigo' => $codigo, 'cantidad' => $pendiente,
                    'motivo' => 'Sin saldo recibido antes de la factura o sin remisión coincidente',
                ]);
            }
        }
        return ['filas' => $filas, 'excepciones' => $excepciones, 'facturas' => $facturas->count()];
    }

    public function index(Request $request)
    {
        $desde = $request->query('desde', now()->startOfMonth()->format('Y-m-d'));
        $hasta = $request->query('hasta', now()->format('Y-m-d'));
        $request->merge(['desde' => $desde, 'hasta' => $hasta]);
        [$desde, $hasta] = $this->rango($request);
        $resultado = $this->propuesta($desde, $hasta);
        return view('ventas.mayoristas_fifo', compact('desde', 'hasta', 'resultado'));
    }

    public function aprobar(Request $request)
    {
        [$desde, $hasta] = $this->rango($request);
        $datos = $request->validate(['referencia' => 'required|string|min:8|max:255']);
        $resultado = DB::transaction(function () use ($request, $desde, $hasta, $datos) {
            // Serializar TODAS las aprobaciones FIFO en Matriz, incluso períodos solapados.
            DB::select("SELECT pg_advisory_xact_lock(250125)");
            $propuesta = $this->propuesta($desde, $hasta, true);
            $cantidad = 0;
            foreach ($propuesta['filas'] as $fila) {
                // El algoritmo calcula saldos globales y asigna cada unidad una sola vez.
                $id = DB::table('mayorista_vinculos')->insertGetId([
                    'factura_clave' => $fila->clave, 'comprobante' => $fila->comprobante,
                    'fecha_factura' => $fila->fecha_factura, 'codigo' => $fila->codigo,
                    'remision_id' => $fila->remision_id, 'cantidad' => $fila->cantidad,
                    'usuario_id' => $request->user()->id,
                    'observacion' => 'FIFO masivo · ' . $datos['referencia'],
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('mayorista_auditoria')->insert([
                    'accion' => 'VINCULAR', 'factura_clave' => $fila->clave,
                    'remision_id' => $fila->remision_id, 'codigo' => $fila->codigo,
                    'cantidad' => $fila->cantidad, 'separacion_id' => null,
                    'usuario_id' => $request->user()->id,
                    'detalle' => 'FIFO MASIVO · vínculo ' . $id . ' · ' . $datos['referencia'],
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $cantidad += $fila->cantidad;
            }
            return ['prendas' => $cantidad, 'excepciones' => $propuesta['excepciones']->sum('cantidad')];
        }, 3);
        return redirect()->route('ventas.mayoristas.fifo', compact('desde', 'hasta'))
            ->with('success', 'FIFO aplicado: ' . $resultado['prendas'] .
                ' prendas vinculadas. Excepciones aún sin asignar: ' . $resultado['excepciones'] . '.');
    }
}
