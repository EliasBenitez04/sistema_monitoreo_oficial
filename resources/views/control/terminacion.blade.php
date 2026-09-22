@extends('layouts.app')

@section('content')
<style>
    .ct-title { font-weight: 700; color: #26364a; }
    .ct-subtitle { color: #7a8796; font-size: 13px; }
    .ct-card { border: 1px solid #e7ebf0; border-radius: 10px; box-shadow: 0 4px 16px rgba(15, 23, 42, .04); }
    .ct-kpi .label { font-size: 12px; color: #7a8796; text-transform: uppercase; font-weight: 700; letter-spacing: .04em; }
    .ct-kpi .value { font-size: 25px; line-height: 1.1; font-weight: 800; color: #24364b; margin-top: 5px; }
    .ct-table th { font-size: 12px; color: #536273; white-space: nowrap; vertical-align: middle; }
    .ct-table td { vertical-align: middle; font-size: 13px; }
    .ct-code { font-family: monospace; font-size: 12px; }
    .ct-nowrap { white-space: nowrap; }
    .ct-badge { min-width: 86px; display: inline-block; padding: 5px 8px; }
    .ct-flow { border: 1px solid #e9edf2; background: #fafbfd; border-radius: 9px; padding: 10px 12px; font-size: 12px; color: #667483; }
</style>

<div class="container-fluid pb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h2 class="ct-title mb-1">
                <i class="fas fa-industry mr-1"></i>
                Control de Terminación y Logística
            </h2>
            <div class="ct-subtitle">
                Una sola vista por OT: Producto Terminado → Logística → Remisión → Recepción
            </div>
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

    <div class="row">
        <div class="col-xl-8 mb-3">
            <div class="card ct-card h-100">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-filter mr-1"></i>Consulta</strong>
                </div>
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
                                <input type="text" name="buscar" class="form-control" value="{{ $buscar }}"
                                    placeholder="Ej. 30619 o 050617600">
                            </div>
                            <div class="col-lg-3 col-md-8 mb-2">
                                <label class="small font-weight-bold d-block">Estado de envío</label>
                                @foreach(['NO ENVIADO' => 'No enviado', 'PARCIAL' => 'Parcial', 'FINALIZADO' => 'Finalizado'] as $valor => $texto)
                                    <label class="mr-2 mb-0">
                                        <input type="checkbox" name="estado[]" value="{{ $valor }}"
                                            {{ in_array($valor, $estados) ? 'checked' : '' }}>
                                        {{ $texto }}
                                    </label>
                                @endforeach
                            </div>
                            <div class="col-lg-2 col-md-4 mb-2">
                                <button class="btn btn-primary btn-block">
                                    <i class="fas fa-search mr-1"></i>Consultar
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="ct-flow mt-2">
                        <strong>Lectura del módulo:</strong>
                        PT es lo terminado por Producción; Logística es lo distribuido por local;
                        Remitido es lo documentado en ENVIOS; Recibido es lo que ya tiene fecha de recepción.
                        El detalle por destino se carga únicamente al abrir una OT.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 mb-3">
            <div class="card ct-card h-100">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-file-excel text-success mr-1"></i>Actualizar remisiones</strong>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('control.terminacion.importar-remisiones') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="fecha_desde" value="{{ $fechaDesde }}">
                        <input type="hidden" name="fecha_hasta" value="{{ $fechaHasta }}">

                        <input type="file" name="archivo_envios" class="form-control-file mb-2"
                            accept=".xlsx,.xls,.csv" required {{ !$tablaRemisionesDisponible ? 'disabled' : '' }}>

                        <button class="btn btn-success btn-sm" {{ !$tablaRemisionesDisponible ? 'disabled' : '' }}>
                            <i class="fas fa-upload mr-1"></i>Importar ENVIOS
                        </button>

                        <div class="ct-subtitle mt-2">
                            Podés reimportar el mismo archivo. Las filas existentes se actualizan y la recepción se completa cuando aparece su fecha.
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-xl-2 col-md-4 col-6 mb-2">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label">Producto terminado</div>
                <div class="value">{{ number_format($totalTerminado, 0, ',', '.') }}</div>
                <small class="text-muted">{{ $totalOTs }} OTs</small>
            </div></div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-2">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label">Salida logística</div>
                <div class="value">{{ number_format($totalLogistica, 0, ',', '.') }}</div>
                <small class="text-muted">{{ number_format($porcentajeEnviado, 1, ',', '.') }}% del PT</small>
            </div></div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-2">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label">Remitido</div>
                <div class="value">{{ number_format($totalRemitido, 0, ',', '.') }}</div>
                <small class="text-muted d-block">
                    {{ number_format($totalPendienteRemitir, 0, ',', '.') }} pendiente por OT
                </small>
                @if($totalExcesoRemitido > 0)
                    <small class="text-warning d-block">
                        {{ number_format($totalExcesoRemitido, 0, ',', '.') }} excedido en otras OTs
                        · saldo neto {{ number_format($saldoNetoRemitir, 0, ',', '.') }}
                    </small>
                @endif
            </div></div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-2">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label">Recibido</div>
                <div class="value">{{ number_format($totalRecibido, 0, ',', '.') }}</div>
                <small class="text-muted">Confirmado por fecha</small>
            </div></div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-2">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label">En tránsito</div>
                <div class="value">{{ number_format($totalEnTransito, 0, ',', '.') }}</div>
                <small class="text-muted">Remitido no recibido</small>
            </div></div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-2">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label">Sin vínculo</div>
                <div class="value">{{ number_format($remisionesSinVincular, 0, ',', '.') }}</div>
                <small class="text-muted">{{ number_format($remisionesSinVincularFilas, 0, ',', '.') }} líneas del período</small>
            </div></div>
        </div>
    </div>

    <div class="card ct-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <strong><i class="fas fa-list-alt mr-1"></i>Seguimiento por OT</strong>
                <div class="ct-subtitle">Resumen operativo. La trazabilidad detallada se consulta bajo demanda.</div>
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
                        <th class="text-right">Logística</th>
                        <th class="text-right">Remitido</th>
                        <th class="text-right">Recibido</th>
                        <th class="text-right">En tránsito</th>
                        <th class="text-right">Pend. remitir</th>
                        <th class="text-right">Dif. PT/Log.</th>
                        <th>Envío</th>
                        <th>Recepción</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($produccionPaginada as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->nro_ot }}</strong>
                                <br><small class="text-muted">{{ $item->destinos_logisticos }} destinos</small>
                            </td>
                            <td>
                                <span class="ct-code">{{ $item->codigo }}</span>
                                <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($item->descripcion, 42) }}</small>
                            </td>
                            <td class="ct-nowrap">
                                {{ $item->fecha_producto_terminado ? \Carbon\Carbon::parse($item->fecha_producto_terminado)->format('d/m/Y') : '—' }}
                                @if($item->ultima_fecha_producto_terminado && $item->ultima_fecha_producto_terminado !== $item->fecha_producto_terminado)
                                    <br><small class="text-muted">
                                        hasta {{ \Carbon\Carbon::parse($item->ultima_fecha_producto_terminado)->format('d/m/Y') }}
                                    </small>
                                @endif
                            </td>
                            <td class="text-right"><strong>{{ number_format($item->cantidad_terminada, 0, ',', '.') }}</strong></td>
                            <td class="text-right">{{ number_format($item->cantidad_logistica, 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($item->cantidad_remitida, 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($item->cantidad_recibida, 0, ',', '.') }}</td>
                            <td class="text-right">
                                @if($item->cantidad_en_transito > 0)
                                    <span class="badge badge-primary">{{ number_format($item->cantidad_en_transito, 0, ',', '.') }}</span>
                                @else
                                    0
                                @endif
                            </td>
                            <td class="text-right">
                                @if($item->pendiente_remitir > 0)
                                    <span class="badge badge-warning">{{ number_format($item->pendiente_remitir, 0, ',', '.') }}</span>
                                @else
                                    0
                                @endif
                            </td>
                            <td class="text-right">
                                <span class="badge {{ $item->diferencia == 0 ? 'badge-success' : ($item->diferencia > 0 ? 'badge-danger' : 'badge-warning') }}">
                                    {{ number_format($item->diferencia, 0, ',', '.') }}
                                </span>
                            </td>
                            <td>
                                @if($item->estado_control === 'FINALIZADO')
                                    <span class="badge badge-success ct-badge">FINALIZADO</span>
                                @elseif($item->estado_control === 'NO ENVIADO')
                                    <span class="badge badge-danger ct-badge">NO ENVIADO</span>
                                @else
                                    <span class="badge badge-warning ct-badge">PARCIAL</span>
                                @endif
                            </td>
                            <td>
                                @if($item->confirmacion_local === 'RECIBIDO')
                                    <span class="badge badge-success ct-badge">RECIBIDO</span>
                                @elseif($item->confirmacion_local === 'EN TRANSITO')
                                    <span class="badge badge-primary ct-badge">EN TRÁNSITO</span>
                                @elseif($item->confirmacion_local === 'PARCIAL')
                                    <span class="badge badge-info ct-badge">PARCIAL</span>
                                @else
                                    <span class="badge badge-secondary ct-badge">SIN REMISIÓN</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <button type="button" class="btn btn-outline-primary btn-sm btn-detalle-ot ct-nowrap"
                                    data-url="{{ route('control.terminacion.detalle', ['idOt' => $item->id_ot]) }}?fecha_pt={{ $item->fecha_producto_terminado }}">
                                    <i class="fas fa-route mr-1"></i>Ver detalle
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center text-muted py-5">
                                No existen OTs para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>TOTAL FILTRADO</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalTerminado, 0, ',', '.') }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalLogistica, 0, ',', '.') }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalRemitido, 0, ',', '.') }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalRecibido, 0, ',', '.') }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalEnTransito, 0, ',', '.') }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalPendienteRemitir, 0, ',', '.') }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalDiferencia, 0, ',', '.') }}</strong></td>
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
                <h5 class="modal-title"><i class="fas fa-route mr-1"></i>Trazabilidad de la OT</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="contenidoDetalleTerminacion">
                <div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>
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