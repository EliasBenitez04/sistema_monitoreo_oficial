@extends('layouts.app')

@section('content')
<section class="content-header pb-2">
<div class="container-fluid trk-shell theme-prod">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <div class="trk-eyebrow">CONTROL DE PEDIDOS</div>
            <h1 class="trk-title mb-1"><i class="fas fa-industry mr-2"></i>Seguimiento de Pedido a Producción</h1>
            <p class="trk-subtitle mb-0">Pedidos <strong>P1, P2...</strong> completos cuando todas sus OT alcanzan <strong>TERMINACION - INGRESO TERMINACION</strong>.</p>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="{{ route('pedidos.index') }}" class="btn btn-dark shadow-sm mr-1">
                <i class="fas fa-th-large mr-1"></i> PEDIDOS
            </a>
            <a href="{{ route('seguimiento-produccion.informe-gerencial') }}" class="btn btn-danger shadow-sm mr-1">
                <i class="fas fa-chart-line mr-1"></i> Informe gerencial
            </a>
            <a href="{{ route('pedidos.importar') }}" class="btn btn-primary shadow-sm mr-1">
                <i class="fas fa-file-import mr-1"></i> Importar datos
            </a>
            <a href="{{ route('seguimiento-ingreso-terminacion.index') }}" class="btn btn-outline-info mr-1">
                <i class="fas fa-sign-in-alt mr-1"></i> Ingreso Terminación
            </a>
            <a href="{{ route('seguimiento-pedidos.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-route mr-1"></i> Locales
            </a>
        </div>
    </div>
</div>
</section>

<section class="content">
<div class="container-fluid trk-shell theme-prod">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger">
            <strong>No se pudo importar:</strong>
            <ul class="mb-0 pl-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @php
        $pendientes = max(0, $resumen->ots - $resumen->ots_completas);
        $avance = $resumen->ots > 0 ? min(100, round(($resumen->ots_completas / $resumen->ots) * 100)) : 0;
    @endphp

    <div class="card border-primary shadow-sm mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap">
            <strong><i class="fas fa-chart-line text-primary mr-2"></i>Panel de decisiones de Producción</strong>
            <span class="text-muted small">Datos registrados hasta: {{ $resumenDecision->fecha_corte->format('d/m/Y') }}</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="trk-kpi">
                        <div><strong>{{ number_format($resumen->ots > 0 ? $resumen->ots_completas * 100 / $resumen->ots : 0,1,',','.') }}%</strong>
                            <span>Avance de OT</span><small>{{ $resumen->ots_completas }} de {{ $resumen->ots }} OT con hito alcanzado</small></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="trk-kpi is-success">
                        <div><strong>{{ $resumenDecision->variacion_7_dias >= 0 ? '+' : '' }}{{ number_format($resumenDecision->variacion_7_dias,1,',','.') }} pp</strong>
                            <span>Evolución en 7 días</span><small>Cambio de avance de OT · puntos porcentuales</small></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="trk-kpi is-warning">
                        <div><strong>{{ number_format($resumenDecision->urgentes,0,',','.') }}</strong>
                            <span>Pedidos requieren atención</span><small>Sin actividad ≥7 días, sin movimientos o ≥30 días de antigüedad</small></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="trk-kpi is-volume">
                        <div><strong>{{ number_format($resumenDecision->prendas_pendientes,0,',','.') }}</strong>
                            <span>Prendas pendientes</span><small>{{ number_format($resumenDecision->porcentaje_prendas,1,',','.') }}% ingresado sobre cantidad solicitada</small></div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 mb-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                        <h6 class="font-weight-bold mb-1">Evolución del cumplimiento del hito (últimos 14 días)</h6>
                        <button type="button" id="btnFindeProduccion" class="btn btn-sm btn-outline-primary"
                                aria-pressed="false">
                            <i class="fas fa-calendar-plus mr-1"></i>Agregar finde
                        </button>
                    </div>
                    <div class="table-responsive" style="max-height:250px;overflow-y:auto">
                        <table class="table table-sm table-striped mb-0">
                            <thead><tr><th>Fecha</th><th class="text-right">OT con hito</th><th class="text-right">Avance</th></tr></thead>
                            <tbody>
                                @foreach($serieAvance as $punto)
                                    <tr class="{{ $punto->fin_de_semana ? 'fila-finde-produccion' : '' }}"{{ $punto->fin_de_semana ? ' style=display:none' : '' }}>
                                        <td>{{ $punto->fecha }}</td>
                                        <td class="text-right">{{ number_format($punto->ot_completas,0,',','.') }}</td>
                                        <td class="text-right font-weight-bold" style="min-width:170px">{{ number_format($punto->porcentaje,1,',','.') }}%<div class="progress mt-1" style="height:7px"><div class="progress-bar bg-primary" style="width:{{ $punto->porcentaje }}%"></div></div></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted">Sábados y domingos ocultos por defecto; podés incluirlos con el botón. Comparación de OT de los pedidos P actuales; una OT cuenta cuando llega a TERMINACION - INGRESO TERMINACION.</small>
                </div>
                <div class="col-lg-6 mb-3">
                    <h6 class="font-weight-bold">Situación general</h6>
                    <div class="progress mb-2" style="height:23px">
                        <div class="progress-bar bg-success" style="width:{{ $resumenDecision->porcentaje_prendas }}%">
                            {{ $resumenDecision->porcentaje_prendas >= 15 ? number_format($resumenDecision->porcentaje_prendas,1,',','.') . '%' : '' }}
                        </div>
                        <div class="progress-bar bg-warning" style="width:{{ 100 - $resumenDecision->porcentaje_prendas }}%"></div>
                    </div>
                    <p class="small mb-2">Prendas ingresadas: <strong>{{ number_format($resumenDecision->prendas_ingresadas,0,',','.') }}</strong> /
                        {{ number_format($resumenDecision->prendas_pedidas,0,',','.') }} solicitadas.</p>
                    <p class="mb-1"><span class="badge badge-danger">{{ $resumenDecision->urgentes }} urgentes</span>
                        <span class="badge badge-warning">{{ $resumenDecision->revisar }} a revisar</span>
                        <span class="badge badge-success">{{ $resumenDecision->avanzando }} con actividad reciente</span></p>
                    <small class="text-muted">Los estados indican actividad reciente, no garantía de cumplimiento del plazo.</small>
                </div>
            </div>
            <h6 class="font-weight-bold mt-1">Pedidos para atender primero</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="thead-light">
                        <tr><th>Pedido</th><th class="text-right">Avance OT</th><th class="text-right">Prendas pendientes</th><th>Última actividad</th><th>Sin movimiento</th><th>Prioridad</th><th>Acción</th></tr>
                    </thead>
                    <tbody>
                    @forelse($rankingPrioridad as $prioridad)
                        <tr>
                            <td><strong>{{ $prioridad->nro_pedido }}</strong></td>
                            <td class="text-right">{{ number_format($prioridad->porcentaje,1,',','.') }}%</td>
                            <td class="text-right">{{ number_format($prioridad->pendiente_prendas,0,',','.') }}</td>
                            <td>{{ $prioridad->ultima_actividad ? date('d/m/Y',strtotime($prioridad->ultima_actividad)) : 'Sin registro' }}</td>
                            <td>{{ $prioridad->dias_sin_movimiento !== null ? $prioridad->dias_sin_movimiento . ' días' : 'Sin registro' }}</td>
                            <td>
                                <span class="badge badge-{{ $prioridad->prioridad === 'URGENTE' ? 'danger' : ($prioridad->prioridad === 'REVISAR' ? 'warning' : 'success') }}">
                                    {{ $prioridad->prioridad }}
                                </span>
                            </td>
                            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('seguimiento-produccion.show',$prioridad->id) }}">Ver OT</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No hay pedidos pendientes.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <small class="text-muted">Criterios iniciales: urgente sin actividad registrada, 7+ días sin movimiento o 30+ días desde el pedido; revisar 3 a 6 días sin movimiento. Se preserva la diferencia entre avance por OT (hito alcanzado) y avance por prendas.</small>
        </div>
    </div>

    <div class="trk-hero mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="trk-pill"><i class="fas fa-stream mr-1"></i>AVANCE GENERAL</div>
                <div class="d-flex justify-content-between align-items-end mt-3">
                    <div>
                        <div class="trk-hero-value">{{ $avance }}%</div>
                        <div class="trk-hero-label">OT que ya alcanzaron Ingreso Terminación</div>
                    </div>
                    <div class="text-right">
                        <div class="trk-hero-detail">{{ number_format($resumen->ots_completas,0,',','.') }} / {{ number_format($resumen->ots,0,',','.') }} OT</div>
                        <small class="text-muted">controladas en pedidos P</small>
                    </div>
                </div>
                <div class="trk-progress mt-3"><div style="width:{{ $avance }}%"></div></div>
            </div>
            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="trk-side">
                    <span>Pedidos P</span>
                    <strong>{{ number_format($resumen->pedidos,0,',','.') }}</strong>
                    <small>{{ number_format($resumen->completos,0,',','.') }} completos · {{ number_format(max(0,$resumen->pedidos-$resumen->completos),0,',','.') }} en curso</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="trk-kpi">
                <div class="trk-kpi-icon"><i class="fas fa-list-ol"></i></div>
                <div><strong>{{ number_format($resumen->ots,0,',','.') }}</strong><span>OT vinculadas</span><small>Total de OT en pedidos P</small></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="trk-kpi is-success">
                <div class="trk-kpi-icon"><i class="fas fa-check"></i></div>
                <div><strong>{{ number_format($resumen->ots_completas,0,',','.') }}</strong><span>OT completadas</span><small>Ya tienen INGRESO TERMINACIÓN</small></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="trk-kpi is-warning">
                <div class="trk-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
                <div><strong>{{ number_format($pendientes,0,',','.') }}</strong><span>OT pendientes</span><small>Aún sin INGRESO TERMINACIÓN</small></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="trk-kpi is-volume">
                <div class="trk-kpi-icon"><i class="fas fa-boxes"></i></div>
                <div><strong>{{ number_format($resumen->ots > 0 ? $resumen->ots : 0,0,',','.') }}</strong><span>Base de seguimiento</span><small>{{ $avance }}% de avance global</small></div>
            </div>
        </div>
    </div>

    <div class="card trk-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-clipboard-list mr-2 text-primary"></i>Pedidos a Producción</h3>
                <small class="text-muted">Seguimiento consolidado por pedido P.</small>
            </div>
            <form class="form-inline mt-2 mt-md-0">
                <input class="form-control form-control-sm mr-2" name="buscar" value="{{ $buscar }}" placeholder="Buscar P1, P2...">
                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 text-center trk-table">
                <thead><tr><th>Pedido</th><th>OT</th><th>Prendas</th><th>Ingreso Terminación</th><th>Avance</th><th>Situación</th><th>Fecha pedido</th><th>Último ingreso</th><th>Tiempo</th><th>Detalle</th></tr></thead>
                <tbody>
                @forelse($pedidos as $pedido)
                    <tr>
                        <td><span class="trk-order">{{ $pedido->nro_pedido }}</span></td>
                        <td><strong>{{ $pedido->ots_total }}</strong> OT</td>
                        <td><strong>{{ number_format($pedido->cantidad_total,0,',','.') }}</strong></td>
                        <td><strong>{{ number_format($pedido->cantidad_ingreso,0,',','.') }}</strong></td>
                        <td>
                            <strong>{{ $pedido->porcentaje }}%</strong>
                            <small class="d-block text-muted">{{ $pedido->ots_completas }}/{{ $pedido->ots_total }} OT</small>
                            <div class="trk-progress-xs"><div style="width:{{ $pedido->porcentaje }}%"></div></div>
                        </td>
                        <td>
                            @if($pedido->completo)
                                <span class="trk-state is-complete"><i class="fas fa-check mr-1"></i>COMPLETO</span>
                            @elseif($pedido->ots_completas > 0)
                                <span class="trk-state is-progress"><i class="fas fa-cogs mr-1"></i>EN PROCESO</span>
                            @else
                                <span class="trk-state is-pending">PENDIENTE</span>
                            @endif
                        </td>
                        <td>{{ $pedido->fecha_pedido ? $pedido->fecha_pedido->format('d/m/Y') : '-' }}</td>
                        <td>{{ $pedido->ultimo_ingreso ? date('d/m/Y',strtotime($pedido->ultimo_ingreso)) : '-' }}</td>
                        <td>
                            @if($pedido->dias !== null)
                                <strong class="text-success">{{ $pedido->dias }} {{ $pedido->dias == 1 ? 'día' : 'días' }}</strong>
                            @elseif($pedido->dias_en_curso !== null)
                                <strong class="text-warning">{{ $pedido->dias_en_curso }} {{ $pedido->dias_en_curso == 1 ? 'día' : 'días' }}</strong>
                                <small class="d-block text-muted">en curso</small>
                            @else -
                            @endif
                        </td>
                        <td><a href="{{ route('seguimiento-produccion.show',$pedido->id) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye mr-1"></i>Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-5">Todavía no hay pedidos P importados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $pedidos->links() }}</div>
    </div>
</div>
</section>
@endsection

@include('seguimiento_shared.tracking_styles')

@push('page_scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const boton = document.getElementById('btnFindeProduccion');
    if (!boton) return;
    let mostrarFinde = false;
    boton.addEventListener('click', function () {
        mostrarFinde = !mostrarFinde;
        document.querySelectorAll('.fila-finde-produccion').forEach(function (fila) {
            fila.style.display = mostrarFinde ? '' : 'none';
        });
        boton.setAttribute('aria-pressed', mostrarFinde ? 'true' : 'false');
        boton.classList.toggle('btn-primary', mostrarFinde);
        boton.classList.toggle('btn-outline-primary', !mostrarFinde);
        boton.innerHTML = mostrarFinde
            ? '<i class="fas fa-calendar-minus mr-1"></i>Quitar finde'
            : '<i class="fas fa-calendar-plus mr-1"></i>Agregar finde';
    });
});
</script>
@endpush

