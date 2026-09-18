@extends('layouts.app')

@section('content')

    <div class="container-fluid">

        <div class="card shadow-sm">

            {{-- ==========================================================
             CABECERA
        =========================================================== --}}

            <div class="card-header">

                <h4 class="mb-0">
                    <i class="fas fa-history"></i>
                    Historia General de Producción
                </h4>

            </div>


            <div class="card-body">

                {{-- ==========================================================
                 FILTROS
            =========================================================== --}}

                <form method="GET" action="{{ route('ots.historia-general') }}" class="row g-3 mb-4">

                    <div class="col-md-3">

                        <label for="fecha_desde" class="form-label">
                            Fecha desde
                        </label>

                        <input type="date" name="fecha_desde" id="fecha_desde" class="form-control"
                            value="{{ request('fecha_desde') }}">

                    </div>


                    <div class="col-md-3">

                        <label for="fecha_hasta" class="form-label">
                            Fecha hasta
                        </label>

                        <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control"
                            value="{{ request('fecha_hasta') }}">

                    </div>


                    <div class="col-md-4 d-flex align-items-end">

                        <button type="submit" class="btn btn-primary me-2">

                            <i class="fas fa-search"></i>
                            Consultar

                        </button>


                        <a href="{{ route('ots.historia-general') }}" class="btn btn-secondary">

                            <i class="fas fa-sync"></i>
                            Limpiar

                        </a>

                    </div>

                </form>


                {{-- ==========================================================
                 TABLA GENERAL
            =========================================================== --}}

                <div class="table-responsive">

                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-dark">

                            <tr>

                                <th style="width: 60px;"></th>

                                <th style="width: 60px;">
                                    #
                                </th>

                                <th>
                                    Proceso
                                </th>

                                <th class="text-center" style="width: 180px;">

                                    Entrada

                                </th>

                                <th class="text-center" style="width: 180px;">

                                    Salida

                                </th>

                                <th class="text-center" style="width: 130px;">

                                    OTs

                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($historia as $index => $item)
                                {{-- ==================================================
                                 FILA PRINCIPAL DEL PROCESO
                            =================================================== --}}

                                <tr>

                                    {{-- BOTÓN EXPANDIR --}}

                                    <td class="text-center">

                                        <button type="button" class="btn btn-sm btn-primary btn-toggle"
                                            data-target="detalle-{{ $index }}" aria-expanded="false">

                                            <i class="fas fa-plus"></i>

                                        </button>

                                    </td>


                                    {{-- NÚMERO --}}

                                    <td>

                                        <strong>
                                            {{ $index + 1 }}
                                        </strong>

                                    </td>


                                    {{-- PROCESO --}}

                                    <td>

                                        <strong>
                                            {{ $item['proceso'] }}
                                        </strong>

                                    </td>


                                    {{-- ENTRADA --}}

                                    <td class="text-center">

                                        <span class="badge bg-info text-dark fs-6">

                                            {{ number_format($item['entrada'], 0, ',', '.') }}

                                        </span>

                                    </td>


                                    {{-- SALIDA --}}

                                    <td class="text-center">

                                        <span class="badge bg-primary fs-6">

                                            {{ number_format($item['salida'], 0, ',', '.') }}

                                        </span>

                                    </td>


                                    {{-- CANTIDAD DE OTs --}}

                                    <td class="text-center">

                                        <span class="badge bg-secondary fs-6">

                                            {{ $item['cantidad_ots'] }}

                                        </span>

                                    </td>

                                </tr>


                                {{-- ==================================================
                                 DETALLE DEL PROCESO
                            =================================================== --}}

                                <tr id="detalle-{{ $index }}" class="detalle-row" style="display: none;">

                                    <td colspan="6" class="p-0">

                                        <div class="p-3 bg-light">

                                            {{-- TÍTULO --}}

                                            <div class="d-flex justify-content-between align-items-center mb-3">

                                                <h6 class="mb-0">

                                                    <i class="fas fa-list"></i>

                                                    Detalle de:

                                                    <strong>
                                                        {{ $item['proceso'] }}
                                                    </strong>

                                                </h6>


                                                <span class="badge bg-dark">

                                                    {{ $item['cantidad_ots'] }}
                                                    OTs

                                                </span>

                                            </div>


                                            {{-- ==================================================
                                             TABLA DE OTs
                                        =================================================== --}}

                                            <div class="table-responsive">

                                                <table class="table table-sm table-bordered table-hover mb-0">

                                                    <thead class="table-secondary">

                                                        <tr>

                                                            <th style="width: 50px;">
                                                                #
                                                            </th>

                                                            <th>
                                                                Fecha
                                                            </th>

                                                            <th>
                                                                N° OT
                                                            </th>

                                                            <th>
                                                                Código
                                                            </th>

                                                            <th>
                                                                Descripción
                                                            </th>

                                                            <th class="text-center">

                                                                Entrada

                                                            </th>

                                                            <th class="text-center">

                                                                Salida

                                                            </th>

                                                            <th>
                                                                Proceso anterior
                                                            </th>

                                                            <th>
                                                                Siguiente proceso
                                                            </th>

                                                        </tr>

                                                    </thead>


                                                    <tbody>

                                                        @foreach ($item['detalles'] as $detalleIndex => $detalle)
                                                            <tr>

                                                                {{-- # --}}

                                                                <td>

                                                                    {{ $detalleIndex + 1 }}

                                                                </td>


                                                                {{-- FECHA --}}

                                                                <td>

                                                                    {{ \Carbon\Carbon::parse($detalle['fecha'])->format('d/m/Y') }}

                                                                </td>


                                                                {{-- NÚMERO REAL DE OT --}}

                                                                <td>

                                                                    <strong>

                                                                        {{ $detalle['nro_ot'] }}

                                                                    </strong>

                                                                </td>


                                                                {{-- CÓDIGO --}}

                                                                <td>

                                                                    {{ $detalle['codigo'] }}

                                                                </td>


                                                                {{-- DESCRIPCIÓN --}}

                                                                <td>

                                                                    <small>

                                                                        {{ $detalle['descripcion'] }}

                                                                    </small>

                                                                </td>


                                                                {{-- ENTRADA --}}

                                                                <td class="text-center">

                                                                    @if ($detalle['entrada'] > 0)
                                                                        <span class="badge bg-info text-dark">

                                                                            {{ number_format($detalle['entrada'], 0, ',', '.') }}

                                                                        </span>
                                                                    @else
                                                                        <span class="text-muted">

                                                                            —

                                                                        </span>
                                                                    @endif

                                                                </td>


                                                                {{-- SALIDA --}}

                                                                <td class="text-center">

                                                                    @if ($detalle['salida'] > 0)
                                                                        <span class="badge bg-primary">

                                                                            {{ number_format($detalle['salida'], 0, ',', '.') }}

                                                                        </span>
                                                                    @else
                                                                        <span class="text-muted">

                                                                            —

                                                                        </span>
                                                                    @endif

                                                                </td>


                                                                {{-- PROCESO ANTERIOR --}}

                                                                <td>

                                                                    @if ($detalle['proceso_anterior'])
                                                                        <span class="badge bg-secondary">

                                                                            <i class="fas fa-arrow-left"></i>

                                                                            {{ $detalle['proceso_anterior'] }}

                                                                        </span>
                                                                    @else
                                                                        <span class="badge bg-dark">

                                                                            <i class="fas fa-play"></i>

                                                                            Inicio

                                                                        </span>
                                                                    @endif

                                                                </td>


                                                                {{-- SIGUIENTE PROCESO --}}

                                                                <td>

                                                                    @if ($detalle['es_ultimo'])
                                                                        <span class="badge bg-success">

                                                                            <i class="fas fa-flag-checkered"></i>

                                                                            FIN

                                                                        </span>
                                                                    @elseif($detalle['proceso_siguiente'])
                                                                        <span class="badge bg-primary">

                                                                            {{ $detalle['proceso_siguiente'] }}

                                                                            <i class="fas fa-arrow-right"></i>

                                                                        </span>
                                                                    @else
                                                                        <span class="text-muted">

                                                                            —

                                                                        </span>
                                                                    @endif

                                                                </td>

                                                            </tr>
                                                        @endforeach

                                                    </tbody>

                                                </table>

                                            </div>

                                        </div>

                                    </td>

                                </tr>


                            @empty

                                {{-- ==================================================
                                 SIN RESULTADOS
                            =================================================== --}}

                                <tr>

                                    <td colspan="6" class="text-center py-5">

                                        <i class="fas fa-info-circle fa-2x mb-3">
                                        </i>

                                        <br>

                                        <strong>
                                            No existen movimientos
                                        </strong>

                                        <br>

                                        <span class="text-muted">

                                            No existen movimientos para
                                            el período seleccionado.

                                        </span>

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>


    {{-- ==============================================================
     JAVASCRIPT PARA + / -
=============================================================== --}}

    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function() {

                const botones =
                    document.querySelectorAll('.btn-toggle');


                botones.forEach(
                    function(boton) {

                        boton.addEventListener(
                            'click',
                            function() {

                                const targetId =
                                    boton.getAttribute(
                                        'data-target'
                                    );


                                const filaDetalle =
                                    document.getElementById(
                                        targetId
                                    );


                                const icono =
                                    boton.querySelector('i');


                                /*
                                |--------------------------------------------------------------------------
                                | ABRIR
                                |--------------------------------------------------------------------------
                                */

                                if (
                                    filaDetalle.style.display ===
                                    'none'
                                ) {

                                    filaDetalle.style.display =
                                        'table-row';


                                    icono.classList.remove(
                                        'fa-plus'
                                    );

                                    icono.classList.add(
                                        'fa-minus'
                                    );


                                    boton.classList.remove(
                                        'btn-primary'
                                    );

                                    boton.classList.add(
                                        'btn-danger'
                                    );


                                    boton.setAttribute(
                                        'aria-expanded',
                                        'true'
                                    );

                                }

                                /*
                                |--------------------------------------------------------------------------
                                | CERRAR
                                |--------------------------------------------------------------------------
                                */
                                else {

                                    filaDetalle.style.display =
                                        'none';


                                    icono.classList.remove(
                                        'fa-minus'
                                    );

                                    icono.classList.add(
                                        'fa-plus'
                                    );


                                    boton.classList.remove(
                                        'btn-danger'
                                    );

                                    boton.classList.add(
                                        'btn-primary'
                                    );


                                    boton.setAttribute(
                                        'aria-expanded',
                                        'false'
                                    );

                                }

                            }
                        );

                    }
                );

            }
        );
    </script>

@endsection
