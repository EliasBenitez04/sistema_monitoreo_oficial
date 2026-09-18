@extends('layouts.app')
@section('content')
    <div class="container-fluid py-3">
        {{-- ========================================================= --}}
        {{-- HEADER --}}
        {{-- ========================================================= --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 font-weight-bold mb-1">
                    <i class="fas fa-truck-loading text-primary mr-2"></i>
                    Dashboard Logística
                </h1>
                <p class="text-muted mb-0">
                    Control y seguimiento de distribución de OTs
                </p>
            </div>
            <div>
                <a href="{{ route('dashboard.ot-logistica', request()->query()) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-sync-alt mr-1"></i>
                    Actualizar
                </a>
            </div>
        </div>
        {{-- ========================================================= --}}
        {{-- FILTROS --}}
        {{-- ========================================================= --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 font-weight-bold">
                            <i class="fas fa-filter text-primary mr-2"></i>
                            Filtros
                        </h5>
                        <small class="text-muted">
                            Filtrá la información del dashboard por fecha de proceso,
                            sucursal u OT.
                        </small>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <form method="GET" action="{{ route('dashboard.ot-logistica') }}">
                    <div class="row">
                        {{-- ================================================= --}}
                        {{-- FECHA DESDE --}}
                        {{-- ================================================= --}}
                        <div class="col-md-3 mb-3">
                            <label for="fecha_desde" class="font-weight-bold">
                                Fecha desde
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar-alt"></i>
                                    </span>
                                </div>
                                <input type="date" name="fecha_desde" id="fecha_desde" class="form-control"
                                    value="{{ request('fecha_desde') }}">
                            </div>
                            <small class="text-muted">
                                Fecha del proceso de trazabilidad
                            </small>
                        </div>
                        {{-- ================================================= --}}
                        {{-- FECHA HASTA --}}
                        {{-- ================================================= --}}
                        <div class="col-md-3 mb-3">
                            <label for="fecha_hasta" class="font-weight-bold">
                                Fecha hasta
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar-check"></i>
                                    </span>
                                </div>
                                <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control"
                                    value="{{ request('fecha_hasta') }}">
                            </div>
                            <small class="text-muted">
                                Fecha del proceso de trazabilidad
                            </small>
                        </div>
                        {{-- ================================================= --}}
                        {{-- SUCURSAL --}}
                        {{-- ================================================= --}}
                        <div class="col-md-3 mb-3">
                            <label for="sucursal" class="font-weight-bold">
                                <i class="fas fa-store text-primary mr-1"></i>
                                Sucursales
                            </label>
                            <select name="sucursal[]" id="sucursal" class="form-control select2" multiple
                                style="width: 100%;">
                                @foreach ($sucursales as $sucursal)
                                    <option value="{{ $sucursal }}"
                                        {{ in_array($sucursal, request('sucursal', [])) ? 'selected' : '' }}>
                                        {{ $sucursal }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        {{-- ================================================= --}}
                        {{-- OT --}}
                        {{-- ================================================= --}}
                        <div class="col-md-3 mb-3">
                            <label for="busqueda" class="font-weight-bold">
                                N° OT / Código
                            </label>
                            <input type="text" name="busqueda" id="busqueda" class="form-control"
                                placeholder="Ej: 30120 o 060617" value="{{ request('busqueda') }}">
                        </div>
                    </div>
                    @include('sweetalert::alert')
                    {{-- ================================================= --}}
                    {{-- BOTONES --}}
                    {{-- ================================================= --}}
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('dashboard.ot-logistica') }}" class="btn btn-light border mr-2">
                            <i class="fas fa-eraser mr-1"></i>
                            Limpiar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search mr-1"></i>
                            Aplicar filtros
                        </button>
                    </div>
                </form>
            </div>
        </div>
        {{-- ========================================================= --}}
        {{-- INDICADORES --}}
        {{-- ========================================================= --}}
        <div class="row">
            {{-- REGISTROS --}}
            {{-- <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card border-left-primary shadow-sm h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Registros
                                </div>
                                <div class="h3 mb-0 font-weight-bold">
                                    {{ number_format($totalRegistros, 0, ',', '.') }}
                                </div>
                                <small class="text-muted">
                                    Detalles registrados
                                </small>
                            </div>
                            <div class="col-auto">
                                <div class="icon-dashboard bg-primary">
                                    <i class="fas fa-list"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> --}}
            {{-- OTS --}}
            {{-- ==========================================================
     CANTIDAD ORDENADA
========================================================== --}}
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card border-left-info shadow-sm h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Cantidad Ordenada
                                </div>
                                <div class="h3 mb-0 font-weight-bold">
                                    {{ number_format($totalCantidadOrdenada, 0, ',', '.') }}
                                </div>
                                <small class="text-muted">
                                    Unidades de las OTs
                                </small>
                            </div>
                            <div class="col-auto">
                                <div class="icon-dashboard bg-info">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            {{-- ==========================================================
     CANTIDAD CORTADA
========================================================== --}}

            {{-- <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card border-left-primary shadow-sm h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Cantidad Cortada
                                </div>
                                <div class="h3 mb-0 font-weight-bold">
                                    {{ number_format($totalCantidadCortada, 0, ',', '.') }}
                                </div>
                                <small class="text-muted">
                                    Unidades cortadas
                                </small>
                            </div>
                            <div class="col-auto">
                                <div class="icon-dashboard bg-primary">
                                    <i class="fas fa-cut"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> --}}

            {{-- ==========================================================
     CANTIDAD ENVIADA
========================================================== --}}

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card border-left-success shadow-sm h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Cantidad Enviada
                                </div>
                                <div class="h3 mb-0 font-weight-bold">
                                    {{ number_format($totalCantidadEnviada, 0, ',', '.') }}
                                </div>
                                <small class="text-muted">
                                    Unidades enviadas
                                </small>
                            </div>
                            <div class="col-auto">
                                <div class="icon-dashboard bg-success">
                                    <i class="fas fa-truck"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-card border-left-warning shadow-sm h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Cantidad Pendiente
                                </div>

                                <div class="h3 mb-0 font-weight-bold">
                                    {{ number_format($totalCantidadOrdenada - $totalCantidadEnviada, 0, ',', '.') }}
                                </div>

                                <small class="text-muted">
                                    Unidades pendientes de envío
                                </small>
                            </div>

                            <div class="col-auto">
                                <div class="icon-dashboard bg-warning">
                                    <i class="fas fa-box-open"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @php
                $diferenciaOrdenadaCortada = $totalCantidadOrdenada - $totalCantidadCortada;
                $diferenciaCortadaEnviada = $totalCantidadCortada - $totalCantidadEnviada;
            @endphp
            {{-- ==========================================================
     DIFERENCIA ORDENADA - CORTADA
========================================================== --}}

            {{-- <div class="col-xl-3 col-md-6 mb-4">
                <div
                    class="card dashboard-card border-left-{{ $diferenciaOrdenadaCortada != 0 ? 'danger' : 'success' }} shadow-sm h-100">

                    <div class="card-body">

                        <div class="row align-items-center">

                            <div class="col">

                                <div
                                    class="text-xs font-weight-bold text-{{ $diferenciaOrdenadaCortada != 0 ? 'danger' : 'success' }} text-uppercase mb-1">
                                    Diferencia Ordenada - Cortada
                                </div>

                                <div
                                    class="h3 mb-0 font-weight-bold text-{{ $diferenciaOrdenadaCortada != 0 ? 'danger' : 'dark' }}">
                                    {{ number_format($diferenciaOrdenadaCortada, 0, ',', '.') }}
                                </div>

                                <small class="text-muted">
                                    {{ $diferenciaOrdenadaCortada != 0 ? 'Existe diferencia' : 'Sin diferencia' }}
                                </small>

                            </div>

                            <div class="col-auto">

                                <div
                                    class="icon-dashboard bg-{{ $diferenciaOrdenadaCortada != 0 ? 'danger' : 'success' }}">

                                    <i
                                        class="fas {{ $diferenciaOrdenadaCortada != 0 ? 'fa-exclamation-triangle' : 'fa-check' }}"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>
            </div> --}}


            {{-- ==========================================================
     DIFERENCIA CORTADA - ENVIADA
========================================================== --}}

            {{-- <div class="col-xl-3 col-md-6 mb-4">
                <div
                    class="card dashboard-card border-left-{{ $diferenciaCortadaEnviada != 0 ? 'danger' : 'success' }} shadow-sm h-100">

                    <div class="card-body">

                        <div class="row align-items-center">

                            <div class="col">

                                <div
                                    class="text-xs font-weight-bold text-{{ $diferenciaCortadaEnviada != 0 ? 'danger' : 'success' }} text-uppercase mb-1">
                                    Diferencia Cortada - Enviada
                                </div>

                                <div
                                    class="h3 mb-0 font-weight-bold text-{{ $diferenciaCortadaEnviada != 0 ? 'danger' : 'dark' }}">
                                    {{ number_format($diferenciaCortadaEnviada, 0, ',', '.') }}
                                </div>

                                <small class="text-muted">
                                    {{ $diferenciaCortadaEnviada != 0 ? 'Existe diferencia' : 'Sin diferencia' }}
                                </small>

                            </div>

                            <div class="col-auto">

                                <div
                                    class="icon-dashboard bg-{{ $diferenciaCortadaEnviada != 0 ? 'danger' : 'success' }}">

                                    <i
                                        class="fas {{ $diferenciaCortadaEnviada != 0 ? 'fa-exclamation-triangle' : 'fa-check' }}"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>
            </div> --}}
        </div>

        {{-- ========================================================= --}}
        {{-- INDICADORES DEL DÍA --}}
        {{-- ========================================================= --}}

        {{-- <div class="row"> --}}

        {{-- CANTIDAD HOY --}}

        {{-- <div class="col-md-4 mb-4">

                <div class="card border-0 shadow-sm">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>

                                <small class="text-muted">

                                    PROCESADO HOY

                                </small>

                                <h4 class="font-weight-bold mb-1">

                                    {{ number_format($cantidadHoy, 0, ',', '.') }}

                                </h4>

                                <span class="text-muted">

                                    unidades

                                </span>

                            </div>

                            <div class="icon-dashboard bg-dark">

                                <i class="fas fa-calendar-day"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div> --}}


        {{-- OTS HOY --}}

        {{-- <div class="col-md-4 mb-4">

                <div class="card border-0 shadow-sm">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>

                                <small class="text-muted">

                                    OTs PROCESADAS HOY

                                </small>

                                <h4 class="font-weight-bold mb-1">

                                    {{ number_format($otHoy, 0, ',', '.') }}

                                </h4>

                                <span class="text-muted">

                                    órdenes

                                </span>

                            </div>

                            <div class="icon-dashboard bg-secondary">

                                <i class="fas fa-clipboard-list"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div> --}}


        {{-- PROMEDIO --}}

        {{-- <div class="col-md-4 mb-4">

                <div class="card border-0 shadow-sm">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>

                                <small class="text-muted">

                                    PROMEDIO

                                </small>

                                <h4 class="font-weight-bold mb-1">

                                    {{ number_format($promedioCantidadOT, 2, ',', '.') }}

                                </h4>

                                <span class="text-muted">

                                    unidades / OT

                                </span>

                            </div>

                            <div class="icon-dashboard bg-primary">

                                <i class="fas fa-chart-bar"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div> --}}


        {{-- ========================================================= --}}
        {{-- RESUMEN POR FECHA DE PROCESO --}}
        {{-- ========================================================= --}}

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-header bg-white border-0">

                <h5 class="font-weight-bold mb-0">

                    <i class="fas fa-calendar-alt text-primary mr-2"></i>

                    Resumen por fecha de proceso

                </h5>

                <small class="text-muted">

                    La fecha corresponde a <strong>fecha_proceso</strong>
                    de la trazabilidad.

                </small>

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-hover mb-0">

                        <thead class="thead-light">

                            <tr>

                                <th>

                                    Fecha de proceso

                                </th>

                                <th class="text-center">

                                    OTs

                                </th>

                                <th class="text-center">

                                    Registros

                                </th>

                                <th class="text-center">

                                    Cantidad

                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($porFecha as $fecha)
                                <tr>

                                    <td class="font-weight-bold">

                                        <i class="fas fa-calendar-day text-primary mr-2"></i>

                                        {{ \Carbon\Carbon::parse($fecha->fecha_proceso)->format('d/m/Y') }}

                                    </td>

                                    <td class="text-center">

                                        <span class="badge badge-primary px-3 py-2">

                                            {{ number_format($fecha->total_ot, 0, ',', '.') }}

                                        </span>

                                    </td>

                                    <td class="text-center">

                                        {{ number_format($fecha->total_registros, 0, ',', '.') }}

                                    </td>

                                    <td class="text-center font-weight-bold">

                                        {{ number_format($fecha->total_cantidad, 0, ',', '.') }}

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="4" class="text-center text-muted py-4">

                                        No existen datos para los filtros seleccionados.

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- RESUMEN POR SUCURSAL --}}
        {{-- ========================================================= --}}

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-header bg-white border-0">

                <h5 class="font-weight-bold mb-0">

                    <i class="fas fa-store text-success mr-2"></i>

                    Distribución por sucursal

                </h5>

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-hover mb-0">

                        <thead class="thead-light">

                            <tr>

                                <th>

                                    Sucursal

                                </th>

                                <th class="text-center">

                                    OTs

                                </th>

                                <th class="text-center">

                                    Registros

                                </th>

                                <th class="text-center">

                                    Cantidad

                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($porSucursal as $sucursal)
                                <tr>

                                    <td class="font-weight-bold">

                                        <i class="fas fa-store text-muted mr-2"></i>

                                        {{ $sucursal->sucursal }}

                                    </td>

                                    <td class="text-center">

                                        {{ number_format($sucursal->total_ot, 0, ',', '.') }}

                                    </td>

                                    <td class="text-center">

                                        {{ number_format($sucursal->total_registros, 0, ',', '.') }}

                                    </td>

                                    <td class="text-center font-weight-bold">

                                        {{ number_format($sucursal->total_cantidad, 0, ',', '.') }}

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="4" class="text-center text-muted py-4">

                                        No existen datos.

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- DETALLE LOGÍSTICA --}}
        {{-- ========================================================= --}}

        <div class="card shadow-sm border-0">

            <div class="card-header bg-white border-0">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <h5 class="font-weight-bold mb-0">

                            <i class="fas fa-table text-primary mr-2"></i>

                            Detalle de logística

                        </h5>

                        <small class="text-muted">

                            Ordenado por fecha de proceso y posteriormente por OT.

                        </small>

                    </div>


                    <div class="d-flex align-items-center">

                        <span class="badge badge-primary mr-2 px-3 py-2">

                            {{ number_format($detalles->total(), 0, ',', '.') }}

                            registros

                        </span>


                        <a href="{{ route('dashboard.logistica.exportar', request()->query()) }}"
                            class="btn btn-outline-success">

                            <i class="fas fa-file-excel mr-1"></i>

                            Exportar

                        </a>

                    </div>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- TABLA --}}
            {{-- ===================================================== --}}

            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-hover table-striped mb-0">

                        <thead class="thead-dark">

                            <tr>

                                <th class="fecha-column">

                                    Fecha proceso

                                </th>

                                <th>

                                    OT

                                </th>

                                <th>

                                    Código

                                </th>

                                <th>

                                    Artículo

                                </th>

                                <th>

                                    Sucursal

                                </th>

                                <th class="text-center">

                                    Cantidad

                                </th>

                                <th>

                                    Proceso

                                </th>

                                <th>

                                    Resultado

                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($detalles as $detalle)
                                <tr>

                                    {{-- ================================================= --}}
                                    {{-- FECHA PROCESO --}}
                                    {{-- ================================================= --}}

                                    <td class="fecha-cell">

                                        @if ($detalle->fecha_proceso)
                                            <div class="fecha-principal">

                                                <i class="fas fa-calendar-day mr-1"></i>

                                                {{ \Carbon\Carbon::parse($detalle->fecha_proceso)->format('d/m/Y') }}

                                            </div>

                                            <small class="text-muted">

                                                {{ \Carbon\Carbon::parse($detalle->fecha_proceso)->locale('es')->translatedFormat('l') }}

                                            </small>
                                        @else
                                            <span class="text-muted">

                                                Sin fecha

                                            </span>
                                        @endif

                                    </td>


                                    {{-- ================================================= --}}
                                    {{-- OT --}}
                                    {{-- ================================================= --}}

                                    <td>

                                        <span class="badge badge-dark ot-badge">

                                            {{ $detalle->nro_ot ?? $detalle->id_ot }}

                                        </span>

                                    </td>


                                    {{-- ================================================= --}}
                                    {{-- CODIGO --}}
                                    {{-- ================================================= --}}

                                    <td>

                                        <span class="codigo-text">

                                            {{ $detalle->codigo ?? '-' }}

                                        </span>

                                    </td>


                                    {{-- ================================================= --}}
                                    {{-- ARTICULO --}}
                                    {{-- ================================================= --}}

                                    <td class="articulo-column">

                                        {{ $detalle->descripcion ?? '-' }}

                                    </td>


                                    {{-- ================================================= --}}
                                    {{-- SUCURSAL --}}
                                    {{-- ================================================= --}}

                                    <td>

                                        <span class="badge badge-light border sucursal-badge">

                                            <i class="fas fa-store mr-1"></i>

                                            {{ $detalle->sucursal ?? '-' }}

                                        </span>

                                    </td>


                                    {{-- ================================================= --}}
                                    {{-- CANTIDAD --}}
                                    {{-- ================================================= --}}

                                    <td class="text-center cantidad-cell">

                                        <strong>

                                            {{ number_format($detalle->cantidad ?? 0, 0, ',', '.') }}

                                        </strong>

                                    </td>


                                    {{-- ================================================= --}}
                                    {{-- PROCESO --}}
                                    {{-- ================================================= --}}

                                    <td>

                                        @if ($detalle->proceso)
                                            <span class="proceso-badge">

                                                {{ $detalle->proceso }}

                                            </span>
                                        @else
                                            <span class="text-muted">

                                                -

                                            </span>
                                        @endif

                                    </td>


                                    {{-- ================================================= --}}
                                    {{-- RESULTADO --}}
                                    {{-- ================================================= --}}

                                    <td>

                                        @if ($detalle->resultado !== null)
                                            @php

                                                $resultado = strtoupper(trim((string) $detalle->resultado));

                                            @endphp


                                            @if (str_contains($resultado, 'ERROR') || str_contains($resultado, 'RECHAZ'))
                                                <span class="badge badge-danger">

                                                    <i class="fas fa-times-circle mr-1"></i>

                                                    {{ $detalle->resultado }}

                                                </span>
                                            @elseif (str_contains($resultado, 'PENDIENT'))
                                                <span class="badge badge-warning">

                                                    <i class="fas fa-clock mr-1"></i>

                                                    {{ $detalle->resultado }}

                                                </span>
                                            @elseif (str_contains($resultado, 'COMPLET') || str_contains($resultado, 'OK') || str_contains($resultado, 'EXIT'))
                                                <span class="badge badge-success">

                                                    <i class="fas fa-check-circle mr-1"></i>

                                                    {{ $detalle->resultado }}

                                                </span>
                                            @else
                                                <span class="badge badge-secondary">

                                                    {{ $detalle->resultado }}

                                                </span>
                                            @endif
                                        @else
                                            <span class="text-muted">

                                                -

                                            </span>
                                        @endif

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="8" class="text-center py-5">

                                        <i class="fas fa-search fa-2x text-muted mb-3"></i>

                                        <h5>

                                            No se encontraron registros

                                        </h5>

                                        <p class="text-muted mb-0">

                                            Probá cambiando los filtros.

                                        </p>

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- PAGINACIÓN --}}
            {{-- ===================================================== --}}

            @if ($detalles->hasPages())
                <div class="card-footer bg-white">

                    <div class="d-flex justify-content-between align-items-center">

                        <small class="text-muted">

                            Mostrando

                            <strong>

                                {{ $detalles->firstItem() }}

                            </strong>

                            -

                            <strong>

                                {{ $detalles->lastItem() }}

                            </strong>

                            de

                            <strong>

                                {{ $detalles->total() }}

                            </strong>

                        </small>


                        <div>

                            {{ $detalles->appends(request()->query())->links() }}

                        </div>

                    </div>

                </div>
            @endif

        </div>


        {{-- ========================================================= --}}
        {{-- CSS --}}
        {{-- ========================================================= --}}

        <style>
            .dashboard-card {

                border-left-width: 4px !important;

                border-radius: 10px;

                transition: all .2s ease;

            }


            .dashboard-card:hover {

                transform: translateY(-3px);

                box-shadow: 0 8px 20px rgba(0, 0, 0, .10) !important;

            }


            .border-left-primary {

                border-left-color: #4e73df !important;

            }


            .border-left-success {

                border-left-color: #1cc88a !important;

            }


            .border-left-warning {

                border-left-color: #f6c23e !important;

            }


            .border-left-info {

                border-left-color: #36b9cc !important;

            }


            .icon-dashboard {

                width: 48px;

                height: 48px;

                border-radius: 12px;

                display: flex;

                align-items: center;

                justify-content: center;

                color: white;

                font-size: 20px;

            }


            .bg-primary {

                background: #4e73df !important;

            }


            .bg-success {

                background: #1cc88a !important;

            }


            .bg-warning {

                background: #f6c23e !important;

            }


            .bg-info {

                background: #36b9cc !important;

            }


            .card {

                border-radius: 10px;

            }


            .table {

                font-size: 14px;

            }


            .table th {

                white-space: nowrap;

                vertical-align: middle;

            }


            .table td {

                vertical-align: middle;

            }


            /* =========================================================
                                                           FECHA
                                                           ========================================================= */

            .fecha-column {

                min-width: 145px;

            }


            .fecha-cell {

                min-width: 145px;

                background: #f8f9fc;

                border-right: 1px solid #e3e6f0;

            }


            .fecha-principal {

                font-weight: 700;

                font-size: 14px;

                color: #2c3e50;

            }


            .fecha-principal i {

                color: #4e73df;

            }


            .fecha-cell small {

                text-transform: capitalize;

                font-size: 11px;

            }


            /* =========================================================
                                                           OT
                                                           ========================================================= */

            .ot-badge {

                font-size: 13px;

                padding: 6px 9px;

            }


            /* =========================================================
                                                           CODIGO
                                                           ========================================================= */

            .codigo-text {

                font-family: monospace;

                font-size: 13px;

            }


            /* =========================================================
                                                           ARTICULO
                                                           ========================================================= */

            .articulo-column {

                min-width: 250px;

                max-width: 350px;

            }


            /* =========================================================
                                                           SUCURSAL
                                                           ========================================================= */

            .sucursal-badge {

                font-size: 12px;

                padding: 6px 8px;

            }


            /* =========================================================
                                                           CANTIDAD
                                                           ========================================================= */

            .cantidad-cell {

                font-size: 15px;

                min-width: 90px;

            }


            /* =========================================================
                                                           PROCESO
                                                           ========================================================= */

            .proceso-badge {

                display: inline-block;

                padding: 5px 8px;

                border-radius: 5px;

                background: #f1f3f5;

                border: 1px solid #dee2e6;

                font-size: 12px;

                font-weight: 500;

            }


            /* =========================================================
                                                           TABLA
                                                           ========================================================= */

            .table-striped tbody tr:nth-of-type(odd) {

                background-color: #fafbfc;

            }


            .table-hover tbody tr:hover {

                background-color: #eef4ff;

            }


            /* =========================================================
                                                           BADGES
                                                           ========================================================= */

            .badge {

                font-weight: 500;

            }


            /* =========================================================
                                                           SELECT2
                                                           ========================================================= */

            .select2-container {

                width: 100% !important;

            }


            .select2-container .select2-selection--single {

                height: 38px !important;

                border: 1px solid #ced4da !important;

                border-radius: .25rem !important;

            }


            .select2-container--default .select2-selection--single .select2-selection__rendered {

                line-height: 36px !important;

                padding-left: 12px !important;

                color: #495057 !important;

            }


            .select2-container--default .select2-selection--single .select2-selection__arrow {

                height: 36px !important;

                right: 5px !important;

            }
        </style>


        {{-- ========================================================= --}}
        {{-- SELECT2 --}}
        {{-- ========================================================= --}}

        <script>
            $(document).ready(function() {

                if ($.fn.select2) {

                    $('#sucursal').select2({

                        placeholder: 'Todas las sucursales',

                        allowClear: true,

                        width: '100%'

                    });

                }

            });
        </script>

    </div>

@endsection
