@extends('layouts.app')
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Auditorias</h1>
                </div>
            </div>
        </div>
    </section>
    <div class="content px-3">
        @include('flash::message')
        <div class="clearfix"></div>
        <div class="card">
            @if ($auditorias->isEmpty())
                <div class="alert alert-dark text-center m-3">
                    No Se Encontraron Resultados
                </div>
            @else
                @include('auditorias.table')
            @endif
        </div>
    </div>
@endsection
