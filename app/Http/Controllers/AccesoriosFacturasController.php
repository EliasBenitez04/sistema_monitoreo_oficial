<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AccesoriosFacturasController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:ADMIN']);
    }

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'buscar' => 'nullable|string|max:100',
            'fco' => 'nullable|string|max:50',
            'linea' => 'nullable|string|max:100',
            'desde' => 'nullable|date_format:Y-m-d',
            'hasta' => 'nullable|date_format:Y-m-d',
        ]);
        $buscar = trim((string) ($filtros['buscar'] ?? ''));
        $fco = trim((string) ($filtros['fco'] ?? ''));
        $linea = trim((string) ($filtros['linea'] ?? ''));
        $desde = (string) ($filtros['desde'] ?? '');
        $hasta = (string) ($filtros['hasta'] ?? '');

        if ($desde !== '' && $hasta !== '' && $desde > $hasta) {
            throw ValidationException::withMessages([
                'hasta' => 'La fecha final no puede ser anterior a la inicial.',
            ]);
        }

        if (!Schema::hasTable('accesorios_compras') || !Schema::hasTable('accesorios_articulos')) {
            return redirect()->route('accesorios.importar.form')
                ->with('error', 'Primero configurá e importá las compras de accesorios.');
        }

        $tipoBusqueda = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $consulta = DB::table('accesorios_compras as c')
            ->join('accesorios_articulos as a', 'a.id', '=', 'c.articulo_id')
            ->when($buscar !== '', function ($q) use ($buscar, $tipoBusqueda) {
                $q->where(function ($b) use ($buscar, $tipoBusqueda) {
                    $b->where('a.codigo', $tipoBusqueda, '%' . $buscar . '%')
                        ->orWhere('a.descripcion', $tipoBusqueda, '%' . $buscar . '%');
                });
            })
            ->when($fco !== '', function ($q) use ($fco, $tipoBusqueda) {
                $q->where('c.fco_nro', $tipoBusqueda, '%' . $fco . '%');
            })
            ->when($linea !== '', function ($q) use ($linea) {
                $q->where('a.linea', $linea);
            })
            ->when($desde !== '', function ($q) use ($desde) {
                $q->whereDate('c.fecha_envio', '>=', $desde);
            })
            ->when($hasta !== '', function ($q) use ($hasta) {
                $q->whereDate('c.fecha_envio', '<=', $hasta);
            });

        // Paginar facturas (FCO + fecha), no filas, para no partir un documento
        // por la mitad. Los totales corresponden a las líneas filtradas.
        $documentos = (clone $consulta)
            ->select('c.fco_nro', 'c.fecha_envio')
            ->selectRaw('COUNT(*) AS renglones')
            ->selectRaw('COALESCE(SUM(c.cantidad_compra),0) AS unidades')
            ->selectRaw('COALESCE(SUM(c.cantidad_compra * c.costo_unitario),0) AS costo_total')
            ->groupBy('c.fco_nro', 'c.fecha_envio')
            ->orderByDesc('c.fecha_envio')
            ->orderByDesc('c.fco_nro')
            ->paginate(8)
            ->appends($request->except('page'));

        $lineasPorDocumento = collect();
        if ($documentos->count() > 0) {
            $filas = (clone $consulta)->where(function ($q) use ($documentos) {
                foreach ($documentos as $documento) {
                    $q->orWhere(function ($sub) use ($documento) {
                        $sub->where('c.fco_nro', $documento->fco_nro)
                            ->whereDate('c.fecha_envio', $documento->fecha_envio);
                    });
                }
            })
                ->select([
                    'c.id as compra_id', 'c.articulo_id', 'c.fco_nro', 'c.fecha_envio',
                    'c.cantidad_compra', 'c.costo_unitario', 'c.precio_venta',
                    'a.codigo', 'a.descripcion', 'a.linea',
                ])
                ->orderBy('a.codigo')
                ->orderBy('c.id')
                ->get();

            $lineasPorDocumento = $filas->groupBy(function ($fila) {
                return $fila->fco_nro . '|' . substr((string) $fila->fecha_envio, 0, 10);
            });
        }

        $opcionesFco = DB::table('accesorios_compras')
            ->distinct()->orderByDesc('fco_nro')->limit(100)->pluck('fco_nro');
        $opcionesLineas = DB::table('accesorios_articulos')
            ->whereNotNull('linea')->distinct()->orderBy('linea')->pluck('linea');

        return view('accesorios.facturas', compact(
            'documentos', 'lineasPorDocumento', 'buscar', 'fco', 'linea',
            'desde', 'hasta', 'opcionesFco', 'opcionesLineas'
        ));
    }

    /**
     * Edita datos reales del FCO importado. Cambiar código reasigna únicamente
     * la línea de compra al nuevo artículo, sin reescribir ventas/remisiones.
     * Descripción y línea pertenecen al catálogo y son comunes a sus FCO.
     */
    public function actualizar(Request $request, int $compraId)
    {
        $request->merge([
            'codigo' => strtoupper(trim((string) $request->input('codigo'))),
            'descripcion' => trim((string) $request->input('descripcion')),
            'linea' => trim((string) $request->input('linea', '')),
            'fco_nro' => trim((string) $request->input('fco_nro')),
        ]);
        $valores = $request->validate([
            'codigo' => 'required|string|max:50',
            'descripcion' => 'required|string|max:200',
            'linea' => 'nullable|string|max:100',
            'fco_nro' => 'required|string|max:50',
            'fecha_envio' => 'required|date_format:Y-m-d',
            'cantidad_compra' => 'required|integer|min:1|max:2147483647',
            'costo_unitario' => ['required', 'numeric', 'min:0', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/'],
            'precio_venta' => ['required', 'numeric', 'min:0', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/'],
        ], [
            'descripcion.required' => 'Ingresá la descripción del artículo.',
            'fco_nro.required' => 'Ingresá el número FCO.',
            'fecha_envio.date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
            'cantidad_compra.min' => 'La cantidad debe ser mayor que cero.',
            'costo_unitario.regex' => 'El costo admite hasta dos decimales con punto.',
            'precio_venta.regex' => 'El precio admite hasta dos decimales con punto.',
        ]);

        DB::transaction(function () use ($valores, $compraId) {
            $compra = DB::table('accesorios_compras')
                ->where('id', $compraId)->lockForUpdate()->first();
            abort_unless($compra, 404, 'Esta línea de compra ya no existe.');

            $original = DB::table('accesorios_articulos')
                ->where('id', $compra->articulo_id)->lockForUpdate()->first();
            abort_unless($original, 404, 'El artículo original no existe.');

            $nuevaLinea = trim((string) ($valores['linea'] ?? ''));
            $nuevaLinea = $nuevaLinea === '' ? null : $nuevaLinea;
            $codigoNuevo = $valores['codigo'];
            $articuloDestinoId = (int) $original->id;

            if ($codigoNuevo !== $original->codigo) {
                // No renombrar el artículo original: puede tener ventas y
                // remisiones históricas. Solo reasignamos ESTA línea FCO.
                $destino = DB::table('accesorios_articulos')
                    ->where('codigo', $codigoNuevo)->lockForUpdate()->first();

                if ($destino) {
                    if ($destino->descripcion !== $valores['descripcion']
                        || (string) $destino->linea !== (string) $nuevaLinea) {
                        throw ValidationException::withMessages([
                            'codigo' => 'El código ya existe con otra descripción o línea. Usá sus datos originales para reasignar esta compra.',
                        ]);
                    }
                    $articuloDestinoId = (int) $destino->id;
                } else {
                    $articuloDestinoId = DB::table('accesorios_articulos')
                        ->insertGetId([
                            'codigo' => $codigoNuevo,
                            'descripcion' => $valores['descripcion'],
                            'linea' => $nuevaLinea,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            } else {
                // Estos datos identifican el catálogo, compartido por
                // todas las compras FCO que tengan el mismo código.
                if ($original->descripcion !== $valores['descripcion']
                    || (string) $original->linea !== (string) $nuevaLinea) {
                    DB::table('accesorios_articulos')->where('id', $original->id)
                        ->update([
                            'descripcion' => $valores['descripcion'],
                            'linea' => $nuevaLinea,
                            'updated_at' => now(),
                        ]);
                }
            }

            // No duplicar una compra cuyo identificador lógico sea
            // artículo + FCO + fecha (índice único existente).
            $duplicado = DB::table('accesorios_compras')
                ->where('articulo_id', $articuloDestinoId)
                ->where('fco_nro', $valores['fco_nro'])
                ->whereDate('fecha_envio', $valores['fecha_envio'])
                ->where('id', '<>', $compraId)->exists();

            if ($duplicado) {
                throw ValidationException::withMessages([
                    'fco_nro' => 'Ya existe una compra de ese código con el mismo FCO y fecha. No se puede duplicar.',
                ]);
            }

            $anteriores = [
                'articulo_id' => (int) $compra->articulo_id,
                'fco_nro' => $compra->fco_nro,
                'fecha_envio' => $compra->fecha_envio,
                'cantidad_compra' => (int) $compra->cantidad_compra,
                'costo_unitario' => $compra->costo_unitario,
                'precio_venta' => $compra->precio_venta,
            ];
            $nuevos = [
                'articulo_id' => $articuloDestinoId,
                'fco_nro' => $valores['fco_nro'],
                'fecha_envio' => $valores['fecha_envio'],
                'cantidad_compra' => (int) $valores['cantidad_compra'],
                'costo_unitario' => number_format((float) $valores['costo_unitario'], 2, '.', ''),
                'precio_venta' => number_format((float) $valores['precio_venta'], 2, '.', ''),
            ];

            DB::table('accesorios_compras')->where('id', $compraId)
                ->update(array_merge($nuevos, ['updated_at' => now()]));

            Log::info('ACCESORIOS_FACTURAS_EDITAR', [
                'usuario_id' => auth()->id(), 'compra_id' => $compraId,
                'codigo_anterior' => $original->codigo, 'codigo_nuevo' => $codigoNuevo,
                'antes' => $anteriores, 'despues' => $nuevos,
            ]);
        });

        return back()->with('success', 'Compra FCO guardada. El detalle y los indicadores utilizan los valores nuevos.');
    }
}
