<style>
    .td-header-card{background:linear-gradient(135deg,#f8fafc,#fff);border:1px solid #e7ebf0;border-radius:12px;padding:14px 16px}
    .td-kpi{border:1px solid #e7ebf0;border-radius:10px;padding:10px 12px;background:#fff;height:100%}
    .td-kpi small{font-size:10px;text-transform:uppercase;font-weight:800;letter-spacing:.05em;color:#7a8796}
    .td-kpi strong{font-size:20px;color:#26364a}
    .td-table{border:1px solid #e7ebf0;border-radius:10px;overflow:hidden}
    .td-table thead th{background:#f5f7fa;border-bottom:1px solid #dfe5ec;font-size:10px;text-transform:uppercase;letter-spacing:.04em;color:#5d6c7c;white-space:nowrap;vertical-align:middle}
    .td-table tbody td{vertical-align:middle;font-size:12px}
    .td-table tbody tr:hover{background:#fbfdff}
    .td-plan-badge{display:inline-block;background:#eef4ff;color:#2956a3;border:1px solid #d8e4fb;border-radius:7px;padding:5px 8px;font-weight:800;white-space:nowrap}
    .td-destino-real{font-weight:800;color:#2f3e4e}
    .td-codigo-variante{font-size:15px;font-weight:800;color:#111!important;letter-spacing:.02em;white-space:nowrap}
    .td-remision{font-weight:800;color:#34465a}
    .td-cantidad{font-size:15px;font-weight:800;color:#26364a}
    .td-confirmado{font-size:15px;font-weight:800;color:#198754}
    .td-subtotal-row{background:#f8fafc;border-top:2px solid #e4e9ef}
    .td-subtotal-box{display:inline-flex;gap:14px;align-items:center;flex-wrap:wrap}
    .td-subtotal-item{white-space:nowrap}
    .td-subtotal-label{font-size:10px;text-transform:uppercase;color:#7a8796;font-weight:800;margin-right:4px}
    .td-subtotal-value{font-size:15px;font-weight:800;color:#26364a}
    .td-section-label{font-size:11px;text-transform:uppercase;font-weight:800;color:#7a8796;letter-spacing:.04em}
    .td-money{white-space:nowrap;font-weight:800;color:#26364a}
    .td-money small{display:block;font-size:10px;font-weight:600;color:#7a8796}
    .td-valor-total{border-left:4px solid #17a2b8}
    .td-valor-remitido{border-left:4px solid #28a745}
    .td-valor-retenido{border-left:4px solid #ffc107;background:#fffdf5}
</style>
<div class="p-2">
    <div class="td-header-card mb-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div>
                <div class="td-section-label mb-1">Trazabilidad posterior de la OT</div>
                <h5 class="mb-1 font-weight-bold text-dark">OT {{ $ot->nro_ot }} · {{ $ot->codigo }}</h5>
                <div class="text-muted">{{ $ot->descripcion }}</div>
            </div>
            <span class="badge badge-dark px-3 py-2 mt-1">
                Orden {{ number_format($ot->cantidad_orden, 0, ',', '.') }}
            </span>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-lg col-6 mb-2"><div class="td-kpi">
            <small class="d-block">Plan según Producto Terminado</small>
            <strong>{{ number_format($totalPlan, 0, ',', '.') }}</strong>
            <div class="mt-1">
                @if($totalSinAsignar > 0)
                    <small class="text-warning">
                        Asignado {{ number_format($totalPlanDetallado, 0, ',', '.') }}
                        · {{ number_format($totalSinAsignar, 0, ',', '.') }} sin asignar
                    </small>
                @else
                    <small class="text-success">
                        Asignado {{ number_format($totalPlanDetallado, 0, ',', '.') }}
                        · completo
                    </small>
                @endif
                @if($totalExcesoPlan > 0)
                    <small class="d-block text-info">
                        +{{ number_format($totalExcesoPlan, 0, ',', '.') }}
                        movimiento(s) adicional(es) de auditoría fuera del PT
                    </small>
                @endif
            </div>
        </div></div>
        <div class="col-lg col-6 mb-2"><div class="td-kpi">
            <small class="d-block">Remitido efectivo</small>
            <strong>{{ number_format($totalRemitido, 0, ',', '.') }}</strong>
            <div class="mt-1">
                <small class="text-muted">
                    Movimiento físico {{ number_format($totalRemitidoFisico, 0, ',', '.') }}
                    @if($totalRemovido > 0)
                        · +{{ number_format($totalRemovido, 0, ',', '.') }} re-movidas
                    @endif
                </small>
            </div>
        </div></div>
        <div class="col-lg col-6 mb-2"><div class="td-kpi">
            <small class="d-block">Confirmado efectivo</small>
            <strong class="text-success">{{ number_format($totalRecibido, 0, ',', '.') }}</strong>
            <div class="mt-1">
                <small class="text-muted">
                    Recepciones físicas {{ number_format($totalRecibidoFisico, 0, ',', '.') }}
                    @if($totalConfirmadoExtra > 0)
                        · +{{ number_format($totalConfirmadoExtra, 0, ',', '.') }} auditoría
                    @endif
                </small>
            </div>
        </div></div>
        <div class="col-lg col-6 mb-2"><div class="td-kpi">
            <small class="d-block">En tránsito</small>
            <strong class="text-primary">{{ number_format($totalEnTransito, 0, ',', '.') }}</strong>
        </div></div>
        <div class="col-lg col-6 mb-2"><div class="td-kpi">
            <small class="d-block">Pendiente físico</small>
            <strong class="{{ $totalPendiente > 0 ? 'text-warning' : 'text-success' }}">{{ number_format($totalPendiente, 0, ',', '.') }}</strong>
            <div class="mt-1">
                <small class="{{ $totalPendientePlanDestino > 0 ? 'text-warning' : 'text-muted' }}">
                    Pendiente por destino histórico:
                    {{ number_format($totalPendientePlanDestino, 0, ',', '.') }}
                </small>
            </div>
        </div></div>
    </div>

    <div class="alert alert-light border py-2 small mb-3">
        <strong>Cuadre de esta OT:</strong>
        objetivo {{ number_format($totalPlan,0,',','.') }}
        = {{ number_format($totalRemitido,0,',','.') }} remitido efectivo
        + {{ number_format($totalPendiente,0,',','.') }} pendiente físico.
        <br>
        <span class="text-muted">
            Auditoría de movimientos:
            {{ number_format($totalPlanDetalladoRaw,0,',','.') }} registros logísticos acumulados,
            {{ number_format($totalRemitidoFisico,0,',','.') }} movimientos remitidos
            y {{ number_format($totalPendientePlanDestino,0,',','.') }} pendiente histórico por destino.
            Estos movimientos no aumentan el plan ni el avance efectivo por encima de
            {{ number_format($totalPlan,0,',','.') }} PT.
        </span>
    </div>

    <div class="td-section-label mb-2">
        Valorización económica de la OT
    </div>

    @if(
        $totalCostoProductoTerminado !== null
        && $totalVentaProductoTerminado !== null
    )
        <div class="row mb-2">
            <div class="col-lg-4 col-md-6 mb-2">
                <div class="td-kpi td-valor-total">
                    <small class="d-block">
                        Valor total del Producto Terminado
                    </small>

                    <strong>
                        {{ number_format($totalPlan, 0, ',', '.') }}
                        prendas
                    </strong>

                    <div class="mt-2 small">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Costo total</span>
                            <strong>
                                Gs {{ number_format($totalCostoProductoTerminado, 0, ',', '.') }}
                            </strong>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Valor venta total</span>
                            <strong class="text-primary">
                                Gs {{ number_format($totalVentaProductoTerminado, 0, ',', '.') }}
                            </strong>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Margen bruto teórico</span>
                            <strong class="text-success">
                                Gs {{ number_format($totalMargenProductoTerminado, 0, ',', '.') }}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-2">
                <div class="td-kpi td-valor-remitido">
                    <small class="d-block">
                        Ya remitido
                    </small>

                    <strong class="text-success">
                        {{ number_format($totalRemitido, 0, ',', '.') }}
                        prendas
                    </strong>

                    <div class="mt-2 small">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Costo remitido</span>
                            <strong>
                                Gs {{ number_format($totalCostoRemitido, 0, ',', '.') }}
                            </strong>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Valor venta remitido</span>
                            <strong class="text-primary">
                                Gs {{ number_format($totalVentaRemitida, 0, ',', '.') }}
                            </strong>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Margen remitido</span>
                            <strong class="text-success">
                                Gs {{ number_format($totalMargenBrutoRemitido, 0, ',', '.') }}
                            </strong>
                        </div>

                        <small class="d-block text-muted mt-1">
                            Confirmado:
                            {{ number_format($totalRecibido, 0, ',', '.') }}
                            prendas · venta
                            Gs {{ number_format($totalVentaRecibida, 0, ',', '.') }}
                        </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-12 mb-2">
                <div class="td-kpi td-valor-retenido">
                    <small class="d-block text-warning">
                        Retenido / pendiente de salida
                    </small>

                    <strong class="{{ $cantidadRetenida > 0 ? 'text-warning' : 'text-success' }}">
                        {{ number_format($cantidadRetenida, 0, ',', '.') }}
                        prendas
                    </strong>

                    <div class="mt-2 small">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Costo retenido</span>
                            <strong class="{{ $cantidadRetenida > 0 ? 'text-warning' : '' }}">
                                Gs {{ number_format($totalCostoRetenido, 0, ',', '.') }}
                            </strong>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span class="text-muted">
                                Valor comercial retenido
                            </span>
                            <strong class="{{ $cantidadRetenida > 0 ? 'text-warning' : 'text-success' }}">
                                Gs {{ number_format($totalVentaRetenida, 0, ',', '.') }}
                            </strong>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span class="text-muted">
                                Margen retenido
                            </span>
                            <strong>
                                Gs {{ number_format($totalMargenRetenido, 0, ',', '.') }}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-light border py-2 small mb-3">
            <strong>Cuadre económico:</strong>
            venta total PT
            <strong>
                Gs {{ number_format($totalVentaProductoTerminado, 0, ',', '.') }}
            </strong>
            =
            <strong>
                Gs {{ number_format($totalVentaRemitida, 0, ',', '.') }}
            </strong>
            remitido
            +
            <strong class="{{ $cantidadRetenida > 0 ? 'text-warning' : 'text-success' }}">
                Gs {{ number_format($totalVentaRetenida, 0, ',', '.') }}
            </strong>
            retenido.

            <br>

            <span class="text-muted">
                Costo unitario:
                Gs {{ number_format($costoUnitarioReferencia, 0, ',', '.') }}
                · Precio venta unitario:
                Gs {{ number_format($precioVentaUnitarioReferencia, 0, ',', '.') }}
                · Fuente:
                <strong>{{ $fuenteValorReferencia }}</strong>
                @if(!$valorReferenciaExacto)
                    · <span class="text-warning">
                        valor referencial porque existen precios/costos variables
                    </span>
                @else
                    · valor unitario uniforme
                @endif
            </span>
        </div>
    @else
        <div class="alert alert-warning py-2 small mb-3">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            No existe una referencia suficiente de costo/precio para valorar
            el total de {{ number_format($totalPlan, 0, ',', '.') }} prendas.
            Se mantienen únicamente los importes de las remisiones efectivamente
            importadas.
        </div>

        <div class="row mb-3">
            <div class="col-md-6 mb-2">
                <div class="td-kpi">
                    <small class="d-block">Costo efectivo remitido</small>
                    <strong>
                        Gs {{ number_format($totalCostoRemitido, 0, ',', '.') }}
                    </strong>
                </div>
            </div>

            <div class="col-md-6 mb-2">
                <div class="td-kpi">
                    <small class="d-block">
                        Valor efectivo a precio de venta
                    </small>
                    <strong class="text-primary">
                        Gs {{ number_format($totalVentaRemitida, 0, ',', '.') }}
                    </strong>
                </div>
            </div>
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 td-table">
            <thead class="thead-light">
                <tr>
                    <th>Destino / movimiento</th>
                    <th>Destino real</th>
                    <th>Código variante</th>
                    <th>Salida</th>
                    <th>Remisión</th>
                    <th class="text-right">Remitido</th>
                    <th class="text-right">Confirmado</th>
                    <th class="text-right">Costo</th>
                    <th class="text-right">Venta</th>
                    <th>Recepción</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($detalles as $detalle)
                    @if($detalle->remisiones->isEmpty())
                        <tr>
                            <td><span class="td-plan-badge">{{ $detalle->sucursal }}</span></td>
                            <td class="text-muted">Pendiente de salida</td>
                            <td class="text-muted">—</td>
                            <td>{{ $detalle->fecha_logistica ? \Carbon\Carbon::parse($detalle->fecha_logistica)->format('d/m/Y') : '—' }}</td>
                            <td class="text-muted">—</td>
                            <td class="text-right"><span class="td-cantidad">0</span></td>
                            <td class="text-right"><span class="text-muted font-weight-bold">0</span></td>
                            <td class="text-right"><span class="td-money">Gs 0</span></td>
                            <td class="text-right"><span class="td-money">Gs 0</span></td>
                            <td class="text-muted">—</td>
                            <td><span class="badge badge-warning">PENDIENTE</span></td>
                        </tr>

                        <tr class="td-subtotal-row">
                            <td colspan="11">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div>
                                        <span class="td-plan-badge">{{ $detalle->sucursal }}</span>
                                    </div>
                                    <div class="td-subtotal-box">
                                        <span class="td-subtotal-item">
                                            <span class="td-subtotal-label">Registro</span>
                                            <span class="td-subtotal-value">{{ number_format($detalle->cantidad, 0, ',', '.') }}</span>
                                        </span>
                                        <span class="td-subtotal-item">
                                            <span class="td-subtotal-label">Remitido</span>
                                            <span class="td-subtotal-value">0</span>
                                        </span>
                                        <span class="td-subtotal-item">
                                            <span class="td-subtotal-label">Confirmado</span>
                                            <span class="td-subtotal-value text-success">0</span>
                                        </span>
                                        <span class="td-subtotal-item">
                                            <span class="td-subtotal-label">Pendiente</span>
                                            <span class="td-subtotal-value text-warning">
                                                {{ number_format($detalle->cantidad, 0, ',', '.') }}
                                            </span>
                                        </span>
                                        <span class="td-subtotal-item">
                                            <span class="td-subtotal-label">Costo</span>
                                            <span class="td-subtotal-value">Gs 0</span>
                                        </span>
                                        <span class="td-subtotal-item">
                                            <span class="td-subtotal-label">Venta</span>
                                            <span class="td-subtotal-value">Gs 0</span>
                                        </span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @else
                        @php
                            $subtotalLocal = (int) $detalle->remisiones->sum('cantidad');
                            $subtotalConfirmado = (int) $detalle->remisiones
                                ->filter(function ($r) { return !empty($r->fecha_recepcion); })
                                ->sum('cantidad');
                            $destinoGrupo = optional($detalle->remisiones->first())->destino_real ?: $detalle->sucursal;
                        @endphp

                        @foreach($detalle->remisiones as $remision)
                            <tr>
                                <td>
                                    <span class="td-plan-badge">{{ $remision->destino_planificado ?: $detalle->sucursal }}</span>
                                </td>
                                <td>
                                    <span class="td-destino-real">{{ $remision->destino_real ?: '—' }}</span>
                                    @if($remision->es_redireccion)
                                        <br><span class="badge badge-warning">
                                            <i class="fas fa-random mr-1"></i>REDIRIGIDO
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="td-codigo-variante">
                                        {{ $remision->codigo_visual ?? $remision->codigo ?? '—' }}
                                    </span>
                                </td>
                                <td>{{ $detalle->fecha_logistica ? \Carbon\Carbon::parse($detalle->fecha_logistica)->format('d/m/Y') : '—' }}</td>
                                <td>
                                    <span class="td-remision">{{ $remision->serie }}-{{ $remision->numero_remision }}</span>
                                    <br><small class="text-muted">
                                        {{ $remision->fecha_remision ? \Carbon\Carbon::parse($remision->fecha_remision)->format('d/m/Y') : '—' }}
                                    </small>
                                </td>
                                <td class="text-right"><span class="td-cantidad">{{ number_format($remision->cantidad, 0, ',', '.') }}</span></td>
                                <td class="text-right">
                                    <span class="{{ $remision->fecha_recepcion ? 'td-confirmado' : 'text-muted font-weight-bold' }}">
                                        {{ number_format($remision->fecha_recepcion ? $remision->cantidad : 0, 0, ',', '.') }}
                                    </span>
                                </td>
                                @php
                                    $cantidadValorizadaLinea = (int) (
                                        $remision->cantidad_efectiva_valorizada
                                        ?? 0
                                    );

                                    $cantidadAuditoriaLinea = max(
                                        0,
                                        (int) $remision->cantidad
                                            - $cantidadValorizadaLinea
                                    );

                                    $costoLinea = isset(
                                        $remision->costo_efectivo_visual
                                    )
                                        ? (float) $remision
                                            ->costo_efectivo_visual
                                        : $cantidadValorizadaLinea
                                            * (float) $remision
                                                ->costo_unitario;

                                    $ventaLinea = isset(
                                        $remision->venta_efectiva_visual
                                    )
                                        ? (float) $remision
                                            ->venta_efectiva_visual
                                        : $cantidadValorizadaLinea
                                            * (float) $remision
                                                ->precio_venta;

                                    $costoUnitarioVisual =
                                        $remision
                                            ->costo_unitario_uniforme
                                        ?? $remision->costo_unitario
                                        ?? null;

                                    $precioUnitarioVisual =
                                        $remision
                                            ->precio_venta_uniforme
                                        ?? $remision->precio_venta
                                        ?? null;
                                @endphp
                                <td class="text-right">
                                    <span class="td-money">
                                        Gs {{ number_format($costoLinea, 0, ',', '.') }}
                                        <small>
                                            @if(
                                                !empty($remision->es_agrupacion_ayala)
                                                && $remision->costo_unitario_uniforme === null
                                            )
                                                valores por variante
                                            @elseif($costoUnitarioVisual !== null)
                                                Gs {{ number_format((float) $costoUnitarioVisual, 0, ',', '.') }}/u
                                            @endif

                                            · {{ number_format($cantidadValorizadaLinea,0,',','.') }} efectivas

                                            @if($cantidadAuditoriaLinea > 0)
                                                · {{ number_format($cantidadAuditoriaLinea,0,',','.') }} auditoría
                                            @endif
                                        </small>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <span class="td-money text-primary">
                                        Gs {{ number_format($ventaLinea, 0, ',', '.') }}
                                        <small>
                                            @if(
                                                !empty($remision->es_agrupacion_ayala)
                                                && $remision->precio_venta_uniforme === null
                                            )
                                                valores por variante
                                            @elseif($precioUnitarioVisual !== null)
                                                Gs {{ number_format((float) $precioUnitarioVisual, 0, ',', '.') }}/u
                                            @endif

                                            · {{ number_format($cantidadValorizadaLinea,0,',','.') }} efectivas

                                            @if($cantidadAuditoriaLinea > 0)
                                                · {{ number_format($cantidadAuditoriaLinea,0,',','.') }} auditoría
                                            @endif
                                        </small>
                                    </span>
                                </td>
                                <td>{{ $remision->fecha_recepcion ? \Carbon\Carbon::parse($remision->fecha_recepcion)->format('d/m/Y') : 'Pendiente' }}</td>
                                <td>
                                    @if($remision->fecha_recepcion)
                                        <span class="badge badge-success">RECIBIDO</span>
                                    @else
                                        <span class="badge badge-primary">EN TRÁNSITO</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                        <tr class="td-subtotal-row">
                            <td colspan="11">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div>
                                        <span class="td-plan-badge">{{ $detalle->sucursal }}</span>
                                        @if($destinoGrupo && strtoupper($destinoGrupo) !== strtoupper($detalle->sucursal))
                                            <span class="text-muted ml-2">→ {{ $destinoGrupo }}</span>
                                        @endif
                                    </div>
                                    <div class="td-subtotal-box">
                                        <span class="td-subtotal-item">
                                            <span class="td-subtotal-label">Plan</span>
                                            <span class="td-subtotal-value">{{ number_format($detalle->cantidad, 0, ',', '.') }}</span>
                                        </span>
                                        <span class="td-subtotal-item">
                                            <span class="td-subtotal-label">Remitido</span>
                                            <span class="td-subtotal-value">{{ number_format($subtotalLocal, 0, ',', '.') }}</span>
                                        </span>
                                        <span class="td-subtotal-item">
                                            <span class="td-subtotal-label">Confirmado</span>
                                            <span class="td-subtotal-value text-success">{{ number_format($subtotalConfirmado, 0, ',', '.') }}</span>
                                        </span>
                                        <span class="td-subtotal-item">
                                            <span class="td-subtotal-label">Pendiente</span>
                                            <span class="td-subtotal-value {{ $detalle->pendiente_remitir > 0 ? 'text-warning' : 'text-success' }}">
                                                {{ number_format($detalle->pendiente_remitir, 0, ',', '.') }}
                                            </span>
                                        </span>
                                        <span class="td-subtotal-item">
                                            <span class="td-subtotal-label">Costo efectivo</span>
                                            <span class="td-subtotal-value">
                                                Gs {{ number_format($detalle->costo_remitido, 0, ',', '.') }}
                                                <small class="text-muted">
                                                    ({{ number_format($detalle->cantidad_valorizada,0,',','.') }} prendas)
                                                </small>
                                            </span>
                                        </span>
                                        <span class="td-subtotal-item">
                                            <span class="td-subtotal-label">Venta efectiva</span>
                                            <span class="td-subtotal-value text-primary">
                                                Gs {{ number_format($detalle->venta_remitida, 0, ',', '.') }}
                                                <small class="text-muted">
                                                    ({{ number_format($detalle->cantidad_valorizada,0,',','.') }} prendas)
                                                </small>
                                            </span>
                                        </span>
                                        <span class="td-subtotal-item">
                                            <span class="td-subtotal-label">Margen</span>
                                            <span class="td-subtotal-value text-success">
                                                Gs {{ number_format($detalle->margen_bruto, 0, ',', '.') }}
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">
                            Esta OT todavía no tiene distribución logística.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($remisionesSinDetalle->isNotEmpty())
        <div class="alert alert-warning mt-3 mb-0">
            <div class="font-weight-bold mb-2">
                <i class="fas fa-unlink mr-1"></i>
                Remisiones asociadas a la OT pero todavía sin asignación logística
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered bg-white mb-0">
                    <thead>
                        <tr>
                            <th>Destino real</th>
                            <th>Código variante</th>
                            <th>Remisión</th>
                            <th class="text-right">Cantidad</th>
                            <th class="text-right">Costo</th>
                            <th class="text-right">Venta</th>
                            <th>Fecha remisión</th>
                            <th>Recepción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($remisionesSinDetalle as $remision)
                            <tr>
                                <td>
                                    {{ $remision->sucursal_destino ?: $remision->sucursal_logistica }}
                                    <br><small class="text-muted">Cod. {{ $remision->cod_sucursal_destino }}</small>
                                </td>
                                <td><code class="font-weight-bold">{{ $remision->codigo ?: '—' }}</code></td>
                                <td><strong>{{ $remision->serie }}-{{ $remision->numero_remision }}</strong></td>
                                <td class="text-right">{{ number_format($remision->cantidad, 0, ',', '.') }}</td>
                                <td class="text-right">
                                    Gs {{ number_format(((float) $remision->cantidad) * ((float) $remision->costo_unitario), 0, ',', '.') }}
                                </td>
                                <td class="text-right">
                                    Gs {{ number_format(((float) $remision->cantidad) * ((float) $remision->precio_venta), 0, ',', '.') }}
                                </td>
                                <td>{{ $remision->fecha_remision ? \Carbon\Carbon::parse($remision->fecha_remision)->format('d/m/Y') : '—' }}</td>
                                <td>
                                    @if($remision->fecha_recepcion)
                                        <span class="badge badge-success">
                                            {{ \Carbon\Carbon::parse($remision->fecha_recepcion)->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="badge badge-primary">EN TRÁNSITO</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>