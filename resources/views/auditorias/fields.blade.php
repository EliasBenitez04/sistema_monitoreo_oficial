<!-- Datos Antiguos Field -->
<div class="form-group col-sm-6">
    {!! Form::label('datos_antiguos', 'Datos Antiguos:') !!}
    {!! Form::text('datos_antiguos', null, ['class' => 'form-control']) !!}
</div>

<!-- Datos Nuevos Field -->
<div class="form-group col-sm-6">
    {!! Form::label('datos_nuevos', 'Datos Nuevos:') !!}
    {!! Form::text('datos_nuevos', null, ['class' => 'form-control']) !!}
</div>

<!-- Operaciones Field -->
<div class="form-group col-sm-6">
    {!! Form::label('operaciones', 'Operaciones:') !!}
    {!! Form::text('operaciones', null, ['class' => 'form-control']) !!}
</div>

<!-- Tabla Field -->
<div class="form-group col-sm-12 col-lg-12">
    {!! Form::label('tabla', 'Tabla:') !!}
    {!! Form::textarea('tabla', null, ['class' => 'form-control']) !!}
</div>

<!-- Ip Field -->
<div class="form-group col-sm-6">
    {!! Form::label('ip', 'Ip:') !!}
    {!! Form::text('ip', null, ['class' => 'form-control']) !!}
</div>

<!-- Id Field -->
<div class="form-group col-sm-12 col-lg-12">
    {!! Form::label('id', 'Id:') !!}
    {!! Form::textarea('id', null, ['class' => 'form-control']) !!}
</div>

<!-- Fecha Field -->
<div class="form-group col-sm-6">
    {!! Form::label('fecha', 'Fecha:') !!}
    {!! Form::text('fecha', null, ['class' => 'form-control','id'=>'fecha']) !!}
</div>

@push('page_scripts')
    <script type="text/javascript">
        $('#fecha').datepicker()
    </script>
@endpush