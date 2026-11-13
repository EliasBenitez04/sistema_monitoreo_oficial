<?php

namespace App\Http\Controllers;

use App\Exports\InviernoArticulosExport;
use App\Exports\InviernoCodigosSinImagenExport;
use App\Exports\InviernoDisponibilidadExport;
use App\Services\ArticuloImagenService;
use App\Services\InviernoDisponibilidadService;
use App\Services\InviernoTrazabilidadService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class InviernoDisponibilidadController extends Controller
{
    private InviernoDisponibilidadService $service;

    public function __construct(InviernoDisponibilidadService $service)
    {
        $this->service = $service;
    }

    private function filtros(Request $request, bool $detalle = false): array
    {
        return $request->validate([
            'grupo' => ($detalle ? 'required' : 'nullable').'|string|max:255',
            'codigo' => 'nullable|string|max:50', 'local' => 'nullable|integer|min:1',
            'buscar' => 'nullable|string|max:100', 'page' => 'nullable|integer|min:1',
            'remisiones_page' => 'nullable|integer|min:1',
            'estado' => ['nullable', Rule::in(array_keys(InviernoTrazabilidadService::ESTADOS))],
            'orden_articulos' => ['nullable', Rule::in(['disponible', 'codigo', 'prendas', 'remitido'])],
            'situacion' => ['nullable', Rule::in(['todos', 'disponibles', 'pendientes', 'revisar'])],
            'orden' => ['nullable', Rule::in(['disponible', 'grupo', 'central', 'pendiente', 'remitido'])],
        ]);
    }

    public function index(Request $request)
    {
        $datos = $this->service->listado($this->filtros($request));
        $datos['paginados'] = $this->paginar($datos['grupos'], $request, 25);
        return view('invierno.disponibilidad', $datos);
    }

    public function detalle(Request $request)
    {
        $filtros = $this->filtros($request, true);
        $datos = $this->service->consultar($filtros);
        $datos['grupo'] = $filtros['grupo'];
        $datos['paginados'] = $this->paginar($datos['lineas']->sortBy('codigo')->values(), $request, 50);
        $datos['imagenes'] = app(\App\Services\ArticuloImagenService::class)->paraCodigos($datos['paginados']->getCollection()->pluck('codigo'));
        $datos['locales'] = app(InviernoTrazabilidadService::class)->locales();
        return view('invierno.disponibilidad-detalle', $datos);
    }

    private function paginar($coleccion, Request $request, int $cantidad): LengthAwarePaginator
    {
        $pagina = max(1, $request->integer('page', 1));
        return new LengthAwarePaginator($coleccion->forPage($pagina, $cantidad)->values(), $coleccion->count(),
            $cantidad, $pagina, ['path' => $request->url(), 'query' => $request->query()]);
    }

    public function exportar(Request $request)
    {
        $datos = $this->service->consultar($this->filtros($request));
        return Excel::download(new InviernoDisponibilidadExport($datos), 'disponibilidad_invierno_'.now()->format('Ymd_His').'.xlsx');
    }

    public function exportarCodigosSinImagen(Request $request, ArticuloImagenService $imagenes)
    {
        $datos = $this->service->consultar($this->filtros($request));
        $codigos = $this->codigosSinImagen($datos['lineas'], $imagenes);
        $claves = $imagenes->clavesParaImportar($codigos);

        return Excel::download(new InviernoCodigosSinImagenExport($claves),
            'codigos_base_sin_imagen_'.now()->format('Ymd_His').'.xlsx');
    }

    private function codigosSinImagen(Collection $lineas, ArticuloImagenService $imagenes): Collection
    {
        $codigos = $lineas->pluck('codigo')->unique();
        return $codigos->diff($imagenes->paraCodigos($codigos)->keys());
    }

    public function articulos(Request $request)
    {
        $filtros = $this->filtros($request);
        $datos = $this->service->consultar($filtros);
        $articulos = $this->service->articulos($datos['lineas'], $filtros['orden_articulos'] ?? 'disponible');
        $datos['paginados'] = $this->paginar($articulos, $request, 50);
        $datos['imagenes'] = app(\App\Services\ArticuloImagenService::class)->paraCodigos($datos['paginados']->getCollection()->pluck('codigo'));
        $pagina = max(1, $request->integer('remisiones_page', 1));
        $datos['lineasPaginadas'] = new LengthAwarePaginator($datos['lineas']->forPage($pagina, 50)->values(),
            $datos['lineas']->count(), 50, $pagina, ['path' => $request->url(), 'query' => $request->query(), 'pageName' => 'remisiones_page']);
        $datos['locales'] = app(InviernoTrazabilidadService::class)->locales();
        $datos['estados'] = InviernoTrazabilidadService::ESTADOS;
        return view('invierno.articulos', $datos);
    }

    public function exportarArticulos(Request $request)
    {
        $filtros = $this->filtros($request);
        $datos = $this->service->consultar($filtros);
        return Excel::download(new InviernoArticulosExport($datos,
            $this->service->articulos($datos['lineas'], $filtros['orden_articulos'] ?? 'disponible')),
            'prendas_invierno_por_remision_'.now()->format('Ymd_His').'.xlsx');
    }
}
