@extends('layouts.app')

@section('content')
<style>
    .rp-title{font-weight:900;color:#1f2937}
    .rp-sub{color:#718096;font-size:13px}
    .rp-card{border:1px solid #e7ebf0;border-radius:14px;box-shadow:0 6px 20px rgba(15,23,42,.05)}
    .rp-kpi{background:#fff;border:1px solid #e7ebf0;border-radius:14px;padding:18px;box-shadow:0 5px 16px rgba(15,23,42,.04);height:100%;position:relative;overflow:hidden}
    .rp-kpi:before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:#64748b}
    .rp-kpi.is-danger:before{background:#dc2626}.rp-kpi.is-warning:before{background:#f59e0b}.rp-kpi.is-primary:before{background:#2563eb}.rp-kpi.is-success:before{background:#16a34a}
    .rp-kpi .label{font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;font-weight:900}
    .rp-kpi .value{font-size:29px;line-height:1;font-weight:900;color:#0f172a;margin:8px 0 5px}
    .rp-kpi .meta{font-size:11px;color:#718096}
    .rp-table th{font-size:10px;text-transform:uppercase;color:#59697a;white-space:nowrap;background:#f8fafc;vertical-align:middle!important}
    .rp-table td{font-size:12px;vertical-align:middle!important}
    .rp-cancel{font-size:17px;font-weight:900;color:#b91c1c}
    .rp-logistic{font-weight:800;color:#b45309}
    .rp-request{display:inline-block;background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;border-radius:8px;padding:6px 9px;font-size:11px;font-weight:900;white-space:nowrap}
    .rp-no-cancel{display:inline-block;background:#eff6ff;color:#1d4ed8;border:1px solid #dbeafe;border-radius:8px;padding:5px 8px;font-size:10px;font-weight:850}
    .rp-explain{background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:14px 16px;color:#9a3412;font-size:12px}
    @media print{
        .no-print,.no-cancel-print{display:none!important}
        .rp-card,.rp-kpi{box-shadow:none}
        .container-fluid{padding:0!important}
        body{background:#fff!important}
    }
</style>

<div class="container-fluid pb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h2 class="rp-title mb-1">
                <i class="fas fa-clipboard-check text-danger mr-2"></i>
                Reporte de Pendientes de Envío y Cancelación
            </h2>
            <div class="rp-sub">
                El rango selecciona las OTs por fecha de Producto Terminado. El faltante de cancelación se calcula contra el PT acumulado completo de cada OT.
            </div>
        </div>
        <div class="no-print mt-2">
            <a href="{{ route('control.terminacion.reporte-pendientes-envio.excel', request()->query()) }}"
               class="btn btn-success btn-sm mr-1">
                <i class="fas fa-file-excel mr-1"></i> Excel cancelación
            </a>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm mr-1">
                <i class="fas fa-print mr-1"></i> Imprimir / PDF
            </button>
            <a href="{{ route('control.terminacion', ['fecha_desde'=>$fechaDesde,'fecha_hasta'=>$fechaHasta]) }}"
               class="btn btn-primary btn-sm">Volver</a>
        </div>
    </div>

    <div class="card rp-card mb-3 no-print">
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

    <div class="rp-explain mb-3">
        <strong><i class="fas fa-info-circle mr-1"></i>Regla:</strong>
        <strong>Pendiente cancelar</strong> = cantidad ordenada − Producto Terminado acumulado.
        Una prenda que ya está en PT pero todavía no tiene destino o remisión se muestra aparte como
        <strong>pendiente logístico y NO se cancela</strong>.
    </div>

    <div class="row mb-3">
        <div class="col-lg-3 col-6 mb-3">
            <div class="rp-kpi is-primary">
                <div class="label">OT revisadas</div>
                <div class="value">{{ number_format($resumen->ots_periodo,0,',','.') }}</div>
                <div class="meta">Con Producto Terminado dentro del rango</div>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-3">
            <div class="rp-kpi is-danger">
                <div class="label">OT para cancelar</div>
                <div class="value">{{ number_format($resumen->ots_cancelar,0,',','.') }}</div>
                <div class="meta">Tienen faltante real contra la cantidad ordenada</div>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-3">
            <div class="rp-kpi is-danger">
                <div class="label">Prendas a cancelar</div>
                <div class="value">{{ number_format($resumen->prendas_cancelar,0,',','.') }}</div>
                <div class="meta">Cantidad para solicitar cierre a Terminación</div>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-3">
            <div class="rp-kpi is-warning">
                <div class="label">Pendiente remitir</div>
                <div class="value">{{ number_format($resumen->pendiente_remitir,0,',','.') }}</div>
                <div class="meta">Existe físicamente; no corresponde cancelar</div>
            </div>
        </div>
    </div>

    <div class="card rp-card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <strong class="text-danger">
                    <i class="fas fa-times-circle mr-1"></i>Solicitudes de cancelación para Terminación
                </strong>
                <div class="rp-sub">
                    Período PT: {{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }}
                    al {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}
                </div>
            </div>
            <span class="badge badge-danger p-2">{{ $reporteCancelar->count() }} OTs · {{ number_format($resumen->prendas_cancelar,0,',','.') }} prendas</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 rp-table">
                <thead>
                    <tr>
                        <th>OT</th>
                        <th>Código / Descripción</th>
                        <th>Último PT</th>
                        <th class="text-right">Orden</th>
                        <th class="text-right">PT acumulado</th>
                        <th class="text-right">Falta cancelar</th>
                        <th class="text-right">Plan logística</th>
                        <th class="text-right">Remitido</th>
                        <th class="text-right">Pend. remitir</th>
                        <th>Solicitud</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($reporteCancelar as $item)
                    <tr>
                        <td><strong>{{ $item->nro_ot }}</strong></td>
                        <td>
                            <strong>{{ $item->codigo }}</strong><br>
                            <small class="text-muted">{{ $item->descripcion }}</small>
                        </td>
                        <td>{{ $item->ultima_fecha_pt ? \Carbon\Carbon::parse($item->ultima_fecha_pt)->format('d/m/Y') : '—' }}</td>
                        <td class="text-right"><strong>{{ number_format($item->cantidad_orden,0,',','.') }}</strong></td>
                        <td class="text-right">{{ number_format($item->cantidad_pt_efectiva,0,',','.') }}</td>
                        <td class="text-right"><span class="rp-cancel">{{ number_format($item->pendiente_cancelar,0,',','.') }}</span></td>
                        <td class="text-right">
                            {{ number_format($item->plan_logistica,0,',','.') }}
                            @if($item->destinos>0)<br><small class="text-muted">{{ $item->destinos }} destinos</small>@endif
                        </td>
                        <td class="text-right">{{ number_format($item->remitido,0,',','.') }}</td>
                        <td class="text-right">
                            @if($item->pendiente_remitir>0)
                                <span class="rp-logistic">{{ number_format($item->pendiente_remitir,0,',','.') }}</span>
                            @else
                                0
                            @endif
                        </td>
                        <td><span class="rp-request">{{ $item->accion }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-success">
                            <i class="fas fa-check-circle mr-1"></i>
                            No hay faltantes para cancelar en las OTs seleccionadas.
                        </td>
                    </tr>
                @endforelse
                </tbody>
                @if($reporteCancelar->isNotEmpty())
                <tfoot>
                    <tr class="font-weight-bold bg-light">
                        <td colspan="3">TOTAL PARA CANCELAR</td>
                        <td class="text-right">{{ number_format($reporteCancelar->sum('cantidad_orden'),0,',','.') }}</td>
                        <td class="text-right">{{ number_format($reporteCancelar->sum('cantidad_pt_efectiva'),0,',','.') }}</td>
                        <td class="text-right text-danger">{{ number_format($reporteCancelar->sum('pendiente_cancelar'),0,',','.') }}</td>
                        <td class="text-right">{{ number_format($reporteCancelar->sum('plan_logistica'),0,',','.') }}</td>
                        <td class="text-right">{{ number_format($reporteCancelar->sum('remitido'),0,',','.') }}</td>
                        <td class="text-right text-warning">{{ number_format($reporteCancelar->sum('pendiente_remitir'),0,',','.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="card rp-card no-cancel-print">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <strong class="text-primary">
                    <i class="fas fa-truck-loading mr-1"></i>Pendientes logísticos — NO CANCELAR
                </strong>
                <div class="rp-sub">Estas prendas sí existen en Producto Terminado; solo falta destino, distribución o remisión.</div>
            </div>
            <span class="badge badge-primary p-2">{{ $reporteLogistica->count() }} OTs</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 rp-table">
                <thead>
                    <tr>
                        <th>OT</th>
                        <th>Código / Descripción</th>
                        <th class="text-right">Orden</th>
                        <th class="text-right">PT</th>
                        <th class="text-right">Plan</th>
                        <th class="text-right">Pend. distribuir</th>
                        <th class="text-right">Remitido</th>
                        <th class="text-right">Pend. remitir</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($reporteLogistica as $item)
                    <tr>
                        <td><strong>{{ $item->nro_ot }}</strong></td>
                        <td><strong>{{ $item->codigo }}</strong><br><small class="text-muted">{{ $item->descripcion }}</small></td>
                        <td class="text-right">{{ number_format($item->cantidad_orden,0,',','.') }}</td>
                        <td class="text-right"><strong>{{ number_format($item->cantidad_pt_efectiva,0,',','.') }}</strong></td>
                        <td class="text-right">{{ number_format($item->plan_logistica,0,',','.') }}</td>
                        <td class="text-right">{{ number_format($item->pendiente_distribuir,0,',','.') }}</td>
                        <td class="text-right">{{ number_format($item->remitido,0,',','.') }}</td>
                        <td class="text-right"><span class="rp-logistic">{{ number_format($item->pendiente_remitir,0,',','.') }}</span></td>
                        <td><span class="rp-no-cancel">{{ $item->accion }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted">No hay pendientes exclusivamente logísticos en el período.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
