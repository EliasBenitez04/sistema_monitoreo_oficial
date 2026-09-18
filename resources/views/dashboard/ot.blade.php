<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Seguimiento OT</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary: #1f3a5f;
            --primary-dark: #162b46;
            --secondary: #64748b;
            --border: #e2e8f0;
            --background: #f5f7fa;
            --white: #ffffff;
            --success: #198754;
            --warning: #d99a00;
            --danger: #c62828;
            --info: #2563eb;
            --text: #1e293b;
            --muted: #64748b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--background);
            color: var(--text);
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 14px;
        }

        /* =========================================================
           CONTENEDOR
        ========================================================= */

        .dashboard-container {
            max-width: 1980px;
            margin: auto;
            padding: 25px 30px 50px;
        }

        /* =========================================================
           HEADER
        ========================================================= */

        .dashboard-header {
            background: var(--white);
            border: 1px solid var(--border);
            border-left: 5px solid var(--primary);
            padding: 22px 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
        }

        .dashboard-header h1 {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            color: var(--primary);
        }

        .dashboard-header p {
            margin: 5px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .search-box {
            display: flex;
            gap: 8px;
        }

        .search-box input {
            min-width: 190px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            height: 38px;
        }

        .btn-enterprise {
            background: var(--primary);
            border: 1px solid var(--primary);
            color: white;
            border-radius: 4px;
            padding: 7px 16px;
            font-weight: 600;
        }

        .btn-enterprise:hover {
            background: var(--primary-dark);
            color: white;
        }

        .btn-outline-enterprise {
            background: white;
            border: 1px solid #cbd5e1;
            color: #334155;
            border-radius: 4px;
            padding: 7px 14px;
        }

        /* =========================================================
           ALERTA
        ========================================================= */

        .system-alert {
            border-radius: 4px;
            border: 1px solid #f1d48a;
            background: #fffaf0;
            padding: 13px 16px;
            margin-bottom: 20px;
        }

        /* =========================================================
           OT IDENTIFICACION
        ========================================================= */

        .ot-header {
            background: white;
            border: 1px solid var(--border);
            box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
            margin-bottom: 18px;
        }

        .ot-header-main {
            padding: 22px 25px;
        }

        .ot-number {
            font-size: 27px;
            font-weight: 700;
            color: var(--primary);
            margin-right: 10px;
        }

        .ot-code {
            background: #eef2f7;
            color: #475569;
            border: 1px solid #d8e0e8;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .ot-description {
            margin-top: 9px;
            color: #475569;
            font-size: 14px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 700;
        }

        /* =========================================================
           AVANCE
        ========================================================= */

        .progress-section {
            border-top: 1px solid var(--border);
            padding: 18px 25px;
            background: #fafbfc;
        }

        .progress-title {
            display: flex;
            justify-content: space-between;
            margin-bottom: 7px;
            font-weight: 600;
            font-size: 13px;
        }

        .enterprise-progress {
            height: 9px;
            border-radius: 2px;
            background: #e8edf3;
            overflow: hidden;
        }

        .enterprise-progress .progress-bar {
            border-radius: 2px;
        }

        /* =========================================================
           KPI
        ========================================================= */

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .kpi {
            background: white;
            border: 1px solid var(--border);
            min-height: 112px;
            padding: 18px;
            position: relative;
            box-shadow: 0 1px 5px rgba(15, 23, 42, .03);
        }

        .kpi::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--primary);
        }

        .kpi-label {
            color: var(--muted);
            text-transform: uppercase;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .06em;
        }

        .kpi-value {
            font-size: 25px;
            font-weight: 700;
            margin-top: 8px;
            color: var(--primary);
        }

        .kpi-sub {
            color: #94a3b8;
            font-size: 11px;
            margin-top: 3px;
        }

        /* =========================================================
           SECCIONES
        ========================================================= */

        .section-card {
            background: white;
            border: 1px solid var(--border);
            box-shadow: 0 1px 6px rgba(15, 23, 42, .03);
            margin-bottom: 20px;
        }

        .section-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
        }

        .section-title i {
            margin-right: 7px;
        }

        .section-body {
            padding: 20px;
        }

        /* =========================================================
           RESUMEN EJECUTIVO
        ========================================================= */

        .executive-summary {
            border-left: 4px solid var(--primary);
            background: #f8fafc;
            padding: 17px 20px;
            color: #475569;
            line-height: 1.7;
        }

        .executive-summary strong {
            color: var(--primary);
        }

        /* =========================================================
           ALERTAS ESTADO
        ========================================================= */

        .status-message {
            padding: 13px 16px;
            border: 1px solid;
            margin-top: 15px;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .status-message-danger {
            background: #fff5f5;
            border-color: #f1b5b5;
            color: #842029;
        }

        .status-message-success {
            background: #f1faf4;
            border-color: #b9dfc5;
            color: #146c43;
        }

        .status-message-secondary {
            background: #f5f6f7;
            border-color: #d6d9dc;
            color: #495057;
        }

        /* =========================================================
           GRAFICOS
        ========================================================= */

        .chart-container {
            height: 320px;
            position: relative;
        }

        /* =========================================================
           TABLA
        ========================================================= */

        .enterprise-table {
            margin: 0;
        }

        .enterprise-table thead th {
            background: #f1f5f9;
            color: #475569;
            border-bottom: 1px solid #cbd5e1;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .04em;
            font-weight: 700;
            padding: 12px;
            white-space: nowrap;
        }

        .enterprise-table tbody td {
            padding: 11px 12px;
            border-bottom: 1px solid #edf0f3;
            vertical-align: middle;
        }

        .enterprise-table tbody tr:hover {
            background: #f8fafc;
        }

        /* =========================================================
           BADGES
        ========================================================= */

        .enterprise-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-process {
            background: #e8f0fe;
            color: #1d4ed8;
        }

        .badge-success {
            background: #e8f7ee;
            color: #147a42;
        }

        .badge-warning {
            background: #fff5d9;
            color: #946200;
        }

        .badge-secondary {
            background: #edf0f2;
            color: #59636e;
        }

        /* =========================================================
           TIMELINE
        ========================================================= */

        .timeline {
            position: relative;
            padding-left: 28px;
        }

        .timeline::before {
            content: "";
            position: absolute;
            left: 7px;
            top: 5px;
            bottom: 5px;
            width: 1px;
            background: #cbd5e1;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 22px;
        }

        .timeline-item::before {
            content: "";
            position: absolute;
            left: -25px;
            top: 3px;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--primary);
            border: 2px solid white;
            box-shadow: 0 0 0 1px #94a3b8;
        }

        .timeline-item.is-last::before {
            background: var(--success);
        }

        .timeline-item.is-suspended::before {
            background: #64748b;
        }

        .timeline-process {
            font-weight: 700;
            color: var(--primary);
        }

        .timeline-date {
            color: #94a3b8;
            font-size: 11px;
            margin-top: 3px;
        }

        /* =========================================================
           LOGISTICA
        ========================================================= */

        .logistica-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
        }

        .branch-card {
            border: 1px solid var(--border);
            padding: 15px;
            background: #fafbfc;
        }

        .branch-name {
            font-size: 11px;
            color: var(--muted);
            text-transform: uppercase;
            font-weight: 700;
        }

        .branch-value {
            font-size: 25px;
            font-weight: 700;
            color: var(--primary);
            margin-top: 5px;
        }

        .logistica-total {
            border-left: 3px solid var(--success);
        }

        .logistica-diferencia {
            border-left: 3px solid var(--warning);
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1200px) {
            .kpi-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {

            .dashboard-container {
                padding: 15px;
            }

            .kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .search-box {
                width: 100%;
            }

            .search-box input {
                min-width: 0;
                flex: 1;
            }

            .dashboard-header {
                padding: 18px;
            }

            .ot-number {
                font-size: 22px;
            }
        }

        @media print {

            body {
                background: white;
            }

            .no-print {
                display: none !important;
            }

            .dashboard-container {
                padding: 0;
            }

            .section-card,
            .ot-header,
            .kpi {
                box-shadow: none !important;
            }

        }
    </style>

</head>

<body>

    <div class="dashboard-container">

        {{-- =========================================================
         HEADER
    ========================================================== --}}

        <div class="dashboard-header">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div>

                    <h1>
                        <i class="bi bi-clipboard2-data me-2"></i>
                        Seguimiento de Orden de Trabajo
                    </h1>

                    <p>
                        Control operativo, trazabilidad y seguimiento de producción
                    </p>

                </div>

                <div class="search-box no-print">

                    <form method="GET" action="{{ route('dashboard.ot') }}" class="d-flex gap-2">

                        <input type="text" name="nro_ot" value="{{ $nroOtBuscada ?? '' }}" class="form-control"
                            placeholder="N° de OT">

                        <button class="btn btn-enterprise">
                            <i class="bi bi-search me-1"></i>
                            Consultar
                        </button>

                        <button type="button" class="btn btn-outline-enterprise"
                            onclick="url = '{{ route('home') }}'; window.location.href = url;">

                            <i class="bi bi-arrow-left"></i>

                        </button>

                    </form>

                </div>

            </div>

        </div>


        {{-- =========================================================
         MENSAJE
    ========================================================== --}}

        @if ($mensaje)
            <div class="system-alert">

                <i class="bi bi-exclamation-triangle me-2"></i>

                {{ $mensaje }}

            </div>
        @endif


        @if ($ot && $resumen)


            {{-- =====================================================
             IDENTIFICACION OT
        ====================================================== --}}

            <div class="ot-header">

                <div class="ot-header-main">

                    <div class="row align-items-center">

                        <div class="col-lg-8">

                            <div class="d-flex align-items-center flex-wrap gap-2">

                                <span class="ot-number">
                                    OT N° {{ $ot->nro_ot }}
                                </span>

                                <span class="ot-code">
                                    {{ $ot->codigo }}
                                </span>

                                <span class="status-badge bg-{{ $resumen['estado_color'] }} text-white">
                                    {{ $resumen['estado_texto'] }}
                                </span>

                            </div>

                            <div class="ot-description">
                                {{ $ot->descripcion }}
                            </div>

                        </div>

                        <div class="col-lg-4 mt-3 mt-lg-0">

                            <div class="small text-muted">

                                <div>
                                    <strong>Inicio:</strong>
                                    {{ $resumen['fecha_inicio']->format('d/m/Y') }}
                                </div>

                                <div>
                                    <strong>Último Proceso:</strong>
                                    {{ $resumen['fecha_ultimo_proceso']->format('d/m/Y') }}
                                </div>

                                <div>
                                    <strong>Consulta:</strong>
                                    {{ $resumen['fecha_generacion_reporte']->format('d/m/Y H:i') }}
                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- AVANCE --}}

                <div class="progress-section">

                    <div class="progress-title">

                        <span>
                            Avance de la Orden de Trabajo
                        </span>

                        <strong>
                            {{ $resumen['avance'] }}%
                        </strong>

                    </div>

                    <div class="progress enterprise-progress">

                        <div class="progress-bar bg-{{ $resumen['avance_color'] }}"
                            style="width: {{ $resumen['avance'] }}%">
                        </div>

                    </div>

                </div>


                {{-- ESTADO DINAMICO --}}

                @if ($resumen['esta_demorada'])
                    <div class="status-message status-message-danger mx-4 mb-4">

                        <i class="bi bi-clock-history fs-5"></i>

                        <div>

                            <strong>OT DEMORADA</strong><br>

                            Sin movimientos durante
                            <strong>
                                {{ $resumen['dias_desde_ultimo_proceso'] }} días
                            </strong>.

                            Último proceso:
                            <strong>
                                {{ $resumen['ultimo_proceso'] }}
                            </strong>

                        </div>

                    </div>
                @elseif ($resumen['finalizada'])
                    <div class="status-message status-message-success mx-4 mb-4">

                        <i class="bi bi-check-circle fs-5"></i>

                        <div>

                            <strong>OT FINALIZADA</strong><br>

                            Completada hace
                            <strong>
                                {{ $resumen['dias_desde_ultimo_proceso'] }} días
                            </strong>.

                        </div>

                    </div>
                @elseif ($resumen['esta_suspendida'])
                    <div class="status-message status-message-secondary mx-4 mb-4">

                        <i class="bi bi-pause-circle fs-5"></i>

                        <div>

                            <strong>OT SUSPENDIDA</strong><br>

                            Suspensión registrada hace
                            <strong>
                                {{ $resumen['dias_desde_ultimo_proceso'] }} días
                            </strong>.

                        </div>

                    </div>
                @endif

            </div>


            {{-- =====================================================
             KPI
        ====================================================== --}}

            <div class="kpi-grid">

                <div class="kpi">

                    <div class="kpi-label">
                        Procesos
                    </div>

                    <div class="kpi-value">
                        {{ $resumen['cantidad_procesos'] }}
                    </div>

                    <div class="kpi-sub">
                        procesos registrados
                    </div>

                </div>


                <div class="kpi">

                    <div class="kpi-label">
                        Avance
                    </div>

                    <div class="kpi-value">
                        {{ $resumen['avance'] }}%
                    </div>

                    <div class="kpi-sub">
                        cumplimiento OT
                    </div>

                </div>


                <div class="kpi">

                    <div class="kpi-label">
                        Tiempo Total
                    </div>

                    <div class="kpi-value">
                        {{ number_format($resumen['tiempo_total_horas'], 1) }} h
                    </div>

                    <div class="kpi-sub">
                        {{ $resumen['tiempo_total_dias'] }} días
                    </div>

                </div>


                <div class="kpi">

                    <div class="kpi-label">

                        @if ($resumen['finalizada'])
                            Días Finalizada
                        @elseif ($resumen['esta_suspendida'])
                            Días Suspendida
                        @else
                            Sin Movimiento
                        @endif

                    </div>

                    <div class="kpi-value">

                        {{ $resumen['dias_desde_ultimo_proceso'] }}

                    </div>

                    <div class="kpi-sub">

                        @if ($resumen['finalizada'])
                            desde finalización
                        @elseif ($resumen['esta_suspendida'])
                            desde suspensión
                        @else
                            desde último proceso
                        @endif

                    </div>

                </div>


                <div class="kpi">

                    <div class="kpi-label">
                        Promedio
                    </div>

                    <div class="kpi-value">
                        {{ number_format($resumen['duracion_promedio_horas'], 1) }} h
                    </div>

                    <div class="kpi-sub">
                        por proceso
                    </div>

                </div>


                <div class="kpi">

                    <div class="kpi-label">
                        Último Proceso
                    </div>

                    <div class="kpi-value" style="font-size:16px;">
                        {{ $resumen['ultimo_proceso'] }}
                    </div>

                </div>

            </div>


            {{-- =====================================================
             RESUMEN EJECUTIVO
        ====================================================== --}}

            <div class="section-card">

                <div class="section-header">

                    <h5 class="section-title">

                        <i class="bi bi-file-earmark-bar-graph"></i>

                        Resumen Ejecutivo

                    </h5>

                </div>

                <div class="section-body">

                    <div class="executive-summary">

                        La OT lleva
                        <strong>
                            {{ $resumen['tiempo_total_dias'] }} días
                        </strong>

                        desde su primer proceso registrado,

                        equivalente a
                        <strong>
                            {{ number_format($resumen['tiempo_total_horas'], 2) }} horas
                        </strong>,

                        con un avance de
                        <strong>
                            {{ $resumen['avance'] }}%
                        </strong>

                        y estado

                        <strong>
                            {{ $resumen['estado_texto'] }}
                        </strong>.

                        @if ($resumen['proceso_mas_lento'])
                            El proceso de mayor duración fue

                            <strong>
                                {{ $resumen['proceso_mas_lento']['proceso'] }}
                            </strong>

                            con

                            <strong>
                                {{ number_format($resumen['proceso_mas_lento']['duracion_horas'], 2) }}
                                horas
                            </strong>.
                        @endif

                        @if ($resumen['finalizada'])
                            La OT se encuentra
                            <strong>finalizada</strong>.
                        @elseif ($resumen['esta_suspendida'])
                            La OT se encuentra
                            <strong>suspendida</strong>.
                        @elseif ($resumen['esta_demorada'])
                            La OT presenta
                            <strong>demora operativa</strong>.
                        @else
                            La OT continúa
                            <strong>en proceso</strong>.
                        @endif

                    </div>

                </div>

            </div>


            {{-- =====================================================
             GRAFICOS
        ====================================================== --}}

            <div class="row g-3 mb-4">

                <div class="col-lg-6">

                    <div class="section-card mb-0">

                        <div class="section-header">

                            <h5 class="section-title">

                                <i class="bi bi-bar-chart"></i>

                                Duración por Proceso

                            </h5>

                            <span class="text-muted small">
                                Horas
                            </span>

                        </div>

                        <div class="section-body">

                            <div class="chart-container">

                                <canvas id="chartTiempos"></canvas>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-lg-6">

                    <div class="section-card mb-0">

                        <div class="section-header">

                            <h5 class="section-title">

                                <i class="bi bi-graph-up"></i>

                                Avance Acumulado

                            </h5>

                            <span class="text-muted small">
                                Porcentaje
                            </span>

                        </div>

                        <div class="section-body">

                            <div class="chart-container">

                                <canvas id="chartProcesos"></canvas>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- =====================================================
 HISTORIAL + TIMELINE
====================================================== --}}

            {{-- =====================================================
 HISTORIAL + TIMELINE
====================================================== --}}

            <div class="row g-3">

                {{-- =====================================================
         TIMELINE
    ====================================================== --}}
                <div class="col-lg-4">

                    <div class="section-card">

                        <div class="section-header">

                            <h5 class="section-title">
                                <i class="bi bi-diagram-3"></i>
                                Secuencia de Procesos
                            </h5>

                        </div>

                        <div class="section-body">

                            <div class="timeline">

                                @foreach ($procesos as $p)
                                    <div
                                        class="timeline-item
                            @if ($p['es_suspendido'] ?? false) is-suspended
                            @elseif ($loop->last)
                                is-last @endif">

                                        <div class="timeline-process">
                                            {{ $p['proceso'] }}
                                        </div>

                                        <div class="timeline-date">
                                            {{ $p['fecha']->format('d/m/Y') }}
                                        </div>

                                        <div class="mt-2">

                                            <span class="enterprise-badge badge-process">
                                                {{ $p['resultado'] }}
                                            </span>

                                            <span class="enterprise-badge badge-secondary">
                                                {{ $p['avance_acumulado'] }}%
                                            </span>

                                        </div>

                                        @if (!$loop->first)
                                            <div class="small text-muted mt-2">
                                                +{{ number_format($p['duracion_horas'], 2) }}
                                                horas
                                            </div>
                                        @endif

                                    </div>
                                @endforeach

                            </div>

                        </div>

                    </div>

                </div>


                {{-- =====================================================
         COLUMNA DERECHA
         HISTORIAL + LOGÍSTICA
    ====================================================== --}}
                <div class="col-lg-8">

                    {{-- =================================================
             HISTORIAL DE TRAZABILIDAD
        ================================================== --}}

                    <div class="section-card">

                        <div class="section-header">

                            <h5 class="section-title">
                                <i class="bi bi-list-check"></i>
                                Historial de Trazabilidad
                            </h5>

                            <span class="text-muted small">
                                {{ count($procesos) }} registros
                            </span>

                        </div>

                        <div class="table-responsive">

                            <table class="table enterprise-table">

                                <thead>

                                    <tr>
                                        <th>#</th>
                                        <th>Proceso</th>
                                        <th>Resultado</th>
                                        <th>Fecha</th>
                                        <th>Duración</th>
                                        <th>Avance</th>
                                        @can('ot destroy')
                                            <th>Acciones</th>
                                        @endcan
                                    </tr>

                                </thead>

                                <tbody>

                                    @foreach ($procesos as $p)
                                        <tr>

                                            <td>
                                                {{ $loop->iteration }}
                                            </td>

                                            <td>
                                                <strong>
                                                    {{ $p['proceso'] }}
                                                </strong>
                                            </td>

                                            <td>

                                                <span class="enterprise-badge badge-process">
                                                    {{ $p['resultado'] }}
                                                </span>

                                            </td>

                                            <td>
                                                {{ $p['fecha']->format('d/m/Y') }}
                                            </td>

                                            <td>

                                                {{ number_format($p['duracion_horas'], 2) }} h

                                                <span class="text-muted">
                                                    ({{ $p['duracion_dias'] }} d)
                                                </span>

                                            </td>

                                            <td>

                                                @if ($p['es_suspendido'] ?? false)
                                                    <span class="enterprise-badge badge-secondary">
                                                        Suspendido
                                                    </span>
                                                @else
                                                    <span
                                                        class="enterprise-badge
                                            @if ($p['avance_acumulado'] >= 100) badge-success
                                            @elseif ($p['avance_acumulado'] >= 50)
                                                badge-process
                                            @else
                                                badge-warning @endif">

                                                        {{ $p['avance_acumulado'] }}%

                                                    </span>
                                                @endif

                                            </td>
                                            <td>
                                                <form
                                                    action="{{ route('ot.trazabilidad.destroy', $p['id_trazabilidad']) }}"
                                                    method="POST" class="form-eliminar-trazabilidad">

                                                    @csrf
                                                    @method('DELETE')

                                                    @can('ot destroy')
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-danger btn-eliminar-trazabilidad"
                                                            title="Eliminar proceso">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    @endcan

                                                </form>
                                            </td>

                                        </tr>
                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    </div>


                    {{-- =================================================
     DISTRIBUCIÓN LOGÍSTICA
================================================= --}}

                    @if ($ot->logisticaDetalle && $ot->logisticaDetalle->count() > 0)

                        <div class="section-card logistica-section">

                            <div class="section-header">

                                <h5 class="section-title">
                                    <i class="bi bi-truck"></i>
                                    Distribución Logística
                                </h5>

                                <span class="text-muted small">
                                    OT {{ $ot->nro_ot }}
                                </span>

                            </div>


                            <div class="section-body">

                                <p class="text-muted mb-3">
                                    Distribución de unidades por sucursal.
                                </p>


                                @php

                                    /*
            |--------------------------------------------------------------------------
            | PRODUCTO TERMINADO DE LA OT
            |--------------------------------------------------------------------------
            */

                                    $trazabilidadPT = $ot->trazabilidades
                                        ->where('proceso', 'TERMINACION - PRODUCTO TERMINADO')
                                        ->sortBy('fecha_proceso')
                                        ->last();

                                    $productoTerminado = (int) ($trazabilidadPT->resultado ?? 0);

                                    /*
            |--------------------------------------------------------------------------
            | TRAZABILIDADES DE LOGÍSTICA
            |--------------------------------------------------------------------------
            */

                                    $trazabilidadesLogistica = $ot->trazabilidades
                                        ->where('proceso', 'LOGISTICA - LOGISTICA Y DISTRIBUCION')
                                        ->sortBy('fecha_proceso');

                                    /*
            |--------------------------------------------------------------------------
            | TOTAL DISTRIBUIDO DE TODA LA OT
            |--------------------------------------------------------------------------
            |
            | Sumamos todas las distribuciones.
            |
            | Ejemplo:
            |
            | Distribución 1 = 351
            | Distribución 2 = 7
            |
            | Total OT = 358
            |
            */

                                    $totalDistribuidoOT = 0;

                                    foreach ($trazabilidadesLogistica as $trazabilidadGeneral) {
                                        $totalDistribuidoOT += (int) $ot->logisticaDetalle
                                            ->where('id_trazabilidad', $trazabilidadGeneral->id_trazabilidad)
                                            ->sum('cantidad');
                                    }

                                    /*
            |--------------------------------------------------------------------------
            | FALTANTE GENERAL DE LA OT
            |--------------------------------------------------------------------------
            */

                                    $faltanteOT = $productoTerminado - $totalDistribuidoOT;

                                    /*
            |--------------------------------------------------------------------------
            | ESTADO GENERAL
            |--------------------------------------------------------------------------
            */

                                    if ($faltanteOT > 0) {
                                        $estadoOT = 'PENDIENTE';
                                    } elseif ($faltanteOT < 0) {
                                        $estadoOT = 'EXCESO';
                                    } else {
                                        $estadoOT = 'COMPLETA';
                                    }

                                @endphp


                                {{-- ================================================================
             RESUMEN GENERAL DE LA OT
        ================================================================= --}}

                                <div class="logistica-grid mb-4">


                                    {{-- PRODUCTO TERMINADO --}}

                                    <div class="branch-card">

                                        <div class="branch-name">
                                            Producto Terminado
                                        </div>

                                        <div class="branch-value">
                                            {{ $productoTerminado }}
                                        </div>

                                    </div>


                                    {{-- TOTAL DISTRIBUIDO --}}

                                    <div class="branch-card logistica-total">

                                        <div class="branch-name">
                                            Total Distribuido OT
                                        </div>

                                        <div class="branch-value text-success">
                                            {{ $totalDistribuidoOT }}
                                        </div>

                                    </div>


                                    {{-- FALTANTE --}}

                                    <div class="branch-card logistica-diferencia">

                                        <div class="branch-name">
                                            {{ $faltanteOT > 0 ? 'Faltan' : ($faltanteOT < 0 ? 'Exceso' : 'Estado') }}
                                        </div>

                                        <div
                                            class="branch-value
                    {{ $faltanteOT > 0 ? 'text-danger' : ($faltanteOT == 0 ? 'text-success' : 'text-warning') }}">

                                            @if ($faltanteOT > 0)
                                                {{ $faltanteOT }}
                                            @elseif ($faltanteOT < 0)
                                                {{ abs($faltanteOT) }}
                                            @else
                                                COMPLETA
                                            @endif

                                        </div>

                                    </div>

                                </div>


                                {{-- ================================================================
             ESTADO GENERAL
        ================================================================= --}}

                                <div class="mb-4">

                                    @if ($faltanteOT > 0)
                                        <span class="text-danger font-weight-bold">
                                            <i class="bi bi-exclamation-circle"></i>
                                            Faltan {{ $faltanteOT }} unidades para completar la OT.
                                        </span>
                                    @elseif ($faltanteOT < 0)
                                        <span class="text-warning font-weight-bold">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            Existe un exceso de {{ abs($faltanteOT) }} unidades.
                                        </span>
                                    @else
                                        <span class="text-success font-weight-bold">
                                            <i class="bi bi-check-circle"></i>
                                            OT completa. Se distribuyeron todas las unidades.
                                        </span>
                                    @endif

                                </div>


                                {{-- ================================================================
             CADA DISTRIBUCIÓN
        ================================================================= --}}

                                @foreach ($trazabilidadesLogistica as $trazabilidad)
                                    @php

                                        /*
                |--------------------------------------------------------------------------
                | DETALLES SOLAMENTE DE ESTA DISTRIBUCIÓN
                |--------------------------------------------------------------------------
                */

                                        $detallesDistribucion = $ot->logisticaDetalle->where(
                                            'id_trazabilidad',
                                            $trazabilidad->id_trazabilidad,
                                        );

                                        /*
                |--------------------------------------------------------------------------
                | AGRUPAR POR SUCURSAL
                |--------------------------------------------------------------------------
                |
                | Si existen dos registros:
                |
                | Multi = 51
                | Multi = 1
                |
                | Se mostrará:
                |
                | Multi = 52
                |
                */

                                        $sucursalesAgrupadas = $detallesDistribucion
                                            ->groupBy('sucursal')
                                            ->map(function ($items) {
                                                return $items->sum(function ($item) {
                                                    return (int) $item->cantidad;
                                                });
                                            });

                                        /*
                |--------------------------------------------------------------------------
                | TOTAL DE ESTA DISTRIBUCIÓN
                |--------------------------------------------------------------------------
                */

                                        $totalDistribuido = (int) $sucursalesAgrupadas->sum();

                                        /*
                |--------------------------------------------------------------------------
                | RESULTADO ESPERADO DE ESTA DISTRIBUCIÓN
                |--------------------------------------------------------------------------
                */

                                        $resultadoEsperado = (int) ($trazabilidad->resultado ?? 0);

                                        /*
                |--------------------------------------------------------------------------
                | DIFERENCIA DE ESTA DISTRIBUCIÓN
                |--------------------------------------------------------------------------
                |
                | Esto solamente controla esta distribución.
                |
                */

                                        $diferencia = $resultadoEsperado - $totalDistribuido;

                                    @endphp


                                    {{-- ============================================================
                 CABECERA
            ============================================================= --}}

                                    <div class="mb-4">

                                        <div class="d-flex justify-content-between align-items-center mb-3">

                                            <div>

                                                <strong>
                                                    Distribución #{{ $trazabilidad->id_trazabilidad }}
                                                </strong>

                                                <span class="text-muted ml-2">

                                                    {{ \Carbon\Carbon::parse($trazabilidad->fecha_proceso)->format('d/m/Y') }}

                                                </span>

                                            </div>


                                            <div>

                                                <strong>
                                                    Total: {{ $totalDistribuido }}
                                                </strong>

                                            </div>

                                        </div>


                                        {{-- ========================================================
                     RESUMEN DE ESTA DISTRIBUCIÓN
                ========================================================= --}}

                                        <div class="mb-3">

                                            <span class="text-muted">
                                                Esperado:
                                            </span>

                                            <strong>
                                                {{ $resultadoEsperado }}
                                            </strong>


                                            <span class="ml-3 text-muted">
                                                Distribuido:
                                            </span>

                                            <strong>
                                                {{ $totalDistribuido }}
                                            </strong>


                                            @if ($diferencia > 0)
                                                <span class="ml-3 text-danger">
                                                    Faltan {{ $diferencia }}
                                                </span>
                                            @elseif ($diferencia < 0)
                                                <span class="ml-3 text-danger">
                                                    Exceso {{ abs($diferencia) }}
                                                </span>
                                            @else
                                                <span class="ml-3 text-success">
                                                    Correcto
                                                </span>
                                            @endif

                                        </div>


                                        {{-- ========================================================
                     SUCURSALES
                ========================================================= --}}

                                        <div class="logistica-grid">


                                            @foreach ($sucursalesAgrupadas as $sucursal => $cantidad)
                                                <div class="branch-card">

                                                    <div class="branch-name">
                                                        {{ $sucursal }}
                                                    </div>

                                                    <div class="branch-value">
                                                        {{ $cantidad }}
                                                    </div>

                                                </div>
                                            @endforeach


                                            {{-- ====================================================
                         TOTAL DISTRIBUIDO
                    ===================================================== --}}

                                            <div class="branch-card logistica-total">

                                                <div class="branch-name">
                                                    Total Distribuido
                                                </div>

                                                <div class="branch-value text-success">
                                                    {{ $totalDistribuido }}
                                                </div>

                                            </div>


                                            {{-- ====================================================
                         RESULTADO ESPERADO
                    ===================================================== --}}

                                            <div class="branch-card">

                                                <div class="branch-name">
                                                    Resultado Esperado
                                                </div>

                                                <div class="branch-value">
                                                    {{ $resultadoEsperado }}
                                                </div>

                                            </div>


                                            {{-- ====================================================
                         DIFERENCIA DE ESTA DISTRIBUCIÓN
                    ===================================================== --}}

                                            <div class="branch-card logistica-diferencia">

                                                <div class="branch-name">
                                                    Diferencia
                                                </div>

                                                <div
                                                    class="branch-value
                            {{ $diferencia > 0 ? 'text-danger' : ($diferencia == 0 ? 'text-success' : 'text-warning') }}">

                                                    @if ($diferencia > 0)
                                                        +{{ $diferencia }}
                                                    @elseif ($diferencia < 0)
                                                        {{ $diferencia }}
                                                    @else
                                                        0
                                                    @endif

                                                </div>

                                            </div>

                                        </div>

                                    </div>


                                    {{-- ============================================================
                 SEPARADOR
            ============================================================= --}}

                                    @if (!$loop->last)
                                        <hr class="my-4">
                                    @endif
                                @endforeach

                            </div>

                        </div>

                    @endif


                </div>

            </div>

            {{-- =========================================================
     CHARTS
========================================================= --}}

            @if ($ot && $resumen)
                <script>
                    const labels = @json($labels);
                    const duraciones = @json($duraciones);
                    const avances = @json($avancesAcumulados);

                    /* =========================================================
                       DURACIÓN
                    ========================================================= */

                    new Chart(
                        document.getElementById('chartTiempos'), {

                            type: 'bar',

                            data: {

                                labels: labels,

                                datasets: [{

                                    label: 'Días',

                                    data: duraciones,

                                    backgroundColor: '#1f3a5f',

                                    borderRadius: 2

                                }]

                            },

                            options: {

                                responsive: true,

                                maintainAspectRatio: false,

                                plugins: {

                                    legend: {
                                        display: false
                                    }

                                },

                                scales: {

                                    x: {

                                        grid: {
                                            display: false
                                        }

                                    },

                                    y: {

                                        beginAtZero: true,

                                        grid: {
                                            color: '#edf0f3'
                                        },

                                        title: {

                                            display: true,

                                            text: 'Horas'

                                        }

                                    }

                                }

                            }

                        }
                    );


                    /* =========================================================
                       AVANCE
                    ========================================================= */

                    new Chart(
                        document.getElementById('chartProcesos'), {

                            type: 'line',

                            data: {

                                labels: labels,

                                datasets: [{

                                    label: '% Avance',

                                    data: avances,

                                    borderColor: '#198754',

                                    backgroundColor: 'rgba(25,135,84,.08)',

                                    borderWidth: 2,

                                    pointRadius: 4,

                                    pointHoverRadius: 6,

                                    fill: true,

                                    tension: .25

                                }]

                            },

                            options: {

                                responsive: true,

                                maintainAspectRatio: false,

                                plugins: {

                                    legend: {
                                        display: false
                                    }

                                },

                                scales: {

                                    x: {

                                        grid: {
                                            display: false
                                        }

                                    },

                                    y: {

                                        beginAtZero: true,

                                        max: 100,

                                        grid: {
                                            color: '#edf0f3'
                                        },

                                        ticks: {

                                            callback: function(value) {

                                                return value + '%';

                                            }

                                        }

                                    }

                                }

                            }

                        }
                    );
                </script>
            @endif


            {{-- =========================================================
     CIERRE DEL IF PRINCIPAL
========================================================= --}}

        @endif


    </div>


    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            document.querySelectorAll('.btn-eliminar-trazabilidad').forEach(function(button) {

                button.addEventListener('click', function() {

                    const form = this.closest('.form-eliminar-trazabilidad');

                    Swal.fire({
                        title: '¿Eliminar proceso?',
                        text: 'Este registro de trazabilidad será eliminado.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {

                        if (result.isConfirmed) {
                            form.submit();
                        }

                    });

                });

            });

        });
    </script>
</body>

</html>
