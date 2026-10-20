<?php

namespace App\Http\Controllers;

use App\Support\MayoristaConciliacion as Conciliador;
use App\Support\MayoristaOrigenRemision as OrigenRemision;
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
            'cantidad'=>'required|integer|min:1','referencia'=>'required|string|min:5|max:255',
            'confirmar_modelo'=>'nullable|in:1',
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
            // MODELO MUESTRA puede documentar una separación previa a la
            // recepción de MATRIZ. Se admite vínculo DOCUMENTAL con
            // confirmación humana, no prueba de entrega física.
            $modelo = (stripos((string)$r->sucursal_destino,'MODELO')!==false
                && stripos((string)$r->sucursal_destino,'MUESTRA')!==false)
                || (stripos((string)$r->sucursal_logistica,'MODELO')!==false
                && stripos((string)$r->sucursal_logistica,'MUESTRA')!==false);
            if(!$modelo && $r->id_logistica_detalle) {
                $detalle=DB::table('ot_logistica_detalle')
                    ->where('id',$r->id_logistica_detalle)->first();
                $nombre=strtoupper(trim((string)($detalle->sucursal??'')));
                $modelo=strpos($nombre,'MODELO')!==false && strpos($nombre,'MUESTRA')!==false;
            }
            if(!$r->fecha_recepcion && (!$modelo || ($datos['confirmar_modelo']??'')!=='1')) {
                throw ValidationException::withMessages(['remision_id'=>
                    'Sin recepción: solo Modelo Muestra con confirmación documental explícita y referencia puede vincularse.']);
            }
            $codigo=Conciliador::codigo($r->codigo);
            if(!isset($necesidades[$codigo])) {
                throw ValidationException::withMessages(['remision_id'=>'El código completo de la remisión no coincide con esta factura.']);
            }
            if(!$r->fecha_remision || $r->fecha_remision > $factura->fecha) {
                throw ValidationException::withMessages(['remision_id'=>
                    'La remisión debe tener fecha de emisión no posterior a la factura para poder cruzarse.']);
            }
            if($r->fecha_recepcion && $r->fecha_recepcion > $factura->fecha && !$modelo) {
                throw ValidationException::withMessages(['remision_id'=>
                    'La recepción en MATRIZ figura posterior a la factura: investigar antes de asignar.']);
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
                'Vínculo '.$id.(!$r->fecha_recepcion ? ' · MODELO MUESTRA SIN RECEPCIÓN EN MATRIZ' : '').' · '.$datos['referencia']);
        },3);
        return redirect()->route('ventas.mayoristas.factura',['clave'=>$clave,'mes'=>$datos['mes']])
            ->with('success','Relación factura–remisión registrada con cantidades auditadas.');
    }

    /**
     * Asocia en una sola transaccion las lineas que el usuario selecciono.
     * La propuesta visual no es evidencia de procedencia; esta accion
     * exige verificacion y referencia. Ningun error deja vinculos parciales.
     */
    public function vincularPropuesta(Request $request, string $clave)
    {
        $datos = $request->validate([
            'mes' => 'required|date_format:Y-m',
            'referencia' => 'required|string|min:5|max:255',
            'asociar' => 'required|array|min:1|max:50',
            'asociar.*' => 'required|integer|min:1',
        ]);
        $cantidades = [];
        foreach ($datos['asociar'] as $id => $cantidad) {
            if (!preg_match('/^[1-9][0-9]*$/D', (string) $id)) {
                throw ValidationException::withMessages(['asociar' =>
                    'Una de las lineas de remision no tiene identificador valido.']);
            }
            $cantidades[(int) $id] = (int) $cantidad;
        }
        $ids = array_keys($cantidades);
        sort($ids, SORT_NUMERIC);

        $factura = VentaMayoristaController::buscarFactura($clave, $datos['mes']);
        $necesidades = VentaMayoristaController::necesidades(
            VentaMayoristaController::lineas($factura)
        );
        $totalConfirmado = 0;

        DB::transaction(function () use (
            $request, $clave, $datos, $ids, $cantidades,
            $factura, $necesidades, &$totalConfirmado
        ) {
            // El bloqueo de la factura se comparte con la vinculacion
            // individual: evita excedentes con solicitudes concurrentes.
            $filaFactura = VentaMayoristaController::ventas()
                ->where('v.fecha', $factura->fecha)
                ->where('v.comprobante', $factura->comprobante)
                ->whereRaw("COALESCE(BTRIM(v.cli_cod),'') = ?", [trim((string) $factura->cli_cod)])
                ->whereRaw("COALESCE(BTRIM(v.cliente),'') = ?", [trim((string) $factura->cliente)])
                ->orderBy('v.id')->lockForUpdate()->first();
            if (!$filaFactura) {
                throw ValidationException::withMessages(['asociar' => 'Factura no encontrada para el cruce.']);
            }

            $remisiones = VentaMayoristaController::entradas()
                ->whereRaw(OrigenRemision::expresionDestinoMatriz('r'))
                ->whereIn('r.id', $ids)
                ->orderBy('r.id')->lockForUpdate()->get()->keyBy('id');

            $porCodigo = DB::table('mayorista_vinculos')
                ->where('factura_clave', $clave)
                ->select('codigo')->selectRaw('SUM(cantidad) as vinculadas')
                ->groupBy('codigo')->pluck('vinculadas', 'codigo')->all();

            foreach ($ids as $id) {
                $r = $remisiones->get($id);
                if (!$r) {
                    throw ValidationException::withMessages(['asociar' =>
                        'La linea de remision #'.$id.' ya no es una entrada valida hacia Matriz.']);
                }
                $fechaFactura = substr((string) $factura->fecha, 0, 10);
                $emision = substr((string) $r->fecha_remision, 0, 10);
                $recepcion = substr((string) $r->fecha_recepcion, 0, 10);
                if ($emision === '' || $recepcion === ''
                    || $emision > $fechaFactura || $recepcion > $fechaFactura) {
                    throw ValidationException::withMessages(['asociar' =>
                        'Remision #'.$id.': emision o recepcion no validas para la fecha de factura.']);
                }
                $nombreModelo = mb_strtoupper(trim((string) ($r->sucursal_destino ?? '')).' '.
                    trim((string) ($r->sucursal_logistica ?? '')), 'UTF-8');
                if (!empty($r->id_logistica_detalle)) {
                    $destinoPlan = DB::table('ot_logistica_detalle')
                        ->where('id', $r->id_logistica_detalle)->value('sucursal');
                    $nombreModelo .= ' '.mb_strtoupper((string) $destinoPlan, 'UTF-8');
                }
                if (strpos($nombreModelo, 'MODELO') !== false
                    && strpos($nombreModelo, 'MUESTRA') !== false) {
                    throw ValidationException::withMessages(['asociar' =>
                        'La remision #'.$id.' corresponde a Modelo Muestra. Revisala individualmente.']);
                }
                if (OrigenRemision::origenDiscordante($r) || empty($r->numero_remision)) {
                    throw ValidationException::withMessages(['asociar' =>
                        'Remision #'.$id.' sin identificacion de origen/documento confiable.']);
                }
                $codigo = Conciliador::codigo($r->codigo);
                if (!isset($necesidades[$codigo])) {
                    throw ValidationException::withMessages(['asociar' =>
                        'La linea #'.$id.' no coincide con el codigo completo de esta factura.']);
                }
                if (DB::table('mayorista_vinculos')->where('factura_clave', $clave)
                    ->where('remision_id', $id)->exists()) {
                    throw ValidationException::withMessages(['asociar' =>
                        'La remision #'.$id.' ya fue asociada a esta factura. Actualiza la pantalla.']);
                }
                $ocupadas = (int) DB::table('mayorista_vinculos')
                    ->where('remision_id', $id)->sum('cantidad');
                $maximo = Conciliador::capacidad(
                    (int) $necesidades[$codigo] - (int) ($porCodigo[$codigo] ?? 0),
                    (int) $r->cantidad - $ocupadas
                );
                $cantidad = $cantidades[$id];
                if ($cantidad > $maximo) {
                    throw ValidationException::withMessages(['asociar' =>
                        'La remision #'.$id.' excede el disponible actual (maximo '.$maximo.'). Revisar propuestas.']);
                }
                $vinculoId = DB::table('mayorista_vinculos')->insertGetId([
                    'factura_clave' => $clave,
                    'comprobante' => $factura->comprobante,
                    'fecha_factura' => $factura->fecha,
                    'codigo' => $codigo,
                    'remision_id' => $id,
                    'cantidad' => $cantidad,
                    'usuario_id' => $request->user()->id,
                    'observacion' => $datos['referencia'],
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $this->auditar($request, 'VINCULAR', $clave, $id, $codigo, $cantidad,
                    'Cruce asistido confirmado por usuario. Vinculo '.$vinculoId.' · '.$datos['referencia']);
                $porCodigo[$codigo] = (int) ($porCodigo[$codigo] ?? 0) + $cantidad;
                $totalConfirmado += $cantidad;
            }
        }, 3);

        return back()->with('success', 'Se asociaron '.$totalConfirmado.
            ' prendas con validacion de codigo, fechas, saldo y auditoria.');
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
            'logistica_detalle_id'=>'required|integer|min:1','fecha_separacion'=>'required|date|before_or_equal:today',
            'cantidad'=>'required|integer|min:1','referencia'=>'required|string|min:5|max:255'
        ]);
        DB::transaction(function()use($request,$datos) {
            $plan=DB::table('ot_logistica_detalle')->where('id',$datos['logistica_detalle_id'])
                ->lockForUpdate()->first();
            abort_unless($plan,404);
            $destino=strtoupper(trim((string)$plan->sucursal));
            if(!(strpos($destino,'MODELO')!==false && strpos($destino,'MUESTRA')!==false)
                && strpos($destino,'MAYORISTA')===false) {
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
