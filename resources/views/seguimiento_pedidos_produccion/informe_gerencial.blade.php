@extends('layouts.app')

@section('content')
@php
    $avance = min(100, max(0, (float) $resumen->avance));
@endphp

<section class="content-header pb-2">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <div class="mgr-eyebrow">PRODUCCIÓN · CONTROL GERENCIAL</div>
            <h1 class="mgr-title mb-1">Informe Gerencial de Producción</h1>
            <p class="text-muted mb-0">Pedidos P pendientes de alcanzar <strong>INGRESO TERMINACIÓN</strong>, ordenados por prioridad y antigüedad.</p>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="{{ route('seguimiento-produccion.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Seguimiento Producción
            </a>
        </div>
    </div>
</div>
</section>

<section class="content">
<div class="container-fluid">

    <div class="mgr-hero mb-4">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-flex align-items-center flex-wrap">
                    <span class="mgr-pill {{ $resumen->ots_pendientes > 0 ? 'is-warning' : 'is-success' }}">
                        <i class="fas {{ $resumen->ots_pendientes > 0 ? 'fa-industry' : 'fa-check-circle' }} mr-1"></i>
                        {{ $resumen->ots_pendientes > 0 ? 'PRODUCCIÓN EN CURSO' : 'SIN PENDIENTES' }}
                    </span>
                    <span class="text-muted small ml-2">{{ $resumen->pedidos }} pedidos P monitoreados</span>
                </div>

                <div class="mgr-avance-wrap mt-3">
                    <div>
                        <div class="mgr-avance-num">{{ number_format($avance,1,',','.') }}%</div>
                        <div class="mgr-avance-label">Avance global hacia Terminación</div>
                    </div>
                    <div class="text-right">
                        <div class="mgr-avance-detail">{{ number_format($resumen->ots_completas,0,',','.') }} / {{ number_format($resumen->ots,0,',','.') }} OT</div>
                        <small class="text-muted">ya alcanzaron Ingreso Terminación</small>
                    </div>
                </div>

                <div class="mgr-progress mt-3">
                    <div class="mgr-progress-bar" style="width:{{ $avance }}%"></div>
                </div>
            </div>

        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-6 mb-3 mb-lg-0">
            <div class="mgr-main-kpi is-pending">
                <div class="mgr-main-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
                <div class="mgr-main-kpi-content">
                    <div class="mgr-main-kpi-label">OT pendientes</div>
                    <div class="mgr-main-kpi-value">{{ number_format($resumen->ots_pendientes,0,',','.') }}</div>
                    <div class="mgr-main-kpi-meta">
                        de {{ number_format($resumen->ots,0,',','.') }} OT totales ·
                        {{ number_format($resumen->ots_completas,0,',','.') }} ya ingresaron a Terminación
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="mgr-main-kpi is-garments">
                <div class="mgr-main-kpi-icon"><i class="fas fa-boxes"></i></div>
                <div class="mgr-main-kpi-content">
                    <div class="mgr-main-kpi-label">Prendas pendientes</div>
                    <div class="mgr-main-kpi-value">{{ number_format($resumen->prendas_pendientes,0,',','.') }}</div>
                    <div class="mgr-main-kpi-meta">
                        asociadas a {{ number_format($resumen->ots_pendientes,0,',','.') }} OT que aún no llegaron a Ingreso Terminación
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mgr-summary mb-4">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="mgr-summary-title"><i class="fas fa-bullseye mr-2"></i>Lectura ejecutiva</div>
                <div class="mgr-summary-text">
                    Hay <strong>{{ number_format($resumen->ots_pendientes,0,',','.') }} OT</strong> pendientes de llegar a Terminación,
                    pertenecientes a <strong>{{ number_format($resumen->pedidos_con_pendiente,0,',','.') }} pedidos</strong>.
                    El detalle inferior está agrupado por <strong>proceso actual</strong> para identificar rápidamente dónde se concentran las OT pendientes.
                </div>
            </div>
        </div>
    </div>

    <div class="card mgr-table-card">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="card-title font-weight-bold mb-0">
                    <i class="fas fa-stream mr-2 text-primary"></i>OT pendientes por proceso
                </h3>
                <small class="text-muted">Ordenado por proceso actual; dentro de cada proceso, primero aparecen las OT con más días pendientes.</small>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 mgr-table">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Fecha pedido</th>
                        <th>OT</th>
                        <th>Cantidad</th>
                        <th>Código / Descripción</th>
                        <th>Proceso actual</th>
                        <th>Fecha proceso</th>
                        <th>Días desde pedido</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($pendientes as $fila)
                    <tr>
                        <td><strong class="text-dark">{{ $fila->nro_pedido }}</strong></td>
                        <td>{{ $fila->fecha_pedido ? date('d/m/Y',strtotime($fila->fecha_pedido)) : '-' }}</td>
                        <td><span class="mgr-ot">{{ $fila->nro_ot }}</span></td>
                        <td><strong>{{ number_format($fila->cantidad_orden,0,',','.') }}</strong></td>
                        <td class="text-left">
                            <strong class="mgr-code">{{ $fila->codigo ?: '-' }}</strong>
                            <small class="d-block text-muted">{{ $fila->descripcion ?: '-' }}</small>
                        </td>
                        <td>
                            @if(strtoupper(trim($fila->proceso_actual)) === 'SIN PROCESO')
                                <span class="mgr-process is-empty"><i class="fas fa-exclamation-circle mr-1"></i>SIN PROCESO</span>
                            @else
                                <span class="mgr-process">{{ $fila->proceso_actual }}</span>
                            @endif
                        </td>
                        <td>{{ $fila->fecha_proceso_actual ? date('d/m/Y',strtotime($fila->fecha_proceso_actual)) : '-' }}</td>
                        <td>
                            <strong class="text-dark">
                                {{ number_format($fila->dias_pendiente,0,',','.') }}
                                {{ $fila->dias_pendiente == 1 ? 'día' : 'días' }}
                            </strong>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="mgr-empty-icon"><i class="fas fa-check-circle"></i></div>
                            <strong class="text-success d-block mt-2">Producción sin pendientes</strong>
                            <small class="text-muted">Todas las OT de los pedidos P alcanzaron Ingreso Terminación.</small>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
</section>
@endsection

@push('page_css')
<style>
.mgr-eyebrow{font-size:10px;font-weight:850;letter-spacing:.12em;color:#94a3b8;margin-bottom:3px}
.mgr-title{font-size:29px;font-weight:850;color:#0f172a}
.mgr-hero{
    background:#fff;border:1px solid #e5eaf0;border-radius:16px;
    padding:24px;box-shadow:0 8px 24px rgba(15,23,42,.05)
}
.mgr-pill{display:inline-flex;align-items:center;border-radius:999px;padding:6px 10px;font-size:10px;font-weight:850;letter-spacing:.04em}
.mgr-pill.is-warning{background:#fff7ed;color:#c2410c;border:1px solid #fed7aa}
.mgr-pill.is-success{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0}
.mgr-avance-wrap{display:flex;justify-content:space-between;align-items:flex-end;gap:20px}
.mgr-avance-num{font-size:43px;line-height:1;font-weight:900;color:#0f172a}
.mgr-avance-label{font-size:13px;color:#64748b;margin-top:5px}
.mgr-avance-detail{font-size:18px;font-weight:850;color:#334155}
.mgr-progress{height:10px;background:#eef2f7;border-radius:999px;overflow:hidden}
.mgr-progress-bar{height:100%;background:#22c55e;border-radius:999px}
.mgr-main-kpi{
    min-height:148px;
    display:flex;
    align-items:center;
    gap:20px;
    background:#fff;
    border:1px solid #e6ebf1;
    border-radius:16px;
    padding:22px 24px;
    box-shadow:0 7px 22px rgba(15,23,42,.05);
    position:relative;
    overflow:hidden;
}
.mgr-main-kpi:before{
    content:'';
    position:absolute;
    left:0;top:0;bottom:0;
    width:5px;
}
.mgr-main-kpi.is-pending:before{background:#f59e0b}
.mgr-main-kpi.is-garments:before{background:#7c3aed}
.mgr-main-kpi-icon{
    width:58px;height:58px;
    border-radius:15px;
    display:flex;align-items:center;justify-content:center;
    flex:0 0 58px;
    font-size:22px;
}
.mgr-main-kpi.is-pending .mgr-main-kpi-icon{background:#fffbeb;color:#d97706}
.mgr-main-kpi.is-garments .mgr-main-kpi-icon{background:#f5f3ff;color:#7c3aed}
.mgr-main-kpi-label{
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.07em;
    font-weight:900;
    color:#64748b;
}
.mgr-main-kpi-value{
    font-size:38px;
    line-height:1;
    font-weight:900;
    color:#0f172a;
    margin:7px 0 6px;
}
.mgr-main-kpi-meta{
    font-size:11px;
    color:#94a3b8;
    line-height:1.4;
}
.mgr-summary{
    background:#f8fafc;border:1px solid #e6ebf1;border-radius:14px;padding:17px 20px
}
.mgr-summary-title{font-size:13px;font-weight:850;color:#334155;margin-bottom:4px}
.mgr-summary-text{font-size:12px;color:#64748b}
.mgr-table-card{border:1px solid #e6ebf1;border-radius:14px;overflow:hidden;box-shadow:0 5px 18px rgba(15,23,42,.04)}
.mgr-table th{
    background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;
    letter-spacing:.04em;white-space:nowrap;vertical-align:middle!important
}
.mgr-table td{font-size:12px;vertical-align:middle!important;text-align:center}
.mgr-table td:nth-child(6){text-align:left}
.mgr-ot{font-size:13px;font-weight:900;color:#0f172a}
.mgr-code{font-size:12px;color:#0f172a}
.mgr-process{
    display:inline-block;
    background:#eff6ff;
    color:#1d4ed8;
    border:1px solid #dbeafe;
    border-radius:9px;
    padding:8px 11px;
    font-size:12px;
    line-height:1.35;
    font-weight:900;
    letter-spacing:.01em;
    min-width:180px;
    max-width:340px;
    white-space:normal;
    text-align:center;
}
.mgr-process.is-empty{
    background:#fef2f2;
    color:#b91c1c;
    border-color:#fecaca;
}
.mgr-empty-icon{font-size:34px;color:#22c55e}
@media(max-width:767.98px){
    .mgr-title{font-size:24px}
    .mgr-avance-wrap{align-items:flex-start;flex-direction:column}
    .mgr-avance-detail{text-align:left}
}
</style>
@endpush
