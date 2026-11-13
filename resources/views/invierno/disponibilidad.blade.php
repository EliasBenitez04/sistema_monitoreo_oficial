@extends('layouts.app')
@include('invierno._estilos')
@section('carga_liviana', '1')

@unless($vistaCompleta)
@push('page_head')
<script>
    const solicitarDisponibilidad = async (url) => {
        const solicitud = new AbortController();
        const plazo = window.setTimeout(() => solicitud.abort(), 30000);
        try {
            const respuesta = await fetch(url, { signal: solicitud.signal, cache: 'no-store' });
            if (respuesta.redirected && new URL(respuesta.url).pathname === @json(route('login', [], false))) {
                window.location.reload();
                return null;
            }
            return respuesta.ok ? await respuesta.text() : null;
        } catch {
            return null;
        } finally {
            window.clearTimeout(plazo);
        }
    };
    const cargaInicialDisponibilidad = solicitarDisponibilidad(@json(route('invierno.disponibilidad.datos', $filtros)));
</script>
@endpush

@push('page_css')
<noscript><style>[data-invierno-cargando] { display: none !important; }</style></noscript>
@endpush
@endunless

@section('content')
<section class="content invierno">
    <div class="container-fluid">
        <header class="iw-hero iw-split">
            <div>
                <div class="iw-eyebrow">PRENDAS DE INVIERNO · ACUMULADO DE REMISIONES</div>
                <h1><i class="fas fa-snowflake mr-2"></i>Disponibilidad de invierno</h1>
                <p>Reuní las prendas de todas las remisiones de invierno y ubicá qué grupos tienen más disponible en Central.</p>
            </div>
            <a class="btn btn-light" href="{{ route('invierno.disponibilidad.exportar', request()->except('page', 'completa')) }}"><i class="fas fa-file-excel mr-1"></i> Exportar Excel</a>
        </header>
        @include('invierno._navegacion')
        @if($vistaCompleta)
            @include('invierno._disponibilidad-datos')
        @else
            @php($urlCompleta = route('invierno.disponibilidad', $filtros + ['completa' => 1]))
            <div id="invierno-disponibilidad"
                 data-datos-url="{{ route('invierno.disponibilidad.datos', $filtros) }}"
                 aria-busy="true">
                <div class="card">
                    <div class="card-header"><h2 class="card-title mb-0">Acumulado por grupo</h2></div>
                    <div class="card-body d-flex align-items-center" data-invierno-cargando>
                        <span class="spinner-border spinner-border-sm text-primary mr-2" aria-hidden="true" data-invierno-espera></span>
                        <span role="status" data-invierno-estado>Cargando disponibilidad de invierno…</span>
                    </div>
                    <div class="card-footer bg-white d-none" data-invierno-error>
                        <button class="btn btn-primary btn-sm" type="button" data-invierno-reintento>Reintentar</button>
                        <a class="btn btn-outline-secondary btn-sm" href="{{ $urlCompleta }}">Abrir vista completa</a>
                    </div>
                    <noscript>
                        <div class="card-footer bg-white"><a href="{{ $urlCompleta }}">Abrir vista completa</a></div>
                    </noscript>
                </div>
            </div>
            <script>
                (() => {
                    const panel = document.getElementById('invierno-disponibilidad');
                    const estado = panel.querySelector('[data-invierno-estado]');
                    const espera = panel.querySelector('[data-invierno-espera]');
                    const error = panel.querySelector('[data-invierno-error]');
                    const reintentar = panel.querySelector('[data-invierno-reintento]');

                    async function cargar(solicitud, devolverFoco = false) {
                        panel.setAttribute('aria-busy', 'true');
                        estado.textContent = 'Cargando disponibilidad de invierno…';
                        espera.hidden = false;
                        error.classList.add('d-none');

                        const contenido = document.createElement('template');
                        contenido.innerHTML = (await solicitud) ?? '';
                        const resultados = contenido.content.querySelector('[data-invierno-resultados]');
                        if (resultados) {
                            panel.replaceChildren(resultados);
                            if (devolverFoco) resultados.focus({ preventScroll: true });
                            if (window.location.hash === '#invierno-grupos') {
                                panel.querySelector('#invierno-grupos')?.scrollIntoView();
                            }
                        } else {
                            estado.textContent = 'No se pudo cargar la disponibilidad. Podés reintentar o abrir la vista completa.';
                            espera.hidden = true;
                            error.classList.remove('d-none');
                            if (devolverFoco) reintentar.focus();
                        }
                        panel.setAttribute('aria-busy', 'false');
                    }

                    reintentar.addEventListener('click', () => cargar(solicitarDisponibilidad(panel.dataset.datosUrl), true));
                    cargar(cargaInicialDisponibilidad);
                })();
            </script>
        @endif

        <p class="iw-note mt-3">Se acumulan únicamente las remisiones registradas en invierno. Grupo: clasificación del maestro de códigos; si falta, se usa Grupo plan o la descripción de la remisión. Disponible es el saldo documental recibido en Central menos las salidas asociadas válidas, excluyendo documentos con diferencias. Se actualiza al importar remisiones o modificar asociaciones; no sustituye el conteo físico.</p>
    </div>
</section>
@endsection
