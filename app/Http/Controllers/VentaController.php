<?php

namespace App\Http\Controllers;

use App\Imports\VentasImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class VentaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:pedido_compras index');
    }

    public function index(Request $request)
    {
        $this->asegurarTablas();

        $fechaMaxima = Cache::remember(
            'ventas:fecha_maxima',
            300,
            function () {
                return DB::table('ventas')->max('fecha');
            }
        );

        $todo = $request->boolean('todo');
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');

        /*
         * Por defecto mostramos el último día importado.
         * Con ?todo=1 se consulta todo el histórico.
         */
        if (!$todo && !$desde && !$hasta && $fechaMaxima) {
            $desde = $fechaMaxima;
            $hasta = $fechaMaxima;
        }

        $local = trim((string) $request->input('local', ''));
        $vendedor = trim((string) $request->input('vendedor', ''));
        $tipo = trim((string) $request->input('tipo', ''));
        $buscar = trim((string) $request->input('buscar', ''));

        $base = DB::table('ventas as v');

        $this->aplicarFiltros(
            $base,
            $desde,
            $hasta,
            $local,
            $vendedor,
            $tipo,
            $buscar
        );

        $resumen = (clone $base)
            ->selectRaw(
                "COUNT(*) as lineas,
                 COALESCE(SUM(v.cantidad), 0) as unidades_netas,
                 COALESCE(SUM(CASE WHEN v.cantidad > 0 THEN v.cantidad ELSE 0 END), 0) as unidades_vendidas,
                 COALESCE(SUM(CASE WHEN v.cantidad < 0 THEN ABS(v.cantidad) ELSE 0 END), 0) as unidades_devueltas,

                 /*
                  * PLISTA, DTO y PVTA ya son totales por línea en el export.
                  * Las NCR llegan firmadas en negativo, por lo tanto se suman
                  * directamente sin volver a multiplicar por CANTIDAD.
                  */
                 COALESCE(SUM(v.p_lista), 0) as venta_lista,
                 COALESCE(SUM(v.descuento), 0) as descuento_otorgado,
                 COALESCE(SUM(v.p_venta), 0) as venta_neta,

                 COALESCE(SUM(CASE WHEN v.p_venta > 0 THEN v.p_venta ELSE 0 END), 0) as venta_positiva,
                 COALESCE(SUM(CASE WHEN v.p_venta < 0 THEN ABS(v.p_venta) ELSE 0 END), 0) as devoluciones_valor,

                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN COALESCE(v.local, '') || '|' || COALESCE(v.comprobante, '') END) as tickets,
                 COUNT(DISTINCT v.codigo) as codigos,
                 COUNT(DISTINCT v.local) as locales,
                 COUNT(DISTINCT v.vendedor) as vendedores"
            )
            ->first();

        $resumen->ticket_promedio =
            (int) $resumen->tickets > 0
                ? (float) $resumen->venta_neta
                    / (int) $resumen->tickets
                : 0;

        $resumen->precio_promedio_unidad =
            (int) $resumen->unidades_netas > 0
                ? (float) $resumen->venta_neta
                    / (int) $resumen->unidades_netas
                : 0;

        $resumen->porcentaje_descuento =
            (float) $resumen->venta_lista > 0
                ? round(
                    ((float) $resumen->descuento_otorgado
                        / (float) $resumen->venta_lista) * 100,
                    1
                )
                : 0;

        $porLocal = (clone $base)
            ->select(
                'v.local'
            )
            ->selectRaw(
                "COALESCE(SUM(v.cantidad), 0) as unidades_netas,
                 COALESCE(SUM(CASE WHEN v.cantidad > 0 THEN v.cantidad ELSE 0 END), 0) as unidades_vendidas,
                 COALESCE(SUM(CASE WHEN v.cantidad < 0 THEN ABS(v.cantidad) ELSE 0 END), 0) as devoluciones,
                 COALESCE(SUM(v.p_lista), 0) as venta_lista,
                 COALESCE(SUM(v.descuento), 0) as descuento,
                 COALESCE(SUM(v.p_venta), 0) as venta_neta,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.comprobante END) as tickets"
            )
            ->groupBy('v.local')
            ->orderByDesc('venta_neta')
            ->get()
            ->map(function ($item) {
                $item->ticket_promedio =
                    (int) $item->tickets > 0
                        ? (float) $item->venta_neta
                            / (int) $item->tickets
                        : 0;

                return $item;
            });

        /*
         * La carga inicial trae solamente el Top 15.
         * El ranking completo se consulta por AJAX cuando el usuario pulsa
         * "Ver todos", evitando generar y renderizar datos que normalmente
         * no se necesitan al entrar al módulo.
         */
        $porVendedor = (clone $base)
            ->whereNotNull('v.vendedor')
            ->where('v.vendedor', '<>', '')
            ->whereNotNull('v.local')
            ->where('v.local', '<>', '')
            ->select(
                'v.local',
                'v.vendedor'
            )
            ->selectRaw(
                "COALESCE(SUM(v.cantidad), 0) as unidades_netas,
                 COALESCE(SUM(v.p_venta), 0) as venta_neta,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.comprobante END) as tickets"
            )
            ->groupBy(
                'v.local',
                'v.vendedor'
            )
            ->orderByDesc('venta_neta')
            ->limit(15)
            ->get();

        $queryProductos = clone $base;

        if (Schema::hasTable('maestro_codigos')) {
            $queryProductos->leftJoin(
                'maestro_codigos as mc',
                'mc.cod_articulo',
                '=',
                'v.codigo'
            );
        }

        $porProducto = $queryProductos
            ->select(
                'v.codigo',
                'v.descripcion'
            )
            ->when(
                Schema::hasTable('maestro_codigos'),
                function ($q) {
                    $q->addSelect(
                        'mc.grupo',
                        'mc.grupo_plan',
                        'mc.temporada',
                        'mc.linea'
                    );
                }
            )
            ->selectRaw(
                "COALESCE(SUM(v.cantidad), 0) as unidades_netas,
                 COALESCE(SUM(v.p_venta), 0) as venta_neta"
            )
            ->groupBy(
                'v.codigo',
                'v.descripcion'
            )
            ->when(
                Schema::hasTable('maestro_codigos'),
                function ($q) {
                    $q->groupBy(
                        'mc.grupo',
                        'mc.grupo_plan',
                        'mc.temporada',
                        'mc.linea'
                    );
                }
            )
            ->orderByDesc('venta_neta')
            ->limit(20)
            ->get();

        $porDia = (clone $base)
            ->select('v.fecha')
            ->selectRaw(
                "COALESCE(SUM(v.cantidad), 0) as unidades_netas,
                 COALESCE(SUM(v.p_venta), 0) as venta_neta,
                 COALESCE(SUM(CASE WHEN v.p_venta < 0 THEN ABS(v.p_venta) ELSE 0 END), 0) as devoluciones,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN COALESCE(v.local, '') || '|' || COALESCE(v.comprobante, '') END) as tickets"
            )
            ->groupBy('v.fecha')
            ->orderBy('v.fecha')
            ->get();

        $detalleQuery = DB::table('ventas as v');

        if (Schema::hasTable('maestro_codigos')) {
            $detalleQuery->leftJoin(
                'maestro_codigos as mc',
                'mc.cod_articulo',
                '=',
                'v.codigo'
            );
        }

        $this->aplicarFiltros(
            $detalleQuery,
            $desde,
            $hasta,
            $local,
            $vendedor,
            $tipo,
            $buscar
        );

        $detalleQuery->select(
            'v.*'
        );

        if (Schema::hasTable('maestro_codigos')) {
            $detalleQuery->addSelect(
                'mc.grupo',
                'mc.grupo_plan',
                'mc.temporada',
                'mc.linea'
            );
        }

        /*
         * simplePaginate evita el COUNT(*) global que Laravel ejecuta con
         * paginate(). Para una tabla de ventas que crecerá todos los días,
         * esto reduce bastante el tiempo de respuesta.
         */
        $ventas = $detalleQuery
            ->orderByDesc('v.fecha')
            ->orderByDesc('v.id')
            ->simplePaginate(50)
            ->appends($request->query());

        /*
         * Los combos casi no cambian entre una apertura y otra.
         * Los cacheamos 15 minutos y se invalidan al terminar una importación.
         */
        $filtrosCatalogo = Cache::remember(
            'ventas:filtros_catalogo',
            900,
            function () {
                return [
                    'locales' => DB::table('ventas')
                        ->whereNotNull('local')
                        ->where('local', '<>', '')
                        ->distinct()
                        ->orderBy('local')
                        ->pluck('local'),
                    'vendedores' => DB::table('ventas')
                        ->whereNotNull('vendedor')
                        ->where('vendedor', '<>', '')
                        ->distinct()
                        ->orderBy('vendedor')
                        ->pluck('vendedor'),
                    'tipos' => DB::table('ventas')
                        ->whereNotNull('tipo_comprobante')
                        ->where('tipo_comprobante', '<>', '')
                        ->distinct()
                        ->orderBy('tipo_comprobante')
                        ->pluck('tipo_comprobante'),
                ];
            }
        );

        $locales = $filtrosCatalogo['locales'];
        $vendedores = $filtrosCatalogo['vendedores'];
        $tipos = $filtrosCatalogo['tipos'];

        $ultimaImportacion = DB::table(
            'ventas_importaciones'
        )
            ->orderByDesc('id')
            ->first();

        return view('ventas.index', compact(
            'ventas',
            'resumen',
            'porLocal',
            'porVendedor',
            'porProducto',
            'porDia',
            'locales',
            'vendedores',
            'tipos',
            'ultimaImportacion',
            'fechaMaxima',
            'desde',
            'hasta',
            'local',
            'vendedor',
            'tipo',
            'buscar',
            'todo'
        ));
    }

    /**
     * Inteligencia comercial de clientes.
     *
     * Se carga por AJAX para no hacer más pesada la apertura del módulo.
     * - historico: ignora desde/hasta y analiza todo el histórico disponible.
     * - periodo: respeta todos los filtros activos, incluidas las fechas.
     */
    public function clientesResumen(Request $request)
    {
        $this->asegurarTablas();

        $modo = $request->input('modo') === 'periodo'
            ? 'periodo'
            : 'historico';

        $desde = $modo === 'periodo'
            ? $request->input('desde')
            : null;

        $hasta = $modo === 'periodo'
            ? $request->input('hasta')
            : null;

        $local = trim((string) $request->input('local', ''));
        $vendedor = trim((string) $request->input('vendedor', ''));
        $tipo = trim((string) $request->input('tipo', ''));
        $buscar = trim((string) $request->input('buscar', ''));

        $base = DB::table('ventas as v');

        $this->aplicarFiltros(
            $base,
            $desde,
            $hasta,
            $local,
            $vendedor,
            $tipo,
            $buscar
        );

        $base->where(function ($q) {
            $q->where(function ($q2) {
                $q2->whereNotNull('v.cli_cod')
                    ->whereRaw("BTRIM(v.cli_cod) <> ''");
            })->orWhere(function ($q2) {
                $q2->whereNotNull('v.cliente')
                    ->whereRaw("BTRIM(v.cliente) <> ''");
            });
        });

        $fechaReferencia = (clone $base)->max('v.fecha');

        if (!$fechaReferencia) {
            return response()->json([
                'modo' => $modo,
                'resumen' => [
                    'clientes' => 0,
                    'recurrentes' => 0,
                    'frecuentes' => 0,
                    'venta_recurrente' => 0,
                    'porcentaje_venta_recurrente' => 0,
                    'valor_promedio_cliente' => 0,
                    'cliente_mas_frecuente' => null,
                    'cliente_mayor_valor' => null,
                    'fecha_referencia' => null,
                ],
                'clientes' => [],
                'recuperar' => [],
            ]);
        }

        $claveCliente = "COALESCE(
            NULLIF(BTRIM(v.cli_cod), ''),
            'N:' || UPPER(BTRIM(v.cliente))
        )";

        $clientes = (clone $base)
            ->selectRaw($claveCliente . ' as cliente_key')
            ->selectRaw(
                "MAX(NULLIF(BTRIM(v.cli_cod), '')) as cli_cod,
                 MAX(NULLIF(BTRIM(v.cliente), '')) as cliente,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.fecha END) as visitas,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN COALESCE(v.local, '') || '|' || COALESCE(v.comprobante, '') END) as tickets,
                 COALESCE(SUM(v.cantidad), 0) as unidades_netas,
                 COALESCE(SUM(v.p_venta), 0) as venta_neta,
                 MIN(CASE WHEN v.cantidad > 0 THEN v.fecha END) as primera_compra,
                 MAX(CASE WHEN v.cantidad > 0 THEN v.fecha END) as ultima_compra,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.local END) as locales,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.codigo END) as codigos"
            )
            ->groupBy(DB::raw($claveCliente))
            ->havingRaw(
                "COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.fecha END) > 0"
            )
            ->get()
            ->map(function ($item) use ($fechaReferencia) {
                $item->visitas = (int) $item->visitas;
                $item->tickets = (int) $item->tickets;
                $item->unidades_netas = (int) $item->unidades_netas;
                $item->venta_neta = (float) $item->venta_neta;
                $item->locales = (int) $item->locales;
                $item->codigos = (int) $item->codigos;

                $item->ticket_promedio =
                    $item->tickets > 0
                        ? $item->venta_neta / $item->tickets
                        : 0;

                $item->unidades_por_visita =
                    $item->visitas > 0
                        ? $item->unidades_netas / $item->visitas
                        : 0;

                $item->dias_sin_compra =
                    $item->ultima_compra
                        ? max(
                            0,
                            (int) floor(
                                (
                                    strtotime((string) $fechaReferencia)
                                    - strtotime((string) $item->ultima_compra)
                                ) / 86400
                            )
                        )
                        : null;

                return $item;
            })
            ->values();

        if ($clientes->isEmpty()) {
            return response()->json([
                'modo' => $modo,
                'resumen' => [
                    'clientes' => 0,
                    'recurrentes' => 0,
                    'frecuentes' => 0,
                    'venta_recurrente' => 0,
                    'porcentaje_venta_recurrente' => 0,
                    'valor_promedio_cliente' => 0,
                    'cliente_mas_frecuente' => null,
                    'cliente_mayor_valor' => null,
                    'fecha_referencia' => $fechaReferencia,
                ],
                'clientes' => [],
                'recuperar' => [],
            ]);
        }

        $ventasOrdenadas = $clientes
            ->pluck('venta_neta')
            ->map(function ($valor) {
                return (float) $valor;
            })
            ->sort()
            ->values();

        $indiceP80 = max(
            0,
            (int) floor(($ventasOrdenadas->count() - 1) * 0.80)
        );

        $umbralVip = (float) $ventasOrdenadas->get(
            $indiceP80,
            0
        );

        $clientes = $clientes
            ->map(function ($item) use ($umbralVip) {
                if (
                    $item->dias_sin_compra !== null
                    && $item->dias_sin_compra >= 30
                    && $item->visitas >= 2
                ) {
                    $item->segmento = 'A RECUPERAR';
                } elseif (
                    $item->visitas >= 2
                    && $item->venta_neta >= $umbralVip
                ) {
                    $item->segmento = 'VIP';
                } elseif ($item->visitas >= 4) {
                    $item->segmento = 'FRECUENTE';
                } elseif ($item->visitas >= 2) {
                    $item->segmento = 'RECURRENTE';
                } else {
                    $item->segmento = 'OCASIONAL';
                }

                return $item;
            })
            ->values();

        $clientesOrdenados = $clientes
            ->sort(function ($a, $b) {
                if ($a->visitas !== $b->visitas) {
                    return $b->visitas <=> $a->visitas;
                }

                if ($a->venta_neta !== $b->venta_neta) {
                    return $b->venta_neta <=> $a->venta_neta;
                }

                return strcmp(
                    (string) $a->cliente,
                    (string) $b->cliente
                );
            })
            ->values();

        $recurrentes = $clientes->filter(function ($item) {
            return $item->visitas >= 2;
        });

        $frecuentes = $clientes->filter(function ($item) {
            return $item->visitas >= 4;
        });

        $ventaTotalClientes = (float) $clientes->sum('venta_neta');
        $ventaRecurrente = (float) $recurrentes->sum('venta_neta');

        $clienteMasFrecuente = $clientesOrdenados->first();

        $clienteMayorValor = $clientes
            ->sortByDesc('venta_neta')
            ->values()
            ->first();

        $recuperar = $clientes
            ->filter(function ($item) {
                return $item->segmento === 'A RECUPERAR';
            })
            ->sortByDesc('venta_neta')
            ->take(10)
            ->values();

        $serializarCliente = function ($item) {
            return [
                'cli_cod' => $item->cli_cod,
                'cliente' => $item->cliente ?: 'SIN NOMBRE',
                'visitas' => (int) $item->visitas,
                'tickets' => (int) $item->tickets,
                'unidades_netas' => (int) $item->unidades_netas,
                'venta_neta' => (float) $item->venta_neta,
                'ticket_promedio' => (float) $item->ticket_promedio,
                'unidades_por_visita' =>
                    round((float) $item->unidades_por_visita, 1),
                'primera_compra' => $item->primera_compra
                    ? date(
                        'd/m/Y',
                        strtotime((string) $item->primera_compra)
                    )
                    : null,
                'ultima_compra' => $item->ultima_compra
                    ? date(
                        'd/m/Y',
                        strtotime((string) $item->ultima_compra)
                    )
                    : null,
                'dias_sin_compra' => $item->dias_sin_compra,
                'locales' => (int) $item->locales,
                'codigos' => (int) $item->codigos,
                'segmento' => $item->segmento,
            ];
        };

        return response()->json([
            'modo' => $modo,
            'resumen' => [
                'clientes' => $clientes->count(),
                'recurrentes' => $recurrentes->count(),
                'frecuentes' => $frecuentes->count(),
                'venta_recurrente' => $ventaRecurrente,
                'porcentaje_venta_recurrente' =>
                    $ventaTotalClientes != 0
                        ? round(
                            ($ventaRecurrente / $ventaTotalClientes) * 100,
                            1
                        )
                        : 0,
                'valor_promedio_cliente' =>
                    $clientes->count() > 0
                        ? $ventaTotalClientes / $clientes->count()
                        : 0,
                'cliente_mas_frecuente' =>
                    $clienteMasFrecuente
                        ? $serializarCliente($clienteMasFrecuente)
                        : null,
                'cliente_mayor_valor' =>
                    $clienteMayorValor
                        ? $serializarCliente($clienteMayorValor)
                        : null,
                'fecha_referencia' => date(
                    'd/m/Y',
                    strtotime((string) $fechaReferencia)
                ),
                'umbral_vip' => $umbralVip,
            ],
            'clientes' => $clientesOrdenados
                ->take(25)
                ->map($serializarCliente)
                ->values(),
            'recuperar' => $recuperar
                ->map($serializarCliente)
                ->values(),
        ]);
    }

    /**
     * Vista completa de inteligencia de clientes.
     */
    public function clientesIndex(Request $request)
    {
        $this->asegurarTablas();

        $desde = $request->input('desde');
        $hasta = $request->input('hasta');
        $local = trim((string) $request->input('local', ''));
        $buscar = trim((string) $request->input('buscar', ''));
        $segmento = strtoupper(
            trim((string) $request->input('segmento', ''))
        );

        $orden = trim(
            (string) $request->input('orden', 'frecuencia')
        );

        $direccion = strtolower(
            (string) $request->input('dir', 'desc')
        ) === 'asc'
            ? 'asc'
            : 'desc';

        $agregados = $this->construirClientesAgregados(
            $desde,
            $hasta,
            $local,
            $buscar
        );

        $fechaReferencia = DB::query()
            ->fromSub(clone $agregados, 'c')
            ->max('c.ultima_compra');

        $fechaReferencia = $fechaReferencia
            ?: DB::table('ventas')->max('fecha');

        $umbralVip = 0;

        if ($fechaReferencia) {
            $percentil = DB::query()
                ->fromSub(clone $agregados, 'c')
                ->selectRaw(
                    'percentile_cont(0.80) WITHIN GROUP '
                    . '(ORDER BY c.venta_neta) as valor'
                )
                ->value('valor');

            $umbralVip = (float) ($percentil ?? 0);
        }

        $stats = DB::query()
            ->fromSub(clone $agregados, 'c')
            ->selectRaw(
                "COUNT(*) as clientes,
                 SUM(CASE WHEN c.visitas >= 2 THEN 1 ELSE 0 END) as recurrentes,
                 SUM(CASE WHEN c.visitas >= 4 THEN 1 ELSE 0 END) as frecuentes,
                 SUM(
                    CASE
                        WHEN ?::date - c.ultima_compra >= 30
                             AND c.visitas >= 2
                        THEN 1 ELSE 0
                    END
                 ) as recuperar,
                 SUM(
                    CASE
                        WHEN ?::date - c.ultima_compra <= 29
                        THEN 1 ELSE 0
                    END
                 ) as activos_30,
                 SUM(CASE WHEN c.visitas = 1 THEN 1 ELSE 0 END) as una_compra,
                 COALESCE(SUM(c.venta_neta), 0) as venta_neta,
                 COALESCE(SUM(c.tickets), 0) as tickets,
                 COALESCE(SUM(c.unidades_netas), 0) as unidades"
            ,
                [
                    $fechaReferencia ?: date('Y-m-d'),
                    $fechaReferencia ?: date('Y-m-d'),
                ]
            )
            ->first();

        $stats->clientes = (int) ($stats->clientes ?? 0);
        $stats->recurrentes = (int) ($stats->recurrentes ?? 0);
        $stats->frecuentes = (int) ($stats->frecuentes ?? 0);
        $stats->recuperar = (int) ($stats->recuperar ?? 0);
        $stats->activos_30 = (int) ($stats->activos_30 ?? 0);
        $stats->una_compra = (int) ($stats->una_compra ?? 0);
        $stats->venta_neta = (float) ($stats->venta_neta ?? 0);
        $stats->tickets = (int) ($stats->tickets ?? 0);
        $stats->unidades = (int) ($stats->unidades ?? 0);

        $stats->tasa_recurrencia =
            $stats->clientes > 0
                ? round(
                    ($stats->recurrentes / $stats->clientes) * 100,
                    1
                )
                : 0;

        $stats->valor_promedio_cliente =
            $stats->clientes > 0
                ? $stats->venta_neta / $stats->clientes
                : 0;

        $stats->ticket_promedio =
            $stats->tickets > 0
                ? $stats->venta_neta / $stats->tickets
                : 0;

        $clientesQuery = DB::query()
            ->fromSub(clone $agregados, 'c')
            ->select('c.*')
            ->selectRaw(
                "CASE
                    WHEN c.tickets > 0
                    THEN c.venta_neta / c.tickets
                    ELSE 0
                 END as ticket_promedio,
                 CASE
                    WHEN c.visitas > 0
                    THEN c.unidades_netas::numeric / c.visitas
                    ELSE 0
                 END as unidades_por_visita,
                 CASE
                    WHEN c.visitas > 1
                    THEN (c.ultima_compra - c.primera_compra)::numeric
                        / (c.visitas - 1)
                    ELSE NULL
                 END as dias_promedio_entre_visitas,
                 ?::date - c.ultima_compra as dias_sin_compra",
                [$fechaReferencia ?: date('Y-m-d')]
            );

        switch ($segmento) {
            case 'VIP':
                $clientesQuery
                    ->where('c.visitas', '>=', 2)
                    ->where('c.venta_neta', '>=', $umbralVip);
                break;

            case 'FRECUENTE':
                $clientesQuery->where('c.visitas', '>=', 4);
                break;

            case 'RECURRENTE':
                $clientesQuery->where('c.visitas', '>=', 2);
                break;

            case 'A RECUPERAR':
                $clientesQuery
                    ->where('c.visitas', '>=', 2)
                    ->whereRaw(
                        '?::date - c.ultima_compra >= 30',
                        [$fechaReferencia ?: date('Y-m-d')]
                    );
                break;

            case 'OCASIONAL':
                $clientesQuery->where('c.visitas', '=', 1);
                break;

            default:
                $segmento = '';
                break;
        }

        $columnasOrden = [
            'frecuencia' => 'c.visitas',
            'valor' => 'c.venta_neta',
            'tickets' => 'c.tickets',
            'reciente' => 'c.ultima_compra',
            'nombre' => 'c.cliente',
            'ticket' => 'ticket_promedio',
        ];

        $columnaOrden =
            $columnasOrden[$orden]
            ?? $columnasOrden['frecuencia'];

        if (!isset($columnasOrden[$orden])) {
            $orden = 'frecuencia';
        }

        $clientes = $clientesQuery
            ->orderBy($columnaOrden, $direccion)
            ->orderByDesc('c.venta_neta')
            ->simplePaginate(50)
            ->appends($request->query());

        $clientes->getCollection()->transform(
            function ($item) use ($umbralVip, $stats) {
                $item->visitas = (int) $item->visitas;
                $item->tickets = (int) $item->tickets;
                $item->unidades_netas = (int) $item->unidades_netas;
                $item->venta_neta = (float) $item->venta_neta;
                $item->venta_lista = (float) $item->venta_lista;
                $item->descuento = (float) $item->descuento;
                $item->devoluciones = (float) $item->devoluciones;
                $item->ticket_promedio =
                    (float) $item->ticket_promedio;
                $item->unidades_por_visita =
                    (float) $item->unidades_por_visita;
                $item->dias_promedio_entre_visitas =
                    $item->dias_promedio_entre_visitas !== null
                        ? (float) $item->dias_promedio_entre_visitas
                        : null;
                $item->dias_sin_compra =
                    (int) $item->dias_sin_compra;

                if (
                    $item->dias_sin_compra >= 30
                    && $item->visitas >= 2
                ) {
                    $item->segmento = 'A RECUPERAR';
                } elseif (
                    $item->visitas >= 2
                    && $item->venta_neta >= $umbralVip
                ) {
                    $item->segmento = 'VIP';
                } elseif ($item->visitas >= 4) {
                    $item->segmento = 'FRECUENTE';
                } elseif ($item->visitas >= 2) {
                    $item->segmento = 'RECURRENTE';
                } else {
                    $item->segmento = 'OCASIONAL';
                }

                $item->participacion =
                    $stats->venta_neta != 0
                        ? round(
                            ($item->venta_neta
                                / $stats->venta_neta) * 100,
                            2
                        )
                        : 0;

                return $item;
            }
        );

        $topFrecuente = DB::query()
            ->fromSub(clone $agregados, 'c')
            ->orderByDesc('c.visitas')
            ->orderByDesc('c.venta_neta')
            ->first();

        $topValor = DB::query()
            ->fromSub(clone $agregados, 'c')
            ->orderByDesc('c.venta_neta')
            ->first();

        $locales = Cache::remember(
            'ventas:clientes:locales',
            900,
            function () {
                return DB::table('ventas')
                    ->whereNotNull('local')
                    ->where('local', '<>', '')
                    ->distinct()
                    ->orderBy('local')
                    ->pluck('local');
            }
        );

        return view('ventas.clientes', compact(
            'clientes',
            'stats',
            'topFrecuente',
            'topValor',
            'locales',
            'desde',
            'hasta',
            'local',
            'buscar',
            'segmento',
            'orden',
            'direccion',
            'fechaReferencia',
            'umbralVip'
        ));
    }

    /**
     * Perfil 360° del cliente: facturas, productos, locales y vendedores.
     */
    public function clienteDetalle(Request $request)
    {
        $this->asegurarTablas();

        $clienteKey = trim(
            (string) $request->input('cliente_key', '')
        );

        abort_if(
            $clienteKey === '',
            422,
            'Cliente no válido.'
        );

        $claveCliente = $this->expresionClaveCliente();

        $query = DB::table('ventas as v')
            ->whereRaw(
                $claveCliente . ' = ?',
                [$clienteKey]
            );

        $resumen = (clone $query)
            ->selectRaw(
                "MAX(NULLIF(BTRIM(v.cli_cod), '')) as cli_cod,
                 MAX(NULLIF(BTRIM(v.cliente), '')) as cliente,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.fecha END) as visitas,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN COALESCE(v.local, '') || '|' || COALESCE(v.comprobante, '') END) as tickets,
                 COALESCE(SUM(v.cantidad), 0) as unidades_netas,
                 COALESCE(SUM(v.p_lista), 0) as venta_lista,
                 COALESCE(SUM(v.descuento), 0) as descuento,
                 COALESCE(SUM(v.p_venta), 0) as venta_neta,
                 COALESCE(SUM(CASE WHEN v.p_venta < 0 THEN ABS(v.p_venta) ELSE 0 END), 0) as devoluciones,
                 MIN(CASE WHEN v.cantidad > 0 THEN v.fecha END) as primera_compra,
                 MAX(CASE WHEN v.cantidad > 0 THEN v.fecha END) as ultima_compra,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.local END) as locales,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.vendedor END) as vendedores,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.codigo END) as productos"
            )
            ->first();

        abort_unless(
            $resumen && $resumen->cliente,
            404,
            'Cliente no encontrado.'
        );

        $resumen->visitas = (int) $resumen->visitas;
        $resumen->tickets = (int) $resumen->tickets;
        $resumen->unidades_netas = (int) $resumen->unidades_netas;
        $resumen->venta_lista = (float) $resumen->venta_lista;
        $resumen->descuento = (float) $resumen->descuento;
        $resumen->venta_neta = (float) $resumen->venta_neta;
        $resumen->devoluciones = (float) $resumen->devoluciones;
        $resumen->locales = (int) $resumen->locales;
        $resumen->vendedores = (int) $resumen->vendedores;
        $resumen->productos = (int) $resumen->productos;

        $resumen->ticket_promedio =
            $resumen->tickets > 0
                ? $resumen->venta_neta / $resumen->tickets
                : 0;

        $resumen->descuento_porcentaje =
            $resumen->venta_lista != 0
                ? round(
                    ($resumen->descuento
                        / $resumen->venta_lista) * 100,
                    1
                )
                : 0;

        $resumen->dias_promedio_entre_visitas =
            $resumen->visitas > 1
            && $resumen->primera_compra
            && $resumen->ultima_compra
                ? round(
                    (
                        strtotime($resumen->ultima_compra)
                        - strtotime($resumen->primera_compra)
                    ) / 86400 / ($resumen->visitas - 1),
                    1
                )
                : null;

        $comprobantes = (clone $query)
            ->select(
                'v.fecha',
                'v.comprobante',
                'v.tipo_comprobante',
                'v.local'
            )
            ->selectRaw(
                "MAX(v.vendedor) as vendedor,
                 COALESCE(SUM(v.cantidad), 0) as unidades,
                 COALESCE(SUM(v.p_lista), 0) as venta_lista,
                 COALESCE(SUM(v.descuento), 0) as descuento,
                 COALESCE(SUM(v.p_venta), 0) as venta_neta,
                 COUNT(DISTINCT v.codigo) as productos"
            )
            ->groupBy(
                'v.fecha',
                'v.comprobante',
                'v.tipo_comprobante',
                'v.local'
            )
            ->orderByDesc('v.fecha')
            ->orderByDesc('v.comprobante')
            ->limit(100)
            ->get();

        $productosQuery = clone $query;

        if (Schema::hasTable('maestro_codigos')) {
            $productosQuery->leftJoin(
                'maestro_codigos as mc',
                'mc.cod_articulo',
                '=',
                'v.codigo'
            );
        }

        $productos = $productosQuery
            ->select(
                'v.codigo',
                DB::raw('MAX(v.descripcion) as descripcion')
            )
            ->when(
                Schema::hasTable('maestro_codigos'),
                function ($q) {
                    $q->addSelect(
                        DB::raw('MAX(mc.grupo) as grupo'),
                        DB::raw('MAX(mc.temporada) as temporada'),
                        DB::raw('MAX(mc.linea) as linea')
                    );
                }
            )
            ->selectRaw(
                "COALESCE(SUM(v.cantidad), 0) as unidades,
                 COALESCE(SUM(v.p_venta), 0) as venta_neta,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN COALESCE(v.local, '') || '|' || COALESCE(v.comprobante, '') END) as tickets,
                 MAX(v.fecha) as ultima_compra"
            )
            ->groupBy('v.codigo')
            ->orderByDesc('venta_neta')
            ->limit(30)
            ->get();

        $locales = (clone $query)
            ->whereNotNull('v.local')
            ->where('v.local', '<>', '')
            ->select('v.local')
            ->selectRaw(
                "COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.fecha END) as visitas,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.comprobante END) as tickets,
                 COALESCE(SUM(v.cantidad), 0) as unidades,
                 COALESCE(SUM(v.p_venta), 0) as venta_neta,
                 MAX(v.fecha) as ultima_compra"
            )
            ->groupBy('v.local')
            ->orderByDesc('venta_neta')
            ->get();

        $vendedores = (clone $query)
            ->whereNotNull('v.vendedor')
            ->where('v.vendedor', '<>', '')
            ->select(
                'v.local',
                'v.vendedor'
            )
            ->selectRaw(
                "COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.comprobante END) as tickets,
                 COALESCE(SUM(v.cantidad), 0) as unidades,
                 COALESCE(SUM(v.p_venta), 0) as venta_neta,
                 MAX(v.fecha) as ultima_venta"
            )
            ->groupBy(
                'v.local',
                'v.vendedor'
            )
            ->orderByDesc('venta_neta')
            ->limit(20)
            ->get();

        $porMes = (clone $query)
            ->selectRaw(
                "TO_CHAR(v.fecha, 'YYYY-MM') as periodo,
                 COALESCE(SUM(v.p_venta), 0) as venta_neta,
                 COALESCE(SUM(v.cantidad), 0) as unidades,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN COALESCE(v.local, '') || '|' || COALESCE(v.comprobante, '') END) as tickets"
            )
            ->groupBy(
                DB::raw("TO_CHAR(v.fecha, 'YYYY-MM')")
            )
            ->orderBy('periodo')
            ->get();

        return response()->json([
            'resumen' => [
                'cli_cod' => $resumen->cli_cod,
                'cliente' => $resumen->cliente,
                'visitas' => $resumen->visitas,
                'tickets' => $resumen->tickets,
                'unidades_netas' => $resumen->unidades_netas,
                'venta_lista' => $resumen->venta_lista,
                'descuento' => $resumen->descuento,
                'descuento_porcentaje' =>
                    $resumen->descuento_porcentaje,
                'venta_neta' => $resumen->venta_neta,
                'devoluciones' => $resumen->devoluciones,
                'ticket_promedio' => $resumen->ticket_promedio,
                'primera_compra' => $resumen->primera_compra
                    ? date(
                        'd/m/Y',
                        strtotime($resumen->primera_compra)
                    )
                    : null,
                'ultima_compra' => $resumen->ultima_compra
                    ? date(
                        'd/m/Y',
                        strtotime($resumen->ultima_compra)
                    )
                    : null,
                'dias_promedio_entre_visitas' =>
                    $resumen->dias_promedio_entre_visitas,
                'locales' => $resumen->locales,
                'vendedores' => $resumen->vendedores,
                'productos' => $resumen->productos,
            ],
            'comprobantes' => $comprobantes->map(function ($item) {
                return [
                    'fecha' => date(
                        'd/m/Y',
                        strtotime($item->fecha)
                    ),
                    'comprobante' => $item->comprobante,
                    'tipo' => $item->tipo_comprobante,
                    'local' => $item->local,
                    'vendedor' => $item->vendedor,
                    'unidades' => (int) $item->unidades,
                    'productos' => (int) $item->productos,
                    'venta_lista' => (float) $item->venta_lista,
                    'descuento' => (float) $item->descuento,
                    'venta_neta' => (float) $item->venta_neta,
                ];
            })->values(),
            'productos' => $productos->map(function ($item) {
                return [
                    'codigo' => $item->codigo,
                    'descripcion' => $item->descripcion,
                    'grupo' => $item->grupo ?? null,
                    'temporada' => $item->temporada ?? null,
                    'linea' => $item->linea ?? null,
                    'unidades' => (int) $item->unidades,
                    'tickets' => (int) $item->tickets,
                    'venta_neta' => (float) $item->venta_neta,
                    'ultima_compra' => $item->ultima_compra
                        ? date(
                            'd/m/Y',
                            strtotime($item->ultima_compra)
                        )
                        : null,
                ];
            })->values(),
            'locales' => $locales->map(function ($item) {
                return [
                    'local' => $item->local,
                    'visitas' => (int) $item->visitas,
                    'tickets' => (int) $item->tickets,
                    'unidades' => (int) $item->unidades,
                    'venta_neta' => (float) $item->venta_neta,
                    'ultima_compra' => $item->ultima_compra
                        ? date(
                            'd/m/Y',
                            strtotime($item->ultima_compra)
                        )
                        : null,
                ];
            })->values(),
            'vendedores' => $vendedores->map(function ($item) {
                return [
                    'local' => $item->local,
                    'vendedor' => $item->vendedor,
                    'tickets' => (int) $item->tickets,
                    'unidades' => (int) $item->unidades,
                    'venta_neta' => (float) $item->venta_neta,
                    'ultima_venta' => $item->ultima_venta
                        ? date(
                            'd/m/Y',
                            strtotime($item->ultima_venta)
                        )
                        : null,
                ];
            })->values(),
            'por_mes' => $porMes->map(function ($item) {
                return [
                    'periodo' => $item->periodo,
                    'venta_neta' => (float) $item->venta_neta,
                    'unidades' => (int) $item->unidades,
                    'tickets' => (int) $item->tickets,
                ];
            })->values(),
        ]);
    }

    /**
     * Ranking completo de vendedores, cargado sólo cuando se solicita.
     */
    public function vendedoresTodos(Request $request)
    {
        $this->asegurarTablas();

        $desde = $request->input('desde');
        $hasta = $request->input('hasta');
        $local = trim((string) $request->input('local', ''));
        $vendedor = trim((string) $request->input('vendedor', ''));
        $tipo = trim((string) $request->input('tipo', ''));
        $buscar = trim((string) $request->input('buscar', ''));

        $query = DB::table('ventas as v');

        $this->aplicarFiltros(
            $query,
            $desde,
            $hasta,
            $local,
            $vendedor,
            $tipo,
            $buscar
        );

        $ranking = $query
            ->whereNotNull('v.vendedor')
            ->where('v.vendedor', '<>', '')
            ->whereNotNull('v.local')
            ->where('v.local', '<>', '')
            ->select(
                'v.local',
                'v.vendedor'
            )
            ->selectRaw(
                "COALESCE(SUM(v.cantidad), 0) as unidades_netas,
                 COALESCE(SUM(v.p_venta), 0) as venta_neta,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.comprobante END) as tickets"
            )
            ->groupBy(
                'v.local',
                'v.vendedor'
            )
            ->orderByDesc('venta_neta')
            ->get();

        return response()->json([
            'total' => $ranking->count(),
            'vendedores' => $ranking->map(function ($item) {
                return [
                    'local' => $item->local,
                    'vendedor' => $item->vendedor,
                    'unidades_netas' => (int) $item->unidades_netas,
                    'tickets' => (int) $item->tickets,
                    'venta_neta' => (float) $item->venta_neta,
                ];
            })->values(),
        ]);
    }

    /**
     * Detalle comercial de un producto desde el dashboard de Ventas.
     *
     * Respeta los mismos filtros activos de fecha/local/vendedor/tipo/búsqueda
     * y agrupa el código por sucursal + vendedor.
     */
    public function detalleProducto(
        Request $request,
        string $codigo
    ) {
        $this->asegurarTablas();

        $codigo = trim($codigo);

        abort_if(
            $codigo === '',
            404,
            'Código no válido.'
        );

        $desde = $request->input('desde');
        $hasta = $request->input('hasta');
        $local = trim((string) $request->input('local', ''));
        $vendedor = trim((string) $request->input('vendedor', ''));
        $tipo = trim((string) $request->input('tipo', ''));
        $buscar = trim((string) $request->input('buscar', ''));

        $query = DB::table('ventas as v')
            ->where('v.codigo', $codigo);

        $this->aplicarFiltros(
            $query,
            $desde,
            $hasta,
            $local,
            $vendedor,
            $tipo,
            $buscar
        );

        $producto = (clone $query)
            ->select(
                'v.codigo',
                DB::raw("MAX(v.descripcion) as descripcion")
            )
            ->selectRaw(
                "COALESCE(SUM(v.cantidad), 0) as cantidad,
                 COALESCE(SUM(v.p_lista), 0) as p_lista,
                 COALESCE(SUM(v.descuento), 0) as descuento,
                 COALESCE(SUM(v.p_venta), 0) as p_venta,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN COALESCE(v.local, '') || '|' || COALESCE(v.comprobante, '') END) as tickets"
            )
            ->groupBy('v.codigo')
            ->first();

        abort_unless(
            $producto,
            404,
            'No hay ventas de este producto con los filtros actuales.'
        );

        $detalle = (clone $query)
            ->select(
                'v.local',
                'v.vendedor'
            )
            ->selectRaw(
                "COALESCE(SUM(v.cantidad), 0) as cantidad,
                 COALESCE(SUM(v.p_lista), 0) as p_lista,
                 COALESCE(SUM(v.descuento), 0) as descuento,
                 COALESCE(SUM(v.p_venta), 0) as p_venta,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.comprobante END) as tickets,
                 COUNT(*) as lineas"
            )
            ->groupBy(
                'v.local',
                'v.vendedor'
            )
            ->orderByDesc('p_venta')
            ->orderBy('v.local')
            ->orderBy('v.vendedor')
            ->get();

        $comprobantes = (clone $query)
            ->select(
                'v.local',
                'v.vendedor',
                'v.fecha',
                'v.comprobante',
                'v.cantidad',
                'v.p_lista',
                'v.descuento',
                'v.p_venta'
            )
            ->orderByDesc('v.fecha')
            ->orderBy('v.local')
            ->orderBy('v.vendedor')
            ->orderBy('v.comprobante')
            ->get();

        $maestro = null;

        if (Schema::hasTable('maestro_codigos')) {
            $maestro = DB::table('maestro_codigos')
                ->where('cod_articulo', $codigo)
                ->select(
                    'grupo',
                    'grupo_plan',
                    'temporada',
                    'linea'
                )
                ->first();
        }

        return response()->json([
            'producto' => [
                'codigo' => $producto->codigo,
                'descripcion' => $producto->descripcion,
                'cantidad' => (int) $producto->cantidad,
                'p_lista' => (float) $producto->p_lista,
                'descuento' => (float) $producto->descuento,
                'p_venta' => (float) $producto->p_venta,
                'tickets' => (int) $producto->tickets,
                'grupo' => $maestro->grupo ?? null,
                'grupo_plan' => $maestro->grupo_plan ?? null,
                'temporada' => $maestro->temporada ?? null,
                'linea' => $maestro->linea ?? null,
            ],
            'detalle' => $detalle->map(function ($item) {
                return [
                    'local' => $item->local ?: '-',
                    'vendedor' => $item->vendedor ?: '-',
                    'cantidad' => (int) $item->cantidad,
                    'p_lista' => (float) $item->p_lista,
                    'descuento' => (float) $item->descuento,
                    'p_venta' => (float) $item->p_venta,
                    'tickets' => (int) $item->tickets,
                    'lineas' => (int) $item->lineas,
                ];
            })->values(),
            'comprobantes' => $comprobantes->map(function ($item) {
                return [
                    'local' => $item->local ?: '-',
                    'vendedor' => $item->vendedor ?: '-',
                    'fecha' => $item->fecha
                        ? date('d/m/Y', strtotime($item->fecha))
                        : '-',
                    'comprobante' => $item->comprobante,
                    'cantidad' => (int) $item->cantidad,
                    'p_lista' => (float) $item->p_lista,
                    'descuento' => (float) $item->descuento,
                    'p_venta' => (float) $item->p_venta,
                ];
            })->values(),
        ]);
    }

    public function importarForm()
    {
        $this->asegurarTablas();

        $ultimasImportaciones = DB::table(
            'ventas_importaciones'
        )
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view(
            'ventas.importar',
            compact('ultimasImportaciones')
        );
    }

    public function omitidasImportacion(int $id)
    {
        $this->asegurarTablas();

        $importacion = DB::table('ventas_importaciones')
            ->where('id', $id)
            ->first();

        abort_unless(
            $importacion,
            404,
            'Importación no encontrada.'
        );

        $omitidas = DB::table('ventas_importacion_omitidas')
            ->where('importacion_id', $id)
            ->orderByRaw(
                "CASE WHEN tipo = 'INVALIDA' THEN 0 ELSE 1 END"
            )
            ->orderBy('fila')
            ->orderBy('id')
            ->get();

        $sinClasificar = max(
            0,
            (int) $importacion->filas_omitidas
                - (int) ($importacion->filas_duplicadas ?? 0)
                - (int) ($importacion->filas_invalidas ?? 0)
        );

        return response()->json([
            'importacion' => [
                'id' => (int) $importacion->id,
                'archivo' => $importacion->nombre_archivo,
                'procesadas' => (int) $importacion->filas_procesadas,
                'insertadas' => (int) $importacion->filas_insertadas,
                'omitidas' => (int) $importacion->filas_omitidas,
                'duplicadas' => (int) ($importacion->filas_duplicadas ?? 0),
                'invalidas' => (int) ($importacion->filas_invalidas ?? 0),
                'sin_clasificar' => $sinClasificar,
            ],
            'omitidas' => $omitidas->map(function ($item) {
                return [
                    'fila' => $item->fila !== null
                        ? (int) $item->fila
                        : null,
                    'tipo' => $item->tipo,
                    'codigo' => $item->codigo,
                    'comprobante' => $item->comprobante,
                    'local' => $item->local,
                    'motivo' => $item->motivo,
                ];
            })->values(),
        ]);
    }

    public function importar(Request $request)
    {
        $this->asegurarTablas();

        $request->validate([
            'archivo' =>
                'required|file|mimes:xlsx,csv,txt|max:204800',
            'import_token' =>
                'required|string|max:100',
        ]);

        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('max_input_time', '-1');
        @ini_set('memory_limit', '1024M');
        @ignore_user_abort(true);

        DB::disableQueryLog();

        $archivo = $request->file('archivo');

        $token = preg_replace(
            '/[^A-Za-z0-9_-]/',
            '',
            (string) $request->input('import_token')
        );

        if ($token === '') {
            $token = Str::random(40);
        }

        $importacionId = DB::table(
            'ventas_importaciones'
        )->insertGetId([
            'nombre_archivo' =>
                $archivo->getClientOriginalName(),
            'archivo_hash' => hash_file(
                'sha256',
                $archivo->getRealPath()
            ),
            'usuario_id' =>
                auth()->id(),
            'estado' => 'PROCESANDO',
            'mensaje' => 'Importación iniciada.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $importador = new VentasImport(
            $importacionId,
            $token
        );

        try {
            $extension = strtolower(
                $archivo->getClientOriginalExtension()
            );

            if ($extension === 'xlsx') {
                $importador->importarXlsx(
                    $archivo->getRealPath()
                );
            } else {
                $importador->importarCsv(
                    $archivo->getRealPath()
                );
            }

            $resumen = $importador->resumen();

            /*
             * No bloquear la respuesta con ANALYZE en una carga diaria chica.
             * PostgreSQL/autovacuum puede actualizar estadísticas luego.
             * Sólo forzamos ANALYZE cuando la carga fue realmente grande.
             */
            if ((int) ($resumen['insertadas'] ?? 0) >= 10000) {
                DB::statement('ANALYZE ventas');
            }

            Cache::forget('ventas:fecha_maxima');
            Cache::forget('ventas:filtros_catalogo');

            return response()->json([
                'success' => true,
                'message' =>
                    'Ventas importadas correctamente.',
                'resumen' => $resumen,
                'redirect' => route('ventas.index', [
                    'desde' => $resumen['fecha_desde'],
                    'hasta' => $resumen['fecha_hasta'],
                ]),
            ]);
        } catch (\Throwable $e) {
            $importador->marcarError(
                $e->getMessage()
            );

            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function progreso($token)
    {
        $token = preg_replace(
            '/[^A-Za-z0-9_-]/',
            '',
            (string) $token
        );

        return response()->json(
            Cache::get(
                'ventas_import_' . $token,
                [
                    'estado' => 'ESPERANDO',
                    'mensaje' =>
                        'Esperando inicio de importación...',
                    'procesadas' => 0,
                    'insertadas' => 0,
                    'omitidas' => 0,
                    'duplicadas' => 0,
                    'invalidas' => 0,
                    'total' => null,
                    'porcentaje' => null,
                ]
            )
        );
    }

    private function expresionClaveCliente(): string
    {
        return "COALESCE(
            NULLIF(BTRIM(v.cli_cod), ''),
            'N:' || UPPER(BTRIM(v.cliente))
        )";
    }

    private function construirClientesAgregados(
        $desde,
        $hasta,
        string $local,
        string $buscar
    ) {
        $query = DB::table('ventas as v');

        if ($desde) {
            $query->where('v.fecha', '>=', $desde);
        }

        if ($hasta) {
            $query->where('v.fecha', '<=', $hasta);
        }

        if ($local !== '') {
            $query->where('v.local', $local);
        }

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where(
                    'v.cliente',
                    'ilike',
                    '%' . $buscar . '%'
                )->orWhere(
                    'v.cli_cod',
                    'ilike',
                    '%' . $buscar . '%'
                );
            });
        }

        $query->where(function ($q) {
            $q->where(function ($q2) {
                $q2->whereNotNull('v.cli_cod')
                    ->whereRaw("BTRIM(v.cli_cod) <> ''");
            })->orWhere(function ($q2) {
                $q2->whereNotNull('v.cliente')
                    ->whereRaw("BTRIM(v.cliente) <> ''");
            });
        });

        $claveCliente = $this->expresionClaveCliente();

        return $query
            ->selectRaw($claveCliente . ' as cliente_key')
            ->selectRaw(
                "MAX(NULLIF(BTRIM(v.cli_cod), '')) as cli_cod,
                 MAX(NULLIF(BTRIM(v.cliente), '')) as cliente,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.fecha END) as visitas,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN COALESCE(v.local, '') || '|' || COALESCE(v.comprobante, '') END) as tickets,
                 COALESCE(SUM(v.cantidad), 0) as unidades_netas,
                 COALESCE(SUM(v.p_lista), 0) as venta_lista,
                 COALESCE(SUM(v.descuento), 0) as descuento,
                 COALESCE(SUM(v.p_venta), 0) as venta_neta,
                 COALESCE(SUM(CASE WHEN v.p_venta < 0 THEN ABS(v.p_venta) ELSE 0 END), 0) as devoluciones,
                 MIN(CASE WHEN v.cantidad > 0 THEN v.fecha END) as primera_compra,
                 MAX(CASE WHEN v.cantidad > 0 THEN v.fecha END) as ultima_compra,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.local END) as locales,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.vendedor END) as vendedores,
                 COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.codigo END) as productos"
            )
            ->groupBy(DB::raw($claveCliente))
            ->havingRaw(
                "COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.fecha END) > 0"
            );
    }

    private function aplicarFiltros(
        $query,
        $desde,
        $hasta,
        string $local,
        string $vendedor,
        string $tipo,
        string $buscar
    ): void {
        /*
         * fecha ya es DATE en PostgreSQL. Comparar directamente permite usar
         * los índices; whereDate() agrega una función/cast innecesario.
         */
        if ($desde) {
            $query->where(
                'v.fecha',
                '>=',
                $desde
            );
        }

        if ($hasta) {
            $query->where(
                'v.fecha',
                '<=',
                $hasta
            );
        }

        if ($local !== '') {
            $query->where(
                'v.local',
                $local
            );
        }

        if ($vendedor !== '') {
            $query->where(
                'v.vendedor',
                $vendedor
            );
        }

        if ($tipo !== '') {
            $query->where(
                'v.tipo_comprobante',
                $tipo
            );
        }

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where(
                    'v.codigo',
                    'ilike',
                    '%' . $buscar . '%'
                )
                    ->orWhere(
                        'v.descripcion',
                        'ilike',
                        '%' . $buscar . '%'
                    )
                    ->orWhere(
                        'v.cliente',
                        'ilike',
                        '%' . $buscar . '%'
                    )
                    ->orWhere(
                        'v.comprobante',
                        'ilike',
                        '%' . $buscar . '%'
                    );
            });
        }
    }

    private function asegurarTablas(): void
    {
        abort_unless(
            Schema::hasTable('ventas')
                && Schema::hasTable('ventas_importaciones')
                && Schema::hasTable('ventas_importacion_omitidas')
                && Schema::hasColumn(
                    'ventas_importaciones',
                    'filas_duplicadas'
                )
                && Schema::hasColumn(
                    'ventas_importaciones',
                    'filas_invalidas'
                ),
            503,
            'Falta ejecutar php artisan migrate para completar el módulo de ventas.'
        );
    }
}
