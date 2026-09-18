@extends('layouts.app')

@section('content')

<div class="container-fluid">

    {{-- =========================================================
         HEADER
    ========================================================== --}}

    <div class="dashboard-header mb-4">

        <div>
            <div class="d-flex align-items-center mb-2">

                <div class="header-icon mr-3">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>

                <div>
                    <h1 class="mb-0 font-weight-bold">
                        OT Atrasadas
                    </h1>

                    <p class="text-muted mb-0">
                        Órdenes de trabajo sin movimiento durante
                        <strong>{{ $diasAlerta }} días o más</strong>
                    </p>
                </div>

            </div>

            <div>
                <span class="badge badge-light border">
                    <i class="fas fa-shield-alt mr-1 text-success"></i>
                    OT POSTERGADAS excluidas
                </span>
            </div>
        </div>

        <div>
            <a href="{{ route('dashboard.ot') }}"
               class="btn btn-primary shadow-sm">

                <i class="fas fa-search mr-1"></i>
                Buscar OT

            </a>
        </div>

    </div>


    {{-- =========================================================
         KPIs
    ========================================================== --}}

    <div class="row mb-4">

        {{-- TOTAL --}}
        <div class="col-xl-3 col-md-6 mb-3">

            <div class="kpi-card kpi-danger h-100">

                <div class="kpi-content">

                    <div>
                        <div class="kpi-label">
                            OT Atrasadas
                        </div>

                        <div class="kpi-value"
                             id="totalOTDashboard">

                            {{ $totalAtrasadas }}

                        </div>

                        <div class="kpi-description">
                            {{ $diasAlerta }} días o más
                        </div>
                    </div>

                    <div class="kpi-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>

                </div>

            </div>

        </div>


        {{-- PROMEDIO --}}
        <div class="col-xl-3 col-md-6 mb-3">

            <div class="kpi-card kpi-warning h-100">

                <div class="kpi-content">

                    <div>

                        <div class="kpi-label">
                            Promedio de atraso
                        </div>

                        <div class="kpi-value">

                            {{ number_format(
                                $promedioDiasAtraso,
                                1,
                                ',',
                                '.'
                            ) }}

                        </div>

                        <div class="kpi-description">
                            días sin movimiento
                        </div>

                    </div>

                    <div class="kpi-icon">
                        <i class="fas fa-clock"></i>
                    </div>

                </div>

            </div>

        </div>


        {{-- MAYOR ATRASO --}}
        <div class="col-xl-3 col-md-6 mb-3">

            <div class="kpi-card kpi-dark h-100">

                <div class="kpi-content">

                    <div>

                        <div class="kpi-label">
                            Mayor atraso
                        </div>

                        <div class="kpi-value">

                            {{ number_format(
                                $mayorAtraso,
                                1,
                                ',',
                                '.'
                            ) }}

                        </div>

                        <div class="kpi-description">
                            días sin movimiento
                        </div>

                    </div>

                    <div class="kpi-icon">
                        <i class="fas fa-hourglass-end"></i>
                    </div>

                </div>

            </div>

        </div>


        {{-- PROCESOS --}}
        <div class="col-xl-3 col-md-6 mb-3">

            <div class="kpi-card kpi-primary h-100">

                <div class="kpi-content">

                    <div>

                        <div class="kpi-label">
                            Procesos afectados
                        </div>

                        <div class="kpi-value">

                            {{ $otsPorProceso->count() }}

                        </div>

                        <div class="kpi-description">
                            procesos con OT detenidas
                        </div>

                    </div>

                    <div class="kpi-icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         NIVELES DE ATRASO
    ========================================================== --}}

    @php

        $criticas = $otsAtrasadas->filter(function ($item) {
            return (float) $item['dias_sin_movimiento'] >= 90;
        })->count();

        $graves = $otsAtrasadas->filter(function ($item) {
            return (float) $item['dias_sin_movimiento'] >= 60
                && (float) $item['dias_sin_movimiento'] < 90;
        })->count();

        $riesgo = $otsAtrasadas->filter(function ($item) {
            return (float) $item['dias_sin_movimiento'] >= 30
                && (float) $item['dias_sin_movimiento'] < 60;
        })->count();

    @endphp


    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white border-bottom-0 pt-4 px-4">

            <div>
                <h5 class="mb-1 font-weight-bold">

                    <i class="fas fa-chart-pie text-danger mr-2"></i>

                    Nivel de atraso

                </h5>

                <small class="text-muted">
                    Clasificación de las OT según los días sin movimiento
                </small>
            </div>

        </div>


        <div class="card-body px-4">

            <div class="row">

                {{-- CRÍTICAS --}}
                <div class="col-lg-4 mb-3">

                    <div
                        class="severity-card severity-critical"
                        data-severity="critica">

                        <div class="severity-icon">
                            <i class="fas fa-fire"></i>
                        </div>

                        <div class="flex-grow-1">

                            <div class="severity-title">
                                Críticas
                            </div>

                            <div class="severity-number">
                                {{ $criticas }}
                            </div>

                            <div class="severity-description">
                                90 días o más
                            </div>

                        </div>

                        <div class="severity-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>

                    </div>

                </div>


                {{-- GRAVES --}}
                <div class="col-lg-4 mb-3">

                    <div
                        class="severity-card severity-serious"
                        data-severity="grave">

                        <div class="severity-icon">
                            <i class="fas fa-exclamation"></i>
                        </div>

                        <div class="flex-grow-1">

                            <div class="severity-title">
                                Graves
                            </div>

                            <div class="severity-number">
                                {{ $graves }}
                            </div>

                            <div class="severity-description">
                                60 a 89 días
                            </div>

                        </div>

                        <div class="severity-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>

                    </div>

                </div>


                {{-- RIESGO --}}
                <div class="col-lg-4 mb-3">

                    <div
                        class="severity-card severity-warning"
                        data-severity="riesgo">

                        <div class="severity-icon">
                            <i class="fas fa-clock"></i>
                        </div>

                        <div class="flex-grow-1">

                            <div class="severity-title">
                                En riesgo
                            </div>

                            <div class="severity-number">
                                {{ $riesgo }}
                            </div>

                            <div class="severity-description">
                                30 a 59 días
                            </div>

                        </div>

                        <div class="severity-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         PROCESOS
    ========================================================== --}}

    @if($otsPorProceso->isNotEmpty())

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white border-bottom-0 pt-4 px-4">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h5 class="mb-1 font-weight-bold">

                        <i class="fas fa-project-diagram text-primary mr-2"></i>

                        OT atrasadas por proceso

                    </h5>

                    <small class="text-muted">

                        Haga clic sobre un proceso para filtrar las OT

                    </small>

                </div>

                <span class="badge badge-primary px-3 py-2">

                    {{ $otsPorProceso->count() }}

                    {{ $otsPorProceso->count() == 1
                        ? 'proceso'
                        : 'procesos' }}

                </span>

            </div>

        </div>


        <div class="card-body px-4">

            <div class="row">

                @foreach($detalleProcesos as $detalle)

                    <div class="col-xl-3 col-lg-4 col-md-6 mb-3">

                        <div
                            class="process-card"
                            data-process="{{ trim($detalle['proceso']) }}">

                            <div class="d-flex justify-content-between">

                                <div class="process-icon">

                                    <i class="fas fa-exclamation-triangle"></i>

                                </div>

                                <span class="process-filter">
                                    <i class="fas fa-filter"></i>
                                </span>

                            </div>


                            <div class="process-name mt-3">

                                {{ $detalle['proceso'] }}

                            </div>


                            <div class="process-number">

                                {{ $detalle['cantidad'] }}

                            </div>


                            <div class="small text-muted">

                                {{ $detalle['cantidad'] == 1
                                    ? 'OT atrasada'
                                    : 'OT atrasadas' }}

                            </div>


                            <div class="mt-3">

                                <div class="d-flex justify-content-between mb-1">

                                    <small class="text-muted">
                                        Participación
                                    </small>

                                    <small class="font-weight-bold">

                                        {{ number_format(
                                            $detalle['porcentaje'],
                                            1,
                                            ',',
                                            '.'
                                        ) }}%

                                    </small>

                                </div>


                                <div class="progress">

                                    <div
                                        class="progress-bar bg-danger"
                                        style="width: {{ min(
                                            $detalle['porcentaje'],
                                            100
                                        ) }}%;">
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                @endforeach

            </div>

        </div>

    </div>

    @endif


    {{-- =========================================================
         SIN OT
    ========================================================== --}}

    @if($otsAtrasadas->isEmpty())

        <div class="empty-state">

            <i class="fas fa-check-circle"></i>

            <h4>
                No hay OT atrasadas
            </h4>

            <p>
                Actualmente no existen órdenes de trabajo con
                {{ $diasAlerta }} días o más sin movimiento.
            </p>

        </div>

    @else


    {{-- =========================================================
         LISTADO
    ========================================================== --}}

    <div class="card shadow-sm border-0">

        {{-- HEADER LISTADO --}}

        <div class="card-header bg-white pt-4 px-4">

            <div class="row align-items-end">

                {{-- TITULO --}}

                <div class="col-xl-3 col-lg-12 mb-3">

                    <h5 class="mb-1 font-weight-bold">

                        <i class="fas fa-list text-danger mr-2"></i>

                        Órdenes atrasadas

                    </h5>

                    <small class="text-muted">

                        Mostrando

                        <strong
                            id="contadorOTFiltradas"
                            class="text-primary">

                            {{ $totalAtrasadas }}

                        </strong>

                        de

                        <strong>
                            {{ $totalAtrasadas }}
                        </strong>

                    </small>

                </div>


                {{-- BUSCADOR --}}

                <div class="col-xl-3 col-lg-4 mb-3">

                    <label class="filter-label">

                        <i class="fas fa-search text-primary mr-1"></i>

                        Buscar

                    </label>

                    <div class="input-group">

                        <input
                            type="text"
                            id="buscarOT"
                            class="form-control"
                            placeholder="OT, código o descripción...">

                        <div class="input-group-append">

                            <span class="input-group-text bg-white">

                                <i class="fas fa-search text-muted"></i>

                            </span>

                        </div>

                    </div>

                </div>


                {{-- PROCESO --}}

                <div class="col-xl-2 col-lg-4 mb-3">

                    <label
                        for="filtroProceso"
                        class="filter-label">

                        <i class="fas fa-project-diagram text-primary mr-1"></i>

                        Proceso

                    </label>

                    <select
                        id="filtroProceso"
                        class="form-control">

                        <option value="">
                            Todos
                        </option>

                        @foreach($detalleProcesos as $detalle)

                            <option value="{{ trim($detalle['proceso']) }}">

                                {{ $detalle['proceso'] }}

                                ({{ $detalle['cantidad'] }})

                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- NIVEL --}}

                <div class="col-xl-2 col-lg-4 mb-3">

                    <label
                        for="filtroNivel"
                        class="filter-label">

                        <i class="fas fa-layer-group text-primary mr-1"></i>

                        Nivel

                    </label>

                    <select
                        id="filtroNivel"
                        class="form-control">

                        <option value="">
                            Todos
                        </option>

                        <option value="critica">
                            🔴 Críticas (90+)
                        </option>

                        <option value="grave">
                            🟠 Graves (60-89)
                        </option>

                        <option value="riesgo">
                            🟡 En riesgo (30-59)
                        </option>

                    </select>

                </div>


                {{-- LIMPIAR --}}

                <div class="col-xl-2 col-lg-4 mb-3">

                    <label class="filter-label">
                        &nbsp;
                    </label>

                    <button
                        type="button"
                        id="btnLimpiarFiltro"
                        class="btn btn-outline-secondary btn-block">

                        <i class="fas fa-sync-alt mr-1"></i>

                        Limpiar

                    </button>

                </div>

            </div>

        </div>


        {{-- FILTRO ACTIVO --}}

        <div
            id="mensajeFiltro"
            class="active-filter d-none">

            <div>

                <i class="fas fa-filter text-primary mr-2"></i>

                Filtros activos:

                <strong id="descripcionFiltros">
                </strong>

            </div>

            <button
                type="button"
                id="quitarFiltro"
                class="btn btn-sm btn-light">

                <i class="fas fa-times"></i>

            </button>

        </div>


        {{-- LISTADO --}}

        <div class="card-body p-0">

            <div
                class="accordion"
                id="accordionOT">

                @foreach($otsAtrasadas as $index => $item)

                    @php

                        $ot = $item['ot'];

                        $estadoOT = strtoupper(
                            trim((string) $ot->estado)
                        );

                    @endphp


                    {{-- EXCLUIR POSTERGADAS --}}

                    @if($estadoOT === 'POSTERGADO')
                        @continue
                    @endif


                    @php

                        $procesos = $item['procesos'];

                        $ultimoMovimiento =
                            $item['ultimo_movimiento'];

                        $diasSinMovimiento =
                            (float) $item['dias_sin_movimiento'];

                        $avance =
                            $item['avance'];

                        $ultimoProceso =
                            $item['ultimo_proceso_normalizado'];


                        /*
                         * NIVEL
                         */

                        if ($diasSinMovimiento >= 30) {

                            $nivel = 'critica';

                        } elseif ($diasSinMovimiento >= 20) {

                            $nivel = 'grave';

                        } else {

                            $nivel = 'riesgo';

                        }

                    @endphp


                    <div
                        class="ot-item"
                        data-proceso="{{ trim($ultimoProceso) }}"
                        data-estado="{{ $estadoOT }}"
                        data-nivel="{{ $nivel }}"
                        data-search="
                            {{ $ot->nro_ot }}
                            {{ $ot->codigo }}
                            {{ $ot->descripcion }}
                        "
                        data-dias="{{ $diasSinMovimiento }}">


                        {{-- CABECERA OT --}}

                        <div
                            class="ot-header"
                            data-toggle="collapse"
                            data-target="#collapse{{ $index }}"
                            aria-expanded="false"
                            aria-controls="collapse{{ $index }}">


                            {{-- INDICADOR --}}

                            <div class="ot-severity
                                {{ $nivel }}">

                                @if($nivel === 'critica')

                                    <i class="fas fa-fire"></i>

                                @elseif($nivel === 'grave')

                                    <i class="fas fa-exclamation"></i>

                                @else

                                    <i class="fas fa-clock"></i>

                                @endif

                            </div>


                            {{-- OT --}}

                            <div class="ot-column ot-number">

                                <small>
                                    Orden de Trabajo
                                </small>

                                <strong>
                                    OT #{{ $ot->nro_ot }}
                                </strong>

                            </div>


                            {{-- CODIGO --}}

                            <div class="ot-column ot-code">

                                <small>
                                    Código
                                </small>

                                <strong>
                                    {{ $ot->codigo }}
                                </strong>

                            </div>


                            {{-- DESCRIPCION --}}

                            <div class="ot-column ot-description">

                                <small>
                                    Descripción
                                </small>

                                <span>
                                    {{ $ot->descripcion }}
                                </span>

                            </div>


                            {{-- PROCESO --}}

                            <div class="ot-column ot-process">

                                <small>
                                    Último proceso
                                </small>

                                <strong>
                                    {{ $ultimoProceso }}
                                </strong>

                                <span>
                                    {{ \Carbon\Carbon::parse(
                                        $ultimoMovimiento->fecha_proceso
                                    )->format('d/m/Y') }}
                                </span>

                            </div>


                            {{-- ATRASO --}}

                            <div class="ot-delay">

                                <span class="delay-badge {{ $nivel }}">

                                    <i class="fas fa-clock mr-1"></i>

                                    {{ number_format(
                                        $diasSinMovimiento,
                                        1,
                                        ',',
                                        '.'
                                    ) }}

                                    días

                                </span>

                            </div>


                            {{-- FLECHA --}}

                            <div class="ot-arrow">

                                <i class="fas fa-chevron-down"></i>

                            </div>

                        </div>


                        {{-- =================================================
                             DETALLE
                        ================================================== --}}

                        <div
                            id="collapse{{ $index }}"
                            class="collapse"
                            data-parent="#accordionOT">

                            <div class="ot-detail">


                                {{-- INFORMACION GENERAL --}}

                                <div class="row mb-4">

                                    <div class="col-md-3 mb-3">

                                        <div class="detail-box">

                                            <small>
                                                Cantidad orden
                                            </small>

                                            <strong>
                                                {{ $ot->cantidad_orden }}
                                            </strong>

                                        </div>

                                    </div>


                                    <div class="col-md-3 mb-3">

                                        <div class="detail-box">

                                            <small>
                                                Último movimiento
                                            </small>

                                            <strong>

                                                {{ \Carbon\Carbon::parse(
                                                    $ultimoMovimiento->fecha_proceso
                                                )->format('d/m/Y') }}

                                            </strong>

                                        </div>

                                    </div>


                                    <div class="col-md-3 mb-3">

                                        <div class="detail-box">

                                            <small>
                                                Días sin movimiento
                                            </small>

                                            <strong class="text-danger">

                                                {{ number_format(
                                                    $diasSinMovimiento,
                                                    2,
                                                    ',',
                                                    '.'
                                                ) }}

                                            </strong>

                                        </div>

                                    </div>


                                    <div class="col-md-3 mb-3">

                                        <div class="detail-box">

                                            <small>
                                                Estado
                                            </small>

                                            <strong class="
                                                {{ $estadoOT === 'ACTIVO'
                                                    ? 'text-success'
                                                    : 'text-secondary' }}
                                            ">

                                                {{ $ot->estado }}

                                            </strong>

                                        </div>

                                    </div>

                                </div>


                                {{-- AVANCE --}}

                                <div class="advance-section mb-4">

                                    <div class="d-flex justify-content-between mb-2">

                                        <span class="font-weight-bold">

                                            <i class="fas fa-chart-line text-primary mr-1"></i>

                                            Avance de la OT

                                        </span>

                                        <strong>
                                            {{ $avance }}%
                                        </strong>

                                    </div>


                                    <div
                                        class="progress"
                                        style="height: 12px;">

                                        <div
                                            class="progress-bar
                                            {{
                                                $avance >= 100
                                                    ? 'bg-success'
                                                    : (
                                                        $avance >= 50
                                                            ? 'bg-info'
                                                            : 'bg-warning'
                                                    )
                                            }}"
                                            role="progressbar"
                                            style="
                                                width: {{ min($avance, 100) }}%;
                                            ">

                                        </div>

                                    </div>

                                </div>


                                {{-- HISTORIAL --}}

                                <div class="history-header">

                                    <div>

                                        <h6 class="font-weight-bold mb-1">

                                            <i class="fas fa-route text-primary mr-2"></i>

                                            Historial de procesos

                                        </h6>

                                        <small class="text-muted">

                                            Seguimiento completo de la OT

                                        </small>

                                    </div>

                                    <span class="badge badge-light border">

                                        {{ count($procesos) }}

                                        {{ count($procesos) == 1
                                            ? 'movimiento'
                                            : 'movimientos' }}

                                    </span>

                                </div>


                                <div class="table-responsive">

                                    <table class="table table-bordered table-hover table-sm">

                                        <thead class="thead-light">

                                            <tr>

                                                <th width="45">
                                                    #
                                                </th>

                                                <th>
                                                    Proceso
                                                </th>

                                                <th>
                                                    Resultado
                                                </th>

                                                <th>
                                                    Fecha
                                                </th>

                                                <th>
                                                    Duración
                                                </th>

                                                <th>
                                                    Acumulado
                                                </th>

                                                <th>
                                                    Avance
                                                </th>

                                            </tr>

                                        </thead>


                                        <tbody>

                                            @foreach($procesos as $numero => $proceso)

                                                <tr
                                                    class="{{
                                                        $proceso['es_suspendido']
                                                            ? 'table-secondary'
                                                            : ''
                                                    }}">

                                                    <td>
                                                        {{ $numero + 1 }}
                                                    </td>


                                                    <td>

                                                        <strong>

                                                            {{ $proceso['proceso'] }}

                                                        </strong>


                                                        @if($proceso['es_suspendido'])

                                                            <span class="badge badge-secondary ml-1">

                                                                Suspendido

                                                            </span>

                                                        @endif

                                                    </td>


                                                    <td>

                                                        @if($proceso['resultado'] !== null)

                                                            {{ $proceso['resultado'] }}

                                                        @else

                                                            <span class="text-muted">
                                                                —
                                                            </span>

                                                        @endif

                                                    </td>


                                                    <td>

                                                        {{ $proceso['fecha']->format('d/m/Y') }}

                                                    </td>


                                                    <td>

                                                        @if($proceso['duracion_horas'] > 0)

                                                            {{ number_format(
                                                                $proceso['duracion_horas'],
                                                                2,
                                                                ',',
                                                                '.'
                                                            ) }}

                                                            h

                                                        @else

                                                            —

                                                        @endif

                                                    </td>


                                                    <td>

                                                        {{ number_format(
                                                            $proceso['horas_acumuladas'],
                                                            2,
                                                            ',',
                                                            '.'
                                                        ) }}

                                                        h

                                                    </td>


                                                    <td>

                                                        <div class="d-flex align-items-center">

                                                            <div
                                                                class="progress flex-grow-1 mr-2"
                                                                style="height: 8px;">

                                                                <div
                                                                    class="progress-bar
                                                                    {{
                                                                        $proceso['avance_acumulado'] >= 100
                                                                            ? 'bg-success'
                                                                            : (
                                                                                $proceso['avance_acumulado'] >= 50
                                                                                    ? 'bg-info'
                                                                                    : 'bg-warning'
                                                                            )
                                                                    }}"
                                                                    style="
                                                                        width: {{
                                                                            min(
                                                                                $proceso['avance_acumulado'],
                                                                                100
                                                                            )
                                                                        }}%;
                                                                    ">

                                                                </div>

                                                            </div>

                                                            <small>

                                                                {{ $proceso['avance_acumulado'] }}%

                                                            </small>

                                                        </div>

                                                    </td>

                                                </tr>

                                            @endforeach

                                        </tbody>

                                    </table>

                                </div>

                            </div>

                        </div>

                    </div>

                @endforeach

            </div>


            {{-- SIN RESULTADOS --}}

            <div
                id="sinResultadosFiltro"
                class="empty-filter d-none">

                <i class="fas fa-search"></i>

                <h5>
                    No se encontraron OT
                </h5>

                <p>
                    No existen órdenes de trabajo que coincidan con los filtros seleccionados.
                </p>

                <button
                    type="button"
                    id="btnLimpiarFiltroEmpty"
                    class="btn btn-outline-primary">

                    <i class="fas fa-sync-alt mr-1"></i>

                    Mostrar todas

                </button>

            </div>

        </div>

    </div>

    @endif

</div>


{{-- =============================================================
     CSS
============================================================= --}}

<style>

    /* =========================================================
       GENERAL
    ========================================================== */

    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }


    .header-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        background: #f8d7da;
        color: #dc3545;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }


    /* =========================================================
       KPI
    ========================================================== */

    .kpi-card {
        background: #fff;
        border-radius: 10px;
        padding: 22px;
        border-left: 4px solid;
        box-shadow: 0 2px 10px rgba(0,0,0,.06);
        transition: all .2s ease;
    }


    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 7px 20px rgba(0,0,0,.10);
    }


    .kpi-danger {
        border-color: #dc3545;
    }


    .kpi-warning {
        border-color: #ffc107;
    }


    .kpi-dark {
        border-color: #343a40;
    }


    .kpi-primary {
        border-color: #007bff;
    }


    .kpi-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }


    .kpi-label {
        font-size: .72rem;
        text-transform: uppercase;
        font-weight: 700;
        color: #6c757d;
        letter-spacing: .3px;
    }


    .kpi-value {
        font-size: 2rem;
        line-height: 1.2;
        font-weight: 700;
        margin: 5px 0;
    }


    .kpi-description {
        font-size: .78rem;
        color: #6c757d;
    }


    .kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        background: #f8f9fa;
    }


    .kpi-danger .kpi-icon {
        color: #dc3545;
    }


    .kpi-warning .kpi-icon {
        color: #d39e00;
    }


    .kpi-dark .kpi-icon {
        color: #343a40;
    }


    .kpi-primary .kpi-icon {
        color: #007bff;
    }


    /* =========================================================
       NIVEL ATRASO
    ========================================================== */

    .severity-card {
        display: flex;
        align-items: center;
        padding: 18px;
        border-radius: 10px;
        border: 1px solid;
        cursor: pointer;
        transition: all .2s ease;
    }


    .severity-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,.08);
    }


    .severity-icon {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
    }


    .severity-title {
        font-size: .75rem;
        font-weight: 700;
        text-transform: uppercase;
    }


    .severity-number {
        font-size: 1.7rem;
        font-weight: 700;
        line-height: 1.2;
    }


    .severity-description {
        font-size: .75rem;
        color: #6c757d;
    }


    .severity-arrow {
        color: #adb5bd;
    }


    .severity-critical {
        background: #fff5f5;
        border-color: #f5c2c7;
    }


    .severity-critical .severity-icon {
        background: #f8d7da;
        color: #dc3545;
    }


    .severity-critical .severity-number {
        color: #dc3545;
    }


    .severity-serious {
        background: #fffaf0;
        border-color: #ffe69c;
    }


    .severity-serious .severity-icon {
        background: #fff3cd;
        color: #d39e00;
    }


    .severity-serious .severity-number {
        color: #d39e00;
    }


    .severity-warning {
        background: #fffdf2;
        border-color: #ffecb5;
    }


    .severity-warning .severity-icon {
        background: #fff3cd;
        color: #856404;
    }


    .severity-warning .severity-number {
        color: #856404;
    }


    .severity-active {
        box-shadow: 0 0 0 2px #007bff;
    }


    /* =========================================================
       PROCESOS
    ========================================================== */

    .process-card {
        background: #fff;
        border: 1px solid #e3e6f0;
        border-left: 4px solid #dc3545;
        border-radius: 8px;
        padding: 18px;
        cursor: pointer;
        transition: all .2s ease;
    }


    .process-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 7px 20px rgba(0,0,0,.08);
        border-left-color: #007bff;
    }


    .process-card.active {
        border-left-color: #007bff;
        box-shadow: 0 0 0 2px rgba(0,123,255,.15);
    }


    .process-icon {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #f8d7da;
        color: #dc3545;
        display: flex;
        align-items: center;
        justify-content: center;
    }


    .process-filter {
        color: #adb5bd;
    }


    .process-name {
        font-size: .75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #6c757d;
        min-height: 32px;
    }


    .process-number {
        font-size: 2rem;
        line-height: 1;
        font-weight: 700;
        color: #dc3545;
        margin-top: 6px;
    }


    .progress {
        border-radius: 20px;
        background: #e9ecef;
    }


    .progress-bar {
        border-radius: 20px;
    }


    /* =========================================================
       FILTROS
    ========================================================== */

    .filter-label {
        display: block;
        font-size: .72rem;
        font-weight: 700;
        color: #6c757d;
        margin-bottom: 5px;
        text-transform: uppercase;
    }


    .form-control {
        border-radius: 6px;
    }


    .active-filter {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 20px;
        background: #f8f9fa;
        border-top: 1px solid #e3e6f0;
        border-bottom: 1px solid #e3e6f0;
        font-size: .85rem;
    }


    /* =========================================================
       OT
    ========================================================== */

    .ot-item {
        border-bottom: 1px solid #e9ecef;
    }


    .ot-header {
        display: flex;
        align-items: center;
        padding: 17px 20px;
        background: #fff;
        cursor: pointer;
        transition: background .2s ease;
    }


    .ot-header:hover {
        background: #f8f9fa;
    }


    .ot-severity {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        flex-shrink: 0;
    }


    .ot-severity.critica {
        background: #f8d7da;
        color: #dc3545;
    }


    .ot-severity.grave {
        background: #fff3cd;
        color: #d39e00;
    }


    .ot-severity.riesgo {
        background: #fff8df;
        color: #856404;
    }


    .ot-column {
        padding: 0 10px;
    }


    .ot-column small {
        display: block;
        color: #6c757d;
        font-size: .68rem;
        margin-bottom: 3px;
    }


    .ot-column strong {
        font-size: .85rem;
    }


    .ot-column span {
        display: block;
        font-size: .78rem;
        color: #6c757d;
    }


    .ot-number {
        width: 13%;
    }


    .ot-code {
        width: 13%;
    }


    .ot-description {
        width: 25%;
    }


    .ot-process {
        width: 22%;
    }


    .ot-delay {
        width: 14%;
        text-align: center;
    }


    .ot-arrow {
        width: 35px;
        text-align: right;
        color: #adb5bd;
        transition: transform .2s ease;
    }


    .delay-badge {
        display: inline-block;
        padding: 7px 10px;
        border-radius: 20px;
        font-size: .75rem;
        font-weight: 700;
        white-space: nowrap;
    }


    .delay-badge.critica {
        background: #f8d7da;
        color: #842029;
    }


    .delay-badge.grave {
        background: #fff3cd;
        color: #664d03;
    }


    .delay-badge.riesgo {
        background: #fff8df;
        color: #856404;
    }


    .ot-item:has(.collapse.show) .ot-arrow {
        transform: rotate(180deg);
    }


    /* =========================================================
       DETALLE
    ========================================================== */

    .ot-detail {
        background: #f8f9fa;
        padding: 25px;
        border-top: 1px solid #e9ecef;
    }


    .detail-box {
        background: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 7px;
        padding: 13px;
    }


    .detail-box small {
        display: block;
        color: #6c757d;
        font-size: .7rem;
        margin-bottom: 4px;
    }


    .detail-box strong {
        font-size: .9rem;
    }


    .advance-section {
        background: #fff;
        padding: 15px;
        border: 1px solid #e3e6f0;
        border-radius: 7px;
    }


    .history-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 15px;
    }


    .table {
        background: #fff;
    }


    .table th {
        font-size: .72rem;
        text-transform: uppercase;
        color: #6c757d;
        white-space: nowrap;
    }


    .table td {
        vertical-align: middle;
        font-size: .78rem;
    }


    /* =========================================================
       ESTADOS VACIOS
    ========================================================== */

    .empty-state,
    .empty-filter {
        text-align: center;
        padding: 60px 20px;
        background: #fff;
        border-radius: 8px;
    }


    .empty-state i,
    .empty-filter i {
        font-size: 50px;
        margin-bottom: 15px;
        color: #adb5bd;
    }


    .empty-state i {
        color: #28a745;
    }


    .empty-state h4,
    .empty-filter h5 {
        font-weight: 700;
    }


    .empty-state p,
    .empty-filter p {
        color: #6c757d;
    }


    /* =========================================================
       OCULTAR
    ========================================================== */

    .ot-item.filtro-oculto {
        display: none !important;
    }


    /* =========================================================
       RESPONSIVE
    ========================================================== */

    @media (max-width: 991px) {

        .dashboard-header {
            flex-direction: column;
            align-items: flex-start;
        }


        .dashboard-header > div:last-child {
            margin-top: 15px;
        }


        .ot-header {
            flex-wrap: wrap;
        }


        .ot-number,
        .ot-code,
        .ot-description,
        .ot-process {
            width: 50%;
            margin-bottom: 10px;
        }


        .ot-delay {
            width: auto;
            margin-left: auto;
        }

    }


    @media (max-width: 576px) {

        .ot-number,
        .ot-code,
        .ot-description,
        .ot-process {
            width: 100%;
        }


        .ot-delay {
            margin-left: 0;
            margin-top: 5px;
        }


        .ot-arrow {
            margin-left: auto;
        }

    }

</style>


{{-- =============================================================
     JAVASCRIPT
============================================================= --}}

<script>

$(document).ready(function () {

    /*
    |--------------------------------------------------------------------------
    | ELEMENTOS
    |--------------------------------------------------------------------------
    */

    const $items = $('.ot-item');

    const totalGeneral = {{ $totalAtrasadas }};


    /*
    |--------------------------------------------------------------------------
    | NORMALIZAR TEXTO
    |--------------------------------------------------------------------------
    */

    function normalizar(texto) {

        return String(texto || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, ' ')
            .trim()
            .toUpperCase();

    }


    /*
    |--------------------------------------------------------------------------
    | OBTENER NIVEL SEGÚN DÍAS
    |--------------------------------------------------------------------------
    */

    function obtenerNivel(dias) {

        dias = parseFloat(dias);

        if (dias >= 30) {
            return 'critica';
        }

        if (dias >= 20) {
            return 'grave';
        }

        return 'riesgo';

    }


    /*
    |--------------------------------------------------------------------------
    | APLICAR FILTROS
    |--------------------------------------------------------------------------
    */

    function aplicarFiltros() {

        const proceso = normalizar(
            $('#filtroProceso').val()
        );

        const nivel = $('#filtroNivel').val();

        const busqueda = normalizar(
            $('#buscarOT').val()
        );


        let visibles = 0;


        /*
        |--------------------------------------------------------------------------
        | CERRAR ABIERTAS
        |--------------------------------------------------------------------------
        */

        $('.ot-item .collapse.show').collapse('hide');


        /*
        |--------------------------------------------------------------------------
        | RECORRER OT
        |--------------------------------------------------------------------------
        */

        $items.each(function () {

            const $item = $(this);

            const itemProceso = normalizar(
                $item.data('proceso')
            );

            const itemNivel = $item.data('nivel');

            const textoBusqueda = normalizar(
                $item.data('search')
            );


            let mostrar = true;


            /*
            |--------------------------------------------------------------------------
            | FILTRO PROCESO
            |--------------------------------------------------------------------------
            */

            if (
                proceso !== '' &&
                itemProceso !== proceso
            ) {

                mostrar = false;

            }


            /*
            |--------------------------------------------------------------------------
            | FILTRO NIVEL
            |--------------------------------------------------------------------------
            */

            if (
                nivel !== '' &&
                itemNivel !== nivel
            ) {

                mostrar = false;

            }


            /*
            |--------------------------------------------------------------------------
            | BUSQUEDA
            |--------------------------------------------------------------------------
            */

            if (
                busqueda !== '' &&
                !textoBusqueda.includes(busqueda)
            ) {

                mostrar = false;

            }


            /*
            |--------------------------------------------------------------------------
            | MOSTRAR / OCULTAR
            |--------------------------------------------------------------------------
            */

            if (mostrar) {

                $item
                    .removeClass('filtro-oculto')
                    .show();

                visibles++;

            } else {

                $item
                    .addClass('filtro-oculto')
                    .hide();

            }

        });


        /*
        |--------------------------------------------------------------------------
        | CONTADOR
        |--------------------------------------------------------------------------
        */

        $('#contadorOTFiltradas')
            .text(visibles);


        /*
        |--------------------------------------------------------------------------
        | MENSAJE FILTRO
        |--------------------------------------------------------------------------
        */

        actualizarMensajeFiltro(
            proceso,
            nivel,
            busqueda,
            visibles
        );


        /*
        |--------------------------------------------------------------------------
        | SIN RESULTADOS
        |--------------------------------------------------------------------------
        */

        if (visibles === 0) {

            $('#sinResultadosFiltro')
                .removeClass('d-none');

        } else {

            $('#sinResultadosFiltro')
                .addClass('d-none');

        }


        /*
        |--------------------------------------------------------------------------
        | PROCESOS ACTIVOS
        |--------------------------------------------------------------------------
        */

        $('.process-card').removeClass('active');


        if (proceso !== '') {

            $('.process-card').each(function () {

                const cardProceso = normalizar(
                    $(this).data('process')
                );

                if (cardProceso === proceso) {

                    $(this).addClass('active');

                }

            });

        }


        /*
        |--------------------------------------------------------------------------
        | NIVELES ACTIVOS
        |--------------------------------------------------------------------------
        */

        $('.severity-card')
            .removeClass('severity-active');


        if (nivel !== '') {

            $('.severity-card[data-severity="' + nivel + '"]')
                .addClass('severity-active');

        }

    }


    /*
    |--------------------------------------------------------------------------
    | MENSAJE DE FILTROS
    |--------------------------------------------------------------------------
    */

    function actualizarMensajeFiltro(
        proceso,
        nivel,
        busqueda,
        visibles
    ) {

        let filtros = [];


        if (proceso !== '') {

            filtros.push(
                'Proceso: ' +
                $('#filtroProceso option:selected').text()
            );

        }


        if (nivel !== '') {

            let nombreNivel = '';

            if (nivel === 'critica') {
                nombreNivel = 'Críticas';
            }

            if (nivel === 'grave') {
                nombreNivel = 'Graves';
            }

            if (nivel === 'riesgo') {
                nombreNivel = 'En riesgo';
            }

            filtros.push(
                'Nivel: ' + nombreNivel
            );

        }


        if (busqueda !== '') {

            filtros.push(
                'Búsqueda: "' +
                $('#buscarOT').val() +
                '"'
            );

        }


        if (filtros.length === 0) {

            $('#mensajeFiltro')
                .addClass('d-none');

            return;

        }


        $('#descripcionFiltros')
            .text(
                filtros.join(' | ') +
                ' — ' +
                visibles +
                ' OT encontradas'
            );


        $('#mensajeFiltro')
            .removeClass('d-none');

    }


    /*
    |--------------------------------------------------------------------------
    | SELECT PROCESO
    |--------------------------------------------------------------------------
    */

    $('#filtroProceso').on(
        'change',
        function () {

            aplicarFiltros();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | SELECT NIVEL
    |--------------------------------------------------------------------------
    */

    $('#filtroNivel').on(
        'change',
        function () {

            aplicarFiltros();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | BUSCADOR
    |--------------------------------------------------------------------------
    */

    $('#buscarOT').on(
        'input',
        function () {

            aplicarFiltros();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | CLICK PROCESO
    |--------------------------------------------------------------------------
    */

    $('.process-card').on(
        'click',
        function () {

            const proceso = $(this)
                .data('process');


            $('#filtroProceso')
                .val(proceso)
                .trigger('change');


            /*
            |--------------------------------------------------------------------------
            | SCROLL AL LISTADO
            |--------------------------------------------------------------------------
            */

            $('html, body').animate(
                {
                    scrollTop:
                        $('#accordionOT').offset().top - 100
                },
                400
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | CLICK NIVEL
    |--------------------------------------------------------------------------
    */

    $('.severity-card').on(
        'click',
        function () {

            const nivel =
                $(this).data('severity');


            $('#filtroNivel')
                .val(nivel)
                .trigger('change');


            $('html, body').animate(
                {
                    scrollTop:
                        $('#accordionOT').offset().top - 100
                },
                400
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | LIMPIAR FILTROS
    |--------------------------------------------------------------------------
    */

    function limpiarFiltros() {

        $('#buscarOT').val('');

        $('#filtroProceso').val('');

        $('#filtroNivel').val('');


        $('.process-card')
            .removeClass('active');


        $('.severity-card')
            .removeClass('severity-active');


        aplicarFiltros();

    }


    /*
    |--------------------------------------------------------------------------
    | BOTÓN LIMPIAR
    |--------------------------------------------------------------------------
    */

    $('#btnLimpiarFiltro').on(
        'click',
        function () {

            limpiarFiltros();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | BOTÓN LIMPIAR DESDE "SIN RESULTADOS"
    |--------------------------------------------------------------------------
    */

    $('#btnLimpiarFiltroEmpty').on(
        'click',
        function () {

            limpiarFiltros();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | QUITAR FILTRO
    |--------------------------------------------------------------------------
    */

    $('#quitarFiltro').on(
        'click',
        function () {

            limpiarFiltros();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | FLECHA AL ABRIR / CERRAR
    |--------------------------------------------------------------------------
    */

    $('.collapse').on(
        'show.bs.collapse',
        function () {

            const id = $(this).attr('id');

            const $item = $('#' + id)
                .closest('.ot-item');

            $item.find('.ot-arrow i')
                .removeClass('fa-chevron-down')
                .addClass('fa-chevron-up');

        }
    );


    $('.collapse').on(
        'hide.bs.collapse',
        function () {

            const id = $(this).attr('id');

            const $item = $('#' + id)
                .closest('.ot-item');

            $item.find('.ot-arrow i')
                .removeClass('fa-chevron-up')
                .addClass('fa-chevron-down');

        }
    );


    /*
    |--------------------------------------------------------------------------
    | ESTADO INICIAL
    |--------------------------------------------------------------------------
    */

    aplicarFiltros();

});

</script>

@endsection
