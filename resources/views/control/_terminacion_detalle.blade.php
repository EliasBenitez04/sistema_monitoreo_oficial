<style>
    .td-codigo-variante{font-size:15px;font-weight:800;color:#111!important;letter-spacing:.02em}
    .td-subtotal-local{font-size:15px;font-weight:800;color:#26364a}
    .td-subtotal-row{background:#f8fafc}
</style>
<div class="p-2">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h5 class="mb-1">OT {{ $ot->nro_ot }} · {{ $ot->codigo }}</h5>
            <div class="text-muted">{{ $ot->descripcion }}</div>
        </div>
        <span class="badge badge-light border px-3 py-2">
            Orden: {{ number_format($ot->cantidad_orden, 0, ',', '.') }}
        </span>
    </div>

    <div class="row mb-3">
        <div class="col-md col-6 mb-2"><div class="border rounded p-2 h-100">
            <small class="text-muted d-block">Plan logística</small>
            <strong>{{ number_format($totalPlan, 0, ',', '.') }}</strong>
        </div></div>
        <div class="col-md col-6 mb-2"><div class="border rounded p-2 h-100">
            <small class="text-muted d-block">Remitido</small>
            <strong>{{ number_format($totalRemitido, 0, ',', '.') }}</strong>
        </div></div>
        <div class="col-md col-6 mb-2"><div class="border rounded p-2 h-100">
            <small class="text-muted d-block">Recibido</small>
            <strong>{{ number_format($totalRecibido, 0, ',', '.') }}</strong>
        </div></div>
        <div class="col-md col-6 mb-2"><div class="border rounded p-2 h-100">
            <small class="text-muted d-block">En tránsito</small>
            <strong>{{ number_format($totalEnTransito, 0, ',', '.') }}</strong>
        </div></div>
        <div class="col-md col-6 mb-2"><div class="border rounded p-2 h-100">
            <small class="text-muted d-block">Pendiente remitir</small>
            <strong>{{ number_format($totalPendiente, 0, ',', '.') }}</strong>
        </div></div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Destino real</th>
                    <th>Código variante</th>
                    <th>Salida</th>
                    <th>Remisión</th>
                    <th class="text-right">Remitido</th>
                    <th>Recepción</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($detalles as $detalle)
                    @if($detalle->remisiones->isEmpty())
                        <tr>
                            <td><strong>{{ $detalle->sucursal }}</strong></td>
                            <td class="text-muted">—</td>
                            <td>{{ $detalle->fecha_logistica ? \Carbon\Carbon::parse($detalle->fecha_logistica)->format('d/m/Y') : '—' }}</td>
                            <td colspan="3" class="text-muted">Todavía sin remisión vinculada.</td>
                            <td><span class="badge badge-secondary">SIN REMISIÓN</span></td>
                        </tr>
                    @else
                        @php
                            $subtotalLocal = (int) $detalle->remisiones->sum('cantidad');
                            $destinoGrupo = optional($detalle->remisiones->first())->destino_real ?: $detalle->sucursal;
                        @endphp

                        @foreach($detalle->remisiones as $remision)
                            <tr>
                                <td>
                                    <strong>{{ $remision->destino_real ?: '—' }}</strong>
                                    @if($remision->es_redireccion)
                                        <br><span class="badge badge-warning">
                                            <i class="fas fa-random mr-1"></i>REDIRIGIDO
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="td-codigo-variante">{{ $remision->codigo ?: '—' }}</span>
                                </td>
                                <td>{{ $detalle->fecha_logistica ? \Carbon\Carbon::parse($detalle->fecha_logistica)->format('d/m/Y') : '—' }}</td>
                                <td>
                                    <strong>{{ $remision->serie }}-{{ $remision->numero_remision }}</strong>
                                    <br><small class="text-muted">
                                        {{ $remision->fecha_remision ? \Carbon\Carbon::parse($remision->fecha_remision)->format('d/m/Y') : '—' }}
                                    </small>
                                </td>
                                <td class="text-right"><strong>{{ number_format($remision->cantidad, 0, ',', '.') }}</strong></td>
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
                            <td colspan="4" class="text-right">
                                <strong>Subtotal {{ $destinoGrupo }}:</strong>
                            </td>
                            <td class="text-right">
                                <span class="td-subtotal-local">{{ number_format($subtotalLocal, 0, ',', '.') }}</span>
                            </td>
                            <td colspan="2">
                                @if($detalle->pendiente_remitir > 0)
                                    <small class="text-warning">
                                        Pendiente {{ number_format($detalle->pendiente_remitir, 0, ',', '.') }}
                                    </small>
                                @else
                                    <small class="text-success">Plan completo</small>
                                @endif
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
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