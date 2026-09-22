<?php

namespace App\Http\Controllers;

use App\Imports\ControlTerminacionRemisionImport;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class ControlTerminacionController extends Controller
{
    public function index(Request $request)
    {
        $fechaDesde = $request->input('fecha_desde', now()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));
        $buscar = trim((string) $request->input('buscar', ''));
        $estados = $request->input('estado', []);

        if (!is_array($estados)) {
            $estados = [$estados];
        }

        $procesoEntradaTerminacion = 'TERMINACION - TERMINACION';
        $procesoProductoTerminado = 'TERMINACION - PRODUCTO TERMINADO';
        $hoy = now()->startOfDay();

        /*
         * REGLA DEL FLUJO
         *
         * TERMINACION - TERMINACION:
         *     ingreso de la prenda al área de Terminación.
         *
         * TERMINACION - PRODUCTO TERMINADO:
         *     salida de Terminación y entrega/entrada a Logística.
         *
         * Por lo tanto Producto Terminado NO se compara contra
         * LOGISTICA - LOGISTICA Y DISTRIBUCION para decidir si fue entregado.
         * Ese último proceso pertenece al Dashboard Logística.
         */
        $queryProduccion = DB::table('ot_trazabilidad as pt')
            ->join('ot as o', 'o.id_ot', '=', 'pt.id_ot')
            ->where('pt.proceso', $procesoProductoTerminado)
            ->whereBetween('pt.fecha_proceso', [$fechaDesde, $fechaHasta]);

        if ($buscar !== '') {
            $queryProduccion->where(function ($q) use ($buscar) {
                if (is_numeric($buscar)) {
                    $q->where('o.nro_ot', (int) $buscar)
                        ->orWhere('o.codigo', 'ILIKE', '%' . $buscar . '%')
                        ->orWhere('o.descripcion', 'ILIKE', '%' . $buscar . '%');
                } else {
                    $q->where('o.codigo', 'ILIKE', '%' . $buscar . '%')
                        ->orWhere('o.descripcion', 'ILIKE', '%' . $buscar . '%');
                }
            });
        }

        $produccionTerminada = $queryProduccion
            ->groupBy(
                'o.id_ot',
                'o.nro_ot',
                'o.codigo',
                'o.descripcion',
                'o.cantidad_orden',
                'o.estado'
            )
            ->select(
                'o.id_ot',
                'o.nro_ot',
                'o.codigo',
                'o.descripcion',
                'o.cantidad_orden',
                'o.estado',
                DB::raw('SUM(pt.resultado) as cantidad_terminada'),
                DB::raw('MIN(pt.fecha_proceso) as fecha_producto_terminado'),
                DB::raw('MAX(pt.fecha_proceso) as ultima_fecha_producto_terminado')
            )
            ->get();

        $idsOt = $produccionTerminada
            ->pluck('id_ot')
            ->filter()
            ->unique()
            ->values();

        $entradaPorOt = collect();

        if ($idsOt->isNotEmpty()) {
            $entradaPorOt = DB::table('ot_trazabilidad')
                ->whereIn('id_ot', $idsOt->all())
                ->where('proceso', $procesoEntradaTerminacion)
                ->groupBy('id_ot')
                ->select(
                    'id_ot',
                    DB::raw('SUM(resultado) as cantidad_ingreso_terminacion'),
                    DB::raw('MIN(fecha_proceso) as primera_fecha_ingreso'),
                    DB::raw('MAX(fecha_proceso) as ultima_fecha_ingreso')
                )
                ->get()
                ->keyBy('id_ot');
        }

        foreach ($produccionTerminada as $item) {
            $entrada = $entradaPorOt->get($item->id_ot);

            $item->cantidad_terminada = (int) $item->cantidad_terminada;
            $item->cantidad_ingreso_terminacion = (int) ($entrada->cantidad_ingreso_terminacion ?? 0);
            $item->primera_fecha_ingreso = $entrada->primera_fecha_ingreso ?? null;
            $item->ultima_fecha_ingreso = $entrada->ultima_fecha_ingreso ?? null;

            // Producto Terminado ya es la entrega/entrada a Logística.
            $item->cantidad_entregada_logistica = $item->cantidad_terminada;

            $item->pendiente_terminar = max(
                0,
                $item->cantidad_ingreso_terminacion - $item->cantidad_terminada
            );

            $item->exceso_producto_terminado = max(
                0,
                $item->cantidad_terminada - $item->cantidad_ingreso_terminacion
            );

            $item->dias_en_terminacion = null;

            if ($item->primera_fecha_ingreso) {
                $fechaIngreso = \Carbon\Carbon::parse($item->primera_fecha_ingreso)->startOfDay();

                if ($item->pendiente_terminar > 0) {
                    $item->dias_en_terminacion = max(
                        0,
                        $fechaIngreso->diffInDays($hoy, false)
                    );
                } else {
                    $fechaSalida = \Carbon\Carbon::parse(
                        $item->ultima_fecha_producto_terminado
                    )->startOfDay();

                    $item->dias_en_terminacion = max(
                        0,
                        $fechaIngreso->diffInDays($fechaSalida, false)
                    );
                }
            }

            if ($item->cantidad_ingreso_terminacion <= 0) {
                $item->estado_control = 'SIN INGRESO';
            } elseif ($item->cantidad_terminada < $item->cantidad_ingreso_terminacion) {
                $item->estado_control = 'PARCIAL';
            } elseif ($item->cantidad_terminada > $item->cantidad_ingreso_terminacion) {
                $item->estado_control = 'EXCEDENTE';
            } else {
                $item->estado_control = 'COMPLETO';
            }
        }

        if (!empty($estados)) {
            $produccionTerminada = $produccionTerminada
                ->filter(function ($item) use ($estados) {
                    return in_array($item->estado_control, $estados, true);
                })
                ->values();
        }

        $prioridadEstado = [
            'PARCIAL' => 1,
            'SIN INGRESO' => 2,
            'EXCEDENTE' => 3,
            'COMPLETO' => 4,
        ];

        $produccionTerminada = $produccionTerminada
            ->sort(function ($a, $b) use ($prioridadEstado) {
                $pa = $prioridadEstado[$a->estado_control] ?? 99;
                $pb = $prioridadEstado[$b->estado_control] ?? 99;

                if ($pa !== $pb) {
                    return $pa <=> $pb;
                }

                $diasA = $a->dias_en_terminacion ?? -1;
                $diasB = $b->dias_en_terminacion ?? -1;

                if ($a->pendiente_terminar > 0 || $b->pendiente_terminar > 0) {
                    if ($diasA !== $diasB) {
                        return $diasB <=> $diasA;
                    }
                }

                return ((int) $b->nro_ot) <=> ((int) $a->nro_ot);
            })
            ->values();

        $totalIngresoTerminacion = (int) $produccionTerminada->sum('cantidad_ingreso_terminacion');
        $totalTerminado = (int) $produccionTerminada->sum('cantidad_terminada');

        // Mismo valor por definición del flujo.
        $totalEntregadoLogistica = $totalTerminado;

        $totalPendienteTerminar = (int) $produccionTerminada->sum('pendiente_terminar');
        $totalExcesoProductoTerminado = (int) $produccionTerminada->sum('exceso_producto_terminado');
        $totalOTs = $produccionTerminada->count();

        $otsParciales = $produccionTerminada
            ->where('estado_control', 'PARCIAL')
            ->count();

        $otsCompletas = $produccionTerminada
            ->where('estado_control', 'COMPLETO')
            ->count();

        $otsSinIngreso = $produccionTerminada
            ->where('estado_control', 'SIN INGRESO')
            ->count();

        $otsExcedidas = $produccionTerminada
            ->where('estado_control', 'EXCEDENTE')
            ->count();

        $porcentajeEntregadoLogistica = $totalTerminado > 0
            ? 100
            : 0;

        $porcentajeTerminado = $totalIngresoTerminacion > 0
            ? round(($totalTerminado / $totalIngresoTerminacion) * 100, 2)
            : 0;

        $pendientes = $produccionTerminada
            ->filter(function ($item) {
                return $item->pendiente_terminar > 0;
            });

        $antiguedadMaximaPendiente = $pendientes->isNotEmpty()
            ? (int) $pendientes->max('dias_en_terminacion')
            : 0;

        $otMasAntiguaPendiente = $pendientes
            ->sortByDesc('dias_en_terminacion')
            ->first();

        $conTiempo = $produccionTerminada
            ->filter(function ($item) {
                return $item->dias_en_terminacion !== null
                    && $item->estado_control !== 'SIN INGRESO';
            });

        $promedioDiasTerminacion = $conTiempo->isNotEmpty()
            ? round($conTiempo->avg('dias_en_terminacion'), 1)
            : 0;

        $porPagina = 50;
        $paginaActual = max(1, (int) $request->input('page', 1));
        $itemsPagina = $produccionTerminada
            ->slice(($paginaActual - 1) * $porPagina, $porPagina)
            ->values();

        $produccionPaginada = new LengthAwarePaginator(
            $itemsPagina,
            $produccionTerminada->count(),
            $porPagina,
            $paginaActual,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('control.terminacion', compact(
            'fechaDesde',
            'fechaHasta',
            'buscar',
            'estados',
            'produccionPaginada',
            'totalIngresoTerminacion',
            'totalTerminado',
            'totalEntregadoLogistica',
            'totalPendienteTerminar',
            'totalExcesoProductoTerminado',
            'totalOTs',
            'otsParciales',
            'otsCompletas',
            'otsSinIngreso',
            'otsExcedidas',
            'porcentajeEntregadoLogistica',
            'porcentajeTerminado',
            'antiguedadMaximaPendiente',
            'otMasAntiguaPendiente',
            'promedioDiasTerminacion'
        ));
    }

    public function detalle(Request $request, $idOt)
    {
        $fechaPt = $request->input('fecha_pt');
        $procesoLogistica = 'LOGISTICA - LOGISTICA Y DISTRIBUCION';

        $ot = DB::table('ot')
            ->where('id_ot', $idOt)
            ->select('id_ot', 'nro_ot', 'codigo', 'descripcion', 'cantidad_orden')
            ->first();

        abort_if(!$ot, 404);

        $queryDetalles = DB::table('ot_trazabilidad as tl')
            ->join('ot_logistica_detalle as d', 'd.id_trazabilidad', '=', 'tl.id_trazabilidad')
            ->where('tl.id_ot', $idOt)
            ->where('tl.proceso', $procesoLogistica);

        if ($fechaPt) {
            $queryDetalles->where('tl.fecha_proceso', '>=', $fechaPt);
        }

        $detalles = $queryDetalles
            ->select(
                'd.id',
                'd.sucursal',
                'd.cantidad',
                'tl.fecha_proceso as fecha_logistica'
            )
            ->orderBy('tl.fecha_proceso')
            ->orderBy('d.id')
            ->get();

        $remisionesPorDetalle = collect();

        if (Schema::hasTable('ot_logistica_remisiones') && $detalles->isNotEmpty()) {
            $remisionesPorDetalle = DB::table('ot_logistica_remisiones')
                ->whereIn('id_logistica_detalle', $detalles->pluck('id')->all())
                ->orderByRaw('COALESCE(fecha_remision, fecha_creacion) ASC')
                ->orderBy('serie')
                ->orderBy('numero_remision')
                ->get()
                ->groupBy('id_logistica_detalle');
        }

        foreach ($detalles as $detalle) {
            $planNormalizado = $this->normalizarDestinoMovimiento($detalle->sucursal);

            $detalle->remisiones = collect(
                $remisionesPorDetalle->get($detalle->id, collect())
            )->map(function ($remision) use ($detalle, $planNormalizado) {
                $destinoReal = $remision->sucursal_destino
                    ?: $remision->sucursal_logistica;

                $remision->destino_planificado = $detalle->sucursal;
                $remision->destino_real = $destinoReal;
                $remision->es_redireccion =
                    $this->normalizarDestinoMovimiento($destinoReal)
                    !== $planNormalizado;

                return $remision;
            });

            $detalle->cantidad_remitida = (int) $detalle->remisiones->sum('cantidad');
            $detalle->cantidad_recibida = (int) $detalle->remisiones
                ->filter(function ($remision) {
                    return !empty($remision->fecha_recepcion);
                })
                ->sum('cantidad');
            $detalle->cantidad_en_transito = max(
                0,
                $detalle->cantidad_remitida - $detalle->cantidad_recibida
            );
            $detalle->pendiente_remitir = max(
                0,
                (int) $detalle->cantidad - $detalle->cantidad_remitida
            );
        }

        $remisionesSinDetalle = collect();

        if (Schema::hasTable('ot_logistica_remisiones')) {
            $remisionesSinDetalle = DB::table('ot_logistica_remisiones')
                ->where('id_ot', $idOt)
                ->whereNull('id_logistica_detalle')
                ->orderByRaw('COALESCE(fecha_remision, fecha_creacion) ASC')
                ->orderBy('serie')
                ->orderBy('numero_remision')
                ->get();
        }

        $totalPlan = (int) $detalles->sum('cantidad');
        $totalRemitido = (int) $detalles->sum('cantidad_remitida');
        $totalRecibido = (int) $detalles->sum('cantidad_recibida');
        $totalEnTransito = (int) $detalles->sum('cantidad_en_transito');
        $totalPendiente = (int) $detalles->sum('pendiente_remitir');

        return view('control._terminacion_detalle', compact(
            'ot',
            'detalles',
            'remisionesSinDetalle',
            'totalPlan',
            'totalRemitido',
            'totalRecibido',
            'totalEnTransito',
            'totalPendiente'
        ));
    }

    private function normalizarDestinoMovimiento($valor): string
    {
        $texto = strtoupper(trim((string) $valor));

        $texto = strtr($texto, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ñ' => 'N',
        ]);

        if (strpos($texto, 'MODELO') !== false) {
            return 'MODELO';
        }

        if (strpos($texto, 'MATRIZ') !== false) {
            return 'MATRIZ';
        }

        if (strpos($texto, 'AYALA') !== false) {
            return 'AYALA';
        }

        if (strpos($texto, 'SAN LORENZO') !== false || $texto === 'SL') {
            return 'SL';
        }

        if (strpos($texto, 'SHOP SAN LO') !== false || $texto === 'SHOPP') {
            return 'SHOPP';
        }

        if (strpos($texto, 'MULTIPLAZA') !== false || $texto === 'MULTI') {
            return 'MULTI';
        }

        if (strpos($texto, 'JARDINES') !== false) {
            return 'JARDINES';
        }

        if (strpos($texto, 'MARIANO') !== false) {
            return 'MARIANO';
        }

        if (strpos($texto, 'PINEDO') !== false) {
            return 'PINEDO';
        }

        if (strpos($texto, 'BONANZA') !== false) {
            return 'BONANZA';
        }

        if (strpos($texto, 'RURAL') !== false) {
            return 'RURAL';
        }

        if (strpos($texto, 'NEMBY') !== false) {
            return 'NEMBY';
        }

        if (strpos($texto, 'LUQUE') !== false) {
            return 'LUQUE';
        }

        if (strpos($texto, 'MALL') !== false) {
            return 'MALL';
        }

        if (strpos($texto, 'L06') !== false) {
            return 'L06';
        }

        return $texto;
    }

    public function importarRemisiones(Request $request)
    {
        // Importaciones grandes: dejamos que PHP termine el trabajo aunque
        // el archivo tenga decenas de miles de líneas.
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('max_input_time', '-1');
        @ini_set('memory_limit', '1536M');
        @ignore_user_abort(true);

        DB::disableQueryLog();

        $rutaRetorno = $request->input('origen') === 'dashboard-logistica'
            ? 'dashboard.ot-logistica'
            : 'control.terminacion';

        $parametrosRetorno = array_filter([
            'fecha_desde' => $request->input('fecha_desde'),
            'fecha_hasta' => $request->input('fecha_hasta'),
        ]);

        $request->validate([
            'archivo_envios' => 'required|file|mimes:xlsx,xls,csv|max:102400',
        ]);

        if (!Schema::hasTable('ot_logistica_remisiones')) {
            return redirect()
                ->route($rutaRetorno, $parametrosRetorno)
                ->with('error', 'Primero creá la tabla ot_logistica_remisiones antes de importar ENVIOS.');
        }

        try {
            $archivo = $request->file('archivo_envios');

            Log::info('INICIO IMPORTACION ENVIOS', [
                'archivo' => $archivo->getClientOriginalName(),
                'tamano_bytes' => $archivo->getSize(),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
            ]);

            $inicio = microtime(true);
            $import = new ControlTerminacionRemisionImport();

            $extension = strtolower($archivo->getClientOriginalExtension());

            if ($extension === 'xlsx') {
                $import->importarXlsxStreaming($archivo->getRealPath());
            } else {
                // Compatibilidad para XLS/CSV pequeños.
                Excel::import($import, $archivo);
            }

            Log::info('FIN IMPORTACION ENVIOS', [
                'segundos' => round(microtime(true) - $inicio, 2),
                'procesadas' => $import->getProcesadas(),
                'vinculadas_ot' => $import->getVinculadasOt(),
                'vinculadas_logistica' => $import->getVinculadas(),
                'sin_detalle_logistico' => $import->getSinVincular(),
                'sin_ot' => $import->getSinOt(),
                'memoria_pico_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ]);

            $mensaje = 'Importación finalizada. '
                . 'Documentos: ' . $import->getDocumentosArchivo()
                . ' | Recibidos en el archivo: ' . $import->getDocumentosRecibidos()
                . ' | En tránsito: ' . $import->getDocumentosEnTransito()
                . ' | Líneas procesadas: ' . $import->getProcesadas()
                . ' | Nuevas: ' . $import->getInsertadas()
                . ' | Actualizadas: ' . $import->getActualizadas()
                . ' | Vinculadas a OT: ' . $import->getVinculadasOt()
                . ' | Vinculadas a logística: ' . $import->getVinculadas()
                . ' | Sin detalle logístico: ' . $import->getSinVincular()
                . ' | Sin OT: ' . $import->getSinOt()
                . ' | Omitidas: ' . $import->getOmitidas() . '.';

            return redirect()
                ->route($rutaRetorno, $parametrosRetorno)
                ->with('success', $mensaje);
        } catch (\Throwable $e) {
            Log::error('ERROR IMPORTACION ENVIOS', [
                'error' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
                'memoria_pico_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ]);

            report($e);

            return redirect()
                ->route($rutaRetorno, $parametrosRetorno)
                ->with('error', 'No se pudo importar el archivo: ' . $e->getMessage());
        }
    }

}
