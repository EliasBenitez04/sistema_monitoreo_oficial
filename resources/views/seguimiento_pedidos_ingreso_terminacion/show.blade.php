@extends('layouts.app')

@section('content')
@php
    $pendientes = max(0, $resumen->ots - $resumen->completas);
    $avance = min(100,max(0,(int)$resumen->porcentaje));
@endphp

<section class="content-header pb-2">
<div class="container-fluid trk-shell theme-it">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <div class="trk-eyebrow">INGRESO TERMINACIÓN</div>
            <h1 class="trk-title mb-1">Pedido {{ $pedido->nro_pedido }}</h1>
            <p class="trk-subtitle mb-0">Completo cuando todas sus OT alcanzan <strong>TERMINACION - TERMINACION</strong>.</p>
        </div>
        <div class="mt-2 mt-md-0"><a href="{{ route('pedidos.index') }}" class="btn btn-dark mr-1"><i class="fas fa-th-large mr-1"></i>PEDIDOS</a><a href="{{ route('seguimiento-ingreso-terminacion.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i>Volver</a></div>
    </div>
</div>
</section>

<section class="content">
<div class="container-fluid trk-shell theme-it">
    <div class="trk-detail-hero mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="trk-state {{ $resumen->completo ? 'is-complete' : 'is-progress' }}">
                    <i class="fas {{ $resumen->completo ? 'fa-check' : 'fa-cogs' }} mr-1"></i>{{ $resumen->completo ? 'COMPLETO' : 'EN PROCESO' }}
                </span>
                <div class="trk-detail-value mt-3">{{ $avance }}%</div>
                <div class="trk-hero-label">Avance hacia Terminación</div>
                <div class="trk-progress mt-3"><div style="width:{{ $avance }}%"></div></div>
                <div class="d-flex justify-content-between mt-2 small">
                    <strong class="text-success">{{ $resumen->completas }} OT completas</strong>
                    <strong class="text-warning">{{ $pendientes }} OT pendientes</strong>
                </div>
            </div>
            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="trk-time">
                    <span>Tiempo del pedido</span>
                    @if(!$resumen->completo && $resumen->dias_en_curso !== null)
                        <strong class="text-warning">{{ $resumen->dias_en_curso }}</strong>
                        <small>{{ $resumen->dias_en_curso == 1 ? 'día en curso' : 'días en curso' }}</small>
                    @elseif($resumen->completo && $resumen->dias !== null)
                        <strong class="text-success">{{ $resumen->dias }}</strong>
                        <small>{{ $resumen->dias == 1 ? 'día total' : 'días totales' }}</small>
                    @else
                        <strong>—</strong><small>sin medición</small>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-xl-3 col-md-6 mb-3"><div class="trk-mini"><strong>{{ $resumen->ots }}</strong><span>OT del pedido</span><small>Total a controlar</small></div></div>
        <div class="col-xl-3 col-md-6 mb-3"><div class="trk-mini"><strong>{{ number_format($resumen->cantidad,0,',','.') }}</strong><span>Prendas ordenadas</span><small>Cantidad total</small></div></div>
        <div class="col-xl-3 col-md-6 mb-3"><div class="trk-mini is-success"><strong>{{ $resumen->completas }}</strong><span>OT ingresadas</span><small>{{ $avance }}% del pedido</small></div></div>
        <div class="col-xl-3 col-md-6 mb-3"><div class="trk-mini is-warning"><strong>{{ $pendientes }}</strong><span>OT pendientes</span><small>Sin TERMINACION - TERMINACION</small></div></div>
    </div>

    <div class="trk-time-card mb-4">
        <div class="row text-center">
            <div class="col-md-4 trk-step"><span>Fecha pedido</span><strong>{{ $pedido->fecha_pedido ? $pedido->fecha_pedido->format('d/m/Y') : '-' }}</strong></div>
            <div class="col-md-4 trk-step"><span>Último ingreso</span><strong>{{ $resumen->ultima_fecha ? date('d/m/Y',strtotime($resumen->ultima_fecha)) : '-' }}</strong></div>
            <div class="col-md-4 trk-step"><span>{{ $resumen->completo ? 'Tiempo total' : 'Antigüedad' }}</span>
                <strong>
                    @if(!$resumen->completo && $resumen->dias_en_curso !== null)
                        {{ $resumen->dias_en_curso }} {{ $resumen->dias_en_curso == 1 ? 'día' : 'días' }}
                    @elseif($resumen->completo && $resumen->dias !== null)
                        {{ $resumen->dias }} {{ $resumen->dias == 1 ? 'día' : 'días' }}
                    @else -
                    @endif
                </strong>
            </div>
        </div>
    </div>

    <div class="card trk-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-list mr-2 text-primary"></i>Seguimiento por OT</h3>
                <small class="text-muted">Detalle de qué OTs ya alcanzaron Terminación.</small>
            </div>
            <div><span class="badge badge-success mr-1">{{ $resumen->completas }} completas</span><span class="badge badge-warning">{{ $pendientes }} pendientes</span></div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 text-center trk-table">
                <thead><tr><th>OT</th><th class="text-left">Código / Descripción</th><th>Cantidad</th><th>Fecha pedido</th><th>Ingreso Terminación</th><th>Cantidad ingreso</th><th>Tiempo</th><th>Estado</th></tr></thead>
                <tbody>
                @foreach($ots as $ot)
                    <tr>
                        <td><span class="trk-ot">{{ $ot->nro_ot }}</span></td>
                        <td class="text-left"><strong>{{ $ot->codigo }}</strong><small class="d-block text-muted">{{ $ot->descripcion }}</small></td>
                        <td><strong>{{ number_format($ot->cantidad_orden,0,',','.') }}</strong></td>
                        <td>{{ $pedido->fecha_pedido ? $pedido->fecha_pedido->format('d/m/Y') : '-' }}</td>
                        <td>{{ $ot->fecha_ingreso ? date('d/m/Y',strtotime($ot->fecha_ingreso)) : '-' }}</td>
                        <td>{{ number_format($ot->cantidad_ingreso,0,',','.') }}</td>
                        <td>
                            @if($ot->dias !== null)<strong>{{ $ot->dias }} {{ $ot->dias == 1 ? 'día' : 'días' }}</strong>
                            @elseif($ot->ingreso_previo)<span class="text-muted">Previo al pedido</span>
                            @else - @endif
                        </td>
                        <td>@if($ot->completo)<span class="trk-state is-complete">COMPLETO</span>@else<span class="trk-state is-pending">PENDIENTE</span>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</section>
@endsection

@include('seguimiento_shared.tracking_styles')
