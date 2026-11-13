@extends('layouts.app')
@include('invierno._estilos')
@section('carga_liviana', '1')

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
            <div class="card-body">
                <form action="{{ route('invierno.articulos') }}" method="GET" class="iw-filtros">
                    @if(request('codigo'))<input type="hidden" name="codigo" value="{{ request('codigo') }}">@endif
                    <div class="form-group"><label for="ia-buscar">Buscar artículo o remisión</label><input id="ia-buscar" name="buscar" class="form-control" value="{{ request('buscar') }}" maxlength="100" placeholder="Código, descripción, local o remisión"></div>
                    <div class="form-group"><label for="ia-grupo">Grupo</label><select id="ia-grupo" name="grupo" class="form-control"><option value="">Todos los grupos</option>@foreach($opcionesGrupos as $opcion)<option value="{{ $opcion }}" {{ request('grupo') === $opcion ? 'selected' : '' }}>{{ $opcion }}</option>@endforeach</select></div>
                    <div class="form-group"><label for="ia-local">Local de origen</label><select id="ia-local" name="local" class="form-control"><option value="">Todos los locales</option>@foreach($locales as $codigo => $nombre)<option value="{{ $codigo }}" {{ request('local') == $codigo ? 'selected' : '' }}>{{ $nombre }}</option>@endforeach</select></div>
                    <div class="form-group"><label for="ia-estado">Estado de remisión</label><select id="ia-estado" name="estado" class="form-control"><option value="">Todos los estados</option>@foreach($estados as $clave => $nombre)<option value="{{ $clave }}" {{ request('estado') === $clave ? 'selected' : '' }}>{{ $nombre }}</option>@endforeach</select></div>
                    <div class="form-group"><label for="ia-orden">Ordenar artículos</label><select id="ia-orden" name="orden_articulos" class="form-control">@foreach(['disponible' => 'Más disponibles', 'codigo' => 'Código', 'prendas' => 'Más prendas documentadas', 'remitido' => 'Más enviadas'] as $clave => $nombre)<option value="{{ $clave }}" {{ request('orden_articulos', 'disponible') === $clave ? 'selected' : '' }}>{{ $nombre }}</option>@endforeach</select></div>
                    <div class="iw-filtros-acciones"><button class="btn btn-primary" type="submit">Consultar</button><a class="btn btn-outline-secondary" href="{{ route('invierno.articulos') }}">Limpiar filtros</a></div>
                </form>
                @if(request('codigo'))<p class="mt-3 mb-0 small">Código seleccionado <strong>{{ request('codigo') }}</strong> · <a href="{{ route('invierno.articulos', request()->except('codigo', 'page', 'remisiones_page')) }}">Ver todos los artículos del filtro</a></p>@endif
            </div>
            <div class="table-responsive iw-tabla">
                <table class="table table-hover" role="table" aria-label="Artículos en general">
                    <thead role="rowgroup">
                        <tr role="row">
                            <th role="columnheader" scope="col">Artículo y grupo</th>
                            <th role="columnheader" scope="col" class="iw-col-cantidades">Cantidades</th>
                            <th role="columnheader" scope="col" class="iw-col-importes">Valor documentado</th>
                            <th role="columnheader" scope="col" class="iw-col-importes">Valor disponible en Central</th>
                        </tr>
                    </thead>
                    <tbody role="rowgroup">
                    @forelse($paginados as $a)
                        <tr role="row">
                            <td role="cell" class="iw-ficha">
                                <div class="iw-product">
                                    @include('invierno._imagen', ['imagenCodigo' => $a->codigo])
                                    <div>
                                        <strong class="iw-code">{{ $a->codigo }}</strong>
                                        <span class="iw-descripcion">{{ $a->descripcion ?: 'Sin descripción' }}</span>
                                        <small class="iw-grupo-articulo">{{ $a->grupo }}</small>
                                        @if($a->lineas_revision)
                                            <small class="d-block iw-importe-pendiente">{{ $n($a->por_revisar) }} de saldo por revisar · {{ $n($a->lineas_revision) }} líneas con diferencias</small>
                                        @endif
                                        <a class="btn btn-sm btn-outline-primary iw-accion" href="{{ route('invierno.articulos', array_merge(request()->except('page', 'remisiones_page'), ['codigo' => $a->codigo])) }}#remisiones-articulo">Ver remisiones</a>
                                    </div>
                                </div>
                            </td>
                            <td role="cell" data-etiqueta="Cantidades">@include('invierno._cantidades-celda', ['cantidades' => [
                                'Remisiones' => $a->documentos, 'Documentadas' => $a->prendas,
                                'Recibidas en Central' => $a->central, 'Pendientes' => $a->pendiente,
                                'Enviadas a locales' => $a->remitido, 'Disponible en Central' => $a->disponible,
                            ]])</td>
                            @include('invierno._valores-totales', ['valores' => $a->valorizacion])
                        </tr>
                    @empty
                        <tr role="row"><td role="cell" colspan="4" class="iw-empty"><i class="fas fa-tshirt"></i><strong>No hay artículos para estos filtros.</strong>Se listan las prendas incluidas en las remisiones registradas como invierno.</td></tr>
                    @endforelse
                    </tbody>
                    @if($paginados->total())
                        <tfoot role="rowgroup">
                            <tr role="row" class="bg-light">
                                <td role="cell" class="iw-ficha font-weight-bold">TOTAL FILTRADO · {{ $n($resumen['codigos']) }} códigos</td>
                                <td role="cell" data-etiqueta="Cantidades">@include('invierno._cantidades-celda', ['cantidades' => [
                                    'Remisiones' => $resumen['documentos'], 'Documentadas' => $resumen['prendas'],
                                    'Recibidas en Central' => $resumen['central'], 'Pendientes' => $resumen['pendiente'],
                                    'Enviadas a locales' => $resumen['remitido'], 'Disponible en Central' => $resumen['disponible'],
                                ]])</td>
                                @include('invierno._valores-totales', ['valores' => $resumen['valorizacion']])
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            @if($paginados->hasPages())<div class="card-footer bg-white">{{ $paginados->links('pagination::bootstrap-4') }}</div>@endif
        </div>

        @if(request('codigo'))
        <div class="card" id="remisiones-articulo">
            <div class="card-header iw-split"><h2 class="card-title mb-0">Remisiones del artículo {{ request('codigo') }}</h2><span class="iw-note">{{ $n($lineasPaginadas->total()) }} líneas</span></div>
            <div class="table-responsive iw-tabla">
                <table class="table table-hover" role="table" aria-label="Remisiones del artículo">
                    <thead role="rowgroup">
                        <tr role="row">
                            <th role="columnheader" scope="col">Remisión y origen</th>
                            <th role="columnheader" scope="col" class="iw-col-cantidades">Cantidades</th>
                            <th role="columnheader" scope="col">Valores unitarios</th>
                            <th role="columnheader" scope="col">Valor documentado</th>
                            <th role="columnheader" scope="col">Valor disponible</th>
                        </tr>
                    </thead>
                    <tbody role="rowgroup">
                    @forelse($lineasPaginadas as $l)
                        <tr role="row">
                            <td role="cell" class="iw-ficha">
                                <strong class="iw-code">{{ $l->serie }} {{ $l->numero_remision }}</strong>
                                <span class="d-block">{{ $l->local }} · {{ $l->local_codigo }}</span>
                                <small class="d-block text-muted">{{ $l->estado_documento_texto }}</small>
                                <a class="btn btn-sm btn-outline-primary iw-accion" href="{{ route('invierno.show', ['id' => $l->documento_id, 'entrada' => $l->entrada_id]) }}">Ver remisión</a>
                            </td>
                            <td role="cell" data-etiqueta="Cantidades">@include('invierno._cantidades-celda', ['cantidades' => [
                                'En remisión' => $l->cantidad_remision, 'Recibidas en Central' => $l->central,
                                'Enviadas asociadas' => $l->remitido, 'Disponible en Central' => $l->disponible ?? 'Por revisar',
                            ]])</td>
                            @include('invierno._valores-fila', ['costo' => $l->costo_centavos, 'precio' => $l->precio_centavos,
                                'cantidadDocumentada' => $l->prendas, 'cantidadDisponible' => $l->disponible])
                        </tr>
                    @empty
                        <tr role="row"><td role="cell" colspan="5" class="iw-empty">No se encontraron líneas de este artículo.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($lineasPaginadas->hasPages())<div class="card-footer bg-white">{{ $lineasPaginadas->links('pagination::bootstrap-4') }}</div>@endif
        </div>
        @endif
        <p class="iw-note mt-3">Invierno se identifica por las remisiones registradas en este módulo. El Excel incluye todas las páginas del filtro, con una hoja de prendas por remisión, otra de artículos acumulados y las remisiones sin detalle. Las cantidades con diferencias se conservan para revisión y no se suman al saldo disponible.</p>
    </div>
</section>
@endsection
