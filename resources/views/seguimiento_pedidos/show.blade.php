@extends('layouts.app')

@section('content')
<section class="content-header">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-route mr-2"></i>Pedido {{ $pedido->nro_pedido }}</h1>
            <p class="text-muted mb-0">Trazabilidad desde TERMINACIÓN - TERMINACIÓN hasta la recepción del local.</p>
        </div>
        <a href="{{ route('seguimiento-pedidos.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i> Volver</a>
    </div>
</div>
</section>

<section class="content"><div class="container-fluid">
<div class="row">
@foreach([
    ['OT', $resumen->ots, 'clipboard-list'],
    ['Cantidad orden', number_format($resumen->cantidad,0,',','.'), 'boxes'],
    ['Producto terminado', number_format($resumen->terminado,0,',','.'), 'check-circle'],
    ['Distribución efectiva', number_format($resumen->enviado,0,',','.'), 'truck'],
    ['Confirmado efectivo', number_format($resumen->recibido,0,',','.'), 'store'],
    ['OT completas', $resumen->completas . '/' . $resumen->ots, 'check-double']
] as $kpi)
<div class="col-lg-2 col-md-4 col-6">
    <div class="small-box bg-light border"><div class="inner"><h4>{{ $kpi[1] }}</h4><p>{{ $kpi[0] }}</p></div><div class="icon"><i class="fas fa-{{ $kpi[2] }}"></i></div></div>
</div>
@endforeach
</div>

<div class="card card-outline card-info mb-4">
    <div class="card-header text-center">
        <h3 class="card-title float-none mb-0"><i class="fas fa-stopwatch mr-2"></i>Tiempo de atención del pedido</h3>
    </div>
    <div class="card-body">
        @if(!$resumen->fecha_pedido)
            <div class="alert alert-warning text-center mb-3">
                Este pedido todavía no tiene <strong>FECHA PEDIDO</strong>. Reimportá el Excel con la columna FECHA PEDIDO para calcular los tiempos.
            </div>
        @endif
        <div class="row text-center">
            <div class="col-lg col-md-4 col-6 mb-3 mb-lg-0">
                <div class="kpi-tiempo">
                    <small class="text-muted d-block text-uppercase">Fecha pedido</small>
                    <strong>{{ $resumen->fecha_pedido ? $resumen->fecha_pedido->format('d/m/Y') : '-' }}</strong>
                </div>
            </div>
            <div class="col-lg col-md-4 col-6 mb-3 mb-lg-0">
                <div class="kpi-tiempo">
                    <small class="text-muted d-block text-uppercase">1er envío logística</small>
                    <strong>{{ $resumen->primer_envio_logistica ? date('d/m/Y', strtotime($resumen->primer_envio_logistica)) : '-' }}</strong>
                </div>
            </div>
            <div class="col-lg col-md-4 col-6 mb-3 mb-lg-0">
                <div class="kpi-tiempo">
                    <small class="text-muted d-block text-uppercase">Última OT confirmada</small>
                    <strong>{{ $resumen->ultima_confirmacion ? date('d/m/Y', strtotime($resumen->ultima_confirmacion)) : '-' }}</strong>
                    @if($resumen->dias_confirmacion_total !== null)
                        <div class="small text-success">{{ $resumen->dias_confirmacion_total }} días desde pedido</div>
                    @endif
                </div>
            </div>
            <div class="col-lg col-md-6 col-6 mb-3 mb-lg-0">
                <div class="kpi-tiempo">
                    <small class="text-muted d-block text-uppercase">Rango de atención</small>
                    @if($resumen->dias_confirmacion_total !== null)
                        <strong class="h4 mb-0">{{ $resumen->dias_confirmacion_total }} días</strong>
                    @elseif($resumen->dias_transcurridos !== null)
                        <strong class="h4 mb-0">{{ $resumen->dias_transcurridos }} días</strong>
                        <div class="small text-warning">pedido en curso</div>
                    @else
                        <strong>-</strong>
                    @endif
                </div>
            </div>
            <div class="col-lg col-md-6 col-12">
                <div class="kpi-tiempo">
                    <small class="text-muted d-block text-uppercase">Promedio por local</small>
                    <strong class="h4 mb-0">{{ $resumen->dias_promedio_confirmacion !== null ? number_format($resumen->dias_promedio_confirmacion, 1, ',', '.') . ' días' : '-' }}</strong>
                    <div class="small text-muted">promedio por OT confirmada</div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(($resumen->movimientos_anteriores_omitidos ?? 0) > 0)
<div class="alert alert-secondary py-2">
    <i class="fas fa-filter mr-1"></i>
    Se omitieron <strong>{{ $resumen->movimientos_anteriores_omitidos }}</strong> movimientos anteriores a la fecha del pedido para no distorsionar los KPI.
</div>
@endif

<div class="card card-outline card-secondary mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list-ol mr-2"></i>Tiempo de atención por OT</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 text-center">
                <thead>
                    <tr>
                        <th>OT</th>
                        <th>Fecha pedido</th>
                        <th>Logística OT</th>
                        <th>Despacho del pedido</th>
                        <th>Recepción local</th>
                        <th>Tiempo</th>
                        <th>Estado KPI</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($ots as $otKpi)
                    <tr>
                        <td><strong>{{ $otKpi->nro_ot }}</strong></td>
                        <td>{{ $resumen->fecha_pedido ? $resumen->fecha_pedido->format('d/m/Y') : '-' }}</td>
                        <td>
                            {{ $otKpi->kpi_fecha_logistica ? date('d/m/Y', strtotime($otKpi->kpi_fecha_logistica)) : '-' }}
                            @if($otKpi->kpi_ot_disponible_previamente)
                                <small class="d-block text-info">OT disponible previamente</small>
                            @endif
                        </td>
                        <td>{{ $otKpi->kpi_fecha_envio ? date('d/m/Y', strtotime($otKpi->kpi_fecha_envio)) : '-' }}</td>
                        <td>{{ $otKpi->kpi_fecha_recepcion ? date('d/m/Y', strtotime($otKpi->kpi_fecha_recepcion)) : '-' }}</td>
                        <td>
                            @if($otKpi->kpi_dias !== null)
                                <strong>{{ $otKpi->kpi_dias }} días</strong>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($otKpi->kpi_estado === 'CONFIRMADO')
                                <span class="badge badge-success">CONFIRMADO</span>
                            @elseif($otKpi->kpi_estado === 'CONFIRMADO - OT DISPONIBLE')
                                <span class="badge badge-info">CONFIRMADO - OT DISPONIBLE</span>
                            @elseif($otKpi->kpi_estado === 'DESPACHADO SIN CONFIRMAR')
                                <span class="badge badge-warning">DESPACHADO SIN CONFIRMAR</span>
                            @else
                                <span class="badge badge-secondary">{{ $otKpi->kpi_estado }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($ots as $ot)
<div class="card card-outline {{ $ot->estado_seguimiento === 'COMPLETO' ? 'card-success' : 'card-primary' }}">
    <div class="card-header seguimiento-card-header" data-toggle="collapse" data-target="#detalle-ot-{{ $ot->id_ot }}" aria-expanded="false" aria-controls="detalle-ot-{{ $ot->id_ot }}">
        <div class="row align-items-center text-center w-100 mx-0">
            <div class="col-lg-2 col-md-3 mb-2 mb-md-0">
                <div class="text-muted small text-uppercase">Orden de trabajo</div>
                <div class="h5 mb-0 font-weight-bold">OT {{ $ot->nro_ot }}</div>
            </div>
            <div class="col-lg-3 col-md-4 mb-2 mb-md-0">
                <div class="text-muted small text-uppercase">Código</div>
                <div class="font-weight-bold">{{ $ot->codigo }}</div>
                <div class="small text-muted text-truncate" title="{{ $ot->descripcion }}">{{ $ot->descripcion }}</div>
            </div>
            <div class="col-lg-2 col-md-2 mb-2 mb-md-0">
                <div class="text-muted small text-uppercase">Cantidad</div>
                <div class="h5 mb-0 font-weight-bold">{{ number_format($ot->cantidad_orden,0,',','.') }}</div>
            </div>
            <div class="col-lg-3 col-md-3 mb-2 mb-md-0">
                <div class="text-muted small text-uppercase">Etapa actual</div>
                <span class="badge badge-pill badge-primary px-3 py-2">{{ $ot->etapa_actual }}</span>
            </div>
            <div class="col-lg-2 mt-2 mt-lg-0">
                <span class="badge {{ $ot->estado_seguimiento === 'COMPLETO' ? 'badge-success' : 'badge-warning' }} px-2 py-2">{{ $ot->estado_seguimiento }}</span>
                <button type="button" class="btn btn-sm btn-light border ml-2 seguimiento-toggle" aria-label="Desplegar detalle">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
        </div>
    </div>
    <div id="detalle-ot-{{ $ot->id_ot }}" class="collapse seguimiento-detalle">
    <div class="card-body">
        <div class="row text-center mb-4">
            <div class="col-md"><small class="text-muted d-block">Orden</small><strong>{{ number_format($ot->cantidad_orden,0,',','.') }}</strong></div>
            <div class="col-md"><small class="text-muted d-block">Ingreso Terminación</small><strong>{{ number_format($ot->ingreso_terminacion,0,',','.') }}</strong><div class="small text-muted">{{ $ot->fecha_ingreso ? date('d/m/Y', strtotime($ot->fecha_ingreso)) : '-' }}</div></div>
            <div class="col-md"><small class="text-muted d-block">Producto Terminado</small><strong>{{ number_format($ot->producto_terminado,0,',','.') }}</strong><div class="small text-muted">{{ $ot->fecha_pt ? date('d/m/Y', strtotime($ot->fecha_pt)) : '-' }}</div></div>
            <div class="col-md"><small class="text-muted d-block">Logística</small><strong>{{ number_format($ot->distribuido,0,',','.') }}</strong><div class="small text-muted">1ª salida: <strong>{{ $ot->fecha_logistica_primera ? date('d/m/Y', strtotime($ot->fecha_logistica_primera)) : '-' }}</strong></div>@if($ot->fecha_logistica_ultima && $ot->fecha_logistica_ultima != $ot->fecha_logistica_primera)<div class="small text-primary">Últ. movimiento: <strong>{{ date('d/m/Y', strtotime($ot->fecha_logistica_ultima)) }}</strong></div>@endif</div>
            <div class="col-md"><small class="text-muted d-block">Distribución efectiva</small><strong>{{ number_format($ot->enviado,0,',','.') }} / {{ number_format($ot->cantidad_orden,0,',','.') }}</strong></div>
            <div class="col-md"><small class="text-muted d-block">Confirmado efectivo</small><strong>{{ number_format($ot->recibido,0,',','.') }} / {{ number_format($ot->cantidad_orden,0,',','.') }}</strong></div>
        </div>

        @php
            $etapas = [
                1 => ['TERMINACIÓN', 'fa-industry'],
                2 => ['PRODUCTO TERMINADO', 'fa-box'],
                3 => ['LOGÍSTICA', 'fa-truck-loading'],
                4 => ['REMISIÓN', 'fa-file-invoice'],
                5 => ['RECEPCIÓN LOCAL', 'fa-store'],
            ];
        @endphp

        <div class="seguimiento-panel mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <div>
                    <div class="text-muted small text-uppercase font-weight-bold">Etapa actual</div>
                    <div class="seguimiento-etapa-actual">
                        <i class="fas fa-map-marker-alt mr-2"></i>{{ $ot->etapa_actual }}
                    </div>
                </div>
                <div class="text-right mt-2 mt-md-0">
                    <strong>{{ $ot->porcentaje_seguimiento }}%</strong>
                    <div class="small text-muted">avance del flujo</div>
                </div>
            </div>

            <div class="seguimiento-linea">
                @foreach($etapas as $numero => $etapa)
                    @php
                        $completada = $ot->etapa_numero > $numero;
                        $actual = $ot->etapa_numero === $numero;
                    @endphp
                    <div class="seguimiento-paso {{ $completada ? 'completado' : '' }} {{ $actual ? 'actual' : '' }}">
                        <div class="seguimiento-circulo">
                            <i class="fas {{ $completada ? 'fa-check' : $etapa[1] }}"></i>
                        </div>
                        <div class="seguimiento-nombre">{{ $etapa[0] }}</div>
                        <div class="seguimiento-estado">
                            @if($completada)
                                Completado
                            @elseif($actual)
                                Etapa actual
                            @else
                                Pendiente
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if($ot->cantidad_movimientos > 0)
        <div class="text-center mb-3">
            <button class="btn btn-sm btn-outline-secondary px-3" type="button" data-toggle="collapse" data-target="#movimientos-ot-{{ $ot->id_ot }}" aria-expanded="false">
                <i class="fas fa-exchange-alt mr-1"></i> Ver movimientos ({{ $ot->cantidad_movimientos }})
                <i class="fas fa-chevron-down ml-1"></i>
            </button>
        </div>
        <div class="collapse mb-3" id="movimientos-ot-{{ $ot->id_ot }}">
            <div class="card border-0 bg-light mb-0">
                <div class="card-header bg-transparent text-center border-0 pb-1">
                    <strong><i class="fas fa-route mr-1"></i> Auditoría de movimientos</strong>
                    <div class="small text-muted">Muestra el recorrido físico. Una redistribución no representa prendas nuevas de la OT.</div>
                </div>
                <div class="card-body pt-2">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover text-center align-middle bg-white mb-0">
                            <thead><tr><th>Fecha</th><th>Origen</th><th></th><th>Destino</th><th>Cantidad</th><th>Tipo</th><th>Recepción</th></tr></thead>
                            <tbody>
                            @foreach($ot->movimientos_detalle as $mov)
                                <tr>
                                    <td>{{ $mov->fecha_remision ? date('d/m/Y', strtotime($mov->fecha_remision)) : '-' }}</td>
                                    <td><strong>{{ $mov->origen_mostrar }}</strong></td>
                                    <td><i class="fas fa-long-arrow-alt-right text-muted"></i></td>
                                    <td><strong>{{ $mov->destino_mostrar }}</strong></td>
                                    <td><span class="badge badge-primary px-2">{{ number_format($mov->cantidad,0,',','.') }}</span></td>
                                    <td><span class="badge {{ $mov->tipo_movimiento === 'DESPACHO CENTRAL' ? 'badge-info' : 'badge-warning' }}">{{ $mov->tipo_movimiento }}</span></td>
                                    <td>{{ $mov->fecha_recepcion ? date('d/m/Y', strtotime($mov->fecha_recepcion)) : 'Pendiente' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="row mb-3">
            <div class="col-md-4"><div class="border rounded p-2"><small class="text-muted d-block">Volumen movido (auditoría)</small><strong>{{ number_format($ot->movimientos_fisicos,0,',','.') }}</strong></div></div>
            <div class="col-md-4"><div class="border rounded p-2"><small class="text-muted d-block">Volumen re-movido</small><strong>{{ number_format($ot->movimientos_adicionales,0,',','.') }}</strong></div></div>
            <div class="col-md-4"><div class="border rounded p-2"><small class="text-muted d-block">Locales comerciales confirmados</small><strong>{{ $ot->locales_confirmados }}/{{ $ot->locales_enviados }}</strong> <span class="text-muted">(máximo operativo: 12)</span></div></div>
        </div>

        <h6 class="font-weight-bold mb-2"><i class="fas fa-store mr-1"></i> Locales comerciales</h6>
        <div class="table-responsive">
            <table class="table table-sm table-hover text-center align-middle">
                <thead><tr><th>Local</th><th class="text-right">Enviado</th><th class="text-right">Recibido</th><th class="text-right">Pendiente</th><th>Últ. remisión</th><th>Recepción</th><th>Estado</th></tr></thead>
                <tbody>
                @forelse($ot->locales_comerciales as $local)
                    <tr>
                        <td>{{ $local->local }}</td>
                        <td class="text-right">{{ number_format($local->enviado,0,',','.') }}</td>
                        <td class="text-right">{{ number_format($local->recibido,0,',','.') }}</td>
                        <td class="text-right">{{ number_format($local->pendiente,0,',','.') }}</td>
                        <td>{{ $local->ultima_remision ? date('d/m/Y', strtotime($local->ultima_remision)) : '-' }}</td>
                        <td>{{ $local->ultima_recepcion ? date('d/m/Y', strtotime($local->ultima_recepcion)) : '-' }}</td>
                        <td><span class="badge {{ $local->estado_local === 'RECIBIDO' ? 'badge-success' : ($local->estado_local === 'PARCIAL' ? 'badge-warning' : 'badge-secondary') }}">{{ $local->estado_local }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">Esta OT todavía no tiene remisiones vinculadas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <h6 class="font-weight-bold mt-4 mb-2"><i class="fas fa-warehouse mr-1"></i> Mayorista / Depósito</h6>
        <div class="table-responsive">
            <table class="table table-sm table-hover text-center align-middle mb-2">
                <thead><tr><th>Nodo</th><th class="text-right">Movimiento</th><th class="text-right">Confirmado</th><th>Últ. remisión</th><th>Recepción</th><th>Estado</th></tr></thead>
                <tbody>
                @forelse($ot->canal_mayorista as $local)
                    <tr>
                        <td>{{ $local->local }}</td>
                        <td class="text-right">{{ number_format($local->enviado,0,',','.') }}</td>
                        <td class="text-right">{{ number_format($local->recibido,0,',','.') }}</td>
                        <td>{{ $local->ultima_remision ? date('d/m/Y', strtotime($local->ultima_remision)) : '-' }}</td>
                        <td>{{ $local->ultima_recepcion ? date('d/m/Y', strtotime($local->ultima_recepcion)) : '-' }}</td>
                        <td><span class="badge {{ $local->estado_local === 'RECIBIDO' ? 'badge-success' : ($local->estado_local === 'PARCIAL' ? 'badge-warning' : 'badge-secondary') }}">{{ $local->estado_local }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Sin movimientos de Mayorista / Depósito.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="small text-muted text-center mt-3">Locales comerciales confirmados: <strong>{{ $ot->locales_confirmados }}/{{ $ot->locales_enviados }}</strong> · Pendiente efectivo de recepción: <strong>{{ number_format($ot->pendiente_recepcion,0,',','.') }}</strong> · Los movimientos adicionales se conservan para auditoría y no aumentan el avance por encima de la cantidad de la OT.</div>
    </div>
    </div>
</div>
@endforeach
</div></section>
@endsection


@push('page_css')
<style>
.kpi-tiempo{height:100%;padding:14px 10px;border:1px solid #e3e8ef;border-radius:10px;background:#f8fafc}
.kpi-tiempo strong{display:block;margin-top:4px;color:#212529}
.seguimiento-panel{background:#f8fafc;border:1px solid #e3e8ef;border-radius:12px;padding:18px 20px}
.seguimiento-etapa-actual{font-size:1.05rem;font-weight:700;color:#212529}
.seguimiento-linea{display:flex;position:relative;justify-content:space-between;margin-top:8px}
.seguimiento-linea:before{content:"";position:absolute;left:8%;right:8%;top:19px;height:3px;background:#dce2e8;z-index:0}
.seguimiento-paso{position:relative;z-index:1;width:20%;text-align:center;padding:0 4px}
.seguimiento-circulo{width:40px;height:40px;margin:0 auto 8px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#fff;border:3px solid #ced4da;color:#adb5bd}
.seguimiento-nombre{font-size:.74rem;font-weight:700;color:#6c757d;line-height:1.15}
.seguimiento-estado{font-size:.68rem;color:#adb5bd;margin-top:3px}
.seguimiento-paso.completado .seguimiento-circulo{background:#28a745;border-color:#28a745;color:#fff}
.seguimiento-paso.completado .seguimiento-nombre{color:#218838}
.seguimiento-paso.completado .seguimiento-estado{color:#28a745}
.seguimiento-paso.actual .seguimiento-circulo{background:#007bff;border-color:#007bff;color:#fff;box-shadow:0 0 0 5px rgba(0,123,255,.12)}
.seguimiento-paso.actual .seguimiento-nombre{color:#0056b3}
.seguimiento-paso.actual .seguimiento-estado{color:#007bff;font-weight:700}
.seguimiento-card-header{cursor:pointer;padding:.9rem .75rem;background:#fff;transition:background-color .15s ease}
.seguimiento-card-header:hover{background:#f8fafc}
.seguimiento-card-header .seguimiento-toggle{min-width:34px}
.seguimiento-card-header[aria-expanded="true"] .seguimiento-toggle i{transform:rotate(180deg)}
.seguimiento-toggle i{transition:transform .2s ease}
.seguimiento-detalle .card-body{padding-top:1.35rem}
.seguimiento-detalle .table th,.seguimiento-detalle .table td{vertical-align:middle}
.seguimiento-detalle h6{text-align:center}
@media(max-width:767.98px){
 .seguimiento-linea{display:block}
 .seguimiento-linea:before{display:none}
 .seguimiento-paso{width:100%;display:flex;align-items:center;text-align:left;margin:9px 0}
 .seguimiento-circulo{margin:0 12px 0 0;min-width:38px;width:38px;height:38px}
 .seguimiento-nombre{width:48%}
 .seguimiento-estado{margin:0 0 0 auto}
}
</style>
@endpush
