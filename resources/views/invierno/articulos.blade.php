@extends('layouts.app')
@include('invierno._estilos')

@section('content')
@php($n = fn ($v) => number_format((int) $v, 0, ',', '.'))
<section class="content invierno">
    <div class="container-fluid">
        <header class="iw-hero iw-split">
            <div><div class="iw-eyebrow">TODAS LAS PRENDAS DE LAS REMISIONES DE INVIERNO</div><h1><i class="fas fa-tshirt mr-2"></i>Artículos de invierno</h1><p>Listado general por código completo. Cada artículo reúne sus cantidades y conserva el detalle de todas sus remisiones.</p></div>
            <a class="btn btn-light" href="{{ route('invierno.articulos.exportar', request()->except('page', 'remisiones_page')) }}"><i class="fas fa-file-excel mr-1"></i> Exportar prendas y remisiones</a>
        </header>
        @include('invierno._navegacion')
        @include('invierno._disponibilidad-totales')
        @if($resumen['sin_detalle'])<div class="alert alert-info">{{ $n($resumen['sin_detalle']) }} remisiones registradas todavía no tienen detalle importado. El Excel las incluye en la hoja «Remisiones sin detalle»; sus prendas aparecerán al actualizar ENVIOS.</div>@endif

        <div class="card mb-3">
            <div class="card-header iw-split"><h2 class="card-title mb-0">Artículos en general</h2><span class="iw-note">{{ $n($paginados->total()) }} códigos según filtros</span></div>
            <div class="card-body pb-2">
                <form action="{{ route('invierno.articulos') }}" method="GET" class="form-row align-items-end">
                    @if(request('codigo'))<input type="hidden" name="codigo" value="{{ request('codigo') }}">@endif
                    <div class="form-group col-lg-3 col-md-6"><label for="ia-buscar">Buscar artículo o remisión</label><input id="ia-buscar" name="buscar" class="form-control" value="{{ request('buscar') }}" maxlength="100" placeholder="Código, descripción, local o remisión"></div>
                    <div class="form-group col-lg-2 col-md-6"><label for="ia-grupo">Grupo</label><select id="ia-grupo" name="grupo" class="form-control"><option value="">Todos los grupos</option>@foreach($opcionesGrupos as $opcion)<option value="{{ $opcion }}" {{ request('grupo') === $opcion ? 'selected' : '' }}>{{ $opcion }}</option>@endforeach</select></div>
                    <div class="form-group col-lg-2 col-md-6"><label for="ia-local">Local de origen</label><select id="ia-local" name="local" class="form-control"><option value="">Todos los locales</option>@foreach($locales as $codigo => $nombre)<option value="{{ $codigo }}" {{ request('local') == $codigo ? 'selected' : '' }}>{{ $nombre }}</option>@endforeach</select></div>
                    <div class="form-group col-lg-2 col-md-6"><label for="ia-estado">Estado de remisión</label><select id="ia-estado" name="estado" class="form-control"><option value="">Todos los estados</option>@foreach($estados as $clave => $nombre)<option value="{{ $clave }}" {{ request('estado') === $clave ? 'selected' : '' }}>{{ $nombre }}</option>@endforeach</select></div>
                    <div class="form-group col-lg-2 col-md-6"><label for="ia-orden">Ordenar artículos</label><select id="ia-orden" name="orden_articulos" class="form-control">@foreach(['disponible' => 'Más disponibles', 'codigo' => 'Código', 'prendas' => 'Más prendas documentadas', 'remitido' => 'Más enviadas'] as $clave => $nombre)<option value="{{ $clave }}" {{ request('orden_articulos', 'disponible') === $clave ? 'selected' : '' }}>{{ $nombre }}</option>@endforeach</select></div>
                    <div class="form-group col-lg-1 col-md-6 d-flex"><button class="btn btn-primary mr-1" type="submit" aria-label="Consultar"><i class="fas fa-search"></i></button><a class="btn btn-outline-secondary" href="{{ route('invierno.articulos') }}" aria-label="Limpiar filtros"><i class="fas fa-undo"></i></a></div>
                </form>
                @if(request('codigo'))<p class="mb-2 small">Código seleccionado: <strong>{{ request('codigo') }}</strong> · <a href="{{ route('invierno.articulos', request()->except('codigo', 'page', 'remisiones_page')) }}">Ver todos los artículos del filtro</a></p>@endif
            </div>
            <div class="table-responsive"><table class="table table-hover">
                <thead><tr><th>Código / artículo</th><th>Grupo</th><th class="num">Remisiones</th><th class="num">Documentadas</th><th class="num">Recibido Central</th><th class="num">Enviado a locales</th><th class="num">Disponible Central</th><th></th></tr></thead>
                <tbody>
                @forelse($paginados as $a)
                    <tr><td><div class="iw-product">@include('invierno._imagen', ['imagenCodigo' => $a->codigo])<div><strong class="iw-code">{{ $a->codigo }}</strong><div>{{ $a->descripcion ?: 'Sin descripción' }}</div>@if($a->lineas_revision)<small class="text-warning">{{ $n($a->por_revisar) }} de saldo por revisar · {{ $n($a->lineas_revision) }} líneas con diferencias</small>@endif</div></div></td><td>{{ $a->grupo }}</td><td class="num">{{ $n($a->documentos) }}</td><td class="num">{{ $n($a->prendas) }}</td><td class="num">{{ $n($a->central) }}<small class="d-block text-muted">{{ $n($a->pendiente) }} pendientes</small></td><td class="num">{{ $n($a->remitido) }}</td><td class="num font-weight-bold text-success">{{ $n($a->disponible) }}</td><td><a class="btn btn-sm btn-outline-primary text-nowrap" href="{{ route('invierno.articulos', array_merge(request()->except('page', 'remisiones_page'), ['codigo' => $a->codigo])) }}#remisiones-articulo">Ver remisiones</a></td></tr>
                @empty
                    <tr><td colspan="8" class="iw-empty"><i class="fas fa-tshirt"></i><strong>No hay artículos para estos filtros.</strong>Se listan las prendas incluidas en las remisiones registradas como invierno.</td></tr>
                @endforelse
                </tbody>
                @if($paginados->total())<tfoot><tr class="bg-light font-weight-bold"><td colspan="3">TOTAL FILTRADO · {{ $n($resumen['codigos']) }} códigos</td><td class="num">{{ $n($resumen['prendas']) }}</td><td class="num">{{ $n($resumen['central']) }}</td><td class="num">{{ $n($resumen['remitido']) }}</td><td class="num text-success">{{ $n($resumen['disponible']) }}</td><td></td></tr></tfoot>@endif
            </table></div>
            @if($paginados->hasPages())<div class="card-footer bg-white">{{ $paginados->links('pagination::bootstrap-4') }}</div>@endif
        </div>

        @if(request('codigo'))
        <div class="card" id="remisiones-articulo">
            <div class="card-header iw-split"><h2 class="card-title mb-0">Remisiones del artículo {{ request('codigo') }}</h2><span class="iw-note">{{ $n($lineasPaginadas->total()) }} líneas</span></div>
            <div class="table-responsive"><table class="table table-hover">
                <thead><tr><th>Local de origen</th><th>Serie / nro. remisión</th><th>Estado</th><th class="num">Cantidad en remisión</th><th class="num">Recibido Central</th><th class="num">Enviado asociado</th><th class="num">Disponible Central</th><th></th></tr></thead>
                <tbody>@forelse($lineasPaginadas as $l)<tr><td>{{ $l->local }}<small class="d-block text-muted">Código {{ $l->local_codigo }}</small></td><td class="iw-code">{{ $l->serie }} {{ $l->numero_remision }}</td><td>{{ $l->estado_documento_texto }}</td><td class="num">{{ $n($l->cantidad_remision) }}</td><td class="num">{{ $n($l->central) }}</td><td class="num">{{ $n($l->remitido) }}</td><td class="num font-weight-bold">{{ $l->disponible === null ? 'Por revisar' : $n($l->disponible) }}</td><td><a class="btn btn-sm btn-outline-primary text-nowrap" href="{{ route('invierno.show', ['id' => $l->documento_id, 'entrada' => $l->entrada_id]) }}">Ver remisión</a></td></tr>@empty<tr><td colspan="8" class="iw-empty">No se encontraron líneas de este artículo.</td></tr>@endforelse</tbody>
            </table></div>
            @if($lineasPaginadas->hasPages())<div class="card-footer bg-white">{{ $lineasPaginadas->links('pagination::bootstrap-4') }}</div>@endif
        </div>
        @endif
        <p class="iw-note mt-3">Invierno se identifica por las remisiones registradas en este módulo. El Excel incluye todas las páginas del filtro, con una hoja de prendas por remisión, otra de artículos acumulados y las remisiones sin detalle. Las cantidades con diferencias se conservan para revisión y no se suman al saldo disponible.</p>
    </div>
</section>
@endsection
