<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Saldo documental de accesorios por código completo y sucursal.
 *
 * Entradas: exclusivamente remisiones con fecha_recepcion <= corte.
 * Salidas: remisiones emitidas <= corte, aunque el destino aún no recibió.
 * Ventas: suma firmada de ventas y notas de crédito desde el inicio histórico
 * de la tabla hasta el corte. No restamos ventas dos veces por reimportaciones.
 *
 * No parte de compras/FCO ni de stock_sucursales: los FCO no identifican a
 * qué destino fue físicamente cada unidad. Depósito de insumos es proveedor
 * del circuito y queda excluido de saldos de sucursales.
 */
class AccesoriosStockService
{
    private const NOMBRES = [
        1 => 'CASA CENTRAL',
        2 => 'SAN LORENZO',
        3 => 'LUQUE',
        4 => 'MALL',
        5 => 'L06',
        6 => 'LA RURAL',
        7 => 'BONANZA',
        8 => 'SHOP SAN LO3',
        9 => 'MULTIPLAZA',
        14 => 'PINEDO SHOPPING',
        15 => 'ÑEMBY',
        16 => 'SHOPPING MARIANO',
        22 => 'JARDINES LUQUE',
        25 => 'COMERCIAL MATRIZ',
    ];

    private const ALIAS = [
        'CASA CENTRAL' => 1,
        'CENTRAL' => 1,
        'SAN LORENZO' => 2,
        'LUQUE' => 3,
        'MALL' => 4,
        'LA RURAL' => 6,
        'RURAL' => 6,
        'SHOP SAN LO3' => 8,
        'SHOP SAN LORENZO' => 8,
        'SHOP SAN LO 3' => 8,
        'SHOPPING SAN LORENZO' => 8,
        'MULTIPLAZA' => 9,
        'MULTI PLAZA' => 9,
        'PINEDO' => 14,
        'PINEDO SHOPPING' => 14,
        'SHOPPING PINEDO' => 14,
        'SHOPPING MARIANO' => 16,
        'MARIANO' => 16,
        'SHOP MARIANO' => 16,
        'JARDINES LUQUE' => 22,
        'LOS JARDINES' => 22,
        'JARDINES' => 22,
        'COMERCIAL MATRIZ' => 25,
        'MATRIZ' => 25,
    ];

    private array $nombresBd = [];
    private array $aliasBd = [];

    public function __construct()
    {
        if (Schema::hasTable('sucursal')) {
            foreach (DB::table('sucursal')->pluck('suc_descri', 'cod_suc') as $codigo => $nombre) {
                $id = (int) $codigo;
                $this->nombresBd[$id] = (string) $nombre;
                $clave = self::normalizar((string) $nombre);
                if ($clave !== '') {
                    $this->aliasBd[$clave] = $id;
                }
            }
        }
    }

    public static function normalizar(?string $valor): string
    {
        $texto = strtoupper(Str::ascii(trim((string) $valor)));
        return trim(preg_replace('/\s+/', ' ', str_replace(['-', '_'], ' ', $texto)));
    }

    public static function esDeposito(?string $nombre): bool
    {
        $texto = self::normalizar($nombre);
        return strpos($texto, 'DEPOSITO') !== false
            || strpos($texto, 'INSUMOS') !== false
            || strpos($texto, 'TEJIDOS') !== false;
    }

    public static function saldo(int $entradas, int $salidas, int $ventasNetas): int
    {
        // Una NCR llega negativa y revierte parte de las ventas.
        return $entradas - $salidas - $ventasNetas;
    }

    /**
     * Desglosa las etapas, sin sumar dos veces la misma prenda al informar
     * ingreso inicial y posterior distribución. Son métricas por ETAPA.
     * Una transferencia repetida puede representar el mismo artículo otra
     * vez; por eso no se llama "unidades únicas" a los despachos.
     */
    public static function clasificarFlujo(
        ?string $origen,
        ?string $destino,
        bool $origenDeposito,
        bool $recibida,
        int $cantidad
    ): array {
        $etapas = [
            'ingreso_deposito' => 0,
            'distribuido_central' => 0,
            'recibido_locales' => 0,
            'traslado_locales' => 0,
            'otros_movimientos' => 0,
        ];

        if ($cantidad <= 0 || $origen === $destino) {
            return $etapas;
        }
        if ($origenDeposito && $destino === 'S:1') {
            // Stock disponible en Central solo después de recibir.
            $etapas['ingreso_deposito'] = $recibida ? $cantidad : 0;
        } elseif ($origen === 'S:1' && $destino !== null) {
            $etapas['distribuido_central'] = $cantidad;
            $etapas['recibido_locales'] = $recibida ? $cantidad : 0;
        } elseif ($origen !== null && $origen !== 'S:1'
            && $destino !== null && $destino !== 'S:1') {
            $etapas['traslado_locales'] = $cantidad;
        } else {
            $etapas['otros_movimientos'] = $cantidad;
        }

        return $etapas;
    }

    private function identificar($codigo, ?string $nombre): ?string
    {
        if (self::esDeposito($nombre)) {
            return null; // Proveedor, fuera del stock de los locales.
        }

        $id = (int) $codigo;
        if ($id > 0) {
            return 'S:' . $id;
        }

        $limpio = self::normalizar($nombre);
        if ($limpio === '') {
            return null;
        }
        $id = self::ALIAS[$limpio] ?? ($this->aliasBd[$limpio] ?? 0);
        return $id > 0 ? 'S:' . $id : 'T:' . $limpio;
    }

    /**
     * Variantes de nombre para comparar ventas/remisiones al filtrar sucursal.
     * Evita perder unidades cuando el export usa "RURAL" y remisiones "LA RURAL".
     */
    public function aliasesPara(?string $nombre): array
    {
        $key = $this->identificar(null, $nombre);
        $aliases = [strtoupper(trim((string) $nombre))];
        if ($key !== null && strpos($key, 'S:') === 0) {
            $id = (int) substr($key, 2);
            foreach (self::ALIAS as $alias => $codigo) {
                if ($codigo === $id) {
                    $aliases[] = $alias;
                }
            }
            if (isset(self::NOMBRES[$id])) {
                $aliases[] = strtoupper(self::NOMBRES[$id]);
            }
            if (isset($this->nombresBd[$id])) {
                $aliases[] = strtoupper($this->nombresBd[$id]);
            }
        }
        return array_values(array_unique(array_filter($aliases, function ($a) {
            return trim((string) $a) !== '';
        })));
    }

    public function codigoPara(?string $nombre): ?int
    {
        $key = $this->identificar(null, $nombre);
        return $key && strpos($key, 'S:') === 0
            ? (int) substr($key, 2) : null;
    }

    private function nombre(string $clave, string $importado): string
    {
        if (strpos($clave, 'S:') === 0) {
            $id = (int) substr($clave, 2);
            return self::NOMBRES[$id] ?? ($this->nombresBd[$id] ?? ('Sucursal ' . $id));
        }
        return trim($importado) ?: substr($clave, 2);
    }

    public function calcular(array $articuloIds, string $corte, ?string $sucursal = null): array
    {
        $articuloIds = array_values(array_unique(array_map('intval', $articuloIds)));
        $agrupado = [];
        $sinOrigen = 0;
        $sinDestino = 0;
        $ventasSinLocal = 0;
        $movimientosNoRecibidos = 0;
        $flujoPorCodigo = [];

        $asegurar = function (int $id, string $clave, string $nombre) use (&$agrupado) {
            if (!isset($agrupado[$id][$clave])) {
                $agrupado[$id][$clave] = (object) [
                    'articulo_id' => $id,
                    'sucursal_key' => $clave,
                    'sucursal' => $this->nombre($clave, $nombre),
                    'entradas' => 0,
                    'salidas' => 0,
                    'ventas' => 0,
                    'en_transito' => 0,
                    'ajuste' => 0,
                    'saldo' => 0,
                    'fecha_llegada' => null,
                    'ultima_recepcion' => null,
                ];
            }
            return $agrupado[$id][$clave];
        };

        if ($articuloIds && Schema::hasTable('ot_logistica_remisiones')) {
            // Usamos el vínculo con catálogo, no OT, ya que los accesorios
            // pueden circular sin id_ot o sin id_logistica_detalle.
            $remisiones = DB::table('ot_logistica_remisiones as r')
                ->whereIn('r.accesorio_articulo_id', $articuloIds)
                ->whereRaw('COALESCE(r.fecha_remision, r.fecha_creacion) <= ?', [$corte])
                ->get([
                    'r.accesorio_articulo_id', 'r.cod_sucursal_salida',
                    'r.sucursal_salida', 'r.cod_sucursal_destino',
                    'r.sucursal_destino', 'r.cantidad', 'r.fecha_recepcion',
                ]);

            foreach ($remisiones as $r) {
                $id = (int) $r->accesorio_articulo_id;
                $cantidad = (int) $r->cantidad;
                if ($cantidad <= 0) {
                    continue;
                }
                $salida = $this->identificar($r->cod_sucursal_salida, $r->sucursal_salida);
                $destino = $this->identificar($r->cod_sucursal_destino, $r->sucursal_destino);
                $recibida = $r->fecha_recepcion
                    && substr((string) $r->fecha_recepcion, 0, 10) <= $corte;
                $etapas = self::clasificarFlujo(
                    $salida,
                    $destino,
                    self::esDeposito($r->sucursal_salida),
                    (bool) $recibida,
                    $cantidad
                );
                if (!isset($flujoPorCodigo[$id])) {
                    $flujoPorCodigo[$id] = array_fill_keys(array_keys($etapas), 0);
                }
                foreach ($etapas as $etapa => $unidades) {
                    $flujoPorCodigo[$id][$etapa] += $unidades;
                }

                if (!$salida && !self::esDeposito($r->sucursal_salida)) {
                    $sinOrigen++;
                }
                if (!$destino && !self::esDeposito($r->sucursal_destino)) {
                    $sinDestino++;
                }
                if ($salida && $salida !== $destino) {
                    $asegurar($id, $salida, (string) $r->sucursal_salida)->salidas += $cantidad;
                }
                if ($destino && $destino !== $salida) {
                    if ($recibida) {
                        $local = $asegurar($id, $destino, (string) $r->sucursal_destino);
                        $local->entradas += $cantidad;
                        $fecha = substr((string) $r->fecha_recepcion, 0, 10);
                        $local->fecha_llegada = $local->fecha_llegada === null
                            ? $fecha : min($local->fecha_llegada, $fecha);
                        $local->ultima_recepcion = $local->ultima_recepcion === null
                            ? $fecha : max($local->ultima_recepcion, $fecha);
                    } else {
                        $asegurar($id, $destino, (string) $r->sucursal_destino)->en_transito += $cantidad;
                        $movimientosNoRecibidos += $cantidad;
                    }
                }
            }
        }

        if ($articuloIds && Schema::hasTable('ventas')) {
            $ventas = DB::table('ventas as v')
                ->join('accesorios_articulos as a', function ($join) {
                    $join->on(DB::raw('UPPER(BTRIM(v.codigo))'), '=', 'a.codigo');
                })
                ->whereIn('a.id', $articuloIds)
                ->where('v.fecha', '<=', $corte)
                ->select('a.id', 'v.local')
                ->selectRaw('SUM(v.cantidad) AS unidades')
                ->groupBy('a.id', 'v.local')->get();

            foreach ($ventas as $v) {
                $clave = $this->identificar(null, $v->local);
                if (!$clave) {
                    $ventasSinLocal += abs((int) $v->unidades);
                    continue;
                }
                $asegurar((int) $v->id, $clave, (string) $v->local)->ventas += (int) $v->unidades;
            }
        }

        // Ajustes realizados por ADMIN. Se conservan como eventos auditables,
        // separados de las remisiones y ventas, y respetan el corte histórico.
        if ($articuloIds && Schema::hasTable('accesorios_stock_ajustes')) {
            $ajustes = DB::table('accesorios_stock_ajustes')
                ->whereIn('accesorio_articulo_id', $articuloIds)
                ->where('fecha_ajuste', '<=', $corte)
                ->select('accesorio_articulo_id', 'sucursal_key')
                ->selectRaw('MAX(sucursal) AS sucursal, SUM(diferencia) AS diferencia')
                ->groupBy('accesorio_articulo_id', 'sucursal_key')
                ->get();

            foreach ($ajustes as $ajuste) {
                $asegurar(
                    (int) $ajuste->accesorio_articulo_id,
                    (string) $ajuste->sucursal_key,
                    (string) $ajuste->sucursal
                )->ajuste += (int) $ajuste->diferencia;
            }
        }

        $filtro = trim((string) $sucursal);
        $filtroKey = $filtro === '' ? null : $this->identificar(null, $filtro);
        $porCodigo = collect();
        $porSucursal = [];
        $detalle = [];
        $todas = [];

        foreach ($agrupado as $articuloId => $porLocal) {
            foreach ($porLocal as $clave => $row) {
                $row->saldo = self::saldo($row->entradas, $row->salidas, $row->ventas) + $row->ajuste;
                $todas[$clave] = $row->sucursal;

                if (!isset($porSucursal[$clave])) {
                    $porSucursal[$clave] = (object) [
                        'sucursal_key' => $clave, 'sucursal' => $row->sucursal,
                        'entradas' => 0, 'salidas' => 0, 'ventas' => 0,
                        'en_transito' => 0, 'ajuste' => 0, 'saldo' => 0, 'codigos' => 0,
                    ];
                }
                $s = $porSucursal[$clave];
                $s->entradas += $row->entradas;
                $s->salidas += $row->salidas;
                $s->ventas += $row->ventas;
                $s->en_transito += $row->en_transito;
                $s->ajuste += $row->ajuste;
                $s->saldo += $row->saldo;
                $s->codigos++;

                if ($filtroKey === null || $filtroKey === $clave) {
                    if (!isset($detalle[$articuloId])) {
                        $detalle[$articuloId] = [];
                    }
                    $detalle[$articuloId][] = $row;
                    if (!isset($porCodigo[$articuloId])) {
                        $porCodigo[$articuloId] = (object) [
                            'saldo' => 0, 'entradas' => 0, 'salidas' => 0,
                            'ventas' => 0, 'en_transito' => 0, 'ajuste' => 0,
                        ];
                    }
                    $total = $porCodigo[$articuloId];
                    $total->saldo += $row->saldo;
                    $total->entradas += $row->entradas;
                    $total->salidas += $row->salidas;
                    $total->ventas += $row->ventas;
                    $total->en_transito += $row->en_transito;
                    $total->ajuste += $row->ajuste;
                }
            }
        }

        foreach ($detalle as &$filas) {
            usort($filas, function ($a, $b) {
                return strcmp($a->sucursal, $b->sucursal);
            });
        }
        unset($filas);

        $sucursales = collect(array_values($porSucursal))
            ->when($filtroKey !== null, function ($items) use ($filtroKey) {
                return $items->filter(function ($s) use ($filtroKey) {
                    return $s->sucursal_key === $filtroKey;
                });
            })
            ->sortBy('sucursal')->values();

        return [
            'flujoPorCodigo' => collect($flujoPorCodigo),
            'porCodigo' => $porCodigo,
            'porSucursal' => $sucursales,
            'detalle' => collect($detalle),
            'sucursalesDisponibles' => collect($todas)->sort()->values(),
            'sinOrigen' => $sinOrigen,
            'sinDestino' => $sinDestino,
            'ventasSinLocal' => $ventasSinLocal,
            'enTransito' => $movimientosNoRecibidos,
            'corte' => $corte,
        ];
    }

    /**
     * Existencias positivas ordenadas por tiempo sin venta en cada sucursal.
     * Reutiliza el saldo al corte: "desde" no recorta recepciones ni facturas.
     * Las fechas describen el código/local; no atribuyen unidades a lotes FCO.
     */
    public function retencion(Collection $catalogo, array $stock, string $corte, string $orden = 'dias'): Collection
    {
        $articulos = $catalogo->keyBy('id');
        $locales = collect();
        foreach ($stock['detalle'] as $id => $filas) {
            if (!$articulos->has($id)) {
                continue;
            }
            foreach ($filas as $local) {
                if ((int) $local->saldo > 0) {
                    $locales->push($local);
                }
            }
        }

        $ventasPorLocal = [];
        $ids = $locales->pluck('articulo_id')->unique()->values()->all();
        if ($ids && Schema::hasTable('ventas')) {
            // Una única consulta para la última factura de cada código/local.
            // ROW_NUMBER funciona en PostgreSQL 9.5. id desempata ventas del
            // mismo día y mantiene el comprobante unido a su fecha real.
            $ordenadas = DB::table('ventas as v')
                ->join('accesorios_articulos as a', function ($join) {
                    $join->on(DB::raw('UPPER(BTRIM(v.codigo))'), '=', 'a.codigo');
                })
                ->whereIn('a.id', $ids)
                ->where('v.fecha', '<=', $corte)
                ->where('v.cantidad', '>', 0)
                ->whereRaw("UPPER(BTRIM(COALESCE(v.comprobante, ''))) NOT LIKE 'NCR%'")
                ->select('a.id as articulo_id', 'v.id as venta_id', 'v.local', 'v.fecha', 'v.comprobante')
                ->selectRaw('ROW_NUMBER() OVER (PARTITION BY a.id, UPPER(BTRIM(v.local)) ORDER BY v.fecha DESC, v.id DESC) AS posicion');

            foreach (DB::query()->fromSub($ordenadas, 'ultimas')->where('posicion', 1)->get() as $venta) {
                $clave = $this->identificar(null, $venta->local);
                if ($clave === null) {
                    continue;
                }
                $anterior = $ventasPorLocal[$venta->articulo_id][$clave] ?? null;
                // Unificamos también MARIANO/SHOPPING MARIANO y demás alias.
                if ($anterior === null || $venta->fecha > $anterior->fecha
                    || ($venta->fecha === $anterior->fecha && (int) $venta->venta_id > (int) $anterior->venta_id)) {
                    $ventasPorLocal[$venta->articulo_id][$clave] = $venta;
                }
            }
        }

        $fechaCorte = Carbon::parse($corte)->startOfDay();
        $filas = $locales->map(function ($local) use ($articulos, $ventasPorLocal, $fechaCorte) {
            $articulo = $articulos->get($local->articulo_id);
            $venta = $ventasPorLocal[$local->articulo_id][$local->sucursal_key] ?? null;
            $llegada = $local->fecha_llegada;
            $ultimaVenta = $venta ? substr((string) $venta->fecha, 0, 10) : null;
            // Si la factura es anterior a la llegada documentada, contamos
            // desde la llegada. Nunca inventamos fechas para stock ajustado.
            $referencia = $ultimaVenta;
            $desdeLlegada = $llegada !== null && ($ultimaVenta === null || $llegada > $ultimaVenta);
            if ($desdeLlegada) {
                $referencia = $llegada;
            }

            return (object) [
                'articulo_id' => (int) $local->articulo_id,
                'codigo' => $articulo->codigo,
                'descripcion' => $articulo->descripcion,
                'sucursal' => $local->sucursal,
                'cantidad' => (int) $local->saldo,
                'fecha_llegada' => $llegada,
                'ultima_recepcion' => $local->ultima_recepcion,
                'ultima_venta' => $ultimaVenta,
                'ultima_factura' => $venta ? trim((string) $venta->comprobante) : '',
                'dias_local' => $llegada !== null ? (int) Carbon::parse($llegada)->diffInDays($fechaCorte) : null,
                'dias_retenidos' => $referencia !== null ? (int) Carbon::parse($referencia)->diffInDays($fechaCorte) : null,
                'desde_llegada' => $desdeLlegada,
            ];
        });

        return $filas->sort(function ($a, $b) use ($orden) {
            $campo = $orden === 'cantidad' ? 'cantidad' : ($orden === 'antiguedad' ? 'dias_local' : 'dias_retenidos');
            $comparacion = 0;
            if ($orden !== 'codigo') {
                $comparacion = $a->$campo === null
                    ? ($b->$campo === null ? 0 : 1)
                    : ($b->$campo === null ? -1 : $b->$campo <=> $a->$campo);
            }
            return $comparacion ?: strcmp($a->codigo, $b->codigo) ?: strcmp($a->sucursal, $b->sucursal);
        })->values();
    }
}
