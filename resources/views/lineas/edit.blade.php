@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1>
                        Editar Linea
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('adminlte-templates::common.errors')

        <div class="card">

            {!! Form::model($linea, ['route' => ['lineas.update', $linea->linea_cod], 'method' => 'patch', 'data-confirm' => 'confirm-submit']) !!}

            <div class="card-body">
                @include('sweetalert::alert')
                <div class="row">
                    @include('lineas.fields')
                </div>
            </div>

            <div class="card-footer">
                {!! Form::submit('Actualizar', ['class' => 'btn btn-success']) !!}
                <a href="{{ route('lineas.index') }}" class="btn btn-primary"> Cancelar </a>
            </div>

            {!! Form::close() !!}

        </div>
    </div>
@endsection
