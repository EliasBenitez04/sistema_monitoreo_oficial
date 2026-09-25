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
    .ct-summary { background:#f8fafc; border:1px solid #e7ebf0; border-radius:10px; padding:12px 14px; }
    .ct-status-strip { display:flex; flex-wrap:wrap; gap:8px; }
    .ct-status-pill { border:1px solid #e4e9ef; background:#fff; border-radius:20px; padding:6px 11px; font-size:12px; }
    .ct-table tbody tr { transition:.15s ease; }
    .ct-table tbody tr:hover { background:#f8fbff; }
    .ct-main-number { font-size:15px; font-weight:800; color:#26364a; }
    .ct-progress-label { font-size:11px; color:#7a8796; }
</style>

<div class="container-fluid pb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h2 class="ct-title mb-1">
                <i class="fas fa-check-double text-primary mr-2"></i>
                Control de Producto Terminado
            </h2>
            <div class="ct-subtitle">
                Vea rápidamente qué entró a Terminación, qué llegó a Producto Terminado y en qué punto del recorrido se encuentra cada OT.
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
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label"><i class="fas fa-sign-in-alt text-primary mr-1"></i>Terminación</div>
                <div class="value">{{ number_format($totalIngresoTerminacion,0,',','.') }}</div>
                <div class="meta">Prendas que ingresaron a Terminación</div>
            </div></div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label"><i class="fas fa-check-double text-success mr-1"></i>Producto Terminado / Logística</div>
                <div class="value">{{ number_format($totalTerminado,0,',','.') }}</div>
                <div class="meta">{{ number_format($porcentajeTerminado,1,',','.') }}% del ingreso · entrada a Logística</div>
            </div></div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label"><i class="fas fa-store text-info mr-1"></i>Recepción Local</div>
                <div class="value">{{ number_format($totalRecepcionLocal,0,',','.') }}</div>
                <div class="meta">Prendas confirmadas por los locales</div>
            </div></div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card ct-card ct-kpi h-100 {{ $totalPendienteTerminar>0?'border-warning':'' }}"><div class="card-body">
                <div class="label"><i class="fas fa-hourglass-half text-warning mr-1"></i>Pendiente Terminación</div>
                <div class="value">{{ number_format($totalPendienteTerminar,0,',','.') }}</div>
                <div class="meta">{{ number_format($otsParciales,0,',','.') }} OTs parciales · máx. {{ number_format($antiguedadMaximaPendiente,0,',','.') }} días</div>
            </div></div>
        </div>
    </div>

    <div class="ct-summary mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <strong>Estado de las OTs del período</strong>
                <div class="ct-subtitle">Producto Terminado ya representa la salida de Terminación y entrada a Logística.</div>
            </div>
            <div class="ct-status-strip mt-2 mt-md-0">
                <span class="ct-status-pill"><i class="fas fa-check-circle text-success mr-1"></i><strong>{{ $otsCompletas }}</strong> completas</span>
                <span class="ct-status-pill"><i class="fas fa-hourglass-half text-warning mr-1"></i><strong>{{ $otsParciales }}</strong> parciales</span>
                @if($otsSinIngreso>0)<span class="ct-status-pill"><i class="fas fa-exclamation-circle text-danger mr-1"></i><strong>{{ $otsSinIngreso }}</strong> sin ingreso</span>@endif
                @if($otsExcedidas>0)<span class="ct-status-pill"><i class="fas fa-exclamation-triangle text-danger mr-1"></i><strong>{{ $otsExcedidas }}</strong> excedidas</span>@endif
            </div>
        </div>
        @if($otMasAntiguaPendiente)
            <div class="mt-2 pt-2 border-top small">
                <i class="fas fa-exclamation-circle text-warning mr-1"></i>
                Mayor atención: <strong>OT {{ $otMasAntiguaPendiente->nro_ot }}</strong> ·
                {{ number_format($otMasAntiguaPendiente->pendiente_terminar,0,',','.') }} prendas pendientes ·
                {{ number_format($otMasAntiguaPendiente->dias_en_terminacion,0,',','.') }} días en Terminación.
            </div>
        @endif
    </div>

    <div class="card ct-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <strong><i class="fas fa-clipboard-check mr-1"></i>Seguimiento de Terminación</strong>
                <div class="ct-subtitle">
                    El rango selecciona las OTs por fecha de Producto Terminado y sigue su recorrido completo hasta los locales.
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
                        <th>Terminación</th>
                        <th>Producto Terminado</th>
                        <th>Situación actual</th>
                        <th class="text-right">Distribución</th>
                        <th class="text-right">Remitido</th>
                        <th class="text-right">Recibido</th>
                        <th>Última remisión</th>
                        <th>Avance</th>
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

                            <td class="ct-nowrap text-center">
                                <span class="ct-main-number">{{ number_format($item->cantidad_ingreso_terminacion,0,',','.') }}</span>
                                <br><small class="text-muted">Ingresó {{ $item->primera_fecha_ingreso ? \Carbon\Carbon::parse($item->primera_fecha_ingreso)->format('d/m/Y') : '—' }}</small>
                                @if($item->pendiente_terminar>0)<br><small class="text-warning"><strong>{{ number_format($item->pendiente_terminar,0,',','.') }}</strong> aún pendientes</small>@endif
                            </td>
                            <td class="ct-nowrap text-center">
                                <span class="ct-main-number">{{ number_format($item->cantidad_terminada,0,',','.') }}</span>
                                <br><small class="text-muted">PT {{ $item->fecha_producto_terminado ? \Carbon\Carbon::parse($item->fecha_producto_terminado)->format('d/m/Y') : '—' }}</small>
                            </td>
                            <td class="text-center">
                                @php
                                    $etapaClase = $item->etapa_numero >= 5 ? 'success' : ($item->etapa_numero >= 4 ? 'warning' : 'primary');
                                @endphp
                                <span class="badge badge-{{ $etapaClase }} px-2 py-2">{{ $item->etapa_actual }}</span>
                            </td>
                            <td class="text-right">
                                <strong>{{ number_format($item->cantidad_logistica,0,',','.') }}</strong>
                                <br><small class="text-muted">{{ $item->primera_fecha_logistica ? 'Desde '.\Carbon\Carbon::parse($item->primera_fecha_logistica)->format('d/m/Y') : 'Aún sin distribución' }}</small>
                            </td>
                            <td class="text-right">
                                <strong>{{ number_format($item->remitido_efectivo,0,',','.') }}/{{ number_format($item->cantidad_orden,0,',','.') }}</strong>
                                @if($item->movimientos_adicionales > 0)<br><small class="text-warning">+{{ number_format($item->movimientos_adicionales,0,',','.') }} mov.</small>@endif
                            </td>
                            <td class="text-right">
                                <strong>{{ number_format($item->recibido_efectivo,0,',','.') }}/{{ number_format($item->cantidad_orden,0,',','.') }}</strong>
                                <br><small class="text-muted">{{ $item->ultima_recepcion ? \Carbon\Carbon::parse($item->ultima_recepcion)->format('d/m/Y') : '—' }}</small>
                            </td>
                            <td class="ct-nowrap text-center">
                                @if($item->ultima_remision)
                                    <strong>{{ \Carbon\Carbon::parse($item->ultima_remision)->format('d/m/Y') }}</strong>
                                    <br><small class="text-muted">últ. remisión</small>
                                @else
                                    <span class="text-muted">Sin remisión</span>
                                @endif
                            </td>
                            <td style="min-width:120px">
                                <div class="progress" style="height:7px"><div class="progress-bar bg-{{ $etapaClase }}" style="width:{{ $item->porcentaje_flujo }}%"></div></div>
                                <small class="d-block text-center ct-progress-label mt-1">{{ $item->porcentaje_flujo }}% · {{ $item->etapa_numero }}/5</small>
                            </td>
                            <td class="text-right">
                                <button type="button"
                                    class="btn btn-outline-primary btn-sm btn-detalle-ot ct-nowrap"
                                    data-url="{{ route('control.terminacion.detalle', ['idOt' => $item->id_ot]) }}">
                                    <i class="fas fa-route mr-1"></i>Detalle
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
                        <td colspan="2"><strong>TOTAL FILTRADO</strong><br><small class="text-muted">{{ number_format($totalOTs,0,',','.') }} OTs</small></td>
                        <td class="text-center"><strong>{{ number_format($totalIngresoTerminacion,0,',','.') }}</strong></td>
                        <td class="text-center"><strong>{{ number_format($totalTerminado,0,',','.') }}</strong></td>
                        <td colspan="7"></td>
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