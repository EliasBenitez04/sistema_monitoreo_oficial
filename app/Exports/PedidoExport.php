<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class PedidoExport implements WithMultipleSheets
{
    protected $id_pedido;

    protected $sucursalesPrioritarias = [
        14,
        8,
        2,
        9,
        5,
    ];

    protected $sucursalesExcluidas = [
        1,   // Sucursal 01
        25,  // Sucursal 25
    ];

    protected $maxSugerencias = 5;

    protected $pedidosPorSucursal = [];

    public function __construct($id_pedido)
    {
        $this->id_pedido = $id_pedido;
    }

    public function sheets(): array
    {

        $resultado = $this->procesarPedido();

        $sheets = [];

        $sheets[] = new PedidoResumenSheet(
            $resultado['resumen']
        );

        foreach (
            $this->pedidosPorSucursal
            as $codigoSucursal => $sucursal
        ) {

            $sheets[] = new PedidoSucursalSheet(
                $sucursal['nombre'],
                $sucursal['items']
            );
        }

        return $sheets;
    }

    protected function procesarPedido(): array
    {

        $items = DB::table('detalle_pedido as d')
            ->join(
                'pedido_compras as p',
                'p.id_pedido',
                '=',
                'd.id_pedido_compras'
            )
            ->join(
                'articulos as a',
                'a.id_articulo',
                '=',
                'd.id_articulo'
            )
            ->where(
                'p.id_pedido',
                $this->id_pedido
            )
            ->select(
                'p.nro_pedido',
                'p.cod_suc',
                'a.art_codigo',
                'a.art_descripcion',
                'd.det_cantidad'
            )
            ->get();

        $resumen = [];

        foreach ($items as $item) {

            $cantidadSolicitada =
                (int) $item->det_cantidad;

            $stocks = DB::table('stock_sucursales')
                ->where(
                    'codigo',
                    $item->art_codigo
                )
                ->where(
                    'cantidad',
                    '>',
                    0
                )
                ->get();

            if ($stocks->isEmpty()) {

                $resumen[] = [

                    'nro_pedido' =>
                    $item->nro_pedido,

                    'codigo' =>
                    $item->art_codigo,

                    'articulo' =>
                    $item->art_descripcion,

                    'necesita' =>
                    $cantidadSolicitada,

                    'total_retirar' =>
                    0,

                    'pendiente' =>
                    $cantidadSolicitada,

                    'situacion' =>
                    'SIN STOCK',

                    'observacion' =>
                    'No hay unidades disponibles.',
                ];

                continue;
            }

            $stocksProcesados = $stocks
                ->map(function ($stock) {

                    preg_match(
                        '/Sucursal:\s*([0-9]+)/',
                        $stock->sucursal ?? '',
                        $matches
                    );

                    $stock->codigo_sucursal =
                        isset($matches[1])
                        ? (int) $matches[1]
                        : null;

                    $stock->cantidad =
                        (int) $stock->cantidad;

                    return $stock;
                })
                ->filter(function ($stock) {

                    return
                        $stock->codigo_sucursal !== null &&
                        $stock->cantidad > 0;
                })
                ->values();

            $stocksProcesados = $stocksProcesados
                ->reject(function ($stock) {
                    return in_array(
                        $stock->codigo_sucursal,
                        $this->sucursalesExcluidas
                    );
                })
                ->values();

            if ($stocksProcesados->isEmpty()) {

                $resumen[] = [

                    'nro_pedido' =>
                    $item->nro_pedido,

                    'codigo' =>
                    $item->art_codigo,

                    'articulo' =>
                    $item->art_descripcion,

                    'necesita' =>
                    $cantidadSolicitada,

                    'total_retirar' =>
                    0,

                    'pendiente' =>
                    $cantidadSolicitada,

                    'situacion' =>
                    'SIN STOCK',

                    'observacion' =>
                    'No se pudo identificar la sucursal.',
                ];

                continue;
            }

            $prioritarias = $stocksProcesados
                ->filter(function ($stock) {

                    return in_array(
                        $stock->codigo_sucursal,
                        $this->sucursalesPrioritarias
                    );
                })
                ->sortBy(function ($stock) {

                    return array_search(
                        $stock->codigo_sucursal,
                        $this->sucursalesPrioritarias
                    );
                })
                ->values();

            $otrasSucursales = $stocksProcesados
                ->filter(function ($stock) {

                    return !in_array(
                        $stock->codigo_sucursal,
                        $this->sucursalesPrioritarias
                    );
                })
                ->sortByDesc('cantidad')
                ->values();

            $stocksOrdenados = $prioritarias
                ->concat($otrasSucursales)
                ->values();

            $sucursalCompleta = null;

            foreach (
                $this->sucursalesPrioritarias
                as $codigoPrioritario
            ) {

                $encontrada =
                    $stocksProcesados->first(
                        function ($stock) use (
                            $codigoPrioritario,
                            $cantidadSolicitada
                        ) {

                            return
                                $stock->codigo_sucursal ==
                                $codigoPrioritario &&
                                $stock->cantidad >=
                                $cantidadSolicitada;
                        }
                    );

                if ($encontrada) {

                    $sucursalCompleta =
                        $encontrada;

                    break;
                }
            }

            $asignaciones = [];

            if ($sucursalCompleta) {

                $asignaciones[] = [

                    'sucursal' =>
                    $sucursalCompleta->sucursal,

                    'codigo_sucursal' =>
                    $sucursalCompleta->codigo_sucursal,

                    'cantidad' =>
                    $cantidadSolicitada,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | REPARTIR ENTRE VARIAS
            |--------------------------------------------------------------------------
            */ else {

                $restante =
                    $cantidadSolicitada;

                foreach (
                    $stocksOrdenados
                    as $stock
                ) {

                    if ($restante <= 0) {
                        break;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | CANTIDAD A TOMAR
                    |--------------------------------------------------------------------------
                    */

                    $cantidadTomar =
                        min(
                            $stock->cantidad,
                            $restante
                        );

                    if ($cantidadTomar <= 0) {
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | ASIGNACIÓN
                    |--------------------------------------------------------------------------
                    */

                    $asignaciones[] = [

                        'sucursal' =>
                        $stock->sucursal,

                        'codigo_sucursal' =>
                        $stock->codigo_sucursal,

                        'cantidad' =>
                        $cantidadTomar,
                    ];

                    /*
                    |--------------------------------------------------------------------------
                    | RESTANTE
                    |--------------------------------------------------------------------------
                    */

                    $restante -=
                        $cantidadTomar;

                    /*
                    |--------------------------------------------------------------------------
                    | MÁXIMO
                    |--------------------------------------------------------------------------
                    */

                    if (
                        count($asignaciones)
                        >= $this->maxSugerencias
                    ) {
                        break;
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | TOTAL RETIRADO
            |--------------------------------------------------------------------------
            */

            $totalRetirar = 0;

            foreach (
                $asignaciones
                as $asignacion
            ) {

                $totalRetirar +=
                    (int) $asignacion['cantidad'];
            }

            /*
            |--------------------------------------------------------------------------
            | PENDIENTE
            |--------------------------------------------------------------------------
            */

            $pendiente =
                max(
                    0,
                    $cantidadSolicitada -
                        $totalRetirar
                );

            /*
            |--------------------------------------------------------------------------
            | SITUACIÓN
            |--------------------------------------------------------------------------
            */

            if ($pendiente === 0) {

                $situacion =
                    'COMPLETO';

                $observacion =
                    'Pedido completo.';
            } elseif ($totalRetirar > 0) {

                $situacion =
                    'PARCIAL';

                $observacion =
                    'Faltan ' .
                    $pendiente .
                    ' unidad(es).';
            } else {

                $situacion =
                    'SIN STOCK';

                $observacion =
                    'No hay unidades disponibles.';
            }

            /*
            |--------------------------------------------------------------------------
            | AGREGAR AL RESUMEN
            |--------------------------------------------------------------------------
            */

            $resumen[] = [

                'nro_pedido' =>
                $item->nro_pedido,

                'codigo' =>
                $item->art_codigo,

                'articulo' =>
                $item->art_descripcion,

                'necesita' =>
                $cantidadSolicitada,

                'total_retirar' =>
                $totalRetirar,

                'pendiente' =>
                $pendiente,

                'situacion' =>
                $situacion,

                'observacion' =>
                $observacion,
            ];

            /*
            |--------------------------------------------------------------------------
            | AGRUPAR POR SUCURSAL
            |--------------------------------------------------------------------------
            */

            foreach (
                $asignaciones
                as $asignacion
            ) {

                $codigoSucursal =
                    $asignacion['codigo_sucursal'];

                $nombreSucursal =
                    $asignacion['sucursal'];

                /*
                |--------------------------------------------------------------------------
                | CREAR SUCURSAL
                |--------------------------------------------------------------------------
                */

                if (
                    !isset(
                        $this->pedidosPorSucursal[$codigoSucursal]
                    )
                ) {

                    $this->pedidosPorSucursal[$codigoSucursal] = [

                        'nombre' =>
                        $this->limpiarNombreHoja(
                            $nombreSucursal
                        ),

                        'items' => [],
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | CLAVE ÚNICA DEL ARTÍCULO
                |--------------------------------------------------------------------------
                */

                $clave =
                    $item->nro_pedido .
                    '|' .
                    $item->art_codigo;

                /*
                |--------------------------------------------------------------------------
                | BUSCAR SI YA EXISTE
                |--------------------------------------------------------------------------
                */

                $indiceExistente = null;

                foreach (
                    $this->pedidosPorSucursal[$codigoSucursal]['items']
                    as $indice => $existente
                ) {

                    if (
                        $existente['clave'] ===
                        $clave
                    ) {

                        $indiceExistente =
                            $indice;

                        break;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | SUMAR SI YA EXISTE
                |--------------------------------------------------------------------------
                */

                if (
                    $indiceExistente !== null
                ) {

                    $this->pedidosPorSucursal[$codigoSucursal]['items'][$indiceExistente]['cantidad'] +=
                        $asignacion['cantidad'];
                }

                /*
                |--------------------------------------------------------------------------
                | NUEVO ARTÍCULO
                |--------------------------------------------------------------------------
                */ else {

                    $this->pedidosPorSucursal[$codigoSucursal]['items'][] = [

                        'clave' =>
                        $clave,

                        'nro_pedido' =>
                        $item->nro_pedido,

                        'codigo' =>
                        $item->art_codigo,

                        'articulo' =>
                        $item->art_descripcion,

                        'cantidad' =>
                        $asignacion['cantidad'],
                    ];
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | RETORNAR RESULTADO
        |--------------------------------------------------------------------------
        */

        return [
            'resumen' =>
            $resumen,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | LIMPIAR NOMBRE DE HOJA
    |--------------------------------------------------------------------------
    */

    private function limpiarNombreHoja(
        $nombre
    ): string {

        $nombre = preg_replace(
            '/[\/\\\\\?\*\[\]:]/',
            '',
            $nombre
        );

        $nombre = mb_substr(
            trim($nombre),
            0,
            31
        );

        if ($nombre === '') {
            $nombre = 'SUCURSAL';
        }

        return $nombre;
    }
}


/*
|--------------------------------------------------------------------------
| HOJA RESUMEN
|--------------------------------------------------------------------------
*/

class PedidoResumenSheet implements
    FromArray,
    WithHeadings,
    WithTitle,
    ShouldAutoSize
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'RESUMEN';
    }

    public function headings(): array
    {
        return [

            'N° Pedido',
            'Código',
            'Artículo',
            'Se necesitan',
            'Total a retirar',
            'Queda pendiente',
            'Situación',
            'Observación',
        ];
    }

    public function array(): array
    {
        $data = [];

        foreach ($this->data as $item) {

            $data[] = [

                $item['nro_pedido'],

                $item['codigo'],

                $item['articulo'],

                $item['necesita'],

                $item['total_retirar'],

                $item['pendiente'],

                $item['situacion'],

                $item['observacion'],
            ];
        }

        return $data;
    }
}


/*
|--------------------------------------------------------------------------
| HOJA DE CADA SUCURSAL
|--------------------------------------------------------------------------
*/

class PedidoSucursalSheet implements
    FromArray,
    WithHeadings,
    WithTitle,
    ShouldAutoSize
{
    protected $nombreSucursal;
    protected $items;

    public function __construct(
        $nombreSucursal,
        array $items
    ) {

        $this->nombreSucursal =
            $nombreSucursal;

        $this->items =
            $items;
    }

    public function title(): string
    {
        return $this->nombreSucursal;
    }

    public function headings(): array
    {
        return [

            'N° Pedido',
            'Código',
            'Artículo',
            'Cantidad a retirar',
        ];
    }

    public function array(): array
    {
        $data = [];

        foreach ($this->items as $item) {

            $data[] = [

                $item['nro_pedido'],

                $item['codigo'],

                $item['articulo'],

                $item['cantidad'],
            ];
        }

        return $data;
    }
}
