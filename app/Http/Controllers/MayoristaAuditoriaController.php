<?php

namespace App\Http\Controllers;

use App\Support\MayoristaConciliacion as Conciliador;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MayoristaAuditoriaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:pedido_compras index');
    }

    private function auditar(Request $request, string $accion, ?string $factura, ?int $remision,
        ?string $codigo, ?int $cantidad, string $detalle, ?int $separacion = null): void
    {
        DB::table('mayorista_auditoria')->insert([
            'accion'=>$accion,'factura_clave'=>$factura,'remision_id'=>$remision,
            'codigo'=>$codigo,'cantidad'=>$cantidad,'separacion_id'=>$separacion,
            'usuario_id'=>$request->user()->id,'detalle'=>$detalle,
            'created_at'=>now(),'updated_at'=>now()
        ]);
    }

    public function vincular(Request $request, string $clave)
    {
        $datos=$request->validate([
            'mes'=>'required|date_format:Y-m','remision_id'=>'required|integer|min:1',
            'cantidad'=>'required|integer|min:1','referencia'=>'required|string|min:5|max:255'
        ]);
        $factura=VentaMayoristaController::buscarFactura($clave,$datos['mes']);
        $necesidades=VentaMayoristaController::necesidades(VentaMayoristaController::lineas($factura));
        DB::transaction(function()use($request,$clave,$factura,$necesidades,$datos) {
            // Fijar orden de bloqueo por factura y documento impide utilizar
            // el mismo saldo concurrentemente para varios compradores.
            VentaMayoristaController::ventas()
                ->where('v.fecha',$factura->fecha)
                ->where('v.comprobante',$factura->comprobante)
                ->whereRaw("COALESCE(BTRIM(v.cli_cod),'') = ?",[trim((string)$factura->cli_cod)])
                ->whereRaw("COALESCE(BTRIM(v.cliente),'') = ?",[trim((string)$factura->cliente)])
                ->orderBy('v.id')->lockForUpdate()->first();

            $r=VentaMayoristaController::entradas()->where('r.id',$datos['remision_id'])
                ->lockForUpdate()->first();
            if(!$r) {
                throw ValidationException::withMessages(['remision_id'=>'La remisión no es una entrada válida a COMERCIAL MATRIZ.']);
            }
            if(!$r->fecha_recepcion) {
                throw ValidationException::withMessages(['remision_id'=>'La remisión aún no tiene recepción confirmada.']);
            }
            $codigo=Conciliador::codigo($r->codigo);
            if(!isset($necesidades[$codigo])) {
                throw ValidationException::withMessages(['remision_id'=>'El código completo de la remisión no coincide con esta factura.']);
            }
            if($r->fecha_remision>Carbon::parse($factura->fecha)->addDays(3)->format('Y-m-d')) {
                throw ValidationException::withMessages(['remision_id'=>'La remisión es posterior a la ventana de análisis. Requiere revisión independiente.']);
            }
            if(DB::table('mayorista_vinculos')->where('factura_clave',$clave)
                ->where('remision_id',$r->id)->exists()) {
                throw ValidationException::withMessages(['remision_id'=>'Esta remisión ya tiene vínculo con la factura; desvinculá antes de modificar la cantidad.']);
            }
            $ocupado=(int)DB::table('mayorista_vinculos')->where('remision_id',$r->id)->sum('cantidad');
            $facturado=(int)DB::table('mayorista_vinculos')
                ->where('factura_clave',$clave)->where('codigo',$codigo)->sum('cantidad');
            $maximo=Conciliador::capacidad((int)$necesidades[$codigo]-$facturado,
                (int)$r->cantidad-$ocupado);
            if($datos['cantidad']>$maximo) {
                throw ValidationException::withMessages(['cantidad'=>'Máximo disponible de la factura y remisión: '.$maximo.' prendas.']);
            }
            $id=DB::table('mayorista_vinculos')->insertGetId([
                'factura_clave'=>$clave,'comprobante'=>$factura->comprobante,
                'fecha_factura'=>$factura->fecha,'codigo'=>$codigo,
                'remision_id'=>$r->id,'cantidad'=>$datos['cantidad'],
                'usuario_id'=>$request->user()->id,'observacion'=>$datos['referencia'],
                'created_at'=>now(),'updated_at'=>now()
            ]);
            $this->auditar($request,'VINCULAR',$clave,$r->id,$codigo,(int)$datos['cantidad'],
                'Vínculo '.$id.' · '.$datos['referencia']);
        },3);
        return redirect()->route('ventas.mayoristas.factura',['clave'=>$clave,'mes'=>$datos['mes']])
            ->with('success','Relación factura–remisión registrada con cantidades auditadas.');
    }

    public function desvincular(Request $request,string $clave,int $id)
    {
        $datos=$request->validate([
            'mes'=>'required|date_format:Y-m','motivo'=>'required|string|min:5|max:255'
        ]);
        VentaMayoristaController::buscarFactura($clave,$datos['mes']);
        DB::transaction(function()use($request,$clave,$id,$datos) {
            $v=DB::table('mayorista_vinculos')->where('id',$id)
                ->where('factura_clave',$clave)->lockForUpdate()->first();
            abort_unless($v,404);
            $this->auditar($request,'DESVINCULAR',$clave,$v->remision_id,$v->codigo,(int)$v->cantidad,
                'Vínculo '.$v->id.' · '.$datos['motivo']);
            DB::table('mayorista_vinculos')->where('id',$id)->delete();
        },3);
        return back()->with('success','Relación retirada; se conservó el registro de auditoría.');
    }

    public function registrarSeparacion(Request $request)
    {
        $datos=$request->validate([
            'logistica_detalle_id'=>'required|integer|min:1','fecha_separacion'=>'required|date',
            'cantidad'=>'required|integer|min:1','referencia'=>'required|string|min:5|max:255'
        ]);
        DB::transaction(function()use($request,$datos) {
            $plan=DB::table('ot_logistica_detalle')->where('id',$datos['logistica_detalle_id'])
                ->lockForUpdate()->first();
            abort_unless($plan,404);
            $destino=strtoupper(trim((string)$plan->sucursal));
            if(strpos($destino,'MODELO MUESTRA')===false && strpos($destino,'MAYORISTA')===false) {
                throw ValidationException::withMessages(['logistica_detalle_id'=>'El plan no corresponde a MODELO MUESTRA ni a MAYORISTA.']);
            }
            $usado=(int)DB::table('mayorista_separaciones')
                ->where('logistica_detalle_id',$plan->id)->sum('cantidad');
            if($usado+$datos['cantidad']>(int)$plan->cantidad) {
                throw ValidationException::withMessages(['cantidad'=>'La separación excede el saldo del plan (máximo '.max(0,(int)$plan->cantidad-$usado).').']);
            }
            $id=DB::table('mayorista_separaciones')->insertGetId([
                'logistica_detalle_id'=>$plan->id,'id_ot'=>$plan->id_ot,
                'fecha_separacion'=>$datos['fecha_separacion'],
                'cantidad'=>$datos['cantidad'],'referencia'=>$datos['referencia'],
                'usuario_id'=>$request->user()->id,'created_at'=>now(),'updated_at'=>now()
            ]);
            $this->auditar($request,'SEPARACION',null,null,null,(int)$datos['cantidad'],
                'Plan '.$plan->id.' · '.$datos['referencia'],$id);
        },3);
        return back()->with('success','Separación documentada con su fecha real y referencia.');
    }

    public function anularSeparacion(Request $request,int $id)
    {
        $datos=$request->validate(['motivo'=>'required|string|min:5|max:255']);
        DB::transaction(function()use($request,$id,$datos) {
            $separacion=DB::table('mayorista_separaciones')->where('id',$id)->lockForUpdate()->first();
            abort_unless($separacion,404);
            $this->auditar($request,'ANULAR_SEPARACION',null,null,null,
                (int)$separacion->cantidad,'Separación '.$id.' · '.$datos['motivo'],$id);
            DB::table('mayorista_separaciones')->where('id',$id)->delete();
        },3);
        return back()->with('success','Separación anulada; se preservó la auditoría.');
    }

    public function registrarSaldo(Request $request)
    {
        $datos=$request->validate([
            'mes'=>'required|date_format:Y-m','codigo'=>'required|string|max:80',
            'cantidad'=>'required|integer|min:0','referencia'=>'required|string|min:5|max:255'
        ]);
        $codigo=Conciliador::codigo($datos['codigo']);
        if($codigo==='') {
            throw ValidationException::withMessages(['codigo'=>'Indica el código completo del artículo.']);
        }
        list($inicio,)=VentaMayoristaController::rango($datos['mes']);
        DB::transaction(function()use($request,$codigo,$datos,$inicio) {
            $actual=DB::table('mayorista_saldos_iniciales')
                ->where('mes',$inicio)->where('codigo',$codigo)->lockForUpdate()->first();
            $anterior=$actual ? (int)$actual->cantidad : null;
            if($actual) {
                DB::table('mayorista_saldos_iniciales')->where('id',$actual->id)->update([
                    'cantidad'=>$datos['cantidad'],'referencia'=>$datos['referencia'],
                    'usuario_id'=>$request->user()->id,'updated_at'=>now()
                ]);
            } else {
                DB::table('mayorista_saldos_iniciales')->insert([
                    'mes'=>$inicio,'codigo'=>$codigo,'cantidad'=>$datos['cantidad'],
                    'referencia'=>$datos['referencia'],'usuario_id'=>$request->user()->id,
                    'created_at'=>now(),'updated_at'=>now()
                ]);
            }
            $this->auditar($request,'SALDO_INICIAL',null,null,$codigo,(int)$datos['cantidad'],
                'Mes '.$inicio.' · anterior '.($anterior===null?'NO DETERMINADO':$anterior)
                .' · '.$datos['referencia']);
        },3);
        return redirect()->route('ventas.mayoristas.index',['mes'=>$datos['mes']])
            ->with('success','Saldo inicial registrado. El cierre es documental estimado, no stock físico.');
    }
}
