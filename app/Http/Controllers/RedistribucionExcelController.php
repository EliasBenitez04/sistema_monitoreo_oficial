<?php

namespace App\Http\Controllers;

use App\Imports\CodigosRedistribucionExcelImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class RedistribucionExcelController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:redistribucionsugerencia index')->only(['index', 'consultar']);
    }

    /**
     * Modulo independiente: no usa stock_ventas_sucursales,
     * sugerencias, lotes ni procesos de redistribucion existentes.
     */
    public function index()
    {
        if (!Schema::hasTable('stock_sucursales')) {
            return view('redistribucion_excel.index', [
                'sucursales' => collect(),
                'errorTabla' => 'No existe la tabla stock_sucursales en esta base de datos.',
            ]);
        }

        return view('redistribucion_excel.index', [
            'sucursales' => $this->sucursales(),
        ]);
    }

    public function consultar(Request $request)
    {
        $data = $request->validate([
            'origenes' => 'required|array|min:1',
            'origenes.*' => 'required|string|max:100|distinct',
            'destinos' => 'required|array|min:1',
            'destinos.*' => 'required|string|max:100|distinct',
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        if (!Schema::hasTable('stock_sucursales')) {
            throw ValidationException::withMessages([
                'archivo' => 'La tabla stock_sucursales no existe.',
            ]);
        }

        $sucursales = $this->sucursales();
        $validas = $sucursales->all();
        $origenes = array_values($data['origenes']);
        $destinos = array_values($data['destinos']);

        if (array_diff(array_merge($origenes, $destinos), $validas)) {
            throw ValidationException::withMessages([
                'origenes' => 'Hay sucursales no disponibles en stock_sucursales. Actualice la pagina.',
            ]);
        }

        if (array_intersect($origenes, $destinos)) {
            throw ValidationException::withMessages([
                'destinos' => 'Una sucursal no puede ser origen y destino en la misma consulta.',
            ]);
        }

        $importador = new CodigosRedistribucionExcelImport();
        Excel::import($importador, $request->file('archivo'));

        if (!$importador->tieneColumnaCodigo) {
            throw ValidationException::withMessages([
                'archivo' => 'El Excel debe tener en la primera fila una columna llamada CODIGO.',
            ]);
        }

        $bases = array_keys($importador->codigos);
        if (!$bases) {
            throw ValidationException::withMessages([
                'archivo' => 'No se encontraron codigos base validos en la columna CODIGO.',
            ]);
        }

        // Evita asignar una misma variante a dos bases solapadas.
        $ordenadas = $bases;
        usort($ordenadas, function ($a, $b) {
            return strlen($a) <=> strlen($b);
        });
        foreach ($ordenadas as $i => $corta) {
            for ($j = $i + 1; $j < count($ordenadas); $j++) {
                if (strpos($ordenadas[$j], $corta) === 0) {
                    throw ValidationException::withMessages([
                        'archivo' => 'El Excel contiene codigos base superpuestos: ' .
                            $corta . ' y ' . $ordenadas[$j] . '. Corrija uno para evitar duplicar stock.',
                    ]);
                }
            }
        }

        $resultado = [];
        foreach ($bases as $base) {
            $resultado[$base] = [
                'codigo' => $base,
                'variantes' => [],
                'total_origen' => 0,
                'total_destino' => 0,
            ];
        }

        $seleccionadas = array_fill_keys(array_merge($origenes, $destinos), true);
        $origenSet = array_fill_keys($origenes, true);
        $destinoSet = array_fill_keys($destinos, true);

        // Los talles se descubren en TODAS las sucursales, incluso cuando las
        // seleccionadas no tienen existencias de uno de esos talles.
        foreach (array_chunk($bases, 30) as $bloque) {
            $stocks = DB::table('stock_sucursales')
                ->select('codigo', 'sucursal', 'descripcion', 'cantidad')
                ->where(function ($consulta) use ($bloque) {
                    foreach ($bloque as $base) {
                        // Bases validadas como alfanumericas: sin comodines SQL.
                        $consulta->orWhereRaw('codigo ILIKE ?', [$base . '%']);
                    }
                })
                ->orderBy('codigo')
                ->get();

            foreach ($stocks as $stock) {
                $completo = strtoupper(trim((string) $stock->codigo));
                $sucursal = (string) $stock->sucursal;

                foreach ($bloque as $base) {
                    if (strlen($completo) <= strlen($base) || strpos($completo, $base) !== 0) {
                        continue;
                    }

                    if (!isset($resultado[$base]['variantes'][$completo])) {
                        $resultado[$base]['variantes'][$completo] = [
                            'codigo' => $completo,
                            'talle' => substr($completo, strlen($base)),
                            'descripcion' => trim((string) $stock->descripcion),
                            'stock' => [],
                            'total_origen' => 0,
                            'total_destino' => 0,
                        ];
                    }

                    if ($resultado[$base]['variantes'][$completo]['descripcion'] === '' && $stock->descripcion) {
                        $resultado[$base]['variantes'][$completo]['descripcion'] = trim((string) $stock->descripcion);
                    }

                    // Un registro unico por (sucursal, codigo) segun la BD.
                    if (isset($seleccionadas[$sucursal])) {
                        $resultado[$base]['variantes'][$completo]['stock'][$sucursal] = (float) $stock->cantidad;
                    }
                    break;
                }
            }
        }

        $resumen = [
            'bases' => count($bases),
            'variantes' => 0,
            'total_origen' => 0,
            'total_destino' => 0,
            'sin_coincidencias' => [],
        ];

        foreach ($resultado as $base => &$grupo) {
            ksort($grupo['variantes'], SORT_NATURAL);
            if (!$grupo['variantes']) {
                $resumen['sin_coincidencias'][] = $base;
                continue;
            }

            foreach ($grupo['variantes'] as &$variante) {
                foreach ($variante['stock'] as $sucursal => $cantidad) {
                    // La disponibilidad nunca puede ser negativa.
                    $disponible = max(0, $cantidad);
                    if (isset($origenSet[$sucursal])) {
                        $variante['total_origen'] += $disponible;
                    }
                    if (isset($destinoSet[$sucursal])) {
                        $variante['total_destino'] += $disponible;
                    }
                }
                $grupo['total_origen'] += $variante['total_origen'];
                $grupo['total_destino'] += $variante['total_destino'];
                $resumen['variantes']++;
            }
            unset($variante);

            $resumen['total_origen'] += $grupo['total_origen'];
            $resumen['total_destino'] += $grupo['total_destino'];
        }
        unset($grupo);

        return view('redistribucion_excel.index', [
            'sucursales' => $sucursales,
            'origenes' => $origenes,
            'destinos' => $destinos,
            'resultado' => $resultado,
            'resumen' => $resumen,
            'invalidos' => $importador->invalidos,
        ]);
    }

    private function sucursales()
    {
        return DB::table('stock_sucursales')
            ->whereNotNull('sucursal')
            ->where('sucursal', '<>', '')
            ->distinct()
            ->orderBy('sucursal')
            ->pluck('sucursal');
    }
}
