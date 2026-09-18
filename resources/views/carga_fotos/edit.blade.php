@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1>
                        Editar Carga De Fotos
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('adminlte-templates::common.errors')

        <div class="card">

            {!! Form::model($foto, [
                'route' => ['carga_fotos.update', $foto->fot_cod],
                'method' => 'patch',
                'files' => true,
                'data-edit' => 'true',
            ]) !!}

            <div class="card-body">

                @include('sweetalert::alert')

                <div class="row">

                    @include('carga_fotos.fields')

                </div>
            </div>

            <div class="card-footer">
                {!! Form::submit('Actualizar', ['class' => 'btn btn-success']) !!}
                <a href="{{ route('carga_fotos.index') }}" class="btn btn-primary"> Cancelar </a>
            </div>

            {!! Form::close() !!}

        </div>
    </div>
@endsection
