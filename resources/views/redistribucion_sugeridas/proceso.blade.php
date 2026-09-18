@extends('layouts.app')

@section('content')
    <div class="redistribucion-proceso-page">

        <div class="container-fluid">

            {{-- ===================================================== --}}
            {{-- HEADER --}}
            {{-- ===================================================== --}}

            <div class="process-header mb-4">

                <div class="header-left">

                    <div class="header-icon">
                        <i class="fas fa-random"></i>
                    </div>

                    <div>
                        <span class="header-overline">
                            REDISTRIBUCIÓN
                        </span>

                        <h3>
                            Proceso #{{ $proceso->id }}
                        </h3>

                        <p>
                            Gestión y preparación de transferencias masivas
                        </p>
                    </div>

                </div>

                <div class="header-right">

                    <span class="process-status">
                        <span class="status-dot"></span>
                        APROBADO
                    </span>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- RESUMEN --}}
            {{-- ===================================================== --}}

            <div class="row mb-4">

                <div class="col-xl-3 col-md-6 mb-3">

                    <div class="summary-card">

                        <div class="summary-icon blue">
                            <i class="fas fa-box"></i>
                        </div>

                        <div>

                            <span class="summary-label">
                                PRODUCTOS
                            </span>

                            <strong>
                                {{ number_format($proceso->total_productos) }}
                            </strong>

                            <small>
                                Productos involucrados
                            </small>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-md-6 mb-3">

                    <div class="summary-card">

                        <div class="summary-icon purple">
                            <i class="fas fa-exchange-alt"></i>
                        </div>

                        <div>

                            <span class="summary-label">
                                TRANSFERENCIAS
                            </span>

                            <strong>
                                {{ number_format($proceso->total_movimientos) }}
                            </strong>

                            <small>
                                Movimientos aprobados
                            </small>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-md-6 mb-3">

                    <div class="summary-card">

                        <div class="summary-icon orange">
                            <i class="fas fa-cubes"></i>
                        </div>

                        <div>

                            <span class="summary-label">
                                UNIDADES
                            </span>

                            <strong>
                                {{ number_format($proceso->detalles->sum('cantidad')) }}
                            </strong>

                            <small>
                                Unidades a transferir
                            </small>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-md-6 mb-3">

                    <div class="summary-card">

                        <div class="summary-icon green">
                            <i class="fas fa-user"></i>
                        </div>

                        <div>

                            <span class="summary-label">
                                USUARIO
                            </span>

                            <strong class="user-value">
                                {{ $proceso->usuario ?? 'SISTEMA' }}
                            </strong>

                            <small>
                                Responsable del proceso
                            </small>

                        </div>

                    </div>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- INFORMACIÓN DEL PROCESO --}}
            {{-- ===================================================== --}}

            <div class="card enterprise-card mb-4">

                <div class="card-header enterprise-card-header">

                    <div>

                        <h5>
                            <i class="fas fa-info-circle"></i>
                            Información del proceso
                        </h5>

                        <small>
                            Datos generales de la redistribución aprobada
                        </small>

                    </div>

                    <span class="approved-badge">
                        <i class="fas fa-check-circle"></i>
                        APROBADO
                    </span>

                </div>


                <div class="card-body">

                    <div class="row">

                        <div class="col-md-3">

                            <div class="info-item">

                                <span>
                                    PROCESO
                                </span>

                                <strong>
                                    #{{ $proceso->id }}
                                </strong>

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="info-item">

                                <span>
                                    FECHA
                                </span>

                                <strong>
                                    {{ optional($proceso->fecha)->format('d/m/Y H:i') }}
                                </strong>

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="info-item">

                                <span>
                                    USUARIO
                                </span>

                                <strong>
                                    {{ $proceso->usuario ?? 'SISTEMA' }}
                                </strong>

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="info-item">

                                <span>
                                    ESTADO
                                </span>

                                <strong class="text-success">
                                    APROBADO
                                </strong>

                            </div>

                        </div>

                    </div>


                    @if ($proceso->observacion)
                        <div class="process-observation mt-4">

                            <i class="fas fa-comment-alt"></i>

                            <div>

                                <strong>
                                    Observación
                                </strong>

                                <p>
                                    {{ $proceso->observacion }}
                                </p>

                            </div>

                        </div>
                    @endif

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- TRANSFERENCIAS --}}
            {{-- ===================================================== --}}

            <div class="card enterprise-card">

                <div class="card-header enterprise-card-header">

                    <div>

                        <h5>
                            <i class="fas fa-list"></i>
                            Transferencias del proceso
                        </h5>

                        <small>
                            {{ $proceso->detalles->count() }}
                            movimientos incluidos en este proceso
                        </small>

                    </div>

                    <span class="movement-counter">
                        {{ number_format($proceso->detalles->count()) }}
                        movimientos
                    </span>

                </div>


                <div class="table-responsive">

                    <table class="table enterprise-table">

                        <thead>

                            <tr>

                                <th>#</th>

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

                            @forelse($proceso->detalles as $detalle)
                                <tr>

                                    <td class="row-number">
                                        {{ $loop->iteration }}
                                    </td>

                                    <td>

                                        <div class="product-info">

                                            <div class="product-mini-icon">
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


                                    <td>

                                        <div class="branch-cell">

                                            <span class="branch-origin">
                                                <i class="fas fa-arrow-up"></i>
                                            </span>

                                            <div>

                                                <strong>
                                                    {{ optional($detalle->origen)->suc_descri ?? $detalle->sucursal_origen }}
                                                </strong>

                                                <small>
                                                    ORIGEN
                                                </small>

                                            </div>

                                        </div>

                                    </td>


                                    <td>

                                        <div class="branch-cell">

                                            <span class="branch-destination">
                                                <i class="fas fa-arrow-down"></i>
                                            </span>

                                            <div>

                                                <strong>
                                                    {{ optional($detalle->destino)->suc_descri ?? $detalle->sucursal_destino }}
                                                </strong>

                                                <small>
                                                    DESTINO
                                                </small>

                                            </div>

                                        </div>

                                    </td>


                                    <td class="text-center">

                                        <span class="quantity">
                                            {{ number_format($detalle->cantidad) }}
                                        </span>

                                    </td>


                                    <td class="text-center">

                                        @if ($detalle->lote_id)
                                            <span class="detail-status generated">
                                                <span></span>
                                                EN LOTE
                                            </span>
                                        @else
                                            <span class="detail-status pending">
                                                <span></span>
                                                PENDIENTE
                                            </span>
                                        @endif

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="6">

                                        <div class="empty-process">

                                            <i class="fas fa-inbox"></i>

                                            <h5>
                                                No existen transferencias
                                            </h5>

                                            <p>
                                                Este proceso no contiene movimientos.
                                            </p>

                                        </div>

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>


                {{-- ================================================= --}}
                {{-- FOOTER ACCIONES --}}
                {{-- ================================================= --}}

                <div class="process-actions">

                    <a href="{{ route('RedistribucionSugeridas.index') }}" class="btn btn-light enterprise-btn">

                        <i class="fas fa-arrow-left"></i>

                        Volver

                    </a>


                    @php
                        $pendientes = $proceso->detalles->whereNull('lote_id')->where('estado', 'PENDIENTE')->count();
                    @endphp

                    @if ($pendientes > 0)
                        <form action="{{ route('RedistribucionSugeridas.generarLote', ['procesoId' => $proceso->id]) }}"
                            method="POST" style="display:inline;">

                            @csrf

                            <button type="submit" class="btn btn-sm btn-primary">

                                <i class="fas fa-layer-group"></i>

                                Generar lote

                            </button>

                        </form>
                    @else
                        <span class="already-generated">

                            <i class="fas fa-check-circle"></i>

                            Todos los movimientos ya tienen lote

                        </span>
                    @endif
                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- CSS --}}
    {{-- ========================================================= --}}

    <style>
        .redistribucion-proceso-page {
            background: #f5f7fa;
            min-height: 100vh;
            padding: 20px 0 40px;
        }


        /* HEADER */

        .process-header {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .header-overline {
            display: block;
            font-size: 10px;
            font-weight: 700;
            color: #2563eb;
            letter-spacing: 1px;
        }

        .process-header h3 {
            margin: 2px 0;
            font-size: 20px;
            font-weight: 700;
            color: #111827;
        }

        .process-header p {
            margin: 0;
            color: #9ca3af;
            font-size: 12px;
        }

        .process-status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #eaf8ef;
            color: #16803c;
            border-radius: 20px;
            padding: 7px 12px;
            font-size: 10px;
            font-weight: 700;
        }

        .process-status .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
        }


        /* SUMMARY */

        .summary-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 17px;
            display: flex;
            align-items: center;
            gap: 13px;
            min-height: 100px;
            box-shadow: 0 2px 7px rgba(15, 23, 42, .035);
        }

        .summary-icon {
            width: 44px;
            height: 44px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .summary-icon.blue {
            background: #eff6ff;
            color: #2563eb;
        }

        .summary-icon.purple {
            background: #f3e8ff;
            color: #7c3aed;
        }

        .summary-icon.orange {
            background: #fff7ed;
            color: #ea580c;
        }

        .summary-icon.green {
            background: #ecfdf5;
            color: #059669;
        }

        .summary-label {
            display: block;
            font-size: 9px;
            color: #9ca3af;
            font-weight: 700;
            letter-spacing: .7px;
        }

        .summary-card strong {
            display: block;
            color: #111827;
            font-size: 22px;
            line-height: 1.2;
        }

        .summary-card small {
            color: #9ca3af;
            font-size: 10px;
        }

        .user-value {
            font-size: 15px !important;
        }


        /* CARD */

        .enterprise-card {
            border: 1px solid #e5e7eb;
            border-radius: 11px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
            overflow: hidden;
        }

        .enterprise-card-header {
            background: #fff;
            border-bottom: 1px solid #eef0f3;
            padding: 17px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .enterprise-card-header h5 {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
            color: #111827;
        }

        .enterprise-card-header h5 i {
            color: #2563eb;
            margin-right: 7px;
        }

        .enterprise-card-header small {
            display: block;
            margin-top: 3px;
            color: #9ca3af;
            font-size: 10px;
        }

        .approved-badge {
            background: #eaf8ef;
            color: #16803c;
            border-radius: 20px;
            padding: 6px 10px;
            font-size: 9px;
            font-weight: 700;
        }

        .approved-badge i {
            margin-right: 4px;
        }

        .movement-counter {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            color: #6b7280;
            border-radius: 6px;
            padding: 6px 9px;
            font-size: 10px;
        }


        /* INFO */

        .info-item {
            padding: 4px 10px;
            border-left: 2px solid #e5e7eb;
        }

        .info-item span {
            display: block;
            font-size: 9px;
            color: #9ca3af;
            font-weight: 700;
            letter-spacing: .5px;
        }

        .info-item strong {
            display: block;
            margin-top: 4px;
            color: #374151;
            font-size: 13px;
        }

        .process-observation {
            background: #f8fafc;
            border: 1px solid #edf0f3;
            border-radius: 8px;
            padding: 12px 14px;
            display: flex;
            gap: 10px;
            color: #6b7280;
        }

        .process-observation>i {
            color: #2563eb;
            margin-top: 2px;
        }

        .process-observation strong {
            display: block;
            font-size: 11px;
            color: #374151;
        }

        .process-observation p {
            margin: 3px 0 0;
            font-size: 11px;
        }


        /* TABLE */

        .enterprise-table {
            margin: 0;
        }

        .enterprise-table thead th {
            background: #f8fafc;
            border-top: 0;
            border-bottom: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .6px;
            padding: 12px 14px;
            white-space: nowrap;
        }

        .enterprise-table tbody td {
            padding: 12px 14px;
            border-top: 1px solid #f1f3f5;
            vertical-align: middle;
            font-size: 11px;
        }

        .enterprise-table tbody tr:hover {
            background: #fafcff;
        }

        .row-number {
            color: #9ca3af;
            font-size: 10px !important;
        }


        /* PRODUCT */

        .product-info {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .product-mini-icon {
            width: 32px;
            height: 32px;
            background: #f3f4f6;
            color: #6b7280;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-info strong {
            display: block;
            color: #111827;
        }

        .product-info small,
        .branch-cell small {
            display: block;
            color: #9ca3af;
            font-size: 8px;
            margin-top: 2px;
        }


        /* BRANCH */

        .branch-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .branch-cell strong {
            color: #374151;
            font-size: 11px;
        }

        .branch-origin,
        .branch-destination {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
        }

        .branch-origin {
            background: #fff1f2;
            color: #dc2626;
        }

        .branch-destination {
            background: #eff6ff;
            color: #2563eb;
        }


        /* QUANTITY */

        .quantity {
            display: inline-block;
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #dbeafe;
            border-radius: 6px;
            padding: 5px 9px;
            font-size: 12px;
            font-weight: 700;
        }


        /* STATUS */

        .detail-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border-radius: 15px;
            padding: 5px 8px;
            font-size: 8px;
            font-weight: 700;
        }

        .detail-status span {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: currentColor;
        }

        .detail-status.pending {
            background: #fff7df;
            color: #b7791f;
        }

        .detail-status.generated {
            background: #e8f4ff;
            color: #1671c5;
        }


        /* ACTIONS */

        .process-actions {
            border-top: 1px solid #eef0f3;
            padding: 15px 18px;
            background: #fafbfc;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .enterprise-btn {
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #6b7280;
            font-size: 12px;
            font-weight: 600;
            border-radius: 7px;
            padding: 8px 14px;
        }

        .enterprise-btn:hover {
            background: #f8fafc;
        }

        .enterprise-btn-main {
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            padding: 9px 15px;
        }

        .enterprise-btn-main i {
            margin-right: 5px;
        }

        .btn-count {
            margin-left: 6px;
            background: rgba(255, 255, 255, .2);
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 9px;
        }

        .already-generated {
            color: #16803c;
            font-size: 11px;
            font-weight: 600;
        }


        /* EMPTY */

        .empty-process {
            padding: 50px;
            text-align: center;
        }

        .empty-process i {
            font-size: 30px;
            color: #d1d5db;
        }

        .empty-process h5 {
            margin-top: 12px;
            color: #374151;
        }

        .empty-process p {
            color: #9ca3af;
            font-size: 11px;
        }


        @media(max-width: 768px) {

            .process-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .process-actions {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }

        }
    </style>
@endsection
