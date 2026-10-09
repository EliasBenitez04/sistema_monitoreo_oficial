@extends('layouts.app')

@section('content')
<section class="content-header pb-2">
<div class="container-fluid it-audit-shell">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <div class="it-audit-eyebrow">CONTROL DE PEDIDOS · INGRESO TERMINACIÓN</div>
            <h1 class="it-audit-title mb-1">
                <i class="fas fa-search-location mr-2"></i>
                Seguimiento de salidas
            </h1>
            <p class="text-muted mb-0">
                Controla qué OT se enviaron a <strong>TERMINACION - TERMINACION</strong>
                en la fecha de un pedido IT y si cada salida estaba realmente solicitada.
            </p>
        </div>

        <div class="mt-2 mt-md-0">
            <a href="{{ route('seguimiento-ingreso-terminacion.informe-gerencial') }}"
               class="btn btn-outline-danger mr-1">
                <i class="fas fa-chart-line mr-1"></i>
                Informe gerencial
            </a>

            <a href="{{ route('seguimiento-ingreso-terminacion.index') }}"
               class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i>
                Volver
            </a>
        </div>
    </div>
</div>
</section>

<section class="content">
<div class="container-fluid it-audit-shell">

    <div class="card it-audit-filter mb-3">
        <div class="card-body pb-2">
            <form method="GET"
                  action="{{ route('seguimiento-ingreso-terminacion.seguimiento-diario') }}">
                <div class="row align-items-end">
                    <div class="col-lg-8 col-md-8 mb-2">
                        <label class="small font-weight-bold">
                            Pedido IT a controlar
                        </label>

                        <select name="pedido" class="form-control">
                            @forelse($pedidosDisponibles as $item)
                                <option value="{{ $item->id }}"
                                        {{ (int) $pedidoId === (int) $item->id ? 'selected' : '' }}>
                                    {{ $item->nro_pedido }}
                                    @if($item->fecha_pedido)
                                        · {{ date('d/m/Y', strtotime($item->fecha_pedido)) }}
                                    @endif
                                </option>
                            @empty
                                <option value="">Sin pedidos IT</option>
                            @endforelse
                        </select>
                    </div>

                    <div class="col-lg-4 col-md-4 mb-2 text-md-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search mr-1"></i>
                            Revisar salidas de esa fecha
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(!$pedido)
        <div class="alert alert-info">
            Todavía no hay pedidos IT con fecha para realizar el control.
        </div>
    @else
        <div class="it-audit-hero mb-3">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <div class="it-audit-hero-label">
                        PEDIDO SELECCIONADO
                    </div>

                    <div class="d-flex align-items-end flex-wrap">
                        <div class="mr-4">
                            <div class="it-audit-order">
                                {{ $pedido->nro_pedido }}
                            </div>
                            <div class="text-muted">
                                Fecha del pedido:
                                <strong>{{ $fechaControl->format('d/m/Y') }}</strong>
                            </div>
                        </div>

                        <div class="it-audit-order-meta">
                            <strong>{{ number_format($resumen->ots_pedidas,0,',','.') }} OT</strong>
                            <span>{{ number_format($resumen->prendas_pedidas,0,',','.') }} prendas solicitadas</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 mt-3 mt-lg-0">
                    <div class="it-audit-request-progress">
                        <div>
                            <small>OT del pedido que salieron ese mismo día</small>
                            <strong>
                                {{ number_format($resumen->pedido_cubierto_mismo_dia,0,',','.') }}
                                /
                                {{ number_format($resumen->ots_pedidas,0,',','.') }}
                            </strong>
                        </div>

                        <div class="it-audit-percent">
                            {{ number_format($resumen->porcentaje_pedido_mismo_dia,1,',','.') }}%
                        </div>
                    </div>

                    <div class="it-audit-progress mt-2">
                        <div style="width:{{ min(100,$resumen->porcentaje_pedido_mismo_dia) }}%"></div>
                    </div>

                    <small class="text-muted d-block mt-1">
                        Este porcentaje mide sólo lo que realmente salió ese día;
                        una OT que ya había salido antes no cuenta como trabajo del día.
                    </small>
                </div>
            </div>
        </div>

        @php
            $porcentajeJustificado = $resumen->salidas_reales > 0
                ? round(($resumen->salidas_con_pedido / $resumen->salidas_reales) * 100, 1)
                : 0;
        @endphp

        <div class="row mb-2">
            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="it-audit-kpi is-dark">
                    <small>Salidas reales</small>
                    <strong>{{ number_format($resumen->salidas_reales,0,',','.') }}</strong>
                    <span>{{ number_format($resumen->prendas_salida,0,',','.') }} prendas</span>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="it-audit-kpi is-success">
                    <small>Con pedido vigente</small>
                    <strong>{{ number_format($resumen->salidas_con_pedido,0,',','.') }}</strong>
                    <span>salidas justificadas</span>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="it-audit-kpi is-primary">
                    <small>Del {{ $pedido->nro_pedido }}</small>
                    <strong>{{ number_format($resumen->salidas_pedido_seleccionado,0,',','.') }}</strong>
                    <span>pertenecían al pedido elegido</span>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="it-audit-kpi is-info">
                    <small>Otros pedidos IT</small>
                    <strong>{{ number_format($resumen->salidas_otros_pedidos,0,',','.') }}</strong>
                    <span>también eran necesarias</span>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="it-audit-kpi {{ $resumen->salidas_sin_pedido > 0 ? 'is-danger' : 'is-success' }}">
                    <small>Sin pedido vigente</small>
                    <strong>{{ number_format($resumen->salidas_sin_pedido,0,',','.') }}</strong>
                    <span>{{ $resumen->salidas_sin_pedido > 0 ? 'requieren revisión' : 'sin desvíos' }}</span>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                <div class="it-audit-kpi {{ $porcentajeJustificado >= 100 ? 'is-success' : 'is-warning' }}">
                    <small>Salida justificada</small>
                    <strong>{{ number_format($porcentajeJustificado,1,',','.') }}%</strong>
                    <span>con pedido existente al salir</span>
                </div>
            </div>
        </div>

        @if($resumen->salidas_sin_pedido > 0)
            <div class="alert alert-danger it-audit-alert">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Atención:</strong>
                {{ number_format($resumen->salidas_sin_pedido,0,',','.') }}
                de {{ number_format($resumen->salidas_reales,0,',','.') }} OT
                que salieron el {{ $fechaControl->format('d/m/Y') }}
                no tenían un pedido IT vigente en ese momento.
                Revisá las filas rojas.
            </div>
        @elseif($resumen->salidas_reales > 0)
            <div class="alert alert-success it-audit-alert">
                <i class="fas fa-check-circle mr-2"></i>
                <strong>Correcto:</strong>
                todas las OT que salieron el {{ $fechaControl->format('d/m/Y') }}
                estaban justificadas por algún pedido IT vigente.
            </div>
        @endif

        <div class="card shadow-sm it-audit-card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h3 class="card-title float-none mb-0 font-weight-bold">
                        <i class="fas fa-sign-out-alt mr-1 text-primary"></i>
                        OT que realmente salieron el {{ $fechaControl->format('d/m/Y') }}
                    </h3>
                    <small class="text-muted">
                        Todas las primeras entradas a TERMINACION - TERMINACION de esa fecha,
                        aunque la OT no figure en ningún pedido.
                    </small>
                </div>

                <span class="badge badge-light border p-2 mt-2 mt-md-0">
                    {{ number_format($resumen->salidas_reales,0,',','.') }} OT
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0 it-audit-table">
                    <thead>
                        <tr>
                            <th>OT</th>
                            <th>Código / descripción</th>
                            <th class="text-center">Cant. OT</th>
                            <th class="text-center">Resultado proceso</th>
                            <th>Pedido que justifica la salida</th>
                            <th>Control</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($salidas as $salida)
                        <tr class="{{ !$salida->tiene_pedido ? 'it-audit-row-danger' : '' }}">
                            <td>
                                <strong class="it-audit-ot">
                                    {{ $salida->nro_ot }}
                                </strong>
                            </td>

                            <td>
                                <strong>{{ $salida->codigo ?: '-' }}</strong>
                                <small class="d-block text-muted">
                                    {{ $salida->descripcion }}
                                </small>
                            </td>

                            <td class="text-center">
                                <strong>{{ number_format($salida->cantidad_orden,0,',','.') }}</strong>
                            </td>

                            <td class="text-center">
                                {{ number_format($salida->resultado_dia,0,',','.') }}

                                @if((int) $salida->eventos_dia > 1)
                                    <small class="d-block text-muted">
                                        {{ number_format($salida->eventos_dia,0,',','.') }} registros
                                    </small>
                                @endif
                            </td>

                            <td>
                                @if($salida->pedidos_vigentes->isNotEmpty())
                                    @foreach($salida->pedidos_vigentes as $pedidoSalida)
                                        <span class="it-audit-order-badge is-valid">
                                            <i class="fas fa-check mr-1"></i>
                                            PEDIDO {{ $pedidoSalida->nro }}
                                        </span>

                                        <small class="d-block text-muted mt-1">
                                            {{ $pedidoSalida->fecha
                                                ? date('d/m/Y', strtotime($pedidoSalida->fecha))
                                                : '-' }}

                                            @if((int) $pedidoSalida->id === (int) $pedido->id)
                                                · pedido seleccionado
                                            @endif
                                        </small>
                                    @endforeach
                                @else
                                    <span class="it-audit-order-badge is-missing">
                                        <i class="fas fa-times mr-1"></i>
                                        SIN PEDIDO VIGENTE
                                    </span>

                                    @if($salida->pedidos_posteriores->isNotEmpty())
                                        <div class="mt-2">
                                            @foreach($salida->pedidos_posteriores as $pedidoPosterior)
                                                <span class="it-audit-order-badge is-future">
                                                    PEDIDO POSTERIOR {{ $pedidoPosterior->nro }}
                                                </span>

                                                <small class="d-block text-muted">
                                                    recién fue pedido el
                                                    {{ date('d/m/Y', strtotime($pedidoPosterior->fecha)) }}
                                                </small>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif
                            </td>

                            <td>
                                @if($salida->clasificacion === 'PEDIDO SELECCIONADO')
                                    <span class="badge badge-primary px-2 py-1">
                                        DEL {{ $pedido->nro_pedido }}
                                    </span>
                                @elseif($salida->clasificacion === 'OTRO PEDIDO')
                                    <span class="badge badge-success px-2 py-1">
                                        PEDIDO
                                    </span>
                                @elseif($salida->clasificacion === 'PEDIDO POSTERIOR')
                                    <span class="badge badge-danger px-2 py-1">
                                        NO PEDIDA AL SALIR
                                    </span>
                                @else
                                    <span class="badge badge-danger px-2 py-1">
                                        SIN PEDIDO
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6"
                                class="text-center text-muted py-5">
                                No hubo OT que alcanzaran TERMINACION - TERMINACION
                                el {{ $fechaControl->format('d/m/Y') }}.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card shadow-sm it-audit-card">
            <div class="card-header bg-white">
                <h3 class="card-title float-none mb-0 font-weight-bold">
                    <i class="fas fa-clipboard-list mr-1 text-info"></i>
                    OT solicitadas en {{ $pedido->nro_pedido }}
                </h3>
                <small class="text-muted">
                    Referencia del pedido seleccionado para saber qué pasó con cada una.
                </small>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0 it-audit-table">
                    <thead>
                        <tr>
                            <th>OT</th>
                            <th>Código / descripción</th>
                            <th class="text-center">Cantidad</th>
                            <th>Situación respecto al {{ $fechaControl->format('d/m/Y') }}</th>
                            <th>Fecha real TERMINACION - TERMINACION</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($otsPedido as $ot)
                        @php
                            $badge = 'badge-secondary';

                            if ($ot->estado_control === 'SALIO ESE DIA') {
                                $badge = 'badge-success';
                            } elseif ($ot->estado_control === 'YA HABIA SALIDO') {
                                $badge = 'badge-info';
                            } elseif ($ot->estado_control === 'SALIO DESPUES') {
                                $badge = 'badge-warning';
                            } elseif ($ot->estado_control === 'PENDIENTE') {
                                $badge = 'badge-danger';
                            }
                        @endphp

                        <tr>
                            <td>
                                <strong class="it-audit-ot">
                                    {{ $ot->nro_ot }}
                                </strong>
                            </td>

                            <td>
                                <strong>{{ $ot->codigo ?: '-' }}</strong>
                                <small class="d-block text-muted">
                                    {{ $ot->descripcion }}
                                </small>
                            </td>

                            <td class="text-center">
                                {{ number_format($ot->cantidad_orden,0,',','.') }}
                            </td>

                            <td>
                                <span class="badge {{ $badge }} px-2 py-1">
                                    {{ $ot->estado_control }}
                                </span>
                            </td>

                            <td>
                                {{ $ot->fecha_cierre
                                    ? date('d/m/Y', strtotime($ot->fecha_cierre))
                                    : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5"
                                class="text-center text-muted py-4">
                                El pedido seleccionado no tiene OT vinculadas.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
</section>
@endsection

@push('page_styles')
<style>
.it-audit-shell{max-width:1800px}
.it-audit-eyebrow{
    font-size:10px;
    font-weight:900;
    letter-spacing:.12em;
    color:#94a3b8;
}
.it-audit-title{
    font-size:27px;
    font-weight:900;
    color:#0f172a;
}
.it-audit-filter,
.it-audit-card{
    border:1px solid #e2e8f0;
    border-radius:14px;
}
.it-audit-hero{
    background:#fff;
    border:1px solid #e2e8f0;
    border-left:5px solid #2563eb;
    border-radius:14px;
    padding:16px 18px;
}
.it-audit-hero-label{
    font-size:9px;
    font-weight:900;
    letter-spacing:.1em;
    color:#64748b;
}
.it-audit-order{
    margin-top:2px;
    font-size:30px;
    font-weight:950;
    color:#0f172a;
}
.it-audit-order-meta{
    border-left:1px solid #e2e8f0;
    padding-left:18px;
}
.it-audit-order-meta strong{
    display:block;
    font-size:18px;
}
.it-audit-order-meta span{
    font-size:11px;
    color:#64748b;
}
.it-audit-request-progress{
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.it-audit-request-progress small{
    display:block;
    font-size:10px;
    color:#64748b;
}
.it-audit-request-progress strong{
    display:block;
    font-size:21px;
}
.it-audit-percent{
    font-size:25px;
    font-weight:950;
    color:#2563eb;
}
.it-audit-progress{
    height:7px;
    background:#e2e8f0;
    border-radius:999px;
    overflow:hidden;
}
.it-audit-progress div{
    height:100%;
    background:#2563eb;
    border-radius:999px;
}
.it-audit-kpi{
    height:100%;
    min-height:108px;
    background:#fff;
    border:1px solid #e2e8f0;
    border-left:4px solid #64748b;
    border-radius:11px;
    padding:13px 14px;
}
.it-audit-kpi.is-dark{border-left-color:#334155}
.it-audit-kpi.is-success{border-left-color:#22c55e}
.it-audit-kpi.is-primary{border-left-color:#2563eb}
.it-audit-kpi.is-info{border-left-color:#06b6d4}
.it-audit-kpi.is-danger{border-left-color:#ef4444}
.it-audit-kpi.is-warning{border-left-color:#f59e0b}
.it-audit-kpi small{
    display:block;
    color:#94a3b8;
    font-size:9px;
    font-weight:900;
    text-transform:uppercase;
}
.it-audit-kpi strong{
    display:block;
    margin:5px 0 3px;
    font-size:25px;
    line-height:1;
    color:#0f172a;
}
.it-audit-kpi span{
    display:block;
    color:#64748b;
    font-size:10px;
}
.it-audit-alert{
    border-radius:11px;
    font-size:12px;
}
.it-audit-table th{
    background:#f8fafc;
    color:#64748b;
    font-size:9px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.04em;
    white-space:nowrap;
    vertical-align:middle!important;
}
.it-audit-table td{
    font-size:11px;
    vertical-align:middle!important;
}
.it-audit-ot{
    font-size:13px;
    color:#0f172a;
}
.it-audit-row-danger{
    background:#fff7f7;
}
.it-audit-row-danger:hover{
    background:#fff0f0!important;
}
.it-audit-order-badge{
    display:inline-block;
    border-radius:999px;
    padding:5px 8px;
    font-size:9px;
    font-weight:900;
    margin:1px 3px 1px 0;
}
.it-audit-order-badge.is-valid{
    background:#dcfce7;
    color:#166534;
    border:1px solid #bbf7d0;
}
.it-audit-order-badge.is-missing{
    background:#fee2e2;
    color:#991b1b;
    border:1px solid #fecaca;
}
.it-audit-order-badge.is-future{
    background:#fef3c7;
    color:#92400e;
    border:1px solid #fde68a;
}
@media(max-width:767.98px){
    .it-audit-title{font-size:22px}
    .it-audit-order{font-size:25px}
    .it-audit-order-meta{
        border-left:0;
        padding-left:0;
        margin-top:10px;
        width:100%;
    }
}
</style>
@endpush
