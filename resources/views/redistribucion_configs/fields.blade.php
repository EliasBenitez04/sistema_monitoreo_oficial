<!-- Metodo Demanda Field -->
<div class="form-group col-sm-6">
    {!! Form::label('metodo_demanda', 'Metodo Demanda:') !!}
    {!! Form::text('metodo_demanda', null, ['class' => 'form-control', 'required', 'maxlength' => 20, 'maxlength' => 20]) !!}
</div>

<!-- Porcentaje Demanda Field -->
<div class="form-group col-sm-6">
    {!! Form::label('porcentaje_demanda', 'Porcentaje Demanda:') !!}
    {!! Form::number('porcentaje_demanda', null, ['class' => 'form-control', 'required']) !!}
</div>

<!-- Stock Minimo Field -->
<div class="form-group col-sm-6">
    {!! Form::label('stock_minimo', 'Stock Minimo:') !!}
    {!! Form::number('stock_minimo', null, ['class' => 'form-control', 'required']) !!}
</div>

<!-- Stock Maximo Field -->
<div class="form-group col-sm-6">
    {!! Form::label('stock_maximo', 'Stock Maximo:') !!}
    {!! Form::number('stock_maximo', null, ['class' => 'form-control', 'required']) !!}
</div>

<!-- Venta Minima Field -->
<div class="form-group col-sm-6">
    {!! Form::label('venta_minima', 'Venta Minima:') !!}
    {!! Form::number('venta_minima', null, ['class' => 'form-control', 'required']) !!}
</div>

<!-- Porcentaje Necesidad Field -->
<div class="form-group col-sm-6">
    {!! Form::label('porcentaje_necesidad', 'Porcentaje Necesidad:') !!}
    {!! Form::number('porcentaje_necesidad', null, ['class' => 'form-control', 'required']) !!}
</div>

<!-- Porcentaje Conservar Origen Field -->
<div class="form-group col-sm-6">
    {!! Form::label('porcentaje_conservar_origen', 'Porcentaje Conservar Origen:') !!}
    {!! Form::number('porcentaje_conservar_origen', null, ['class' => 'form-control', 'required']) !!}
</div>

<!-- Cantidad Minima Field -->
<div class="form-group col-sm-6">
    {!! Form::label('cantidad_minima', 'Cantidad Minima:') !!}
    {!! Form::number('cantidad_minima', null, ['class' => 'form-control', 'required']) !!}
</div>

<!-- Cantidad Maxima Field -->
<div class="form-group col-sm-6">
    {!! Form::label('cantidad_maxima', 'Cantidad Maxima:') !!}
    {!! Form::number('cantidad_maxima', null, ['class' => 'form-control', 'required']) !!}
</div>

<!-- Dias Bloqueo Field -->
<div class="form-group col-sm-6">
    {!! Form::label('dias_bloqueo', 'Dias Bloqueo:') !!}
    {!! Form::number('dias_bloqueo', null, ['class' => 'form-control', 'required']) !!}
</div>

<!-- Bloquear Pendientes Field -->
<div class="form-group col-sm-6">
    <div class="form-check">
        {!! Form::hidden('bloquear_pendientes', 0, ['class' => 'form-check-input']) !!}
        {!! Form::checkbox('bloquear_pendientes', '1', null, ['class' => 'form-check-input']) !!}
        {!! Form::label('bloquear_pendientes', 'Bloquear Pendientes', ['class' => 'form-check-label']) !!}
    </div>
</div>

<!-- Bloquear En Proceso Field -->
<div class="form-group col-sm-6">
    <div class="form-check">
        {!! Form::hidden('bloquear_en_proceso', 0, ['class' => 'form-check-input']) !!}
        {!! Form::checkbox('bloquear_en_proceso', '1', null, ['class' => 'form-check-input']) !!}
        {!! Form::label('bloquear_en_proceso', 'Bloquear En Proceso', ['class' => 'form-check-label']) !!}
    </div>
</div>

<!-- Bloquear Finalizados Recientes Field -->
<div class="form-group col-sm-6">
    <div class="form-check">
        {!! Form::hidden('bloquear_finalizados_recientes', 0, ['class' => 'form-check-input']) !!}
        {!! Form::checkbox('bloquear_finalizados_recientes', '1', null, ['class' => 'form-check-input']) !!}
        {!! Form::label('bloquear_finalizados_recientes', 'Bloquear Finalizados Recientes', ['class' => 'form-check-label']) !!}
    </div>
</div>

<!-- Activo Field -->
<div class="form-group col-sm-6">
    <div class="form-check">
        {!! Form::hidden('activo', 0, ['class' => 'form-check-input']) !!}
        {!! Form::checkbox('activo', '1', null, ['class' => 'form-check-input']) !!}
        {!! Form::label('activo', 'Activo', ['class' => 'form-check-label']) !!}
    </div>
</div>