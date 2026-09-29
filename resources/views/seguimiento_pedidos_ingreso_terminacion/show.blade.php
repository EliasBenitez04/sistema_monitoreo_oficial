@extends('layouts.app')

@section('content')
@php
    $avance = min(100,max(0,(int)$resumen->porcentaje));
@endphp

<section class="content-header pb-2">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <div class="it-eyebrow">INGRESO TERMINACIÓN</div>
            <h1 class="it-title mb-1">Pedido {{ $pedido->nro_pedido }}</h1>
            <p class="text-muted mb-0">Se considera completo cuando todas sus OT tienen <strong>TERMINACION - TERMINACION</strong>.</p>
        </div>
        <a href="{{ route('seguimiento-ingreso-terminacion.index') }}" class="btn btn-outline-secondary mt-2 mt-md-0"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
    </div>
</div>
</section>

<section class="content"><div class="container-fluid">
    <div class="it-detail-hero mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="it-state {{ $resumen->completo ? 'is-complete' : 'is-progress' }}">{{ $resumen->completo ? 'COMPLETO' : 'EN PROCESO' }}</span>
                <div class="it-detail-value mt-3">{{ $avance }}%</div>
                <div class="text-muted">Avance hacia Ingreso Terminación</div>
                <div class="it-progress mt-3"><div style="width:{{ $avance }}%"></div></div>
                <div class="d-flex justify-content-between mt-2 small">
                    <strong class="text-success">{{ $resumen->completas }} OT completas</strong>
                    <strong class="text-warning">{{ $resumen->pendientes }} OT pendientes</strong>
                </div>
            </div>
            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="it-time">
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
        <div class="col-xl-3 col-md-6 mb-3"><div class="it-mini"><strong>{{ $resumen->ots }}</strong><span>OT del pedido</span><small>Total a controlar</small></div></div>
        <div class="col-xl-3 col-md-6 mb-3"><div class="it-mini"><strong>{{ number_format($resumen->cantidad,0,',','.') }}</strong><span>Prendas ordenadas</span><small>Cantidad total</small></div></div>
        <div class="col-xl-3 col-md-6 mb-3"><div class="it-mini is-success"><strong>{{ $resumen->completas }}</strong><span>OT ingresadas</span><small>{{ $avance }}% del pedido</small></div></div>
        <div class="col-xl-3 col-md-6 mb-3"><div class="it-mini is-warning"><strong>{{ $resumen->pendientes }}</strong><span>OT pendientes</span><small>Sin TERMINACION - TERMINACION</small></div></div>
    </div>

    <div class="it-time-card mb-4">
        <div class="row text-center">
            <div class="col-md-4 it-step"><span>Fecha pedido</span><strong>{{ $pedido->fecha_pedido ? $pedido->fecha_pedido->format('d/m/Y') : '-' }}</strong></div>
            <div class="col-md-4 it-step"><span>Último ingreso</span><strong>{{ $resumen->ultima_fecha ? date('d/m/Y',strtotime($resumen->ultima_fecha)) : '-' }}</strong></div>
            <div class="col-md-4 it-step"><span>{{ $resumen->completo ? 'Tiempo total' : 'Antigüedad' }}</span>
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

    <div class="card it-table-card">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="card-title font-weight-bold"><i class="fas fa-list mr-2 text-primary"></i>Seguimiento por OT</h3>
                <small class="text-muted d-block">Detalle de ingreso real a Terminación.</small>
            </div>
            <div><span class="badge badge-success mr-1">{{ $resumen->completas }} completas</span><span class="badge badge-warning">{{ $resumen->pendientes }} pendientes</span></div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 text-center it-table">
                <thead><tr><th>OT</th><th class="text-left">Código / Descripción</th><th>Cantidad</th><th>Fecha pedido</th><th>Ingreso Terminación</th><th>Cantidad ingreso</th><th>Tiempo</th><th>Estado</th></tr></thead>
                <tbody>
                @foreach($ots as $ot)
                    <tr>
                        <td><strong class="it-ot">{{ $ot->nro_ot }}</strong></td>
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
                        <td>@if($ot->completo)<span class="it-state is-complete">COMPLETO</span>@else<span class="it-state is-pending">PENDIENTE</span>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div></section>
@endsection

@push('page_css')
<style>
.it-eyebrow{font-size:10px;font-weight:900;letter-spacing:.12em;color:#94a3b8}.it-title{font-size:29px;font-weight:850;color:#0f172a}
.it-detail-hero,.it-time-card{background:#fff;border:1px solid #e6ebf1;border-radius:16px;padding:22px;box-shadow:0 7px 22px rgba(15,23,42,.05)}
.it-detail-value{font-size:42px;line-height:1;font-weight:900;color:#0f172a}.it-progress{height:9px;background:#eef2f7;border-radius:999px;overflow:hidden}.it-progress div{height:100%;background:#6366f1;border-radius:999px}
.it-time{background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:20px;text-align:center}.it-time span{display:block;font-size:10px;text-transform:uppercase;font-weight:900;letter-spacing:.06em;color:#94a3b8}.it-time strong{display:block;font-size:34px;line-height:1;margin:8px 0 4px}.it-time small{color:#64748b}
.it-mini{height:100%;min-height:110px;background:#fff;border:1px solid #e6ebf1;border-radius:14px;padding:17px;box-shadow:0 5px 18px rgba(15,23,42,.04);position:relative;overflow:hidden}.it-mini:before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:#6366f1}.it-mini.is-success:before{background:#22c55e}.it-mini.is-warning:before{background:#f59e0b}.it-mini strong{display:block;font-size:26px;color:#0f172a}.it-mini span{display:block;font-size:12px;font-weight:800;color:#334155}.it-mini small{color:#94a3b8;font-size:10px}
.it-step{padding:12px}.it-step:not(:last-child){border-right:1px solid #eef2f6}.it-step span{display:block;font-size:10px;text-transform:uppercase;font-weight:900;color:#94a3b8;letter-spacing:.05em}.it-step strong{display:block;font-size:16px;color:#0f172a;margin-top:4px}
.it-table-card{border:1px solid #e6ebf1;border-radius:14px;overflow:hidden;box-shadow:0 5px 18px rgba(15,23,42,.04)}.it-table th{background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}.it-table td{font-size:12px;vertical-align:middle!important}.it-ot{font-size:14px;color:#3730a3}
.it-state{display:inline-block;border-radius:999px;padding:5px 8px;font-size:9px;font-weight:900}.it-state.is-complete{background:#ecfdf5;color:#047857}.it-state.is-progress{background:#fff7ed;color:#c2410c}.it-state.is-pending{background:#f1f5f9;color:#64748b}
@media(max-width:767.98px){.it-title{font-size:23px}.it-step:not(:last-child){border-right:0;border-bottom:1px solid #eef2f6}}
</style>
@endpush
