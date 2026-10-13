@extends('layouts.app')

@section('content')
<section class="content-header pb-2">
<div class="container-fluid it-day-shell">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <div class="it-day-eyebrow">CONTROL DE PEDIDOS · INGRESO TERMINACIÓN</div>
            <h1 class="it-day-title mb-1">
                <i class="fas fa-calendar-check mr-2"></i>
                Control mensual y diario de OT
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

    <div class="card shadow-sm it-calendar-card mb-3">
        <div class="card-header bg-white border-0 pb-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <div class="it-calendar-eyebrow">DASHBOARD MENSUAL</div>
                    <h3 class="it-calendar-title mb-1">
                        <i class="far fa-calendar-alt mr-2 text-primary"></i>
                        {{ ucfirst($mesControl->locale('es')->translatedFormat('F Y')) }}
                    </h3>
                    <small class="text-muted">
                        Cada día muestra las OT que llegaron a TERMINACION - TERMINACION
                        y si estaban justificadas por un pedido IT vigente.
                    </small>
                </div>

                <div class="it-calendar-nav mt-2 mt-md-0">
                    <a class="btn btn-outline-secondary btn-sm"
                       href="{{ route('seguimiento-ingreso-terminacion.seguimiento-diario', [
                           'fecha' => $fechaControl->format('Y-m-d'),
                           'mes' => $mesControl->copy()->subMonth()->format('Y-m')
                       ]) }}">
                        <i class="fas fa-chevron-left"></i>
                    </a>

                    <a class="btn btn-outline-primary btn-sm"
                       href="{{ route('seguimiento-ingreso-terminacion.seguimiento-diario', [
                           'fecha' => $fechaControl->format('Y-m-d'),
                           'mes' => $fechaControl->format('Y-m')
                       ]) }}">
                        Mes de la fecha
                    </a>

                    <a class="btn btn-outline-secondary btn-sm"
                       href="{{ route('seguimiento-ingreso-terminacion.seguimiento-diario', [
                           'fecha' => $fechaControl->format('Y-m-d'),
                           'mes' => $mesControl->copy()->addMonth()->format('Y-m')
                       ]) }}">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body pt-3">
            <div class="row mb-3">
                <div class="col-xl-3 col-md-6 mb-2 mb-xl-0">
                    <div class="it-month-kpi">
                        <small>DÍAS CON SALIDA</small>
                        <strong>{{ number_format($resumenMensual->dias_con_salida,0,',','.') }}</strong>
                        <span>días con movimiento T-T</span>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-2 mb-xl-0">
                    <div class="it-month-kpi">
                        <small>OT DEL MES</small>
                        <strong>{{ number_format($resumenMensual->ots_unicas,0,',','.') }}</strong>
                        <span>{{ number_format($resumenMensual->registros_ot_dia,0,',','.') }} registros OT/día</span>
                        <span>{{ number_format($resumenMensual->prendas_salida,0,',','.') }} prendas procesadas</span>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-2 mb-md-0">
                    <div class="it-month-kpi is-success">
                        <small>CON PEDIDO</small>
                        <strong>{{ number_format($resumenMensual->salidas_con_pedido,0,',','.') }}</strong>
                        <span>{{ number_format($resumenMensual->porcentaje_justificado,1,',','.') }}% justificado</span>
                        <span>{{ number_format($resumenMensual->prendas_con_pedido,0,',','.') }} prendas con pedido</span>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="it-month-kpi {{ $resumenMensual->salidas_sin_pedido > 0 ? 'is-danger' : 'is-success' }}">
                        <small>SIN PEDIDO</small>
                        <strong>{{ number_format($resumenMensual->salidas_sin_pedido,0,',','.') }}</strong>
                        <span>{{ $resumenMensual->salidas_sin_pedido > 0 ? 'registros para revisar' : 'sin desvíos' }}</span>
                        <span>{{ number_format($resumenMensual->prendas_sin_pedido,0,',','.') }} prendas sin pedido</span>
                    </div>
                </div>
            </div>


            <div class="card border-primary mb-3">
                <div class="card-header bg-light">
                    <strong><i class="fas fa-chart-line mr-1 text-primary"></i> Simulación gerencial a 30 días</strong>
                </div>
                <div class="card-body">
                    @if($proyeccionMensual->disponible)
                        <p class="text-muted small">
                            Datos reales hasta <strong>{{ $proyeccionMensual->fecha_corte->format('d/m/Y') }}</strong>
                            · {{ $proyeccionMensual->dias_observados }} días calendario observados
                            · {{ $proyeccionMensual->dias_con_salida }} días con movimientos.
                        </p>
                        <div class="row">
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="it-month-kpi">
                                    <small>PROMEDIO DIARIO</small>
                                    <strong>{{ number_format($proyeccionMensual->ritmo_diario,1,',','.') }}</strong>
                                    <span>prendas T-T / día calendario</span>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="it-month-kpi">
                                    <small>PRÓXIMOS 30 DÍAS</small>
                                    <strong>{{ number_format($proyeccionMensual->prendas_30_dias,0,',','.') }}</strong>
                                    <span>prendas adicionales estimadas</span>
                                    <span>hasta {{ $proyeccionMensual->fecha_horizonte->format('d/m/Y') }}</span>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="it-month-kpi is-success">
                                    <small>PRENDAS CON PEDIDO</small>
                                    <strong>{{ number_format($proyeccionMensual->porcentaje_prendas_con_pedido,1,',','.') }}%</strong>
                                    <span>{{ number_format($proyeccionMensual->prendas_con_pedido_30_dias,0,',','.') }} prendas estimadas / 30 días</span>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="it-month-kpi is-danger">
                                    <small>PRENDAS SIN PEDIDO</small>
                                    <strong>{{ number_format($proyeccionMensual->porcentaje_prendas_sin_pedido,1,',','.') }}%</strong>
                                    <span>{{ number_format($proyeccionMensual->prendas_sin_pedido_30_dias,0,',','.') }} prendas estimadas / 30 días</span>
                                </div>
                            </div>
                        </div>
                        <div class="progress mb-2" style="height:18px">
                            <div class="progress-bar bg-success"
                                 style="width:{{ $proyeccionMensual->porcentaje_prendas_con_pedido }}%"></div>
                            <div class="progress-bar bg-danger"
                                 style="width:{{ $proyeccionMensual->porcentaje_prendas_sin_pedido }}%"></div>
                        </div>
                        <p class="small mb-1">
                            <strong>Simulación al cierre de {{ ucfirst($mesControl->locale('es')->translatedFormat('F')) }}:</strong>
                            {{ number_format($proyeccionMensual->proyeccion_cierre_mes,0,',','.') }} prendas procesadas
                            si se mantiene el ritmo.
                        </p>
                        <small class="text-muted">
                            Se calcula con el total de prendas T-T desde el primer día del mes hasta el último registro,
                            dividido entre días calendario (incluye días sin movimientos).
                            La simulación es lineal, no una meta ni una garantía.
                            Los porcentajes indican prendas con/sin pedido IT vigente, no cumplimiento de pedidos.
                            Se actualiza al incorporar nuevos movimientos y volver a abrir el dashboard.
                        </small>
                    @else
                        <p class="text-muted mb-0">
                            La simulación se muestra en el mes actual cuando existen registros T-T.
                            Los meses históricos mantienen sus resultados reales sin proyectar.
                        </p>
                    @endif
                </div>
            </div>

            <div class="it-calendar-weekdays">
                <div>LUN</div>
                <div>MAR</div>
                <div>MIÉ</div>
                <div>JUE</div>
                <div>VIE</div>
                <div>SÁB</div>
                <div>DOM</div>
            </div>

            <div class="it-calendar-grid">
                @foreach($semanasCalendario as $semana)
                    @foreach($semana as $dia)
                        <a href="{{ route('seguimiento-ingreso-terminacion.seguimiento-diario', [
                                'fecha' => $dia->fecha,
                                'mes' => $mesControl->format('Y-m')
                            ]) }}"
                           class="it-calendar-day
                                  {{ !$dia->es_mes ? 'is-outside' : '' }}
                                  {{ $dia->es_hoy ? 'is-today' : '' }}
                                  {{ $dia->seleccionado ? 'is-selected' : '' }}
                                  {{ $dia->sin_pedido > 0 ? 'has-danger' : ($dia->ot_total > 0 ? 'has-success' : '') }}">

                            <div class="it-calendar-day-top">
                                <span class="it-calendar-day-number">{{ $dia->numero }}</span>

                                @if($dia->ot_total > 0)
                                    <span class="it-calendar-day-total">
                                        {{ $dia->ot_total }} OT
                                    </span>
                                @endif
                            </div>

                            @if($dia->ot_total > 0)
                                <div class="it-calendar-statuses">
                                    @if($dia->con_pedido > 0)
                                        <span class="it-calendar-chip is-success">
                                            <i class="fas fa-check mr-1"></i>
                                            {{ $dia->con_pedido }} con pedido
                                        </span>
                                    @endif

                                    @if($dia->sin_pedido > 0)
                                        <span class="it-calendar-chip is-danger">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            {{ $dia->sin_pedido }} sin pedido
                                        </span>
                                    @endif
                                </div>

                                @if($dia->pedidos->isNotEmpty())
                                    <div class="it-calendar-orders">
                                        @foreach($dia->pedidos->take(3) as $pedidoDia)
                                            <span>{{ $pedidoDia }}</span>
                                        @endforeach

                                        @if($dia->pedidos->count() > 3)
                                            <span>+{{ $dia->pedidos->count() - 3 }}</span>
                                        @endif
                                    </div>
                                @endif

                                <div class="it-calendar-units">
                                    {{ number_format($dia->prendas_salida,0,',','.') }} prendas T-T
                                </div>
                            @else
                                <div class="it-calendar-empty">Sin salida</div>
                            @endif
                        </a>
                    @endforeach
                @endforeach
            </div>

            <div class="it-calendar-legend mt-3">
                <span><i class="it-legend-dot is-success"></i> Todo con pedido</span>
                <span><i class="it-legend-dot is-danger"></i> Tiene OT sin pedido</span>
                <span><i class="it-legend-dot is-selected"></i> Día seleccionado</span>
                <span class="text-muted">Hacé clic en un día para ver el detalle de OT debajo.</span>
            </div>
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

@push('page_css')
<style>
.it-day-shell{max-width:1650px}
.it-calendar-card{
    border:1px solid #e2e8f0;
    border-radius:14px;
    overflow:hidden;
}
.it-calendar-eyebrow{
    font-size:9px;
    font-weight:900;
    letter-spacing:.12em;
    color:#94a3b8;
}
.it-calendar-title{
    font-size:22px;
    font-weight:900;
    color:#0f172a;
}
.it-calendar-nav{
    display:flex;
    gap:6px;
}
.it-month-kpi{
    height:100%;
    min-height:92px;
    border:1px solid #e2e8f0;
    border-left:4px solid #334155;
    border-radius:10px;
    padding:12px 13px;
    background:#fff;
}
.it-month-kpi.is-success{border-left-color:#22c55e}
.it-month-kpi.is-danger{border-left-color:#ef4444}
.it-month-kpi small{
    display:block;
    color:#94a3b8;
    font-size:9px;
    font-weight:900;
    letter-spacing:.04em;
}
.it-month-kpi strong{
    display:block;
    margin:4px 0 2px;
    font-size:25px;
    line-height:1;
    color:#0f172a;
}
.it-month-kpi span{
    color:#64748b;
    font-size:10px;
}
.it-calendar-weekdays,
.it-calendar-grid{
    display:grid;
    grid-template-columns:repeat(7,minmax(0,1fr));
}
.it-calendar-weekdays{
    border:1px solid #e2e8f0;
    border-bottom:0;
    border-radius:10px 10px 0 0;
    overflow:hidden;
    background:#f8fafc;
}
.it-calendar-weekdays div{
    padding:8px 6px;
    color:#64748b;
    font-size:9px;
    font-weight:900;
    text-align:center;
    letter-spacing:.06em;
}
.it-calendar-grid{
    border-top:1px solid #e2e8f0;
    border-left:1px solid #e2e8f0;
}
.it-calendar-day{
    position:relative;
    min-height:128px;
    padding:9px;
    border-right:1px solid #e2e8f0;
    border-bottom:1px solid #e2e8f0;
    background:#fff;
    color:#334155;
    text-decoration:none!important;
    transition:background .15s ease, box-shadow .15s ease;
}
.it-calendar-day:hover{
    z-index:2;
    background:#f8fafc;
    color:#0f172a;
    box-shadow:inset 0 0 0 2px #bfdbfe;
}
.it-calendar-day.is-outside{
    background:#f8fafc;
    opacity:.5;
}
.it-calendar-day.has-success{
    background:#f7fff9;
}
.it-calendar-day.has-danger{
    background:#fff7f7;
}
.it-calendar-day.is-selected{
    z-index:3;
    box-shadow:inset 0 0 0 3px #2563eb;
}
.it-calendar-day.is-today .it-calendar-day-number{
    background:#2563eb;
    color:#fff;
}
.it-calendar-day-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:8px;
}
.it-calendar-day-number{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:26px;
    height:26px;
    border-radius:50%;
    font-size:12px;
    font-weight:900;
    color:#0f172a;
}
.it-calendar-day-total{
    font-size:10px;
    font-weight:900;
    color:#475569;
}
.it-calendar-statuses{
    display:flex;
    flex-wrap:wrap;
    gap:4px;
    margin-top:8px;
}
.it-calendar-chip{
    display:inline-block;
    border-radius:999px;
    padding:4px 6px;
    font-size:8px;
    font-weight:900;
    line-height:1.2;
}
.it-calendar-chip.is-success{
    background:#dcfce7;
    color:#166534;
}
.it-calendar-chip.is-danger{
    background:#fee2e2;
    color:#991b1b;
}
.it-calendar-orders{
    display:flex;
    flex-wrap:wrap;
    gap:3px;
    margin-top:7px;
}
.it-calendar-orders span{
    display:inline-block;
    border:1px solid #dbeafe;
    border-radius:5px;
    background:#eff6ff;
    color:#1d4ed8;
    padding:2px 4px;
    font-size:8px;
    font-weight:900;
}
.it-calendar-units{
    margin-top:7px;
    color:#64748b;
    font-size:8px;
    font-weight:700;
}
.it-calendar-empty{
    margin-top:12px;
    color:#cbd5e1;
    font-size:9px;
}
.it-calendar-legend{
    display:flex;
    align-items:center;
    flex-wrap:wrap;
    gap:12px;
    font-size:9px;
}
.it-legend-dot{
    display:inline-block;
    width:9px;
    height:9px;
    margin-right:4px;
    border-radius:50%;
}
.it-legend-dot.is-success{background:#22c55e}
.it-legend-dot.is-danger{background:#ef4444}
.it-legend-dot.is-selected{
    background:#2563eb;
    box-shadow:0 0 0 2px #bfdbfe;
}
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
@media(max-width:991.98px){
    .it-calendar-day{min-height:112px;padding:7px}
    .it-calendar-orders span{font-size:7px}
}
@media(max-width:767.98px){
    .it-day-title{font-size:22px}
    .it-day-date{font-size:25px}
    .it-calendar-card .card-body{overflow-x:auto}
    .it-calendar-weekdays,
    .it-calendar-grid{
        min-width:760px;
    }
}
</style>
@endpush
