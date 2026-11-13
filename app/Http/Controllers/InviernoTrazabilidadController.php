<?php

namespace App\Http\Controllers;

use App\Exports\InviernoTrazabilidadExport;
use App\Imports\InviernoRemisionesImport;
use App\Services\InviernoTrazabilidadService;
use App\Support\InviernoDocumento as Documento;
use App\Support\InviernoValorizacion as Valorizacion;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class InviernoTrazabilidadController extends Controller
{
    private InviernoTrazabilidadService $service;

    public function __construct(InviernoTrazabilidadService $service)
    {
        $this->service = $service;
    }

    private function filtrados(Request $request): Collection
    {
        $filtros = $request->validate([
            'local' => 'nullable|integer|min:1',
            'estado' => ['nullable', Rule::in(array_keys(InviernoTrazabilidadService::ESTADOS))],
            'buscar' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1',
        ]);
        $buscar = Documento::texto($filtros['buscar'] ?? '');
        return $this->service->panorama()->filter(function ($d) use ($filtros, $buscar) {
            return (empty($filtros['local']) || (int) $d->cod_sucursal_origen === (int) $filtros['local'])
                && (empty($filtros['estado']) || $d->estado === $filtros['estado'])
                && ($buscar === '' || str_contains(Documento::texto($d->local.' '.$d->serie.' '.$d->numero_remision), $buscar));
        })->values();
    }

    public function index(Request $request)
    {
        $filtrados = $this->filtrados($request);
        $documentos = new LengthAwarePaginator($filtrados->forPage($request->integer('page', 1), 25)->values(),
            $filtrados->count(), 25, $request->integer('page', 1), ['path' => $request->url(), 'query' => $request->query()]);
        $valorizacionFiltrada = new Valorizacion();
        $valorizaciones = [];
        foreach ($filtrados as $documento) {
            $valorizacionFiltrada->sumarRemision($documento);
        }
        foreach ($documentos as $documento) {
            $valorizacion = new Valorizacion();
            $valorizacion->sumarRemision($documento);
            $valorizaciones[$documento->id] = $valorizacion->totales();
        }
        $resumen = [
            'documentos' => $filtrados->count(), 'prendas' => $filtrados->sum('total'),
            'central' => $filtrados->sum('confirmado_central'), 'pendiente' => $filtrados->sum('pendiente_central'),
            'remitido' => $filtrados->sum('remitido'), 'recibido' => $filtrados->sum('recibido_local'),
            'saldo' => $filtrados->sum('saldo_central'), 'revisar' => $filtrados->where('estado', 'REVISAR')->count(),
            'sin_remision' => $filtrados->where('estado', 'SIN_REMISION')->count(),
            'valorizacion' => $valorizacionFiltrada->totales(),
        ];
        return view('invierno.index', ['documentos' => $documentos, 'resumen' => $resumen,
            'locales' => $this->service->locales(), 'estados' => InviernoTrazabilidadService::ESTADOS,
            'valorizaciones' => $valorizaciones]);
    }

    public function registrar(Request $request)
    {
        $datos = $request->validate(['local' => 'required|string|max:100', 'serie' => 'required|string|max:20', 'nro' => 'required|string|max:30']);
        $nueva = $this->service->registrar($datos, $request->user()?->id);
        return redirect()->route('invierno.index')->with('success', $nueva ? 'Remisión de invierno registrada.' : 'Esta remisión ya estaba registrada; no se duplicó.');
    }

    public function importar(Request $request)
    {
        $request->validate(['archivo' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240']);
        $archivo = $request->file('archivo');
        $delimitador = ',';
        if (in_array(strtolower($archivo->getClientOriginalExtension()), ['csv', 'txt'], true)) {
            $handle = fopen($archivo->getRealPath(), 'r');
            $cabecera = fgets($handle) ?: '';
            fclose($handle);
            foreach ([',', ';', "\t"] as $opcion) {
                if (count(str_getcsv($cabecera, $opcion)) >= 3) {
                    $delimitador = $opcion;
                    break;
                }
            }
        }
        $import = new InviernoRemisionesImport($this->service, $request->user()?->id, $delimitador);
        try {
            // Mantener la extensión para que Laravel Excel reconozca XLS/CSV.
            Excel::import($import, $archivo);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['archivo' => 'No se pudo leer el archivo. Verificá su formato y los encabezados LOCAL, SERIE y NRO.']);
        }
        return redirect()->route('invierno.index')->with('success', 'Importación finalizada: '.$import->nuevas.' nuevas, '
            .$import->existentes.' ya registradas y '.$import->rechazadas.' rechazadas.')
            ->with('invierno_errores', $import->errores);
    }

    public function plantilla()
    {
        return response()->streamDownload(function () {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, ['LOCAL', 'SERIE', 'NRO'], ';');
            fputcsv($salida, ['BONANZA', 'F', '1188'], ';');
            fputcsv($salida, ['JARDINES', 'J1', '465'], ';');
            fclose($salida);
        }, 'plantilla_prendas_invierno.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportar(Request $request)
    {
        return Excel::download(new InviernoTrazabilidadExport($this->filtrados($request)), 'prendas_invierno_'.now()->format('Ymd_His').'.xlsx');
    }

    public function show(Request $request, int $id)
    {
        $request->validate(['entrada' => 'nullable|integer|min:1', 'buscar_salida' => 'nullable|string|max:100',
            'destino' => 'nullable|integer|min:1', 'salidas_page' => 'nullable|integer|min:1']);
        $documento = $this->service->panorama(documentoId: $id)->first();
        abort_unless($documento, 404);
        $entrada = $request->filled('entrada') ? $documento->entradas->firstWhere('id', $request->integer('entrada')) : null;
        abort_if($request->filled('entrada') && !$entrada, 404);
        $salidas = null;
        if ($entrada && !$entrada->problema && Documento::recibido($entrada) && $entrada->disponible > 0) {
            $usadas = DB::table('invierno_asignaciones')->whereNull('anulada_at')->groupBy('remision_salida_id')
                ->select('remision_salida_id')->selectRaw('SUM(cantidad) as asignada');
            $query = DB::table('ot_logistica_remisiones as r')->leftJoinSub($usadas, 'a', 'a.remision_salida_id', '=', 'r.id')
                ->where('r.cod_sucursal_salida', 1)->where('r.cod_sucursal_destino', '>', 0)->where('r.cod_sucursal_destino', '<>', 1)
                ->whereRaw('UPPER(TRIM(r.codigo)) = ?', [Documento::texto($entrada->codigo)])
                ->whereRaw('COALESCE(r.fecha_remision, r.fecha_creacion) >= ?', [$entrada->fecha_recepcion])
                ->whereRaw('COALESCE(r.fecha_remision, r.fecha_creacion) <= ?', [now()->toDateString()])
                ->whereRaw('r.cantidad > COALESCE(a.asignada, 0)')
                ->select('r.*')->selectRaw('r.cantidad - COALESCE(a.asignada, 0) as disponible');
            if ($request->filled('destino')) $query->where('r.cod_sucursal_destino', $request->integer('destino'));
            if ($request->filled('buscar_salida')) {
                $buscar = '%'.Documento::texto($request->input('buscar_salida')).'%';
                $query->where(function ($q) use ($buscar) {
                    $q->whereRaw('UPPER(r.serie) LIKE ?', [$buscar])->orWhere('r.numero_remision', 'like', $buscar)
                        ->orWhereRaw("(UPPER(TRIM(r.serie)) || ' ' || TRIM(r.numero_remision)) LIKE ?", [$buscar])
                        ->orWhereRaw('UPPER(r.sucursal_destino) LIKE ?', [$buscar]);
                });
            }
            $salidas = $query->orderByRaw('COALESCE(r.fecha_remision, r.fecha_creacion)')->orderBy('r.id')
                ->paginate(20, ['*'], 'salidas_page')->withQueryString();
            foreach ($salidas as $salida) {
                $salida->problema = $this->service->problemaVinculo($documento, $entrada, $salida);
                $salida->maximo = min((int) $entrada->disponible, (int) $salida->disponible);
            }
        }
        $historial = DB::table('invierno_asignaciones')->where('invierno_remision_id', $id)->whereNotNull('anulada_at')->orderByDesc('id')->get();
        $imagenes = app(\App\Services\ArticuloImagenService::class)->paraCodigos($documento->entradas->pluck('codigo'));
        $valorizacionRemision = new Valorizacion();
        $valorizacionRemision->sumarRemision($documento);
        $valorizacion = $valorizacionRemision->totales();
        return view('invierno.show', compact('documento', 'entrada', 'salidas', 'historial', 'imagenes', 'valorizacion'));
    }

    public function asociar(Request $request, int $id)
    {
        $datos = $request->validate(['entrada_id' => 'required|integer|min:1', 'salida_id' => 'required|integer|min:1',
            'cantidad' => 'required|integer|min:1|max:2147483647', 'observacion' => 'nullable|string|max:300']);
        $this->service->asociar($id, (int) $datos['entrada_id'], (int) $datos['salida_id'], (int) $datos['cantidad'],
            $request->user()?->id, $datos['observacion'] ?? null);
        return redirect()->route('invierno.show', $id)->with('success', 'Salida asociada. Se actualizaron los saldos del documento.');
    }

    public function anular(Request $request, int $id, int $asignacion)
    {
        $datos = $request->validate(['motivo' => 'required|string|min:3|max:300']);
        $this->service->anular($id, $asignacion, $request->user()?->id, $datos['motivo']);
        return redirect()->route('invierno.show', $id)->with('success', 'Asociación anulada. El historial y el motivo quedaron registrados.');
    }

    public function retirar(int $id)
    {
        DB::transaction(function () use ($id) {
            $documento = DB::table('invierno_remisiones')->where('id', $id)->lockForUpdate()->first();
            abort_unless($documento, 404);
            if (DB::table('invierno_asignaciones')->where('invierno_remision_id', $id)->exists()) {
                throw ValidationException::withMessages(['documento' => 'Este documento tiene historial de asociaciones y debe conservarse.']);
            }
            DB::table('invierno_remisiones')->where('id', $id)->delete();
        });
        return redirect()->route('invierno.index')->with('success', 'Documento retirado del seguimiento de invierno.');
    }
}
