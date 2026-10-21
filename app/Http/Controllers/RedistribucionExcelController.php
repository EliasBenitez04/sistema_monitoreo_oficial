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
     * Nuevo modulo separado de RedistribucionSugerida.
     * No modifica lotes existentes ni la tabla stock_sucursales.
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
        $datos = $request->validate([
            'origenes' => 'nullable|array',
            'origenes.*' => 'required|string|max:100|distinct',
            'destinos' => 'required|array|min:1',
            'destinos.*' => 'required|string|max:100|distinct',
            'reserva_minima' => 'required|integer|min:1|max:1000',
            'reserva_porcentaje' => 'required|integer|min:0|max:90',
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        if (!Schema::hasTable('stock_sucursales')) {
            throw ValidationException::withMessages([
                'archivo' => 'La tabla stock_sucursales no existe.',
            ]);
        }

        $sucursales = $this->sucursales();
        $validas = $sucursales->all();
        $destinos = array_values($datos['destinos']);
        $origenesSolicitados = array_values($datos['origenes'] ?? []);
        $automatica = count($origenesSolicitados) === 0;
        $origenes = $automatica
            ? array_values(array_diff($validas, $destinos))
            : $origenesSolicitados;

        if (array_diff(array_merge($origenes, $destinos), $validas)) {
            throw ValidationException::withMessages([
                'destinos' => 'Seleccione solamente sucursales que figuren en stock_sucursales.',
            ]);
        }

        if (array_intersect($origenes, $destinos)) {
            throw ValidationException::withMessages([
                'origenes' => 'Una sucursal no puede ser origen y destino en esta misma propuesta.',
            ]);
        }

        if (!$origenes) {
            throw ValidationException::withMessages([
                'destinos' => 'No quedan sucursales de origen distintas de los destinos.',
            ]);
        }

        $importador = new CodigosRedistribucionExcelImport();
        Excel::import($importador, $request->file('archivo'));

        if (!$importador->tieneColumnaCodigo) {
            throw ValidationException::withMessages([
                'archivo' => 'El archivo debe tener una columna CODIGO en la primera fila.',
            ]);
        }

        $bases = array_keys($importador->codigos);
        if (!$bases) {
            throw ValidationException::withMessages([
                'archivo' => 'No se encontraron codigos base validos en la columna CODIGO.',
            ]);
        }

        // Prevenir doble conteo cuando un codigo base es prefijo de otro.
        $ordenadas = $bases;
        usort($ordenadas, function ($a, $b) { return strlen($a) <=> strlen($b); });
        foreach ($ordenadas as $i => $corta) {
            for ($j = $i + 1; $j < count($ordenadas); $j++) {
                if (strpos($ordenadas[$j], $corta) === 0) {
                    throw ValidationException::withMessages([
                        'archivo' => 'Codigos base solapados (' . $corta . ' y ' .
                            $ordenadas[$j] . '). Corrija el Excel para no duplicar stock.',
                    ]);
                }
            }
        }

        $resultado = [];
        foreach ($bases as $base) {
            $resultado[$base] = [
                'codigo' => $base,
                'variantes' => [],
                'movimientos' => [],
                'total' => 0,
            ];
        }

        // Se leen todas las sucursales para descubrir TODOS los talles, aunque
        // los destinos elegidos aun no tengan el codigo cargado.
        foreach (array_chunk($bases, 25) as $bloque) {
            $filas = DB::table('stock_sucursales')
                ->select('codigo', 'sucursal', 'descripcion', 'cantidad')
                ->where(function ($query) use ($bloque) {
                    foreach ($bloque as $base) {
                        $query->orWhereRaw('codigo ILIKE ?', [$base . '%']);
                    }
                })
                ->orderBy('codigo')
                ->get();

            foreach ($filas as $stock) {
                $codigoCompleto = strtoupper(trim((string) $stock->codigo));
                foreach ($bloque as $base) {
                    if (strlen($codigoCompleto) <= strlen($base) ||
                        strpos($codigoCompleto, $base) !== 0) {
                        continue;
                    }

                    if (!isset($resultado[$base]['variantes'][$codigoCompleto])) {
                        $resultado[$base]['variantes'][$codigoCompleto] = [
                            'codigo' => $codigoCompleto,
                            'talle' => substr($codigoCompleto, strlen($base)),
                            'descripcion' => trim((string) $stock->descripcion),
                            'stock' => [],
                            'total_origen' => 0,
                            'total_destino' => 0,
                        ];
                    }

                    $variante =& $resultado[$base]['variantes'][$codigoCompleto];
                    if (!$variante['descripcion'] && $stock->descripcion) {
                        $variante['descripcion'] = trim((string) $stock->descripcion);
                    }
                    $variante['stock'][(string) $stock->sucursal] = max(
                        0, (int) floor((float) $stock->cantidad)
                    );
                    unset($variante);
                    break;
                }
            }
        }

        $resumen = [
            'bases' => count($bases),
            'variantes' => 0,
            'propuesto' => 0,
            'origenes' => count($origenes),
            'destinos' => count($destinos),
            'sin_coincidencias' => [],
            'sin_traslado' => [],
            'por_destino' => array_fill_keys($destinos, 0),
        ];
        $movimientos = [];
        $reservaMinima = (int) $datos['reserva_minima'];
        $reservaPorcentaje = (int) $datos['reserva_porcentaje'];

        foreach ($resultado as $base => &$grupo) {
            ksort($grupo['variantes'], SORT_NATURAL);

            if (!$grupo['variantes']) {
                $resumen['sin_coincidencias'][] = $base;
                continue;
            }

            foreach ($grupo['variantes'] as &$variante) {
                $resumen['variantes']++;
                foreach ($origenes as $origen) {
                    $variante['total_origen'] += (int) ($variante['stock'][$origen] ?? 0);
                }
                foreach ($destinos as $destino) {
                    $variante['total_destino'] += (int) ($variante['stock'][$destino] ?? 0);
                }

                $propuestas = $this->equilibrarTalle(
                    $variante, $base, $origenes, $destinos,
                    $reservaMinima, $reservaPorcentaje
                );

                foreach ($propuestas as $movimiento) {
                    $grupo['movimientos'][] = $movimiento;
                    $movimientos[] = $movimiento;
                    $grupo['total'] += $movimiento['cantidad'];
                    $resumen['propuesto'] += $movimiento['cantidad'];
                    $resumen['por_destino'][$movimiento['destino']] += $movimiento['cantidad'];
                }
            }
            unset($variante);

            if ($grupo['total'] === 0) {
                $resumen['sin_traslado'][] = $base;
            }
        }
        unset($grupo);

        return view('redistribucion_excel.index', [
            'sucursales' => $sucursales,
            'origenesSeleccionados' => $origenesSolicitados,
            'destinosSeleccionados' => $destinos,
            'reservaMinima' => $reservaMinima,
            'reservaPorcentaje' => $reservaPorcentaje,
            'resultado' => $resultado,
            'movimientos' => $movimientos,
            'resumen' => $resumen,
            'invalidos' => $importador->invalidos,
            'automatica' => $automatica,
            'origenesUtilizados' => $origenes,
        ]);
    }

    /**
     * El origen de mayor stock aporta primero; el destino de menor stock
     * recibe primero. Nunca se deja un origen por debajo de su reserva.
     * Tampoco se envia cuando el origen solo tiene una unidad mas que
     * el destino: la transferencia ya no mejoraria el equilibrio.
     */
    private function equilibrarTalle(
        array $variante,
        string $base,
        array $origenes,
        array $destinos,
        int $reservaMinima,
        int $reservaPorcentaje
    ): array {
        $fuentes = [];
        foreach ($origenes as $origen) {
            $inicial = (int) ($variante['stock'][$origen] ?? 0);
            if ($inicial <= 0) {
                continue;
            }
            $fuentes[$origen] = [
                'inicial' => $inicial,
                'actual' => $inicial,
                'reserva' => min($inicial, max(
                    $reservaMinima,
                    (int) ceil($inicial * $reservaPorcentaje / 100)
                )),
            ];
        }

        $receptores = [];
        foreach ($destinos as $destino) {
            $inicial = (int) ($variante['stock'][$destino] ?? 0);
            $receptores[$destino] = ['inicial' => $inicial, 'actual' => $inicial];
        }

        $agregados = [];
        $pasos = 0;
        while (++$pasos <= 100000) {
            $origenesOrdenados = array_keys(array_filter($fuentes, function ($fuente) {
                return $fuente['actual'] > $fuente['reserva'];
            }));
            if (!$origenesOrdenados || !$receptores) {
                break;
            }

            usort($origenesOrdenados, function ($a, $b) use (&$fuentes) {
                return ($fuentes[$b]['actual'] <=> $fuentes[$a]['actual']) ?: strcmp($a, $b);
            });
            $destinosOrdenados = array_keys($receptores);
            usort($destinosOrdenados, function ($a, $b) use (&$receptores) {
                return ($receptores[$a]['actual'] <=> $receptores[$b]['actual']) ?: strcmp($a, $b);
            });

            $origen = $origenesOrdenados[0];
            $destino = $destinosOrdenados[0];
            $enOrigen = $fuentes[$origen]['actual'];
            $enDestino = $receptores[$destino]['actual'];
            if ($enOrigen <= $enDestino + 1) {
                break;
            }

            $transferibles = $enOrigen - $fuentes[$origen]['reserva'];
            $cantidad = min($transferibles, intdiv($enOrigen - $enDestino, 2));

            // Los saltos no deben superar el stock del siguiente destino:
            // si hay dos destinos con cero, se van atendiendo por igual.
            if (isset($destinosOrdenados[1])) {
                $proximoDestino = $receptores[$destinosOrdenados[1]]['actual'];
                $cantidad = min($cantidad, max(1, $proximoDestino - $enDestino));
            }

            // Desempatar tambien entre sucursales de origen con mucho stock.
            if (isset($origenesOrdenados[1])) {
                $proximoOrigen = $fuentes[$origenesOrdenados[1]]['actual'];
                $cantidad = min($cantidad, max(1, $enOrigen - $proximoOrigen));
            }

            if ($cantidad <= 0) {
                break;
            }

            $fuentes[$origen]['actual'] -= $cantidad;
            $receptores[$destino]['actual'] += $cantidad;
            $clave = $origen . "\x1F" . $destino;

            if (!isset($agregados[$clave])) {
                $agregados[$clave] = [
                    'base' => $base,
                    'codigo' => $variante['codigo'],
                    'talle' => $variante['talle'],
                    'descripcion' => $variante['descripcion'],
                    'origen' => $origen,
                    'destino' => $destino,
                    'stock_origen' => $fuentes[$origen]['inicial'],
                    'stock_destino' => $receptores[$destino]['inicial'],
                    'reserva' => $fuentes[$origen]['reserva'],
                    'cantidad' => 0,
                    'origen_final' => 0,
                    'destino_final' => 0,
                ];
            }

            $agregados[$clave]['cantidad'] += $cantidad;
        }

        foreach ($agregados as &$movimiento) {
            $movimiento['origen_final'] = $fuentes[$movimiento['origen']]['actual'];
            $movimiento['destino_final'] = $receptores[$movimiento['destino']]['actual'];
        }
        unset($movimiento);

        return array_values($agregados);
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
