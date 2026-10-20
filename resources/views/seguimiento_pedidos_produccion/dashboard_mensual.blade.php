@extends('layouts.app')
@section('content')
    <style>
        .prod-month-header {
            background: linear-gradient(115deg, #183153, #285587);
            color: #fff;
            border-radius: 12px;
            padding: 23px 26px;
            margin-bottom: 18px
        }

        .prod-month-header h1 {
            font-size: 1.65rem;
            font-weight: 700;
            margin: 0 0 7px
        }

        .prod-month-header .prod-subtitle {
            color: #dce8f8;
            font-size: .9rem;
            margin: 0
        }

        .prod-overview {
            border: 1px solid #e3e9f0;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            margin-bottom: 18px;
            box-shadow: 0 4px 15px rgba(20, 45, 72, .05)
        }

        .prod-overview-head {
            padding: 16px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e7edf3
        }

        .prod-kpi {
            padding: 19px 20px;
            height: 100%;
            border-right: 1px solid #e8edf3
        }

        .prod-kpi:last-child {
            border-right: 0
        }

        .prod-kpi-label {
            font-size: .76rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #64748b
        }

        .prod-kpi-value {
            font-size: 2rem;
            line-height: 1.22;
            font-weight: 750;
            margin: 6px 0;
            color: #1e293b
        }

        .prod-kpi-desc {
            font-size: .82rem;
            color: #64748b
        }

        .prod-kpi-value.good {
            color: #168052
        }

        .prod-kpi-value.bad {
            color: #b64040
        }

        .prod-progress {
            height: 13px;
            border-radius: 30px;
            overflow: hidden;
            background: #e9edf2;
            display: flex
        }

        .prod-progress>div {
            height: 100%
        }

        .prod-section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #24394f;
            margin-bottom: 6px
        }

        @media(max-width:767px) {
            .prod-kpi {
                border-right: 0;
                border-bottom: 1px solid #e8edf3
            }

            .prod-kpi:last-child {
                border-bottom: 0
            }

            .prod-month-header {
                padding: 18px
            }

            .prod-kpi-value {
                font-size: 1.7rem
            }
        }

        /* Presentación operativa: calendario, indicadores y tablas */
        .prod-calendar {
            border-collapse: separate;
            border-spacing: 5px;
            background: #f3f6fa
        }

        .prod-calendar th {
            background: #edf2f7;
            border: 0 !important;
            color: #526579;
            font-size: .76rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: 11px
        }

        .prod-calendar td {
            height: 143px;
            border: 1px solid #e5eaf1 !important;
            border-radius: 9px;
            background: #fff;
            padding: 10px !important;
            text-align: left;
            min-width: 112px
        }

        .prod-calendar td.prod-day-risk {
            background: #fff9f5;
            border-color: #f0d9c9 !important
        }

        .prod-calendar td.prod-day-idle {
            background: #f9fafb;
            color: #9aa5b1
        }

        .prod-day-number {
            font-size: .86rem;
            font-weight: 800;
            color: #334155;
            margin-bottom: 5px
        }

        .prod-day-count {
            font-weight: 800;
            color: #223850;
            font-size: 1.08rem
        }

        .prod-day-metric {
            font-size: .76rem;
            line-height: 1.6
        }

        .prod-day-qty {
            font-size: .76rem;
            color: #64748b;
            font-weight: 600;
            margin: 5px 0
        }

        .prod-section-card {
            border: 1px solid #e5eaf1 !important;
            border-radius: 11px !important;
            overflow: hidden;
            box-shadow: 0 3px 13px rgba(20, 45, 72, .04);
            margin-bottom: 18px
        }

        .prod-section-card>.card-header {
            background: #f8fafc !important;
            border-bottom: 1px solid #e5eaf1;
            padding: 15px 18px;
            font-weight: 700;
            color: #24394f
        }

        .prod-section-card>.card-header strong {
            font-size: 1rem
        }

        .prod-table {
            font-size: .86rem
        }

        .prod-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #edf2f7;
            color: #45596e;
            border: 0;
            white-space: nowrap;
            padding: 11px 12px
        }

        .prod-table tbody td {
            padding: 10px 12px;
            vertical-align: middle;
            border-top: 1px solid #edf1f5
        }

        .prod-table tbody tr:hover {
            background: #f2f7fb
        }

        .prod-table td:last-child {
            white-space: nowrap
        }

        .prod-stat {
            border: 1px solid #e5eaf1;
            border-radius: 9px;
            padding: 15px;
            background: #fff;
            height: 100%
        }

        .prod-stat small {
            display: block;
            color: #64748b;
            font-size: .78rem
        }

        .prod-stat h3 {
            font-size: 1.65rem;
            font-weight: 800;
            margin: 7px 0 0;
            color: #24394f
        }

        .prod-stage-bar {
            height: 5px;
            background: #e9eef4;
            border-radius: 9px;
            overflow: hidden;
            margin-top: 6px
        }

        .prod-stage-bar span {
            display: block;
            height: 100%;
            background: #547cae;
            border-radius: 9px
        }

        .prod-average-wrap {
            border: 1px solid #dbe7f2;
            border-radius: 12px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 5px 18px rgba(20, 45, 72, .06);
            margin-bottom: 18px
        }

        .prod-average-heading {
            background: linear-gradient(110deg, #eaf2fa, #f8fbff);
            padding: 17px 20px;
            border-bottom: 1px solid #dbe7f2
        }

        .prod-average-kpi {
            height: 100%;
            padding: 16px;
            border: 1px solid #e2eaf3;
            border-radius: 10px;
            background: #f9fbfd
        }

        .prod-average-kpi .value {
            font-size: 1.7rem;
            font-weight: 800;
            line-height: 1.2;
            color: #24394f;
            margin-top: 4px
        }

        .prod-average-kpi small {
            display: block;
            color: #64748b;
            font-size: .78rem
        }

        .prod-average-trend {
            height: 8px;
            border-radius: 20px;
            background: #e4edf7;
            min-width: 110px;
            overflow: hidden
        }

        .prod-average-trend span {
            height: 100%;
            display: block;
            background: #5484b4;
            border-radius: 20px
        }

        .prod-average-table tbody tr:last-child td {
            border-bottom: 1px solid #edf1f5
        }

        .prod-average-table tr.prod-date-future {
            color: #94a3b8;
            background: #f8fafc
        }

        .prod-delta-up,
        .prod-average-kpi .value.prod-delta-up {
            color: #12845d;
            font-weight: 700
        }

        .prod-delta-down,
        .prod-average-kpi .value.prod-delta-down {
            color: #c2413a;
            font-weight: 700
        }

        .prod-delta-even,
        .prod-average-kpi .value.prod-delta-even {
            color: #64748b;
            font-weight: 700
        }

        .prod-day-average {
            font-size: .73rem;
            font-weight: 700;
            color: #285587;
            line-height: 1.45
        }

        .prod-calendar td.prod-day-future {
            background: #f3f6f9;
            color: #94a3b8
        }

        .prod-chart-card>.card-header {
            background: #fff !important;
            padding: 14px 19px;
            gap: 12px
        }

        .prod-chart-card .prod-chart-switch .btn {
            min-width: 75px;
            font-size: .86rem;
            font-weight: 600
        }

        .prod-chart-card .prod-chart-switch .btn.active {
            box-shadow: none
        }

        .prod-day-filter {
            gap: 7px
        }

        .prod-day-filter-label {
            font-size: .76rem;
            color: #64748b;
            font-weight: 600;
            margin-right: 3px
        }

        .prod-day-filter .btn {
            font-size: .79rem;
            font-weight: 700
        }

        .prod-day-filter .btn.active {
            box-shadow: none
        }

        @media(max-width:575px) {
            .prod-day-filter {
                width: 100%;
                justify-content: flex-end
            }
        }

        .prod-chart-card .prod-chart-caption {
            font-size: .8rem;
            color: #64748b
        }

        .prod-chart-legend {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            font-size: .82rem;
            color: #536579;
            margin: 1px 0 10px
        }

        .prod-chart-key {
            display: inline-block;
            vertical-align: middle;
            width: 13px;
            height: 13px;
            border-radius: 3px;
            margin-right: 6px
        }

        .prod-chart-svg {
            display: block;
            width: 100%;
            max-height: 350px
        }

        .prod-chart-canvas {
            min-height: 260px;
            background: #fff;
            position: relative
        }

        .prod-chart-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 18px
        }

        .prod-chart-summary-item {
            border: 1px solid #e3ebf3;
            background: #f9fbfd;
            border-radius: 9px;
            padding: 11px 14px;
            min-width: 0
        }

        .prod-chart-summary-item small {
            display: block;
            color: #63758b;
            font-size: .72rem;
            margin-bottom: 3px
        }

        .prod-chart-summary-item strong {
            font-size: 1.15rem;
            color: #23364b;
            font-weight: 800
        }

        .prod-chart-summary-item strong.is-positive {
            color: #15815c
        }

        .prod-chart-summary-item strong.is-negative {
            color: #ba4141
        }

        .prod-chart-tooltip {
            position: absolute;
            z-index: 5;
            pointer-events: none;
            background: #183153;
            color: #fff;
            border-radius: 8px;
            padding: 11px 13px;
            font-size: .76rem;
            line-height: 1.7;
            white-space: pre-line;
            box-shadow: 0 6px 18px rgba(15, 35, 55, .22);
            max-width: 260px
        }

        .prod-chart-tooltip[hidden] {
            display: none
        }

        .prod-chart-chartlabel {
            font-size: .83rem;
            font-weight: 700;
            color: #28445f
        }

        .prod-chart-zoom-note {
            font-size: .73rem;
            color: #64748b;
            text-align: center;
            margin-top: 5px
        }

        @media(max-width:767px) {
            .prod-chart-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr))
            }

            .prod-chart-summary-item {
                padding: 9px
            }

            .prod-chart-summary-item strong {
                font-size: 1rem
            }
        }

        .prod-chart-donut-svg {
            display: block;
            margin: auto;
            max-width: 280px;
            width: 100%;
            height: auto
        }

        .prod-chart-donut-legend {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 22px;
            margin: 10px 0 8px;
            font-size: .88rem
        }

        .prod-chart-empty {
            color: #64748b;
            text-align: center;
            padding: 110px 10px
        }

        @media(max-width:575px) {
            .prod-chart-card>.card-header {
                padding: 12px
            }

            .prod-chart-svg {
                min-width: 490px
            }

            .prod-chart-scroll {
                overflow-x: auto
            }

            .prod-chart-card .prod-chart-switch .btn {
                min-width: 65px
            }
        }

        @media(max-width:768px) {
            .prod-calendar td {
                height: 120px;
                padding: 6px !important
            }

            .prod-day-count {
                font-size: .91rem
            }

            .prod-calendar {
                border-spacing: 2px
            }
        }
    </style>
    <section class="content">
        <div class="container-fluid">
            <div class="prod-month-header d-flex justify-content-between align-items-center flex-wrap">
                <div class="mr-3 mb-2">
                    <h1><i class="fas fa-chart-line mr-2"></i>Control mensual de Producción P</h1>
                    <p class="prod-subtitle">Hito: <strong>TERMINACION - INGRESO TERMINACION</strong> · Pedidos P y
                        prioridades</p>
                </div>
                <a href="{{ route('seguimiento-produccion.index') }}" class="btn btn-light btn-sm mb-2"><i
                        class="fas fa-arrow-left mr-1"></i>Volver a Producción</a>
            </div>
            <div class="prod-overview">
                <div class="prod-overview-head d-flex align-items-center justify-content-between flex-wrap">
                    <div class="mb-2 mb-md-0">
                        <h2 class="prod-section-title mb-1">Resumen ·
                            {{ ucfirst($mes->locale('es')->translatedFormat('F Y')) }}</h2>
                        <small class="text-muted"><i class="far fa-calendar-check mr-1"></i>Corte del calendario y proyección (último hito IT):
                            <strong>{{ $indicadores->fecha_corte ? $indicadores->fecha_corte->format('d/m/Y') : 'Sin hitos registrados del período' }}</strong></small>
                    </div>
                    <div class="btn-group btn-group-sm" aria-label="Navegar meses">
                        <a class="btn btn-outline-primary"
                            href="{{ route('seguimiento-produccion.dashboard-mensual', ['mes' => $mes->copy()->subMonth()->format('Y-m')]) }}"
                            title="Mes anterior"><i class="fas fa-chevron-left"></i></a>
                        <a class="btn btn-outline-primary"
                            href="{{ route('seguimiento-produccion.dashboard-mensual') }}">Último mes cargado</a>
                        <a class="btn btn-outline-success"
                            href="{{ route('seguimiento-produccion.dashboard-mensual', ['mes' => now()->format('Y-m')]) }}"
                            title="Consultar pendientes hasta el momento actual">Pendientes a hoy</a>
                        <a class="btn btn-outline-primary"
                            href="{{ route('seguimiento-produccion.dashboard-mensual', ['mes' => $mes->copy()->addMonth()->format('Y-m')]) }}"
                            title="Mes siguiente"><i class="fas fa-chevron-right"></i></a>
                    </div>
                </div>
                <div class="row no-gutters">
                    <div class="col-lg-3 col-sm-6">
                        <div class="prod-kpi">
                            <div class="prod-kpi-label">Promedio diario de OT</div>
                            <div class="prod-kpi-value">
                                {{ $indicadores->promedio_ot !== null ? number_format($indicadores->promedio_ot, 1, ',', '.') : '-' }}
                            </div>
                            <div class="prod-kpi-desc">{{ $indicadores->dias_transcurridos }} días hábiles transcurridos ·
                                {{ number_format($indicadores->ot_dia, 0, ',', '.') }} registros</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="prod-kpi">
                            <div class="prod-kpi-label">Promedio diario de prendas</div>
                            <div class="prod-kpi-value">
                                {{ $indicadores->promedio !== null ? number_format($indicadores->promedio, 1, ',', '.') : '-' }}
                            </div>
                            <div class="prod-kpi-desc">{{ number_format($indicadores->prendas, 0, ',', '.') }} prendas
                                acumuladas · {{ $indicadores->dias_habiles_mes }} días hábiles del mes</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="prod-kpi">
                            <div class="prod-kpi-label">Con pedido P</div>
                            <div class="prod-kpi-value good">{{ number_format($indicadores->con, 0, ',', '.') }}</div>
                            <div class="prod-kpi-desc"><strong>{{ number_format($indicadores->ots_con_pedido, 0, ',', '.') }} OT distintas</strong>
                                · {{ number_format($indicadores->efectividad, 1, ',', '.') }}% de prendas</div>
                            <div class="prod-kpi-desc">{{ number_format($indicadores->registros_con_pedido, 0, ',', '.') }} movimientos OT/día</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="prod-kpi">
                            <div class="prod-kpi-label">Sin pedido P</div>
                            <div class="prod-kpi-value bad">{{ number_format($indicadores->sin, 0, ',', '.') }}</div>
                            <div class="prod-kpi-desc"><strong>{{ number_format($indicadores->ots_sin_pedido, 0, ',', '.') }} OT distintas</strong>
                                · {{ number_format($indicadores->prendas > 0 ? ($indicadores->sin * 100) / $indicadores->prendas : 0, 1, ',', '.') }}% de prendas</div>
                            <div class="prod-kpi-desc">{{ number_format($indicadores->registros_sin_pedido, 0, ',', '.') }} movimientos OT/día</div>
                        </div>
                    </div>
                </div>
                <div class="px-4 pt-2 pb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <small class="font-weight-bold text-success">Con pedido P</small>
                        <small class="font-weight-bold text-danger">Sin pedido P</small>
                    </div>
                    <div class="prod-progress">
                        <div class="bg-success" style="width:{{ $indicadores->efectividad }}%"></div>
                        <div class="bg-danger"
                            style="width:{{ $indicadores->prendas > 0 ? 100 - $indicadores->efectividad : 0 }}%"></div>
                    </div>
                    <p class="small text-muted mb-0 mt-2">Las prendas con pedido P más las prendas sin pedido P equivalen al
                        total ingresado. Una OT puede vincularse a un pedido de fecha posterior al movimiento.</p>
                </div>
            </div>
            {{-- Gráfico interactivo del mismo período y los mismos indicadores usados en la tabla. --}}
            @php
                $serieGraficaProduccion = $dias
                    ->filter(function ($dia) {
                        return $dia->registrado && $dia->proyeccion !== null;
                    })
                    ->map(function ($dia) {
                        return [
                            'fecha' => $dia->fecha,
                            'habil' => (bool) $dia->habil,
                            'proyeccion' => (int) $dia->proyeccion,
                            'anterior' => $dia->proyeccion_anterior !== null ? (int) $dia->proyeccion_anterior : null,
                            'prendas' => (int) $dia->prendas,
                            'acumulado' => (int) $dia->acumulado,
                            'promedio' => $dia->promedio !== null ? (float) $dia->promedio : null,
                            'prendas_ayer' => $dia->prendas_anterior !== null ? (int) $dia->prendas_anterior : null,
                            'dias_habiles' => (int) $dia->dias_transcurridos,
                        ];
                    })
                    ->values()
                    ->all();
            @endphp
            <div class="card prod-section-card prod-chart-card" id="produccionGraficos">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <strong><i class="fas fa-chart-pie text-primary mr-2"></i>Gráficos de Producción</strong>
                        <div class="prod-chart-caption font-weight-normal mt-1">Comparación de producción real contra el
                            promedio diario y seguimiento de la proyección del mes.</div>
                    </div>
                    <div id="prodFiltroTipoDia" class="d-flex align-items-center flex-wrap prod-day-filter ml-auto mr-2"
                        role="group" aria-label="Filtrar fechas del gráfico">
                        <span class="prod-day-filter-label">Días:</span>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Tipo de día">
                            <button type="button" class="btn btn-primary active prod-day-filter-button"
                                data-filtro-dia="habil" aria-pressed="true"
                                title="Mostrar solo lunes a viernes">Hábil</button>
                            <button type="button" class="btn btn-outline-primary prod-day-filter-button"
                                data-filtro-dia="no-habil" aria-pressed="false"
                                title="Incluir sábados y domingos junto con los días hábiles">No hábil</button>
                        </div>
                    </div>
                    <div class="btn-group btn-group-sm prod-chart-switch" role="group"
                        aria-label="Cambiar tipo de gráfico">
                        <button type="button" class="btn btn-primary active prod-chart-switch-button" data-grafico="diario"
                            aria-pressed="true">Ritmo diario</button>
                        <button type="button" class="btn btn-outline-primary prod-chart-switch-button"
                            data-grafico="proyeccion" aria-pressed="false">Proyección</button>
                        <button type="button" class="btn btn-outline-primary prod-chart-switch-button" data-grafico="donut"
                            aria-pressed="false">Donut</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="prodGraficoArea">
                        <div class="prod-chart-summary" aria-live="polite">
                            <div class="prod-chart-summary-item"><small id="prodResumenEtiqueta1">Prendas del último
                                    día</small><strong id="prodResumenValor1">—</strong></div>
                            <div class="prod-chart-summary-item"><small id="prodResumenEtiqueta2">Prendas del día
                                    anterior</small><strong id="prodResumenValor2">—</strong></div>
                            <div class="prod-chart-summary-item"><small id="prodResumenEtiqueta3">Diferencia con
                                    ayer</small><strong id="prodResumenValor3">—</strong></div>
                            <div class="prod-chart-summary-item"><small id="prodResumenEtiqueta4">Promedio diario
                                    acumulado</small><strong id="prodResumenValor4">—</strong></div>
                        </div>
                        <div class="prod-chart-legend">
                            <span><span class="prod-chart-key" style="background:#3d91c2"></span><span
                                    id="prodLeyendaAzul">Prendas producidas ese día</span></span>
                            <span><span class="prod-chart-key" style="background:#c8ced8"></span><span
                                    id="prodLeyendaGris">Promedio por día hábil hasta esa fecha</span></span>
                        </div>
                        <div class="prod-chart-scroll">
                            <div class="prod-chart-canvas">
                                <svg id="prodGraficoAreaSvg" class="prod-chart-svg" viewBox="0 0 960 350" role="img"
                                    aria-label="Prendas reales producidas cada fecha comparadas con el promedio de producción por día hábil a esa fecha"></svg>
                                <div class="prod-chart-tooltip" id="prodGraficoTooltip" hidden></div>
                            </div>
                        </div>
                        <p id="prodDescripcionGrafico" class="small text-muted text-center mb-0 mt-2">Azul: producción
                            registrada en esa fecha. Gris: promedio diario acumulado. Son dos cifras en prendas comparables;
                            los fines de semana sin producción figuran en cero.</p>
                        <div id="prodNotaEscala" class="prod-chart-zoom-note d-none">Escala vertical ampliada para
                            apreciar las variaciones de la proyección; el eje no comienza en cero.</div>
                    </div>
                    <div id="prodGraficoDonut" class="d-none">
                        <svg id="prodGraficoDonutSvg" class="prod-chart-donut-svg" viewBox="0 0 280 280" role="img"
                            aria-label="Distribución real de prendas registradas con pedido P y sin pedido P"></svg>
                        <div class="prod-chart-donut-legend">
                            <span><span class="prod-chart-key" style="background:#3d91c2"></span>Con pedido P:
                                <strong>{{ number_format($indicadores->con, 0, ',', '.') }} prendas</strong>
                                · {{ number_format($indicadores->ots_con_pedido, 0, ',', '.') }} OT distintas</span>
                            <span><span class="prod-chart-key" style="background:#c8ced8"></span>Sin pedido P:
                                <strong>{{ number_format($indicadores->sin, 0, ',', '.') }} prendas</strong>
                                · {{ number_format($indicadores->ots_sin_pedido, 0, ',', '.') }} OT distintas</span>
                        </div>
                        <p class="small text-muted text-center mb-0">El donut muestra prendas realmente registradas hasta
                            el corte; no representa producción proyectada.</p>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h3 class="prod-section-title mb-1"><i class="far fa-calendar-alt mr-2 text-primary"></i>Calendario de
                        ingresos a Producción</h3>
                    <small class="text-muted">Cada día muestra OT procesadas, prendas ingresadas y asociación P. Seleccioná
                        «Ver OT» para consultar el detalle.</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table prod-calendar mb-0" style="min-width:840px">
                            <thead class="thead-light">
                                <tr>
                                    <th>Lun</th>
                                    <th>Mar</th>
                                    <th>Mié</th>
                                    <th>Jue</th>
                                    <th>Vie</th>
                                    <th>Sáb</th>
                                    <th>Dom</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $primerDia = $mes->copy()->startOfMonth();
                                    $desplazamiento = $primerDia->dayOfWeekIso - 1;
                                    $casillas = array_fill(0, $desplazamiento, null);
                                    foreach ($dias as $diaCalendario) {
                                        $casillas[] = $diaCalendario;
                                    }
                                    while (count($casillas) % 7 !== 0) {
                                        $casillas[] = null;
                                    }
                                @endphp
                                @foreach (array_chunk($casillas, 7) as $semana)
                                    <tr>
                                        @foreach ($semana as $dia)
                                            <td style="width:14.28%;vertical-align:top"
                                                class="{{ $dia && !$dia->registrado ? 'prod-day-future' : (!$dia || !$dia->total ? 'prod-day-idle' : ($dia->sin ? 'prod-day-risk' : '')) }}">
                                                @if ($dia)
                                                    <div class="prod-day-number">{{ $dia->numero }}</div>
                                                    @if ($dia->registrado && $dia->total)
                                                        <div class="prod-day-count">{{ $dia->total }} OT</div>
                                                        <div class="prod-day-metric text-success"><i
                                                                class="fas fa-check-circle mr-1"></i>{{ $dia->con }}
                                                            con P</div>
                                                        @if ($dia->sin)
                                                            <div class="prod-day-metric text-danger"><i
                                                                    class="fas fa-exclamation-circle mr-1"></i>{{ $dia->sin }}
                                                                sin P</div>
                                                        @endif
                                                        <div class="prod-day-qty">
                                                            {{ number_format($dia->prendas, 0, ',', '.') }} prendas</div>
                                                        <button type="button"
                                                            class="btn btn-outline-primary btn-sm mt-1 btn-block"
                                                            data-toggle="modal"
                                                            data-target="#detalleProduccion{{ $dia->numero }}">Ver
                                                            OT</button>
                                                    @elseif($dia->registrado)
                                                        <div class="small text-muted">Sin movimiento</div>
                                                    @else
                                                        <div class="small text-muted">Pendiente</div>
                                                    @endif
                                                    @if ($dia->registrado && $dia->proyeccion !== null)
                                                        <div class="prod-day-average mt-2">Prom.:
                                                            {{ number_format($dia->promedio, 1, ',', '.') }}/hábil</div>
                                                        <div class="prod-day-average">Cierre:
                                                            {{ number_format($dia->proyeccion, 0, ',', '.') }}</div>
                                                        @if ($dia->variacion_proyeccion !== null)
                                                            <div
                                                                class="small {{ $dia->variacion_proyeccion > 0 ? 'prod-delta-up' : ($dia->variacion_proyeccion < 0 ? 'prod-delta-down' : 'prod-delta-even') }}">
                                                                {{ $dia->variacion_proyeccion > 0 ? '+' : '' }}{{ number_format($dia->variacion_proyeccion, 0, ',', '.') }}
                                                                vs ayer
                                                            </div>
                                                        @endif
                                                    @elseif($dia->registrado)
                                                        <div class="small text-muted mt-2">Sin día hábil transcurrido</div>
                                                    @endif
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Detalle diario de las mismas OT y prendas que utiliza el calendario. --}}
            @foreach ($dias as $diaDetalle)
                @if ($diaDetalle->total > 0)
                    <div class="modal fade" id="detalleProduccion{{ $diaDetalle->numero }}" tabindex="-1"
                        role="dialog" aria-labelledby="tituloDetalleProduccion{{ $diaDetalle->numero }}"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="tituloDetalleProduccion{{ $diaDetalle->numero }}">
                                        OT ingresadas a IT · {{ date('d/m/Y', strtotime($diaDetalle->fecha)) }}
                                    </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span
                                            aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <p class="small text-muted">Hito TERMINACION - INGRESO TERMINACION.
                                        {{ $diaDetalle->total }} OT; {{ $diaDetalle->con }} con pedido P y
                                        {{ $diaDetalle->sin }} sin pedido P.</p>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Nº OT</th>
                                                    <th>Descripción</th>
                                                    <th class="text-right">Cantidad ingresada</th>
                                                    <th>Pedido P</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($salidas->where('fecha', $diaDetalle->fecha) as $salidaDetalle)
                                                    <tr>
                                                        <td><strong>{{ $salidaDetalle->nro_ot }}</strong></td>
                                                        <td>{{ $salidaDetalle->descripcion }}</td>
                                                        <td class="text-right">
                                                            {{ number_format($salidaDetalle->prendas, 0, ',', '.') }}</td>
                                                        <td>{{ $salidaDetalle->pedidos ?: '-' }}</td>
                                                        <td><span
                                                                class="badge badge-{{ $salidaDetalle->justificada ? 'success' : 'danger' }}">{{ !$salidaDetalle->justificada ? 'SIN PEDIDO' : ($salidaDetalle->pedido_posterior ? 'PEDIDO POSTERIOR' : 'CON PEDIDO') }}</span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr class="font-weight-bold">
                                                    <td colspan="2">Total del día</td>
                                                    <td class="text-right">
                                                        {{ number_format($diaDetalle->prendas, 0, ',', '.') }}</td>
                                                    <td colspan="2"></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer"><button type="button" class="btn btn-secondary"
                                        data-dismiss="modal">Cerrar</button></div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach

            <div class="alert {{ $indicadores->pendientes_en_vivo ? 'alert-info' : 'alert-secondary' }} mb-3">
                <i class="fas fa-clock mr-1"></i>
                <strong>{{ $indicadores->pendientes_en_vivo ? 'Pendientes vigentes' : 'Pendientes al corte histórico' }}:</strong>
                @if($indicadores->fecha_corte_pendientes)
                    calculados hasta el
                    <strong>{{ $indicadores->fecha_corte_pendientes->format('d/m/Y H:i') }}</strong>.
                @else
                    no se calculan pendientes para meses futuros.
                @endif
                <span class="d-block small mt-1">
                    Este corte es independiente del último hito de Producción.
                    «Sin movimiento» mide desde el registro más reciente de la OT;
                    la prioridad mide la espera desde la fecha posterior entre pedido P y movimiento.
                    No confundir 25 días de un corte anterior con los días actuales.
                </span>
            </div>
            <div class="row">
                <div class="col-xl-5">
                    <div class="card prod-section-card">
                        <div class="card-header"><strong><i class="fas fa-layer-group text-primary mr-2"></i>Cuello de
                                botella · etapa de OT pendientes</strong></div>
                        <div class="table-responsive">
                            <table class="table prod-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Proceso actual</th>
                                        <th>OT</th>
                                        <th>Prendas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($porEtapa as $fila)
                                        <tr>
                                            <td>{{ $fila->etapa }}</td>
                                            <td>{{ $fila->ots }}</td>
                                            <td>{{ number_format($fila->prendas, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty<tr>
                                            <td colspan="3" class="text-muted">Sin OT pendientes</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-xl-7">
                    <div class="card prod-section-card">
                        <div class="card-header"><strong><i class="fas fa-clipboard-list text-primary mr-2"></i>OT
                                pendientes de pedidos P</strong></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-4"><small class="text-muted">OT pendientes</small>
                                    <h3>{{ number_format($indicadores->pendientes, 0, ',', '.') }}</h3>
                                </div>
                                <div class="col-4"><small class="text-muted">Prendas según etapa vigente</small>
                                    <h3>{{ number_format($indicadores->prendas_pendientes, 0, ',', '.') }}</h3>
                                </div>
                                <div class="col-4"><small class="text-muted">Urgentes · 15 días o más de espera P</small>
                                    <h3 class="text-danger">{{ number_format($indicadores->urgentes, 0, ',', '.') }}</h3>
                                </div>
                            </div>
                            <p class="small text-muted mb-0">
                                Los días sin movimiento real se calculan desde la fecha y hora de la última trazabilidad
                                hasta la fecha de corte indicada arriba. La espera del pedido P comienza en la fecha
                                posterior entre pedido y movimiento. Prioridad: normal menos de 8 días;
                                revisar desde 8 hasta menos de 15 días; urgente 15 días o más de espera.
                                Una OT deja de estar pendiente al registrar
                                TERMINACION - INGRESO TERMINACION, aunque cambie la cantidad.
                                Las prendas mostradas corresponden al resultado de la etapa vigente
                                seleccionada por fecha y jerarquía de producción.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card prod-section-card">
                <div class="card-header"><strong><i class="fas fa-exclamation-triangle text-warning mr-2"></i>Prioridades
                        y pendientes para intervenir</strong>
                    <div class="small text-muted font-weight-normal mt-1">
                        Ordenadas por días sin movimiento, de mayor a menor.
                        Corte: {{ $indicadores->fecha_corte_pendientes ? $indicadores->fecha_corte_pendientes->format('d/m/Y H:i') : 'no disponible' }}.
                        La prioridad se calcula según la espera del pedido P.
                    </div>
                </div>
                <div class="table-responsive" style="max-height:540px;overflow:auto">
                    <table class="table table-hover prod-table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>OT</th>
                                <th>Pedido P</th>
                                <th>Descripción</th>
                                <th>Etapa actual</th>
                                <th>Última actividad</th>
                                <th title="Días completos desde la última actividad hasta la fecha de corte del informe">Sin movimiento</th>
                                <th>Prendas OT</th>
                                <th>Ingresadas a IT</th>
                                <th>Cant. etapa vigente</th>
                                <th>Prioridad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendientes->sort(function($a, $b) { if ($a->dias_sin_movimiento === null) return $b->dias_sin_movimiento === null ? strcmp((string)$a->nro_ot, (string)$b->nro_ot) : 1; if ($b->dias_sin_movimiento === null) return -1; return ($b->dias_sin_movimiento <=> $a->dias_sin_movimiento) ?: strcmp((string)$a->nro_ot, (string)$b->nro_ot); }) as $fila)
                                <tr>
                                    <td><strong>{{ $fila->nro_ot }}</strong></td>
                                    <td>{{ $fila->pedidos }}</td>
                                    <td>{{ $fila->descripcion }}</td>
                                    <td>{{ $fila->etapa }}</td>
                                    <td>{{ $fila->ultima_fecha ? date('d/m/Y', strtotime($fila->ultima_fecha)) : '-' }}</td>
                                    <td>{{ $fila->dias_sin_movimiento !== null ? number_format((int) floor($fila->dias_sin_movimiento), 0, ',', '.') . ' días' : 'Sin datos' }}</td>
                                    <td>{{ number_format($fila->cantidad, 0, ',', '.') }}</td>
                                    <td>{{ number_format($fila->cantidad_ingresada, 0, ',', '.') }}</td>
                                    <td><strong>{{ number_format($fila->cantidad_pendiente, 0, ',', '.') }}</strong></td>
                                    <td><span
                                            class="badge badge-{{ $fila->prioridad === 'URGENTE' ? 'danger' : ($fila->prioridad === 'REVISAR' ? 'warning' : ($fila->prioridad === 'SIN DATOS' ? 'secondary' : 'success')) }}">{{ $fila->prioridad }}</span>
                                    </td>
                                </tr>
                            @empty<tr>
                                    <td colspan="10" class="text-center text-muted">Sin pendientes para este corte</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card prod-section-card">
                <div class="card-header"><strong><i class="fas fa-truck-loading text-primary mr-2"></i>Movimientos del
                        hito durante el mes · Asociación con pedidos P</strong></div>
                <div class="table-responsive" style="max-height:450px;overflow:auto">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>OT</th>
                                <th>Descripción</th>
                                <th>Prendas</th>
                                <th>Pedido P</th>
                                <th>Situación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($salidas as $fila)
                                <tr>
                                    <td>{{ date('d/m/Y', strtotime($fila->fecha)) }}</td>
                                    <td>{{ $fila->nro_ot }}</td>
                                    <td>{{ $fila->descripcion }}</td>
                                    <td>{{ number_format($fila->prendas, 0, ',', '.') }}</td>
                                    <td>{{ $fila->pedidos ?: '-' }}</td>
                                    <td><span
                                            class="badge badge-{{ $fila->justificada ? 'success' : 'danger' }}">{{ !$fila->justificada ? 'SIN PEDIDO' : ($fila->pedido_posterior ? 'PEDIDO POSTERIOR' : 'CON PEDIDO') }}</span>
                                    </td>
                                </tr>
                            @empty<tr>
                                    <td colspan="6" class="text-center text-muted">Sin movimientos registrados en el
                                        mes</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection


@push('page_scripts')
    <script>
        (function() {
            'use strict';
            const raiz = document.getElementById('produccionGraficos');
            if (!raiz) return;

            const registrosBase = @json($serieGraficaProduccion);
            let filtroDia = 'habil';
            let registros = registrosBase.filter(function(fila) {
                return fila.habil === true;
            });
            const cantidadConP = Number(@json($indicadores->con)) || 0;
            const cantidadSinP = Number(@json($indicadores->sin)) || 0;
            const namespaceSVG = 'http://www.w3.org/2000/svg';
            const formato = new Intl.NumberFormat('es-PY', {
                maximumFractionDigits: 0
            });

            function elementoSvg(etiqueta, atributos, texto) {
                const elemento = document.createElementNS(namespaceSVG, etiqueta);
                Object.keys(atributos || {}).forEach(function(nombre) {
                    elemento.setAttribute(nombre, atributos[nombre]);
                });
                if (texto !== undefined && texto !== null) {
                    elemento.textContent = texto;
                }
                return elemento;
            }

            function etiquetaGrafico(numero) {
                const magnitud = Math.abs(numero);
                if (magnitud >= 1000000) return (numero / 1000000).toFixed(1) + ' M';
                if (magnitud >= 1000) return (numero / 1000).toFixed(magnitud % 1000 === 0 ? 0 : 1) + ' mil';
                return formato.format(numero);
            }

            // Los dos modos comparten fechas; las magnitudes comparadas tienen
            // siempre la misma unidad dentro de cada gráfico.
            let modoArea = 'diario';

            function resumen(entradas) {
                entradas.forEach(function(entrada, indice) {
                    const numero = indice + 1;
                    document.getElementById('prodResumenEtiqueta' + numero).textContent = entrada[0];
                    const valor = document.getElementById('prodResumenValor' + numero);
                    valor.textContent = entrada[1];
                    valor.classList.remove('is-positive', 'is-negative');
                    if (entrada[2] === 'positivo') valor.classList.add('is-positive');
                    if (entrada[2] === 'negativo') valor.classList.add('is-negative');
                });
            }

            function numeroSignado(valor) {
                if (valor === null || valor === undefined) return '—';
                return (valor > 0 ? '+' : '') + formato.format(valor);
            }

            function fechaCorta(fecha) {
                return String(fecha).slice(8, 10) + '/' + String(fecha).slice(5, 7);
            }

            function dibujarArea(modo) {
                modoArea = modo;
                const svg = document.getElementById('prodGraficoAreaSvg');
                const tooltip = document.getElementById('prodGraficoTooltip');
                if (!svg) return;
                svg.replaceChildren();
                tooltip.hidden = true;

                const esDiario = modo === 'diario';
                const azulTitulo = esDiario ?
                    'Prendas ingresadas a IT cada día' :
                    'Proyección al cierre calculada cada día';
                const grisTitulo = esDiario ?
                    'Promedio acumulado por día hábil' :
                    'Primera proyección con movimiento';
                document.getElementById('prodLeyendaAzul').textContent = azulTitulo;
                document.getElementById('prodLeyendaGris').textContent = grisTitulo;
                document.getElementById('prodNotaEscala').classList.toggle('d-none', esDiario);
                document.getElementById('prodDescripcionGrafico').textContent = esDiario ?
                    'Azul: ingreso diario al hito IT. Gris: promedio acumulado por día hábil. Hábil muestra lunes a viernes; No hábil incluye también sábados y domingos. No cambia los cálculos.' :
                    'La línea azul indica la estimación de cada fecha visible y la gris la primera proyección con movimientos de ese tipo de día. La escala vertical ampliada está identificada.';

                svg.setAttribute('aria-label', esDiario ?
                    'Prendas ingresadas al hito de Producción por fecha comparadas con el promedio acumulado diario' :
                    'Proyección mensual histórica comparada con la primera proyección con movimiento');

                if (!registros.length) {
                    resumen([
                        ['Último día', '—'],
                        ['Referencia', '—'],
                        ['Diferencia', '—'],
                        ['Promedio', '—']
                    ]);
                    svg.appendChild(elementoSvg('text', {
                        x: 480,
                        y: 170,
                        'text-anchor': 'middle',
                        fill: '#64748b',
                        'font-size': 19
                    }, 'No hay fechas de este tipo disponibles para graficar.'));
                    return;
                }

                // Evita usar una jornada sin movimientos como referencia de cierre.
                const indiceInicial = Math.max(0, registros.findIndex(function(fila) {
                    return Number(fila.proyeccion) > 0;
                }));
                const primero = registros[indiceInicial];
                const ultimo = registros[registros.length - 1];
                const inicial = Number(primero.proyeccion);
                // Comparar con la fecha anterior visible, no contra un fin de semana
                // oculto. No cambia los promedios ni las proyecciones calculadas.
                const ayer = registros.length > 1 ? Number(registros[registros.length - 2].prendas) : null;
                const produccion = Number(ultimo.prendas);
                const diferenciaDia = ayer === null ? null : produccion - ayer;
                const proyeccionAnterior = registros.length > 1 ?
                    Number(registros[registros.length - 2].proyeccion) : null;
                const diferenciaProyeccion = proyeccionAnterior === null ?
                    null : Number(ultimo.proyeccion) - proyeccionAnterior;

                if (esDiario) {
                    resumen([
                        ['Ingreso a IT · ' + fechaCorta(ultimo.fecha), formato.format(produccion)],
                        ['Fecha anterior visible', ayer === null ? '—' : formato.format(ayer)],
                        ['Diferencia vs ayer', numeroSignado(diferenciaDia),
                            diferenciaDia === null ? '' : (diferenciaDia > 0 ? 'positivo' : (diferenciaDia < 0 ?
                                'negativo' : ''))
                        ],
                        ['Promedio por día hábil', ultimo.promedio === null ? '—' : formato.format(ultimo
                            .promedio)]
                    ]);
                } else {
                    const deltaInicio = Number(ultimo.proyeccion) - inicial;
                    resumen([
                        ['Proyección actual', formato.format(ultimo.proyeccion)],
                        ['Proyección inicial', formato.format(inicial)],
                        ['Variación desde el inicio', numeroSignado(deltaInicio),
                            deltaInicio > 0 ? 'positivo' : (deltaInicio < 0 ? 'negativo' : '')
                        ],
                        ['Cambio vs fecha anterior', numeroSignado(diferenciaProyeccion),
                            diferenciaProyeccion === null ? '' : (diferenciaProyeccion > 0 ? 'positivo' : (
                                diferenciaProyeccion < 0 ? 'negativo' : ''))
                        ]
                    ]);
                }

                const azul = registros.map(function(fila) {
                    return esDiario ? Math.max(0, Number(fila.prendas)) : Math.max(0, Number(fila.proyeccion));
                });
                const gris = registros.map(function(fila, indice) {
                    return esDiario ?
                        (fila.promedio === null ? null : Math.max(0, Number(fila.promedio))) :
                        (indice >= indiceInicial ? inicial : null);
                });

                const izquierda = 88;
                const derecha = 926;
                const arriba = 25;
                const abajo = 303;
                const ancho = derecha - izquierda;
                const alto = abajo - arriba;
                const valores = azul.concat(gris.filter(function(valor) {
                    return valor !== null;
                }));
                const minimoDato = Math.min.apply(null, valores);
                const maximoDato = Math.max.apply(null, valores);
                const escala = Math.max(1, maximoDato - minimoDato);
                // Producción real siempre desde cero. La proyección usa escala
                // ampliada y explícitamente rotulada para destacar los desvíos.
                const inferior = esDiario ? 0 : Math.max(0, minimoDato - Math.max(500, escala * 0.35));
                const superior = esDiario ? Math.max(1, maximoDato * 1.12) :
                    maximoDato + Math.max(500, escala * 0.35);
                const ejeY = function(valor) {
                    return abajo - (Math.max(inferior, Number(valor)) - inferior) /
                        (superior - inferior) * alto;
                };
                const ejeX = function(indice) {
                    return izquierda + (registros.length === 1 ?
                        ancho / 2 : indice * ancho / (registros.length - 1));
                };

                svg.appendChild(elementoSvg('text', {
                    x: izquierda,
                    y: 14,
                    fill: '#66788c',
                    'font-size': 12
                }, esDiario ? 'Prendas por día' : 'Prendas proyectadas al cierre'));

                for (let paso = 0; paso <= 4; paso++) {
                    const valor = inferior + (superior - inferior) * paso / 4;
                    const y = ejeY(valor);
                    svg.appendChild(elementoSvg('line', {
                        x1: izquierda,
                        x2: derecha,
                        y1: y,
                        y2: y,
                        stroke: '#e4ebf2',
                        'stroke-width': 1
                    }));
                    svg.appendChild(elementoSvg('text', {
                        x: izquierda - 12,
                        y: y + 4,
                        'text-anchor': 'end',
                        fill: '#718298',
                        'font-size': 13
                    }, etiquetaGrafico(valor)));
                }

                const pasoEtiquetas = Math.max(1, Math.ceil(registros.length / 9));
                registros.forEach(function(fila, indice) {
                    if (indice % pasoEtiquetas && indice !== registros.length - 1) return;
                    svg.appendChild(elementoSvg('text', {
                        x: ejeX(indice),
                        y: 334,
                        'text-anchor': 'middle',
                        fill: '#718298',
                        'font-size': 13
                    }, fechaCorta(fila.fecha)));
                });

                function trayectoria(valoresSerie) {
                    let camino = '';
                    let activos = [];
                    const tramos = [];
                    valoresSerie.forEach(function(valor, i) {
                        if (valor === null) {
                            if (activos.length) tramos.push(activos);
                            activos = [];
                        } else {
                            activos.push({
                                x: ejeX(i),
                                y: ejeY(valor)
                            });
                        }
                    });
                    if (activos.length) tramos.push(activos);

                    return tramos.map(function(puntos) {
                        camino = 'M ' + puntos[0].x + ' ' + puntos[0].y;
                        for (let i = 1; i < puntos.length; i++) {
                            const previo = puntos[i - 1];
                            const punto = puntos[i];
                            const medio = (previo.x + punto.x) / 2;
                            camino += ' C ' + medio + ' ' + previo.y + ' ' +
                                medio + ' ' + punto.y + ' ' +
                                punto.x + ' ' + punto.y;
                        }
                        if (puntos.length === 1) camino += ' L ' + puntos[0].x + ' ' + puntos[0].y;
                        return {
                            linea: camino,
                            area: camino + ' L ' + puntos[puntos.length - 1].x + ' ' + abajo +
                                ' L ' + puntos[0].x + ' ' + abajo + ' Z'
                        };
                    });
                }

                const lineaGris = trayectoria(gris);
                const lineaAzul = trayectoria(azul);

                // El gris se dibuja primero y su línea se mantiene visible
                // por encima del relleno azul, evitando la superposición confusa.
                lineaGris.forEach(function(tramo) {
                    if (esDiario) svg.appendChild(elementoSvg('path', {
                        d: tramo.area,
                        fill: '#c8d1dc',
                        'fill-opacity': .30
                    }));
                });
                lineaAzul.forEach(function(tramo) {
                    svg.appendChild(elementoSvg('path', {
                        d: tramo.area,
                        fill: '#4799c7',
                        'fill-opacity': esDiario ? .33 : .25
                    }));
                });
                lineaGris.forEach(function(tramo) {
                    svg.appendChild(elementoSvg('path', {
                        d: tramo.linea,
                        fill: 'none',
                        stroke: '#9aa9bd',
                        'stroke-width': esDiario ? 3 : 2.7,
                        'stroke-dasharray': esDiario ? '7 5' : '9 6',
                        'stroke-linecap': 'round'
                    }));
                });
                lineaAzul.forEach(function(tramo) {
                    svg.appendChild(elementoSvg('path', {
                        d: tramo.linea,
                        fill: 'none',
                        stroke: '#2386bf',
                        'stroke-width': 3.3,
                        'stroke-linecap': 'round'
                    }));
                });

                const guia = elementoSvg('line', {
                    x1: 0,
                    x2: 0,
                    y1: arriba,
                    y2: abajo,
                    stroke: '#456d93',
                    'stroke-width': 1.3,
                    'stroke-dasharray': '4 5',
                    'pointer-events': 'none',
                    visibility: 'hidden'
                });
                const marca = elementoSvg('circle', {
                    cx: 0,
                    cy: 0,
                    r: 5.5,
                    fill: '#fff',
                    stroke: '#2386bf',
                    'stroke-width': 2.8,
                    'pointer-events': 'none',
                    visibility: 'hidden'
                });
                svg.appendChild(guia);
                svg.appendChild(marca);

                function mostrarPunto(indice, px) {
                    const fila = registros[indice];
                    const proyeccionHoy = Number(fila.proyeccion);
                    const anteriorVisible = indice > 0 ? registros[indice - 1] : null;
                    const diferenciaHoy = anteriorVisible === null ? null :
                        proyeccionHoy - Number(anteriorVisible.proyeccion);
                    const cambioPrendas = anteriorVisible === null ? null :
                        Number(fila.prendas) - Number(anteriorVisible.prendas);
                    const texto = esDiario ?
                        fechaCorta(fila.fecha) + ' · Ingreso diario a IT' +
                        '\nPrendas ingresadas: ' + formato.format(fila.prendas) +
                        '\nPromedio día hábil: ' + (fila.promedio === null ? '—' : formato.format(fila.promedio)) +
                        '\nFecha anterior visible: ' + (anteriorVisible === null ? '—' : formato.format(anteriorVisible
                            .prendas)) +
                        '\nDiferencia vs anterior: ' + numeroSignado(cambioPrendas) :
                        fechaCorta(fila.fecha) + ' · Proyección mensual' +
                        '\nEstimación de cierre: ' + formato.format(fila.proyeccion) +
                        '\nProyección inicial: ' + formato.format(inicial) +
                        '\nProyección anterior visible: ' + (anteriorVisible === null ? '—' : formato.format(
                            anteriorVisible.proyeccion)) +
                        '\nVariación vs anterior: ' + numeroSignado(diferenciaHoy) +
                        '\nProducción acumulada: ' + formato.format(fila.acumulado);
                    tooltip.textContent = texto;
                    tooltip.hidden = false;
                    const anchoTip = Math.min(260, tooltip.offsetWidth || 220);
                    tooltip.style.left = Math.max(5, Math.min(px + 10,
                        svg.getBoundingClientRect().width - anchoTip - 5)) + 'px';
                    tooltip.style.top = '50px';
                    const x = ejeX(indice);
                    guia.setAttribute('x1', x);
                    guia.setAttribute('x2', x);
                    guia.setAttribute('visibility', 'visible');
                    marca.setAttribute('cx', x);
                    marca.setAttribute('cy', ejeY(azul[indice]));
                    marca.setAttribute('visibility', 'visible');
                }

                svg.onpointermove = function(evento) {
                    const rect = svg.getBoundingClientRect();
                    if (!rect.width) return;
                    const px = evento.clientX - rect.left;
                    const x = px / rect.width * 960;
                    const indice = registros.length === 1 ? 0 :
                        Math.max(0, Math.min(registros.length - 1, Math.round(
                            (x - izquierda) / ancho * (registros.length - 1)
                        )));
                    mostrarPunto(indice, px);
                };
                svg.onpointerleave = function() {
                    tooltip.hidden = true;
                    guia.setAttribute('visibility', 'hidden');
                    marca.setAttribute('visibility', 'hidden');
                };
            }

            function dibujarDonut() {
                const svg = document.getElementById('prodGraficoDonutSvg');
                if (!svg) return;
                svg.replaceChildren();

                const total = Math.max(0, cantidadConP) + Math.max(0, cantidadSinP);
                const centro = 140;
                const radio = 92;
                const perimetro = 2 * Math.PI * radio;
                const proporcion = total > 0 ? Math.max(0, cantidadConP) / total : 0;
                const base = {
                    cx: centro,
                    cy: centro,
                    r: radio,
                    fill: 'none',
                    'stroke-width': 45,
                    transform: 'rotate(-90 140 140)'
                };

                svg.appendChild(elementoSvg('circle', Object.assign({}, base, {
                    stroke: '#ecf1f6'
                })));

                if (total > 0) {
                    const longitudCon = perimetro * proporcion;
                    const longitudSin = perimetro - longitudCon;

                    if (longitudCon > 0) {
                        const segmentoP = elementoSvg('circle', Object.assign({}, base, {
                            stroke: '#3d91c2',
                            'stroke-dasharray': longitudCon + ' ' + (perimetro - longitudCon)
                        }));
                        segmentoP.appendChild(elementoSvg('title', {},
                            'Con pedido P: ' + formato.format(cantidadConP) + ' prendas'));
                        svg.appendChild(segmentoP);
                    }
                    if (longitudSin > 0) {
                        const segmentoSin = elementoSvg('circle', Object.assign({}, base, {
                            stroke: '#c8ced8',
                            'stroke-dashoffset': -longitudCon,
                            'stroke-dasharray': longitudSin + ' ' + (perimetro - longitudSin)
                        }));
                        segmentoSin.appendChild(elementoSvg('title', {},
                            'Sin pedido P: ' + formato.format(cantidadSinP) + ' prendas'));
                        svg.appendChild(segmentoSin);
                    }
                }

                svg.appendChild(elementoSvg('text', {
                    x: centro,
                    y: 134,
                    'text-anchor': 'middle',
                    fill: '#23364b',
                    'font-size': 30,
                    'font-weight': 800
                }, total > 0 ? (proporcion * 100).toFixed(1).replace('.', ',') + '%' : 'Sin datos'));
                svg.appendChild(elementoSvg('text', {
                    x: centro,
                    y: 158,
                    'text-anchor': 'middle',
                    fill: '#718096',
                    'font-size': 14
                }, total > 0 ? 'Con pedido P' : 'Sin movimientos'));
            }

            dibujarArea('diario');
            dibujarDonut();

            const botonesFiltro = raiz.querySelectorAll('.prod-day-filter-button');
            botonesFiltro.forEach(function(boton) {
                boton.addEventListener('click', function() {
                    filtroDia = boton.getAttribute('data-filtro-dia');
                    registros = registrosBase.filter(function(fila) {
                        return filtroDia === 'no-habil' || fila.habil === true;
                    });
                    botonesFiltro.forEach(function(otro) {
                        const seleccionado = otro.getAttribute('data-filtro-dia') === filtroDia;
                        otro.classList.toggle('active', seleccionado);
                        otro.classList.toggle('btn-primary', seleccionado);
                        otro.classList.toggle('btn-outline-primary', !seleccionado);
                        otro.setAttribute('aria-pressed', seleccionado ? 'true' : 'false');
                    });
                    dibujarArea(modoArea);
                });
            });

            const botones = raiz.querySelectorAll('.prod-chart-switch-button');
            botones.forEach(function(boton) {
                boton.addEventListener('click', function() {
                    const modo = boton.getAttribute('data-grafico');
                    const esDonut = modo === 'donut';
                    raiz.querySelector('#prodFiltroTipoDia').classList.toggle('d-none', esDonut);
                    raiz.querySelector('#prodGraficoArea').classList.toggle('d-none', esDonut);
                    raiz.querySelector('#prodGraficoDonut').classList.toggle('d-none', !esDonut);

                    if (!esDonut && modoArea !== modo) {
                        dibujarArea(modo);
                    }
                    botones.forEach(function(otro) {
                        const seleccionado = otro.getAttribute('data-grafico') === modo;
                        otro.classList.toggle('active', seleccionado);
                        otro.classList.toggle('btn-primary', seleccionado);
                        otro.classList.toggle('btn-outline-primary', !seleccionado);
                        otro.setAttribute('aria-pressed', seleccionado ? 'true' : 'false');
                    });
                });
            });
        })();
    </script>
@endpush
