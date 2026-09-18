<!-- Name Field -->
<div class="col-sm-12">
    {!! Form::label('name', 'Name:') !!}
    <p>{{ $user->name }}</p>
</div>

<!-- Email Field -->
<div class="col-sm-12">
    {!! Form::label('email', 'Email:') !!}
    <p>{{ $user->email }}</p>
</div>

<!-- Email Verified At Field -->
<div class="col-sm-12">
    {!! Form::label('email_verified_at', 'Email Verified At:') !!}
    <p>{{ $user->email_verified_at }}</p>
</div>

<!-- Password Field -->
<div class="col-sm-12">
    {!! Form::label('password', 'Password:') !!}
    <p>{{ $user->password }}</p>
</div>

<!-- Remember Token Field -->
<div class="col-sm-12">
    {!! Form::label('remember_token', 'Remember Token:') !!}
    <p>{{ $user->remember_token }}</p>
</div>

<!-- Direccion Field -->
<div class="col-sm-12">
    {!! Form::label('direccion', 'Direccion:') !!}
    <p>{{ $user->direccion }}</p>
</div>

<!-- Celular Field -->
<div class="col-sm-12">
    {!! Form::label('celular', 'Celular:') !!}
    <p>{{ $user->celular }}</p>
</div>

<!-- Nro Documento Field -->
<div class="col-sm-12">
    {!! Form::label('nro_documento', 'Nro Documento:') !!}
    <p>{{ $user->nro_documento }}</p>
</div>

<!-- Role Id Field -->
<div class="col-sm-12">
    {!! Form::label('role_id', 'Role Id:') !!}
    <p>{{ $user->role_id }}</p>
</div>

<!-- Delete At Field -->
<div class="col-sm-12">
    {!! Form::label('delete_at', 'Delete At:') !!}
    <p>{{ $user->delete_at }}</p>
</div>

