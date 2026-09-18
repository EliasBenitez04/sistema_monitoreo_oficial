@php
    $productos = $productos ?? collect();
@endphp

<table class="table table-hover align-middle">

    <thead>
        <tr>
            <th>Código</th>
            <th>Producto</th>
            <th>Precio</th>
            <th>Stock general</th>
            <th>Disponible para pedir</th>
        </tr>
    </thead>

    <tbody>

        @forelse($productos as $product)
            @php
                $stockSucursal = $product->cantidad ?? 0;
                $stockGeneral = $product->stock_general ?? 0;
                $stockDisponible = $product->stock_disponible_pedir ?? 0;
            @endphp

            <tr
                @if ($stockDisponible >=0 ) style="cursor: pointer;"
                    onclick="seleccionarProductoPed(
                        '{{ $product->art_codigo }}',
                        '{{ addslashes($product->art_descripcion) }}',
                        {{ $product->prec_vent ?? 0 }},
                        {{ $stockDisponible }}
                    )"
                @else
                    style="opacity: 0.6; cursor: not-allowed;" @endif>

                {{-- Código --}}
                <td>
                    <strong>
                        {{ $product->art_codigo }}
                    </strong>
                </td>

                {{-- Descripción --}}
                <td>
                    {{ $product->art_descripcion }}
                </td>

                {{-- Precio --}}
                <td>
                    {{ number_format($product->prec_vent ?? 0, 0, ',', '.') }}
                </td>

                {{-- Stock general --}}
                <td class="text-center align-middle">
                    <span class="badge bg-info text-dark">
                        {{ number_format($stockGeneral, 0, ',', '.') }}
                    </span>
                </td>

                {{-- Stock disponible para pedir --}}
                <td>

                    @if ($stockDisponible > 0)
                        <span class="badge bg-success">
                            Puede pedir:
                            {{ number_format($stockDisponible, 0, ',', '.') }}
                        </span>
                    @else
                        <span class="badge bg-danger text-dark">
                            Sin stock para pedir
                        </span>
                    @endif

                </td>

            </tr>

        @empty

            <tr>
                <td colspan="6" class="text-center">
                    No se encontraron productos.
                </td>
            </tr>
        @endforelse

    </tbody>

</table>
