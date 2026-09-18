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
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {

            try {

                // =========================
                // 🔥 LOG DE FILA COMPLETA
                // =========================
                Log::info('IMPORT OT - FILA', [
                    'fila' => $index,
                    'row' => $row->toArray(),
                ]);

                // =========================
                // 🔥 EXTRACCIÓN SEGURA
                // =========================
                $nroOt = $this->get($row, ['n_ot', 'n_ot ', 'n° ot', 'nro_ot']);
                $codigo = $this->get($row, ['codigo']);
                $descripcion = $this->get($row, ['descripcion', 'descripción']);
                $orden = $this->get($row, ['orden']);
                $resultado = $this->get($row, ['resultado']);
                $fecha = $this->get($row, ['fecha']);
                $proceso = $this->get($row, ['procesos', 'proceso']);

                // =========================
                // 🔥 LOG EXTRACCIÓN
                // =========================
                Log::info('IMPORT OT - CAMPOS', [
                    'nroOt' => $nroOt,
                    'codigo' => $codigo,
                    'descripcion' => $descripcion,
                    'orden' => $orden,
                    'resultado' => $resultado,
                    'fecha' => $fecha,
                    'proceso' => $proceso,
                ]);

                // =========================
                // 🔥 VALIDACIÓN
                // =========================
                if (empty($nroOt) || empty($proceso)) {

                    Log::warning('FILA OMITIDA (VALIDACIÓN)', [
                        'fila' => $index,
                        'nroOt' => $nroOt,
                        'proceso' => $proceso,
                    ]);

                    continue;
                }

                $nroOt = (int) $nroOt;
                $orden = is_numeric($orden) ? (int)$orden : 0;
                $resultado = is_numeric($resultado) ? (int)$resultado : 0;

                // =========================
                // 🔥 OT
                // =========================
                $ot = Ot::firstOrCreate(
                    ['nro_ot' => $nroOt],
                    [
                        'codigo' => $codigo ?? 'SIN_CODIGO',
                        'descripcion' => $descripcion ?? 'SIN_DESCRIPCION',
                        'cantidad_orden' => $orden,
                    ]
                );

                // =========================
                // 🔥 FECHA
                // =========================
                $fecha = $this->parseDate($fecha);

                // =========================
                // 🔥 INSERT TRAZABILIDAD
                // =========================
                OtTrazabilidad::create([
                    'id_ot' => $ot->id_ot,
                    'proceso' => $proceso,
                    'resultado' => $resultado,
                    'fecha_proceso' => $fecha,
                ]);
            } catch (\Exception $e) {

                // =========================
                // 🚨 ERROR POR FILA
                // =========================
                Log::error('ERROR IMPORT OT', [
                    'fila' => $index,
                    'error' => $e->getMessage(),
                    'row' => $row->toArray(),
                ]);
            }
        }
    }

    // =========================
    // 🔥 BUSCADOR FLEXIBLE
    // =========================
    private function get($row, array $keys)
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && trim($row[$key]) !== '') {
                return $row[$key];
            }
        }
        return null;
    }

    // =========================
    // 🔥 FECHA
    // =========================
    private function parseDate($value)
    {
        if (empty($value)) return null;

        if (is_numeric($value)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
                ->format('Y-m-d');
        }

        $value = trim($value);

        try {
            return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
        } catch (\Exception $e) {
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
        }

        return null;
    }
}
