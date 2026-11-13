<div class="iw-valores-celda">
    <div>
        <span class="iw-etiqueta-importe">Costo</span>
        @include('invierno._importe', ['tipo' => 'costo'])
    </div>
    <div>
        <span class="iw-etiqueta-importe">{{ !empty($sinIndicador) ? 'Precio de venta' : 'Venta referencial' }}</span>
        @include('invierno._importe', ['tipo' => 'venta'])
    </div>
</div>
