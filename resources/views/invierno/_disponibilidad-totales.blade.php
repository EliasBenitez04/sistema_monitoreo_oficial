<div class="row">
    <div class="col-6 col-lg-2"><div class="card iw-metric iw-primary"><span>Prendas documentadas</span><strong>{{ $n($resumen['prendas']) }}</strong><small>{{ $n($resumen['documentos']) }} remisiones · {{ $n($resumen['codigos']) }} códigos</small></div></div>
    <div class="col-6 col-lg-2"><div class="card iw-metric iw-primary"><span>Recibido en Central</span><strong>{{ $n($resumen['central']) }}</strong><small>Recepción confirmada</small></div></div>
    <div class="col-6 col-lg-2"><div class="card iw-metric iw-orange"><span>Pendiente en Central</span><strong>{{ $n($resumen['pendiente']) }}</strong><small>Todavía no disponible</small></div></div>
    <div class="col-6 col-lg-2"><div class="card iw-metric iw-primary"><span>Enviado a locales</span><strong>{{ $n($resumen['remitido']) }}</strong><small>{{ $n($resumen['recibido_local']) }} recibidas · {{ $n($resumen['transito_local']) }} en tránsito</small></div></div>
    <div class="col-6 col-lg-2"><div class="card iw-metric iw-green"><span>Disponible en Central</span><strong class="text-success">{{ $n($resumen['disponible']) }}</strong><small>Recibido menos salidas asociadas</small></div></div>
    <div class="col-6 col-lg-2"><div class="card iw-metric iw-orange"><span>Saldo por revisar</span><strong>{{ $n($resumen['por_revisar']) }}</strong><small>{{ $n($resumen['lineas_revision']) }} líneas con diferencias</small></div></div>
</div>
