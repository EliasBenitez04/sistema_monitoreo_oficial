@extends('layouts.app')

@section('content')
<section class="content-header">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-route mr-2"></i>Pedido {{ $pedido->nro_pedido }}</h1>
            <p class="text-muted mb-0">Trazabilidad desde TERMINACIÓN - TERMINACIÓN hasta la recepción del local.</p>
        </div>
        <a href="{{ route('seguimiento-pedidos.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i> Volver</a>
    </div>
</div>
</section>

<section class="content"><div class="container-fluid">
<div class="row">
@foreach([
    ['OT', $resumen->ots, 'clipboard-list'],
    ['Cantidad orden', number_format($resumen->cantidad,0,',','.'), 'boxes'],
    ['Producto terminado', number_format($resumen->terminado,0,',','.'), 'check-circle'],
    ['Distribución efectiva', number_format($resumen->enviado,0,',','.'), 'truck'],
    ['Confirmado efectivo', number_format($resumen->recibido,0,',','.'), 'store'],
    ['OT completas', $resumen->completas . '/' . $resumen->ots, 'check-double']
] as $kpi)
<div class="col-lg-2 col-md-4 col-6">
    <div class="small-box bg-light border"><div class="inner"><h4>{{ $kpi[1] }}</h4><p>{{ $kpi[0] }}</p></div><div class="icon"><i class="fas fa-{{ $kpi[2] }}"></i></div></div>
</div>
@endforeach
</div>

@foreach($ots as $ot)
<div class="card card-outline {{ $ot->estado_seguimiento === 'COMPLETO' ? 'card-success' : 'card-primary' }}">
    <div class="card-header">
        <h3 class="card-title"><strong>OT {{ $ot->nro_ot }}</strong> · {{ $ot->codigo }} · {{ $ot->descripcion }}</h3>
        <div class="card-tools"><span class="badge {{ $ot->estado_seguimiento === 'COMPLETO' ? 'badge-success' : 'badge-warning' }}">{{ $ot->estado_seguimiento }}</span></div>
    </div>
    <div class="card-body">
        <div class="row text-center mb-4">
            <div class="col-md"><small class="text-muted d-block">Orden</small><strong>{{ number_format($ot->cantidad_orden,0,',','.') }}</strong></div>
            <div class="col-md"><small class="text-muted d-block">Ingreso Terminación</small><strong>{{ number_format($ot->ingreso_terminacion,0,',','.') }}</strong><div class="small text-muted">{{ $ot->fecha_ingreso ? date('d/m/Y', strtotime($ot->fecha_ingreso)) : '-' }}</div></div>
            <div class="col-md"><small class="text-muted d-block">Producto Terminado</small><strong>{{ number_format($ot->producto_terminado,0,',','.') }}</strong><div class="small text-muted">{{ $ot->fecha_pt ? date('d/m/Y', strtotime($ot->fecha_pt)) : '-' }}</div></div>
            <div class="col-md"><small class="text-muted d-block">Logística</small><strong>{{ number_format($ot->distribuido,0,',','.') }}</strong><div class="small text-muted">{{ $ot->fecha_logistica ? date('d/m/Y', strtotime($ot->fecha_logistica)) : '-' }}</div></div>
            <div class="col-md"><small class="text-muted d-block">Distribución efectiva</small><strong>{{ number_format($ot->enviado,0,',','.') }} / {{ number_format($ot->cantidad_orden,0,',','.') }}</strong></div>
            <div class="col-md"><small class="text-muted d-block">Confirmado efectivo</small><strong>{{ number_format($ot->recibido,0,',','.') }} / {{ number_format($ot->cantidad_orden,0,',','.') }}</strong></div>
        </div>

        <div class="d-flex align-items-center mb-3">
            <span class="badge badge-secondary mr-2">TERMINACIÓN</span><i class="fas fa-arrow-right text-muted mr-2"></i>
            <span class="badge badge-info mr-2">PRODUCTO TERMINADO</span><i class="fas fa-arrow-right text-muted mr-2"></i>
            <span class="badge badge-primary mr-2">LOGÍSTICA</span><i class="fas fa-arrow-right text-muted mr-2"></i>
            <span class="badge badge-warning mr-2">REMISIÓN</span><i class="fas fa-arrow-right text-muted mr-2"></i>
            <span class="badge badge-success">RECEPCIÓN LOCAL</span>
        </div>

        <div class="row mb-3">
            <div class="col-md-4"><div class="border rounded p-2"><small class="text-muted d-block">Movimientos físicos</small><strong>{{ number_format($ot->movimientos_fisicos,0,',','.') }}</strong></div></div>
            <div class="col-md-4"><div class="border rounded p-2"><small class="text-muted d-block">Movimientos adicionales</small><strong>{{ number_format($ot->movimientos_adicionales,0,',','.') }}</strong></div></div>
            <div class="col-md-4"><div class="border rounded p-2"><small class="text-muted d-block">Locales comerciales confirmados</small><strong>{{ $ot->locales_confirmados }}/{{ $ot->locales_enviados }}</strong> <span class="text-muted">(máximo operativo: 12)</span></div></div>
        </div>

        <h6 class="font-weight-bold mb-2"><i class="fas fa-store mr-1"></i> Locales comerciales</h6>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead><tr><th>Local</th><th class="text-right">Enviado</th><th class="text-right">Recibido</th><th class="text-right">Pendiente</th><th>Últ. remisión</th><th>Recepción</th><th>Estado</th></tr></thead>
                <tbody>
                @forelse($ot->locales_comerciales as $local)
                    <tr>
                        <td>{{ $local->local }}</td>
                        <td class="text-right">{{ number_format($local->enviado,0,',','.') }}</td>
                        <td class="text-right">{{ number_format($local->recibido,0,',','.') }}</td>
                        <td class="text-right">{{ number_format($local->pendiente,0,',','.') }}</td>
                        <td>{{ $local->ultima_remision ? date('d/m/Y', strtotime($local->ultima_remision)) : '-' }}</td>
                        <td>{{ $local->ultima_recepcion ? date('d/m/Y', strtotime($local->ultima_recepcion)) : '-' }}</td>
                        <td><span class="badge {{ $local->estado_local === 'RECIBIDO' ? 'badge-success' : ($local->estado_local === 'PARCIAL' ? 'badge-warning' : 'badge-secondary') }}">{{ $local->estado_local }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">Esta OT todavía no tiene remisiones vinculadas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <h6 class="font-weight-bold mt-4 mb-2"><i class="fas fa-warehouse mr-1"></i> Mayorista / Depósito</h6>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-2">
                <thead><tr><th>Nodo</th><th class="text-right">Movimiento</th><th class="text-right">Confirmado</th><th>Últ. remisión</th><th>Recepción</th><th>Estado</th></tr></thead>
                <tbody>
                @forelse($ot->canal_mayorista as $local)
                    <tr>
                        <td>{{ $local->local }}</td>
                        <td class="text-right">{{ number_format($local->enviado,0,',','.') }}</td>
                        <td class="text-right">{{ number_format($local->recibido,0,',','.') }}</td>
                        <td>{{ $local->ultima_remision ? date('d/m/Y', strtotime($local->ultima_remision)) : '-' }}</td>
                        <td>{{ $local->ultima_recepcion ? date('d/m/Y', strtotime($local->ultima_recepcion)) : '-' }}</td>
                        <td><span class="badge {{ $local->estado_local === 'RECIBIDO' ? 'badge-success' : ($local->estado_local === 'PARCIAL' ? 'badge-warning' : 'badge-secondary') }}">{{ $local->estado_local }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Sin movimientos de Mayorista / Depósito.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="small text-muted">Locales comerciales confirmados: <strong>{{ $ot->locales_confirmados }}/{{ $ot->locales_enviados }}</strong> · Pendiente efectivo de recepción: <strong>{{ number_format($ot->pendiente_recepcion,0,',','.') }}</strong> · Los movimientos adicionales se conservan para auditoría y no aumentan el avance por encima de la cantidad de la OT.</div>
    </div>
</div>
@endforeach
</div></section>
@endsection
