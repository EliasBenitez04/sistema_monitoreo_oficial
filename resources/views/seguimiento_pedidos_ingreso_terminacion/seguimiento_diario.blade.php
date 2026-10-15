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

    <div class="card it-day-filter mb-3" id="buscador-ot-it">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                <div>
                    <h5 class="font-weight-bold mb-1">
                        <i class="fas fa-search-location text-primary mr-1"></i>
                        Buscar OT y consultar sus movimientos
                    </h5>
                    <p class="small text-muted mb-0">
                        Ingresá el número de OT para ver dónde se encuentra según sus registros,
                        sus pedidos IT, todas las etapas de producción, el plan logístico,
                        los envíos, devoluciones y las recepciones.
                    </p>
                </div>
                <span class="badge badge-light border p-2 mt-2 mt-md-0">Búsqueda histórica · todas las fechas</span>
            </div>

            <form method="GET"
                  action="{{ route('seguimiento-ingreso-terminacion.seguimiento-diario') }}#resultado-busqueda-ot">
                <input type="hidden" name="fecha" value="{{ $fechaControl->format('Y-m-d') }}">
                <input type="hidden" name="mes" value="{{ $mesControl->format('Y-m') }}">
                <div class="row align-items-end">
                    <div class="col-lg-7 col-md-7 mb-2 mb-md-0">
                        <label class="small font-weight-bold mb-1" for="numero-ot-consulta">Número de OT</label>
                        <input id="numero-ot-consulta" type="text" name="ot"
                               class="form-control" inputmode="numeric"
                               pattern="[0-9]+" maxlength="12" required
                               autocomplete="off"
                               placeholder="Ej.: 30652"
                               value="{{ $numeroOtBuscado }}">
                    </div>
                    <div class="col-lg-3 col-md-3 mb-2 mb-md-0">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search mr-1"></i>Buscar OT
                        </button>
                    </div>
                    <div class="col-lg-2 col-md-2">
                        <a class="btn btn-outline-secondary btn-block"
                           href="{{ route('seguimiento-ingreso-terminacion.seguimiento-diario', [
                               'fecha' => $fechaControl->format('Y-m-d'),
                               'mes' => $mesControl->format('Y-m')
                           ]) }}">
                            Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($numeroOtBuscado !== '')
        @include('seguimiento_pedidos_ingreso_terminacion._consulta_ot')
    @endif

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

            <div class="card border-success mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <div class="text-success font-weight-bold small">EFECTIVIDAD DE SALIDA DEL MES</div>
                            <h3 class="mb-1 font-weight-bold">{{ number_format($resumenMensual->efectividad_prendas,1,',','.') }}%</h3>
                            <span class="text-muted small">
                                {{ number_format($resumenMensual->prendas_con_pedido,0,',','.') }} prendas con pedido IT
                                de {{ number_format($resumenMensual->prendas_salida,0,',','.') }} procesadas.
                            </span>
                        </div>
                        <div class="text-muted small mt-2 mt-md-0">
                            Efectividad = prendas con pedido / prendas procesadas × 100
                        </div>
                    </div>
                    <div class="progress mt-3" style="height:14px">
                        <div class="progress-bar bg-success" role="progressbar"
                             style="width:{{ $resumenMensual->efectividad_prendas }}%"
                             aria-valuenow="{{ $resumenMensual->efectividad_prendas }}"
                             aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <small class="d-block mt-2 text-muted">
                        Mide qué porcentaje de prendas T-T corresponde a pedidos IT vigentes.
                        No representa el porcentaje de cumplimiento de todos los pedidos pendientes.
                    </small>
                </div>
            </div>

            <div class="card border-primary mb-3">
                <div class="card-header bg-light">
                    <strong><i class="fas fa-calculator text-primary mr-1"></i>
                        Análisis de prendas · proyección por días hábiles
                    </strong>
                </div>
                <div class="card-body">
                    @if($proyeccionDiasHabiles->disponible)
                        <p class="small text-muted mb-3">
                            Mes: {{ ucfirst($mesControl->locale('es')->translatedFormat('F Y')) }}
                            · Corte de datos T-T: <strong>{{ $proyeccionDiasHabiles->fecha_corte->format('d/m/Y') }}</strong>
                            · Solo se proyectan días de lunes a viernes.
                        </p>
                        <div class="row">
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="it-month-kpi">
                                    <small>PRENDAS INGRESADAS EN EL MES</small>
                                    <strong>{{ number_format($proyeccionDiasHabiles->cantidad_real,0,',','.') }}</strong>
                                    <span>cantidad real hasta la fecha de corte</span>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="it-month-kpi">
                                    <small>PROMEDIO POR DÍA HÁBIL</small>
                                    <strong>{{ number_format($proyeccionDiasHabiles->promedio_diario,1,',','.') }}</strong>
                                    <span>{{ $proyeccionDiasHabiles->dias_transcurridos }} días hábiles transcurridos</span>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="it-month-kpi">
                                    <small>PRENDAS ADICIONALES PROYECTADAS</small>
                                    <strong>{{ number_format($proyeccionDiasHabiles->cantidad_restante,0,',','.') }}</strong>
                                    <span>{{ $proyeccionDiasHabiles->dias_restantes }} días hábiles restantes</span>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="it-month-kpi is-success">
                                    <small>TOTAL PROYECTADO AL CIERRE</small>
                                    <strong>{{ number_format($proyeccionDiasHabiles->total_proyectado,0,',','.') }}</strong>
                                    <span>real + prendas adicionales estimadas</span>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted">
                            Fórmula: prendas T-T del mes ÷ días hábiles desde el 1 hasta el último
                            movimiento cargado × días hábiles que faltan.
                            Los días hábiles sin movimientos cuentan para el promedio.
                            Los movimientos ocurridos en fin de semana se incluyen en la cantidad real,
                            pero sábados y domingos no se cuentan como días futuros de producción.
                            No se excluyen feriados; es una estimación según el ritmo actual.
                        </small>
                    @else
                        <p class="text-muted mb-0">
                            La proyección se muestra para el mes actual cuando hay movimientos T-T
                            y al menos un día hábil transcurrido. Para otros meses se conservan
                            las cifras reales del calendario sin proyectar.
                        </p>
                    @endif
                </div>
            </div>

            <div class="card border-warning mb-3">
                <div class="card-header bg-light">
                    <strong><i class="fas fa-exclamation-circle text-warning mr-1"></i>
                        Análisis de atención de pedidos IT · OT pendientes vs. salidas sin pedido
                    </strong>
                </div>
                <div class="card-body">
                    @if($analisisPendientes->disponible)
                        <p class="small text-muted">
                            Corte: <strong>{{ $analisisPendientes->fecha_corte->format('d/m/Y') }}</strong>.
                            Pedidos IT solicitados hasta ese día; se cuentan OT únicas.
                        </p>
                        <div class="row">
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="it-month-kpi">
                                    <small>OT IT SOLICITADAS</small>
                                    <strong>{{ number_format($analisisPendientes->solicitadas,0,',','.') }}</strong>
                                    <span>{{ number_format($analisisPendientes->porcentaje_atencion,1,',','.') }}% con hito registrado al corte</span>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="it-month-kpi is-success">
                                    <small>OT IT ATENDIDAS</small>
                                    <strong>{{ number_format($analisisPendientes->atendidas,0,',','.') }}</strong>
                                    <span>Ya alcanzaron T-T hasta el corte</span>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="it-month-kpi is-danger">
                                    <small>OT IT PENDIENTES</small>
                                    <strong>{{ number_format($analisisPendientes->pendientes,0,',','.') }}</strong>
                                    <span>Solicitadas sin T-T hasta el corte</span>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="it-month-kpi">
                                    <small>SALIDAS SIN PEDIDO DEL MES</small>
                                    <strong>{{ number_format($analisisPendientes->sin_pedido_mes,0,',','.') }}</strong>
                                    <span>Registros OT/día que requieren revisión</span>
                                </div>
                            </div>
                        </div>
                        <div class="card border-success mb-3">
                            <div class="card-body">
                                <small class="text-success font-weight-bold">EFECTIVIDAD HISTÓRICA DE ATENCIÓN IT</small>
                                <h3 class="font-weight-bold mb-1">{{ number_format($analisisPendientes->porcentaje_atencion,1,',','.') }}%</h3>
                                <div class="progress mt-2" style="height:14px">
                                    <div class="progress-bar bg-success" role="progressbar"
                                         style="width:{{ $analisisPendientes->porcentaje_atencion }}%"
                                         aria-valuenow="{{ $analisisPendientes->porcentaje_atencion }}"
                                         aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    {{ number_format($analisisPendientes->atendidas,0,',','.') }} OT atendidas de
                                    {{ number_format($analisisPendientes->solicitadas,0,',','.') }} OT IT solicitadas
                                    hasta el {{ $analisisPendientes->fecha_corte->format('d/m/Y') }}.
                                    Fórmula: OT atendidas / OT solicitadas × 100.
                                    Mide cumplimiento histórico por OT que alcanzó T-T al menos una vez,
                                    no cumplimiento total de prendas ni efectividad de salida del mes.
                                </small>
                            </div>
                        </div>
                        @if($analisisPendientes->pendientes > 0 && $analisisPendientes->sin_pedido_mes > 0)
                            <div class="alert alert-warning">
                                <strong>Oportunidad de priorización:</strong>
                                quedaron {{ number_format($analisisPendientes->pendientes,0,',','.') }} OT solicitadas pendientes
                                mientras hubo {{ number_format($analisisPendientes->sin_pedido_mes,0,',','.') }} salidas OT/día sin pedido IT vigente.
                                Como referencia, hasta
                                <strong>{{ number_format($analisisPendientes->oportunidad,0,',','.') }} OT</strong>
                                podrían haberse priorizado en lugar de esas salidas, si estaban disponibles
                                y requerían una carga de trabajo comparable.
                                No implica que esas OT pudieran completarse automáticamente.
                            </div>
                        @endif
                        <details>
                            <summary class="font-weight-bold" style="cursor:pointer">
                                Ver las {{ number_format($analisisPendientes->pendientes,0,',','.') }} OT solicitadas pendientes
                            </summary>
                            <div class="table-responsive mt-2" style="max-height:360px;overflow:auto">
                                <table class="table table-sm table-striped">
                                    <thead><tr><th>Nº OT</th><th>Descripción</th><th class="text-right">Prendas ordenadas</th><th>Pedido IT</th></tr></thead>
                                    <tbody>
                                        @forelse($otsPendientesIt as $item)
                                            <tr>
                                                <td><strong>{{ $item->nro_ot }}</strong></td>
                                                <td>{{ $item->descripcion }}</td>
                                                <td class="text-right">{{ number_format($item->cantidad_orden,0,',','.') }}</td>
                                                <td>{{ $item->pedidos }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-muted">No quedan OT solicitadas pendientes al corte.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </details>
                        <small class="d-block text-muted mt-2">
                            Criterio: una OT está atendida si existe al menos un registro
                            TERMINACION - TERMINACION hasta la fecha de corte.
                            Las pendientes pueden corresponder a pedidos anteriores al mes.
                            Este indicador mide OT, no prendas completas.
                            Las salidas sin pedido del mes son registros OT/día, por lo que
                            pueden repetir una misma OT en días distintos.
                        </small>
                    @else
                        <p class="text-muted mb-0">No hay corte de movimientos cargados para analizar las OT pendientes del mes actual.</p>
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
                        <div class="it-calendar-day
                                  {{ !$dia->es_mes ? 'is-outside' : '' }}
                                  {{ $dia->es_hoy ? 'is-today' : '' }}
                                  {{ $dia->seleccionado ? 'is-selected' : '' }}
                                  {{ $dia->sin_pedido > 0 ? 'has-danger' : ($dia->ot_total > 0 ? 'has-success' : '') }}">
                            <a class="it-calendar-day-link" href="{{ route('seguimiento-ingreso-terminacion.seguimiento-diario', [
                                    'fecha' => $dia->fecha,
                                    'mes' => $mesControl->format('Y-m')
                                ]) }}" title="Ver la fecha seleccionada y sus OT">
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
                            @if($dia->ot_total > 0)
                                <button type="button"
                                        class="btn btn-outline-primary btn-sm it-calendar-view-ot"
                                        data-toggle="modal"
                                        data-target="#itDetalle{{ str_replace('-', '', $dia->fecha) }}"
                                        aria-label="Ver las {{ $dia->ot_total }} OT del {{ date('d/m/Y', strtotime($dia->fecha)) }}">
                                    <i class="fas fa-list mr-1"></i> Ver OT
                                </button>
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>

            <div class="it-calendar-legend mt-3">
                <span><i class="it-legend-dot is-success"></i> Todo con pedido</span>
                <span><i class="it-legend-dot is-danger"></i> Tiene OT sin pedido</span>
                <span><i class="it-legend-dot is-selected"></i> Día seleccionado</span>
                <span class="text-muted">Usá «Ver OT» para abrir el listado del día o hacé clic en la fecha para verlo debajo.</span>
            </div>
        </div>
    </div>


    {{-- Detalle mensual: MISMAS OT/día, cantidades y pedidos IT que alimentan el calendario. --}}
    @foreach($salidasMesPorFecha as $fechaDetalle => $otsDiaDetalle)
        @php
            $cantidadOtDia = (int) $otsDiaDetalle->sum('cantidad_orden');
            $cantidadRealDia = (int) $otsDiaDetalle->sum('resultado_dia');
            $cantidadConPedidoDia = $otsDiaDetalle->where('tiene_pedido', true)->count();
            $cantidadSinPedidoDia = $otsDiaDetalle->where('tiene_pedido', false)->count();
        @endphp
        <div class="modal fade" id="itDetalle{{ str_replace('-', '', $fechaDetalle) }}" tabindex="-1"
             role="dialog" aria-labelledby="itTituloDetalle{{ str_replace('-', '', $fechaDetalle) }}" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document" style="max-width:1160px">
                <div class="modal-content it-calendar-modal">
                    <div class="modal-header bg-light align-items-start">
                        <div>
                            <small class="it-day-eyebrow">DETALLE DIARIO · TERMINACION - TERMINACION</small>
                            <h5 class="modal-title font-weight-bold" id="itTituloDetalle{{ str_replace('-', '', $fechaDetalle) }}">
                                <i class="fas fa-calendar-day text-primary mr-2"></i>OT del {{ date('d/m/Y', strtotime($fechaDetalle)) }}
                            </h5>
                            <small class="text-muted">Pedidos IT vigentes en la fecha de cada salida.</small>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-lg-4 col-6 mb-2"><div class="it-modal-stat"><small>OT DEL DÍA</small><strong>{{ number_format($otsDiaDetalle->count(),0,',','.') }}</strong></div></div>
                            <div class="col-lg-4 col-6 mb-2"><div class="it-modal-stat"><small>CANTIDAD OT</small><strong>{{ number_format($cantidadOtDia,0,',','.') }}</strong></div></div>
                            <div class="col-lg-4 col-12 mb-2"><div class="it-modal-stat"><small>SALIDA REAL T-T</small><strong>{{ number_format($cantidadRealDia,0,',','.') }}</strong></div></div>
                            <div class="col-lg-6 col-6 mb-2"><div class="it-modal-stat is-success"><small>CON PEDIDO</small><strong>{{ number_format($cantidadConPedidoDia,0,',','.') }}</strong></div></div>
                            <div class="col-lg-6 col-6 mb-2"><div class="it-modal-stat {{ $cantidadSinPedidoDia > 0 ? 'is-danger' : 'is-success' }}"><small>SIN PEDIDO</small><strong>{{ number_format($cantidadSinPedidoDia,0,',','.') }}</strong></div></div>
                        </div>
                        @if($cantidadSinPedidoDia > 0)
                            <div class="alert alert-danger py-2 small"><i class="fas fa-exclamation-triangle mr-1"></i>
                                {{ $cantidadSinPedidoDia }} OT sin pedido IT vigente en la fecha seleccionada.
                            </div>
                        @else
                            <div class="alert alert-success py-2 small"><i class="fas fa-check-circle mr-1"></i>
                                Todas las OT del {{ date('d/m/Y', strtotime($fechaDetalle)) }} estaban vinculadas a un pedido IT.
                            </div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-sm table-hover it-day-table">
                                <thead class="thead-light">
                                <tr><th>OT</th><th>Código / descripción</th><th class="text-right">Cantidad OT</th><th class="text-right">Salida T-T</th><th>Pedido</th><th class="text-center">Estado</th></tr>
                                </thead>
                                <tbody>
                                @foreach($otsDiaDetalle as $salidaDetalle)
                                    <tr class="{{ !$salidaDetalle->tiene_pedido ? 'it-day-row-danger' : '' }}">
                                        <td><strong class="it-day-ot">{{ $salidaDetalle->nro_ot }}</strong></td>
                                        <td><strong>{{ $salidaDetalle->codigo ?: '-' }}</strong>
                                            <small class="d-block text-muted">{{ $salidaDetalle->descripcion }}</small>
                                        </td>
                                        <td class="text-right font-weight-bold">{{ number_format($salidaDetalle->cantidad_orden,0,',','.') }}</td>
                                        <td class="text-right font-weight-bold">{{ number_format($salidaDetalle->resultado_dia,0,',','.') }}</td>
                                        <td>
                                            @forelse($salidaDetalle->pedidos_vigentes as $pedidoDetalle)
                                                <span class="it-day-order-badge"><i class="fas fa-check mr-1"></i>PEDIDO {{ $pedidoDetalle->nro }}</span>
                                            @empty
                                                <span class="it-day-missing-badge"><i class="fas fa-times mr-1"></i>SIN PEDIDO</span>
                                            @endforelse
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-{{ $salidaDetalle->tiene_pedido ? 'success' : 'danger' }} px-3 py-2">
                                                {{ $salidaDetalle->tiene_pedido ? 'PEDIDO' : 'REVISAR' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot class="bg-light font-weight-bold">
                                <tr><td colspan="2">TOTAL DEL DÍA · {{ $otsDiaDetalle->count() }} OT</td>
                                    <td class="text-right">{{ number_format($cantidadOtDia,0,',','.') }}</td>
                                    <td class="text-right">{{ number_format($cantidadRealDia,0,',','.') }}</td>
                                    <td colspan="2"></td></tr>
                                </tfoot>
                            </table>
                        </div>
                        <p class="small text-muted mb-0"><i class="fas fa-info-circle mr-1"></i>
                            Salida real: <strong>{{ number_format($cantidadRealDia,0,',','.') }} prendas T-T</strong>.
                            Cada OT se cuenta una sola vez por día; si aparece otro día, se muestra en la fecha correspondiente.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <a class="btn btn-outline-primary btn-sm" href="{{ route('seguimiento-ingreso-terminacion.seguimiento-diario', ['fecha' => $fechaDetalle, 'mes' => $mesControl->format('Y-m')]) }}">
                            <i class="fas fa-external-link-alt mr-1"></i>Ver página del día
                        </a>
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

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
.it-calendar-day-link{display:block;color:inherit;text-decoration:none!important}
.it-calendar-day-link:hover{color:#0f172a}
.it-calendar-view-ot{display:block;margin-top:8px;width:100%;font-size:10px;font-weight:700;padding:5px}
.it-calendar-modal{border:0;border-radius:12px;overflow:hidden}
.it-modal-stat{border:1px solid #e2e8f0;border-left:3px solid #334155;border-radius:9px;padding:11px;background:#f8fafc}
.it-modal-stat.is-success{border-left-color:#22c55e}
.it-modal-stat.is-danger{border-left-color:#ef4444}
.it-modal-stat small{display:block;color:#64748b;font-size:10px;font-weight:800}
.it-modal-stat strong{display:block;color:#0f172a;font-size:23px;font-weight:900;line-height:1.25;margin-top:5px}
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
.it-ot-search-kpi{
    border:1px solid #e2e8f0;
    border-left:3px solid #2563eb;
    border-radius:10px;
    padding:11px 13px;
    height:100%;
    min-height:90px;
    background:#fff;
}
.it-ot-search-kpi small{
    display:block;
    font-size:9px;
    color:#64748b;
    font-weight:900;
    letter-spacing:.04em;
}
.it-ot-search-kpi strong{
    display:block;
    color:#0f172a;
    font-size:21px;
    margin-top:5px;
    overflow-wrap:anywhere;
}
.it-ot-search-kpi strong.it-ot-search-stage{
    font-size:14px;
    line-height:1.3;
}
.it-ot-search-kpi span{
    display:block;
    color:#64748b;
    font-size:11px;
    margin-top:3px;
}
.it-ot-search-history{
    max-height:580px;
    overflow:auto;
    border:1px solid #e2e8f0;
    border-radius:9px;
}
.it-ot-search-history thead th{
    position:sticky;
    top:0;
    z-index:1;
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
