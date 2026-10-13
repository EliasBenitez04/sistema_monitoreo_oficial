@extends('layouts.app')

@section('content')
<section class="content-header pb-2">
<div class="container-fluid it-day-shell">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <div class="it-day-eyebrow">CONTROL DE PEDIDOS · INGRESO TERMINACIÓN</div>
            <h1 class="it-day-title mb-1">
                <i class="fas fa-calendar-check mr-2"></i>
                Control diario de OT
            </h1>
            <p class="text-muted mb-0">
                Elegí una fecha para ver las OT que realmente salieron en
                <strong>TERMINACION - TERMINACION</strong> y a qué pedido IT corresponden.
            </p>
        </div>

        <div class="mt-2 mt-md-0">
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
<div class="container-fluid it-day-shell">

    <div class="card it-day-filter mb-3">
        <div class="card-body">
            <form method="GET"
                  action="{{ route('seguimiento-ingreso-terminacion.seguimiento-diario') }}">
                <div class="row align-items-end">
                    <div class="col-lg-5 col-md-6 mb-2 mb-md-0">
                        <label class="small font-weight-bold mb-1">
                            Fecha a controlar
                        </label>

                        <input type="date"
                               name="fecha"
                               class="form-control"
                               value="{{ $fechaControl->format('Y-m-d') }}">
                    </div>

                    <div class="col-lg-3 col-md-4">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search mr-1"></i>
                            Ver OT de ese día
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="it-day-hero mb-3">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="it-day-hero-label">FECHA SELECCIONADA</div>
                <div class="it-day-date">
                    {{ $fechaControl->format('d/m/Y') }}
                </div>
                <div class="text-muted">
                    OT registradas ese día en TERMINACION - TERMINACION.
                </div>
            </div>

            <div class="col-lg-5 mt-3 mt-lg-0">
                <div class="row text-center">
                    <div class="col-4">
                        <small class="d-block text-muted">OT</small>
                        <strong class="it-day-hero-number">
                            {{ number_format($resumen->salidas_reales,0,',','.') }}
                        </strong>
                    </div>
                    <div class="col-4">
                        <small class="d-block text-muted">Cantidad OT</small>
                        <strong class="it-day-hero-number">
                            {{ number_format($resumen->prendas_programadas,0,',','.') }}
                        </strong>
                    </div>
                    <div class="col-4">
                        <small class="d-block text-muted">Salida real</small>
                        <strong class="it-day-hero-number">
                            {{ number_format($resumen->prendas_salida,0,',','.') }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-1">
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="it-day-kpi is-dark">
                <small>OT DEL DÍA</small>
                <strong>{{ number_format($resumen->salidas_reales,0,',','.') }}</strong>
                <span>salidas registradas</span>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-3">
            <div class="it-day-kpi is-success">
                <small>CON PEDIDO</small>
                <strong>{{ number_format($resumen->salidas_con_pedido,0,',','.') }}</strong>
                <span>OT justificadas por un pedido IT</span>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-3">
            <div class="it-day-kpi {{ $resumen->salidas_sin_pedido > 0 ? 'is-danger' : 'is-success' }}">
                <small>SIN PEDIDO</small>
                <strong>{{ number_format($resumen->salidas_sin_pedido,0,',','.') }}</strong>
                <span>{{ $resumen->salidas_sin_pedido > 0 ? 'OT para revisar' : 'sin desvíos' }}</span>
            </div>
        </div>
    </div>

    @if($resumen->salidas_sin_pedido > 0)
        <div class="alert alert-danger it-day-alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <strong>Atención:</strong>
            hay {{ number_format($resumen->salidas_sin_pedido,0,',','.') }}
            OT que salieron el {{ $fechaControl->format('d/m/Y') }}
            y no estaban dentro de ningún pedido IT vigente a esa fecha.
        </div>
    @elseif($resumen->salidas_reales > 0)
        <div class="alert alert-success it-day-alert">
            <i class="fas fa-check-circle mr-2"></i>
            Todas las OT del {{ $fechaControl->format('d/m/Y') }}
            estaban vinculadas a un pedido IT.
        </div>
    @endif

    <div class="card shadow-sm it-day-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="card-title float-none mb-0 font-weight-bold">
                    <i class="fas fa-list mr-1 text-primary"></i>
                    OT del {{ $fechaControl->format('d/m/Y') }}
                </h3>
                <small class="text-muted">
                    Se cruza cada OT con los pedidos IT para saber si correspondía sacarla.
                </small>
            </div>

            <span class="badge badge-light border p-2 mt-2 mt-md-0">
                {{ number_format($resumen->salidas_reales,0,',','.') }} OT
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 it-day-table">
                <thead>
                    <tr>
                        <th>OT</th>
                        <th>Código / descripción</th>
                        <th class="text-center">Cantidad OT</th>
                        <th class="text-center">Salida T-T</th>
                        <th>Pedido</th>
                        <th class="text-center">Estado</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($salidas as $salida)
                    <tr class="{{ !$salida->tiene_pedido ? 'it-day-row-danger' : '' }}">
                        <td>
                            <strong class="it-day-ot">
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
                            <strong>
                                {{ number_format($salida->cantidad_orden,0,',','.') }}
                            </strong>
                        </td>

                        <td class="text-center">
                            <strong>
                                {{ number_format($salida->resultado_dia,0,',','.') }}
                            </strong>
                        </td>

                        <td>
                            @if($salida->pedidos_vigentes->isNotEmpty())
                                @foreach($salida->pedidos_vigentes as $pedidoSalida)
                                    <span class="it-day-order-badge">
                                        <i class="fas fa-check mr-1"></i>
                                        PEDIDO {{ $pedidoSalida->nro }}
                                    </span>
                                @endforeach
                            @else
                                <span class="it-day-missing-badge">
                                    <i class="fas fa-times mr-1"></i>
                                    SIN PEDIDO
                                </span>
                            @endif
                        </td>

                        <td class="text-center">
                            @if($salida->tiene_pedido)
                                <span class="badge badge-success px-3 py-2">
                                    PEDIDO
                                </span>
                            @else
                                <span class="badge badge-danger px-3 py-2">
                                    REVISAR
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6"
                            class="text-center text-muted py-5">
                            No hay OT registradas en TERMINACION - TERMINACION
                            para el {{ $fechaControl->format('d/m/Y') }}.
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

@push('page_styles')
<style>
.it-day-shell{max-width:1650px}
.it-day-eyebrow{
    font-size:10px;
    font-weight:900;
    letter-spacing:.12em;
    color:#94a3b8;
}
.it-day-title{
    font-size:27px;
    font-weight:900;
    color:#0f172a;
}
.it-day-filter,
.it-day-card{
    border:1px solid #e2e8f0;
    border-radius:14px;
}
.it-day-hero{
    background:#fff;
    border:1px solid #e2e8f0;
    border-left:5px solid #2563eb;
    border-radius:14px;
    padding:16px 18px;
}
.it-day-hero-label{
    font-size:9px;
    font-weight:900;
    letter-spacing:.1em;
    color:#64748b;
}
.it-day-date{
    margin-top:2px;
    font-size:30px;
    font-weight:950;
    color:#0f172a;
}
.it-day-hero-number{
    display:block;
    font-size:24px;
    color:#0f172a;
}
.it-day-kpi{
    height:100%;
    min-height:108px;
    background:#fff;
    border:1px solid #e2e8f0;
    border-left:4px solid #64748b;
    border-radius:11px;
    padding:13px 14px;
}
.it-day-kpi.is-dark{border-left-color:#334155}
.it-day-kpi.is-success{border-left-color:#22c55e}
.it-day-kpi.is-danger{border-left-color:#ef4444}
.it-day-kpi small{
    display:block;
    color:#94a3b8;
    font-size:9px;
    font-weight:900;
}
.it-day-kpi strong{
    display:block;
    margin:5px 0 3px;
    font-size:27px;
    line-height:1;
    color:#0f172a;
}
.it-day-kpi span{
    display:block;
    color:#64748b;
    font-size:10px;
}
.it-day-alert{
    border-radius:11px;
    font-size:12px;
}
.it-day-table th{
    background:#f8fafc;
    color:#64748b;
    font-size:9px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.04em;
    white-space:nowrap;
    vertical-align:middle!important;
}
.it-day-table td{
    font-size:11px;
    vertical-align:middle!important;
}
.it-day-ot{
    font-size:14px;
    color:#0f172a;
}
.it-day-row-danger{
    background:#fff4f4;
}
.it-day-row-danger:hover{
    background:#ffeaea!important;
}
.it-day-order-badge,
.it-day-missing-badge{
    display:inline-block;
    border-radius:999px;
    padding:6px 9px;
    font-size:9px;
    font-weight:900;
    margin:1px 3px 1px 0;
}
.it-day-order-badge{
    background:#dcfce7;
    color:#166534;
    border:1px solid #bbf7d0;
}
.it-day-missing-badge{
    background:#fee2e2;
    color:#991b1b;
    border:1px solid #fecaca;
}
@media(max-width:767.98px){
    .it-day-title{font-size:22px}
    .it-day-date{font-size:25px}
}
</style>
@endpush
