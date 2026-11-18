@php
    $filtrosRemisiones = compact('desde', 'hasta', 'sucursal', 'linea', 'buscar') + [
        'pagina_remisiones' => $remisiones->currentPage(),
        'page' => $articulos->currentPage(),
    ];
@endphp
<section class="card mp-card" id="remisiones" aria-labelledby="mp-remisiones-titulo">
    <header class="card-header mp-card-header mp-card-acciones">
        <div>
            <h2 id="mp-remisiones-titulo">Remisiones y confirmaciones</h2>
            <p>Envíos del período filtrado con sus recepciones registradas, incluso en meses posteriores.</p>
        </div>
        <a class="btn btn-sm btn-outline-primary" href="#articulos">Ver artículos</a>
    </header>

    @if($remisiones->isNotEmpty())
        <dl class="mp-recepcion-totales">
            <div>
                <dt>Remisiones</dt>
                <dd>{{ $unidades($remisiones->total()) }}</dd>
            </div>
            <div class="mp-recepcion-confirmada">
                <dt>Unidades confirmadas</dt>
                <dd>{{ $unidades($confirmadas) }}</dd>
            </div>
            <div class="mp-recepcion-pendiente">
                <dt>Unidades pendientes</dt>
                <dd>{{ $unidades($pendientes) }}</dd>
            </div>
        </dl>
        <div class="table-responsive mp-tabla-scroll" tabindex="0" role="region" aria-label="Lista de remisiones">
            <table class="table table-hover mp-tabla mb-0">
                <thead>
                    <tr>
                        <th scope="col">Remisión</th>
                        <th scope="col">Fecha de envío</th>
                        <th scope="col">Destino</th>
                        <th scope="col" class="text-right">Enviadas</th>
                        <th scope="col" class="text-right">Confirmadas</th>
                        <th scope="col" class="text-right">Pendientes</th>
                        <th scope="col">Recepción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($remisiones as $documento)
                        @php
                            $numero = $documento->serie . '-' . $documento->numero_remision;
                            [$estado, $tono] = match (true) {
                                $documento->confirmadas === $documento->enviadas => ['Confirmada', 'confirmada'],
                                $documento->confirmadas > 0 => ['Parcial', 'parcial'],
                                default => ['En tránsito', 'pendiente'],
                            };
                        @endphp
                        <tr @class(['mp-fila-activa' => $remisionActiva && $remisionActiva->id === $documento->id])>
                            <th scope="row" class="text-nowrap">
                                <a class="mp-enlace mp-codigo"
                                    aria-label="Ver remisión {{ $numero }} con destino a {{ $documento->local }}"
                                    href="{{ route('accesorios.maria-pirelli.index', $filtrosRemisiones + ['remision' => $documento->id]) }}#detalle-remision">
                                    {{ $numero }} <i class="fas fa-angle-right ml-1" aria-hidden="true"></i>
                                </a>
                            </th>
                            <td class="text-nowrap">{{ $fecha($documento->fecha_envio) }}</td>
                            <td>{{ $documento->local }}</td>
                            <td class="text-right">{{ $unidades($documento->enviadas) }}</td>
                            <td class="text-right">{{ $unidades($documento->confirmadas) }}</td>
                            <td class="text-right">{{ $unidades($documento->enviadas - $documento->confirmadas) }}</td>
                            <td class="text-nowrap">
                                <span class="mp-estado mp-estado-{{ $tono }}">{{ $estado }}</span>
                                @if($documento->ultima_confirmacion)
                                    <small class="d-block mt-1 text-muted">Última {{ $fecha($documento->ultima_confirmacion) }}</small>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($remisiones->hasPages())
            <div class="card-footer">{{ $remisiones->links('pagination::bootstrap-4') }}</div>
        @endif
    @else
        <p class="mp-vacio">
            {{ $conRemisiones ? 'No hay remisiones de María Pirelli para estos filtros.' : 'La fuente de remisiones todavía no está disponible.' }}
        </p>
    @endif
</section>

@if($remisionActiva)
    <section class="card mp-card" id="detalle-remision" aria-labelledby="mp-detalle-titulo">
        <header class="card-header mp-card-header mp-card-acciones">
            <div>
                <h2 id="mp-detalle-titulo">Remisión {{ $remisionActiva->serie }}-{{ $remisionActiva->numero_remision }}</h2>
                <p>Casa Central → {{ $remisionActiva->local }} · Enviada el {{ $fecha($remisionActiva->fecha_envio) }}</p>
            </div>
            <a class="btn btn-sm btn-outline-secondary"
                href="{{ route('accesorios.maria-pirelli.index', $filtrosRemisiones) }}#remisiones">Volver a remisiones</a>
        </header>
        <div class="table-responsive mp-tabla-scroll" tabindex="0" role="region" aria-label="Artículos de la remisión">
            <table class="table table-hover mp-tabla mb-0">
                <thead>
                    <tr>
                        <th scope="col">Código</th>
                        <th scope="col">Descripción</th>
                        <th scope="col" class="text-right">Enviadas</th>
                        <th scope="col">Confirmación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lineasRemision as $fila)
                        <tr>
                            <th scope="row" class="mp-codigo text-nowrap">{{ $fila->codigo }}</th>
                            <td>{{ $fila->descripcion }}</td>
                            <td class="text-right">{{ $unidades($fila->cantidad) }}</td>
                            <td class="text-nowrap">
                                @if($fila->fecha_recepcion)
                                    <time class="mp-estado mp-estado-confirmada" datetime="{{ $fila->fecha_recepcion }}">
                                        {{ $fecha($fila->fecha_recepcion) }}
                                    </time>
                                @else
                                    <span class="mp-estado mp-estado-pendiente">Pendiente</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@elseif(request()->filled('remision'))
    <div class="alert alert-info" id="detalle-remision" role="status">La remisión seleccionada no está disponible con estos filtros.</div>
@endif
