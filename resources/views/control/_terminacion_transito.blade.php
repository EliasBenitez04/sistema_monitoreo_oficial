{{-- Desglose de En tránsito. El KPI neto y las líneas documentales brutas son dimensiones distintas. --}}
<div class="modal fade" id="modalEnTransitoTerminacion" tabindex="-1" role="dialog"
     aria-labelledby="tituloEnTransitoTerminacion" aria-hidden="true">
    <div class="modal-dialog" role="document" style="width:96vw;max-width:1800px">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title font-weight-bold" id="tituloEnTransitoTerminacion">
                        <i class="fas fa-shipping-fast text-primary mr-2"></i>
                        Remisiones en tránsito · pendientes de recepción
                    </h5>
                    <small class="text-muted">
                        OT seleccionadas por los filtros del Control Terminación · {{ $fechaDesde }} al {{ $fechaHasta }}.
                        Se incluyen los movimientos posteriores a septiembre si pertenecen a las OT filtradas.
                    </small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-2">
                    <div class="col-lg-3 col-md-6 mb-2">
                        <div class="border rounded bg-light p-3">
                            <small class="d-block text-muted">OT CON TRÁNSITO</small>
                            <h4 class="font-weight-bold mb-0">{{ number_format($detalleEnTransitoOt->count(),0,',','.') }}</h4>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-2">
                        <div class="border rounded bg-light p-3">
                            <small class="d-block text-muted">PRENDAS EN TRÁNSITO SEGÚN KPI</small>
                            <h4 class="font-weight-bold text-primary mb-0">{{ number_format($totalEnTransito,0,',','.') }}</h4>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-2">
                        <div class="border rounded bg-light p-3">
                            <small class="d-block text-muted">LÍNEAS ENVIOS SIN RECEPCIÓN</small>
                            <h4 class="font-weight-bold mb-0">{{ number_format($remisionesEnTransito->count(),0,',','.') }}</h4>
                            <small class="text-muted">{{ number_format($totalDocumentalEnTransito,0,',','.') }} prendas documentales brutas</small>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-2">
                        <div class="border rounded bg-light p-3">
                            <small class="d-block text-muted">CONTROL DOCUMENTAL VS KPI</small>
                            <h5 class="font-weight-bold mb-0 {{ (int)$totalDocumentalEnTransito === (int)$totalEnTransito ? 'text-success' : 'text-warning' }}">
                                {{ (int)$totalDocumentalEnTransito === (int)$totalEnTransito ? 'COINCIDE' : 'REVISAR DIFERENCIA' }}
                            </h5>
                            <small class="text-muted">
                                Diferencia documental:
                                {{ number_format((int)$totalDocumentalEnTransito - (int)$totalEnTransito,0,',','.') }}
                            </small>
                        </div>
                    </div>
                </div>

                <div class="alert alert-light border py-2 small">
                    <i class="fas fa-info-circle text-info mr-1"></i>
                    <strong>KPI En tránsito</strong> = salida efectiva neta menos recepción efectiva neta, calculada por OT.
                    <strong>Documentos sin recepción</strong> = líneas de ENVIOS pendientes de fecha_recepcion.
                    Pueden diferir por topes de Producto Terminado, redistribuciones, devoluciones o vínculos faltantes.
                    No se inventan remisiones ni se modifica el KPI para hacerlos coincidir.
                </div>

                <h6 class="font-weight-bold mb-2">
                    <i class="fas fa-clipboard-list mr-1 text-primary"></i>
                    Resumen de OT pendientes de confirmación
                </h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered table-striped ct-table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>OT</th>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th class="text-right">En tránsito KPI</th>
                                <th class="text-right">Sin recepción ENVIOS</th>
                                <th class="text-right">Diferencia</th>
                                <th>Seguimiento</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($detalleEnTransitoOt as $otTransito)
                                @php
                                    $lineasOt = $remisionesEnTransitoPorOt->get($otTransito->id_ot, collect());
                                    $cantidadBruta = (int) $lineasOt->sum('cantidad');
                                    $diferenciaOt = $cantidadBruta - (int) $otTransito->en_transito;
                                @endphp
                                <tr>
                                    <td><strong>{{ $otTransito->nro_ot }}</strong></td>
                                    <td><code>{{ $otTransito->codigo }}</code></td>
                                    <td>{{ $otTransito->descripcion }}</td>
                                    <td class="text-right font-weight-bold text-primary">
                                        {{ number_format($otTransito->en_transito,0,',','.') }}
                                    </td>
                                    <td class="text-right">{{ number_format($cantidadBruta,0,',','.') }}</td>
                                    <td class="text-right {{ $diferenciaOt === 0 ? 'text-success' : 'text-warning font-weight-bold' }}">
                                        {{ $diferenciaOt === 0 ? '0' : number_format($diferenciaOt,0,',','.') }}
                                    </td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="{{ route('control.terminacion', [
                                               'fecha_desde' => $fechaDesde,
                                               'fecha_hasta' => $fechaHasta,
                                               'buscar' => (string)$otTransito->nro_ot
                                           ]) }}">
                                            Ver OT
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-3">No hay OT en tránsito con estos filtros.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-light font-weight-bold">
                            <tr>
                                <td colspan="3">TOTAL</td>
                                <td class="text-right text-primary">{{ number_format($totalEnTransito,0,',','.') }}</td>
                                <td class="text-right">{{ number_format($totalDocumentalEnTransito,0,',','.') }}</td>
                                <td class="text-right">{{ number_format((int)$totalDocumentalEnTransito - (int)$totalEnTransito,0,',','.') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <h6 class="font-weight-bold mb-2">
                    <i class="fas fa-file-invoice text-primary mr-1"></i>
                    Documentos originales sin fecha de recepción
                </h6>
                <p class="text-muted small mb-2">
                    Ordenados por antigüedad: primero los que llevan más días sin confirmación.
                    Si falta fecha de remisión se usa la fecha de creación y se señala expresamente.
                    Cada fila corresponde a una línea de artículo, no necesariamente a un documento completo.
                </p>
                <div class="table-responsive" style="max-height:65vh;overflow:auto">
                    <table class="table table-sm table-striped table-hover ct-table mb-0">
                        <thead class="thead-light" style="position:sticky;top:0;z-index:2">
                            <tr>
                                <th>OT</th>
                                <th>Remisión</th>
                                <th>Fecha salida</th>
                                <th class="text-center">Días en tránsito</th>
                                <th>Origen</th>
                                <th>Destino</th>
                                <th>Movimiento</th>
                                <th>Vínculo OT</th>
                                <th>Código completo</th>
                                <th>Artículo</th>
                                <th class="text-right">Cantidad</th>
                                <th>Recepción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($remisionesEnTransito as $remision)
                                <tr>
                                    <td><strong>{{ $remision->nro_ot }}</strong></td>
                                    <td class="text-nowrap"><strong>{{ $remision->serie }}-{{ $remision->numero_remision }}</strong></td>
                                    <td class="text-nowrap">
                                        {{ $remision->fecha_documento ? \Carbon\Carbon::parse($remision->fecha_documento)->format('d/m/Y') : 'Sin fecha' }}
                                        @if(!$remision->fecha_remision && $remision->fecha_creacion)
                                            <small class="d-block text-warning">Fecha creación (sin remisión)</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($remision->dias_transito !== null)
                                            <span class="badge p-2 {{ $remision->dias_transito >= 15 ? 'badge-danger' : ($remision->dias_transito >= 7 ? 'badge-warning' : 'badge-secondary') }}">
                                                {{ $remision->dias_transito }} días
                                            </span>
                                        @else
                                            <span class="text-muted">Sin fecha</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $remision->sucursal_salida ?: 'Sin nombre' }}
                                        <small class="d-block text-muted">Cod. {{ $remision->cod_sucursal_salida }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ $remision->destino_real ?: 'No identificado' }}</strong>
                                        <small class="d-block text-muted">Cod. {{ $remision->cod_sucursal_destino }}</small>
                                    </td>
                                    <td><small>{{ $remision->tipo_movimiento }}</small></td>
                                    <td>
                                        @if($remision->vinculo_ot === 'Por código único')
                                            <span class="badge badge-info" title="Remisión importada sin id_ot. Código base único; vínculo de consulta, no guardado en la BD.">Código único</span>
                                        @else
                                            <small class="text-muted">ID OT</small>
                                        @endif
                                    </td>
                                    <td><code>{{ $remision->codigo }}</code></td>
                                    <td>{{ $remision->descripcion }}</td>
                                    <td class="text-right font-weight-bold">{{ number_format($remision->cantidad,0,',','.') }}</td>
                                    <td><span class="badge badge-warning">SIN CONFIRMAR</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center text-muted py-4">
                                        No se localizaron documentos vinculados sin fecha_recepcion para las OT seleccionadas.
                                        Revisar remisiones sin id_ot o movimientos de cierre entre locales.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-light font-weight-bold">
                            <tr>
                                <td colspan="10">TOTAL DOCUMENTAL SIN RECEPCIÓN</td>
                                <td class="text-right">{{ number_format($totalDocumentalEnTransito,0,',','.') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <p class="small text-muted mb-0 mt-2">
                    Remisión y recepción son eventos distintos. La falta de fecha_recepcion significa
                    que la recepción no está confirmada en ENVIOS; no prueba por sí sola
                    dónde se encuentra físicamente la mercadería. Este reporte es de solo lectura.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
