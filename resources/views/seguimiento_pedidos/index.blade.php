@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1><i class="fas fa-route mr-2"></i>Seguimiento de Pedidos</h1>
                <p class="text-muted mb-0">Seguimiento desde Terminación hasta la recepción confirmada por los locales.</p>
            </div>
            <a href="{{ route('seguimiento-pedidos.informe-gerencial') }}" class="btn btn-danger shadow-sm">
                <i class="fas fa-briefcase mr-1"></i> Informe gerencial
            </a>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-white border-left border-primary shadow-sm">
                <div class="inner"><h3>{{ number_format($resumenGerencial->pedidos,0,',','.') }}</h3><p>Pedidos monitoreados</p></div>
                <div class="icon"><i class="fas fa-clipboard-list text-primary"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-white border-left border-info shadow-sm">
                <div class="inner"><h3>{{ number_format($resumenGerencial->prendas,0,',','.') }}</h3><p>Prendas solicitadas · {{ $resumenGerencial->ots }} OT</p></div>
                <div class="icon"><i class="fas fa-tshirt text-info"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-white border-left border-success shadow-sm">
                <div class="inner"><h3>{{ $resumenGerencial->cobertura_confirmada }}%</h3><p>Confirmado en locales</p></div>
                <div class="icon"><i class="fas fa-check-circle text-success"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-white border-left border-warning shadow-sm">
                <div class="inner"><h3>{{ number_format($resumenGerencial->pendiente_confirmar,0,',','.') }}</h3><p>Prendas pendientes de confirmar</p></div>
                <div class="icon"><i class="fas fa-exclamation-triangle text-warning"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-dark mb-4">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Resumen ejecutivo</h3>
            <span class="float-right text-muted small">Situación general para toma de decisiones</span>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3 border-right mb-3 mb-md-0">
                    <div class="text-muted text-uppercase small">Producto terminado</div>
                    <div class="h4 mb-1">{{ number_format($resumenGerencial->pt,0,',','.') }} / {{ number_format($resumenGerencial->prendas,0,',','.') }}</div>
                    <div class="progress progress-sm"><div class="progress-bar bg-info" style="width:{{ min(100,$resumenGerencial->cobertura_pt) }}%"></div></div>
                    <small>{{ $resumenGerencial->cobertura_pt }}% · faltan {{ number_format($resumenGerencial->pendiente_pt,0,',','.') }}</small>
                </div>
                <div class="col-md-3 border-right mb-3 mb-md-0">
                    <div class="text-muted text-uppercase small">Pedidos completos</div>
                    <div class="h4 mb-1 text-success">{{ $resumenGerencial->completos }}</div>
                    <small>{{ $resumenGerencial->en_curso }} todavía en curso</small>
                </div>
                <div class="col-md-3 border-right mb-3 mb-md-0">
                    <div class="text-muted text-uppercase small">Tiempo de atención</div>
                    <div class="h4 mb-1">{{ $resumenGerencial->promedio_dias !== null ? $resumenGerencial->promedio_dias.' días' : '-' }}</div>
                    <small>Pedido → primera confirmación local</small>
                </div>
                <div class="col-md-3">
                    <div class="text-muted text-uppercase small">Requieren atención</div>
                    <div class="h4 mb-1 text-warning">{{ $resumenGerencial->en_curso }}</div>
                    <small>pedidos sin cierre completo</small>
                </div>
            </div>
        </div>
    </div>

    @if($resumenGerencial->en_curso > 0)
    <div class="card card-outline card-warning mb-4">
        <div class="card-header py-2"><h3 class="card-title"><i class="fas fa-bullseye mr-2"></i>Dónde mirar primero</h3></div>
        <div class="card-body py-3">
            <div class="row text-center">
                <div class="col-md-4">
                    <strong class="d-block h5 mb-0">{{ $resumenGerencial->sin_movimiento }}</strong>
                    <span class="text-muted">Sin movimiento/remisión</span>
                </div>
                <div class="col-md-4">
                    <strong class="d-block h5 mb-0">{{ $resumenGerencial->sin_confirmar }}</strong>
                    <span class="text-muted">Remitidos sin confirmación</span>
                </div>
                <div class="col-md-4">
                    <strong class="d-block h5 mb-0">{{ $resumenGerencial->recepcion_parcial }}</strong>
                    <span class="text-muted">Con recepción parcial</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="card card-outline card-primary collapsed-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-excel mr-2 text-success"></i>Importar OT por pedido</h3>
            <div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button></div>
        </div>
        <form method="POST" action="{{ route('seguimiento-pedidos.importar') }}" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <div class="alert alert-info mb-3">
                    El Excel debe contener <strong>NRO OT</strong>, <strong>NRO PEDIDO</strong> y <strong>FECHA PEDIDO</strong> (también se aceptan OT, PEDIDO y FECHA).
                    Ejemplo: <strong>30703 / T1 / 01/09/2026</strong>. La fecha se guarda una sola vez por pedido y la confirmación se toma de la primera recepción real del local; remisiones posteriores no aumentan el tiempo del pedido original.
                </div>
                <div class="custom-file">
                    <input type="file" name="archivo" class="custom-file-input" id="archivo-pedidos" accept=".xlsx,.xls,.csv" required>
                    <label class="custom-file-label" for="archivo-pedidos">Seleccionar archivo...</label>
                </div>
            </div>
            <div class="card-footer text-right">
                <button class="btn btn-primary"><i class="fas fa-upload mr-1"></i> Importar pedidos</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <form class="form-inline float-right">
                <input class="form-control form-control-sm mr-2" name="buscar" value="{{ $buscar }}" placeholder="Buscar T1, T2...">
                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
            </form>
            <h3 class="card-title"><i class="fas fa-list-alt mr-2"></i>Pedidos registrados</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 text-center seguimiento-resumen">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>OT</th>
                        <th>Prendas</th>
                        <th>Prod. terminado</th>
                        <th>Movimientos</th>
                        <th>Confirmado</th>
                        <th>Situación</th>
                        <th>Fecha pedido</th>
                        <th>1ª confirmación local</th>
                        <th>Tiempo</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($pedidos as $pedido)
                    @php
                        $cantidad = (int) $pedido->cantidad_total;
                        $pt = min($cantidad, (int) $pedido->producto_terminado);
                        $mov = (int) $pedido->movimientos;
                        $conf = min($cantidad, (int) $pedido->confirmado);
                        $pctPt = $cantidad > 0 ? min(100, round(($pt / $cantidad) * 100)) : 0;
                        $pctConf = $cantidad > 0 ? min(100, round(($conf / $cantidad) * 100)) : 0;
                        if ($cantidad > 0 && $conf >= $cantidad) {
                            $situacion = 'COMPLETO'; $clase = 'success'; $icono = 'check-circle';
                        } elseif ($mov > 0) {
                            $situacion = $conf > 0 ? 'RECEPCIÓN PARCIAL' : 'EN REMISIÓN'; $clase = 'warning'; $icono = 'truck';
                        } elseif ($pt > 0) {
                            $situacion = $pt >= $cantidad ? 'PRODUCTO TERMINADO' : 'EN PRODUCCIÓN'; $clase = 'info'; $icono = 'box';
                        } else {
                            $situacion = 'EN TERMINACIÓN'; $clase = 'secondary'; $icono = 'industry';
                        }
                    @endphp
                    <tr>
                        <td class="align-middle"><div class="pedido-numero">{{ $pedido->nro_pedido }}</div></td>
                        <td class="align-middle"><span class="badge badge-primary px-2 py-2">{{ $pedido->detalles_count }} OT</span></td>
                        <td class="align-middle"><strong>{{ number_format($cantidad,0,',','.') }}</strong></td>
                        <td class="align-middle progreso-celda">
                            <strong>{{ number_format($pt,0,',','.') }}</strong>
                            <small class="d-block text-muted">{{ $pctPt }}%</small>
                            <div class="progress progress-xs"><div class="progress-bar bg-info" style="width:{{ $pctPt }}%"></div></div>
                        </td>
                        <td class="align-middle">
                            <strong>{{ number_format($mov,0,',','.') }}</strong>
                            @if($mov > $cantidad)<small class="d-block text-warning">+{{ number_format($mov-$cantidad,0,',','.') }} mov. extra</small>@endif
                        </td>
                        <td class="align-middle progreso-celda">
                            <strong>{{ number_format($conf,0,',','.') }} / {{ number_format($cantidad,0,',','.') }}</strong>
                            <small class="d-block text-muted">{{ $pctConf }}%</small>
                            <div class="progress progress-xs"><div class="progress-bar bg-success" style="width:{{ $pctConf }}%"></div></div>
                        </td>
                        <td class="align-middle"><span class="badge badge-{{ $clase }} px-2 py-2"><i class="fas fa-{{ $icono }} mr-1"></i>{{ $situacion }}</span></td>
                        <td class="align-middle">
                            <strong>{{ $pedido->fecha_pedido ? $pedido->fecha_pedido->format('d/m/Y') : '-' }}</strong>
                            @if(!$pedido->fecha_pedido)<small class="d-block text-danger">Reimportar con fecha</small>@endif
                        </td>
                        <td class="align-middle">
                            {{ $pedido->ultima_confirmacion ? date('d/m/Y', strtotime($pedido->ultima_confirmacion)) : '-' }}
                        </td>
                        <td class="align-middle">
                            @if($pedido->dias_confirmacion !== null)
                                <span class="badge badge-success px-2 py-2">{{ $pedido->dias_confirmacion }} días</span>
                            @elseif($pedido->dias_transcurridos !== null)
                                <span class="badge badge-warning px-2 py-2">{{ $pedido->dias_transcurridos }} días</span>
                                <small class="d-block text-muted">en curso</small>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="align-middle"><a href="{{ route('seguimiento-pedidos.show', $pedido->id) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye mr-1"></i> Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center text-muted py-4">Todavía no hay pedidos importados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $pedidos->links() }}</div>
    </div>
</div>
</section>
@endsection

@push('page_scripts')
<script>
document.getElementById('archivo-pedidos').addEventListener('change', function () {
    this.nextElementSibling.textContent = this.files.length ? this.files[0].name : 'Seleccionar archivo...';
});
</script>
@endpush

@push('page_css')
<style>
.seguimiento-resumen th{font-size:.75rem;text-transform:uppercase;letter-spacing:.02em;color:#6c757d;background:#f8fafc;vertical-align:middle!important;white-space:nowrap}
.seguimiento-resumen td{vertical-align:middle!important}
.pedido-numero{font-size:1.05rem;font-weight:700;color:#343a40}
.progreso-celda{min-width:125px}
.progress-xs{height:4px;margin:4px auto 0;max-width:110px;background:#e9ecef}
.small-box.bg-white .icon{top:8px;font-size:48px;opacity:.16}
.small-box.bg-white .inner h3{font-size:1.8rem}
.border-left{border-left-width:4px!important}
.progress-sm{height:7px;margin:8px 0 4px}
</style>
@endpush
