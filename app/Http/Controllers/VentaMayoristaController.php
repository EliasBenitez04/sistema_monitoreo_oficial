<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VentaMayoristaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:pedido_compras index');
    }

    /**
     * Ventas del canal mayorista = TODAS las líneas del local COMERCIAL MATRIZ.
     * Las remisiones son movimientos físicos, NO comprobantes de venta.
     *
     * Para enlazar ventas con OT se normaliza el código base de 9 caracteres
     * y se asigna únicamente cuando esa base pertenece a UNA OT en toda la BD.
     * Cuando no existe una coincidencia unívoca, no se inventa una OT.
     */
    public function index(Request $request)
    {
        $tablas = Schema::hasTable('ot')
            && Schema::hasTable('ot_logistica_detalle')
            && Schema::hasTable('ot_logistica_remisiones');
        $tieneVentas = Schema::hasTable('ventas');

        $fechaVentas = $tieneVentas ? DB::table('ventas')
            ->whereRaw("UPPER(BTRIM(COALESCE(local,''))) = 'COMERCIAL MATRIZ'")
            ->max('fecha') : null;
        $fechaRemision = $tablas ? DB::table('ot_logistica_remisiones')->max('fecha_remision') : null;
        $referencia = Carbon::parse(max($fechaVentas ?: '', $fechaRemision ?: '') ?: Carbon::today()->format('Y-m-d'));

        try {
            $desde = $request->filled('desde')
                ? Carbon::createFromFormat('Y-m-d', (string) $request->query('desde'))->format('Y-m-d')
                : $referencia->copy()->startOfMonth()->format('Y-m-d');
            $hasta = $request->filled('hasta')
                ? Carbon::createFromFormat('Y-m-d', (string) $request->query('hasta'))->format('Y-m-d')
                : $referencia->format('Y-m-d');
        } catch (\Exception $e) {
            $desde = $referencia->copy()->startOfMonth()->format('Y-m-d');
            $hasta = $referencia->format('Y-m-d');
        }
        if ($desde > $hasta) {
            list($desde, $hasta) = [$hasta, $desde];
        }

        $tipo = (string) $request->query('destino', 'todos');
        if (!in_array($tipo, ['todos', 'modelo', 'mayorista'], true)) {
            $tipo = 'todos';
        }
        $buscar = trim((string) $request->query('buscar', ''));
        $muestrasPorOt = min(20, max(0, (int) $request->query('muestras_por_ot', 1)));
        $incluirPlan = $request->boolean('incluir_plan');

        $baseOt = $this->baseCodigoSql('o.codigo');
        $baseVentas = $this->baseCodigoSql('v.codigo');
        $modeloD = "UPPER(BTRIM(COALESCE(d.sucursal,''))) LIKE '%MODELO%MUESTRA%'";
        $mayoristaD = "(UPPER(BTRIM(COALESCE(d.sucursal,''))) LIKE '%MAYORISTA%' OR UPPER(BTRIM(COALESCE(d.sucursal,''))) = 'COMERCIAL MATRIZ')";
        $modeloR = "(UPPER(BTRIM(COALESCE(r.sucursal_logistica,''))) LIKE '%MODELO%MUESTRA%' OR UPPER(BTRIM(COALESCE(r.sucursal_destino,''))) LIKE '%MODELO%MUESTRA%' OR $modeloD)";
        $mayoristaR = "(UPPER(BTRIM(COALESCE(r.sucursal_salida,''))) = 'COMERCIAL MATRIZ' OR UPPER(BTRIM(COALESCE(r.sucursal_logistica,''))) LIKE '%MAYORISTA%' OR UPPER(BTRIM(COALESCE(r.sucursal_destino,''))) LIKE '%MAYORISTA%' OR UPPER(BTRIM(COALESCE(r.sucursal_destino,''))) = 'COMERCIAL MATRIZ' OR $mayoristaD)";
        $clase = "CASE WHEN $modeloR THEN 'MODELO MUESTRA' ELSE 'MAYORISTA' END";
        $clasePlan = "CASE WHEN $modeloD THEN 'MODELO MUESTRA' ELSE 'MAYORISTA' END";

        $baseVentasQuery = null;
        $ventasPorCodigo = collect();
        $ventasDias = collect();
        $clientes = collect();
        $ventasResumen = (object) [
            'prendas_vendidas' => 0, 'devoluciones' => 0,
            'prendas_netas' => 0, 'importe_neto' => 0,
            'venta_lista' => 0, 'descuento' => 0, 'comprobantes' => 0,
            'clientes' => 0, 'codigos' => 0,
        ];

        $basesOtBuscadas = [];
        if ($buscar !== '' && $tablas) {
            $basesOtBuscadas = DB::table('ot as o')
                ->where(function ($q) use ($buscar) {
                    $q->whereRaw('CAST(o.nro_ot AS TEXT) ILIKE ?', ['%' . $buscar . '%'])
                        ->orWhere('o.codigo', 'ILIKE', '%' . $buscar . '%')
                        ->orWhere('o.descripcion', 'ILIKE', '%' . $buscar . '%');
                })->selectRaw($baseOt . ' as base')->pluck('base')->filter()->unique()->all();
        }

        if ($tieneVentas) {
            $baseVentasQuery = DB::table('ventas as v')
                ->whereRaw("UPPER(BTRIM(COALESCE(v.local,''))) = 'COMERCIAL MATRIZ'")
                ->whereBetween('v.fecha', [$desde, $hasta]);
            if ($buscar !== '') {
                $baseVentasQuery->where(function ($q) use ($buscar, $baseVentas, $basesOtBuscadas) {
                    $q->where('v.codigo', 'ILIKE', '%' . $buscar . '%')
                        ->orWhere('v.descripcion', 'ILIKE', '%' . $buscar . '%')
                        ->orWhere('v.cliente', 'ILIKE', '%' . $buscar . '%');
                    if ($basesOtBuscadas) {
                        foreach (array_chunk($basesOtBuscadas, 400) as $bases) {
                            $q->orWhereIn(DB::raw($baseVentas), $bases);
                        }
                    }
                });
            }
            $ventasResumen = (clone $baseVentasQuery)
                ->selectRaw("COALESCE(SUM(CASE WHEN v.cantidad > 0 THEN v.cantidad ELSE 0 END),0) as prendas_vendidas")
                ->selectRaw("COALESCE(SUM(CASE WHEN v.cantidad < 0 THEN -v.cantidad ELSE 0 END),0) as devoluciones")
                ->selectRaw("COALESCE(SUM(v.cantidad),0) as prendas_netas, COALESCE(SUM(v.p_venta),0) as importe_neto")
                ->selectRaw("COALESCE(SUM(v.p_lista),0) as venta_lista, COALESCE(SUM(v.descuento),0) as descuento")
                ->selectRaw("COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.comprobante ELSE NULL END) as comprobantes")
                ->selectRaw("COUNT(DISTINCT NULLIF(BTRIM(COALESCE(v.cli_cod,'')),'')) as clientes, COUNT(DISTINCT v.codigo) as codigos")
                ->first();
            $ventasPorCodigo = (clone $baseVentasQuery)
                ->selectRaw("$baseVentas as codigo_base, MAX(v.descripcion) as descripcion")
                ->selectRaw("COALESCE(SUM(v.cantidad),0) as prendas_netas, COALESCE(SUM(v.p_venta),0) as venta_neta")
                ->selectRaw("COALESCE(SUM(CASE WHEN v.cantidad > 0 THEN v.cantidad ELSE 0 END),0) as vendidas")
                ->selectRaw("COALESCE(SUM(CASE WHEN v.cantidad < 0 THEN -v.cantidad ELSE 0 END),0) as devoluciones")
                ->selectRaw("COUNT(DISTINCT CASE WHEN v.cantidad > 0 THEN v.comprobante ELSE NULL END) as comprobantes")
                ->groupByRaw($baseVentas)->get();

            $ventasDias = (clone $baseVentasQuery)
                ->select('v.fecha')
                ->selectRaw('COALESCE(SUM(v.cantidad),0) as prendas, COALESCE(SUM(v.p_venta),0) as importe')
                ->groupBy('v.fecha')->orderBy('v.fecha')->get();

            $clientes = (clone $baseVentasQuery)
                ->selectRaw("COALESCE(NULLIF(BTRIM(v.cliente),''),'CLIENTE SIN IDENTIFICAR') as cliente")
                ->selectRaw("COALESCE(SUM(v.p_venta),0) as venta_neta, COALESCE(SUM(v.cantidad),0) as prendas")
                ->groupByRaw("COALESCE(NULLIF(BTRIM(v.cliente),''),'CLIENTE SIN IDENTIFICAR')")
                ->orderBy('venta_neta','desc')->limit(8)->get();
        }

        // Nunca atribuir ventas solo por descripción. El código base debe
        // identificar inequívocamente una OT en TODA la base histórica.
        $idsPorBase = [];
        if ($tablas && $ventasPorCodigo->isNotEmpty()) {
            $bases = $ventasPorCodigo->pluck('codigo_base')->filter()->unique()->values()->all();
            $candidatos = collect();
            foreach (array_chunk($bases, 400) as $fragmento) {
                $candidatos = $candidatos->concat(DB::table('ot as o')
                    ->whereIn(DB::raw($baseOt), $fragmento)
                    ->select('o.id_ot', 'o.nro_ot', 'o.codigo', 'o.descripcion')
                    ->selectRaw("$baseOt as codigo_base")->get());
            }
            foreach ($candidatos->groupBy('codigo_base') as $base => $grupo) {
                if ($grupo->pluck('id_ot')->unique()->count() === 1) {
                    $idsPorBase[$base] = $grupo->first();
                }
            }
        }

        $mapa = [];
        $nuevo = function ($idOt, $codigoBase, $descripcion, $nroOt) use (&$mapa) {
            $clave = $idOt !== null ? 'OT:' . $idOt : 'SIN_OT:' . $codigoBase;
            if (!isset($mapa[$clave])) {
                $mapa[$clave] = (object) [
                    'id_ot' => $idOt, 'nro_ot' => $nroOt,
                    'codigo' => $codigoBase, 'descripcion' => $descripcion,
                    'plan_modelo' => 0, 'plan_mayorista' => 0,
                    'remitido_modelo' => 0, 'remitido_mayorista' => 0,
                    'recibido_modelo' => 0, 'recibido_mayorista' => 0,
                    'salida_central' => 0, 'traslado' => 0,
                    'valor_referencial' => 0, 'costo_referencial' => 0,
                    'documentos' => 0, 'ultima_remision' => null,
                    'ventas_netas' => 0, 'vendidas' => 0, 'devoluciones' => 0,
                    'venta_neta' => 0, 'comprobantes' => 0,
                    'venta_sin_ot' => false, 'remision_sin_ot' => false,
                ];
            }
            return $clave;
        };

        $remisiones = null;
        if ($tablas) {
            $baseRemision = DB::table('ot_logistica_remisiones as r')
                ->leftJoin('ot_logistica_detalle as d', 'd.id', '=', 'r.id_logistica_detalle')
                ->leftJoin('ot as o', function ($join) {
                    $join->on('o.id_ot', '=', DB::raw('COALESCE(r.id_ot, d.id_ot)'));
                })
                ->whereNotNull('r.fecha_remision')
                ->whereBetween('r.fecha_remision', [$desde, $hasta])
                ->where(function ($q) use ($tipo, $modeloR, $mayoristaR) {
                    if ($tipo !== 'mayorista') $q->orWhereRaw($modeloR);
                    if ($tipo !== 'modelo') $q->orWhereRaw($mayoristaR);
                });
            if ($buscar !== '') {
                $baseRemision->where(function ($q) use ($buscar) {
                    $q->whereRaw('CAST(o.nro_ot AS TEXT) ILIKE ?', ['%' . $buscar . '%'])
                        ->orWhere('o.codigo', 'ILIKE', '%' . $buscar . '%')
                        ->orWhere('r.codigo', 'ILIKE', '%' . $buscar . '%')
                        ->orWhere('r.descripcion', 'ILIKE', '%' . $buscar . '%')
                        ->orWhere('o.descripcion', 'ILIKE', '%' . $buscar . '%');
                });
            }

            $idExp = 'COALESCE(r.id_ot, d.id_ot)';
            // Código normalizado también para las remisiones sin OT;
            // quedan separadas del canal de ventas hasta resolver el vínculo.
            $baseRem = $this->baseCodigoSql('r.codigo');
            $agrupadas = (clone $baseRemision)
                ->selectRaw("$idExp as id_ot, $baseRem as codigo_base, $clase as canal")
                ->selectRaw("MAX(o.nro_ot) as nro_ot, MAX(o.codigo) as codigo_ot, MAX(o.descripcion) as descripcion_ot, MAX(r.descripcion) as descripcion_remision")
                ->selectRaw("COALESCE(SUM(r.cantidad),0) as remitido, COALESCE(SUM(CASE WHEN r.fecha_recepcion IS NOT NULL THEN r.cantidad ELSE 0 END),0) as recibido")
                ->selectRaw("COALESCE(SUM(CASE WHEN r.cod_sucursal_salida = 1 OR UPPER(BTRIM(COALESCE(r.sucursal_salida,''))) IN ('CASA CENTRAL','MATRIZ') THEN r.cantidad ELSE 0 END),0) as salida_central")
                ->selectRaw("COALESCE(SUM(CASE WHEN r.cod_sucursal_salida = 1 OR UPPER(BTRIM(COALESCE(r.sucursal_salida,''))) IN ('CASA CENTRAL','MATRIZ') THEN 0 ELSE r.cantidad END),0) as traslado")
                ->selectRaw("COALESCE(SUM(r.cantidad * COALESCE(r.precio_venta,0)),0) as valor_referencial, COALESCE(SUM(r.cantidad * COALESCE(r.costo_unitario,0)),0) as costo_referencial")
                ->selectRaw("MAX(r.fecha_remision) as ultima_remision, COUNT(*) as documentos")
                ->groupByRaw("$idExp, $baseRem, $clase")
                ->get();

            foreach ($agrupadas as $dato) {
                $clave = $nuevo($dato->id_ot, $dato->id_ot !== null ? ($dato->codigo_ot ?: $dato->codigo_base) : $dato->codigo_base,
                    $dato->descripcion_ot ?: $dato->descripcion_remision, $dato->nro_ot);
                $fila = $mapa[$clave];
                $categoria = $dato->canal === 'MODELO MUESTRA' ? 'modelo' : 'mayorista';
                $fila->{'remitido_' . $categoria} += (int) $dato->remitido;
                $fila->{'recibido_' . $categoria} += (int) $dato->recibido;
                $fila->salida_central += (int) $dato->salida_central;
                $fila->traslado += (int) $dato->traslado;
                $fila->valor_referencial += (float) $dato->valor_referencial;
                $fila->costo_referencial += (float) $dato->costo_referencial;
                $fila->documentos += (int) $dato->documentos;
                $fila->remision_sin_ot = $dato->id_ot === null;
                if (!$fila->ultima_remision || $dato->ultima_remision > $fila->ultima_remision) {
                    $fila->ultima_remision = $dato->ultima_remision;
                }
            }

            $remisiones = (clone $baseRemision)
                ->selectRaw("$idExp as id_ot, $clase as canal")
                ->addSelect('r.fecha_remision','r.fecha_recepcion','r.serie','r.numero_remision','r.codigo',
                    'r.descripcion','r.cantidad','r.precio_venta','r.sucursal_salida',
                    'r.sucursal_destino','r.sucursal_logistica','o.nro_ot')
                ->orderBy('r.fecha_remision','desc')->orderBy('r.id','desc')
                ->paginate(30, ['*'], 'remisiones_page')
                ->appends($request->except('remisiones_page'));
        }

        // Las ventas de COMERCIAL MATRIZ se integran una vez por código base.
        // La venta NO se atribuye a MODELO MUESTRA/otro destino en particular.
        foreach ($ventasPorCodigo as $venta) {
            $base = (string) $venta->codigo_base;
            $ot = $idsPorBase[$base] ?? null;
            $clave = $nuevo($ot ? $ot->id_ot : null, $ot ? $ot->codigo : $base,
                $ot ? $ot->descripcion : $venta->descripcion, $ot ? $ot->nro_ot : null);
            $fila = $mapa[$clave];
            $fila->ventas_netas += (int) $venta->prendas_netas;
            $fila->vendidas += (int) $venta->vendidas;
            $fila->devoluciones += (int) $venta->devoluciones;
            $fila->venta_neta += (float) $venta->venta_neta;
            $fila->comprobantes += (int) $venta->comprobantes;
            $fila->venta_sin_ot = $ot === null;
        }

        // Incorporar el plan solo para OT activas en el período por
        // ventas o remisiones; opción de auditoría para planes sin actividad.
        if ($tablas) {
            $idsActivos = collect($mapa)->pluck('id_ot')->filter()->unique()->values()->all();
            if ($idsActivos || $incluirPlan) {
                $planQuery = DB::table('ot_logistica_detalle as d')
                    ->join('ot as o', 'o.id_ot', '=', 'd.id_ot')
                    ->where(function ($q) use ($tipo,$modeloD,$mayoristaD) {
                        if ($tipo !== 'mayorista') $q->orWhereRaw($modeloD);
                        if ($tipo !== 'modelo') $q->orWhereRaw($mayoristaD);
                    });
                if (!$incluirPlan) {
                    $planQuery->whereIn('d.id_ot',$idsActivos);
                }
                if ($buscar !== '') {
                    $planQuery->where(function ($q) use ($buscar) {
                        $q->whereRaw('CAST(o.nro_ot AS TEXT) ILIKE ?', ['%' . $buscar . '%'])
                            ->orWhere('o.codigo','ILIKE','%' . $buscar . '%')
                            ->orWhere('o.descripcion','ILIKE','%' . $buscar . '%');
                    });
                }
                $planes = $planQuery
                    ->selectRaw("d.id_ot, $clasePlan as canal, MAX(o.nro_ot) as nro_ot, MAX(o.codigo) as codigo, MAX(o.descripcion) as descripcion, COALESCE(SUM(d.cantidad),0) as asignado")
                    ->groupBy('d.id_ot')->groupByRaw($clasePlan)->get();
                foreach ($planes as $dato) {
                    $clave = $nuevo($dato->id_ot, $dato->codigo, $dato->descripcion, $dato->nro_ot);
                    $categoria = $dato->canal === 'MODELO MUESTRA' ? 'modelo' : 'mayorista';
                    $mapa[$clave]->{'plan_' . $categoria} += (int) $dato->asignado;
                }
            }
        }

        $filas = collect(array_values($mapa))->map(function ($fila) use ($muestrasPorOt) {
            $fila->plan = $fila->plan_modelo + $fila->plan_mayorista;
            $fila->remitido = $fila->remitido_modelo + $fila->remitido_mayorista;
            $fila->recibido = $fila->recibido_modelo + $fila->recibido_mayorista;
            $fila->transito = max(0, $fila->remitido - $fila->recibido);
            // Movimiento interno != mercadería adicional: separar traslado
            // y salida original, evitando inflar KPI de stock o facturación.
            $fila->reserva_foto = $fila->id_ot === null
                ? null : min($muestrasPorOt, max(0, $fila->recibido_modelo));
            $fila->recibido_venta_estimado = $fila->id_ot === null
                ? null : max(0, $fila->recibido - $fila->reserva_foto);
            $fila->brecha_movimientos = $fila->plan - $fila->remitido;
            $fila->status = $fila->id_ot === null ? 'SIN OT SEGURA'
                : ($fila->ventas_netas > 0 && $fila->plan === 0 && $fila->remitido === 0
                    ? 'VENTA SIN PLAN/REMISIÓN'
                    : ($fila->ventas_netas > 0 && $fila->remitido === 0
                        ? 'VENTA SIN REMISIÓN'
                        : ($fila->transito > 0 ? 'RECEPCIÓN PENDIENTE' : 'CON DOCUMENTOS')));
            return $fila;
        })->sort(function ($a,$b) {
            $alerta = ['SIN OT SEGURA'=>0,'VENTA SIN PLAN/REMISIÓN'=>1,'VENTA SIN REMISIÓN'=>2,'RECEPCIÓN PENDIENTE'=>3,'CON DOCUMENTOS'=>4];
            $prioridad = ($alerta[$a->status] ?? 5) <=> ($alerta[$b->status] ?? 5);
            return $prioridad ?: ($b->venta_neta <=> $a->venta_neta);
        })->values();

        $vinculadas = $filas->whereNotNull('id_ot');
        $resumen = (object) [
            'ot_plan' => $vinculadas->where('plan','>',0)->count(),
            'ot_con_venta' => $vinculadas->where('comprobantes','>',0)->count(),
            'plan' => (int) $vinculadas->sum('plan'),
            'remitido' => (int) $filas->sum('remitido'),
            'recibido' => (int) $filas->sum('recibido'),
            'transito' => (int) $filas->sum('transito'),
            'salida_central' => (int) $filas->sum('salida_central'),
            'traslados' => (int) $filas->sum('traslado'),
            'reserva_foto' => (int) $vinculadas->sum('reserva_foto'),
            'recibido_venta_estimado' => (int) $vinculadas->sum('recibido_venta_estimado'),
            'sin_ot_venta' => (int) $filas->where('venta_sin_ot',true)->sum('ventas_netas'),
            'sin_ot_movimiento' => (int) $filas->where('remision_sin_ot',true)->sum('remitido'),
            'con_alerta' => $filas->where('status','<>','CON DOCUMENTOS')->count(),
        ];

        if ($request->query('exportar') === 'csv') {
            return $this->csv($filas,$desde,$hasta);
        }
        $pagina = max(1, (int) $request->query('page',1));
        $porPagina = 35;
        $ordenes = new LengthAwarePaginator(
            $filas->slice(($pagina - 1) * $porPagina,$porPagina)->values(),
            $filas->count(),$porPagina,$pagina,
            ['path'=>$request->url(),'query'=>$request->query()]
        );
        return view('ventas.mayoristas',compact(
            'desde','hasta','tipo','buscar','muestrasPorOt','incluirPlan',
            'resumen','ventasResumen','ordenes','remisiones','ventasDias',
            'clientes','tablas','tieneVentas'
        ));
    }

    /**
     * Misma normalización empleada en conciliación logística:
     * 8 dígitos -> 0 inicial; demás códigos -> primeros 9 caracteres
     * alfanuméricos. Menos de 8 caracteres no son atribuibles.
     */
    private function baseCodigoSql(string $campo): string
    {
        $limpio = "REGEXP_REPLACE(UPPER(BTRIM(COALESCE($campo,''))),'[^0-9A-Z]','','g')";
        return "(CASE WHEN $limpio ~ '^[0-9]{8}$' THEN LPAD($limpio,9,'0') "
            . "WHEN LENGTH($limpio) >= 9 THEN LEFT($limpio,9) ELSE '' END)";
    }

    private function csv($filas, string $desde, string $hasta)
    {
        return response()->streamDownload(function () use ($filas) {
            $s = fopen('php://output','w');
            fwrite($s,"\xEF\xBB\xBF");
            fputcsv($s,[
                'OT','Código','Descripción','Plan MODELO MUESTRA','Plan otros',
                'Plan total','Remitido','Recibido','En tránsito',
                'Salida original Central','Traslados desde locales',
                'Reserva fotos estimada','Recibido comercial estimado',
                'Unidades vendidas','Devoluciones','Unidades netas venta',
                'Venta neta Gs','Comprobantes','Estado conciliación'
            ],';');
            $csvSeguro = function ($v) {
                $v = (string) $v;
                return preg_match('/^\s*[=+\-@]/u',$v) ? "'" . $v : $v;
            };
            foreach($filas as $f) {
                fputcsv($s,[
                    $csvSeguro($f->nro_ot ?: 'SIN OT'),$csvSeguro($f->codigo),
                    $csvSeguro($f->descripcion),$f->plan_modelo,$f->plan_mayorista,
                    $f->plan,$f->remitido,$f->recibido,$f->transito,
                    $f->salida_central,$f->traslado,$f->reserva_foto,
                    $f->recibido_venta_estimado,$f->vendidas,$f->devoluciones,
                    $f->ventas_netas,round($f->venta_neta,2),$f->comprobantes,
                    $csvSeguro($f->status)
                ],';');
            }
            fclose($s);
        },'mayoristas_comercial_matriz_'.$desde.'_'.$hasta.'.csv',[
            'Content-Type'=>'text/csv; charset=UTF-8'
        ]);
    }
}
