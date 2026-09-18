@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Ciudades</h1>
                </div>
                <div class="col-sm-6">
                    @can('ciudades create')
                        <a class="btn btn-primary float-right" href="{{ route('ciudades.create') }}">
                            Nuevo
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        {{-- @include('flash::message') --}}
        @include('sweetalert::alert')

        <div class="clearfix"></div>

        <div class="card">
            @include('ciudads.table')
        </div>
    </div>
@endsection
