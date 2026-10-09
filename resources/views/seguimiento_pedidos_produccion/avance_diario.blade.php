@extends('layouts.app')

@section('content')
<section class="content-header pb-2">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <div class="day-eyebrow">PRODUCCIÓN · EVOLUCIÓN DIARIA</div>
            <h1 class="day-title mb-1">
                <i class="fas fa-calendar-day mr-2"></i>
                Avance por día
            </h1>
            <p class="text-muted mb-0">
                Qué OT avanzaron cada día, hasta qué proceso llegaron y cuáles alcanzaron
                <strong>TERMINACION - INGRESO TERMINACION</strong>.
            </p>
        </div>

        <div class="mt-2 mt-md-0">
            <a href="{{ route('seguimiento-produccion.informe-gerencial') }}"
               class="btn btn-outline-danger mr-1">
                <i class="fas fa-chart-line mr-1"></i>
                Informe gerencial
            </a>

            <a href="{{ route('seguimiento-produccion.index') }}"
               class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i>
                Seguimiento Producción
            </a>
        </div>
    </div>
</div>
</section>

<section class="content">
<div class="container-fluid">

    <div class="card day-filter-card mb-3">
        <div class="card-body pb-2">
            <form method="GET"
                  action="{{ route('seguimiento-produccion.avance-diario') }}">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-md-4 mb-2">
                        <label class="small font-weight-bold">Desde</label>
                        <input type="date"
                               name="desde"
                               class="form-control"
                               value="{{ $fechaDesde->format('Y-m-d') }}">
                    </div>

                    <div class="col-lg-3 col-md-4 mb-2">
                        <label class="small font-weight-bold">Hasta</label>
                        <input type="date"
                               name="hasta"
                               class="form-control"
                               value="{{ $fechaHasta->format('Y-m-d') }}">
                    </div>

                    <div class="col-lg-4 col-md-4 mb-2">
                        <label class="small font-weight-bold">Pedido P</label>
                        <select name="pedido" class="form-control">
                            <option value="">Todos los pedidos P</option>

                            @foreach($pedidosDisponibles as $pedido)
                                <option value="{{ $pedido->id }}"
                                        {{ (int) $pedidoId === (int) $pedido->id ? 'selected' : '' }}>
                                    {{ $pedido->nro_pedido }}
                                    @if($pedido->fecha_pedido)
                                        · {{ CarbonCarbon::parse($pedido->fecha_pedido)->format('d/m/Y') }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 mb-2 text-lg-right">
                        <a href="{{ route('seguimiento-produccion.avance-diario') }}"
                           class="btn btn-light border mr-1">
                            <i class="fas fa-eraser"></i>
                        </a>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search mr-1"></i>
                            Consultar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="alert alert-light border day-reading mb-3">
        <strong>Lectura:</strong>
        una OT se cuenta una sola vez por día aunque tenga varios registros de trazabilidad.
        Si pasó por varios procesos en la misma jornada, se muestra el proceso de mayor avance.
        <strong>Prendas con avance</strong> representa la cantidad ordenada de las OT que tuvieron movimiento,
        no una suma física de cada proceso.
    </div>

    <div class="row mb-2">
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="day-kpi">
                <small>Días consultados</small>
                <strong>{{ number_format($resumen->dias_consultados,0,',','.') }}</strong>
                <span>{{ number_format($resumen->dias_con_movimiento,0,',','.') }} con movimiento</span>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="day-kpi day-kpi-info">
                <small>OT con movimiento</small>
                <strong>{{ number_format($resumen->ots_con_movimiento,0,',','.') }}</strong>
                <span>OT distintas en el período</span>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="day-kpi day-kpi-purple">
                <small>Eventos de trazabilidad</small>
                <strong>{{ number_format($resumen->eventos,0,',','.') }}</strong>
                <span>registros de proceso</span>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="day-kpi day-kpi-success">
                <small>OT cerradas</small>
                <strong>{{ number_format($resumen->ots_cerradas,0,',','.') }}</strong>
                <span>alcanzaron Ingreso Terminación</span>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="day-kpi day-kpi-success">
                <small>Prendas cerradas</small>
                <strong>{{ number_format($resumen->prendas_cerradas,0,',','.') }}</strong>
                <span>volumen de OT cerradas</span>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="day-kpi day-kpi-warning">
                <small>Promedio OT / día</small>
                <strong>{{ number_format($resumen->promedio_ot_dia,1,',','.') }}</strong>
                <span>días que tuvieron movimiento</span>
            </div>
        </div>
    </div>

    <div class="card shadow-sm day-table-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="card-title float-none mb-0 font-weight-bold">
                    <i class="fas fa-chart-bar mr-1 text-primary"></i>
                    Evolución diaria
                </h3>
                <small class="text-muted">
                    {{ $fechaDesde->format('d/m/Y') }} al {{ $fechaHasta->format('d/m/Y') }}
                    · Base: {{ number_format($resumen->total_ot_base,0,',','.') }} OT
                </small>
            </div>

            <div class="day-legend mt-2 mt-md-0">
                <span><i class="fas fa-circle text-primary"></i> Movimiento</span>
                <span><i class="fas fa-circle text-success"></i> Cerró Producción</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 day-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th class="text-center">Pedidos</th>
                        <th class="text-center">OT con avance</th>
                        <th class="text-center">Prendas con avance</th>
                        <th class="text-center">Eventos</th>
                        <th class="text-center">Nuevos ingresos</th>
                        <th class="text-center">Prendas cerradas</th>
                        <th>Proceso predominante</th>
                        <th class="text-center">Avance acumulado</th>
                        <th class="text-center">Detalle</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($dias as $dia)
                        @php
                            $tieneMovimiento = $dia->ots_movimiento > 0;
                            $collapseId = 'dia-' . str_replace('-', '', $dia->fecha);
                        @endphp

                        <tr class="{{ !$tieneMovimiento ? 'day-row-empty' : '' }}">
                            <td>
                                <strong>{{ $dia->fecha_label }}</strong>
                                <small class="d-block text-muted">
                                    {{ $dia->dia_semana }}
                                </small>
                            </td>

                            <td class="text-center">
                                {{ number_format($dia->pedidos_movimiento,0,',','.') }}
                            </td>

                            <td class="text-center">
                                @if($dia->ots_movimiento > 0)
                                    <span class="day-number day-number-blue">
                                        {{ number_format($dia->ots_movimiento,0,',','.') }}
                                    </span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>

                            <td class="text-center">
                                {{ number_format($dia->prendas_movimiento,0,',','.') }}
                            </td>

                            <td class="text-center">
                                {{ number_format($dia->eventos,0,',','.') }}
                            </td>

                            <td class="text-center">
                                @if($dia->ots_cierre > 0)
                                    <span class="day-number day-number-green">
                                        +{{ number_format($dia->ots_cierre,0,',','.') }}
                                    </span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>

                            <td class="text-center">
                                {{ number_format($dia->prendas_cierre,0,',','.') }}
                            </td>

                            <td>
                                @if($dia->proceso_principal)
                                    <span class="day-process">
                                        {{ $dia->proceso_principal }}
                                    </span>
                                @else
                                    <span class="text-muted">Sin movimiento</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <strong>{{ number_format($dia->avance_acumulado,1,',','.') }}%</strong>
                                <small class="d-block text-muted">
                                    {{ number_format($dia->ots_acumuladas,0,',','.') }}
                                    / {{ number_format($resumen->total_ot_base,0,',','.') }} OT
                                </small>

                                <div class="day-progress mt-1">
                                    <div style="width:{{ min(100,$dia->avance_acumulado) }}%"></div>
                                </div>
                            </td>

                            <td class="text-center">
                                @if($tieneMovimiento)
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-toggle="collapse"
                                            data-target="#{{ $collapseId }}"
                                            aria-expanded="false"
                                            aria-controls="{{ $collapseId }}">
                                        <i class="fas fa-eye mr-1"></i>
                                        Ver
                                    </button>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>

                        @if($tieneMovimiento)
                            <tr class="day-detail-row">
                                <td colspan="10" class="p-0 border-0">
                                    <div class="collapse" id="{{ $collapseId }}">
                                        <div class="day-detail">
                                            <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                                                <div>
                                                    <div class="day-detail-title">
                                                        Movimiento del {{ $dia->fecha_label }}
                                                    </div>
                                                    <small class="text-muted">
                                                        Una fila por OT; se muestra el proceso de mayor avance alcanzado ese día.
                                                    </small>
                                                </div>

                                                <div class="day-process-badges mt-2 mt-md-0">
                                                    @foreach($dia->por_proceso as $proceso)
                                                        <span>
                                                            {{ $proceso->proceso }}
                                                            <strong>{{ $proceso->ots }} OT</strong>
                                                            · {{ number_format($proceso->prendas,0,',','.') }} prendas
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover mb-0 day-detail-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Pedido</th>
                                                            <th>OT</th>
                                                            <th>Código / descripción</th>
                                                            <th class="text-center">Cantidad</th>
                                                            <th>Proceso alcanzado ese día</th>
                                                            <th class="text-center">Eventos</th>
                                                            <th class="text-center">Cierre Producción</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        @foreach($dia->movimientos as $mov)
                                                            <tr>
                                                                <td>
                                                                    @foreach($mov->pedidos as $nroPedido)
                                                                        <span class="badge badge-light border mr-1">
                                                                            {{ $nroPedido }}
                                                                        </span>
                                                                    @endforeach
                                                                </td>

                                                                <td>
                                                                    <strong>{{ $mov->nro_ot }}</strong>
                                                                </td>

                                                                <td>
                                                                    <strong>{{ $mov->codigo }}</strong>
                                                                    <small class="d-block text-muted">
                                                                        {{ $mov->descripcion }}
                                                                    </small>
                                                                </td>

                                                                <td class="text-center">
                                                                    {{ number_format($mov->cantidad_orden,0,',','.') }}
                                                                </td>

                                                                <td>
                                                                    <span class="day-process">
                                                                        {{ $mov->proceso ?: 'SIN PROCESO' }}
                                                                    </span>
                                                                </td>

                                                                <td class="text-center">
                                                                    {{ number_format($mov->eventos,0,',','.') }}
                                                                </td>

                                                                <td class="text-center">
                                                                    @if($mov->cerro_hoy)
                                                                        <span class="badge badge-success px-2 py-1">
                                                                            <i class="fas fa-check mr-1"></i>
                                                                            INGRESÓ
                                                                        </span>
                                                                    @else
                                                                        <span class="text-muted">-</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="10"
                                class="text-center text-muted py-5">
                                No hay información para el período seleccionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
</section>
@endsection

@push('page_css')
<style>
.day-eyebrow{
    font-size:10px;
    font-weight:900;
    letter-spacing:.12em;
    color:#94a3b8;
}
.day-title{
    font-size:28px;
    font-weight:900;
    color:#0f172a;
}
.day-filter-card,
.day-table-card{
    border:1px solid #e5eaf0;
    border-radius:14px;
}
.day-reading{
    border-radius:10px;
    font-size:12px;
    color:#64748b;
}
.day-kpi{
    height:100%;
    min-height:112px;
    background:#fff;
    border:1px solid #e5eaf0;
    border-left:4px solid #2563eb;
    border-radius:12px;
    padding:13px 14px;
    box-shadow:0 4px 12px rgba(15,23,42,.035);
}
.day-kpi-info{border-left-color:#0ea5e9}
.day-kpi-purple{border-left-color:#7c3aed}
.day-kpi-success{border-left-color:#22c55e}
.day-kpi-warning{border-left-color:#f59e0b}
.day-kpi small{
    display:block;
    text-transform:uppercase;
    font-size:9px;
    letter-spacing:.05em;
    font-weight:850;
    color:#94a3b8;
}
.day-kpi strong{
    display:block;
    margin:5px 0 3px;
    font-size:25px;
    line-height:1;
    color:#0f172a;
}
.day-kpi span{
    display:block;
    font-size:10px;
    line-height:1.25;
    color:#64748b;
}
.day-legend{
    display:flex;
    gap:12px;
    font-size:10px;
    color:#64748b;
}
.day-table th,
.day-detail-table th{
    background:#f8fafc;
    color:#64748b;
    font-size:9px;
    text-transform:uppercase;
    letter-spacing:.04em;
    white-space:nowrap;
    vertical-align:middle!important;
}
.day-table td{
    font-size:11px;
    vertical-align:middle!important;
}
.day-row-empty{
    background:#fafafa;
    color:#94a3b8;
}
.day-number{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:31px;
    padding:3px 7px;
    border-radius:999px;
    font-weight:900;
}
.day-number-blue{
    background:#eff6ff;
    color:#1d4ed8;
}
.day-number-green{
    background:#ecfdf5;
    color:#047857;
}
.day-process{
    display:inline-block;
    border-radius:8px;
    background:#eff6ff;
    color:#1d4ed8;
    border:1px solid #dbeafe;
    padding:5px 7px;
    font-size:10px;
    line-height:1.25;
    font-weight:800;
}
.day-progress{
    height:4px;
    background:#e2e8f0;
    border-radius:999px;
    overflow:hidden;
}
.day-progress div{
    height:100%;
    background:#22c55e;
    border-radius:999px;
}
.day-detail{
    background:#f8fafc;
    border-top:1px solid #e2e8f0;
    border-bottom:1px solid #e2e8f0;
    padding:16px 18px;
}
.day-detail-title{
    font-size:13px;
    font-weight:900;
    color:#0f172a;
}
.day-process-badges{
    display:flex;
    flex-wrap:wrap;
    gap:5px;
    justify-content:flex-end;
}
.day-process-badges span{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:999px;
    padding:4px 8px;
    font-size:9px;
    color:#64748b;
}
.day-process-badges strong{
    color:#0f172a;
}
.day-detail-table td{
    font-size:10px;
    vertical-align:middle!important;
}
@media(max-width:767.98px){
    .day-title{font-size:23px}
    .day-process-badges{justify-content:flex-start}
}
</style>
@endpush
