@php
    $stockTotales = $stockActual['totales'];
@endphp
<div class="mp-indicadores">
    <div class="mp-indicador mp-indicador-envios">
        <span class="mp-indicador-icono"><i class="fas fa-boxes" aria-hidden="true"></i></span>
        <div>
            <span>Stock general</span>
            <strong>{{ $unidades($stockTotales['unidades']) }} unidades</strong>
            @if($stockTotales['negativas'] > 0)
                <span>{{ $unidades($stockTotales['positivas']) }} positivas · {{ $unidades($stockTotales['negativas']) }} negativas</span>
            @endif
        </div>
    </div>
    <div class="mp-indicador mp-indicador-importe">
        <span class="mp-indicador-icono"><i class="fas fa-coins" aria-hidden="true"></i></span>
        <div>
            <span>Costo general</span>
            <strong>{{ $guaranies($stockTotales['costo']) }}</strong>
            <span>Stock neto a costo unitario</span>
        </div>
    </div>
    <div class="mp-indicador mp-indicador-ventas">
        <span class="mp-indicador-icono"><i class="fas fa-tag" aria-hidden="true"></i></span>
        <div>
            <span>Valor de venta potencial</span>
            <strong>{{ $guaranies($stockTotales['venta_potencial']) }}</strong>
            <span>Unidades positivas @if($stockTotales['negativas'] > 0) · Neto {{ $guaranies($stockTotales['valor_neto']) }} @endif</span>
        </div>
    </div>
</div>
