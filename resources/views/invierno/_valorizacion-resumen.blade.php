<div class="row" aria-label="Valorización de prendas">
    @foreach(['prendas' => $tituloDocumentado ?? 'Documentado', 'disponible' => 'Disponible en Central'] as $etapa => $titulo)
        @php
            $porRevisar = $etapa === 'disponible' && !empty($saldoPorRevisar);
            $valoresEtapa = $porRevisar || !empty($sinDetalleValorizado)
                ? ['costo' => null, 'venta' => null, 'sin_costo' => 0, 'sin_precio' => 0]
                : $valorizacion[$etapa];
        @endphp
        <div class="col-md-6">
            <div class="card iw-metric {{ $etapa === 'disponible' ? 'iw-green' : 'iw-primary' }}">
                <h2 class="iw-valor-titulo">{{ $titulo }}</h2>
                <div class="row">
                    <div class="col-sm-6 mb-2 mb-sm-0"><span>Costo total</span><div class="iw-valor-destacado">@include('invierno._importe', ['valor' => $valoresEtapa, 'tipo' => 'costo'])</div></div>
                    <div class="col-sm-6"><span>Valor a precio de venta</span><div class="iw-valor-destacado">@include('invierno._importe', ['valor' => $valoresEtapa, 'tipo' => 'venta'])</div></div>
                </div>
                @if($porRevisar)
                    <small class="mt-2">Saldo pendiente de revisión.</small>
                @endif
            </div>
        </div>
    @endforeach
</div>
@empty($sinDetalleValorizado)
<details class="card mb-3 p-3">
    <summary>Valorización por estado</summary>
    <div class="table-responsive iw-tabla mt-2">
        <table class="table table-sm" role="table" aria-label="Valorización por estado">
            <thead role="rowgroup">
                <tr role="row"><th role="columnheader" scope="col">Estado</th><th role="columnheader" scope="col">Prendas</th><th role="columnheader" scope="col">Costo total</th><th role="columnheader" scope="col">Valor a precio de venta</th></tr>
            </thead>
            <tbody role="rowgroup">
            @foreach(\App\Support\InviernoValorizacion::ETAPAS as $etapa => $nombre)
                <tr role="row">
                    <td role="cell" class="iw-ficha">{{ ucfirst($nombre) }}</td>
                    @if($etapa === 'disponible' && !empty($saldoPorRevisar))
                        <td role="cell" colspan="3" class="text-muted">Por revisar</td>
                    @else
                        <td role="cell" data-etiqueta="Prendas">{{ $n($valorizacion[$etapa]['cantidad']) }}</td>
                        <td role="cell" data-etiqueta="Costo total">@include('invierno._importe', ['valor' => $valorizacion[$etapa], 'tipo' => 'costo'])</td>
                        <td role="cell" data-etiqueta="Valor a precio de venta">@include('invierno._importe', ['valor' => $valorizacion[$etapa], 'tipo' => 'venta'])</td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</details>
<p class="iw-note mb-3">Importes según ENVIOS. El valor a precio de venta es referencial. Si faltan datos, el subtotal suma las prendas que tienen el valor cargado.</p>
@endempty
