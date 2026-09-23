<?php

namespace App\Http\Controllers;

use App\Imports\SeguimientoPedidoImport;
use App\Models\SeguimientoPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class SeguimientoPedidoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ot dashboard');
    }

    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $query = SeguimientoPedido::query()
            ->withCount('detalles')
            ->orderByDesc('id');

        if ($buscar !== '') {
            $query->where('nro_pedido', 'ILIKE', '%' . $buscar . '%');
        }

        $pedidos = $query->paginate(30)->appends($request->query());

        return view('seguimiento_pedidos.index', compact('pedidos', 'buscar'));
    }

    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        $import = new SeguimientoPedidoImport();
        Excel::import($import, $request->file('archivo'));

        $mensaje = 'Importación finalizada. Filas: ' . $import->procesadas
            . ' | OT vinculadas: ' . $import->vinculadas
            . ' | OT no encontradas: ' . count($import->noEncontradas) . '.';

        if (!empty($import->noEncontradas)) {
            $mensaje .= ' No encontradas: ' . implode(', ', array_slice(array_unique($import->noEncontradas), 0, 20));
        }

        return back()->with('success', $mensaje);
    }

    public function show($id)
    {
        $pedido = SeguimientoPedido::findOrFail($id);

        $ots = DB::table('seguimiento_pedido_detalle as spd')
            ->join('ot as o', 'o.id_ot', '=', 'spd.id_ot')
            ->where('spd.seguimiento_pedido_id', $pedido->id)
            ->select('o.id_ot', 'o.nro_ot', 'o.codigo', 'o.descripcion', 'o.cantidad_orden')
            ->orderBy('o.nro_ot')
            ->get();

        $idsOt = $ots->pluck('id_ot')->all();

        $trazas = collect();
        $logistica = collect();
        $remisiones = collect();

        if (!empty($idsOt)) {
            $trazas = DB::table('ot_trazabilidad')
                ->whereIn('id_ot', $idsOt)
                ->whereIn('proceso', [
                    'TERMINACION - TERMINACION',
                    'TERMINACION - PRODUCTO TERMINADO',
                    'LOGISTICA - LOGISTICA Y DISTRIBUCION',
                ])
                ->select(
                    'id_ot',
                    'proceso',
                    DB::raw('SUM(resultado) as cantidad'),
                    DB::raw('MIN(fecha_proceso) as primera_fecha'),
                    DB::raw('MAX(fecha_proceso) as ultima_fecha')
                )
                ->groupBy('id_ot', 'proceso')
                ->get()
                ->groupBy('id_ot');

            $logistica = DB::table('ot_logistica_detalle')
                ->whereIn('id_ot', $idsOt)
                ->select('id_ot', 'sucursal', DB::raw('SUM(cantidad) as cantidad'))
                ->groupBy('id_ot', 'sucursal')
                ->get()
                ->groupBy('id_ot');

            if (Schema::hasTable('ot_logistica_remisiones')) {
                $remisiones = DB::table('ot_logistica_remisiones')
                    ->whereIn('id_ot', $idsOt)
                    ->select(
                        'id_ot',
                        'sucursal_logistica',
                        'sucursal_destino',
                        'cod_sucursal_destino',
                        DB::raw('SUM(cantidad) as enviado'),
                        DB::raw('SUM(CASE WHEN fecha_recepcion IS NOT NULL THEN cantidad ELSE 0 END) as recibido'),
                        DB::raw('MIN(fecha_remision) as primera_remision'),
                        DB::raw('MAX(fecha_remision) as ultima_remision'),
                        DB::raw('MAX(fecha_recepcion) as ultima_recepcion')
                    )
                    ->groupBy('id_ot', 'sucursal_logistica', 'sucursal_destino', 'cod_sucursal_destino')
                    ->get()
                    ->groupBy('id_ot');
            }
        }

        foreach ($ots as $ot) {
            $porProceso = collect($trazas->get($ot->id_ot, collect()))->keyBy('proceso');

            $entrada = $porProceso->get('TERMINACION - TERMINACION');
            $pt = $porProceso->get('TERMINACION - PRODUCTO TERMINADO');
            $salidaLogistica = $porProceso->get('LOGISTICA - LOGISTICA Y DISTRIBUCION');

            $ot->ingreso_terminacion = (int) ($entrada->cantidad ?? 0);
            $ot->producto_terminado = (int) ($pt->cantidad ?? 0);
            $ot->distribuido = (int) ($salidaLogistica->cantidad ?? 0);
            $ot->fecha_ingreso = $entrada->primera_fecha ?? null;
            $ot->fecha_pt = $pt->ultima_fecha ?? null;
            $ot->fecha_logistica = $salidaLogistica->ultima_fecha ?? null;

            $ot->locales = collect($remisiones->get($ot->id_ot, collect()))->map(function ($r) {
                $r->local = $r->sucursal_logistica ?: $r->sucursal_destino ?: ('Sucursal ' . $r->cod_sucursal_destino);
                $r->enviado = (int) $r->enviado;
                $r->recibido = (int) $r->recibido;
                $r->pendiente = max(0, $r->enviado - $r->recibido);
                $r->estado_local = $r->enviado > 0 && $r->recibido >= $r->enviado
                    ? 'RECIBIDO'
                    : ($r->recibido > 0 ? 'PARCIAL' : 'EN TRANSITO');
                return $r;
            })->values();

            // Los 12 locales comerciales se controlan separados del canal Mayorista/Depósito.
            // CASA CENTRAL y MATRIZ son nodos del canal mayorista y no deben inflar el contador de locales.
            $esMayorista = function ($local) {
                $nombre = strtoupper(trim((string) $local->local));
                return in_array($nombre, ['CASA CENTRAL', 'MATRIZ'], true);
            };

            $ot->locales_comerciales = $ot->locales->reject($esMayorista)->values();
            $ot->canal_mayorista = $ot->locales->filter($esMayorista)->values();

            // Movimientos físicos: auditoría. Pueden superar la cantidad de la OT por retornos/reenvíos.
            $ot->movimientos_fisicos = (int) $ot->locales->sum('enviado');
            $ot->movimientos_confirmados = (int) $ot->locales->sum('recibido');

            // Avance efectivo: nunca supera las prendas reales de la OT.
            $topeOt = max(0, (int) $ot->cantidad_orden);
            $ot->enviado = min($topeOt, $ot->movimientos_fisicos);
            $ot->recibido = min($topeOt, $ot->movimientos_confirmados);
            $ot->movimientos_adicionales = max(0, $ot->movimientos_fisicos - $topeOt);
            $ot->movimientos_confirmados_adicionales = max(0, $ot->movimientos_confirmados - $topeOt);

            $ot->locales_enviados = $ot->locales_comerciales->count();
            $ot->locales_confirmados = $ot->locales_comerciales->where('estado_local', 'RECIBIDO')->count();
            $ot->pendiente_recepcion = max(0, $topeOt - $ot->recibido);

            // Etapa real de punta a punta. No se infiere por una etiqueta manual:
            // se determina por la evidencia existente en trazabilidad/remisiones.
            if ($ot->ingreso_terminacion <= 0) {
                $ot->etapa_actual = 'PENDIENTE TERMINACION';
                $ot->etapa_numero = 0;
                $ot->estado_seguimiento = 'SIN TERMINACION';
            } elseif ($ot->producto_terminado < $ot->ingreso_terminacion) {
                $ot->etapa_actual = 'TERMINACION';
                $ot->etapa_numero = 1;
                $ot->estado_seguimiento = 'EN TERMINACION';
            } elseif ($ot->distribuido <= 0) {
                $ot->etapa_actual = 'PRODUCTO TERMINADO';
                $ot->etapa_numero = 2;
                $ot->estado_seguimiento = 'TERMINADO';
            } elseif ($ot->movimientos_fisicos <= 0) {
                $ot->etapa_actual = 'LOGISTICA';
                $ot->etapa_numero = 3;
                $ot->estado_seguimiento = 'EN LOGISTICA';
            } elseif ($ot->recibido < $ot->enviado) {
                $ot->etapa_actual = 'REMISION';
                $ot->etapa_numero = 4;
                $ot->estado_seguimiento = $ot->recibido > 0 ? 'RECEPCION PARCIAL' : 'EN TRANSITO';
            } else {
                $ot->etapa_actual = 'RECEPCION LOCAL';
                $ot->etapa_numero = 5;
                $ot->estado_seguimiento = 'COMPLETO';
            }

            $ot->porcentaje_seguimiento = $ot->etapa_numero > 0
                ? (int) round(($ot->etapa_numero / 5) * 100)
                : 0;
        }

        $resumen = (object) [
            'ots' => $ots->count(),
            'cantidad' => (int) $ots->sum('cantidad_orden'),
            'terminado' => (int) $ots->sum('producto_terminado'),
            'enviado' => (int) $ots->sum('enviado'),
            'recibido' => (int) $ots->sum('recibido'),
            'completas' => $ots->where('estado_seguimiento', 'COMPLETO')->count(),
        ];

        return view('seguimiento_pedidos.show', compact('pedido', 'ots', 'resumen'));
    }
}
