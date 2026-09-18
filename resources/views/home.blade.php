@extends('layouts.app')

@section('content')
    <style>
        :root {

            --primary: #2563eb;
            --primary-dark: #1e40afc9;

            --success: #16a34a;
            --warning: #f38405;
            --danger: #dc2626;
            --info: #0891b2;

            --dark: #172033;
            --text: #334155;
            --muted: #64748b;

            --border: #e2e8f0;
            --background: #f4f6f9;
            --white: #ffffff;

        }


        /*
                |--------------------------------------------------------------------------
                | GENERAL
                |--------------------------------------------------------------------------
                */

        .content-wrapper {

            background: var(--background) !important;
            min-height: 100vh;

        }


        /*
                |--------------------------------------------------------------------------
                | HEADER
                |--------------------------------------------------------------------------
                */

        .enterprise-header {

            padding: 25px 0 22px;

        }


        .enterprise-header-content {

            display: flex;
            justify-content: space-between;
            align-items: center;

        }


        .enterprise-title {

            margin: 0;
            color: var(--dark);
            font-size: 25px;
            font-weight: 750;
            letter-spacing: -.5px;

        }


        .enterprise-subtitle {

            margin-top: 5px;
            color: var(--muted);
            font-size: 12px;

        }


        .system-status {

            display: inline-flex;
            align-items: center;
            gap: 7px;

            padding: 7px 12px;

            background: #ffffff;

            border: 1px solid var(--border);

            border-radius: 20px;

            color: #475569;

            font-size: 10px;
            font-weight: 700;

        }


        .system-status-dot {

            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: #16a34a;

        }


        /*
                |--------------------------------------------------------------------------
                | KPI
                |--------------------------------------------------------------------------
                */

        .kpi-card {

            position: relative;

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 13px;

            min-height: 135px;

            padding: 19px;

            overflow: hidden;

            box-shadow: 0 3px 14px rgba(15, 23, 42, .045);

            transition: all .2s ease;

        }


        .kpi-card:hover {

            transform: translateY(-3px);

            box-shadow: 0 9px 25px rgba(15, 23, 42, .08);

        }


        .kpi-label {

            color: var(--muted);

            font-size: 10px;
            font-weight: 750;

            text-transform: uppercase;

            letter-spacing: .5px;

        }


        .kpi-value {

            margin-top: 7px;

            color: var(--dark);

            font-size: 29px;

            line-height: 1;

            font-weight: 750;

        }


        .kpi-description {

            margin-top: 8px;

            color: #000000d5;

            font-size: 10px;

        }


        .kpi-icon {

            position: absolute;

            right: 18px;
            top: 17px;

            width: 39px;
            height: 39px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 10px;

            font-size: 15px;

        }


        .kpi-blue {

            background: #eff6ff;
            color: var(--primary);

        }


        .kpi-orange {

            background: #fff7ed;
            color: var(--warning);

        }


        .kpi-green {

            background: #f0fdf4;
            color: var(--success);

        }


        .kpi-cyan {

            background: #ecfeff;
            color: var(--info);

        }


        /*
                |--------------------------------------------------------------------------
                | CARDS
                |--------------------------------------------------------------------------
                */

        .enterprise-card {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 13px;

            overflow: hidden;

            box-shadow: 0 3px 14px rgba(15, 23, 42, .045);

            margin-bottom: 20px;

        }


        .enterprise-card-header {

            min-height: 59px;

            padding: 13px 17px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            border-bottom: 1px solid var(--border);

        }


        .enterprise-card-title {

            margin: 0;

            color: var(--dark);

            font-size: 13px;

            font-weight: 750;

        }


        .enterprise-card-subtitle {

            margin-top: 3px;

            color: #000000;

            font-size: 9px;

        }


        .enterprise-card-body {

            padding: 18px;

        }


        /*
                |--------------------------------------------------------------------------
                | WORKFLOW
                |--------------------------------------------------------------------------
                */

        .workflow {

            display: flex;

            align-items: center;

            justify-content: space-between;

        }


        .workflow-step {

            flex: 1;

            text-align: center;

        }


        .workflow-icon {

            width: 45px;
            height: 45px;

            margin: auto;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 50%;

            font-size: 15px;

        }


        .workflow-step strong {

            display: block;

            margin-top: 8px;

            color: var(--dark);

            font-size: 11px;

        }


        .workflow-step span {

            display: block;

            margin-top: 3px;

            color: var(--muted);

            font-size: 9px;

        }


        .workflow-line {

            width: 50px;
            height: 1px;

            background: #cbd5e1;

        }


        .workflow-generated {

            background: #fff7ed;
            color: var(--warning);

        }


        .workflow-process {

            background: #eff6ff;
            color: var(--primary);

        }


        .workflow-finished {

            background: #f0fdf4;
            color: var(--success);

        }


        /*
                |--------------------------------------------------------------------------
                | PROGRESS
                |--------------------------------------------------------------------------
                */

        .progress-item {

            margin-bottom: 17px;

        }


        .progress-item:last-child {

            margin-bottom: 0;

        }


        .progress-header {

            display: flex;

            justify-content: space-between;

            margin-bottom: 6px;

        }


        .progress-label {

            color: var(--text);

            font-size: 10px;

            font-weight: 650;

        }


        .progress-value {

            color: var(--dark);

            font-size: 10px;

            font-weight: 750;

        }


        .enterprise-progress {

            height: 7px !important;

            background: #f1f5f9 !important;

            border-radius: 10px !important;

        }


        .enterprise-progress .progress-bar {

            border-radius: 10px;

        }


        .progress-warning {

            background: #d97706 !important;

        }


        .progress-blue {

            background: #2563eb !important;

        }


        .progress-green {

            background: #16a34a !important;

        }


        /*
                |--------------------------------------------------------------------------
                | TABLE
                |--------------------------------------------------------------------------
                */

        .enterprise-table {

            width: 100%;

            border-collapse: collapse;

        }


        .enterprise-table th {

            padding: 10px 14px;

            background: #f8fafc;

            border-bottom: 1px solid var(--border);

            color: var(--dark);

            font-size: 10px;

            font-weight: 750;

            text-transform: uppercase;

            letter-spacing: .5px;

        }


        .enterprise-table td {

            padding: 11px 14px;

            border-bottom: 1px solid #edf1f5;

            color: var(--text);

            font-size: 12px;

            vertical-align: middle;

            text-align: center;

        }


        .enterprise-table tr:last-child td {

            border-bottom: 0;

        }


        .enterprise-table tbody tr:hover {

            background: #f8fafc;

        }


        .lote-code {

            color: var(--dark);

            font-weight: 750;

        }


        /*
                |--------------------------------------------------------------------------
                | BADGES
                |--------------------------------------------------------------------------
                */

        .badge-enterprise {

            display: inline-flex;

            align-items: center;

            padding: 4px 8px;

            border-radius: 20px;

            font-size: 8px;

            font-weight: 750;

        }


        .badge-generated {

            color: #92400e;
            background: #fef3c7;

        }


        .badge-process {

            color: #1e40af;
            background: #dbeafe;

        }


        .badge-finished {

            color: #166534;
            background: #dcfce7;

        }


        /*
                |--------------------------------------------------------------------------
                | AVANCE DEL LOTE
                |--------------------------------------------------------------------------
                */

        .lote-progress {

            min-width: 110px;

        }


        .lote-progress-header {

            display: flex;

            justify-content: space-between;

            margin-bottom: 6px;

        }


        .lote-progress-text {

            font-size: 10px;

            color: var(--dark);

        }


        .lote-progress-percent {

            font-size: 10px;

            font-weight: 750;

            color: var(--dark);

        }


        .lote-progress-bar {

            height: 6px;

            background: #f1f5f9;

            border-radius: 10px;

            overflow: hidden;

        }


        .lote-progress-fill {

            height: 100%;

            background: var(--success);

            border-radius: 10px;

        }


        /*
                |--------------------------------------------------------------------------
                | BUTTON
                |--------------------------------------------------------------------------
                */

        .enterprise-button {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 6px 9px;

            border: 1px solid var(--border);

            border-radius: 7px;

            background: white;

            color: var(--text);

            font-size: 9px;

            font-weight: 700;

            text-decoration: none !important;

        }


        .enterprise-button:hover {

            background: #f8fafc;

            color: var(--primary);

        }


        /*
                |--------------------------------------------------------------------------
                | ALERT
                |--------------------------------------------------------------------------
                */

        .operation-alert {

            display: flex;

            align-items: center;

            gap: 11px;

            padding: 12px;

            margin-bottom: 8px;

            border: 1px solid var(--border);

            border-radius: 9px;

            background: #fff;

        }


        .operation-alert:last-child {

            margin-bottom: 0;

        }


        .alert-icon {

            width: 35px;
            height: 35px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 8px;

            font-size: 13px;

        }


        .alert-generated {

            background: #fff7ed;

            color: var(--warning);

        }


        .alert-process {

            background: #eff6ff;

            color: var(--primary);

        }


        .alert-info {

            flex: 1;

        }


        .alert-title {

            color: var(--dark);

            font-size: 10px;

            font-weight: 750;

        }


        .alert-description {

            margin-top: 2px;

            color: var(--muted);

            font-size: 9px;

        }


        /*
                |--------------------------------------------------------------------------
                | SUMMARY BOX
                |--------------------------------------------------------------------------
                */

        .summary-box {

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 12px 0;

            border-bottom: 1px solid #edf1f5;

        }


        .summary-box:last-child {

            border-bottom: 0;

        }


        .summary-label {

            color: var(--muted);

            font-size: 10px;

        }


        .summary-value {

            color: var(--dark);

            font-size: 12px;

            font-weight: 750;

        }


        /*
                |--------------------------------------------------------------------------
                | RESPONSIVE
                |--------------------------------------------------------------------------
                */

        @media(max-width:768px) {

            .enterprise-header-content {

                align-items: flex-start;

                flex-direction: column;

                gap: 10px;

            }


            .workflow {

                flex-direction: column;

                gap: 12px;

            }


            .workflow-line {

                width: 1px;

                height: 25px;

            }

        }
    </style>


    <div class="container-fluid">


        {{-- ==========================================================
        HEADER
    =========================================================== --}}

        <div class="enterprise-header">

            <div class="enterprise-header-content">

                <div>

                    <h1 class="enterprise-title">

                        Centro de Operaciones

                    </h1>

                    <div class="enterprise-subtitle">

                        Panel ejecutivo de gestión y redistribución

                    </div>

                </div>


                <div class="system-status">

                    <span class="system-status-dot"></span>

                    Sistema operativo

                </div>

            </div>

        </div>


        {{-- ==========================================================
        KPI
    =========================================================== --}}

        <div class="row">


            {{-- TOTAL LOTES --}}

            <div class="col-lg-3 col-md-6 mb-4">

                <div class="kpi-card">

                    <div class="kpi-icon kpi-blue">

                        <i class="fas fa-layer-group"></i>

                    </div>

                    <div class="kpi-label">

                        Total de lotes

                    </div>

                    <div class="kpi-value">

                        {{ number_format($totalLotes) }}

                    </div>

                    <div class="kpi-description">

                        Lotes registrados

                    </div>

                </div>

            </div>


            {{-- PENDIENTES --}}

            <div class="col-lg-3 col-md-6 mb-4">

                <div class="kpi-card">

                    <div class="kpi-icon kpi-orange">

                        <i class="fas fa-clock"></i>

                    </div>

                    <div class="kpi-label">

                        Pendientes

                    </div>

                    <div class="kpi-value">

                        {{ number_format($lotesGenerados) }}

                    </div>

                    <div class="kpi-description">

                        Lotes esperando ejecución

                    </div>

                </div>

            </div>


            {{-- EN PROCESO --}}

            <div class="col-lg-3 col-md-6 mb-4">

                <div class="kpi-card">

                    <div class="kpi-icon kpi-cyan">

                        <i class="fas fa-sync-alt"></i>

                    </div>

                    <div class="kpi-label">

                        En proceso

                    </div>

                    <div class="kpi-value">

                        {{ number_format($lotesEnProceso) }}

                    </div>

                    <div class="kpi-description">

                        Lotes actualmente activos

                    </div>

                </div>

            </div>


            {{-- FINALIZADOS --}}

            <div class="col-lg-3 col-md-6 mb-4">

                <div class="kpi-card">

                    <div class="kpi-icon kpi-green">

                        <i class="fas fa-check-circle"></i>

                    </div>

                    <div class="kpi-label">

                        Finalizados

                    </div>

                    <div class="kpi-value">

                        {{ number_format($lotesFinalizados) }}

                    </div>

                    <div class="kpi-description">

                        Lotes completados

                    </div>

                </div>

            </div>

        </div>


        <div class="row">


            {{-- ======================================================
            COLUMNA IZQUIERDA
        ======================================================= --}}

            <div class="col-lg-8">


                {{-- FLUJO OPERATIVO --}}

                <div class="enterprise-card">

                    <div class="enterprise-card-header">

                        <div>

                            <div class="enterprise-card-title">

                                Flujo operativo

                            </div>

                            <div class="enterprise-card-subtitle">

                                Estado general de los lotes de redistribución

                            </div>

                        </div>

                    </div>


                    <div class="enterprise-card-body">

                        <div class="workflow">


                            <div class="workflow-step">

                                <div class="workflow-icon workflow-generated">

                                    <i class="fas fa-box"></i>

                                </div>

                                <strong>

                                    Generados

                                </strong>

                                <span>

                                    {{ number_format($lotesGenerados) }} lotes

                                </span>

                            </div>


                            <div class="workflow-line"></div>


                            <div class="workflow-step">

                                <div class="workflow-icon workflow-process">

                                    <i class="fas fa-cogs"></i>

                                </div>

                                <strong>

                                    En proceso

                                </strong>

                                <span>

                                    {{ number_format($lotesEnProceso) }} lotes

                                </span>

                            </div>


                            <div class="workflow-line"></div>


                            <div class="workflow-step">

                                <div class="workflow-icon workflow-finished">

                                    <i class="fas fa-check"></i>

                                </div>

                                <strong>

                                    Finalizados

                                </strong>

                                <span>

                                    {{ number_format($lotesFinalizados) }} lotes

                                </span>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- ACTIVIDAD RECIENTE --}}

                <div class="enterprise-card">

                    <div class="enterprise-card-header">

                        <div>

                            <div class="enterprise-card-title">

                                Actividad reciente

                            </div>

                            <div class="enterprise-card-subtitle">

                                Estado real de las transferencias asociadas a cada lote

                            </div>

                        </div>


                        <a href="{{ route('RedistribucionSugeridas.lotes') }}" class="enterprise-button">

                            Ver todos

                            <i class="fas fa-arrow-right"></i>

                        </a>

                    </div>


                    <div class="table-responsive">

                        <table class="enterprise-table">

                            <thead>

                                <tr>

                                    <th>Lote</th>

                                    <th>Fecha</th>

                                    {{-- <th>Transferencias</th> --}}

                                    <th>Unidades</th>

                                    <th>Finalizadas</th>

                                    <th>Diferencias</th>

                                    <th>Avance</th>

                                    <th>Estado</th>

                                    <th></th>

                                </tr>

                            </thead>


                            <tbody>

                                @forelse($ultimosLotes as $lote)
                                    <tr>


                                        {{-- LOTE --}}

                                        <td>

                                            <span class="lote-code">

                                                {{ $lote->numero_lote }}

                                            </span>

                                        </td>


                                        {{-- FECHA --}}

                                        <td>

                                            {{ \Carbon\Carbon::parse($lote->fecha_generacion)->format('d/m/Y H:i') }}

                                        </td>


                                        {{-- TRANSFERENCIAS --}}

                                        {{-- <td>

                                            <strong>

                                                {{ number_format($lote->cantidad_transferencias) }}

                                            </strong>

                                        </td> --}}


                                        {{-- UNIDADES --}}

                                        <td>

                                            {{ number_format($lote->cantidad_unidades) }}

                                        </td>


                                        {{-- FINALIZADAS --}}

                                        <td>

                                            <span style="color:#16a34a;font-weight:750;">

                                                {{ number_format($lote->unidades_finalizadas) }}
                                            </span>

                                        </td>

                                        {{-- DIFERENCIAS --}}
                                        <td>
                                            <span
                                                style="color: {{ $lote->cantidad_unidades - $lote->unidades_finalizadas > 0 ? '#ff0000' : '#000000' }};font-weight: 750;">
                                                {{ number_format($lote->cantidad_unidades - $lote->unidades_finalizadas) }}
                                            </span>
                                        </td>


                                        {{-- AVANCE --}}

                                        {{-- AVANCE --}}

                                        <td>
                                            @php
                                                $porcentajePrendas =
                                                    $lote->cantidad_unidades > 0
                                                        ? ($lote->unidades_finalizadas / $lote->cantidad_unidades) * 100
                                                        : 0;
                                            @endphp

                                            <div class="lote-progress">

                                                <div class="lote-progress-header">

                                                    <span class="lote-progress-text">
                                                        {{ number_format($lote->unidades_finalizadas) }}/{{ number_format($lote->cantidad_unidades) }}
                                                    </span>

                                                    <span class="lote-progress-percent">
                                                        {{ number_format($porcentajePrendas, 1) }}%
                                                    </span>

                                                </div>

                                                <div class="lote-progress-bar">

                                                    <div class="lote-progress-fill"
                                                        style="width: {{ min(100, $porcentajePrendas) }}%;">
                                                    </div>

                                                </div>

                                            </div>
                                        </td>

                                        {{-- ESTADO --}}

                                        <td>

                                            @if ($lote->estado === 'GENERADO')
                                                <span class="badge-enterprise badge-generated">

                                                    GENERADO

                                                </span>
                                            @elseif($lote->estado === 'EN PROCESO')
                                                <span class="badge-enterprise badge-process">

                                                    EN PROCESO

                                                </span>
                                            @elseif($lote->estado === 'FINALIZADO')
                                                <span class="badge-enterprise badge-finished">

                                                    FINALIZADO

                                                </span>
                                            @else
                                                <span class="badge-enterprise">

                                                    {{ $lote->estado }}

                                                </span>
                                            @endif

                                        </td>


                                        {{-- VER --}}

                                        <td class="text-right">

                                            <a href="{{ route('RedistribucionSugeridas.lote', ['id' => $lote->id]) }}"
                                                class="enterprise-button">

                                                <i class="fas fa-eye"></i>

                                            </a>

                                        </td>

                                    </tr>


                                @empty

                                    <tr>

                                        <td colspan="8" class="text-center py-4">

                                            <i class="fas fa-inbox" style="font-size:25px;color:#94a3b8;">
                                            </i>

                                            <div style="margin-top:8px;">

                                                No existen lotes registrados.

                                            </div>

                                        </td>

                                    </tr>
                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>


            </div>


            {{-- ======================================================
            COLUMNA DERECHA
        ======================================================= --}}

            <div class="col-lg-4">


                {{-- INDICADORES --}}

                <div class="enterprise-card">

                    <div class="enterprise-card-header">

                        <div>

                            <div class="enterprise-card-title">

                                Indicadores operativos

                            </div>

                            <div class="enterprise-card-subtitle">

                                Estado real de las transferencias de los lotes

                            </div>

                        </div>

                    </div>


                    <div class="enterprise-card-body">


                        {{-- PENDIENTES --}}

                        <div class="progress-item">

                            <div class="progress-header">

                                <span class="progress-label">

                                    Transferencias pendientes

                                </span>

                                <span class="progress-value">

                                    {{ number_format($transferenciasPendientes) }}

                                </span>

                            </div>


                            <div class="progress enterprise-progress">

                                <div class="progress-bar progress-warning"
                                    style="width: {{ $totalTransferencias > 0 ? ($transferenciasPendientes / $totalTransferencias) * 100 : 0 }}%;">
                                </div>

                            </div>

                        </div>


                        {{-- EN PROCESO --}}

                        <div class="progress-item">

                            <div class="progress-header">

                                <span class="progress-label">

                                    Transferencias en proceso

                                </span>

                                <span class="progress-value">

                                    {{ number_format($transferenciasEnProceso) }}

                                </span>

                            </div>


                            <div class="progress enterprise-progress">

                                <div class="progress-bar progress-blue"
                                    style="width: {{ $totalTransferencias > 0 ? ($transferenciasEnProceso / $totalTransferencias) * 100 : 0 }}%;">
                                </div>

                            </div>

                        </div>


                        {{-- FINALIZADAS --}}

                        <div class="progress-item">

                            <div class="progress-header">

                                <span class="progress-label">

                                    Transferencias finalizadas

                                </span>

                                <span class="progress-value">

                                    {{ number_format($transferenciasFinalizadas) }}

                                </span>

                            </div>


                            <div class="progress enterprise-progress">

                                <div class="progress-bar progress-green"
                                    style="width: {{ $totalTransferencias > 0 ? ($transferenciasFinalizadas / $totalTransferencias) * 100 : 0 }}%;">
                                </div>

                            </div>

                        </div>


                        <hr>


                        {{-- TOTAL UNIDADES --}}

                        <div class="summary-box">

                            <span class="summary-label">

                                Total de unidades

                            </span>

                            <span class="summary-value">

                                {{ number_format($totalUnidades) }}

                            </span>

                        </div>


                        {{-- UNIDADES FINALIZADAS --}}

                        <div class="summary-box">

                            <span class="summary-label">

                                Unidades finalizadas

                            </span>

                            <span class="summary-value" style="color:#16a34a;">

                                {{ number_format($unidadesFinalizadas) }}

                            </span>

                        </div>


                        {{-- PORCENTAJE --}}

                        <div class="summary-box">

                            <span class="summary-label">

                                Avance de unidades

                            </span>

                            <span class="summary-value">

                                {{ $porcentajeUnidadesFinalizadas }}%

                            </span>

                        </div>


                    </div>

                </div>


                {{-- ATENCIÓN --}}

                <div class="enterprise-card">

                    <div class="enterprise-card-header">

                        <div>

                            <div class="enterprise-card-title">

                                Atención requerida

                            </div>

                            <div class="enterprise-card-subtitle">

                                Lotes que requieren acción

                            </div>

                        </div>

                    </div>


                    <div class="enterprise-card-body">


                        @forelse($lotesAtencion as $lote)
                            <div class="operation-alert">


                                @if ($lote->estado === 'GENERADO')
                                    <div class="alert-icon alert-generated">

                                        <i class="fas fa-clock"></i>

                                    </div>
                                @else
                                    <div class="alert-icon alert-process">

                                        <i class="fas fa-sync-alt"></i>

                                    </div>
                                @endif


                                <div class="alert-info">

                                    <div class="alert-title">

                                        {{ $lote->numero_lote }}

                                    </div>


                                    <div class="alert-description">

                                        @if ($lote->estado === 'GENERADO')
                                            Pendiente de iniciar
                                        @else
                                            {{ $lote->transferencias_finalizadas }}
                                            de
                                            {{ $lote->cantidad_transferencias }}
                                            transferencias finalizadas
                                        @endif

                                    </div>

                                </div>


                                <a href="{{ route('RedistribucionSugeridas.lote', ['id' => $lote->id]) }}"
                                    class="enterprise-button">

                                    <i class="fas fa-arrow-right"></i>

                                </a>


                            </div>


                        @empty


                            <div class="text-center py-3">

                                <i class="fas fa-check-circle" style="font-size:25px;color:#16a34a;">
                                </i>

                                <div style="margin-top:8px;color:#64748b;font-size:10px;">

                                    No hay operaciones pendientes

                                </div>

                            </div>
                        @endforelse


                    </div>

                </div>


            </div>

        </div>


    </div>
@endsection
