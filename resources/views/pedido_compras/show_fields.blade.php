<div class="table-responsive">

    <!-- ================= DETALLES GENERALES ================= -->
    <table class="table table-bordered table-sm"
        style="border-collapse: collapse; width:100%; font-family:Arial,sans-serif; font-size:13px;">

        <thead style="background:#0051a3; color:#fff;">
            <tr>
                <th colspan="6" style="padding:6px; text-align:center; font-size:15px; font-weight:bold;">
                    Detalles Generales
                </th>
            </tr>
        </thead>

        <tbody style="background:#f8f9fa;">
            <tr>
                <td style="padding:6px; font-weight:bold; width:12%;">Fecha Pedido</td>
                <td style="padding:6px; width:18%;">
                    {{ \Carbon\Carbon::parse($pedido->ped_fecha)->format('d/m/Y') }}
                </td>

                <td style="padding:6px; font-weight:bold; width:10%;">Usuario</td>
                <td style="padding:6px; width:20%;">{{ $pedido->usuario }}</td>

                <td style="padding:6px; font-weight:bold; width:10%;">Sucursal</td>
                <td style="padding:6px; width:30%;">{{ $pedido->sucursal }}</td>
            </tr>

            <tr>
                <td style="padding:6px; font-weight:bold;">Estado</td>
                <td style="padding:6px;">{{ $pedido->ped_estado }}</td>

                <td style="padding:6px; font-weight:bold;">Condición</td>
                <td style="padding:6px;">{{ $pedido->condicion }}</td>

                <td style="padding:6px; font-weight:bold;">Cliente</td>
                <td style="padding:6px;">{{ $pedido->cliente }}</td>
            </tr>
        </tbody>
    </table>


    <!-- ================= PRODUCTOS ================= -->
    <table class="table table-bordered table-sm mt-2"
        style="border-collapse: collapse; width:100%; font-family:Arial,sans-serif; font-size:12px;">

        <thead style="background:#0051a3; color:#fff;">
            <tr>
                <th style="width:10%; text-align:center; padding:6px;">Código</th>
                <th style="width:36%; text-align:left; padding:6px;">Producto</th>
                <th style="width:8%; text-align:center; padding:6px;">Cant.</th>
                <th style="width:14%; text-align:center; padding:6px;">Precio</th>
                <th style="width:14%; text-align:center; padding:6px;">Subtotal</th>

                @if ($detalle->first()->det_descuento > 0)
                    <th style="width:8%; text-align:center; padding:6px;">Desc.</th>
                    <th style="width:16%; text-align:center; padding:6px;">Total</th>
                @endif
            </tr>
        </thead>

        <tbody>
            @forelse ($detalle as $det)
                @php
                    $factor = 1 - $det->det_descuento / 100;

                    if ($det->det_cantidad > 0 && $factor > 0) {
                        $precioUnitario = $det->det_subtotal / ($det->det_cantidad * $factor);
                        $subtotalSinDesc = $precioUnitario * $det->det_cantidad;
                    } else {
                        $precioUnitario = 0;
                        $subtotalSinDesc = 0;
                    }

                    $subtotalConDesc = $det->det_subtotal;
                @endphp

                <tr style="background:#fff;">

                    <td style="padding:5px; text-align:center; font-weight:bold;">
                        {{ $det->art_codigo }}
                    </td>

                    <td style="padding:5px;">
                        {{ $det->art_descripcion }}
                    </td>

                    <td style="padding:5px; text-align:center;">
                        {{ $det->det_cantidad }}
                    </td>

                    <td style="padding:5px; text-align:right;">
                        {{ number_format($precioUnitario, 0, ',', '.') }}
                    </td>

                    <td style="padding:5px; text-align:right;">
                        {{ number_format($subtotalSinDesc, 0, ',', '.') }}
                    </td>

                    @if ($det->det_descuento > 0)
                        <td style="padding:5px; text-align:center;">
                            {{ $det->det_descuento }}%
                        </td>

                        <td style="padding:5px; text-align:right;">
                            {{ number_format($subtotalConDesc, 0, ',', '.') }}
                        </td>
                    @endif

                </tr>

            @empty

                <tr>
                    <td colspan="7" style="padding:10px; text-align:center; color:#666;">
                        No hay detalles disponibles
                    </td>
                </tr>
            @endforelse
        </tbody>


        <!-- ================= FOOTER ================= -->
        <tfoot>

            @if ($detalle->count() > 0)

                @php
                    $totalCantidad = $detalle->sum('det_cantidad');

                    $totalSinDesc = $detalle->sum(function ($d) {
                        $factor = 1 - $d->det_descuento / 100;
                        return $factor > 0 ? $d->det_subtotal / $factor : 0;
                    });

                    $totalConDesc = $detalle->sum('det_subtotal');
                @endphp

                <tr style="background:#e9ecef; font-weight:bold;">

                    <td colspan="2" style="padding:6px; text-align:center;">
                        TOTAL
                    </td>

                    <td style="padding:6px; text-align:center;">
                        {{ $totalCantidad }}
                    </td>

                    <td style="padding:6px; text-align:center;">-</td>

                    <td style="padding:6px; text-align:right;">
                        {{ number_format($totalSinDesc, 0, ',', '.') }}
                    </td>

                    @if ($detalle->first()->det_descuento > 0)
                        <td style="padding:6px; text-align:center;">-</td>

                        <td style="padding:6px; text-align:right;">
                            {{ number_format($totalConDesc, 0, ',', '.') }}
                        </td>
                    @endif

                </tr>

            @endif

        </tfoot>

    </table>

</div>
