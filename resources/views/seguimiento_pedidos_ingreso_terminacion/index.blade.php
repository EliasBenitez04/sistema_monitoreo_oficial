@extends('layouts.app')

@section('content')
<section class="content-header pb-2">
<div class="container-fluid trk-shell theme-it">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <div class="trk-eyebrow">CONTROL DE PEDIDOS</div>
            <h1 class="trk-title mb-1"><i class="fas fa-sign-in-alt mr-2"></i>Seguimiento Pedido a Ingreso Terminación</h1>
            <p class="trk-subtitle mb-0">Pedidos <strong>IT1, IT2...</strong> completos cuando todas sus OT alcanzan <strong>TERMINACION - TERMINACION</strong>.</p>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="{{ route('pedidos.index') }}" class="btn btn-dark shadow-sm mr-1">
                <i class="fas fa-th-large mr-1"></i> PEDIDOS
            </a>
            <a href="{{ route('seguimiento-ingreso-terminacion.informe-gerencial') }}" class="btn btn-danger shadow-sm mr-1">
                <i class="fas fa-chart-line mr-1"></i> Informe gerencial
            </a>
            <a href="{{ route('seguimiento-ingreso-terminacion.seguimiento-diario') }}" class="btn btn-info shadow-sm mr-1">
                <i class="fas fa-clipboard-check mr-1"></i> Seguimiento diario
            </a>
            <a href="{{ route('pedidos.importar') }}" class="btn btn-primary shadow-sm mr-1">
                <i class="fas fa-file-import mr-1"></i> Importar datos
            </a>
            <a href="{{ route('seguimiento-produccion.index') }}" class="btn btn-outline-primary mr-1">
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
<div class="container-fluid trk-shell theme-it">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    @php
        $pendientes = max(0, $resumen->ots - $resumen->ots_completas);
        $avance = $resumen->ots > 0 ? min(100, round(($resumen->ots_completas / $resumen->ots) * 100)) : 0;
    @endphp

    <div class="trk-hero mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="trk-pill"><i class="fas fa-stream mr-1"></i>AVANCE GENERAL</div>
                <div class="d-flex justify-content-between align-items-end mt-3">
                    <div>
                        <div class="trk-hero-value">{{ $avance }}%</div>
                        <div class="trk-hero-label">OT que ya ingresaron a Terminación</div>
                    </div>
                    <div class="text-right">
                        <div class="trk-hero-detail">{{ number_format($resumen->ots_completas,0,',','.') }} / {{ number_format($resumen->ots,0,',','.') }} OT</div>
                        <small class="text-muted">controladas en pedidos IT</small>
                    </div>
                </div>
                <div class="trk-progress mt-3"><div style="width:{{ $avance }}%"></div></div>
            </div>
            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="trk-side">
                    <span>Pedidos IT</span>
                    <strong>{{ number_format($resumen->pedidos,0,',','.') }}</strong>
                    <small>{{ number_format($resumen->completos,0,',','.') }} completos · {{ number_format(max(0,$resumen->pedidos-$resumen->completos),0,',','.') }} en curso</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="trk-kpi">
                <div class="trk-kpi-icon"><i class="fas fa-list-ol"></i></div>
                <div><strong>{{ number_format($resumen->ots,0,',','.') }}</strong><span>OT vinculadas</span><small>Total de OT en pedidos IT</small></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="trk-kpi is-success">
                <div class="trk-kpi-icon"><i class="fas fa-check"></i></div>
                <div><strong>{{ number_format($resumen->ots_completas,0,',','.') }}</strong><span>OT ingresadas</span><small>Ya tienen TERMINACION - TERMINACION</small></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="trk-kpi is-warning">
                <div class="trk-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
                <div><strong>{{ number_format($pendientes,0,',','.') }}</strong><span>OT pendientes</span><small>Aún sin ingreso a Terminación</small></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="trk-kpi is-volume">
                <div class="trk-kpi-icon"><i class="fas fa-boxes"></i></div>
                <div><strong>{{ number_format($resumen->prendas,0,',','.') }}</strong><span>Prendas del seguimiento</span><small>{{ number_format($resumen->prendas_ingresadas,0,',','.') }} ya ingresadas</small></div>
            </div>
        </div>
    </div>

    <div class="card trk-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-clipboard-list mr-2 text-primary"></i>Pedidos a Ingreso Terminación</h3>
                <small class="text-muted">Seguimiento consolidado por pedido IT.</small>
            </div>
            <form class="form-inline mt-2 mt-md-0">
                <input class="form-control form-control-sm mr-2" name="buscar" value="{{ $buscar }}" placeholder="Buscar IT1, IT2...">
                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 text-center trk-table">
                <thead><tr><th>Pedido</th><th>OT</th><th>Prendas</th><th>Ingreso Terminación</th><th>Avance</th><th>Situación</th><th>Fecha pedido</th><th>Último ingreso</th><th>Tiempo</th><th>Detalle</th></tr></thead>
                <tbody>
                @forelse($pedidos as $pedido)
                    <tr>
                        <td><span class="trk-order">{{ $pedido->nro_pedido }}</span></td>
                        <td><strong>{{ $pedido->ots_total }}</strong> OT</td>
                        <td><strong>{{ number_format($pedido->cantidad_total,0,',','.') }}</strong></td>
                        <td><strong>{{ number_format($pedido->cantidad_ingreso,0,',','.') }}</strong></td>
                        <td>
                            <strong>{{ $pedido->porcentaje }}%</strong>
                            <small class="d-block text-muted">{{ $pedido->ots_completas }}/{{ $pedido->ots_total }} OT</small>
                            <div class="trk-progress-xs"><div style="width:{{ $pedido->porcentaje }}%"></div></div>
                        </td>
                        <td>
                            @if($pedido->completo)
                                <span class="trk-state is-complete"><i class="fas fa-check mr-1"></i>COMPLETO</span>
                            @elseif($pedido->ots_completas > 0)
                                <span class="trk-state is-progress"><i class="fas fa-cogs mr-1"></i>EN PROCESO</span>
                            @else
                                <span class="trk-state is-pending">PENDIENTE</span>
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
                            @else -
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

@include('seguimiento_shared.tracking_styles')
