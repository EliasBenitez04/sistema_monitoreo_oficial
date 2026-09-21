<?php

namespace App\Http\Controllers;

use App\Models\RedistribucionConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RedistribucionConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->middleware('permission:redistribucionsugerencia index')
            ->only(['index']);

        $this->middleware('permission:redistribucionsugerencia create')
            ->only(['store']);
    }

    /**
     * Pantalla única de configuración del motor actual de redistribución.
     *
     * Se reutilizan columnas existentes para no exigir cambios de estructura:
     * - metodo_demanda: COBERTURA_AUTO | COBERTURA_FIJA
     * - porcentaje_demanda: 100 + porcentaje de seguridad
     * - cantidad_maxima: días de cobertura cuando el modo es fijo
     */
    public function index()
    {
        $config = RedistribucionConfig::where('activo', true)
            ->orderByDesc('id')
            ->first();

        if (!$config) {
            $config = RedistribucionConfig::orderByDesc('id')->first();
        }

        $esOperativa = $config
            && in_array(
                (string) $config->metodo_demanda,
                ['COBERTURA_AUTO', 'COBERTURA_FIJA'],
                true
            );

        $valores = [
            'metodo_demanda' => $esOperativa
                ? (string) $config->metodo_demanda
                : 'COBERTURA_AUTO',
            'dias_cobertura' => $esOperativa
                ? max(1, min(90, (int) $config->cantidad_maxima))
                : 7,
            'seguridad_porcentaje' => $esOperativa
                ? max(0, min(100, (int) round((float) $config->porcentaje_demanda - 100)))
                : 20,
            'stock_minimo_origen' => $esOperativa
                ? max(1, min(100, (int) $config->stock_minimo))
                : 1,
            'venta_minima' => $esOperativa
                ? max(1, (int) $config->venta_minima)
                : 1,
            'dias_bloqueo' => $esOperativa
                ? max(0, min(365, (int) $config->dias_bloqueo))
                : 30,
            'bloquear_pendientes' => $esOperativa
                ? (bool) $config->bloquear_pendientes
                : true,
            'bloquear_en_proceso' => $esOperativa
                ? (bool) $config->bloquear_en_proceso
                : true,
            'bloquear_finalizados_recientes' => $esOperativa
                ? (bool) $config->bloquear_finalizados_recientes
                : true,
        ];

        return view('redistribucion_configs.index', compact('config', 'valores', 'esOperativa'));
    }

    /**
     * Guarda o actualiza la configuración activa.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'metodo_demanda' => 'required|in:COBERTURA_AUTO,COBERTURA_FIJA',
            'dias_cobertura' => 'nullable|required_if:metodo_demanda,COBERTURA_FIJA|integer|min:1|max:90',
            'seguridad_porcentaje' => 'required|integer|min:0|max:100',
            'stock_minimo_origen' => 'required|integer|min:1|max:100',
            'venta_minima' => 'required|integer|min:1|max:1000000',
            'dias_bloqueo' => 'required|integer|min:0|max:365',
            'bloquear_pendientes' => 'required|boolean',
            'bloquear_en_proceso' => 'required|boolean',
            'bloquear_finalizados_recientes' => 'required|boolean',
        ], [
            'dias_cobertura.required_if' => 'Indique los días de cobertura cuando el modo es fijo.',
        ]);

        DB::transaction(function () use ($request, $data) {
            $config = RedistribucionConfig::where('activo', true)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (!$config) {
                $config = RedistribucionConfig::orderByDesc('id')
                    ->lockForUpdate()
                    ->first();
            }

            if (!$config) {
                $config = new RedistribucionConfig();

                // Valores compatibles con las columnas históricas que el motor actual
                // ya no utiliza directamente, pero pueden ser NOT NULL en la BD.
                $config->stock_maximo = 0;
                $config->porcentaje_necesidad = 100;
                $config->porcentaje_conservar_origen = 0;
                $config->cantidad_minima = 1;
            }

            $diasCobertura = (int) ($data['dias_cobertura'] ?? 7);

            $config->metodo_demanda = $data['metodo_demanda'];
            $config->porcentaje_demanda = 100 + (int) $data['seguridad_porcentaje'];
            $config->stock_minimo = (int) $data['stock_minimo_origen'];
            $config->venta_minima = (int) $data['venta_minima'];

            // En el motor vigente cantidad_maxima actúa como almacenamiento del
            // horizonte fijo de cobertura. Solo se interpreta así cuando
            // metodo_demanda es COBERTURA_AUTO/COBERTURA_FIJA.
            $config->cantidad_maxima = $diasCobertura;

            $config->dias_bloqueo = (int) $data['dias_bloqueo'];
            $config->bloquear_pendientes = $request->boolean('bloquear_pendientes');
            $config->bloquear_en_proceso = $request->boolean('bloquear_en_proceso');
            $config->bloquear_finalizados_recientes = $request->boolean('bloquear_finalizados_recientes');
            $config->activo = true;
            $config->save();

            RedistribucionConfig::where('id', '<>', $config->id)
                ->where('activo', true)
                ->update(['activo' => false]);
        });

        return redirect()
            ->route('redistribucion-configs.index')
            ->with('success', 'Configuración de redistribución guardada correctamente.');
    }
}
