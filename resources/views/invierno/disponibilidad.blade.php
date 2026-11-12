@extends('layouts.app')
@include('invierno._estilos')

@section('content')
@php($n = fn ($v) => number_format((int) $v, 0, ',', '.'))
<section class="content invierno">
    <div class="container-fluid">
        <header class="iw-hero iw-split">
            <div>
                <div class="iw-eyebrow">PRENDAS DE INVIERNO · ACUMULADO DE REMISIONES</div>
                <h1><i class="fas fa-snowflake mr-2"></i>Disponibilidad de invierno</h1>
                <p>Reuní las prendas de todas las remisiones de invierno y ubicá qué grupos tienen más disponible en Central.</p>
            </div>
            <a class="btn btn-light" href="{{ route('invierno.disponibilidad.exportar', request()->except('page')) }}"><i class="fas fa-file-excel mr-1"></i> Exportar Excel</a>
        </header>
        @include('invierno._navegacion')
        @include('invierno._disponibilidad-totales')

        <section class="row" aria-label="Imágenes de las prendas documentadas">
            <div class="col-sm-6">
                <div class="card iw-metric iw-green">
                    <span>Prendas con imagen asociada</span>
                    <strong class="text-success">{{ $n($resumenImagenes['prendas_con_imagen']) }}</strong>
                    <small>{{ $n($resumenImagenes['codigos_con_imagen']) }} códigos distintos</small>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card iw-metric iw-orange">
                    <div class="iw-split">
                        <div>
                            <span>Prendas sin imagen asociada</span>
                            <strong>{{ $n($resumenImagenes['prendas_sin_imagen']) }}</strong>
                            <small>{{ $n($resumenImagenes['codigos_sin_imagen']) }} códigos distintos</small>
                        </div>
                        @if($resumenImagenes['codigos_sin_imagen'] > 0)
                            <a class="btn btn-sm btn-outline-primary"
                               href="{{ route('invierno.disponibilidad.codigos-sin-imagen.exportar', request()->except('page', 'remisiones_page')) }}">
                                <i class="fas fa-file-excel mr-1" aria-hidden="true"></i> Exportar códigos base
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        @if($resumen['sin_detalle'])
            <div class="alert alert-info">Hay {{ $n($resumen['sin_detalle']) }} remisiones registradas de invierno{{ request('local') ? ' de este origen' : '' }} sin detalle importado. Sus cantidades aparecerán cuando actualices ENVIOS.</div>
        @endif
        @if($resumen['lineas_revision'])
            <div class="alert alert-warning">El saldo de documentos con diferencias queda fuera de Disponible en Central. Abrí su detalle para revisar las remisiones antes de contar esas prendas como disponibles.</div>
        @endif

        <div class="card">
            <div class="card-header iw-split"><h2 class="card-title mb-0">Acumulado por grupo</h2><span class="iw-note">{{ $n($paginados->total()) }} grupos según filtros</span></div>
            <div class="card-body pb-2">
                <form method="GET" action="{{ route('invierno.disponibilidad') }}" class="form-row align-items-end">
                    <div class="form-group col-lg-3 col-md-6"><label for="id-grupo">Grupo de prendas</label><select id="id-grupo" name="grupo" class="form-control"><option value="">Todos los grupos</option>@foreach($opcionesGrupos as $opcion)<option value="{{ $opcion }}" {{ request('grupo') === $opcion ? 'selected' : '' }}>{{ $opcion }}</option>@endforeach</select></div>
                    <div class="form-group col-lg-2 col-md-6"><label for="id-local">Local de origen</label><select id="id-local" name="local" class="form-control"><option value="">Todos los locales</option>@foreach($locales as $codigo => $nombre)<option value="{{ $codigo }}" {{ request('local') == $codigo ? 'selected' : '' }}>{{ $nombre }}</option>@endforeach</select></div>
                    <div class="form-group col-lg-2 col-md-6"><label for="id-situacion">Mostrar</label><select id="id-situacion" name="situacion" class="form-control">@foreach(['todos' => 'Todos los grupos', 'disponibles' => 'Con disponibilidad', 'pendientes' => 'Con recepción pendiente', 'revisar' => 'Con diferencias'] as $clave => $nombre)<option value="{{ $clave }}" {{ request('situacion', 'todos') === $clave ? 'selected' : '' }}>{{ $nombre }}</option>@endforeach</select></div>
                    <div class="form-group col-lg-2 col-md-6"><label for="id-orden">Ordenar por</label><select id="id-orden" name="orden" class="form-control">@foreach(['disponible' => 'Más disponible primero', 'central' => 'Más recibido en Central', 'pendiente' => 'Más pendiente en Central', 'remitido' => 'Más enviado a locales', 'grupo' => 'Nombre del grupo'] as $clave => $nombre)<option value="{{ $clave }}" {{ request('orden', 'disponible') === $clave ? 'selected' : '' }}>{{ $nombre }}</option>@endforeach</select></div>
                    <div class="form-group col-lg-3"><label for="id-buscar">Buscar código, descripción o remisión</label><div class="input-group"><input id="id-buscar" class="form-control" name="buscar" maxlength="100" value="{{ request('buscar') }}" placeholder="Ej. campera o F 1188"><div class="input-group-append"><button class="btn btn-primary" type="submit" aria-label="Consultar"><i class="fas fa-search"></i></button><a class="btn btn-outline-secondary" href="{{ route('invierno.disponibilidad') }}" aria-label="Limpiar filtros"><i class="fas fa-undo"></i></a></div></div></div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Grupo de prendas</th><th class="num">Documentadas</th><th class="num">Recibido Central</th><th class="num">Pendiente Central</th><th class="num">Enviado a locales</th><th class="num">Disponible Central</th><th></th></tr></thead>
                    <tbody>
                    @forelse($paginados as $g)
                        <tr>
                            <td><strong>{{ $g->grupo }}</strong><small class="d-block text-muted">{{ $n($g->codigos) }} códigos · {{ $n($g->documentos) }} remisiones</small>@if($g->lineas_revision)<small class="d-block text-warning">{{ $n($g->por_revisar) }} de saldo por revisar · {{ $n($g->lineas_revision) }} líneas con diferencias</small>@endif</td>
                            <td class="num">{{ $n($g->prendas) }}</td><td class="num">{{ $n($g->central) }}</td><td class="num">{{ $n($g->pendiente) }}</td><td class="num">{{ $n($g->remitido) }}</td>
                            <td class="num"><strong class="{{ $g->disponible > 0 ? 'text-success' : 'text-muted' }}" style="font-size:1.15rem">{{ $n($g->disponible) }}</strong></td>
                            <td><a class="btn btn-sm btn-outline-primary text-nowrap" href="{{ route('invierno.disponibilidad.detalle', array_merge(request()->except('page', 'codigo', 'situacion', 'orden'), ['grupo' => $g->grupo])) }}">Ver detalle <i class="fas fa-arrow-right ml-1"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="iw-empty"><i class="fas fa-snowflake"></i><strong>No hay prendas de invierno para estos filtros.</strong>Revisá las remisiones registradas o limpiá los filtros.</td></tr>
                    @endforelse
                    </tbody>
                    @if($paginados->total())<tfoot><tr class="bg-light font-weight-bold"><td>TOTAL FILTRADO · {{ $n($resumen['grupos']) }} grupos</td><td class="num">{{ $n($resumen['prendas']) }}</td><td class="num">{{ $n($resumen['central']) }}</td><td class="num">{{ $n($resumen['pendiente']) }}</td><td class="num">{{ $n($resumen['remitido']) }}</td><td class="num text-success">{{ $n($resumen['disponible']) }}</td><td></td></tr></tfoot>@endif
                </table>
            </div>
            @if($paginados->hasPages())<div class="card-footer bg-white">{{ $paginados->links('pagination::bootstrap-4') }}</div>@endif
        </div>
        <p class="iw-note mt-3">Se acumulan únicamente las remisiones registradas en invierno. Grupo: clasificación del maestro de códigos; si falta, se usa Grupo plan o la descripción de la remisión. Disponible es el saldo documental recibido en Central menos las salidas asociadas válidas, excluyendo documentos con diferencias. Se actualiza al importar remisiones o modificar asociaciones; no sustituye el conteo físico.</p>
    </div>
</section>
@endsection
