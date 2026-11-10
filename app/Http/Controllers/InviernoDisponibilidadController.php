<?php

namespace App\Http\Controllers;

use App\Exports\InviernoDisponibilidadExport;
use App\Services\InviernoDisponibilidadService;
use App\Services\InviernoTrazabilidadService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
            'situacion' => ['nullable', Rule::in(['todos', 'disponibles', 'pendientes', 'revisar'])],
            'orden' => ['nullable', Rule::in(['disponible', 'grupo', 'central', 'pendiente', 'remitido'])],
        ]);
    }

    public function index(Request $request)
    {
        $datos = $this->service->consultar($this->filtros($request));
        $datos['paginados'] = $this->paginar($datos['grupos'], $request, 25);
        $datos['locales'] = app(InviernoTrazabilidadService::class)->locales();
        return view('invierno.disponibilidad', $datos);
    }

    public function detalle(Request $request)
    {
        $filtros = $this->filtros($request, true);
        $datos = $this->service->consultar($filtros);
        $datos['grupo'] = $filtros['grupo'];
        $datos['paginados'] = $this->paginar($datos['lineas']->sortBy('codigo')->values(), $request, 50);
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
}
