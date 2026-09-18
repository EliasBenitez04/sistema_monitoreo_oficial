@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Articulos</h1>
                </div>
                @can('articulos create')
                    <div class="col-sm-6 d-flex justify-content-end">
                        <a class="btn btn-primary btn-sg" href="{{ route('articulos.create') }}" role="button">
                            <i></i> Nuevo
                        </a>
                    </div>
                @endcan
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('sweetalert::alert')

        <div class="clearfix"></div>

        <div class="card">
            @include('articulos.table')
        </div>
    </div>
@endsection
