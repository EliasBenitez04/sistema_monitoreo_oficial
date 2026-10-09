@extends('layouts.app')

@section('content')
<section class="content-header pb-2">
<div class="container-fluid it-day-shell">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <div class="it-day-eyebrow">CONTROL DE PEDIDOS · INGRESO TERMINACIÓN</div>
            <h1 class="it-day-title mb-1">
                <i class="fas fa-clipboard-check mr-2"></i>
                Seguimiento diario de cumplimiento
            </h1>
            <p class="text-muted mb-0">
                Compara lo pedido por día contra las OT que realmente alcanzaron
                <strong>TERMINACION - TERMINACION</strong>.
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
<div class="container-fluid it-day-shell">

    <div class="card it-day-filter mb-3">
        <div class="card-body pb-2">
            <form method="GET"
                  action="{{ route('seguimiento-ingreso-terminacion.seguimiento-diario') }}">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-md-4 mb-2">
                        <label class="small font-weight-bold">Desde</label>
                        <input type="date"
                               name="desde"
                               class="form-control"
                               value="{{ $desde->format('Y-m-d') }}">
                    </div>

                    <div class="col-lg-3 col-md-4 mb-2">
                        <label class="small font-weight-bold">Hasta</label>
                        <input type="date"
                               name="hasta"
                               class="form-control"
                               value="{{ $hasta->format('Y-m-d') }}">
                    </div>

                    <div class="col-lg-4 col-md-4 mb-2">
                        <label class="small font-weight-bold">Pedido IT</label>
                        <select name="pedido" class="form-control">
                            <option value="">Todos los pedidos IT</option>

                            @foreach($pedidosDisponibles as $pedido)
                                <option value="{{ $pedido->id }}"
                                        {{ (int) $pedidoId === (int) $pedido->id ? 'selected' : '' }}>
                                    {{ $pedido->nro_pedido }}
                                    @if($pedido->fecha_pedido)
                                        · {{ date('d/m/Y', strtotime($pedido->fecha_pedido)) }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 mb-2 text-lg-right">
                        <a href="{{ route('seguimiento-ingreso-terminacion.seguimiento-diario') }}"
                           class="btn btn-light border mr-1"
                           title="Limpiar filtros">
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

    <div class="alert alert-light border it-day-reading mb-3">
        <strong>Cómo leerlo:</strong>
        <strong>Pedido del día</strong> = OT solicitadas en pedidos IT de esa fecha.
        <strong>Ya disponible</strong> = la OT ya había alcanzado TERMINACION - TERMINACION antes del pedido.
        <strong>Salió el mismo día</strong> = alcanzó el proceso objetivo ese día.
        <strong>Pendiente al cierre</strong> = no estaba disponible antes ni salió ese mismo día.
        <strong>Salida real</strong> puede incluir OT atrasadas de pedidos anteriores.
    </div>

    <div class="row mb-2">
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="it-day-kpi">
                <small>Días con pedido</small>
                <strong>{{ number_format($resumen->dias_con_pedido,0,',','.') }}</strong>
                <span>de {{ number_format($resumen->dias,0,',','.') }} días consultados</span>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="it-day-kpi is-primary">
                <small>OT pedidas</small>
                <strong>{{ number_format($resumen->ots_pedidas,0,',','.') }}</strong>
                <span>{{ number_format($resumen->prendas_pedidas,0,',','.') }} prendas</span>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="it-day-kpi is-success">
                <small>Salieron mismo día</small>
                <strong>{{ number_format($resumen->ots_mismo_dia,0,',','.') }}</strong>
                <span>respuesta nueva al pedido diario</span>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="it-day-kpi is-info">
                <small>Ya disponibles</small>
                <strong>{{ number_format($resumen->ots_ya_disponibles,0,',','.') }}</strong>
                <span>estaban listas antes del pedido</span>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="it-day-kpi is-warning">
                <small>Pendiente al cierre</small>
                <strong>{{ number_format($resumen->ots_pendientes_cierre,0,',','.') }}</strong>
                <span>no cubiertas ese día</span>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="it-day-kpi is-dark">
                <small>Días 100% cumplidos</small>
                <strong>{{ number_format($resumen->dias_cumplidos,0,',','.') }}</strong>
                <span>{{ number_format($resumen->cumplimiento_dias,1,',','.') }}% de días con pedido</span>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-lg-4 mb-3 mb-lg-0">
            <div class="it-day-highlight is-output">
                <div>
                    <small>SALIDA REAL DEL PERÍODO</small>
                    <strong>{{ number_format($resumen->salidas_reales,0,',','.') }} OT</strong>
                    <span>alcanzaron TERMINACION - TERMINACION</span>
                </div>
                <i class="fas fa-sign-out-alt"></i>
            </div>
        </div>

        <div class="col-lg-4 mb-3 mb-lg-0">
            <div class="it-day-highlight is-request">
                <div>
                    <small>DE ESA SALIDA, ERA PEDIDO DEL DÍA</small>
                    <strong>{{ number_format($resumen->salidas_reales - $resumen->salidas_otras_fechas,0,',','.') }} OT</strong>
                    <span>salieron y correspondían al pedido de esa fecha</span>
                </div>
                <i class="fas fa-bullseye"></i>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="it-day-highlight is-backlog">
                <div>
                    <small>DE ESA SALIDA, OTRAS FECHAS</small>
                    <strong>{{ number_format($resumen->salidas_otras_fechas,0,',','.') }} OT</strong>
                    <span>correspondían a pedidos anteriores u otras fechas</span>
                </div>
                <i class="fas fa-history"></i>
            </div>
        </div>
    </div>

    <div class="card shadow-sm it-day-table-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="card-title float-none mb-0 font-weight-bold">
                    <i class="fas fa-calendar-check mr-1 text-primary"></i>
                    Cumplimiento por día
                </h3>
                <small class="text-muted">
                    {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}
                </small>
            </div>

            <div class="it-day-legend mt-2 mt-md-0">
                <span><i class="fas fa-circle text-success"></i> 100%</span>
                <span><i class="fas fa-circle text-warning"></i> Parcial</span>
                <span><i class="fas fa-circle text-danger"></i> Sin cubrir</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 it-day-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th class="text-center">Pedidos IT</th>
                        <th class="text-center">OT pedidas</th>
                        <th class="text-center">Prendas pedidas</th>
                        <th class="text-center">Ya disponibles</th>
                        <th class="text-center">Salieron mismo día</th>
                        <th class="text-center">Pendiente cierre</th>
                        <th class="text-center">Cobertura</th>
                        <th class="text-center">Salida real día</th>
                        <th class="text-center">Era pedido del día</th>
                        <th class="text-center">De otras fechas</th>
                        <th class="text-center">Detalle</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($dias as $dia)
                    @php
                        $sinPedido = $dia->ots_pedidas === 0;
                        $coverage = $dia->cobertura;

                        if ($coverage === null) {
                            $coverageClass = 'is-neutral';
                        } elseif ($coverage >= 100) {
                            $coverageClass = 'is-good';
                        } elseif ($coverage > 0) {
                            $coverageClass = 'is-partial';
                        } else {
                            $coverageClass = 'is-bad';
                        }

                        $collapseId = 'itday-' . str_replace('-', '', $dia->fecha);
                    @endphp

                    <tr class="{{ $sinPedido && $dia->salidas_reales === 0 ? 'it-day-empty-row' : '' }}">
                        <td>
                            <strong>{{ $dia->fecha_label }}</strong>
                            <small class="d-block text-muted">
                                {{ $dia->dia_semana }}
                            </small>
                        </td>

                        <td class="text-center">
                            {{ number_format($dia->pedidos,0,',','.') }}
                        </td>

                        <td class="text-center">
                            @if($dia->ots_pedidas > 0)
                                <span class="it-day-number is-blue">
                                    {{ number_format($dia->ots_pedidas,0,',','.') }}
                                </span>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>

                        <td class="text-center">
                            {{ number_format($dia->prendas_pedidas,0,',','.') }}
                        </td>

                        <td class="text-center">
                            {{ number_format($dia->ya_disponibles,0,',','.') }}
                        </td>

                        <td class="text-center">
                            @if($dia->salieron_hoy_pedido > 0)
                                <span class="it-day-number is-green">
                                    {{ number_format($dia->salieron_hoy_pedido,0,',','.') }}
                                </span>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>

                        <td class="text-center">
                            @if($dia->pendientes_al_cierre > 0)
                                <span class="it-day-number is-orange">
                                    {{ number_format($dia->pendientes_al_cierre,0,',','.') }}
                                </span>
                            @else
                                <span class="text-success font-weight-bold">0</span>
                            @endif
                        </td>

                        <td class="text-center">
                            @if($coverage !== null)
                                <span class="it-day-coverage {{ $coverageClass }}">
                                    {{ number_format($coverage,1,',','.') }}%
                                </span>
                                <small class="d-block text-muted mt-1">
                                    {{ number_format($dia->ya_disponibles + $dia->salieron_hoy_pedido,0,',','.') }}
                                    / {{ number_format($dia->ots_pedidas,0,',','.') }} OT
                                </small>
                            @else
                                <span class="text-muted">Sin pedido</span>
                            @endif
                        </td>

                        <td class="text-center">
                            <strong>{{ number_format($dia->salidas_reales,0,',','.') }}</strong>
                            @if($dia->salidas_reales > 0)
                                <small class="d-block text-muted">
                                    {{ number_format($dia->prendas_salidas_reales,0,',','.') }} prendas
                                </small>
                            @endif
                        </td>

                        <td class="text-center">
                            @if($dia->salidas_del_pedido > 0)
                                <span class="badge badge-success px-2 py-1">
                                    {{ number_format($dia->salidas_del_pedido,0,',','.') }}
                                </span>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>

                        <td class="text-center">
                            @if($dia->salidas_otras_fechas > 0)
                                <span class="badge badge-secondary px-2 py-1">
                                    {{ number_format($dia->salidas_otras_fechas,0,',','.') }}
                                </span>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>

                        <td class="text-center">
                            @if($dia->ots_pedidas > 0 || $dia->salidas_reales > 0)
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-toggle="collapse"
                                        data-target="#{{ $collapseId }}"
                                        aria-expanded="false">
                                    <i class="fas fa-eye mr-1"></i>
                                    Ver
                                </button>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>

                    @if($dia->ots_pedidas > 0 || $dia->salidas_reales > 0)
                        <tr class="it-day-detail-row">
                            <td colspan="12" class="p-0 border-0">
                                <div class="collapse" id="{{ $collapseId }}">
                                    <div class="it-day-detail">

                                        <div class="row">
                                            <div class="col-xl-7 mb-3 mb-xl-0">
                                                <div class="it-day-detail-title">
                                                    <i class="fas fa-clipboard-list mr-1"></i>
                                                    Pedido del día
                                                </div>
                                                <small class="text-muted">
                                                    Qué se pidió y cuándo terminó realmente cada OT.
                                                </small>

                                                <div class="table-responsive mt-2">
                                                    <table class="table table-sm table-hover mb-0 it-day-detail-table">
                                                        <thead>
                                                            <tr>
                                                                <th>Pedido</th>
                                                                <th>OT</th>
                                                                <th>Código / descripción</th>
                                                                <th class="text-center">Cant.</th>
                                                                <th>Resultado</th>
                                                                <th>Fecha salida</th>
                                                            </tr>
                                                        </thead>

                                                        <tbody>
                                                        @forelse($dia->ots_pedido as $ot)
                                                            @php
                                                                $estadoClass = 'badge-secondary';

                                                                if ($ot->estado === 'SALIO HOY') {
                                                                    $estadoClass = 'badge-success';
                                                                } elseif ($ot->estado === 'YA DISPONIBLE') {
                                                                    $estadoClass = 'badge-info';
                                                                } elseif ($ot->estado === 'SALIO DESPUES') {
                                                                    $estadoClass = 'badge-warning';
                                                                } elseif ($ot->estado === 'PENDIENTE') {
                                                                    $estadoClass = 'badge-danger';
                                                                }
                                                            @endphp

                                                            <tr>
                                                                <td>
                                                                    @foreach($ot->pedidos as $nroPedido)
                                                                        <span class="badge badge-light border mr-1">
                                                                            {{ $nroPedido }}
                                                                        </span>
                                                                    @endforeach
                                                                </td>

                                                                <td>
                                                                    <strong>{{ $ot->nro_ot }}</strong>
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
                                                                    <span class="badge {{ $estadoClass }}">
                                                                        {{ $ot->estado }}
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
                                                                <td colspan="6"
                                                                    class="text-center text-muted py-3">
                                                                    No hubo pedido IT ese día.
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <div class="col-xl-5">
                                                <div class="it-day-detail-title">
                                                    <i class="fas fa-sign-out-alt mr-1"></i>
                                                    Salida real del día
                                                </div>
                                                <small class="text-muted">
                                                    Todo lo que llegó a TERMINACION - TERMINACION ese día.
                                                </small>

                                                <div class="table-responsive mt-2">
                                                    <table class="table table-sm table-hover mb-0 it-day-detail-table">
                                                        <thead>
                                                            <tr>
                                                                <th>OT</th>
                                                                <th>Pedido vinculado</th>
                                                                <th class="text-center">Cant.</th>
                                                                <th>Origen</th>
                                                            </tr>
                                                        </thead>

                                                        <tbody>
                                                        @forelse($dia->ots_salida as $ot)
                                                            <tr>
                                                                <td>
                                                                    <strong>{{ $ot->nro_ot }}</strong>
                                                                    <small class="d-block text-muted">
                                                                        {{ $ot->codigo }}
                                                                    </small>
                                                                </td>

                                                                <td>
                                                                    @foreach($ot->pedidos as $nroPedido)
                                                                        <span class="badge badge-light border mr-1">
                                                                            {{ $nroPedido }}
                                                                        </span>
                                                                    @endforeach
                                                                </td>

                                                                <td class="text-center">
                                                                    {{ number_format($ot->cantidad_orden,0,',','.') }}
                                                                </td>

                                                                <td>
                                                                    @if($ot->corresponde_hoy)
                                                                        <span class="badge badge-success">
                                                                            PEDIDO DEL DÍA
                                                                        </span>
                                                                    @else
                                                                        <span class="badge badge-secondary">
                                                                            OTRA FECHA
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="4"
                                                                    class="text-center text-muted py-3">
                                                                    No hubo salida real ese día.
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="12" class="text-center text-muted py-5">
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

@push('page_styles')
<style>
.it-day-shell{max-width:1800px}
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
.it-day-table-card{
    border:1px solid #e2e8f0;
    border-radius:14px;
}
.it-day-reading{
    border-radius:10px;
    font-size:12px;
    color:#64748b;
}
.it-day-kpi{
    height:100%;
    min-height:110px;
    border:1px solid #e2e8f0;
    border-left:4px solid #64748b;
    border-radius:11px;
    background:#fff;
    padding:13px 14px;
}
.it-day-kpi.is-primary{border-left-color:#2563eb}
.it-day-kpi.is-success{border-left-color:#22c55e}
.it-day-kpi.is-info{border-left-color:#06b6d4}
.it-day-kpi.is-warning{border-left-color:#f59e0b}
.it-day-kpi.is-dark{border-left-color:#334155}
.it-day-kpi small{
    display:block;
    font-size:9px;
    font-weight:850;
    text-transform:uppercase;
    color:#94a3b8;
    letter-spacing:.04em;
}
.it-day-kpi strong{
    display:block;
    margin:5px 0 3px;
    font-size:24px;
    line-height:1;
    color:#0f172a;
}
.it-day-kpi span{
    display:block;
    font-size:10px;
    color:#64748b;
}
.it-day-highlight{
    height:100%;
    display:flex;
    align-items:center;
    justify-content:space-between;
    border-radius:12px;
    padding:14px 16px;
    border:1px solid #e2e8f0;
    background:#fff;
}
.it-day-highlight small{
    display:block;
    font-size:9px;
    font-weight:900;
    color:#64748b;
    letter-spacing:.05em;
}
.it-day-highlight strong{
    display:block;
    margin-top:3px;
    font-size:22px;
}
.it-day-highlight span{
    display:block;
    font-size:10px;
    color:#64748b;
}
.it-day-highlight i{
    font-size:27px;
    opacity:.25;
}
.it-day-highlight.is-output{
    border-left:4px solid #22c55e;
}
.it-day-highlight.is-request{
    border-left:4px solid #2563eb;
}
.it-day-highlight.is-backlog{
    border-left:4px solid #64748b;
}
.it-day-legend{
    display:flex;
    gap:10px;
    font-size:10px;
    color:#64748b;
}
.it-day-table th,
.it-day-detail-table th{
    background:#f8fafc;
    color:#64748b;
    font-size:9px;
    text-transform:uppercase;
    letter-spacing:.04em;
    white-space:nowrap;
    vertical-align:middle!important;
}
.it-day-table td{
    font-size:11px;
    vertical-align:middle!important;
}
.it-day-empty-row{
    background:#fafafa;
    color:#94a3b8;
}
.it-day-number{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:31px;
    border-radius:999px;
    padding:3px 8px;
    font-weight:900;
}
.it-day-number.is-blue{
    background:#eff6ff;
    color:#1d4ed8;
}
.it-day-number.is-green{
    background:#ecfdf5;
    color:#047857;
}
.it-day-number.is-orange{
    background:#fff7ed;
    color:#c2410c;
}
.it-day-coverage{
    display:inline-block;
    min-width:58px;
    border-radius:999px;
    padding:5px 8px;
    font-weight:900;
    border:1px solid transparent;
}
.it-day-coverage.is-good{
    background:#ecfdf5;
    color:#047857;
    border-color:#bbf7d0;
}
.it-day-coverage.is-partial{
    background:#fff7ed;
    color:#c2410c;
    border-color:#fed7aa;
}
.it-day-coverage.is-bad{
    background:#fef2f2;
    color:#b91c1c;
    border-color:#fecaca;
}
.it-day-coverage.is-neutral{
    background:#f8fafc;
    color:#64748b;
    border-color:#e2e8f0;
}
.it-day-detail{
    padding:16px 18px;
    background:#f8fafc;
    border-top:1px solid #e2e8f0;
    border-bottom:1px solid #e2e8f0;
}
.it-day-detail-title{
    font-size:13px;
    font-weight:900;
    color:#0f172a;
}
.it-day-detail-table td{
    font-size:10px;
    vertical-align:middle!important;
    background:#fff;
}
@media(max-width:767.98px){
    .it-day-title{font-size:22px}
}
</style>
@endpush
