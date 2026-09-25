@extends('layouts.app')

@section('content')
<section class="content-header">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1><i class="fas fa-industry mr-2"></i>Seguimiento de Pedido a Producción</h1>
            <p class="text-muted mb-0">Pedidos P1, P2... completos cuando todas sus OT alcanzan TERMINACION - INGRESO TERMINACION.</p>
        </div>
        <div>
            <a href="{{ route('seguimiento-pedidos.index', ['abrir_import' => 1]) }}#importar-pedidos" class="btn btn-primary mr-1">
                <i class="fas fa-file-import mr-1"></i> Importar pedidos T / P
            </a>
            <a href="{{ route('seguimiento-pedidos.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-route mr-1"></i> Seguimiento a locales
            </a>
        </div>
    </div>
</div>
</section>

<section class="content">
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <strong>No se pudo importar:</strong>
            <ul class="mb-0 pl-3">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    @php
        $pendientesProduccion = max(0, $resumen->ots - $resumen->ots_completas);
        $avanceProduccion = $resumen->ots > 0
            ? min(100, round(($resumen->ots_completas / $resumen->ots) * 100))
            : 0;
    @endphp

    <div class="row produccion-kpis mb-3">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="prod-kpi-card">
                <div class="prod-kpi-top">
                    <div class="prod-kpi-icon"><i class="fas fa-clipboard-list"></i></div>
                    <span class="prod-kpi-tag">Pedidos</span>
                </div>
                <div class="prod-kpi-value">{{ number_format($resumen->pedidos,0,',','.') }}</div>
                <div class="prod-kpi-label">Pedidos a Producción</div>
                <div class="prod-kpi-meta">P1, P2, P3...</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="prod-kpi-card">
                <div class="prod-kpi-top">
                    <div class="prod-kpi-icon"><i class="fas fa-layer-group"></i></div>
                    <span class="prod-kpi-tag">Carga</span>
                </div>
                <div class="prod-kpi-value">{{ number_format($resumen->ots,0,',','.') }}</div>
                <div class="prod-kpi-label">OT vinculadas</div>
                <div class="prod-kpi-meta">Total incluido en los pedidos P</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="prod-kpi-card prod-kpi-success">
                <div class="prod-kpi-top">
                    <div class="prod-kpi-icon"><i class="fas fa-check"></i></div>
                    <span class="prod-kpi-tag">Completado</span>
                </div>
                <div class="prod-kpi-value">{{ number_format($resumen->ots_completas,0,',','.') }}</div>
                <div class="prod-kpi-label">OT ingresadas a Terminación</div>
                <div class="prod-kpi-meta">{{ $avanceProduccion }}% del total de OT</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="prod-kpi-card {{ $pendientesProduccion > 0 ? 'prod-kpi-warning' : 'prod-kpi-success' }}">
                <div class="prod-kpi-top">
                    <div class="prod-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
                    <span class="prod-kpi-tag">Pendiente</span>
                </div>
                <div class="prod-kpi-value">{{ number_format($pendientesProduccion,0,',','.') }}</div>
                <div class="prod-kpi-label">OT por ingresar a Terminación</div>
                <div class="prod-kpi-meta">{{ 100 - $avanceProduccion }}% pendiente</div>
            </div>
        </div>
    </div>

    <div class="produccion-avance-card mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
            <div>
                <strong>Avance general a Terminación</strong>
                <div class="text-muted small">El pedido se completa cuando todas sus OT alcanzan INGRESO TERMINACIÓN.</div>
            </div>
            <div class="produccion-avance-numero">{{ $avanceProduccion }}%</div>
        </div>
        <div class="produccion-progress">
            <div class="produccion-progress-bar" style="width:{{ $avanceProduccion }}%"></div>
        </div>
        <div class="d-flex justify-content-between mt-2 small text-muted">
            <span>{{ number_format($resumen->ots_completas,0,',','.') }} OT completas</span>
            <span>{{ number_format($pendientesProduccion,0,',','.') }} OT pendientes</span>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <form class="form-inline float-right">
                <input class="form-control form-control-sm mr-2" name="buscar" value="{{ $buscar }}" placeholder="Buscar P1, P2...">
                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
            </form>
            <h3 class="card-title"><i class="fas fa-list-alt mr-2"></i>Pedidos a producción</h3>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 text-center produccion-resumen">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>OT</th>
                        <th>Prendas</th>
                        <th>Ingreso Terminación</th>
                        <th>Avance OT</th>
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
                        <td><div class="pedido-produccion">{{ $pedido->nro_pedido }}</div></td>
                        <td><span class="badge badge-primary px-2 py-2">{{ $pedido->ots_total }} OT</span></td>
                        <td><strong>{{ number_format($pedido->cantidad_total,0,',','.') }}</strong></td>
                        <td><strong>{{ number_format($pedido->cantidad_ingreso,0,',','.') }}</strong></td>
                        <td class="progreso-celda">
                            <strong>{{ $pedido->porcentaje }}%</strong>
                            <small class="d-block text-muted">{{ $pedido->ots_completas }} de {{ $pedido->ots_total }} OT</small>
                            <div class="progress progress-xs"><div class="progress-bar bg-success" style="width:{{ $pedido->porcentaje }}%"></div></div>
                        </td>
                        <td>
                            @if($pedido->completo)
                                <span class="badge badge-success px-2 py-2"><i class="fas fa-check-circle mr-1"></i>COMPLETO</span>
                            @elseif($pedido->ots_completas > 0)
                                <span class="badge badge-warning px-2 py-2"><i class="fas fa-industry mr-1"></i>EN PROCESO</span>
                            @else
                                <span class="badge badge-secondary px-2 py-2">PENDIENTE</span>
                            @endif
                        </td>
                        <td><strong>{{ $pedido->fecha_pedido ? $pedido->fecha_pedido->format('d/m/Y') : '-' }}</strong></td>
                        <td>{{ $pedido->ultimo_ingreso ? date('d/m/Y', strtotime($pedido->ultimo_ingreso)) : '-' }}</td>
                        <td>
                            @if($pedido->dias !== null)
                                <span class="badge badge-success px-2 py-2">{{ $pedido->dias }} días</span>
                            @elseif($pedido->dias_en_curso !== null)
                                <span class="badge badge-warning px-2 py-2">{{ $pedido->dias_en_curso }} días</span>
                                <small class="d-block text-muted">en curso</small>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td><a href="{{ route('seguimiento-produccion.show',$pedido->id) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye mr-1"></i>Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">Todavía no hay pedidos P importados.</td></tr>
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
.produccion-resumen th{font-size:.75rem;text-transform:uppercase;letter-spacing:.02em;color:#6c757d;background:#f8fafc;vertical-align:middle!important;white-space:nowrap}
.produccion-resumen td{vertical-align:middle!important}
.pedido-produccion{font-size:1.08rem;font-weight:800;color:#26364a}
.progreso-celda{min-width:120px}
.progress-xs{height:4px;margin:4px auto 0;max-width:110px;background:#e9ecef}

.prod-kpi-card{
    position:relative;
    background:#fff;
    border:1px solid #e6ebf1;
    border-radius:14px;
    padding:18px;
    min-height:150px;
    box-shadow:0 6px 18px rgba(15,23,42,.05);
    overflow:hidden;
}
.prod-kpi-card:before{
    content:'';
    position:absolute;
    left:0;top:0;bottom:0;
    width:4px;
    background:#3b82f6;
}
.prod-kpi-success:before{background:#22c55e}
.prod-kpi-warning:before{background:#f59e0b}
.prod-kpi-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.prod-kpi-icon{
    width:38px;height:38px;border-radius:10px;
    display:flex;align-items:center;justify-content:center;
    background:#f1f5f9;color:#334155;font-size:16px;
}
.prod-kpi-tag{
    font-size:10px;text-transform:uppercase;letter-spacing:.08em;
    font-weight:800;color:#94a3b8;
}
.prod-kpi-value{font-size:30px;line-height:1;font-weight:800;color:#0f172a;margin-bottom:6px}
.prod-kpi-label{font-size:13px;font-weight:700;color:#334155}
.prod-kpi-meta{font-size:11px;color:#94a3b8;margin-top:3px}

.produccion-avance-card{
    background:#fff;
    border:1px solid #e6ebf1;
    border-radius:14px;
    padding:18px 20px;
    box-shadow:0 6px 18px rgba(15,23,42,.04);
}
.produccion-avance-numero{font-size:24px;font-weight:800;color:#0f172a}
.produccion-progress{height:8px;background:#edf2f7;border-radius:999px;overflow:hidden}
.produccion-progress-bar{height:100%;background:#22c55e;border-radius:999px;transition:width .25s ease}
</style>
@endpush
