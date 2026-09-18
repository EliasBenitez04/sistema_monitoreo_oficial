@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1>
                        Editar Permiso
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">
        
        @include('adminlte-templates::common.errors')
        @include('sweetalert::alert')
        
        <div class="card">

            {!! Form::model($permissions, ['route' => ['permissions.update', $permissions->id], 'method' => 'patch']) !!}


            <div class="card-body">
                <div class="row">
                    @include('permissions.fields')
                </div>
            </div>

            <div class="card-footer">
                {!! Form::submit('Actualizar', ['class' => 'btn btn-success']) !!}
                <a href="{{ route('permissions.index') }}" class="btn btn-primary"> Cancelar </a>
            </div>

            {!! Form::close() !!}

        </div>
    </div>
@endsection
