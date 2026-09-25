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
                    <div class="mgr-main-kpi-topline"><span>CONTROL DE CARGA</span></div>
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
                    <div class="mgr-main-kpi-topline"><span>VOLUMEN PENDIENTE</span></div>
                    <div class="mgr-main-kpi-label">Prendas pendientes</div>
                    <div class="mgr-main-kpi-value">{{ number_format($resumen->prendas_pendientes,0,',','.') }}</div>
                    <div class="mgr-main-kpi-meta">
                        asociadas a {{ number_format($resumen->ots_pendientes,0,',','.') }} OT que aún no llegaron a Ingreso Terminación
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mgr-process-section mb-4">
        <div class="d-flex justify-content-between align-items-end flex-wrap mb-3">
            <div>
                <div class="mgr-section-eyebrow">DISTRIBUCIÓN DE LA CARGA</div>
                <h3 class="mgr-section-title mb-1">OT pendientes por proceso actual</h3>
                <p class="text-muted small mb-0">Cada tarjeta muestra cuántas OT y prendas están concentradas actualmente en ese proceso.</p>
            </div>
            <div class="mgr-process-total mt-2 mt-md-0">
                <strong>{{ number_format($resumen->ots_pendientes,0,',','.') }}</strong>
                <span>OT pendientes</span>
            </div>
        </div>

        <div class="row">
            @forelse($porProcesos as $proceso)
                @php
                    $sinProceso = strtoupper(trim($proceso->proceso)) === 'SIN PROCESO';
                @endphp
                <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                    <div class="mgr-process-kpi {{ $sinProceso ? 'is-empty' : '' }}">
                        <div class="mgr-process-kpi-head">
                            <div class="mgr-process-kpi-icon">
                                <i class="fas {{ $sinProceso ? 'fa-exclamation-circle' : 'fa-cogs' }}"></i>
                            </div>
                            <div class="mgr-process-kpi-share">{{ number_format($proceso->porcentaje,1,',','.') }}%</div>
                        </div>

                        <div class="mgr-process-kpi-name">{{ $proceso->proceso }}</div>

                        <div class="mgr-process-kpi-stats">
                            <div>
                                <strong>{{ number_format($proceso->ots,0,',','.') }}</strong>
                                <span>OT</span>
                            </div>
                            <div>
                                <strong>{{ number_format($proceso->prendas,0,',','.') }}</strong>
                                <span>Prendas</span>
                            </div>
                            <div>
                                <strong>{{ number_format($proceso->pedidos,0,',','.') }}</strong>
                                <span>Pedidos</span>
                            </div>
                        </div>

                        <div class="mgr-process-kpi-progress">
                            <div style="width:{{ min(100,$proceso->porcentaje) }}%"></div>
                        </div>
                        <div class="mgr-process-kpi-foot">{{ number_format($proceso->porcentaje,1,',','.') }}% de las OT pendientes</div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="mgr-process-empty">
                        <i class="fas fa-check-circle mr-2"></i>No hay carga pendiente por proceso.
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <div class="mgr-summary mb-4">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="mgr-summary-title"><i class="fas fa-bullseye mr-2"></i>Lectura ejecutiva</div>
                <div class="mgr-summary-text">
                    Hay <strong>{{ number_format($resumen->ots_pendientes,0,',','.') }} OT</strong> pendientes de llegar a Terminación,
                    pertenecientes a <strong>{{ number_format($resumen->pedidos_con_pendiente,0,',','.') }} pedidos</strong>.
                    Los KPI superiores muestran la concentración por proceso y el detalle inferior permite revisar las OT que componen cada grupo.
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
                <small class="text-muted">Agrupado por proceso actual; dentro de cada grupo aparecen primero las OT con más días pendientes.</small>
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
                @php $procesoAnterior = null; @endphp
                @forelse($pendientes as $fila)
                    @php
                        $procesoFila = trim((string) $fila->proceso_actual) !== ''
                            ? trim((string) $fila->proceso_actual)
                            : 'SIN PROCESO';
                    @endphp

                    @if($procesoAnterior !== $procesoFila)
                        <tr class="mgr-process-group-row">
                            <td colspan="8">
                                <div class="mgr-process-group">
                                    <span class="mgr-process-group-icon"><i class="fas fa-cogs"></i></span>
                                    <strong>{{ $procesoFila }}</strong>
                                    <span>{{ $porProcesos->firstWhere('proceso', $procesoFila)->ots ?? 0 }} OT</span>
                                </div>
                            </td>
                        </tr>
                        @php $procesoAnterior = $procesoFila; @endphp
                    @endif

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
.mgr-main-kpi{
    transition:transform .18s ease, box-shadow .18s ease;
}
.mgr-main-kpi:hover{
    transform:translateY(-2px);
    box-shadow:0 11px 28px rgba(15,23,42,.08);
}
.mgr-main-kpi-topline span{
    display:inline-block;
    font-size:9px;
    font-weight:900;
    letter-spacing:.09em;
    color:#94a3b8;
    margin-bottom:5px;
}

.mgr-process-section{
    background:#fff;
    border:1px solid #e6ebf1;
    border-radius:16px;
    padding:20px;
    box-shadow:0 7px 22px rgba(15,23,42,.04);
}
.mgr-section-eyebrow{
    font-size:9px;
    font-weight:900;
    letter-spacing:.11em;
    color:#94a3b8;
}
.mgr-section-title{
    font-size:18px;
    font-weight:900;
    color:#0f172a;
}
.mgr-process-total{
    text-align:right;
}
.mgr-process-total strong{
    display:block;
    font-size:22px;
    line-height:1;
    color:#0f172a;
}
.mgr-process-total span{
    font-size:10px;
    text-transform:uppercase;
    font-weight:800;
    letter-spacing:.05em;
    color:#94a3b8;
}

.mgr-process-kpi{
    height:100%;
    min-height:185px;
    background:linear-gradient(180deg,#fff,#fbfdff);
    border:1px solid #e6ebf1;
    border-radius:14px;
    padding:16px;
    box-shadow:0 4px 14px rgba(15,23,42,.035);
    transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.mgr-process-kpi:hover{
    transform:translateY(-2px);
    border-color:#cbd5e1;
    box-shadow:0 9px 20px rgba(15,23,42,.07);
}
.mgr-process-kpi.is-empty{
    background:#fffafa;
    border-color:#fecaca;
}
.mgr-process-kpi-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:12px;
}
.mgr-process-kpi-icon{
    width:34px;
    height:34px;
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#eff6ff;
    color:#2563eb;
}
.mgr-process-kpi.is-empty .mgr-process-kpi-icon{
    background:#fef2f2;
    color:#dc2626;
}
.mgr-process-kpi-share{
    font-size:12px;
    font-weight:900;
    color:#475569;
}
.mgr-process-kpi-name{
    min-height:38px;
    font-size:13px;
    line-height:1.35;
    font-weight:900;
    color:#0f172a;
    margin-bottom:13px;
}
.mgr-process-kpi-stats{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:7px;
    margin-bottom:13px;
}
.mgr-process-kpi-stats div{
    background:#f8fafc;
    border-radius:8px;
    padding:7px 5px;
    text-align:center;
}
.mgr-process-kpi-stats strong{
    display:block;
    font-size:14px;
    line-height:1.1;
    color:#0f172a;
}
.mgr-process-kpi-stats span{
    display:block;
    margin-top:3px;
    font-size:8px;
    text-transform:uppercase;
    letter-spacing:.04em;
    font-weight:800;
    color:#94a3b8;
}
.mgr-process-kpi-progress{
    height:5px;
    overflow:hidden;
    border-radius:999px;
    background:#edf2f7;
}
.mgr-process-kpi-progress div{
    height:100%;
    border-radius:999px;
    background:#3b82f6;
}
.mgr-process-kpi.is-empty .mgr-process-kpi-progress div{
    background:#ef4444;
}
.mgr-process-kpi-foot{
    font-size:9px;
    color:#94a3b8;
    margin-top:6px;
}
.mgr-process-empty{
    border:1px dashed #bbf7d0;
    background:#f0fdf4;
    color:#15803d;
    border-radius:12px;
    padding:18px;
    text-align:center;
    font-weight:800;
}

.mgr-process-group-row td{
    background:#f1f5f9!important;
    border-top:2px solid #e2e8f0!important;
    border-bottom:1px solid #e2e8f0!important;
    padding:8px 12px!important;
}
.mgr-process-group{
    display:flex;
    align-items:center;
    gap:8px;
    text-align:left;
}
.mgr-process-group-icon{
    width:28px;
    height:28px;
    border-radius:8px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    background:#fff;
    color:#2563eb;
}
.mgr-process-group strong{
    font-size:12px;
    color:#0f172a;
}
.mgr-process-group span{
    font-size:9px;
    text-transform:uppercase;
    font-weight:900;
    letter-spacing:.05em;
    color:#64748b;
    background:#fff;
    border-radius:999px;
    padding:3px 7px;
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
.mgr-table td:nth-child(5){text-align:left}
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
