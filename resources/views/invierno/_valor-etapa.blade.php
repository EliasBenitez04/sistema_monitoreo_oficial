<td role="cell" data-etiqueta="{{ $etiqueta }}" class="{{ $etapa === 'disponible' ? 'iw-disponible-importe iw-etapa-saldo' : '' }}">
    <div class="iw-etapa-cantidad"><strong>{{ $n($registro[$etapa]) }}</strong> prendas</div>
    @if($registro[$etapa] > 0 || !empty($mostrarCero))
        @include('invierno._valores-celda', ['valor' => $registro['valorizacion'][$etapa]])
    @endif
</td>
