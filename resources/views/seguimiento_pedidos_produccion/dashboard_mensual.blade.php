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
<p class="text-muted small">Fecha de corte: <strong>{{ $indicadores->fecha_corte ? $indicadores->fecha_corte->format('d/m/Y') : 'Sin movimientos del período' }}</strong>. Las salidas muestran OT/día; una misma OT puede aparecer más de una vez en diferentes fechas.</p>
<div class="row">
@foreach([
['OT/día procesadas',number_format($indicadores->ot_dia,0,',','.'),'Eventos agrupados por día'],
['Prendas ingresadas',number_format($indicadores->prendas,0,',','.'),'Cantidad real T-T de Producción'],
['Efectividad de salida',number_format($indicadores->efectividad,1,',','.').'%','Prendas de OT con pedido P / todas las prendas'],
['Prendas sin pedido P',number_format($indicadores->sin,0,',','.'),'Revisar priorización']
] as $kpi)
<div class="col-xl-3 col-md-6 mb-3"><div class="border rounded p-3 h-100 bg-light">
    <small class="text-muted d-block">{{ $kpi[0] }}</small><h2 class="font-weight-bold my-1">{{ $kpi[1] }}</h2><small>{{ $kpi[2] }}</small>
</div></div>
@endforeach
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
@else <div class="small text-muted">Sin salida</div> @endif
@endif
</td>
@endforeach</tr>
@endforeach
</tbody></table></div>
</div></div>

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
<div class="col-4"><small class="text-muted">Sin avance ≥7 días</small><h3 class="text-danger">{{ number_format($indicadores->urgentes,0,',','.') }}</h3></div>
</div><p class="small text-muted mb-0">Las etapas y alertas provienen del último movimiento importado de cada OT. Urgente: sin movimientos o 7+ días sin actividad; revisar: 3 a 6 días.</p></div>
</div></div></div>

<div class="card"><div class="card-header"><strong>Prioridades y pendientes para intervenir</strong></div>
<div class="table-responsive" style="max-height:540px;overflow:auto"><table class="table table-sm table-hover mb-0">
<thead class="thead-light"><tr><th>OT</th><th>Pedido P</th><th>Descripción</th><th>Etapa actual</th><th>Última actividad</th><th>Sin movimiento</th><th>Prendas OT</th><th>Prioridad</th></tr></thead><tbody>
@forelse($pendientes->sortBy(function($item){return ($item->prioridad==='URGENTE'?'0':($item->prioridad==='REVISAR'?'1':'2')).$item->nro_ot;}) as $fila)
<tr><td><strong>{{ $fila->nro_ot }}</strong></td><td>{{ $fila->pedidos }}</td><td>{{ $fila->descripcion }}</td>
<td>{{ $fila->etapa }}</td><td>{{ $fila->ultima_fecha ? date('d/m/Y',strtotime($fila->ultima_fecha)) : '-' }}</td>
<td>{{ $fila->dias_quieto !== null ? $fila->dias_quieto.' días' : 'Sin datos' }}</td><td>{{ number_format($fila->cantidad,0,',','.') }}</td>
<td><span class="badge badge-{{ $fila->prioridad==='URGENTE'?'danger':($fila->prioridad==='REVISAR'?'warning':'success') }}">{{ $fila->prioridad }}</span></td></tr>
@empty<tr><td colspan="8" class="text-center text-muted">Sin pendientes para este corte</td></tr>@endforelse
</tbody></table></div></div>
<div class="card"><div class="card-header"><strong>Movimientos del hito durante el mes · Con pedido y sin pedido</strong></div>
<div class="table-responsive" style="max-height:450px;overflow:auto"><table class="table table-sm mb-0">
<thead><tr><th>Fecha</th><th>OT</th><th>Descripción</th><th>Prendas</th><th>Pedido P</th><th>Situación</th></tr></thead><tbody>
@forelse($salidas as $fila)<tr><td>{{ date('d/m/Y',strtotime($fila->fecha)) }}</td><td>{{ $fila->nro_ot }}</td>
<td>{{ $fila->descripcion }}</td><td>{{ number_format($fila->prendas,0,',','.') }}</td><td>{{ $fila->pedidos ?: '-' }}</td>
<td><span class="badge badge-{{ $fila->justificada?'success':'danger' }}">{{ $fila->justificada?'CON PEDIDO':'SIN PEDIDO' }}</span></td></tr>
@empty<tr><td colspan="6" class="text-center text-muted">Sin movimientos registrados en el mes</td></tr>@endforelse
</tbody></table></div></div>
</div></section>
@endsection
