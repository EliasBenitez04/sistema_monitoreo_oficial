<?php

namespace App\Imports;

use App\Models\Ot;
use App\Models\OtTrazabilidad;
use App\Models\OtLogisticaDetalle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LogisticaImport implements ToCollection, WithHeadingRow
{
    private $fechaProceso;

    public function __construct($fechaProceso)
    {
        $this->fechaProceso = $fechaProceso;
    }

    public function collection(Collection $rows)
    {
        set_time_limit(0);

        foreach ($rows as $index => $row) {

            DB::beginTransaction();

            try {

                // =========================================================
                // DATOS DEL EXCEL
                // =========================================================

                $codigo = trim((string) ($row['codigo'] ?? ''));
                $nroOtExcel = trim((string) ($row['nro_ot'] ?? ''));

                /*
                 * IMPORTANTE:
                 *
                 * El resultado SÍ viene del Excel y SÍ se debe guardar
                 * en ot_trazabilidad.resultado.
                 *
                 * Ejemplo:
                 *
                 * resultado = 144
                 * resultado = 223
                 * resultado = 1
                 */

                $resultado = (int) ($row['resultado'] ?? 0);


                // =========================================================
                // VALIDAR CÓDIGO
                // =========================================================

                if ($codigo === '') {

                    DB::rollBack();

                    Log::warning('FILA SIN CODIGO', [
                        'fila' => $index + 2,
                    ]);

                    continue;
                }


                // =========================================================
                // BUSCAR OT
                // =========================================================

                $ot = null;

                /*
                 * Primero buscamos por:
                 *
                 * N° OT + código
                 */

                if ($nroOtExcel !== '') {

                    $ot = Ot::where('nro_ot', $nroOtExcel)
                        ->where('codigo', $codigo)
                        ->first();
                }


                /*
                 * Si no encontramos, buscamos solamente por código.
                 */

                if (!$ot) {

                    $ot = Ot::where('codigo', $codigo)
                        ->first();
                }


                // =========================================================
                // OT NO ENCONTRADA
                // =========================================================

                if (!$ot) {

                    Log::warning('CODIGO / OT NO ENCONTRADO', [

                        'fila' => $index + 2,
                        'codigo' => $codigo,
                        'nro_ot' => $nroOtExcel,

                    ]);

                    DB::rollBack();

                    continue;
                }


                // =========================================================
                // VALIDAR PRODUCTO TERMINADO
                // =========================================================

                $productoTerminado = OtTrazabilidad::where(
                    'id_ot',
                    $ot->id_ot
                )
                    ->where(
                        'proceso',
                        'TERMINACION - PRODUCTO TERMINADO'
                    )
                    ->exists();


                if (!$productoTerminado) {

                    Log::warning('OT SIN PRODUCTO TERMINADO', [

                        'fila' => $index + 2,
                        'codigo' => $codigo,
                        'nro_ot' => $ot->nro_ot,
                        'id_ot' => $ot->id_ot,

                    ]);

                    DB::rollBack();

                    continue;
                }


                // =========================================================
                // BUSCAR TRAZABILIDAD DE ESTA DISTRIBUCIÓN
                // =========================================================
                //
                // La identificación correcta es:
                //
                // OT + PROCESO + FECHA
                //
                // NO usamos resultado para buscarla.
                //
                // Esto permite tener:
                //
                // OT 29809 - 12/03/2026 - resultado 144
                // OT 29809 - 13/03/2026 - resultado 223
                // OT 29809 - 09/06/2026 - resultado 1
                //
                // como tres distribuciones independientes.
                // =========================================================

                $trazabilidad = OtTrazabilidad::where(
                    'id_ot',
                    $ot->id_ot
                )
                    ->where(
                        'proceso',
                        'LOGISTICA - LOGISTICA Y DISTRIBUCION'
                    )
                    ->where(
                        'fecha_proceso',
                        $this->fechaProceso
                    )
                    ->first();


                // =========================================================
                // CREAR TRAZABILIDAD
                // =========================================================

                if (!$trazabilidad) {

                    $trazabilidad = OtTrazabilidad::create([

                        'id_ot' => $ot->id_ot,

                        'proceso' =>
                            'LOGISTICA - LOGISTICA Y DISTRIBUCION',

                        /*
                         * EL RESULTADO DEL EXCEL SE GUARDA.
                         */
                        'resultado' => $resultado,

                        'fecha_proceso' => $this->fechaProceso,

                    ]);


                    Log::info('TRAZABILIDAD CREADA', [

                        'id_trazabilidad' =>
                            $trazabilidad->id_trazabilidad,

                        'id_ot' => $ot->id_ot,

                        'nro_ot' => $ot->nro_ot,

                        'codigo' => $codigo,

                        'resultado' => $resultado,

                        'fecha' => $this->fechaProceso,

                    ]);
                }


                // =========================================================
                // SI YA EXISTE
                // =========================================================
                //
                // Si volvemos a importar la misma OT + fecha:
                //
                // NO creamos otra trazabilidad.
                //
                // Actualizamos el resultado con el valor del Excel.
                // =========================================================

                else {

                    $trazabilidad->resultado = $resultado;

                    $trazabilidad->save();


                    Log::info('TRAZABILIDAD ACTUALIZADA', [

                        'id_trazabilidad' =>
                            $trazabilidad->id_trazabilidad,

                        'id_ot' => $ot->id_ot,

                        'nro_ot' => $ot->nro_ot,

                        'codigo' => $codigo,

                        'resultado' => $resultado,

                        'fecha' => $this->fechaProceso,

                    ]);
                }


                // =========================================================
                // SUCURSALES
                // =========================================================

                $sucursales = [

                    'SL' =>
                        $row['sl'] ?? 0,

                    'Bonanza' =>
                        $row['bonanza'] ?? 0,

                    'Shopp' =>
                        $row['shopp'] ?? 0,

                    'Luque' =>
                        $row['luque'] ?? 0,

                    'Rural' =>
                        $row['rural'] ?? 0,

                    'Mall' =>
                        $row['mall'] ?? 0,

                    'Ayala' =>
                        $row['ayala'] ?? 0,

                    'Mariano' =>
                        $row['mariano'] ?? 0,

                    'Ñemby' =>
                        $row['nemby'] ?? 0,

                    'Pinedo' =>
                        $row['pinedo'] ?? 0,

                    'L06' =>
                        $row['l06'] ?? 0,

                    'Multi' =>
                        $row['multi'] ?? 0,

                    'Los Jardines' =>
                        $row['los_jardines'] ?? 0,

                    'Modelo Muestra' =>
                        $row['modelo_muestra'] ?? 0,

                ];


                // =========================================================
                // GUARDAR DETALLES
                // =========================================================

                foreach ($sucursales as $sucursal => $cantidad) {

                    $cantidad = (int) $cantidad;


                    /*
                     * No guardamos cantidades 0.
                     */

                    if ($cantidad <= 0) {

                        continue;
                    }


                    // =====================================================
                    // BUSCAR DETALLE
                    // =====================================================
                    //
                    // Buscamos:
                    //
                    // OT
                    // +
                    // TRAZABILIDAD
                    // +
                    // SUCURSAL
                    //
                    // De esta manera cada distribución mantiene
                    // sus propios detalles.
                    // =====================================================

                    $detalle = OtLogisticaDetalle::where(
                        'id_ot',
                        $ot->id_ot
                    )
                        ->where(
                            'id_trazabilidad',
                            $trazabilidad->id_trazabilidad
                        )
                        ->where(
                            'sucursal',
                            $sucursal
                        )
                        ->first();


                    // =====================================================
                    // ACTUALIZAR DETALLE EXISTENTE
                    // =====================================================

                    if ($detalle) {

                        /*
                         * IMPORTANTE:
                         *
                         * Si volvemos a importar el mismo Excel,
                         * actualizamos la cantidad.
                         *
                         * NO sumamos.
                         */

                        $detalle->cantidad = $cantidad;

                        $detalle->save();


                        Log::info(
                            'DETALLE LOGISTICA ACTUALIZADO',
                            [

                                'id' => $detalle->id,

                                'ot' => $ot->nro_ot,

                                'id_ot' => $ot->id_ot,

                                'trazabilidad' =>
                                    $trazabilidad->id_trazabilidad,

                                'sucursal' => $sucursal,

                                'cantidad' => $cantidad,

                            ]
                        );
                    }


                    // =====================================================
                    // CREAR DETALLE
                    // =====================================================

                    else {

                        $detalle =
                            OtLogisticaDetalle::create([

                                'id_ot' =>
                                    $ot->id_ot,

                                'id_trazabilidad' =>
                                    $trazabilidad->id_trazabilidad,

                                'sucursal' =>
                                    $sucursal,

                                'cantidad' =>
                                    $cantidad,

                            ]);


                        Log::info(
                            'DETALLE LOGISTICA CREADO',
                            [

                                'id' => $detalle->id,

                                'ot' => $ot->nro_ot,

                                'id_ot' => $ot->id_ot,

                                'trazabilidad' =>
                                    $trazabilidad->id_trazabilidad,

                                'sucursal' => $sucursal,

                                'cantidad' => $cantidad,

                            ]
                        );
                    }
                }


                // =========================================================
                // TOTAL REAL DISTRIBUIDO
                // =========================================================
                //
                // ESTE VALOR NO SE GUARDA.
                //
                // Se calcula directamente desde los detalles.
                //
                // Si tenés:
                //
                // Multi = 51
                // Multi = 1
                //
                // el total será 52.
                //
                // =========================================================

                $totalDistribuido =
                    OtLogisticaDetalle::where(
                        'id_trazabilidad',
                        $trazabilidad->id_trazabilidad
                    )->sum('cantidad');


                // =========================================================
                // VERIFICACIÓN
                // =========================================================

                $detallesGuardados =
                    OtLogisticaDetalle::where(
                        'id_trazabilidad',
                        $trazabilidad->id_trazabilidad
                    )->get();


                Log::info(
                    'VERIFICACION IMPORTACION LOGISTICA',
                    [

                        'ot' =>
                            $ot->nro_ot,

                        'codigo' =>
                            $codigo,

                        'id_ot' =>
                            $ot->id_ot,

                        'id_trazabilidad' =>
                            $trazabilidad->id_trazabilidad,

                        'fecha_proceso' =>
                            $trazabilidad->fecha_proceso,

                        /*
                         * ESTE ES EL RESULTADO DEL EXCEL.
                         */
                        'resultado' =>
                            $trazabilidad->resultado,

                        /*
                         * ESTE ES EL TOTAL CALCULADO.
                         */
                        'total_distribuido' =>
                            $totalDistribuido,

                        'cantidad_detalles' =>
                            $detallesGuardados->count(),

                        'detalles' =>
                            $detallesGuardados->toArray(),

                    ]
                );


                // =========================================================
                // COMMIT
                // =========================================================

                DB::commit();


                Log::info(
                    'LOGISTICA CARGADA CORRECTAMENTE',
                    [

                        'fila' =>
                            $index + 2,

                        'codigo' =>
                            $codigo,

                        'ot' =>
                            $ot->nro_ot,

                        'id_ot' =>
                            $ot->id_ot,

                        /*
                         * RESULTADO IMPORTADO DESDE EXCEL.
                         */
                        'resultado' =>
                            $resultado,

                        'fecha_proceso' =>
                            $this->fechaProceso,

                        'trazabilidad' =>
                            $trazabilidad->id_trazabilidad,

                        /*
                         * TOTAL CALCULADO DESDE DETALLES.
                         */
                        'total_distribuido' =>
                            $totalDistribuido,

                    ]
                );
            }


            // =============================================================
            // ERROR
            // =============================================================

            catch (\Throwable $e) {

                DB::rollBack();


                Log::error(
                    'ERROR AL PROCESAR LOGISTICA',
                    [

                        'fila' =>
                            $index + 2,

                        'codigo' =>
                            $codigo ?? null,

                        'nro_ot' =>
                            $nroOtExcel ?? null,

                        'error' =>
                            $e->getMessage(),

                        'line' =>
                            $e->getLine(),

                        'file' =>
                            $e->getFile(),

                    ]
                );
            }
        }
    }
}
