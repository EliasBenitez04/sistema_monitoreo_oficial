@extends('layouts.app')
@include('invierno._estilos')
@section('carga_liviana', '1')

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
            <div class="table-responsive iw-tabla">
                <table class="table table-hover" role="table" aria-label="Remisiones y artículos del grupo">
                    <thead role="rowgroup">
                        <tr role="row">
                            <th role="columnheader" scope="col">Artículo y remisión</th>
                            <th role="columnheader" scope="col" class="iw-col-cantidades">Cantidades</th>
                            <th role="columnheader" scope="col">Valores unitarios</th>
                            <th role="columnheader" scope="col">Valor documentado</th>
                            <th role="columnheader" scope="col">Valor disponible</th>
                        </tr>
                    </thead>
                    <tbody role="rowgroup">
                    @forelse($paginados as $l)
                        <tr role="row">
                            <td role="cell" class="iw-ficha">
                                <div class="iw-product">
                                    @include('invierno._imagen', ['imagenCodigo' => $l->codigo])
                                    <div>
                                        <strong class="iw-code">{{ $l->codigo }}</strong>
                                        <span class="iw-descripcion">{{ $l->descripcion ?: 'Sin descripción' }}</span>
                                        <small class="iw-grupo-articulo">{{ $l->local }} · {{ $l->serie }} {{ $l->numero_remision }}</small>
                                        <small class="d-block text-muted">Envío {{ $fecha($l->fecha_remision) }} · Recepción {{ $fecha($l->fecha_recepcion) }}</small>
                                        @if($l->revisar)
                                            <span class="iw-pill iw-warning">Revisar documento</span>
                                        @elseif($l->central > 0)
                                            <span class="iw-pill iw-success">Recepción confirmada</span>
                                        @else
                                            <span class="iw-pill iw-muted">Recepción pendiente</span>
                                        @endif
                                        <div><a class="btn btn-sm btn-outline-primary iw-accion" href="{{ route('invierno.show', ['id' => $l->documento_id, 'entrada' => $l->entrada_id]) }}">Ver remisión</a></div>
                                    </div>
                                </div>
                            </td>
                            <td role="cell" data-etiqueta="Cantidades">@include('invierno._cantidades-celda', ['cantidades' => [
                                'Documentadas' => $l->prendas, 'Recibidas en Central' => $l->central,
                                'Enviadas a locales' => $l->remitido, 'Recibidas en locales' => $l->recibido_local,
                                'En tránsito' => $l->transito_local, 'Disponible en Central' => $l->disponible ?? 'Por revisar',
                            ]])</td>
                            @include('invierno._valores-fila', ['costo' => $l->costo_centavos, 'precio' => $l->precio_centavos,
                                'cantidadDocumentada' => $l->prendas, 'cantidadDisponible' => $l->disponible])
                        </tr>
                    @empty
                        <tr role="row"><td role="cell" colspan="5" class="iw-empty">No hay artículos de este grupo para los filtros seleccionados.</td></tr>
                    @endforelse
                    </tbody>
                    @if($paginados->total())
                        <tfoot role="rowgroup">
                            <tr role="row" class="bg-light">
                                <td role="cell" class="iw-ficha font-weight-bold">TOTAL FILTRADO DEL GRUPO</td>
                                <td role="cell" data-etiqueta="Cantidades">@include('invierno._cantidades-celda', ['cantidades' => [
                                    'Documentadas' => $resumen['prendas'], 'Recibidas en Central' => $resumen['central'],
                                    'Enviadas a locales' => $resumen['remitido'], 'Recibidas en locales' => $resumen['recibido_local'],
                                    'En tránsito' => $resumen['transito_local'], 'Disponible en Central' => $resumen['disponible'],
                                ]])</td>
                                <td role="cell" class="iw-celda-vacia"></td>
                                @include('invierno._valores-totales', ['valores' => $resumen['valorizacion']])
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            @if($paginados->hasPages())<div class="card-footer bg-white">{{ $paginados->links('pagination::bootstrap-4') }}</div>@endif
        </div>
        <p class="iw-note mt-3">Los totales reúnen todas las páginas del filtro. El disponible se calcula con las recepciones y salidas asociadas actuales. El saldo de documentos con diferencias queda pendiente de revisión y no se agrega al disponible.</p>
    </div>
</section>
@endsection
