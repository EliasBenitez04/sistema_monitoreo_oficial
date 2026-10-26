<?php

namespace App\Imports;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class AccesoriosCompraImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public int $filas = 0;
    public int $articulosNuevos = 0;
    public int $comprasNuevas = 0;
    public int $comprasActualizadas = 0;
    public int $sinCambios = 0;

    public function collection(Collection $rows)
    {
        $preparadas = [];
        $errores = [];
        $vistos = [];

        foreach ($rows as $indice => $row) {
            $campos = [];
            foreach ($row->toArray() as $nombre => $valor) {
                $clave = preg_replace('/[^a-z0-9]/', '', strtolower(Str::ascii((string) $nombre)));
                $campos[$clave] = $valor;
            }

            if (count(array_filter($campos, function ($valor) {
                return $valor !== null && trim((string) $valor) !== '';
            })) === 0) {
                continue;
            }

            $this->filas++;
            $numeroFila = $indice + 2;
            $codigo = strtoupper(trim((string) ($campos['codigo'] ?? '')));
            $descripcion = trim((string) ($campos['descripcion'] ?? ''));
            $linea = trim((string) ($campos['linea'] ?? ''));
            $fco = trim((string) ($campos['fconro'] ?? ''));
            $fecha = self::fecha($campos['fechaenvio'] ?? null);
            $cantidad = self::importe($campos['cantcompra'] ?? null);
            $costo = self::importe($campos['costo'] ?? null);
            $precio = self::importe($campos['precvent'] ?? ($campos['precioventa'] ?? null));

            if ($codigo === '' || strlen($codigo) > 50 || $descripcion === ''
                || mb_strlen($descripcion) > 200 || mb_strlen($linea) > 100
                || $fco === '' || strlen($fco) > 50 || !$fecha
                || $cantidad === null || $cantidad <= 0 || $cantidad != floor($cantidad)
                || $cantidad > 2147483647 || $costo === null || $costo < 0
                || $precio === null || $precio < 0) {
                $errores[] = 'Fila ' . $numeroFila . ': revise código, descripción, costo, precio, FCO, fecha y cantidad.';
                continue;
            }

            $registro = [
                'codigo' => $codigo,
                'descripcion' => $descripcion,
                'linea' => $linea !== '' ? $linea : null,
                'fco_nro' => $fco,
                'fecha_envio' => $fecha,
                'cantidad_compra' => (int) $cantidad,
                'costo_unitario' => round($costo, 2),
                'precio_venta' => round($precio, 2),
            ];

            $claveLote = implode('|', [$codigo, $fco, $fecha]);
            if (isset($vistos[$claveLote])) {
                if ($vistos[$claveLote] !== $registro) {
                    $errores[] = 'Fila ' . $numeroFila . ': el código, FCO y fecha ya están repetidos con valores distintos.';
                } else {
                    $this->sinCambios++;
                }
                continue;
            }
            $vistos[$claveLote] = $registro;
            $preparadas[] = $registro;
        }

        if ($errores) {
            throw ValidationException::withMessages([
                'archivo' => implode(' ', array_slice($errores, 0, 15)),
            ]);
        }
        if (!$preparadas) {
            throw ValidationException::withMessages([
                'archivo' => 'Archivo vacío o con encabezados incorrectos: CODIGO, DESCRIPCION, COSTO, PREC. VENT., FCO NRO, FECHA ENVIO, LINEA, CANT. COMPRA.',
            ]);
        }

        DB::transaction(function () use ($preparadas) {
            foreach ($preparadas as $fila) {
                $ahora = now();
                $id = DB::table('accesorios_articulos')->where('codigo', $fila['codigo'])->value('id');
                if (!$id) {
                    $id = DB::table('accesorios_articulos')->insertGetId([
                        'codigo' => $fila['codigo'],
                        'descripcion' => $fila['descripcion'],
                        'linea' => $fila['linea'],
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ]);
                    $this->articulosNuevos++;
                } else {
                    DB::table('accesorios_articulos')->where('id', $id)->update([
                        'descripcion' => $fila['descripcion'],
                        'linea' => $fila['linea'],
                        'updated_at' => $ahora,
                    ]);
                }

                $identidad = [
                    'articulo_id' => $id,
                    'fco_nro' => $fila['fco_nro'],
                    'fecha_envio' => $fila['fecha_envio'],
                ];
                $anterior = DB::table('accesorios_compras')->where($identidad)->first();
                $valores = [
                    'cantidad_compra' => $fila['cantidad_compra'],
                    'costo_unitario' => $fila['costo_unitario'],
                    'precio_venta' => $fila['precio_venta'],
                ];
                if (!$anterior) {
                    DB::table('accesorios_compras')->insert(array_merge(
                        $identidad, $valores, ['created_at' => $ahora, 'updated_at' => $ahora]
                    ));
                    $this->comprasNuevas++;
                } elseif ((int) $anterior->cantidad_compra !== $valores['cantidad_compra']
                    || round((float) $anterior->costo_unitario, 2) !== $valores['costo_unitario']
                    || round((float) $anterior->precio_venta, 2) !== $valores['precio_venta']) {
                    DB::table('accesorios_compras')->where('id', $anterior->id)
                        ->update(array_merge($valores, ['updated_at' => $ahora]));
                    $this->comprasActualizadas++;
                } else {
                    $this->sinCambios++;
                }
            }
        });
    }

    // Paraguay: 9.881 => 9881; 9.881,50 => 9881.50.
    public static function importe($valor): ?float
    {
        if ($valor === null || trim((string) $valor) === '') {
            return null;
        }
        $texto = preg_replace('/[^0-9,.\-]/u', '', (string) $valor);
        if (!$texto || !preg_match('/^-?\d[\d.,]*$/', $texto)) {
            return null;
        }
        if (strpos($texto, ',') !== false) {
            $texto = str_replace(',', '.', str_replace('.', '', $texto));
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $texto)) {
            $texto = str_replace('.', '', $texto);
        }
        return is_numeric($texto) ? (float) $texto : null;
    }

    public static function fecha($valor): ?string
    {
        if ($valor instanceof DateTimeInterface) {
            return $valor->format('Y-m-d');
        }
        if (is_numeric($valor) && (float) $valor > 1000) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $valor)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $texto = trim((string) $valor);
        foreach (['!d/m/Y' => 'd/m/Y', '!d-m-Y' => 'd-m-Y', '!Y-m-d' => 'Y-m-d'] as $entrada => $salida) {
            $fecha = DateTimeImmutable::createFromFormat($entrada, $texto);
            if ($fecha && $fecha->format($salida) === $texto) {
                return $fecha->format('Y-m-d');
            }
        }
        return null;
    }
}
