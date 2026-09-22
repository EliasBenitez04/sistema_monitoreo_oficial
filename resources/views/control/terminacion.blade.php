@extends('layouts.app')

@section('content')
<style>
    .ct-title { font-weight: 800; color: #26364a; }
    .ct-subtitle { color: #7a8796; font-size: 13px; }
    .ct-card { border: 1px solid #e7ebf0; border-radius: 12px; box-shadow: 0 5px 18px rgba(15, 23, 42, .045); }
    .ct-kpi .label { font-size: 11px; color: #7a8796; text-transform: uppercase; font-weight: 800; letter-spacing: .05em; }
    .ct-kpi .value { font-size: 27px; line-height: 1.1; font-weight: 800; color: #24364b; margin: 6px 0 3px; }
    .ct-kpi .meta { font-size: 12px; color: #7a8796; }
    .ct-table th { font-size: 11px; color: #536273; text-transform: uppercase; letter-spacing: .03em; white-space: nowrap; vertical-align: middle; }
    .ct-table td { vertical-align: middle; font-size: 13px; }
    .ct-code { font-family: monospace; font-size: 12px; }
    .ct-nowrap { white-space: nowrap; }
    .ct-badge { min-width: 86px; display: inline-block; padding: 5px 8px; }
    .ct-flow { border: 1px solid #e9edf2; background: #fafbfd; border-radius: 9px; padding: 11px 13px; font-size: 12px; color: #667483; }
    .ct-mini { border: 1px solid #edf0f4; border-radius: 9px; padding: 11px 13px; background: #fff; height: 100%; }
    .ct-wait-high { color: #dc3545; font-weight: 700; }
    .ct-wait-mid { color: #d39e00; font-weight: 700; }
</style>

<div class="container-fluid pb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h2 class="ct-title mb-1">
                <i class="fas fa-check-double text-primary mr-2"></i>
                Control de Producto Terminado
            </h2>
            <div class="ct-subtitle">
                Control de Terminación: ingreso al área → Producto Terminado → entrega/entrada a Logística.
            </div>
        </div>

        <div class="mt-2 mt-md-0">
            <a href="{{ route('dashboard.ot-logistica') }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-truck-loading mr-1"></i>Ir a Dashboard Logística
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle mr-1"></i>{{ session('error') }}
        </div>
    @endif

    <div class="card ct-card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('control.terminacion') }}">
                <div class="row align-items-end">
                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="small font-weight-bold">PT desde</label>
                        <input type="date" name="fecha_desde" class="form-control" value="{{ $fechaDesde }}" required>
                    </div>

                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="small font-weight-bold">PT hasta</label>
                        <input type="date" name="fecha_hasta" class="form-control" value="{{ $fechaHasta }}" required>
                    </div>

                    <div class="col-lg-3 col-md-4 mb-2">
                        <label class="small font-weight-bold">OT / código / descripción</label>
                        <input type="text" name="buscar" class="form-control"
                            value="{{ $buscar }}" placeholder="Ej. 30619 o 050617600">
                    </div>

                    <div class="col-lg-3 col-md-8 mb-2">
                        <label class="small font-weight-bold d-block">Estado de Terminación</label>
                        @foreach(['PARCIAL' => 'Parcial', 'COMPLETO' => 'Completo', 'SIN INGRESO' => 'Sin ingreso', 'EXCEDENTE' => 'Excedente'] as $valor => $texto)
                            <label class="mr-2 mb-0">
                                <input type="checkbox" name="estado[]" value="{{ $valor }}"
                                    {{ in_array($valor, $estados) ? 'checked' : '' }}>
                                {{ $texto }}
                            </label>
                        @endforeach
                    </div>

                    <div class="col-lg-2 col-md-4 mb-2">
                        <div class="d-flex">
                            <a href="{{ route('control.terminacion') }}" class="btn btn-light border mr-1" title="Limpiar">
                                <i class="fas fa-eraser"></i>
                            </a>
                            <button class="btn btn-primary flex-fill">
                                <i class="fas fa-search mr-1"></i>Consultar
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="ct-flow mt-2">
                <strong>Regla del flujo:</strong>
                <code>TERMINACION - TERMINACION</code> es el ingreso a Terminación.
                <code>TERMINACION - PRODUCTO TERMINADO</code> es la salida de Terminación y ya representa
                la <strong>entrega/entrada a Logística</strong>.
                La distribución posterior a locales se controla en el Dashboard Logística.
            </div>
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label">Ingreso Terminación</div>
                <div class="value">{{ number_format($totalIngresoTerminacion, 0, ',', '.') }}</div>
                <div class="meta">Base de las OTs con PT del período</div>
            </div></div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label">Producto terminado</div>
                <div class="value">{{ number_format($totalTerminado, 0, ',', '.') }}</div>
                <div class="meta">{{ number_format($totalOTs, 0, ',', '.') }} OTs · {{ number_format($porcentajeTerminado, 1, ',', '.') }}% del ingreso</div>
            </div></div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label">Entregado a Logística</div>
                <div class="value">{{ number_format($totalEntregadoLogistica, 0, ',', '.') }}</div>
                <div class="meta">{{ number_format($porcentajeEntregadoLogistica, 1, ',', '.') }}% del Producto Terminado</div>
            </div></div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label">Pendiente terminar</div>
                <div class="value">{{ number_format($totalPendienteTerminar, 0, ',', '.') }}</div>
                <div class="meta">
                    @if($totalExcesoProductoTerminado > 0)
                        {{ number_format($totalExcesoProductoTerminado, 0, ',', '.') }} excedido vs ingreso
                    @else
                        Sin excedentes
                    @endif
                </div>
            </div></div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label">Máxima espera parcial</div>
                <div class="value">{{ number_format($antiguedadMaximaPendiente, 0, ',', '.') }}</div>
                <div class="meta">días dentro de Terminación</div>
            </div></div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label">Tiempo en Terminación</div>
                <div class="value">{{ number_format($promedioDiasTerminacion, 1, ',', '.') }}</div>
                <div class="meta">promedio de días ingreso → PT</div>
            </div></div>
        </div>
    </div>

    <div class="card ct-card mb-3">
        <div class="card-body py-3">
            <div class="row">
                <div class="col-lg-3 col-6 mb-2 mb-lg-0">
                    <div class="ct-mini">
                        <small class="text-muted d-block">Completas</small>
                        <strong class="h5 mb-0 text-success">{{ number_format($otsCompletas, 0, ',', '.') }} OTs</strong>
                    </div>
                </div>

                <div class="col-lg-3 col-6 mb-2 mb-lg-0">
                    <div class="ct-mini">
                        <small class="text-muted d-block">Parciales</small>
                        <strong class="h5 mb-0 text-warning">{{ number_format($otsParciales, 0, ',', '.') }} OTs</strong>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="ct-mini">
                        <small class="text-muted d-block">Sin ingreso Terminación</small>
                        <strong class="h5 mb-0 text-danger">{{ number_format($otsSinIngreso, 0, ',', '.') }} OTs</strong>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="ct-mini">
                        <small class="text-muted d-block">PT excedido vs ingreso</small>
                        <strong class="h5 mb-0 {{ $otsExcedidas > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($otsExcedidas, 0, ',', '.') }} OTs
                        </strong>
                    </div>
                </div>
            </div>

            @if($otMasAntiguaPendiente)
                <div class="alert alert-light border mt-3 mb-0 py-2">
                    <i class="fas fa-hourglass-half text-warning mr-1"></i>
                    <strong>Mayor antigüedad parcial:</strong>
                    OT {{ $otMasAntiguaPendiente->nro_ot }} ·
                    {{ $otMasAntiguaPendiente->codigo }} ·
                    {{ number_format($otMasAntiguaPendiente->pendiente_terminar, 0, ',', '.') }} prendas todavía en Terminación ·
                    {{ number_format($otMasAntiguaPendiente->dias_en_terminacion, 0, ',', '.') }} días.
                </div>
            @endif
        </div>
    </div>

    <div class="card ct-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <strong><i class="fas fa-clipboard-check mr-1"></i>Seguimiento de Terminación</strong>
                <div class="ct-subtitle">
                    Producto Terminado ya se considera entregado a Logística.
                </div>
            </div>
            <span class="badge badge-primary">{{ $produccionPaginada->total() }} OTs</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 ct-table">
                <thead>
                    <tr>
                        <th>OT</th>
                        <th>Código / descripción</th>
                        <th>Ingreso Terminación</th>
                        <th class="text-right">Cant. ingreso</th>
                        <th>Producto Terminado</th>
                        <th class="text-right">Cant. PT</th>
                        <th class="text-right">Entregado Logística</th>
                        <th class="text-right">Pend. terminar</th>
                        <th class="text-right">Días</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($produccionPaginada as $item)
                        <tr>
                            <td><strong>{{ $item->nro_ot }}</strong></td>

                            <td>
                                <span class="ct-code">{{ $item->codigo }}</span>
                                <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($item->descripcion, 46) }}</small>
                            </td>

                            <td class="ct-nowrap">
                                {{ $item->primera_fecha_ingreso ? \Carbon\Carbon::parse($item->primera_fecha_ingreso)->format('d/m/Y') : '—' }}
                                @if($item->ultima_fecha_ingreso && $item->ultima_fecha_ingreso !== $item->primera_fecha_ingreso)
                                    <br><small class="text-muted">
                                        hasta {{ \Carbon\Carbon::parse($item->ultima_fecha_ingreso)->format('d/m/Y') }}
                                    </small>
                                @endif
                            </td>

                            <td class="text-right">{{ number_format($item->cantidad_ingreso_terminacion, 0, ',', '.') }}</td>

                            <td class="ct-nowrap">
                                {{ $item->fecha_producto_terminado ? \Carbon\Carbon::parse($item->fecha_producto_terminado)->format('d/m/Y') : '—' }}
                                @if($item->ultima_fecha_producto_terminado && $item->ultima_fecha_producto_terminado !== $item->fecha_producto_terminado)
                                    <br><small class="text-muted">
                                        hasta {{ \Carbon\Carbon::parse($item->ultima_fecha_producto_terminado)->format('d/m/Y') }}
                                    </small>
                                @endif
                            </td>

                            <td class="text-right"><strong>{{ number_format($item->cantidad_terminada, 0, ',', '.') }}</strong></td>

                            <td class="text-right">
                                <span class="badge badge-success">
                                    {{ number_format($item->cantidad_entregada_logistica, 0, ',', '.') }}
                                </span>
                            </td>

                            <td class="text-right">
                                @if($item->pendiente_terminar > 0)
                                    <span class="badge badge-warning">
                                        {{ number_format($item->pendiente_terminar, 0, ',', '.') }}
                                    </span>
                                @elseif($item->exceso_producto_terminado > 0)
                                    <span class="badge badge-danger">
                                        +{{ number_format($item->exceso_producto_terminado, 0, ',', '.') }}
                                    </span>
                                @else
                                    0
                                @endif
                            </td>

                            <td class="text-right ct-nowrap">
                                @if($item->dias_en_terminacion !== null)
                                    <span class="{{ $item->pendiente_terminar > 0 && $item->dias_en_terminacion >= 7 ? 'ct-wait-high' : ($item->pendiente_terminar > 0 && $item->dias_en_terminacion >= 3 ? 'ct-wait-mid' : '') }}">
                                        {{ number_format($item->dias_en_terminacion, 0, ',', '.') }} días
                                    </span>
                                @else
                                    —
                                @endif
                            </td>

                            <td>
                                @if($item->estado_control === 'COMPLETO')
                                    <span class="badge badge-success ct-badge">COMPLETO</span>
                                @elseif($item->estado_control === 'PARCIAL')
                                    <span class="badge badge-warning ct-badge">PARCIAL</span>
                                @elseif($item->estado_control === 'EXCEDENTE')
                                    <span class="badge badge-danger ct-badge">EXCEDENTE</span>
                                @else
                                    <span class="badge badge-secondary ct-badge">SIN INGRESO</span>
                                @endif
                            </td>

                            <td class="text-right">
                                <button type="button"
                                    class="btn btn-outline-primary btn-sm btn-detalle-ot ct-nowrap"
                                    data-url="{{ route('control.terminacion.detalle', ['idOt' => $item->id_ot]) }}?fecha_pt={{ $item->fecha_producto_terminado }}">
                                    <i class="fas fa-route mr-1"></i>Trazabilidad
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-5">
                                No existen registros de Producto Terminado para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    <tr>
                        <td colspan="3"><strong>TOTAL FILTRADO</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalIngresoTerminacion, 0, ',', '.') }}</strong></td>
                        <td></td>
                        <td class="text-right"><strong>{{ number_format($totalTerminado, 0, ',', '.') }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalEntregadoLogistica, 0, ',', '.') }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalPendienteTerminar, 0, ',', '.') }}</strong></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if($produccionPaginada->hasPages())
            <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap">
                <small class="text-muted">
                    Mostrando {{ $produccionPaginada->firstItem() }}–{{ $produccionPaginada->lastItem() }}
                    de {{ $produccionPaginada->total() }} OTs
                </small>
                <div>{{ $produccionPaginada->links('pagination::bootstrap-4') }}</div>
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="modalDetalleTerminacion" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">
                        <i class="fas fa-route mr-1"></i>Trazabilidad posterior de la OT
                    </h5>
                    <small class="text-muted">
                        Muestra la distribución, remisiones y recepción después de Producto Terminado.
                    </small>
                </div>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body" id="contenidoDetalleTerminacion">
                <div class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('click', function (event) {
    var boton = event.target.closest('.btn-detalle-ot');
    if (!boton) return;

    var contenido = document.getElementById('contenidoDetalleTerminacion');
    var url = boton.getAttribute('data-url');

    contenido.innerHTML =
        '<div class="text-center py-5">' +
        '<i class="fas fa-spinner fa-spin fa-2x text-primary"></i>' +
        '<div class="text-muted mt-2">Cargando trazabilidad…</div>' +
        '</div>';

    $('#modalDetalleTerminacion').modal('show');

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (respuesta) {
            if (!respuesta.ok) throw new Error('No se pudo cargar el detalle.');
            return respuesta.text();
        })
        .then(function (html) {
            contenido.innerHTML = html;
        })
        .catch(function () {
            contenido.innerHTML =
                '<div class="alert alert-danger mb-0">' +
                '<i class="fas fa-exclamation-triangle mr-1"></i>' +
                'No se pudo cargar la trazabilidad de esta OT.' +
                '</div>';
        });
});
</script>
@endsection