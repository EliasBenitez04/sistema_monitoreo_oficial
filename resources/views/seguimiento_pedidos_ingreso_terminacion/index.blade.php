@extends('layouts.app')

@section('content')
<section class="content-header pb-2">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <div class="it-eyebrow">CONTROL DE PEDIDOS</div>
            <h1 class="it-title mb-1"><i class="fas fa-sign-in-alt mr-2"></i>Seguimiento Pedido a Ingreso Terminación</h1>
            <p class="text-muted mb-0">Pedidos <strong>IT1, IT2...</strong> completos cuando todas sus OT alcanzan <strong>TERMINACION - TERMINACION</strong>.</p>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="{{ route('seguimiento-pedidos.index', ['abrir_import' => 1]) }}#importar-pedidos" class="btn btn-primary shadow-sm mr-1">
                <i class="fas fa-file-import mr-1"></i> Importar T / P / IT
            </a>
            <a href="{{ route('seguimiento-produccion.index') }}" class="btn btn-outline-secondary mr-1">
                <i class="fas fa-industry mr-1"></i> Producción
            </a>
            <a href="{{ route('seguimiento-pedidos.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-route mr-1"></i> Locales
            </a>
        </div>
    </div>
</div>
</section>

<section class="content">
<div class="container-fluid">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    @php
        $pendientes = max(0, $resumen->ots - $resumen->ots_completas);
        $avance = $resumen->ots > 0
            ? min(100, round(($resumen->ots_completas / $resumen->ots) * 100))
            : 0;
    @endphp

    <div class="it-hero mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="it-status"><i class="fas fa-stream mr-1"></i>AVANCE GENERAL</div>
                <div class="d-flex justify-content-between align-items-end mt-3">
                    <div>
                        <div class="it-hero-value">{{ $avance }}%</div>
                        <div class="it-hero-label">OT que ya ingresaron a Terminación</div>
                    </div>
                    <div class="text-right">
                        <strong class="it-hero-detail">{{ number_format($resumen->ots_completas,0,',','.') }} / {{ number_format($resumen->ots,0,',','.') }} OT</strong>
                        <small class="d-block text-muted">controladas en pedidos IT</small>
                    </div>
                </div>
                <div class="it-progress mt-3"><div style="width:{{ $avance }}%"></div></div>
            </div>
            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="it-side-box">
                    <span>Pedidos IT</span>
                    <strong>{{ number_format($resumen->pedidos,0,',','.') }}</strong>
                    <small>{{ number_format($resumen->completos,0,',','.') }} completos · {{ number_format(max(0,$resumen->pedidos-$resumen->completos),0,',','.') }} en curso</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="it-kpi">
                <div class="it-kpi-icon"><i class="fas fa-list-ol"></i></div>
                <div><strong>{{ number_format($resumen->ots,0,',','.') }}</strong><span>OT vinculadas</span><small>Total de OT en pedidos IT</small></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="it-kpi is-success">
                <div class="it-kpi-icon"><i class="fas fa-check"></i></div>
                <div><strong>{{ number_format($resumen->ots_completas,0,',','.') }}</strong><span>OT ingresadas</span><small>Ya tienen TERMINACION - TERMINACION</small></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="it-kpi is-warning">
                <div class="it-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
                <div><strong>{{ number_format($pendientes,0,',','.') }}</strong><span>OT pendientes</span><small>Aún sin ingreso a Terminación</small></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="it-kpi is-volume">
                <div class="it-kpi-icon"><i class="fas fa-boxes"></i></div>
                <div><strong>{{ number_format($resumen->prendas,0,',','.') }}</strong><span>Prendas del seguimiento</span><small>{{ number_format($resumen->prendas_ingresadas,0,',','.') }} ya ingresadas</small></div>
            </div>
        </div>
    </div>

    <div class="card it-table-card">
        <div class="card-header bg-white border-0">
            <form class="form-inline float-right">
                <input class="form-control form-control-sm mr-2" name="buscar" value="{{ $buscar }}" placeholder="Buscar IT1, IT2...">
                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
            </form>
            <h3 class="card-title font-weight-bold"><i class="fas fa-clipboard-list mr-2 text-primary"></i>Pedidos a Ingreso Terminación</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 text-center it-table">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>OT</th>
                        <th>Prendas</th>
                        <th>Ingreso Terminación</th>
                        <th>Avance</th>
                        <th>Situación</th>
                        <th>Fecha pedido</th>
                        <th>Último ingreso</th>
                        <th>Tiempo</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($pedidos as $pedido)
                    <tr>
                        <td><span class="it-pedido">{{ $pedido->nro_pedido }}</span></td>
                        <td><strong>{{ $pedido->ots_total }}</strong> OT</td>
                        <td><strong>{{ number_format($pedido->cantidad_total,0,',','.') }}</strong></td>
                        <td><strong>{{ number_format($pedido->cantidad_ingreso,0,',','.') }}</strong></td>
                        <td>
                            <strong>{{ $pedido->porcentaje }}%</strong>
                            <small class="d-block text-muted">{{ $pedido->ots_completas }}/{{ $pedido->ots_total }} OT</small>
                            <div class="progress progress-xs"><div class="progress-bar bg-success" style="width:{{ $pedido->porcentaje }}%"></div></div>
                        </td>
                        <td>
                            @if($pedido->completo)
                                <span class="it-state is-complete">COMPLETO</span>
                            @elseif($pedido->ots_completas > 0)
                                <span class="it-state is-progress">EN PROCESO</span>
                            @else
                                <span class="it-state is-pending">PENDIENTE</span>
                            @endif
                        </td>
                        <td>{{ $pedido->fecha_pedido ? $pedido->fecha_pedido->format('d/m/Y') : '-' }}</td>
                        <td>{{ $pedido->ultimo_ingreso ? date('d/m/Y',strtotime($pedido->ultimo_ingreso)) : '-' }}</td>
                        <td>
                            @if($pedido->dias !== null)
                                <strong class="text-success">{{ $pedido->dias }} {{ $pedido->dias == 1 ? 'día' : 'días' }}</strong>
                            @elseif($pedido->dias_en_curso !== null)
                                <strong class="text-warning">{{ $pedido->dias_en_curso }} {{ $pedido->dias_en_curso == 1 ? 'día' : 'días' }}</strong>
                                <small class="d-block text-muted">en curso</small>
                            @else
                                -
                            @endif
                        </td>
                        <td><a href="{{ route('seguimiento-ingreso-terminacion.show',$pedido->id) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye mr-1"></i>Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-5">Todavía no hay pedidos IT importados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $pedidos->links() }}</div>
    </div>
</div>
</section>
@endsection

@push('page_css')
<style>
.it-eyebrow{font-size:10px;font-weight:900;letter-spacing:.12em;color:#94a3b8}
.it-title{font-size:28px;font-weight:850;color:#0f172a}
.it-hero{background:#fff;border:1px solid #e6ebf1;border-radius:16px;padding:24px;box-shadow:0 8px 24px rgba(15,23,42,.05)}
.it-status{display:inline-block;background:#eef2ff;color:#4338ca;border-radius:999px;padding:6px 10px;font-size:10px;font-weight:900;letter-spacing:.05em}
.it-hero-value{font-size:42px;line-height:1;font-weight:900;color:#0f172a}
.it-hero-label{font-size:12px;color:#64748b;margin-top:5px}
.it-hero-detail{font-size:19px;color:#334155}
.it-progress{height:9px;background:#eef2f7;border-radius:999px;overflow:hidden}
.it-progress div{height:100%;background:#6366f1;border-radius:999px}
.it-side-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:20px;text-align:center}
.it-side-box span{display:block;font-size:10px;font-weight:900;text-transform:uppercase;color:#94a3b8;letter-spacing:.06em}
.it-side-box strong{display:block;font-size:33px;line-height:1;color:#0f172a;margin:9px 0 5px}
.it-side-box small{color:#64748b}
.it-kpi{min-height:118px;background:#fff;border:1px solid #e6ebf1;border-radius:14px;padding:17px;display:flex;align-items:center;gap:14px;box-shadow:0 5px 18px rgba(15,23,42,.04);position:relative;overflow:hidden}
.it-kpi:before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:#6366f1}
.it-kpi.is-success:before{background:#22c55e}.it-kpi.is-warning:before{background:#f59e0b}.it-kpi.is-volume:before{background:#8b5cf6}
.it-kpi-icon{width:43px;height:43px;border-radius:11px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#475569;font-size:17px;flex:0 0 43px}
.it-kpi strong{display:block;font-size:25px;line-height:1;color:#0f172a}.it-kpi span{display:block;font-size:12px;font-weight:800;color:#334155;margin-top:5px}.it-kpi small{display:block;font-size:10px;color:#94a3b8;margin-top:2px}
.it-table-card{border:1px solid #e6ebf1;border-radius:14px;overflow:hidden;box-shadow:0 5px 18px rgba(15,23,42,.04)}
.it-table th{background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;vertical-align:middle!important}
.it-table td{font-size:12px;vertical-align:middle!important}
.it-pedido{font-size:15px;font-weight:900;color:#3730a3}
.it-state{display:inline-block;border-radius:999px;padding:5px 8px;font-size:9px;font-weight:900}
.it-state.is-complete{background:#ecfdf5;color:#047857}.it-state.is-progress{background:#fff7ed;color:#c2410c}.it-state.is-pending{background:#f1f5f9;color:#64748b}
.progress-xs{height:4px;margin:4px auto 0;max-width:100px;background:#e9ecef}
@media(max-width:767.98px){.it-title{font-size:23px}.it-hero-value{font-size:34px}}
</style>
@endpush
