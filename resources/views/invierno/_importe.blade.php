@php
    $monto = $valor[$tipo];
    $faltantes = $valor[$tipo === 'costo' ? 'sin_costo' : 'sin_precio'];
@endphp
<span class="iw-importe {{ $monto === null ? 'text-muted' : '' }}">{{ \App\Support\InviernoValorizacion::mostrar($monto) }}</span>
@if($faltantes && empty($sinIndicador))
    <small class="d-block iw-importe-pendiente">{{ number_format($faltantes, 0, ',', '.') }} {{ $tipo === 'costo' ? 'sin costo' : 'sin precio' }}</small>
@endif
