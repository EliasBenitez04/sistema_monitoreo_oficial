@extends('layouts.app')

@section('content')
<style>
    .fd-title{font-weight:800;color:#26364a}.fd-sub{color:#7a8796;font-size:13px}
    .fd-card{border:1px solid #e7ebf0;border-radius:12px;box-shadow:0 5px 18px rgba(15,23,42,.045)}
    .fd-kpi .label{font-size:11px;text-transform:uppercase;font-weight:800;letter-spacing:.05em;color:#7a8796}
    .fd-kpi .value{font-size:27px;font-weight:800;color:#26364a;line-height:1.1;margin:6px 0 2px}
    .fd-table th{font-size:11px;text-transform:uppercase;color:#536273;white-space:nowrap;vertical-align:middle}
    .fd-table td{font-size:13px;vertical-align:middle}
    .fd-code{font-family:monospace;font-size:12px}
    .fd-flow{display:flex;align-items:center;flex-wrap:wrap;gap:8px}
    .fd-step{background:#f8fafc;border:1px solid #e6ebf1;border-radius:20px;padding:7px 12px;font-size:12px}
    .fd-arrow{color:#a7b1bc}
    .nav-pills .nav-link{border-radius:20px;font-size:13px;font-weight:700}
    .fd-variant{font-weight:800;color:#34465a}
    .fd-badge{min-width:82px;display:inline-block}
</style>

<div class="container-fluid pb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h2 class="fd-title mb-1"><i class="fas fa-project-diagram text-primary mr-2"></i>Flujo Diario Terminación → Logística → Locales</h2>
            <div class="fd-sub">Seguimiento discriminado desde el ingreso a Terminación hasta la recepción por código, color y talle.</div>
        </div>
        <div class="mt-2">
            <a href="{{ route('control.terminacion', ['fecha_desde'=>$fechaDesde,'fecha_hasta'=>$fechaHasta]) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i>Control Terminación
            </a>
        </div>
    </div>

    <div class="card fd-card mb-3"><div class="card-body">
        <form method="GET" action="{{ route('control.flujo-diario') }}">
            <div class="row align-items-end">
                <div class="col-lg-2 col-md-4 mb-2"><label class="small font-weight-bold">Desde</label><input type="date" name="fecha_desde" class="form-control" value="{{ $fechaDesde }}"></div>
                <div class="col-lg-2 col-md-4 mb-2"><label class="small font-weight-bold">Hasta</label><input type="date" name="fecha_hasta" class="form-control" value="{{ $fechaHasta }}"></div>
                <div class="col-lg-5 col-md-8 mb-2"><label class="small font-weight-bold">OT / código / descripción</label><input type="text" name="buscar" class="form-control" value="{{ $buscar }}" placeholder="Ej. 30788 o 060617769"></div>
                <div class="col-lg-3 col-md-4 mb-2"><button class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i>Consultar flujo</button></div>
            </div>
        </form>
        <div class="fd-flow mt-2">
            <span class="fd-step"><strong>1.</strong> TERMINACIÓN - TERMINACIÓN</span><i class="fas fa-angle-right fd-arrow"></i>
            <span class="fd-step"><strong>2.</strong> PRODUCTO TERMINADO = ENTRA A LOGÍSTICA</span><i class="fas fa-angle-right fd-arrow"></i>
            <span class="fd-step"><strong>3.</strong> REMISIÓN</span><i class="fas fa-angle-right fd-arrow"></i>
            <span class="fd-step"><strong>4.</strong> RECEPCIÓN LOCAL</span>
        </div>
    </div></div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-3"><div class="card fd-card fd-kpi h-100"><div class="card-body">
            <div class="label"><i class="fas fa-sign-in-alt text-primary mr-1"></i>Entrada Terminación</div>
            <div class="value">{{ number_format($resumen->entrada_terminacion,0,',','.') }}</div>
            <small class="text-muted">{{ $resumen->ots_entrada }} OTs ingresaron</small>
        </div></div></div>
        <div class="col-xl-3 col-md-6 mb-3"><div class="card fd-card fd-kpi h-100"><div class="card-body">
            <div class="label"><i class="fas fa-check-double text-success mr-1"></i>PT / Entrada Logística</div>
            <div class="value">{{ number_format($resumen->producto_terminado,0,',','.') }}</div>
            <small class="text-muted">{{ $resumen->ots_pt }} OTs llegaron a PT</small>
        </div></div></div>
        <div class="col-xl-3 col-md-6 mb-3"><div class="card fd-card fd-kpi h-100"><div class="card-body">
            <div class="label"><i class="fas fa-truck text-warning mr-1"></i>Enviado a Locales</div>
            <div class="value">{{ number_format($resumen->enviado,0,',','.') }}</div>
            <small class="text-muted">{{ $resumen->documentos }} remisiones</small>
        </div></div></div>
        <div class="col-xl-3 col-md-6 mb-3"><div class="card fd-card fd-kpi h-100"><div class="card-body">
            <div class="label"><i class="fas fa-store text-info mr-1"></i>Recepcionado</div>
            <div class="value">{{ number_format($resumen->recepcionado,0,',','.') }}</div>
            <small class="text-muted">@if($resumen->transito>0){{ number_format($resumen->transito,0,',','.') }} en tránsito @else Sin tránsito pendiente @endif</small>
        </div></div></div>
    </div>

    <div class="card fd-card">
        <div class="card-header bg-white">
            <ul class="nav nav-pills" id="flujoTabs" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#entrada"><i class="fas fa-sign-in-alt mr-1"></i>Entrada Terminación <span class="badge badge-light ml-1">{{ $entradasTerminacion->count() }}</span></a></li>
                <li class="nav-item ml-1"><a class="nav-link" data-toggle="pill" href="#pt"><i class="fas fa-check-double mr-1"></i>PT / Logística <span class="badge badge-light ml-1">{{ $salidasProductoTerminado->count() }}</span></a></li>
                <li class="nav-item ml-1"><a class="nav-link" data-toggle="pill" href="#envios"><i class="fas fa-truck mr-1"></i>Envíos y Recepción <span class="badge badge-light ml-1">{{ $envios->count() }}</span></a></li>
            </ul>
        </div>

        <div class="card-body p-0">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="entrada">
                    <div class="px-3 pt-3"><strong>Lo que ingresó a Terminación</strong><div class="fd-sub">Movimiento exacto TERMINACION - TERMINACION del período seleccionado.</div></div>
                    <div class="table-responsive mt-2"><table class="table table-hover mb-0 fd-table">
                        <thead><tr><th>Fecha</th><th>OT</th><th>Código</th><th>Descripción</th><th class="text-right">Orden</th><th class="text-right">Ingresó</th></tr></thead>
                        <tbody>
                        @forelse($entradasTerminacion as $item)
                            <tr>
                                <td>{{ CarbonCarbon::parse($item->fecha)->format('d/m/Y') }}</td>
                                <td><strong>{{ $item->nro_ot }}</strong></td>
                                <td class="fd-code">{{ $item->codigo }}</td>
                                <td>{{ $item->descripcion }}</td>
                                <td class="text-right">{{ number_format($item->cantidad_orden,0,',','.') }}</td>
                                <td class="text-right"><strong>{{ number_format($item->cantidad,0,',','.') }}</strong></td>
                            </tr>
                        @empty<tr><td colspan="6" class="text-center text-muted py-4">Sin ingresos a Terminación en el período.</td></tr>@endforelse
                        </tbody>
                    </table></div>
                </div>

                <div class="tab-pane fade" id="pt">
                    <div class="px-3 pt-3"><strong>Lo que salió de Terminación y entró a Logística</strong><div class="fd-sub">Movimiento exacto TERMINACION - PRODUCTO TERMINADO.</div></div>
                    <div class="table-responsive mt-2"><table class="table table-hover mb-0 fd-table">
                        <thead><tr><th>Fecha PT</th><th>OT</th><th>Código</th><th>Descripción</th><th class="text-right">Orden</th><th class="text-right">PT / Entró Logística</th></tr></thead>
                        <tbody>
                        @forelse($salidasProductoTerminado as $item)
                            <tr>
                                <td>{{ CarbonCarbon::parse($item->fecha)->format('d/m/Y') }}</td>
                                <td><strong>{{ $item->nro_ot }}</strong></td>
                                <td class="fd-code">{{ $item->codigo }}</td>
                                <td>{{ $item->descripcion }}</td>
                                <td class="text-right">{{ number_format($item->cantidad_orden,0,',','.') }}</td>
                                <td class="text-right"><strong>{{ number_format($item->cantidad,0,',','.') }}</strong></td>
                            </tr>
                        @empty<tr><td colspan="6" class="text-center text-muted py-4">Sin Producto Terminado en el período.</td></tr>@endforelse
                        </tbody>
                    </table></div>
                </div>

                <div class="tab-pane fade" id="envios">
                    <div class="px-3 pt-3"><strong>Envíos discriminados por variante</strong><div class="fd-sub">Muestra las remisiones originales de las OTs que llegaron a PT en el período, incluso si fueron enviadas después.</div></div>
                    <div class="table-responsive mt-2"><table class="table table-hover mb-0 fd-table">
                        <thead><tr>
                            <th>OT</th><th>Código base</th><th>Código variante</th><th>Color</th><th>Talle</th>
                            <th>Destino</th><th>Remisión</th><th>Fecha envío</th><th class="text-right">Enviado</th>
                            <th class="text-right">Recepcionado</th><th>Fecha recepción</th><th>Estado</th>
                        </tr></thead>
                        <tbody>
                        @forelse($envios as $item)
                            <tr>
                                <td><strong>{{ $item->nro_ot }}</strong><br><small class="text-muted">{{ IlluminateSupportStr::limit($item->descripcion,32) }}</small></td>
                                <td class="fd-code">{{ $item->codigo_base }}</td>
                                <td class="fd-code">{{ $item->codigo_variante }}</td>
                                <td><span class="fd-variant">{{ $item->color ?: '—' }}</span></td>
                                <td><span class="fd-variant">{{ $item->talle ?: '—' }}</span></td>
                                <td><strong>{{ $item->destino }}</strong></td>
                                <td><strong>{{ $item->serie }}-{{ $item->numero_remision }}</strong></td>
                                <td>{{ $item->fecha_remision ? CarbonCarbon::parse($item->fecha_remision)->format('d/m/Y') : '—' }}</td>
                                <td class="text-right"><strong>{{ number_format($item->cantidad,0,',','.') }}</strong></td>
                                <td class="text-right">{{ number_format($item->cantidad_recepcionada,0,',','.') }}</td>
                                <td>{{ $item->fecha_recepcion ? CarbonCarbon::parse($item->fecha_recepcion)->format('d/m/Y') : '—' }}</td>
                                <td>
                                    @if($item->fecha_recepcion)
                                        <span class="badge badge-success fd-badge">RECIBIDO</span>
                                    @else
                                        <span class="badge badge-primary fd-badge">EN TRÁNSITO</span>
                                    @endif
                                </td>
                            </tr>
                        @empty<tr><td colspan="12" class="text-center text-muted py-4">Sin envíos vinculados a las OTs de PT del período.</td></tr>@endforelse
                        </tbody>
                    </table></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
