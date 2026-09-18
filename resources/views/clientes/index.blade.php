@extends('layouts.app')

@section('content')
    {{-- Encabezado de la página --}}
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">

                {{-- Título --}}
                <div class="col-sm-6">
                    <h1>
                        <i class="fas fa-users"></i>
                        Listado Clientes
                    </h1>
                </div>

                {{-- Breadcrumb --}}
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">

                        <li class="breadcrumb-item">
                            <a href="{{ url('/home') }}">
                                <i class="fas fa-home"></i>
                                Inicio
                            </a>
                        </li>

                        <li class="breadcrumb-item active">
                            Clientes
                        </li>

                    </ol>
                </div>

            </div>
        </div>
    </section>


    {{-- Contenido --}}
    <div class="content px-3">

        {{-- Alertas --}}
        @include('sweetalert::alert')

        <div class="clearfix"></div>


        {{-- Card de clientes --}}
        <div class="card">

            {{-- Encabezado de la card --}}
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i>
                    Clientes registrados
                </h3>

                <div class="card-tools">
                    <a href="{{ route('clientes.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i>
                        Agregar Nuevo
                    </a>
                </div>
            </div>

            {{-- Tabla --}}
            <div class="card-body p-0">
                @include('clientes.table')
            </div>

        </div>

    </div>
@endsection
