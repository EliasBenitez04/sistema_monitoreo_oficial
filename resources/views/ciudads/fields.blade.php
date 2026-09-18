<!-- Ciu Descripcion Field -->
<div class="form-group col-sm-12">
    {!! Form::label('ciu_descripcion', 'Ciudad:') !!}
    {!! Form::text('ciu_descripcion', null, [
        'class' => 'form-control',
        'required',
        'maxlength' => 45,
    ]) !!}
</div>

