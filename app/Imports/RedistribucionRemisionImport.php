<?php

namespace App\Imports;

use App\Models\RedistribucionProcesoDetalle;
use App\Models\RedistribucionRemision;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RedistribucionRemisionImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {

            DB::beginTransaction();

            try {

                // =====================================================
                // LOG DE FILA COMPLETA
                // =====================================================

                Log::info('IMPORT REDISTRIBUCION - FILA', [
                    'fila' => $index,
                    'row' => $row->toArray(),
                ]);

                // =====================================================
                // EXTRAER DATOS DEL EXCEL
                // =====================================================

                $fechaRemision = $this->get($row, [
                    'fecha_remision',
                    'fecha remision',
                ]);

                $fechaCreacion = $this->get($row, [
                    'fecha_creacion',
                    'fecha creacion',
                ]);

                $fechaRecepcion = $this->get($row, [
                    'fecha_recepcion',
                    'fecha recepcion',
                ]);

                $sucursalOrigen = $this->get($row, [
                    'cod_sucursal_salida',
                    'cod sucursal salida',
                ]);

                $sucursalDestino = $this->get($row, [
                    'cod_sucursal',
                    'cod sucursal',
                ]);

                $codigo = $this->get($row, [
                    'cod_articulo',
                    'cod articulo',
                ]);

                $serie = $this->get($row, [
                    'serie',
                ]);

                $numeroRemision = $this->get($row, [
                    'numero_remision',
                    'numero remision',
                ]);

                $cantidadTransferida = $this->get($row, [
                    'cantidad',
                ]);

                // =====================================================
                // LOG DE CAMPOS
                // =====================================================

                Log::info('IMPORT REDISTRIBUCION - CAMPOS', [
                    'fila' => $index,
                    'fechaRemision' => $fechaRemision,
                    'fechaCreacion' => $fechaCreacion,
                    'fechaRecepcion' => $fechaRecepcion,
                    'sucursalOrigen' => $sucursalOrigen,
                    'sucursalDestino' => $sucursalDestino,
                    'codigo' => $codigo,
                    'serie' => $serie,
                    'numeroRemision' => $numeroRemision,
                    'cantidadTransferida' => $cantidadTransferida,
                ]);

                // =====================================================
                // VALIDACIONES BÁSICAS
                // =====================================================

                if (
                    empty($sucursalOrigen) ||
                    empty($sucursalDestino) ||
                    empty($codigo)
                ) {

                    Log::warning('FILA OMITIDA - DATOS INCOMPLETOS', [
                        'fila' => $index,
                        'sucursalOrigen' => $sucursalOrigen,
                        'sucursalDestino' => $sucursalDestino,
                        'codigo' => $codigo,
                    ]);

                    DB::rollBack();

                    continue;
                }

                if (empty($serie) || empty($numeroRemision)) {

                    Log::warning('FILA OMITIDA - REMISIÓN SIN IDENTIFICADOR', [
                        'fila' => $index,
                        'serie' => $serie,
                        'numeroRemision' => $numeroRemision,
                        'codigo' => $codigo,
                    ]);

                    DB::rollBack();

                    continue;
                }

                if (
                    $cantidadTransferida === null ||
                    $cantidadTransferida === '' ||
                    !is_numeric(
                        str_replace(
                            [',', '.'],
                            '',
                            str_replace(' ', '', (string) $cantidadTransferida)
                        )
                    )
                ) {

                    Log::warning('FILA OMITIDA - CANTIDAD INVÁLIDA', [
                        'fila' => $index,
                        'cantidad' => $cantidadTransferida,
                        'codigo' => $codigo,
                    ]);

                    DB::rollBack();

                    continue;
                }

                // =====================================================
                // NORMALIZAR DATOS
                // =====================================================

                $sucursalOrigen = (int) $sucursalOrigen;
                $sucursalDestino = (int) $sucursalDestino;

                // Código:
                // '100617481VD02
                // 100617481VD02

                $codigo = trim((string) $codigo);
                $codigo = ltrim($codigo, "'");

                // Serie

                $serie = trim((string) $serie);

                // Número de remisión

                $numeroRemision = (int) $numeroRemision;

                // Cantidad

                $cantidadTransferida = $this->parseQuantity(
                    $cantidadTransferida
                );

                if ($cantidadTransferida <= 0) {

                    Log::warning('FILA OMITIDA - CANTIDAD MENOR O IGUAL A CERO', [
                        'fila' => $index,
                        'cantidad' => $cantidadTransferida,
                        'codigo' => $codigo,
                    ]);

                    DB::rollBack();

                    continue;
                }

                // =====================================================
                // CONVERTIR FECHAS
                // =====================================================

                $fechaRemision = $this->parseDate($fechaRemision);
                $fechaCreacion = $this->parseDate($fechaCreacion);
                $fechaRecepcion = $this->parseDate($fechaRecepcion);

                Log::info('IMPORT REDISTRIBUCION - FECHAS CONVERTIDAS', [
                    'fila' => $index,
                    'fechaRemision' => $fechaRemision,
                    'fechaCreacion' => $fechaCreacion,
                    'fechaRecepcion' => $fechaRecepcion,
                ]);

                // =====================================================
                // BUSCAR DETALLES
                // =====================================================

                $detalles = RedistribucionProcesoDetalle::where(
                    'sucursal_origen',
                    $sucursalOrigen
                )
                    ->where(
                        'sucursal_destino',
                        $sucursalDestino
                    )
                    ->where(
                        'codigo',
                        $codigo
                    )
                    ->orderBy('id')
                    ->get();

                if ($detalles->isEmpty()) {

                    Log::warning('REDISTRIBUCIÓN NO ENCONTRADA', [
                        'fila' => $index,
                        'sucursalOrigen' => $sucursalOrigen,
                        'sucursalDestino' => $sucursalDestino,
                        'codigo' => $codigo,
                        'serie' => $serie,
                        'numeroRemision' => $numeroRemision,
                    ]);

                    DB::rollBack();

                    continue;
                }

                // =====================================================
                // PRIMERO:
                // BUSCAR SI ESTA REMISIÓN YA EXISTE
                //
                // IMPORTANTE:
                //
                // Esto se hace ANTES de buscar cantidad pendiente.
                //
                // De esta manera, si el detalle ya está COMPLETO pero
                // posteriormente importamos la fecha de recepción,
                // podemos actualizar la remisión existente.
                // =====================================================

                $remisionExistente = null;
                $detalleDeRemisionExistente = null;

                foreach ($detalles as $detalleItem) {

                    $remision = RedistribucionRemision::where(
                        'detalle_id',
                        $detalleItem->id
                    )
                        ->where(
                            'serie',
                            $serie
                        )
                        ->where(
                            'numero_remision',
                            $numeroRemision
                        )
                        ->first();

                    if ($remision) {

                        $remisionExistente = $remision;
                        $detalleDeRemisionExistente = $detalleItem;

                        break;
                    }
                }

                // =====================================================
                // SI LA REMISIÓN YA EXISTE
                //
                // NO LA DUPLICAMOS.
                //
                // ACTUALIZAMOS LAS FECHAS QUE VIENEN DEL EXCEL.
                // =====================================================

                if ($remisionExistente) {

                    $detalle = $detalleDeRemisionExistente;

                    $actualizado = false;

                    // -------------------------------------------------
                    // FECHA REMISIÓN
                    // -------------------------------------------------

                    if ($fechaRemision) {

                        $remisionExistente->fecha_remision = $fechaRemision;

                        if (!$detalle->fecha_remision) {
                            $detalle->fecha_remision = $fechaRemision;
                        }

                        $actualizado = true;
                    }

                    // -------------------------------------------------
                    // FECHA CREACIÓN
                    // -------------------------------------------------

                    if ($fechaCreacion) {

                        $remisionExistente->fecha_creacion = $fechaCreacion;

                        $actualizado = true;
                    }

                    // -------------------------------------------------
                    // FECHA RECEPCIÓN
                    // -------------------------------------------------

                    if ($fechaRecepcion) {

                        $remisionExistente->fecha_recepcion = $fechaRecepcion;

                        $detalle->fecha_recepcion = $fechaRecepcion;

                        $actualizado = true;
                    }

                    // -------------------------------------------------
                    // CANTIDAD
                    //
                    // No modificamos la cantidad de una remisión
                    // existente automáticamente.
                    //
                    // Esto evita alterar cantidades ya importadas.
                    // -------------------------------------------------

                    if ($actualizado) {

                        $remisionExistente->save();

                        // =============================================
                        // RECALCULAR ESTADO DEL DETALLE
                        // =============================================

                        $totalTransferido = RedistribucionRemision::where(
                            'detalle_id',
                            $detalle->id
                        )->sum('cantidad_transferida');

                        $cantidadPedida = (int) $detalle->cantidad;

                        $diferencia = $cantidadPedida - $totalTransferido;

                        if ($totalTransferido > $cantidadPedida) {

                            $estadoCantidad = 'EXCEDENTE';
                        } elseif ($totalTransferido == $cantidadPedida) {

                            $estadoCantidad = 'COMPLETO';
                        } else {

                            $estadoCantidad = 'PARCIAL';
                        }

                        // =============================================
                        // ACTUALIZAR ESTADO
                        // =============================================

                        if ($estadoCantidad === 'COMPLETO') {

                            if ($fechaRecepcion || $detalle->fecha_recepcion) {
                                $detalle->estado = 'FINALIZADO';
                            } else {
                                $detalle->estado = 'REALIZADO';
                            }
                        } elseif ($estadoCantidad === 'EXCEDENTE') {

                            if ($fechaRecepcion || $detalle->fecha_recepcion) {
                                $detalle->estado = 'FINALIZADO';
                            } else {
                                $detalle->estado = 'REALIZADO';
                            }
                        } else {

                            // PARCIAL
                            if ($fechaRecepcion || $detalle->fecha_recepcion) {
                                $detalle->estado = 'FINALIZADO';
                            } elseif ($fechaRemision || $detalle->fecha_remision) {
                                $detalle->estado = 'REALIZADO';
                            }
                        }

                        // =============================================
                        // OBSERVACIÓN
                        // =============================================

                        $detalle->observacion =
                            'Pedido: ' . $cantidadPedida .
                            ' | Transferido: ' . $totalTransferido .
                            ' | Diferencia: ' . $diferencia .
                            ' | Resultado: ' . $estadoCantidad;

                        $detalle->save();
                    }

                    DB::commit();

                    Log::info(
                        'REMISIÓN YA EXISTENTE - FECHAS ACTUALIZADAS',
                        [
                            'fila' => $index,

                            'detalle_id' => $detalle->id,

                            'remision_id' => $remisionExistente->id,

                            'codigo' => $codigo,

                            'origen' => $sucursalOrigen,
                            'destino' => $sucursalDestino,

                            'serie' => $serie,
                            'numero_remision' => $numeroRemision,

                            'fecha_remision' => $fechaRemision,
                            'fecha_creacion' => $fechaCreacion,
                            'fecha_recepcion' => $fechaRecepcion,

                            'estado_final' => $detalle->estado,
                        ]
                    );

                    continue;
                }

                // =====================================================
                // BUSCAR EL DETALLE CORRECTO
                //
                // SOLAMENTE llegamos acá si la remisión NO existe.
                //
                // Preferimos un detalle que todavía tenga saldo.
                // =====================================================

                $detalleSeleccionado = null;

                foreach ($detalles as $detalleItem) {

                    $totalTransferido = RedistribucionRemision::where(
                        'detalle_id',
                        $detalleItem->id
                    )->sum('cantidad_transferida');

                    $pendiente = (int) $detalleItem->cantidad
                        - (int) $totalTransferido;

                    if ($pendiente >= $cantidadTransferida) {

                        $detalleSeleccionado = $detalleItem;

                        break;
                    }
                }

                // =====================================================
                // SEGUNDO INTENTO
                //
                // Si no encontramos uno con saldo suficiente,
                // buscamos cualquiera que tenga saldo pendiente.
                //
                // Esto mantiene compatibilidad con el comportamiento
                // anterior y permite importaciones parciales.
                // =====================================================

                if (!$detalleSeleccionado) {

                    foreach ($detalles as $detalleItem) {

                        $totalTransferido = RedistribucionRemision::where(
                            'detalle_id',
                            $detalleItem->id
                        )->sum('cantidad_transferida');

                        $pendiente = (int) $detalleItem->cantidad
                            - (int) $totalTransferido;

                        if ($pendiente > 0) {

                            $detalleSeleccionado = $detalleItem;

                            break;
                        }
                    }
                }

                // =====================================================
                // SI NO HAY DETALLE CON SALDO PENDIENTE
                // =====================================================

                if (!$detalleSeleccionado) {

                    Log::warning(
                        'NO SE ENCONTRÓ DETALLE CON CANTIDAD PENDIENTE',
                        [
                            'fila' => $index,
                            'codigo' => $codigo,
                            'origen' => $sucursalOrigen,
                            'destino' => $sucursalDestino,
                            'serie' => $serie,
                            'numeroRemision' => $numeroRemision,
                            'cantidad_excel' => $cantidadTransferida,
                        ]
                    );

                    DB::rollBack();

                    continue;
                }

                $detalle = $detalleSeleccionado;

                // =====================================================
                // GUARDAR LA REMISIÓN
                // =====================================================

                $remision = new RedistribucionRemision();

                $remision->detalle_id = $detalle->id;
                $remision->serie = $serie;
                $remision->numero_remision = $numeroRemision;
                $remision->fecha_remision = $fechaRemision;
                $remision->fecha_creacion = $fechaCreacion;
                $remision->fecha_recepcion = $fechaRecepcion;
                $remision->cantidad_transferida = $cantidadTransferida;

                $remision->save();

                // =====================================================
                // CALCULAR TOTAL TRANSFERIDO
                //
                // Sumamos TODAS las remisiones del detalle.
                // =====================================================

                $totalTransferido = RedistribucionRemision::where(
                    'detalle_id',
                    $detalle->id
                )->sum('cantidad_transferida');

                $cantidadPedida = (int) $detalle->cantidad;

                $diferencia = $cantidadPedida - $totalTransferido;

                // =====================================================
                // DETERMINAR ESTADO
                // =====================================================

                if ($totalTransferido > $cantidadPedida) {

                    $estadoCantidad = 'EXCEDENTE';
                } elseif ($totalTransferido == $cantidadPedida) {

                    $estadoCantidad = 'COMPLETO';
                } else {

                    $estadoCantidad = 'PARCIAL';
                }

                // =====================================================
                // ACTUALIZAR FECHAS DEL DETALLE
                //
                // Se mantienen por compatibilidad con tu sistema.
                // =====================================================

                if ($fechaRemision) {

                    $detalle->fecha_remision = $fechaRemision;
                }

                if ($fechaRecepcion) {

                    $detalle->fecha_recepcion = $fechaRecepcion;
                }

                // =====================================================
                // ACTUALIZAR ESTADO DEL DETALLE

                // =====================================================
                if ($estadoCantidad === 'COMPLETO') {

                    if ($fechaRecepcion) {
                        $detalle->estado = 'FINALIZADO';
                    } else {
                        $detalle->estado = 'REALIZADO';
                    }
                } elseif ($estadoCantidad === 'EXCEDENTE') {

                    if ($fechaRecepcion) {
                        $detalle->estado = 'FINALIZADO';
                    } else {
                        $detalle->estado = 'REALIZADO';
                    }
                } else {

                    // PARCIAL
                    // Si ya tenemos fecha de recepción,
                    // significa que la transferencia terminó aunque
                    // la cantidad haya sido incompleta.
                    if ($fechaRecepcion) {
                        $detalle->estado = 'FINALIZADO';
                    } elseif ($fechaRemision) {
                        $detalle->estado = 'REALIZADO';
                    }
                }

                // =====================================================
                // OBSERVACIÓN
                // =====================================================

                $detalle->observacion =
                    'Pedido: ' . $cantidadPedida .
                    ' | Transferido: ' . $totalTransferido .
                    ' | Diferencia: ' . $diferencia .
                    ' | Resultado: ' . $estadoCantidad;

                $detalle->save();

                // =====================================================
                // COMMIT
                // =====================================================

                DB::commit();

                // =====================================================
                // LOG FINAL
                // =====================================================

                Log::info(
                    'REDISTRIBUCIÓN ACTUALIZADA CORRECTAMENTE',
                    [
                        'fila' => $index,

                        'detalle_id' => $detalle->id,

                        'remision_id' => $remision->id,

                        'codigo' => $codigo,

                        'origen' => $sucursalOrigen,
                        'destino' => $sucursalDestino,

                        'serie' => $serie,
                        'numero_remision' => $numeroRemision,

                        'cantidad_pedida' => $cantidadPedida,
                        'cantidad_remision' => $cantidadTransferida,
                        'cantidad_transferida_total' => $totalTransferido,

                        'diferencia' => $diferencia,
                        'resultado_cantidad' => $estadoCantidad,

                        'fecha_remision' => $fechaRemision,
                        'fecha_creacion' => $fechaCreacion,
                        'fecha_recepcion' => $fechaRecepcion,

                        'estado_final' => $detalle->estado,
                    ]
                );
            } catch (\Throwable $e) {

                DB::rollBack();

                // =====================================================
                // ERROR POR FILA
                // =====================================================

                Log::error(
                    'ERROR IMPORT REDISTRIBUCION',
                    [
                        'fila' => $index,
                        'error' => $e->getMessage(),
                        'archivo' => $e->getFile(),
                        'linea' => $e->getLine(),
                        'row' => $row->toArray(),
                    ]
                );
            }
        }
    }

    // =========================================================
    // BUSCADOR FLEXIBLE
    // =========================================================

    private function get($row, array $keys)
    {
        foreach ($keys as $key) {

            if (
                isset($row[$key]) &&
                trim((string) $row[$key]) !== ''
            ) {

                return $row[$key];
            }
        }

        return null;
    }

    // =========================================================
    // CONVERSIÓN DE CANTIDAD
    // =========================================================

    private function parseQuantity($value): int
    {
        if ($value === null || $value === '') {

            return 0;
        }

        // Eliminar espacios

        $value = trim((string) $value);

        $value = str_replace(' ', '', $value);

        // Si tiene coma y punto:
        //
        // 1.234,00 -> 1234

        if (
            str_contains($value, ',') &&
            str_contains($value, '.')
        ) {

            $value = str_replace('.', '', $value);

            $value = str_replace(',', '.', $value);
        } elseif (str_contains($value, ',')) {

            // 10,00

            $value = str_replace(',', '.', $value);
        }

        return (int) round((float) $value);
    }

    // =========================================================
    // CONVERSIÓN DE FECHAS
    // =========================================================

    private function parseDate($value)
    {
        if ($value === null || $value === '') {

            return null;
        }

        // =====================================================
        // FECHA SERIAL DE EXCEL
        // =====================================================

        if (is_numeric($value)) {

            try {

                return \PhpOffice\PhpSpreadsheet\Shared\Date
                    ::excelToDateTimeObject($value)
                    ->format('Y-m-d');
            } catch (\Throwable $e) {

                Log::warning(
                    'ERROR CONVIRTIENDO FECHA SERIAL EXCEL',
                    [
                        'valor' => $value,
                        'error' => $e->getMessage(),
                    ]
                );

                return null;
            }
        }

        $value = trim((string) $value);

        // =====================================================
        // NORMALIZAR MESES EN ESPAÑOL
        // =====================================================

        $meses = [
            'ene' => 'Jan',
            'enero' => 'January',

            'feb' => 'Feb',
            'febrero' => 'February',

            'mar' => 'Mar',
            'marzo' => 'March',

            'abr' => 'Apr',
            'abril' => 'April',

            'may' => 'May',
            'mayo' => 'May',

            'jun' => 'Jun',
            'junio' => 'June',

            'jul' => 'Jul',
            'julio' => 'July',

            'ago' => 'Aug',
            'agosto' => 'August',

            'sep' => 'Sep',
            'sept' => 'Sep',
            'septiembre' => 'September',

            'oct' => 'Oct',
            'octubre' => 'October',

            'nov' => 'Nov',
            'noviembre' => 'November',

            'dic' => 'Dec',
            'diciembre' => 'December',
        ];

        $valor = strtolower($value);

        foreach ($meses as $es => $en) {

            $valor = preg_replace(
                '/\b' . preg_quote($es, '/') . '\b/u',
                $en,
                $valor
            );
        }

        // =====================================================
        // FORMATO:
        // 24-ago-26
        // =====================================================

        try {

            return Carbon::createFromFormat(
                'd-M-y',
                $valor
            )->format('Y-m-d');
        } catch (\Throwable $e) {
        }

        // =====================================================
        // FORMATO:
        // 24/08/2026
        // =====================================================

        try {

            return Carbon::createFromFormat(
                'd/m/Y',
                $valor
            )->format('Y-m-d');
        } catch (\Throwable $e) {
        }

        // =====================================================
        // FORMATO:
        // 24-08-2026
        // =====================================================

        try {

            return Carbon::createFromFormat(
                'd-m-Y',
                $valor
            )->format('Y-m-d');
        } catch (\Throwable $e) {
        }

        // =====================================================
        // INTENTO GENERAL
        // =====================================================

        try {

            return Carbon::parse($valor)
                ->format('Y-m-d');
        } catch (\Throwable $e) {
        }

        Log::warning('FECHA NO PUDO SER CONVERTIDA', [
            'valor_original' => $value,
        ]);

        return null;
    }
}
