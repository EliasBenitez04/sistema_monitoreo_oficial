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

        /*
         * Detección masiva de candidatas, solo para orientación en el
         * listado: NUNCA reserva ni vincula automáticamente una remisión.
         * No se suman estas coberturas entre facturas, porque diferentes
         * compradores pueden estar viendo la misma mercadería candidata.
         *
         * Se exige código COMPLETO, remisión emitida y recepción
         * no posteriores a la fecha de la factura. Los movimientos
         * posteriores se investigan en la ficha detallada.
         */
        $necesidadesPorFactura = [];
        $codigosMes = [];
        $lineasParaCruce = self::ventas()
            ->whereBetween('v.fecha',[$desde,$hasta])
            ->where('v.cantidad','>',0)
            ->select('v.fecha','v.comprobante','v.cli_cod','v.cliente','v.codigo')
            ->selectRaw('SUM(v.cantidad) as prendas')
            ->groupBy('v.fecha','v.comprobante','v.cli_cod','v.cliente','v.codigo')
            ->get();
        foreach($lineasParaCruce as $linea) {
            $clave=Conciliador::claveFactura($linea->fecha,$linea->comprobante,
                $linea->cli_cod,$linea->cliente);
            $codigo=Conciliador::codigo($linea->codigo);
            if($codigo==='') continue;
            if(!isset($necesidadesPorFactura[$clave])) $necesidadesPorFactura[$clave]=[];
            $necesidadesPorFactura[$clave][$codigo]=
                ($necesidadesPorFactura[$clave][$codigo]??0)+(int)$linea->prendas;
            $codigosMes[$codigo]=true;
        }

        $usosPorFactura=[];
        $usadosPorCodigo=$claves?DB::table('mayorista_vinculos')
            ->whereIn('factura_clave',$claves)
            ->select('factura_clave','codigo')
            ->selectRaw('SUM(cantidad) as prendas')
            ->groupBy('factura_clave','codigo')->get():collect();
        foreach($usadosPorCodigo as $uso) {
            $usosPorFactura[$uso->factura_clave][Conciliador::codigo($uso->codigo)]=
                (int)$uso->prendas;
        }

        // Clave de documento físico: las remisiones tienen muchas líneas
        // por artículo/talle, que no deben mostrarse como documentos distintos.
        $claveRemisionSql="COALESCE(r.serie,'') || '|' || COALESCE(r.numero_remision,'')"
            ." || '|' || COALESCE(CAST(r.cod_sucursal_salida AS TEXT),'')"
            ." || '|' || COALESCE(CAST(r.cod_sucursal_destino AS TEXT),'')";
        $usadasRemision=DB::table('mayorista_vinculos')
            ->select('remision_id')->selectRaw('SUM(cantidad) as usadas')
            ->groupBy('remision_id');
        $candidatasPorCodigo=[];
        $codigosCruce=array_keys($codigosMes);
        foreach(array_chunk($codigosCruce,250) as $loteCodigos) {
            $resultados=self::entradas()
                ->leftJoinSub($usadasRemision,'usos','usos.remision_id','=','r.id')
                ->whereIn(DB::raw('UPPER(BTRIM(r.codigo))'),$loteCodigos)
                ->whereNotNull('r.fecha_remision')
                ->whereNotNull('r.fecha_recepcion')
                ->where('r.fecha_remision','<=',$hasta)
                ->where('r.fecha_recepcion','<=',$hasta)
                ->selectRaw('UPPER(BTRIM(r.codigo)) as codigo')
                ->addSelect('r.fecha_remision','r.fecha_recepcion')
                ->selectRaw("$claveRemisionSql as clave_remision")
                ->selectRaw('SUM(GREATEST(0,r.cantidad - COALESCE(usos.usadas,0))) as prendas_disponibles')
                ->groupByRaw("UPPER(BTRIM(r.codigo)), r.fecha_remision, r.fecha_recepcion, $claveRemisionSql")
                ->get();
            foreach($resultados as $grupo) {
                if((int)$grupo->prendas_disponibles <= 0) continue;
                $candidatasPorCodigo[$grupo->codigo][]=$grupo;
            }
        }

        foreach($facturas as $factura) {
            $factura->documentos_recientes=0;
            $factura->documentos_historicos=0;
            $factura->cobertura_reciente=0;
            $factura->cobertura_historica=0;
            $factura->cobertura_candidata=0;
            $factura->pendiente_sin_candidata=0;
            $requeridas=$necesidadesPorFactura[$factura->clave]??[];
            $corteRecientes=Carbon::parse($factura->fecha)->subDays(30)->format('Y-m-d');
            $docsRecientes=[];
            $docsHistoricos=[];
            foreach($requeridas as $codigo=>$cantidad) {
                $pendienteCodigo=max(0,$cantidad-
                    (int)($usosPorFactura[$factura->clave][$codigo]??0));
                if($pendienteCodigo===0) continue;
                $recientes=0;
                $historicas=0;
                foreach(($candidatasPorCodigo[$codigo]??[]) as $grupo) {
                    if($grupo->fecha_remision > $factura->fecha
                        || $grupo->fecha_recepcion > $factura->fecha) continue;
                    if($grupo->fecha_remision >= $corteRecientes) {
                        $recientes+=(int)$grupo->prendas_disponibles;
                        $docsRecientes[$grupo->clave_remision]=true;
                    } else {
                        $historicas+=(int)$grupo->prendas_disponibles;
                        $docsHistoricos[$grupo->clave_remision]=true;
                    }
                }
                $recienteCubierta=min($pendienteCodigo,$recientes);
                $historicaCubierta=min(max(0,$pendienteCodigo-$recienteCubierta),$historicas);
                $factura->cobertura_reciente+=$recienteCubierta;
                $factura->cobertura_historica+=$historicaCubierta;
                $factura->cobertura_candidata+=$recienteCubierta+$historicaCubierta;
                $factura->pendiente_sin_candidata+=max(0,$pendienteCodigo-$recienteCubierta-$historicaCubierta);
            }
            $factura->documentos_recientes=count($docsRecientes);
            $factura->documentos_historicos=count($docsHistoricos);
            $factura->situacion_busqueda=$factura->pendientes===0
                ? 'CONCILIADA'
                :($factura->cobertura_reciente >= $factura->pendientes
                    ? 'COBERTURA RECIENTE POSIBLE'
                    :($factura->cobertura_candidata >= $factura->pendientes
                        ? 'REQUIERE HISTORIAL'
                        :($factura->cobertura_candidata>0
                            ? 'COBERTURA PARCIAL'
                            : 'SIN CANDIDATAS PREVIAS')));
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

        // Resumen ejecutivo para mostrar avance comprobable y definir qué
        // facturas auditar primero. Las coberturas candidatas NO se suman
        // como prendas asignadas: podrían usar el mismo documento.
        $facturasVenta=$facturas->filter(function($f) {
            return (int)$f->brutas>0;
        });
        $facturasPrioridad=$facturasVenta->sortByDesc('brutas')->take(5)->values();
        $prioritariasDos=$facturasPrioridad->take(2);
        $avanceGerencial=(object)[
            'facturas_venta'=>$facturasVenta->count(),
            'notas_credito'=>$facturas->count()-$facturasVenta->count(),
            'facturas_confirmadas'=>$facturasVenta->filter(function($f) {
                return $f->estado==='CONFIRMADA DOCUMENTALMENTE';
            })->count(),
            'facturas_parciales'=>$facturasVenta->filter(function($f) {
                return $f->estado==='PARCIAL';
            })->count(),
            'facturas_sin_validar'=>$facturasVenta->filter(function($f) {
                return $f->estado==='SIN VALIDAR';
            })->count(),
            'facturas_cobertura_reciente'=>$facturasVenta->filter(function($f) {
                return $f->cobertura_reciente>0;
            })->count(),
            'top_dos_prendas'=>(int)$prioritariasDos->sum('brutas'),
            'top_dos_porcentaje'=>$resumen->brutas>0
                ? round($prioritariasDos->sum('brutas')*100/$resumen->brutas,1) : 0,
            'avance_porcentaje'=>$resumen->brutas>0
                ? round($resumen->vinculadas*100/$resumen->brutas,1) : null,
        ];

        $emitidas=self::entradas()->whereBetween('r.fecha_remision',[$desde,$hasta])
            ->selectRaw("COUNT(DISTINCT $claveRemisionSql) as documentos, COUNT(*) as lineas, COALESCE(SUM(r.cantidad),0) as prendas")
            ->selectRaw("COALESCE(SUM(CASE WHEN UPPER(BTRIM(COALESCE(r.sucursal_salida,''))) IN ('CASA CENTRAL','MATRIZ') OR r.cod_sucursal_salida=1 THEN r.cantidad ELSE 0 END),0) as central")
            ->first();
        $recibidas=self::entradas()->whereBetween('r.fecha_recepcion',[$desde,$hasta])
            ->selectRaw("COUNT(DISTINCT $claveRemisionSql) as documentos, COUNT(*) as lineas, COALESCE(SUM(r.cantidad),0) as prendas")
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
        // Las diferencias del período ayudan a priorizar una investigación,
        // pero nunca equivalen a faltante físico o saldo final confirmado.
        $conDiferencia=$saldos->filter(function($fila) {
            return (int)$fila->entrada !== (int)$fila->venta;
        })->sortByDesc(function($fila) {
            return abs((int)$fila->entrada-(int)$fila->venta);
        })->values();
        $verTodosSaldos=$request->query('ver_saldos')==='todos';
        $saldosVisibles=$verTodosSaldos ? $saldos : (
            $conDiferencia->isNotEmpty() ? $conDiferencia->take(40) : $saldos->take(40)
        );
        $cantidadDiferencias=$conDiferencia->count();
        $cierre=$saldos->isNotEmpty() && $sinSaldo===0?$saldos->sum('cierre'):null;

        // Comparación de los seis meses anteriores hasta el seleccionado.
        // Las columnas son hechos fechados independientes, no stock acumulado.
        $mesesComparacion=collect(range(0,5))->map(function($i)use($mes) {
            return Carbon::parse($mes.'-01')->subMonths($i)->format('Y-m');
        });
        list($inicioComparacion,)=self::rango($mesesComparacion->last());
        $mesSepSql="TO_CHAR(fecha_separacion::timestamp,'YYYY-MM')";
        $separadosMes=DB::table('mayorista_separaciones')
            ->whereBetween('fecha_separacion',[$inicioComparacion,$hasta])
            ->selectRaw("$mesSepSql as mes, SUM(cantidad) as prendas")
            ->groupByRaw($mesSepSql)->pluck('prendas','mes');
        $mesEmitSql="TO_CHAR(r.fecha_remision::timestamp,'YYYY-MM')";
        $emitidosMes=self::entradas()->whereBetween('r.fecha_remision',[$inicioComparacion,$hasta])
            ->selectRaw("$mesEmitSql as mes, SUM(r.cantidad) as prendas")
            ->groupByRaw($mesEmitSql)->pluck('prendas','mes');
        $mesRecepSql="TO_CHAR(r.fecha_recepcion::timestamp,'YYYY-MM')";
        $recibidosMes=self::entradas()->whereBetween('r.fecha_recepcion',[$inicioComparacion,$hasta])
            ->selectRaw("$mesRecepSql as mes, SUM(r.cantidad) as prendas")
            ->groupByRaw($mesRecepSql)->pluck('prendas','mes');
        $mesVentaSql="TO_CHAR(v.fecha::timestamp,'YYYY-MM')";
        $ventasMes=self::ventas()->whereBetween('v.fecha',[$inicioComparacion,$hasta])
            ->selectRaw("$mesVentaSql as mes, SUM(v.cantidad) as prendas")
            ->groupByRaw($mesVentaSql)->pluck('prendas','mes');
        $resumenMeses=$mesesComparacion->map(function($mesFila)use(
            $separadosMes,$emitidosMes,$recibidosMes,$ventasMes,$mes,$cierre) {
            return (object)[
                'mes'=>$mesFila,
                'separadas'=>(int)($separadosMes[$mesFila]??0),
                'emitidas'=>(int)($emitidosMes[$mesFila]??0),
                'recibidas'=>(int)($recibidosMes[$mesFila]??0),
                'facturadas'=>(int)($ventasMes[$mesFila]??0),
                'cierre'=>$mesFila===$mes?$cierre:null,
                'es_actual'=>$mesFila===$mes
            ];
        });

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
        // Filtros de investigación: nunca se convierten en vínculos
        // confirmados. Una remisión o una OT filtra facturas por códigos
        // candidatos, no acredita su procedencia.
        $filtroFactura=trim((string)$request->query('factura',''));
        $filtroCliente=trim((string)$request->query('cliente',''));
        $filtroCodigo=trim((string)$request->query('codigo',''));
        $filtroRemision=trim((string)$request->query('remision',''));
        $filtroOt=trim((string)$request->query('ot',''));
        $filtroEstado=trim((string)$request->query('estado',''));
        if($filtroFactura!=='') {
            $facturas=$facturas->filter(function($f)use($filtroFactura) {
                return stripos((string)$f->comprobante,$filtroFactura)!==false;
            });
        }
        if($filtroCliente!=='') {
            $facturas=$facturas->filter(function($f)use($filtroCliente) {
                return stripos((string)$f->cliente,$filtroCliente)!==false
                    || stripos((string)$f->cli_cod,$filtroCliente)!==false;
            });
        }
        if($filtroEstado!=='') {
            $facturas=$facturas->filter(function($f)use($filtroEstado) {
                return $f->estado===$filtroEstado;
            });
        }
        $codigosCandidatos=null;
        if($filtroRemision!=='') {
            $codigosCandidatos=self::entradas()->where(function($q)use($filtroRemision) {
                $q->where('r.numero_remision','ILIKE','%'.$filtroRemision.'%')
                  ->orWhere('r.serie','ILIKE','%'.$filtroRemision.'%');
            })->distinct()->pluck('r.codigo')->map(function($x) {
                return Conciliador::codigo($x);
            })->all();
        }
        if($filtroOt!=='') {
            $codigosOt=self::entradas()
                ->leftJoin('ot_logistica_detalle as d','d.id','=','r.id_logistica_detalle')
                ->leftJoin('ot as o',function($join) {
                    $join->on('o.id_ot','=',DB::raw('COALESCE(r.id_ot,d.id_ot)'));
                })
                ->whereRaw('CAST(o.nro_ot AS TEXT) ILIKE ?',['%'.$filtroOt.'%'])
                ->distinct()->pluck('r.codigo')->map(function($x) {
                    return Conciliador::codigo($x);
                })->all();
            $codigosCandidatos=$codigosCandidatos===null?$codigosOt:
                array_values(array_intersect($codigosCandidatos,$codigosOt));
        }
        if($filtroCodigo!=='' || $codigosCandidatos!==null) {
            $qCodigo=self::ventas()->whereBetween('v.fecha',[$desde,$hasta]);
            if($filtroCodigo!=='') {
                $qCodigo->where('v.codigo','ILIKE','%'.$filtroCodigo.'%');
            }
            if($codigosCandidatos!==null) {
                if($codigosCandidatos) {
                    $qCodigo->whereIn(DB::raw('UPPER(BTRIM(v.codigo))'),$codigosCandidatos);
                } else {
                    $qCodigo->whereRaw('1 = 0');
                }
            }
            $clavesPermitidas=$qCodigo->distinct()
                ->select('v.fecha','v.comprobante','v.cli_cod','v.cliente')->get()
                ->map(function($v) {
                    return Conciliador::claveFactura($v->fecha,$v->comprobante,$v->cli_cod,$v->cliente);
                })->unique()->all();
            $facturas=$facturas->filter(function($f)use($clavesPermitidas) {
                return in_array($f->clave,$clavesPermitidas,true);
            });
        }
        $facturas=$facturas->values();

        if($request->query('exportar')==='csv') {
            return $this->csv($facturas,$mes);
        }
        if($request->query('exportar')==='detalle') {
            return $this->csvDetalle($facturas,$mes);
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
            'muestrasPorOt','reservaFotoEstimada','planBuscar','resumenMeses',
            'filtroFactura','filtroCliente','filtroCodigo','filtroRemision','filtroOt','filtroEstado',
            'saldosVisibles','verTodosSaldos','cantidadDiferencias',
            'avanceGerencial','facturasPrioridad'
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
        if ($estado==='SIN COINCIDENCIA' && $candidatasLimitadas) {
            $estado='BÚSQUEDA INCOMPLETA';
        } elseif($estado==='SIN COINCIDENCIA' && $candidatas->isNotEmpty()) {
            $estado='SIN COBERTURA CONFIRMADA';
        }
        $porCodigo=collect($requeridas)->map(function($necesita,$codigo)use($confirmadas) {
            $validado=(int)($confirmadas[$codigo]??0);
            return (object)['codigo'=>$codigo,'necesita'=>$necesita,'validado'=>$validado,
                'pendiente'=>max(0,$necesita-$validado)];
        });
        // Carpeta inicial de auditoría: documentos próximos a la factura
        // agrupados por número/serie/origen/destino y fecha. La cifra
        // aportable se calcula por código completo y NO constituye
        // asignación ni puede sumarse entre documentos sin conciliar.
        $documentosSugeridos=collect();
        if($requeridas) {
            $usadoRem=DB::table('mayorista_vinculos')
                ->select('remision_id')->selectRaw('SUM(cantidad) as usadas')
                ->groupBy('remision_id');
            $docs=self::entradas()->leftJoinSub($usadoRem,'usos','usos.remision_id','=','r.id')
                ->whereNotNull('r.fecha_remision')
                ->whereNotNull('r.fecha_recepcion')
                ->whereBetween('r.fecha_remision',[
                    Carbon::parse($factura->fecha)->subDays(30)->format('Y-m-d'),
                    $factura->fecha
                ])
                ->where('r.fecha_recepcion','<=',$factura->fecha)
                ->whereIn(DB::raw('UPPER(BTRIM(r.codigo))'),array_keys($requeridas))
                ->select('r.serie','r.numero_remision','r.sucursal_salida',
                    'r.sucursal_destino','r.fecha_remision')
                ->selectRaw('MIN(r.id) as primera_linea')
                ->selectRaw('UPPER(BTRIM(r.codigo)) as codigo')
                ->selectRaw('SUM(GREATEST(0,r.cantidad-COALESCE(usos.usadas,0))) as disponibles')
                ->groupBy('r.serie','r.numero_remision','r.sucursal_salida',
                    'r.sucursal_destino','r.fecha_remision')
                ->groupByRaw('UPPER(BTRIM(r.codigo))')
                ->get();

            $agrupados=[];
            foreach($docs as $doc) {
                $codigo=(string)$doc->codigo;
                $pendiente=isset($porCodigo[$codigo])?(int)$porCodigo[$codigo]->pendiente:0;
                $aporte=min($pendiente,max(0,(int)$doc->disponibles));
                if($aporte<=0)continue;
                $claveDocumento=implode('|',[
                    $doc->serie,$doc->numero_remision,$doc->sucursal_salida,
                    $doc->sucursal_destino,$doc->fecha_remision
                ]);
                if($doc->numero_remision===null) {
                    $claveDocumento.='|SIN_NUMERO|'.$doc->primera_linea;
                }
                if(!isset($agrupados[$claveDocumento])) {
                    $agrupados[$claveDocumento]=(object)[
                        'serie'=>$doc->serie,'numero'=>$doc->numero_remision,
                        'origen'=>$doc->sucursal_salida,'destino'=>$doc->sucursal_destino,
                        'fecha'=>$doc->fecha_remision,
                        'aporte'=>0,'codigos'=>0,'ejemplo_codigo'=>$codigo
                    ];
                }
                $agrupados[$claveDocumento]->aporte+=$aporte;
                $agrupados[$claveDocumento]->codigos++;
            }
            $documentosSugeridos=collect(array_values($agrupados))
                ->sortByDesc('aporte')->take(15)->values();
        }
        return view('ventas.mayoristas_factura',compact(
            'mes','factura','lineas','requeridas','enlaces','confirmadas',
            'candidatas','candidatasLimitadas','porCodigo','estado','auditoria',
            'documentosSugeridos'
        ));
    }

    private function csvDetalle($facturas,string $mes)
    {
        return response()->streamDownload(function()use($facturas) {
            $f=fopen('php://output','w');
            fwrite($f,"\xEF\xBB\xBF");
            fputcsv($f,['Mes venta','Fecha factura','Factura','Cliente','Código completo',
                'Artículo','Unidades positivas','Devoluciones','Código remisión',
                'Remisión','Fecha remisión','Origen','Destino','Unidades vinculadas',
                'Unidades pendientes','Referencia auditoría','Estado documental'],';');
            $safe=function($value) {
                $value=(string)$value;
                return preg_match('/^\\s*[=+\\-@]/u',$value)?"'".$value:$value;
            };
            foreach($facturas as $factura) {
                $lineas=self::lineas($factura)->groupBy(function($v) {
                    return Conciliador::codigo($v->codigo);
                });
                $vinculadas=DB::table('mayorista_vinculos as m')
                    ->join('ot_logistica_remisiones as r','r.id','=','m.remision_id')
                    ->where('m.factura_clave',$factura->clave)
                    ->select('m.codigo','m.cantidad','m.observacion','r.codigo as codigo_rem',
                        'r.serie','r.numero_remision','r.fecha_remision',
                        'r.sucursal_salida','r.sucursal_destino')
                    ->get()->groupBy('codigo');
                foreach($lineas as $codigo=>$articulos) {
                    $brutas=(int)$articulos->filter(function($v){return $v->cantidad>0;})->sum('cantidad');
                    $devoluciones=abs((int)$articulos->filter(function($v){return $v->cantidad<0;})->sum('cantidad'));
                    $links=$vinculadas->get($codigo,collect());
                    $vinculado=(int)$links->sum('cantidad');
                    $pendiente=max(0,$brutas-$vinculado);
                    $base=[$factura->fecha?substr($factura->fecha,0,7):'',
                        $factura->fecha,$safe($factura->comprobante),
                        $safe($factura->cliente),$safe($codigo),
                        $safe($articulos->first()->descripcion),$brutas,$devoluciones];
                    if($links->isEmpty()) {
                        fputcsv($f,array_merge($base,
                            ['','','','','',0,$pendiente,'','SIN VÍNCULO']),';');
                    } else {
                        foreach($links as $link) {
                            fputcsv($f,array_merge($base,[
                                $safe($link->codigo_rem),
                                $safe($link->serie.'-'.$link->numero_remision),
                                $link->fecha_remision,
                                $safe($link->sucursal_salida),
                                $safe($link->sucursal_destino),
                                $link->cantidad,$pendiente,
                                $safe($link->observacion),
                                $pendiente===0?'CONFIRMADA DOCUMENTALMENTE':'PARCIAL'
                            ]),';');
                        }
                    }
                }
            }
            fclose($f);
        },'mayoristas_facturas_remisiones_'.$mes.'.csv',
        ['Content-Type'=>'text/csv; charset=UTF-8']);
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
