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
            <a href="{{ route('control.flujo-diario', ['fecha_desde'=>$fechaDesde, 'fecha_hasta'=>$fechaHasta, 'buscar'=>$buscar]) }}" class="btn btn-primary btn-sm mr-1">
                <i class="fas fa-project-diagram mr-1"></i>Flujo diario detallado
            </a>
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
                <div class="value">{{ number_format($totalTerminado,0,',','.') }}</div>
                <div class="meta">Volumen consolidado de Terminación</div>
            </div></div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label"><i class="fas fa-check-double text-success mr-1"></i>Producto Terminado</div>
                <div class="value">{{ number_format($totalTerminado,0,',','.') }}</div>
                <div class="meta">100,0% del volumen consolidado · disponible para Logística</div>
            </div></div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card ct-card ct-kpi h-100 {{ $totalFaltaTerminacion>0?'border-danger':'' }}"><div class="card-body">
                <div class="label"><i class="fas fa-industry text-danger mr-1"></i>Falta Terminación</div>
                <div class="value">{{ number_format($totalFaltaTerminacion,0,',','.') }}</div>
                <div class="meta">{{ number_format($otsFaltaTerminacion,0,',','.') }} OTs todavía incompletas de producción</div>
            </div></div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card ct-card ct-kpi h-100 {{ $totalSinDestino>0?'border-warning':'' }}"><div class="card-body">
                <div class="label"><i class="fas fa-map-marker-alt text-warning mr-1"></i>Sin destino</div>
                <div class="value">{{ number_format($totalSinDestino,0,',','.') }}</div>
                <div class="meta">{{ number_format($otsSinDestino,0,',','.') }} OTs con PT sin plan ni remisión original suficiente</div>
                <a href="{{ route('control.terminacion.reporte-pendientes-envio', ['fecha_desde'=>$fechaDesde, 'fecha_hasta'=>$fechaHasta, 'buscar'=>$buscar]) }}"
                   class="btn btn-sm btn-outline-warning mt-2">
                    <i class="fas fa-balance-scale mr-1"></i>Ver conciliación
                </a>
            </div></div>
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label"><i class="fas fa-file-invoice text-info mr-1"></i>Salida neta de OT</div>
                <div class="value">{{ number_format($totalRemitidoReal,0,',','.') }}</div>
                <div class="meta">
                    {{ number_format($totalRemitidoBruto,0,',','.') }} remitidas desde Central
                    − {{ number_format($totalDevolucionesRecibidas,0,',','.') }} retornos ingresados
                    (más coberturas locales válidas). No espera la recepción del local destino.
                </div>
            </div></div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <button type="button" class="card ct-card ct-kpi h-100 w-100 text-left {{ $totalPendienteRemitirPlan>0?'border-info':'' }}" style="background:#fff;padding:0;cursor:pointer" data-toggle="modal" data-target="#modalPendienteRemitirTerminacion" aria-label="Ver OTs pendientes de remitir">
                <div class="card-body w-100">
                    <div class="label"><i class="fas fa-truck-loading text-info mr-1"></i>Pendiente remitir <i class="fas fa-search-plus float-right text-primary"></i></div>
                    <div class="value">{{ number_format($totalPendienteRemitirPlan,0,',','.') }}</div>
                    <div class="meta">{{ number_format($otsPendienteRemitirPlan,0,',','.') }} OTs con plan asignado todavía no remitido · <span class="text-primary font-weight-bold">Ver detalle</span></div>
                </div>
            </button>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <button type="button"
                    class="card ct-card ct-kpi h-100 w-100 text-left {{ $totalEnTransito>0?'border-primary':'' }}"
                    style="background:#fff;padding:0;cursor:pointer"
                    data-toggle="modal" data-target="#modalEnTransitoTerminacion"
                    aria-label="Ver las remisiones en tránsito">
                <div class="card-body w-100">
                    <div class="label">
                        <i class="fas fa-shipping-fast text-primary mr-1"></i>En tránsito
                        <i class="fas fa-search-plus float-right text-primary"></i>
                    </div>
                    <div class="value">{{ number_format($totalEnTransito,0,',','.') }}</div>
                    <div class="meta">
                        {{ number_format($otsEnTransito,0,',','.') }} OTs remitidas pendientes de recepción
                        · <span class="text-primary font-weight-bold">Ver remisiones y fechas</span>
                    </div>
                </div>
            </button>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card ct-card ct-kpi h-100"><div class="card-body">
                <div class="label"><i class="fas fa-store text-success mr-1"></i>Recepción Local</div>
                <div class="value">{{ number_format($totalRecepcionLocal,0,',','.') }}</div>
                <div class="meta">Sólo remisiones con fecha_recepcion confirmada</div>
            </div></div>
        </div>
    </div>

    {{-- Los siguientes cuadres usan los mismos valores de los KPI superiores.
         La descomposición de pendiente con destino se obtiene OT por OT,
         no por una resta del resumen, para detectar inconsistencias. --}}
    @php
        $totalPendienteConDestinoCuadre = (int) $detallePendienteRemitirPlan
            ->sum(function ($ot) {
                return max(
                    0,
                    (int) $ot->pendiente_remitir_plan
                        - (int) $ot->sin_destino
                );
            });

        $cuadreRecepcion = (int) $totalRecepcionLocal
            + (int) $totalEnTransito;
        $cuadrePendientes = $totalPendienteConDestinoCuadre
            + (int) $totalSinDestino;
        $cuadreDistribucion = (int) $totalRemitidoReal
            + (int) $totalPendienteRemitirPlan;
        $cuadreProduccion = (int) $totalTerminado
            + (int) $totalFaltaTerminacion;

        $verificaRecepcion = $cuadreRecepcion === (int) $totalRemitidoReal;
        $verificaPendientes = $cuadrePendientes === (int) $totalPendienteRemitirPlan;
        $verificaDistribucion = $cuadreDistribucion === (int) $totalTerminado;
        $verificaProduccion = $cuadreProduccion === (int) $totalIngresoTerminacion;
        $verificaCuadreGeneral = $verificaRecepcion
            && $verificaPendientes
            && $verificaDistribucion
            && $verificaProduccion;
    @endphp

    <div class="card ct-card mb-3" id="cuadre-indicadores-terminacion">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <strong><i class="fas fa-balance-scale text-primary mr-1"></i>Cuadre de indicadores de Terminación y Logística</strong>
                <div class="text-muted small">
                    Conciliación automática con las mismas OT y filtros de los indicadores superiores.
                </div>
            </div>
            <span class="badge {{ $verificaCuadreGeneral ? 'badge-success' : 'badge-danger' }} p-2 mt-2 mt-md-0">
                <i class="fas {{ $verificaCuadreGeneral ? 'fa-check-circle' : 'fa-exclamation-triangle' }} mr-1"></i>
                {{ $verificaCuadreGeneral ? 'CUADRA 100%' : 'HAY DIFERENCIAS' }}
            </span>
        </div>
        <div class="card-body pt-3 pb-2">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-2">
                    <thead class="thead-light">
                        <tr>
                            <th>Conciliación</th>
                            <th>Comprobación de cantidades</th>
                            <th class="text-right">Resultado</th>
                            <th class="text-center">Control</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Recepción + Tránsito = Salida neta</strong></td>
                            <td>
                                {{ number_format($totalRecepcionLocal,0,',','.') }} recibidas
                                + {{ number_format($totalEnTransito,0,',','.') }} en tránsito
                            </td>
                            <td class="text-right text-nowrap">
                                <strong>{{ number_format($cuadreRecepcion,0,',','.') }}</strong>
                                <small class="d-block text-muted">Salida neta: {{ number_format($totalRemitidoReal,0,',','.') }}</small>
                            </td>
                            <td class="text-center text-nowrap">
                                @if($verificaRecepcion)
                                    <span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>CUADRA</span>
                                @else
                                    <span class="text-danger font-weight-bold">DIFERENCIA {{ number_format(abs($cuadreRecepcion - (int)$totalRemitidoReal),0,',','.') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Con destino + Sin destino = Pendiente total</strong></td>
                            <td>
                                {{ number_format($totalPendienteConDestinoCuadre,0,',','.') }} pendientes con destino
                                + {{ number_format($totalSinDestino,0,',','.') }} sin destino
                            </td>
                            <td class="text-right text-nowrap">
                                <strong>{{ number_format($cuadrePendientes,0,',','.') }}</strong>
                                <small class="d-block text-muted">Pendiente remitir: {{ number_format($totalPendienteRemitirPlan,0,',','.') }}</small>
                            </td>
                            <td class="text-center text-nowrap">
                                @if($verificaPendientes)
                                    <span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>CUADRA</span>
                                @else
                                    <span class="text-danger font-weight-bold">DIFERENCIA {{ number_format(abs($cuadrePendientes - (int)$totalPendienteRemitirPlan),0,',','.') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Salida neta + Pendiente = Producto Terminado</strong></td>
                            <td>
                                {{ number_format($totalRemitidoReal,0,',','.') }} salida neta
                                + {{ number_format($totalPendienteRemitirPlan,0,',','.') }} pendiente total
                            </td>
                            <td class="text-right text-nowrap">
                                <strong>{{ number_format($cuadreDistribucion,0,',','.') }}</strong>
                                <small class="d-block text-muted">PT: {{ number_format($totalTerminado,0,',','.') }}</small>
                            </td>
                            <td class="text-center text-nowrap">
                                @if($verificaDistribucion)
                                    <span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>CUADRA</span>
                                @else
                                    <span class="text-danger font-weight-bold">DIFERENCIA {{ number_format(abs($cuadreDistribucion - (int)$totalTerminado),0,',','.') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Producto Terminado + Falta Terminación = Ingreso Terminación</strong></td>
                            <td>
                                {{ number_format($totalTerminado,0,',','.') }} PT
                                + {{ number_format($totalFaltaTerminacion,0,',','.') }} falta terminar
                            </td>
                            <td class="text-right text-nowrap">
                                <strong>{{ number_format($cuadreProduccion,0,',','.') }}</strong>
                                <small class="d-block text-muted">Ingreso: {{ number_format($totalIngresoTerminacion,0,',','.') }}</small>
                            </td>
                            <td class="text-center text-nowrap">
                                @if($verificaProduccion)
                                    <span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>CUADRA</span>
                                @else
                                    <span class="text-danger font-weight-bold">DIFERENCIA {{ number_format(abs($cuadreProduccion - (int)$totalIngresoTerminacion),0,',','.') }}</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @unless($verificaCuadreGeneral)
                <div class="alert alert-warning py-2 small mb-2">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Hay saldos que no coinciden. Revisar las OT del filtro, trazabilidad de producción,
                    distribución y remisiones antes de modificar importaciones.
                </div>
            @endunless
            <p class="small text-muted mb-0">
                <strong>Nota:</strong> estas sumas verifican la consistencia de los indicadores, no confirman
                por sí solas el stock físico ni cada remisión. La cuarta ecuación puede evidenciar
                exceso de PT o registros de trazabilidad incompletos. El control del plan asignado
                se realiza por separado en <strong>Ver conciliación</strong>.
            </p>
        </div>
    </div>

    <div class="card ct-card mb-3">
        <div class="card-header bg-white">
            <strong><i class="fas fa-coins text-warning mr-1"></i>Valorización económica</strong>
            <div class="ct-subtitle">
                Costo y valor efectivo de las remisiones originales, limitado por el Producto Terminado reconocido en cada OT.
            </div>
        </div>
        <div class="card-body pb-2">
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="ct-mini">
                        <div class="small text-uppercase text-muted font-weight-bold">Costo efectivo remitido</div>
                        <div class="h5 font-weight-bold mb-1">Gs {{ number_format($totalCostoRemitido,0,',','.') }}</div>
                        <small class="text-muted">
                            {{ number_format($totalCantidadValorizadaRemitida,0,',','.') }} prendas valorizadas · limitado por PT
                        </small>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="ct-mini">
                        <div class="small text-uppercase text-muted font-weight-bold">Valor efectivo a precio de venta</div>
                        <div class="h5 font-weight-bold mb-1 text-primary">Gs {{ number_format($totalVentaRemitida,0,',','.') }}</div>
                        <small class="text-muted">
                            {{ number_format($totalCantidadValorizadaRemitida,0,',','.') }} prendas efectivas · precio_venta importado
                        </small>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="ct-mini">
                        <div class="small text-uppercase text-muted font-weight-bold">Costo efectivo recibido</div>
                        <div class="h5 font-weight-bold mb-1">Gs {{ number_format($totalCostoRecibido,0,',','.') }}</div>
                        <small class="text-muted">
                            {{ number_format($totalCantidadValorizadaRecibida,0,',','.') }} prendas valorizadas con recepción
                        </small>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="ct-mini">
                        <div class="small text-uppercase text-muted font-weight-bold">Valor efectivo recibido</div>
                        <div class="h5 font-weight-bold mb-1 text-success">Gs {{ number_format($totalVentaRecibida,0,',','.') }}</div>
                        <small class="text-muted">
                            Valor comercial de {{ number_format($totalCantidadValorizadaRecibida,0,',','.') }} prendas efectivamente recibidas
                        </small>
                    </div>
                </div>
            </div>

            <div class="ct-flow mb-2">
                <strong>Margen bruto teórico efectivo:</strong>
                Gs {{ number_format($totalMargenBrutoRemitido,0,',','.') }}
                · {{ number_format($porcentajeMargenBrutoRemitido,1,',','.') }}% sobre el valor de venta.
                <span class="text-muted">
                    Se calcula sólo sobre las prendas efectivas valorizadas (tope PT) como precio de venta menos costo; no representa utilidad neta ni venta ya cobrada.
                </span>
            </div>
        </div>
    </div>

    @if($totalHuecoDetalleLogistico > 0)
        <div class="alert alert-info py-2 mb-2">
            <i class="fas fa-info-circle mr-1"></i>
            <strong>Conciliación de datos:</strong>
            {{ number_format($totalHuecoDetalleLogistico,0,',','.') }} prendas de
            {{ number_format($otsHuecoDetalleLogistico,0,',','.') }} OTs ya tienen remisión original,
            pero el plan logístico todavía no las explica completamente.
            <strong>No se cuentan como faltante físico.</strong>
        </div>

        {{-- Seguimiento de las mismas OT y cantidades del aviso, sin limitar a la página actual. --}}
        <div class="card ct-card mb-3">
            <div class="card-header bg-white py-2">
                <strong><i class="fas fa-search-plus text-info mr-1"></i>OT con remisión no cubierta por el plan</strong>
                <span class="text-muted small ml-1">· {{ number_format($otsHuecoDetalleLogistico,0,',','.') }} OT · {{ number_format($totalHuecoDetalleLogistico,0,',','.') }} prendas por conciliar</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover ct-table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>OT</th>
                            <th>Código / descripción</th>
                            <th class="text-right">PT</th>
                            <th class="text-right">Plan aplicable</th>
                            <th class="text-right">Remitido</th>
                            <th class="text-right">Sin cobertura del plan</th>
                            <th>Última remisión</th>
                            <th class="text-right">Seguimiento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($detalleHuecoLogistico as $otHueco)
                            <tr>
                                <td><strong>{{ $otHueco->nro_ot }}</strong></td>
                                <td>
                                    <span class="ct-code">{{ $otHueco->codigo }}</span>
                                    <br><small class="text-muted">{{ $otHueco->descripcion }}</small>
                                </td>
                                <td class="text-right">{{ number_format($otHueco->producto_terminado_referencia,0,',','.') }}</td>
                                <td class="text-right">{{ number_format($otHueco->cantidad_destino_detalle,0,',','.') }}</td>
                                <td class="text-right">{{ number_format($otHueco->remitido_efectivo,0,',','.') }}</td>
                                <td class="text-right font-weight-bold text-info">{{ number_format($otHueco->hueco_detalle_logistico,0,',','.') }}</td>
                                <td class="ct-nowrap">{{ $otHueco->ultima_remision ? \Carbon\Carbon::parse($otHueco->ultima_remision)->format('d/m/Y') : '—' }}</td>
                                <td class="text-right">
                                    <button type="button"
                                            class="btn btn-outline-primary btn-sm btn-detalle-ot ct-nowrap"
                                            data-url="{{ route('control.terminacion.detalle', ['idOt' => $otHueco->id_ot]) }}">
                                        <i class="fas fa-route mr-1"></i>Ver detalle
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td colspan="5">TOTAL · {{ number_format($otsHuecoDetalleLogistico,0,',','.') }} OT</td>
                            <td class="text-right text-info">{{ number_format($detalleHuecoLogistico->sum('hueco_detalle_logistico'),0,',','.') }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="card-footer bg-white small text-muted py-2">
                La diferencia se calcula por OT: remitido efectivo menos plan aplicable a PT.
                El botón Ver detalle abre su distribución, remisiones y recepción.
            </div>
        </div>
    @endif

    @if($detalleDevoluciones->isNotEmpty())
        <div class="card ct-card mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
                <strong><i class="fas fa-undo text-info mr-1"></i>Entradas por devolución y reenvíos de OT</strong>
                <span class="text-muted small">{{ number_format($detalleDevoluciones->count(),0,',','.') }} OTs con devoluciones importadas</span>
            </div>
            <div class="card-body py-2">
                <div class="row small">
                    <div class="col-md-3 mb-2"><strong>Remisiones brutas Central:</strong> {{ number_format($totalRemitidoBruto,0,',','.') }}</div>
                    <div class="col-md-3 mb-2"><strong>Retornos emitidos:</strong> {{ number_format($totalDevolucionesEmitidas,0,',','.') }}</div>
                    <div class="col-md-3 mb-2"><strong>Retornos recibidos Central:</strong> {{ number_format($totalDevolucionesRecibidas,0,',','.') }}</div>
                    <div class="col-md-3 mb-2"><strong>Retornos en camino:</strong> {{ number_format($totalDevolucionesEnTransito,0,',','.') }}</div>
                </div>
                @if($totalDevolucionesSinSalida > 0)
                    <div class="alert alert-warning small py-2">
                        {{ number_format($totalDevolucionesSinSalida,0,',','.') }} devoluciones superan las remisiones centrales vinculadas.
                        Revisar identificación de OT antes de acreditar el movimiento.
                    </div>
                @endif
                <div class="table-responsive" style="max-height:45vh;overflow:auto">
                    <table class="table table-sm table-striped table-hover ct-table mb-0">
                        <thead class="thead-light"><tr>
                            <th>OT</th><th>Código</th>
                            <th class="text-right">PT</th>
                            <th class="text-right">Salida bruta</th>
                            <th class="text-right">Retornos ingresados</th>
                            <th class="text-right">Retornos en tránsito</th>
                            <th class="text-right">Salida neta</th>
                            <th class="text-right">Saldo teórico Central</th>
                            <th></th>
                        </tr></thead>
                        <tbody>
                        @foreach($detalleDevoluciones as $itemRetorno)
                            <tr>
                                <td><strong>{{ $itemRetorno->nro_ot }}</strong></td>
                                <td><code>{{ $itemRetorno->codigo }}</code></td>
                                <td class="text-right">{{ number_format($itemRetorno->cantidad_terminada,0,',','.') }}</td>
                                <td class="text-right">{{ number_format($itemRetorno->remitido_bruto,0,',','.') }}</td>
                                <td class="text-right text-success font-weight-bold">{{ number_format($itemRetorno->devoluciones_recibidas,0,',','.') }}</td>
                                <td class="text-right text-warning">{{ number_format($itemRetorno->devoluciones_en_transito,0,',','.') }}</td>
                                <td class="text-right font-weight-bold">{{ number_format($itemRetorno->remitido_efectivo,0,',','.') }}</td>
                                <td class="text-right">{{ number_format($itemRetorno->stock_central_disponible,0,',','.') }}</td>
                                <td><button type="button" class="btn btn-sm btn-outline-primary btn-detalle-ot"
                                            data-url="{{ route('control.terminacion.detalle', ['idOt' => $itemRetorno->id_ot]) }}">
                                        Ver movimientos
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <small class="text-muted">El stock Central es un saldo teórico de la OT: PT menos salida neta; no sustituye el stock físico registrado por código, color y talle. Los retornos sin fecha_recepcion todavía no reponen stock.</small>
            </div>
        </div>
    @endif

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
                        <th class="text-right">Plan logístico</th>
                        <th class="text-right">Remitido real</th>
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
                                    $estadoClase = [
                                        'FALTA TERMINACION' => 'danger',
                                        'SIN DESTINO' => 'warning',
                                        'PENDIENTE REMITIR' => 'info',
                                        'PENDIENTE SALIDA' => 'info',
                                        'EN TRANSITO' => 'primary',
                                        'CONFIRMADO' => 'success',
                                    ][$item->estado_conciliacion] ?? 'secondary';

                                    $etapaClase = $estadoClase;
                                @endphp
                                <span class="badge badge-{{ $estadoClase }} px-2 py-2">
                                    {{ $item->estado_conciliacion }}
                                </span>
                            </td>
                            <td class="text-right">
                                <strong>{{ number_format($item->cantidad_logistica,0,',','.') }}/{{ number_format($item->producto_terminado_referencia,0,',','.') }}</strong>

                                @if($item->sin_destino_plan > 0)
                                    <br>
                                    <small class="text-warning">
                                        {{ number_format($item->cantidad_destino_detalle,0,',','.') }} distribuidas
                                        · {{ number_format($item->sin_destino_plan,0,',','.') }} sin asignar
                                    </small>
                                @else
                                    <br>
                                    <small class="text-success">
                                        {{ number_format($item->cantidad_destino_detalle,0,',','.') }} distribuidas · plan completo
                                    </small>
                                @endif
                            </td>

                            <td class="text-right">
                                <strong>{{ number_format($item->remitido_efectivo,0,',','.') }}/{{ number_format($item->producto_terminado_referencia,0,',','.') }}</strong>
                                @if($item->pendiente_real_salida > 0)
                                    <br><small class="text-info">
                                        {{ number_format($item->pendiente_real_salida,0,',','.') }} sin salida
                                    </small>
                                @else
                                    <br><small class="text-success">Salida cubierta</small>
                                @endif
                            </td>

                            <td class="text-right">
                                <strong>{{ number_format($item->recibido_efectivo,0,',','.') }}/{{ number_format($item->remitido_efectivo,0,',','.') }}</strong>
                                @if($item->en_transito > 0)
                                    <br><small class="text-primary">
                                        {{ number_format($item->en_transito,0,',','.') }} en tránsito
                                    </small>
                                @else
                                    <br><small class="text-muted">{{ $item->ultima_recepcion ? \Carbon\Carbon::parse($item->ultima_recepcion)->format('d/m/Y') : '—' }}</small>
                                @endif
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

{{-- Control auditable de remisiones pendientes de recepción para las OT del KPI. --}}
@include('control._terminacion_transito')

{{-- Detalle completo del KPI, sin paginación: el total coincide con el resumen. --}}
<div class="modal fade" id="modalPendienteRemitirTerminacion" tabindex="-1" role="dialog" aria-labelledby="tituloPendienteRemitirTerminacion" aria-hidden="true">
    <div class="modal-dialog" role="document" style="width:96vw;max-width:1800px">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title font-weight-bold" id="tituloPendienteRemitirTerminacion"><i class="fas fa-truck-loading text-primary mr-2"></i>Detalle de prendas pendientes de remitir</h5>
                    <small class="text-muted">OT seleccionadas por los filtros del Control Terminación · {{ $fechaDesde }} al {{ $fechaHasta }} · ordenadas por antigüedad desde el primer PRODUCTO TERMINADO</small>
                </div>
                <div class="d-flex align-items-center ml-2">
                    <a href="{{ route('control.terminacion.pendiente-remitir.excel', array_merge(['fecha_desde' => $fechaDesde, 'fecha_hasta' => $fechaHasta, 'buscar' => $buscar], !empty($estados) ? ['estado' => $estados] : [])) }}"
                       class="btn btn-sm btn-success mr-3" title="Descargar todas las OT pendientes del indicador en formato Excel">
                        <i class="fas fa-file-excel mr-1"></i> Exportar Excel
                    </a>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                </div>
            </div>
            <div class="modal-body">
                @php $totalDetalleRemitirPlan = (int) $detallePendienteRemitirPlan->sum('pendiente_remitir_plan'); @endphp
                <div class="row mb-3">
                    <div class="col-md-4 mb-2"><div class="border rounded p-3 bg-light"><small class="text-muted d-block">OT PENDIENTES</small><h4 class="font-weight-bold mb-0">{{ number_format($detallePendienteRemitirPlan->count(),0,',','.') }}</h4></div></div>
                    <div class="col-md-4 mb-2"><div class="border rounded p-3 bg-light"><small class="text-muted d-block">PRENDAS PENDIENTES</small><h4 class="font-weight-bold text-danger mb-0">{{ number_format($totalDetalleRemitirPlan,0,',','.') }}</h4></div></div>
                    <div class="col-md-4 mb-2"><div class="border rounded p-3 bg-light"><small class="text-muted d-block">CONTROL CONTRA KPI</small><h4 class="font-weight-bold mb-0 {{ $totalDetalleRemitirPlan === (int)$totalPendienteRemitirPlan ? 'text-success' : 'text-danger' }}">{{ $totalDetalleRemitirPlan === (int)$totalPendienteRemitirPlan ? 'COINCIDE' : 'NO COINCIDE' }}</h4></div></div>
                </div>
                <div class="alert alert-light border py-2 mb-2 small">
                    <i class="fas fa-clock text-danger mr-1"></i>
                    <strong>Prioridad por antigüedad:</strong> primero las OT que llevan más días desde su
                    <strong>primer registro en TERMINACION - PRODUCTO TERMINADO</strong> hasta hoy.
                    Los reenvíos y devoluciones posteriores no reinician el contador.
                    En igualdad de días, se ordena por mayor cantidad pendiente.
                </div>
                <div class="table-responsive" style="max-height:70vh;overflow:auto">
                    <table class="table table-sm table-striped table-hover ct-table mb-0">
                        <thead class="thead-light" style="position:sticky;top:0;z-index:2"><tr>
                            <th>OT</th>
                            <th class="text-center">Antigüedad PT</th>
                            <th>Fecha primer PT</th>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th class="text-right">Cantidad OT</th>
                            <th class="text-right">Plan asignado</th>
                            <th class="text-right">Remitido (fecha remisión)</th>
                            <th class="text-right">Recibido (fecha recepción)</th>
                            <th class="text-right">En tránsito</th>
                            <th class="text-right">Pendiente de remitir</th>
                        </tr></thead>
                        <tbody>
                        @forelse($detallePendienteRemitirPlan as $otPendiente)
                            <tr>
                                <td><strong>{{ $otPendiente->nro_ot }}</strong></td>
                                <td class="text-center">
                                    @if($otPendiente->dias_desde_pt !== null)
                                        <span class="badge p-2 {{ $otPendiente->dias_desde_pt >= 30 ? 'badge-danger' : ($otPendiente->dias_desde_pt >= 15 ? 'badge-warning' : 'badge-secondary') }}">
                                            {{ number_format($otPendiente->dias_desde_pt,0,',','.') }} días
                                        </span>
                                    @else
                                        <span class="text-muted">Sin fecha</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    {{ $otPendiente->primera_fecha_pt_historica ? date('d/m/Y', strtotime($otPendiente->primera_fecha_pt_historica)) : 'Sin registro' }}
                                </td>
                                <td><code>{{ $otPendiente->codigo }}</code></td>
                                <td>{{ $otPendiente->descripcion }}</td>
                                <td class="text-right">{{ number_format($otPendiente->cantidad_orden,0,',','.') }}</td>
                                <td class="text-right">{{ number_format($otPendiente->cantidad_logistica,0,',','.') }}</td>
                                <td class="text-right text-primary font-weight-bold">
                                    {{ number_format($otPendiente->remitido_efectivo,0,',','.') }}
                                    @if(!empty($otPendiente->ultima_remision))
                                        <small class="d-block text-muted font-weight-normal">Remisión: {{ date('d/m/Y', strtotime($otPendiente->ultima_remision)) }}</small>
                                    @endif
                                    @if(($otPendiente->remitido_sin_vinculo_recuperado ?? 0) > 0)
                                        <small class="d-block text-info font-weight-normal" title="Remisión emitida sin id_ot, asociada en consulta porque el código pertenece a una sola OT.">
                                            {{ number_format($otPendiente->remitido_sin_vinculo_recuperado,0,',','.') }} con OT recuperada por código
                                        </small>
                                    @endif
                                </td>
                                <td class="text-right">
                                    {{ number_format($otPendiente->recibido_efectivo,0,',','.') }}
                                    @if(!empty($otPendiente->ultima_recepcion))
                                        <small class="d-block text-muted">Recepción: {{ date('d/m/Y', strtotime($otPendiente->ultima_recepcion)) }}</small>
                                    @endif
                                </td>
                                <td class="text-right text-info font-weight-bold">{{ number_format($otPendiente->en_transito,0,',','.') }}</td>
                                <td class="text-right font-weight-bold text-danger">{{ number_format($otPendiente->pendiente_remitir_plan,0,',','.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="text-center text-muted py-4">No existen OT pendientes de remitir con estos filtros.</td></tr>
                        @endforelse
                        </tbody>
                        <tfoot class="bg-light font-weight-bold"><tr><td colspan="10">TOTAL · {{ number_format($detallePendienteRemitirPlan->count(),0,',','.') }} OT</td><td class="text-right text-danger">{{ number_format($totalDetalleRemitirPlan,0,',','.') }}</td></tr></tfoot>
                    </table>
                </div>
                <p class="small text-muted mt-3 mb-0">Remitido = documento de remisión emitido/importado (no espera fecha de recepción). Recibido = confirmado con fecha_recepcion. En tránsito = remitido menos recibido. Pendiente de remitir = todavía sin remisión de salida. Las OT de esta lista siguen teniendo algún pendiente de remitir; los envíos en tránsito también aparecen en el KPI de tránsito. No se altera la base de datos.</p>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>
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