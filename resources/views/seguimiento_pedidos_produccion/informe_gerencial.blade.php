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
            <div class="col-lg-8">
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

            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="mgr-focus">
                    <div class="mgr-focus-label">FOCO DE HOY</div>
                    <div class="mgr-focus-value">{{ number_format($resumen->urgentes,0,',','.') }}</div>
                    <div class="mgr-focus-title">OT urgentes</div>
                    <div class="mgr-focus-meta">
                        {{ number_format($resumen->prendas_pendientes,0,',','.') }} prendas pendientes en total
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mgr-kpis">
        <div class="col-xl col-lg-4 col-md-6 mb-3">
            <div class="mgr-kpi">
                <div class="mgr-kpi-icon is-blue"><i class="fas fa-clipboard-list"></i></div>
                <div>
                    <div class="mgr-kpi-value">{{ number_format($resumen->pedidos_con_pendiente,0,',','.') }}</div>
                    <div class="mgr-kpi-label">Pedidos con pendientes</div>
                    <div class="mgr-kpi-meta">{{ number_format($resumen->pedidos_completos,0,',','.') }} pedidos completos</div>
                </div>
            </div>
        </div>

        <div class="col-xl col-lg-4 col-md-6 mb-3">
            <div class="mgr-kpi">
                <div class="mgr-kpi-icon is-amber"><i class="fas fa-hourglass-half"></i></div>
                <div>
                    <div class="mgr-kpi-value">{{ number_format($resumen->ots_pendientes,0,',','.') }}</div>
                    <div class="mgr-kpi-label">OT pendientes</div>
                    <div class="mgr-kpi-meta">de {{ number_format($resumen->ots,0,',','.') }} OT totales</div>
                </div>
            </div>
        </div>

        <div class="col-xl col-lg-4 col-md-6 mb-3">
            <div class="mgr-kpi">
                <div class="mgr-kpi-icon is-violet"><i class="fas fa-boxes"></i></div>
                <div>
                    <div class="mgr-kpi-value">{{ number_format($resumen->prendas_pendientes,0,',','.') }}</div>
                    <div class="mgr-kpi-label">Prendas pendientes</div>
                    <div class="mgr-kpi-meta">asociadas a OT sin cierre</div>
                </div>
            </div>
        </div>

        <div class="col-xl col-lg-4 col-md-6 mb-3">
            <div class="mgr-kpi">
                <div class="mgr-kpi-icon is-red"><i class="fas fa-exclamation-triangle"></i></div>
                <div>
                    <div class="mgr-kpi-value">{{ number_format($resumen->urgentes,0,',','.') }}</div>
                    <div class="mgr-kpi-label">OT urgentes</div>
                    <div class="mgr-kpi-meta">2 días o más pendientes</div>
                </div>
            </div>
        </div>

        <div class="col-xl col-lg-4 col-md-6 mb-3">
            <div class="mgr-kpi">
                <div class="mgr-kpi-icon is-slate"><i class="fas fa-stopwatch"></i></div>
                <div>
                    <div class="mgr-kpi-value">{{ number_format($resumen->antiguedad_maxima,0,',','.') }}</div>
                    <div class="mgr-kpi-label">Días máx. de espera</div>
                    <div class="mgr-kpi-meta">promedio {{ number_format($resumen->promedio_pendiente,1,',','.') }} días</div>
                </div>
            </div>
        </div>
    </div>

    <div class="mgr-summary mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="mgr-summary-title"><i class="fas fa-bullseye mr-2"></i>Lectura ejecutiva</div>
                <div class="mgr-summary-text">
                    Hay <strong>{{ number_format($resumen->ots_pendientes,0,',','.') }} OT</strong> pendientes de llegar a Terminación,
                    pertenecientes a <strong>{{ number_format($resumen->pedidos_con_pendiente,0,',','.') }} pedidos</strong>.
                    @if($resumen->urgentes > 0)
                        <span class="text-danger font-weight-bold">{{ $resumen->urgentes }} requieren atención prioritaria.</span>
                    @else
                        <span class="text-success font-weight-bold">No hay OT en condición urgente.</span>
                    @endif
                </div>
            </div>
            <div class="col-lg-4 mt-3 mt-lg-0">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="mgr-mini-value text-success">{{ number_format($resumen->ots_completas,0,',','.') }}</div>
                        <div class="mgr-mini-label">OT completas</div>
                    </div>
                    <div class="col-6">
                        <div class="mgr-mini-value {{ $resumen->sin_proceso > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($resumen->sin_proceso,0,',','.') }}</div>
                        <div class="mgr-mini-label">Sin proceso</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mgr-table-card">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="card-title font-weight-bold mb-0">
                    <i class="fas fa-list-ol mr-2 text-danger"></i>Prioridades de Producción
                </h3>
                <small class="text-muted">Ordenado por urgencia y días transcurridos desde la fecha del pedido.</small>
            </div>
            <div class="mt-2 mt-md-0">
                <span class="mgr-legend is-urgent"><i class="fas fa-circle mr-1"></i>Urgente ≥ 2 días</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 mgr-table">
                <thead>
                    <tr>
                        <th>Prioridad</th>
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
                    <tr class="{{ $fila->urgente ? 'is-urgent-row' : '' }}">
                        <td>
                            @if($fila->urgente)
                                <span class="mgr-priority is-urgent"><i class="fas fa-exclamation-triangle mr-1"></i>URGENTE</span>
                            @else
                                <span class="mgr-priority is-normal">NORMAL</span>
                            @endif
                        </td>
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
                                <span class="mgr-process is-empty">SIN PROCESO</span>
                            @else
                                <span class="mgr-process">{{ $fila->proceso_actual }}</span>
                            @endif
                        </td>
                        <td>{{ $fila->fecha_proceso_actual ? date('d/m/Y',strtotime($fila->fecha_proceso_actual)) : '-' }}</td>
                        <td>
                            <strong class="{{ $fila->urgente ? 'text-danger' : 'text-dark' }}">
                                {{ number_format($fila->dias_pendiente,0,',','.') }}
                                {{ $fila->dias_pendiente == 1 ? 'día' : 'días' }}
                            </strong>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
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
.mgr-focus{
    text-align:center;background:#fff7ed;border:1px solid #fed7aa;border-radius:14px;padding:20px
}
.mgr-focus-label{font-size:10px;font-weight:850;letter-spacing:.08em;color:#c2410c}
.mgr-focus-value{font-size:36px;line-height:1;font-weight:900;color:#9a3412;margin:8px 0 4px}
.mgr-focus-title{font-size:13px;font-weight:800;color:#7c2d12}
.mgr-focus-meta{font-size:11px;color:#9a3412;margin-top:4px}
.mgr-kpi{
    min-height:112px;display:flex;align-items:center;gap:13px;
    background:#fff;border:1px solid #e6ebf1;border-radius:14px;
    padding:16px;box-shadow:0 5px 18px rgba(15,23,42,.04)
}
.mgr-kpi-icon{
    width:42px;height:42px;border-radius:12px;display:flex;align-items:center;
    justify-content:center;flex:0 0 42px;font-size:16px
}
.mgr-kpi-icon.is-blue{background:#eff6ff;color:#2563eb}
.mgr-kpi-icon.is-amber{background:#fffbeb;color:#d97706}
.mgr-kpi-icon.is-violet{background:#f5f3ff;color:#7c3aed}
.mgr-kpi-icon.is-red{background:#fef2f2;color:#dc2626}
.mgr-kpi-icon.is-slate{background:#f1f5f9;color:#475569}
.mgr-kpi-value{font-size:24px;line-height:1;font-weight:900;color:#0f172a}
.mgr-kpi-label{font-size:12px;font-weight:800;color:#334155;margin-top:5px}
.mgr-kpi-meta{font-size:10px;color:#94a3b8;margin-top:2px}
.mgr-summary{
    background:#f8fafc;border:1px solid #e6ebf1;border-radius:14px;padding:17px 20px
}
.mgr-summary-title{font-size:13px;font-weight:850;color:#334155;margin-bottom:4px}
.mgr-summary-text{font-size:12px;color:#64748b}
.mgr-mini-value{font-size:22px;font-weight:900}
.mgr-mini-label{font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;font-weight:800}
.mgr-table-card{border:1px solid #e6ebf1;border-radius:14px;overflow:hidden;box-shadow:0 5px 18px rgba(15,23,42,.04)}
.mgr-table th{
    background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;
    letter-spacing:.04em;white-space:nowrap;vertical-align:middle!important
}
.mgr-table td{font-size:12px;vertical-align:middle!important;text-align:center}
.mgr-table td:nth-child(6){text-align:left}
.is-urgent-row{background:#fff8f8}
.mgr-priority{display:inline-flex;align-items:center;border-radius:999px;padding:5px 8px;font-size:9px;font-weight:850}
.mgr-priority.is-urgent{background:#fef2f2;color:#b91c1c}
.mgr-priority.is-normal{background:#f1f5f9;color:#64748b}
.mgr-ot{font-size:13px;font-weight:900;color:#0f172a}
.mgr-code{font-size:12px;color:#0f172a}
.mgr-process{
    display:inline-block;background:#eff6ff;color:#1d4ed8;border-radius:7px;
    padding:5px 7px;font-size:9px;font-weight:800;max-width:220px
}
.mgr-process.is-empty{background:#fef2f2;color:#b91c1c}
.mgr-legend{font-size:10px;font-weight:750;color:#64748b}
.mgr-legend.is-urgent{color:#b91c1c}
.mgr-empty-icon{font-size:34px;color:#22c55e}
@media(max-width:767.98px){
    .mgr-title{font-size:24px}
    .mgr-avance-wrap{align-items:flex-start;flex-direction:column}
    .mgr-avance-detail{text-align:left}
}
</style>
@endpush
