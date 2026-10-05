@extends('layouts.app')

@section('content')
<style>
    .fd-title{font-weight:900;color:#1f2937}
    .fd-sub{color:#718096;font-size:13px}
    .fd-card{border:1px solid #e7ebf0;border-radius:14px;box-shadow:0 6px 20px rgba(15,23,42,.05)}
    .fd-kpi{background:#fff;border:1px solid #e7ebf0;border-radius:14px;padding:18px;box-shadow:0 5px 16px rgba(15,23,42,.04);height:100%;position:relative;overflow:hidden}
    .fd-kpi:before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:#64748b}
    .fd-kpi.is-warning:before{background:#f59e0b}.fd-kpi.is-danger:before{background:#dc2626}.fd-kpi.is-primary:before{background:#2563eb}
    .fd-kpi .label{font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;font-weight:900}
    .fd-kpi .value{font-size:29px;line-height:1;font-weight:900;color:#0f172a;margin:8px 0 5px}
    .fd-kpi .meta{font-size:11px;color:#718096}
    .fd-rule{background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:14px 16px;color:#9a3412;font-size:12px}
    .fd-table th{font-size:10px;text-transform:uppercase;color:#59697a;white-space:nowrap;background:#f8fafc;vertical-align:middle!important}
    .fd-table td{font-size:12px;vertical-align:middle!important}
    .fd-missing{font-size:18px;font-weight:900;color:#b91c1c}
    .fd-request{display:inline-block;background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;border-radius:8px;padding:6px 9px;font-size:11px;font-weight:900;white-space:nowrap}
    .fd-code{font-weight:800;color:#0f172a}
    @media print{
        .no-print{display:none!important}
        .fd-card,.fd-kpi{box-shadow:none!important}
        .container-fluid{padding:0!important}
        body{background:#fff!important}
    }
</style>

<div class="container-fluid pb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h2 class="fd-title mb-1">
                <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
                Faltantes para Completar Destino
            </h2>
            <div class="fd-sub">
                Muestra únicamente las prendas que todavía no tienen destino asignado dentro de la OT.
            </div>
        </div>

        <div class="no-print mt-2">
            <a href="{{ route('control.terminacion.reporte-pendientes-envio.excel', request()->query()) }}"
               class="btn btn-success btn-sm mr-1">
                <i class="fas fa-file-excel mr-1"></i> Excel
            </a>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm mr-1">
                <i class="fas fa-print mr-1"></i> Imprimir / PDF
            </button>
            <a href="{{ route('control.terminacion', ['fecha_desde'=>$fechaDesde,'fecha_hasta'=>$fechaHasta]) }}"
               class="btn btn-primary btn-sm">Volver</a>
        </div>
    </div>

    <div class="card fd-card mb-3 no-print">
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

    <div class="fd-rule mb-3">
        <strong><i class="fas fa-calculator mr-1"></i>Regla:</strong>
        <strong>Faltante real = Producto Terminado − mayor evidencia de asignación.</strong>
        Se considera tanto el detalle logístico como la remisión original Casa Central/Matriz → destino.
        Una remisión válida evita marcar como faltante una prenda que ya salió físicamente.
    </div>

    <div class="row mb-3">
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="fd-kpi is-primary">
                <div class="label">OT revisadas</div>
                <div class="value">{{ number_format($resumen->ots_periodo,0,',','.') }}</div>
                <div class="meta">OT con Producto Terminado dentro del rango</div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-3">
            <div class="fd-kpi is-warning">
                <div class="label">OT con faltante</div>
                <div class="value">{{ number_format($resumen->ots_pendientes,0,',','.') }}</div>
                <div class="meta">Tienen PT sin destino ni remisión original</div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-3">
            <div class="fd-kpi is-danger">
                <div class="label">Prendas a solicitar</div>
                <div class="value">{{ number_format($resumen->prendas_pendientes,0,',','.') }}</div>
                <div class="meta">Cantidad total que falta completar</div>
            </div>
        </div>
    </div>

    <div class="card fd-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <strong class="text-warning">
                    <i class="fas fa-clipboard-list mr-1"></i>Prendas faltantes para solicitar a Terminación
                </strong>
                <div class="fd-sub">
                    Período PT:
                    {{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }}
                    al
                    {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}
                </div>
            </div>

            <span class="badge badge-warning p-2">
                {{ $reportePendiente->count() }} OTs ·
                {{ number_format($resumen->prendas_pendientes,0,',','.') }} prendas
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 fd-table">
                <thead>
                    <tr>
                        <th>OT</th>
                        <th>Código / Descripción</th>
                        <th class="text-right">Producto Terminado</th>
                        <th class="text-right">Asignado efectivo</th>
                        <th class="text-right">Detalle logístico</th>
                        <th class="text-right">Falta real</th>
                        <th>Último PT</th>
                        <th>Última asignación</th>
                        <th>Solicitud</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($reportePendiente as $item)
                    <tr>
                        <td><strong>{{ $item->nro_ot }}</strong></td>

                        <td>
                            <span class="fd-code">{{ $item->codigo }}</span><br>
                            <small class="text-muted">{{ $item->descripcion }}</small>
                        </td>

                        <td class="text-right">
                            <strong>{{ number_format($item->producto_terminado,0,',','.') }}</strong>
                        </td>

                        <td class="text-right">
                            <strong>{{ number_format($item->asignado_efectivo,0,',','.') }}</strong>
                            @if($item->remitido_original > $item->detalle_logistico)
                                <br><small class="text-success">
                                    remisión original {{ number_format($item->remitido_original,0,',','.') }}
                                </small>
                            @endif
                        </td>

                        <td class="text-right">
                            {{ number_format($item->detalle_logistico,0,',','.') }}
                            @if($item->destinos > 0)
                                <br><small class="text-muted">{{ $item->destinos }} destinos</small>
                            @endif
                            @if($item->hueco_detalle > 0)
                                <br><small class="text-warning font-weight-bold">
                                    {{ number_format($item->hueco_detalle,0,',','.') }} sin vínculo en detalle
                                </small>
                            @endif
                        </td>

                        <td class="text-right">
                            <span class="fd-missing">
                                {{ number_format($item->faltante_destino,0,',','.') }}
                            </span>
                        </td>

                        <td>
                            {{ $item->ultima_fecha_pt
                                ? \Carbon\Carbon::parse($item->ultima_fecha_pt)->format('d/m/Y')
                                : '—' }}
                        </td>

                        <td>
                            {{ $item->ultima_fecha_logistica
                                ? \Carbon\Carbon::parse($item->ultima_fecha_logistica)->format('d/m/Y')
                                : '—' }}
                        </td>

                        <td>
                            <span class="fd-request">{{ $item->solicitud }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-success">
                            <i class="fas fa-check-circle mr-1"></i>
                            Todas las OTs del rango tienen el destino completo.
                        </td>
                    </tr>
                @endforelse
                </tbody>

                @if($reportePendiente->isNotEmpty())
                    <tfoot>
                        <tr class="font-weight-bold bg-light">
                            <td colspan="2">TOTAL</td>
                            <td class="text-right">
                                {{ number_format($reportePendiente->sum('producto_terminado'),0,',','.') }}
                            </td>
                            <td class="text-right">
                                {{ number_format($reportePendiente->sum('asignado_efectivo'),0,',','.') }}
                            </td>
                            <td class="text-right">
                                {{ number_format($reportePendiente->sum('detalle_logistico'),0,',','.') }}
                            </td>
                            <td class="text-right text-danger">
                                {{ number_format($reportePendiente->sum('faltante_destino'),0,',','.') }}
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
