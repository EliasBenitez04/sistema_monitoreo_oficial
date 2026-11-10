@extends('layouts.app')
@include('invierno._estilos')

@section('content')
@php
    $n = fn ($v) => number_format((int) $v, 0, ',', '.');
    $fecha = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d/m/Y') : '—';
@endphp
<section class="content invierno">
    <div class="container-fluid">
        <header class="iw-hero iw-split">
            <div><div class="iw-eyebrow">DISPONIBILIDAD DE INVIERNO · DETALLE DEL GRUPO</div><h1>{{ $grupo }}</h1><p>Códigos y remisiones que componen este grupo. Los totales corresponden a los filtros seleccionados.</p></div>
            <a class="btn btn-light" href="{{ route('invierno.disponibilidad.exportar', request()->except('page')) }}"><i class="fas fa-file-excel mr-1"></i> Exportar Excel</a>
        </header>
        @include('invierno._navegacion')
        @include('invierno._disponibilidad-totales')
        <div class="card">
            <div class="card-header iw-split"><h2 class="card-title mb-0">Remisiones y artículos del grupo</h2><span class="iw-note">{{ $n($paginados->total()) }} líneas según filtros</span></div>
            <div class="card-body pb-2">
                <form action="{{ route('invierno.disponibilidad.detalle') }}" method="GET" class="form-row align-items-end">
                    <input type="hidden" name="grupo" value="{{ $grupo }}">
                    <div class="form-group col-md-4"><label for="idd-codigo">Artículo / código completo</label><select id="idd-codigo" name="codigo" class="form-control"><option value="">Todos los códigos del grupo</option>@foreach($codigos as $codigo => $descripcion)<option value="{{ $codigo }}" {{ request('codigo') === (string) $codigo ? 'selected' : '' }}>{{ $codigo }} · {{ $descripcion }}</option>@endforeach</select></div>
                    <div class="form-group col-md-3"><label for="idd-local">Local de origen</label><select id="idd-local" name="local" class="form-control"><option value="">Todos los locales</option>@foreach($locales as $codigo => $nombre)<option value="{{ $codigo }}" {{ request('local') == $codigo ? 'selected' : '' }}>{{ $nombre }}</option>@endforeach</select></div>
                    <div class="form-group col-md-3"><label for="idd-buscar">Buscar artículo o remisión</label><input id="idd-buscar" class="form-control" name="buscar" value="{{ request('buscar') }}" maxlength="100" placeholder="Código, descripción o remisión"></div>
                    <div class="form-group col-md-2 d-flex"><button type="submit" class="btn btn-primary flex-fill mr-1">Consultar</button><a class="btn btn-outline-secondary" href="{{ route('invierno.disponibilidad.detalle', ['grupo' => $grupo]) }}" aria-label="Limpiar filtros"><i class="fas fa-undo"></i></a></div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Origen / remisión</th><th>Código / artículo</th><th>Recepción Central</th><th class="num">Documentadas</th><th class="num">Recibido Central</th><th class="num">Enviado a locales</th><th class="num">Disponible Central</th><th></th></tr></thead>
                    <tbody>
                    @forelse($paginados as $l)
                        <tr>
                            <td><strong>{{ $l->local }}</strong><div class="iw-code">{{ $l->serie }} {{ $l->numero_remision }}</div><small class="text-muted">Remitido: {{ $fecha($l->fecha_remision) }}</small></td>
                            <td><span class="iw-code">{{ $l->codigo }}</span><div>{{ $l->descripcion ?: 'Sin descripción' }}</div>@if($l->revisar)<small class="text-danger">El documento tiene diferencias. Abrí la remisión para revisarlas.</small>@endif</td>
                            <td>@if($l->revisar)<span class="iw-pill iw-warning">Revisar documento</span>@elseif($l->central > 0)<span class="iw-pill iw-success">Confirmada</span>@else<span class="iw-pill iw-muted">Pendiente</span>@endif<small class="d-block mt-1">{{ $fecha($l->fecha_recepcion) }}</small></td>
                            <td class="num">{{ $n($l->prendas) }}</td><td class="num">{{ $n($l->central) }}</td><td class="num">{{ $n($l->remitido) }}<small class="d-block text-muted">{{ $n($l->recibido_local) }} recibidas · {{ $n($l->transito_local) }} en tránsito</small></td>
                            <td class="num font-weight-bold {{ $l->disponible > 0 ? 'text-success' : '' }}">{{ $l->disponible === null ? 'Por revisar' : $n($l->disponible) }}</td>
                            <td><a class="btn btn-sm btn-outline-primary text-nowrap" href="{{ route('invierno.show', ['id' => $l->documento_id, 'entrada' => $l->entrada_id]) }}">Ver remisión <i class="fas fa-arrow-right ml-1"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="iw-empty">No hay artículos de este grupo para los filtros seleccionados.</td></tr>
                    @endforelse
                    </tbody>
                    @if($paginados->total())<tfoot><tr class="bg-light font-weight-bold"><td colspan="3">TOTAL FILTRADO DEL GRUPO</td><td class="num">{{ $n($resumen['prendas']) }}</td><td class="num">{{ $n($resumen['central']) }}</td><td class="num">{{ $n($resumen['remitido']) }}</td><td class="num text-success">{{ $n($resumen['disponible']) }}</td><td></td></tr></tfoot>@endif
                </table>
            </div>
            @if($paginados->hasPages())<div class="card-footer bg-white">{{ $paginados->links('pagination::bootstrap-4') }}</div>@endif
        </div>
        <p class="iw-note mt-3">Los totales reúnen todas las páginas del filtro. El disponible se calcula con las recepciones y salidas asociadas actuales. El saldo de documentos con diferencias queda pendiente de revisión y no se agrega al disponible.</p>
    </div>
</section>
@endsection
