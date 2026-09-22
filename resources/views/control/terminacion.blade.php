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
                Control del traspaso Terminación → Logística. La recepción de locales se gestiona en el Dashboard Logística.
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
                        <input type="date" name="fecha_desde" class="form-control"
                            value="{{ $fechaDesde }}" required>
                    </div>

                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="small font-weight-bold">PT hasta</label>
                        <input type="date" name="fecha_hasta" class="form-control"
                            value="{{ $fechaHasta }}" required>
                    </div>

                    <div class="col-lg-3 col-md-4 mb-2">
                        <label class="small font-weight-bold">OT / código / descripción</label>
                        <input type="text" name="buscar" class="form-control"
                            value="{{ $buscar }}" placeholder="Ej. 30619 o 050617600">
                    </div>

                    <div class="col-lg-3 col-md-8 mb-2">
                        <label class="small font-weight-bold d-block">Estado</label>
                        @foreach(['SIN ENVIAR' => 'Sin enviar', 'PARCIAL' => 'Parcial', 'ENTREGADO' => 'Entregado', 'EXCEDENTE' => 'Excedente'] as $valor => $texto)
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
                <strong>Qué controla esta pantalla:</strong>
                compara lo que salió de <strong>Producto Terminado</strong> contra lo que ya fue tomado por
                <strong>Logística y Distribución</strong>. Las OTs pendientes más antiguas aparecen primero.
            </div>
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label">Producto terminado</div>
                <div class="value">{{ number_format($totalTerminado, 0, ',', '.') }}</div>
                <div class="meta">{{ number_format($totalOTs, 0, ',', '.') }} OTs</div>
            </div></div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label">Entregado a Logística</div>
                <div class="value">{{ number_format($totalLogistica, 0, ',', '.') }}</div>
                <div class="meta">{{ number_format($porcentajeEntregado, 1, ',', '.') }}% del PT</div>
            </div></div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label">Pendiente entregar</div>
                <div class="value">{{ number_format($totalPendienteLogistica, 0, ',', '.') }}</div>
                <div class="meta">
                    @if($totalExcesoLogistica > 0)
                        {{ number_format($totalExcesoLogistica, 0, ',', '.') }} excedido en otras OTs
                    @else
                        Sin excedentes
                    @endif
                </div>
            </div></div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label">OTs pendientes</div>
                <div class="value">{{ number_format($otsPendientes, 0, ',', '.') }}</div>
                <div class="meta">
                    {{ number_format($otsSinEnviar, 0, ',', '.') }} sin enviar ·
                    {{ number_format($otsParciales, 0, ',', '.') }} parciales
                </div>
            </div></div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label">Máxima espera pendiente</div>
                <div class="value">{{ number_format($antiguedadMaximaPendiente, 0, ',', '.') }}</div>
                <div class="meta">días desde Producto Terminado</div>
            </div></div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label">PT → primera salida</div>
                <div class="value">{{ number_format($promedioDiasPrimeraSalida, 1, ',', '.') }}</div>
                <div class="meta">promedio de días</div>
            </div></div>
        </div>
    </div>

    <div class="card ct-card mb-3">
        <div class="card-body py-3">
            <div class="row">
                <div class="col-lg-3 col-6 mb-2 mb-lg-0">
                    <div class="ct-mini">
                        <small class="text-muted d-block">Entregadas completas</small>
                        <strong class="h5 mb-0 text-success">{{ number_format($otsEntregadas, 0, ',', '.') }} OTs</strong>
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
                        <small class="text-muted d-block">Sin enviar</small>
                        <strong class="h5 mb-0 text-danger">{{ number_format($otsSinEnviar, 0, ',', '.') }} OTs</strong>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="ct-mini">
                        <small class="text-muted d-block">Excedidas</small>
                        <strong class="h5 mb-0 {{ $otsExcedidas > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($otsExcedidas, 0, ',', '.') }} OTs
                        </strong>
                    </div>
                </div>
            </div>

            @if($otMasAntiguaPendiente)
                <div class="alert alert-light border mt-3 mb-0 py-2">
                    <i class="fas fa-hourglass-half text-warning mr-1"></i>
                    <strong>Mayor antigüedad pendiente:</strong>
                    OT {{ $otMasAntiguaPendiente->nro_ot }} ·
                    {{ $otMasAntiguaPendiente->codigo }} ·
                    {{ number_format($otMasAntiguaPendiente->pendiente_logistica, 0, ',', '.') }} prendas pendientes ·
                    {{ number_format($otMasAntiguaPendiente->dias_espera, 0, ',', '.') }} días desde PT.
                </div>
            @endif
        </div>
    </div>

    <div class="card ct-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <strong><i class="fas fa-clipboard-check mr-1"></i>Seguimiento Terminación → Logística</strong>
                <div class="ct-subtitle">
                    Primero se muestran las OTs pendientes con mayor antigüedad.
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
                        <th>Fecha PT</th>
                        <th class="text-right">PT</th>
                        <th>Primera salida</th>
                        <th>Última salida</th>
                        <th class="text-right">Logística</th>
                        <th class="text-right">Pendiente</th>
                        <th class="text-right">Espera</th>
                        <th class="text-right">Destinos</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($produccionPaginada as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->nro_ot }}</strong>
                                @if($item->movimientos_logisticos > 1)
                                    <br><small class="text-muted">{{ $item->movimientos_logisticos }} salidas</small>
                                @endif
                            </td>

                            <td>
                                <span class="ct-code">{{ $item->codigo }}</span>
                                <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($item->descripcion, 46) }}</small>
                            </td>

                            <td class="ct-nowrap">
                                {{ $item->fecha_producto_terminado ? \Carbon\Carbon::parse($item->fecha_producto_terminado)->format('d/m/Y') : '—' }}
                                @if($item->ultima_fecha_producto_terminado && $item->ultima_fecha_producto_terminado !== $item->fecha_producto_terminado)
                                    <br><small class="text-muted">
                                        hasta {{ \Carbon\Carbon::parse($item->ultima_fecha_producto_terminado)->format('d/m/Y') }}
                                    </small>
                                @endif
                            </td>

                            <td class="text-right">
                                <strong>{{ number_format($item->cantidad_terminada, 0, ',', '.') }}</strong>
                            </td>

                            <td class="ct-nowrap">
                                {{ $item->primera_salida ? \Carbon\Carbon::parse($item->primera_salida)->format('d/m/Y') : '—' }}
                            </td>

                            <td class="ct-nowrap">
                                {{ $item->ultima_salida ? \Carbon\Carbon::parse($item->ultima_salida)->format('d/m/Y') : '—' }}
                            </td>

                            <td class="text-right">
                                {{ number_format($item->cantidad_logistica, 0, ',', '.') }}
                                @if($item->exceso_logistica > 0)
                                    <br><small class="text-danger">+{{ number_format($item->exceso_logistica, 0, ',', '.') }} excedido</small>
                                @endif
                            </td>

                            <td class="text-right">
                                @if($item->pendiente_logistica > 0)
                                    <span class="badge badge-warning">
                                        {{ number_format($item->pendiente_logistica, 0, ',', '.') }}
                                    </span>
                                @else
                                    0
                                @endif
                            </td>

                            <td class="text-right ct-nowrap">
                                @if($item->pendiente_logistica > 0)
                                    <span class="{{ $item->dias_espera >= 7 ? 'ct-wait-high' : ($item->dias_espera >= 3 ? 'ct-wait-mid' : '') }}">
                                        {{ number_format($item->dias_espera, 0, ',', '.') }} días
                                    </span>
                                @elseif($item->dias_primera_salida !== null)
                                    <span class="text-muted">{{ number_format($item->dias_primera_salida, 0, ',', '.') }} días</span>
                                @else
                                    —
                                @endif
                            </td>

                            <td class="text-right">{{ number_format($item->destinos_logisticos, 0, ',', '.') }}</td>

                            <td>
                                @if($item->estado_control === 'ENTREGADO')
                                    <span class="badge badge-success ct-badge">ENTREGADO</span>
                                @elseif($item->estado_control === 'SIN ENVIAR')
                                    <span class="badge badge-danger ct-badge">SIN ENVIAR</span>
                                @elseif($item->estado_control === 'EXCEDENTE')
                                    <span class="badge badge-danger ct-badge">EXCEDENTE</span>
                                @else
                                    <span class="badge badge-warning ct-badge">PARCIAL</span>
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
                            <td colspan="12" class="text-center text-muted py-5">
                                No existen OTs para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    <tr>
                        <td colspan="3"><strong>TOTAL FILTRADO</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalTerminado, 0, ',', '.') }}</strong></td>
                        <td colspan="2"></td>
                        <td class="text-right"><strong>{{ number_format($totalLogistica, 0, ',', '.') }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalPendienteLogistica, 0, ',', '.') }}</strong></td>
                        <td colspan="4"></td>
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
                        <i class="fas fa-route mr-1"></i>Trazabilidad de la OT
                    </h5>
                    <small class="text-muted">
                        El detalle incluye destinos, remisiones y recepción solo para investigación.
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