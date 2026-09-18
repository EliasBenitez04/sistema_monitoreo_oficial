@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Departamentos</h1>
                </div>
                <div class="col-sm-6">
                    @can('departamentos create')
                        <a class="btn btn-primary float-right" href="{{ route('Departamentos.create') }}">
                            Nuevo
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('sweetalert::alert')

        <div class="clearfix"></div>

        <div class="card">
            @include('departamentos.table')
        </div>
    </div>
@endsection
