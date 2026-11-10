@extends('layouts.app')
@include('invierno._estilos')

@section('content')
@php
    $n = fn ($v) => number_format((int) $v, 0, ',', '.');
    $fecha = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d/m/Y') : '—';
@endphp
<section class="content invierno">
    <div class="container-fluid">
        <a href="{{ route('invierno.index') }}" class="btn btn-sm btn-outline-secondary mb-3"><i class="fas fa-arrow-left mr-1"></i> Prendas de invierno</a>
        <header class="iw-hero iw-split">
            <div>
                <div class="iw-eyebrow">{{ $documento->local }} → CASA CENTRAL · 01</div>
                <h1>Remisión {{ $documento->serie }} {{ $documento->numero_remision }}</h1>
                <p>Recepción en Central, detalle por artículo y seguimiento de cada salida asociada.</p>
            </div>
            <div>@include('invierno._estado')</div>
        </header>

        @include('invierno._alertas')
        @if($documento->incidencias)
            <div class="alert alert-danger"><strong>Revisá los datos antes de cerrar el seguimiento.</strong><ul class="mb-0 mt-2">@foreach($documento->incidencias as $incidencia)<li>{{ $incidencia }}</li>@endforeach</ul><small class="d-block mt-2">Las cantidades de vínculos observados no se computan como envíos válidos. El saldo de este documento queda pendiente de revisión.</small></div>
        @endif
        @if($documento->entradas->isEmpty())
            <div class="alert alert-info">Todavía no se encontró esta remisión desde {{ $documento->local }} hacia Casa Central (01). Revisá LOCAL, SERIE y NRO, y actualizá la importación general de remisiones. El seguimiento se completará al encontrarla.</div>
        @endif

        <div class="row">
            <div class="col-6 col-lg-3"><div class="card iw-metric iw-primary"><span>Prendas en remisión</span><strong>{{ $documento->entradas->isEmpty() ? '—' : $n($documento->total) }}</strong><small>{{ $documento->entradas->count() }} líneas de artículos</small></div></div>
            <div class="col-6 col-lg-3"><div class="card iw-metric iw-green"><span>Recepción confirmada en Central</span><strong>{{ $n($documento->confirmado_central) }}</strong><small>{{ $n($documento->pendiente_central) }} pendientes</small></div></div>
            <div class="col-6 col-lg-3"><div class="card iw-metric iw-primary"><span>Enviado a locales · asociado</span><strong>{{ $n($documento->remitido) }}</strong><small>{{ $n($documento->recibido_local) }} recibidas · {{ $n($documento->transito_local) }} en tránsito</small></div></div>
            <div class="col-6 col-lg-3"><div class="card iw-metric iw-orange"><span>Saldo documental en Central</span><strong>{{ $documento->saldo_central === null || $documento->entradas->isEmpty() ? '—' : $n($documento->saldo_central) }}</strong><small>{{ $documento->saldo_central === null ? 'Pendiente de revisión' : 'Recibido menos salidas asociadas' }}</small></div></div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h2 class="card-title mb-0"><i class="fas fa-warehouse mr-2"></i>1. Recepción de la mercadería en Central</h2></div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Código completo / descripción</th><th>Fecha remisión</th><th>Recepción Central</th><th class="num">Prendas</th><th class="num">Asociadas</th><th class="num">Disponibles</th><th></th></tr></thead>
                    <tbody>
                    @forelse($documento->entradas as $linea)
                        <tr class="{{ $entrada && $entrada->id === $linea->id ? 'table-primary' : '' }}">
                            <td><span class="iw-code">{{ $linea->codigo }}</span><div>{{ $linea->descripcion }}</div>@if($linea->problema)<small class="text-danger">{{ $linea->problema }}</small>@endif</td>
                            <td>{{ $fecha(\App\Support\InviernoDocumento::fechaSalida($linea)) }}</td>
                            <td>@if(\App\Support\InviernoDocumento::recibido($linea) && !$linea->problema)<span class="iw-pill iw-success">Confirmado</span><small class="d-block mt-1">{{ $fecha($linea->fecha_recepcion) }}</small>@else<span class="iw-pill {{ $linea->problema ? 'iw-danger' : 'iw-warning' }}">{{ $linea->problema ? 'Revisar' : 'Pendiente' }}</span>@endif</td>
                            <td class="num">{{ $n($linea->cantidad) }}</td><td class="num">{{ $n($linea->asignada) }}</td>
                            <td class="num font-weight-bold">{{ $linea->problema ? '—' : $n($linea->disponible) }}</td>
                            <td>
                                @can('ot edit')
                                    @if($linea->disponible > 0 && !$linea->problema)
                                        <a class="btn btn-sm btn-outline-primary text-nowrap" href="{{ route('invierno.show', ['id' => $documento->id, 'entrada' => $linea->id]) }}#salidas">Asociar salida</a>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="iw-empty">El detalle estará disponible cuando se importe la remisión.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @can('ot edit')
        @if($entrada)
        <div class="card mb-4" id="salidas">
            <div class="card-header iw-split"><h2 class="card-title mb-0"><i class="fas fa-link mr-2"></i>2. Asociar salida de {{ $entrada->codigo }}</h2><a href="{{ route('invierno.show', $documento->id) }}" class="btn btn-sm btn-outline-secondary">Cerrar selección</a></div>
            <div class="card-body pb-2">
                <p class="iw-note">Disponibles de esta devolución: <strong>{{ $n($entrada->disponible) }}</strong>. Se muestran remisiones del mismo código completo, desde Central hacia otra sucursal y con fecha igual o posterior a la recepción. Revisá la procedencia y guardá la cantidad que corresponde.</p>
                <form method="GET" action="{{ route('invierno.show', $documento->id) }}#salidas" class="form-row align-items-end">
                    <input type="hidden" name="entrada" value="{{ $entrada->id }}">
                    <div class="form-group col-md-8"><label for="iw-buscar-salida">Buscar serie, número o local de destino</label><input id="iw-buscar-salida" class="form-control" name="buscar_salida" value="{{ request('buscar_salida') }}" maxlength="100" placeholder="Ejemplo: F 1200 o BONANZA"></div>
                    <div class="form-group col-md-4"><button class="btn btn-primary" type="submit">Buscar salidas</button><a class="btn btn-outline-secondary ml-1" href="{{ route('invierno.show', ['id' => $documento->id, 'entrada' => $entrada->id]) }}#salidas">Limpiar</a></div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Remisión de salida</th><th>Local de destino</th><th>Fecha / recepción</th><th class="num">Cantidad total</th><th class="num">Saldo sin asignar</th><th style="min-width:260px">Cantidad a asociar</th></tr></thead>
                    <tbody>
                    @forelse($salidas ?? [] as $salida)
                        <tr>
                            <td><strong class="iw-code">{{ $salida->serie }} {{ $salida->numero_remision }}</strong><small class="d-block text-muted">{{ $salida->codigo }}</small></td>
                            <td>{{ $salida->sucursal_destino ?: 'Sucursal '.$salida->cod_sucursal_destino }}<small class="d-block text-muted">Código {{ $salida->cod_sucursal_destino }}</small></td>
                            <td>{{ $fecha(\App\Support\InviernoDocumento::fechaSalida($salida)) }}<div class="mt-1"><span class="iw-pill {{ \App\Support\InviernoDocumento::recibido($salida) ? 'iw-success' : 'iw-warning' }}">{{ \App\Support\InviernoDocumento::recibido($salida) ? 'Recibido: '.$fecha($salida->fecha_recepcion) : 'En tránsito' }}</span></div></td>
                            <td class="num">{{ $n($salida->cantidad) }}</td><td class="num">{{ $n($salida->disponible) }}</td>
                            <td>@if($salida->problema)<span class="text-danger small">{{ $salida->problema }}</span>@else
                                <form method="POST" action="{{ route('invierno.asociar', $documento->id) }}">
                                    @csrf
                                    <input type="hidden" name="entrada_id" value="{{ $entrada->id }}"><input type="hidden" name="salida_id" value="{{ $salida->id }}">
                                    <label for="iw-cantidad-{{ $salida->id }}" class="small mb-1">Máximo {{ $n($salida->maximo) }} prendas</label>
                                    <div class="input-group input-group-sm mb-2"><input id="iw-cantidad-{{ $salida->id }}" type="number" min="1" step="1" max="{{ $salida->maximo }}" name="cantidad" class="form-control" placeholder="Cantidad" required><div class="input-group-append"><button type="submit" class="btn btn-primary">Asociar</button></div></div>
                                    <input name="observacion" class="form-control form-control-sm" maxlength="300" placeholder="Referencia u observación (opcional)" aria-label="Observación de la asociación">
                                </form>
                            @endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="iw-empty"><i class="fas fa-truck"></i><strong>No hay salidas disponibles con estos criterios.</strong>Revisá el saldo de la entrada, los filtros y la última importación general de remisiones.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($salidas && $salidas->hasPages())<div class="card-footer bg-white">{{ $salidas->links('pagination::bootstrap-4') }}</div>@endif
        </div>
        @endif
        @endcan

        <div class="card mb-4">
            <div class="card-header iw-split"><h2 class="card-title mb-0"><i class="fas fa-route mr-2"></i>Salidas asociadas y recepción en locales</h2><span class="iw-note">{{ $documento->asignaciones->count() }} vínculos activos</span></div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Código / remisión de salida</th><th>Destino</th><th>Fecha de salida</th><th>Recepción en el local</th><th class="num">Prendas asociadas</th><th>Control del vínculo</th>@can('ot edit')<th>Corregir asociación</th>@endcan</tr></thead>
                    <tbody>
                    @forelse($documento->asignaciones as $vinculo)
                        <tr>
                            <td><strong class="iw-code">{{ $vinculo->codigo }}</strong><div>{{ $vinculo->serie_salida }} {{ $vinculo->numero_salida }}</div>@if($vinculo->observacion)<small class="text-muted">{{ $vinculo->observacion }}</small>@endif<small class="d-block text-muted">Asociado: {{ \Carbon\Carbon::parse($vinculo->created_at)->format('d/m/Y H:i') }}</small></td>
                            <td>{{ $vinculo->sucursal_destino ?: 'Sucursal '.$vinculo->cod_sucursal_destino }}</td>
                            <td>{{ $vinculo->salida ? $fecha(\App\Support\InviernoDocumento::fechaSalida($vinculo->salida)) : '—' }}</td>
                            <td>@if(!$vinculo->problema && \App\Support\InviernoDocumento::recibido($vinculo->salida))<span class="iw-pill iw-success">Confirmado</span><small class="d-block mt-1">{{ $fecha($vinculo->salida->fecha_recepcion) }}</small>@else<span class="iw-pill {{ $vinculo->problema ? 'iw-danger' : 'iw-warning' }}">{{ $vinculo->problema ? 'Por revisar' : 'En tránsito' }}</span>@endif</td>
                            <td class="num font-weight-bold">{{ $n($vinculo->cantidad) }}</td>
                            <td>@if($vinculo->problema)<span class="text-danger small">{{ $vinculo->problema }}</span>@else<span class="iw-pill iw-success">Válido</span>@endif</td>
                            @can('ot edit')
                            <td style="min-width:210px"><details><summary class="small text-danger">Anular vínculo</summary><form class="mt-2" method="POST" action="{{ route('invierno.anular', ['id' => $documento->id, 'asignacion' => $vinculo->id]) }}">@csrf @method('PATCH')<label class="sr-only" for="iw-motivo-{{ $vinculo->id }}">Motivo de anulación</label><input id="iw-motivo-{{ $vinculo->id }}" name="motivo" class="form-control form-control-sm mb-2" minlength="3" maxlength="300" placeholder="Motivo de la anulación" required><button class="btn btn-sm btn-outline-danger" type="submit">Registrar anulación</button></form></details></td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="7" class="iw-empty">Todavía no hay salidas asociadas a esta devolución. Seleccioná un artículo recibido en Central para vincular su salida.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($historial->isNotEmpty())
        <div class="card mb-4"><div class="card-body"><details><summary>Historial de asociaciones anuladas ({{ $historial->count() }})</summary><div class="table-responsive mt-3"><table class="table"><thead><tr><th>Artículo</th><th>Salida / destino</th><th class="num">Cantidad</th><th>Fecha anulación</th><th>Motivo</th></tr></thead><tbody>@foreach($historial as $anulada)<tr><td class="iw-code">{{ $anulada->codigo }}</td><td>{{ $anulada->serie_salida }} {{ $anulada->numero_salida }}<div>{{ $anulada->sucursal_destino }}</div></td><td class="num">{{ $n($anulada->cantidad) }}</td><td>{{ \Carbon\Carbon::parse($anulada->anulada_at)->format('d/m/Y H:i') }}</td><td>{{ $anulada->motivo_anulacion }}</td></tr>@endforeach</tbody></table></div></details></div></div>
        @endif

        <p class="iw-note">Las fechas y confirmaciones se actualizan desde la importación general. Una salida del mismo artículo puede corresponder a otra mercadería: verificá su procedencia antes de asociarla. El saldo documental no sustituye el stock físico.</p>
        @can('ot edit')
        @if($documento->asignaciones->isEmpty() && $historial->isEmpty())
        <details class="mt-3"><summary class="small text-muted">Documento registrado por error</summary><form class="mt-2" method="POST" action="{{ route('invierno.retirar', $documento->id) }}" onsubmit="return confirm('¿Retirar esta remisión del seguimiento de invierno?');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Retirar del seguimiento</button><small class="ml-2 text-muted">Solo retira este documento del listado de invierno.</small></form></details>
        @endif
        @endcan
    </div>
</section>
@endsection
