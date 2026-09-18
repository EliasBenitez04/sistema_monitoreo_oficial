<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', 'Nombre y Apellido:') !!}
    {!! Form::text('name', null, ['class' => 'form-control', 'required' => 'required']) !!}
</div>

<!-- Email Field -->
<div class="form-group col-sm-6">
    {!! Form::label('email', 'Email:') !!}
    {!! Form::email('email', null, ['class' => 'form-control', 'required' => 'required']) !!}
</div>

<!-- Nro Documento Field -->
<div class="form-group col-sm-6">
    {!! Form::label('nro_documento', 'Nro Documento:') !!}
    {!! Form::text('nro_documento', null, ['class' => 'form-control', 'required' => 'required']) !!}
</div>
<!-- Celular Field -->
<div class="form-group col-sm-6">
    {!! Form::label('celular', 'Celular:') !!}
    {!! Form::text('celular', null, ['class' => 'form-control']) !!}
</div>

<!-- password Field -->
<div class="form-group col-sm-6">
    {!! Form::label('password', 'Password:') !!}
    {!! Form::password('password', ['class' => 'form-control', 'placeholder' => isset($user) ? 'Dejar vacio la contraseña si no se actualiza' : 'Ingresa una contraseña', 'required' => 'required']) !!}
</div>


<!-- Role Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('role_id', 'Roles:') !!}
    {!! Form::select('role_id', $roles, null,
    ['class' => 'form-control',
    'placeholder' => 'Seleccione',
    'required' => 'required'
    ]) !!}
</div>

<!-- Direccion Field -->
<div class="form-group col-sm-12 col-lg-12">
    {!! Form::label('direccion', 'Dirección:') !!}
    {!! Form::textarea('direccion', null, ['class' => 'form-control']) !!}
</div>


@push('page_scripts')
    <script type="text/javascript">

    </script>
@endpush
