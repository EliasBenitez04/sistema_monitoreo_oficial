@extends('layouts.app')

@section('content')
<style>
    .rs-title { font-weight: 800; color: #25364a; }
    .rs-subtitle { color: #7a8796; font-size: 13px; }
    .rs-card { border: 1px solid #e7ecf2; border-radius: 12px; box-shadow: 0 5px 18px rgba(15,23,42,.045); }
    .rs-kpi .label { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #778596; }
    .rs-kpi .value { font-size: 26px; line-height: 1.1; font-weight: 800; color: #233449; margin: 6px 0 3px; }
    .rs-kpi .meta { font-size: 12px; color: #7a8796; }
    .rs-table th { font-size: 11px; text-transform: uppercase; letter-spacing: .03em; color: #5f6e7e; white-space: nowrap; vertical-align: middle; }
    .rs-table td { font-size: 13px; vertical-align: middle; }
    .rs-code { font-family: monospace; font-size: 12px; }
    .rs-nowrap { white-space: nowrap; }
    .rs-plan { background: #f7f9fc; }
    .rs-real { background: #f4fbf7; }
    .rs-summary { border: 1px solid #e7ecf2; background: #fafbfd; border-radius: 10px; padding: 11px 13px; font-size: 12px; }
</style>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h2 class="rs-title mb-1">
                <i class="fas fa-calendar-week text-primary mr-2"></i>
                Reporte Semanal de Logística
            </h2>
            <div class="rs-subtitle">
                Plan de distribución vs movimiento real a locales y Mayorista / Comercial Matriz.
            </div>
        </div>

        <div class="mt-2 mt-md-0">
            <a href="{{ route('reporte.logistica-semanal.exportar', request()->query()) }}"
                class="btn btn-success btn-sm mr-1">
                <i class="fas fa-file-excel mr-1"></i>Exportar Excel
            </a>

            <a href="{{ route('dashboard.ot-logistica', ['fecha_desde' => $fechaDesde, 'fecha_hasta' => $fechaHasta]) }}"
                class="btn btn-outline-primary btn-sm">
                <i class="fas fa-truck-loading mr-1"></i>Dashboard Logística
            </a>
        </div>
    </div>

    <div class="card rs-card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('reporte.logistica-semanal') }}">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-md-4 mb-2">
                        <label class="small font-weight-bold">Fecha desde</label>
                        <input type="date" name="fecha_desde" class="form-control"
                            value="{{ $fechaDesde }}" required>
                    </div>

                    <div class="col-lg-3 col-md-4 mb-2">
                        <label class="small font-weight-bold">Fecha hasta</label>
                        <input type="date" name="fecha_hasta" class="form-control"
                            value="{{ $fechaHasta }}" required>
                    </div>

                    <div class="col-lg-4 col-md-8 mb-2">
                        <label class="small font-weight-bold">OT / código / descripción</label>
                        <input type="text" name="busqueda" class="form-control"
                            value="{{ $busqueda }}" placeholder="Opcional">
                    </div>

                    <div class="col-lg-2 col-md-4 mb-2">
                        <div class="d-flex">
                            <a href="{{ route('reporte.logistica-semanal') }}"
                                class="btn btn-light border mr-1" title="Semana actual">
                                <i class="fas fa-eraser"></i>
                            </a>
                            <button class="btn btn-primary flex-fill">
                                <i class="fas fa-search mr-1"></i>Generar
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="rs-summary mt-2">
                <strong>Período por fecha de Logística:</strong>
                {{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }}
                al
                {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}.
                <strong>Plan</strong> conserva Ayala y Modelo Muestra tal como fueron cargados;
                <strong>Real</strong> clasifica como Mayorista cuando la remisión llegó a COMERCIAL MATRIZ.
            </div>
        </div>
    </div>

    @if(!$tablaRemisionesDisponible)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            La tabla de remisiones no está disponible. Las columnas reales quedarán en cero y solo se mostrará el plan de Logística.
        </div>
    @endif

    @if($remisionesSinVinculo > 0)
        <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <i class="fas fa-unlink mr-1"></i>
                Hay <strong>{{ number_format($remisionesSinVinculo, 0, ',', '.') }} líneas</strong>
                de remisión del período todavía sin vínculo logístico
                ({{ number_format($cantidadSinVinculo, 0, ',', '.') }} unidades).
                Esas unidades no pueden clasificarse todavía como Local o Mayorista dentro de este reporte.
            </div>

            <button type="button"
                class="btn btn-warning btn-sm mt-2 mt-md-0"
                data-toggle="collapse"
                data-target="#detalleSinVinculo"
                aria-expanded="false">
                <i class="fas fa-eye mr-1"></i>Ver líneas sin vínculo
            </button>
        </div>

        <div class="collapse mb-3" id="detalleSinVinculo">
            <div class="card rs-card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <strong><i class="fas fa-unlink text-warning mr-1"></i>Detalle de remisiones sin vínculo</strong>
                        <div class="rs-subtitle">
                            Si aparece <strong>SIN OT</strong>, todavía no se identificó la OT.
                            Si aparece <strong>SIN ASIGNACIÓN LOGÍSTICA</strong>, la OT ya está identificada pero falta relacionarla con una distribución/local concreto.
                        </div>
                    </div>
                    <span class="badge badge-warning">
                        {{ number_format($remisionesSinVinculo, 0, ',', '.') }} líneas ·
                        {{ number_format($cantidadSinVinculo, 0, ',', '.') }} unidades
                    </span>
                </div>

                @if($sinVinculoPorDestino->isNotEmpty())
                    <div class="card-body border-bottom py-2">
                        <div class="row">
                            @foreach($sinVinculoPorDestino as $destino)
                                <div class="col-xl-3 col-md-4 col-6 mb-2">
                                    <div class="border rounded p-2 h-100">
                                        <small class="text-muted d-block">{{ $destino->destino }}</small>
                                        <strong>{{ number_format($destino->cantidad, 0, ',', '.') }} unidades</strong>
                                        <div class="small text-muted">
                                            {{ number_format($destino->lineas, 0, ',', '.') }} líneas
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 rs-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Remisión</th>
                                <th>OT</th>
                                <th>Código ENVIOS</th>
                                <th>Código base</th>
                                <th>Descripción</th>
                                <th>Destino</th>
                                <th class="text-right">Cantidad</th>
                                <th>Recepción</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($detalleSinVinculo as $linea)
                                <tr>
                                    <td class="rs-nowrap">
                                        @php
                                            $fechaLinea = $linea->fecha_remision ?: $linea->fecha_creacion;
                                        @endphp
                                        {{ $fechaLinea ? \Carbon\Carbon::parse($fechaLinea)->format('d/m/Y') : '—' }}
                                    </td>
                                    <td>
                                        <strong>{{ $linea->serie }}-{{ $linea->numero_remision }}</strong>
                                    </td>
                                    <td>
                                        @if($linea->nro_ot)
                                            <strong>{{ $linea->nro_ot }}</strong>
                                            @if($linea->codigo_ot)
                                                <br><small class="text-muted">{{ $linea->codigo_ot }}</small>
                                            @endif
                                        @else
                                            <span class="text-danger">—</span>
                                        @endif
                                    </td>
                                    <td><span class="rs-code">{{ $linea->codigo }}</span></td>
                                    <td><span class="rs-code">{{ $linea->codigo_base }}</span></td>
                                    <td>{{ \Illuminate\Support\Str::limit($linea->descripcion, 36) }}</td>
                                    <td>
                                        <strong>{{ $linea->destino_real }}</strong>
                                        @if($linea->cod_sucursal_destino)
                                            <br><small class="text-muted">Cod. {{ $linea->cod_sucursal_destino }}</small>
                                        @endif
                                    </td>
                                    <td class="text-right"><strong>{{ number_format($linea->cantidad, 0, ',', '.') }}</strong></td>
                                    <td>
                                        @if($linea->fecha_recepcion)
                                            <span class="badge badge-success">
                                                {{ \Carbon\Carbon::parse($linea->fecha_recepcion)->format('d/m/Y') }}
                                            </span>
                                        @else
                                            <span class="badge badge-primary">EN TRÁNSITO</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($linea->motivo_sin_vinculo === 'SIN OT')
                                            <span class="badge badge-danger">SIN OT</span>
                                        @else
                                            <span class="badge badge-warning">SIN ASIGNACIÓN LOGÍSTICA</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card rs-card rs-kpi h-100"><div class="card-body">
                <div class="label">OTs</div>
                <div class="value">{{ number_format($totales['ots'], 0, ',', '.') }}</div>
                <div class="meta">con movimiento logístico</div>
            </div></div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card rs-card rs-kpi h-100"><div class="card-body">
                <div class="label">Distribución</div>
                <div class="value">{{ number_format($totales['distribucion'], 0, ',', '.') }}</div>
                <div class="meta">plan total</div>
            </div></div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card rs-card rs-kpi h-100"><div class="card-body">
                <div class="label">Envío real locales</div>
                <div class="value">{{ number_format($totales['locales_reales'], 0, ',', '.') }}</div>
                <div class="meta">remisiones a sucursales</div>
            </div></div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card rs-card rs-kpi h-100"><div class="card-body">
                <div class="label">Mayorista / Matriz</div>
                <div class="value">{{ number_format($totales['mayorista_real'], 0, ',', '.') }}</div>
                <div class="meta">destino real Comercial Matriz</div>
            </div></div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card rs-card rs-kpi h-100"><div class="card-body">
                <div class="label">Pendiente remitir</div>
                <div class="value">{{ number_format($totales['pendiente_remitir'], 0, ',', '.') }}</div>
                <div class="meta">
                    @if($totales['exceso_remitido'] > 0)
                        {{ number_format($totales['exceso_remitido'], 0, ',', '.') }} excedido
                    @else
                        sin excedentes
                    @endif
                </div>
            </div></div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card rs-card rs-kpi h-100"><div class="card-body">
                <div class="label">Recibido / tránsito</div>
                <div class="value">{{ number_format($totales['recibido'], 0, ',', '.') }}</div>
                <div class="meta">{{ number_format($totales['en_transito'], 0, ',', '.') }} en tránsito</div>
            </div></div>
        </div>
    </div>

    @if($resumenTipos->isNotEmpty())
        <div class="card rs-card mb-3">
            <div class="card-body py-3">
                <div class="row">
                    @foreach($resumenTipos as $tipo)
                        <div class="col-xl-3 col-md-6 mb-2 mb-xl-0">
                            <div class="border rounded p-2 h-100">
                                <small class="text-muted d-block">{{ $tipo->tipo }}</small>
                                <strong>{{ number_format($tipo->cantidad, 0, ',', '.') }} prendas</strong>
                                <div class="small text-muted">
                                    {{ number_format($tipo->movimientos, 0, ',', '.') }} movimientos ·
                                    {{ number_format($tipo->ots, 0, ',', '.') }} OTs
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="card rs-card mb-3">
        <div class="card-header bg-white">
            <strong><i class="fas fa-table mr-1"></i>Detalle del período</strong>
            <div class="rs-subtitle">
                Similar al reporte histórico, pero separando el plan de Logística del destino real de las remisiones.
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 rs-table">
                <thead>
                    <tr>
                        <th rowspan="2">Fecha</th>
                        <th rowspan="2">Tipo movimiento</th>
                        <th rowspan="2">OT</th>
                        <th rowspan="2">Código / artículo</th>
                        <th rowspan="2" class="text-right">PT ref.</th>
                        <th rowspan="2" class="text-right">Movimiento</th>
                        <th rowspan="2" class="text-right">Acum. OT</th>
                        <th rowspan="2" class="text-right">Pend. OT</th>
                        <th colspan="3" class="text-center rs-plan">Plan del movimiento</th>
                        <th colspan="5" class="text-center rs-real">Movimiento real</th>
                        <th rowspan="2">Estado</th>
                    </tr>
                    <tr>
                        <th class="text-right rs-plan">Locales</th>
                        <th class="text-right rs-plan">Ayala</th>
                        <th class="text-right rs-plan">Modelo/Muestra</th>
                        <th class="text-right rs-real">Locales</th>
                        <th class="text-right rs-real">Mayorista</th>
                        <th class="text-right rs-real">Remitido</th>
                        <th class="text-right rs-real">Pend. remitir</th>
                        <th class="text-right rs-real">Recibido</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($detalles as $item)
                        <tr>
                            <td class="rs-nowrap">
                                {{ $item->fecha_logistica ? \Carbon\Carbon::parse($item->fecha_logistica)->format('d/m/Y') : '—' }}
                            </td>
                            <td>
                                @if($item->tipo_movimiento === 'CANCELACION / CIERRE')
                                    <span class="badge badge-info">CANCELACIÓN / CIERRE</span>
                                @elseif($item->tipo_movimiento === 'COMPLEMENTO')
                                    <span class="badge badge-primary">COMPLEMENTO</span>
                                @elseif($item->tipo_movimiento === 'AJUSTE / EXCEDENTE')
                                    <span class="badge badge-danger">AJUSTE / EXCEDENTE</span>
                                @else
                                    <span class="badge badge-secondary">DISTRIBUCIÓN</span>
                                @endif
                            </td>
                            <td><strong>{{ $item->nro_ot }}</strong></td>
                            <td>
                                <span class="rs-code">{{ $item->codigo }}</span>
                                <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($item->descripcion, 38) }}</small>
                            </td>
                            <td class="text-right">
                                @if($item->tipo_movimiento === 'DISTRIBUCION')
                                    {{ number_format($item->cantidad_pt, 0, ',', '.') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-right"><strong>{{ number_format($item->cantidad_movimiento, 0, ',', '.') }}</strong></td>
                            <td class="text-right">{{ number_format($item->acumulado_ot, 0, ',', '.') }}</td>
                            <td class="text-right">
                                @if($item->pendiente_ot === null)
                                    —
                                @elseif($item->pendiente_ot > 0)
                                    <span class="badge badge-warning">{{ number_format($item->pendiente_ot, 0, ',', '.') }}</span>
                                @else
                                    <span class="badge badge-success">0</span>
                                @endif
                            </td>
                            <td class="text-right rs-plan">{{ number_format($item->plan_locales, 0, ',', '.') }}</td>
                            <td class="text-right rs-plan">{{ number_format($item->plan_ayala, 0, ',', '.') }}</td>
                            <td class="text-right rs-plan">{{ number_format($item->plan_modelo_muestra, 0, ',', '.') }}</td>
                            <td class="text-right rs-real">{{ number_format($item->locales_reales, 0, ',', '.') }}</td>
                            <td class="text-right rs-real">
                                @if($item->mayorista_real > 0)
                                    <strong>{{ number_format($item->mayorista_real, 0, ',', '.') }}</strong>
                                @else
                                    0
                                @endif
                            </td>
                            <td class="text-right rs-real">{{ number_format($item->remitido, 0, ',', '.') }}</td>
                            <td class="text-right rs-real">
                                @if($item->pendiente_remitir > 0)
                                    <span class="badge badge-warning">{{ number_format($item->pendiente_remitir, 0, ',', '.') }}</span>
                                @elseif($item->exceso_remitido > 0)
                                    <span class="badge badge-danger">+{{ number_format($item->exceso_remitido, 0, ',', '.') }}</span>
                                @else
                                    0
                                @endif
                            </td>
                            <td class="text-right rs-real">{{ number_format($item->recibido, 0, ',', '.') }}</td>
                            <td>
                                @if($item->estado === 'COMPLETO')
                                    <span class="badge badge-success">COMPLETO</span>
                                @elseif($item->estado === 'PENDIENTE')
                                    <span class="badge badge-warning">PENDIENTE</span>
                                @elseif($item->estado === 'EXCEDENTE')
                                    <span class="badge badge-danger">EXCEDENTE</span>
                                @else
                                    <span class="badge badge-secondary">SIN REMISIÓN</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="17" class="text-center text-muted py-5">
                                No existen movimientos logísticos para el período seleccionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                @if($detalles->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="5"><strong>TOTAL MOVIMIENTOS DEL PERÍODO</strong></td>
                            <td class="text-right"><strong>{{ number_format($totales['distribucion'], 0, ',', '.') }}</strong></td>
                            <td colspan="2"></td>
                            <td class="text-right"><strong>{{ number_format($totales['plan_locales'], 0, ',', '.') }}</strong></td>
                            <td class="text-right"><strong>{{ number_format($totales['plan_ayala'], 0, ',', '.') }}</strong></td>
                            <td class="text-right"><strong>{{ number_format($totales['plan_modelo_muestra'], 0, ',', '.') }}</strong></td>
                            <td class="text-right"><strong>{{ number_format($totales['locales_reales'], 0, ',', '.') }}</strong></td>
                            <td class="text-right"><strong>{{ number_format($totales['mayorista_real'], 0, ',', '.') }}</strong></td>
                            <td class="text-right"><strong>{{ number_format($totales['remitido'], 0, ',', '.') }}</strong></td>
                            <td class="text-right"><strong>{{ number_format($totales['pendiente_remitir'], 0, ',', '.') }}</strong></td>
                            <td class="text-right"><strong>{{ number_format($totales['recibido'], 0, ',', '.') }}</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 mb-3">
            <div class="card rs-card h-100">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-calendar-day mr-1"></i>Resumen por día</strong>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 rs-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th class="text-right">OTs</th>
                                <th class="text-right">Distribución</th>
                                <th class="text-right">Locales</th>
                                <th class="text-right">Mayorista</th>
                                <th class="text-right">Pendiente</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($porDia as $dia)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($dia->fecha)->format('d/m/Y') }}</td>
                                    <td class="text-right">{{ number_format($dia->ots, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($dia->distribucion, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($dia->locales_reales, 0, ',', '.') }}</td>
                                    <td class="text-right"><strong>{{ number_format($dia->mayorista_real, 0, ',', '.') }}</strong></td>
                                    <td class="text-right">{{ number_format($dia->pendiente_remitir, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Sin actividad.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mb-3">
            <div class="card rs-card h-100">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-map-marker-alt mr-1"></i>Destinos reales</strong>
                    <div class="rs-subtitle">Según las remisiones efectivamente importadas.</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 rs-table">
                        <thead>
                            <tr>
                                <th>Destino</th>
                                <th class="text-right">OTs</th>
                                <th class="text-right">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($porDestinoReal as $destino)
                                <tr>
                                    <td>
                                        <strong>{{ $destino->destino }}</strong>
                                        @if(stripos($destino->destino, 'MATRIZ') !== false)
                                            <span class="badge badge-info ml-1">MAYORISTA</span>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ number_format($destino->ots, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($destino->cantidad, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">Sin remisiones vinculadas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection