@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Usuarios</h1>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-primary float-right" href="{{ route('users.create') }}">
                        Nuevo Usuario
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('sweetalert::alert')

        <div class="clearfix"></div>

        <div class="card">
            @include('users.table')
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Usuarios</h1>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-primary float-right" href="{{ route('users.create') }}">
                        Nuevo Usuario
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('sweetalert::alert')

        <div class="clearfix">
            @includeIf('layouts.buscador')
        </div>

        <div class="card" id="tabla-container">
            @include('users.table')
        </div>
    </div>
@endsection

@push('page_scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            /** bucador mediante ajax*/
            $('#buscar').on('input', function() {
                var query = $(this).val(); //valor del input buscar
                $.ajax({
                    url: '{{ route('users.index') }}',
                    type: 'GET',
                    data: {
                        buscar: query
                    },
                    success: function(response) {
                        $('#tabla-container').empty(); //vaciar la tabla
                        $('#tabla-container').html(
                            response); //cargar devuelta el html tabla segun lo filtrado
                    }
                });
            });
        });
    </script>
@endpush
