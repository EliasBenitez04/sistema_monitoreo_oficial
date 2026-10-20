<?php

namespace App\Http\Controllers;

use App\Support\MayoristaConciliacion as Conciliador;
use App\Support\MayoristaCruceDocumento;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

    public function index(Request $request)
    {
        if (!Schema::hasTable('ventas') || !Schema::hasTable('ot_logistica_remisiones')) {
            return view('ventas.mayoristas_matriz', ['sinFuentes' => true]);
        }

        list($desde, $hasta) = $this->fechas($request);
        if ($request->query('exportar') === 'ventas') {
            return $this->exportarVentas($desde, $hasta);
        }
        $facturas = $this->facturasEntre($desde, $hasta);
        $vinculadas = $this->vinculos($facturas->pluck('clave'));

        $facturas->each(function ($f) use ($vinculadas) {
            $f->asignadas = (int) ($vinculadas[$f->clave] ?? 0);
            $f->por_asignar = max(0, (int) $f->brutas - $f->asignadas);
            $f->estado_cruce = (int) $f->brutas === 0 ? 'NOTA DE CRÉDITO'
                : ($f->por_asignar === 0 ? 'ASIGNADA' : ($f->asignadas > 0 ? 'PARCIAL' : 'SIN ASIGNAR'));
        });

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

        return view('ventas.mayoristas_matriz', compact(
            'desde','hasta','facturas','resumen','ventasDetalle','porCodigo'
        ));
    }

    private function exportarVentas(string $desde, string $hasta)
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
                'r.sucursal_salida','r.sucursal_destino', 'r.sucursal_logistica')
            ->orderBy('m.id')->get();

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
            $consulta = VentaMayoristaController::entradas()
                ->leftJoinSub($usos, 'usos', 'usos.remision_id', '=', 'r.id')
                ->whereIn(DB::raw('UPPER(BTRIM(r.codigo))'), array_keys($requeridas))
                ->whereNotNull('r.fecha_remision')
                ->where('r.fecha_remision', '<=', $factura->fecha)
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
            $limitadas = false;
        }

        // Nunca fusionar talles procedentes de remisiones diferentes.
        $documentos = MayoristaCruceDocumento::agrupar(
            $candidatas, $requeridas, $porCodigo
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
            'totales','diasHistorial'
        ));
    }
}
