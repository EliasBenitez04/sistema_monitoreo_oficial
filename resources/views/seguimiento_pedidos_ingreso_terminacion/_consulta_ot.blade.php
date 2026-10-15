{{-- Consulta independiente del calendario: movimientos reales por OT, sin alterar KPI IT. --}}
<div id="resultado-busqueda-ot" class="mb-3">
    @if($errorOt)
        <div class="alert alert-warning it-day-alert mb-0">
            <i class="fas fa-exclamation-triangle mr-1"></i>{{ $errorOt }}
        </div>
    @elseif($resultadoOt)
        @php
            $consulta = $resultadoOt;
            $ot = $consulta->ot;
            $conc = $consulta->conciliacion;
            $ultimoProceso = $consulta->ultimo_proceso;
            $ultimaRemision = $consulta->ultima_remision;
        @endphp
        <div class="card it-day-card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <small class="text-primary font-weight-bold">SEGUIMIENTO COMPLETO DE ORDEN DE TRABAJO</small>
                    <h4 class="font-weight-bold mb-1 mt-1">
                        <i class="fas fa-route mr-1 text-primary"></i>
                        OT {{ $ot->nro_ot }}
                        <span class="badge badge-light border ml-1">{{ $ot->estado ?: 'Sin estado OT' }}</span>
                    </h4>
                    <div class="small text-muted">{{ $ot->codigo }} · {{ $ot->descripcion }}</div>
                </div>
                <a class="btn btn-outline-secondary btn-sm mt-2 mt-md-0"
                   href="{{ route('seguimiento-ingreso-terminacion.seguimiento-diario', [
                       'fecha' => $fechaControl->format('Y-m-d'),
                       'mes' => $mesControl->format('Y-m')
                   ]) }}">
                    <i class="fas fa-times mr-1"></i>Limpiar búsqueda
                </a>
            </div>

            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-lg-4 col-md-6 mb-2">
                        <div class="it-ot-search-kpi">
                            <small>ETAPA MÁS RECIENTE DE PRODUCCIÓN</small>
                            <strong class="it-ot-search-stage">{{ $ultimoProceso ? $ultimoProceso->proceso : 'SIN TRAZABILIDAD' }}</strong>
                            <span>
                                {{ $ultimoProceso && $ultimoProceso->fecha_proceso
                                    ? \Carbon\Carbon::parse($ultimoProceso->fecha_proceso)->format('d/m/Y H:i')
                                    : 'Sin movimiento de producción registrado' }}
                            </span>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-2">
                        <div class="it-ot-search-kpi">
                            <small>INGRESO A TERMINACIÓN (T-T)</small>
                            <strong>{{ $consulta->primera_terminacion ? 'REGISTRADO' : 'SIN REGISTRO' }}</strong>
                            <span>{{ $consulta->primera_terminacion
                                ? \Carbon\Carbon::parse($consulta->primera_terminacion->fecha_proceso)->format('d/m/Y')
                                : 'No se registró TERMINACION - TERMINACION' }}</span>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-12 mb-2">
                        <div class="it-ot-search-kpi">
                            <small>ÚLTIMO DOCUMENTO DE MOVIMIENTO</small>
                            <strong class="it-ot-search-stage">
                                {{ $ultimaRemision ? $ultimaRemision->serie . '-' . $ultimaRemision->numero_remision : 'SIN REMISIÓN' }}
                            </strong>
                            <span>
                                @if($ultimaRemision)
                                    {{ $ultimaRemision->sucursal_salida ?: 'Origen sin nombre' }}
                                    <i class="fas fa-arrow-right mx-1"></i>
                                    {{ $ultimaRemision->sucursal_destino ?: 'Destino sin nombre' }}
                                @else
                                    No hay movimientos de remisión vinculados o identificados inequívocamente
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-lg col-6 mb-2">
                        <div class="it-ot-search-kpi">
                            <small>CANTIDAD OT</small>
                            <strong>{{ number_format($ot->cantidad_orden,0,',','.') }}</strong>
                            <span>Plan original</span>
                        </div>
                    </div>
                    <div class="col-lg col-6 mb-2">
                        <div class="it-ot-search-kpi">
                            <small>PRODUCTO TERMINADO</small>
                            <strong>{{ number_format($conc->producto_terminado ?? 0,0,',','.') }}</strong>
                            <span>Acumulado reconocido</span>
                        </div>
                    </div>
                    <div class="col-lg col-6 mb-2">
                        <div class="it-ot-search-kpi">
                            <small>SALIDA NETA</small>
                            <strong>{{ number_format($conc->remitido_original ?? 0,0,',','.') }}</strong>
                            <span>Descontando retornos recibidos</span>
                        </div>
                    </div>
                    <div class="col-lg col-6 mb-2">
                        <div class="it-ot-search-kpi">
                            <small>RECIBIDO NETO</small>
                            <strong>{{ number_format($conc->recibido_original ?? 0,0,',','.') }}</strong>
                            <span>Confirmación de destino</span>
                        </div>
                    </div>
                    <div class="col-lg col-6 mb-2">
                        <div class="it-ot-search-kpi">
                            <small>PENDIENTE / TRÁNSITO</small>
                            <strong>{{ number_format($conc->pendiente_real_salida ?? 0,0,',','.') }}
                                / {{ number_format($conc->en_transito ?? 0,0,',','.') }}</strong>
                            <span>Sin salida / en tránsito a locales</span>
                        </div>
                    </div>
                </div>

                <div class="border rounded p-3 mb-3">
                    <strong class="d-block mb-2"><i class="fas fa-clipboard-list text-primary mr-1"></i>Pedidos IT asociados</strong>
                    @forelse($consulta->pedidos as $pedidoOt)
                        <a class="btn btn-sm mb-1 {{ $pedidoOt->vigente ? 'btn-outline-success' : 'btn-outline-secondary' }}"
                           href="{{ route('seguimiento-ingreso-terminacion.show', ['id' => $pedidoOt->id]) }}">
                            <strong>{{ $pedidoOt->nro_pedido }}</strong>
                            · {{ $pedidoOt->fecha_pedido ? \Carbon\Carbon::parse($pedidoOt->fecha_pedido)->format('d/m/Y') : 'Sin fecha' }}
                            · {{ $pedidoOt->vigente ? 'VIGENTE' : 'ANULADO / REEMPLAZADO' }}
                        </a>
                    @empty
                        <span class="badge badge-warning p-2">SIN PEDIDO IT ASOCIADO</span>
                    @endforelse
                    <div class="text-muted small mt-1">
                        La asociación con pedidos IT es histórica; una salida anterior a la fecha del pedido no se considera justificada por ese pedido.
                    </div>
                </div>

                <div class="border rounded p-3 mb-3 bg-light">
                    <strong class="d-block mb-2"><i class="fas fa-map-marker-alt text-primary mr-1"></i>¿Dónde está la OT?</strong>
                    <div class="row small">
                        <div class="col-md-6 mb-2">
                            <strong>Última etapa registrada:</strong>
                            {{ $ultimoProceso ? $ultimoProceso->proceso : 'Sin trazabilidad' }}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Saldo teórico pendiente en Casa Central:</strong>
                            {{ number_format($conc->stock_central_disponible ?? 0,0,',','.') }}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Devoluciones recibidas en Central:</strong>
                            {{ number_format($conc->devoluciones_recibidas ?? 0,0,',','.') }}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Devoluciones en camino a Central:</strong>
                            {{ number_format($conc->devoluciones_en_transito ?? 0,0,',','.') }}
                        </div>
                    </div>
                    <div class="text-muted small">
                        Una OT puede distribuirse entre varias sucursales. La ubicación física exacta se determina por las remisiones y recepciones de cada línea, no por el nombre de la última etapa ni del último documento.
                        El saldo Central es teórico y no sustituye el inventario físico por talle y color.
                    </div>
                    @if($consulta->sin_vinculo_recuperado > 0)
                        <div class="text-info small mt-2">
                            <i class="fas fa-link mr-1"></i>
                            {{ $consulta->sin_vinculo_recuperado }} línea(s) de remisión sin id_ot vinculada(s) visualmente por código base único; no se modificó la base de datos.
                        </div>
                    @endif
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                    <div>
                        <strong><i class="fas fa-history text-primary mr-1"></i>Todos los movimientos registrados</strong>
                        <div class="text-muted small">
                            {{ number_format($consulta->movimientos->count(),0,',','.') }} eventos cronológicos.
                            Se muestran procesos, distribuciones planificadas, remisiones, traslados, devoluciones y recepciones.
                        </div>
                    </div>
                    <span class="badge badge-light border p-2 mt-2 mt-md-0">
                        Origen: trazabilidad + plan + ENVIOS
                    </span>
                </div>

                <div class="table-responsive it-ot-search-history">
                    <table class="table table-sm table-hover table-striped it-day-table mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Proceso / movimiento</th>
                                <th>Código / origen → destino</th>
                                <th class="text-right">Cantidad</th>
                                <th>Documento</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($consulta->movimientos as $mov)
                                @php
                                    $tipoClase = [
                                        'PROCESO' => 'secondary',
                                        'PLAN LOGÍSTICO' => 'info',
                                        'REMISIÓN CENTRAL' => 'primary',
                                        'DEVOLUCIÓN' => 'warning',
                                        'TRASLADO' => 'dark',
                                        'RECEPCIÓN' => 'success',
                                    ][$mov->tipo] ?? 'light';
                                @endphp
                                <tr>
                                    <td class="text-nowrap">
                                        {{ $mov->fecha ? \Carbon\Carbon::parse($mov->fecha)->format('d/m/Y H:i') : 'Sin fecha' }}
                                    </td>
                                    <td><span class="badge badge-{{ $tipoClase }}">{{ $mov->tipo }}</span></td>
                                    <td><strong>{{ $mov->detalle }}</strong></td>
                                    <td><small class="text-muted">{{ $mov->informacion }}</small></td>
                                    <td class="text-right font-weight-bold">{{ number_format($mov->cantidad,0,',','.') }}</td>
                                    <td class="text-nowrap">{{ $mov->documento ?: '—' }}</td>
                                    <td>
                                        @if($mov->estado === 'RECIBIDO')
                                            <span class="badge badge-success">RECIBIDO</span>
                                        @elseif($mov->estado === 'EN TRÁNSITO')
                                            <span class="badge badge-warning">EN TRÁNSITO</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center py-4 text-muted">No hay movimientos registrados para esta OT.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted mt-2 mb-0">
                    Importante: los eventos de proceso, plan, despacho y recepción representan etapas distintas.
                    No deben sumarse entre sí; una misma prenda puede aparecer en varios movimientos, incluidas las devoluciones y los reenvíos.
                </p>
            </div>
        </div>
    @endif
</div>
