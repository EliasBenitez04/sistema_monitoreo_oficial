<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name') }}</title>

    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    {{-- CSRF Laravel --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- FontAwesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css"
        integrity="sha512-1PKOgIY59xJ8Co8+NE6FZ+LOAZKjy+KY8iq0G4B3CyeY6wYHN3yt9PW0XpSriVlkMXe40PTKnXrLnZ9+fkDaog=="
        crossorigin="anonymous" />

    {{-- App CSS --}}
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">

    {{-- Google Font --}}
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">

    {{-- Select2 CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

    @stack('third_party_stylesheets')
    @stack('page_css')

    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Roboto', sans-serif;
        }

        .select2-container .select2-selection--single {
            box-sizing: border-box;
            cursor: pointer;
            display: block;
            height: 38px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            padding: 4px 5px;
            width: 100%;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #3c8dbc;
            border-color: #367fa9;
            color: #fff;
            padding: 1px 10px;
        }

        .select2-container--default .select2-selection--single {
            background-color: #fff;
            border-radius: 3px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 35px;
            position: absolute;
            top: 1px;
            right: 1px;
            width: 20px;
        }

        .select2-selection__arrow {
            height: 42px !important;
        }
    </style>
</head>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('form.confirm-submit').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: '¿Desea Guardar Los Cambios?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, Guardar',
                    cancelButtonText: 'No, Cancelar',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        // Alertas desde sesión
        @if (Session::has('swal-alert'))
            let alertData = @json(Session::get('swal-alert'));
            Swal.fire({
                icon: alertData.icon,
                title: alertData.title,
                text: alertData.text,
                confirmButtonText: 'Aceptar'
            });
        @endif
    });
</script>

<body class="hold-transition sidebar-mini layout-navbar-not-fixed">
    <div class="wrapper">

        {{-- NAVBAR --}}
        <nav class="main-header navbar navbar-expand navbar-lightblue navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                        <img src="{{ asset('storage/logos/gts_logo.jpg') }}" class="img-circle elevation-2 mr-2"
                            alt="Logo" style="width:30px; height:30px; opacity: .9;">
                        <span class="d-none d-md-inline text-bold">{{ Auth::user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <li class="user-header bg-primary text-center">
                            <!-- Logo -->
                            <img src="{{ asset('storage/logos/gts_logo.jpg') }}" class="img-circle elevation-2 mb-2"
                                alt="Logo" style="width:60px; height:60px; opacity: .9;">
                            <!-- Nombre del usuario -->
                            <p>{{ Auth::user()->name }}</p>
                        </li>
                        <li class="user-footer">
                            <a href="{!! url('users/detail/perfil') !!}" class="btn btn-success btn-flat">Perfil</a>
                            <a href="#" class="btn btn-danger btn-flat float-right"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                Salir
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>

        {{-- SIDEBAR --}}
        @include('layouts.sidebar')

        {{-- CONTENT --}}
        <div class="content-wrapper">
            @yield('content')
        </div>

        {{-- FOOTER --}}
        {{-- <footer class="main-footer">
            <div class="float-right d-none d-sm-block">
                <b>Version</b> 3.0.5
            </div>
            <strong>
                Copyright &copy; 2014-2022
                <a href="https://adminlte.io">AdminLTE.io</a>.
            </strong>
            All rights reserved.
        </footer> --}}
    </div>

    {{-- ================== SCRIPTS ================== --}}

    {{-- App JS (incluye jQuery + Bootstrap + AdminLTE) --}}
    <script src="{{ mix('js/app.js') }}"></script>

    {{-- Select2 JS (DESPUÉS de jQuery) --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    {{-- SweetAlert --}}

    {{-- SweetAlert Confirm --}}
    <script>
        $('.alert-delete').click(function(event) {
            event.preventDefault();

            let form = $(this).closest("form");
            let valor = $(this).data("mensaje") || "Este Registro";
            let accion = $(this).data("accion") || "Borrar";

            Swal.fire({
                title: "Atención",
                text: `¿Desea ${accion} ${valor}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "Confirmar",
                cancelButtonText: "Cancelar",
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>
    <script>
        $('.alert-confirm').click(function(event) {
            event.preventDefault();

            let form = $(this).closest("form");
            let valor = $(this).data("mensaje") || "este pedido";
            let accion = $(this).data("accion") || "confirmar";

            Swal.fire({
                title: "Atención",
                text: `¿Desea ${accion} ${valor}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: "Confirmar",
                cancelButtonText: "Cancelar",
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>

    {{-- BUSCADOR AJAX --}}
    <script>
        $(document).ready(function() {
            $('.buscar').on('keyup', function() {
                let query = this.value;
                let url = this.getAttribute('data-url');

                fetch(url + '?buscar=' + encodeURIComponent(query), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.text())
                    .then(html => {
                        $('.tabla-container').html(html);
                    })
                    .catch(err => console.error(err));
            });
        });
    </script>
    {{-- BUSCADOR AJAX FOTOS --}}
    <script>
        $(document).ready(function() {
            $('#form-busqueda-fotos .buscar-fotos').on('keyup', function() {
                let query = this.value;
                let url = this.getAttribute('data-url');

                // AJAX usando Fetch
                fetch(url + '?search=' + encodeURIComponent(query), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.text())
                    .then(html => {
                        // Reemplaza la tabla de fotos
                        $('.tabla-fotos-container').html(html);
                    })
                    .catch(err => console.error(err));
            });
        });
    </script>


    {{-- STACKS --}}
    @stack('third_party_scripts')
    @stack('page_scripts')
    @include('sweetalert::alert')

    <script>
        $(".select2:visible").select2({
            placeholder: "Seleccione...",
            width: '100%',
            allowClear: true
        });
    </script>
</body>

</html>
