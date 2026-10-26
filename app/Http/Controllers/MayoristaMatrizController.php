<?php

namespace App\Http\Controllers;

use App\Support\MayoristaConciliacion as Conciliador;
use App\Support\MayoristaCruceDocumento;
use App\Support\MayoristaOrigenRemision as OrigenRemision;
use App\Support\MayoristaTopClientes;
use App\Support\MayoristaExportacionVentas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Conciliación exclusiva de ventas registradas en COMERCIAL MATRIZ.
 * No mezcla ventas de otros locales ni considera automáticamente
 * una remisión como mercadería físicamente separada o facturada.
 */
class MayoristaMatrizController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:pedido_compras index');
    }

    private function fechas(Request $request): array
    {
        $ultima = VentaMayoristaController::ventas()->max('v.fecha');
        $base = $ultima ? Carbon::parse($ultima) : Carbon::today();
        $mesAnterior = (string) $request->query('mes', '');
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mesAnterior)) {
            $base = Carbon::parse($mesAnterior.'-01');
            $finDefecto = $base->format('Y-m') === Carbon::today()->format('Y-m')
                ? Carbon::today()->format('Y-m-d')
                : $base->copy()->endOfMonth()->format('Y-m-d');
        } else {
            $finDefecto = $base->format('Y-m-d');
        }
        $inicio = $request->query('desde', $base->copy()->startOfMonth()->format('Y-m-d'));
        $fin = $request->query('hasta', $finDefecto);
        $request->merge(['desde' => $inicio, 'hasta' => $fin]);
        $request->validate(['desde' => 'required|date_format:Y-m-d', 'hasta' => 'required|date_format:Y-m-d|after_or_equal:desde']);
        return [$inicio, $fin];
    }

    private function facturasEntre(string $desde, string $hasta)
    {
        // El filtro admite períodos que crucen meses; no omitir facturas
        // silenciosamente por consultar solo el mes inicial.
        $inicio = Carbon::parse($desde)->startOfMonth();
        $fin = Carbon::parse($hasta)->startOfMonth();
        $facturas = collect();
        while ($inicio->lte($fin)) {
            $facturas = $facturas->concat(
                VentaMayoristaController::facturas($inicio->format('Y-m'))
            );
            $inicio->addMonth();
        }
        return $facturas->filter(function ($factura) use ($desde, $hasta) {
            return $factura->fecha >= $desde && $factura->fecha <= $hasta;
        })->sortByDesc('fecha')->values();
    }

    private function vinculos($claves)
    {
        return $claves->isEmpty() ? collect() : DB::table('mayorista_vinculos')
            ->whereIn('factura_clave', $claves->all())
            ->select('factura_clave')->selectRaw('SUM(cantidad) as prendas')
            ->groupBy('factura_clave')->pluck('prendas', 'factura_clave');
    }


    private function sugerirConciliacion($facturas, string $hasta): void
    {
        foreach ($facturas as $f) {
            $f->sugeridas = 0;
            $f->sin_coincidencia = (int) $f->por_asignar;
        }
        if ($facturas->isEmpty()) return;

        $porClave = $facturas->keyBy('clave');
        $confirmadas = DB::table('mayorista_vinculos')
            ->whereIn('factura_clave', $porClave->keys()->all())
            ->select('factura_clave', 'codigo')
            ->selectRaw('SUM(cantidad) as cantidad')
            ->groupBy('factura_clave', 'codigo')->get();
        $confirmado = [];
        foreach ($confirmadas as $v) {
            $k = $v->factura_clave.'|'.Conciliador::codigo($v->codigo);
            $confirmado[$k] = ($confirmado[$k] ?? 0) + (int) $v->cantidad;
        }

        $uso = DB::table('mayorista_vinculos')
            ->select('remision_id')->selectRaw('SUM(cantidad) as cantidad')
            ->groupBy('remision_id');
        $remisiones = VentaMayoristaController::entradas()
            ->whereRaw(OrigenRemision::expresionDestinoMatriz('r'))
            ->where('r.fecha_remision', '<=', $hasta)
            ->whereNotNull('r.fecha_recepcion')
            ->leftJoinSub($uso, 'u', 'u.remision_id', '=', 'r.id')
            ->select('r.id', 'r.codigo', 'r.fecha_remision', 'r.fecha_recepcion', 'r.cantidad')
            ->selectRaw('COALESCE(u.cantidad,0) as usadas')
            ->orderBy('r.fecha_recepcion')->orderBy('r.id')->get();

        $disponibles = [];
        foreach ($remisiones as $r) {
            $codigo = Conciliador::codigo($r->codigo);
            $r->saldo = max(0, (int) $r->cantidad - (int) $r->usadas);
            if ($codigo !== '' && $r->saldo > 0) $disponibles[$codigo][] = $r;
        }

        $ventas = VentaMayoristaController::ventas()
            ->whereBetween('v.fecha', [$facturas->min('fecha'), $hasta])
            ->where('v.cantidad', '>', 0)
            ->select('v.fecha', 'v.comprobante', 'v.cli_cod', 'v.cliente', 'v.codigo')
            ->selectRaw('SUM(v.cantidad) as cantidad')
            ->groupBy('v.fecha', 'v.comprobante', 'v.cli_cod', 'v.cliente', 'v.codigo')
            ->orderBy('v.fecha')->orderBy('v.comprobante')->get();

        foreach ($ventas as $v) {
            $clave = Conciliador::claveFactura($v->fecha, $v->comprobante, $v->cli_cod, $v->cliente);
            $factura = $porClave->get($clave);
            if (!$factura) continue;
            $codigo = Conciliador::codigo($v->codigo);
            $faltante = max(0, (int) $v->cantidad - ($confirmado[$clave.'|'.$codigo] ?? 0));
            foreach ($disponibles[$codigo] ?? [] as $r) {
                if (!$faltante) break;
                if (!$r->saldo || $r->fecha_remision > $v->fecha
                    || substr((string) $r->fecha_recepcion, 0, 10) > $v->fecha) continue;
                $n = min($faltante, $r->saldo);
                $r->saldo -= $n;
                $faltante -= $n;
                $factura->sugeridas += $n;
            }
        }
        foreach ($facturas as $f) {
            $f->sugeridas = min($f->sugeridas, $f->por_asignar);
            $f->sin_coincidencia = max(0, $f->por_asignar - $f->sugeridas);
        }
    }

    public function index(Request $request)
    {
        if (!Schema::hasTable('ventas') || !Schema::hasTable('ot_logistica_remisiones')) {
            return view('ventas.mayoristas_matriz', ['sinFuentes' => true]);
        }

        list($desde, $hasta) = $this->fechas($request);
        if ($request->query('exportar') === 'ventas') {
            return $this->exportarVentas($desde, $hasta);
        }
        if ($request->query('exportar') === 'historico') {
            return $this->exportarVentas(null, null);
        }
        if ($request->query('exportar') === 'auditoria') {
            return $this->exportarVentasAuditoria($desde, $hasta);
        }
        $facturas = $this->facturasEntre($desde, $hasta);
        // Ranking por comprobante agrupado, NO por líneas de venta.
        // Se calcula antes del filtro visual de cliente, para mostrar
        // los principales clientes de todo el período consultado.
        $topClientes = MayoristaTopClientes::ranking($facturas);
        $vinculadas = $this->vinculos($facturas->pluck('clave'));

        $facturas->each(function ($f) use ($vinculadas) {
            $f->asignadas = (int) ($vinculadas[$f->clave] ?? 0);
            $f->por_asignar = max(0, (int) $f->brutas - $f->asignadas);
            $f->estado_cruce = (int) $f->brutas === 0 ? 'NOTA DE CRÉDITO'
                : ($f->por_asignar === 0 ? 'ASIGNADA' : ($f->asignadas > 0 ? 'PARCIAL' : 'SIN ASIGNAR'));
        });

        $this->sugerirConciliacion($facturas, $hasta);

        $resumen = VentaMayoristaController::ventas()
            ->whereBetween('v.fecha', [$desde, $hasta])
            ->selectRaw('COUNT(*) as lineas, COUNT(DISTINCT v.comprobante) as comprobantes')
            ->selectRaw('COALESCE(SUM(CASE WHEN v.cantidad>0 THEN v.cantidad ELSE 0 END),0) as brutas')
            ->selectRaw('COALESCE(SUM(CASE WHEN v.cantidad<0 THEN -v.cantidad ELSE 0 END),0) as notas_credito')
            ->selectRaw('COALESCE(SUM(v.cantidad),0) as unidades_netas')
            ->selectRaw('COALESCE(SUM(v.p_lista),0) as importe_lista')
            ->selectRaw('COALESCE(SUM(v.descuento),0) as descuentos')
            ->selectRaw('COALESCE(SUM(v.p_venta),0) as importe_neto')->first();

        $resumen->asignadas = (int) $facturas->sum('asignadas');
        $resumen->pendientes = (int) $facturas->sum('por_asignar');

        // No truncar datos: paginación real con totales calculados SIN paginar.
        $ventasDetalle = VentaMayoristaController::ventas()
            ->whereBetween('v.fecha', [$desde, $hasta])
            ->select('v.id','v.fecha','v.comprobante','v.tipo_comprobante',
                'v.cli_cod','v.cliente','v.vendedor','v.codigo','v.descripcion',
                'v.cantidad','v.p_lista','v.descuento','v.p_venta')
            ->orderBy('v.fecha', 'desc')->orderBy('v.comprobante','desc')
            ->orderBy('v.codigo')->orderBy('v.id')
            ->paginate(100)->appends($request->only(['desde','hasta']));

        $porCodigo = VentaMayoristaController::ventas()
            ->whereBetween('v.fecha', [$desde, $hasta])
            ->selectRaw('UPPER(BTRIM(v.codigo)) as codigo')
            ->selectRaw('MAX(v.descripcion) as descripcion')
            ->selectRaw('SUM(CASE WHEN v.cantidad>0 THEN v.cantidad ELSE 0 END) as ventas_brutas')
            ->selectRaw('SUM(CASE WHEN v.cantidad<0 THEN -v.cantidad ELSE 0 END) as devoluciones')
            ->selectRaw('SUM(v.cantidad) as ventas_netas')
            ->selectRaw('SUM(v.p_venta) as total_neto')
            ->groupByRaw('UPPER(BTRIM(v.codigo))')
            ->orderByRaw('SUM(v.cantidad) DESC')->get();

        // Las transferencias de LOCALES a MATRIZ forman parte de los
        // documentos de entrada; no confundir asignación logística con
        // asignación a factura, que exige vínculo y evidencia.
        // Solo destino MATRIZ (cod. 25 o alias): MODELO MUESTRA con otro
        // destino se investiga en la ficha de factura por separado.
        $usosRemision = DB::table('mayorista_vinculos')
            ->select('remision_id')->selectRaw('SUM(cantidad) as usadas')
            ->groupBy('remision_id');
        $baseTransferencias = VentaMayoristaController::entradas()
            ->whereRaw(OrigenRemision::expresionDestinoMatriz('r'))
            ->whereBetween('r.fecha_remision', [$desde, $hasta])
            ->leftJoinSub($usosRemision, 'uso', 'uso.remision_id', '=', 'r.id');

        $claveDocumento = "COALESCE(r.serie,'') || '|' || COALESCE(r.numero_remision,'')"
            . " || '|' || COALESCE(CAST(r.cod_sucursal_salida AS TEXT),'')"
            . " || '|' || COALESCE(CAST(r.cod_sucursal_destino AS TEXT),'')"
            . " || '|' || COALESCE(CAST(r.fecha_remision AS TEXT),'')";

        // Agrupar por código, no por variante textual del nombre.
        // Con código NULL, el nombre de origen permite diferenciar locales.
        $claveOrigen = "COALESCE(CAST(NULLIF(r.cod_sucursal_salida,0) AS TEXT),"
            . " UPPER(BTRIM(COALESCE(r.sucursal_salida,''))))";
        $agrupadasOrigen = (clone $baseTransferencias)
            ->selectRaw('MAX(r.cod_sucursal_salida) as cod_sucursal_salida')
            ->selectRaw('MAX(r.sucursal_salida) as sucursal_salida')
            ->selectRaw("COUNT(DISTINCT $claveDocumento) as documentos")
            ->selectRaw('COUNT(*) as lineas, COALESCE(SUM(r.cantidad),0) as unidades')
            ->selectRaw("COALESCE(SUM(CASE WHEN r.fecha_recepcion IS NOT NULL THEN r.cantidad ELSE 0 END),0) as recibidas")
            ->selectRaw('COALESCE(SUM(COALESCE(uso.usadas,0)),0) as vinculadas')
            ->selectRaw('COALESCE(SUM(GREATEST(0,r.cantidad-COALESCE(uso.usadas,0))),0) as saldo')
            ->groupByRaw($claveOrigen)->get();

        // Un mismo local puede tener nombres alternativos en distintas
        // importaciones; consolidar siempre por código de salida.
        $transferenciasOrigen = $agrupadasOrigen
            ->groupBy(function ($fila) {
                return OrigenRemision::nombreOrigen($fila);
            })->map(function ($filas, $nombre) {
                $primera = $filas->first();
                return (object) [
                    'nombre' => $nombre,
                    'codigo_origen' => $primera->cod_sucursal_salida,
                    'nombre_importado' => $primera->sucursal_salida,
                    'tipo' => OrigenRemision::tipoOrigen($primera),
                    'documentos' => (int) $filas->sum('documentos'),
                    'lineas' => (int) $filas->sum('lineas'),
                    'unidades' => (int) $filas->sum('unidades'),
                    'recibidas' => (int) $filas->sum('recibidas'),
                    'vinculadas' => (int) $filas->sum('vinculadas'),
                    'saldo' => (int) $filas->sum('saldo'),
                ];
            })->sortByDesc('unidades')->values();

        $transferenciasResumen = (object) [
            'locales' => $transferenciasOrigen->where('tipo','LOCAL')->sum('unidades'),
            'central' => $transferenciasOrigen->where('tipo','CENTRAL')->sum('unidades'),
            'sin_origen' => $transferenciasOrigen->where('tipo','NO IDENTIFICADO')->sum('unidades'),
            'remitidas' => $transferenciasOrigen->sum('unidades'),
            'recibidas' => $transferenciasOrigen->sum('recibidas'),
        ];

        $remisionesDocumentos = (clone $baseTransferencias)
            ->selectRaw('MIN(r.id) as remision_id')
            ->addSelect('r.serie','r.numero_remision','r.cod_sucursal_salida',
                'r.cod_sucursal_destino','r.fecha_remision')
            ->selectRaw('MAX(r.sucursal_salida) as sucursal_salida')
            ->selectRaw('MAX(r.sucursal_destino) as sucursal_destino')
            ->selectRaw('COUNT(*) as lineas, SUM(r.cantidad) as unidades')
            ->selectRaw('SUM(COALESCE(uso.usadas,0)) as vinculadas')
            ->selectRaw('SUM(GREATEST(0,r.cantidad-COALESCE(uso.usadas,0))) as saldo')
            ->selectRaw('COUNT(*) FILTER (WHERE r.fecha_recepcion IS NOT NULL) as lineas_recibidas')
            ->groupBy('r.serie','r.numero_remision','r.cod_sucursal_salida',
                'r.cod_sucursal_destino','r.fecha_remision')
            ->orderBy('r.fecha_remision','desc')
            ->orderBy('r.numero_remision','desc')
            ->paginate(30,['*'],'remisiones_page')
            ->appends($request->only(['desde','hasta','page']));

        $remisionesDocumentos->getCollection()->transform(function ($doc) {
            $doc->origen_real = OrigenRemision::nombreOrigen($doc);
            $doc->tipo_origen = OrigenRemision::tipoOrigen($doc);
            $doc->origen_discordante = OrigenRemision::origenDiscordante($doc);
            return $doc;
        });

        return view('ventas.mayoristas_matriz', compact(
            'desde','hasta','facturas','resumen','ventasDetalle','porCodigo',
            'transferenciasOrigen','transferenciasResumen','remisionesDocumentos',
            'topClientes'
        ));
    }


    /**
     * Historial comercial de un cliente en COMERCIAL MATRIZ.
     *
     * El token preserva (código, nombre), porque un mismo código puede
     * repetirse con distintas denominaciones en la exportación.
     * Las facturas se cuentan una vez por documento y no por artículo.
     */
    public function cliente(Request $request)
    {
        abort_unless(Schema::hasTable('ventas'), 404);
        list($desde, $hasta) = $this->fechas($request);
        $datos = $request->validate([
            'cliente' => 'required|string|max:1000',
            'page' => 'nullable|integer|min:1',
        ]);

        $identidad = MayoristaTopClientes::identidad($datos['cliente']);
        abort_unless($identidad !== null, 404, 'Cliente mayorista no válido.');
        list($codigoCliente, $nombreCliente) = $identidad;

        $facturas = MayoristaTopClientes::seleccionar(
            $this->facturasEntre($desde, $hasta),
            $codigoCliente,
            $nombreCliente
        )->sort(function ($a, $b) {
            return strcmp($b->fecha . '|' . $b->comprobante,
                $a->fecha . '|' . $a->comprobante);
        })->values();

        $resumenCliente = MayoristaTopClientes::resumen($facturas);
        $pagina = max(1, (int) $request->query('page', 1));
        $facturasPagina = new LengthAwarePaginator(
            $facturas->forPage($pagina, 30)->values(),
            $facturas->count(),
            30,
            $pagina,
            ['path' => $request->url(), 'query' => $request->except('page')]
        );

        return view('ventas.mayoristas_cliente', compact(
            'desde', 'hasta', 'codigoCliente', 'nombreCliente',
            'resumenCliente', 'facturasPagina'
        ));
    }

    /**
     * Navegación documental: sucursal de origen -> sus remisiones emitidas
     * hacia Matriz en el mismo período de la pantalla Mayoristas.
     * No confundir una transferencia logística con una factura conciliada.
     */
    public function sucursal(Request $request)
    {
        list($desde, $hasta) = $this->fechas($request);
        $datos = $request->validate([
            'codigo' => 'nullable|integer|min:1',
            'nombre' => 'nullable|string|max:100',
            'sin_origen' => 'nullable|in:1',
        ]);
        $codigo = isset($datos['codigo']) ? (int) $datos['codigo'] : null;
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $sinOrigen = ($datos['sin_origen'] ?? '') === '1';

        if ($codigo === null && $nombre === '' && !$sinOrigen) {
            abort(422, 'Indicá la sucursal de origen a consultar.');
        }
        if ($codigo === OrigenRemision::CODIGO_MATRIZ) {
            abort(404, 'Matriz no puede ser origen de una entrada a sí misma.');
        }

        $usoPorLinea = DB::table('mayorista_vinculos')
            ->select('remision_id')
            ->selectRaw('SUM(cantidad) as usadas')
            ->groupBy('remision_id');

        $base = VentaMayoristaController::entradas()
            ->whereRaw(OrigenRemision::expresionDestinoMatriz('r'))
            ->whereBetween('r.fecha_remision', [$desde, $hasta])
            ->when($codigo !== null, function ($q) use ($codigo) {
                $q->where('r.cod_sucursal_salida', $codigo);
            })
            ->when($codigo === null, function ($q) use ($nombre, $sinOrigen) {
                // Cuando el origen no tiene código en la importación,
                // localizarlo por su nombre, sin arrastrar otras sucursales.
                $q->where(function ($sinCodigo) {
                    $sinCodigo->whereNull('r.cod_sucursal_salida')
                        ->orWhere('r.cod_sucursal_salida', 0);
                });
                if ($sinOrigen) {
                    $q->whereRaw("BTRIM(COALESCE(r.sucursal_salida,'')) = ''");
                } else {
                    $q->whereRaw(
                        "UPPER(BTRIM(COALESCE(r.sucursal_salida,''))) = ?",
                        [mb_strtoupper($nombre, 'UTF-8')]
                    );
                }
            })
            ->leftJoinSub($usoPorLinea, 'uso', 'uso.remision_id', '=', 'r.id');

        $primera = (clone $base)
            ->select('r.cod_sucursal_salida','r.sucursal_salida')->first();
        if (!$primera) {
            abort(404, 'Sin remisiones de esa sucursal hacia Matriz en el período.');
        }

        $origen = (object) [
            'codigo' => $codigo,
            'nombre' => OrigenRemision::nombreOrigen($primera),
            'tipo' => OrigenRemision::tipoOrigen($primera),
        ];

        $claveDocumento = "COALESCE(r.serie,'') || '|' || COALESCE(r.numero_remision,'')"
            . " || '|' || COALESCE(CAST(r.cod_sucursal_salida AS TEXT),'')"
            . " || '|' || COALESCE(CAST(r.cod_sucursal_destino AS TEXT),'')"
            . " || '|' || COALESCE(CAST(r.fecha_remision AS TEXT),'')";

        $totales = (clone $base)
            ->selectRaw("COUNT(DISTINCT $claveDocumento) as documentos")
            ->selectRaw('COUNT(*) as lineas')
            ->selectRaw('COALESCE(SUM(r.cantidad),0) as remitidas')
            ->selectRaw('COALESCE(SUM(CASE WHEN r.fecha_recepcion IS NOT NULL THEN r.cantidad ELSE 0 END),0) as recibidas')
            ->selectRaw('COALESCE(SUM(COALESCE(uso.usadas,0)),0) as vinculadas')
            ->selectRaw('COALESCE(SUM(GREATEST(0,r.cantidad-COALESCE(uso.usadas,0))),0) as disponibles')
            ->first();

        $remisiones = (clone $base)
            ->selectRaw('MIN(r.id) as remision_id')
            ->addSelect('r.serie','r.numero_remision','r.cod_sucursal_salida',
                'r.cod_sucursal_destino','r.fecha_remision')
            ->selectRaw('MAX(r.sucursal_salida) as sucursal_salida')
            ->selectRaw('MAX(r.sucursal_destino) as sucursal_destino')
            ->selectRaw('COUNT(*) as lineas, COALESCE(SUM(r.cantidad),0) as remitidas')
            ->selectRaw('COALESCE(SUM(CASE WHEN r.fecha_recepcion IS NOT NULL THEN r.cantidad ELSE 0 END),0) as recibidas')
            ->selectRaw('COALESCE(SUM(COALESCE(uso.usadas,0)),0) as vinculadas')
            ->selectRaw('COALESCE(SUM(GREATEST(0,r.cantidad-COALESCE(uso.usadas,0))),0) as disponibles')
            ->selectRaw('COUNT(*) FILTER (WHERE r.fecha_recepcion IS NOT NULL) as lineas_recibidas')
            ->groupBy('r.serie','r.numero_remision','r.cod_sucursal_salida',
                'r.cod_sucursal_destino','r.fecha_remision')
            ->orderBy('r.fecha_remision', 'desc')
            ->orderBy('r.numero_remision', 'desc')
            ->paginate(40)
            ->appends($request->only(['desde','hasta','codigo','nombre','sin_origen']));

        return view('ventas.mayoristas_sucursal', compact(
            'desde','hasta','origen','codigo','nombre','sinOrigen','totales','remisiones'
        ));
    }

    /**
     * Abre una remisión concreta por ID de una de sus líneas.
     * La serie/número solos NO identifican con seguridad un documento:
     * se preservan origen, destino y fecha para evitar mezclar remisiones.
     */
    public function remision(Request $request, int $id)
    {
        $cabecera = VentaMayoristaController::entradas()
            ->whereRaw(OrigenRemision::expresionDestinoMatriz('r'))
            ->where('r.id', $id)
            ->select('r.id','r.serie','r.numero_remision','r.fecha_remision',
                'r.cod_sucursal_salida','r.sucursal_salida',
                'r.cod_sucursal_destino','r.sucursal_destino',
                'r.sucursal_logistica')
            ->first();
        if (!$cabecera) {
            abort(404, 'Remisión no encontrada entre las entradas hacia Matriz.');
        }

        $base = VentaMayoristaController::entradas()
            ->whereRaw(OrigenRemision::expresionDestinoMatriz('r'))
            ->where('r.serie', $cabecera->serie)
            ->where('r.numero_remision', $cabecera->numero_remision)
            ->where('r.fecha_remision', $cabecera->fecha_remision)
            ->where('r.cod_sucursal_salida', $cabecera->cod_sucursal_salida)
            ->where('r.cod_sucursal_destino', $cabecera->cod_sucursal_destino)
            // Si el importador no entregó código, el nombre de origen
            // y destino también forma parte de la identidad documental.
            // Evita mezclar dos sucursales sin código con mismo número.
            ->when(!$cabecera->cod_sucursal_salida, function ($q) use ($cabecera) {
                $q->whereRaw(
                    "UPPER(BTRIM(COALESCE(r.sucursal_salida,''))) = ?",
                    [mb_strtoupper(trim((string) $cabecera->sucursal_salida), 'UTF-8')]
                );
            })
            ->when(!$cabecera->cod_sucursal_destino, function ($q) use ($cabecera) {
                $q->whereRaw(
                    "UPPER(BTRIM(COALESCE(r.sucursal_destino,''))) = ?",
                    [mb_strtoupper(trim((string) $cabecera->sucursal_destino), 'UTF-8')]
                );
            })
            ->leftJoinSub(
                DB::table('mayorista_vinculos')
                    ->select('remision_id')
                    ->selectRaw('SUM(cantidad) as usadas')
                    ->groupBy('remision_id'),
                'uso', 'uso.remision_id', '=', 'r.id'
            );

        $totales = (clone $base)
            ->selectRaw('COUNT(*) as lineas')
            ->selectRaw('COALESCE(SUM(r.cantidad),0) as remitidas')
            ->selectRaw('COALESCE(SUM(CASE WHEN r.fecha_recepcion IS NOT NULL THEN r.cantidad ELSE 0 END),0) as recibidas')
            ->selectRaw('COALESCE(SUM(COALESCE(uso.usadas,0)),0) as vinculadas')
            ->selectRaw('COALESCE(SUM(GREATEST(0,r.cantidad-COALESCE(uso.usadas,0))),0) as disponibles')
            ->first();

        $lineas = (clone $base)
            ->leftJoin('ot as o', 'o.id_ot', '=', 'r.id_ot')
            ->select('r.id','r.serie','r.numero_remision','r.id_ot','o.nro_ot',
                'r.codigo','r.descripcion','r.cantidad','r.fecha_recepcion',
                'r.estado','r.precio_venta','r.costo_unitario')
            ->selectRaw('COALESCE(uso.usadas,0) as vinculadas')
            ->selectRaw('GREATEST(0,r.cantidad-COALESCE(uso.usadas,0)) as disponibles')
            ->orderBy('r.codigo')->orderBy('r.id')
            ->paginate(100)->appends($request->only(['desde','hasta','codigo','nombre','sin_origen']));

        $cabecera->origen_real = OrigenRemision::nombreOrigen($cabecera);
        $cabecera->tipo_origen = OrigenRemision::tipoOrigen($cabecera);
        $cabecera->origen_discordante = OrigenRemision::origenDiscordante($cabecera);

        return view('ventas.mayoristas_remision', compact(
            'cabecera','totales','lineas'
        ));
    }

    /**
     * CSV de las 12 columnas originales del importador, sin campos
     * técnicos (id, hash, timestamps). Se exporta por LÍNEA, no factura:
     * así se preservan cantidades y talles sin agrupar ni perder datos.
     *
     * Los negativos de las notas de crédito se conservan exactamente.
     * Si no hay rango, se exporta todo el historial de COMERCIAL MATRIZ.
     */
    private function exportarVentas(?string $desde, ?string $hasta)
    {
        $nombre = $desde !== null && $hasta !== null
            ? 'ventas_matriz_importacion_' . $desde . '_al_' . $hasta . '.csv'
            : 'ventas_matriz_historico_completo.csv';

        return response()->streamDownload(function () use ($desde, $hasta) {
            $archivo = fopen('php://output', 'w');
            fwrite($archivo, "\xEF\xBB\xBF"); // UTF-8 Excel y acentos en clientes.
            fputcsv($archivo, MayoristaExportacionVentas::ENCABEZADOS, ';');

            $consulta = VentaMayoristaController::ventas();
            if ($desde !== null && $hasta !== null) {
                $consulta->whereBetween('v.fecha', [$desde, $hasta]);
            }

            $campos = [
                'v.id', 'v.local', 'v.codigo', 'v.descripcion',
                'v.cli_cod', 'v.cliente', 'v.vendedor', 'v.p_lista',
                'v.descuento', 'v.p_venta', 'v.fecha',
                'v.cantidad', 'v.comprobante',
            ];

            // chunkById evita limitar la descarga a las 100 líneas
            // mostradas por página y no carga todo el histórico en RAM.
            $consulta->select($campos)->chunkById(1000, function ($ventas) use ($archivo) {
                foreach ($ventas as $venta) {
                    fputcsv($archivo, MayoristaExportacionVentas::fila($venta), ';');
                }
            }, 'v.id', 'id');

            fclose($archivo);
        }, $nombre, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Descarga técnica anterior con metadatos de importación; se mantiene
     * disponible de forma separada para auditoría sin mezclar formatos.
     */
    private function exportarVentasAuditoria(string $desde, string $hasta)
    {
        // CSV completo, independiente de la paginación visual. Las cifras
        // proceden exclusivamente de la tabla ventas en COMERCIAL MATRIZ.
        $campos = [
            'id','fecha','local','comprobante','tipo_comprobante',
            'cli_cod','cliente','vendedor','codigo','descripcion','cantidad',
            'p_lista','descuento','p_venta','importacion_id','hash_linea',
            'created_at','updated_at',
        ];
        return response()->streamDownload(function () use ($desde, $hasta, $campos) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, $campos, ';');
            VentaMayoristaController::ventas()->whereBetween('v.fecha',[$desde,$hasta])
                ->orderBy('v.id')
                ->select(array_map(function ($campo) {
                    return 'v.'.$campo;
                }, $campos))
                ->chunkById(1000, function ($filas) use ($salida, $campos) {
                    foreach ($filas as $fila) {
                        $valores = [];
                        foreach ($campos as $campo) {
                            $valor = $fila->{$campo};
                            // No ejecutar fórmulas al abrir textos importados
                            // de clientes/comprobantes en Excel.
                            if (is_string($valor) && preg_match('/^[=+@]/', trim($valor))) {
                                $valor = "'".$valor;
                            }
                            $valores[] = $valor;
                        }
                        fputcsv($salida, $valores, ';');
                    }
                }, 'v.id', 'id');
            fclose($salida);
        }, 'ventas_comercial_matriz_'.$desde.'_al_'.$hasta.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8'
        ]);
    }

    public function factura(Request $request, string $clave)
    {
        $mes = $request->query('mes', '');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $mes)) {
            abort(422, 'Se requiere el mes válido de la factura.');
        }
        $factura = VentaMayoristaController::buscarFactura($clave, $mes);
        $lineas = VentaMayoristaController::lineas($factura);
        $requeridas = VentaMayoristaController::necesidades($lineas);
        $enlaces = DB::table('mayorista_vinculos as m')
            ->join('ot_logistica_remisiones as r','r.id','=','m.remision_id')
            ->where('m.factura_clave',$clave)
            ->select('m.*','r.serie','r.numero_remision','r.fecha_remision',
                'r.sucursal_salida','r.cod_sucursal_salida',
                'r.sucursal_destino','r.cod_sucursal_destino','r.sucursal_logistica')
            ->orderBy('m.id')->get();

        $enlaces->each(function ($v) {
            $v->origen_real = OrigenRemision::nombreOrigen($v);
            $v->tipo_origen = OrigenRemision::tipoOrigen($v);
            $v->origen_discordante = OrigenRemision::origenDiscordante($v);
        });
        $confirmadas = [];
        foreach ($enlaces as $v) {
            $codigo = Conciliador::codigo($v->codigo);
            $confirmadas[$codigo] = ($confirmadas[$codigo] ?? 0) + (int) $v->cantidad;
        }

        $porCodigo = collect($requeridas)->map(function ($cantidad, $codigo) use ($confirmadas) {
            $asignadas = (int) ($confirmadas[$codigo] ?? 0);
            return (object) [
                'codigo' => $codigo,'necesita' => $cantidad,'validado' => $asignadas,
                'pendiente' => max(0, $cantidad-$asignadas),
            ];
        });

        $candidatas = collect();
        if ($requeridas) {
            $usos = DB::table('mayorista_vinculos')
                ->select('remision_id')->selectRaw('SUM(cantidad) as usadas')
                ->groupBy('remision_id');
            $diasHistorial = in_array($request->query('historial'), ['90','365','todo'], true)
                ? $request->query('historial') : '90';
            $filtroOrigen = in_array($request->query('origen'), ['todos','locales','central'], true)
                ? $request->query('origen') : 'todos';
            $consulta = VentaMayoristaController::entradas()
                ->leftJoinSub($usos, 'usos', 'usos.remision_id', '=', 'r.id')
                ->whereIn(DB::raw('UPPER(BTRIM(r.codigo))'), array_keys($requeridas))
                ->whereNotNull('r.fecha_remision')
                // Remisiones posteriores (hasta 3 días) se muestran SOLO
                // para investigar, sin permitir asociarlas a esta factura.
                ->where('r.fecha_remision', '<=', Carbon::parse($factura->fecha)
                    ->addDays(3)->format('Y-m-d'))
                ->select('r.id','r.id_ot','r.id_logistica_detalle','r.serie','r.numero_remision',
                    'r.codigo','r.descripcion','r.cantidad','r.fecha_remision',
                    'r.fecha_recepcion','r.sucursal_salida','r.sucursal_destino',
                    'r.sucursal_logistica','r.cod_sucursal_salida','r.cod_sucursal_destino')
                ->selectRaw('COALESCE(usos.usadas,0) as usadas')
                ->selectRaw("CASE WHEN UPPER(COALESCE(r.sucursal_destino,'')) LIKE '%MODELO%MUESTRA%'
                        OR UPPER(COALESCE(r.sucursal_logistica,'')) LIKE '%MODELO%MUESTRA%'
                        OR EXISTS (SELECT 1 FROM ot_logistica_detalle d
                          WHERE d.id=r.id_logistica_detalle
                            AND UPPER(COALESCE(d.sucursal,'')) LIKE '%MODELO%MUESTRA%')
                    THEN 1 ELSE 0 END AS es_modelo")
                ->orderBy('r.fecha_remision','desc')->orderBy('r.id','desc');

            if ($filtroOrigen === 'locales') {
                // Esta pestaña es específicamente transferencia LOCAL
                // con DESTINO MATRIZ. Las remisiones a MODELO MUESTRA
                // fuera de Matriz siguen visibles en "Todas".
                $consulta->whereRaw(OrigenRemision::expresionDestinoMatriz('r'));
                $consulta->where(function ($q) {
                    $q->whereRaw('r.cod_sucursal_salida NOT IN (1,25)')
                      ->orWhere(function ($sinCodigo) {
                          $sinCodigo->whereNull('r.cod_sucursal_salida')
                              ->whereNotNull('r.sucursal_salida')
                              ->whereRaw("UPPER(BTRIM(r.sucursal_salida)) NOT IN ('CASA CENTRAL','CENTRAL','MATRIZ','COMERCIAL MATRIZ')");
                      });
                });
            } elseif ($filtroOrigen === 'central') {
                $consulta->where(function ($q) {
                    $q->where('r.cod_sucursal_salida',1)
                      ->orWhere(function ($sinCodigo) {
                          $sinCodigo->whereNull('r.cod_sucursal_salida')
                              ->whereRaw("UPPER(BTRIM(COALESCE(r.sucursal_salida,''))) IN ('CASA CENTRAL','CENTRAL')");
                      });
                });
            }

            if ($diasHistorial !== 'todo') {
                $consulta->where('r.fecha_remision','>=',Carbon::parse($factura->fecha)
                    ->subDays((int)$diasHistorial)->format('Y-m-d'));
            }
            // Máximo para procesamiento interactivo; indicar claramente
            // si existe otra página documental, nunca presentar "sin saldo".
            $limitadas = (clone $consulta)->count() > 3000;
            $candidatas = $consulta->limit(3000)->get();
        } else {
            $diasHistorial = '90';
            $filtroOrigen = 'todos';
            $limitadas = false;
        }

        // Nunca fusionar talles procedentes de remisiones diferentes.
        $documentos = MayoristaCruceDocumento::agrupar(
            $candidatas, $requeridas, $porCodigo, $factura->fecha
        );

        $auditoria = DB::table('mayorista_auditoria')
            ->where('factura_clave', $clave)->orderBy('created_at','desc')->limit(40)->get();
        $totales = (object) [
            'asignadas' => array_sum($confirmadas),
            'pendientes' => (int) $factura->brutas - array_sum($confirmadas),
            'codigos' => count($requeridas),
            'documentos' => $documentos->count(),
            'documentos_completos' => $documentos->where('completo',true)->count(),
            'candidatas_limitadas' => $limitadas,
        ];

        return view('ventas.mayoristas_matriz_factura', compact(
            'mes','factura','lineas','porCodigo','enlaces','auditoria','documentos',
            'totales','diasHistorial','filtroOrigen'
        ));
    }
}
