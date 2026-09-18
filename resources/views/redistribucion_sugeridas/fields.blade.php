<!-- Codigo Field -->
<div class="form-group col-sm-6">
    {!! Form::label('codigo', 'Codigo:') !!}
    {!! Form::text('codigo', null, ['class' => 'form-control']) !!}
</div>

<!-- Sucursal Origen Field -->
<div class="form-group col-sm-6">
    {!! Form::label('sucursal_origen', 'Sucursal Origen:') !!}
    {!! Form::number('sucursal_origen', null, ['class' => 'form-control']) !!}
</div>

<!-- Sucursal Destino Field -->
<div class="form-group col-sm-6">
    {!! Form::label('sucursal_destino', 'Sucursal Destino:') !!}
    {!! Form::number('sucursal_destino', null, ['class' => 'form-control']) !!}
</div>

<!-- Cantidad Field -->
<div class="form-group col-sm-6">
    {!! Form::label('cantidad', 'Cantidad:') !!}
    {!! Form::number('cantidad', null, ['class' => 'form-control']) !!}
</div>

<!-- Stock Origen Field -->
<div class="form-group col-sm-6">
    {!! Form::label('stock_origen', 'Stock Origen:') !!}
    {!! Form::number('stock_origen', null, ['class' => 'form-control']) !!}
</div>

<!-- Stock Destino Field -->
<div class="form-group col-sm-6">
    {!! Form::label('stock_destino', 'Stock Destino:') !!}
    {!! Form::number('stock_destino', null, ['class' => 'form-control']) !!}
</div>

<!-- Venta Origen Field -->
<div class="form-group col-sm-6">
    {!! Form::label('venta_origen', 'Venta Origen:') !!}
    {!! Form::number('venta_origen', null, ['class' => 'form-control']) !!}
</div>

<!-- Venta Destino Field -->
<div class="form-group col-sm-6">
    {!! Form::label('venta_destino', 'Venta Destino:') !!}
    {!! Form::number('venta_destino', null, ['class' => 'form-control']) !!}
</div>

<!-- Motivo Field -->
<div class="form-group col-sm-6">
    {!! Form::label('motivo', 'Motivo:') !!}
    {!! Form::text('motivo', null, ['class' => 'form-control']) !!}
</div>

<!-- Estado Field -->
<div class="form-group col-sm-6">
    {!! Form::label('estado', 'Estado:') !!}
    {!! Form::text('estado', null, ['class' => 'form-control']) !!}
</div>

<!-- Fecha Generacion Field -->
<div class="form-group col-sm-6">
    {!! Form::label('fecha_generacion', 'Fecha Generacion:') !!}
    {!! Form::text('fecha_generacion', null, ['class' => 'form-control','id'=>'fecha_generacion']) !!}
</div>

@push('page_scripts')
    <script type="text/javascript">
        $('#fecha_generacion').datepicker()
    </script>
@endpush