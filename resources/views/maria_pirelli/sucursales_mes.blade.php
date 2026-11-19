<section class="card mp-card" id="sucursales-mes" aria-labelledby="mp-mes-sucursales-titulo">
    <header class="card-header mp-card-header mp-card-acciones">
        <div>
            <h2 id="mp-mes-sucursales-titulo">
                Ventas y stock por sucursal
                @if($mesSeleccionado)
                    · {{ $mesSeleccionado }}
                @endif
            </h2>
            @if($mesSeleccionado)
                <p>Ventas y envíos del {{ $fecha($desde) }} al {{ $fecha($hasta) }}.</p>
            @endif
        </div>
        @if($mesSeleccionado)
            <div class="d-flex flex-wrap mp-stock-acciones">
                @if($sucursal !== '')
                    <a class="btn btn-sm btn-outline-secondary"
                        href="{{ route('maria-pirelli.index', ['desde' => $desde, 'hasta' => $hasta, 'linea' => $linea, 'buscar' => $buscar]) }}#sucursales-mes">Todas las sucursales</a>
                @endif
                <a class="btn btn-sm btn-outline-primary"
                    href="{{ route('maria-pirelli.index', compact('desde', 'hasta', 'sucursal', 'linea', 'buscar')) }}#remisiones">Ver remisiones</a>
            </div>
        @endif
    </header>

    @if(!$mesSeleccionado)
        <p class="mp-vacio">Elegí un mes en el resumen mensual o filtrá fechas de un mismo mes para ver sus sucursales.</p>
    @else
        <div class="mp-mes-contexto">
            @if($stockMes['corte'])
                <p>
                    <strong>Stock del {{ $fecha($stockMes['corte']->fecha_corte) }}</strong>
                    · Último inventario importado hasta el {{ $fecha($hasta) }}.
                    @if($stockMes['corte']->fecha_corte !== $hasta)
                        No incluye movimientos posteriores a esa fecha de stock.
                    @endif
                </p>
            @else
                <p>No hay inventario importado hasta el {{ $fecha($hasta) }}. Podés consultar las ventas y los envíos del período.</p>
            @endif
            <p>Costo del stock = cantidad neta × costo unitario. Venta estimada = unidades positivas × precio de venta del inventario, sin descuentos.</p>
            @if($sucursalesSinStock > 0)
                <p><strong>{{ $unidades($sucursalesSinStock) }}</strong> sucursales con movimientos sin stock informado para estos filtros. “Sin dato” indica que falta inventario.</p>
            @endif
            <p id="mp-mes-orden-ayuda">Hacé clic en una cabecera para ordenar. Volvé a hacer clic para invertir el orden.</p>
            <p id="mp-mes-orden-estado" class="sr-only" role="status" aria-live="polite"></p>
        </div>

        <div class="table-responsive mp-tabla-scroll" tabindex="0" role="region" aria-labelledby="mp-mes-sucursales-titulo">
            <table id="mp-sucursales-mes-tabla" class="table table-hover mp-tabla mp-tabla-mes mb-0" aria-describedby="mp-mes-orden-ayuda">
                <thead>
                    <tr>
                        @foreach(['Sucursal', 'Enviadas', 'Vendidas netas', 'Venta neta', 'Stock del corte', 'Costo del stock', 'Venta estimada del stock'] as $columna)
                            <th scope="col" @class(['text-right' => !$loop->first]) aria-sort="none">
                                <button type="button" class="mp-mes-ordenar" data-sort-column="{{ $loop->index }}"
                                    data-sort-type="{{ $loop->first ? 'text' : 'number' }}" data-sort-label="{{ $columna }}">
                                    {{ $columna }} <i class="fas fa-sort" aria-hidden="true"></i>
                                </button>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($sucursalesDelMes as $local)
                        <tr>
                            <th scope="row" data-sort-value="{{ $local['sucursal'] }}">
                                @if($local['filtrable'])
                                    <a class="mp-enlace"
                                        href="{{ route('maria-pirelli.index', ['desde' => $desde, 'hasta' => $hasta, 'sucursal' => $local['sucursal'], 'linea' => $linea, 'buscar' => $buscar]) }}#sucursales-mes">
                                        {{ $local['sucursal'] }}
                                    </a>
                                @else
                                    {{ $local['sucursal'] }}
                                @endif
                            </th>
                            <td class="text-right" data-sort-value="{{ $conRemisiones ? $local['enviadas'] : '' }}">{{ $conRemisiones ? $unidades($local['enviadas']) : '—' }}</td>
                            <td class="text-right" data-sort-value="{{ $conVentas ? $local['vendidas'] : '' }}">{{ $conVentas ? $unidades($local['vendidas']) : '—' }}</td>
                            <td class="text-right text-nowrap" data-sort-value="{{ $conVentas ? $local['venta_neta'] : '' }}">{{ $conVentas ? $guaranies($local['venta_neta']) : '—' }}</td>
                            <td class="text-right text-nowrap" data-sort-value="{{ $local['unidades'] }}">
                                @if($local['unidades'] !== null)
                                    <strong @class(['text-danger' => $local['unidades'] < 0])>{{ $unidades($local['unidades']) }}</strong>
                                    @if($local['negativas'] > 0)
                                        <small class="d-block text-danger">{{ $unidades($local['negativas']) }} negativas</small>
                                    @endif
                                @else
                                    <span class="mp-sin-dato">Sin dato</span>
                                @endif
                            </td>
                            <td class="text-right text-nowrap" data-sort-value="{{ $local['costo'] }}">{{ $local['costo'] !== null ? $guaranies($local['costo']) : 'Sin dato' }}</td>
                            <td class="text-right text-nowrap" data-sort-value="{{ $local['venta_potencial'] }}"><strong>{{ $local['venta_potencial'] !== null ? $guaranies($local['venta_potencial']) : 'Sin dato' }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="mp-vacio">No hay ventas, envíos ni stock informado para estas sucursales y artículos.</td></tr>
                    @endforelse
                </tbody>
                @if($sucursalesDelMes->isNotEmpty())
                    <tfoot>
                        <tr>
                            <th scope="row">Total informado</th>
                            <td class="text-right">{{ $conRemisiones ? $unidades($totalesMes['enviadas']) : '—' }}</td>
                            <td class="text-right">{{ $conVentas ? $unidades($totalesMes['vendidas']) : '—' }}</td>
                            <td class="text-right text-nowrap">{{ $conVentas ? $guaranies($totalesMes['venta_neta']) : '—' }}</td>
                            <td class="text-right">{{ $totalesMes['unidades'] !== null ? $unidades($totalesMes['unidades']) : 'Sin dato' }}</td>
                            <td class="text-right text-nowrap">{{ $totalesMes['costo'] !== null ? $guaranies($totalesMes['costo']) : 'Sin dato' }}</td>
                            <td class="text-right text-nowrap">{{ $totalesMes['venta_potencial'] !== null ? $guaranies($totalesMes['venta_potencial']) : 'Sin dato' }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    @endif
</section>
