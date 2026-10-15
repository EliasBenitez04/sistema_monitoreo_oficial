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
        <strong>Pendiente remitir</strong> = cobertura de plan disponible para PT sin remisión ·
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
                <div class="value">{{ number_format($resumen->plan_registrado,0,',','.') }}</div>
                <div class="meta">
                    Suma registrada en ot_logistica_detalle.
                    Aplicable a PT:
                    <strong>{{ number_format($resumen->plan_detallado,0,',','.') }}</strong>;
                    exceso por OT sobre PT:
                    <strong>{{ number_format($resumen->exceso_plan_sobre_pt,0,',','.') }}</strong>.
                    Es un plan, no una remisión.
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="cc-kpi"><div class="label">Salida neta de OT</div><div class="value">{{ number_format($resumen->remitido,0,',','.') }}</div><div class="meta">Remisión central emitida menos devoluciones recibidas en Central, más coberturas locales válidas. No requiere recepción del local destino.</div></div>
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
            <button type="button" class="cc-kpi w-100 text-left" style="cursor:pointer;border:1px solid #ecd9a5;background:#fff;padding:16px" data-toggle="modal" data-target="#modalSinDestinoReporte" aria-label="Ver detalle de prendas sin destino">
                <div class="label text-warning">Sin destino <i class="fas fa-search-plus float-right"></i></div>
                <div class="value">{{ number_format($resumen->sin_destino,0,',','.') }}</div>
                <div class="meta">PT sin asignación suficiente · <span class="text-primary font-weight-bold">Ver OT</span></div>
            </button>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <button type="button" class="cc-kpi w-100 text-left" style="cursor:pointer;border:1px solid #cce5ee;background:#fff;padding:16px" data-toggle="modal" data-target="#modalPendienteRemitirReporte" aria-label="Ver detalle de prendas pendientes de remitir">
                <div class="label text-info">Pendiente remitir <i class="fas fa-search-plus float-right"></i></div>
                <div class="value">{{ number_format($resumen->pendiente_remitir_plan,0,',','.') }}</div>
                <div class="meta">Con destino asignado · {{ number_format($resumen->pendiente_real_salida,0,',','.') }} pendientes de salida en total · <span class="text-primary font-weight-bold">Ver OT</span></div>
            </button>
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
                El Plan asignado registrado ({{ number_format($resumen->plan_registrado,0,',','.') }})
                es una referencia de distribución y se superpone con lo ya remitido.
                Para los saldos operativos se usa únicamente el plan aplicable al PT
                ({{ number_format($resumen->plan_detallado,0,',','.') }}).
                @if($resumen->cubierto_sin_plan_por_remision > 0)
                    {{ number_format($resumen->cubierto_sin_plan_por_remision,0,',','.') }}
                    prendas sin plan suficiente ya demostraron destino mediante una remisión real.
                @endif
            </div>

            <div class="small border-top pt-2 mt-2">
                <strong>Conciliación del plan contra PT:</strong>
                <strong>{{ number_format($resumen->plan_registrado,0,',','.') }}</strong> plan registrado
                − <strong>{{ number_format($resumen->exceso_plan_sobre_pt,0,',','.') }}</strong> exceso de plan por OT
                + <strong>{{ number_format($resumen->cubierto_sin_plan_por_remision,0,',','.') }}</strong> PT con destino demostrado por remisión sin plan suficiente
                + <strong>{{ number_format($resumen->sin_destino,0,',','.') }}</strong> sin destino
                = <strong>{{ number_format($resumen->producto_terminado,0,',','.') }}</strong> PT.
                <span class="text-muted">No corresponde sumar únicamente Plan asignado + Sin destino porque el plan bruto puede incluir excedentes en unas OT y faltantes en otras.</span>
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

    {{-- Cuadre contable: los totales NO son iguales si hay excesos de plan
         o despachos acreditados por documentos fuera de la cobertura. --}}
    @php
        $destinoAcreditado = (int) $resumen->remitido
            + (int) $resumen->pendiente_remitir_plan;
        $diferenciaPlanDestino = (int) $resumen->plan_registrado
            - $destinoAcreditado;
        $auditoriaPlanDestino = $diagnosticoPlan
            ->filter(function ($ot) {
                return (int) $ot->exceso_plan_sobre_pt > 0
                    || (int) $ot->cubierto_sin_plan_por_remision > 0;
            })
            ->values();
    @endphp

    <div class="card cc-card mb-3" id="auditoria-plan-destino">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <strong><i class="fas fa-calculator mr-1"></i>Conciliación: Plan registrado vs. destino acreditado</strong>
                <div class="cc-sub">
                    La salida neta más lo pendiente de remitir no tiene por qué ser igual
                    al plan bruto cuando existen asignaciones excedidas o remisiones sin plan suficiente.
                </div>
            </div>
            <span class="badge {{ $diferenciaPlanDestino === 0 ? 'badge-success' : 'badge-warning' }} p-2">
                {{ $diferenciaPlanDestino === 0 ? 'DIFERENCIA NETA 0' : 'DIFERENCIA NETA ' . number_format(abs($diferenciaPlanDestino),0,',','.') }}
            </span>
        </div>
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-lg-3 col-md-6 mb-2">
                    <div class="border rounded p-2">
                        <small class="text-muted d-block">Plan registrado</small>
                        <strong>{{ number_format($resumen->plan_registrado,0,',','.') }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-2">
                    <div class="border rounded p-2">
                        <small class="text-muted d-block">Exceso de plan sobre PT, por OT</small>
                        <strong class="text-warning">−{{ number_format($resumen->exceso_plan_sobre_pt,0,',','.') }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-2">
                    <div class="border rounded p-2">
                        <small class="text-muted d-block">Remitido sin cobertura suficiente del plan</small>
                        <strong class="text-info">+{{ number_format($resumen->cubierto_sin_plan_por_remision,0,',','.') }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-2">
                    <div class="border rounded p-2">
                        <small class="text-muted d-block">Con destino acreditado</small>
                        <strong>{{ number_format($destinoAcreditado,0,',','.') }}</strong>
                        <small class="text-muted d-block">
                            {{ number_format($resumen->remitido,0,',','.') }} netas +
                            {{ number_format($resumen->pendiente_remitir_plan,0,',','.') }} pendientes
                        </small>
                    </div>
                </div>
            </div>
            <p class="small mb-2">
                <strong>Cuadre del destino:</strong>
                {{ number_format($resumen->plan_registrado,0,',','.') }}
                − {{ number_format($resumen->exceso_plan_sobre_pt,0,',','.') }}
                + {{ number_format($resumen->cubierto_sin_plan_por_remision,0,',','.') }}
                = <strong>{{ number_format($destinoAcreditado,0,',','.') }}</strong>.
                @if($diferenciaPlanDestino !== 0)
                    <span class="text-warning font-weight-bold">
                        Diferencia neta con el plan bruto: {{ number_format(abs($diferenciaPlanDestino),0,',','.') }}
                        {{ $diferenciaPlanDestino > 0 ? 'de más en plan' : 'de más en destino acreditado' }}.
                    </span>
                @endif
                No se modifica el PT ni se agregan prendas para forzar el resultado.
            </p>
            @if($auditoriaPlanDestino->isNotEmpty())
                <button class="btn btn-sm btn-outline-primary no-print mb-2"
                        type="button" data-toggle="collapse"
                        data-target="#detalleAuditoriaPlanDestino"
                        aria-expanded="false" aria-controls="detalleAuditoriaPlanDestino">
                    <i class="fas fa-search-plus mr-1"></i>
                    Ver {{ $auditoriaPlanDestino->count() }} OT que explican el ajuste
                </button>
                <div class="collapse" id="detalleAuditoriaPlanDestino">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover cc-table mb-0">
                            <thead><tr>
                                <th>OT</th>
                                <th>Código / descripción</th>
                                <th class="text-right">PT</th>
                                <th class="text-right">Plan registrado</th>
                                <th class="text-right">Plan aplicable</th>
                                <th class="text-right">Remitido neto</th>
                                <th class="text-right">Exceso plan</th>
                                <th class="text-right">Remitido sin plan</th>
                                <th>Seguimiento</th>
                            </tr></thead>
                            <tbody>
                                @foreach($auditoriaPlanDestino as $itemAuditoria)
                                    <tr>
                                        <td><strong>{{ $itemAuditoria->nro_ot }}</strong></td>
                                        <td>
                                            <span class="cc-code">{{ $itemAuditoria->codigo }}</span><br>
                                            <small class="text-muted">{{ $itemAuditoria->descripcion }}</small>
                                        </td>
                                        <td class="text-right">{{ number_format($itemAuditoria->producto_terminado,0,',','.') }}</td>
                                        <td class="text-right">{{ number_format($itemAuditoria->plan_registrado,0,',','.') }}</td>
                                        <td class="text-right">{{ number_format($itemAuditoria->plan_detallado,0,',','.') }}</td>
                                        <td class="text-right">{{ number_format($itemAuditoria->remitido_original,0,',','.') }}</td>
                                        <td class="text-right text-warning font-weight-bold">
                                            {{ number_format($itemAuditoria->exceso_plan_sobre_pt,0,',','.') }}
                                        </td>
                                        <td class="text-right text-info font-weight-bold">
                                            {{ number_format($itemAuditoria->cubierto_sin_plan_por_remision,0,',','.') }}
                                        </td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary no-print"
                                               href="{{ route('control.terminacion', ['fecha_desde'=>$fechaDesde, 'fecha_hasta'=>$fechaHasta, 'buscar'=>(string) $itemAuditoria->nro_ot]) }}">
                                                Ver OT
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light font-weight-bold"><tr>
                                <td colspan="6">TOTAL</td>
                                <td class="text-right text-warning">{{ number_format($auditoriaPlanDestino->sum('exceso_plan_sobre_pt'),0,',','.') }}</td>
                                <td class="text-right text-info">{{ number_format($auditoriaPlanDestino->sum('cubierto_sin_plan_por_remision'),0,',','.') }}</td>
                                <td></td>
                            </tr></tfoot>
                        </table>
                    </div>
                    <p class="small text-muted mt-2 mb-0">
                        El exceso del plan se comprueba en la importación de Logística.
                        El remitido sin plan se comprueba contra las remisiones originales y devoluciones.
                        No corregir automáticamente ni asignar una OT por código ambiguo.
                    </p>
                </div>
            @endif
        </div>
    </div>

    @if($detalleDevoluciones->isNotEmpty())
        <div class="cc-card card mb-3">
            <div class="card-header bg-white">
                <strong><i class="fas fa-undo text-info mr-1"></i>Devoluciones a Casa Central y reenvíos</strong>
            </div>
            <div class="card-body">
                <div class="row small mb-2">
                    <div class="col-lg-3 mb-2"><strong>Despacho bruto Central:</strong> {{ number_format($resumen->remitido_bruto,0,',','.') }}</div>
                    <div class="col-lg-3 mb-2"><strong>Retornos emitidos:</strong> {{ number_format($resumen->devoluciones_emitidas,0,',','.') }}</div>
                    <div class="col-lg-3 mb-2"><strong>Retornos ingresados a Central:</strong> {{ number_format($resumen->devoluciones_recibidas,0,',','.') }}</div>
                    <div class="col-lg-3 mb-2"><strong>Retornos en tránsito:</strong> {{ number_format($resumen->devoluciones_en_transito,0,',','.') }}</div>
                </div>
                <p class="small text-muted">
                    Remisiones brutas − devoluciones efectivamente recibidas en Casa Central = salidas netas centrales.
                    El resumen operativo puede incluir otras coberturas locales admitidas por la conciliación.
                    Retornos sin recepción no reponen stock y se muestran aparte.
                </p>
                @if($resumen->devoluciones_sin_salida > 0)
                    <div class="alert alert-warning small">
                        {{ number_format($resumen->devoluciones_sin_salida,0,',','.') }}
                        devoluciones recibidas no pudieron descontarse de salidas centrales vinculadas.
                        Revisar la OT asignada al retorno.
                    </div>
                @endif
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-striped cc-table mb-0">
                        <thead><tr>
                            <th>OT</th><th>Código</th><th class="text-right">PT</th>
                            <th class="text-right">Salida bruta</th>
                            <th class="text-right">Dev. recibida</th>
                            <th class="text-right">Dev. en tránsito</th>
                            <th class="text-right">Salida neta</th>
                            <th class="text-right">Saldo teórico Central</th>
                            <th>Detalle</th>
                        </tr></thead>
                        <tbody>
                            @foreach($detalleDevoluciones as $ret)
                                <tr>
                                    <td><strong>{{ $ret->nro_ot }}</strong></td>
                                    <td><code>{{ $ret->codigo }}</code></td>
                                    <td class="text-right">{{ number_format($ret->producto_terminado,0,',','.') }}</td>
                                    <td class="text-right">{{ number_format($ret->remitido_bruto,0,',','.') }}</td>
                                    <td class="text-right">{{ number_format($ret->devoluciones_recibidas,0,',','.') }}</td>
                                    <td class="text-right">{{ number_format($ret->devoluciones_en_transito,0,',','.') }}</td>
                                    <td class="text-right font-weight-bold">{{ number_format($ret->remitido_original,0,',','.') }}</td>
                                    <td class="text-right">{{ number_format($ret->stock_central_disponible,0,',','.') }}</td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('control.terminacion', ['fecha_desde'=>$fechaDesde, 'fecha_hasta'=>$fechaHasta, 'buscar'=>(string) $ret->nro_ot]) }}">
                                            Seguir OT
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <small class="text-muted">El stock de Central es teórico por OT, no un ajuste automático del stock físico por variante. Los detalles del documento, color y talle están en el seguimiento individual.</small>
            </div>
        </div>
    @endif

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
                            Plan registrado: <strong>{{ number_format($resumen->plan_registrado,0,',','.') }}</strong> ·
                            cobertura aplicable al PT: <strong>{{ number_format($resumen->plan_detallado,0,',','.') }}</strong> ·
                            PT: <strong>{{ number_format($resumen->producto_terminado,0,',','.') }}</strong>
                        </div>
                        <div class="small mt-2">
                            Exceso de plan por OT sobre PT:
                            <strong>{{ number_format($resumen->exceso_plan_sobre_pt,0,',','.') }}</strong> ·
                            PT no cubierto por plan:
                            <strong>{{ number_format($resumen->sin_destino_plan,0,',','.') }}</strong>.
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
                                            <th class="text-right">Plan registrado</th>
                                            <th class="text-right">Plan aplicable a PT</th>
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
                                                    {{ number_format($item->plan_registrado,0,',','.') }}
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
                                                <td colspan="9" class="text-center text-success py-3">
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
                        <th class="text-right">Remitido neto</th>
                        <th class="text-right">Recibido neto</th>
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
                            {{ number_format($item->plan_registrado,0,',','.') }}
                            @if($item->plan_registrado !== $item->plan_detallado)
                                <br>
                                <small class="text-muted">
                                    aplicable PT: {{ number_format($item->plan_detallado,0,',','.') }}
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

{{-- Desgloses calculados desde el mismo universo y campos que los KPI. --}}
@foreach([
    ['id' => 'modalSinDestinoReporte', 'titulo' => 'PT sin destino asignado', 'detalle' => $detalleSinDestino, 'campo' => 'sin_destino', 'total' => $resumen->sin_destino],
    ['id' => 'modalPendienteRemitirReporte', 'titulo' => 'Pendiente de remitir con destino', 'detalle' => $detallePendienteRemitir, 'campo' => 'pendiente_remitir_plan', 'total' => $resumen->pendiente_remitir_plan]
] as $desglose)
<div class="modal fade" id="{{ $desglose['id'] }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width:1150px">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title font-weight-bold">{{ $desglose['titulo'] }}</h5>
                    <small class="text-muted">OT con Producto Terminado en el período {{ $fechaDesde }} a {{ $fechaHasta }}; conciliación acumulada de sus movimientos.</small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                @php $totalDesglose = (int) $desglose['detalle']->sum($desglose['campo']); @endphp
                <div class="row mb-3">
                    <div class="col-md-4 mb-2"><div class="border rounded p-3"><small class="text-muted d-block">OT CON PENDIENTE</small><h4 class="font-weight-bold mb-0">{{ number_format($desglose['detalle']->count(),0,',','.') }}</h4></div></div>
                    <div class="col-md-4 mb-2"><div class="border rounded p-3"><small class="text-muted d-block">PRENDAS</small><h4 class="font-weight-bold mb-0">{{ number_format($totalDesglose,0,',','.') }}</h4></div></div>
                    <div class="col-md-4 mb-2"><div class="border rounded p-3"><small class="text-muted d-block">CONCILIACIÓN KPI</small><h4 class="font-weight-bold mb-0 {{ $totalDesglose === (int)$desglose['total'] ? 'text-success' : 'text-danger' }}">{{ $totalDesglose === (int)$desglose['total'] ? 'COINCIDE' : 'REVISAR' }}</h4></div></div>
                </div>
                <div class="table-responsive" style="max-height:60vh;overflow:auto">
                    <table class="table table-sm table-hover table-striped cc-table">
                        <thead class="thead-light"><tr>
                            <th>OT</th><th>Código</th><th>Descripción</th>
                            <th class="text-right">Cantidad OT</th><th class="text-right">PT</th><th class="text-right">Plan asignado</th><th class="text-right">Remitido</th><th class="text-right">Pendiente</th>
                        </tr></thead>
                        <tbody>
                        @forelse($desglose['detalle'] as $filaDetalle)
                        <tr>
                            <td class="font-weight-bold">{{ $filaDetalle->nro_ot }}</td>
                            <td><code>{{ $filaDetalle->codigo }}</code></td>
                            <td>{{ $filaDetalle->descripcion }}</td>
                            <td class="text-right">{{ number_format($filaDetalle->cantidad_orden,0,',','.') }}</td>
                            <td class="text-right">{{ number_format($filaDetalle->producto_terminado,0,',','.') }}</td>
                            <td class="text-right">{{ number_format($filaDetalle->planificado,0,',','.') }}</td>
                            <td class="text-right">{{ number_format($filaDetalle->remitido_original,0,',','.') }}</td>
                            <td class="text-right text-danger font-weight-bold">{{ number_format($filaDetalle->{$desglose['campo']},0,',','.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted py-3">Sin OT para este indicador.</td></tr>
                        @endforelse
                        </tbody>
                        <tfoot class="bg-light font-weight-bold"><tr><td colspan="7">TOTAL</td><td class="text-right text-danger">{{ number_format($totalDesglose,0,',','.') }}</td></tr></tfoot>
                    </table>
                </div>
                <p class="small text-muted mt-2 mb-0">Sin destino: PT todavía sin asignación suficiente. Pendiente remitir: plan asignado aún sin remisión. Ambas listas respetan el mismo filtro y la misma fórmula del reporte.</p>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>
        </div>
    </div>
</div>
@endforeach

@endsection
