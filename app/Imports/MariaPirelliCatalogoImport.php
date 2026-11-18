<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class MariaPirelliCatalogoImport extends StringValueBinder implements
    ToCollection, WithHeadingRow, WithCustomValueBinder, WithMultipleSheets
{
    public int $articulos = 0;

    public function sheets(): array
    {
        return [0 => $this];
    }

    public function collection(Collection $rows): void
    {
        $primeraFila = $rows->first();
        if ($primeraFila !== null) {
            $this->validarEncabezados($this->campos($primeraFila));
        }

        $registros = [];
        $errores = [];
        $ahora = now();

        foreach ($rows as $indice => $row) {
            if ($row->every(fn ($valor) => $valor === null || (is_string($valor) && trim($valor) === ''))) {
                continue;
            }

            $campos = $this->campos($row);
            $numeroFila = $indice + 2;
            $codigo = strtoupper(ltrim(trim((string) ($campos['codigo'] ?? '')), "'’‘`"));
            $descripcion = trim((string) ($campos['descripcion'] ?? ''));
            $linea = trim((string) ($campos['linea'] ?? ''));
            $costo = AccesoriosCompraImport::importe($campos['costo'] ?? null);
            $precio = AccesoriosCompraImport::importe($campos['precvent'] ?? $campos['precioventa'] ?? null);
            $fecha = AccesoriosCompraImport::fecha($campos['fechaenvio'] ?? null);

            $problemas = [];
            if ($codigo === '' || mb_strlen($codigo) > 50) {
                $problemas[] = 'CODIGO debe tener entre 1 y 50 caracteres';
            }
            if ($descripcion === '' || mb_strlen($descripcion) > 200) {
                $problemas[] = 'DESCRIPCION debe tener entre 1 y 200 caracteres';
            }
            if ($costo === null || $costo < 0 || round($costo, 2) >= 1e13) {
                $problemas[] = 'COSTO debe ser un importe válido mayor o igual a cero';
            }
            if ($precio === null || $precio < 0 || round($precio, 2) >= 1e13) {
                $problemas[] = 'PREC. VENT. debe ser un importe válido mayor o igual a cero';
            }
            if ($fecha === null || strlen($fecha) !== 10 || substr($fecha, 0, 4) === '0000') {
                $problemas[] = 'FECHA ENVIO debe ser una fecha válida, por ejemplo 18/11/2026';
            }
            if (mb_strlen($linea) > 100) {
                $problemas[] = 'LINEA admite hasta 100 caracteres';
            }
            if ($problemas) {
                $errores[] = "Fila {$numeroFila}. " . implode('. ', $problemas) . '.';
                continue;
            }

            $registro = [
                'codigo' => $codigo,
                'descripcion' => $descripcion,
                'costo_unitario' => round($costo, 2),
                'precio_venta' => round($precio, 2),
                'fecha_envio' => $fecha,
                'linea' => $linea !== '' ? $linea : null,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];

            if (isset($registros[$codigo]) && $registros[$codigo] !== $registro) {
                $errores[] = "Fila {$numeroFila}. El código {$codigo} ya figura con otros datos en el archivo.";
                continue;
            }
            $registros[$codigo] = $registro;
        }

        if ($errores) {
            throw ValidationException::withMessages(['archivo' => array_slice($errores, 0, 15)]);
        }
        if (!$registros) {
            throw ValidationException::withMessages([
                'archivo' => 'Cargá al menos un artículo debajo de los encabezados de la primera hoja.',
            ]);
        }

        ksort($registros, SORT_STRING);
        DB::transaction(function () use ($registros) {
            foreach (array_chunk($registros, 200) as $lote) {
                DB::table('maria_pirelli_articulos')->upsert($lote, ['codigo'], [
                    'descripcion', 'costo_unitario', 'precio_venta', 'fecha_envio', 'linea', 'updated_at',
                ]);
            }
        });

        $this->articulos = count($registros);
    }

    private function campos(Collection $fila): array
    {
        $campos = [];
        foreach ($fila as $nombre => $valor) {
            $clave = preg_replace('/[^a-z0-9]/', '', strtolower(Str::ascii((string) $nombre)));
            $campos[$clave] = $valor;
        }

        return $campos;
    }

    private function validarEncabezados(array $campos): void
    {
        $obligatorios = [
            'codigo' => 'CODIGO',
            'descripcion' => 'DESCRIPCION',
            'costo' => 'COSTO',
            'precvent' => 'PREC. VENT.',
            'fechaenvio' => 'FECHA ENVIO',
        ];
        if (array_key_exists('precioventa', $campos)) {
            unset($obligatorios['precvent']);
        }

        $faltantes = array_diff_key($obligatorios, $campos);
        if ($faltantes) {
            $mensaje = count($faltantes) === 1 ? 'Falta el encabezado ' : 'Faltan los encabezados ';
            throw ValidationException::withMessages([
                'archivo' => $mensaje . implode(', ', $faltantes) . ' en la primera fila de la primera hoja.',
            ]);
        }
    }
}
