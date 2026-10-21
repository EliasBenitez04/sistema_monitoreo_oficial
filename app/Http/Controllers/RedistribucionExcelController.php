<?php

namespace App\Http\Controllers;

use App\Exports\RedistribucionExcelPropuestaExport;
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
        $this->middleware('permission:redistribucionsugerencia index')->only(['index', 'consultar', 'exportar']);
    }

    public function index()
    {
        if (!Schema::hasTable('stock_sucursales')) {
            return view('redistribucion_excel.index', [
                'sucursales' => collect(),
                'errorTabla' => 'No existe la tabla stock_sucursales en esta base de datos.',
            ]);
        }

        return view('redistribucion_excel.index', ['sucursales' => $this->sucursales()]);
    }

    public function consultar(Request $request)
    {
        $datos = $request->validate([
            'origenes' => 'nullable|array',
            'origenes.*' => 'required|string|max:100|distinct',
            'destinos' => 'required|array|min:1',
            'destinos.*' => 'required|string|max:100|distinct',
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        if (!Schema::hasTable('stock_sucursales')) {
            throw ValidationException::withMessages(['archivo' => 'La tabla stock_sucursales no existe.']);
        }

        $sucursales = $this->sucursales();
        $validas = $sucursales->all();
        $destinos = array_values($datos['destinos']);
        $origenesSolicitados = array_values($datos['origenes'] ?? []);
        $automatica = count($origenesSolicitados) === 0;
        $origenes = $automatica ? array_values(array_diff($validas, $destinos)) : $origenesSolicitados;

        if (array_diff(array_merge($origenes, $destinos), $validas)) {
            throw ValidationException::withMessages(['destinos' => 'Seleccione solamente sucursales que figuren en stock_sucursales.']);
        }
        if (array_intersect($origenes, $destinos)) {
            throw ValidationException::withMessages(['origenes' => 'Una sucursal no puede ser origen y destino en esta misma propuesta.']);
        }
        if (!$origenes) {
            throw ValidationException::withMessages(['destinos' => 'No quedan sucursales de origen distintas de los destinos.']);
        }

        $importador = new CodigosRedistribucionExcelImport();
        Excel::import($importador, $request->file('archivo'));
        if (!$importador->tieneColumnaCodigo) {
            throw ValidationException::withMessages(['archivo' => 'El archivo debe tener una columna CODIGO en la primera fila.']);
        }

        $bases = array_keys($importador->codigos);
        if (!$bases) {
            throw ValidationException::withMessages(['archivo' => 'No se encontraron codigos base validos en la columna CODIGO.']);
        }

        $ordenadas = $bases;
        usort($ordenadas, function ($a, $b) { return strlen($a) <=> strlen($b); });
        foreach ($ordenadas as $i => $corta) {
            for ($j = $i + 1; $j < count($ordenadas); $j++) {
                if (strpos($ordenadas[$j], $corta) === 0) {
                    throw ValidationException::withMessages([
                        'archivo' => 'Codigos base solapados (' . $corta . ' y ' . $ordenadas[$j] . '). Corrija el Excel para no duplicar stock.',
                    ]);
                }
            }
        }

        $resultado = [];
        foreach ($bases as $base) {
            $resultado[$base] = ['codigo' => $base, 'variantes' => [], 'movimientos' => [], 'total' => 0];
        }

        foreach (array_chunk($bases, 25) as $bloque) {
            $filas = DB::table('stock_sucursales')
                ->select('codigo', 'sucursal', 'descripcion', 'cantidad')
                ->where(function ($query) use ($bloque) {
                    foreach ($bloque as $base) {
                        $query->orWhereRaw('codigo ILIKE ?', [$base . '%']);
                    }
                })
                ->orderBy('codigo')->get();

            foreach ($filas as $stock) {
                $codigoCompleto = strtoupper(trim((string) $stock->codigo));
                foreach ($bloque as $base) {
                    if (strlen($codigoCompleto) <= strlen($base) || strpos($codigoCompleto, $base) !== 0) continue;
                    if (!isset($resultado[$base]['variantes'][$codigoCompleto])) {
                        $resultado[$base]['variantes'][$codigoCompleto] = [
                            'codigo' => $codigoCompleto,
                            'talle' => substr($codigoCompleto, strlen($base)),
                            'descripcion' => trim((string) $stock->descripcion),
                            'stock' => [], 'total_origen' => 0, 'total_destino' => 0,
                        ];
                    }
                    $variante =& $resultado[$base]['variantes'][$codigoCompleto];
                    if (!$variante['descripcion'] && $stock->descripcion) $variante['descripcion'] = trim((string) $stock->descripcion);
                    $variante['stock'][(string) $stock->sucursal] = max(0, (int) floor((float) $stock->cantidad));
                    unset($variante);
                    break;
                }
            }
        }

        $resumen = [
            'bases' => count($bases), 'variantes' => 0, 'propuesto' => 0,
            'origenes' => count($origenes), 'destinos' => count($destinos),
            'sin_coincidencias' => [], 'sin_traslado' => [],
            'por_destino' => array_fill_keys($destinos, 0),
        ];
        $movimientos = [];

        foreach ($resultado as $base => &$grupo) {
            ksort($grupo['variantes'], SORT_NATURAL);
            if (!$grupo['variantes']) {
                $resumen['sin_coincidencias'][] = $base;
                continue;
            }
            foreach ($grupo['variantes'] as &$variante) {
                $resumen['variantes']++;
                foreach ($origenes as $origen) $variante['total_origen'] += (int) ($variante['stock'][$origen] ?? 0);
                foreach ($destinos as $destino) $variante['total_destino'] += (int) ($variante['stock'][$destino] ?? 0);

                $propuestas = $this->repartirTalle($variante, $base, $origenes, $destinos);
                foreach ($propuestas as $movimiento) {
                    $grupo['movimientos'][] = $movimiento;
                    $movimientos[] = $movimiento;
                    $grupo['total'] += $movimiento['cantidad'];
                    $resumen['propuesto'] += $movimiento['cantidad'];
                    $resumen['por_destino'][$movimiento['destino']] += $movimiento['cantidad'];
                }
            }
            unset($variante);
            if ($grupo['total'] === 0) $resumen['sin_traslado'][] = $base;
        }
        unset($grupo);

        session(['redistribucion_excel_export' => $movimientos]);

        return view('redistribucion_excel.index', [
            'sucursales' => $sucursales,
            'origenesSeleccionados' => $origenesSolicitados,
            'destinosSeleccionados' => $destinos,
            'resultado' => $resultado,
            'movimientos' => $movimientos,
            'resumen' => $resumen,
            'invalidos' => $importador->invalidos,
            'automatica' => $automatica,
            'origenesUtilizados' => $origenes,
        ]);
    }

    /**
     * Regla de negocio:
     * - cada origen conserva exactamente como minimo 1 unidad por codigo/talle;
     * - todo el excedente se distribuye entre los destinos elegidos;
     * - SHOPP tiene prioridad para recibir los sobrantes del reparto;
     * - los demas destinos conservan el orden elegido por el usuario;
     * - primero aportan los origenes con mayor stock.
     *
     * Ejemplo: origen=7 y destinos SL, SHOPP, MARIANO => se conservan 1 y
     * se reparten 6: SHOPP 2, SL 2, MARIANO 2. Si fueran 8, SHOPP recibe
     * primero la unidad sobrante: SHOPP 3, SL 2, MARIANO 2.
     */
    private function repartirTalle(array $variante, string $base, array $origenes, array $destinos): array
    {
        $fuentes = [];
        foreach ($origenes as $origen) {
            $inicial = (int) ($variante['stock'][$origen] ?? 0);
            if ($inicial > 1) {
                $fuentes[$origen] = ['inicial' => $inicial, 'actual' => $inicial];
            }
        }
        if (!$fuentes || !$destinos) return [];

        uasort($fuentes, function ($a, $b) { return $b['inicial'] <=> $a['inicial']; });

        $receptores = [];
        foreach ($destinos as $posicion => $destino) {
            $receptores[$destino] = [
                'inicial' => (int) ($variante['stock'][$destino] ?? 0),
                'actual' => (int) ($variante['stock'][$destino] ?? 0),
                'orden' => $posicion,
                'prioridad' => stripos($destino, 'SHOPP') !== false ? 0 : 1,
            ];
        }

        $destinosOrdenados = array_keys($receptores);
        usort($destinosOrdenados, function ($a, $b) use ($receptores) {
            return ($receptores[$a]['prioridad'] <=> $receptores[$b]['prioridad'])
                ?: ($receptores[$a]['orden'] <=> $receptores[$b]['orden']);
        });

        $agregados = [];
        foreach (array_keys($fuentes) as $origen) {
            $disponible = $fuentes[$origen]['actual'] - 1;
            if ($disponible <= 0) continue;

            $cantidadDestinos = count($destinosOrdenados);
            $basePorDestino = intdiv($disponible, $cantidadDestinos);
            $sobrante = $disponible % $cantidadDestinos;

            foreach ($destinosOrdenados as $indice => $destino) {
                $cantidad = $basePorDestino + ($indice < $sobrante ? 1 : 0);
                if ($cantidad <= 0) continue;

                $fuentes[$origen]['actual'] -= $cantidad;
                $receptores[$destino]['actual'] += $cantidad;
                $clave = $origen . "\x1F" . $destino;
                $agregados[$clave] = [
                    'base' => $base,
                    'codigo' => $variante['codigo'],
                    'talle' => $variante['talle'],
                    'descripcion' => $variante['descripcion'],
                    'origen' => $origen,
                    'destino' => $destino,
                    'stock_origen' => $fuentes[$origen]['inicial'],
                    'stock_destino' => $receptores[$destino]['inicial'],
                    'reserva' => 1,
                    'cantidad' => $cantidad,
                    'origen_final' => 0,
                    'destino_final' => 0,
                ];
            }
        }

        foreach ($agregados as &$movimiento) {
            $movimiento['origen_final'] = $fuentes[$movimiento['origen']]['actual'];
            $movimiento['destino_final'] = $receptores[$movimiento['destino']]['actual'];
        }
        unset($movimiento);

        return array_values($agregados);
    }

    public function exportar()
    {
        $movimientos = session('redistribucion_excel_export', []);
        if (!$movimientos) {
            return redirect()->route('redistribucion-excel.index')
                ->with('error', 'Primero genere una propuesta de redistribucion para exportarla.');
        }

        return Excel::download(
            new RedistribucionExcelPropuestaExport($movimientos),
            'redistribucion_propuesta_' . date('Ymd_His') . '.xlsx'
        );
    }

    private function sucursales()
    {
        return DB::table('stock_sucursales')
            ->whereNotNull('sucursal')->where('sucursal', '<>', '')
            ->distinct()->orderBy('sucursal')->pluck('sucursal');
    }
}
