@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1><i class="fas fa-route mr-2"></i>Seguimiento de Pedidos</h1>
                <p class="text-muted mb-0">Seguimiento desde Terminación hasta la recepción confirmada por los locales.</p>
            </div>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-file-excel mr-2 text-success"></i>Importar OT por pedido</h3></div>
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
</style>
@endpush
