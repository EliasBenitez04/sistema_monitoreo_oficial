@extends('layouts.app')

@section('content')
<style>
    .ct-card { border: 1px solid #e7eaee; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.04); }
    .ct-title { font-weight: 700; color: #2f3542; }
    .ct-subtitle { color: #6c757d; font-size: 13px; }
    .ct-kpi { border-left: 4px solid #6c757d; min-height: 112px; }
    .ct-kpi.primary { border-left-color: #007bff; }
    .ct-kpi.success { border-left-color: #28a745; }
    .ct-kpi.warning { border-left-color: #ffc107; }
    .ct-kpi.info { border-left-color: #17a2b8; }
    .ct-kpi .value { font-size: 26px; line-height: 1.1; font-weight: 700; color: #343a40; }
    .ct-kpi .label { font-size: 11px; text-transform: uppercase; font-weight: 700; color: #6c757d; }
    .ct-table th { font-size: 11px; text-transform: uppercase; white-space: nowrap; background: #f8f9fa; }
    .ct-table td { font-size: 13px; vertical-align: middle; }
    .ct-code { font-family: monospace; font-size: 12px; }
    .ct-badge { min-width: 92px; display: inline-block; padding: 5px 8px; }
    .ct-detail { background: #fbfcfd; }
    .ct-detail-box { border: 1px solid #e6eaee; border-radius: 8px; background: #fff; padding: 14px; }
    .ct-remision { font-weight: 700; white-space: nowrap; }
    .ct-import { background: linear-gradient(180deg, #fff, #fbfcfd); }
    .ct-help { font-size: 12px; color: #6c757d; }
    .ct-nowrap { white-space: nowrap; }
</style>

<div class="container-fluid pb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h2 class="ct-title mb-1">
                <i class="fas fa-industry mr-1"></i>
                Control de Terminación y Recepción
            </h2>
            <div class="ct-subtitle">
                Producto terminado → salida de logística → remisión → confirmación de recepción del local
            </div>
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

    @if(!$tablaRemisionesDisponible)
        <div class="alert alert-warning">
            <strong>Falta preparar la base de datos.</strong>
            Ejecutá <code>php artisan migrate</code> después del pull para habilitar la importación y confirmación de locales.
        </div>
    @endif

    <div class="row">
        <div class="col-xl-8 mb-3">
            <div class="card ct-card h-100">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-filter mr-1"></i>Filtros del control</strong>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('control.terminacion') }}">
                        <div class="row align-items-end">
                            <div class="col-md-3 mb-2">
                                <label class="small font-weight-bold">Producto terminado desde</label>
                                <input type="date" name="fecha_desde" class="form-control" value="{{ $fechaDesde }}" required>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="small font-weight-bold">Producto terminado hasta</label>
                                <input type="date" name="fecha_hasta" class="form-control" value="{{ $fechaHasta }}" required>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="small font-weight-bold d-block">Estado de envío</label>
                                @foreach(['NO ENVIADO' => 'No enviado', 'PARCIAL' => 'Parcial', 'FINALIZADO' => 'Finalizado'] as $valor => $texto)
                                    <label class="mr-2 mb-0">
                                        <input type="checkbox" name="estado[]" value="{{ $valor }}"
                                            {{ in_array($valor, request()->get('estado', [])) ? 'checked' : '' }}>
                                        {{ $texto }}
                                    </label>
                                @endforeach
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
        </div>

        <div class="col-xl-4 mb-3">
            <div class="card ct-card ct-import h-100">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-file-excel mr-1 text-success"></i>Importar remisiones / recepciones</strong>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('control.terminacion.importar-remisiones') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="fecha_desde" value="{{ $fechaDesde }}">
                        <input type="hidden" name="fecha_hasta" value="{{ $fechaHasta }}">

                        <div class="form-group mb-2">
                            <input type="file" name="archivo_envios" class="form-control-file" accept=".xlsx,.xls,.csv" required
                                {{ !$tablaRemisionesDisponible ? 'disabled' : '' }}>
                        </div>

                        <button class="btn btn-success btn-sm" {{ !$tablaRemisionesDisponible ? 'disabled' : '' }}>
                            <i class="fas fa-upload mr-1"></i>Importar ENVIOS
                        </button>

                        <div class="ct-help mt-2">
                            Podés importar el mismo archivo todas las veces que necesites. La misma remisión se actualiza;
                            no se duplica. Si luego aparece la fecha de recepción, el estado pasa a <strong>RECIBIDO</strong>.
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-xl-2 col-md-4 col-6 mb-2">
            <div class="card ct-card ct-kpi primary h-100"><div class="card-body">
                <div class="label">Producto terminado</div>
                <div class="value">{{ number_format($totalTerminado, 0, ',', '.') }}</div>
                <small class="text-muted">{{ $totalOTs }} OTs</small>
            </div></div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-2">
            <div class="card ct-card ct-kpi success h-100"><div class="card-body">
                <div class="label">Salida logística</div>
                <div class="value">{{ number_format($totalLogistica, 0, ',', '.') }}</div>
                <small class="text-muted">{{ number_format($porcentajeEnviado, 1, ',', '.') }}% del terminado</small>
            </div></div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-2">
            <div class="card ct-card ct-kpi info h-100"><div class="card-body">
                <div class="label">Remitido</div>
                <div class="value">{{ number_format($totalRemitido, 0, ',', '.') }}</div>
                <small class="text-muted">{{ number_format($totalPendienteRemitir, 0, ',', '.') }} pendiente remitir</small>
            </div></div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-2">
            <div class="card ct-card ct-kpi success h-100"><div class="card-body">
                <div class="label">Recibido por locales</div>
                <div class="value">{{ number_format($totalRecibido, 0, ',', '.') }}</div>
                <small class="text-muted">Confirmado por fecha recepción</small>
            </div></div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-2">
            <div class="card ct-card ct-kpi warning h-100"><div class="card-body">
                <div class="label">En tránsito</div>
                <div class="value">{{ number_format($totalEnTransito, 0, ',', '.') }}</div>
                <small class="text-muted">Remitido todavía no recibido</small>
            </div></div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-2">
            <div class="card ct-card ct-kpi warning h-100"><div class="card-body">
                <div class="label">Sin vínculo logístico</div>
                <div class="value">{{ number_format($remisionesSinVincular, 0, ',', '.') }}</div>
                <small class="text-muted">{{ number_format($remisionesSinVincularFilas, 0, ',', '.') }} líneas</small>
            </div></div>
        </div>
    </div>

    <div class="card ct-card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <strong><i class="fas fa-list-alt mr-1"></i>Seguimiento por OT</strong>
                <div class="ct-subtitle">El botón “Ver trazabilidad” muestra sucursales, remisiones y recepción.</div>
            </div>
            <span class="badge badge-primary">{{ $totalOTs }} OTs</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 ct-table">
                <thead>
                    <tr>
                        <th>OT</th>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th class="text-right">Cant. PT</th>
                        <th>Fecha PT</th>
                        <th>Primera salida</th>
                        <th>Última salida</th>
                        <th class="text-right">Logística</th>
                        <th class="text-right">Remitido</th>
                        <th class="text-right">Recibido</th>
                        <th class="text-right">Dif. PT/Log.</th>
                        <th>Estado</th>
                        <th>Recepción local</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($produccionTerminada as $item)
                        <tr>
                            <td><strong>{{ $item->nro_ot }}</strong></td>
                            <td><span class="ct-code">{{ $item->codigo }}</span></td>
                            <td>{{ $item->descripcion }}</td>
                            <td class="text-right"><strong>{{ number_format($item->cantidad_terminada, 0, ',', '.') }}</strong></td>
                            <td class="ct-nowrap">{{ $item->fecha_producto_terminado ? \Carbon\Carbon::parse($item->fecha_producto_terminado)->format('d/m/Y') : '—' }}</td>
                            <td class="ct-nowrap">{{ $item->primera_salida ? \Carbon\Carbon::parse($item->primera_salida)->format('d/m/Y') : '—' }}</td>
                            <td class="ct-nowrap">{{ $item->ultima_salida ? \Carbon\Carbon::parse($item->ultima_salida)->format('d/m/Y') : '—' }}</td>
                            <td class="text-right"><strong>{{ number_format($item->cantidad_logistica, 0, ',', '.') }}</strong></td>
                            <td class="text-right">{{ number_format($item->cantidad_remitida, 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($item->cantidad_recibida, 0, ',', '.') }}</td>
                            <td class="text-right">
                                <span class="badge {{ $item->diferencia == 0 ? 'badge-success' : ($item->diferencia > 0 ? 'badge-danger' : 'badge-warning') }} ct-badge">
                                    {{ number_format($item->diferencia, 0, ',', '.') }}
                                </span>
                            </td>
                            <td>
                                @if($item->estado_control === 'FINALIZADO')
                                    <span class="badge badge-success ct-badge">FINALIZADO</span>
                                @elseif($item->estado_control === 'NO ENVIADO')
                                    <span class="badge badge-danger ct-badge">NO ENVIADO</span>
                                @else
                                    <span class="badge badge-warning ct-badge">PARCIAL</span>
                                @endif
                            </td>
                            <td>
                                @if($item->confirmacion_local === 'RECIBIDO')
                                    <span class="badge badge-success ct-badge"><i class="fas fa-check mr-1"></i>RECIBIDO</span>
                                @elseif($item->confirmacion_local === 'PARCIAL')
                                    <span class="badge badge-info ct-badge">PARCIAL</span>
                                @elseif($item->confirmacion_local === 'EN TRANSITO')
                                    <span class="badge badge-primary ct-badge">EN TRÁNSITO</span>
                                @else
                                    <span class="badge badge-secondary ct-badge">{{ $item->confirmacion_local }}</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <button class="btn btn-outline-primary btn-sm ct-nowrap" type="button"
                                    data-toggle="collapse" data-target="#traza-{{ $item->id_trazabilidad_producto }}">
                                    <i class="fas fa-route mr-1"></i>Ver trazabilidad
                                </button>
                            </td>
                        </tr>

                        <tr class="collapse ct-detail" id="traza-{{ $item->id_trazabilidad_producto }}">
                            <td colspan="14">
                                <div class="ct-detail-box">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong>
                                            OT {{ $item->nro_ot }} · {{ $item->codigo }}
                                        </strong>
                                        <small class="text-muted">
                                            {{ $item->detalle_logistica->count() }} destinos logísticos
                                        </small>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0 ct-table">
                                            <thead>
                                                <tr>
                                                    <th>Local destino</th>
                                                    <th class="text-right">Cant. logística</th>
                                                    <th>Fecha salida</th>
                                                    <th>Remisión</th>
                                                    <th>Fecha remisión</th>
                                                    <th class="text-right">Cant. remisión</th>
                                                    <th>Fecha recepción</th>
                                                    <th>Estado local</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($item->detalle_logistica as $local)
                                                    @if($local->remisiones->isEmpty())
                                                        <tr>
                                                            <td><strong>{{ $local->sucursal }}</strong></td>
                                                            <td class="text-right">{{ number_format($local->cantidad, 0, ',', '.') }}</td>
                                                            <td>{{ $local->fecha_logistica ? \Carbon\Carbon::parse($local->fecha_logistica)->format('d/m/Y') : '—' }}</td>
                                                            <td colspan="4" class="text-muted">Todavía no se encontró una remisión importada para este código/local.</td>
                                                            <td><span class="badge badge-secondary">SIN REMISIÓN</span></td>
                                                        </tr>
                                                    @else
                                                        @foreach($local->remisiones as $remision)
                                                            <tr>
                                                                <td>
                                                                    <strong>{{ $local->sucursal }}</strong>
                                                                    @if($remision->sucursal_destino)
                                                                        <br><small class="text-muted">{{ $remision->sucursal_destino }}</small>
                                                                    @endif
                                                                </td>
                                                                <td class="text-right">{{ number_format($local->cantidad, 0, ',', '.') }}</td>
                                                                <td>{{ $local->fecha_logistica ? \Carbon\Carbon::parse($local->fecha_logistica)->format('d/m/Y') : '—' }}</td>
                                                                <td class="ct-remision">{{ $remision->serie }}-{{ $remision->numero_remision }}</td>
                                                                <td>{{ $remision->fecha_remision ? \Carbon\Carbon::parse($remision->fecha_remision)->format('d/m/Y') : '—' }}</td>
                                                                <td class="text-right">{{ number_format($remision->cantidad, 0, ',', '.') }}</td>
                                                                <td>
                                                                    {{ $remision->fecha_recepcion ? \Carbon\Carbon::parse($remision->fecha_recepcion)->format('d/m/Y') : 'Pendiente' }}
                                                                </td>
                                                                <td>
                                                                    @if($remision->fecha_recepcion)
                                                                        <span class="badge badge-success">RECIBIDO</span>
                                                                    @else
                                                                        <span class="badge badge-primary">REMISIÓN GENERADA / EN TRÁNSITO</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @endif
                                                @empty
                                                    <tr>
                                                        <td colspan="8" class="text-center text-muted py-3">La OT todavía no tiene detalle de distribución a sucursales.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="14" class="text-center text-muted py-5">No hay OTs para los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>TOTAL</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalTerminado, 0, ',', '.') }}</strong></td>
                        <td colspan="3"></td>
                        <td class="text-right"><strong>{{ number_format($totalLogistica, 0, ',', '.') }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalRemitido, 0, ',', '.') }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalRecibido, 0, ',', '.') }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalDiferencia, 0, ',', '.') }}</strong></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card ct-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <strong><i class="fas fa-store mr-1"></i>Detalle por sucursal</strong>
                <div class="ct-subtitle">Trazabilidad completa del despacho y confirmación del local.</div>
            </div>
            <span class="badge badge-info">{{ $detalleLogistica->count() }} movimientos</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 ct-table">
                <thead>
                    <tr>
                        <th>OT</th>
                        <th>Código</th>
                        <th>Local</th>
                        <th class="text-right">Logística</th>
                        <th>Salida logística</th>
                        <th>Remisión</th>
                        <th>Fecha remisión</th>
                        <th>Fecha recepción</th>
                        <th>Confirmación</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($detalleLogistica as $detalle)
                        @if($detalle->remisiones->isEmpty())
                            <tr>
                                <td><strong>{{ $detalle->nro_ot }}</strong></td>
                                <td><span class="ct-code">{{ $detalle->codigo }}</span></td>
                                <td><strong>{{ $detalle->sucursal }}</strong></td>
                                <td class="text-right">{{ number_format($detalle->cantidad, 0, ',', '.') }}</td>
                                <td>{{ $detalle->fecha_logistica ? \Carbon\Carbon::parse($detalle->fecha_logistica)->format('d/m/Y') : '—' }}</td>
                                <td colspan="3" class="text-muted">Sin remisión importada</td>
                                <td><span class="badge badge-secondary">SIN REMISIÓN</span></td>
                            </tr>
                        @else
                            @foreach($detalle->remisiones as $remision)
                                <tr>
                                    <td><strong>{{ $detalle->nro_ot }}</strong></td>
                                    <td><span class="ct-code">{{ $detalle->codigo }}</span></td>
                                    <td>
                                        <strong>{{ $detalle->sucursal }}</strong>
                                        @if($remision->sucursal_destino)
                                            <br><small class="text-muted">{{ $remision->sucursal_destino }}</small>
                                        @endif
                                        <br><small class="text-muted">
                                            Remitido {{ number_format($detalle->cantidad_remitida, 0, ',', '.') }}
                                            · Recibido {{ number_format($detalle->cantidad_recibida, 0, ',', '.') }}
                                            · Pendiente {{ number_format($detalle->pendiente_remision, 0, ',', '.') }}
                                        </small>
                                    </td>
                                    <td class="text-right">{{ number_format($detalle->cantidad, 0, ',', '.') }}</td>
                                    <td>{{ $detalle->fecha_logistica ? \Carbon\Carbon::parse($detalle->fecha_logistica)->format('d/m/Y') : '—' }}</td>
                                    <td class="ct-remision">{{ $remision->serie }}-{{ $remision->numero_remision }}</td>
                                    <td>{{ $remision->fecha_remision ? \Carbon\Carbon::parse($remision->fecha_remision)->format('d/m/Y') : '—' }}</td>
                                    <td>
                                        {{ $remision->fecha_recepcion ? \Carbon\Carbon::parse($remision->fecha_recepcion)->format('d/m/Y') : 'Pendiente' }}
                                    </td>
                                    <td>
                                        @if($remision->fecha_recepcion)
                                            <span class="badge badge-success">RECIBIDO</span>
                                        @else
                                            <span class="badge badge-primary">EN TRÁNSITO</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No existen movimientos logísticos para las OTs seleccionadas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($tablaRemisionesDisponible && $remisionesSinVincular > 0)
        <div class="card ct-card mt-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <strong><i class="fas fa-unlink text-warning mr-1"></i>Remisiones todavía sin vínculo logístico</strong>
                    <div class="ct-subtitle">
                        Estas remisiones existen en ENVIOS, pero todavía no se pudo encontrar una salida de logística
                        compatible por código, sucursal y fecha. Se muestran hasta 50 documentos.
                    </div>
                </div>
                <span class="badge badge-warning">
                    {{ $remisionesSinVincular }} documentos · {{ $remisionesSinVincularFilas }} líneas
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 ct-table">
                    <thead>
                        <tr>
                            <th>Remisión</th>
                            <th>Fecha</th>
                            <th>Cod. suc.</th>
                            <th>Destino ENVIOS</th>
                            <th>Destino normalizado</th>
                            <th class="text-right">Líneas</th>
                            <th class="text-right">Cantidad</th>
                            <th>Recepción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resumenSinVincular as $sinVinculo)
                            <tr>
                                <td class="ct-remision">{{ $sinVinculo->serie }}-{{ $sinVinculo->numero_remision }}</td>
                                <td>{{ $sinVinculo->fecha_remision ? \Carbon\Carbon::parse($sinVinculo->fecha_remision)->format('d/m/Y') : '—' }}</td>
                                <td>{{ $sinVinculo->cod_sucursal_destino }}</td>
                                <td>{{ $sinVinculo->sucursal_destino ?: '—' }}</td>
                                <td><span class="badge badge-light border">{{ $sinVinculo->sucursal_logistica ?: '—' }}</span></td>
                                <td class="text-right">{{ number_format($sinVinculo->lineas, 0, ',', '.') }}</td>
                                <td class="text-right"><strong>{{ number_format($sinVinculo->cantidad, 0, ',', '.') }}</strong></td>
                                <td>
                                    @if($sinVinculo->fecha_recepcion)
                                        <span class="badge badge-success">RECIBIDO</span>
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
@endsection
