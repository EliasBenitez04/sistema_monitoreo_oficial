<?php

namespace App\Http\Controllers;

use App\Imports\ControlTerminacionRemisionImport;
use App\Imports\RedistribucionRemisionImport;
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
            $rutaArchivo = $archivo->getRealPath();
            $extension = strtolower($archivo->getClientOriginalExtension());

            Log::info('INICIO IMPORTACION CENTRAL REMISIONES', [
                'archivo' => $archivo->getClientOriginalName(),
                'tamano_bytes' => $archivo->getSize(),
            ]);

            /*
             * IMPORTANTE:
             * La unificación se hace en el CONTROLADOR, no mezclando las reglas
             * de negocio de ambos importadores.
             *
             * 1) Redistribución ejecuta exactamente su importador histórico.
             * 2) OT/Logística ejecuta su importador propio.
             *
             * El usuario selecciona el archivo UNA sola vez, pero internamente
             * cada módulo procesa el mismo archivo con la lógica que ya funcionaba
             * cuando estaban separados.
             */
            $redisAntes = (int) DB::table('redistribucion_remision')->count();
            $redisImport = new RedistribucionRemisionImport();
            Excel::import($redisImport, $archivo);
            $redisDespues = (int) DB::table('redistribucion_remision')->count();
            $redisNuevas = max(0, $redisDespues - $redisAntes);

            $import = new ControlTerminacionRemisionImport();

            if ($extension === 'xlsx') {
                $import->importarXlsxStreaming($rutaArchivo);
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
                . ' | Redistribución: importador original ejecutado'
                . ' | Nuevas remisiones redistribución: ' . $redisNuevas
                . ' | Omitidas: ' . $import->getOmitidas()
                . ' | Tiempo total: ' . $tiempo . '.';

            Log::info('FIN IMPORTACION CENTRAL REMISIONES', [
                'lineas' => $import->getProcesadas(),
                'redistribucion_importador_original' => true,
                'redistribucion_nuevas_remisiones' => $redisNuevas,
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
