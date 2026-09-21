<?php

namespace App\Http\Controllers;

use App\Models\RedistribucionLote;
use App\Models\RedistribucionSugerida;
use App\Models\RedistribucionProceso;
use App\Models\RedistribucionProcesoDetalle;
use App\Models\StockVentasSucursal;
use App\Models\RedistribucionConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\LoteRedistribucionExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\RedistribucionRemisionImport;

class RedistribucionSugeridaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        // VER
        $this->middleware('permission:redistribucionsugerencia index')
            ->only([
                'index',
                'lotes',
                'proceso',
                'lote',
                'show',
            ]);

        // CREAR / GENERAR
        $this->middleware('permission:redistribucionsugerencia create')
            ->only([
                'analizar',
                'generarLote',
            ]);

        // MODIFICAR / PROCESAR
        $this->middleware('permission:redistribucionsugerencia update')
            ->only([
                'aprobar',
                'rechazar',
                'procesarLote',
                'finalizarLote',
                'importarRemisiones',
            ]);
    }


    /*
    |--------------------------------------------------------------------------
    | PANTALLA PRINCIPAL
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $periodos = StockVentasSucursal::query()
            ->whereNotNull('periodo')
            ->where('periodo', '<>', '')
            ->distinct()
            ->orderByDesc('periodo')
            ->pluck('periodo');

        $gruposPlan = StockVentasSucursal::query()
            ->whereNotNull('grupo_plan')
            ->where('grupo_plan', '<>', '')
            ->distinct()
            ->orderBy('grupo_plan')
            ->pluck('grupo_plan');

        $lineas = StockVentasSucursal::query()
            ->whereNotNull('linea')
            ->where('linea', '<>', '')
            ->distinct()
            ->orderBy('linea')
            ->pluck('linea');

        $temporadas = StockVentasSucursal::query()
            ->whereNotNull('temporada')
            ->where('temporada', '<>', '')
            ->distinct()
            ->orderBy('temporada')
            ->pluck('temporada');

        /*
         * Sucursales
         */
        $sucursales = StockVentasSucursal::query()
            ->join(
                'sucursal',
                'stock_ventas_sucursales.sucursal_id',
                '=',
                'sucursal.cod_suc'
            )
            ->select(
                'sucursal.cod_suc',
                'sucursal.suc_descri'
            )
            ->distinct()
            ->orderBy('sucursal.suc_descri')
            ->get();

        /*
         * Sugerencias
         */
        $sugerencias = RedistribucionSugerida::with([
            'origen',
            'destino'
        ])
            ->whereIn('estado', [
                'PENDIENTE',
                'RECHAZADA'
            ])
            ->orderBy('codigo')
            ->orderBy('fecha_generacion', 'desc')
            ->get();

        return view(
            'redistribucion_sugeridas.index',
            compact(
                'periodos',
                'gruposPlan',
                'lineas',
                'temporadas',
                'sucursales',
                'sugerencias'
            )
        );
    }

    /**
     * =========================================================
     * ANALIZAR REDISTRIBUCIÓN
     * =========================================================
     */
    public function analizar(Request $request)
    {
        $request->validate([
            'periodo' => 'required|string|max:40',
            'grupo_plan' => 'nullable|array',
            'grupo_plan.*' => 'required|string|distinct',
            'linea' => 'nullable|string',
            'temporada' => 'nullable|string',
            'fecha_desde' => 'nullable|required_with:fecha_hasta|date_format:Y-m-d',
            'fecha_hasta' => 'nullable|required_with:fecha_desde|date_format:Y-m-d|after_or_equal:fecha_desde',
            'dias_cobertura' => 'nullable|integer|min:1|max:90',
            'seguridad_porcentaje' => 'nullable|integer|min:0|max:100',
            'stock_minimo_origen' => 'nullable|integer|min:1|max:100',
            'dias_bloqueo' => 'nullable|integer|min:0|max:365',
        ]);

        try {
            $periodo = trim((string) $request->input('periodo'));
            $hoy = now()->startOfDay();

            if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
                $fechaDesde = \Carbon\Carbon::createFromFormat('!Y-m-d', $request->input('fecha_desde'));
                $fechaHasta = \Carbon\Carbon::createFromFormat('!Y-m-d', $request->input('fecha_hasta'));
            } elseif (preg_match('/^(\d{4}-\d{2}-\d{2})(?:[ T]\d{2}:\d{2}:\d{2})?$/', $periodo, $partesPeriodo)) {
                $fechaReporte = \Carbon\Carbon::createFromFormat('!Y-m-d', $partesPeriodo[1]);
                if ($fechaReporte->format('Y-m-d') !== $partesPeriodo[1]) {
                    throw new \InvalidArgumentException('La fecha del periodo no es valida.');
                }
                $fechaHasta = $fechaReporte->copy()->subDay();
                $fechaDesde = $fechaHasta->copy()->startOfMonth();
            } else {
                throw new \InvalidArgumentException(
                    'Para este formato de periodo, indique fecha_desde y fecha_hasta (AAAA-MM-DD).'
                );
            }

            if ($fechaDesde->gt($fechaHasta) || $fechaDesde->format('Y-m') !== $fechaHasta->format('Y-m')) {
                throw new \InvalidArgumentException('El monitoreo debe corresponder a un mismo mes y contener al menos un dia.');
            }

            $diasMonitoreados = (int) $fechaDesde->diffInDays($fechaHasta) + 1;
            $diasRestantes = $fechaHasta->daysInMonth - $fechaHasta->day;

            /*
             * Configuración operativa centralizada.
             *
             * Para no alterar la estructura actual de redistribucion_config:
             * - metodo_demanda: conserva el valor PERIODO exigido por la BD
             * - porcentaje_conservar_origen: 0 = cobertura automática, 1 = fija
             * - porcentaje_demanda: margen de seguridad (20 = +20%)
             * - cantidad_maxima: días de cobertura cuando el método es FIJO
             * - stock_minimo: reserva mínima
             * - venta_minima: venta mínima para que un local pueda ser destino
             */
            $configuracion = RedistribucionConfig::where('activo', true)
                ->orderByDesc('id')
                ->first();

            $configuracionOperativa = (bool) $configuracion;

            $metodoCobertura = $configuracionOperativa
                && (float) $configuracion->porcentaje_conservar_origen >= 1
                    ? 'COBERTURA_FIJA'
                    : 'COBERTURA_AUTO';

            $diasCoberturaConfigurados = $configuracionOperativa
                ? max(1, min(90, (int) $configuracion->cantidad_maxima))
                : 7;

            $seguridadConfigurada = $configuracionOperativa
                ? max(0, min(100, (int) round((float) $configuracion->porcentaje_demanda)))
                : 20;

            $minimoOrigenConfigurado = $configuracionOperativa
                ? max(1, min(100, (int) $configuracion->stock_minimo))
                : 1;

            $ventaMinima = $configuracionOperativa
                ? max(1, (int) $configuracion->venta_minima)
                : 1;

            $diasBloqueoConfigurados = $configuracionOperativa
                ? max(0, min(365, (int) $configuracion->dias_bloqueo))
                : 30;

            $bloquearPendientes = $configuracionOperativa
                ? (bool) $configuracion->bloquear_pendientes
                : true;

            $bloquearEnProceso = $configuracionOperativa
                ? (bool) $configuracion->bloquear_en_proceso
                : true;

            $bloquearFinalizadosRecientes = $configuracionOperativa
                ? (bool) $configuracion->bloquear_finalizados_recientes
                : true;

            // Mantiene compatibilidad: si una llamada antigua envía estos campos,
            // el valor enviado tiene prioridad sobre la configuración guardada.
            $diasCoberturaAutomaticos = $diasRestantes > 0 ? $diasRestantes : 7;
            $diasCobertura = $request->filled('dias_cobertura')
                ? (int) $request->input('dias_cobertura')
                : ($metodoCobertura === 'COBERTURA_FIJA'
                    ? $diasCoberturaConfigurados
                    : $diasCoberturaAutomaticos);

            $seguridad = $request->filled('seguridad_porcentaje')
                ? (int) $request->input('seguridad_porcentaje')
                : $seguridadConfigurada;

            $minimoOrigen = $request->filled('stock_minimo_origen')
                ? (int) $request->input('stock_minimo_origen')
                : $minimoOrigenConfigurado;

            $diasBloqueo = $request->filled('dias_bloqueo')
                ? (int) $request->input('dias_bloqueo')
                : $diasBloqueoConfigurados;

            /* Motor puro: calcula un codigo completo antes de grabar sugerencias.
         * Una sucursal solo puede ser origen O destino para ese codigo.
         * Se usan unidades enteras y el mismo stock objetivo en ambos lados.
         */
            $planificarCodigo = static function (array $locales) use (
                $diasMonitoreados,
                $diasCobertura,
                $seguridad,
                $minimoOrigen,
                $ventaMinima
            ): array {
                $origenes = [];
                $destinos = [];
                $movimientos = [];
                $denominador = $diasMonitoreados * 100;

                foreach ($locales as $local) {
                    $numerador = $local['venta'] * $diasCobertura * (100 + $seguridad);
                    // ceil(venta / diasMonitoreados * diasCobertura * (1 + margen))
                    // sin errores de redondeo binario en multiplos exactos.
                    $objetivo = max($minimoOrigen, intdiv($numerador + $denominador - 1, $denominador));
                    $local['objetivo'] = $objetivo;
                    $local['saldo'] = $local['stock'];
                    if ($local['stock'] > $objetivo) {
                        $local['exceso'] = $local['stock'] - $objetivo;
                        $origenes[] = $local;
                    } elseif ($local['venta'] >= $ventaMinima && $local['stock'] < $objetivo) {
                        $local['necesidad'] = $objetivo - $local['stock'];
                        $destinos[] = $local;
                    }
                }

                // Primero liberar prendas sin ventas; luego, mayor cobertura.
                // Agotar el excedente de un origen antes de abrir otro reduce rutas.
                usort($origenes, static function ($a, $b) {
                    if (($a['venta'] === 0) !== ($b['venta'] === 0)) {
                        return $a['venta'] === 0 ? -1 : 1;
                    }
                    if ($a['venta'] > 0 && $b['venta'] > 0) {
                        $comparacion = ($b['stock'] * $a['venta']) <=> ($a['stock'] * $b['venta']);
                        if ($comparacion !== 0) {
                            return $comparacion;
                        }
                    }
                    return ($b['exceso'] <=> $a['exceso'])
                        ?: ($a['venta'] <=> $b['venta'])
                        ?: ($a['sucursal_id'] <=> $b['sucursal_id']);
                });

                $indiceOrigen = 0;
                while ($indiceOrigen < count($origenes)) {
                    if ($origenes[$indiceOrigen]['exceso'] <= 0) {
                        $indiceOrigen++;
                        continue;
                    }

                    $mejorDestino = null;
                    foreach ($destinos as $indice => $destino) {
                        if ($destino['necesidad'] <= 0) {
                            continue;
                        }
                        if ($mejorDestino === null) {
                            $mejorDestino = $indice;
                            continue;
                        }
                        $mejor = $destinos[$mejorDestino];
                        // Menor stock/venta primero; al empatar, mayor venta.
                        // El factor diasMonitoreados es comun y se cancela.
                        $comparacion = ($destino['saldo'] * $mejor['venta'])
                            <=> ($mejor['saldo'] * $destino['venta']);
                        if ($comparacion < 0 || ($comparacion === 0 && (
                            $destino['venta'] > $mejor['venta'] || (
                                $destino['venta'] === $mejor['venta']
                                && $destino['sucursal_id'] < $mejor['sucursal_id']
                            )
                        ))) {
                            $mejorDestino = $indice;
                        }
                    }
                    if ($mejorDestino === null) {
                        break;
                    }

                    $clave = $indiceOrigen . ':' . $mejorDestino;
                    if (!isset($movimientos[$clave])) {
                        $movimientos[$clave] = ['origen' => $indiceOrigen, 'destino' => $mejorDestino, 'cantidad' => 0];
                    }
                    // Repartir por unidad evita que un local absorba todo al inicio.
                    // Luego se consolida en una sola sugerencia por codigo/ruta.
                    $movimientos[$clave]['cantidad']++;
                    $origenes[$indiceOrigen]['exceso']--;
                    $origenes[$indiceOrigen]['saldo']--;
                    $destinos[$mejorDestino]['necesidad']--;
                    $destinos[$mejorDestino]['saldo']++;
                }

                $resultado = [];
                foreach ($movimientos as $movimiento) {
                    $origen = $origenes[$movimiento['origen']];
                    $destino = $destinos[$movimiento['destino']];
                    if (
                        $origen['sucursal_id'] === $destino['sucursal_id']
                        || $origen['saldo'] < $origen['objetivo']
                        || $destino['venta'] < $ventaMinima || $destino['saldo'] > $destino['objetivo']
                    ) {
                        throw new \LogicException('El plan no respeta las reservas o los limites de stock.');
                    }
                    $resultado[] = [
                        'origen' => $origen,
                        'destino' => $destino,
                        'cantidad' => $movimiento['cantidad'],
                    ];
                }
                return [
                    'movimientos' => $resultado,
                    'faltante' => array_sum(array_column($destinos, 'necesidad')),
                ];
            };

            $resumen = DB::transaction(function () use (
                $request,
                $periodo,
                $hoy,
                $fechaDesde,
                $fechaHasta,
                $diasMonitoreados,
                $diasCobertura,
                $seguridad,
                $diasBloqueo,
                $bloquearPendientes,
                $bloquearEnProceso,
                $bloquearFinalizadosRecientes,
                $planificarCodigo
            ) {
                // Serializa las ejecuciones de ESTE metodo en PostgreSQL 9.5+.
                // Aprobar/generar lote tambien debe revalidar stock y estado.
                if (DB::connection()->getDriverName() === 'pgsql') {
                    DB::select('SELECT pg_advisory_xact_lock(21092026, 1)');
                }

                $query = StockVentasSucursal::where('periodo', $periodo);
                $gruposSeleccionados = $request->input('grupo_plan') ?? [];
                if (!empty($gruposSeleccionados)) {
                    $query->whereIn('grupo_plan', $gruposSeleccionados);
                }
                foreach (['linea', 'temporada'] as $filtro) {
                    if ($request->filled($filtro)) {
                        $query->where($filtro, $request->input($filtro));
                    }
                }
                $datos = $query->select(['codigo', 'sucursal_id', 'cant_vta', 'stock_actual'])
                    ->orderBy('codigo')->orderBy('sucursal_id')->lockForUpdate()->toBase()->get();
                if ($datos->isEmpty()) {
                    return ['sin_datos' => true];
                }

                $porCodigo = [];
                $invalidos = [];
                $duplicadosIguales = 0;
                $filasSinCodigo = 0;
                foreach ($datos as $item) {
                    $codigo = (string) $item->codigo;
                    if (trim($codigo) === '') {
                        $filasSinCodigo++;
                        continue;
                    }
                    // Prefijo para preservar codigos numericos y ceros iniciales.
                    $claveCodigo = 'sku:' . $codigo;
                    if (!isset($porCodigo[$claveCodigo])) {
                        $porCodigo[$claveCodigo] = ['codigo' => $codigo, 'locales' => []];
                    }
                    $valido = trim($codigo) === $codigo;
                    foreach (['sucursal_id', 'cant_vta', 'stock_actual'] as $campo) {
                        $valor = $item->{$campo};
                        $valido = $valido && is_numeric($valor) && is_finite((float) $valor)
                            && (float) $valor >= 0 && floor((float) $valor) === (float) $valor;
                    }
                    if (!$valido || (int) $item->sucursal_id < 1) {
                        $invalidos[$claveCodigo] = true;
                        continue;
                    }
                    $local = [
                        'sucursal_id' => (int) $item->sucursal_id,
                        'venta' => (int) $item->cant_vta,
                        'stock' => (int) $item->stock_actual,
                    ];
                    $anterior = $porCodigo[$claveCodigo]['locales'][$local['sucursal_id']] ?? null;
                    if ($anterior !== null) {
                        if ($anterior !== $local) {
                            // Dos saldos diferentes no se suman ni se adivinan.
                            $invalidos[$claveCodigo] = true;
                        } else {
                            $duplicadosIguales++;
                        }
                        continue;
                    }
                    $porCodigo[$claveCodigo]['locales'][$local['sucursal_id']] = $local;
                }
                unset($datos);
                foreach ($invalidos as $clave => $_) {
                    unset($porCodigo[$clave]);
                }
                $codigos = array_column(array_values($porCodigo), 'codigo');
                if (empty($codigos)) {
                    return ['sin_validos' => true];
                }

                // Regenerar solo borradores de hoy, de los codigos validos del filtro.
                // No borrar otros grupos ni sugerencias aprobadas/procesadas.
                foreach (array_chunk($codigos, 500) as $bloque) {
                    RedistribucionSugerida::whereIn('codigo', $bloque)
                        ->where('estado', 'PENDIENTE')
                        ->where('fecha_generacion', '>=', $hoy)
                        ->where('fecha_generacion', '<', $hoy->copy()->addDay())
                        ->delete();
                }

                // Bloqueo por codigo Y sucursal: las otras sucursales siguen operando.
                // Se congela el nodo para no asumir si la mercaderia ya fue descontada
                // o recibida. No se vuelve a descontar una remision del stock importado.
                $bloqueados = [];
                $marcarBloqueados = static function ($registros) use (&$bloqueados) {
                    foreach ($registros as $registro) {
                        foreach (['sucursal_origen', 'sucursal_destino'] as $campo) {
                            $id = (int) $registro->{$campo};
                            if ($id > 0) {
                                $bloqueados['sku:' . (string) $registro->codigo][$id] = true;
                            }
                        }
                    }
                };
                $tablaDetalle = (new RedistribucionProcesoDetalle())->getTable();
                foreach (array_chunk($codigos, 500) as $bloque) {
                    if ($bloquearPendientes) {
                        $marcarBloqueados(RedistribucionSugerida::whereIn('codigo', $bloque)
                            ->whereIn('estado', ['PENDIENTE', 'APROBADO', 'APROBADA'])
                            ->lockForUpdate()
                            ->get(['codigo', 'sucursal_origen', 'sucursal_destino']));
                    }

                    if ($bloquearEnProceso) {
                        $marcarBloqueados(RedistribucionSugerida::whereIn('codigo', $bloque)
                            ->whereIn('estado', ['EN PROCESO', 'EN_PROCESO'])
                            ->lockForUpdate()
                            ->get(['codigo', 'sucursal_origen', 'sucursal_destino']));

                        $marcarBloqueados(RedistribucionProcesoDetalle::whereIn('codigo', $bloque)
                            ->whereIn('estado', ['PENDIENTE', 'EN PROCESO', 'EN_PROCESO'])
                            ->lockForUpdate()
                            ->get(['codigo', 'sucursal_origen', 'sucursal_destino']));
                    }

                    if ($bloquearFinalizadosRecientes && $diasBloqueo > 0) {
                        $marcarBloqueados(RedistribucionProcesoDetalle::query()
                            ->leftJoin('redistribucion_lote as rl', 'rl.id', '=', $tablaDetalle . '.lote_id')
                            ->whereIn($tablaDetalle . '.codigo', $bloque)
                            ->whereIn($tablaDetalle . '.estado', ['FINALIZADO', 'REALIZADO'])
                            ->where(function ($q) use ($hoy, $diasBloqueo) {
                                $q->where('rl.fecha_finalizacion', '>=', $hoy->copy()->subDays($diasBloqueo))
                                    ->orWhereNull('rl.fecha_finalizacion');
                            })
                            ->get([
                                $tablaDetalle . '.codigo',
                                $tablaDetalle . '.sucursal_origen',
                                $tablaDetalle . '.sucursal_destino',
                            ]));
                    }
                }

                $pendientesInsertar = [];
                $cantidadSugerencias = 0;
                $unidades = 0;
                $faltante = 0;
                $nodosBloqueados = 0;
                $fechaGeneracion = now();
                foreach ($porCodigo as $claveCodigo => $grupo) {
                    $locales = [];
                    foreach ($grupo['locales'] as $id => $local) {
                        if (isset($bloqueados[$claveCodigo][$id])) {
                            $nodosBloqueados++;
                            continue;
                        }
                        $locales[] = $local;
                    }
                    $plan = $planificarCodigo($locales);
                    $faltante += $plan['faltante'];
                    foreach ($plan['movimientos'] as $movimiento) {
                        $origen = $movimiento['origen'];
                        $destino = $movimiento['destino'];
                        $pendientesInsertar[] = [
                            'codigo' => $grupo['codigo'],
                            'sucursal_origen' => $origen['sucursal_id'],
                            'sucursal_destino' => $destino['sucursal_id'],
                            'cantidad' => $movimiento['cantidad'],
                            'stock_origen' => $origen['stock'],
                            'stock_destino' => $destino['stock'],
                            'venta_origen' => $origen['venta'],
                            'venta_destino' => $destino['venta'],
                            'motivo' => sprintf(
                                'Ventas %s-%s; cubrir %dd +%d%%. Origen reserva %d, saldo %d. Destino objetivo %d, saldo %d, ventas %d.',
                                $fechaDesde->format('d/m/Y'),
                                $fechaHasta->format('d/m/Y'),
                                $diasCobertura,
                                $seguridad,
                                $origen['objetivo'],
                                $origen['saldo'],
                                $destino['objetivo'],
                                $destino['saldo'],
                                $destino['venta']
                            ),
                            'estado' => 'PENDIENTE',
                            'fecha_generacion' => $fechaGeneracion,
                        ];
                        $cantidadSugerencias++;
                        $unidades += $movimiento['cantidad'];
                        if (count($pendientesInsertar) >= 500) {
                            RedistribucionSugerida::insert($pendientesInsertar);
                            $pendientesInsertar = [];
                        }
                    }
                }
                if (!empty($pendientesInsertar)) {
                    RedistribucionSugerida::insert($pendientesInsertar);
                }

                $resumen = [
                    'sugerencias' => $cantidadSugerencias,
                    'unidades' => $unidades,
                    'faltante' => $faltante,
                    'nodos_bloqueados' => $nodosBloqueados,
                    'codigos_invalidos' => count($invalidos),
                    'filas_sin_codigo' => $filasSinCodigo,
                    'duplicados_iguales' => $duplicadosIguales,
                    'periodo' => $periodo,
                    'grupos_plan' => $gruposSeleccionados,
                    'fecha_desde' => $fechaDesde->toDateString(),
                    'fecha_hasta' => $fechaHasta->toDateString(),
                    'dias_monitoreados' => $diasMonitoreados,
                    'dias_cobertura' => $diasCobertura,
                    'seguridad_porcentaje' => $seguridad,
                    'stock_minimo_origen' => $minimoOrigen,
                    'venta_minima' => $ventaMinima,
                    'dias_bloqueo' => $diasBloqueo,
                    'metodo_cobertura' => $metodoCobertura,
                ];
                return $resumen;
            }, 3);

            if (!empty($resumen['sin_datos'])) {
                return redirect()->route('RedistribucionSugeridas.index')
                    ->with('warning', 'No se encontraron datos para ese periodo y filtros.');
            }
            if (!empty($resumen['sin_validos'])) {
                return redirect()->route('RedistribucionSugeridas.index')
                    ->with('warning', 'No hay codigos validos. Revise saldos negativos, datos vacios y duplicados con valores diferentes.');
            }

            Log::info('Analisis de redistribucion por cobertura', $resumen);
            $mensaje = sprintf(
                'Analisis %s al %s (%d dias). %d sugerencias, %d prendas. Cobertura: %d dias + %d%%. Necesidad sin cubrir en locales habilitados: %d prendas. Bloqueos: %d combinaciones codigo/local.',
                $fechaDesde->format('d/m/Y'),
                $fechaHasta->format('d/m/Y'),
                $diasMonitoreados,
                $resumen['sugerencias'],
                $resumen['unidades'],
                $diasCobertura,
                $seguridad,
                $resumen['faltante'],
                $resumen['nodos_bloqueados']
            );
            if ($resumen['codigos_invalidos'] > 0 || $resumen['filas_sin_codigo'] > 0) {
                $mensaje .= sprintf(
                    ' Omitidos por datos invalidos: %d codigos y %d filas sin codigo.',
                    $resumen['codigos_invalidos'],
                    $resumen['filas_sin_codigo']
                );
            }
            if ($resumen['duplicados_iguales'] > 0) {
                $mensaje .= ' Duplicados identicos ignorados: ' . $resumen['duplicados_iguales'] . '.';
            }
            return redirect()->route('RedistribucionSugeridas.index')
                ->with($resumen['sugerencias'] > 0 ? 'success' : 'warning', $mensaje);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('RedistribucionSugeridas.index')->with('warning', $e->getMessage());
        } catch (\Throwable $e) {
            // DB::transaction revierte borrado e inserciones si falla cualquier paso.
            Log::error('Error al analizar redistribucion sugerida', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return redirect()->route('RedistribucionSugeridas.index')
                ->with('error', 'No se pudo completar el analisis. No se guardaron cambios; revise el registro de errores.');
        }
    }

    // public function analizar(Request $request)
    // {
    //     $request->validate([
    //         'periodo'     => 'required',
    //         'grupo_plan'  => 'nullable',
    //         'linea'       => 'nullable',
    //         'temporada'   => 'nullable',
    //     ]);

    //     DB::beginTransaction();

    //     try {

    //         $fechaGeneracion = now();

    //         /**
    //          * =====================================================
    //          * 1. ELIMINAR SUGERENCIAS PENDIENTES GENERADAS HOY
    //          * =====================================================
    //          *
    //          * Esto NO elimina:
    //          *
    //          * - APROBADAS
    //          * - RECHAZADAS
    //          *
    //          * Las rechazadas quedan históricamente registradas,
    //          * pero NO bloquean futuros análisis.
    //          */
    //         $inicioDia = $fechaGeneracion->copy()->startOfDay();
    //         $finDia    = $fechaGeneracion->copy()->endOfDay();

    //         RedistribucionSugerida::whereBetween(
    //             'fecha_generacion',
    //             [$inicioDia, $finDia]
    //         )
    //             ->where('estado', 'PENDIENTE')
    //             ->delete();


    //         /**
    //          * =====================================================
    //          * 2. OBTENER STOCK Y VENTAS
    //          * =====================================================
    //          */
    //         $query = StockVentasSucursal::query()
    //             ->where('periodo', $request->periodo);

    //         if ($request->filled('grupo_plan')) {
    //             $query->where(
    //                 'grupo_plan',
    //                 $request->grupo_plan
    //             );
    //         }

    //         if ($request->filled('linea')) {
    //             $query->where(
    //                 'linea',
    //                 $request->linea
    //             );
    //         }

    //         if ($request->filled('temporada')) {
    //             $query->where(
    //                 'temporada',
    //                 $request->temporada
    //             );
    //         }

    //         $datos = $query->get();


    //         /**
    //          * =====================================================
    //          * 3. VALIDAR DATOS
    //          * =====================================================
    //          */
    //         if ($datos->isEmpty()) {

    //             DB::rollBack();

    //             return back()
    //                 ->withInput()
    //                 ->with(
    //                     'error',
    //                     'No existen datos para los filtros seleccionados.'
    //                 );
    //         }


    //         /**
    //          * =====================================================
    //          * 4. CÓDIGOS BLOQUEADOS
    //          * =====================================================
    //          *
    //          * REGLA DEL NEGOCIO:
    //          *
    //          * A) PENDIENTE
    //          *    -> BLOQUEADO
    //          *
    //          * B) EN PROCESO
    //          *    -> BLOQUEADO
    //          *
    //          * C) FINALIZADO MENOS DE 30 DÍAS
    //          *    -> BLOQUEADO
    //          *
    //          * D) FINALIZADO HACE 30 DÍAS O MÁS
    //          *    -> DISPONIBLE NUEVAMENTE
    //          *
    //          * E) RECHAZADA
    //          *    -> NUNCA BLOQUEA
    //          *
    //          * F) APROBADA
    //          *    -> No se utiliza directamente para bloquear.
    //          *       Una aprobada ya genera un detalle de proceso,
    //          *       por lo que el bloqueo se controla mediante
    //          *       RedistribucionProcesoDetalle.
    //          */


    //         /**
    //          * =====================================================
    //          * 4.1 CÓDIGOS EN PROCESOS ACTIVOS
    //          * =====================================================
    //          *
    //          * PENDIENTE:
    //          * Ya fue aprobado y está esperando lote.
    //          *
    //          * EN PROCESO:
    //          * Está siendo procesado.
    //          */
    //         $codigosActivos = RedistribucionProcesoDetalle::query()
    //             ->whereIn('estado', [
    //                 'PENDIENTE',
    //                 'EN PROCESO'
    //             ])
    //             ->pluck('codigo')
    //             ->unique()
    //             ->values();


    //         $fechaLimite = now()->subDays(30);

    //         $codigosFinalizadosRecientes =
    //             RedistribucionProcesoDetalle::query()
    //             ->join(
    //                 'redistribucion_lote',
    //                 'redistribucion_proceso_detalle.lote_id',
    //                 '=',
    //                 'redistribucion_lote.id'
    //             )
    //             ->where(
    //                 'redistribucion_lote.estado',
    //                 'FINALIZADO'
    //             )
    //             ->whereNotNull(
    //                 'redistribucion_lote.fecha_finalizacion'
    //             )
    //             ->where(
    //                 'redistribucion_lote.fecha_finalizacion',
    //                 '>=',
    //                 $fechaLimite
    //             )
    //             ->pluck(
    //                 'redistribucion_proceso_detalle.codigo'
    //             )
    //             ->unique()
    //             ->values();


    //         /**
    //          * =====================================================
    //          * 4.3 UNIFICAR BLOQUEADOS
    //          * =====================================================
    //          */
    //         $codigosBloqueados = $codigosActivos
    //             ->merge($codigosFinalizadosRecientes)
    //             ->unique()
    //             ->values();


    //         /**
    //          * =====================================================
    //          * 5. EXCLUIR CÓDIGOS BLOQUEADOS
    //          * =====================================================
    //          *
    //          * IMPORTANTE:
    //          *
    //          * NO buscamos RECHAZADAS.
    //          *
    //          * Por lo tanto una sugerencia rechazada queda libre
    //          * automáticamente para un nuevo análisis.
    //          */
    //         if ($codigosBloqueados->isNotEmpty()) {

    //             $datos = $datos
    //                 ->reject(function ($item) use ($codigosBloqueados) {

    //                     return $codigosBloqueados->contains(
    //                         $item->codigo
    //                     );
    //                 })
    //                 ->values();
    //         }


    //         /**
    //          * =====================================================
    //          * 6. VALIDAR SI QUEDARON PRODUCTOS
    //          * =====================================================
    //          */
    //         if ($datos->isEmpty()) {

    //             DB::rollBack();

    //             return redirect()
    //                 ->route(
    //                     'RedistribucionSugeridas.index'
    //                 )
    //                 ->with(
    //                     'warning',
    //                     'No existen prendas disponibles para analizar. Las prendas actualmente están pendientes, en proceso o fueron finalizadas hace menos de 30 días.'
    //                 );
    //         }


    //         /**
    //          * =====================================================
    //          * 7. AGRUPAR POR PRODUCTO
    //          * =====================================================
    //          */
    //         $productos = $datos->groupBy('codigo');


    //         /**
    //          * =====================================================
    //          * 8. ACUMULADORES
    //          * =====================================================
    //          */
    //         $insertar = [];

    //         $totalSugerencias = 0;


    //         /**
    //          * =====================================================
    //          * 9. ANALIZAR CADA PRODUCTO
    //          * =====================================================
    //          */
    //         foreach ($productos as $codigo => $sucursales) {

    //             /**
    //              * Ordenar sucursales por mayor venta.
    //              */
    //             $sucursales = $sucursales
    //                 ->sortByDesc('cant_vta')
    //                 ->values();

    //             $destinos = [];
    //             $origenes = [];


    //             /**
    //              * =================================================
    //              * 9.1 IDENTIFICAR ORÍGENES Y DESTINOS
    //              * =================================================
    //              */
    //             foreach ($sucursales as $item) {

    //                 $venta = (int) $item->cant_vta;
    //                 $stock = (int) $item->stock_actual;


    //                 /**
    //                  * Stock objetivo = venta.
    //                  */
    //                 $stockObjetivo = $venta;


    //                 /**
    //                  * Necesidad.
    //                  */
    //                 $necesidad =
    //                     $stockObjetivo - $stock;


    //                 /**
    //                  * Exceso.
    //                  */
    //                 $exceso =
    //                     $stock - $stockObjetivo;


    //                 /**
    //                  * =============================================
    //                  * DESTINO
    //                  * =============================================
    //                  *
    //                  * Solamente se consideran destinos con:
    //                  *
    //                  * venta > 2
    //                  * y stock menor que venta.
    //                  */
    //                 if (
    //                     $venta > 2 &&
    //                     $necesidad > 0
    //                 ) {

    //                     $destinos[] = [

    //                         'sucursal_id' =>
    //                         $item->sucursal_id,

    //                         'stock' =>
    //                         $stock,

    //                         'venta' =>
    //                         $venta,

    //                         'necesidad' =>
    //                         $necesidad,
    //                     ];
    //                 }


    //                 /**
    //                  * =============================================
    //                  * ORIGEN
    //                  * =============================================
    //                  */
    //                 if ($exceso > 0) {

    //                     $origenes[] = [

    //                         'sucursal_id' =>
    //                         $item->sucursal_id,

    //                         'stock' =>
    //                         $stock,

    //                         'venta' =>
    //                         $venta,

    //                         'exceso' =>
    //                         $exceso,
    //                     ];
    //                 }
    //             }


    //             /**
    //              * =================================================
    //              * 9.2 ORDENAR ORÍGENES
    //              * =================================================
    //              *
    //              * Primero la sucursal con mayor exceso.
    //              */
    //             usort(
    //                 $origenes,
    //                 function ($a, $b) {

    //                     return
    //                         $b['exceso']
    //                         <=>
    //                         $a['exceso'];
    //                 }
    //             );


    //             /**
    //              * =================================================
    //              * 9.3 ORDENAR DESTINOS
    //              * =================================================
    //              *
    //              * Primero la sucursal con mayor venta.
    //              */
    //             usort(
    //                 $destinos,
    //                 function ($a, $b) {

    //                     return
    //                         $b['venta']
    //                         <=>
    //                         $a['venta'];
    //                 }
    //             );


    //             /**
    //              * =================================================
    //              * 9.4 GENERAR TRANSFERENCIAS
    //              * =================================================
    //              */
    //             foreach ($destinos as &$destino) {

    //                 if (
    //                     $destino['necesidad'] <= 0
    //                 ) {
    //                     continue;
    //                 }


    //                 /**
    //                  * =================================================
    //                  * LÍMITE DE TRANSFERENCIA
    //                  * =================================================
    //                  *
    //                  * Máximo 50% de la necesidad.
    //                  */
    //                 $maximoTransferirDestino =
    //                     (int) ceil(
    //                         $destino['necesidad'] * 0.50
    //                     );


    //                 $pendienteTransferir =
    //                     $maximoTransferirDestino;


    //                 /**
    //                  * =================================================
    //                  * BUSCAR ORIGEN
    //                  * =================================================
    //                  */
    //                 foreach ($origenes as &$origen) {

    //                     if (
    //                         $origen['exceso'] <= 0
    //                     ) {
    //                         continue;
    //                     }


    //                     /**
    //                      * Nunca transferir a la misma sucursal.
    //                      */
    //                     if (
    //                         $origen['sucursal_id']
    //                         ==
    //                         $destino['sucursal_id']
    //                     ) {
    //                         continue;
    //                     }


    //                     /**
    //                      * Cantidad a transferir.
    //                      */
    //                     $cantidad = min(
    //                         $pendienteTransferir,
    //                         $origen['exceso']
    //                     );


    //                     if ($cantidad <= 0) {
    //                         continue;
    //                     }


    //                     /**
    //                      * Motivo.
    //                      */
    //                     $motivo =
    //                         'Transferencia por exceso de stock y mayor demanda.';


    //                     /**
    //                      * =================================================
    //                      * PREPARAR INSERT
    //                      * =================================================
    //                      */
    //                     $insertar[] = [

    //                         'codigo' =>
    //                         $codigo,

    //                         'sucursal_origen' =>
    //                         $origen['sucursal_id'],

    //                         'sucursal_destino' =>
    //                         $destino['sucursal_id'],

    //                         'cantidad' =>
    //                         $cantidad,

    //                         'stock_origen' =>
    //                         $origen['stock'],

    //                         'stock_destino' =>
    //                         $destino['stock'],

    //                         'venta_origen' =>
    //                         $origen['venta'],

    //                         'venta_destino' =>
    //                         $destino['venta'],

    //                         'motivo' =>
    //                         $motivo,

    //                         'estado' =>
    //                         'PENDIENTE',

    //                         'fecha_generacion' =>
    //                         $fechaGeneracion,
    //                     ];


    //                     /**
    //                      * =================================================
    //                      * ACTUALIZAR RESTANTES
    //                      * =================================================
    //                      */
    //                     $pendienteTransferir -=
    //                         $cantidad;

    //                     $origen['exceso'] -=
    //                         $cantidad;

    //                     $totalSugerencias++;


    //                     /**
    //                      * Ya llegó al límite del destino.
    //                      */
    //                     if (
    //                         $pendienteTransferir <= 0
    //                     ) {
    //                         break;
    //                     }
    //                 }

    //                 unset($origen);
    //             }

    //             unset($destino);
    //         }


    //         /**
    //          * =====================================================
    //          * 10. INSERT MASIVO
    //          * =====================================================
    //          */
    //         if (!empty($insertar)) {

    //             foreach (
    //                 array_chunk(
    //                     $insertar,
    //                     1000
    //                 ) as $chunk
    //             ) {

    //                 RedistribucionSugerida::insert(
    //                     $chunk
    //                 );
    //             }
    //         }


    //         /**
    //          * =====================================================
    //          * 11. CONFIRMAR
    //          * =====================================================
    //          */
    //         DB::commit();


    //         /**
    //          * =====================================================
    //          * 12. SIN RESULTADOS
    //          * =====================================================
    //          */
    //         if (
    //             $totalSugerencias === 0
    //         ) {

    //             return redirect()
    //                 ->route(
    //                     'RedistribucionSugeridas.index'
    //                 )
    //                 ->with(
    //                     'warning',
    //                     'El análisis terminó, pero no se encontraron redistribuciones necesarias.'
    //                 );
    //         }


    //         /**
    //          * =====================================================
    //          * 13. RESULTADO
    //          * =====================================================
    //          */
    //         return redirect()
    //             ->route(
    //                 'RedistribucionSugeridas.index'
    //             )
    //             ->with(
    //                 'success',
    //                 'Análisis realizado correctamente. Se generaron '
    //                     . $totalSugerencias
    //                     . ' sugerencias.'
    //             );
    //     } catch (\Exception $e) {

    //         DB::rollBack();

    //         Log::error(
    //             'Error redistribucion',
    //             [
    //                 'error' =>
    //                 $e->getMessage(),

    //                 'line' =>
    //                 $e->getLine(),

    //                 'file' =>
    //                 $e->getFile(),

    //                 'trace' =>
    //                 $e->getTraceAsString(),
    //             ]
    //         );

    //         return back()
    //             ->withInput()
    //             ->with(
    //                 'error',
    //                 'Error al analizar redistribución: '
    //                     . $e->getMessage()
    //             );
    //     }
    // }

    public function analizar2(Request $request)
    {
        $request->validate([
            'periodo'    => 'required',
            'grupo_plan' => 'nullable',
            'linea'      => 'nullable',
            'temporada'  => 'nullable',
        ]);

        DB::beginTransaction();

        try {

            $fechaGeneracion = now();

            /*
        |--------------------------------------------------------------------------
        | 1. ELIMINAR SUGERENCIAS PENDIENTES GENERADAS HOY
        |--------------------------------------------------------------------------
        |
        | Las sugerencias:
        |
        | PENDIENTE  -> se pueden regenerar
        | APROBADA   -> se conserva
        | RECHAZADA  -> se conserva como histórico y NO bloquea
        |
        */

            $inicioDia = $fechaGeneracion->copy()->startOfDay();
            $finDia    = $fechaGeneracion->copy()->endOfDay();

            RedistribucionSugerida::whereBetween(
                'fecha_generacion',
                [$inicioDia, $finDia]
            )
                ->where('estado', 'PENDIENTE')
                ->delete();


            /*
        |--------------------------------------------------------------------------
        | 2. CONFIGURACIÓN DEL ANÁLISIS
        |--------------------------------------------------------------------------
        |
        | Según el informe ejecutivo:
        |
        | RECEPTORES:
        | 9 = Multiplaza
        | 8  = Shop San Lo3
        | 14  = Pinedo Shopping
        | 16 = Shopping Mariano
        | 2 = San Lorenzo
        | 15  = Ñemby
        |
        */

            $sucursalesReceptoras = [
                9,
                8,
                14,
                16,
                2,
                15,
            ];


            /*
        |--------------------------------------------------------------------------
        | 3. OBTENER STOCK Y VENTAS
        |--------------------------------------------------------------------------
        */

            $query = StockVentasSucursal::query()
                ->where('periodo', $request->periodo);

            if ($request->filled('grupo_plan')) {
                $query->where(
                    'grupo_plan',
                    $request->grupo_plan
                );
            }

            if ($request->filled('linea')) {
                $query->where(
                    'linea',
                    $request->linea
                );
            }

            if ($request->filled('temporada')) {
                $query->where(
                    'temporada',
                    $request->temporada
                );
            }

            $datos = $query->get();


            /*
        |--------------------------------------------------------------------------
        | 4. VALIDAR DATOS
        |--------------------------------------------------------------------------
        */

            if ($datos->isEmpty()) {

                DB::rollBack();

                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'No existen datos para los filtros seleccionados.'
                    );
            }


            /*
        |--------------------------------------
        | 5. DETERMINAR LAS SUCURSALES EMISORAS
        |--------------------------------------
        |
        | No se fijan manualmente los IDs.
        |
        | Las emisoras son las sucursales que aparecen en el período
        | y que NO forman parte de los 6 receptores.
        |
        | Esto evita depender de IDs que puedan cambiar.
        |
        */

            $sucursalesDisponibles = $datos
                ->pluck('sucursal_id')
                ->unique()
                ->map(function ($id) {
                    return (int) $id;
                })
                ->values();

            $sucursalesEmisoras = $sucursalesDisponibles
                ->reject(function ($id) use ($sucursalesReceptoras) {

                    return in_array(
                        $id,
                        $sucursalesReceptoras
                    );
                })
                ->values()
                ->all();


            /*
        |--------------------------------------------------------------------------
        | 6. CÓDIGOS BLOQUEADOS
        |--------------------------------------------------------------------------
        |
        | PENDIENTE   -> BLOQUEADO
        | EN PROCESO  -> BLOQUEADO
        |
        | FINALIZADO:
        | menos de 30 días -> BLOQUEADO
        | 30 días o más    -> DISPONIBLE
        |
        | RECHAZADO:
        | NO BLOQUEA
        |
        */

            $codigosActivos = RedistribucionProcesoDetalle::query()
                ->whereIn('estado', [
                    'PENDIENTE',
                    'EN PROCESO'
                ])
                ->pluck('codigo')
                ->unique()
                ->values();


            $fechaLimite = now()->subDays(30);

            $codigosFinalizadosRecientes =
                RedistribucionProcesoDetalle::query()
                ->join(
                    'redistribucion_lote',
                    'redistribucion_proceso_detalle.lote_id',
                    '=',
                    'redistribucion_lote.id'
                )
                ->where(
                    'redistribucion_lote.estado',
                    'FINALIZADO'
                )
                ->whereNotNull(
                    'redistribucion_lote.fecha_finalizacion'
                )
                ->where(
                    'redistribucion_lote.fecha_finalizacion',
                    '>=',
                    $fechaLimite
                )
                ->pluck(
                    'redistribucion_proceso_detalle.codigo'
                )
                ->unique()
                ->values();


            $codigosBloqueados = $codigosActivos
                ->merge($codigosFinalizadosRecientes)
                ->unique()
                ->values();


            /*
        |------------------------------
        | 7. EXCLUIR CÓDIGOS BLOQUEADOS
        |------------------------------
        */

            if ($codigosBloqueados->isNotEmpty()) {

                $datos = $datos
                    ->reject(function ($item) use ($codigosBloqueados) {

                        return $codigosBloqueados->contains(
                            $item->codigo
                        );
                    })
                    ->values();
            }


            /*
        |-----------------------------
        | 8. VALIDAR SI QUEDARON DATOS
        |-----------------------------
        */

            if ($datos->isEmpty()) {

                DB::rollBack();

                return redirect()
                    ->route(
                        'RedistribucionSugeridas.index'
                    )
                    ->with(
                        'warning',
                        'No existen prendas disponibles para analizar. Las referencias actualmente están pendientes, en proceso o fueron finalizadas hace menos de 30 días.'
                    );
            }


            /*
        |--------------------------------------------------------------------------
        | 9. AGRUPAR POR CÓDIGO
        |--------------------------------------------------------------------------
        |
        | IMPORTANTE:
        |
        | NO agrupamos por grupo_plan.
        | NO agrupamos por línea.
        | NO agrupamos por color.
        | NO agrupamos por talle.
        |
        | El "codigo" ya identifica la referencia completa.
        |
        | Ejemplo:
        |
        | 060617120RS10
        |
        | representa la referencia específica.
        |
        */

            $productos = $datos->groupBy('codigo');


            /*
        |--------------------------------------------------------------------------
        | 10. ACUMULADORES
        |--------------------------------------------------------------------------
        */

            $insertar = [];

            $totalSugerencias = 0;

            $totalUnidadesSugeridas = 0;


            /*
        |--------------------------------------------------------------------------
        | 11. ANALIZAR CADA CÓDIGO
        |--------------------------------------------------------------------------
        */

            foreach ($productos as $codigo => $sucursales) {

                /*
            |--------------------------------------------------------------------------
            | 11.1 SEPARAR RECEPTORES Y EMISORES
            |--------------------------------------------------------------------------
            */

                $destinos = [];

                $origenes = [];


                foreach ($sucursales as $item) {

                    $sucursalId = (int) $item->sucursal_id;

                    $venta = max(
                        0,
                        (int) $item->cant_vta
                    );

                    $stock = max(
                        0,
                        (int) $item->stock_actual
                    );


                    /*
                |--------------------------------------------------------------------------
                | RECEPTOR
                |--------------------------------------------------------------------------
                |
                | Regla fundamental del Word:
                |
                | "Código/talle vendido en un local receptor:
                | puede redistribuirse dentro del límite de cobertura."
                |
                | Por lo tanto:
                |
                | venta > 0
                |
                | Si nunca vendió ese código:
                | NO RECIBE.
                |
                */

                    if (
                        in_array(
                            $sucursalId,
                            $sucursalesReceptoras
                        )
                        &&
                        $venta > 0
                    ) {

                        /*
                    |--------------------------------------------------------------------------
                    | COBERTURA DEL RECEPTOR
                    |--------------------------------------------------------------------------
                    |
                    | El informe muestra receptores entre:
                    |
                    | 2,04 y 3,38 meses de cobertura.
                    |
                    | Para evitar saturarlos al cierre de temporada,
                    | utilizamos 3 meses como techo operativo.
                    |
                    | Esto NO significa que se deba llenar hasta 3 meses.
                    | Solamente define el máximo razonable.
                    |
                    */

                        $stockMaximo = (int) ceil(
                            $venta * 3
                        );


                        /*
                    |--------------------------------------------------------------------------
                    | CAPACIDAD REAL DEL RECEPTOR
                    |--------------------------------------------------------------------------
                    */

                        $capacidad = max(
                            0,
                            $stockMaximo - $stock
                        );


                        if ($capacidad > 0) {

                            $destinos[] = [

                                'sucursal_id' =>
                                $sucursalId,

                                'stock' =>
                                $stock,

                                'venta' =>
                                $venta,

                                'capacidad' =>
                                $capacidad,
                            ];
                        }
                    }


                    /*
                |--------------------------------------------------------------------------
                | EMISOR
                |--------------------------------------------------------------------------
                |
                | Se consideran únicamente los 6 locales fuera de los receptores.
                |
                */

                    if (
                        in_array(
                            $sucursalId,
                            $sucursalesEmisoras
                        )
                        &&
                        $stock > 0
                    ) {

                        /*
                    |--------------------------------------------------------------------------
                    | CASO 1:
                    | STOCK SIN VENTA
                    |--------------------------------------------------------------------------
                    |
                    | El Word indica:
                    |
                    | "Stock sin venta o con rotación muy baja:
                    | retirar para evaluación."
                    |
                    | Si no tiene venta, todo el stock queda como candidato.
                    |
                    */

                        if ($venta == 0) {

                            $stockMinimo = 0;

                            $exceso = $stock;
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | CASO 2:
                    | TIENE VENTA
                    |--------------------------------------------------------------------------
                    |
                    | No debemos vaciar un local que todavía vende.
                    |
                    | Conservamos una existencia equivalente a una venta
                    | del período como mínimo operativo.
                    |
                    */ else {

                            $stockMinimo = $venta;

                            $exceso = max(
                                0,
                                $stock - $stockMinimo
                            );
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | SOLO CANDIDATOS CON EXCESO
                    |--------------------------------------------------------------------------
                    */

                        if ($exceso > 0) {

                            $origenes[] = [

                                'sucursal_id' =>
                                $sucursalId,

                                'stock' =>
                                $stock,

                                'venta' =>
                                $venta,

                                'stock_minimo' =>
                                $stockMinimo,

                                'exceso' =>
                                $exceso,
                            ];
                        }
                    }
                }


                /*
            |---------------------
            | 12. ORDENAR ORÍGENES
            |---------------------
            |
            | Prioridad:
            |
            | 1. Sin venta
            | 2. Mayor exceso
            |
            | Esto sigue la recomendación del Word:
            |
            | Bonanza
            | L06 San Lo2
            | La Rural
            |
            | como puntos prioritarios de revisión.
            |
            */

                usort(
                    $origenes,
                    function ($a, $b) {

                        /*
                    | Sin venta primero.
                    */

                        if (
                            $a['venta'] == 0
                            &&
                            $b['venta'] > 0
                        ) {
                            return -1;
                        }

                        if (
                            $a['venta'] > 0
                            &&
                            $b['venta'] == 0
                        ) {
                            return 1;
                        }


                        /*
                    | Después mayor exceso.
                    */

                        return
                            $b['exceso']
                            <=>
                            $a['exceso'];
                    }
                );


                /*
            |-----------------------
            | 13. ORDENAR RECEPTORES
            |-----------------------
            |
            | Primero los receptores con mayor venta del código.
            |
            | Así la mercadería se concentra donde existe mayor evidencia
            | de demanda.
            |
            */

                usort(
                    $destinos,
                    function ($a, $b) {

                        return
                            $b['venta']
                            <=>
                            $a['venta'];
                    }
                );


                /*
            |--------------------------------------------------------------------------
            | 14. GENERAR TRANSFERENCIAS
            |--------------------------------------------------------------------------
            */

                foreach ($destinos as &$destino) {

                    if (
                        $destino['capacidad'] <= 0
                    ) {
                        continue;
                    }


                    /*
                |--------------------------------------------------------------------------
                | NO SATURAR EL RECEPTOR
                |--------------------------------------------------------------------------
                |
                | Se utiliza solamente una parte de la capacidad disponible
                | en cada análisis.
                |
                | De esta forma evitamos una centralización agresiva.
                |
                | El objetivo es una redistribución SELECTIVA.
                |
                */

                    $cantidadMaximaDestino =
                        (int) ceil(
                            $destino['capacidad'] * 0.50
                        );


                    $pendienteTransferir =
                        $cantidadMaximaDestino;


                    if (
                        $pendienteTransferir <= 0
                    ) {
                        continue;
                    }


                    /*
                |--------------------------------------------------------------------------
                | 15. BUSCAR STOCK EN LOS EMISORES
                |--------------------------------------------------------------------------
                */

                    foreach ($origenes as &$origen) {

                        if (
                            $origen['exceso'] <= 0
                        ) {
                            continue;
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | NUNCA MISMA SUCURSAL
                    |--------------------------------------------------------------------------
                    */

                        if (
                            $origen['sucursal_id']
                            ==
                            $destino['sucursal_id']
                        ) {
                            continue;
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | CANTIDAD A TRANSFERIR
                    |--------------------------------------------------------------------------
                    */

                        $cantidad = min(
                            $pendienteTransferir,
                            $origen['exceso']
                        );


                        if ($cantidad <= 0) {
                            continue;
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | MOTIVO
                    |--------------------------------------------------------------------------
                    */

                        if (
                            $origen['venta'] == 0
                        ) {

                            $motivo =
                                'Stock sin venta en el local emisor y demanda comprobada del mismo código en el local receptor.';
                        } else {

                            $motivo =
                                'Redistribución selectiva por exceso de stock, conservando existencia mínima en el local emisor y priorizando un local receptor con venta comprobada del mismo código.';
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | 16. PREPARAR SUGERENCIA
                    |--------------------------------------------------------------------------
                    */

                        $insertar[] = [

                            'codigo' =>
                            $codigo,

                            'sucursal_origen' =>
                            $origen['sucursal_id'],

                            'sucursal_destino' =>
                            $destino['sucursal_id'],

                            'cantidad' =>
                            $cantidad,

                            'stock_origen' =>
                            $origen['stock'],

                            'stock_destino' =>
                            $destino['stock'],

                            'venta_origen' =>
                            $origen['venta'],

                            'venta_destino' =>
                            $destino['venta'],

                            'motivo' =>
                            $motivo,

                            'estado' =>
                            'PENDIENTE',

                            'fecha_generacion' =>
                            $fechaGeneracion,
                        ];


                        /*
                    |--------------------------------------------------------------------------
                    | 17. ACTUALIZAR DISPONIBLES
                    |--------------------------------------------------------------------------
                    |
                    | Esto es importante.
                    |
                    | Si Bonanza tiene 100 unidades y se envían 20:
                    |
                    | exceso = 80
                    |
                    | La siguiente transferencia NO puede volver a utilizar
                    | esas 20 unidades.
                    |
                    */

                        $pendienteTransferir -=
                            $cantidad;

                        $origen['exceso'] -=
                            $cantidad;

                        $destino['capacidad'] -=
                            $cantidad;

                        $totalSugerencias++;

                        $totalUnidadesSugeridas +=
                            $cantidad;


                        /*
                    |--------------------------------------------------------------------------
                    | DESTINO COMPLETO
                    |--------------------------------------------------------------------------
                    */

                        if (
                            $pendienteTransferir <= 0
                        ) {
                            break;
                        }
                    }

                    unset($origen);
                }

                unset($destino);
            }


            /*
        |--------------------------------------------------------------------------
        | 18. INSERT MASIVO
        |--------------------------------------------------------------------------
        */

            if (!empty($insertar)) {

                foreach (
                    array_chunk(
                        $insertar,
                        1000
                    ) as $chunk
                ) {

                    RedistribucionSugerida::insert(
                        $chunk
                    );
                }
            }


            /*
        |--------------------------------------------------------------------------
        | 19. CONFIRMAR TRANSACCIÓN
        |--------------------------------------------------------------------------
        */

            DB::commit();


            /*
        |--------------------------------------------------------------------------
        | 20. SIN SUGERENCIAS
        |--------------------------------------------------------------------------
        */

            if (
                $totalSugerencias === 0
            ) {

                return redirect()
                    ->route(
                        'RedistribucionSugeridas.index'
                    )
                    ->with(
                        'warning',
                        'El análisis terminó, pero no se encontraron redistribuciones que cumplan los criterios de demanda comprobada, exceso de stock y capacidad del local receptor.'
                    );
            }


            /*
        |--------------------------------------------------------------------------
        | 21. RESULTADO
        |--------------------------------------------------------------------------
        */

            return redirect()
                ->route(
                    'RedistribucionSugeridas.index'
                )
                ->with(
                    'success',
                    'Análisis realizado correctamente. Se generaron '
                        . $totalSugerencias
                        . ' sugerencias por '
                        . $totalUnidadesSugeridas
                        . ' unidades.'
                );
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error(
                'Error redistribucion',
                [
                    'error' =>
                    $e->getMessage(),

                    'line' =>
                    $e->getLine(),

                    'file' =>
                    $e->getFile(),

                    'trace' =>
                    $e->getTraceAsString(),
                ]
            );

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Error al analizar redistribución: '
                        . $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | APROBAR
    |--------------------------------------------------------------------------
    */

    public function aprobar(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        DB::beginTransaction();

        try {

            /*
             * Bloquear registros mientras se aprueban.
             */

            $sugerencias = RedistribucionSugerida::whereIn(
                'id',
                $request->ids
            )
                ->where('estado', 'PENDIENTE')
                ->lockForUpdate()
                ->get();


            if ($sugerencias->isEmpty()) {

                DB::rollBack();

                return back()->with(
                    'error',
                    'No existen sugerencias pendientes para aprobar.'
                );
            }


            /*
             * Usuario.
             */

            $usuario = optional(auth()->user())->name
                ?? optional(auth()->user())->email
                ?? 'SISTEMA';


            /*
             * Crear proceso.
             */

            $proceso = RedistribucionProceso::create([

                'fecha' => now(),

                'usuario' => $usuario,

                'total_productos' =>
                $sugerencias
                    ->unique('codigo')
                    ->count(),

                'total_movimientos' =>
                $sugerencias->count(),

                'observacion' =>
                'Redistribución aprobada desde sugerencias automáticas.',
            ]);


            /*
             * Detalles.
             */

            $fecha = now();

            $detallesInsertar = [];


            foreach ($sugerencias as $sugerencia) {

                $detallesInsertar[] = [

                    'proceso_id' =>
                    $proceso->id,

                    'codigo' =>
                    $sugerencia->codigo,

                    'sucursal_origen' =>
                    $sugerencia->sucursal_origen,

                    'sucursal_destino' =>
                    $sugerencia->sucursal_destino,

                    'cantidad' =>
                    $sugerencia->cantidad,

                    'estado' =>
                    'PENDIENTE',

                    'observacion' =>
                    $sugerencia->motivo,

                    'fecha' =>
                    $fecha,

                    'lote_id' =>
                    null,
                ];
            }


            /*
             * Insertar detalles.
             */

            foreach (
                array_chunk($detallesInsertar, 1000)
                as $chunk
            ) {

                RedistribucionProcesoDetalle::insert(
                    $chunk
                );
            }


            /*
             * Marcar sugerencias como aprobadas.
             */

            RedistribucionSugerida::whereIn(
                'id',
                $sugerencias->pluck('id')
            )
                ->where('estado', 'PENDIENTE')
                ->update([
                    'estado' => 'APROBADA',
                ]);


            DB::commit();


            return redirect()
                ->route('RedistribucionSugeridas.lotes')
                ->with(
                    'success',
                    'Proceso #' .
                        $proceso->id .
                        ' creado correctamente con ' .
                        $sugerencias->count() .
                        ' movimientos.'
                );
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error(
                'Error aprobando redistribución: ' .
                    $e->getMessage(),
                [
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ]
            );

            return back()->with(
                'error',
                'Error al aprobar las redistribuciones: ' .
                    $e->getMessage()
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | RECHAZAR
    |--------------------------------------------------------------------------
    */

    public function rechazar(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        DB::beginTransaction();

        try {

            $cantidad = RedistribucionSugerida::whereIn(
                'id',
                $request->ids
            )
                ->where('estado', 'PENDIENTE')
                ->update([
                    'estado' => 'RECHAZADA',
                ]);


            if ($cantidad == 0) {

                DB::rollBack();

                return back()->with(
                    'error',
                    'No existen sugerencias pendientes para rechazar.'
                );
            }


            DB::commit();


            return redirect()
                ->route('RedistribucionSugeridas.index')
                ->with(
                    'success',
                    $cantidad .
                        ' sugerencias rechazadas correctamente.'
                );
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error(
                'Error rechazando redistribución: ' .
                    $e->getMessage(),
                [
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ]
            );

            return back()->with(
                'error',
                'Error al rechazar las redistribuciones: ' .
                    $e->getMessage()
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | VER PROCESO
    |--------------------------------------------------------------------------
    */

    public function proceso($id)
    {
        $proceso = RedistribucionProceso::with([
            'detalles.origen',
            'detalles.destino'
        ])->findOrFail($id);

        return view(
            'redistribucion_sugeridas.proceso',
            compact('proceso')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | GENERAR LOTE
    |--------------------------------------------------------------------------
    */

    public function generarLote(Request $request)
    {
        $request->validate([
            'proceso_id' => 'required|integer'
        ]);

        DB::beginTransaction();

        try {

            $proceso = RedistribucionProceso::findOrFail(
                $request->proceso_id
            );


            /*
         * ============================================================
         * OBTENER DETALLES PENDIENTES SIN LOTE
         * ============================================================
         */

            $detalles = RedistribucionProcesoDetalle::where(
                'proceso_id',
                $proceso->id
            )
                ->where('estado', 'PENDIENTE')
                ->whereNull('lote_id')
                ->lockForUpdate()
                ->get();


            if ($detalles->isEmpty()) {

                DB::rollBack();

                return back()->with(
                    'warning',
                    'No existen transferencias pendientes para generar el lote.'
                );
            }


            /*
         * ============================================================
         * NÚMERO DE LOTE CORRELATIVO
         * ============================================================
         *
         * LOT-1
         * LOT-2
         * LOT-3
         * LOT-4
         * LOT-5
         * ...
         *
         * Busca el último lote generado y suma 1.
         */

            $ultimoLote = RedistribucionLote::orderByDesc('id')
                ->lockForUpdate()
                ->first();


            if ($ultimoLote) {

                /*
             * Extraer solamente el número.
             *
             * Ejemplo:
             *
             * LOT-2
             *
             * se convierte en:
             *
             * 2
             */

                $ultimoNumero = (int) str_replace(
                    'LOT-',
                    '',
                    $ultimoLote->numero_lote
                );

                $siguienteNumero = $ultimoNumero + 1;
            } else {

                /*
             * Si todavía no existe ningún lote.
             */

                $siguienteNumero = 1;
            }


            /*
         * Crear número definitivo.
         */

            $numeroLote = 'LOT-' . $siguienteNumero;


            /*
         * ============================================================
         * VERIFICAR QUE NO EXISTA
         * ============================================================
         *
         * Esto agrega una protección adicional.
         */

            while (
                RedistribucionLote::where(
                    'numero_lote',
                    $numeroLote
                )->exists()
            ) {

                $siguienteNumero++;

                $numeroLote = 'LOT-' . $siguienteNumero;
            }


            /*
         * ============================================================
         * USUARIO
         * ============================================================
         */

            $usuarioId = auth()->id();

            $usuarioNombre = auth()->user()->name
                ?? auth()->user()->email
                ?? 'SISTEMA';


            /*
         * ============================================================
         * TOTALES
         * ============================================================
         */

            $totalMovimientos =
                $detalles->count();

            $totalTransferencias =
                $totalMovimientos;

            $totalProductos =
                $detalles
                ->unique('codigo')
                ->count();

            $totalUnidades =
                $detalles->sum('cantidad');


            /*
         * ============================================================
         * CREAR LOTE
         * ============================================================
         */

            $lote = RedistribucionLote::create([

                'proceso_id' =>
                $proceso->id,

                'numero_lote' =>
                $numeroLote,

                'fecha_generacion' =>
                now(),

                'usuario' =>
                $usuarioNombre,

                'total_movimientos' =>
                $totalMovimientos,

                'total_transferencias' =>
                $totalTransferencias,

                'total_productos' =>
                $totalProductos,

                'total_unidades' =>
                $totalUnidades,

                'estado' =>
                'GENERADO',

                'observacion' =>
                'Lote generado desde proceso de redistribución #' .
                    $proceso->id,

                'usuario_generacion' =>
                $usuarioId,
            ]);


            /*
         * ============================================================
         * ASOCIAR DETALLES AL LOTE
         * ============================================================
         */

            RedistribucionProcesoDetalle::whereIn(
                'id',
                $detalles->pluck('id')
            )->update([
                'lote_id' => $lote->id,
            ]);


            /*
         * ============================================================
         * CONFIRMAR TRANSACCIÓN
         * ============================================================
         */

            DB::commit();


            /*
         * ============================================================
         * REDIRECCIONAR
         * ============================================================
         */

            return redirect()
                ->route('RedistribucionSugeridas.lotes')
                ->with(
                    'success',
                    'Lote ' .
                        $numeroLote .
                        ' generado correctamente con ' .
                        $totalTransferencias .
                        ' transferencias.'
                );
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error(
                'Error generando lote de redistribución: ' .
                    $e->getMessage(),
                [
                    'line' =>
                    $e->getLine(),

                    'file' =>
                    $e->getFile(),

                    'proceso_id' =>
                    $request->proceso_id ?? null,
                ]
            );

            return back()->with(
                'error',
                'Error al generar el lote: ' .
                    $e->getMessage()
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | VER LOTE
    |--------------------------------------------------------------------------
    */

    public function lote($id)
    {
        $lote = RedistribucionLote::findOrFail($id);

        $detalles = RedistribucionProcesoDetalle::with([
            'origen',
            'destino',
            'proceso'
        ])
            ->where('lote_id', $lote->id)
            ->orderBy('codigo')
            ->get();

        return view(
            'redistribucion_sugeridas.lote',
            compact(
                'lote',
                'detalles'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PROCESAR LOTE
    |--------------------------------------------------------------------------
    */

    public function procesarLote(Request $request, $id)
    {
        $inicioTotal = microtime(true);

        DB::beginTransaction();

        try {

            $lote = RedistribucionLote::lockForUpdate()
                ->findOrFail($id);


            Log::info('PROCESANDO LOTE', [
                'lote_id' =>
                $lote->id,

                'numero_lote' =>
                $lote->numero_lote,

                'estado_lote' =>
                $lote->estado
            ]);


            /*
             * Validar estado.
             */

            if ($lote->estado !== 'GENERADO') {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' =>
                    'El lote no puede procesarse porque actualmente se encuentra en estado: ' .
                        $lote->estado
                ], 422);
            }


            /*
             * Pasar detalles a EN PROCESO.
             */

            $totalTransferencias =
                RedistribucionProcesoDetalle::where(
                    'lote_id',
                    $lote->id
                )
                ->where(
                    'estado',
                    'PENDIENTE'
                )
                ->update([
                    'estado' =>
                    'EN PROCESO'
                ]);


            if ($totalTransferencias === 0) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' =>
                    'El lote está en estado GENERADO, pero no tiene detalles PENDIENTES.'
                ], 422);
            }


            /*
             * Actualizar lote.
             */

            $lote->update([
                'estado' =>
                'EN PROCESO'
            ]);


            DB::commit();


            Log::info(
                'LOTE PROCESADO CORRECTAMENTE',
                [
                    'lote_id' =>
                    $lote->id,

                    'total_transferencias' =>
                    $totalTransferencias,

                    'tiempo' =>
                    microtime(true) -
                        $inicioTotal
                ]
            );


            return response()->json([
                'success' => true,

                'message' =>
                'Lote ' .
                    $lote->numero_lote .
                    ' procesado correctamente.',

                'lote_id' =>
                $lote->id,

                'total_transferencias' =>
                $totalTransferencias
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error(
                'ERROR PROCESANDO LOTE',
                [
                    'lote_id' =>
                    $id,

                    'error' =>
                    $e->getMessage(),

                    'line' =>
                    $e->getLine(),

                    'file' =>
                    $e->getFile()
                ]
            );

            return response()->json([
                'success' => false,

                'message' =>
                'Error al procesar el lote: ' .
                    $e->getMessage()
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | FINALIZAR LOTE
    |--------------------------------------------------------------------------
    */

    public function finalizarLote(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            $lote = RedistribucionLote::lockForUpdate()
                ->findOrFail($id);


            /*
             * Validar estado.
             */

            if ($lote->estado !== 'EN PROCESO') {

                DB::rollBack();

                return response()->json([
                    'success' => false,

                    'message' =>
                    'El lote no puede finalizarse porque actualmente se encuentra en estado: ' .
                        $lote->estado
                ], 422);
            }


            /*
             * =====================================================
             * FINALIZAR DETALLES
             *
             * IMPORTANTE:
             *
             * updated_at se actualiza automáticamente.
             *
             * Esa fecha será utilizada para determinar los
             * 30 días de bloqueo.
             * =====================================================
             */

            $totalFinalizados =
                RedistribucionProcesoDetalle::where(
                    'lote_id',
                    $lote->id
                )
                ->where(
                    'estado',
                    'EN PROCESO'
                )
                ->update([
                    'estado' =>
                    'FINALIZADO',
                ]);


            /*
             * Validar detalles.
             */

            if ($totalFinalizados === 0) {

                DB::rollBack();

                return response()->json([
                    'success' => false,

                    'message' =>
                    'No existen transferencias en proceso para finalizar.'
                ], 422);
            }


            /*
             * Finalizar lote.
             */

            $lote->update([
                'estado' => 'FINALIZADO',
                'fecha_finalizacion' => now(),
            ]);


            DB::commit();


            return response()->json([
                'success' => true,

                'message' =>
                'Lote ' .
                    $lote->numero_lote .
                    ' finalizado correctamente con ' .
                    $totalFinalizados .
                    ' transferencias.',

                'lote_id' =>
                $lote->id,

                'total_transferencias' =>
                $totalFinalizados
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error(
                'Error finalizando lote de redistribución: ' .
                    $e->getMessage(),
                [
                    'line' =>
                    $e->getLine(),

                    'file' =>
                    $e->getFile(),

                    'lote_id' =>
                    $id,
                ]
            );

            return response()->json([
                'success' => false,

                'message' =>
                'Error al finalizar el lote: ' .
                    $e->getMessage()
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | GESTIÓN DE LOTES
    |--------------------------------------------------------------------------
    */

    public function lotes()
    {
        /*
         * Procesos pendientes.
         */

        $procesosPendientes =
            RedistribucionProceso::whereHas(
                'detalles',
                function ($query) {

                    $query
                        ->where(
                            'estado',
                            'PENDIENTE'
                        )
                        ->whereNull(
                            'lote_id'
                        );
                }
            )
            ->with([
                'detalles' => function ($query) {

                    $query
                        ->where(
                            'estado',
                            'PENDIENTE'
                        )
                        ->whereNull(
                            'lote_id'
                        );
                }
            ])
            ->orderByDesc('fecha')
            ->get();


        /*
         * Lotes generados.
         */

        $lotesGenerados =
            RedistribucionLote::where(
                'estado',
                'GENERADO'
            )
            ->with('detalles')
            ->orderByDesc(
                'fecha_generacion'
            )
            ->paginate(
                10,
                ['*'],
                'generados'
            );


        /*
         * Lotes en proceso.
         */

        $lotesEnProceso =
            RedistribucionLote::where(
                'estado',
                'EN PROCESO'
            )
            ->with('detalles')
            ->orderByDesc(
                'fecha_generacion'
            )
            ->paginate(
                10,
                ['*'],
                'en_proceso'
            );


        /*
         * Lotes finalizados.
         */

        $lotesFinalizados =
            RedistribucionLote::where(
                'estado',
                'FINALIZADO'
            )
            ->with('detalles')
            ->orderByDesc(
                'fecha_generacion'
            )
            ->paginate(
                10,
                ['*'],
                'finalizados'
            );


        return view(
            'redistribucion_sugeridas.lotes',
            compact(
                'procesosPendientes',
                'lotesGenerados',
                'lotesEnProceso',
                'lotesFinalizados'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | EXPORTAR LOTE PDF
    |--------------------------------------------------------------------------
    */

    public function exportarLotePdf($id)
    {
        try {

            /*
        |--------------------------------------------------------------------------
        | AUMENTAR MEMORIA SOLAMENTE PARA LA GENERACIÓN DEL PDF
        |--------------------------------------------------------------------------
        */

            ini_set('memory_limit', '2048M');


            /*
        |--------------------------------------------------------------------------
        | 1. BUSCAR LOTE
        |--------------------------------------------------------------------------
        */

            $lote = RedistribucionLote::with([
                'detalles.origen',
                'detalles.destino'
            ])->findOrFail($id);


            /*
        |--------------------------------------------------------------------------
        | 2. OBTENER CÓDIGOS DEL LOTE
        |--------------------------------------------------------------------------
        */

            $codigos = $lote->detalles
                ->pluck('codigo')
                ->filter()
                ->unique()
                ->values();


            /*
        |--------------------------------------------------------------------------
        | 3. BUSCAR DESCRIPCIONES
        |--------------------------------------------------------------------------
        */

            $descripciones = collect();

            if ($codigos->isNotEmpty()) {

                $descripciones = DB::table('stock_ventas_sucursales')
                    ->whereIn('codigo', $codigos->toArray())
                    ->select(
                        'codigo',
                        'grupo_plan'
                    )
                    ->orderBy('id')
                    ->get()
                    ->groupBy('codigo')
                    ->map(function ($items) {

                        return $items->first()->grupo_plan ?? '-';
                    });
            }


            /*
        |--------------------------------------------------------------------------
        | 4. ASIGNAR DESCRIPCIÓN
        |--------------------------------------------------------------------------
        */

            $lote->detalles->each(function ($detalle) use ($descripciones) {

                $detalle->descripcion =
                    $descripciones->get(
                        $detalle->codigo,
                        '-'
                    );
            });


            /*
        |--------------------------------------------------------------------------
        | 5. ORDENAR DETALLES
        |--------------------------------------------------------------------------
        */

            $detallesOrdenados = $lote->detalles
                ->sortBy(function ($detalle) {

                    $origen = $detalle->origen;

                    $destino = $detalle->destino;

                    return [

                        strtoupper(
                            trim(
                                $origen
                                    ? ($origen->suc_descri ?? '')
                                    : ''
                            )
                        ),

                        strtoupper(
                            trim(
                                $destino
                                    ? ($destino->suc_descri ?? '')
                                    : ''
                            )
                        ),

                        strtoupper(
                            trim(
                                $detalle->codigo ?? ''
                            )
                        )
                    ];
                })
                ->values();


            /*
        |--------------------------------------------------------------------------
        | 6. REEMPLAZAR RELACIÓN ORDENADA
        |--------------------------------------------------------------------------
        */

            $lote->setRelation(
                'detalles',
                $detallesOrdenados
            );


            /*
        |--------------------------------------------------------------------------
        | 7. CONFIGURAR DOMPDF
        |--------------------------------------------------------------------------
        */

            $pdf = Pdf::loadView(
                'redistribucion_sugeridas.pdf.lote',
                [
                    'lote' => $lote
                ]
            );


            /*
        |--------------------------------------------------------------------------
        | 8. CONFIGURAR PAPEL
        |--------------------------------------------------------------------------
        */

            $pdf->setPaper(
                'A4',
                'portrait'
            );


            /*
        |--------------------------------------------------------------------------
        | 9. DESCARGAR
        |--------------------------------------------------------------------------
        */

            return $pdf->download(
                'Lote-' . $lote->numero_lote . '.pdf'
            );
        } catch (\Throwable $e) {

            /*
        |--------------------------------------------------------------------------
        | REGISTRAR ERROR
        |--------------------------------------------------------------------------
        */

            Log::error(
                'Error exportando lote a PDF',
                [
                    'lote_id' => $id,

                    'error' => $e->getMessage(),

                    'line' => $e->getLine(),

                    'file' => $e->getFile(),

                    'memory_usage' => memory_get_usage(true),

                    'memory_peak' => memory_get_peak_usage(true),

                    'trace' => $e->getTraceAsString(),
                ]
            );


            /*
        |--------------------------------------------------------------------------
        | VOLVER CON ERROR
        |--------------------------------------------------------------------------
        */

            return redirect()
                ->back()
                ->with(
                    'error',
                    'No se pudo generar el PDF. El lote puede contener demasiados detalles para generar el documento.'
                );
        }
    }



    /*
    |--------------------------------------------------------------------------
    | EXPORTAR LOTE EXCEL
    |--------------------------------------------------------------------------
    */

    public function exportarLoteExcel($id)
    {
        try {

            $lote = RedistribucionLote::with([
                'detalles.origen',
                'detalles.destino'
            ])->findOrFail($id);

            $lote->setRelation(
                'detalles',
                $lote->detalles
                    ->sortBy(function ($detalle) {

                        return [
                            strtoupper(
                                trim(
                                    $detalle->origen->suc_descri ?? ''
                                )
                            ),

                            strtoupper(
                                trim(
                                    $detalle->destino->suc_descri ?? ''
                                )
                            ),

                            strtoupper(
                                trim(
                                    $detalle->codigo ?? ''
                                )
                            )
                        ];
                    })
                    ->values()
            );

            return Excel::download(
                new LoteRedistribucionExport($lote),
                'Lote-' . $lote->numero_lote . '.xlsx'
            );
        } catch (\Exception $e) {

            Log::error(
                'Error exportando lote a Excel',
                [
                    'lote_id' => $id,
                    'error'   => $e->getMessage(),
                    'line'    => $e->getLine(),
                    'file'    => $e->getFile()
                ]
            );

            return back()->with(
                'error',
                'No se pudo generar el Excel: ' . $e->getMessage()
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | VER DETALLE DE SUGERENCIA
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $redistribucion =
            RedistribucionSugerida::with([
                'origen',
                'destino'
            ])->findOrFail($id);


        return view(
            'redistribucion_sugeridas.show',
            compact('redistribucion')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ELIMINAR SUGERENCIA
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $redistribucion =
            RedistribucionSugerida::findOrFail($id);


        /*
         * No permitir eliminar una sugerencia ya aprobada.
         */

        if ($redistribucion->estado === 'APROBADA') {

            return back()->with(
                'error',
                'No se puede eliminar una sugerencia que ya fue aprobada.'
            );
        }


        $redistribucion->delete();


        return back()->with(
            'success',
            'Sugerencia eliminada correctamente.'
        );
    }

    public function importarRemisiones(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls',
        ]);

        try {

            Excel::import(
                new RedistribucionRemisionImport(),
                $request->file('archivo')
            );

            return back()->with(
                'success',
                'Las redistribuciones coincidentes fueron actualizadas automáticamente.'
            );
        } catch (\Exception $e) {

            Log::error('ERROR IMPORTANDO REMISIONES', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return back()->with(
                'error',
                'Error al importar el archivo: ' . $e->getMessage()
            );
        }
    }
}
