@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="text-primary font-weight-bold">
                        <i class="fas fa-shopping-cart"></i> Nro Pedido: {{ $pedido->nro_pedido }}
                    </h1>
                </div>
                <div class="col-sm-6 text-right">
                    <a class="btn btn-outline-primary btn-lg shadow-sm" href="{{ route('pedido_compras.index') }}"
                        style="border-radius: 30px;">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="row">
                    @include('pedido_compras.show_fields')
                </div>
            </div>
            <div class="card-footer text-right">
                <a href="{{ route('pedido_compras.index') }}" class="btn btn-secondary">
                    <i class="fa fa-list"></i> Ver Todos Los Pedidos
                </a>
            </div>
        </div>
    </div>
@endsection
