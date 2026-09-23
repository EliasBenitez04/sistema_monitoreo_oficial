<?php

namespace App\Http\Controllers;

use App\Imports\ControlTerminacionRemisionImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class RemisionesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:redistribucionsugerencia update');
    }

    public function index()
    {
        $ultimaActualizacion = null;

        if (Schema::hasTable('ot_logistica_remisiones')) {
            $ultimaActualizacion = DB::table('ot_logistica_remisiones')
                ->max('updated_at');
        }

        return view('remisiones.index', compact('ultimaActualizacion'));
    }

    public function importar(Request $request)
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('max_input_time', '-1');
        @ini_set('memory_limit', '1536M');
        @ignore_user_abort(true);

        DB::disableQueryLog();

        $request->validate([
            'archivo_envios' => 'required|file|mimes:xlsx,xls,csv|max:102400',
        ]);

        if (!Schema::hasTable('ot_logistica_remisiones')) {
            return back()->with('error', 'Primero debe existir la tabla ot_logistica_remisiones.');
        }

        try {
            $inicio = microtime(true);
            $archivo = $request->file('archivo_envios');
            $import = new ControlTerminacionRemisionImport();
            $extension = strtolower($archivo->getClientOriginalExtension());

            Log::info('INICIO IMPORTACION CENTRAL REMISIONES', [
                'archivo' => $archivo->getClientOriginalName(),
                'tamano_bytes' => $archivo->getSize(),
            ]);

            if ($extension === 'xlsx') {
                $import->importarXlsxStreaming($archivo->getRealPath());
            } else {
                Excel::import($import, $archivo);
            }

            $segundos = microtime(true) - $inicio;
            $minutos = floor($segundos / 60);
            $segundosRestantes = $segundos - ($minutos * 60);
            $tiempo = $minutos > 0
                ? $minutos . ' min ' . number_format($segundosRestantes, 2, ',', '.') . ' s'
                : number_format($segundosRestantes, 2, ',', '.') . ' s';

            $mensaje = 'Importación finalizada. '
                . 'Líneas: ' . $import->getProcesadas()
                . ' | Nuevas OT/logística: ' . $import->getInsertadas()
                . ' | Actualizadas OT/logística: ' . $import->getActualizadas()
                . ' | Vinculadas a OT: ' . $import->getVinculadasOt()
                . ' | Vinculadas a logística: ' . $import->getVinculadas()
                . ' | Redistribuciones actualizadas: ' . $import->getRedistribucionActualizadas()
                . ' | Redistribuciones sin coincidencia: ' . $import->getRedistribucionSinCoincidencia()
                . ' | Omitidas: ' . $import->getOmitidas()
                . ' | Tiempo total: ' . $tiempo . '.';

            Log::info('FIN IMPORTACION CENTRAL REMISIONES', [
                'lineas' => $import->getProcesadas(),
                'redistribuciones_actualizadas' => $import->getRedistribucionActualizadas(),
                'segundos' => round($segundos, 3),
            ]);

            return back()->with('success', $mensaje);
        } catch (\Throwable $e) {
            Log::error('ERROR IMPORTACION CENTRAL REMISIONES', [
                'error' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
            ]);

            report($e);

            return back()->with('error', 'No se pudo importar el archivo: ' . $e->getMessage());
        }
    }
}
