@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1>
                        Editar Articulos
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('adminlte-templates::common.errors')

        <div class="card">

            {!! Form::model($articulo, [
                'route' => ['articulos.update', $articulo->id_articulo],
                'method' => 'patch',
                'files' => true,
                'class' => 'confirm-submit'
            ]) !!}

            <div class="card-body">

                @include('sweetalert::alert')

                <div class="row">

                    @include('articulos.fields')

                </div>
            </div>

            <div class="card-footer">
                {!! Form::submit('Guardar', ['class' => 'btn btn-primary']) !!}
                <a href="{{ route('articulos.index') }}" class="btn btn-default"> Cancelar </a>
            </div>

            {!! Form::close() !!}

        </div>
    </div>
@endsection
