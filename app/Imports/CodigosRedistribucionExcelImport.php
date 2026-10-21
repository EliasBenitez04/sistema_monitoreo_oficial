<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Archivo independiente de redistribucion: solo necesita la columna "codigo".
 * Cada valor corresponde al articulo + color, sin talle.
 */
class CodigosRedistribucionExcelImport implements ToCollection, WithHeadingRow, WithChunkReading, SkipsEmptyRows
{
    public $codigos = [];
    public $invalidos = [];
    public $tieneColumnaCodigo = false;
    public $filasLeidas = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $this->filasLeidas++;
            $datos = $row->toArray();

            if (array_key_exists('codigo', $datos)) {
                $this->tieneColumnaCodigo = true;
            }

            $codigo = strtoupper(trim((string) ($datos['codigo'] ?? '')));
            if ($codigo === '') {
                continue;
            }

            if (!preg_match('/^[A-Z0-9]{6,40}$/', $codigo)) {
                if (count($this->invalidos) < 30) {
                    $this->invalidos[] = [
                        'fila' => $this->filasLeidas + 1,
                        'codigo' => $codigo,
                    ];
                }
                continue;
            }

            $this->codigos[$codigo] = true;

            if (count($this->codigos) > 500) {
                throw ValidationException::withMessages([
                    'archivo' => 'El archivo supera el limite de 500 codigos base distintos por consulta.',
                ]);
            }
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
