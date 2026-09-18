@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1>
                        Crear Nuevo Pedido
                    </h1>
                </div>
            </div>
        </div>
    </section>


    <div class="content px-3">

        @include('adminlte-templates::common.errors')

        <div class="card">

            {!! Form::open([
                'route' => 'pedido_compras.store',
                'class' => 'confirm-submit',
                'id' => 'form-pedido',
            ]) !!}

            <div class="card-body">

                @include('sweetalert::alert')

                <div class="row">

                    @include('pedido_compras.fields')

                </div>

            </div>


            <div class="card-footer">

                {!! Form::submit('Guardar', [
                    'class' => 'btn btn-success',
                ]) !!}

                <a href="{{ route('pedido_compras.index') }}" class="btn btn-primary">
                    Cancelar
                </a>

            </div>

            {!! Form::close() !!}


            {{-- =====================================================
                 MODAL NUEVO CLIENTE

                 IMPORTANTE:
                 Está FUERA del formulario del pedido
                 ===================================================== --}}

            @include('pedido_compras.modal_cliente')


        </div>

    </div>
@endsection
