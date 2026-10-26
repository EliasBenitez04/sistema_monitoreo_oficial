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
            ['Entrada Depósito → Central', $etapasRemision['ingreso_deposito'], 'primary'],
            ['Distribuidas desde Central', $etapasRemision['distribuido_central'], 'info'],
            ['Recibidas en locales desde Central', $etapasRemision['recibido_locales'], 'success'],
            ['Ventas netas registradas', $totalVendido, 'secondary'],
            ['Stock calculado', $stockCalculado, ($stockCalculado !== null && $stockCalculado < 0 ? 'danger' : 'success')]
        ] as $kpi)
            <div class="col-md-3 col-6"><div class="card border-{{ $kpi[2] }} shadow-sm"><div class="card-body">
                <div class="text-muted small">{{ $kpi[0] }}</div><h3 class="mb-0">{{ $kpi[1] === null ? 'Sin datos' : number_format($kpi[1], 0, ',', '.') }}</h3>
            </div></div></div>
        @endforeach
    </div>
    <div class="alert alert-light border small">
        <i class="fas fa-info-circle text-primary mr-1"></i>
        <strong>Seguimiento por etapas:</strong> las unidades que ingresan de Depósito a Casa Central
        y después salen de Casa Central hacia los locales <strong>son las mismas prendas</strong>.
        Cada cifra corresponde a una etapa diferente y <strong>no se suma entre etapas</strong>.
        Las transferencias entre locales se presentan aparte:
        <strong>{{ number_format($etapasRemision['traslado_locales'] ?? 0, 0, ',', '.') }} unidades movidas</strong>.
        No representan prendas nuevas.
    </div>
    <div class="card card-outline card-success shadow-sm">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-balance-scale mr-2"></i>Stock calculado por sucursal</h3></div>
        <div class="px-3 py-2 bg-light small text-muted">
            Corte: {{ now()->format('d/m/Y') }}. Fórmula por sucursal:
            <strong>remisiones recibidas − remisiones enviadas − ventas netas históricas</strong>.
            Las devoluciones (notas de crédito negativas) incrementan el saldo. Las remisiones sin recepción no suman al destino.
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover table-sm mb-0">
                <thead><tr>
                    <th>Sucursal</th>
                    <th class="text-right">Entradas recibidas</th>
                    <th class="text-right">Salidas remitidas</th>
                    <th class="text-right">Ventas netas</th>
                    <th class="text-right">En tránsito hacia local</th>
                    <th class="text-right">Stock calculado</th>
                </tr></thead>
                <tbody>
                @forelse($saldosStock as $fila)
                    <tr>
                        <td class="font-weight-bold">{{ $fila->sucursal }}</td>
                        <td class="text-right text-success">+{{ number_format($fila->entradas, 0, ',', '.') }}</td>
                        <td class="text-right text-danger">−{{ number_format($fila->salidas, 0, ',', '.') }}</td>
                        <td class="text-right">−{{ number_format($fila->ventas, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($fila->en_transito, 0, ',', '.') }}</td>
                        <td class="text-right">
                            <span class="badge badge-{{ $fila->saldo < 0 ? 'danger' : ($fila->saldo === 0 ? 'secondary' : 'success') }}" style="font-size:13px">
                                {{ number_format($fila->saldo, 0, ',', '.') }}
                            </span>
                        </td>
                    </tr>
                @empty <tr><td colspan="6" class="text-center text-muted p-3">No hay movimientos ni ventas vinculadas para calcular el saldo.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer small">
            <strong>Atención:</strong> un saldo negativo no se oculta: puede indicar una venta anterior a las remisiones importadas, una recepción faltante o una discrepancia de sucursal.
            El saldo supone que el historial documental importado está completo.
        </div>
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
            <h3 class="card-title">Historial documental de remisiones · movimientos de las mismas unidades</h3>
        </div>
        <div class="px-3 py-2 bg-light border-bottom small">
            <i class="fas fa-warehouse text-primary mr-1"></i><strong>1. Depósito → Casa Central</strong>
            <span class="mx-2 text-muted">→</span>
            <i class="fas fa-truck text-info mr-1"></i><strong>2. Casa Central → Locales</strong>
            <span class="mx-2 text-muted">→</span>
            <span class="text-muted">3. Otros movimientos</span>
            <div class="text-muted mt-1">
                Dentro de cada etapa: fecha de remisión ascendente y número de remisión.
                Una misma prenda puede figurar en dos o más remisiones; las cantidades de esta tabla
                <strong>no deben sumarse para obtener stock ni compras.</strong>
            </div>
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
    <div class="alert alert-light border small"><strong>Alcance:</strong> las compras por FCO se conservan como referencia, pero no se suman directamente al stock. El saldo se calcula una sola vez en cada sucursal, a partir de las recepciones, los envíos y las ventas netas acumuladas. Es un <strong>stock documental estimado</strong>, no un conteo físico. Las unidades en tránsito quedan fuera de las existencias del destino hasta su recepción.</div>
</div></section>
@endsection
