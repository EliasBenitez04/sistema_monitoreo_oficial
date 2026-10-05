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
                    <div class="col-md-3 mb-2">
                        <label class="small font-weight-bold">PT desde</label>
                        <input type="date" name="fecha_desde" value="{{ $fechaDesde }}" class="form-control">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="small font-weight-bold">PT hasta</label>
                        <input type="date" name="fecha_hasta" value="{{ $fechaHasta }}" class="form-control">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="small font-weight-bold">OT / código / descripción</label>
                        <input type="text" name="buscar" value="{{ $buscar }}" class="form-control">
                    </div>
                    <div class="col-md-2 mb-2">
                        <button class="btn btn-primary btn-block">
                            <i class="fas fa-search mr-1"></i>Consultar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="cc-rule mb-3">
        <strong>Lectura:</strong>
        <strong>Falta Terminación</strong> = Objetivo − PT ·
        <strong>Sin destino</strong> = PT sin plan/remisión suficiente ·
        <strong>Pendiente remitir</strong> = Plan disponible − Remitido ·
        <strong>En tránsito</strong> = Remitido − Recibido.
    </div>

    <div class="row mb-2">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="cc-kpi"><div class="label">Producto Terminado</div><div class="value">{{ number_format($resumen->producto_terminado,0,',','.') }}</div><div class="meta">de {{ number_format($resumen->objetivo,0,',','.') }} prendas objetivo</div></div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="cc-kpi"><div class="label">Plan logístico</div><div class="value">{{ number_format($resumen->planificado,0,',','.') }}</div><div class="meta">distribución planificada</div></div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="cc-kpi"><div class="label">Remitido real</div><div class="value">{{ number_format($resumen->remitido,0,',','.') }}</div><div class="meta">Casa Central/Matriz → destino</div></div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="cc-kpi"><div class="label">Recibido</div><div class="value">{{ number_format($resumen->recibido,0,',','.') }}</div><div class="meta">confirmado por fecha_recepcion</div></div>
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
            <div class="cc-kpi"><div class="label text-info">Pendiente remitir</div><div class="value">{{ number_format($resumen->pendiente_remitir,0,',','.') }}</div><div class="meta">plan aún sin salida</div></div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="cc-kpi"><div class="label text-primary">En tránsito</div><div class="value">{{ number_format($resumen->en_transito,0,',','.') }}</div><div class="meta">remitido aún no recibido</div></div>
        </div>
    </div>

    @if($resumen->hueco_plan_vs_real > 0)
        <div class="alert alert-info py-2">
            <i class="fas fa-info-circle mr-1"></i>
            {{ number_format($resumen->hueco_plan_vs_real,0,',','.') }} prendas ya tienen remisión real por encima de lo explicado por el plan.
            No se cuentan como faltante físico.
        </div>
    @endif

    <div class="card cc-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <strong><i class="fas fa-clipboard-list mr-1"></i>OT que requieren atención</strong>
                <div class="cc-sub">
                    Período PT {{ CarbonCarbon::parse($fechaDesde)->format('d/m/Y') }}
                    al {{ CarbonCarbon::parse($fechaHasta)->format('d/m/Y') }}
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
                        <th class="text-right">Objetivo</th>
                        <th class="text-right">PT</th>
                        <th class="text-right">Plan</th>
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
                        <td class="text-right"><strong>{{ number_format($item->producto_terminado,0,',','.') }}</strong></td>
                        <td class="text-right">{{ number_format($item->planificado,0,',','.') }}</td>
                        <td class="text-right">{{ number_format($item->remitido_original,0,',','.') }}</td>
                        <td class="text-right">{{ number_format($item->recibido_original,0,',','.') }}</td>
                        <td class="text-right text-danger">{{ number_format($item->falta_terminacion,0,',','.') }}</td>
                        <td class="text-right text-warning">{{ number_format($item->sin_destino,0,',','.') }}</td>
                        <td class="text-right text-info">{{ number_format($item->pendiente_remitir,0,',','.') }}</td>
                        <td class="text-right text-primary">{{ number_format($item->en_transito,0,',','.') }}</td>
                        <td><span class="cc-badge {{ $estadoClase }}">{{ $item->estado_conciliacion }}</span></td>
                        <td><span class="cc-action">{{ $item->solicitud }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="text-center py-5 text-success">
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
