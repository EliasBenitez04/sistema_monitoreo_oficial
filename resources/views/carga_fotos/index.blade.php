@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Imagenes Cargadas</h1>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-primary float-right" href="{{ route('carga_fotos.create') }}">
                        Nuevo
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('sweetalert::alert')

        <form id="form-busqueda-fotos" method="GET" action="{{ route('carga_fotos.index') }}">
            <div class="input-group mb-3">
                <input type="text" class="form-control buscar-fotos" name="search"
                    value="{{ request()->get('search', '') }}" placeholder="Buscar por OT, descripción o línea..."
                    data-url="{{ route('carga_fotos.index') }}">
                <button class="btn btn-outline-secondary" type="submit">Buscar</button>
            </div>
        </form>

        <div id="tabla-fotos-container">
            @include('carga_fotos.table', ['fotos' => $fotos])
    </div>

    </div>
@endsection
@push('page_scripts')
    <script>
        $(document).ready(function() {
            $('.buscar-fotos').on('input', function() {
                let query = this.value;
                let url = this.dataset.url;

                fetch(url + '?search=' + encodeURIComponent(query), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.text())
                    .then(html => {
                        document.getElementById('tabla-fotos-container').innerHTML = html;
                    })
                    .catch(err => console.error(err));
            });
        });
    </script>
@endpush
