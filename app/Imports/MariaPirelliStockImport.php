<?php

namespace App\Imports;

use App\Services\AccesoriosStockService;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MariaPirelliStockImport
{
    private const ENCABEZADOS = [
        'sucursal' => 'SUCURSAL',
        'codigo' => 'CODIGO',
        'stock_actual' => 'STOCK ACTUAL',
        'costo_unitario' => 'COSTO UNITARIO',
        'pvta_uni' => 'PVTA UNI',
    ];

    public function importar(UploadedFile $archivo, ?string $fechaIndicada): array
    {
        $catalogo = DB::table('maria_pirelli_articulos')->pluck('id', 'codigo');
        if ($catalogo->isEmpty()) {
            throw ValidationException::withMessages(['archivo' => 'Importá primero el catálogo de María Pirelli.']);
        }

        $ruta = $archivo->getRealPath();
        try {
            $lector = IOFactory::createReaderForFile($ruta, [IOFactory::READER_XLSX]);
            $hojas = $lector->listWorksheetNames($ruta);
            if (!$hojas) {
                throw new ReaderException('El archivo no tiene hojas.');
            }
            $lector->setLoadSheetsOnly($hojas[0]);
            $libro = $lector->setReadDataOnly(true)->load($ruta);
        } catch (ReaderException $e) {
            throw ValidationException::withMessages(['archivo' => 'Subí un archivo de Excel (.xlsx) válido.']);
        }

        try {
            [$filas, $fechas, $omitidas] = $this->leer($libro->getSheet(0), $catalogo->all());
        } finally {
            $libro->disconnectWorksheets();
        }

        $fecha = $this->fechaCorte($fechas, $fechaIndicada, $archivo->getClientOriginalName());

        DB::transaction(function () use ($fecha, $filas) {
            $ahora = now();
            // El upsert mantiene bloqueado el corte durante el reemplazo, incluso en cargas simultáneas.
            DB::table('maria_pirelli_stock_cortes')->upsert([
                ['fecha_corte' => $fecha, 'created_at' => $ahora, 'updated_at' => $ahora],
            ], ['fecha_corte'], ['updated_at']);
            $corte = DB::table('maria_pirelli_stock_cortes')->where('fecha_corte', $fecha)->value('id');
            DB::table('maria_pirelli_stock')->where('corte_id', $corte)->delete();
            foreach (array_chunk($filas, 200) as $lote) {
                DB::table('maria_pirelli_stock')->insert(array_map(fn ($fila) => $fila + ['corte_id' => $corte], $lote));
            }
        });

        return ['fecha' => $fecha, 'filas' => count($filas), 'omitidas' => $omitidas];
    }

    private function leer(Worksheet $hoja, array $catalogo): array
    {
        $columnas = $this->columnas($hoja);
        $sucursales = new AccesoriosStockService();
        $nombres = $filas = $fechas = [];
        $omitidas = 0;
        $ultimaFila = $hoja->getHighestDataRow();

        for ($fila = 2; $fila <= $ultimaFila; $fila++) {
            $codigo = strtoupper(ltrim(trim((string) $this->valor($hoja, $columnas['codigo'], $fila)), "'’‘`"));
            $sucursal = trim((string) $this->valor($hoja, $columnas['sucursal'], $fila));
            if ($codigo === '' && $sucursal === '') {
                continue;
            }
            if ($codigo === '') {
                throw ValidationException::withMessages(['archivo' => "Fila {$fila}. Falta el CÓDIGO del artículo."]);
            }
            if (!isset($catalogo[$codigo])) {
                $omitidas++;
                continue;
            }

            $normalizada = AccesoriosStockService::normalizar($sucursal);
            if ($normalizada === '' || mb_strlen($sucursal) > 120 || strlen($normalizada) > 120) {
                throw ValidationException::withMessages(['archivo' => "Fila {$fila}. Revisá la SUCURSAL."]);
            }
            $clave = $sucursales->identificar(null, $sucursal) ?? 'T:' . $normalizada;
            $nombres[$clave] ??= $sucursales->nombre($clave, $sucursal);
            $articulo = $catalogo[$codigo];
            $id = $clave . ':' . $articulo;
            if (isset($filas[$id])) {
                throw ValidationException::withMessages([
                    'archivo' => "Fila {$fila}. El código {$codigo} aparece más de una vez en {$nombres[$clave]}.",
                ]);
            }

            $cantidad = $this->numero($hoja, $columnas['stock_actual'], $fila);
            if (floor($cantidad) !== $cantidad || $cantidad < -2147483648 || $cantidad > 2147483647) {
                throw ValidationException::withMessages(['archivo' => "Fila {$fila}. STOCK ACTUAL debe ser una cantidad entera."]);
            }
            $costo = $this->numero($hoja, $columnas['costo_unitario'], $fila);
            $precio = $this->numero($hoja, $columnas['pvta_uni'], $fila);
            if ($costo < 0 || $precio < 0 || round($costo, 2) >= 1e13 || round($precio, 2) >= 1e13) {
                throw ValidationException::withMessages(['archivo' => "Fila {$fila}. Revisá COSTO UNITARIO y PVTA UNI."]);
            }

            $filas[$id] = [
                'articulo_id' => $articulo,
                'sucursal_clave' => $clave,
                'sucursal' => $nombres[$clave],
                'cantidad' => (int) $cantidad,
                'costo_unitario' => round($costo, 2),
                'precio_venta' => round($precio, 2),
            ];
            if (isset($columnas['fecha_stock'])) {
                $valor = $this->valor($hoja, $columnas['fecha_stock'], $fila);
                $texto = preg_replace('/^STOCK\s+/iu', '', trim((string) $valor));
                if ($texto !== '') {
                    $fechas[$texto] ??= $fila;
                }
            }
        }

        if (!$filas) {
            throw ValidationException::withMessages(['archivo' => 'El archivo no contiene códigos del catálogo de María Pirelli.']);
        }

        return [array_values($filas), $fechas, $omitidas];
    }

    private function columnas(Worksheet $hoja): array
    {
        $columnas = [];
        foreach ($hoja->rangeToArray('A1:' . $hoja->getHighestDataColumn(1) . '1', null, false, false)[0] as $indice => $valor) {
            $clave = Str::slug(trim((string) $valor), '_');
            if (isset(self::ENCABEZADOS[$clave]) || $clave === 'fecha_stock') {
                $columnas[$clave] = $indice + 1;
            }
        }
        $faltantes = array_diff_key(self::ENCABEZADOS, $columnas);
        if ($faltantes) {
            throw ValidationException::withMessages([
                'archivo' => 'Faltan encabezados en la primera fila: ' . implode(', ', $faltantes) . '.',
            ]);
        }

        return $columnas;
    }

    private function valor(Worksheet $hoja, int $columna, int $fila)
    {
        $celda = $hoja->getCell([$columna, $fila]);
        // Las fórmulas de tabla se leen con el resultado que guardó Excel.
        return $celda->getDataType() === DataType::TYPE_FORMULA
            ? $celda->getOldCalculatedValue()
            : $celda->getValue();
    }

    private function numero(Worksheet $hoja, int $columna, int $fila): float
    {
        $valor = $this->valor($hoja, $columna, $fila);
        $numero = null;
        if (is_int($valor) || is_float($valor)) {
            $numero = (float) $valor;
        } elseif (is_string($valor) && preg_match('/^(?:Gs\.?\s*)?([+-]?)(\d+(?:[,.]\d+)?|\d{1,3}(?:\.\d{3})+(?:,\d+)?)$/iu', trim($valor), $partes)) {
            $numero = AccesoriosCompraImport::importe($partes[2]);
            if ($numero !== null && $partes[1] === '-') {
                $numero = -$numero;
            }
        }
        if ($numero === null || !is_finite($numero)) {
            $encabezado = $this->valor($hoja, $columna, 1);
            throw ValidationException::withMessages([
                'archivo' => "Fila {$fila}. Revisá {$encabezado} y guardá el Excel con los cálculos actualizados.",
            ]);
        }

        return $numero;
    }

    private function fechaCorte(array $fechas, ?string $indicada, string $nombre): string
    {
        $fecha = $indicada;
        $dias = [];
        foreach ($fechas as $texto => $fila) {
            if (preg_match('/^(\d{1,2})\/(\d{1,2})$/', (string) $texto, $partes)) {
                $dias[sprintf('%02d/%02d', $partes[1], $partes[2])] = $fila;
                continue;
            }
            $completa = AccesoriosCompraImport::fecha($texto);
            $fecha ??= $completa;
            if (!$completa || $fecha !== $completa) {
                throw ValidationException::withMessages(['archivo' => "Fila {$fila}. Revisá la FECHA STOCK del corte."]);
            }
        }

        if ($fecha === null && preg_match('/(?<!\d)(\d{8})(?!\d)/', $nombre, $partes)) {
            $delNombre = DateTimeImmutable::createFromFormat('!dmY', $partes[1]);
            if ($delNombre && $delNombre->format('dmY') === $partes[1]) {
                $fecha = $delNombre->format('Y-m-d');
            }
        }
        if ($fecha === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || str_starts_with($fecha, '0000')) {
            throw ValidationException::withMessages(['fecha_corte' => 'Indicá la fecha completa del stock.']);
        }
        foreach ($dias as $dia => $fila) {
            if ($dia !== substr($fecha, 8, 2) . '/' . substr($fecha, 5, 2)) {
                throw ValidationException::withMessages(['archivo' => "Fila {$fila}. La FECHA STOCK difiere de la fecha de corte."]);
            }
        }

        return $fecha;
    }
}
