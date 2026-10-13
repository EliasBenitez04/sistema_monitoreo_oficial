@extends('layouts.app')
@section('content')
<style>
    .prod-month-header{background:linear-gradient(115deg,#183153,#285587);color:#fff;border-radius:12px;padding:23px 26px;margin-bottom:18px}
    .prod-month-header h1{font-size:1.65rem;font-weight:700;margin:0 0 7px}
    .prod-month-header .prod-subtitle{color:#dce8f8;font-size:.9rem;margin:0}
    .prod-overview{border:1px solid #e3e9f0;border-radius:12px;overflow:hidden;background:#fff;margin-bottom:18px;box-shadow:0 4px 15px rgba(20,45,72,.05)}
    .prod-overview-head{padding:16px 20px;background:#f8fafc;border-bottom:1px solid #e7edf3}
    .prod-kpi{padding:19px 20px;height:100%;border-right:1px solid #e8edf3}
    .prod-kpi:last-child{border-right:0}
    .prod-kpi-label{font-size:.76rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#64748b}
    .prod-kpi-value{font-size:2rem;line-height:1.22;font-weight:750;margin:6px 0;color:#1e293b}
    .prod-kpi-desc{font-size:.82rem;color:#64748b}
    .prod-kpi-value.good{color:#168052}.prod-kpi-value.bad{color:#b64040}
    .prod-progress{height:13px;border-radius:30px;overflow:hidden;background:#e9edf2;display:flex}
    .prod-progress > div{height:100%}
    .prod-section-title{font-size:1.1rem;font-weight:700;color:#24394f;margin-bottom:6px}
    @media(max-width:767px){.prod-kpi{border-right:0;border-bottom:1px solid #e8edf3}.prod-kpi:last-child{border-bottom:0}.prod-month-header{padding:18px}.prod-kpi-value{font-size:1.7rem}}
</style>
<section class="content"><div class="container-fluid">
<div class="prod-month-header d-flex justify-content-between align-items-center flex-wrap">
    <div class="mr-3 mb-2">
        <h1><i class="fas fa-chart-line mr-2"></i>Control mensual de Producción P</h1>
        <p class="prod-subtitle">Hito: <strong>TERMINACION - INGRESO TERMINACION</strong> · Pedidos P y prioridades</p>
    </div>
    <a href="{{ route('seguimiento-produccion.index') }}" class="btn btn-light btn-sm mb-2"><i class="fas fa-arrow-left mr-1"></i>Volver a Producción</a>
</div>
<div class="prod-overview">
    <div class="prod-overview-head d-flex align-items-center justify-content-between flex-wrap">
        <div class="mb-2 mb-md-0">
            <h2 class="prod-section-title mb-1">Resumen · {{ ucfirst($mes->locale('es')->translatedFormat('F Y')) }}</h2>
            <small class="text-muted"><i class="far fa-calendar-check mr-1"></i>Fecha de corte: <strong>{{ $indicadores->fecha_corte ? $indicadores->fecha_corte->format('d/m/Y') : 'Sin movimientos del período' }}</strong></small>
        </div>
        <div class="btn-group btn-group-sm" aria-label="Navegar meses">
            <a class="btn btn-outline-primary" href="{{ route('seguimiento-produccion.dashboard-mensual',['mes'=>$mes->copy()->subMonth()->format('Y-m')]) }}" title="Mes anterior"><i class="fas fa-chevron-left"></i></a>
            <a class="btn btn-outline-primary" href="{{ route('seguimiento-produccion.dashboard-mensual') }}">Último mes cargado</a>
            <a class="btn btn-outline-primary" href="{{ route('seguimiento-produccion.dashboard-mensual',['mes'=>$mes->copy()->addMonth()->format('Y-m')]) }}" title="Mes siguiente"><i class="fas fa-chevron-right"></i></a>
        </div>
    </div>
    <div class="row no-gutters">
        <div class="col-lg-3 col-sm-6"><div class="prod-kpi">
            <div class="prod-kpi-label">OT/día procesadas</div>
            <div class="prod-kpi-value">{{ number_format($indicadores->ot_dia,0,',','.') }}</div>
            <div class="prod-kpi-desc">Registros agrupados por OT y día</div>
        </div></div>
        <div class="col-lg-3 col-sm-6"><div class="prod-kpi">
            <div class="prod-kpi-label">Total prendas ingresadas</div>
            <div class="prod-kpi-value">{{ number_format($indicadores->prendas,0,',','.') }}</div>
            <div class="prod-kpi-desc">100% de las prendas del mes</div>
        </div></div>
        <div class="col-lg-3 col-sm-6"><div class="prod-kpi">
            <div class="prod-kpi-label">Con pedido P</div>
            <div class="prod-kpi-value good">{{ number_format($indicadores->con,0,',','.') }}</div>
            <div class="prod-kpi-desc">{{ number_format($indicadores->efectividad,1,',','.') }}% del total</div>
        </div></div>
        <div class="col-lg-3 col-sm-6"><div class="prod-kpi">
            <div class="prod-kpi-label">Sin pedido P</div>
            <div class="prod-kpi-value bad">{{ number_format($indicadores->sin,0,',','.') }}</div>
            <div class="prod-kpi-desc">{{ number_format($indicadores->prendas > 0 ? $indicadores->sin * 100 / $indicadores->prendas : 0,1,',','.') }}% del total</div>
        </div></div>
    </div>
    <div class="px-4 pt-2 pb-3">
        <div class="d-flex justify-content-between mb-2">
            <small class="font-weight-bold text-success">Con pedido P</small>
            <small class="font-weight-bold text-danger">Sin pedido P</small>
        </div>
        <div class="prod-progress">
            <div class="bg-success" style="width:{{ $indicadores->efectividad }}%"></div>
            <div class="bg-danger" style="width:{{ $indicadores->prendas > 0 ? 100 - $indicadores->efectividad : 0 }}%"></div>
        </div>
        <p class="small text-muted mb-0 mt-2">Las prendas con pedido P más las prendas sin pedido P equivalen al total ingresado. Una OT puede vincularse a un pedido de fecha posterior al movimiento.</p>
    </div>
</div>
<div class="card mb-3">
    <div class="card-header bg-white">
        <h3 class="prod-section-title mb-1"><i class="far fa-calendar-alt mr-2 text-primary"></i>Calendario de ingresos a Producción</h3>
        <small class="text-muted">Cada día muestra OT procesadas, prendas ingresadas y asociación P. Seleccioná «Ver OT» para consultar el detalle.</small>
    </div>
    <div class="card-body">
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
    </div>
</div>

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
<div class="col-4"><small class="text-muted">Prendas según último proceso</small><h3>{{ number_format($indicadores->prendas_pendientes,0,',','.') }}</h3></div>
<div class="col-4"><small class="text-muted">Espera ≥7 días</small><h3 class="text-danger">{{ number_format($indicadores->urgentes,0,',','.') }}</h3></div>
</div><p class="small text-muted mb-0">La espera se cuenta desde la fecha de pedido o el último movimiento, lo que sea posterior. Normal: 0 a 7 días; revisar: 8 a 14 días; urgente: 15 días o más. Sin datos: no puede clasificarse por falta de fecha. Una OT deja de estar pendiente cuando registra TERMINACION - INGRESO TERMINACION, aunque la cantidad ingresada difiera de la cantidad ordenada. Para OT sin ese hito, las prendas pendientes se basan en el resultado del último movimiento disponible, sin depender de CORTE.</p></div>
</div></div></div>

<div class="card"><div class="card-header"><strong>Prioridades y pendientes para intervenir</strong></div>
<div class="table-responsive" style="max-height:540px;overflow:auto"><table class="table table-sm table-hover mb-0">
<thead class="thead-light"><tr><th>OT</th><th>Pedido P</th><th>Descripción</th><th>Etapa actual</th><th>Última actividad</th><th>Sin movimiento</th><th>Prendas OT</th><th>Ingresadas a IT</th><th>Cant. último proceso</th><th>Prioridad</th></tr></thead><tbody>
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
