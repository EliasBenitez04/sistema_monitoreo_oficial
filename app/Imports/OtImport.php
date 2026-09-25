<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\Ot;
use App\Models\OtTrazabilidad;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class OtImport implements ToCollection, WithHeadingRow
{
    private $procesadas = 0;
    private $otCreadas = 0;
    private $otActualizadas = 0;
    private $trazabilidadesNuevas = 0;
    private $trazabilidadesExistentes = 0;
    private $omitidas = 0;
    private $errores = 0;
    private $observaciones = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $this->procesadas++;

            try {
                $fila = $index + 2;

                $nroOtRaw = $this->get($row, ['n_ot', 'nro_ot', 'ot', 'numero_ot', 'n°_ot', 'n_ot ']);
                $codigoRaw = $this->get($row, ['codigo', 'cod_articulo', 'codigo_articulo']);
                $descripcion = $this->get($row, ['descripcion', 'descripción', 'articulo', 'artículo']);
                $orden = $this->get($row, ['orden', 'cantidad_orden', 'cantidad']);
                $resultado = $this->get($row, ['resultado', 'cantidad_resultado', 'resultado_proceso']);
                $fechaRaw = $this->get($row, ['fecha', 'fecha_proceso', 'fecha proceso']);
                $proceso = $this->get($row, ['procesos', 'proceso', 'nombre_proceso']);

                $nroOt = $this->normalizarNroOt($nroOtRaw);
                $codigo = $this->normalizarCodigoBase($codigoRaw);
                $proceso = preg_replace('/\s+/u', ' ', trim((string) $proceso));
                $descripcion = trim((string) $descripcion);
                $orden = is_numeric($orden) ? (int) round((float) $orden) : 0;
                $resultado = is_numeric($resultado) ? (int) round((float) $resultado) : 0;
                $fecha = $this->parseDate($fechaRaw);

                if (!$nroOt || $proceso === '') {
                    $this->omitidas++;

                    $this->agregarObservacion(
                        $fila,
                        $nroOtRaw,
                        'OMITIDA: número de OT o proceso vacío'
                    );

                    Log::warning('IMPORT OT - FILA OMITIDA', [
                        'fila' => $fila,
                        'nro_ot' => $nroOtRaw,
                        'codigo' => $codigoRaw,
                        'proceso' => $proceso,
                        'motivo' => 'NRO OT O PROCESO VACIO',
                    ]);
                    continue;
                }

                if (!$fecha) {
                    $this->omitidas++;

                    $this->agregarObservacion(
                        $fila,
                        $nroOt,
                        'OMITIDA: fecha inválida [' . (string) $fechaRaw . ']'
                    );

                    Log::warning('IMPORT OT - FECHA INVALIDA', [
                        'fila' => $fila,
                        'nro_ot' => $nroOt,
                        'codigo' => $codigo,
                        'fecha_original' => $fechaRaw,
                    ]);
                    continue;
                }

                /*
                 * La OT se identifica por nro_ot, pero a diferencia del antiguo
                 * firstOrCreate actualizamos sus datos maestros cuando la OT ya
                 * existe. Así una OT creada antes con código vacío/mal formado
                 * no queda permanentemente desactualizada.
                 */
                $ot = Ot::where('nro_ot', $nroOt)->first();
                $otExistia = (bool) $ot;

                if (!$ot) {
                    $ot = new Ot();
                    $ot->nro_ot = $nroOt;
                }

                $codigoAnterior = $ot->codigo;

                if ($codigo !== '') {
                    $ot->codigo = $codigo;
                }

                if ($descripcion !== '') {
                    $ot->descripcion = $descripcion;
                }

                if ($orden > 0) {
                    $ot->cantidad_orden = $orden;
                } elseif (!$ot->exists && empty($ot->cantidad_orden)) {
                    $ot->cantidad_orden = 0;
                }

                if (!$ot->codigo) {
                    $ot->codigo = 'SIN_CODIGO';
                }

                if (!$ot->descripcion) {
                    $ot->descripcion = 'SIN_DESCRIPCION';
                }

                $ot->save();

                if ($otExistia) {
                    $this->otActualizadas++;
                } else {
                    $this->otCreadas++;
                }

                if (
                    $codigoAnterior
                    && $codigoAnterior !== $ot->codigo
                    && $codigoAnterior !== 'SIN_CODIGO'
                ) {
                    Log::warning('IMPORT OT - CODIGO ACTUALIZADO', [
                        'fila' => $fila,
                        'nro_ot' => $nroOt,
                        'codigo_anterior' => $codigoAnterior,
                        'codigo_nuevo' => $ot->codigo,
                    ]);
                }

                /*
                 * Reimportar el mismo archivo ya no duplica la trazabilidad.
                 * La clave replica la restricción lógica de la tabla:
                 * OT + proceso + fecha + resultado.
                 */
                $trazabilidad = OtTrazabilidad::firstOrCreate([
                    'id_ot' => $ot->id_ot,
                    'proceso' => $proceso,
                    'resultado' => $resultado,
                    'fecha_proceso' => $fecha,
                ]);

                if (!$trazabilidad || !$trazabilidad->id_trazabilidad) {
                    throw new \RuntimeException(
                        'La OT fue encontrada/guardada pero no se obtuvo id_trazabilidad.'
                    );
                }

                if ($trazabilidad->wasRecentlyCreated) {
                    $this->trazabilidadesNuevas++;
                } else {
                    $this->trazabilidadesExistentes++;
                }

                Log::info('IMPORT OT - OK', [
                    'fila' => $fila,
                    'id_ot' => $ot->id_ot,
                    'nro_ot' => $ot->nro_ot,
                    'codigo' => $ot->codigo,
                    'proceso' => $proceso,
                    'resultado' => $resultado,
                    'fecha' => $fecha,
                    'trazabilidad_nueva' => $trazabilidad->wasRecentlyCreated,
                ]);
            } catch (\Throwable $e) {
                $this->errores++;

                $nroError = isset($nroOt) && $nroOt ? $nroOt : ($nroOtRaw ?? null);
                $this->agregarObservacion(
                    $index + 2,
                    $nroError,
                    'ERROR: ' . $e->getMessage()
                );

                Log::error('ERROR IMPORT OT', [
                    'fila' => $index + 2,
                    'error' => $e->getMessage(),
                    'archivo' => $e->getFile(),
                    'linea' => $e->getLine(),
                    'row' => $row->toArray(),
                ]);
            }
        }
    }

    private function agregarObservacion($fila, $nroOt, string $mensaje): void
    {
        if (count($this->observaciones) >= 20) {
            return;
        }

        $this->observaciones[] = [
            'fila' => $fila,
            'nro_ot' => $nroOt,
            'mensaje' => $mensaje,
        ];
    }

    public function getObservaciones(): array
    {
        return $this->observaciones;
    }

    public function getProcesadas()
    {
        return $this->procesadas;
    }

    public function getOtCreadas()
    {
        return $this->otCreadas;
    }

    public function getOtActualizadas()
    {
        return $this->otActualizadas;
    }

    public function getTrazabilidadesNuevas()
    {
        return $this->trazabilidadesNuevas;
    }

    public function getTrazabilidadesExistentes()
    {
        return $this->trazabilidadesExistentes;
    }

    public function getOmitidas()
    {
        return $this->omitidas;
    }

    public function getErrores()
    {
        return $this->errores;
    }

    private function get($row, array $keys)
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
                return $row[$key];
            }
        }

        return null;
    }

    private function normalizarNroOt($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $texto = trim((string) $valor);
        $texto = preg_replace('/[^0-9]/', '', $texto);

        return $texto !== '' ? (int) $texto : null;
    }

    /**
     * Los códigos maestros de OT son de 9 dígitos.
     *
     * Excel puede convertir 050617600 en 50617600 si la celda es numérica.
     * En ese caso restauramos el cero inicial con str_pad.
     */
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

    private function parseDate($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }

        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
                    ->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $value = trim((string) $value);

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'] as $formato) {
            try {
                $fecha = Carbon::createFromFormat($formato, $value);

                if ($fecha !== false) {
                    return $fecha->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                // Probar siguiente formato.
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
