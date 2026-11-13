<div class="iw-valores-celda mt-1">
    <div><span class="text-muted">Costo</span> @include('invierno._importe', ['tipo' => 'costo'])</div>
    <div><span class="text-muted">{{ !empty($sinIndicador) ? 'Precio de venta' : 'Venta ref.' }}</span> @include('invierno._importe', ['tipo' => 'venta'])</div>
</div>
