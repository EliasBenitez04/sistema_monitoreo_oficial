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

        $procesoProductoTerminado = 'TERMINACION - PRODUCTO TERMINADO';
        $procesoLogistica = 'LOGISTICA - LOGISTICA Y DISTRIBUCION';
        $hoy = now()->startOfDay();

        /*
         * CONTROL DE PRODUCTO TERMINADO
         *
         * Esta pantalla responde únicamente:
         * 1) ¿Cuánto terminó Terminación?
         * 2) ¿Cuánto recibió/movió Logística?
         * 3) ¿Qué OT sigue pendiente y desde cuándo?
         *
         * Remisiones y recepción de locales se consultan como trazabilidad
         * secundaria al abrir una OT, no forman parte de los KPIs principales.
         */
        $queryProduccion = DB::table('ot_trazabilidad as tp')
            ->join('ot as o', 'o.id_ot', '=', 'tp.id_ot')
            ->where('tp.proceso', $procesoProductoTerminado)
            ->whereBetween('tp.fecha_proceso', [$fechaDesde, $fechaHasta]);

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
                DB::raw('SUM(tp.resultado) as cantidad_terminada'),
                DB::raw('MIN(tp.fecha_proceso) as fecha_producto_terminado'),
                DB::raw('MAX(tp.fecha_proceso) as ultima_fecha_producto_terminado')
            )
            ->get();

        $idsOt = $produccionTerminada
            ->pluck('id_ot')
            ->filter()
            ->unique()
            ->values();

        $logisticaPorOt = collect();

        if ($idsOt->isNotEmpty()) {
            /*
             * La logística se considera a partir del primer PT del período
             * seleccionado para cada OT.
             */
            $ptMinimo = DB::table('ot_trazabilidad')
                ->where('proceso', $procesoProductoTerminado)
                ->whereBetween('fecha_proceso', [$fechaDesde, $fechaHasta])
                ->whereIn('id_ot', $idsOt->all())
                ->groupBy('id_ot')
                ->select('id_ot', DB::raw('MIN(fecha_proceso) as fecha_pt'));

            $logisticaPorOt = DB::table('ot_trazabilidad as tl')
                ->join('ot_logistica_detalle as d', 'd.id_trazabilidad', '=', 'tl.id_trazabilidad')
                ->joinSub($ptMinimo, 'ptx', function ($join) {
                    $join->on('ptx.id_ot', '=', 'tl.id_ot')
                        ->whereColumn('tl.fecha_proceso', '>=', 'ptx.fecha_pt');
                })
                ->where('tl.proceso', $procesoLogistica)
                ->whereIn('tl.id_ot', $idsOt->all())
                ->groupBy('tl.id_ot')
                ->select(
                    'tl.id_ot',
                    DB::raw('SUM(d.cantidad) as cantidad_logistica'),
                    DB::raw('MIN(tl.fecha_proceso) as primera_salida'),
                    DB::raw('MAX(tl.fecha_proceso) as ultima_salida'),
                    DB::raw('COUNT(DISTINCT d.sucursal) as destinos_logisticos'),
                    DB::raw('COUNT(DISTINCT tl.id_trazabilidad) as movimientos_logisticos')
                )
                ->get()
                ->keyBy('id_ot');
        }

        foreach ($produccionTerminada as $item) {
            $logistica = $logisticaPorOt->get($item->id_ot);

            $item->cantidad_terminada = (int) $item->cantidad_terminada;
            $item->cantidad_logistica = (int) ($logistica->cantidad_logistica ?? 0);
            $item->primera_salida = $logistica->primera_salida ?? null;
            $item->ultima_salida = $logistica->ultima_salida ?? null;
            $item->destinos_logisticos = (int) ($logistica->destinos_logisticos ?? 0);
            $item->movimientos_logisticos = (int) ($logistica->movimientos_logisticos ?? 0);

            $item->pendiente_logistica = max(
                0,
                $item->cantidad_terminada - $item->cantidad_logistica
            );

            $item->exceso_logistica = max(
                0,
                $item->cantidad_logistica - $item->cantidad_terminada
            );

            $fechaPt = CarbonCarbon::parse($item->fecha_producto_terminado)->startOfDay();

            if ($item->primera_salida) {
                $fechaPrimeraSalida = CarbonCarbon::parse($item->primera_salida)->startOfDay();
                $item->dias_primera_salida = max(0, $fechaPt->diffInDays($fechaPrimeraSalida, false));
            } else {
                $item->dias_primera_salida = null;
            }

            /*
             * Antigüedad operativa:
             * - si falta entregar, días desde PT hasta hoy;
             * - si ya se entregó, días hasta la primera salida de Logística.
             */
            if ($item->pendiente_logistica > 0) {
                $item->dias_espera = max(0, $fechaPt->diffInDays($hoy, false));
            } elseif ($item->primera_salida) {
                $item->dias_espera = $item->dias_primera_salida;
            } else {
                $item->dias_espera = 0;
            }

            if ($item->cantidad_logistica <= 0) {
                $item->estado_control = 'SIN ENVIAR';
            } elseif ($item->cantidad_logistica < $item->cantidad_terminada) {
                $item->estado_control = 'PARCIAL';
            } elseif ($item->cantidad_logistica > $item->cantidad_terminada) {
                $item->estado_control = 'EXCEDENTE';
            } else {
                $item->estado_control = 'ENTREGADO';
            }
        }

        if (!empty($estados)) {
            $produccionTerminada = $produccionTerminada
                ->filter(function ($item) use ($estados) {
                    return in_array($item->estado_control, $estados, true);
                })
                ->values();
        }

        /*
         * Los pendientes más antiguos aparecen primero.
         * Luego parciales/excedentes y al final las OTs ya entregadas.
         */
        $prioridadEstado = [
            'SIN ENVIAR' => 1,
            'PARCIAL' => 2,
            'EXCEDENTE' => 3,
            'ENTREGADO' => 4,
        ];

        $produccionTerminada = $produccionTerminada
            ->sort(function ($a, $b) use ($prioridadEstado) {
                $pa = $prioridadEstado[$a->estado_control] ?? 99;
                $pb = $prioridadEstado[$b->estado_control] ?? 99;

                if ($pa !== $pb) {
                    return $pa <=> $pb;
                }

                if ($a->pendiente_logistica > 0 || $b->pendiente_logistica > 0) {
                    if ($a->dias_espera !== $b->dias_espera) {
                        return $b->dias_espera <=> $a->dias_espera;
                    }
                }

                if ((string) $a->fecha_producto_terminado !== (string) $b->fecha_producto_terminado) {
                    return strcmp(
                        (string) $a->fecha_producto_terminado,
                        (string) $b->fecha_producto_terminado
                    );
                }

                return ((int) $b->nro_ot) <=> ((int) $a->nro_ot);
            })
            ->values();

        /*
         * KPIs exclusivos de Terminación -> Logística.
         */
        $totalTerminado = (int) $produccionTerminada->sum('cantidad_terminada');
        $totalLogistica = (int) $produccionTerminada->sum('cantidad_logistica');
        $totalPendienteLogistica = (int) $produccionTerminada->sum('pendiente_logistica');
        $totalExcesoLogistica = (int) $produccionTerminada->sum('exceso_logistica');
        $totalOTs = $produccionTerminada->count();

        $otsSinEnviar = $produccionTerminada
            ->where('estado_control', 'SIN ENVIAR')
            ->count();

        $otsParciales = $produccionTerminada
            ->where('estado_control', 'PARCIAL')
            ->count();

        $otsEntregadas = $produccionTerminada
            ->where('estado_control', 'ENTREGADO')
            ->count();

        $otsExcedidas = $produccionTerminada
            ->where('estado_control', 'EXCEDENTE')
            ->count();

        $otsPendientes = $otsSinEnviar + $otsParciales;

        $porcentajeEntregado = $totalTerminado > 0
            ? round(($totalLogistica / $totalTerminado) * 100, 2)
            : 0;

        $pendientes = $produccionTerminada
            ->filter(function ($item) {
                return $item->pendiente_logistica > 0;
            });

        $antiguedadMaximaPendiente = $pendientes->isNotEmpty()
            ? (int) $pendientes->max('dias_espera')
            : 0;

        $otMasAntiguaPendiente = $pendientes
            ->sortByDesc('dias_espera')
            ->first();

        $conPrimeraSalida = $produccionTerminada
            ->filter(function ($item) {
                return $item->dias_primera_salida !== null;
            });

        $promedioDiasPrimeraSalida = $conPrimeraSalida->isNotEmpty()
            ? round($conPrimeraSalida->avg('dias_primera_salida'), 1)
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
            'totalTerminado',
            'totalLogistica',
            'totalPendienteLogistica',
            'totalExcesoLogistica',
            'totalOTs',
            'otsSinEnviar',
            'otsParciales',
            'otsEntregadas',
            'otsExcedidas',
            'otsPendientes',
            'porcentajeEntregado',
            'antiguedadMaximaPendiente',
            'otMasAntiguaPendiente',
            'promedioDiasPrimeraSalida'
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

        $request->validate([
            'archivo_envios' => 'required|file|mimes:xlsx,xls,csv|max:102400',
        ]);

        if (!Schema::hasTable('ot_logistica_remisiones')) {
            return redirect()
                ->route('control.terminacion', $request->only('fecha_desde', 'fecha_hasta'))
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
                ->route('control.terminacion', $request->only('fecha_desde', 'fecha_hasta'))
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
                ->route('control.terminacion', $request->only('fecha_desde', 'fecha_hasta'))
                ->with('error', 'No se pudo importar el archivo: ' . $e->getMessage());
        }
    }

}
