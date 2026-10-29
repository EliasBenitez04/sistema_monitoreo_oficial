@php
    $n = function ($cantidad) { return number_format((int) $cantidad, 0, ',', '.'); };
    $mostrarEdicion = $tipo === 'stock' && $puedeEditarStock;
    $columnas = $mostrarEdicion ? 4 : 3;
@endphp

<div class="acc-detail-overview">
    <span><i class="fas fa-calendar-alt"></i> {{ $tipo === 'stock' ? 'Corte de stock' : 'Período de ventas' }}
        <strong>{{ $tipo === 'stock' ? \Carbon\Carbon::parse($hasta)->format('d/m/Y') : \Carbon\Carbon::parse($desde)->format('d/m/Y') . ' – ' . \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}</strong>
    </span>
    <span><i class="fas fa-store"></i> Sucursal <strong>{{ $sucursal !== '' ? $sucursal : 'Todas' }}</strong></span>
    <span><i class="fas fa-barcode"></i> Códigos <strong>{{ $n($grupos->count()) }}</strong></span>
    @if($mostrarEdicion)
        <span><i class="fas fa-user-shield"></i> <strong>Edición exclusiva ADMIN</strong></span>
    @endif
</div>

<div class="acc-detail-table-wrap table-responsive">
    <table class="acc-detail-table">
        <thead>
            <tr>
                <th style="width:19%">Código</th>
                <th>Descripción / sucursal</th>
                <th class="text-right" style="width:20%">Cantidad</th>
                @if($mostrarEdicion)<th class="text-center" style="width:12%">Acción</th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse($grupos as $grupo)
                <tr class="acc-detail-group">
                    <td colspan="{{ $columnas }}">
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
                    @php $ajusteId = 'acc-ajuste-' . $grupo->articulo_id . '-' . $loop->index; @endphp
                    <tr class="acc-detail-local">
                        <td class="acc-detail-branch-indent"><i class="fas fa-level-up-alt fa-rotate-90"></i></td>
                        <td class="acc-local-name">
                            <i class="fas fa-store-alt"></i> {{ $local->sucursal }}
                            @if($tipo === 'stock' && $local->ajuste !== 0)
                                <small class="acc-adjusted-note">Ajuste manual: {{ $local->ajuste > 0 ? '+' : '' }}{{ $n($local->ajuste) }}</small>
                            @endif
                        </td>
                        <td class="text-right"><span class="acc-detail-qty {{ $local->cantidad < 0 ? 'is-negative' : ($local->cantidad === 0 ? 'is-zero' : 'is-positive') }}">{{ $n($local->cantidad) }}</span></td>
                        @if($mostrarEdicion)
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary acc-ajuste-toggle" data-target="{{ $ajusteId }}" aria-expanded="false" aria-controls="{{ $ajusteId }}">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                            </td>
                        @endif
                    </tr>
                    @if($mostrarEdicion)
                        <tr id="{{ $ajusteId }}" class="acc-ajuste-row d-none">
                            <td colspan="{{ $columnas }}">
                                <form action="{{ route('accesorios.stock.ajustar', $grupo->articulo_id) }}" method="POST" class="acc-ajuste-form">
                                    @csrf
                                    <input type="hidden" name="sucursal_key" value="{{ $local->sucursal_key }}">
                                    <input type="hidden" name="saldo_anterior" value="{{ $local->cantidad }}">
                                    <div class="acc-ajuste-fields">
                                        <div>
                                            <label>Nuevo stock · {{ $local->sucursal }}</label>
                                            <input type="number" class="form-control form-control-sm" name="saldo_nuevo" value="{{ max(0, $local->cantidad) }}" min="0" max="100000000" step="1" required>
                                        </div>
                                        <div class="acc-ajuste-motivo">
                                            <label>Motivo (opcional)</label>
                                            <input type="text" class="form-control form-control-sm" name="motivo" maxlength="250" placeholder="Ej.: conteo físico">
                                        </div>
                                        <div class="acc-ajuste-buttons">
                                            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Guardar</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary acc-ajuste-cancelar" data-target="{{ $ajusteId }}">Cancelar</button>
                                        </div>
                                    </div>
                                    <small class="text-muted">Saldo actual: {{ $n($local->cantidad) }}. El cambio se registra con usuario, fecha y diferencia; no modifica remisiones ni ventas.</small>
                                    <div class="acc-ajuste-error alert alert-danger mt-2 mb-0 d-none" role="alert"></div>
                                </form>
                            </td>
                        </tr>
                    @endif
                @endforeach
                <tr class="acc-detail-subtotal">
                    <td colspan="{{ $columnas - 1 }}" class="text-right"><i class="fas fa-calculator"></i> Subtotal · {{ $grupo->codigo }}</td>
                    <td class="text-right"><strong class="{{ $grupo->subtotal < 0 ? 'acc-detail-negative' : '' }}">{{ $n($grupo->subtotal) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $columnas }}" class="acc-detail-empty">
                        <i class="fas fa-box-open"></i>
                        <strong>Sin {{ $tipo === 'stock' ? 'existencias registradas' : 'ventas' }}</strong>
                        <span>No se encontraron registros para los filtros seleccionados.</span>
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="acc-detail-total">
                <td colspan="{{ $columnas - 1 }}" class="text-right">TOTAL GENERAL</td>
                <td class="text-right">{{ $n($total) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
<div class="acc-detail-legend">
    @if($tipo === 'stock')
        <i class="fas fa-info-circle"></i>
        Se muestran también saldos cero para permitir corregirlos.
        Stock ajustado = entradas recibidas − salidas remitidas − ventas netas históricas + ajustes manuales registrados.
        @if(!$mostrarEdicion && $hasta !== now()->toDateString())
            Para editar saldos, seleccioná como fecha de corte el día de hoy.
        @endif
    @else
        <i class="fas fa-info-circle"></i>
        Ventas netas del período, con notas de crédito descontadas.
        Cada subtotal corresponde a su código; el total general suma los códigos una sola vez.
    @endif
</div>
