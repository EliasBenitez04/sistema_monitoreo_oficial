<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RedistribucionLote;
use App\Models\RedistribucionProcesoDetalle;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | LOTES
        |--------------------------------------------------------------------------
        */

        $totalLotes = RedistribucionLote::count();

        $lotesGenerados = RedistribucionLote::where('estado', 'GENERADO')
            ->count();

        $lotesEnProceso = RedistribucionLote::where('estado', 'EN PROCESO')
            ->count();

        $lotesFinalizados = RedistribucionLote::where('estado', 'FINALIZADO')
            ->count();


        /*
        |--------------------------------------------------------------------------
        | DETALLES DE TRANSFERENCIAS
        |--------------------------------------------------------------------------
        |
        | IMPORTANTE:
        | El dashboard considera solamente detalles que pertenecen a un lote.
        |
        */

        $detallesLote = RedistribucionProcesoDetalle::whereNotNull('lote_id');


        /*
        |--------------------------------------------------------------------------
        | TRANSFERENCIAS
        |--------------------------------------------------------------------------
        */

        $totalTransferencias = (clone $detallesLote)->count();

        $transferenciasPendientes = (clone $detallesLote)
            ->where('estado', 'PENDIENTE')
            ->count();

        $transferenciasEnProceso = (clone $detallesLote)
            ->where('estado', 'EN PROCESO')
            ->count();

        $transferenciasFinalizadas = (clone $detallesLote)
            ->where('estado', 'FINALIZADO')
            ->count();


        /*
        |--------------------------------------------------------------------------
        | UNIDADES
        |--------------------------------------------------------------------------
        */

        $totalUnidades = (clone $detallesLote)
            ->sum('cantidad');

        $unidadesPendientes = (clone $detallesLote)
            ->where('estado', 'PENDIENTE')
            ->sum('cantidad');

        $unidadesEnProceso = (clone $detallesLote)
            ->where('estado', 'EN PROCESO')
            ->sum('cantidad');

        $unidadesFinalizadas = (clone $detallesLote)
            ->where('estado', 'FINALIZADO')
            ->sum('cantidad');


        /*
        |--------------------------------------------------------------------------
        | PORCENTAJE DE FINALIZACIÓN
        |--------------------------------------------------------------------------
        |
        | Se calcula sobre las transferencias de los detalles,
        | no sobre el total de lotes.
        |
        */

        $porcentajeFinalizacion = $totalTransferencias > 0
            ? round(($transferenciasFinalizadas / $totalTransferencias) * 100, 1)
            : 0;


        /*
        |--------------------------------------------------------------------------
        | PORCENTAJE DE UNIDADES FINALIZADAS
        |--------------------------------------------------------------------------
        */

        $porcentajeUnidadesFinalizadas = $totalUnidades > 0
            ? round(($unidadesFinalizadas / $totalUnidades) * 100, 1)
            : 0;


        /*
        |--------------------------------------------------------------------------
        | ÚLTIMOS LOTES
        |--------------------------------------------------------------------------
        |
        | Se cargan los detalles de cada lote.
        |
        */

        $ultimosLotes = RedistribucionLote::with([
            'detalles' => function ($query) {
                $query->select(
                    'id',
                    'lote_id',
                    'codigo',
                    'sucursal_origen',
                    'sucursal_destino',
                    'cantidad',
                    'estado',
                    'fecha',
                    'fecha_remision',
                    'fecha_recepcion'
                );
            }
        ])
            ->orderBy('fecha_generacion', 'desc')
            ->limit(5)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | CALCULAR INFORMACIÓN REAL DE CADA LOTE
        |--------------------------------------------------------------------------
        |
        | Esto permite que la vista muestre:
        |
        | - cantidad de transferencias
        | - unidades
        | - unidades finalizadas
        | - transferencias finalizadas
        |
        */

        foreach ($ultimosLotes as $lote) {

            $detalles = $lote->detalles;

            $lote->cantidad_transferencias = $detalles->count();

            $lote->cantidad_unidades = $detalles->sum('cantidad');

            $lote->transferencias_finalizadas = $detalles
                ->where('estado', 'FINALIZADO')
                ->count();

            $lote->unidades_finalizadas = $detalles
                ->where('estado', 'FINALIZADO')
                ->sum('cantidad');

            $lote->transferencias_pendientes = $detalles
                ->where('estado', 'PENDIENTE')
                ->count();

            $lote->transferencias_en_proceso = $detalles
                ->where('estado', 'EN PROCESO')
                ->count();

            /*
             * Porcentaje real de avance del lote
             */

            $lote->porcentaje_avance = $lote->cantidad_transferencias > 0
                ? round(
                    ($lote->transferencias_finalizadas /
                        $lote->cantidad_transferencias) * 100,
                    1
                )
                : 0;
        }


        /*
        |--------------------------------------------------------------------------
        | ÚLTIMO LOTE
        |--------------------------------------------------------------------------
        */

        $ultimoLote = RedistribucionLote::with('detalles')
            ->orderBy('fecha_generacion', 'desc')
            ->first();


        /*
        |--------------------------------------------------------------------------
        | LOTES QUE REQUIEREN ATENCIÓN
        |--------------------------------------------------------------------------
        */

        $lotesAtencion = RedistribucionLote::with('detalles')
            ->whereIn('estado', [
                'GENERADO',
                'EN PROCESO'
            ])
            ->orderBy('fecha_generacion', 'asc')
            ->limit(5)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | INFORMACIÓN DE ATENCIÓN POR LOTE
        |--------------------------------------------------------------------------
        */

        foreach ($lotesAtencion as $lote) {

            $detalles = $lote->detalles;

            $lote->cantidad_transferencias = $detalles->count();

            $lote->cantidad_unidades = $detalles->sum('cantidad');

            $lote->transferencias_finalizadas = $detalles
                ->where('estado', 'FINALIZADO')
                ->count();

            $lote->unidades_finalizadas = $detalles
                ->where('estado', 'FINALIZADO')
                ->sum('cantidad');

            $lote->porcentaje_avance = $lote->cantidad_transferencias > 0
                ? round(
                    ($lote->transferencias_finalizadas /
                        $lote->cantidad_transferencias) * 100,
                    1
                )
                : 0;
        }


        /*
        |--------------------------------------------------------------------------
        | RESUMEN POR ESTADO
        |--------------------------------------------------------------------------
        */

        $resumenEstados = [
            'pendientes' => $transferenciasPendientes,
            'proceso' => $transferenciasEnProceso,
            'finalizadas' => $transferenciasFinalizadas,
        ];


        /*
        |--------------------------------------------------------------------------
        | RETORNAR VISTA
        |--------------------------------------------------------------------------
        */

        return view('home', compact(

            // LOTES
            'totalLotes',
            'lotesGenerados',
            'lotesEnProceso',
            'lotesFinalizados',

            // TRANSFERENCIAS
            'totalTransferencias',
            'transferenciasPendientes',
            'transferenciasEnProceso',
            'transferenciasFinalizadas',

            // UNIDADES
            'totalUnidades',
            'unidadesPendientes',
            'unidadesEnProceso',
            'unidadesFinalizadas',

            // PORCENTAJES
            'porcentajeFinalizacion',
            'porcentajeUnidadesFinalizadas',

            // LOTES
            'ultimosLotes',
            'ultimoLote',
            'lotesAtencion',

            // RESUMEN
            'resumenEstados'
        ));
    }
}
