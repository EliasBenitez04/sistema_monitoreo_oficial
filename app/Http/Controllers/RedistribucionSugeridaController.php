<?php

namespace App\Http\Controllers;

use App\Models\RedistribucionLote;
use App\Models\RedistribucionSugerida;
use App\Models\RedistribucionProceso;
use App\Models\RedistribucionProcesoDetalle;
use App\Models\StockVentasSucursal;
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
            'periodo'    => 'required',
            'grupo_plan' => 'nullable',
            'linea'      => 'nullable',
            'temporada'  => 'nullable',
        ]);

        DB::beginTransaction();

        try {

            /*
        |--------------------------------------------------------------------------
        | 1. ELIMINAR SUGERENCIAS PENDIENTES GENERADAS HOY
        |--------------------------------------------------------------------------
        */

            RedistribucionSugerida::whereDate(
                'fecha_generacion',
                now()->toDateString()
            )
                ->where('estado', 'PENDIENTE')
                ->delete();


            /*
        |--------------------------------------------------------------------------
        | 2. LOCALES ORIGEN
        |--------------------------------------------------------------------------
        | Orden de prioridad para entregar stock.
        |--------------------------------------------------------------------------
        */

            $localesOrigen = [
                22, // Jardines Luque
                6,  // La Rural
                3,  // Luque
                4,  // Mall
                7,  // Bonanza
                5,  // L06 San Lo2
            ];


            /*
        |--------------------------------------------------------------------------
        | 3. LOCALES DESTINO
        |--------------------------------------------------------------------------
        | Orden de prioridad para recibir stock.
        |--------------------------------------------------------------------------
        */

            $localesDestino = [
                9,  // Multiplaza
                8,  // Shop San Lo3
                14, // Pinedo
                16, // Shopping Mariano
                2,  // San Lorenzo
                15, // Ñemby
            ];


            /*
        |--------------------------------------------------------------------------
        | 4. CONSULTAR STOCK Y VENTAS
        |--------------------------------------------------------------------------
        */

            $query = StockVentasSucursal::where(
                'periodo',
                $request->periodo
            );

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
        | 5. VALIDAR DATOS
        |--------------------------------------------------------------------------
        */

            if ($datos->isEmpty()) {

                DB::rollBack();

                return redirect()
                    ->route('RedistribucionSugeridas.index')
                    ->with(
                        'warning',
                        'No se encontraron datos para analizar.'
                    );
            }


            /*
        |--------------------------------------------------------------------------
        | 6. CÓDIGOS BLOQUEADOS
        |--------------------------------------------------------------------------
        */

            $codigosBloqueados = collect();


            /*
        |--------------------------------------------------------------------------
        | 7. REDISTRIBUCIONES PENDIENTES / EN PROCESO
        |--------------------------------------------------------------------------
        */

            $codigosPendientes = RedistribucionProcesoDetalle::whereIn(
                'estado',
                [
                    'PENDIENTE',
                    'EN PROCESO'
                ]
            )
                ->pluck('codigo')
                ->filter()
                ->unique();

            $codigosBloqueados = $codigosBloqueados
                ->merge($codigosPendientes);


            /*
        |--------------------------------------------------------------------------
        | 8. REDISTRIBUCIONES FINALIZADAS RECIENTES
        |--------------------------------------------------------------------------
        */

            $codigosFinalizados = RedistribucionProcesoDetalle::where(
                'redistribucion_proceso_detalle.estado',
                'FINALIZADO'
            )
                ->join(
                    'redistribucion_lote',
                    'redistribucion_lote.id',
                    '=',
                    'redistribucion_proceso_detalle.lote_id'
                )
                ->where(
                    'redistribucion_lote.fecha_finalizacion',
                    '>=',
                    now()->subDays(30)
                )
                ->pluck(
                    'redistribucion_proceso_detalle.codigo'
                )
                ->filter()
                ->unique();


            $codigosBloqueados = $codigosBloqueados
                ->merge($codigosFinalizados)
                ->unique()
                ->values();


            /*
        |--------------------------------------------------------------------------
        | 9. AGRUPAR POR CÓDIGO
        |--------------------------------------------------------------------------
        */

            $datosPorCodigo = $datos->groupBy('codigo');


            /*
        |--------------------------------------------------------------------------
        | 10. PREPARAR SUGERENCIAS
        |--------------------------------------------------------------------------
        */

            $sugerencias = [];

            $cantidadSugerencias = 0;

            /*
        | Total de unidades transferidas.
        | Esto sirve para saber cuánto stock realmente se redistribuyó.
        */

            $totalUnidadesTransferidas = 0;


            /*
        |--------------------------------------------------------------------------
        | 11. ANALIZAR CADA CÓDIGO
        |--------------------------------------------------------------------------
        */

            foreach ($datosPorCodigo as $codigo => $items) {

                /*
            |--------------------------------------------------------------------------
            | BLOQUEO DE CÓDIGOS
            |--------------------------------------------------------------------------
            |
            | Actualmente sigue desactivado, igual que en tu código.
            |
            */

                /*
            if ($codigosBloqueados->contains($codigo)) {
                continue;
            }
            */


                $origenes = [];

                $destinos = [];


                /*
            |--------------------------------------------------------------------------
            | 12. ANALIZAR CADA SUCURSAL
            |--------------------------------------------------------------------------
            */

                foreach ($items as $item) {

                    $sucursalId = (int) $item->sucursal_id;

                    $venta = (int) $item->cant_vta;

                    $stock = (int) $item->stock_actual;


                    /*
                |--------------------------------------------------------------------------
                | ORIGEN
                |--------------------------------------------------------------------------
                |
                | EL ORIGEN CONSERVA SOLAMENTE EL 5% DE SUS VENTAS.
                |
                | Esto libera bastante stock para redistribuir.
                |
                */

                    $stockObjetivoOrigen = (int) ceil(
                        $venta * 0.05
                    );


                    /*
                |--------------------------------------------------------------------------
                | EXCESO DEL ORIGEN
                |--------------------------------------------------------------------------
                */

                    $exceso = $stock - $stockObjetivoOrigen;


                    /*
                |--------------------------------------------------------------------------
                | DESTINO
                |--------------------------------------------------------------------------
                |
                | IMPORTANTE:
                |
                | El destino ahora busca llegar al 200% de sus ventas.
                |
                | Ejemplo:
                |
                | Venta = 100
                | Stock = 20
                |
                | Objetivo anterior:
                | 100
                |
                | Necesidad:
                | 100 - 20 = 80
                |
                |
                | Objetivo nuevo:
                | 200
                |
                | Necesidad:
                | 200 - 20 = 180
                |
                | Esto permite absorber mucho más stock.
                |
                */

                    $stockObjetivoDestino = (int) ceil(
                        $venta * 2.00
                    );

                    $necesidad = $stockObjetivoDestino - $stock;


                    /*
                |--------------------------------------------------------------------------
                | 13. DESTINO
                |--------------------------------------------------------------------------
                */

                    if (
                        in_array(
                            $sucursalId,
                            $localesDestino
                        )
                        &&
                        $venta >= 0
                        &&
                        $necesidad >= 0
                    ) {

                        $destinos[] = [

                            'sucursal_id'   => $sucursalId,

                            'venta'         => $venta,

                            'stock'         => $stock,

                            'stockObjetivo' => $stockObjetivoDestino,

                            'necesidad'     => $necesidad,
                        ];
                    }


                    /*
                |--------------------------------------------------------------------------
                | 14. ORIGEN
                |--------------------------------------------------------------------------
                */

                    if (
                        in_array(
                            $sucursalId,
                            $localesOrigen
                        )
                        &&
                        $exceso >= 0
                    ) {

                        $origenes[] = [

                            'sucursal_id'   => $sucursalId,

                            'venta'         => $venta,

                            'stock'         => $stock,

                            'stockObjetivo' => $stockObjetivoOrigen,

                            'exceso'        => $exceso,
                        ];
                    }
                }


                /*
            |--------------------------------------------------------------------------
            | 15. ORDENAR DESTINOS
            |--------------------------------------------------------------------------
            */

                usort(
                    $destinos,
                    function ($a, $b) use ($localesDestino) {

                        $posA = array_search(
                            $a['sucursal_id'],
                            $localesDestino
                        );

                        $posB = array_search(
                            $b['sucursal_id'],
                            $localesDestino
                        );

                        return $posA <=> $posB;
                    }
                );


                /*
            |--------------------------------------------------------------------------
            | 16. ORDENAR ORÍGENES
            |--------------------------------------------------------------------------
            */

                usort(
                    $origenes,
                    function ($a, $b) use ($localesOrigen) {

                        $posA = array_search(
                            $a['sucursal_id'],
                            $localesOrigen
                        );

                        $posB = array_search(
                            $b['sucursal_id'],
                            $localesOrigen
                        );

                        return $posA <=> $posB;
                    }
                );


                /*
            |--------------------------------------------------------------------------
            | 17. REDISTRIBUIR
            |--------------------------------------------------------------------------
            */

                foreach ($destinos as $destino) {

                    /*
                |--------------------------------------------------------------------------
                | EL DESTINO PUEDE RECIBIR TODA SU NECESIDAD
                |--------------------------------------------------------------------------
                */

                    $pendienteTransferir =
                        (int) $destino['necesidad'];


                    /*
                |--------------------------------------------------------------------------
                | BUSCAR ORIGEN POR ORDEN DE PRIORIDAD
                |--------------------------------------------------------------------------
                */

                    foreach ($origenes as &$origen) {

                        if ($pendienteTransferir <= 0) {
                            break;
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | NO TRANSFERIR A LA MISMA SUCURSAL
                    |--------------------------------------------------------------------------
                    */

                        if (
                            $origen['sucursal_id'] ===
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

                        $motivo =
                            'Redistribución automática: ' .
                            'origen con exceso de stock y destino con necesidad. ' .
                            'Origen conserva 5% de ventas y destino busca alcanzar 200% de ventas.';


                        /*
                    |--------------------------------------------------------------------------
                    | CREAR SUGERENCIA
                    |--------------------------------------------------------------------------
                    */

                        $sugerencias[] = [

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
                            now(),
                        ];


                        /*
                    |--------------------------------------------------------------------------
                    | DESCONTAR DEL EXCESO DEL ORIGEN
                    |--------------------------------------------------------------------------
                    */

                        $origen['exceso'] -= $cantidad;


                        /*
                    |--------------------------------------------------------------------------
                    | DESCONTAR LA NECESIDAD DEL DESTINO
                    |--------------------------------------------------------------------------
                    */

                        $pendienteTransferir -= $cantidad;


                        /*
                    |--------------------------------------------------------------------------
                    | CONTAR SUGERENCIA
                    |--------------------------------------------------------------------------
                    */

                        $cantidadSugerencias++;


                        /*
                    |--------------------------------------------------------------------------
                    | CONTAR UNIDADES REALES TRANSFERIDAS
                    |--------------------------------------------------------------------------
                    */

                        $totalUnidadesTransferidas += $cantidad;
                    }

                    unset($origen);
                }
            }


            /*
        |--------------------------------------------------------------------------
        | 18. INSERTAR EN BLOQUES DE 1000
        |--------------------------------------------------------------------------
        */

            if (!empty($sugerencias)) {

                foreach (
                    array_chunk(
                        $sugerencias,
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
        | 20. RESULTADO SIN SUGERENCIAS
        |--------------------------------------------------------------------------
        */

            if ($cantidadSugerencias === 0) {

                return redirect()
                    ->route(
                        'RedistribucionSugeridas.index'
                    )
                    ->with(
                        'warning',
                        'El análisis terminó, pero no se encontraron redistribuciones necesarias.'
                    );
            }


            /*
        |--------------------------------------------------------------------------
        | 21. RESULTADO FINAL
        |--------------------------------------------------------------------------
        */

            return redirect()
                ->route(
                    'RedistribucionSugeridas.index'
                )
                ->with(
                    'success',
                    'Análisis realizado correctamente. Se generaron '
                        . $cantidadSugerencias
                        . ' sugerencias con '
                        . $totalUnidadesTransferidas
                        . ' unidades de stock para redistribuir.'
                );
        } catch (\Throwable $e) {

            DB::rollBack();


            /*
        |--------------------------------------------------------------------------
        | REGISTRAR ERROR
        |--------------------------------------------------------------------------
        */

            Log::error(
                'Error al analizar redistribución sugerida',
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


            return redirect()
                ->route(
                    'RedistribucionSugeridas.index'
                )
                ->with(
                    'error',
                    'Ocurrió un error al realizar el análisis: '
                        . $e->getMessage()
                );
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
