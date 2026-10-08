@extends('layouts.app')

@section('content')
<style>
    .cc-title{font-weight:900;color:#1f2937}
    .cc-sub{color:#718096;font-size:13px}
    .cc-card{border:1px solid #e7ebf0;border-radius:14px;box-shadow:0 6px 20px rgba(15,23,42,.05)}
    .cc-kpi{background:#fff;border:1px solid #e7ebf0;border-radius:14px;padding:16px;height:100%;box-shadow:0 5px 16px rgba(15,23,42,.04)}
    .cc-kpi .label{font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;font-weight:900}
    .cc-kpi .value{font-size:28px;line-height:1;font-weight:900;color:#0f172a;margin:8px 0 4px}
    .cc-kpi .meta{font-size:10px;color:#718096}
    .cc-rule{background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:13px 15px;color:#1e40af;font-size:12px}
    .cc-table th{font-size:9px;text-transform:uppercase;color:#59697a;white-space:nowrap;background:#f8fafc;vertical-align:middle!important}
    .cc-table td{font-size:12px;vertical-align:middle!important}
    .cc-code{font-weight:800;color:#0f172a}
    .cc-badge{display:inline-block;border-radius:999px;padding:5px 8px;font-size:9px;font-weight:900;white-space:nowrap}
    .cc-badge.danger{background:#fef2f2;color:#b91c1c}
    .cc-badge.warning{background:#fff7ed;color:#c2410c}
    .cc-badge.info{background:#ecfeff;color:#0e7490}
    .cc-badge.primary{background:#eff6ff;color:#1d4ed8}
    .cc-badge.success{background:#f0fdf4;color:#15803d}
    .cc-action{font-size:10px;font-weight:900;color:#334155;white-space:nowrap}
    .cc-season-box{border:1px solid #ced4da;border-radius:.25rem;background:#fff;padding:7px 10px;min-height:38px;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .cc-season-option{margin:0;display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;color:#475569;white-space:nowrap}
    .cc-season-option input{margin:0}
    .cc-filter-badge{display:inline-block;background:#eef2ff;color:#3730a3;border:1px solid #c7d2fe;border-radius:999px;padding:3px 8px;font-size:10px;font-weight:800;margin-right:4px}
    @media print{
        .no-print{display:none!important}
        .cc-card,.cc-kpi{box-shadow:none!important}
        body{background:#fff!important}
    }
</style>

<div class="container-fluid pb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h2 class="cc-title mb-1">
                <i class="fas fa-balance-scale text-primary mr-2"></i>
                Conciliación de OT
            </h2>
            <div class="cc-sub">
                Producción, plan logístico, remisión real y recepción en una sola vista.
            </div>
        </div>

        <div class="no-print mt-2">
            <a href="{{ route('control.terminacion.reporte-pendientes-envio.excel', request()->query()) }}"
               class="btn btn-success btn-sm mr-1">
                <i class="fas fa-file-excel mr-1"></i>Excel
            </a>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm mr-1">
                <i class="fas fa-print mr-1"></i>Imprimir / PDF
            </button>
            <a href="{{ route('control.terminacion', ['fecha_desde'=>$fechaDesde,'fecha_hasta'=>$fechaHasta]) }}"
               class="btn btn-primary btn-sm">Volver</a>
        </div>
    </div>

    <div class="card cc-card mb-3 no-print">
        <div class="card-body">
            <form method="GET">
                <div class="row align-items-end">
                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="small font-weight-bold">PT desde</label>
                        <input type="date" name="fecha_desde" value="{{ $fechaDesde }}" class="form-control">
                    </div>
                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="small font-weight-bold">PT hasta</label>
                        <input type="date" name="fecha_hasta" value="{{ $fechaHasta }}" class="form-control">
                    </div>
                    <div class="col-lg-3 col-md-4 mb-2">
                        <label class="small font-weight-bold">OT / código / descripción</label>
                        <input type="text" name="buscar" value="{{ $buscar }}" class="form-control">
                    </div>
                    <div class="col-lg-3 col-md-8 mb-2">
                        <label class="small font-weight-bold d-block">
                            Temporada
                            <span class="text-muted font-weight-normal">(podés marcar varias)</span>
                        </label>
                        <div class="cc-season-box">
                            @forelse($temporadasDisponibles as $temporada)
                                <label class="cc-season-option">
                                    <input type="checkbox"
                                           name="temporada[]"
                                           value="{{ $temporada }}"
                                           {{ in_array($temporada, $temporadas, true) ? 'checked' : '' }}>
                                    <span>{{ $temporada }}</span>
                                </label>
                            @empty
                                <span class="text-muted small">Sin temporadas cargadas</span>
                            @endforelse
                        </div>
                        <small class="text-muted">
                            Sin marcar = todas. “AMBOS” se trata como una temporada independiente.
                        </small>
                    </div>
                    <div class="col-lg-2 col-md-4 mb-2">
                        <div class="d-flex">
                            <a href="{{ route('control.terminacion.reporte-pendientes-envio', ['fecha_desde'=>$fechaDesde, 'fecha_hasta'=>$fechaHasta]) }}"
                               class="btn btn-light border mr-1"
                               title="Limpiar filtros">
                                <i class="fas fa-eraser"></i>
                            </a>
                            <button class="btn btn-primary flex-fill">
                                <i class="fas fa-search mr-1"></i>Consultar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="cc-rule mb-3">
        <strong>Lectura:</strong>
        <strong>Falta Terminación</strong> = Ingreso a Terminación − PT ·
        <strong>Sin destino</strong> = PT todavía sin destino asignado ·
        <strong>Pendiente remitir</strong> = Plan asignado − Remitido ·
        <strong>Pendiente salida total</strong> = PT − Remitido ·
        <strong>En tránsito</strong> = Remitido − Recibido.
    </div>

    <div class="row mb-2">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="cc-kpi"><div class="label">Producto Terminado</div><div class="value">{{ number_format($resumen->producto_terminado,0,',','.') }}</div><div class="meta">de {{ number_format($resumen->ingreso_terminacion,0,',','.') }} que ingresaron a Terminación · OT original {{ number_format($resumen->objetivo,0,',','.') }}</div></div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="cc-kpi">
                <div class="label">Plan asignado</div>
                <div class="value">{{ number_format($resumen->plan_detallado,0,',','.') }}</div>
                <div class="meta">
                    de {{ number_format($resumen->planificado,0,',','.') }} de objetivo ·
                    referencia de distribución, no se suma al Remitido
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="cc-kpi"><div class="label">Remitido real</div><div class="value">{{ number_format($resumen->remitido,0,',','.') }}</div><div class="meta">Tiene remisión emitida/importada · no requiere recepción</div></div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="cc-kpi"><div class="label">Recibido</div><div class="value">{{ number_format($resumen->recibido,0,',','.') }}</div><div class="meta">Confirmado únicamente cuando existe fecha_recepcion</div></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="cc-kpi"><div class="label text-danger">Falta Terminación</div><div class="value">{{ number_format($resumen->falta_terminacion,0,',','.') }}</div><div class="meta">producción incompleta</div></div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="cc-kpi"><div class="label text-warning">Sin destino</div><div class="value">{{ number_format($resumen->sin_destino,0,',','.') }}</div><div class="meta">PT sin asignación suficiente</div></div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="cc-kpi">
                <div class="label text-info">Pendiente remitir</div>
                <div class="value">{{ number_format($resumen->pendiente_remitir_plan,0,',','.') }}</div>
                <div class="meta">
                    con destino asignado ·
                    {{ number_format($resumen->pendiente_real_salida,0,',','.') }}
                    pendientes de salida en total
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="cc-kpi"><div class="label text-primary">En tránsito</div><div class="value">{{ number_format($resumen->en_transito,0,',','.') }}</div><div class="meta">Tiene remisión pero todavía no tiene fecha_recepcion</div></div>
        </div>
    </div>

    <div class="card cc-card mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                <strong>Cuadre físico de Producto Terminado</strong>
                @if($resumen->diferencia_cuadre === 0)
                    <span class="badge badge-success p-2">CUADRA 100%</span>
                @else
                    <span class="badge badge-danger p-2">
                        DIFERENCIA {{ number_format($resumen->diferencia_cuadre,0,',','.') }}
                    </span>
                @endif
            </div>

            <div class="cc-rule mb-2">
                <strong>{{ number_format($resumen->remitido,0,',','.') }}</strong> remitidas
                +
                <strong>{{ number_format($resumen->pendiente_remitir_plan,0,',','.') }}</strong> con destino pendientes de remitir
                +
                <strong>{{ number_format($resumen->sin_destino,0,',','.') }}</strong> sin destino
                =
                <strong>{{ number_format($resumen->cuadre_fisico,0,',','.') }}</strong> PT
            </div>

            <div class="small text-muted">
                El Plan asignado ({{ number_format($resumen->plan_detallado,0,',','.') }})
                es una referencia de distribución y se superpone con lo ya remitido.
                @if($resumen->cubierto_sin_plan_por_remision > 0)
                    {{ number_format($resumen->cubierto_sin_plan_por_remision,0,',','.') }}
                    prendas sin plan suficiente ya demostraron destino mediante una remisión real.
                @endif
            </div>

            <div class="small text-muted mt-1">
                Control de recepción:
                <strong>{{ number_format($resumen->recibido,0,',','.') }}</strong> recibidas
                +
                <strong>{{ number_format($resumen->en_transito,0,',','.') }}</strong> en tránsito
                =
                <strong>{{ number_format($resumen->remitido,0,',','.') }}</strong> remitidas.
            </div>
        </div>
    </div>

    @if($resumen->hueco_plan_vs_real > 0)
        <div class="alert alert-info py-2">
            <i class="fas fa-info-circle mr-1"></i>
            {{ number_format($resumen->hueco_plan_vs_real,0,',','.') }} prendas ya tienen remisión real por encima de lo explicado por el plan.
            No se cuentan como faltante físico.
        </div>
    @endif

    <div class="card cc-card mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <strong>
                    <i class="fas fa-search-plus mr-1"></i>
                    Diagnóstico de diferencias
                </strong>
                <div class="cc-sub">
                    Identifica exactamente qué OTs explican las diferencias de Producción y Plan Logístico.
                </div>
            </div>
            <div>
                <span class="badge badge-danger p-2 mr-1">
                    {{ $resumen->ots_diferencia_produccion }} OT producción
                </span>
                <span class="badge badge-warning p-2">
                    {{ $resumen->ots_diferencia_plan }} OT plan
                </span>
            </div>
        </div>

        <div class="card-body">
            <div class="row mb-3">
                <div class="col-lg-6 mb-3 mb-lg-0">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Diferencia Producción</strong>
                            <span class="badge {{ $resumen->diferencia_pt_ingreso === 0 ? 'badge-success' : 'badge-danger' }}">
                                {{ $resumen->diferencia_pt_ingreso > 0 ? '+' : '' }}{{ number_format($resumen->diferencia_pt_ingreso,0,',','.') }}
                            </span>
                        </div>
                        <div class="small text-muted">
                            PT {{ number_format($resumen->producto_terminado,0,',','.') }}
                            − Ingreso Terminación {{ number_format($resumen->ingreso_terminacion,0,',','.') }}
                        </div>
                        <div class="small mt-2">
                            <strong>{{ number_format($resumen->exceso_pt_sobre_ingreso,0,',','.') }}</strong>
                            PT por encima del ingreso ·
                            <strong>{{ number_format($resumen->faltante_pt_vs_ingreso,0,',','.') }}</strong>
                            pendientes respecto al ingreso.
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Diferencia Plan Logístico</strong>
                            <span class="badge {{ $resumen->sin_destino_plan === 0 ? 'badge-success' : 'badge-warning' }}">
                                {{ number_format($resumen->sin_destino_plan,0,',','.') }}
                            </span>
                        </div>
                        <div class="small text-muted">
                            PT no explicado inicialmente por el plan detallado.
                        </div>
                        <div class="small mt-2">
                            <strong>{{ number_format($resumen->cubierto_sin_plan_por_remision,0,',','.') }}</strong>
                            ya demostraron destino mediante remisión ·
                            <strong>{{ number_format($resumen->sin_destino,0,',','.') }}</strong>
                            siguen realmente sin destino.
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion" id="diagnosticoDiferencias">
                <div class="card mb-2">
                    <div class="card-header py-2" id="headingProduccion">
                        <button class="btn btn-link btn-block text-left font-weight-bold p-0"
                                type="button"
                                data-toggle="collapse"
                                data-target="#collapseProduccion"
                                aria-expanded="false"
                                aria-controls="collapseProduccion">
                            <i class="fas fa-industry text-danger mr-1"></i>
                            OTs con diferencia entre Ingreso Terminación y Producto Terminado
                            <span class="badge badge-light border ml-1">
                                {{ $diagnosticoProduccion->count() }}
                            </span>
                        </button>
                    </div>

                    <div id="collapseProduccion"
                         class="collapse"
                         aria-labelledby="headingProduccion"
                         data-parent="#diagnosticoDiferencias">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0 cc-table">
                                    <thead>
                                        <tr>
                                            <th>OT</th>
                                            <th>Código / Descripción</th>
                                            <th class="text-right">Ingreso Term.</th>
                                            <th class="text-right">PT</th>
                                            <th class="text-right">Diferencia</th>
                                            <th>Qué revisar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($diagnosticoProduccion as $item)
                                            <tr>
                                                <td><strong>{{ $item->nro_ot }}</strong></td>
                                                <td>
                                                    <span class="cc-code">{{ $item->codigo }}</span><br>
                                                    <small class="text-muted">{{ $item->descripcion }}</small>
                                                </td>
                                                <td class="text-right">
                                                    {{ number_format($item->ingreso_terminacion,0,',','.') }}
                                                </td>
                                                <td class="text-right">
                                                    <strong>{{ number_format($item->producto_terminado,0,',','.') }}</strong>
                                                </td>
                                                <td class="text-right">
                                                    @if($item->diferencia_pt_ingreso > 0)
                                                        <span class="text-danger font-weight-bold">
                                                            +{{ number_format($item->diferencia_pt_ingreso,0,',','.') }}
                                                        </span>
                                                    @else
                                                        <span class="text-warning font-weight-bold">
                                                            {{ number_format($item->diferencia_pt_ingreso,0,',','.') }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($item->diferencia_pt_ingreso > 0)
                                                        <span class="text-danger">
                                                            PT supera lo registrado en Ingreso Terminación. Revisar trazabilidad/importación.
                                                        </span>
                                                    @else
                                                        <span class="text-warning">
                                                            Falta Producto Terminado respecto a lo ingresado a Terminación.
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-success py-3">
                                                    Sin diferencias entre Ingreso Terminación y PT.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-0">
                    <div class="card-header py-2" id="headingPlan">
                        <button class="btn btn-link btn-block text-left font-weight-bold p-0"
                                type="button"
                                data-toggle="collapse"
                                data-target="#collapsePlan"
                                aria-expanded="false"
                                aria-controls="collapsePlan">
                            <i class="fas fa-route text-warning mr-1"></i>
                            OTs con diferencia entre PT y Plan asignado
                            <span class="badge badge-light border ml-1">
                                {{ $diagnosticoPlan->count() }}
                            </span>
                        </button>
                    </div>

                    <div id="collapsePlan"
                         class="collapse"
                         aria-labelledby="headingPlan"
                         data-parent="#diagnosticoDiferencias">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0 cc-table">
                                    <thead>
                                        <tr>
                                            <th>OT</th>
                                            <th>Código / Descripción</th>
                                            <th class="text-right">PT</th>
                                            <th class="text-right">Plan asignado</th>
                                            <th class="text-right">Hueco plan</th>
                                            <th class="text-right">Cubierto por remisión</th>
                                            <th class="text-right">Sin destino real</th>
                                            <th>Qué revisar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($diagnosticoPlan as $item)
                                            <tr>
                                                <td><strong>{{ $item->nro_ot }}</strong></td>
                                                <td>
                                                    <span class="cc-code">{{ $item->codigo }}</span><br>
                                                    <small class="text-muted">{{ $item->descripcion }}</small>
                                                </td>
                                                <td class="text-right">
                                                    <strong>{{ number_format($item->producto_terminado,0,',','.') }}</strong>
                                                </td>
                                                <td class="text-right">
                                                    {{ number_format($item->plan_detallado,0,',','.') }}
                                                </td>
                                                <td class="text-right text-warning font-weight-bold">
                                                    @if($item->hueco_plan_bruto > 0)
                                                        {{ number_format($item->hueco_plan_bruto,0,',','.') }}
                                                    @elseif($item->exceso_plan_sobre_pt > 0)
                                                        +{{ number_format($item->exceso_plan_sobre_pt,0,',','.') }} exceso plan
                                                    @else
                                                        0
                                                    @endif
                                                </td>
                                                <td class="text-right text-info">
                                                    {{ number_format($item->cubierto_sin_plan_por_remision,0,',','.') }}
                                                </td>
                                                <td class="text-right text-danger font-weight-bold">
                                                    {{ number_format($item->sin_destino,0,',','.') }}
                                                </td>
                                                <td>
                                                    @if($item->sin_destino > 0)
                                                        <span class="text-danger">
                                                            Falta asignar destino para {{ number_format($item->sin_destino,0,',','.') }} prenda(s).
                                                        </span>
                                                    @elseif($item->cubierto_sin_plan_por_remision > 0)
                                                        <span class="text-info">
                                                            El plan quedó corto, pero la remisión real ya demuestra el destino.
                                                        </span>
                                                    @elseif($item->exceso_plan_sobre_pt > 0)
                                                        <span class="text-warning">
                                                            El plan supera el PT. Revisar distribución cargada.
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center text-success py-3">
                                                    Sin diferencias entre PT y Plan asignado.
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
        </div>
    </div>

    <div class="card cc-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <strong><i class="fas fa-clipboard-list mr-1"></i>OT que requieren atención</strong>
                <div class="cc-sub">
                    Período PT {{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }}
                    al {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}
                    @if(!empty($temporadas))
                        <span class="ml-1">· Temporada:</span>
                        @foreach($temporadas as $temporada)
                            <span class="cc-filter-badge">{{ $temporada }}</span>
                        @endforeach
                    @else
                        <span class="ml-1">· Todas las temporadas</span>
                    @endif
                </div>
            </div>
            <span class="badge badge-primary p-2">
                {{ $reportePendiente->count() }} OTs
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 cc-table">
                <thead>
                    <tr>
                        <th>OT</th>
                        <th>Código / Descripción</th>
                        <th class="text-right">OT original</th>
                        <th class="text-right">Ingreso Term.</th>
                        <th class="text-right">PT</th>
                        <th class="text-right">Plan asignado</th>
                        <th class="text-right">Remitido</th>
                        <th class="text-right">Recibido</th>
                        <th class="text-right">Falta Term.</th>
                        <th class="text-right">Sin destino</th>
                        <th class="text-right">Pend. remitir</th>
                        <th class="text-right">Tránsito</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($reportePendiente as $item)
                    @php
                        $estadoClase = [
                            'FALTA TERMINACION' => 'danger',
                            'SIN DESTINO' => 'warning',
                            'PENDIENTE REMITIR' => 'info',
                            'PENDIENTE SALIDA' => 'info',
                            'EN TRANSITO' => 'primary',
                            'CONFIRMADO' => 'success',
                        ][$item->estado_conciliacion] ?? 'secondary';
                    @endphp
                    <tr>
                        <td><strong>{{ $item->nro_ot }}</strong></td>
                        <td><span class="cc-code">{{ $item->codigo }}</span><br><small class="text-muted">{{ $item->descripcion }}</small></td>
                        <td class="text-right">{{ number_format($item->objetivo,0,',','.') }}</td>
                        <td class="text-right">{{ number_format($item->ingreso_terminacion,0,',','.') }}</td>
                        <td class="text-right"><strong>{{ number_format($item->producto_terminado,0,',','.') }}</strong></td>
                        <td class="text-right">
                            {{ number_format($item->plan_detallado,0,',','.') }}
                            @if($item->plan_detallado !== $item->planificado)
                                <br>
                                <small class="text-muted">
                                    objetivo {{ number_format($item->planificado,0,',','.') }}
                                </small>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($item->remitido_original,0,',','.') }}</td>
                        <td class="text-right">{{ number_format($item->recibido_original,0,',','.') }}</td>
                        <td class="text-right text-danger">{{ number_format($item->falta_terminacion,0,',','.') }}</td>
                        <td class="text-right text-warning">{{ number_format($item->sin_destino,0,',','.') }}</td>
                        <td class="text-right text-info">
                            {{ number_format($item->pendiente_remitir_plan,0,',','.') }}
                            @if($item->pendiente_remitir > $item->pendiente_remitir_plan)
                                <br>
                                <small class="text-muted">
                                    total salida {{ number_format($item->pendiente_remitir,0,',','.') }}
                                </small>
                            @endif
                        </td>
                        <td class="text-right text-primary">{{ number_format($item->en_transito,0,',','.') }}</td>
                        <td><span class="cc-badge {{ $estadoClase }}">{{ $item->estado_conciliacion }}</span></td>
                        <td><span class="cc-action">{{ $item->solicitud }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="text-center py-5 text-success">
                            <i class="fas fa-check-circle mr-1"></i>
                            No hay OTs pendientes con los filtros seleccionados.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
