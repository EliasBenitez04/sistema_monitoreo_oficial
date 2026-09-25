@extends('layouts.app')

@section('content')
@php
    $pendientes = max(0, $resumen->ots - $resumen->completas);
    $porcentaje = min(100, max(0, (int) $resumen->porcentaje));
@endphp

<section class="content-header pb-2">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <div class="prod-eyebrow">SEGUIMIENTO A PRODUCCIÓN</div>
            <h1 class="prod-title mb-1">Pedido {{ $pedido->nro_pedido }}</h1>
            <p class="text-muted mb-0">El pedido se completa cuando todas sus OT alcanzan <strong>INGRESO TERMINACIÓN</strong>.</p>
        </div>
        <a href="{{ route('seguimiento-produccion.index') }}" class="btn btn-outline-secondary mt-2 mt-md-0">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
</div>
</section>

<section class="content">
<div class="container-fluid">

    <div class="prod-hero mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center flex-wrap mb-2">
                    <span class="prod-status {{ $resumen->completo ? 'is-complete' : 'is-progress' }}">
                        <i class="fas {{ $resumen->completo ? 'fa-check-circle' : 'fa-cogs' }} mr-1"></i>
                        {{ $resumen->completo ? 'COMPLETO' : 'EN PROCESO' }}
                    </span>
                    <span class="text-muted small ml-2">
                        {{ $resumen->completas }} de {{ $resumen->ots }} OT alcanzaron Terminación
                    </span>
                </div>

                <div class="prod-hero-number">{{ $porcentaje }}%</div>
                <div class="prod-hero-label">Avance del pedido hacia Terminación</div>

                <div class="prod-main-progress mt-3">
                    <div class="prod-main-progress-bar" style="width:{{ $porcentaje }}%"></div>
                </div>

                <div class="d-flex justify-content-between mt-2 small">
                    <span class="text-success font-weight-bold">{{ $resumen->completas }} OT completas</span>
                    <span class="text-warning font-weight-bold">{{ $pendientes }} OT pendientes</span>
                </div>
            </div>

            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="prod-time-box">
                    <div class="prod-time-label">Tiempo del pedido</div>

                    @if(!$resumen->completo && $resumen->dias_en_curso !== null)
                        <div class="prod-time-value text-warning">
                            {{ $resumen->dias_en_curso }}
                            <span>{{ $resumen->dias_en_curso == 1 ? 'día' : 'días' }}</span>
                        </div>
                        <div class="prod-time-meta">en curso desde {{ $pedido->fecha_pedido ? $pedido->fecha_pedido->format('d/m/Y') : '-' }}</div>
                    @elseif($resumen->completo && $resumen->dias !== null)
                        <div class="prod-time-value text-success">
                            {{ $resumen->dias }}
                            <span>{{ $resumen->dias == 1 ? 'día' : 'días' }}</span>
                        </div>
                        <div class="prod-time-meta">tiempo total de atención</div>
                    @else
                        <div class="prod-time-value">—</div>
                        <div class="prod-time-meta">sin fecha suficiente para medir</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row prod-summary-row">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="prod-summary-card">
                <div class="prod-summary-icon"><i class="fas fa-list-ol"></i></div>
                <div>
                    <div class="prod-summary-value">{{ number_format($resumen->ots,0,',','.') }}</div>
                    <div class="prod-summary-label">OT del pedido</div>
                    <div class="prod-summary-meta">Total a controlar</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="prod-summary-card">
                <div class="prod-summary-icon"><i class="fas fa-boxes"></i></div>
                <div>
                    <div class="prod-summary-value">{{ number_format($resumen->cantidad,0,',','.') }}</div>
                    <div class="prod-summary-label">Prendas ordenadas</div>
                    <div class="prod-summary-meta">Cantidad total del pedido</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="prod-summary-card is-success">
                <div class="prod-summary-icon"><i class="fas fa-check"></i></div>
                <div>
                    <div class="prod-summary-value">{{ number_format($resumen->completas,0,',','.') }}</div>
                    <div class="prod-summary-label">OT en Terminación</div>
                    <div class="prod-summary-meta">{{ $porcentaje }}% del pedido</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="prod-summary-card {{ $pendientes > 0 ? 'is-warning' : 'is-success' }}">
                <div class="prod-summary-icon"><i class="fas fa-hourglass-half"></i></div>
                <div>
                    <div class="prod-summary-value">{{ number_format($pendientes,0,',','.') }}</div>
                    <div class="prod-summary-label">OT pendientes</div>
                    <div class="prod-summary-meta">Aún no ingresaron a Terminación</div>
                </div>
            </div>
        </div>
    </div>

    <div class="prod-timeline-card mb-4">
        <div class="prod-section-title">
            <i class="fas fa-stopwatch mr-2"></i>Tiempo de atención
        </div>
        <div class="row text-center mt-3">
            <div class="col-md-4 prod-time-step">
                <div class="prod-time-step-icon"><i class="far fa-calendar-alt"></i></div>
                <div class="prod-time-step-label">Fecha del pedido</div>
                <div class="prod-time-step-value">{{ $pedido->fecha_pedido ? $pedido->fecha_pedido->format('d/m/Y') : '-' }}</div>
            </div>

            <div class="col-md-4 prod-time-step">
                <div class="prod-time-step-icon"><i class="fas fa-sign-in-alt"></i></div>
                <div class="prod-time-step-label">Último ingreso a Terminación</div>
                <div class="prod-time-step-value">{{ $resumen->ultima_fecha ? date('d/m/Y',strtotime($resumen->ultima_fecha)) : '-' }}</div>
            </div>

            <div class="col-md-4 prod-time-step">
                <div class="prod-time-step-icon"><i class="fas fa-clock"></i></div>
                <div class="prod-time-step-label">{{ $resumen->completo ? 'Tiempo total' : 'Antigüedad del pedido' }}</div>
                <div class="prod-time-step-value">
                    @if(!$resumen->completo && $resumen->dias_en_curso !== null)
                        {{ $resumen->dias_en_curso }} {{ $resumen->dias_en_curso == 1 ? 'día' : 'días' }} en curso
                    @elseif($resumen->completo && $resumen->dias !== null)
                        {{ $resumen->dias }} {{ $resumen->dias == 1 ? 'día' : 'días' }}
                    @else
                        -
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card prod-table-card">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-list mr-2 text-primary"></i>Seguimiento por OT</h3>
                <small class="text-muted">Detalle de qué OTs ya alcanzaron Terminación y cuáles siguen pendientes.</small>
            </div>
            <div class="mt-2 mt-md-0">
                <span class="badge badge-success px-2 py-2 mr-1">{{ $resumen->completas }} completas</span>
                <span class="badge badge-warning px-2 py-2">{{ $pendientes }} pendientes</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 text-center prod-detail-table">
                <thead>
                    <tr>
                        <th>OT</th>
                        <th class="text-left">Código / Descripción</th>
                        <th>Cantidad</th>
                        <th>Fecha pedido</th>
                        <th>Ingreso Terminación</th>
                        <th>Cantidad ingreso</th>
                        <th>Tiempo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($ots as $ot)
                    <tr>
                        <td><span class="prod-ot-number">{{ $ot->nro_ot }}</span></td>
                        <td class="text-left">
                            <strong class="text-dark">{{ $ot->codigo }}</strong>
                            <small class="d-block text-muted">{{ $ot->descripcion }}</small>
                        </td>
                        <td><strong>{{ number_format($ot->cantidad_orden,0,',','.') }}</strong></td>
                        <td>{{ $pedido->fecha_pedido ? $pedido->fecha_pedido->format('d/m/Y') : '-' }}</td>
                        <td>
                            @if($ot->fecha_ingreso)
                                <strong>{{ date('d/m/Y',strtotime($ot->fecha_ingreso)) }}</strong>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ number_format($ot->cantidad_ingreso,0,',','.') }}</td>
                        <td>
                            @if($ot->dias !== null)
                                <strong>{{ $ot->dias }} {{ $ot->dias == 1 ? 'día' : 'días' }}</strong>
                            @elseif($ot->ingreso_previo)
                                <span class="text-muted">Previo al pedido</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($ot->completo)
                                <span class="prod-state is-complete"><i class="fas fa-check mr-1"></i>COMPLETO</span>
                            @else
                                <span class="prod-state is-pending"><i class="fas fa-clock mr-1"></i>PENDIENTE</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
</section>
@endsection

@push('page_css')
<style>
.prod-eyebrow{font-size:10px;font-weight:800;letter-spacing:.12em;color:#94a3b8;margin-bottom:3px}
.prod-title{font-size:30px;font-weight:800;color:#0f172a}

.prod-hero{
    background:#fff;
    border:1px solid #e5eaf0;
    border-radius:16px;
    padding:24px;
    box-shadow:0 8px 24px rgba(15,23,42,.05);
}
.prod-status{
    display:inline-flex;align-items:center;
    padding:6px 10px;border-radius:999px;
    font-size:11px;font-weight:800;letter-spacing:.03em;
}
.prod-status.is-progress{background:#fff7ed;color:#c2410c;border:1px solid #fed7aa}
.prod-status.is-complete{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0}
.prod-hero-number{font-size:42px;line-height:1;font-weight:900;color:#0f172a;margin-top:12px}
.prod-hero-label{font-size:13px;color:#64748b;margin-top:5px}
.prod-main-progress{height:10px;background:#eef2f7;border-radius:999px;overflow:hidden}
.prod-main-progress-bar{height:100%;background:#22c55e;border-radius:999px;transition:width .25s ease}

.prod-time-box{
    background:#f8fafc;border:1px solid #e7edf3;border-radius:14px;
    padding:20px;text-align:center;
}
.prod-time-label{font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:800;color:#94a3b8}
.prod-time-value{font-size:34px;line-height:1.1;font-weight:900;color:#0f172a;margin:8px 0 4px}
.prod-time-value span{font-size:15px;font-weight:700}
.prod-time-meta{font-size:11px;color:#94a3b8}

.prod-summary-card{
    display:flex;align-items:center;gap:14px;
    background:#fff;border:1px solid #e6ebf1;border-radius:14px;
    padding:17px;min-height:112px;
    box-shadow:0 5px 18px rgba(15,23,42,.04);
    position:relative;overflow:hidden;
}
.prod-summary-card:before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:#3b82f6}
.prod-summary-card.is-success:before{background:#22c55e}
.prod-summary-card.is-warning:before{background:#f59e0b}
.prod-summary-icon{
    width:42px;height:42px;border-radius:11px;
    background:#f1f5f9;display:flex;align-items:center;justify-content:center;
    color:#334155;font-size:17px;flex:0 0 42px;
}
.prod-summary-value{font-size:25px;line-height:1;font-weight:850;color:#0f172a}
.prod-summary-label{font-size:12px;font-weight:750;color:#334155;margin-top:5px}
.prod-summary-meta{font-size:10px;color:#94a3b8;margin-top:2px}

.prod-timeline-card{
    background:#fff;border:1px solid #e6ebf1;border-radius:14px;
    padding:19px 20px;box-shadow:0 5px 18px rgba(15,23,42,.04);
}
.prod-section-title{font-size:13px;font-weight:800;color:#334155}
.prod-time-step{padding:12px 15px}
.prod-time-step:not(:last-child){border-right:1px solid #eef2f6}
.prod-time-step-icon{
    width:34px;height:34px;border-radius:50%;margin:0 auto 8px;
    background:#f1f5f9;color:#475569;
    display:flex;align-items:center;justify-content:center;
}
.prod-time-step-label{font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;font-weight:800}
.prod-time-step-value{font-size:16px;color:#0f172a;font-weight:800;margin-top:3px}

.prod-table-card{border:1px solid #e6ebf1;border-radius:14px;overflow:hidden;box-shadow:0 5px 18px rgba(15,23,42,.04)}
.prod-detail-table th{
    background:#f8fafc;color:#64748b;
    font-size:10px;text-transform:uppercase;letter-spacing:.04em;
    white-space:nowrap;border-top:1px solid #eef2f6!important;
}
.prod-detail-table td{vertical-align:middle!important;font-size:12px}
.prod-ot-number{font-size:14px;font-weight:850;color:#0f172a}
.prod-state{
    display:inline-flex;align-items:center;padding:5px 8px;border-radius:999px;
    font-size:10px;font-weight:800;white-space:nowrap;
}
.prod-state.is-complete{background:#ecfdf5;color:#047857}
.prod-state.is-pending{background:#fff7ed;color:#c2410c}

@media(max-width:767.98px){
    .prod-time-step:not(:last-child){border-right:0;border-bottom:1px solid #eef2f6}
    .prod-title{font-size:24px}
    .prod-hero-number{font-size:34px}
}
</style>
@endpush
