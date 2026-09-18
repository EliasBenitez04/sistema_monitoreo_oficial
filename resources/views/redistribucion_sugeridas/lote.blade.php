@extends('layouts.app')

@section('content')
    <div class="redistribucion-lote-page">

        <div class="container-fluid">

            {{-- ===================================================== --}}
            {{-- HEADER --}}
            {{-- ===================================================== --}}

            <div class="lote-header mb-4">

                <div class="lote-header-left">

                    <div class="lote-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>

                    <div>

                        <span>
                            LOTE DE REDISTRIBUCIÓN
                        </span>

                        <h3>
                            {{ $lote->numero_lote }}
                        </h3>

                        <p>
                            Transferencias masivas agrupadas para procesamiento
                        </p>

                    </div>

                </div>

                <div>

                    @switch($lote->estado)
                        @case('GENERADO')
                            <span class="lote-status generated">
                                <span></span>
                                GENERADO
                            </span>
                        @break

                        @case('EN PROCESO')
                            <span class="lote-status process">
                                <span></span>
                                EN PROCESO
                            </span>
                        @break

                        @case('FINALIZADO')
                            <span class="lote-status finished">
                                <span></span>
                                FINALIZADO
                            </span>
                        @break

                        @default
                            <span class="lote-status default">
                                <span></span>
                                {{ $lote->estado }}
                            </span>
                    @endswitch
                    <a href="{{ route('RedistribucionSugeridas.lotes') }}" class="btn btn-primary lote-btn">
                        <i class="fas fa-arrow-left"></i>
                        Volver
                    </a>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- KPIs --}}
            {{-- ===================================================== --}}

            <div class="row mb-4">

                <div class="col-xl-3 col-md-6 mb-3">

                    <div class="lote-kpi">

                        <div class="lote-kpi-icon blue">
                            <i class="fas fa-exchange-alt"></i>
                        </div>

                        <div>

                            <span>
                                TRANSFERENCIAS
                            </span>

                            <strong>
                                {{ number_format($lote->total_transferencias) }}
                            </strong>

                            <small>
                                Movimientos del lote
                            </small>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-md-6 mb-3">

                    <div class="lote-kpi">

                        <div class="lote-kpi-icon purple">
                            <i class="fas fa-boxes"></i>
                        </div>

                        <div>

                            <span>
                                PRODUCTOS
                            </span>

                            <strong>
                                {{ number_format($lote->total_productos) }}
                            </strong>

                            <small>
                                Códigos diferentes
                            </small>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-md-6 mb-3">

                    <div class="lote-kpi">

                        <div class="lote-kpi-icon orange">
                            <i class="fas fa-cubes"></i>
                        </div>

                        <div>

                            <span>
                                UNIDADES
                            </span>

                            <strong>
                                {{ number_format($lote->total_unidades) }}
                            </strong>

                            <small>
                                Total a transferir
                            </small>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-md-6 mb-3">

                    <div class="lote-kpi">

                        <div class="lote-kpi-icon green">
                            <i class="fas fa-calendar-alt"></i>
                        </div>

                        <div>

                            <span>
                                GENERADO
                            </span>

                            <strong class="date-value">
                                {{ optional($lote->fecha_generacion)->format('d/m/Y') }}
                            </strong>

                            <small>
                                {{ optional($lote->fecha_generacion)->format('H:i') }}
                            </small>

                        </div>

                    </div>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- INFORMACIÓN --}}
            {{-- ===================================================== --}}

            <div class="card lote-card mb-4">

                <div class="card-header lote-card-header">

                    <div>

                        <h5>
                            <i class="fas fa-info-circle"></i>
                            Información del lote
                        </h5>

                        <small>
                            Identificación y control del procesamiento
                        </small>

                    </div>

                    <span class="lot-id">
                        ID #{{ $lote->id }}
                    </span>

                </div>


                <div class="card-body">

                    <div class="row">

                        <div class="col-md-3">

                            <div class="lote-info">

                                <span>
                                    NÚMERO DE LOTE
                                </span>

                                <strong>
                                    {{ $lote->numero_lote }}
                                </strong>

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="lote-info">

                                <span>
                                    USUARIO GENERACIÓN
                                </span>

                                <strong>
                                    {{ $lote->usuario_generacion ?? 'SISTEMA' }}
                                </strong>

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="lote-info">

                                <span>
                                    FECHA GENERACIÓN
                                </span>

                                <strong>
                                    {{ optional($lote->fecha_generacion)->format('d/m/Y H:i') }}
                                </strong>

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="lote-info">

                                <span>
                                    ESTADO
                                </span>

                                <strong
                                    class="
                                    @if ($lote->estado === 'FINALIZADO') text-success
                                    @elseif ($lote->estado === 'EN PROCESO')
                                        text-warning
                                    @else
                                        text-primary @endif
                                ">
                                    {{ $lote->estado }}
                                </strong>

                            </div>

                        </div>

                    </div>


                    @if ($lote->observacion)
                        <div class="lote-observation mt-4">

                            <i class="fas fa-comment-alt"></i>

                            <div>

                                <strong>
                                    Observación
                                </strong>

                                <p>
                                    {{ $lote->observacion }}
                                </p>

                            </div>

                        </div>
                    @endif

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- TABLA MASIVA --}}
            {{-- ===================================================== --}}

            <div class="card lote-card">

                <div class="card-header lote-card-header">

                    <div>

                        <h5>
                            <i class="fas fa-list-ul"></i>
                            Transferencias del lote
                        </h5>

                        <small>
                            Detalle completo de las operaciones incluidas
                        </small>

                    </div>


                    <div class="table-tools">

                        <div class="table-search">

                            <i class="fas fa-search"></i>

                            <input type="text" id="buscarLote" placeholder="Buscar código, origen o destino..."
                                autocomplete="off">

                        </div>

                    </div>

                </div>


                <div class="table-responsive">

                    <table class="table lote-table" id="tablaLote">

                        <thead>

                            <tr>

                                <th>
                                    #
                                </th>

                                <th>
                                    PRODUCTO
                                </th>

                                <th>
                                    ORIGEN
                                </th>

                                <th>
                                    DESTINO
                                </th>

                                <th class="text-center">
                                    CANTIDAD
                                </th>

                                <th class="text-center">
                                    ESTADO
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($lote->detalles as $detalle)
                                <tr class="lote-row">

                                    <td class="number-cell">
                                        {{ $loop->iteration }}
                                    </td>


                                    {{-- PRODUCTO --}}

                                    <td>

                                        <div class="lote-product">

                                            <div class="product-icon">
                                                <i class="fas fa-barcode"></i>
                                            </div>

                                            <div>

                                                <strong>
                                                    {{ $detalle->codigo }}
                                                </strong>

                                                <small>
                                                    Código de producto
                                                </small>

                                            </div>

                                        </div>

                                    </td>


                                    {{-- ORIGEN --}}

                                    <td>

                                        <div class="transfer-location">

                                            <div class="transfer-icon origin">

                                                <i class="fas fa-arrow-up"></i>

                                            </div>

                                            <div>

                                                <strong>
                                                    {{ optional($detalle->origen)->suc_descri ?? $detalle->sucursal_origen }}
                                                </strong>

                                                <small>
                                                    SUCURSAL ORIGEN
                                                </small>

                                            </div>

                                        </div>

                                    </td>


                                    {{-- DESTINO --}}

                                    <td>

                                        <div class="transfer-location">

                                            <div class="transfer-icon destination">

                                                <i class="fas fa-arrow-down"></i>

                                            </div>

                                            <div>

                                                <strong>
                                                    {{ optional($detalle->destino)->suc_descri ?? $detalle->sucursal_destino }}
                                                </strong>

                                                <small>
                                                    SUCURSAL DESTINO
                                                </small>

                                            </div>

                                        </div>

                                    </td>


                                    {{-- CANTIDAD --}}

                                    <td class="text-center">

                                        <span class="lote-quantity">

                                            {{ number_format($detalle->cantidad) }}

                                        </span>

                                    </td>


                                    {{-- ESTADO --}}

                                    <td class="text-center">

                                        @switch($detalle->estado)
                                            @case('PENDIENTE')
                                                <span class="detail-badge pending">

                                                    <span></span>

                                                    PENDIENTE

                                                </span>
                                            @break

                                            @case('EN PROCESO')
                                                <span class="detail-badge process">

                                                    <span></span>

                                                    EN PROCESO

                                                </span>
                                            @break

                                            @case('FINALIZADO')
                                                <span class="detail-badge finished">

                                                    <span></span>

                                                    FINALIZADO

                                                </span>
                                            @break

                                            @default
                                                <span class="detail-badge default">

                                                    {{ $detalle->estado }}

                                                </span>
                                        @endswitch

                                    </td>

                                </tr>

                                @empty

                                    <tr>

                                        <td colspan="6">

                                            <div class="empty-lote">

                                                <i class="fas fa-inbox"></i>

                                                <h5>
                                                    Lote sin transferencias
                                                </h5>

                                                <p>
                                                    No existen detalles asociados a este lote.
                                                </p>

                                            </div>

                                        </td>

                                    </tr>
                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    {{-- ================================================= --}}
                    {{-- FOOTER --}}
                    {{-- ================================================= --}}

                    <div class="lote-actions">

                        <a href="{{ route('RedistribucionSugeridas.lotes') }}" class="btn btn-light lote-btn">

                            <i class="fas fa-arrow-left"></i>

                            Volver

                        </a>


                        <div class="actions-right">


                            {{-- ========================================= --}}
                            {{-- GENERADO --}}
                            {{-- ========================================= --}}

                            @if ($lote->estado === 'GENERADO')
                                @can('redistribucionsugerencia procesarLote')
                                    <button type="button" class="btn btn-outline-primary lote-btn" id="btnProcesarLote">

                                        <i class="fas fa-play"></i>

                                        Procesar lote

                                    </button>
                                @endcan


                                {{-- ========================================= --}}
                                {{-- EN PROCESO --}}
                                {{-- ========================================= --}}
                            @elseif ($lote->estado === 'EN PROCESO')
                                @can('redistribucionsugerencia finalizarLote')
                                    <button type="button" class="btn btn-success lote-btn-main" id="btnFinalizarLote">

                                        <i class="fas fa-check"></i>

                                        Finalizar lote

                                    </button>
                                @endcan


                                {{-- ========================================= --}}
                                {{-- FINALIZADO --}}
                                {{-- ========================================= --}}
                            @elseif ($lote->estado === 'FINALIZADO')
                                <span class="lote-finished-message">

                                    <i class="fas fa-check-circle"></i>

                                    Lote finalizado correctamente

                                </span>
                            @endif


                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- CSS --}}
        {{-- ========================================================= --}}

        <style>
            .redistribucion-lote-page {
                background: #f5f7fa;
                min-height: 100vh;
                padding: 20px 0 40px;
            }


            /* HEADER */

            .lote-header {
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 20px 24px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
            }

            .lote-header-left {
                display: flex;
                align-items: center;
                gap: 15px;
            }

            .lote-icon {
                width: 48px;
                height: 48px;
                border-radius: 10px;
                background: #eef4ff;
                color: #2563eb;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
            }

            .lote-header span:not(.lote-status) {
                display: block;
                font-size: 9px;
                font-weight: 700;
                color: #2563eb;
                letter-spacing: 1px;
            }

            .lote-header h3 {
                margin: 2px 0;
                color: #111827;
                font-size: 20px;
                font-weight: 700;
            }

            .lote-header p {
                margin: 0;
                color: #9ca3af;
                font-size: 12px;
            }


            /* STATUS */

            .lote-status {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                border-radius: 20px;
                padding: 7px 12px;
                font-size: 9px;
                font-weight: 700;
            }

            .lote-status span {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: currentColor;
            }

            .lote-status.generated {
                background: #e8f4ff;
                color: #1671c5;
            }

            .lote-status.process {
                background: #fff7df;
                color: #b7791f;
            }

            .lote-status.finished {
                background: #eaf8ef;
                color: #16803c;
            }

            .lote-status.default {
                background: #f3f4f6;
                color: #6b7280;
            }


            /* KPI */

            .lote-kpi {
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                padding: 17px;
                min-height: 100px;
                display: flex;
                align-items: center;
                gap: 13px;
                box-shadow: 0 2px 7px rgba(15, 23, 42, .035);
            }

            .lote-kpi-icon {
                width: 44px;
                height: 44px;
                border-radius: 9px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 18px;
            }

            .lote-kpi-icon.blue {
                background: #eff6ff;
                color: #2563eb;
            }

            .lote-kpi-icon.purple {
                background: #f3e8ff;
                color: #7c3aed;
            }

            .lote-kpi-icon.orange {
                background: #fff7ed;
                color: #ea580c;
            }

            .lote-kpi-icon.green {
                background: #ecfdf5;
                color: #059669;
            }

            .lote-kpi span {
                display: block;
                font-size: 9px;
                font-weight: 700;
                color: #9ca3af;
                letter-spacing: .6px;
            }

            .lote-kpi strong {
                display: block;
                color: #111827;
                font-size: 21px;
                line-height: 1.3;
            }

            .lote-kpi small {
                display: block;
                color: #9ca3af;
                font-size: 9px;
            }

            .date-value {
                font-size: 15px !important;
            }


            /* CARD */

            .lote-card {
                border: 1px solid #e5e7eb;
                border-radius: 11px;
                box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
                overflow: hidden;
            }

            .lote-card-header {
                background: #fff;
                border-bottom: 1px solid #eef0f3;
                padding: 17px 20px;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .lote-card-header h5 {
                margin: 0;
                color: #111827;
                font-size: 14px;
                font-weight: 700;
            }

            .lote-card-header h5 i {
                color: #2563eb;
                margin-right: 7px;
            }

            .lote-card-header small {
                display: block;
                margin-top: 3px;
                color: #9ca3af;
                font-size: 10px;
            }

            .lot-id {
                background: #f8fafc;
                border: 1px solid #e5e7eb;
                color: #6b7280;
                padding: 6px 9px;
                border-radius: 6px;
                font-size: 9px;
                font-weight: 600;
            }


            /* INFO */

            .lote-info {
                border-left: 2px solid #e5e7eb;
                padding-left: 10px;
            }

            .lote-info span {
                display: block;
                color: #9ca3af;
                font-size: 8px;
                font-weight: 700;
                letter-spacing: .5px;
            }

            .lote-info strong {
                display: block;
                margin-top: 4px;
                color: #374151;
                font-size: 12px;
            }

            .lote-observation {
                display: flex;
                gap: 10px;
                background: #f8fafc;
                border: 1px solid #edf0f3;
                border-radius: 8px;
                padding: 12px;
            }

            .lote-observation>i {
                color: #2563eb;
            }

            .lote-observation strong {
                display: block;
                font-size: 10px;
                color: #374151;
            }

            .lote-observation p {
                margin: 3px 0 0;
                color: #6b7280;
                font-size: 10px;
            }


            /* SEARCH */

            .table-tools {
                display: flex;
                align-items: center;
            }

            .table-search {
                position: relative;
            }

            .table-search i {
                position: absolute;
                left: 11px;
                top: 10px;
                color: #9ca3af;
                font-size: 11px;
            }

            .table-search input {
                width: 260px;
                height: 34px;
                border: 1px solid #dfe3e8;
                border-radius: 6px;
                padding-left: 32px;
                font-size: 11px;
                outline: none;
            }

            .table-search input:focus {
                border-color: #80bdff;
                box-shadow: 0 0 0 .12rem rgba(0, 123, 255, .08);
            }


            /* TABLE */

            .lote-table {
                margin: 0;
            }

            .lote-table thead th {
                background: #f8fafc;
                border-top: 0;
                border-bottom: 1px solid #e5e7eb;
                padding: 12px 14px;
                color: #6b7280;
                font-size: 9px;
                font-weight: 700;
                letter-spacing: .5px;
                white-space: nowrap;
            }

            .lote-table tbody td {
                padding: 12px 14px;
                border-top: 1px solid #f1f3f5;
                vertical-align: middle;
                font-size: 11px;
            }

            .lote-table tbody tr:hover {
                background: #fafcff;
            }

            .number-cell {
                color: #9ca3af;
                font-size: 9px !important;
            }


            /* PRODUCT */

            .lote-product {
                display: flex;
                align-items: center;
                gap: 9px;
            }

            .product-icon {
                width: 32px;
                height: 32px;
                border-radius: 7px;
                background: #f3f4f6;
                color: #6b7280;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .lote-product strong {
                display: block;
                color: #111827;
            }

            .lote-product small {
                display: block;
                color: #9ca3af;
                font-size: 8px;
                margin-top: 2px;
            }


            /* LOCATION */

            .transfer-location {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .transfer-icon {
                width: 28px;
                height: 28px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 9px;
            }

            .transfer-icon.origin {
                background: #fff1f2;
                color: #dc2626;
            }

            .transfer-icon.destination {
                background: #eff6ff;
                color: #2563eb;
            }

            .transfer-location strong {
                display: block;
                color: #374151;
                font-size: 11px;
            }

            .transfer-location small {
                display: block;
                color: #9ca3af;
                font-size: 8px;
                margin-top: 2px;
            }


            /* QUANTITY */

            .lote-quantity {
                display: inline-block;
                background: #eff6ff;
                border: 1px solid #dbeafe;
                color: #2563eb;
                border-radius: 6px;
                padding: 5px 10px;
                font-size: 12px;
                font-weight: 700;
            }


            /* DETAIL STATUS */

            .detail-badge {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                border-radius: 15px;
                padding: 5px 8px;
                font-size: 8px;
                font-weight: 700;
            }

            .detail-badge span {
                width: 5px;
                height: 5px;
                border-radius: 50%;
                background: currentColor;
            }

            .detail-badge.pending {
                background: #fff7df;
                color: #b7791f;
            }

            .detail-badge.process {
                background: #0076e4;
                color: #ffffff;
            }

            .detail-badge.finished {
                background: #07c74a;
                color: #ffffff;
            }

            .detail-badge.default {
                background: #f5de0e;
                color: #0c0c0b;
            }


            /* ACTIONS */

            .lote-actions {
                background: #fafbfc;
                border-top: 1px solid #eef0f3;
                padding: 15px 18px;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .actions-right {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .lote-btn {
                border-radius: 7px;
                font-size: 11px;
                font-weight: 600;
                padding: 8px 13px;
            }

            .lote-btn-main {
                border-radius: 7px;
                font-size: 11px;
                font-weight: 600;
                padding: 9px 15px;
            }

            .lote-btn i,
            .lote-btn-main i {
                margin-right: 5px;
            }

            .lote-finished-message {
                color: #16803c;
                font-size: 11px;
                font-weight: 600;
            }

            .lote-finished-message i {
                margin-right: 5px;
            }


            /* EMPTY */

            .empty-lote {
                text-align: center;
                padding: 55px 20px;
            }

            .empty-lote i {
                font-size: 30px;
                color: #d1d5db;
            }

            .empty-lote h5 {
                margin-top: 12px;
                color: #374151;
            }

            .empty-lote p {
                color: #9ca3af;
                font-size: 11px;
            }


            /* RESPONSIVE */

            @media(max-width: 768px) {

                .lote-header {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 15px;
                }

                .lote-card-header {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 12px;
                }

                .table-search input {
                    width: 100%;
                }

                .lote-actions {
                    flex-direction: column;
                    gap: 10px;
                    align-items: stretch;
                }

                .actions-right {
                    flex-direction: column;
                }

            }
        </style>


        {{-- ========================================================= --}}
        {{-- JAVASCRIPT --}}
        {{-- ========================================================= --}}

        <script>
            document.addEventListener('DOMContentLoaded', function() {


                /* =========================================================
                   BUSCADOR
                   ========================================================= */

                const buscar = document.getElementById('buscarLote');

                if (buscar) {

                    buscar.addEventListener('input', function() {

                        const texto = this.value
                            .toLowerCase()
                            .trim();

                        document
                            .querySelectorAll('.lote-row')
                            .forEach(function(row) {

                                const contenido =
                                    row.textContent.toLowerCase();

                                row.style.display =
                                    contenido.includes(texto) ?
                                    '' :
                                    'none';

                            });

                    });

                }


                /* =========================================================
                   PROCESAR LOTE
                   GENERADO -> EN PROCESO
                   ========================================================= */

                const btnProcesar =
                    document.getElementById('btnProcesarLote');

                if (btnProcesar) {

                    btnProcesar.addEventListener('click', function() {

                        Swal.fire({

                            icon: 'question',

                            title: '¿Procesar lote?',

                            html: 'Se iniciará el procesamiento del lote ' +
                                '<strong>{{ $lote->numero_lote }}</strong>.' +
                                '<br><br>' +
                                'Transferencias: <strong>{{ number_format($lote->total_transferencias) }}</strong>' +
                                '<br>' +
                                'Unidades: <strong>{{ number_format($lote->total_unidades) }}</strong>',

                            showCancelButton: true,

                            confirmButtonText: 'Sí, procesar',

                            cancelButtonText: 'Cancelar',

                            reverseButtons: true

                        }).then(function(result) {

                            if (!result.isConfirmed) {
                                return;
                            }


                            /* -----------------------------------------
                               LOADING
                               ----------------------------------------- */

                            Swal.fire({

                                title: 'Procesando lote...',

                                text: 'Por favor espere.',

                                allowOutsideClick: false,

                                allowEscapeKey: false,

                                didOpen: function() {

                                    Swal.showLoading();

                                }

                            });
                            const btnProcesar = document.getElementById('btnProcesarLote');

                            if (btnProcesar) {

                                btnProcesar.addEventListener('click', function() {

                                    Swal.fire({

                                        icon: 'question',

                                        title: '¿Procesar lote?',

                                        html: 'Se iniciará el procesamiento del lote ' +
                                            '<strong>{{ $lote->numero_lote }}</strong>.' +
                                            '<br><br>' +
                                            'Transferencias: <strong>{{ number_format($lote->total_transferencias) }}</strong>' +
                                            '<br>' +
                                            'Unidades: <strong>{{ number_format($lote->total_unidades) }}</strong>',

                                        showCancelButton: true,

                                        confirmButtonText: 'Sí, procesar',

                                        cancelButtonText: 'Cancelar',

                                        reverseButtons: true

                                    }).then(function(result) {

                                        if (result.isConfirmed) {

                                            const form = document.createElement('form');

                                            form.method = 'POST';

                                            form.action =
                                                "{{ route('RedistribucionSugeridas.procesarLote', $lote->id) }}";

                                            const csrf = document.createElement(
                                                'input');

                                            csrf.type = 'hidden';

                                            csrf.name = '_token';

                                            csrf.value = "{{ csrf_token() }}";

                                            form.appendChild(csrf);

                                            document.body.appendChild(form);

                                            form.submit();
                                        }

                                    });

                                });

                            }

                            const btnFinalizar = document.getElementById('btnFinalizarLote');

                            if (btnFinalizar) {

                                btnFinalizar.addEventListener('click', function() {

                                    Swal.fire({

                                        icon: 'question',

                                        title: '¿Finalizar lote?',

                                        html: '¿Está seguro de finalizar el lote ' +
                                            '<strong>{{ $lote->numero_lote }}</strong>?' +
                                            '<br><br>' +
                                            'Una vez finalizado, no podrá volver a procesarse.',

                                        showCancelButton: true,

                                        confirmButtonText: 'Sí, finalizar',

                                        cancelButtonText: 'Cancelar',

                                        reverseButtons: true

                                    }).then(function(result) {

                                        if (result.isConfirmed) {

                                            const form = document.createElement('form');

                                            form.method = 'POST';

                                            form.action =
                                                "{{ route('RedistribucionSugeridas.finalizarLote', $lote->id) }}";

                                            const csrf = document.createElement(
                                                'input');

                                            csrf.type = 'hidden';

                                            csrf.name = '_token';

                                            csrf.value = "{{ csrf_token() }}";

                                            form.appendChild(csrf);

                                            document.body.appendChild(form);

                                            form.submit();
                                        }

                                    });

                                });

                            }


                            /* -----------------------------------------
                               REQUEST
                               ----------------------------------------- */

                            fetch(
                                    "{{ route('RedistribucionSugeridas.procesarLote', $lote->id) }}", {

                                        method: 'POST',

                                        headers: {

                                            'Content-Type': 'application/json',

                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',

                                            'Accept': 'application/json'

                                        },

                                        body: JSON.stringify({})

                                    }
                                )

                                .then(async function(response) {

                                    const data =
                                        await response.json();

                                    if (!response.ok) {

                                        throw new Error(
                                            data.message ||
                                            'Error procesando el lote.'
                                        );

                                    }

                                    return data;

                                })

                                .then(function(data) {

                                    Swal.fire({

                                        icon: 'success',

                                        title: 'Lote procesado',

                                        text: data.message ||
                                            'El lote pasó a EN PROCESO.',

                                        confirmButtonText: 'Aceptar'

                                    }).then(function() {

                                        /*
                                         * Recargamos para que Blade
                                         * detecte EN PROCESO
                                         */

                                        window.location.reload();

                                    });

                                })

                                .catch(function(error) {

                                    console.error(
                                        'Error procesando lote:',
                                        error
                                    );

                                    Swal.fire({

                                        icon: 'error',

                                        title: 'Error',

                                        text: error.message ||
                                            'No se pudo procesar el lote.',

                                        confirmButtonText: 'Aceptar'

                                    });

                                });

                        });

                    });

                }


                /* =========================================================
                   FINALIZAR LOTE
                   EN PROCESO -> FINALIZADO
                   ========================================================= */

                const btnFinalizar =
                    document.getElementById('btnFinalizarLote');

                if (btnFinalizar) {

                    btnFinalizar.addEventListener('click', function() {

                        Swal.fire({

                            icon: 'warning',

                            title: '¿Finalizar lote?',

                            html: 'El lote ' +
                                '<strong>{{ $lote->numero_lote }}</strong>' +
                                ' será marcado como <strong>FINALIZADO</strong>.' +
                                '<br><br>' +
                                'Esta acción indica que las transferencias fueron procesadas.',

                            showCancelButton: true,

                            confirmButtonText: 'Sí, finalizar',

                            cancelButtonText: 'Cancelar',

                            reverseButtons: true

                        }).then(function(result) {

                            if (!result.isConfirmed) {
                                return;
                            }


                            /* -----------------------------------------
                               LOADING
                               ----------------------------------------- */

                            Swal.fire({

                                title: 'Finalizando lote...',

                                text: 'Por favor espere.',

                                allowOutsideClick: false,

                                allowEscapeKey: false,

                                didOpen: function() {

                                    Swal.showLoading();

                                }

                            });


                            /* -----------------------------------------
                               REQUEST
                               ----------------------------------------- */

                            fetch(
                                    "{{ route('RedistribucionSugeridas.finalizarLote', $lote->id) }}", {

                                        method: 'POST',

                                        headers: {

                                            'Content-Type': 'application/json',

                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',

                                            'Accept': 'application/json'

                                        },

                                        body: JSON.stringify({})

                                    }
                                )

                                .then(async function(response) {

                                    const data =
                                        await response.json();

                                    if (!response.ok) {

                                        throw new Error(
                                            data.message ||
                                            'Error finalizando el lote.'
                                        );

                                    }

                                    return data;

                                })

                                .then(function(data) {

                                    Swal.fire({

                                        icon: 'success',

                                        title: 'Lote finalizado',

                                        text: data.message ||
                                            'El lote fue finalizado correctamente.',

                                        confirmButtonText: 'Aceptar'

                                    }).then(function() {

                                        window.location.reload();

                                    });

                                })

                                .catch(function(error) {

                                    console.error(
                                        'Error finalizando lote:',
                                        error
                                    );

                                    Swal.fire({

                                        icon: 'error',

                                        title: 'Error',

                                        text: error.message ||
                                            'No se pudo finalizar el lote.',

                                        confirmButtonText: 'Aceptar'

                                    });

                                });

                        });

                    });

                }

            });
        </script>
    @endsection
