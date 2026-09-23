@extends('layouts.app')

@section('content')
<style>
    .lg-title { font-weight: 800; color: #25364a; }
    .lg-subtitle { color: #7a8796; font-size: 13px; }
    .lg-card {
        border: 1px solid #e7ecf2;
        border-radius: 12px;
        box-shadow: 0 5px 18px rgba(15, 23, 42, .045);
    }
    .lg-kpi .label {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #778596;
    }
    .lg-kpi .value {
        font-size: 26px;
        line-height: 1.1;
        font-weight: 800;
        color: #233449;
        margin: 6px 0 3px;
    }
    .lg-kpi .meta { font-size: 12px; color: #7a8796; }
    .lg-table th {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #5f6e7e;
        white-space: nowrap;
        vertical-align: middle;
    }
    .lg-table td { font-size: 13px; vertical-align: middle; }
    .lg-code { font-family: monospace; font-size: 12px; }
    .lg-nowrap { white-space: nowrap; }
    .lg-badge { min-width: 82px; display: inline-block; padding: 5px 7px; }
    .lg-summary {
        border: 1px solid #e7ecf2;
        background: #fafbfd;
        border-radius: 10px;
        padding: 12px 14px;
    }
    .lg-summary strong { color: #2b3b4f; }
    .lg-progress {
        height: 7px;
        border-radius: 10px;
        background: #edf1f5;
        overflow: hidden;
    }
    .lg-progress > span {
        display: block;
        height: 100%;
        background: #4e73df;
    }
    .lg-mini-stat {
        padding: 10px 12px;
        border: 1px solid #edf0f4;
        border-radius: 9px;
        background: #fff;
        height: 100%;
    }
</style>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h2 class="lg-title mb-1">
                <i class="fas fa-truck-loading text-primary mr-2"></i>
                Dashboard Logística
            </h2>
            <div class="lg-subtitle">
                Distribución planificada, documentación de remisiones y recepción por sucursal.
            </div>
        </div>

        <div class="mt-2 mt-md-0">
            <a href="{{ route('reporte.logistica-semanal', ['fecha_desde' => $fechaDesde, 'fecha_hasta' => $fechaHasta]) }}"
                class="btn btn-outline-primary btn-sm mr-1">
                <i class="fas fa-calendar-week mr-1"></i>Reporte Semanal
            </a>
            <a href="{{ route('dashboard.logistica.exportar', request()->query()) }}"
                class="btn btn-success btn-sm mr-1">
                <i class="fas fa-file-excel mr-1"></i>Exportar
            </a>
            <a href="{{ route('dashboard.ot-logistica', request()->query()) }}"
                class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-sync-alt mr-1"></i>Actualizar
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

    <div class="card lg-card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('dashboard.ot-logistica') }}">
                <div class="row align-items-end">
                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="small font-weight-bold">Fecha desde</label>
                        <input type="date" name="fecha_desde" class="form-control" value="{{ $fechaDesde }}">
                    </div>

                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="small font-weight-bold">Fecha hasta</label>
                        <input type="date" name="fecha_hasta" class="form-control" value="{{ $fechaHasta }}">
                    </div>

                    <div class="col-lg-3 col-md-4 mb-2">
                        <label class="small font-weight-bold">Sucursales</label>
                        <select name="sucursal[]" id="sucursal" class="form-control select2" multiple style="width:100%">
                            @foreach($sucursales as $sucursal)
                                <option value="{{ $sucursal }}"
                                    {{ in_array($sucursal, $sucursalesSeleccionadas) ? 'selected' : '' }}>
                                    {{ $sucursal }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-8 mb-2">
                        <label class="small font-weight-bold">OT / código / descripción</label>
                        <input type="text" name="busqueda" class="form-control"
                            value="{{ $busqueda }}" placeholder="Ej. 30619, 050617600 o SHORT">
                    </div>

                    <div class="col-lg-2 col-md-4 mb-2">
                        <div class="d-flex">
                            <a href="{{ route('dashboard.ot-logistica') }}"
                                class="btn btn-light border mr-1" title="Limpiar">
                                <i class="fas fa-eraser"></i>
                            </a>
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="fas fa-search mr-1"></i>Consultar
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="lg-summary mt-2">
                <strong>Período:</strong>
                {{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }}
                al
                {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}
                · <strong>{{ number_format($totalOT, 0, ',', '.') }} OTs</strong>
                · {{ number_format($totalSucursales, 0, ',', '.') }} destinos
                · promedio {{ number_format($promedioCantidadOT, 1, ',', '.') }} unidades por OT.
            </div>


            </div>
        </div>
    </div>

    @if(!$tablaRemisionesDisponible)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            La tabla de remisiones todavía no está disponible. El dashboard mostrará únicamente la distribución logística.
        </div>
    @endif

    <div class="row">
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card lg-card lg-kpi h-100">
                <div class="card-body">
                    <div class="label">OTs distribuidas</div>
                    <div class="value">{{ number_format($totalOT, 0, ',', '.') }}</div>
                    <div class="meta">{{ number_format($totalRegistros, 0, ',', '.') }} destinos registrados</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card lg-card lg-kpi h-100">
                <div class="card-body">
                    <div class="label">Plan logística</div>
                    <div class="value">{{ number_format($totalCantidadEnviada, 0, ',', '.') }}</div>
                    <div class="meta">Unidades distribuidas</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card lg-card lg-kpi h-100">
                <div class="card-body">
                    <div class="label">Remitido</div>
                    <div class="value">{{ number_format($totalRemitido, 0, ',', '.') }}</div>
                    <div class="meta">{{ number_format($coberturaRemision, 1, ',', '.') }}% documentado</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card lg-card lg-kpi h-100">
                <div class="card-body">
                    <div class="label">Recibido</div>
                    <div class="value">{{ number_format($totalRecibido, 0, ',', '.') }}</div>
                    <div class="meta">{{ number_format($coberturaRecepcion, 1, ',', '.') }}% de lo remitido</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card lg-card lg-kpi h-100">
                <div class="card-body">
                    <div class="label">En tránsito</div>
                    <div class="value">{{ number_format($totalEnTransito, 0, ',', '.') }}</div>
                    <div class="meta">Remitido sin recepción</div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card lg-card lg-kpi h-100">
                <div class="card-body">
                    <div class="label">Pendiente remitir</div>
                    <div class="value">{{ number_format($totalPendienteRemitir, 0, ',', '.') }}</div>
                    <div class="meta">
                        @if($totalExcesoRemitido > 0)
                            {{ number_format($totalExcesoRemitido, 0, ',', '.') }} excedido en otras OTs
                        @else
                            Sin excedentes
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card lg-card mb-3">
        <div class="card-body py-3">
            <div class="row">
                <div class="col-lg-3 col-6 mb-2 mb-lg-0">
                    <div class="lg-mini-stat">
                        <small class="text-muted d-block">Documentación completa</small>
                        <strong class="h5 mb-0">{{ number_format($otsCompletas, 0, ',', '.') }} OTs</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-6 mb-2 mb-lg-0">
                    <div class="lg-mini-stat">
                        <small class="text-muted d-block">Pendientes de remisión</small>
                        <strong class="h5 mb-0 text-warning">{{ number_format($otsPendientes, 0, ',', '.') }} OTs</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="lg-mini-stat">
                        <small class="text-muted d-block">Sin remisión</small>
                        <strong class="h5 mb-0 text-danger">{{ number_format($otsSinRemision, 0, ',', '.') }} OTs</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="lg-mini-stat">
                        <small class="text-muted d-block">Remisión excedida</small>
                        <strong class="h5 mb-0 {{ $otsExcedidas > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($otsExcedidas, 0, ',', '.') }} OTs
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-7 mb-3">
            <div class="card lg-card h-100">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-store text-primary mr-1"></i>Distribución por sucursal</strong>
                    <div class="lg-subtitle">Plan, remisión y recepción del período seleccionado.</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 lg-table">
                        <thead>
                            <tr>
                                <th>Sucursal</th>
                                <th class="text-right">OTs</th>
                                <th class="text-right">Plan</th>
                                <th class="text-right">Remitido</th>
                                <th class="text-right">Recibido</th>
                                <th class="text-right">Pendiente</th>
                                <th>Cobertura</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($porSucursal as $item)
                                @php
                                    $coberturaSucursal = $item->cantidad_logistica > 0
                                        ? min(100, round(($item->cantidad_remitida / $item->cantidad_logistica) * 100, 1))
                                        : 0;
                                @endphp
                                <tr>
                                    <td><strong>{{ $item->sucursal }}</strong></td>
                                    <td class="text-right">{{ number_format($item->total_ot, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($item->cantidad_logistica, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($item->cantidad_remitida, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($item->cantidad_recibida, 0, ',', '.') }}</td>
                                    <td class="text-right">
                                        @if($item->pendiente_remitir > 0)
                                            <span class="badge badge-warning">
                                                {{ number_format($item->pendiente_remitir, 0, ',', '.') }}
                                            </span>
                                        @else
                                            0
                                        @endif
                                    </td>
                                    <td style="min-width:120px">
                                        <div class="d-flex justify-content-between">
                                            <small>{{ number_format($coberturaSucursal, 1, ',', '.') }}%</small>
                                        </div>
                                        <div class="lg-progress">
                                            <span style="width: {{ $coberturaSucursal }}%"></span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">Sin movimientos para los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5 mb-3">
            <div class="card lg-card h-100">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-calendar-alt text-primary mr-1"></i>Actividad por fecha</strong>
                    <div class="lg-subtitle">Volumen logístico y seguimiento documental.</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 lg-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th class="text-right">OTs</th>
                                <th class="text-right">Plan</th>
                                <th class="text-right">Remitido</th>
                                <th class="text-right">Recibido</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($porFecha as $item)
                                <tr>
                                    <td class="lg-nowrap">
                                        {{ $item->fecha_proceso ? \Carbon\Carbon::parse($item->fecha_proceso)->format('d/m/Y') : '—' }}
                                    </td>
                                    <td class="text-right">{{ number_format($item->total_ot, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($item->cantidad_logistica, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($item->cantidad_remitida, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($item->cantidad_recibida, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Sin actividad.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card lg-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <strong><i class="fas fa-clipboard-list mr-1"></i>Seguimiento por OT</strong>
                <div class="lg-subtitle">Una fila por OT, sin repetir cada destino logístico.</div>
            </div>
            <span class="badge badge-primary">{{ number_format($detalles->total(), 0, ',', '.') }} OTs</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 lg-table">
                <thead>
                    <tr>
                        <th>OT</th>
                        <th>Código / artículo</th>
                        <th>Salida logística</th>
                        <th class="text-right">PT</th>
                        <th class="text-right">Plan</th>
                        <th class="text-right">Remitido</th>
                        <th class="text-right">Recibido</th>
                        <th class="text-right">Tránsito</th>
                        <th class="text-right">Pendiente</th>
                        <th class="text-right">Destinos</th>
                        <th>Documentación</th>
                        <th>Recepción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($detalles as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->nro_ot }}</strong>
                                @if($item->movimientos_logisticos > 1)
                                    <br><small class="text-muted">{{ $item->movimientos_logisticos }} movimientos</small>
                                @endif
                            </td>
                            <td>
                                <span class="lg-code">{{ $item->codigo }}</span>
                                <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($item->descripcion, 44) }}</small>
                            </td>
                            <td class="lg-nowrap">
                                {{ $item->primera_salida ? \Carbon\Carbon::parse($item->primera_salida)->format('d/m/Y') : '—' }}
                                @if($item->ultima_salida && $item->ultima_salida !== $item->primera_salida)
                                    <br><small class="text-muted">hasta {{ \Carbon\Carbon::parse($item->ultima_salida)->format('d/m/Y') }}</small>
                                @endif
                            </td>
                            <td class="text-right">{{ number_format($item->cantidad_pt, 0, ',', '.') }}</td>
                            <td class="text-right"><strong>{{ number_format($item->cantidad_logistica, 0, ',', '.') }}</strong></td>
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
                                @elseif($item->exceso_remitido > 0)
                                    <span class="badge badge-danger">+{{ number_format($item->exceso_remitido, 0, ',', '.') }}</span>
                                @else
                                    0
                                @endif
                            </td>
                            <td class="text-right">{{ number_format($item->destinos, 0, ',', '.') }}</td>
                            <td>
                                @if($item->estado_documental === 'COMPLETO')
                                    <span class="badge badge-success lg-badge">COMPLETO</span>
                                @elseif($item->estado_documental === 'PENDIENTE')
                                    <span class="badge badge-warning lg-badge">PENDIENTE</span>
                                @elseif($item->estado_documental === 'EXCEDENTE')
                                    <span class="badge badge-danger lg-badge">EXCEDENTE</span>
                                @else
                                    <span class="badge badge-secondary lg-badge">SIN REMISIÓN</span>
                                @endif
                            </td>
                            <td>
                                @if($item->estado_recepcion === 'RECIBIDO')
                                    <span class="badge badge-success lg-badge">RECIBIDO</span>
                                @elseif($item->estado_recepcion === 'EN TRANSITO')
                                    <span class="badge badge-primary lg-badge">EN TRÁNSITO</span>
                                @elseif($item->estado_recepcion === 'PARCIAL')
                                    <span class="badge badge-info lg-badge">PARCIAL</span>
                                @else
                                    <span class="badge badge-secondary lg-badge">SIN REMISIÓN</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="text-center text-muted py-5">No se encontraron movimientos logísticos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($detalles->hasPages())
            <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap">
                <small class="text-muted">
                    Mostrando {{ $detalles->firstItem() }}–{{ $detalles->lastItem() }}
                    de {{ $detalles->total() }} OTs
                </small>
                <div>{{ $detalles->links('pagination::bootstrap-4') }}</div>
            </div>
        @endif
    </div>
</div>

<script>
$(function () {
    if ($.fn.select2) {
        $('#sucursal').select2({
            placeholder: 'Todas las sucursales',
            allowClear: true,
            width: '100%'
        });
    }
});
</script>
@endsection