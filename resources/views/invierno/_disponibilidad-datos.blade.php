@php($n = fn ($v) => number_format((int) $v, 0, ',', '.'))
<div data-invierno-resultados tabindex="-1">
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
                           href="{{ route('invierno.disponibilidad.codigos-sin-imagen.exportar', request()->except('page', 'remisiones_page', 'completa')) }}">
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

    <div class="card" id="invierno-grupos">
        @php($etapas = ['prendas' => 'Documentadas', 'central' => 'Recibidas en Central', 'pendiente' => 'Pendientes en Central',
            'remitido' => 'Enviadas a locales', 'disponible' => 'Disponible en Central'])
        <div class="card-header iw-split"><h2 class="card-title mb-0">Acumulado por grupo</h2><span class="iw-note">{{ $n($paginados->total()) }} grupos según filtros</span></div>
        <div class="card-body">
            <form method="GET" action="{{ route('invierno.disponibilidad') }}" class="iw-filtros">
                @if(request()->boolean('completa'))<input type="hidden" name="completa" value="1">@endif
                <div class="form-group"><label for="id-grupo">Grupo de prendas</label><select id="id-grupo" name="grupo" class="form-control"><option value="">Todos los grupos</option>@foreach($opcionesGrupos as $opcion)<option value="{{ $opcion }}" {{ request('grupo') === $opcion ? 'selected' : '' }}>{{ $opcion }}</option>@endforeach</select></div>
                <div class="form-group"><label for="id-local">Local de origen</label><select id="id-local" name="local" class="form-control"><option value="">Todos los locales</option>@foreach($locales as $codigo => $nombre)<option value="{{ $codigo }}" {{ request('local') == $codigo ? 'selected' : '' }}>{{ $nombre }}</option>@endforeach</select></div>
                <div class="form-group"><label for="id-situacion">Mostrar</label><select id="id-situacion" name="situacion" class="form-control">@foreach(['todos' => 'Todos los grupos', 'disponibles' => 'Con disponibilidad', 'pendientes' => 'Con recepción pendiente', 'revisar' => 'Con diferencias'] as $clave => $nombre)<option value="{{ $clave }}" {{ request('situacion', 'todos') === $clave ? 'selected' : '' }}>{{ $nombre }}</option>@endforeach</select></div>
                <div class="form-group"><label for="id-orden">Ordenar por</label><select id="id-orden" name="orden" class="form-control">@foreach(['disponible' => 'Más disponible primero', 'central' => 'Más recibido en Central', 'pendiente' => 'Más pendiente en Central', 'remitido' => 'Más enviado a locales', 'grupo' => 'Nombre del grupo'] as $clave => $nombre)<option value="{{ $clave }}" {{ request('orden', 'disponible') === $clave ? 'selected' : '' }}>{{ $nombre }}</option>@endforeach</select></div>
                <div class="form-group">
                    <label for="id-buscar">Buscar código, descripción o remisión</label>
                    <input id="id-buscar" class="form-control" name="buscar" maxlength="100" value="{{ request('buscar') }}" placeholder="Ej. campera o F 1188">
                </div>
                <div class="iw-filtros-acciones">
                    <button class="btn btn-primary" type="submit">Consultar</button>
                    <a class="btn btn-outline-secondary" href="{{ route('invierno.disponibilidad', request()->boolean('completa') ? ['completa' => 1] : []) }}">Limpiar filtros</a>
                </div>
            </form>
        </div>
        <div class="table-responsive iw-tabla">
            <table class="table table-hover" role="table" aria-label="Acumulado por grupo">
                <thead role="rowgroup">
                    <tr role="row">
                        <th role="columnheader" scope="col" class="iw-col-grupo">Grupo de prendas</th>
                        @foreach($etapas as $etapa => $etiqueta)
                            <th role="columnheader" scope="col" class="{{ $etapa === 'disponible' ? 'iw-col-saldo' : '' }}">{{ $etiqueta }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody role="rowgroup">
                @forelse($paginados as $g)
                    <tr role="row">
                        <td role="cell" class="iw-ficha">
                            <strong>{{ $g->grupo }}</strong>
                            <small class="d-block text-muted">{{ $n($g->codigos) }} códigos · {{ $n($g->documentos) }} remisiones</small>
                            @if($g->lineas_revision)<small class="d-block text-warning">{{ $n($g->por_revisar) }} de saldo por revisar · {{ $n($g->lineas_revision) }} líneas con diferencias</small>@endif
                            <a class="btn btn-sm btn-outline-primary iw-accion" href="{{ route('invierno.disponibilidad.detalle', array_merge(request()->except('page', 'codigo', 'situacion', 'orden', 'completa'), ['grupo' => $g->grupo])) }}">Ver detalle</a>
                        </td>
                        @foreach($etapas as $etapa => $etiqueta)
                            @include('invierno._valor-etapa', ['registro' => (array) $g])
                        @endforeach
                    </tr>
                @empty
                    <tr role="row"><td role="cell" colspan="6" class="iw-empty"><i class="fas fa-snowflake"></i><strong>No hay prendas de invierno para estos filtros.</strong>Revisá las remisiones registradas o limpiá los filtros.</td></tr>
                @endforelse
                </tbody>
                @if($paginados->total())
                    <tfoot role="rowgroup"><tr role="row" class="bg-light">
                        <td role="cell" class="iw-ficha font-weight-bold">TOTAL FILTRADO · {{ $n($resumen['grupos']) }} grupos</td>
                        @foreach($etapas as $etapa => $etiqueta)
                            @include('invierno._valor-etapa', ['registro' => $resumen, 'mostrarCero' => true])
                        @endforeach
                    </tr></tfoot>
                @endif
            </table>
        </div>
        @if($paginados->hasPages())<div class="card-footer bg-white">{{ $paginados->links('pagination::bootstrap-4') }}</div>@endif
    </div>
</div>
