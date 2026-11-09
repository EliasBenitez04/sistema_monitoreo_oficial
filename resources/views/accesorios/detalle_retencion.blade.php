<div class="acc-detail-overview">
    <span><i class="fas fa-calendar-alt"></i> Corte <strong>{{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}</strong></span>
    <span><i class="fas fa-store"></i> Sucursal <strong>{{ $sucursal !== '' ? $sucursal : 'Todas' }}</strong></span>
    <span><i class="fas fa-boxes"></i> Código / sucursal <strong>{{ number_format($retencion->count(), 0, ',', '.') }}</strong></span>
    <span>Unidades con stock <strong>{{ number_format($retencion->sum('cantidad'), 0, ',', '.') }}</strong></span>
</div>

@include('accesorios.tabla_retencion', ['filasRetencion' => $retencion])

<div class="acc-detail-legend">
    <i class="fas fa-info-circle"></i>
    <strong>Días en local:</strong> desde la primera recepción confirmada hasta el corte.
    <strong>Días retenidos:</strong> desde la última venta del código en esa sucursal; si no hubo ventas posteriores a la llegada, desde la llegada.
    Solo se muestran saldos positivos. Las devoluciones no reinician el conteo.
    Se consulta todo el historial hasta «Hasta», aunque la factura o recepción sea anterior a «Desde».
    Sin recepción ni venta registrada, los días quedan «Sin dato».
    Los filtros FCO y fecha de envío seleccionan códigos; las fechas no identifican la antigüedad de cada unidad de una compra.
</div>
