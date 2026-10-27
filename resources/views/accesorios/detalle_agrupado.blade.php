@php
    $n = function ($cantidad) { return number_format((int) $cantidad, 0, ',', '.'); };
@endphp
<div class="acc-detail-overview">
    <span><i class="fas fa-calendar-alt mr-1"></i>{{ $tipo === 'stock' ? 'Stock hasta' : 'Ventas del' }}
        <strong>{{ $tipo === 'stock' ? \Carbon\Carbon::parse($hasta)->format('d/m/Y') : \Carbon\Carbon::parse($desde)->format('d/m/Y') . ' al ' . \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}</strong></span>
    <span><i class="fas fa-store mr-1"></i>Sucursal: <strong>{{ $sucursal !== '' ? $sucursal : 'Todas' }}</strong></span>
    <span><i class="fas fa-barcode mr-1"></i><strong>{{ $n($grupos->count()) }}</strong> códigos con movimientos</span>
</div>
<div class="table-responsive">
    <table class="acc-detail-table">
        <thead>
            <tr><th>Código</th><th>Descripción / sucursal</th><th class="text-right">Cantidad</th></tr>
        </thead>
        <tbody>
            @forelse($grupos as $grupo)
                <tr class="acc-detail-group">
                    <td><strong>{{ $grupo->codigo }}</strong></td>
                    <td><strong>{{ $grupo->descripcion }}</strong></td>
                    <td></td>
                </tr>
                @foreach($grupo->locales as $local)
                    <tr class="acc-detail-local">
                        <td></td>
                        <td class="acc-local-name"><i class="fas fa-store-alt mr-2"></i>{{ $local->sucursal }}</td>
                        <td class="text-right {{ $local->cantidad < 0 ? 'acc-detail-negative' : '' }}">{{ $n($local->cantidad) }}</td>
                    </tr>
                @endforeach
                <tr class="acc-detail-subtotal">
                    <td colspan="2" class="text-right">Subtotal · {{ $grupo->codigo }}</td>
                    <td class="text-right {{ $grupo->subtotal < 0 ? 'acc-detail-negative' : '' }}">{{ $n($grupo->subtotal) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted p-4">No hay {{ $tipo === 'stock' ? 'movimientos de stock' : 'ventas' }} para los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="acc-detail-total"><td colspan="2" class="text-right">TOTAL GENERAL</td><td class="text-right">{{ $n($total) }}</td></tr>
        </tfoot>
    </table>
</div>
<p class="text-muted small mt-3 mb-0">
    @if($tipo === 'stock')
        Stock documental por local: entradas recibidas − salidas remitidas − ventas netas históricas. Los saldos negativos se muestran sin ocultar.
    @else
        Unidades netas del período. Las notas de crédito restan unidades; el total coincide con el indicador de unidades vendidas netas para los mismos filtros.
    @endif
    Las sucursales aparecen debajo de cada código y los subtotales no se suman por separado del total general.
</p>
