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

    private $procesadas = 0;
    private $cargadas = 0;
    private $sinCodigo = 0;
    private $otNoEncontrada = 0;
    private $sinProductoTerminado = 0;
    private $diferencias = 0;
    private $errores = 0;

    public function __construct($fechaProceso)
    {
        $this->fechaProceso = $fechaProceso;
    }

    public function collection(Collection $rows)
    {
        set_time_limit(0);

        foreach ($rows as $index => $row) {
            $this->procesadas++;

            DB::beginTransaction();

            try {

                // =========================================================
                // DATOS DEL EXCEL
                // =========================================================

                $codigoOriginal = $row['codigo'] ?? '';
                $nroOtOriginal = $row['nro_ot'] ?? '';

                $codigo = $this->normalizarCodigoBase($codigoOriginal);
                $nroOtExcel = $this->normalizarNroOt($nroOtOriginal);

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
                    $this->sinCodigo++;

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
                $otPorNumero = null;

                /*
                 * 1) El nro_ot es la referencia principal.
                 * 2) El código se compara NORMALIZADO para tolerar:
                 *    050617600 <-> 50617600
                 *    '050617600 <-> 050617600
                 */
                if ($nroOtExcel) {
                    $otPorNumero = Ot::where('nro_ot', $nroOtExcel)->first();

                    if ($otPorNumero) {
                        $codigoOt = $this->normalizarCodigoBase($otPorNumero->codigo);

                        if (
                            $codigoOt === $codigo
                            || $codigoOt === ''
                            || strtoupper((string) $otPorNumero->codigo) === 'SIN_CODIGO'
                        ) {
                            $ot = $otPorNumero;

                            /*
                             * Si la OT estaba creada sin código válido,
                             * la reparamos con el código del Excel logístico.
                             */
                            if (
                                $codigoOt === ''
                                || strtoupper((string) $otPorNumero->codigo) === 'SIN_CODIGO'
                            ) {
                                $ot->codigo = $codigo;
                                $ot->save();

                                Log::warning('LOGISTICA - CODIGO OT REPARADO', [
                                    'fila' => $index + 2,
                                    'nro_ot' => $nroOtExcel,
                                    'codigo_nuevo' => $codigo,
                                ]);
                            }
                        }
                    }
                }

                /*
                 * Si no coincidió por nro_ot, buscamos por código normalizado.
                 * Se consideran también códigos históricos sin cero inicial.
                 */
                if (!$ot) {
                    $alternativasCodigo = array_values(array_unique([
                        $codigo,
                        ltrim($codigo, '0'),
                    ]));

                    $ot = Ot::whereIn(
                        DB::raw("REPLACE(TRIM(codigo), '''', '')"),
                        $alternativasCodigo
                    )->first();
                }

                // =========================================================
                // OT NO ENCONTRADA / INCONSISTENTE
                // =========================================================

                if (!$ot) {
                    $this->otNoEncontrada++;

                    Log::warning('CODIGO / OT NO ENCONTRADO', [
                        'fila' => $index + 2,
                        'codigo_original' => $codigoOriginal,
                        'codigo_normalizado' => $codigo,
                        'nro_ot_original' => $nroOtOriginal,
                        'nro_ot_normalizado' => $nroOtExcel,
                        'ot_por_numero_existe' => (bool) $otPorNumero,
                        'codigo_guardado_ot' => $otPorNumero ? $otPorNumero->codigo : null,
                    ]);

                    DB::rollBack();
                    continue;
                }

                if (
                    $nroOtExcel
                    && (int) $ot->nro_ot !== (int) $nroOtExcel
                ) {
                    Log::warning('LOGISTICA - OT ENCONTRADA SOLO POR CODIGO', [
                        'fila' => $index + 2,
                        'nro_ot_excel' => $nroOtExcel,
                        'nro_ot_bd' => $ot->nro_ot,
                        'codigo' => $codigo,
                    ]);
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
                    $this->sinProductoTerminado++;

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
                     * Reimportación segura:
                     * si antes existía una cantidad para esta sucursal y el
                     * Excel corregido ahora trae 0, no dejamos el valor viejo.
                     */
                    if ($cantidad <= 0) {
                        $detalleCero = OtLogisticaDetalle::where(
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

                        if ($detalleCero) {
                            /*
                             * Conservamos el ID si existen remisiones ligadas
                             * a este detalle; en ese caso lo dejamos en 0.
                             */
                            $tieneRemisiones = DB::table('ot_logistica_remisiones')
                                ->where('id_logistica_detalle', $detalleCero->id)
                                ->exists();

                            if ($tieneRemisiones) {
                                $detalleCero->cantidad = 0;
                                $detalleCero->save();
                            } else {
                                $detalleCero->delete();
                            }

                            Log::info('DETALLE LOGISTICA LIMPIADO', [
                                'fila' => $index + 2,
                                'ot' => $ot->nro_ot,
                                'sucursal' => $sucursal,
                                'con_remisiones' => $tieneRemisiones,
                            ]);
                        }

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


                if ($totalDistribuido !== $resultado) {
                    $this->diferencias++;

                    Log::warning('LOGISTICA - RESULTADO NO COINCIDE CON DETALLES', [
                        'fila' => $index + 2,
                        'ot' => $ot->nro_ot,
                        'codigo' => $codigo,
                        'resultado_excel' => $resultado,
                        'total_detalles' => $totalDistribuido,
                        'diferencia' => $resultado - $totalDistribuido,
                    ]);
                }

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
                $this->cargadas++;


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
                $this->errores++;

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

    public function getProcesadas()
    {
        return $this->procesadas;
    }

    public function getCargadas()
    {
        return $this->cargadas;
    }

    public function getSinCodigo()
    {
        return $this->sinCodigo;
    }

    public function getOtNoEncontrada()
    {
        return $this->otNoEncontrada;
    }

    public function getSinProductoTerminado()
    {
        return $this->sinProductoTerminado;
    }

    public function getDiferencias()
    {
        return $this->diferencias;
    }

    public function getErrores()
    {
        return $this->errores;
    }

    private function normalizarNroOt($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $texto = preg_replace('/[^0-9]/', '', trim((string) $valor));

        return $texto !== '' ? (int) $texto : null;
    }

    private function normalizarCodigoBase($valor)
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        $codigo = strtoupper(trim((string) $valor));
        $codigo = ltrim($codigo, "'’`");
        $codigo = preg_replace('/\s+/u', '', $codigo);

        if (preg_match('/^(\d{1,9})$/', $codigo)) {
            return str_pad($codigo, 9, '0', STR_PAD_LEFT);
        }

        if (preg_match('/^(\d{9})/', $codigo, $match)) {
            return $match[1];
        }

        return $codigo;
    }

    }
}
