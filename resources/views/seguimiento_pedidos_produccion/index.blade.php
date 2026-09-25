@extends('layouts.app')

@section('content')
<section class="content-header">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1><i class="fas fa-industry mr-2"></i>Seguimiento de Pedido a Producción</h1>
            <p class="text-muted mb-0">Pedidos P1, P2... completos cuando todas sus OT alcanzan TERMINACION - INGRESO TERMINACION.</p>
        </div>
        <a href="{{ route('seguimiento-pedidos.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-route mr-1"></i> Seguimiento a locales
        </a>
    </div>
</div>
</section>

<section class="content">
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-white border-left border-primary shadow-sm">
                <div class="inner"><h3>{{ number_format($resumen->pedidos,0,',','.') }}</h3><p>Pedidos P visibles</p></div>
                <div class="icon"><i class="fas fa-clipboard-list text-primary"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-white border-left border-info shadow-sm">
                <div class="inner"><h3>{{ number_format($resumen->ots,0,',','.') }}</h3><p>OT vinculadas</p></div>
                <div class="icon"><i class="fas fa-list-ol text-info"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-white border-left border-success shadow-sm">
                <div class="inner"><h3>{{ number_format($resumen->ots_completas,0,',','.') }}</h3><p>OT ingresadas a Terminación</p></div>
                <div class="icon"><i class="fas fa-check-circle text-success"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-white border-left border-warning shadow-sm">
                <div class="inner"><h3>{{ number_format(max(0,$resumen->ots-$resumen->ots_completas),0,',','.') }}</h3><p>OT pendientes</p></div>
                <div class="icon"><i class="fas fa-hourglass-half text-warning"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary mb-4">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-excel mr-2 text-success"></i>Importar pedidos P</h3>
            <span class="float-right text-muted small">NRO OT + PEDIDO + FECHA PEDIDO</span>
        </div>
        <form method="POST" action="{{ route('seguimiento-produccion.importar') }}" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <div class="alert alert-info">
                    Usá el mismo formato del seguimiento T:
                    <strong>NRO OT</strong>, <strong>PEDIDO</strong> y <strong>FECHA PEDIDO</strong>.
                    Para producción el pedido debe ser <strong>P1, P2, P3...</strong>.
                </div>

                <div class="form-group mb-0">
                    <label for="archivo-produccion"><i class="fas fa-file-upload mr-1"></i>Archivo Excel</label>
                    <div class="custom-file">
                        <input type="file" name="archivo" class="custom-file-input" id="archivo-produccion" accept=".xlsx,.xls,.csv" required>
                        <label class="custom-file-label" for="archivo-produccion">Seleccionar archivo .xlsx, .xls o .csv</label>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-muted">El sistema vincula cada NRO OT existente al pedido P indicado.</small>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload mr-1"></i> Importar pedido a producción
                </button>
            </div>
        </form>
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

@push('page_scripts')
<script>
document.getElementById('archivo-produccion').addEventListener('change', function () {
    this.nextElementSibling.textContent = this.files.length ? this.files[0].name : 'Seleccionar archivo...';
});
</script>
@endpush

@push('page_css')
<style>
.produccion-resumen th{font-size:.75rem;text-transform:uppercase;letter-spacing:.02em;color:#6c757d;background:#f8fafc;vertical-align:middle!important;white-space:nowrap}
.produccion-resumen td{vertical-align:middle!important}
.pedido-produccion{font-size:1.08rem;font-weight:800;color:#343a40}
.progreso-celda{min-width:120px}
.progress-xs{height:4px;margin:4px auto 0;max-width:110px;background:#e9ecef}
.small-box.bg-white .icon{top:8px;font-size:48px;opacity:.16}
.border-left{border-left-width:4px!important}
</style>
@endpush
