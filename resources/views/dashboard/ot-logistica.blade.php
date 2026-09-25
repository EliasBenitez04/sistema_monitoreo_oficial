@extends('layouts.app')

@section('content')
<style>
    .lg-title{font-weight:800;color:#25364a}.lg-subtitle{color:#7a8796;font-size:13px}
    .lg-card{border:1px solid #e7ecf2;border-radius:12px;box-shadow:0 5px 18px rgba(15,23,42,.045)}
    .lg-kpi .label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#778596}
    .lg-kpi .value{font-size:28px;line-height:1.1;font-weight:800;color:#233449;margin:6px 0 3px}
    .lg-kpi .meta{font-size:12px;color:#7a8796}.lg-table th{font-size:11px;text-transform:uppercase;white-space:nowrap;color:#5f6e7e}
    .lg-table td{font-size:13px;vertical-align:middle}.lg-code{font-family:monospace;font-size:12px}.lg-nowrap{white-space:nowrap}
    .lg-badge{min-width:82px;display:inline-block;padding:5px 7px}.lg-attention{border-left:4px solid #f6c23e}
    .lg-ok{border-left:4px solid #1cc88a}.lg-flow{font-size:12px;color:#66788a}
    .lg-kpi{transition:.18s ease;cursor:pointer}.lg-kpi:hover{transform:translateY(-2px);box-shadow:0 9px 24px rgba(15,23,42,.09)}
    .lg-kpi.active{border-color:#4e73df;box-shadow:0 0 0 2px rgba(78,115,223,.12)}
    .lg-toolbar{background:#f8fafc;border:1px solid #e7ecf2;border-radius:10px;padding:10px 12px}
    .lg-status-filter .btn{border-radius:18px;margin-right:5px;margin-bottom:4px}
    .lg-row{cursor:pointer}.lg-detail-row{display:none;background:#fafbfd}.lg-detail-box{padding:12px 18px}
    .lg-step{display:inline-flex;align-items:center;margin-right:14px;margin-bottom:5px}.lg-step i{margin-right:5px}
    .lg-confirm-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:8px;margin-top:10px}
    .lg-confirm-item{background:#fff;border:1px solid #e7ecf2;border-radius:9px;padding:9px 11px}
    .lg-confirm-item .branch{font-weight:700;color:#34465a}.lg-confirm-item .numbers{font-size:12px;color:#687789;margin-top:3px}
</style>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h2 class="lg-title mb-1"><i class="fas fa-truck-loading text-primary mr-2"></i>Control Logístico</h2>
            <div class="lg-subtitle">Qué salió de Logística, qué fue remitido, qué recibió el local y qué sigue pendiente.</div>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="{{ route('reporte.logistica-semanal', ['fecha_desde'=>$fechaDesde,'fecha_hasta'=>$fechaHasta]) }}" class="btn btn-outline-primary btn-sm mr-1"><i class="fas fa-calendar-week mr-1"></i>Reporte semanal</a>
            <a href="{{ route('dashboard.logistica.exportar', request()->query()) }}" class="btn btn-success btn-sm"><i class="fas fa-file-excel mr-1"></i>Exportar</a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card lg-card mb-3"><div class="card-body py-3">
        <form method="GET" action="{{ route('dashboard.ot-logistica') }}">
            <div class="row align-items-end">
                <div class="col-lg-2 col-md-4 mb-2"><label class="small font-weight-bold">Desde</label><input type="date" name="fecha_desde" class="form-control" value="{{ $fechaDesde }}"></div>
                <div class="col-lg-2 col-md-4 mb-2"><label class="small font-weight-bold">Hasta</label><input type="date" name="fecha_hasta" class="form-control" value="{{ $fechaHasta }}"></div>
                <div class="col-lg-3 col-md-4 mb-2"><label class="small font-weight-bold">Sucursal</label><select name="sucursal[]" id="sucursal" class="form-control select2" multiple style="width:100%">@foreach($sucursales as $sucursal)<option value="{{ $sucursal }}" {{ in_array($sucursal,$sucursalesSeleccionadas)?'selected':'' }}>{{ $sucursal }}</option>@endforeach</select></div>
                <div class="col-lg-3 col-md-8 mb-2"><label class="small font-weight-bold">Buscar OT / código / artículo</label><input type="text" name="busqueda" class="form-control" value="{{ $busqueda }}" placeholder="Ej. 30808"></div>
                <div class="col-lg-2 col-md-4 mb-2"><button class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i>Consultar</button></div>
            </div>
        </form>
        <small class="text-muted">Período {{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}. Las redistribuciones entre locales no vuelven a sumar como salida de la OT.</small>
    </div></div>

    <div class="row">
        <div class="col-lg-3 col-6 mb-3"><div class="card lg-card lg-kpi h-100 js-kpi-filter" data-filter="TODOS"><div class="card-body"><div class="label">OT en el período</div><div class="value">{{ number_format($resumenEjecutivo->ots,0,',','.') }}</div><div class="meta">{{ number_format($resumenEjecutivo->plan,0,',','.') }} prendas planificadas</div></div></div></div>
        <div class="col-lg-3 col-6 mb-3"><div class="card lg-card lg-kpi h-100 js-kpi-filter" data-filter="REMITIDO"><div class="card-body"><div class="label">Remitido desde Central</div><div class="value">{{ number_format($resumenEjecutivo->remitido,0,',','.') }}</div><div class="meta">{{ number_format($resumenEjecutivo->avance_remision,1,',','.') }}% del plan</div></div></div></div>
        <div class="col-lg-3 col-6 mb-3"><div class="card lg-card lg-kpi h-100 js-kpi-filter" data-filter="COMPLETO"><div class="card-body"><div class="label">Confirmado por locales</div><div class="value">{{ number_format($resumenEjecutivo->recibido,0,',','.') }}</div><div class="meta">{{ number_format($resumenEjecutivo->avance_recepcion,1,',','.') }}% de lo remitido</div></div></div></div>
        <div class="col-lg-3 col-6 mb-3"><div class="card lg-card lg-kpi h-100 js-kpi-filter {{ $resumenEjecutivo->pendiente_remitir>0?'lg-attention':'lg-ok' }}" data-filter="PENDIENTE"><div class="card-body"><div class="label">Pendiente de remitir</div><div class="value">{{ number_format($resumenEjecutivo->pendiente_remitir,0,',','.') }}</div><div class="meta">{{ number_format($resumenEjecutivo->ots_atencion,0,',','.') }} OT requieren revisión</div></div></div></div>
    </div>

    <div class="card lg-card mb-3"><div class="card-body py-3">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <strong>Situación del período</strong>
                <div class="lg-flow mt-2">
                    Plan <strong>{{ number_format($resumenEjecutivo->plan,0,',','.') }}</strong>
                    <i class="fas fa-angle-right mx-2"></i>
                    Remitido <strong>{{ number_format($resumenEjecutivo->remitido,0,',','.') }}</strong>
                    <i class="fas fa-angle-right mx-2"></i>
                    Recibido <strong>{{ number_format($resumenEjecutivo->recibido,0,',','.') }}</strong>
                </div>
            </div>
            <div class="col-lg-4 mt-3 mt-lg-0">
                <div><strong>{{ number_format($resumenEjecutivo->transito,0,',','.') }}</strong> prendas en tránsito</div>
                <div><strong>{{ number_format($resumenEjecutivo->pendiente_recepcion,0,',','.') }}</strong> remitidas pendientes de confirmación</div>
            </div>
        </div>
    </div></div>

    <div class="lg-toolbar mb-3 d-flex justify-content-between align-items-center flex-wrap">
        <div class="lg-status-filter">
            <button type="button" class="btn btn-sm btn-primary js-status active" data-filter="TODOS">Todas</button>
            <button type="button" class="btn btn-sm btn-outline-warning js-status" data-filter="PENDIENTE">Pendientes</button>
            <button type="button" class="btn btn-sm btn-outline-primary js-status" data-filter="TRANSITO">En tránsito</button>
            <button type="button" class="btn btn-sm btn-outline-success js-status" data-filter="COMPLETO">Completas</button>
            <button type="button" class="btn btn-sm btn-outline-secondary js-status" data-filter="SIN_REMISION">Sin remisión</button>
        </div>
        <small class="text-muted"><i class="fas fa-mouse-pointer mr-1"></i>Hacé clic en una OT para ver su trazabilidad sin salir de la pantalla.</small>
    </div>

    <div class="card lg-card mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div><strong><i class="fas fa-exclamation-circle text-warning mr-1"></i>Seguimiento por OT</strong><div class="lg-subtitle">Una fila responde: cuánto debía salir, cuánto salió y cuánto falta.</div></div>
            <span class="badge badge-primary">{{ number_format($detalles->total(),0,',','.') }} OTs</span>
        </div>
        <div class="table-responsive"><table class="table table-hover mb-0 lg-table">
            <thead><tr><th>OT / artículo</th><th>Salida</th><th class="text-right">Plan</th><th class="text-right">Remitido</th><th class="text-right">Recibido</th><th class="text-right">Falta remitir</th><th class="text-right">En tránsito</th><th>Estado</th></tr></thead>
            <tbody>
            @forelse($detalles as $item)
                @php
                    $estadoFila = $item->pendiente_remitir > 0 ? 'PENDIENTE'
                        : ($item->cantidad_en_transito > 0 ? 'TRANSITO'
                        : (($item->cantidad_remitida > 0 && $item->cantidad_recibida >= $item->cantidad_remitida) ? 'COMPLETO' : 'SIN_REMISION'));
                @endphp
                <tr class="lg-row js-ot-row" data-estado="{{ $estadoFila }}" data-detail="detail-{{ $item->id_ot }}">
                    <td><i class="fas fa-chevron-right text-muted mr-1 js-chevron"></i><strong>{{ $item->nro_ot }}</strong> <span class="lg-code ml-1">{{ $item->codigo }}</span><br><small class="text-muted">{{ \Illuminate\Support\Str::limit($item->descripcion,55) }}</small></td>
                    <td class="lg-nowrap">{{ $item->primera_salida ? \Carbon\Carbon::parse($item->primera_salida)->format('d/m/Y') : '—' }}</td>
                    <td class="text-right"><strong>{{ number_format($item->cantidad_logistica,0,',','.') }}</strong></td>
                    <td class="text-right">{{ number_format($item->cantidad_remitida,0,',','.') }}</td>
                    <td class="text-right">{{ number_format($item->cantidad_recibida,0,',','.') }}</td>
                    <td class="text-right">@if($item->pendiente_remitir>0)<span class="badge badge-warning">{{ number_format($item->pendiente_remitir,0,',','.') }}</span>@else 0 @endif</td>
                    <td class="text-right">@if($item->cantidad_en_transito>0)<span class="badge badge-primary">{{ number_format($item->cantidad_en_transito,0,',','.') }}</span>@else 0 @endif</td>
                    <td>
                        @if($item->pendiente_remitir>0)<span class="badge badge-warning lg-badge">PENDIENTE</span>
                        @elseif($item->cantidad_en_transito>0)<span class="badge badge-primary lg-badge">EN TRÁNSITO</span>
                        @elseif($item->cantidad_remitida>0 && $item->cantidad_recibida >= $item->cantidad_remitida)<span class="badge badge-success lg-badge">COMPLETO</span>
                        @else<span class="badge badge-secondary lg-badge">SIN REMISIÓN</span>@endif
                        @if($item->cantidad_redistribuida>0)<br><small class="text-muted">{{ number_format($item->cantidad_redistribuida,0,',','.') }} redistribuidas</small>@endif
                    </td>
                </tr>
                <tr id="detail-{{ $item->id_ot }}" class="lg-detail-row" data-parent-estado="{{ $estadoFila }}">
                    <td colspan="8">
                        <div class="lg-detail-box">
                            <div class="row">
                                <div class="col-lg-8">
                                    <strong>Trazabilidad de la OT {{ $item->nro_ot }}</strong>
                                    <div class="mt-2">
                                        <span class="lg-step"><i class="fas fa-box text-secondary"></i>PT: <strong class="ml-1">{{ number_format($item->cantidad_pt,0,',','.') }}</strong></span>
                                        <span class="lg-step"><i class="fas fa-clipboard-list text-info"></i>Plan: <strong class="ml-1">{{ number_format($item->cantidad_logistica,0,',','.') }}</strong></span>
                                        <span class="lg-step"><i class="fas fa-file-alt text-primary"></i>Remitido: <strong class="ml-1">{{ number_format($item->cantidad_remitida,0,',','.') }}</strong></span>
                                        <span class="lg-step"><i class="fas fa-check-circle text-success"></i>Recibido: <strong class="ml-1">{{ number_format($item->cantidad_recibida,0,',','.') }}</strong></span>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <small class="text-muted d-block">Confirmación de sucursales</small>
                                    <strong class="text-success">{{ number_format($item->sucursales_confirmadas,0,',','.') }} confirmadas</strong>
                                    @if($item->sucursales_pendientes_confirmar>0)
                                        <span class="text-warning ml-2"><strong>{{ number_format($item->sucursales_pendientes_confirmar,0,',','.') }}</strong> pendientes</span>
                                    @endif
                                    @if($item->cantidad_redistribuida>0)<small class="text-muted d-block mt-1">Redistribuciones posteriores: {{ number_format($item->cantidad_redistribuida,0,',','.') }}</small>@endif
                                </div>
                            </div>

                            <div class="mt-3 border-top pt-2">
                                <small class="font-weight-bold text-uppercase text-muted">Confirmación real por sucursal</small>
                                <div class="lg-confirm-grid">
                                    @forelse($item->confirmaciones_sucursales as $confirmacion)
                                        <div class="lg-confirm-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <span class="branch">{{ $confirmacion->sucursal_confirmacion }}</span>
                                                @if($confirmacion->estado_confirmacion === 'CONFIRMADO')
                                                    <span class="badge badge-success">CONFIRMADO</span>
                                                @elseif($confirmacion->estado_confirmacion === 'PARCIAL')
                                                    <span class="badge badge-info">PARCIAL</span>
                                                @else
                                                    <span class="badge badge-warning">PENDIENTE</span>
                                                @endif
                                            </div>
                                            <div class="numbers">
                                                Enviado <strong>{{ number_format($confirmacion->cantidad_enviada,0,',','.') }}</strong>
                                                · Confirmado <strong>{{ number_format($confirmacion->cantidad_confirmada,0,',','.') }}</strong>
                                                @if($confirmacion->pendiente_confirmar>0)
                                                    · Falta <strong class="text-warning">{{ number_format($confirmacion->pendiente_confirmar,0,',','.') }}</strong>
                                                @endif
                                            </div>
                                            <small class="text-muted">
                                                @if($confirmacion->primera_confirmacion)
                                                    Confirmó {{ \Carbon\Carbon::parse($confirmacion->primera_confirmacion)->format('d/m/Y') }}
                                                    @if($confirmacion->ultima_confirmacion && $confirmacion->ultima_confirmacion !== $confirmacion->primera_confirmacion)
                                                        · última {{ \Carbon\Carbon::parse($confirmacion->ultima_confirmacion)->format('d/m/Y') }}
                                                    @endif
                                                @else
                                                    Sin fecha de recepción
                                                @endif
                                            </small>
                                        </div>
                                    @empty
                                        <div class="text-muted small">Todavía no hay remisiones originales asociadas a esta OT.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty<tr><td colspan="8" class="text-center text-muted py-5">Sin movimientos para los filtros seleccionados.</td></tr>@endforelse
            </tbody>
        </table></div>
        @if($detalles->hasPages())<div class="card-footer bg-white">{{ $detalles->links('pagination::bootstrap-4') }}</div>@endif
    </div>

    <div class="card lg-card mb-3">
        <div class="card-header bg-white"><strong><i class="fas fa-store text-primary mr-1"></i>Recepción por sucursal</strong><div class="lg-subtitle">Solo lo necesario para detectar sucursales con mercadería pendiente.</div></div>
        <div class="table-responsive"><table class="table table-sm table-hover mb-0 lg-table">
            <thead><tr><th>Sucursal</th><th class="text-right">Plan</th><th class="text-right">Remitido</th><th class="text-right">Recibido</th><th class="text-right">Pendiente remitir</th><th class="text-right">En tránsito</th></tr></thead>
            <tbody>@forelse($porSucursal as $item)<tr><td><strong>{{ $item->sucursal }}</strong></td><td class="text-right">{{ number_format($item->cantidad_logistica,0,',','.') }}</td><td class="text-right">{{ number_format($item->cantidad_remitida,0,',','.') }}</td><td class="text-right">{{ number_format($item->cantidad_recibida,0,',','.') }}</td><td class="text-right">{{ number_format($item->pendiente_remitir,0,',','.') }}</td><td class="text-right">{{ number_format($item->cantidad_en_transito,0,',','.') }}</td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">Sin movimientos.</td></tr>@endforelse</tbody>
        </table></div>
    </div>

    <div class="card lg-card">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div><strong><i class="fas fa-file-upload text-success mr-1"></i>Actualizar remisiones</strong><div class="lg-subtitle">Importá ENVIOS cuando necesites actualizar recepción y documentación.</div></div>
                <form method="POST" action="{{ route('control.terminacion.importar-remisiones') }}" enctype="multipart/form-data" class="d-flex mt-2 mt-md-0">@csrf
                    <input type="hidden" name="origen" value="dashboard-logistica"><input type="hidden" name="fecha_desde" value="{{ $fechaDesde }}"><input type="hidden" name="fecha_hasta" value="{{ $fechaHasta }}">
                    <input type="file" name="archivo_envios" class="form-control-file mr-2" accept=".xlsx,.xls,.csv" required>
                    <button class="btn btn-success btn-sm"><i class="fas fa-upload mr-1"></i>Importar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    function filtrar(estado) {
        $('.js-status').removeClass('active btn-primary btn-warning btn-success btn-secondary')
            .addClass(function(){ return $(this).data('filter')==='PENDIENTE'?'btn-outline-warning':($(this).data('filter')==='TRANSITO'?'btn-outline-primary':($(this).data('filter')==='COMPLETO'?'btn-outline-success':($(this).data('filter')==='SIN_REMISION'?'btn-outline-secondary':'btn-outline-primary'))); });
        $('.js-status[data-filter="'+estado+'"]').addClass('active').removeClass('btn-outline-primary btn-outline-warning btn-outline-success btn-outline-secondary').addClass('btn-primary');
        $('.js-kpi-filter').removeClass('active');
        $('.js-kpi-filter[data-filter="'+estado+'"]').addClass('active');
        $('.js-ot-row').each(function(){
            var visible = estado==='TODOS' || (estado==='REMITIDO' ? $(this).find('td:nth-child(4)').text().trim()!=='0' : $(this).data('estado')===estado);
            $(this).toggle(visible);
            $('#'+$(this).data('detail')).hide();
            $(this).find('.js-chevron').removeClass('fa-chevron-down').addClass('fa-chevron-right');
        });
    }

    $('.js-status').on('click', function(){ filtrar($(this).data('filter')); });
    $('.js-kpi-filter').on('click', function(){ filtrar($(this).data('filter')); });
    $('.js-ot-row').on('click', function(){
        var detail=$('#'+$(this).data('detail'));
        detail.toggle();
        $(this).find('.js-chevron').toggleClass('fa-chevron-right fa-chevron-down');
    });

    if ($.fn.select2) {
        $('#sucursal').select2({
            placeholder: 'Todas las sucursales',
            allowClear: true,
            width: '100%'
        });
    }
});
</script>
@endsection