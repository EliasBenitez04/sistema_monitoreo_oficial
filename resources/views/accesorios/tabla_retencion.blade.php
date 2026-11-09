<div class="acc-table-scroll acc-detail-table-wrap table-responsive">
    <table class="acc-table acc-detail-table acc-retention-table">
        <thead>
            <tr>
                <th>Sucursal</th>
                <th>Código</th>
                <th>Descripción</th>
                <th class="text-right">Cantidad</th>
                <th>Llegada al local</th>
                <th>Última factura / venta</th>
                <th class="text-right">Días en local</th>
                <th class="text-right">Días retenidos</th>
            </tr>
        </thead>
        <tbody>
            @forelse($filasRetencion as $retenido)
                <tr>
                    <td><strong>{{ $retenido->sucursal }}</strong></td>
                    <td>{{ $retenido->codigo }}</td>
                    <td>{{ $retenido->descripcion }}</td>
                    <td class="text-right">{{ number_format($retenido->cantidad, 0, ',', '.') }}</td>
                    <td>
                        {{ $retenido->fecha_llegada ? \Carbon\Carbon::parse($retenido->fecha_llegada)->format('d/m/Y') : 'Sin recepción registrada' }}
                        @if($retenido->ultima_recepcion && $retenido->ultima_recepcion !== $retenido->fecha_llegada)
                            <small>Última recepción: {{ \Carbon\Carbon::parse($retenido->ultima_recepcion)->format('d/m/Y') }}</small>
                        @endif
                    </td>
                    <td>
                        @if($retenido->ultima_venta)
                            <strong>{{ $retenido->ultima_factura !== '' ? $retenido->ultima_factura : 'Sin comprobante informado' }}</strong>
                            <small>{{ \Carbon\Carbon::parse($retenido->ultima_venta)->format('d/m/Y') }}</small>
                        @else
                            Sin ventas registradas
                        @endif
                    </td>
                    <td class="text-right">{{ $retenido->dias_local === null ? 'Sin dato' : number_format($retenido->dias_local, 0, ',', '.') }}</td>
                    <td class="text-right">
                        <span class="acc-retention-days">{{ $retenido->dias_retenidos === null ? 'Sin dato' : number_format($retenido->dias_retenidos, 0, ',', '.') }}</span>
                        @if($retenido->desde_llegada)<small>Desde la llegada</small>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="acc-retention-empty">No hay artículos con stock positivo para los filtros y la fecha de corte seleccionados.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
