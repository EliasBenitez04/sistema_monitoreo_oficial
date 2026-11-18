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
        $registros = [];
        $errores = [];
        $ahora = now();

        foreach ($rows as $indice => $row) {
            if ($row->every(fn ($valor) => trim((string) $valor) === '')) {
                continue;
            }

            $campos = [];
            foreach ($row as $nombre => $valor) {
                $clave = preg_replace('/[^a-z0-9]/', '', strtolower(Str::ascii((string) $nombre)));
                $campos[$clave] = $valor;
            }

            $numeroFila = $indice + 2;
            $codigo = strtoupper(ltrim(trim((string) ($campos['codigo'] ?? '')), "'’‘`"));
            $descripcion = trim((string) ($campos['descripcion'] ?? ''));
            $linea = trim((string) ($campos['linea'] ?? ''));
            $costo = AccesoriosCompraImport::importe($campos['costo'] ?? null);
            $precio = AccesoriosCompraImport::importe($campos['precvent'] ?? $campos['precioventa'] ?? null);
            $fecha = AccesoriosCompraImport::fecha($campos['fechaenvio'] ?? null);

            if ($codigo === '' || mb_strlen($codigo) > 50 || $descripcion === ''
                || mb_strlen($descripcion) > 200 || mb_strlen($linea) > 100
                || $costo === null || $costo < 0 || round($costo, 2) >= 1e13
                || $precio === null || $precio < 0 || round($precio, 2) >= 1e13
                || $fecha === null || strlen($fecha) !== 10 || substr($fecha, 0, 4) === '0000') {
                $errores[] = "Fila {$numeroFila}. Revisá código, descripción, costo, precio, fecha de envío y línea.";
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
}
