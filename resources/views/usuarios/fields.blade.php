<div class="row">

    <!-- Nombre -->
    <div class="form-group col-md-6">
        {!! Form::label('name', 'Nombre y Apellido', ['class' => 'font-weight-bold']) !!}
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="fas fa-user"></i>
                </span>
            </div>
            {!! Form::text('name', null, [
                'class' => 'form-control',
                'placeholder' => 'Ingrese nombre completo',
                'required',
            ]) !!}
        </div>
    </div>

    <!-- Username -->
    <div class="form-group col-md-6">
        {!! Form::label('email', 'Usuario / Email', ['class' => 'font-weight-bold']) !!}
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="fas fa-envelope"></i>
                </span>
            </div>
            {!! Form::text('email', null, [
                'class' => 'form-control',
                'placeholder' => 'usuario@empresa.com',
                'required',
            ]) !!}
        </div>
    </div>

    <!-- Password -->
    <div class="form-group col-md-6">
        {!! Form::label('password', 'Contraseña', ['class' => 'font-weight-bold']) !!}
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="fas fa-lock"></i>
                </span>
            </div>
            {!! Form::password('password', [
                'class' => 'form-control',
                'placeholder' => isset($usuario) ? 'Dejar vacío si no se modifica' : 'Ingrese una contraseña segura',
            ]) !!}
        </div>
        <small class="text-muted">
            Mínimo 6 caracteres
        </small>
    </div>

    <!-- CI -->
    <div class="form-group col-md-6">
        {!! Form::label('ci', 'Número de Cédula', ['class' => 'font-weight-bold']) !!}
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="fas fa-id-card"></i>
                </span>
            </div>
            {!! Form::text('ci', null, [
                'class' => 'form-control',
                'placeholder' => 'Ej: 1234567',
                'required',
            ]) !!}
        </div>
    </div>

    <!-- Dirección -->
    <div class="form-group col-md-12">
        {!! Form::label('direccion', 'Dirección', ['class' => 'font-weight-bold']) !!}
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="fas fa-map-marker-alt"></i>
                </span>
            </div>
            {!! Form::text('direccion', null, [
                'class' => 'form-control',
                'placeholder' => 'Dirección completa',
            ]) !!}
        </div>
    </div>

    <!-- Rol -->
    <div class="form-group col-md-6">
        {!! Form::label('role_id', 'Rol del Usuario', ['class' => 'font-weight-bold']) !!}
        {!! Form::select('role_id', $roles, null, [
            'class' => 'form-control select2',
            'placeholder' => 'Seleccione un rol',
            'required',
        ]) !!}
    </div>

    <!-- Sucursal -->
    <div class="form-group col-md-6">
        {!! Form::label('cod_suc', 'Sucursal', ['class' => 'font-weight-bold']) !!}
        {!! Form::select('cod_suc', $sucursal, null, [
            'class' => 'form-control select2',
            'placeholder' => 'Seleccione sucursal',
            'required',
        ]) !!}
    </div>

    <!-- Estado -->
    <div class="form-group col-md-6">
        {!! Form::label('estado', 'Estado', ['class' => 'font-weight-bold']) !!}
        {!! Form::select('estado', $estado, isset($usuario) ? $usuario->estado : null, [
            'class' => 'form-control',
            'required',
        ]) !!}
    </div>

    <!-- Teléfono -->
    <div class="form-group col-md-6">
        {!! Form::label('telefono', 'Teléfono', ['class' => 'font-weight-bold']) !!}
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="fas fa-phone"></i>
                </span>
            </div>
            {!! Form::text('telefono', null, [
                'class' => 'form-control',
                'placeholder' => 'Ej: 0981 123 456',
            ]) !!}
        </div>
    </div>

</div>
