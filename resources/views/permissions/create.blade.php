@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1>Crear Permisos</h1>
                </div>
            </div>
        </div>
    </section>

    @include('sweetalert::alert')

    <div class="content px-3">
        @include('adminlte-templates::common.errors')

        <div class="card">
            {!! Form::open(['route' => 'permissions.store','class' => 'confirm-submit']) !!}
            <div class="card-body">
                <div class="row">
                    @include('permissions.fields')
                </div>
            </div>
            <div class="card-footer">
                {!! Form::submit('Guardar', ['class' => 'btn btn-success']) !!}
                <a href="{{ route('permissions.index') }}" class="btn btn-primary">Cancelar</a>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
@endsection
