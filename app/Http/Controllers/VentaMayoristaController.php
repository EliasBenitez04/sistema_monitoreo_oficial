<?php

namespace App\Http\Controllers;

use App\Support\MayoristaConciliacion as Conciliador;
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

    public static function entradas()
    {
        return DB::table('ot_logistica_remisiones as r')
            ->where(function ($q) {
                $q->whereRaw("UPPER(BTRIM(COALESCE(r.sucursal_destino,''))) = 'COMERCIAL MATRIZ'")
                  ->orWhereRaw("UPPER(BTRIM(COALESCE(r.sucursal_logistica,''))) = 'COMERCIAL MATRIZ'");
            })
            ->whereRaw("UPPER(BTRIM(COALESCE(r.sucursal_salida,''))) <> 'COMERCIAL MATRIZ'");
    }

    public static function ventas()
    {
        return DB::table('ventas as v')
            ->whereRaw("UPPER(BTRIM(COALESCE(v.local,''))) = 'COMERCIAL MATRIZ'");
    }

    public static function rango(string $mes): array
    {
        $inicio = Carbon::parse($mes . '-01')->startOfMonth();
        return [$inicio->format('Y-m-d'), $inicio->copy()->endOfMonth()->format('Y-m-d')];
    }

    public static function mes(Request $request): string
    {
        $mes = (string) $request->query('mes', '');
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes)) {
            return $mes;
        }
        $ultima = self::ventas()->max('v.fecha');
        return $ultima ? substr((string) $ultima, 0, 7) : Carbon::today()->format('Y-m');
    }

    public static function facturas(string $mes)
    {
        list($desde, $hasta) = self::rango($mes);
        return self::ventas()->whereBetween('v.fecha', [$desde, $hasta])
            ->select('v.fecha','v.comprobante','v.cli_cod','v.cliente','v.tipo_comprobante')
            ->selectRaw('SUM(CASE WHEN v.cantidad > 0 THEN v.cantidad ELSE 0 END) as brutas')
            ->selectRaw('SUM(CASE WHEN v.cantidad < 0 THEN -v.cantidad ELSE 0 END) as devoluciones')
            ->selectRaw('COALESCE(SUM(v.cantidad),0) as netas, COALESCE(SUM(v.p_venta),0) as pvta')
            ->selectRaw('COUNT(*) as lineas, COUNT(DISTINCT v.codigo) as articulos')
            ->groupBy('v.fecha','v.comprobante','v.cli_cod','v.cliente','v.tipo_comprobante')
            ->orderBy('v.fecha','desc')->orderBy('v.comprobante','desc')
            ->get()->map(function ($f) {
                $f->clave = Conciliador::claveFactura($f->fecha,$f->comprobante,$f->cli_cod,$f->cliente);
                return $f;
            });
    }

    public static function buscarFactura(string $clave,string $mes)
    {
        $factura = self::facturas($mes)->first(function($f)use($clave) {
            return hash_equals($f->clave,$clave);
        });
        abort_unless($factura,404,'Factura mayorista no encontrada en el mes seleccionado.');
        return $factura;
    }

    public static function lineas($factura)
    {
        return self::ventas()->where('v.fecha',$factura->fecha)
            ->where('v.comprobante',$factura->comprobante)
            ->whereRaw("COALESCE(BTRIM(v.cli_cod),'') = ?",[trim((string)$factura->cli_cod)])
            ->whereRaw("COALESCE(BTRIM(v.cliente),'') = ?",[trim((string)$factura->cliente)])
            ->select('v.id','v.codigo','v.descripcion','v.cantidad','v.p_lista','v.descuento','v.p_venta')
            ->orderBy('v.codigo')->get();
    }

    public static function necesidades($lineas): array
    {
        $requeridas=[];
        foreach($lineas as $linea) {
            $codigo=Conciliador::codigo($linea->codigo);
            if($codigo!=='' && (int)$linea->cantidad>0) {
                $requeridas[$codigo]=($requeridas[$codigo]??0)+(int)$linea->cantidad;
            }
        }
        return $requeridas;
    }

    public function index(Request $request)
    {
        if (!Schema::hasTable('ventas') || !Schema::hasTable('ot_logistica_remisiones')) {
            return view('ventas.mayoristas',['sinFuentes'=>true]);
        }
        $mes=self::mes($request);
        list($desde,$hasta)=self::rango($mes);
        $facturas=self::facturas($mes);
        $claves=$facturas->pluck('clave')->all();
        $vinculos=$claves?DB::table('mayorista_vinculos')->whereIn('factura_clave',$claves)
            ->select('factura_clave')->selectRaw('SUM(cantidad) as cantidad')
            ->groupBy('factura_clave')->pluck('cantidad','factura_clave'):collect();
        foreach($facturas as $f) {
            $f->vinculadas=(int)($vinculos[$f->clave]??0);
            $f->pendientes=max(0,(int)$f->brutas-$f->vinculadas);
            $f->estado=(int)$f->brutas===0?'NOTA DE CRÉDITO'
                :($f->pendientes===0?'CONFIRMADA DOCUMENTALMENTE'
                    :($f->vinculadas>0?'PARCIAL':'SIN VALIDAR'));
        }

        $resumen=(object)[
            'facturas'=>$facturas->count(),
            'brutas'=>$facturas->sum('brutas'),
            'devoluciones'=>$facturas->sum('devoluciones'),
            'netas'=>$facturas->sum('netas'),
            'pvta'=>$facturas->sum('pvta'),
            'vinculadas'=>$facturas->sum('vinculadas'),
            'pendientes'=>$facturas->sum('pendientes'),
            'facturas_pendientes'=>$facturas->where('pendientes','>',0)->count()
        ];

        $emitidas=self::entradas()->whereBetween('r.fecha_remision',[$desde,$hasta])
            ->selectRaw('COUNT(*) as documentos, COALESCE(SUM(r.cantidad),0) as prendas')
            ->selectRaw("COALESCE(SUM(CASE WHEN UPPER(BTRIM(COALESCE(r.sucursal_salida,''))) IN ('CASA CENTRAL','MATRIZ') OR r.cod_sucursal_salida=1 THEN r.cantidad ELSE 0 END),0) as central")
            ->first();
        $recibidas=self::entradas()->whereBetween('r.fecha_recepcion',[$desde,$hasta])
            ->selectRaw('COUNT(*) as documentos, COALESCE(SUM(r.cantidad),0) as prendas')
            ->selectRaw("COALESCE(SUM(CASE WHEN UPPER(BTRIM(COALESCE(r.sucursal_salida,''))) IN ('CASA CENTRAL','MATRIZ') OR r.cod_sucursal_salida=1 THEN r.cantidad ELSE 0 END),0) as central")
            ->first();

        $separaciones=DB::table('mayorista_separaciones')
            ->whereBetween('fecha_separacion',[$desde,$hasta])
            ->selectRaw('COUNT(*) as registros, COALESCE(SUM(cantidad),0) as prendas')->first();
        $muestrasPorOt=min(20,max(0,(int)$request->query('muestras_por_ot',1)));
        $fotoPorOt=DB::table('mayorista_separaciones as s')
            ->join('ot_logistica_detalle as d','d.id','=','s.logistica_detalle_id')
            ->whereBetween('s.fecha_separacion',[$desde,$hasta])
            ->whereRaw("UPPER(BTRIM(d.sucursal)) LIKE '%MODELO%MUESTRA%'")
            ->select('s.id_ot')->selectRaw('SUM(s.cantidad) as cantidad')
            ->groupBy('s.id_ot')->get();
        $reservaFotoEstimada=$fotoPorOt->sum(function($ot)use($muestrasPorOt) {
            return min($muestrasPorOt,(int)$ot->cantidad);
        });
        $separacionesDetalle=DB::table('mayorista_separaciones as s')
            ->leftJoin('ot as o','o.id_ot','=','s.id_ot')
            ->whereBetween('s.fecha_separacion',[$desde,$hasta])
            ->select('s.*','o.nro_ot','o.codigo')
            ->orderBy('s.fecha_separacion','desc')->limit(30)->get();

        // No inventar un saldo inicial de cero: exige declaración por
        // cada código que tuvo ingresos o movimientos comerciales.
        $aperturas=DB::table('mayorista_saldos_iniciales')->where('mes',$desde)
            ->get()->keyBy('codigo');
        $entradasCodigo=self::entradas()->whereBetween('r.fecha_recepcion',[$desde,$hasta])
            ->selectRaw('UPPER(BTRIM(r.codigo)) as codigo, SUM(r.cantidad) as prendas')
            ->groupByRaw('UPPER(BTRIM(r.codigo))')->pluck('prendas','codigo');
        $ventasCodigo=self::ventas()->whereBetween('v.fecha',[$desde,$hasta])
            ->selectRaw('UPPER(BTRIM(v.codigo)) as codigo, SUM(v.cantidad) as prendas')
            ->groupByRaw('UPPER(BTRIM(v.codigo))')->pluck('prendas','codigo');
        $codigos=$entradasCodigo->keys()->merge($ventasCodigo->keys())
            ->merge($aperturas->keys())->unique()->filter();
        $saldos=$codigos->map(function($codigo)use($aperturas,$entradasCodigo,$ventasCodigo) {
            $apertura=$aperturas->has($codigo)?(int)$aperturas[$codigo]->cantidad:null;
            $entrada=(int)($entradasCodigo[$codigo]??0);
            $venta=(int)($ventasCodigo[$codigo]??0);
            return (object)[
                'codigo'=>$codigo,'inicial'=>$apertura,'entrada'=>$entrada,
                'venta'=>$venta,
                'cierre'=>Conciliador::saldoDocumental($apertura,$entrada,$venta)
            ];
        })->sortBy('codigo')->values();
        $sinSaldo=$saldos->filter(function($fila) {
            return $fila->inicial===null;
        })->count();
        $cierre=$saldos->isNotEmpty() && $sinSaldo===0?$saldos->sum('cierre'):null;

        $arrastres=self::entradas()
            ->leftJoin('ot_logistica_detalle as d','d.id','=','r.id_logistica_detalle')
            ->leftJoin('ot as o',function($join) {
                $join->on('o.id_ot','=',DB::raw('COALESCE(r.id_ot,d.id_ot)'));
            })
            ->where('r.fecha_remision','<',$desde)
            ->whereNotNull('r.fecha_recepcion')->where('r.fecha_recepcion','<',$desde)
            ->select('r.id','r.fecha_remision','r.codigo','r.cantidad','r.serie',
                'r.numero_remision','r.sucursal_salida','o.nro_ot')
            ->orderBy('r.fecha_remision','desc')->limit(100)->get();
        $ids=$arrastres->pluck('id')->all();
        $usos=$ids?DB::table('mayorista_vinculos')->whereIn('remision_id',$ids)
            ->select('remision_id')->selectRaw('SUM(cantidad) as usado')
            ->groupBy('remision_id')->pluck('usado','remision_id'):collect();
        $arrastres=$arrastres->map(function($r)use($usos) {
            $r->sin_vincular=max(0,(int)$r->cantidad-(int)($usos[$r->id]??0));
            return $r;
        })->where('sin_vincular','>',0)->take(30)->values();

        $planBuscar=trim((string)$request->query('ot_plan',''));
        $planes=collect();
        if(Schema::hasTable('ot_logistica_detalle') && Schema::hasTable('ot')) {
            $planesQuery=DB::table('ot_logistica_detalle as d')
                ->join('ot as o','o.id_ot','=','d.id_ot')
                ->where(function($q) {
                    $q->whereRaw("UPPER(BTRIM(d.sucursal)) LIKE '%MODELO%MUESTRA%'")
                        ->orWhereRaw("UPPER(BTRIM(d.sucursal)) LIKE '%MAYORISTA%'");
                });
            if($planBuscar!=='') {
                $planesQuery->where(function($q)use($planBuscar) {
                    $q->whereRaw('CAST(o.nro_ot AS TEXT) ILIKE ?',['%'.$planBuscar.'%'])
                      ->orWhere('o.codigo','ILIKE','%'.$planBuscar.'%');
                });
            }
            $planes=$planesQuery->select('d.id','d.id_ot','d.sucursal','d.cantidad','o.nro_ot','o.codigo')
                ->orderBy('d.id','desc')->limit(60)->get();
            $planIds=$planes->pluck('id')->all();
            $declaradas=$planIds?DB::table('mayorista_separaciones')
                ->whereIn('logistica_detalle_id',$planIds)
                ->select('logistica_detalle_id')->selectRaw('SUM(cantidad) as cantidad')
                ->groupBy('logistica_detalle_id')->pluck('cantidad','logistica_detalle_id'):collect();
            foreach($planes as $p) {
                $p->pendiente=max(0,(int)$p->cantidad-(int)($declaradas[$p->id]??0));
            }
        }

        $buscar=trim((string)$request->query('buscar',''));
        if($buscar!=='') {
            $facturas=$facturas->filter(function($f)use($buscar) {
                return stripos((string)$f->comprobante,$buscar)!==false
                    || stripos((string)$f->cliente,$buscar)!==false
                    || stripos((string)$f->cli_cod,$buscar)!==false;
            })->values();
        }
        if($request->query('exportar')==='csv') {
            return $this->csv($facturas,$mes);
        }
        $pagina=max(1,(int)$request->query('page',1));
        $facturasPaginadas=new LengthAwarePaginator(
            $facturas->slice(($pagina-1)*30,30)->values(),
            $facturas->count(),30,$pagina,
            ['path'=>$request->url(),'query'=>$request->query()]
        );
        return view('ventas.mayoristas',compact(
            'mes','desde','hasta','resumen','emitidas','recibidas',
            'separaciones','separacionesDetalle','saldos','sinSaldo','cierre',
            'arrastres','planes','buscar','facturasPaginadas',
            'muestrasPorOt','reservaFotoEstimada','planBuscar'
        ));
    }

    public function factura(Request $request,string $clave)
    {
        $mes=self::mes($request);
        $factura=self::buscarFactura($clave,$mes);
        $lineas=self::lineas($factura);
        $requeridas=self::necesidades($lineas);
        $enlaces=DB::table('mayorista_vinculos as m')
            ->join('ot_logistica_remisiones as r','r.id','=','m.remision_id')
            ->where('m.factura_clave',$clave)
            ->select('m.*','r.serie','r.numero_remision','r.fecha_remision',
                'r.sucursal_salida','r.sucursal_destino','r.cantidad as remision_total')
            ->orderBy('m.created_at')->get();
        $confirmadas=[];
        foreach($enlaces as $v) {
            $c=Conciliador::codigo($v->codigo);
            $confirmadas[$c]=($confirmadas[$c]??0)+(int)$v->cantidad;
        }
        $candidatas=collect();
        $candidatasLimitadas=false;
        if($requeridas) {
            $q=self::entradas()
                ->leftJoin('ot_logistica_detalle as d','d.id','=','r.id_logistica_detalle')
                ->leftJoin('ot as o',function($join) {
                    $join->on('o.id_ot','=',DB::raw('COALESCE(r.id_ot,d.id_ot)'));
                })
                ->whereIn(DB::raw('UPPER(BTRIM(r.codigo))'),array_keys($requeridas))
                ->whereNotNull('r.fecha_remision')
                ->where('r.fecha_remision','<=',Carbon::parse($factura->fecha)->addDays(3)->format('Y-m-d'))
                ->select('r.id','r.codigo','r.descripcion','r.fecha_remision','r.fecha_recepcion',
                    'r.cantidad','r.serie','r.numero_remision','r.sucursal_salida',
                    'r.sucursal_destino','r.sucursal_logistica','o.nro_ot')
                ->selectRaw('COALESCE(r.id_ot,d.id_ot) as id_ot_resuelta')
                ->orderBy('r.fecha_remision','desc')->orderBy('r.id','desc');
            $candidatasLimitadas=(clone $q)->count()>300;
            $candidatas=$q->limit(300)->get();
            $ids=$candidatas->pluck('id')->all();
            $asignado=$ids?DB::table('mayorista_vinculos')
                ->whereIn('remision_id',$ids)->select('remision_id')
                ->selectRaw('SUM(cantidad) as cantidad')->groupBy('remision_id')
                ->pluck('cantidad','remision_id'):collect();
            foreach($candidatas as $c) {
                $c->codigo_normalizado=Conciliador::codigo($c->codigo);
                $c->disponible=max(0,(int)$c->cantidad-(int)($asignado[$c->id]??0));
                $c->tardia=$c->fecha_remision>$factura->fecha;
            }
        }
        $auditoria=DB::table('mayorista_auditoria')
            ->where('factura_clave',$clave)
            ->orderBy('created_at','desc')->limit(50)->get();
        $estado=Conciliador::estado($requeridas,$confirmadas,
            $candidatas->map(function($c) {
                return ['codigo'=>$c->codigo,'disponible'=>$c->fecha_recepcion?$c->disponible:0];
            })->all());
        $porCodigo=collect($requeridas)->map(function($necesita,$codigo)use($confirmadas) {
            $validado=(int)($confirmadas[$codigo]??0);
            return (object)['codigo'=>$codigo,'necesita'=>$necesita,'validado'=>$validado,
                'pendiente'=>max(0,$necesita-$validado)];
        });
        return view('ventas.mayoristas_factura',compact(
            'mes','factura','lineas','requeridas','enlaces','confirmadas',
            'candidatas','candidatasLimitadas','porCodigo','estado','auditoria'
        ));
    }

    private function csv($facturas,string $mes)
    {
        return response()->streamDownload(function()use($facturas) {
            $f=fopen('php://output','w');
            fwrite($f,"ï»¿");
            fputcsv($f,['Fecha','Comprobante','Cliente','Facturadas',
                'Devoluciones','Neto','PVTA Gs','Vinculadas','Pendientes','Estado'],';');
            foreach($facturas as $v) {
                $safe=function($x) {
                    $x=(string)$x;
                    return preg_match('/^\s*[=+\-@]/u',$x)?"'".$x:$x;
                };
                fputcsv($f,[$v->fecha,$safe($v->comprobante),$safe($v->cliente),
                    $v->brutas,$v->devoluciones,$v->netas,$v->pvta,
                    $v->vinculadas,$v->pendientes,$safe($v->estado)],';');
            }
            fclose($f);
        },'mayoristas_facturas_'.$mes.'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}
