<dl class="iw-cantidades">
    @foreach($cantidades as $etiqueta => $cantidad)
        @php($clase = $etiqueta === 'Disponible en Central' ? 'iw-saldo' : '')
        <dt class="{{ $clase }}">{{ $etiqueta }}</dt>
        <dd class="{{ $clase }}">{{ is_int($cantidad) ? $n($cantidad) : ($cantidad ?? '—') }}</dd>
    @endforeach
</dl>
