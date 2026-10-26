<?php

namespace App\Services;

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
                    'saldo' => 0,
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
                    if ($r->fecha_recepcion && substr((string) $r->fecha_recepcion, 0, 10) <= $corte) {
                        $asegurar($id, $destino, (string) $r->sucursal_destino)->entradas += $cantidad;
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

        $filtro = trim((string) $sucursal);
        $filtroKey = $filtro === '' ? null : $this->identificar(null, $filtro);
        $porCodigo = collect();
        $porSucursal = [];
        $detalle = [];
        $todas = [];

        foreach ($agrupado as $articuloId => $porLocal) {
            foreach ($porLocal as $clave => $row) {
                $row->saldo = self::saldo($row->entradas, $row->salidas, $row->ventas);
                $todas[$clave] = $row->sucursal;

                if (!isset($porSucursal[$clave])) {
                    $porSucursal[$clave] = (object) [
                        'sucursal_key' => $clave, 'sucursal' => $row->sucursal,
                        'entradas' => 0, 'salidas' => 0, 'ventas' => 0,
                        'en_transito' => 0, 'saldo' => 0, 'codigos' => 0,
                    ];
                }
                $s = $porSucursal[$clave];
                $s->entradas += $row->entradas;
                $s->salidas += $row->salidas;
                $s->ventas += $row->ventas;
                $s->en_transito += $row->en_transito;
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
                            'ventas' => 0, 'en_transito' => 0,
                        ];
                    }
                    $total = $porCodigo[$articuloId];
                    $total->saldo += $row->saldo;
                    $total->entradas += $row->entradas;
                    $total->salidas += $row->salidas;
                    $total->ventas += $row->ventas;
                    $total->en_transito += $row->en_transito;
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
}
