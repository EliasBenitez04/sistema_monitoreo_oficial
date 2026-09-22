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
        $tablaRemisionesDisponible = Schema::hasTable('ot_logistica_remisiones');

        /*
         * Una sola fila operativa por OT.
         *
         * Si Producto Terminado fue registrado más de una vez para la misma OT,
         * se acumula el resultado y se conservan primera/última fecha de PT.
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
            ->orderBy('fecha_producto_terminado', 'desc')
            ->orderBy('o.nro_ot', 'desc')
            ->get();

        $idsOt = $produccionTerminada
            ->pluck('id_ot')
            ->filter()
            ->unique()
            ->values();

        $logisticaPorOt = collect();
        $remisionesPorOt = collect();

        if ($idsOt->isNotEmpty()) {
            /*
             * Resumen de logística. Solo agregados: no cargamos cada detalle ni
             * cada remisión en la pantalla principal.
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
                    DB::raw('COUNT(d.id) as destinos_logisticos')
                )
                ->get()
                ->keyBy('id_ot');

            if ($tablaRemisionesDisponible) {
                $remisionesPorOt = DB::table('ot_logistica_remisiones')
                    ->whereIn('id_ot', $idsOt->all())
                    ->groupBy('id_ot')
                    ->select(
                        'id_ot',
                        DB::raw('SUM(cantidad) as cantidad_remitida'),
                        DB::raw("SUM(CASE WHEN fecha_recepcion IS NOT NULL THEN cantidad ELSE 0 END) as cantidad_recibida"),
                        DB::raw("SUM(CASE WHEN fecha_recepcion IS NULL THEN cantidad ELSE 0 END) as cantidad_en_transito"),
                        DB::raw('COUNT(*) as lineas_remision'),
                        DB::raw("COUNT(DISTINCT CONCAT_WS('|', COALESCE(serie, ''), COALESCE(numero_remision, ''), COALESCE(CAST(cod_sucursal_destino AS TEXT), ''))) as documentos_remision")
                    )
                    ->get()
                    ->keyBy('id_ot');
            }
        }

        foreach ($produccionTerminada as $item) {
            $logistica = $logisticaPorOt->get($item->id_ot);
            $remisiones = $remisionesPorOt->get($item->id_ot);

            $item->cantidad_terminada = (int) $item->cantidad_terminada;
            $item->cantidad_logistica = (int) ($logistica->cantidad_logistica ?? 0);
            $item->primera_salida = $logistica->primera_salida ?? null;
            $item->ultima_salida = $logistica->ultima_salida ?? null;
            $item->destinos_logisticos = (int) ($logistica->destinos_logisticos ?? 0);

            $item->cantidad_remitida = (int) ($remisiones->cantidad_remitida ?? 0);
            $item->cantidad_recibida = (int) ($remisiones->cantidad_recibida ?? 0);
            $item->cantidad_en_transito = (int) ($remisiones->cantidad_en_transito ?? 0);
            $item->documentos_remision = (int) ($remisiones->documentos_remision ?? 0);

            $item->pendiente_remitir = max(
                0,
                $item->cantidad_logistica - $item->cantidad_remitida
            );

            $item->diferencia = $item->cantidad_terminada - $item->cantidad_logistica;

            if ($item->diferencia === 0) {
                $item->estado_control = 'FINALIZADO';
            } elseif ($item->cantidad_logistica === 0) {
                $item->estado_control = 'NO ENVIADO';
            } else {
                $item->estado_control = 'PARCIAL';
            }

            if ($item->cantidad_remitida <= 0) {
                $item->confirmacion_local = 'SIN REMISION';
            } elseif ($item->cantidad_en_transito > 0 && $item->cantidad_recibida > 0) {
                $item->confirmacion_local = 'PARCIAL';
            } elseif ($item->cantidad_en_transito > 0) {
                $item->confirmacion_local = 'EN TRANSITO';
            } elseif ($item->cantidad_recibida >= $item->cantidad_remitida) {
                $item->confirmacion_local = 'RECIBIDO';
            } else {
                $item->confirmacion_local = 'PARCIAL';
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
         * KPIs del período completo filtrado. Se calculan antes de paginar.
         */
        $totalTerminado = (int) $produccionTerminada->sum('cantidad_terminada');
        $totalLogistica = (int) $produccionTerminada->sum('cantidad_logistica');
        $totalRemitido = (int) $produccionTerminada->sum('cantidad_remitida');
        $totalRecibido = (int) $produccionTerminada->sum('cantidad_recibida');
        $totalEnTransito = (int) $produccionTerminada->sum('cantidad_en_transito');
        $totalPendienteRemitir = (int) $produccionTerminada->sum('pendiente_remitir');
        $totalDiferencia = $totalTerminado - $totalLogistica;
        $totalOTs = $produccionTerminada->count();

        $porcentajeEnviado = $totalTerminado > 0
            ? round(($totalLogistica / $totalTerminado) * 100, 2)
            : 0;

        /*
         * Inconsistencias únicamente del período consultado. Antes se contaba
         * toda la historia de ot_logistica_remisiones.
         */
        $remisionesSinVincular = 0;
        $remisionesSinVincularFilas = 0;

        if ($tablaRemisionesDisponible) {
            $baseSinVinculo = DB::table('ot_logistica_remisiones')
                ->whereNull('id_logistica_detalle')
                ->whereBetween(
                    DB::raw('COALESCE(fecha_remision, fecha_creacion)'),
                    [$fechaDesde, $fechaHasta]
                );

            $remisionesSinVincularFilas = (clone $baseSinVinculo)->count();

            $remisionesSinVincular = (int) (
                (clone $baseSinVinculo)
                    ->selectRaw(
                        "COUNT(DISTINCT CONCAT_WS('|', COALESCE(serie, ''), COALESCE(numero_remision, ''), COALESCE(CAST(cod_sucursal_destino AS TEXT), ''))) as total"
                    )
                    ->value('total') ?? 0
            );
        }

        /*
         * Paginación en memoria: el conjunto principal ya es una fila por OT y
         * normalmente es pequeño, mientras los detalles pesados se cargan AJAX.
         */
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
            'totalRemitido',
            'totalRecibido',
            'totalEnTransito',
            'totalPendienteRemitir',
            'totalDiferencia',
            'totalOTs',
            'porcentajeEnviado',
            'tablaRemisionesDisponible',
            'remisionesSinVincular',
            'remisionesSinVincularFilas'
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

        return view('control._terminacion_detalle', compact(
            'ot',
            'detalles',
            'remisionesSinDetalle'
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
