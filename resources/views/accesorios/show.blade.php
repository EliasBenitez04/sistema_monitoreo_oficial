@extends('layouts.app')

@section('content')
<section class="content-header"><div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
    <div><h1>{{ $articulo->codigo }} <small class="text-muted">{{ $articulo->descripcion }}</small></h1><p class="text-muted">Línea: {{ $articulo->linea ?: 'Sin línea' }}</p></div>
    <a class="btn btn-outline-secondary" href="{{ route('accesorios.index') }}"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
</div></section>
<section class="content"><div class="container-fluid">
    <div class="row">
        @foreach([
            ['Compradas', $totalComprado, 'primary'],
            ['Remitidas (movimientos)', $resumenRemisiones->remitido, 'info'],
            ['Con recepción registrada', $resumenRemisiones->recibido, 'success'],
            ['Ventas netas registradas', $totalVendido, 'secondary']
        ] as $kpi)
            <div class="col-md-3 col-6"><div class="card border-{{ $kpi[2] }} shadow-sm"><div class="card-body">
                <div class="text-muted small">{{ $kpi[0] }}</div><h3 class="mb-0">{{ number_format($kpi[1], 0, ',', '.') }}</h3>
            </div></div></div>
        @endforeach
    </div>
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title">Historial de compras · FCO</h3></div>
        <div class="card-body table-responsive p-0"><table class="table table-striped table-sm mb-0">
            <thead><tr><th>FCO</th><th>Fecha envío</th><th class="text-right">Cantidad</th><th class="text-right">Costo unitario</th><th class="text-right">Precio venta</th><th class="text-right">Costo total</th></tr></thead><tbody>
            @forelse($compras as $compra)
                <tr><td>{{ $compra->fco_nro }}</td>
                    <td>{{ \Carbon\Carbon::parse($compra->fecha_envio)->format('d/m/Y') }}</td>
                    <td class="text-right">{{ number_format($compra->cantidad_compra, 0, ',', '.') }}</td>
                    <td class="text-right">Gs {{ number_format($compra->costo_unitario, 0, ',', '.') }}</td>
                    <td class="text-right">Gs {{ number_format($compra->precio_venta, 0, ',', '.') }}</td>
                    <td class="text-right">Gs {{ number_format($compra->cantidad_compra * $compra->costo_unitario, 0, ',', '.') }}</td></tr>
            @empty <tr><td colspan="6" class="text-muted text-center">Sin compras registradas.</td></tr> @endforelse
            </tbody></table></div>
    </div>
    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title">Recorrido de remisiones · orden de ingreso y distribución</h3>
        </div>
        <div class="px-3 py-2 bg-light border-bottom small">
            <i class="fas fa-warehouse text-primary mr-1"></i><strong>1. Depósito → Casa Central</strong>
            <span class="mx-2 text-muted">→</span>
            <i class="fas fa-truck text-info mr-1"></i><strong>2. Casa Central → Locales</strong>
            <span class="mx-2 text-muted">→</span>
            <span class="text-muted">3. Otros movimientos</span>
            <div class="text-muted mt-1">Dentro de cada etapa: fecha de remisión ascendente y número de remisión.</div>
        </div>
        <div class="card-body table-responsive p-0"><table class="table table-striped table-sm mb-0">
            <thead><tr><th>Remisión</th><th>Fecha</th><th>Origen</th><th>Destino</th><th class="text-right">Cantidad</th><th>Recepción</th><th>Estado</th></tr></thead><tbody>
            @forelse($remisiones as $remision)
                <tr><td>{{ $remision->serie }} - {{ $remision->numero_remision }}</td>
                    <td>{{ $remision->fecha_remision ? \Carbon\Carbon::parse($remision->fecha_remision)->format('d/m/Y') : '—' }}</td>
                    <td>{{ $remision->sucursal_salida ?: $remision->cod_sucursal_salida }}</td>
                    <td>{{ $remision->sucursal_destino ?: $remision->cod_sucursal_destino }}</td>
                    <td class="text-right">{{ number_format($remision->cantidad, 0, ',', '.') }}</td>
                    <td>{{ $remision->fecha_recepcion ? \Carbon\Carbon::parse($remision->fecha_recepcion)->format('d/m/Y') : 'Pendiente' }}</td>
                    <td><span class="badge badge-{{ $remision->fecha_recepcion ? 'success' : 'warning' }}">{{ $remision->fecha_recepcion ? 'RECIBIDO' : 'EN TRÁNSITO' }}</span></td></tr>
            @empty <tr><td colspan="7" class="text-center text-muted p-3">Sin remisiones vinculadas.</td></tr> @endforelse
            </tbody></table></div>
        <div class="card-footer">{{ $remisiones->links() }}</div>
    </div>
    <div class="card card-outline card-success">
        <div class="card-header"><h3 class="card-title">Ventas netas por local · código completo</h3></div>
        <div class="card-body table-responsive p-0"><table class="table table-sm table-striped mb-0">
            <thead><tr><th>Local</th><th class="text-right">Unidades netas</th></tr></thead><tbody>
            @forelse($ventasPorLocal as $venta)
                <tr><td>{{ $venta->local }}</td><td class="text-right">{{ number_format($venta->cantidad, 0, ',', '.') }}</td></tr>
            @empty <tr><td colspan="2" class="text-muted text-center p-3">Sin ventas importadas.</td></tr> @endforelse
            </tbody></table></div>
    </div>
    <div class="alert alert-light border small"><strong>Alcance:</strong> el FCO identifica cada compra, pero remisiones y ventas no tienen FCO. Se relacionan por código completo; los movimientos pueden repetir unidades en transferencias. No constituyen stock físico ni conciliación por lote.</div>
</div></section>
@endsection
