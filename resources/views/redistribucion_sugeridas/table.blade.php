<div class="card-body p-0 redistribucion-module">

    {{-- ========================================================= --}}
    {{-- KPIs --}}
    {{-- ========================================================= --}}

    <div class="px-4 pt-4 pb-2">
        <div class="row">

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="erp-kpi">
                    <div class="erp-kpi-icon primary">
                        <i class="fas fa-random"></i>
                    </div>

                    <div class="erp-kpi-info">
                        <span class="erp-kpi-label">SUGERENCIAS</span>

                        <strong class="erp-kpi-value">
                            {{ number_format($sugerencias->count()) }}
                        </strong>

                        <small>
                            Movimientos generados
                        </small>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="erp-kpi">
                    <div class="erp-kpi-icon warning">
                        <i class="fas fa-boxes"></i>
                    </div>

                    <div class="erp-kpi-info">
                        <span class="erp-kpi-label">UNIDADES</span>

                        <strong class="erp-kpi-value">
                            {{ number_format($sugerencias->sum('cantidad')) }}
                        </strong>

                        <small>
                            Unidades a redistribuir
                        </small>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="erp-kpi">
                    <div class="erp-kpi-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>

                    <div class="erp-kpi-info">
                        <span class="erp-kpi-label">PENDIENTES</span>

                        <strong class="erp-kpi-value">
                            {{ number_format($sugerencias->where('estado', 'PENDIENTE')->count()) }}
                        </strong>

                        <small>
                            Requieren aprobación
                        </small>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="erp-kpi">
                    <div class="erp-kpi-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>

                    <div class="erp-kpi-info">
                        <span class="erp-kpi-label">APROBADAS</span>

                        <strong class="erp-kpi-value">
                            {{ number_format($sugerencias->where('estado', 'APROBADA')->count()) }}
                        </strong>

                        <small>
                            Movimientos aprobados
                        </small>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- TOOLBAR --}}
    {{-- ========================================================= --}}

    <div class="px-4 pb-3">

        <div class="erp-toolbar">

            <div class="erp-toolbar-title">

                <div class="erp-toolbar-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>

                <div>
                    <strong>Gestión de Redistribución</strong>

                    <span>
                        Seleccione los movimientos que desea procesar
                    </span>
                </div>

            </div>

            <div class="erp-toolbar-actions">

                <div class="erp-selection">

                    <span class="erp-selection-icon">
                        <i class="fas fa-check"></i>
                    </span>

                    <div>
                        <strong id="contadorSeleccionados">0</strong>
                        <small>seleccionados</small>
                    </div>

                </div>

                <div class="erp-divider"></div>

                @can('redistribucionsugerencia aprobar')
                    <button type="button" class="btn btn-success erp-action-btn" id="btnAprobar">

                        <i class="fas fa-check mr-1"></i>
                        Aprobar

                    </button>
                @endcan
                @can('redistribucionsugerencia rechazar')
                    <button type="button" class="btn btn-danger erp-action-btn" id="btnRechazar">
                        <i class="fas fa-times mr-1"></i>
                        Rechazar
                    </button>
                @endcan
                {{-- @can('redistribucionsugerencia automatico') --}}
                    <button type="button" class="btn btn-outline-primary erp-action-btn" id="btnAutomatico">

                        <i class="fas fa-robot mr-1"></i>
                        Automático

                    </button>
                {{-- @endcan --}}

            </div>

        </div>

    </div>

    {{-- ========================================================= --}}
    {{-- FILTROS --}}
    {{-- ========================================================= --}}

    <div class="px-4 pb-3">

        <div class="erp-filter">

            <div class="erp-search">

                <i class="fas fa-search"></i>

                <input type="text" id="buscarRedistribucion" placeholder="Buscar producto, sucursal o motivo..."
                    autocomplete="off">

            </div>

            <div class="erp-status-filter">

                <select id="filtroEstado" class="form-control">

                    <option value="">
                        Todos los estados
                    </option>

                    <option value="PENDIENTE">
                        Pendientes
                    </option>

                    <option value="APROBADA">
                        Aprobadas
                    </option>

                    <option value="RECHAZADA">
                        Rechazadas
                    </option>

                    <option value="EN PROCESO">
                        En proceso
                    </option>

                </select>

            </div>

            <div class="erp-results">

                <span>
                    Mostrando
                </span>

                <strong id="cantidadVisible">
                    {{ $sugerencias->count() }}
                </strong>

                <span>
                    movimientos
                </span>

            </div>

        </div>

    </div>

    {{-- ========================================================= --}}
    {{-- TABLA --}}
    {{-- ========================================================= --}}

    <div class="px-4 pb-4">

        <div class="erp-table-container">

            <table class="table erp-table" id="redistribucion-sugeridas-table">

                <thead>

                    <tr>

                        <th class="erp-check-column">

                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="seleccionarTodosTabla">

                                <label class="custom-control-label" for="seleccionarTodosTabla">
                                </label>
                            </div>

                        </th>

                        <th>PRODUCTO</th>

                        <th>ORIGEN</th>

                        <th>DESTINO</th>

                        <th class="text-center">
                            CANTIDAD
                        </th>

                        <th class="text-center">
                            STOCK
                        </th>

                        <th class="text-center">
                            VENTAS
                        </th>

                        <th>
                            MOTIVO
                        </th>

                        <th class="text-center">
                            ESTADO
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse ($sugerencias as $redistribucionSugerida)
                        <tr class="redistribucion-row" data-estado="{{ $redistribucionSugerida->estado }}">

                            {{-- CHECKBOX --}}

                            <td class="text-center">

                                @if ($redistribucionSugerida->estado === 'PENDIENTE')
                                    <div class="custom-control custom-checkbox">

                                        <input type="checkbox" class="custom-control-input sugerencia-checkbox"
                                            id="sugerencia_{{ $redistribucionSugerida->id }}"
                                            value="{{ $redistribucionSugerida->id }}">

                                        <label class="custom-control-label"
                                            for="sugerencia_{{ $redistribucionSugerida->id }}">
                                        </label>

                                    </div>
                                @else
                                    <span class="erp-locked">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                @endif

                            </td>

                            {{-- PRODUCTO --}}

                            <td>

                                <div class="erp-product">

                                    <div class="erp-product-icon">
                                        <i class="fas fa-barcode"></i>
                                    </div>

                                    <div>

                                        <strong>
                                            {{ $redistribucionSugerida->codigo }}
                                        </strong>

                                        <span>
                                            Código de producto
                                        </span>

                                    </div>

                                </div>

                            </td>

                            {{-- ORIGEN --}}

                            <td>

                                <div class="erp-location">

                                    <div class="erp-location-icon origin">
                                        <i class="fas fa-arrow-up"></i>
                                    </div>

                                    <div>

                                        <strong>
                                            {{ $redistribucionSugerida->origen->suc_descri ?? $redistribucionSugerida->sucursal_origen }}
                                        </strong>

                                        <span>
                                            Sucursal origen
                                        </span>

                                    </div>

                                </div>

                            </td>

                            {{-- DESTINO --}}

                            <td>

                                <div class="erp-location">

                                    <div class="erp-location-icon destination">
                                        <i class="fas fa-arrow-down"></i>
                                    </div>

                                    <div>

                                        <strong>
                                            {{ $redistribucionSugerida->destino->suc_descri ?? $redistribucionSugerida->sucursal_destino }}
                                        </strong>

                                        <span>
                                            Sucursal destino
                                        </span>

                                    </div>

                                </div>

                            </td>

                            {{-- CANTIDAD --}}

                            <td class="text-center">

                                <div class="erp-quantity">

                                    <strong>
                                        {{ number_format($redistribucionSugerida->cantidad) }}
                                    </strong>

                                    <span>
                                        unidades
                                    </span>

                                </div>

                            </td>

                            {{-- STOCK --}}

                            <td>

                                <div class="erp-metric">

                                    <div>
                                        <span>Origen</span>
                                        <strong>
                                            {{ number_format($redistribucionSugerida->stock_origen) }}
                                        </strong>
                                    </div>

                                    <i class="fas fa-arrow-right"></i>

                                    <div>
                                        <span>Destino</span>

                                        <strong
                                            class="{{ $redistribucionSugerida->stock_destino <= 0 ? 'danger' : '' }}">
                                            {{ number_format($redistribucionSugerida->stock_destino) }}
                                        </strong>
                                    </div>

                                </div>

                            </td>

                            {{-- VENTAS --}}

                            <td>

                                <div class="erp-metric">

                                    <div>
                                        <span>Origen</span>

                                        <strong>
                                            {{ number_format($redistribucionSugerida->venta_origen) }}
                                        </strong>
                                    </div>

                                    <i class="fas fa-arrow-right"></i>

                                    <div>

                                        <span>Destino</span>

                                        <strong class="primary">
                                            {{ number_format($redistribucionSugerida->venta_destino) }}
                                        </strong>

                                    </div>

                                </div>

                            </td>

                            {{-- MOTIVO --}}

                            <td>

                                <div class="erp-reason">

                                    <i class="fas fa-info-circle"></i>

                                    <span title="{{ $redistribucionSugerida->motivo }}">
                                        {{ $redistribucionSugerida->motivo }}
                                    </span>

                                </div>

                            </td>

                            {{-- ESTADO --}}

                            <td class="text-center">

                                @switch($redistribucionSugerida->estado)
                                    @case('PENDIENTE')
                                        <span class="erp-status pending">
                                            <i class="fas fa-clock"></i>
                                            PENDIENTE
                                        </span>
                                    @break

                                    @case('APROBADA')
                                        <span class="erp-status approved">
                                            <i class="fas fa-check"></i>
                                            APROBADA
                                        </span>
                                    @break

                                    @case('RECHAZADA')
                                        <span class="erp-status rejected">
                                            <i class="fas fa-times"></i>
                                            RECHAZADA
                                        </span>
                                    @break

                                    @case('EN PROCESO')
                                        <span class="erp-status process">
                                            <i class="fas fa-spinner"></i>
                                            EN PROCESO
                                        </span>
                                    @break

                                    @default
                                        <span class="erp-status default">
                                            {{ $redistribucionSugerida->estado }}
                                        </span>
                                @endswitch

                            </td>

                        </tr>

                        @empty

                            <tr>

                                <td colspan="9">

                                    <div class="erp-empty">

                                        <div class="erp-empty-icon">
                                            <i class="fas fa-box-open"></i>
                                        </div>

                                        <h4>
                                            No hay redistribuciones
                                        </h4>

                                        <p>
                                            Ejecutá un análisis para generar propuestas de redistribución.
                                        </p>

                                    </div>

                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    <style>
        .redistribucion-module {
            background: #f7f8fa;
        }

        /* =========================================================
               KPI
            ========================================================= */

        .erp-kpi {
            background: #fff;
            border: 1px solid #e9edf2;
            border-radius: 8px;
            min-height: 96px;
            padding: 16px;
            display: flex;
            align-items: center;
            transition: all .2s ease;
        }

        .erp-kpi:hover {
            border-color: #d6dde6;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .06);
            transform: translateY(-1px);
        }

        .erp-kpi-icon {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 13px;
            font-size: 18px;
        }

        .erp-kpi-icon.primary {
            background: #edf4ff;
            color: #2563eb;
        }

        .erp-kpi-icon.warning {
            background: #fff7e6;
            color: #d97706;
        }

        .erp-kpi-icon.pending {
            background: #f1f3f5;
            color: #6b7280;
        }

        .erp-kpi-icon.success {
            background: #eaf8ef;
            color: #16a34a;
        }

        .erp-kpi-info {
            display: flex;
            flex-direction: column;
        }

        .erp-kpi-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .7px;
            color: #8a94a6;
        }

        .erp-kpi-value {
            font-size: 23px;
            line-height: 1.2;
            color: #1f2937;
        }

        .erp-kpi-info small {
            font-size: 10px;
            color: #9ca3af;
        }


        /* =========================================================
               TOOLBAR
            ========================================================= */

        .erp-toolbar {
            min-height: 68px;
            background: #fff;
            border: 1px solid #e5e9ef;
            border-radius: 8px;
            padding: 12px 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .erp-toolbar-title {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .erp-toolbar-icon {
            width: 38px;
            height: 38px;
            border-radius: 7px;
            background: #edf4ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .erp-toolbar-title strong {
            display: block;
            font-size: 13px;
            color: #1f2937;
        }

        .erp-toolbar-title span {
            display: block;
            font-size: 10px;
            color: #9ca3af;
            margin-top: 2px;
        }

        .erp-toolbar-actions {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .erp-selection {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-right: 5px;
        }

        .erp-selection-icon {
            width: 27px;
            height: 27px;
            border-radius: 6px;
            background: #edf4ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
        }

        .erp-selection strong {
            color: #1f2937;
            font-size: 12px;
        }

        .erp-selection small {
            display: block;
            color: #9ca3af;
            font-size: 9px;
        }

        .erp-divider {
            width: 1px;
            height: 28px;
            background: #e5e7eb;
            margin: 0 5px;
        }

        .erp-action-btn {
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            padding: 7px 12px;
        }


        /* =========================================================
               FILTROS
            ========================================================= */

        .erp-filter {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .erp-search {
            position: relative;
            flex: 1;
        }

        .erp-search i {
            position: absolute;
            left: 12px;
            top: 11px;
            color: #9ca3af;
            font-size: 12px;
        }

        .erp-search input {
            width: 100%;
            height: 36px;
            border: 1px solid #e1e5ea;
            border-radius: 6px;
            padding-left: 34px;
            font-size: 12px;
            color: #374151;
            outline: none;
        }

        .erp-search input:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 .12rem rgba(13, 110, 253, .08);
        }

        .erp-status-filter {
            width: 190px;
        }

        .erp-status-filter select {
            height: 36px;
            border: 1px solid #e1e5ea;
            border-radius: 6px;
            font-size: 12px;
        }

        .erp-results {
            min-width: 130px;
            text-align: right;
            color: #9ca3af;
            font-size: 10px;
        }

        .erp-results strong {
            color: #374151;
            font-size: 12px;
            margin: 0 3px;
        }


        /* =========================================================
               TABLA
            ========================================================= */

        .erp-table-container {
            background: #fff;
            border: 1px solid #e3e7ec;
            border-radius: 8px;
            overflow-x: auto;
            overflow-y: hidden;
        }

        .erp-table {
            min-width: 1280px;
            margin: 0;
        }

        .erp-table thead th {
            background: #f8fafc;
            border: 0;
            border-bottom: 1px solid #e1e5ea;
            padding: 11px 12px;
            color: #7b8494;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .55px;
            white-space: nowrap;
        }

        .erp-table tbody td {
            padding: 11px 12px;
            border-top: 1px solid #f0f2f5;
            color: #374151;
            font-size: 11px;
            vertical-align: middle;
        }

        .erp-table tbody tr {
            transition: background .15s ease;
        }

        .erp-table tbody tr:hover {
            background: #fafcff;
        }

        .erp-check-column {
            width: 42px;
        }


        /* =========================================================
               PRODUCTO
            ========================================================= */

        .erp-product {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .erp-product-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: #f1f3f5;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .erp-product strong {
            display: block;
            color: #1f2937;
            font-size: 11px;
        }

        .erp-product span {
            display: block;
            color: #a0a7b2;
            font-size: 8px;
            margin-top: 2px;
        }


        /* =========================================================
               SUCURSALES
            ========================================================= */

        .erp-location {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .erp-location-icon {
            width: 27px;
            height: 27px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }

        .erp-location-icon.origin {
            background: #fff1f2;
            color: #dc2626;
        }

        .erp-location-icon.destination {
            background: #edf4ff;
            color: #2563eb;
        }

        .erp-location strong {
            display: block;
            color: #374151;
            font-size: 11px;
        }

        .erp-location span {
            display: block;
            color: #a0a7b2;
            font-size: 8px;
            margin-top: 2px;
        }


        /* =========================================================
               CANTIDAD
            ========================================================= */

        .erp-quantity {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            min-width: 65px;
            padding: 5px 9px;
            background: #edf4ff;
            border: 1px solid #dce9ff;
            border-radius: 6px;
        }

        .erp-quantity strong {
            color: #2563eb;
            font-size: 13px;
        }

        .erp-quantity span {
            color: #7b8494;
            font-size: 8px;
        }


        /* =========================================================
               STOCK / VENTAS
            ========================================================= */

        .erp-metric {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .erp-metric>div {
            min-width: 36px;
        }

        .erp-metric span {
            display: block;
            color: #a0a7b2;
            font-size: 8px;
        }

        .erp-metric strong {
            display: block;
            color: #374151;
            font-size: 11px;
        }

        .erp-metric strong.primary {
            color: #2563eb;
        }

        .erp-metric strong.danger {
            color: #dc2626;
        }

        .erp-metric>i {
            color: #cbd0d7;
            font-size: 9px;
        }


        /* =========================================================
               MOTIVO
            ========================================================= */

        .erp-reason {
            max-width: 230px;
            display: flex;
            gap: 7px;
            align-items: flex-start;
            color: #6b7280;
        }

        .erp-reason i {
            color: #a0a7b2;
            font-size: 10px;
            margin-top: 2px;
        }

        .erp-reason span {
            font-size: 9px;
            line-height: 1.45;
        }


        /* =========================================================
               ESTADOS
            ========================================================= */

        .erp-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 8px;
            border-radius: 5px;
            font-size: 8px;
            font-weight: 700;
            white-space: nowrap;
        }

        .erp-status i {
            font-size: 8px;
        }

        .erp-status.pending {
            background: #fff7e6;
            color: #b7791f;
        }

        .erp-status.approved {
            background: #eaf8ef;
            color: #16803c;
        }

        .erp-status.rejected {
            background: #fff0f0;
            color: #c53030;
        }

        .erp-status.process {
            background: #edf5ff;
            color: #1671c5;
        }

        .erp-status.default {
            background: #f1f3f5;
            color: #6b7280;
        }

        .erp-locked {
            color: #c4c9d0;
            font-size: 10px;
        }


        /* =========================================================
               VACÍO
            ========================================================= */

        .erp-empty {
            padding: 65px 20px;
            text-align: center;
        }

        .erp-empty-icon {
            width: 62px;
            height: 62px;
            border-radius: 50%;
            background: #f1f3f5;
            color: #9ca3af;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 13px;
            font-size: 25px;
        }

        .erp-empty h4 {
            margin-bottom: 5px;
            color: #374151;
            font-size: 15px;
        }

        .erp-empty p {
            color: #9ca3af;
            font-size: 11px;
            margin: 0;
        }


        /* =========================================================
               RESPONSIVE
            ========================================================= */

        @media (max-width: 991px) {

            .erp-toolbar {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .erp-toolbar-actions {
                flex-wrap: wrap;
            }

            .erp-filter {
                flex-direction: column;
                align-items: stretch;
            }

            .erp-status-filter {
                width: 100%;
            }

            .erp-results {
                text-align: left;
            }

        }
    </style>


    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const seleccionarTodos = document.getElementById('seleccionarTodosTabla');
            const contador = document.getElementById('contadorSeleccionados');
            const buscar = document.getElementById('buscarRedistribucion');
            const filtroEstado = document.getElementById('filtroEstado');
            const cantidadVisible = document.getElementById('cantidadVisible');

            function obtenerCheckboxes() {
                return document.querySelectorAll('.sugerencia-checkbox');
            }

            function actualizarContador() {

                const seleccionados = document.querySelectorAll(
                    '.sugerencia-checkbox:checked'
                );

                contador.textContent = seleccionados.length;
            }

            if (seleccionarTodos) {

                seleccionarTodos.addEventListener('change', function() {

                    obtenerCheckboxes().forEach(function(checkbox) {

                        const fila = checkbox.closest('tr');

                        if (
                            fila &&
                            fila.style.display !== 'none'
                        ) {
                            checkbox.checked = seleccionarTodos.checked;
                        }

                    });

                    actualizarContador();

                });

            }

            document.addEventListener('change', function(e) {

                if (
                    e.target.classList.contains('sugerencia-checkbox')
                ) {

                    actualizarContador();

                    const todos = Array.from(
                        obtenerCheckboxes()
                    );

                    const visibles = todos.filter(function(checkbox) {

                        const fila = checkbox.closest('tr');

                        return fila &&
                            fila.style.display !== 'none';

                    });

                    const seleccionadosVisibles = visibles.filter(
                        checkbox => checkbox.checked
                    );

                    if (seleccionarTodos) {

                        seleccionarTodos.checked =
                            visibles.length > 0 &&
                            seleccionadosVisibles.length === visibles.length;

                    }

                }

            });


            function filtrarTabla() {

                const texto = buscar ?
                    buscar.value.toLowerCase().trim() :
                    '';

                const estado = filtroEstado ?
                    filtroEstado.value.toLowerCase() :
                    '';

                const filas = document.querySelectorAll(
                    '.redistribucion-row'
                );

                let visibles = 0;

                filas.forEach(function(fila) {

                    const contenido =
                        fila.textContent.toLowerCase();

                    const estadoFila =
                        (fila.dataset.estado || '').toLowerCase();

                    const coincideTexto =
                        contenido.includes(texto);

                    const coincideEstado = !estado ||
                        estadoFila === estado;

                    const mostrar =
                        coincideTexto &&
                        coincideEstado;

                    fila.style.display =
                        mostrar ? '' : 'none';

                    if (mostrar) {
                        visibles++;
                    }

                });

                if (cantidadVisible) {
                    cantidadVisible.textContent = visibles;
                }

                if (seleccionarTodos) {
                    seleccionarTodos.checked = false;
                }

                actualizarContador();
            }


            if (buscar) {
                buscar.addEventListener(
                    'input',
                    filtrarTabla
                );
            }

            if (filtroEstado) {
                filtroEstado.addEventListener(
                    'change',
                    filtrarTabla
                );
            }


            function obtenerSeleccionados() {

                const ids = [];

                document
                    .querySelectorAll('.sugerencia-checkbox:checked')
                    .forEach(function(checkbox) {

                        ids.push(checkbox.value);

                    });

                return ids;
            }


            function enviarFormulario(url, ids) {

                const form =
                    document.createElement('form');

                form.method = 'POST';
                form.action = url;

                const csrf =
                    document.createElement('input');

                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = "{{ csrf_token() }}";

                form.appendChild(csrf);

                ids.forEach(function(id) {

                    const input =
                        document.createElement('input');

                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = id;

                    form.appendChild(input);

                });

                document.body.appendChild(form);

                form.submit();
            }


            /* =====================================================
               APROBAR
            ===================================================== */

            const btnAprobar =
                document.getElementById('btnAprobar');

            if (btnAprobar) {

                btnAprobar.addEventListener(
                    'click',
                    function() {

                        const ids =
                            obtenerSeleccionados();

                        if (!ids.length) {

                            Swal.fire({
                                icon: 'warning',
                                title: 'Sin selección',
                                text: 'Seleccione al menos una sugerencia pendiente.',
                                confirmButtonText: 'Entendido'
                            });

                            return;
                        }

                        Swal.fire({

                            icon: 'question',

                            title: 'Confirmar aprobación',

                            html: 'Se aprobarán <strong>' +
                                ids.length +
                                '</strong> movimiento(s).',

                            showCancelButton: true,

                            confirmButtonText: '<i class="fas fa-check"></i> Sí, aprobar',

                            cancelButtonText: 'Cancelar',

                            reverseButtons: true

                        }).then(function(result) {

                            if (result.isConfirmed) {

                                enviarFormulario(
                                    "{{ route('RedistribucionSugeridas.aprobar') }}",
                                    ids
                                );

                            }

                        });

                    }
                );

            }


            /* =====================================================
               RECHAZAR
            ===================================================== */

            const btnRechazar =
                document.getElementById('btnRechazar');

            if (btnRechazar) {

                btnRechazar.addEventListener(
                    'click',
                    function() {

                        const ids =
                            obtenerSeleccionados();

                        if (!ids.length) {

                            Swal.fire({
                                icon: 'warning',
                                title: 'Sin selección',
                                text: 'Seleccione al menos una sugerencia pendiente.',
                                confirmButtonText: 'Entendido'
                            });

                            return;
                        }

                        Swal.fire({

                            icon: 'warning',

                            title: 'Confirmar rechazo',

                            html: 'Se rechazarán <strong>' +
                                ids.length +
                                '</strong> movimiento(s).',

                            showCancelButton: true,

                            confirmButtonText: '<i class="fas fa-times"></i> Sí, rechazar',

                            cancelButtonText: 'Cancelar',

                            reverseButtons: true

                        }).then(function(result) {

                            if (result.isConfirmed) {

                                enviarFormulario(
                                    "{{ route('RedistribucionSugeridas.rechazar') }}",
                                    ids
                                );

                            }

                        });

                    }
                );

            }


            /* =====================================================
               AUTOMÁTICO
            ===================================================== */

            const btnAutomatico =
                document.getElementById('btnAutomatico');

            if (btnAutomatico) {

                btnAutomatico.addEventListener(
                    'click',
                    function() {

                        Swal.fire({

                            icon: 'info',

                            title: 'Aprobación automática',

                            text: 'Esta función estará disponible próximamente.',

                            confirmButtonText: 'Entendido'

                        });

                    }
                );

            }

        });


        /* =========================================================
           MENSAJES DE SESIÓN
        ========================================================= */

        document.addEventListener(
            'DOMContentLoaded',
            function() {

                @if (session('success'))

                    Swal.fire({
                        icon: 'success',
                        title: 'Operación realizada',
                        text: @json(session('success')),
                        confirmButtonText: 'Entendido'
                    });
                @endif

                @if (session('warning'))

                    Swal.fire({
                        icon: 'warning',
                        title: 'Atención',
                        text: @json(session('warning')),
                        confirmButtonText: 'Entendido'
                    });
                @endif

                @if (session('error'))

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: @json(session('error')),
                        confirmButtonText: 'Entendido'
                    });
                @endif

            }
        );
    </script>
