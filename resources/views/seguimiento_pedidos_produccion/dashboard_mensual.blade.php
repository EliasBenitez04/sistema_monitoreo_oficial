@extends('layouts.app')
@section('content')
<section class="content-header"><div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
    <div><h1><i class="fas fa-chart-line mr-2"></i>Control mensual de Producción P</h1>
    <p class="text-muted mb-0">Hito: <strong>TERMINACION - INGRESO TERMINACION</strong>. Producción real, pedidos P pendientes y prioridades.</p></div>
    <a href="{{ route('seguimiento-produccion.index') }}" class="btn btn-outline-secondary">Volver a Producción</a>
</div></section>
<section class="content"><div class="container-fluid">
<div class="card"><div class="card-header d-flex justify-content-between align-items-center flex-wrap">
    <h3 class="card-title">Dashboard · {{ ucfirst($mes->locale('es')->translatedFormat('F Y')) }}</h3>
    <div class="btn-group btn-group-sm">
        <a class="btn btn-outline-primary" href="{{ route('seguimiento-produccion.dashboard-mensual',['mes'=>$mes->copy()->subMonth()->format('Y-m')]) }}"><i class="fas fa-chevron-left"></i></a>
        <a class="btn btn-outline-primary" href="{{ route('seguimiento-produccion.dashboard-mensual') }}">Último mes cargado</a>
        <a class="btn btn-outline-primary" href="{{ route('seguimiento-produccion.dashboard-mensual',['mes'=>$mes->copy()->addMonth()->format('Y-m')]) }}"><i class="fas fa-chevron-right"></i></a>
    </div>
</div><div class="card-body">
<p class="text-muted small">Fecha de corte: <strong>{{ $indicadores->fecha_corte ? $indicadores->fecha_corte->format('d/m/Y') : 'Sin movimientos del período' }}</strong>. Las salidas muestran OT/día; una misma OT puede aparecer más de una vez en diferentes fechas. Una OT asociada a un pedido P se reconoce aunque el pedido tenga fecha posterior a la salida.</p>
<div class="row">
    <div class="col-md-4 mb-3">
        <div class="border rounded p-3 bg-light h-100">
            <small class="text-muted d-block">OT/día procesadas</small>
            <h2 class="font-weight-bold my-1">{{ number_format($indicadores->ot_dia,0,',','.') }}</h2>
            <small>Registros agrupados por OT y día</small>
        </div>
    </div>
</div>
<div class="card border-primary mb-3">
    <div class="card-header"><strong>Distribución de prendas ingresadas según pedidos P</strong></div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-4 mb-3">
                <small class="text-muted d-block">TOTAL INGRESADO</small>
                <h2 class="font-weight-bold">{{ number_format($indicadores->prendas,0,',','.') }}</h2>
                <small>100% de las prendas del mes</small>
            </div>
            <div class="col-md-4 mb-3">
                <small class="text-success d-block">CON PEDIDO P</small>
                <h2 class="font-weight-bold text-success">{{ number_format($indicadores->con,0,',','.') }}</h2>
                <small>{{ number_format($indicadores->efectividad,1,',','.') }}% del total</small>
            </div>
            <div class="col-md-4 mb-3">
                <small class="text-danger d-block">SIN PEDIDO P</small>
                <h2 class="font-weight-bold text-danger">{{ number_format($indicadores->sin,0,',','.') }}</h2>
                <small>{{ number_format($indicadores->prendas > 0 ? $indicadores->sin * 100 / $indicadores->prendas : 0,1,',','.') }}% del total</small>
            </div>
        </div>
        <div class="progress" style="height:16px">
            <div class="progress-bar bg-success" style="width:{{ $indicadores->efectividad }}%" title="Con pedido P"></div>
            <div class="progress-bar bg-danger" style="width:{{ $indicadores->prendas > 0 ? 100 - $indicadores->efectividad : 0 }}%" title="Sin pedido P"></div>
        </div>
        <small class="text-muted d-block mt-2">Conciliación: prendas con pedido P + prendas sin pedido P = total ingresado. Incluye pedidos P asociados aunque su fecha sea posterior al movimiento.</small>
    </div>
</div>
<h5 class="mb-3">Calendario de ingresos a Producción</h5>
<div class="table-responsive">
<table class="table table-sm table-bordered text-center" style="min-width:740px">
<thead class="thead-light"><tr><th>Lun</th><th>Mar</th><th>Mié</th><th>Jue</th><th>Vie</th><th>Sáb</th><th>Dom</th></tr></thead><tbody>
@php
$primerDia = $mes->copy()->startOfMonth();
$desplazamiento = $primerDia->dayOfWeekIso - 1;
$casillas = array_fill(0,$desplazamiento,null);
foreach($dias as $diaCalendario) $casillas[] = $diaCalendario;
while(count($casillas)%7 !== 0) $casillas[]=null;
@endphp
@foreach(array_chunk($casillas,7) as $semana)
<tr>@foreach($semana as $dia)
<td style="width:14.28%;vertical-align:top;min-height:90px" class="{{ $dia && $dia->sin ? 'table-warning' : '' }}">
@if($dia)
<strong>{{ $dia->numero }}</strong>
@if($dia->total)
<div class="small"><strong>{{ $dia->total }} OT</strong></div>
<div class="small text-success">{{ $dia->con }} con P</div>
@if($dia->sin)<div class="small text-danger">{{ $dia->sin }} sin P</div>@endif
<div class="small">{{ number_format($dia->prendas,0,',','.') }} prendas</div>
<button type="button" class="btn btn-outline-primary btn-sm mt-1" data-toggle="modal" data-target="#detalleProduccion{{ $dia->numero }}">Ver OT</button>
@else <div class="small text-muted">Sin salida</div> @endif
@endif
</td>
@endforeach</tr>
@endforeach
</tbody></table></div>
</div></div>

{{-- Detalle diario de las mismas OT y prendas que utiliza el calendario. --}}
@foreach($dias as $diaDetalle)
@if($diaDetalle->total > 0)
<div class="modal fade" id="detalleProduccion{{ $diaDetalle->numero }}" tabindex="-1" role="dialog" aria-labelledby="tituloDetalleProduccion{{ $diaDetalle->numero }}" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloDetalleProduccion{{ $diaDetalle->numero }}">
                    OT ingresadas a IT · {{ date('d/m/Y', strtotime($diaDetalle->fecha)) }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Hito TERMINACION - INGRESO TERMINACION. {{ $diaDetalle->total }} OT; {{ $diaDetalle->con }} con pedido P y {{ $diaDetalle->sin }} sin pedido P.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead><tr><th>Nº OT</th><th>Descripción</th><th class="text-right">Cantidad ingresada</th><th>Pedido P</th><th>Estado</th></tr></thead>
                        <tbody>
                        @foreach($salidas->where('fecha', $diaDetalle->fecha) as $salidaDetalle)
                        <tr>
                            <td><strong>{{ $salidaDetalle->nro_ot }}</strong></td>
                            <td>{{ $salidaDetalle->descripcion }}</td>
                            <td class="text-right">{{ number_format($salidaDetalle->prendas,0,',','.') }}</td>
                            <td>{{ $salidaDetalle->pedidos ?: '-' }}</td>
                            <td><span class="badge badge-{{ $salidaDetalle->justificada ? 'success' : 'danger' }}">{{ !$salidaDetalle->justificada ? 'SIN PEDIDO' : ($salidaDetalle->pedido_posterior ? 'PEDIDO POSTERIOR' : 'CON PEDIDO') }}</span></td>
                        </tr>
                        @endforeach
                        </tbody>
                        <tfoot><tr class="font-weight-bold"><td colspan="2">Total del día</td><td class="text-right">{{ number_format($diaDetalle->prendas,0,',','.') }}</td><td colspan="2"></td></tr></tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>
        </div>
    </div>
</div>
@endif
@endforeach

<div class="card border-info"><div class="card-header"><strong>Análisis de producción y proyección por días hábiles</strong></div>
<div class="card-body">
<div class="row">
@foreach([
['Prendas reales',$indicadores->prendas],
['Promedio por día hábil',$indicadores->promedio],
['Adicional proyectado',$indicadores->proyeccion_adicional],
['Total proyectado al cierre',$indicadores->proyeccion_cierre]
] as $elemento)
<div class="col-xl-3 col-md-6 mb-3"><div class="border rounded p-3">
<small class="d-block text-muted">{{ $elemento[0] }}</small>
<h3 class="mb-0">{{ number_format($elemento[1],is_float($elemento[1])?1:0,',','.') }}</h3>
</div></div>
@endforeach</div>
<p class="small text-muted mb-0">
{{ $indicadores->habiles_transcurridos }} días hábiles transcurridos;
{{ $indicadores->habiles_restantes }} restantes. Se cuentan lunes a viernes, sin excluir feriados.
En un mes cerrado, el resultado ya es real y no suma días futuros. La proyección no garantiza capacidad ni disponibilidad de OT.
</p></div></div>

<div class="row">
<div class="col-xl-5"><div class="card"><div class="card-header"><strong>Cuello de botella · etapa de OT pendientes</strong></div><div class="table-responsive">
<table class="table table-sm mb-0"><thead><tr><th>Proceso actual</th><th>OT</th><th>Prendas</th></tr></thead><tbody>
@forelse($porEtapa as $fila)<tr><td>{{ $fila->etapa }}</td><td>{{ $fila->ots }}</td><td>{{ number_format($fila->prendas,0,',','.') }}</td></tr>
@empty<tr><td colspan="3" class="text-muted">Sin OT pendientes</td></tr>@endforelse
</tbody></table></div></div></div>
<div class="col-xl-7"><div class="card"><div class="card-header"><strong>OT pendientes de pedidos P</strong></div>
<div class="card-body"><div class="row">
<div class="col-4"><small class="text-muted">OT pendientes</small><h3>{{ number_format($indicadores->pendientes,0,',','.') }}</h3></div>
<div class="col-4"><small class="text-muted">Prendas pendientes</small><h3>{{ number_format($indicadores->prendas_pendientes,0,',','.') }}</h3></div>
<div class="col-4"><small class="text-muted">Espera ≥7 días</small><h3 class="text-danger">{{ number_format($indicadores->urgentes,0,',','.') }}</h3></div>
</div><p class="small text-muted mb-0">La espera se cuenta desde la fecha de pedido o el último movimiento, lo que sea posterior. Normal: 0 a 7 días; revisar: 8 a 14 días; urgente: 15 días o más. Sin datos: no puede clasificarse por falta de fecha. Las OT con ingreso parcial mantienen su saldo pendiente.</p></div>
</div></div></div>

<div class="card"><div class="card-header"><strong>Prioridades y pendientes para intervenir</strong></div>
<div class="table-responsive" style="max-height:540px;overflow:auto"><table class="table table-sm table-hover mb-0">
<thead class="thead-light"><tr><th>OT</th><th>Pedido P</th><th>Descripción</th><th>Etapa actual</th><th>Última actividad</th><th>Sin movimiento</th><th>Prendas OT</th><th>Ingresadas</th><th>Saldo</th><th>Prioridad</th></tr></thead><tbody>
@forelse($pendientes->sort(function($a, $b) { if ($a->dias_quieto === null) return $b->dias_quieto === null ? strcmp((string)$a->nro_ot, (string)$b->nro_ot) : 1; if ($b->dias_quieto === null) return -1; return ($b->dias_quieto <=> $a->dias_quieto) ?: strcmp((string)$a->nro_ot, (string)$b->nro_ot); }) as $fila)
<tr><td><strong>{{ $fila->nro_ot }}</strong></td><td>{{ $fila->pedidos }}</td><td>{{ $fila->descripcion }}</td>
<td>{{ $fila->etapa }}</td><td>{{ $fila->ultima_fecha ? date('d/m/Y',strtotime($fila->ultima_fecha)) : '-' }}</td>
<td>{{ $fila->dias_quieto !== null ? $fila->dias_quieto.' días' : 'Sin datos' }}</td><td>{{ number_format($fila->cantidad,0,',','.') }}</td><td>{{ number_format($fila->cantidad_ingresada,0,',','.') }}</td><td><strong>{{ number_format($fila->cantidad_pendiente,0,',','.') }}</strong></td>
<td><span class="badge badge-{{ $fila->prioridad==='URGENTE'?'danger':($fila->prioridad==='REVISAR'?'warning':($fila->prioridad==='SIN DATOS'?'secondary':'success')) }}">{{ $fila->prioridad }}</span></td></tr>
@empty<tr><td colspan="10" class="text-center text-muted">Sin pendientes para este corte</td></tr>@endforelse
</tbody></table></div></div>
<div class="card"><div class="card-header"><strong>Movimientos del hito durante el mes · Asociación con pedidos P</strong></div>
<div class="table-responsive" style="max-height:450px;overflow:auto"><table class="table table-sm mb-0">
<thead><tr><th>Fecha</th><th>OT</th><th>Descripción</th><th>Prendas</th><th>Pedido P</th><th>Situación</th></tr></thead><tbody>
@forelse($salidas as $fila)<tr><td>{{ date('d/m/Y',strtotime($fila->fecha)) }}</td><td>{{ $fila->nro_ot }}</td>
<td>{{ $fila->descripcion }}</td><td>{{ number_format($fila->prendas,0,',','.') }}</td><td>{{ $fila->pedidos ?: '-' }}</td>
<td><span class="badge badge-{{ $fila->justificada?'success':'danger' }}">{{ !$fila->justificada ? 'SIN PEDIDO' : ($fila->pedido_posterior ? 'PEDIDO POSTERIOR' : 'CON PEDIDO') }}</span></td></tr>
@empty<tr><td colspan="6" class="text-center text-muted">Sin movimientos registrados en el mes</td></tr>@endforelse
</tbody></table></div></div>
</div></section>
@endsection
