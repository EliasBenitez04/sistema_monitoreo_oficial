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

    private function aplicarFiltros(
        $query,
        $desde,
        $hasta,
        string $local,
        string $vendedor,
        string $tipo,
        string $buscar
    ): void {
        if ($desde) {
            $query->whereDate(
                'v.fecha',
                '>=',
                $desde
            );
        }

        if ($hasta) {
            $query->whereDate(
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
