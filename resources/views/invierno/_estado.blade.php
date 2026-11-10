@php
    $colorEstado = [
        'SIN_REMISION' => 'iw-muted', 'PENDIENTE_RECEPCION' => 'iw-warning',
        'RECEPCION_PARCIAL' => 'iw-warning', 'EN_CENTRAL' => 'iw-info',
        'ENVIO_PARCIAL' => 'iw-info', 'EN_TRANSITO_LOCALES' => 'iw-warning',
        'COMPLETO' => 'iw-success', 'REVISAR' => 'iw-danger',
    ][$documento->estado];
@endphp
<span class="iw-pill {{ $colorEstado }}">{{ $documento->estado_texto }}</span>
