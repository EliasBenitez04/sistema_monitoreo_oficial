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
    /* Presentación operativa: calendario, indicadores y tablas */
    .prod-calendar{border-collapse:separate;border-spacing:5px;background:#f3f6fa}
    .prod-calendar th{background:#edf2f7;border:0!important;color:#526579;font-size:.76rem;text-transform:uppercase;letter-spacing:.06em;padding:11px}
    .prod-calendar td{height:143px;border:1px solid #e5eaf1!important;border-radius:9px;background:#fff;padding:10px!important;text-align:left;min-width:112px}
    .prod-calendar td.prod-day-risk{background:#fff9f5;border-color:#f0d9c9!important}
    .prod-calendar td.prod-day-idle{background:#f9fafb;color:#9aa5b1}
    .prod-day-number{font-size:.86rem;font-weight:800;color:#334155;margin-bottom:5px}
    .prod-day-count{font-weight:800;color:#223850;font-size:1.08rem}
    .prod-day-metric{font-size:.76rem;line-height:1.6}
    .prod-day-qty{font-size:.76rem;color:#64748b;font-weight:600;margin:5px 0}
    .prod-section-card{border:1px solid #e5eaf1!important;border-radius:11px!important;overflow:hidden;box-shadow:0 3px 13px rgba(20,45,72,.04);margin-bottom:18px}
    .prod-section-card>.card-header{background:#f8fafc!important;border-bottom:1px solid #e5eaf1;padding:15px 18px;font-weight:700;color:#24394f}
    .prod-section-card>.card-header strong{font-size:1rem}
    .prod-table{font-size:.86rem}
    .prod-table thead th{position:sticky;top:0;z-index:2;background:#edf2f7;color:#45596e;border:0;white-space:nowrap;padding:11px 12px}
    .prod-table tbody td{padding:10px 12px;vertical-align:middle;border-top:1px solid #edf1f5}
    .prod-table tbody tr:hover{background:#f2f7fb}
    .prod-table td:last-child{white-space:nowrap}
    .prod-stat{border:1px solid #e5eaf1;border-radius:9px;padding:15px;background:#fff;height:100%}
    .prod-stat small{display:block;color:#64748b;font-size:.78rem}
    .prod-stat h3{font-size:1.65rem;font-weight:800;margin:7px 0 0;color:#24394f}
    .prod-stage-bar{height:5px;background:#e9eef4;border-radius:9px;overflow:hidden;margin-top:6px}
    .prod-stage-bar span{display:block;height:100%;background:#547cae;border-radius:9px}
    .prod-average-wrap{border:1px solid #dbe7f2;border-radius:12px;background:#fff;overflow:hidden;box-shadow:0 5px 18px rgba(20,45,72,.06);margin-bottom:18px}
    .prod-average-heading{background:linear-gradient(110deg,#eaf2fa,#f8fbff);padding:17px 20px;border-bottom:1px solid #dbe7f2}
    .prod-average-kpi{height:100%;padding:16px;border:1px solid #e2eaf3;border-radius:10px;background:#f9fbfd}
    .prod-average-kpi .value{font-size:1.7rem;font-weight:800;line-height:1.2;color:#24394f;margin-top:4px}
    .prod-average-kpi small{display:block;color:#64748b;font-size:.78rem}
    .prod-average-trend{height:8px;border-radius:20px;background:#e4edf7;min-width:110px;overflow:hidden}
    .prod-average-trend span{height:100%;display:block;background:#5484b4;border-radius:20px}
    .prod-average-table tbody tr:last-child td{border-bottom:1px solid #edf1f5}
    .prod-average-table tr.prod-date-future{color:#94a3b8;background:#f8fafc}
    .prod-delta-up,.prod-average-kpi .value.prod-delta-up{color:#12845d;font-weight:700}
    .prod-delta-down,.prod-average-kpi .value.prod-delta-down{color:#c2413a;font-weight:700}
    .prod-delta-even,.prod-average-kpi .value.prod-delta-even{color:#64748b;font-weight:700}
    .prod-day-average{font-size:.73rem;font-weight:700;color:#285587;line-height:1.45}
    .prod-calendar td.prod-day-future{background:#f3f6f9;color:#94a3b8}
    @media(max-width:768px){.prod-calendar td{height:120px;padding:6px!important}.prod-day-count{font-size:.91rem}.prod-calendar{border-spacing:2px}}
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
            <div class="prod-kpi-label">Promedio diario de OT</div>
            <div class="prod-kpi-value">{{ number_format($indicadores->promedio_ot,1,',','.') }}</div>
            <div class="prod-kpi-desc">{{ number_format($indicadores->ot_dia,0,',','.') }} registros de OT en el período</div>
        </div></div>
        <div class="col-lg-3 col-sm-6"><div class="prod-kpi">
            <div class="prod-kpi-label">Promedio diario de prendas</div>
            <div class="prod-kpi-value">{{ number_format($indicadores->promedio,1,',','.') }}</div>
            <div class="prod-kpi-desc">{{ number_format($indicadores->prendas,0,',','.') }} prendas acumuladas del período</div>
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
{{-- Evolución histórica: el promedio se reconstruye día por día sin usar datos posteriores. --}}
<div class="prod-average-wrap">
    <div class="prod-average-heading">
        <h3 class="prod-section-title mb-1"><i class="fas fa-chart-line text-primary mr-2"></i>Evolución del promedio diario de Producción</h3>
        <div class="small text-muted">Promedio hasta cada fecha = prendas acumuladas desde el 1 del mes ÷ días calendario transcurridos, incluidos los días sin movimiento. Cada fila compara el promedio de esa fecha con el del día anterior.</div>
    </div>
    <div class="p-3">
        <div class="row">
            <div class="col-lg-3 col-sm-6 mb-3"><div class="prod-average-kpi">
                <small>Promedio al cierre del período</small>
                <div class="value">{{ $indicadores->dias_transcurridos > 0 ? number_format($indicadores->promedio,1,',','.') : '-' }}</div>
                <small>prendas por día calendario</small>
            </div></div>
            <div class="col-lg-3 col-sm-6 mb-3"><div class="prod-average-kpi">
                <small>Promedio del día anterior</small>
                <div class="value">{{ $indicadores->promedio_ayer !== null ? number_format($indicadores->promedio_ayer,1,',','.') : '-' }}</div>
                <small>mismo cálculo hasta ayer</small>
            </div></div>
            <div class="col-lg-3 col-sm-6 mb-3"><div class="prod-average-kpi">
                <small>Cambio respecto a ayer</small>
                @php $cambioPromedio = $indicadores->variacion_promedio; @endphp
                <div class="value {{ $cambioPromedio === null ? '' : ($cambioPromedio > 0 ? 'prod-delta-up' : ($cambioPromedio < 0 ? 'prod-delta-down' : 'prod-delta-even')) }}">
                    {{ $cambioPromedio === null ? '-' : ($cambioPromedio > 0 ? '+' : '') . number_format($cambioPromedio,1,',','.') }}
                </div>
                <small>{{ $indicadores->variacion_promedio_pct !== null ? ($indicadores->variacion_promedio_pct > 0 ? '+' : '') . number_format($indicadores->variacion_promedio_pct,1,',','.') . '% frente a ayer' : 'sin comparación porcentual disponible' }}</small>
            </div></div>
            <div class="col-lg-3 col-sm-6 mb-3"><div class="prod-average-kpi">
                <small>Días transcurridos / total</small>
                <div class="value">{{ $indicadores->dias_transcurridos }} / {{ $dias->count() }}</div>
                <small>Los días futuros no se incluyen</small>
            </div></div>
        </div>
        <div class="table-responsive" style="max-height:580px;overflow:auto">
            <table class="table prod-table prod-average-table mb-0">
                <thead><tr>
                    <th>Fecha</th>
                    <th class="text-right">OT del día</th>
                    <th class="text-right">Prendas del día</th>
                    <th class="text-right">Diferencia vs ayer</th>
                    <th class="text-right">Promedio al día</th>
                    <th class="text-right">Promedio ayer</th>
                    <th class="text-right">Variación promedio</th>
                    <th>Tendencia</th>
                </tr></thead>
                <tbody>
                @foreach($dias as $diaEvolucion)
                <tr class="{{ $diaEvolucion->registrado ? '' : 'prod-date-future' }}">
                    <td><strong>{{ date('d/m/Y', strtotime($diaEvolucion->fecha)) }}</strong> <span class="small text-muted">{{ ucfirst(\Carbon\Carbon::parse($diaEvolucion->fecha)->locale('es')->translatedFormat('D')) }}</span></td>
                    @if($diaEvolucion->registrado)
                    <td class="text-right">{{ number_format($diaEvolucion->total,0,',','.') }}</td>
                    <td class="text-right font-weight-bold">{{ number_format($diaEvolucion->prendas,0,',','.') }}</td>
                    <td class="text-right">
                        @if($diaEvolucion->variacion_prendas !== null)
                            <span class="{{ $diaEvolucion->variacion_prendas > 0 ? 'prod-delta-up' : ($diaEvolucion->variacion_prendas < 0 ? 'prod-delta-down' : 'prod-delta-even') }}">
                                {{ $diaEvolucion->variacion_prendas > 0 ? '+' : '' }}{{ number_format($diaEvolucion->variacion_prendas,0,',','.') }}
                            </span>
                        @else - @endif
                    </td>
                    <td class="text-right font-weight-bold">{{ number_format($diaEvolucion->promedio,1,',','.') }}</td>
                    <td class="text-right">{{ $diaEvolucion->promedio_anterior !== null ? number_format($diaEvolucion->promedio_anterior,1,',','.') : '-' }}</td>
                    <td class="text-right">
                        @if($diaEvolucion->variacion !== null)
                            <span class="{{ $diaEvolucion->variacion > 0 ? 'prod-delta-up' : ($diaEvolucion->variacion < 0 ? 'prod-delta-down' : 'prod-delta-even') }}">
                                {{ $diaEvolucion->variacion > 0 ? '+' : '' }}{{ number_format($diaEvolucion->variacion,1,',','.') }}
                            </span>
                            @if($diaEvolucion->variacion_porcentaje !== null)
                                <small class="d-block text-muted">{{ $diaEvolucion->variacion_porcentaje > 0 ? '+' : '' }}{{ number_format($diaEvolucion->variacion_porcentaje,1,',','.') }}%</small>
                            @endif
                        @else - @endif
                    </td>
                    <td><div class="prod-average-trend" title="Promedio: {{ number_format($diaEvolucion->promedio,1,',','.') }}"><span style="width:{{ max(0, min(100, round(100 * $diaEvolucion->promedio / $escalaPromedios,1))) }}%"></span></div></td>
                    @else
                        <td colspan="7" class="text-center text-muted">Pendiente · fecha posterior al corte</td>
                    @endif
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <p class="small text-muted mt-2 mb-0">El promedio es acumulado a la fecha: no representa el promedio por OT. «Prendas del día» es la producción real de esa jornada y «Diferencia vs ayer» compara ambos días. Los totales históricos se conservan para auditoría y el seguimiento de pedidos P.</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-white">
        <h3 class="prod-section-title mb-1"><i class="far fa-calendar-alt mr-2 text-primary"></i>Calendario de ingresos a Producción</h3>
        <small class="text-muted">Cada día muestra OT procesadas, prendas ingresadas y asociación P. Seleccioná «Ver OT» para consultar el detalle.</small>
    </div>
    <div class="card-body">
<div class="table-responsive">
<table class="table prod-calendar mb-0" style="min-width:840px">
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
<td style="width:14.28%;vertical-align:top" class="{{ $dia && !$dia->registrado ? 'prod-day-future' : (!$dia || !$dia->total ? 'prod-day-idle' : ($dia->sin ? 'prod-day-risk' : '')) }}">
@if($dia)
<div class="prod-day-number">{{ $dia->numero }}</div>
@if($dia->registrado && $dia->total)
<div class="prod-day-count">{{ $dia->total }} OT</div>
<div class="prod-day-metric text-success"><i class="fas fa-check-circle mr-1"></i>{{ $dia->con }} con P</div>
@if($dia->sin)<div class="prod-day-metric text-danger"><i class="fas fa-exclamation-circle mr-1"></i>{{ $dia->sin }} sin P</div>@endif
<div class="prod-day-qty">{{ number_format($dia->prendas,0,',','.') }} prendas</div>
<button type="button" class="btn btn-outline-primary btn-sm mt-1 btn-block" data-toggle="modal" data-target="#detalleProduccion{{ $dia->numero }}">Ver OT</button>
@elseif($dia->registrado)
<div class="small text-muted">Sin movimiento</div>
@else
<div class="small text-muted">Pendiente</div>
@endif
@if($dia->registrado)
<div class="prod-day-average mt-2">Prom.: {{ number_format($dia->promedio,1,',','.') }}/día</div>
@if($dia->variacion !== null)
<div class="small {{ $dia->variacion > 0 ? 'prod-delta-up' : ($dia->variacion < 0 ? 'prod-delta-down' : 'prod-delta-even') }}">
    {{ $dia->variacion > 0 ? '+' : '' }}{{ number_format($dia->variacion,1,',','.') }} vs ayer
</div>
@endif
@endif
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

<div class="card prod-section-card"><div class="card-header"><strong><i class="fas fa-chart-area text-primary mr-2"></i>Análisis y proyección según promedio diario</strong></div>
<div class="card-body">
<div class="row">
@foreach([
['Prendas acumuladas',$indicadores->prendas],
['Promedio diario acumulado',$indicadores->promedio],
['Adicional proyectado',$indicadores->proyeccion_adicional],
['Total proyectado al cierre',$indicadores->proyeccion_cierre]
] as $elemento)
<div class="col-xl-3 col-md-6 mb-3"><div class="prod-stat">
<small class="d-block text-muted">{{ $elemento[0] }}</small>
<h3 class="mb-0">{{ number_format($elemento[1],is_float($elemento[1])?1:0,',','.') }}</h3>
</div></div>
@endforeach</div>
<p class="small text-muted mb-0">
{{ $indicadores->dias_transcurridos }} días calendario transcurridos;
{{ $indicadores->dias_restantes }} restantes. Se incluyen sábados y domingos, incluso sin movimientos.
En un mes cerrado, el resultado es real y no suma días futuros. La proyección es una referencia estadística, no garantiza capacidad ni disponibilidad de OT.
</p></div></div>

<div class="row">
<div class="col-xl-5"><div class="card prod-section-card"><div class="card-header"><strong><i class="fas fa-layer-group text-primary mr-2"></i>Cuello de botella · etapa de OT pendientes</strong></div><div class="table-responsive">
<table class="table prod-table mb-0"><thead><tr><th>Proceso actual</th><th>OT</th><th>Prendas</th></tr></thead><tbody>
@forelse($porEtapa as $fila)<tr><td>{{ $fila->etapa }}</td><td>{{ $fila->ots }}</td><td>{{ number_format($fila->prendas,0,',','.') }}</td></tr>
@empty<tr><td colspan="3" class="text-muted">Sin OT pendientes</td></tr>@endforelse
</tbody></table></div></div></div>
<div class="col-xl-7"><div class="card prod-section-card"><div class="card-header"><strong><i class="fas fa-clipboard-list text-primary mr-2"></i>OT pendientes de pedidos P</strong></div>
<div class="card-body"><div class="row">
<div class="col-4"><small class="text-muted">OT pendientes</small><h3>{{ number_format($indicadores->pendientes,0,',','.') }}</h3></div>
<div class="col-4"><small class="text-muted">Prendas según último proceso</small><h3>{{ number_format($indicadores->prendas_pendientes,0,',','.') }}</h3></div>
<div class="col-4"><small class="text-muted">Urgentes · 15 días o más</small><h3 class="text-danger">{{ number_format($indicadores->urgentes,0,',','.') }}</h3></div>
</div><p class="small text-muted mb-0">La espera se cuenta desde la fecha de pedido o el último movimiento, lo que sea posterior. Normal: 0 a 7 días; revisar: 8 a 14 días; urgente: 15 días o más. Sin datos: no puede clasificarse por falta de fecha. Una OT deja de estar pendiente cuando registra TERMINACION - INGRESO TERMINACION, aunque la cantidad ingresada difiera de la cantidad ordenada. Para OT sin ese hito, las prendas pendientes se basan en el resultado del último movimiento disponible, sin depender de CORTE.</p></div>
</div></div></div>

<div class="card prod-section-card"><div class="card-header"><strong><i class="fas fa-exclamation-triangle text-warning mr-2"></i>Prioridades y pendientes para intervenir</strong><div class="small text-muted font-weight-normal mt-1">Ordenadas por días sin movimiento, de mayor a menor.</div></div>
<div class="table-responsive" style="max-height:540px;overflow:auto"><table class="table table-hover prod-table mb-0">
<thead class="thead-light"><tr><th>OT</th><th>Pedido P</th><th>Descripción</th><th>Etapa actual</th><th>Última actividad</th><th>Sin movimiento</th><th>Prendas OT</th><th>Ingresadas a IT</th><th>Cant. último proceso</th><th>Prioridad</th></tr></thead><tbody>
@forelse($pendientes->sort(function($a, $b) { if ($a->dias_quieto === null) return $b->dias_quieto === null ? strcmp((string)$a->nro_ot, (string)$b->nro_ot) : 1; if ($b->dias_quieto === null) return -1; return ($b->dias_quieto <=> $a->dias_quieto) ?: strcmp((string)$a->nro_ot, (string)$b->nro_ot); }) as $fila)
<tr><td><strong>{{ $fila->nro_ot }}</strong></td><td>{{ $fila->pedidos }}</td><td>{{ $fila->descripcion }}</td>
<td>{{ $fila->etapa }}</td><td>{{ $fila->ultima_fecha ? date('d/m/Y',strtotime($fila->ultima_fecha)) : '-' }}</td>
<td>{{ $fila->dias_quieto !== null ? $fila->dias_quieto.' días' : 'Sin datos' }}</td><td>{{ number_format($fila->cantidad,0,',','.') }}</td><td>{{ number_format($fila->cantidad_ingresada,0,',','.') }}</td><td><strong>{{ number_format($fila->cantidad_pendiente,0,',','.') }}</strong></td>
<td><span class="badge badge-{{ $fila->prioridad==='URGENTE'?'danger':($fila->prioridad==='REVISAR'?'warning':($fila->prioridad==='SIN DATOS'?'secondary':'success')) }}">{{ $fila->prioridad }}</span></td></tr>
@empty<tr><td colspan="10" class="text-center text-muted">Sin pendientes para este corte</td></tr>@endforelse
</tbody></table></div></div>
<div class="card prod-section-card"><div class="card-header"><strong><i class="fas fa-truck-loading text-primary mr-2"></i>Movimientos del hito durante el mes · Asociación con pedidos P</strong></div>
<div class="table-responsive" style="max-height:450px;overflow:auto"><table class="table table-sm mb-0">
<thead><tr><th>Fecha</th><th>OT</th><th>Descripción</th><th>Prendas</th><th>Pedido P</th><th>Situación</th></tr></thead><tbody>
@forelse($salidas as $fila)<tr><td>{{ date('d/m/Y',strtotime($fila->fecha)) }}</td><td>{{ $fila->nro_ot }}</td>
<td>{{ $fila->descripcion }}</td><td>{{ number_format($fila->prendas,0,',','.') }}</td><td>{{ $fila->pedidos ?: '-' }}</td>
<td><span class="badge badge-{{ $fila->justificada?'success':'danger' }}">{{ !$fila->justificada ? 'SIN PEDIDO' : ($fila->pedido_posterior ? 'PEDIDO POSTERIOR' : 'CON PEDIDO') }}</span></td></tr>
@empty<tr><td colspan="6" class="text-center text-muted">Sin movimientos registrados en el mes</td></tr>@endforelse
</tbody></table></div></div>
</div></section>
@endsection
