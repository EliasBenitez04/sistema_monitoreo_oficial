@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div>
                <h1 class="mb-1"><i class="fas fa-truck-loading text-primary mr-2"></i>Monitoreo de Mayoristas</h1>
                <p class="text-muted mb-0">Asignaciones, envíos, recepciones y reserva estimada para fotografías de MODELO MUESTRA.</p>
            </div>
            <a href="{{ route('ventas.index') }}" class="btn btn-outline-primary mt-2 mt-md-0">
                <i class="fas fa-arrow-left mr-1"></i>Volver a Ventas
            </a>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">
    <div class="alert alert-info border-0 mb-3" role="note">
        <strong><i class="fas fa-info-circle mr-1"></i>Cómo se interpreta este control:</strong>
        <strong>Remitido</strong> significa que existe una remisión emitida;
        <strong>Recibido</strong> requiere fecha de recepción;
        <strong>reservado para fotos</strong> es una estimación según la regla elegida,
        y <strong>potencial para venta</strong> NO significa que esas prendas ya se vendieron.
        No se clasifican automáticamente como mayoristas las operaciones de COMERCIAL MATRIZ.
    </div>

    <div class="card card-outline card-primary mb-3">
        <div class="card-header bg-white">
            <h3 class="card-title font-weight-bold"><i class="fas fa-sliders-h mr-1"></i>Consulta de mayoristas</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('ventas.mayoristas.index') }}" method="GET">
                <div class="row align-items-end">
                    <div class="col-xl-2 col-md-3 col-6 mb-2">
                        <label for="desdeMay" class="small font-weight-bold">Remisiones desde</label>
                        <input id="desdeMay" type="date" class="form-control" name="desde" value="{{ $desde }}">
                    </div>
                    <div class="col-xl-2 col-md-3 col-6 mb-2">
                        <label for="hastaMay" class="small font-weight-bold">Remisiones hasta</label>
                        <input id="hastaMay" type="date" class="form-control" name="hasta" value="{{ $hasta }}">
                    </div>
                    <div class="col-xl-2 col-md-3 mb-2">
                        <label for="destinoMay" class="small font-weight-bold">Canal</label>
                        <select name="destino" id="destinoMay" class="form-control">
                            <option value="todos" {{ $tipo === 'todos' ? 'selected' : '' }}>Todos los mayoristas identificados</option>
                            <option value="modelo" {{ $tipo === 'modelo' ? 'selected' : '' }}>MODELO MUESTRA</option>
                            <option value="mayorista" {{ $tipo === 'mayorista' ? 'selected' : '' }}>Otros marcados MAYORISTA</option>
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-3 mb-2">
                        <label for="muestrasMay" class="small font-weight-bold">Reserva foto por OT</label>
                        <select name="muestras_por_ot" id="muestrasMay" class="form-control">
                            @foreach([0,1,2,3,4,5] as $valor)
                                <option value="{{ $valor }}" {{ $muestrasPorOt === $valor ? 'selected' : '' }}>
                                    {{ $valor }} {{ $valor === 1 ? 'prenda' : 'prendas' }}
                                </option>
                            @endforeach
                            @if($muestrasPorOt > 5)
                                <option value="{{ $muestrasPorOt }}" selected>{{ $muestrasPorOt }} prendas</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-6 mb-2">
                        <label for="buscarMay" class="small font-weight-bold">OT, código o artículo</label>
                        <input id="buscarMay" type="text" name="buscar" class="form-control" value="{{ $buscar }}" placeholder="Ej. 30443">
                    </div>
                    <div class="col-xl-2 col-md-6 mb-2">
                        <button class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i>Consultar</button>
                    </div>
                </div>
            </form>
            <small class="text-muted">
                Las fechas filtran <strong>remisiones emitidas</strong>. El plan asignado se muestra como
                <strong>histórico de la OT</strong>, incluso cuando se registró antes del período.
                La reserva de una prenda por OT es una regla configurable para estimación, no una confirmación fotográfica.
            </small>
        </div>
    </div>

    @if(!$tablas)
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-1"></i>No existen todas las tablas de logística necesarias. Este control no puede calcular cantidades hasta disponer de ellas.</div>
    @endif

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-3"><div class="may-stat">
            <small>Enviado a los destinos</small>
            <strong>{{ number_format($resumen->remitido,0,',','.') }}</strong>
            <span>prendas con remisión del período</span>
        </div></div>
        <div class="col-xl-3 col-md-6 mb-3"><div class="may-stat is-success">
            <small>Recepción confirmada</small>
            <strong>{{ number_format($resumen->recibido,0,',','.') }}</strong>
            <span>prendas de esos envíos con fecha de recepción</span>
        </div></div>
        <div class="col-xl-3 col-md-6 mb-3"><div class="may-stat is-warning">
            <small>En tránsito</small>
            <strong>{{ number_format($resumen->en_transito,0,',','.') }}</strong>
            <span>remitidas sin recepción confirmada</span>
        </div></div>
        <div class="col-xl-3 col-md-6 mb-3"><div class="may-stat">
            <small>Órdenes vinculadas</small>
            <strong>{{ number_format($resumen->ot,0,',','.') }}</strong>
            <span>OT distintas; no movimientos repetidos</span>
        </div></div>
    </div>

    <div class="row">
        <div class="col-xl-4 col-md-6 mb-3"><div class="may-stat">
            <small>Asignación logística histórica</small>
            <strong>{{ number_format($resumen->asignado_historico,0,',','.') }}</strong>
            <span>unidades del plan mayorista, sin filtro de fecha</span>
        </div></div>
        <div class="col-xl-4 col-md-6 mb-3"><div class="may-stat is-photo">
            <small>Reserva estimada para fotos</small>
            <strong>{{ number_format($resumen->muestra_estimada,0,',','.') }}</strong>
            <span>hasta {{ $muestrasPorOt }} por OT vinculada de MODELO MUESTRA</span>
        </div></div>
        <div class="col-xl-4 col-md-6 mb-3"><div class="may-stat is-success">
            <small>Potencial para comercialización</small>
            <strong>{{ number_format($resumen->potencial_venta,0,',','.') }}</strong>
            <span>enviado vinculado menos reserva estimada; NO vendido</span>
        </div></div>
    </div>

    <div class="row">
        <div class="col-xl-4 col-md-6 mb-3"><div class="may-info">
            <small>Remisiones procedentes de redistribución</small>
            <strong>{{ number_format($resumen->traslados,0,',','.') }} prendas</strong>
            <span>Movimientos internos; no se interpretan como nuevas salidas desde Central.</span>
        </div></div>
        <div class="col-xl-4 col-md-6 mb-3"><div class="may-info">
            <small>Valor referencial de lo remitido</small>
            <strong>Gs {{ number_format($resumen->valor_referencial,0,',','.') }}</strong>
            <span>Cantidad × precio_venta de la remisión, cuando está informado. No es venta cobrada.</span>
        </div></div>
        <div class="col-xl-4 col-md-6 mb-3"><div class="may-info">
            <small>Costo referencial de lo remitido</small>
            <strong>Gs {{ number_format($resumen->costo_referencial,0,',','.') }}</strong>
            <span>Cantidad × costo_unitario de la remisión, cuando existe.</span>
        </div></div>
    </div>

    @if($resumen->sin_vinculo > 0)
        <div class="alert alert-warning">
            <strong><i class="fas fa-exclamation-triangle mr-1"></i>Vínculos pendientes:</strong>
            {{ number_format($resumen->sin_vinculo,0,',','.') }} prendas remitidas al canal no tienen una OT identificable.
            Se incluyen en remitido/recibido, pero <strong>no</strong> en potencial para comercializar ni en reserva para fotos.
            Revisá los documentos en la sección de auditoría.
        </div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white">
            <h3 class="card-title font-weight-bold"><i class="fas fa-camera text-primary mr-1"></i>Regla de MODELO MUESTRA</h3>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <p class="mb-2">Por ejemplo, si una OT envía <strong>13 prendas</strong> a MODELO MUESTRA y la regla es reservar <strong>1 prenda</strong> para fotografías:</p>
                    <div class="may-rule"><span>13 enviadas</span><i class="fas fa-minus"></i><span>1 foto (estimada)</span><i class="fas fa-equals"></i><strong>12 potenciales para venta</strong></div>
                </div>
                <div class="col-md-5">
                    <div class="alert alert-light border mb-0 small">
                        <strong>Control de interpretación:</strong> sin un registro de fotografías y sin comprobantes mayoristas vinculados, no sabemos si la muestra ya fue utilizada ni cuántas de las 12 se vendieron efectivamente.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white">
            <h3 class="card-title font-weight-bold"><i class="fas fa-file-invoice-dollar text-primary mr-1"></i>Ventas registradas explícitamente como mayoristas</h3>
        </div>
        <div class="card-body">
            @if($ventasMay)
                <div class="row">
                    <div class="col-md-4 mb-2"><div class="may-value"><small>Prendas netas</small><strong>{{ number_format($ventasMay->prendas_netas,0,',','.') }}</strong></div></div>
                    <div class="col-md-4 mb-2"><div class="may-value"><small>Venta neta</small><strong>Gs {{ number_format($ventasMay->importe_neto,0,',','.') }}</strong></div></div>
                    <div class="col-md-4 mb-2"><div class="may-value"><small>Comprobantes</small><strong>{{ number_format($ventasMay->comprobantes,0,',','.') }}</strong></div></div>
                </div>
                <p class="small text-muted mb-0 mt-2">Solo ventas donde el campo <strong>local</strong> indica explícitamente MODELO MUESTRA o MAYORISTA. Si ese canal factura fuera del importador actual, estos importes serán cero o incompletos. No se atribuyen ventas de COMERCIAL MATRIZ sin evidencia.</p>
            @else
                <p class="mb-0 text-muted">No existe la tabla de ventas para consultar facturación mayorista.</p>
            @endif
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white d-flex align-items-center justify-content-between flex-wrap">
            <h3 class="card-title float-none font-weight-bold mb-0">
                <i class="fas fa-list text-primary mr-1"></i>Control por orden de trabajo
            </h3>
            <small class="text-muted">{{ number_format($ordenes->total(),0,',','.') }} combinaciones OT/canal · fecha aplicada solo a remisiones</small>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 may-table">
                <thead class="thead-light">
                    <tr>
                        <th>OT / Código</th><th>Descripción / Canal</th>
                        <th class="text-right">Asignado histórico</th>
                        <th class="text-right">Remitido período</th>
                        <th class="text-right">Recibido</th>
                        <th class="text-right">En tránsito</th>
                        <th class="text-right">Reserva foto</th>
                        <th class="text-right">Potencial venta</th>
                        <th>Últ. remisión</th>
                        <th>Control</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ordenes as $item)
                    <tr class="{{ $item->sin_vinculo ? 'table-warning' : '' }}">
                        <td>
                            @if($item->nro_ot)
                                <strong>OT {{ $item->nro_ot }}</strong>
                            @else
                                <strong class="text-warning">SIN OT</strong>
                            @endif
                            <small class="d-block text-muted">{{ $item->codigo ?: 'Sin código' }}</small>
                        </td>
                        <td>{{ $item->descripcion ?: 'Sin descripción' }}<small class="d-block may-tag">{{ $item->canal }}</small></td>
                        <td class="text-right">{{ number_format($item->asignado,0,',','.') }}</td>
                        <td class="text-right font-weight-bold">{{ number_format($item->remitido,0,',','.') }}</td>
                        <td class="text-right text-success">{{ number_format($item->recibido,0,',','.') }}</td>
                        <td class="text-right {{ $item->en_transito > 0 ? 'text-warning font-weight-bold' : '' }}">{{ number_format($item->en_transito,0,',','.') }}</td>
                        <td class="text-right">{{ $item->sin_vinculo ? 'No clasificable' : number_format($item->muestra_estimada,0,',','.') }}</td>
                        <td class="text-right">{{ $item->sin_vinculo ? 'No clasificable' : number_format($item->potencial_venta,0,',','.') }}</td>
                        <td>{{ $item->ultima_remision ? date('d/m/Y',strtotime($item->ultima_remision)) : 'Sin remisión' }}</td>
                        <td>
                            @if($item->sin_vinculo)
                                <span class="badge badge-warning">Sin vínculo OT</span>
                            @elseif($item->remitido <= 0)
                                <span class="badge badge-secondary">Solo plan</span>
                            @elseif($item->en_transito > 0)
                                <span class="badge badge-warning">Recepción pendiente</span>
                            @else
                                <span class="badge badge-success">Remisiones recibidas</span>
                            @endif
                            @if($item->traslado > 0)
                                <small class="d-block text-muted mt-1">{{ number_format($item->traslado,0,',','.') }} redistribuidas</small>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-muted p-4">No se encontraron asignaciones ni remisiones mayoristas con estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($ordenes->hasPages())
            <div class="card-footer bg-white">{{ $ordenes->links() }}</div>
        @endif
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h3 class="card-title font-weight-bold"><i class="fas fa-clipboard-check text-primary mr-1"></i>Auditoría de remisiones</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-striped may-table mb-0">
                <thead class="thead-light">
                    <tr><th>Fecha</th><th>OT</th><th>Remisión</th><th>Origen → Destino</th><th>Canal</th><th class="text-right">Cantidad</th><th class="text-right">P. venta unit.</th><th>Recepción</th></tr>
                </thead>
                <tbody>
                    @forelse($remisiones ?? [] as $mov)
                    <tr>
                        <td>{{ $mov->fecha_remision ? date('d/m/Y',strtotime($mov->fecha_remision)) : '-' }}</td>
                        <td class="{{ !$mov->id_ot ? 'text-warning font-weight-bold' : '' }}">{{ $mov->nro_ot ?: 'Sin OT' }}</td>
                        <td><strong>{{ $mov->serie }}-{{ $mov->numero_remision }}</strong><small class="d-block text-muted">{{ $mov->codigo }}</small></td>
                        <td>{{ $mov->sucursal_salida ?: '-' }} <i class="fas fa-long-arrow-alt-right mx-1 text-muted"></i>{{ $mov->sucursal_logistica ?: $mov->sucursal_destino ?: '-' }}</td>
                        <td>{{ $mov->canal }}</td>
                        <td class="text-right font-weight-bold">{{ number_format($mov->cantidad,0,',','.') }}</td>
                        <td class="text-right">{{ $mov->precio_venta !== null ? 'Gs '.number_format($mov->precio_venta,0,',','.') : '-' }}</td>
                        <td>
                            @if($mov->fecha_recepcion)
                                <span class="badge badge-success">{{ date('d/m/Y',strtotime($mov->fecha_recepcion)) }}</span>
                            @else
                                <span class="badge badge-warning">Sin confirmar</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center p-4 text-muted">No hay remisiones emitidas en este período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($remisiones && $remisiones->hasPages())
            <div class="card-footer bg-white">{{ $remisiones->links() }}</div>
        @endif
    </div>
</div>
</section>
@endsection

@push('page_css')
<style>
.may-stat,.may-info{height:100%;padding:16px;border:1px solid #e1e8f1;background:#fff;border-radius:11px;box-shadow:0 3px 12px rgba(15,35,60,.04)}
.may-stat{border-top:3px solid #3b82f6}
.may-stat.is-success{border-top-color:#16a34a}
.may-stat.is-warning{border-top-color:#f59e0b}
.may-stat.is-photo{border-top-color:#8b5cf6}
.may-stat small,.may-info small,.may-value small{display:block;color:#64748b;font-weight:700;font-size:.78rem;margin-bottom:6px}
.may-stat strong{display:block;color:#1e293b;font-size:1.8rem;font-weight:800}
.may-stat span,.may-info span{display:block;color:#64748b;font-size:.79rem}
.may-info strong{display:block;font-size:1.25rem;color:#1e293b;margin:5px 0}
.may-rule{display:flex;align-items:center;flex-wrap:wrap;gap:11px;color:#334155}
.may-rule span{padding:8px 12px;border-radius:7px;background:#f1f5f9}
.may-rule strong{color:#15803d;padding:8px 12px;border:1px solid #bbf7d0;border-radius:7px;background:#f0fdf4}
.may-value{padding:13px;border-radius:8px;background:#f8fafc;border:1px solid #e6eaf1}
.may-value strong{font-size:1.4rem;color:#1e293b}
.may-table{font-size:.84rem}
.may-table th{white-space:nowrap;font-size:.76rem}
.may-table td{vertical-align:middle}
.may-tag{margin-top:5px;color:#4169a5;font-weight:700;font-size:.76rem}
@media(max-width:767px){.may-stat strong{font-size:1.5rem}.may-rule{gap:5px}}
</style>
@endpush
