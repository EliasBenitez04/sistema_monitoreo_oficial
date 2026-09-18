@extends('layouts.app')

@section('content')

<style>
    /* =========================================================
   VARIABLES
========================================================= */

    :root {
        --dash-primary: #2563eb;
        --dash-primary-dark: #1d4ed8;
        --dash-blue-soft: #eff6ff;

        --dash-success: #16a34a;
        --dash-success-soft: #f0fdf4;

        --dash-warning: #d97706;
        --dash-warning-soft: #fffbeb;

        --dash-danger: #dc2626;
        --dash-danger-soft: #fef2f2;

        --dash-purple: #7c3aed;
        --dash-purple-soft: #f5f3ff;

        --dash-cyan: #0891b2;
        --dash-cyan-soft: #ecfeff;

        --dash-dark: #172033;
        --dash-text: #475569;
        --dash-muted: #94a3b8;

        --dash-border: #e7ebf3;
        --dash-bg: #f5f7fb;
    }


    /* =========================================================
   GENERAL
========================================================= */

    .ot-dashboard {
        background: var(--dash-bg);
        min-height: calc(100vh - 70px);
        padding: 25px 18px 60px;
    }

    .ot-dashboard * {
        box-sizing: border-box;
    }


    /* =========================================================
   HEADER
========================================================= */

    .dashboard-header {
        background:
            radial-gradient(circle at top right, rgba(37, 99, 235, .08), transparent 35%),
            linear-gradient(135deg, #ffffff 0%, #f8faff 100%);

        border: 1px solid var(--dash-border);
        border-radius: 20px;
        padding: 24px 28px;
        margin-bottom: 22px;

        box-shadow: 0 6px 25px rgba(30, 41, 59, .05);
    }

    .dashboard-title-wrapper {
        display: flex;
        align-items: center;
    }

    .dashboard-title-icon {
        width: 52px;
        height: 52px;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 15px;

        background: linear-gradient(135deg,
                #2563eb,
                #4f46e5);

        color: #fff;
        font-size: 21px;

        margin-right: 14px;

        box-shadow:
            0 8px 20px rgba(37, 99, 235, .25);
    }

    .dashboard-title {
        margin: 0;

        font-size: 27px;
        font-weight: 800;

        color: var(--dash-dark);

        letter-spacing: -.4px;
    }

    .dashboard-subtitle {
        margin-top: 4px;

        font-size: 13px;

        color: #7a8498;
    }

    .dashboard-status {
        display: flex;
        align-items: center;
        gap: 7px;

        padding: 8px 12px;

        border-radius: 9px;

        background: var(--dash-success-soft);
        color: var(--dash-success);

        font-size: 11px;
        font-weight: 800;

        text-transform: uppercase;
    }

    .dashboard-status-dot {
        width: 7px;
        height: 7px;

        border-radius: 50%;

        background: currentColor;
    }


    /* =========================================================
   BUTTONS
========================================================= */

    .btn-dashboard-danger {
        display: inline-flex;
        align-items: center;

        border-radius: 11px;

        padding: 10px 16px;

        font-size: 13px;
        font-weight: 700;

        border: 1px solid #fecaca;

        color: var(--dash-danger);
        background: #fff;

        transition: all .2s ease;
    }

    .btn-dashboard-danger:hover {
        background: var(--dash-danger);
        color: #fff;

        transform: translateY(-1px);

        box-shadow: 0 7px 18px rgba(220, 38, 38, .18);
    }

    .btn-filter {
        height: 43px;

        border-radius: 10px;

        font-weight: 700;

        background:
            linear-gradient(135deg,
                #2563eb,
                #4f46e5);

        border: none;

        box-shadow: 0 5px 14px rgba(37, 99, 235, .16);

        transition: all .2s ease;
    }

    .btn-filter:hover {
        transform: translateY(-1px);

        box-shadow: 0 8px 18px rgba(37, 99, 235, .22);
    }

    .btn-clear-filter {
        height: 43px;

        border-radius: 10px;

        font-weight: 600;

        background: #fff;

        border: 1px solid #dfe4ec;

        color: #64748b;
    }

    .btn-clear-filter:hover {
        background: #f8fafc;
    }


    /* =========================================================
   FILTERS
========================================================= */

    .filter-card {
        background: #fff;

        border: 1px solid var(--dash-border);

        border-radius: 17px;

        padding: 19px 21px;

        margin-bottom: 22px;

        box-shadow: 0 5px 18px rgba(30, 41, 59, .04);
    }

    .filter-title {
        display: flex;
        align-items: center;

        font-size: 12px;
        font-weight: 800;

        color: #64748b;

        text-transform: uppercase;
        letter-spacing: .7px;

        margin-bottom: 16px;
    }

    .filter-title i {
        color: var(--dash-primary);
    }

    .filter-label {
        display: block;

        font-size: 11px;

        font-weight: 800;

        color: #64748b;

        margin-bottom: 6px;

        text-transform: uppercase;
    }

    .filter-card .form-control {
        height: 43px;

        border-radius: 10px;

        border: 1px solid #dfe4ec;

        font-size: 13px;

        box-shadow: none;

        color: #334155;
    }

    .filter-card .form-control:focus {
        border-color: var(--dash-primary);

        box-shadow:
            0 0 0 3px rgba(37, 99, 235, .08);
    }


    /* =========================================================
   KPI
========================================================= */

    .kpi-card {
        position: relative;

        background: #fff;

        border: 1px solid var(--dash-border);

        border-radius: 17px;

        padding: 19px;

        min-height: 145px;

        overflow: hidden;

        box-shadow: 0 5px 18px rgba(30, 41, 59, .045);

        transition:
            transform .2s ease,
            box-shadow .2s ease,
            border-color .2s ease;
    }

    .kpi-card:hover {
        transform: translateY(-4px);

        box-shadow:
            0 12px 28px rgba(30, 41, 59, .09);

        border-color: #d5ddec;
    }

    .kpi-card::after {
        content: "";

        position: absolute;

        width: 110px;
        height: 110px;

        right: -40px;
        bottom: -45px;

        border-radius: 50%;

        background: currentColor;

        opacity: .045;
    }

    .kpi-top {
        position: relative;
        z-index: 2;

        display: flex;

        justify-content: space-between;

        align-items: flex-start;
    }

    .kpi-label {
        font-size: 10px;

        font-weight: 800;

        color: #7b8497;

        text-transform: uppercase;

        letter-spacing: .8px;
    }

    .kpi-value {
        font-size: 29px;

        line-height: 1.1;

        font-weight: 800;

        color: var(--dash-dark);

        margin-top: 8px;

        letter-spacing: -.6px;
    }

    .kpi-description {
        font-size: 11px;

        color: #98a1b2;

        margin-top: 6px;
    }

    .kpi-icon {
        width: 46px;
        height: 46px;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 13px;

        font-size: 18px;
    }


    /* KPI COLORS */

    .kpi-blue {
        color: #2563eb;
    }

    .kpi-blue .kpi-icon {
        background: var(--dash-blue-soft);
        color: #2563eb;
    }

    .kpi-cyan {
        color: var(--dash-cyan);
    }

    .kpi-cyan .kpi-icon {
        background: var(--dash-cyan-soft);
        color: var(--dash-cyan);
    }

    .kpi-green {
        color: var(--dash-success);
    }

    .kpi-green .kpi-icon {
        background: var(--dash-success-soft);
        color: var(--dash-success);
    }

    .kpi-red {
        color: var(--dash-danger);
    }

    .kpi-red .kpi-icon {
        background: var(--dash-danger-soft);
        color: var(--dash-danger);
    }

    .kpi-yellow {
        color: var(--dash-warning);
    }

    .kpi-yellow .kpi-icon {
        background: var(--dash-warning-soft);
        color: var(--dash-warning);
    }

    .kpi-purple {
        color: var(--dash-purple);
    }

    .kpi-purple .kpi-icon {
        background: var(--dash-purple-soft);
        color: var(--dash-purple);
    }

    .kpi-dark {
        color: #475569;
    }

    .kpi-dark .kpi-icon {
        background: #f1f5f9;
        color: #475569;
    }


    /* =========================================================
   SECTION
========================================================= */

    .section-card {
        background: #fff;

        border: 1px solid var(--dash-border);

        border-radius: 17px;

        overflow: hidden;

        box-shadow: 0 5px 18px rgba(30, 41, 59, .04);
    }

    .section-header {
        padding: 17px 21px;

        border-bottom: 1px solid #edf0f5;

        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 15px;
    }

    .section-title-wrapper {
        display: flex;
        align-items: center;
    }

    .section-title {
        font-size: 14px;

        font-weight: 800;

        color: var(--dash-dark);

        margin: 0;
    }

    .section-subtitle {
        font-size: 11px;

        color: #8b95a7;

        margin-top: 3px;
    }

    .section-icon {
        width: 39px;
        height: 39px;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 10px;

        margin-right: 11px;

        flex-shrink: 0;
    }

    .section-icon-blue {
        background: var(--dash-blue-soft);
        color: var(--dash-primary);
    }

    .section-icon-green {
        background: var(--dash-success-soft);
        color: var(--dash-success);
    }

    .section-icon-yellow {
        background: var(--dash-warning-soft);
        color: var(--dash-warning);
    }

    .section-icon-red {
        background: var(--dash-danger-soft);
        color: var(--dash-danger);
    }

    .section-body {
        padding: 21px;
    }


    /* =========================================================
   CHARTS
========================================================= */

    .chart-container {
        position: relative;

        height: 330px;
    }

    .chart-container canvas {
        width: 100% !important;
        height: 100% !important;
    }


    /* =========================================================
   TABLE
========================================================= */

    .dashboard-table {
        margin: 0;
    }

    .dashboard-table thead th {
        background: #f8fafc;

        color: #64748b;

        font-size: 9px;

        font-weight: 800;

        text-transform: uppercase;

        letter-spacing: .6px;

        border-top: none;

        border-bottom: 1px solid #e8ecf3;

        padding: 12px 14px;

        white-space: nowrap;
    }

    .dashboard-table tbody td {
        padding: 12px 14px;

        border-color: #f0f2f6;

        vertical-align: middle;

        font-size: 12px;

        color: #475569;
    }

    .dashboard-table tbody tr {
        transition: background .15s ease;
    }

    .dashboard-table tbody tr:hover {
        background: #f8faff;
    }

    .ot-number {
        font-weight: 800;

        color: var(--dash-primary);

        white-space: nowrap;
    }

    .product-description {
        font-weight: 600;

        color: #334155;
    }

    .product-description-cell {
        min-width: 220px;

        max-width: 350px;

        line-height: 1.35;
    }

    code {
        color: #475569;

        background: #f1f5f9;

        padding: 4px 7px;

        border-radius: 6px;

        font-size: 10px;
    }


    /* =========================================================
   BADGES
========================================================= */

    .status-badge {
        display: inline-flex;

        align-items: center;

        padding: 5px 9px;

        border-radius: 7px;

        font-size: 9px;

        font-weight: 800;

        text-transform: uppercase;

        letter-spacing: .3px;

        white-space: nowrap;
    }

    .badge-process {
        background: var(--dash-blue-soft);
        color: var(--dash-primary);
    }

    .badge-danger-modern {
        background: var(--dash-danger-soft);
        color: var(--dash-danger);
    }

    .badge-success-modern {
        background: var(--dash-success-soft);
        color: var(--dash-success);
    }

    .badge-warning-modern {
        background: var(--dash-warning-soft);
        color: var(--dash-warning);
    }

    .badge-info-modern {
        background: var(--dash-cyan-soft);
        color: var(--dash-cyan);
    }


    /* =========================================================
   PROGRESS
========================================================= */

    .modern-progress {
        height: 7px;

        border-radius: 20px;

        background: #edf1f6;

        overflow: hidden;
    }

    .modern-progress .progress-bar {
        height: 100%;

        border-radius: 20px;

        transition: width .5s ease;
    }

    .progress-wrapper {
        min-width: 145px;
    }

    .progress-label {
        display: flex;

        justify-content: space-between;

        margin-bottom: 5px;

        font-size: 10px;
    }


    /* =========================================================
   PROCESS CARDS
========================================================= */

    .process-card {
        position: relative;

        background: #fff;

        border: 1px solid #e8ecf3;

        border-radius: 14px;

        padding: 16px;

        height: 100%;

        transition: all .2s ease;
    }

    .process-card:hover {
        border-color: #cdd8ef;

        transform: translateY(-2px);

        box-shadow:
            0 7px 20px rgba(30, 41, 59, .07);
    }

    .process-name {
        font-size: 10px;

        line-height: 1.35;

        font-weight: 800;

        color: #64748b;

        text-transform: uppercase;

        min-height: 32px;

        padding-right: 5px;
    }

    .process-count {
        font-size: 27px;

        font-weight: 800;

        color: var(--dash-primary);

        margin-top: 5px;
    }

    .process-icon {
        width: 39px;
        height: 39px;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 10px;

        background: var(--dash-blue-soft);

        color: var(--dash-primary);

        flex-shrink: 0;
    }

    .process-meta {
        display: flex;

        justify-content: space-between;

        align-items: center;

        font-size: 10px;

        color: #94a3b8;
    }

    .process-percentage {
        font-weight: 800;

        color: #475569;
    }


    /* =========================================================
   CRITICALITY
========================================================= */

    .process-normal {
        border-left: 4px solid #16a34a;
    }

    .process-warning {
        border-left: 4px solid #d97706;
    }

    .process-danger {
        border-left: 4px solid #dc2626;
    }


    /* =========================================================
   ALERT RANKING
========================================================= */

    .alert-row {
        position: relative;
    }

    .alert-row td:first-child {
        border-left: 3px solid transparent;
    }

    .alert-critical td:first-child {
        border-left-color: #dc2626;
    }

    .alert-warning td:first-child {
        border-left-color: #d97706;
    }


    /* =========================================================
   SUMMARY
========================================================= */

    .summary-card {
        position: relative;

        border-radius: 15px;

        padding: 18px;

        border: 1px solid;

        height: 100%;

        overflow: hidden;
    }

    .summary-card::after {
        content: "";

        position: absolute;

        width: 80px;
        height: 80px;

        right: -25px;
        bottom: -30px;

        border-radius: 50%;

        background: currentColor;

        opacity: .06;
    }

    .summary-title {
        position: relative;
        z-index: 2;

        font-size: 10px;

        font-weight: 800;

        text-transform: uppercase;

        letter-spacing: .6px;
    }

    .summary-value {
        position: relative;
        z-index: 2;

        font-size: 25px;

        font-weight: 800;

        margin-top: 7px;
    }

    .summary-danger {
        background: #fff7f7;

        border-color: #fecaca;

        color: #dc2626;
    }

    .summary-warning {
        background: #fffbeb;

        border-color: #fde68a;

        color: #d97706;
    }

    .summary-primary {
        background: #f5f8ff;

        border-color: #bfdbfe;

        color: #2563eb;
    }


    /* =========================================================
   EMPTY STATE
========================================================= */

    .empty-state {
        text-align: center;

        padding: 38px 20px;

        color: #94a3b8;
    }

    .empty-state i {
        display: block;

        font-size: 31px;

        margin-bottom: 10px;
    }

    .empty-state div {
        font-size: 12px;
    }


    /* =========================================================
   RANKING NUMBER
========================================================= */

    .rank-number {
        width: 27px;
        height: 27px;

        display: inline-flex;

        align-items: center;
        justify-content: center;

        border-radius: 8px;

        background: #f1f5f9;

        color: #64748b;

        font-size: 10px;

        font-weight: 800;
    }

    .rank-number.top-1 {
        background: #fef3c7;
        color: #b45309;
    }

    .rank-number.top-2 {
        background: #f1f5f9;
        color: #475569;
    }

    .rank-number.top-3 {
        background: #fff7ed;
        color: #c2410c;
    }


    /* =========================================================
   RESPONSIVE
========================================================= */

    @media (max-width: 992px) {

        .dashboard-header {
            padding: 20px;
        }

        .dashboard-title {
            font-size: 23px;
        }

        .chart-container {
            height: 300px;
        }

    }

    @media (max-width: 768px) {

        .ot-dashboard {
            padding: 15px 7px 40px;
        }

        .dashboard-header {
            padding: 17px;
            border-radius: 15px;
        }

        .dashboard-title-wrapper {
            align-items: flex-start;
        }

        .dashboard-title-icon {
            width: 42px;
            height: 42px;

            font-size: 17px;

            margin-right: 10px;
        }

        .dashboard-title {
            font-size: 19px;
        }

        .dashboard-subtitle {
            font-size: 11px;
        }

        .dashboard-status {
            display: none;
        }

        .chart-container {
            height: 275px;
        }

        .kpi-value {
            font-size: 25px;
        }

        .section-header {
            padding: 14px 15px;
        }

        .section-body {
            padding: 15px;
        }

    }
</style>


<div class="ot-dashboard">


    {{-- =========================================================
         HEADER
    ========================================================== --}}

    <div class="dashboard-header">

        <div class="d-flex justify-content-between align-items-center flex-wrap">

            <div class="dashboard-title-wrapper">

                <div class="dashboard-title-icon">
                    <i class="fas fa-chart-line"></i>
                </div>

                <div>

                    <h1 class="dashboard-title">
                        Dashboard de Órdenes de Trabajo
                    </h1>

                    <div class="dashboard-subtitle">
                        Monitoreo general de producción, procesos, tiempos y atrasos
                    </div>

                </div>

            </div>


            <div class="d-flex align-items-center mt-3 mt-md-0">

                <div class="dashboard-status mr-3">

                    <span class="dashboard-status-dot"></span>

                    Monitoreo activo

                </div>


                <a
                    href="{{ route('dashboard.ot-atrasadas') }}"
                    class="btn btn-dashboard-danger">

                    <i class="fas fa-exclamation-triangle mr-1"></i>

                    Ver OT atrasadas

                </a>

            </div>

        </div>

    </div>


    {{-- =========================================================
         FILTROS
    ========================================================== --}}

    <div class="filter-card">

        <div class="filter-title">

            <i class="fas fa-sliders-h mr-2"></i>

            Filtros de consulta

        </div>


        <form
            method="GET"
            action="{{ route('dashboard.ot') }}">

            <div class="row align-items-end">


                {{-- FECHA DESDE --}}

                <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">

                    <label class="filter-label">
                        Fecha desde
                    </label>

                    <input
                        type="date"
                        name="fecha_desde"
                        class="form-control"
                        value="{{ request('fecha_desde') }}">

                </div>


                {{-- FECHA HASTA --}}

                <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">

                    <label class="filter-label">
                        Fecha hasta
                    </label>

                    <input
                        type="date"
                        name="fecha_hasta"
                        class="form-control"
                        value="{{ request('fecha_hasta') }}">

                </div>


                {{-- PROCESO --}}

                <div class="col-lg-4 col-md-8 mb-3 mb-lg-0">

                    <label class="filter-label">
                        Proceso
                    </label>

                    <select
                        name="proceso"
                        class="form-control">

                        <option value="">
                            Todos los procesos
                        </option>

                        @foreach($procesosDisponibles as $proceso)

                        <option
                            value="{{ $proceso }}"
                            {{ request('proceso') === $proceso ? 'selected' : '' }}>

                            {{ $proceso }}

                        </option>

                        @endforeach

                    </select>

                </div>


                {{-- BOTONES --}}

                <div class="col-lg-2 col-md-4">

                    <div class="d-flex">

                        <button
                            type="submit"
                            class="btn btn-primary btn-filter btn-block mr-2">

                            <i class="fas fa-filter mr-1"></i>

                            Filtrar

                        </button>


                        @if(request()->hasAny([
                        'fecha_desde',
                        'fecha_hasta',
                        'proceso'
                        ]))

                        <a
                            href="{{ route('dashboard.ot') }}"
                            class="btn btn-clear-filter">

                            <i class="fas fa-times"></i>

                        </a>

                        @endif

                    </div>

                </div>

            </div>

        </form>

    </div>


    {{-- =========================================================
         KPIs PRINCIPALES
    ========================================================== --}}

    <div class="row mb-1">


        {{-- TOTAL --}}

        <div class="col-xl-3 col-md-6 mb-4">

            <div class="kpi-card kpi-blue">

                <div class="kpi-top">

                    <div>

                        <div class="kpi-label">
                            Total OT
                        </div>

                        <div class="kpi-value">
                            {{ number_format($totalOT, 0, ',', '.') }}
                        </div>

                        <div class="kpi-description">
                            Órdenes registradas
                        </div>

                    </div>

                    <div class="kpi-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>

                </div>

            </div>

        </div>


        {{-- EN PROCESO --}}

        <div class="col-xl-3 col-md-6 mb-4">

            <div class="kpi-card kpi-cyan">

                <div class="kpi-top">

                    <div>

                        <div class="kpi-label">
                            En proceso
                        </div>

                        <div class="kpi-value">
                            {{ number_format($otEnProceso, 0, ',', '.') }}
                        </div>

                        <div class="kpi-description">
                            OT actualmente activas
                        </div>

                    </div>

                    <div class="kpi-icon">
                        <i class="fas fa-cogs"></i>
                    </div>

                </div>

            </div>

        </div>


        {{-- FINALIZADAS --}}

        <div class="col-xl-3 col-md-6 mb-4">

            <div class="kpi-card kpi-green">

                <div class="kpi-top">

                    <div>

                        <div class="kpi-label">
                            Finalizadas
                        </div>

                        <div class="kpi-value">
                            {{ number_format($otFinalizadas, 0, ',', '.') }}
                        </div>

                        <div class="kpi-description">
                            OT completadas
                        </div>

                    </div>

                    <div class="kpi-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>

                </div>

            </div>

        </div>


        {{-- ATRASADAS --}}

        <div class="col-xl-3 col-md-6 mb-4">

            <div class="kpi-card kpi-red">

                <div class="kpi-top">

                    <div>

                        <div class="kpi-label">
                            OT atrasadas
                        </div>

                        <div class="kpi-value">
                            {{ number_format($otAtrasadas, 0, ',', '.') }}
                        </div>

                        <div class="kpi-description">

                            Más de {{ $diasAlerta }} días sin movimiento

                        </div>

                    </div>

                    <div class="kpi-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         KPIs PRODUCCIÓN
    ========================================================== --}}

    <div class="row">


        {{-- ORDENADO --}}

        <div class="col-xl-3 col-md-6 mb-4">

            <div class="kpi-card kpi-dark">

                <div class="kpi-top">

                    <div>

                        <div class="kpi-label">
                            Cantidad ordenada
                        </div>

                        <div class="kpi-value">
                            {{ number_format($cantidadOrdenada, 0, ',', '.') }}
                        </div>

                        <div class="kpi-description">
                            Unidades solicitadas
                        </div>

                    </div>

                    <div class="kpi-icon">
                        <i class="fas fa-boxes"></i>
                    </div>

                </div>

            </div>

        </div>


        {{-- PRODUCIDO --}}

        <div class="col-xl-3 col-md-6 mb-4">

            <div class="kpi-card kpi-green">

                <div class="kpi-top">

                    <div>

                        <div class="kpi-label">
                            Cantidad producida
                        </div>

                        <div class="kpi-value">
                            {{ number_format($cantidadProducida, 0, ',', '.') }}
                        </div>

                        <div class="kpi-description">
                            Unidades producidas
                        </div>

                    </div>

                    <div class="kpi-icon">
                        <i class="fas fa-industry"></i>
                    </div>

                </div>

            </div>

        </div>


        {{-- CUMPLIMIENTO --}}

        <div class="col-xl-3 col-md-6 mb-4">

            <div class="kpi-card kpi-yellow">

                <div class="kpi-top">

                    <div>

                        <div class="kpi-label">
                            Cumplimiento
                        </div>

                        <div class="kpi-value">

                            {{ number_format($cumplimientoGeneral, 2, ',', '.') }}%

                        </div>

                        <div class="kpi-description">
                            Producido / ordenado
                        </div>

                    </div>

                    <div class="kpi-icon">
                        <i class="fas fa-percentage"></i>
                    </div>

                </div>

            </div>

        </div>


        {{-- PROMEDIO --}}

        <div class="col-xl-3 col-md-6 mb-4">

            <div class="kpi-card kpi-purple">

                <div class="kpi-top">

                    <div>

                        <div class="kpi-label">
                            Promedio de días
                        </div>

                        <div class="kpi-value">

                            {{ number_format($promedioDias, 2, ',', '.') }}

                        </div>

                        <div class="kpi-description">
                            Inicio → último movimiento
                        </div>

                    </div>

                    <div class="kpi-icon">
                        <i class="fas fa-stopwatch"></i>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         GRÁFICOS
    ========================================================== --}}

    <div class="row">


        {{-- OT POR PROCESO --}}

        <div class="col-lg-6 mb-4">

            <div class="section-card h-100">

                <div class="section-header">

                    <div class="section-title-wrapper">

                        <div class="section-icon section-icon-blue">

                            <i class="fas fa-project-diagram"></i>

                        </div>

                        <div>

                            <div class="section-title">
                                OT por proceso
                            </div>

                            <div class="section-subtitle">
                                Distribución actual de las órdenes
                            </div>

                        </div>

                    </div>

                </div>


                <div class="section-body">

                    <div class="chart-container">

                        <canvas id="graficoProcesos"></canvas>

                    </div>

                </div>

            </div>

        </div>


        {{-- PRODUCCIÓN --}}

        <div class="col-lg-6 mb-4">

            <div class="section-card h-100">

                <div class="section-header">

                    <div class="section-title-wrapper">

                        <div class="section-icon section-icon-green">

                            <i class="fas fa-chart-area"></i>

                        </div>

                        <div>

                            <div class="section-title">
                                Producción diaria
                            </div>

                            <div class="section-subtitle">
                                Evolución de unidades producidas
                            </div>

                        </div>

                    </div>

                </div>


                <div class="section-body">

                    <div class="chart-container">

                        <canvas id="graficoProduccion"></canvas>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         TIEMPO POR PROCESO
    ========================================================== --}}

    <div class="section-card mb-4">

        <div class="section-header">

            <div class="section-title-wrapper">

                <div class="section-icon section-icon-yellow">

                    <i class="fas fa-stopwatch"></i>

                </div>

                <div>

                    <div class="section-title">
                        Tiempo promedio por proceso
                    </div>

                    <div class="section-subtitle">
                        Identificación de los procesos que generan mayor demora
                    </div>

                </div>

            </div>

        </div>


        <div class="table-responsive">

            <table class="table dashboard-table">

                <thead>

                    <tr>

                        <th>Proceso</th>

                        <th>OT</th>

                        <th>Promedio</th>

                        <th>Nivel</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($tiempoPorProceso as $item)

                    @php

                    $dias = (float) $item['promedio_dias'];

                    if ($dias >= 30) {
                    $nivel = 'CRÍTICO';
                    $badge = 'badge-danger-modern';
                    $icon = 'fa-fire';
                    } elseif ($dias >= 15) {
                    $nivel = 'ALERTA';
                    $badge = 'badge-warning-modern';
                    $icon = 'fa-exclamation-triangle';
                    } elseif ($dias >= 7) {
                    $nivel = 'ATENCIÓN';
                    $badge = 'badge-info-modern';
                    $icon = 'fa-clock';
                    } else {
                    $nivel = 'NORMAL';
                    $badge = 'badge-success-modern';
                    $icon = 'fa-check';
                    }

                    @endphp

                    <tr>

                        <td>

                            <strong class="product-description">

                                {{ $item['proceso'] }}

                            </strong>

                        </td>

                        <td>

                            <strong>

                                {{ number_format($item['cantidad'], 0, ',', '.') }}

                            </strong>

                        </td>

                        <td>

                            <span class="status-badge {{ $badge }}">

                                <i class="fas {{ $icon }} mr-1"></i>

                                {{ number_format($dias, 2, ',', '.') }}

                                días

                            </span>

                        </td>

                        <td>

                            <span class="status-badge {{ $badge }}">

                                {{ $nivel }}

                            </span>

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="4">

                            <div class="empty-state">

                                <i class="fas fa-database"></i>

                                No existen datos disponibles.

                            </div>

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    {{-- =========================================================
         DISTRIBUCIÓN POR PROCESO
    ========================================================== --}}

    <div class="section-card mb-4">

        <div class="section-header">

            <div class="section-title-wrapper">

                <div class="section-icon section-icon-blue">

                    <i class="fas fa-layer-group"></i>

                </div>

                <div>

                    <div class="section-title">
                        Distribución de OT por proceso
                    </div>

                    <div class="section-subtitle">
                        Cantidad, participación y nivel de concentración
                    </div>

                </div>

            </div>

        </div>


        <div class="section-body">

            <div class="row">

                @foreach($detalleProcesos as $detalle)

                @php

                $porcentaje = (float) $detalle['porcentaje'];

                $diasProceso = isset($detalle['promedio_dias'])
                ? (float) $detalle['promedio_dias']
                : 0;

                if ($diasProceso >= 30) {
                $processClass = 'process-danger';
                } elseif ($diasProceso >= 15) {
                $processClass = 'process-warning';
                } else {
                $processClass = 'process-normal';
                }

                @endphp


                <div class="col-xl-3 col-lg-4 col-md-6 mb-3">

                    <div class="process-card {{ $processClass }}">

                        <div class="d-flex justify-content-between">

                            <div style="min-width:0;">

                                <div class="process-name">

                                    {{ $detalle['proceso'] }}

                                </div>

                                <div class="process-count">

                                    {{ number_format($detalle['cantidad'], 0, ',', '.') }}

                                </div>

                            </div>


                            <div class="process-icon">

                                <i class="fas fa-cogs"></i>

                            </div>

                        </div>


                        <div class="mt-3">

                            <div class="process-meta mb-1">

                                <span>
                                    Participación
                                </span>

                                <span class="process-percentage">

                                    {{ number_format($porcentaje, 1, ',', '.') }}%

                                </span>

                            </div>


                            <div class="modern-progress">

                                <div
                                    class="progress-bar bg-primary"
                                    style="width: {{ min($porcentaje, 100) }}%;">
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                @endforeach

            </div>

        </div>

    </div>


    {{-- =========================================================
         OT MÁS ATRASADAS
    ========================================================== --}}

    <div class="section-card mb-4">

        <div class="section-header">

            <div class="section-title-wrapper">

                <div class="section-icon section-icon-red">

                    <i class="fas fa-exclamation-circle"></i>

                </div>

                <div>

                    <div class="section-title">
                        OT más atrasadas
                    </div>

                    <div class="section-subtitle">
                        Top 20 órdenes con mayor tiempo sin movimiento
                    </div>

                </div>

            </div>


            <span class="status-badge badge-danger-modern">

                <i class="fas fa-exclamation-triangle mr-1"></i>

                {{ number_format($otAtrasadas, 0, ',', '.') }}

                atrasadas

            </span>

        </div>


        <div class="table-responsive">

            <table class="table dashboard-table">

                <thead>

                    <tr>

                        <th>OT</th>

                        <th>Código</th>

                        <th>Descripción</th>

                        <th>Proceso</th>

                        <th>Ordenado</th>

                        <th>Producido</th>

                        <th>Avance</th>

                        <th>Días</th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($rankingAtrasadas as $item)

                    @php

                    $diasAtraso =
                    (float) $item['dias_sin_movimiento'];

                    if ($diasAtraso >= 30) {

                    $rowClass = 'alert-critical';

                    $badgeDias = 'badge-danger-modern';

                    } else {

                    $rowClass = 'alert-warning';

                    $badgeDias = 'badge-warning-modern';

                    }

                    $cumplimiento =
                    (float) $item['cumplimiento'];

                    @endphp


                    <tr class="alert-row {{ $rowClass }}">


                        {{-- OT --}}

                        <td>

                            <span class="ot-number">

                                #{{ $item['ot']->nro_ot }}

                            </span>

                        </td>


                        {{-- CODIGO --}}

                        <td>

                            <code>

                                {{ $item['ot']->codigo }}

                            </code>

                        </td>


                        {{-- DESCRIPCIÓN --}}

                        <td class="product-description-cell">

                            <span class="product-description">

                                {{ $item['ot']->descripcion }}

                            </span>

                        </td>


                        {{-- PROCESO --}}

                        <td>

                            <span class="status-badge badge-process">

                                {{ $item['ultimo_proceso'] }}

                            </span>

                        </td>


                        {{-- ORDENADO --}}

                        <td>

                            <strong>

                                {{ number_format(
                                        $item['cantidad_ordenada'],
                                        0,
                                        ',',
                                        '.'
                                    ) }}

                            </strong>

                        </td>


                        {{-- PRODUCIDO --}}

                        <td>

                            <strong>

                                {{ number_format(
                                        $item['cantidad_producida'],
                                        0,
                                        ',',
                                        '.'
                                    ) }}

                            </strong>

                        </td>


                        {{-- AVANCE --}}

                        <td>

                            <div class="progress-wrapper">

                                <div class="progress-label">

                                    <span class="text-muted">
                                        Avance
                                    </span>

                                    <strong>

                                        {{ number_format(
                                                $cumplimiento,
                                                1,
                                                ',',
                                                '.'
                                            ) }}%

                                    </strong>

                                </div>


                                <div class="modern-progress">

                                    <div
                                        class="progress-bar
                                            {{ $cumplimiento >= 100
                                                ? 'bg-success'
                                                : ($cumplimiento >= 50
                                                    ? 'bg-info'
                                                    : 'bg-warning') }}"
                                        style="
                                                width:
                                                {{ min(
                                                    $cumplimiento,
                                                    100
                                                ) }}%;
                                            ">

                                    </div>

                                </div>

                            </div>

                        </td>


                        {{-- DÍAS --}}

                        <td>

                            <span
                                class="status-badge {{ $badgeDias }}">

                                <i class="fas fa-clock mr-1"></i>

                                {{ number_format(
                                        $diasAtraso,
                                        1,
                                        ',',
                                        '.'
                                    ) }}

                                días

                            </span>

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="8">

                            <div class="empty-state">

                                <i class="fas fa-check-circle text-success"></i>

                                No existen OT atrasadas.

                            </div>

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    {{-- =========================================================
         ANÁLISIS POR DESCRIPCIÓN
    ========================================================== --}}

    <div class="section-card mb-4">

        <div class="section-header">

            <div class="section-title-wrapper">

                <div class="section-icon section-icon-blue">

                    <i class="fas fa-boxes"></i>

                </div>

                <div>

                    <div class="section-title">
                        Análisis por descripción
                    </div>

                    <div class="section-subtitle">
                        Productos con mayor cantidad de OT, volumen y atraso
                    </div>

                </div>

            </div>

        </div>


        <div class="table-responsive">

            <table class="table dashboard-table">

                <thead>

                    <tr>

                        <th>#</th>

                        <th>Descripción</th>

                        <th>OT</th>

                        <th>Ordenado</th>

                        <th>Producido</th>

                        <th>Cumplimiento</th>

                        <th>Promedio atraso</th>

                        <th>Mayor atraso</th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($porDescripcion->take(30) as $index => $item)

                    @php

                    $cumplimiento =
                    (float) $item['cumplimiento'];

                    if ($index === 0) {

                    $rankClass = 'top-1';

                    } elseif ($index === 1) {

                    $rankClass = 'top-2';

                    } elseif ($index === 2) {

                    $rankClass = 'top-3';

                    } else {

                    $rankClass = '';

                    }

                    if ($cumplimiento >= 100) {

                    $cumplimientoClass =
                    'badge-success-modern';

                    } elseif ($cumplimiento >= 80) {

                    $cumplimientoClass =
                    'badge-warning-modern';

                    } else {

                    $cumplimientoClass =
                    'badge-danger-modern';

                    }

                    @endphp


                    <tr>


                        {{-- RANK --}}

                        <td>

                            <span class="rank-number {{ $rankClass }}">

                                {{ $index + 1 }}

                            </span>

                        </td>


                        {{-- DESCRIPCIÓN --}}

                        <td class="product-description-cell">

                            <span class="product-description">

                                {{ $item['descripcion'] }}

                            </span>

                        </td>


                        {{-- OT --}}

                        <td>

                            <strong>

                                {{ number_format(
                                        $item['cantidad_ot'],
                                        0,
                                        ',',
                                        '.'
                                    ) }}

                            </strong>

                        </td>


                        {{-- ORDENADO --}}

                        <td>

                            {{ number_format(
                                    $item['cantidad_ordenada'],
                                    0,
                                    ',',
                                    '.'
                                ) }}

                        </td>


                        {{-- PRODUCIDO --}}

                        <td>

                            {{ number_format(
                                    $item['cantidad_producida'],
                                    0,
                                    ',',
                                    '.'
                                ) }}

                        </td>


                        {{-- CUMPLIMIENTO --}}

                        <td>

                            <span
                                class="status-badge
                                    {{ $cumplimientoClass }}">

                                {{ number_format(
                                        $cumplimiento,
                                        1,
                                        ',',
                                        '.'
                                    ) }}%

                            </span>

                        </td>


                        {{-- PROMEDIO --}}

                        <td>

                            {{ number_format(
                                    $item['promedio_atraso'],
                                    1,
                                    ',',
                                    '.'
                                ) }}

                            días

                        </td>


                        {{-- MAYOR --}}

                        <td>

                            <span class="font-weight-bold">

                                {{ number_format(
                                        $item['mayor_atraso'],
                                        1,
                                        ',',
                                        '.'
                                    ) }}

                                días

                            </span>

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="8">

                            <div class="empty-state">

                                <i class="fas fa-box-open"></i>

                                No existen datos por descripción.

                            </div>

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    {{-- =========================================================
         RESUMEN EJECUTIVO
    ========================================================== --}}

    <div class="row mb-4">


        {{-- MAYOR ATRASO --}}

        <div class="col-md-4 mb-3">

            <div class="summary-card summary-danger">

                <div class="summary-title">

                    <i class="fas fa-fire mr-1"></i>

                    Mayor atraso

                </div>

                <div class="summary-value">

                    {{ number_format(
                        $mayorAtraso,
                        2,
                        ',',
                        '.'
                    ) }}

                    <small>días</small>

                </div>

            </div>

        </div>


        {{-- PROMEDIO ATRASO --}}

        <div class="col-md-4 mb-3">

            <div class="summary-card summary-warning">

                <div class="summary-title">

                    <i class="fas fa-clock mr-1"></i>

                    Promedio atraso

                </div>

                <div class="summary-value">

                    {{ number_format(
                        $promedioAtraso,
                        2,
                        ',',
                        '.'
                    ) }}

                    <small>días</small>

                </div>

            </div>

        </div>


        {{-- CUMPLIMIENTO --}}

        <div class="col-md-4 mb-3">

            <div class="summary-card summary-primary">

                <div class="summary-title">

                    <i class="fas fa-chart-line mr-1"></i>

                    Cumplimiento general

                </div>

                <div class="summary-value">

                    {{ number_format(
                        $cumplimientoGeneral,
                        2,
                        ',',
                        '.'
                    ) }}%

                </div>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     CHART.JS
========================================================= --}}

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script>
    document.addEventListener('DOMContentLoaded', function() {


        /* =========================================================
           DATOS
        ========================================================= */

        const procesosLabels = @json(
            $otsPorProceso -> keys() -> values()
        );

        const procesosData = @json(
            $otsPorProceso -> values()
        );


        const produccionLabels = @json(
            $produccionDiariaFinal -> keys() -> values()
        );

        const produccionData = @json(
            $produccionDiariaFinal -> values()
        );


        /* =========================================================
           PLUGIN PARA MOSTRAR VALORES
        ========================================================= */

        const mostrarValores = {

            id: 'mostrarValores',

            afterDatasetsDraw(chart) {

                const {
                    ctx
                } = chart;

                chart.data.datasets.forEach(
                    (dataset, datasetIndex) => {

                        const meta =
                            chart.getDatasetMeta(datasetIndex);

                        meta.data.forEach(
                            (element, index) => {

                                const value =
                                    dataset.data[index];

                                if (
                                    value === null ||
                                    value === undefined
                                ) {
                                    return;
                                }

                                ctx.save();

                                ctx.font =
                                    '700 10px Arial';

                                ctx.fillStyle =
                                    '#475569';

                                ctx.textAlign =
                                    'center';

                                ctx.textBaseline =
                                    'bottom';

                                ctx.fillText(
                                    Number(value).toLocaleString(
                                        'es-PY'
                                    ),
                                    element.x,
                                    element.y - 7
                                );

                                ctx.restore();

                            }
                        );

                    }
                );

            }

        };


        /* =========================================================
           GRÁFICO OT POR PROCESO
        ========================================================= */

        const canvasProcesos =
            document.getElementById(
                'graficoProcesos'
            );


        if (canvasProcesos) {

            new Chart(
                canvasProcesos, {

                    type: 'bar',

                    plugins: [
                        mostrarValores
                    ],

                    data: {

                        labels: procesosLabels,

                        datasets: [

                            {

                                label: 'Cantidad de OT',

                                data: procesosData,

                                borderRadius: 7,

                                borderSkipped: false,

                                maxBarThickness: 48,

                                backgroundColor: 'rgba(37,99,235,.78)',

                                hoverBackgroundColor: '#2563eb'

                            }

                        ]

                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        layout: {

                            padding: {

                                top: 25

                            }

                        },

                        interaction: {

                            intersect: false,

                            mode: 'index'

                        },

                        plugins: {

                            legend: {

                                display: false

                            },

                            tooltip: {

                                backgroundColor: '#172033',

                                titleFont: {

                                    size: 12

                                },

                                bodyFont: {

                                    size: 12

                                },

                                padding: 12,

                                cornerRadius: 8,

                                displayColors: false,

                                callbacks: {

                                    label: function(context) {

                                        return (
                                            ' OT: ' +
                                            Number(
                                                context.raw
                                            ).toLocaleString(
                                                'es-PY'
                                            )
                                        );

                                    }

                                }

                            }

                        },

                        scales: {

                            x: {

                                grid: {

                                    display: false

                                },

                                ticks: {

                                    font: {

                                        size: 9

                                    },

                                    color: '#7b8497',

                                    maxRotation: 35,

                                    minRotation: 0

                                }

                            },

                            y: {

                                beginAtZero: true,

                                grid: {

                                    color: '#edf0f5'

                                },

                                ticks: {

                                    precision: 0,

                                    color: '#7b8497'

                                }

                            }

                        }

                    }

                }
            );

        }


        /* =========================================================
           GRÁFICO PRODUCCIÓN DIARIA
        ========================================================= */

        const canvasProduccion =
            document.getElementById(
                'graficoProduccion'
            );


        if (canvasProduccion) {

            new Chart(
                canvasProduccion, {

                    type: 'line',

                    data: {

                        labels: produccionLabels,

                        datasets: [

                            {

                                label: 'Unidades producidas',

                                data: produccionData,

                                fill: true,

                                tension: .35,

                                borderWidth: 3,

                                pointRadius: 3,

                                pointHoverRadius: 6,

                                borderColor: '#16a34a',

                                backgroundColor: 'rgba(22,163,74,.10)',

                                pointBackgroundColor: '#16a34a',

                                pointBorderColor: '#ffffff',

                                pointBorderWidth: 2

                            }

                        ]

                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        interaction: {

                            intersect: false,

                            mode: 'index'

                        },

                        plugins: {

                            legend: {

                                position: 'top',

                                align: 'end',

                                labels: {

                                    boxWidth: 10,

                                    usePointStyle: true,

                                    font: {

                                        size: 10

                                    }

                                }

                            },

                            tooltip: {

                                backgroundColor: '#172033',

                                padding: 12,

                                cornerRadius: 8,

                                displayColors: false,

                                callbacks: {

                                    label: function(context) {

                                        return (
                                            ' Producción: ' +
                                            Number(
                                                context.raw
                                            ).toLocaleString(
                                                'es-PY'
                                            ) +
                                            ' unidades'
                                        );

                                    }

                                }

                            }

                        },

                        scales: {

                            x: {

                                grid: {

                                    display: false

                                },

                                ticks: {

                                    color: '#7b8497',

                                    font: {

                                        size: 9

                                    },

                                    maxRotation: 35

                                }

                            },

                            y: {

                                beginAtZero: true,

                                grid: {

                                    color: '#edf0f5'

                                },

                                ticks: {

                                    color: '#7b8497',

                                    callback: function(value) {

                                        return Number(
                                            value
                                        ).toLocaleString(
                                            'es-PY'
                                        );

                                    }

                                }

                            }

                        }

                    }

                }
            );

        }

    });
</script>

@endsection
