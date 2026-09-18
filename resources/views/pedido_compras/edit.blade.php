@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1>
                        Editar Pedido Compras
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('adminlte-templates::common.errors')

        <div class="card">

            {!! Form::model($pedido_compras, [
                'route' => ['pedido_compras.update', $pedido_compras->id_pedido],
                'method' => 'patch',
                'id' => 'formPedido',
                'class' => 'confirm-submit',
            ]) !!}

            <div class="card-body">
                @include('sweetalert::alert')

                <div class="row">

                    {{-- 🔥 IMPORTANTE: PASAR DETALLE AL FORM --}}
                    @php
                        $pedido = $pedido_compras;
                        $detalles = $detalle ?? [];
                    @endphp

                    @include('pedido_compras.fields')

                </div>
            </div>

            <div class="card-footer">
                {!! Form::submit('Actualizar', ['class' => 'btn btn-success']) !!}
                <a href="{{ route('pedido_compras.index') }}" class="btn btn-primary"> Cancelar </a>
            </div>

            {!! Form::close() !!}

        </div>
    </div>
@endsection
