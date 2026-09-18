<!-- Datos Antiguos Field -->
<div class="col-sm-12">
    {!! Form::label('datos_antiguos', 'Datos Antiguos:') !!}
    <p>{{ $auditoria->datos_antiguos }}</p>
</div>

<!-- Datos Nuevos Field -->
<div class="col-sm-12">
    {!! Form::label('datos_nuevos', 'Datos Nuevos:') !!}
    <p>{{ $auditoria->datos_nuevos }}</p>
</div>

<!-- Operaciones Field -->
<div class="col-sm-12">
    {!! Form::label('operaciones', 'Operaciones:') !!}
    <p>{{ $auditoria->operaciones }}</p>
</div>

<!-- Tabla Field -->
<div class="col-sm-12">
    {!! Form::label('tabla', 'Tabla:') !!}
    <p>{{ $auditoria->tabla }}</p>
</div>

<!-- Ip Field -->
<div class="col-sm-12">
    {!! Form::label('ip', 'Ip:') !!}
    <p>{{ $auditoria->ip }}</p>
</div>

<!-- Id Field -->
<div class="col-sm-12">
    {!! Form::label('id', 'Id:') !!}
    <p>{{ $auditoria->id }}</p>
</div>

<!-- Fecha Field -->
<div class="col-sm-12">
    {!! Form::label('fecha', 'Fecha:') !!}
    <p>{{ $auditoria->fecha }}</p>
</div>

