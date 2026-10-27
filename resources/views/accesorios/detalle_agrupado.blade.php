@php
    $n = function ($cantidad) { return number_format((int) $cantidad, 0, ',', '.'); };
@endphp

<div class="acc-detail-overview">
    <span><i class="fas fa-calendar-alt"></i> {{ $tipo === 'stock' ? 'Corte de stock' : 'Período de ventas' }}
        <strong>{{ $tipo === 'stock' ? \Carbon\Carbon::parse($hasta)->format('d/m/Y') : \Carbon\Carbon::parse($desde)->format('d/m/Y') . ' – ' . \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}</strong>
    </span>
    <span><i class="fas fa-store"></i> Sucursal <strong>{{ $sucursal !== '' ? $sucursal : 'Todas' }}</strong></span>
    <span><i class="fas fa-barcode"></i> Códigos <strong>{{ $n($grupos->count()) }}</strong></span>
</div>

<div class="acc-detail-table-wrap table-responsive">
    <table class="acc-detail-table">
        <thead>
            <tr>
                <th style="width:19%">Código</th>
                <th>Descripción / sucursal</th>
                <th class="text-right" style="width:20%">Cantidad</th>
            </tr>
        </thead>
        <tbody>
            @forelse($grupos as $grupo)
                <tr class="acc-detail-group">
                    <td colspan="3">
                        <div class="acc-detail-group-head">
                            <div class="acc-detail-article">
                                <span class="acc-detail-box-icon"><i class="fas fa-box"></i></span>
                                <div>
                                    <span class="acc-detail-code">{{ $grupo->codigo }}</span>
                                    <span class="acc-detail-desc">{{ $grupo->descripcion }}</span>
                                </div>
                            </div>
                            <span class="acc-detail-count">{{ $n(count($grupo->locales)) }} {{ count($grupo->locales) === 1 ? 'sucursal' : 'sucursales' }}</span>
                        </div>
                    </td>
                </tr>
                @foreach($grupo->locales as $local)
                    <tr class="acc-detail-local">
                        <td class="acc-detail-branch-indent"><i class="fas fa-level-up-alt fa-rotate-90"></i></td>
                        <td class="acc-local-name"><i class="fas fa-store-alt"></i> {{ $local->sucursal }}</td>
                        <td class="text-right"><span class="acc-detail-qty {{ $local->cantidad < 0 ? 'is-negative' : 'is-positive' }}">{{ $n($local->cantidad) }}</span></td>
                    </tr>
                @endforeach
                <tr class="acc-detail-subtotal">
                    <td colspan="2" class="text-right"><i class="fas fa-calculator"></i> Subtotal · {{ $grupo->codigo }}</td>
                    <td class="text-right"><strong class="{{ $grupo->subtotal < 0 ? 'acc-detail-negative' : '' }}">{{ $n($grupo->subtotal) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="acc-detail-empty">
                        <i class="fas fa-box-open"></i>
                        <strong>Sin {{ $tipo === 'stock' ? 'existencias distintas de cero' : 'ventas' }}</strong>
                        <span>No se encontraron registros para los filtros seleccionados.</span>
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="acc-detail-total">
                <td colspan="2" class="text-right">TOTAL GENERAL</td>
                <td class="text-right">{{ $n($total) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
<div class="acc-detail-legend">
    @if($tipo === 'stock')
        <i class="fas fa-info-circle"></i>
        Solo se muestran sucursales con saldo distinto de cero. Los saldos negativos se conservan para revisión.
        Stock documental = entradas recibidas − salidas remitidas − ventas netas históricas.
    @else
        <i class="fas fa-info-circle"></i>
        Ventas netas del período, con notas de crédito descontadas.
        Cada subtotal corresponde a su código; el total general suma los códigos una sola vez.
    @endif
</div>
