<!-- Sucursal Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('sucursal_id', 'Sucursal Id:') !!}
    {!! Form::number('sucursal_id', null, ['class' => 'form-control']) !!}
</div>

<!-- Linea Field -->
<div class="form-group col-sm-6">
    {!! Form::label('linea', 'Linea:') !!}
    {!! Form::text('linea', null, ['class' => 'form-control']) !!}
</div>

<!-- Grupo Plan Field -->
<div class="form-group col-sm-6">
    {!! Form::label('grupo_plan', 'Grupo Plan:') !!}
    {!! Form::text('grupo_plan', null, ['class' => 'form-control']) !!}
</div>

<!-- Talle Field -->
<div class="form-group col-sm-6">
    {!! Form::label('talle', 'Talle:') !!}
    {!! Form::text('talle', null, ['class' => 'form-control']) !!}
</div>

<!-- Color Field -->
<div class="form-group col-sm-6">
    {!! Form::label('color', 'Color:') !!}
    {!! Form::text('color', null, ['class' => 'form-control']) !!}
</div>

<!-- Tejido Field -->
<div class="form-group col-sm-6">
    {!! Form::label('tejido', 'Tejido:') !!}
    {!! Form::text('tejido', null, ['class' => 'form-control']) !!}
</div>

<!-- Codigo Field -->
<div class="form-group col-sm-6">
    {!! Form::label('codigo', 'Codigo:') !!}
    {!! Form::text('codigo', null, ['class' => 'form-control']) !!}
</div>

<!-- Temporada Field -->
<div class="form-group col-sm-6">
    {!! Form::label('temporada', 'Temporada:') !!}
    {!! Form::text('temporada', null, ['class' => 'form-control']) !!}
</div>

<!-- Cant Vta Field -->
<div class="form-group col-sm-6">
    {!! Form::label('cant_vta', 'Cant Vta:') !!}
    {!! Form::number('cant_vta', null, ['class' => 'form-control']) !!}
</div>

<!-- Stock Actual Field -->
<div class="form-group col-sm-6">
    {!! Form::label('stock_actual', 'Stock Actual:') !!}
    {!! Form::number('stock_actual', null, ['class' => 'form-control']) !!}
</div>

<!-- Periodo Field -->
<div class="form-group col-sm-6">
    {!! Form::label('periodo', 'Periodo:') !!}
    {!! Form::text('periodo', null, ['class' => 'form-control']) !!}
</div>

<!-- Fecha Importacion Field -->
<div class="form-group col-sm-6">
    {!! Form::label('fecha_importacion', 'Fecha Importacion:') !!}
    {!! Form::text('fecha_importacion', null, ['class' => 'form-control','id'=>'fecha_importacion']) !!}
</div>

@push('page_scripts')
    <script type="text/javascript">
        $('#fecha_importacion').datepicker()
    </script>
@endpush