@extends('layouts.app')

@section('content')
@php
    $money = function ($v) { return $v === null ? 'Sin dato' : 'Gs ' . number_format((float) $v, 0, ',', '.'); };
    $whole = function ($v) { return number_format((int) $v, 0, ',', '.'); };
    $maxSuc = max(1, (float) $sucursales->max('importe'));
    $peligros = $rankingCompleto->filter(function ($p) {
        return $p->reposicion !== null && $p->reposicion > 0;
    })->sortByDesc('reposicion')->take(6);
    $bajaRentabilidad = $rankingCompleto->filter(function ($p) {
        return $p->unidades > 0 && $p->margen !== null && $p->margen < 20;
    })->sortBy('margen')->take(4);
@endphp
<div id="accdash">
    <section class="acc-hero">
        <div class="acc-hero-top">
            <div class="acc-brand">
                <span class="acc-brand-icon"><i class="fas fa-chart-line"></i></span>
                <div>
                    <div class="acc-eyebrow">CONTROL COMERCIAL Y LOGÍSTICO</div>
                    <h1>Monitoreo de Accesorios <span>{{ \Carbon\Carbon::parse($hasta)->format('Y') }}</span></h1>
                    <p>Compras · FCO · Remisiones · Recepciones · Ventas · Reposición</p>
                </div>
            </div>
            <div class="acc-actions">
                <a href="{{ route('accesorios.listado') }}" class="acc-btn acc-btn-outline"><i class="fas fa-list"></i> Ver artículos</a>
                <a href="{{ route('accesorios.facturas.index') }}" class="acc-btn acc-btn-outline"><i class="fas fa-file-invoice-dollar"></i> Compras · Facturas</a>
                @role('ADMIN')
                <a href="{{ route('accesorios.importar.form') }}" class="acc-btn acc-btn-outline"><i class="fas fa-file-import"></i> Importar datos</a>
                <a href="{{ route('accesorios.exportar', request()->query()) }}" class="acc-btn acc-btn-solid"><i class="fas fa-file-csv"></i> Exportar informe</a>
                @endrole
            </div>
        </div>
        <div class="acc-meta">
            <span><i class="far fa-calendar-alt"></i> Ventas y remisiones: <strong>{{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}</strong></span>
            <span><i class="fas fa-cube"></i> <strong>{{ $catalogo->count() }}</strong> códigos en el análisis</span>
            <span><i class="fas fa-sync"></i> Última venta registrada: <strong>{{ $ultimaVenta ? \Carbon\Carbon::parse($ultimaVenta)->format('d/m/Y') : 'No disponible' }}</strong></span>
        </div>
    </section>

    @role('ADMIN')
    <div class="acc-source-toolbar">
        <strong><i class="fas fa-database mr-1"></i> Actualizar información:</strong>
        <a href="{{ route('accesorios.importar.form') }}"><i class="fas fa-box-open"></i> Compras y FCO</a>
        <a href="{{ route('ventas.importar.form') }}"><i class="fas fa-receipt"></i> Ventas</a>
        @can('redistribucionsugerencia update')
            <a href="{{ route('remisiones.index') }}"><i class="fas fa-truck"></i> Remisiones</a>
        @endcan
        <span class="acc-stock-source"><i class="fas fa-calculator"></i> Stock calculado con las importaciones anteriores</span>
    </div>
    @endrole

    @if(session('error'))<div class="alert alert-danger mt-3">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger mt-3">{{ $errors->first() }}</div>@endif

    <section class="acc-filter-panel">
        <div class="acc-section-heading"><i class="fas fa-filter"></i> Filtros del monitoreo <small>Los filtros se aplican al presionar Consultar</small></div>
        <form action="{{ route('accesorios.index') }}" method="GET" class="acc-filter-grid">
            <input type="hidden" name="agrupacion" value="{{ $agrupacion }}">
            <div><label for="acc-desde">Venta desde</label><input id="acc-desde" type="date" name="desde" value="{{ $desde }}" class="form-control form-control-sm"></div>
            <div><label for="acc-hasta">Venta hasta</label><input id="acc-hasta" type="date" name="hasta" value="{{ $hasta }}" class="form-control form-control-sm"></div>
            <div><label for="acc-sucursal">Sucursal</label>
                <select id="acc-sucursal" name="sucursal" class="form-control form-control-sm">
                    <option value="">Todas las sucursales</option>
                    @foreach($opciones['sucursales'] as $op)<option value="{{ $op }}" {{ $sucursal === $op ? 'selected' : '' }}>{{ $op }}</option>@endforeach
                </select>
            </div>
            <div><label for="acc-fco">Factura de compra (FCO)</label>
                <select id="acc-fco" name="fco" class="form-control form-control-sm">
                    <option value="">Todos los FCO</option>
                    @foreach($opciones['fcos'] as $op)<option value="{{ $op }}" {{ $fco === (string)$op ? 'selected' : '' }}>{{ $op }}</option>@endforeach
                </select>
            </div>
            <div><label for="acc-envio">Fecha de envío / compra</label>
                <select id="acc-envio" name="fecha_envio" class="form-control form-control-sm">
                    <option value="">Todas las fechas</option>
                    @foreach($opciones['fechasEnvio'] as $op)<option value="{{ $op }}" {{ $fechaEnvio === (string)$op ? 'selected' : '' }}>{{ \Carbon\Carbon::parse($op)->format('d/m/Y') }}</option>@endforeach
                </select>
            </div>
            <div><label for="acc-linea">Línea</label>
                <select id="acc-linea" name="linea" class="form-control form-control-sm">
                    <option value="">Todas las líneas</option>
                    @foreach($opciones['lineas'] as $op)<option value="{{ $op }}" {{ $linea === (string)$op ? 'selected' : '' }}>{{ $op }}</option>@endforeach
                </select>
            </div>
            <div><label for="acc-buscar">Código / descripción</label><input id="acc-buscar" type="text" name="buscar" value="{{ $buscar }}" placeholder="Ej.: MEDNI" class="form-control form-control-sm"></div>
            <div><label for="acc-orden">Ordenar ranking</label>
                <select id="acc-orden" name="orden" class="form-control form-control-sm">
                    <option value="ventas" {{ $orden === 'ventas' ? 'selected' : '' }}>Unidades vendidas</option>
                    <option value="importe" {{ $orden === 'importe' ? 'selected' : '' }}>Mayor facturación</option>
                    <option value="stock" {{ $orden === 'stock' ? 'selected' : '' }}>Menor stock conocido</option>
                    <option value="margen" {{ $orden === 'margen' ? 'selected' : '' }}>Menor margen estimado</option>
                    <option value="cobertura" {{ $orden === 'cobertura' ? 'selected' : '' }}>Menor cobertura</option>
                </select>
            </div>
            <div class="acc-filter-buttons">
                <button type="submit" class="acc-btn acc-btn-primary"><i class="fas fa-search"></i> Consultar</button>
                <a href="{{ route('accesorios.index') }}" class="acc-btn acc-btn-reset">Limpiar</a>
            </div>
        </form>
        @if($fco !== '' || $fechaEnvio !== '')
            <div class="acc-inline-note"><i class="fas fa-info-circle"></i> <strong>Al filtrar FCO o fecha de compra:</strong> se seleccionan los códigos incluidos en esas compras. Las ventas y remisiones corresponden a esos códigos, <strong>no se atribuyen a ese FCO específico</strong>.</div>
        @endif
    </section>

    <section class="acc-kpi-grid">
        <div class="acc-kpi"><div class="acc-kpi-top"><span>Venta neta registrada</span><i class="fas fa-chart-column fas fa-chart-bar"></i></div><strong>{{ $money($kpi->importe) }}</strong><small>Importe real de ventas y notas de crédito</small></div>
        <div class="acc-kpi"><div class="acc-kpi-top"><span>Ganancia bruta estimada</span><i class="fas fa-coins"></i></div><strong>{{ $money($gananciaEstimada) }}</strong><small>Costo promedio ponderado por código; no utilidad neta</small></div>
        <div class="acc-kpi"><div class="acc-kpi-top"><span>Margen bruto estimado</span><i class="fas fa-percent"></i></div><strong>{{ $margenEstimado === null ? 'Sin dato' : number_format($margenEstimado, 1, ',', '.') . '%' }}</strong><small>Basado en compras históricas; no atribuido por FCO</small></div>
        <div class="acc-kpi"><div class="acc-kpi-top"><span>Unidades vendidas netas</span><i class="fas fa-shopping-bag"></i></div><strong>{{ $whole($kpi->unidades) }}</strong><small>{{ $whole($kpi->brutas) }} brutas · {{ $whole($kpi->devoluciones) }} devueltas</small><button type="button" class="acc-detail-link" data-acc-tipo="ventas"><i class="fas fa-list-ul"></i> Ver más</button></div>
        <div class="acc-kpi"><div class="acc-kpi-top"><span>Ticket promedio</span><i class="fas fa-receipt"></i></div><strong>{{ $money($ticketPromedio) }}</strong><small>{{ $whole($kpi->tickets) }} comprobantes positivos distintos</small></div>
        <div class="acc-kpi {{ !$conStock ? 'acc-kpi-unavailable' : '' }}"><div class="acc-kpi-top"><span>Stock calculado</span><i class="fas fa-boxes"></i></div><strong>{{ $conStock ? $whole($stockTotal) : 'Sin dato' }}</strong><small>{{ $stockFuente ?: 'Sin movimientos para calcular stock' }}</small><button type="button" class="acc-detail-link" data-acc-tipo="stock"><i class="fas fa-list-ul"></i> Ver más</button></div>
    </section>

    <section class="acc-second-grid">
        <div class="acc-mini"><i class="fas fa-box-open"></i><div><span>Unidades compradas</span><strong>{{ $whole($comprasFiltradas->unidades) }}</strong><small>{{ $whole($comprasFiltradas->documentos) }} compras/FCO en alcance</small></div></div>
        <div class="acc-mini"><i class="fas fa-wallet"></i><div><span>Inversión de compras</span><strong>{{ $money($comprasFiltradas->inversion) }}</strong><small>No es costo de venta realizado</small></div></div>
        <div class="acc-mini"><i class="fas fa-calculator"></i><div><span>Costo de ventas estimado</span><strong>{{ $money($costoEstimado) }}</strong><small>Por costo promedio de compra</small></div></div>
        <div class="acc-mini"><i class="fas fa-tags"></i><div><span>Descuentos registrados</span><strong>{{ $money($kpi->descuento) }}</strong><small>Según archivo de ventas</small></div></div>
        <div class="acc-mini acc-mini-warn"><i class="fas fa-truck"></i><div><span>En tránsito documental</span><strong>{{ $whole($remisiones->transito) }}</strong><small>Sin recepción, remisiones del período</small></div></div>
        <div class="acc-mini {{ $conStock && $productosCriticos > 0 ? 'acc-mini-warn' : '' }}">
            <i class="fas fa-exclamation-circle"></i>
            <div><span>Estado de reposición</span>
                <strong>{{ !$conStock ? 'SIN DATOS' : ($productosCriticos > 0 ? 'URGENTE' : ($productosReponer > 0 ? 'REVISAR' : 'SIN ALERTAS')) }}</strong>
                <small>{{ $conStock ? $whole($productosCriticos) . ' códigos con cobertura menor a 7 días' : 'No hay remisiones/ventas vinculadas' }}</small>
            </div>
        </div>
    </section>

    <section class="acc-main-grid">
        <div class="acc-panel acc-ranking">
            <div class="acc-panel-head">
                <div><h2><i class="fas fa-trophy"></i> Ranking de artículos</h2><p>Desempeño comercial y saldos documentales calculados</p></div>
                <span class="acc-light-tag">{{ $whole($rankingCompleto->count()) }} artículos</span>
            </div>
            <div class="acc-table-scroll">
                <table class="acc-table">
                    <thead><tr>
                        <th>Artículo</th><th class="acc-num">Vendidas netas</th><th class="acc-num">Facturación</th>
                        <th class="acc-num">Stock*</th><th class="acc-num">Margen est.</th><th class="acc-num">Cobertura</th>
                        <th class="acc-num">Reponer**</th><th></th>
                    </tr></thead>
                    <tbody>
                        @forelse($rankingPagina as $p)
                            <tr>
                                <td><a class="acc-code" href="{{ route('accesorios.show', $p->id) }}">{{ $p->codigo }}</a><span class="acc-description">{{ $p->descripcion }}</span><span class="acc-muted">{{ $p->linea }}</span></td>
                                <td class="acc-num acc-bold">{{ $whole($p->unidades) }}</td>
                                <td class="acc-num">{{ $money($p->importe) }}</td>
                                <td class="acc-num">
                                    @if($p->stock === null)<span class="acc-unknown">Sin dato</span>
                                    @elseif($p->cobertura_dias !== null && $p->cobertura_dias < 7)<span class="acc-status acc-red">{{ $whole($p->stock) }}</span>
                                    @elseif($p->cobertura_dias !== null && $p->cobertura_dias < 14)<span class="acc-status acc-amber">{{ $whole($p->stock) }}</span>
                                    @else<span class="acc-status acc-green">{{ $whole($p->stock) }}</span>@endif
                                </td>
                                <td class="acc-num">{{ $p->margen === null ? '—' : number_format($p->margen, 1, ',', '.') . '%' }}</td>
                                <td class="acc-num">{{ $p->cobertura_dias === null ? '—' : number_format($p->cobertura_dias, 1, ',', '.') . ' días' }}</td>
                                <td class="acc-num acc-bold">{{ $p->reposicion === null ? '—' : ($p->reposicion > 0 ? $whole($p->reposicion) : '0') }}</td>
                                <td><a href="{{ route('accesorios.show', $p->id) }}" class="acc-view" title="Detalle del accesorio"><i class="fas fa-chevron-right"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="acc-empty">No hay accesorios para los filtros seleccionados. @role('ADMIN')<a href="{{ route('accesorios.importar.form') }}">Importar compras</a>@endrole</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="acc-panel-foot">
                <span>* Saldo documental calculado con todo el historial hasta la fecha Hasta (no depende de Desde). ** Objetivo orientativo de 30 días según ventas netas recientes.</span>
                <div>{{ $rankingPagina->links() }}</div>
            </div>
        </div>

        <div class="acc-side">
            <div class="acc-panel">
                <div class="acc-panel-head"><div><h2><i class="fas fa-route"></i> Circuito de distribución</h2><p>Etapas documentales: no sumar las cantidades entre sí</p></div></div>
                <div class="acc-flow">
                    <div class="acc-flow-item"><span class="acc-flow-icon"><i class="fas fa-warehouse"></i></span><div><b>Depósito → Casa Central</b><small>Entrada recibida en Central</small></div><strong>{{ $whole($remisiones->deposito_central) }}</strong></div>
                    <div class="acc-flow-arrow"><i class="fas fa-arrow-down"></i></div>
                    <div class="acc-flow-item"><span class="acc-flow-icon"><i class="fas fa-truck-loading"></i></span><div><b>Casa Central → locales</b><small>Distribución (mismas prendas, nueva etapa)</small></div><strong>{{ $whole($remisiones->central_locales) }}</strong></div>
                    <div class="acc-flow-divider"></div>
                    <div class="acc-flow-duo"><div><small>Recibidas en locales desde Central</small><b>{{ $whole($remisiones->recibidas) }}</b></div><div><small>Documentos del período</small><b>{{ $whole($remisiones->documentos) }}</b></div></div>
                    <div class="acc-flow-duo"><div><small>En tránsito documental</small><b class="acc-danger">{{ $whole($remisiones->transito) }}</b></div><div><small>Distribuidas desde Central aún sin recepción</small><b>{{ $whole(max(0, (int) $remisiones->central_locales - (int) $remisiones->recibidas)) }}</b></div></div>
                    <p class="small text-muted mb-0">Cada cifra corresponde a una etapa. Las prendas que llegan de Depósito y luego se distribuyen desde Central <strong>no se suman dos veces</strong>.</p>
                    <div class="acc-flow-divider"></div>
                    <div class="acc-flow-duo">
                        <div><small>Cobertura global estimada</small><b>{{ $coberturaGlobal === null ? 'Sin dato' : number_format($coberturaGlobal, 1, ',', '.') . ' días' }}</b></div>
                        <div><small>Códigos críticos</small><b class="{{ $productosCriticos > 0 ? 'acc-danger' : '' }}">{{ $conStock ? $whole($productosCriticos) : 'Sin dato' }}</b></div>
                    </div>
                </div>
            </div>
            <div class="acc-panel">
                <div class="acc-panel-head"><div><h2><i class="fas fa-exclamation-triangle"></i> Alertas para revisar</h2><p>Derivadas de los datos disponibles</p></div></div>
                <div class="acc-alert-list">
                    @forelse($alertas as $alerta)
                        <div class="acc-alert acc-alert-{{ $alerta['tipo'] }}"><i class="fas fa-circle"></i><div><strong>{{ $alerta['titulo'] }}</strong><p>{{ $alerta['texto'] }}</p></div></div>
                    @empty<div class="acc-empty">Sin alertas automáticas con los filtros actuales.</div>@endforelse
                </div>
            </div>
        </div>
    </section>

    <section class="acc-panel mb-3">
        <div class="acc-panel-head">
            <div>
                <h2><i class="fas fa-store"></i> Existencias calculadas por sucursal</h2>
                <p>Todos los movimientos históricos hasta {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }} · entradas recibidas − salidas remitidas − ventas netas</p>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap:8px">
                <span class="acc-light-tag">Corte {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}</span>
                <button type="button" class="acc-btn acc-btn-primary" data-acc-tipo="stock"><i class="fas fa-list-ul"></i> Ver más</button>
                @role('ADMIN')
                <a href="{{ route('accesorios.stock.exportar', request()->query()) }}" class="acc-btn acc-btn-primary"><i class="fas fa-file-csv"></i> Exportar stock por local</a>
                @endrole
            </div>
        </div>
        <div class="acc-table-scroll">
            <table class="acc-table">
                <thead><tr>
                    <th>Sucursal</th>
                    <th class="acc-num">Entradas recibidas</th>
                    <th class="acc-num">Salidas enviadas</th>
                    <th class="acc-num">Ventas netas</th>
                    <th class="acc-num">En tránsito hacia local</th>
                    <th class="acc-num">Stock calculado</th>
                    <th>Códigos con movimientos</th>
                </tr></thead>
                <tbody>
                    @forelse($saldosSucursal as $local)
                        <tr>
                            <td><strong>{{ $local->sucursal }}</strong></td>
                            <td class="acc-num">{{ $whole($local->entradas) }}</td>
                            <td class="acc-num">{{ $whole($local->salidas) }}</td>
                            <td class="acc-num">{{ $whole($local->ventas) }}</td>
                            <td class="acc-num">{{ $whole($local->en_transito) }}</td>
                            <td class="acc-num">
                                <span class="acc-status {{ $local->saldo < 0 ? 'acc-red' : ($local->saldo == 0 ? 'acc-amber' : 'acc-green') }}">
                                    {{ $whole($local->saldo) }}
                                </span>
                            </td>
                            <td>{{ $whole($local->codigos) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="acc-empty">No existen remisiones ni ventas relacionadas para este corte.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="acc-panel-foot">Cuando un local recibe una transferencia de cualquier origen, sus entradas aumentan. Las ventas se descuentan una sola vez; las notas de crédito negativas revierten ventas.</div>
    </section>

    <section class="acc-charts-grid">
        <div class="acc-panel">
            <div class="acc-panel-head"><div><h2><i class="fas fa-store"></i> Ventas netas por sucursal</h2><p>Importes reales según sucursal del comprobante</p></div><button type="button" class="acc-btn acc-btn-primary" data-acc-tipo="ventas"><i class="fas fa-list-ul"></i> Ver más</button></div>
            <div class="acc-bars">
                @forelse($sucursales as $s)
                    @php($anchura = max(0, min(100, (float)$s->importe / $maxSuc * 100)))
                    <div class="acc-bar-row"><span title="{{ $s->local }}">{{ $s->local ?: 'Sin sucursal' }}</span><div class="acc-track"><div class="acc-fill" style="width: {{ $anchura }}%"></div></div><b>{{ $money($s->importe) }}</b></div>
                @empty<div class="acc-empty">No hay ventas para el período seleccionado.</div>@endforelse
            </div>
        </div>
        <div class="acc-panel" id="acc-evolucion">
            <div class="acc-panel-head acc-evolution-head">
                <div>
                    <h2><i class="fas fa-chart-area"></i> Evolución de ventas</h2>
                    <p>Facturación neta real · agrupación {{ ['dia' => 'diaria', 'semana' => 'semanal', 'mes' => 'mensual'][$agrupacion] }}</p>
                </div>
                <nav class="acc-evolution-switch" aria-label="Agrupar evolución de ventas">
                    @foreach(['dia' => ['Día', 'fa-calendar-day'], 'semana' => ['Semana', 'fa-calendar-week'], 'mes' => ['Mes', 'fa-calendar-alt']] as $modo => $opcion)
                        <a href="{{ route('accesorios.index', array_merge(request()->except(['page', 'agrupacion']), ['agrupacion' => $modo])) }}#acc-evolucion"
                           class="acc-evolution-option {{ $agrupacion === $modo ? 'active' : '' }}"
                           aria-current="{{ $agrupacion === $modo ? 'true' : 'false' }}"
                           title="Agrupar facturación por {{ strtolower($opcion[0]) }} sin cambiar los filtros">
                            <i class="fas {{ $opcion[1] }}"></i> {{ $opcion[0] }}
                        </a>
                    @endforeach
                </nav>
            </div>
            <div class="acc-month-chart acc-chart-{{ $agrupacion }}">
                @forelse($evolucion as $m)
                    <div class="acc-month" title="{{ $m->descripcion }} · {{ $money($m->importe) }} · {{ $whole($m->unidades) }} unidades netas">
                        <div class="acc-month-plot">
                            @if((float) $m->importe != 0)
                                <div class="acc-month-bar {{ $m->negativo ? 'is-negative' : '' }}" style="height: {{ $m->altura }}%"></div>
                            @else
                                <div class="acc-month-no-sales" title="Sin facturación neta en este período"></div>
                            @endif
                        </div>
                        <span>{{ $m->etiqueta }}</span>
                        <small>{{ $whole($m->unidades) }} un.</small>
                    </div>
                @empty
                    <div class="acc-empty">No hay ventas para mostrar en el período seleccionado.</div>
                @endforelse
            </div>
            <div class="acc-evolution-footer">
                <i class="fas fa-info-circle"></i>
                El gráfico respeta el rango {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }}–{{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }} y los demás filtros.
                @if($agrupacion === 'semana')
                    Cada semana comienza el lunes; las semanas incompletas incluyen solo las fechas seleccionadas.
                @endif
                @if($diasPeriodo === 1)
                    Elegiste un solo día. Ampliá el rango Desde/Hasta para comparar varios días, semanas o meses.
                @endif
                @unless($evolucionPeriodosCompletos)
                    El rango es muy amplio: se muestran los períodos con movimientos; los intervalos sin ventas se omiten.
                @endunless
            </div>
        </div>
    </section>

    <section class="acc-bottom-grid">
        <div class="acc-panel">
            <div class="acc-panel-head"><div><h2><i class="fas fa-lightbulb"></i> Decisiones sugeridas</h2><p>Prioridades calculadas con las ventas recientes y stock disponible</p></div></div>
            <div class="acc-decisions">
                @if(!$conStock)
                    <div class="acc-decision"><span>1</span><p><strong>Importar remisiones y ventas.</strong> El saldo se calcula automáticamente por código y sucursal cuando existan movimientos enlazados.</p></div>
                @elseif($peligros->isNotEmpty())
                    @foreach($peligros as $i => $p)
                        <div class="acc-decision"><span>{{ $i + 1 }}</span><p><strong>Revisar {{ $p->codigo }}.</strong> Reposición orientativa de {{ $whole($p->reposicion) }} unidades para alcanzar 30 días de demanda reciente. <a href="{{ route('accesorios.show', $p->id) }}">Ver trazabilidad</a>.</p></div>
                    @endforeach
                @else
                    <div class="acc-decision"><span><i class="fas fa-check"></i></span><p>Con la velocidad comercial disponible, no hay reposiciones sugeridas con el historial de remisiones y ventas analizado.</p></div>
                @endif
                @foreach($bajaRentabilidad as $p)
                    <div class="acc-decision"><span><i class="fas fa-percentage"></i></span><p><strong>Revisar rentabilidad de {{ $p->codigo }}</strong>: margen bruto estimado {{ number_format($p->margen, 1, ',', '.') }}%. Verificar costo real y descuentos antes de ajustar precios.</p></div>
                @endforeach
            </div>
        </div>
        <div class="acc-panel">
            <div class="acc-panel-head"><div><h2><i class="fas fa-shipping-fast"></i> Remisiones pendientes de recepción</h2><p>Primeras remisiones sin confirmación, ordenadas por fecha</p></div></div>
            <div class="acc-table-scroll">
                <table class="acc-table acc-table-compact"><thead><tr><th>Remisión</th><th>Artículo</th><th>Destino</th><th class="acc-num">Un.</th><th>Fecha</th></tr></thead>
                    <tbody>
                    @forelse($pendientes as $p)
                        <tr><td>{{ $p->serie }} - {{ $p->numero_remision }}</td><td>{{ $p->codigo }}</td><td>{{ $p->sucursal_destino }}</td><td class="acc-num">{{ $whole($p->cantidad) }}</td><td>{{ $p->fecha_remision ? \Carbon\Carbon::parse($p->fecha_remision)->format('d/m/Y') : '—' }}</td></tr>
                    @empty<tr><td colspan="5" class="acc-empty">No hay remisiones sin recepción dentro de los filtros.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <div class="acc-footer-note">
        <i class="fas fa-info-circle"></i>
        <div><strong>Criterios de interpretación.</strong>
            Ventas e importes provienen de <code>ventas</code>; compras y costos de <code>accesorios_compras</code>; logística de <code>ot_logistica_remisiones</code>.
            El stock se recalcula desde cero al abrir el panel, con el historial de remisiones y ventas desde el primer registro hasta {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}. No se utiliza un Excel externo de stock ni se suman compras/FCO al saldo. Envíos pendientes reducen el stock del origen, pero no aumentan el del destino hasta su fecha de recepción. Saldos negativos se muestran para revisión. El cálculo supone que las importaciones históricas son completas.
            El costo de las unidades vendidas y el margen se <strong>estiman</strong> con costo promedio ponderado de todas las compras del código, sin asignación por FCO. Reposición y cobertura requieren ventas en los últimos 30 días terminados en {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}.
            Las remisiones pueden volver a transportar la misma unidad, por eso sus totales no equivalen a stock. No se incluyen gastos operativos ni se calcula utilidad neta real.
        </div>
    </div>
</div>


<div class="modal fade" id="accDetalleModal" data-url="{{ route('accesorios.detalle.resumen') }}" tabindex="-1" role="dialog" aria-labelledby="accDetalleTitulo" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title font-weight-bold" id="accDetalleTitulo">Detalle por código y sucursal</h5>
                    <small class="text-muted">Según los filtros aplicados en Monitoreo de Accesorios</small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-end align-items-center mb-3">
                    <label for="accDetalleOrden" class="mb-0 mr-2 small font-weight-bold">Ordenar por</label>
                    <select id="accDetalleOrden" class="form-control form-control-sm" style="max-width:220px">
                        <option value="codigo">Código (A–Z)</option>
                        <option value="cantidad">Cantidad (mayor a menor)</option>
                    </select>
                </div>
                <div id="accDetalleContenido" aria-live="polite"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('page_css')
<style>
    #accdash{--acc-navy:#0c2351;--acc-blue:#17499b;--acc-blue2:#2869c6;--acc-line:#dfe7f3;--acc-muted:#63718a;--acc-bg:#f4f7fc;box-sizing:border-box;max-width:100%;width:100%;min-width:0;overflow-x:clip;color:#172d52;background:var(--acc-bg);padding:16px 15px 28px;margin:0;font-family:Roboto,Arial,sans-serif}
    #accdash *{box-sizing:border-box}
    #accdash .acc-hero{background:linear-gradient(113deg,#0d2e73,#1652ad 70%,#2471d4);color:#fff;padding:23px 25px 15px;border-radius:16px;box-shadow:0 10px 28px rgba(23,72,155,.16);margin-bottom:15px}
    #accdash .acc-hero-top{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;flex-wrap:wrap}
    #accdash .acc-brand{display:flex;align-items:center;gap:16px;min-width:0}
    #accdash .acc-brand>div{min-width:0}
    #accdash .acc-brand-icon{display:grid;place-items:center;flex:0 0 58px;background:rgba(255,255,255,.14);width:58px;height:58px;border-radius:14px;font-size:23px}
    #accdash .acc-eyebrow{font-size:10px;letter-spacing:2px;font-weight:800;color:#c3d9ff;margin-bottom:5px}
    #accdash .acc-hero h1{margin:0;font-size:clamp(24px,2vw,34px);font-weight:800;letter-spacing:-.7px}
    #accdash h1 span{color:#b7d9ff}
    #accdash .acc-brand p{margin:7px 0 0;font-size:13px;color:#d8e8ff}
    #accdash .acc-actions{display:flex;gap:7px;flex-wrap:wrap;max-width:100%}
    #accdash .acc-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;border-radius:8px;padding:9px 13px;min-height:36px;font-weight:700;font-size:12px;text-decoration:none!important;border:1px solid transparent;white-space:nowrap;cursor:pointer}
    #accdash .acc-btn-outline{color:white;border-color:rgba(255,255,255,.4);background:rgba(255,255,255,.09)}
    #accdash .acc-btn-outline:hover{color:#fff;background:rgba(255,255,255,.2)}
    #accdash .acc-btn-solid{background:white;color:#154a9a}
    #accdash .acc-btn-primary{background:#1552a9;color:#fff}
    #accdash .acc-btn-reset{color:#56647e;background:#eaf0f8}
    #accdash .acc-meta{display:flex;gap:22px;flex-wrap:wrap;margin-top:20px;padding-top:12px;border-top:1px solid rgba(255,255,255,.25);font-size:11px;color:#dceaff}
    #accdash .acc-meta i{margin-right:6px}
    #accdash .acc-source-toolbar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0 0 14px;color:#59708f;font-size:11px}
    #accdash .acc-source-toolbar strong{font-size:11px;margin-right:4px}
    #accdash .acc-stock-source{display:inline-flex;gap:6px;align-items:center;background:#e9f5ed;color:#24734c;padding:6px 10px;border-radius:7px;font-size:11px;font-weight:700}
    #accdash .acc-source-toolbar a{display:inline-flex;align-items:center;gap:6px;color:#205ba4;background:#e8f0fc;border:1px solid #d3e2f6;padding:6px 10px;border-radius:7px;font-size:11px;font-weight:700}
    #accdash .acc-source-toolbar a:hover{background:#dceafd}
    #accdash .acc-filter-panel,#accdash .acc-panel{border:1px solid var(--acc-line);background:#fff;border-radius:12px;box-shadow:0 2px 9px rgba(20,54,99,.035)}
    #accdash .acc-filter-panel{padding:16px 18px;margin-bottom:14px}
    #accdash .acc-section-heading{font-weight:800;font-size:14px;margin-bottom:12px;color:#153c7c}
    #accdash .acc-section-heading i{margin-right:7px}
    #accdash .acc-section-heading small{font-size:11px;color:var(--acc-muted);font-weight:400;margin-left:8px}
    #accdash .acc-filter-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:11px 14px;align-items:end}
    #accdash .acc-filter-grid label{font-weight:700;color:#3d506e;font-size:11px;display:block;margin-bottom:5px}
    #accdash .acc-filter-grid .form-control{border:1px solid #d6dfeb;border-radius:7px;background:#fff;height:35px;width:100%;font-size:12px;color:#25436e}
    #accdash .acc-filter-buttons{display:flex;gap:7px;align-items:center}
    #accdash .acc-inline-note{font-size:11px;background:#f2f7ff;color:#365582;border:1px solid #d9e6fa;padding:10px 12px;border-radius:8px;margin-top:12px}
    #accdash .acc-kpi-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin-bottom:12px}
    #accdash .acc-kpi{background:#fff;border:1px solid var(--acc-line);border-radius:12px;padding:17px 15px;min-height:132px;box-shadow:0 2px 7px rgba(30,59,100,.035);min-width:0}
    #accdash .acc-kpi-top{display:flex;align-items:center;justify-content:space-between;gap:6px;color:#526587;font-size:12px;font-weight:700}
    #accdash .acc-kpi-top i{color:#1b60bb;background:#e8f2ff;padding:9px;border-radius:8px;font-size:15px}
    #accdash .acc-kpi strong{display:block;color:#123472;font-size:clamp(17px,1.5vw,25px);font-weight:900;letter-spacing:-.6px;margin-top:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    #accdash .acc-kpi small{display:block;color:#72809a;margin-top:5px;font-size:10px;line-height:1.35}
    #accdash .acc-kpi-unavailable strong{color:#748198}
    #accdash .acc-second-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:16px}
    #accdash .acc-mini{background:#fff;border:1px solid var(--acc-line);border-radius:11px;padding:12px;display:flex;align-items:center;gap:11px;min-width:0}
    #accdash .acc-mini>i{flex-shrink:0;background:#edf4ff;color:#2261b3;border-radius:9px;min-width:41px;height:41px;display:grid;place-items:center}
    #accdash .acc-mini span{display:block;font-size:11px;color:#536a89;font-weight:700}
    #accdash .acc-mini strong{display:block;color:#123472;font-size:20px;font-weight:900;margin:4px 0}
    #accdash .acc-mini small{display:block;font-size:10px;color:#8993a5}
    #accdash .acc-mini-warn>i{color:#b75b12;background:#fff4e6}
    #accdash .acc-mini-warn strong{color:#b75b12}
    #accdash .acc-main-grid{display:grid;grid-template-columns:minmax(0,1.8fr) minmax(310px,.78fr);gap:14px;margin-bottom:14px;align-items:start}
    #accdash .acc-panel{overflow:hidden}
    #accdash .acc-panel-head{display:flex;align-items:center;justify-content:space-between;gap:8px;border-bottom:1px solid #e7edf6;padding:16px 17px}
    #accdash .acc-panel-head h2{font-size:14px;font-weight:800;color:#123872;margin:0}
    #accdash .acc-panel-head h2 i{color:#2167c4;margin-right:7px}
    #accdash .acc-panel-head p{font-size:10px;color:#8792a8;margin:5px 0 0}
    #accdash .acc-light-tag{background:#eaf3ff;color:#235da9;padding:6px 9px;border-radius:20px;white-space:nowrap;font-size:10px;font-weight:700}
    #accdash .acc-table-scroll{width:100%;overflow-x:auto}
    #accdash .acc-table{width:100%;border-collapse:collapse;font-size:11px;white-space:nowrap}
    #accdash .acc-table thead{background:#f1f5fb;color:#536481}
    #accdash .acc-table th{padding:12px 10px;font-size:10px;font-weight:800;letter-spacing:.1px;text-transform:uppercase}
    #accdash .acc-table td{padding:10px;border-top:1px solid #edf2f8;vertical-align:middle}
    #accdash .acc-table tbody tr:hover{background:#f6faff}
    #accdash .acc-num{text-align:right;font-variant-numeric:tabular-nums}
    #accdash .acc-bold{font-weight:800;color:#1c4078}
    #accdash .acc-code{display:block;font-size:11px;color:#1255a8;font-weight:800}
    #accdash .acc-description{display:block;max-width:190px;overflow:hidden;text-overflow:ellipsis;font-size:11px;color:#2d4466;margin-top:3px}
    #accdash .acc-muted{display:block;color:#9aa5b8;font-size:9px;margin-top:2px}
    #accdash .acc-status{display:inline-block;min-width:32px;text-align:center;padding:5px 9px;border-radius:6px;font-weight:800}
    #accdash .acc-red{color:#bd2730;background:#ffebec}
    #accdash .acc-amber{color:#aa6b0c;background:#fff0d4}
    #accdash .acc-green{color:#218052;background:#e2f5e9}
    #accdash .acc-unknown{color:#8793a4;font-size:10px}
    #accdash .acc-view{color:#3676c0;padding:8px}
    #accdash .acc-panel-foot{padding:12px 16px;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;border-top:1px solid #e8eef7;font-size:10px;color:#8791a5}
    #accdash .acc-panel-foot .pagination{margin:0}
    #accdash .acc-panel-foot .page-link{font-size:10px;padding:5px 8px}
    #accdash .acc-side{display:flex;flex-direction:column;gap:14px}
    #accdash .acc-flow{padding:13px 16px}
    #accdash .acc-flow-item{display:flex;align-items:center;gap:11px;padding:7px 0}
    #accdash .acc-flow-icon{background:#ecf4ff;color:#2567bb;padding:12px;border-radius:10px;width:43px;text-align:center}
    #accdash .acc-flow-item>div{flex:1}
    #accdash .acc-flow-item b{display:block;font-size:12px}
    #accdash .acc-flow-item small{display:block;color:#8793a8;margin-top:3px;font-size:10px}
    #accdash .acc-flow-item strong{font-size:23px;color:#0e3775}
    #accdash .acc-flow-arrow{padding-left:14px;color:#94add1}
    #accdash .acc-flow-divider{border-top:1px dashed #d8e2f0;margin:12px 0}
    #accdash .acc-flow-duo{display:flex;justify-content:space-between;gap:10px;margin-bottom:12px}
    #accdash .acc-flow-duo>div{width:50%}
    #accdash .acc-flow-duo small{display:block;color:#7c8ba2;font-size:11px}
    #accdash .acc-flow-duo b{display:block;color:#183b75;font-size:20px;margin-top:3px}
    #accdash .acc-flow-duo .acc-danger{color:#c33138}
    #accdash .acc-alert-list{padding:12px}
    #accdash .acc-alert{display:flex;gap:10px;padding:10px 11px;border-radius:8px;margin-bottom:8px;font-size:11px}
    #accdash .acc-alert:last-child{margin-bottom:0}
    #accdash .acc-alert>i{font-size:9px;margin-top:3px}
    #accdash .acc-alert strong{display:block;font-size:11px}
    #accdash .acc-alert p{font-size:10px;line-height:1.45;margin:4px 0 0}
    #accdash .acc-alert-danger{background:#fff0f1;color:#a12835}
    #accdash .acc-alert-warning{background:#fff6e7;color:#975b1a}
    #accdash .acc-alert-info{background:#edf5ff;color:#265c99}
    #accdash .acc-charts-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px}
    #accdash .acc-bars{padding:16px}
    #accdash .acc-bar-row{display:grid;grid-template-columns:125px 1fr 100px;align-items:center;gap:10px;margin-bottom:13px;font-size:10px}
    #accdash .acc-bar-row>span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#445b7d;font-weight:700}
    #accdash .acc-bar-row>b{text-align:right;font-size:10px;color:#254b88}
    #accdash .acc-track{height:13px;background:#e9f1fc;border-radius:5px;overflow:hidden}
    #accdash .acc-fill{height:100%;border-radius:5px;background:linear-gradient(90deg,#225fba,#58a0f0)}
    #accdash .acc-month-chart{padding:18px;display:flex;gap:9px;min-height:235px;overflow-x:auto;align-items:stretch}
    #accdash .acc-evolution-head{flex-wrap:wrap}
    #accdash .acc-evolution-switch{display:inline-flex;align-items:center;gap:3px;flex-shrink:0;padding:4px;border:1px solid #dce6f5;background:#f2f6fc;border-radius:9px}
    #accdash .acc-evolution-option{display:inline-flex;align-items:center;gap:6px;padding:7px 11px;font-size:11px;font-weight:800;border-radius:6px;color:#6580a3;text-decoration:none!important;transition:background .15s,color .15s}
    #accdash .acc-evolution-option:hover{color:#144b99;background:#e3efff}
    #accdash .acc-evolution-option.active{background:#1d5eac;color:#fff;box-shadow:0 2px 5px rgba(25,77,158,.18)}
    #accdash .acc-evolution-option.active:hover{background:#164d99;color:#fff}
    #accdash .acc-evolution-option i{font-size:11px}
    #accdash .acc-evolution-footer{padding:10px 17px 14px;border-top:1px solid #e8eef7;color:#7689a5;font-size:10px;line-height:1.6}
    #accdash .acc-evolution-footer i{color:#3579c5;margin-right:5px}
    #accdash .acc-chart-dia .acc-month{min-width:44px}
    #accdash .acc-chart-semana .acc-month{min-width:75px}
    #accdash .acc-chart-mes .acc-month{min-width:40px}
    #accdash .acc-month-bar.is-negative{background:linear-gradient(180deg,#c9515b,#e99a9f)}
    #accdash .acc-month-no-sales{width:18px;height:3px;background:#c4d1e2;border-radius:2px}

    #accdash .acc-month{flex:1;min-width:36px;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;text-align:center;color:#60728f;font-size:10px}
    #accdash .acc-month-plot{height:145px;min-height:145px;width:100%;display:flex;align-items:flex-end;justify-content:center;border-bottom:1px solid #dbe5f3;background:repeating-linear-gradient(to top,transparent 0,transparent 35px,#f1f5fb 36px)}
    #accdash .acc-month-bar{width:min(42px,66%);min-height:3px;border-radius:5px 5px 0 0;background:linear-gradient(180deg,#256cca,#75abec)}
    #accdash .acc-month>span{margin-top:9px;font-weight:700;color:#4f6486}
    #accdash .acc-month>small{margin-top:3px;color:#9aa7ba;font-size:9px}
    #accdash .acc-bottom-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px}
    #accdash .acc-decisions{padding:15px 18px}
    #accdash .acc-decision{display:flex;gap:11px;align-items:flex-start;margin-bottom:13px}
    #accdash .acc-decision>span{background:#e6f0ff;border-radius:50%;width:29px;height:29px;flex-shrink:0;display:grid;place-items:center;font-size:11px;font-weight:900;color:#2466b9}
    #accdash .acc-decision p{font-size:11px;color:#546985;line-height:1.5;margin:4px 0}
    #accdash .acc-decision strong{color:#24476f}
    #accdash .acc-empty{text-align:center;padding:20px;color:#8492a9;font-size:12px}
    #accdash .acc-table-compact td{font-size:11px}
    #accdash .acc-footer-note{display:flex;align-items:flex-start;gap:12px;background:#ecf3fc;border:1px solid #dce6f5;padding:14px 16px;border-radius:10px;color:#5b6f8f;font-size:11px;line-height:1.65}
    #accdash .acc-footer-note>i{font-size:17px;color:#3271bc;margin-top:2px}
    #accdash code{font-size:10px;color:#365d90;background:transparent}
    @media(max-width:1500px){#accdash .acc-kpi-grid{grid-template-columns:repeat(3,minmax(0,1fr))}#accdash .acc-second-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
    @media(max-width:1150px){#accdash .acc-main-grid{grid-template-columns:1fr}#accdash .acc-side{display:grid;grid-template-columns:1fr 1fr}#accdash .acc-filter-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
    @media(max-width:800px){#accdash .acc-kpi-grid,#accdash .acc-second-grid{grid-template-columns:repeat(2,minmax(0,1fr))}#accdash .acc-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}#accdash .acc-side,#accdash .acc-charts-grid,#accdash .acc-bottom-grid{grid-template-columns:1fr}#accdash .acc-hero{padding:20px}#accdash .acc-meta{gap:10px}#accdash .acc-brand-icon{display:none}}
    @media(max-width:500px){#accdash{padding:12px 10px 22px}#accdash .acc-kpi-grid,#accdash .acc-second-grid{grid-template-columns:1fr}#accdash .acc-filter-grid{grid-template-columns:1fr}#accdash .acc-actions{width:100%}#accdash .acc-actions .acc-btn{flex:1 1 auto}#accdash .acc-meta{grid-template-columns:1fr}#accdash .acc-bar-row{grid-template-columns:82px 1fr 85px}#accdash .acc-hero h1{font-size:24px}}

    #accdash .acc-detail-link{display:inline-flex;gap:5px;align-items:center;border:0;background:none;padding:4px 0;margin-top:6px;color:#165bad;font-size:11px;font-weight:800;cursor:pointer}
    #accdash .acc-detail-link:hover{text-decoration:underline}
    #accDetalleModal .modal-dialog{max-width:min(1080px,96vw)}
    #accDetalleModal .modal-content{border:0;border-radius:14px;overflow:hidden;box-shadow:0 20px 65px rgba(11,38,81,.27)}
    #accDetalleModal .modal-header{background:linear-gradient(110deg,#113773,#1c5cb0);color:#fff;padding:20px 24px;border:0}
    #accDetalleModal .modal-title{font-size:18px;font-weight:800}
    #accDetalleModal .modal-header .text-muted{color:#e1edff!important;font-size:12px}
    #accDetalleModal .modal-header .close{color:#fff;text-shadow:none;opacity:1;font-size:30px}
    #accDetalleModal .modal-body{background:#fff;max-height:70vh;overflow-y:auto;padding:20px 24px}
    #accDetalleModal .modal-body>div:first-child{background:#f0f5fc;padding:10px 12px;border-radius:9px;border:1px solid #dbe6f5;margin-bottom:14px!important;color:#294c7b}
    #accDetalleModal .modal-body #accDetalleOrden{border:1px solid #96b5df;background:#fff;color:#153d73;font-weight:700}
    #accDetalleModal .acc-detail-overview{display:flex;gap:9px;flex-wrap:wrap;margin-bottom:15px;font-size:12px;color:#39577e}
    #accDetalleModal .acc-detail-overview span{display:inline-flex;align-items:center;gap:6px;background:#eef5ff;border:1px solid #d0e2fa;border-radius:8px;padding:9px 12px}
    #accDetalleModal .acc-detail-overview i{color:#1d61b9}
    #accDetalleModal .acc-detail-overview strong{color:#103c79;font-weight:800}
    #accDetalleModal .acc-detail-table-wrap{border:1px solid #cad9ef;border-radius:10px;overflow-x:auto}
    #accDetalleModal .acc-detail-table{width:100%;border-collapse:separate;border-spacing:0;font-size:13px;min-width:570px}
    #accDetalleModal .acc-detail-table th{background:#dceafb;color:#12376e;padding:13px 14px;border-bottom:2px solid #9fc2ee;font-size:11px;text-transform:uppercase;letter-spacing:.45px;font-weight:900}
    #accDetalleModal .acc-detail-table td{padding:10px 14px;border-bottom:1px solid #e1eaf5;vertical-align:middle}
    #accDetalleModal .acc-detail-group td{background:#e9f3ff;border-top:11px solid #fff;border-bottom:1px solid #b9d2f2;padding:12px 14px}
    #accDetalleModal .acc-detail-group:first-child td{border-top:0}
    #accDetalleModal .acc-detail-group-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
    #accDetalleModal .acc-detail-article{display:flex;align-items:center;gap:12px;min-width:0}
    #accDetalleModal .acc-detail-box-icon{flex:0 0 35px;display:grid;place-items:center;height:35px;border-radius:8px;background:#1b5aaa;color:#fff}
    #accDetalleModal .acc-detail-code{display:block;color:#0d4289;font-weight:900;font-size:14px;letter-spacing:.15px}
    #accDetalleModal .acc-detail-desc{display:block;color:#253e62;font-size:12px;margin-top:3px;font-weight:600}
    #accDetalleModal .acc-detail-count{background:#fff;border:1px solid #c4d8f1;color:#20549c;border-radius:20px;font-size:11px;font-weight:800;padding:5px 10px;white-space:nowrap}
    #accDetalleModal .acc-detail-local td{background:#fff;color:#203953;font-size:13px}
    #accDetalleModal .acc-detail-local:nth-of-type(2n) td{background:#f8fbff}
    #accDetalleModal .acc-detail-local:hover td{background:#ebf5ff}
    #accDetalleModal .acc-detail-branch-indent{color:#8aafd9!important;text-align:right}
    #accDetalleModal .acc-detail-local .acc-local-name{font-weight:650}
    #accDetalleModal .acc-detail-local .acc-local-name i{color:#2974cc;margin-right:7px}
    #accDetalleModal .acc-detail-qty{display:inline-block;min-width:57px;text-align:center;border-radius:7px;font-weight:900;font-size:13px;padding:6px 11px;font-variant-numeric:tabular-nums}
    #accDetalleModal .acc-detail-qty.is-positive{background:#ddf8e8;border:1px solid #9be1b6;color:#136338}
    #accDetalleModal .acc-detail-qty.is-negative{background:#ffe3e5;border:1px solid #f2b0b4;color:#a62334}
    #accDetalleModal .acc-detail-qty.is-zero{background:#edf1f7;border:1px solid #d0dce9;color:#63778d}
    #accDetalleModal .acc-detail-subtotal td{background:#dcecff;color:#134580;font-weight:850;border-top:1px solid #b8d2f1;border-bottom:1px solid #b8d2f1}
    #accDetalleModal .acc-detail-subtotal i{color:#1c62ae;margin-right:6px}
    #accDetalleModal .acc-detail-subtotal strong{font-size:14px;font-weight:900}
    #accDetalleModal .acc-detail-total td{background:#133f81!important;color:#fff!important;font-size:15px;font-weight:900;letter-spacing:.3px;padding:16px 14px;border:0;font-variant-numeric:tabular-nums}
    #accDetalleModal .acc-detail-negative{color:#b12336!important}
    #accDetalleModal .acc-detail-empty{text-align:center;color:#5a7394;padding:36px 15px!important}
    #accDetalleModal .acc-detail-empty i{display:block;font-size:25px;color:#3d7bc8;margin-bottom:9px}
    #accDetalleModal .acc-detail-empty strong{display:block;color:#244c82;margin-bottom:5px}
    #accDetalleModal .acc-detail-empty span{display:block}
    #accDetalleModal .acc-detail-legend{margin-top:14px;padding:11px 14px;border:1px solid #cfdff4;border-radius:8px;background:#f2f7ff;color:#426083;font-size:12px;line-height:1.6}
    #accDetalleModal .acc-detail-legend i{color:#2165b5;margin-right:6px}
    #accDetalleModal .acc-adjusted-note{display:inline-block;margin-left:8px;font-weight:700;font-size:10px;color:#83580a;background:#fff4dc;padding:3px 6px;border-radius:5px}
    #accDetalleModal .acc-ajuste-row td{background:#f1f7ff;padding:15px 18px;border-top:1px solid #c7dcf8}
    #accDetalleModal .acc-ajuste-fields{display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap;margin-bottom:9px}
    #accDetalleModal .acc-ajuste-fields>div{flex:0 1 160px}
    #accDetalleModal .acc-ajuste-fields>.acc-ajuste-motivo{flex:1 1 220px}
    #accDetalleModal .acc-ajuste-fields>.acc-ajuste-buttons{flex:0 0 auto;display:flex;gap:7px}
    #accDetalleModal .acc-ajuste-form label{display:block;font-size:11px;font-weight:800;color:#244671;margin-bottom:5px}
    #accDetalleModal .acc-ajuste-form input{border:1px solid #b7cce6}
    #accDetalleModal .acc-ajuste-form input:focus{border-color:#3d79c4;box-shadow:0 0 0 3px rgba(50,105,189,.12)}
</style>
@endpush

@push('page_scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('accDetalleModal');
    var contenido = document.getElementById('accDetalleContenido');
    var titulo = document.getElementById('accDetalleTitulo');
    var selector = document.getElementById('accDetalleOrden');
    var urlBase = modal.getAttribute('data-url');
    var tipoActual = 'ventas';
    var peticion = null;
    var recargarAlCerrar = false;
    var mostrarConfirmacion = false;

    function cargarDetalle() {
        if (peticion) peticion.abort();
        peticion = new AbortController();
        var url = new URL(urlBase, window.location.href);
        var filtros = new URLSearchParams(window.location.search);
        filtros.forEach(function (valor, clave) {
            if (clave !== 'page' && clave !== 'tipo' && clave !== 'orden_detalle') {
                url.searchParams.append(clave, valor);
            }
        });
        url.searchParams.set('tipo', tipoActual);
        url.searchParams.set('orden_detalle', selector.value);
        contenido.innerHTML = '<div class="text-center text-muted p-4"><i class="fas fa-spinner fa-spin mr-2"></i>Cargando detalle...</div>';

        fetch(url.toString(), {
            headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
            signal: peticion.signal
        }).then(function (respuesta) {
            if (!respuesta.ok) throw new Error('Respuesta ' + respuesta.status);
            return respuesta.text();
        }).then(function (html) {
            contenido.innerHTML = (mostrarConfirmacion ? '<div class="alert alert-success"><i class="fas fa-check-circle mr-1"></i> Stock actualizado correctamente y cambio auditado.</div>' : '') + html;
            mostrarConfirmacion = false;
        }).catch(function (error) {
            if (error.name !== 'AbortError') {
                contenido.innerHTML = '<div class="alert alert-danger">No se pudo cargar el detalle. Revisá los filtros e intentá de nuevo.</div>';
            }
        });
    }

    Array.prototype.forEach.call(document.querySelectorAll('[data-acc-tipo]'), function (boton) {
        boton.addEventListener('click', function () {
            tipoActual = boton.getAttribute('data-acc-tipo');
            titulo.textContent = tipoActual === 'stock'
                ? 'Stock calculado por código y sucursal'
                : 'Unidades vendidas netas por código y sucursal';
            selector.value = 'codigo';
            $(modal).modal('show');
        });
    });
    $(modal).on('shown.bs.modal', cargarDetalle);
    $(modal).on('hidden.bs.modal', function () {
        if (peticion) peticion.abort();
        if (recargarAlCerrar) window.location.reload();
    });
    selector.addEventListener('change', cargarDetalle);

    // Los formularios se renderizan al abrir el detalle por AJAX.
    contenido.addEventListener('click', function (evento) {
        var boton = evento.target.closest('.acc-ajuste-toggle, .acc-ajuste-cancelar');
        if (!boton) return;
        var fila = document.getElementById(boton.getAttribute('data-target'));
        if (!fila) return;
        var abrir = boton.classList.contains('acc-ajuste-toggle')
            && fila.classList.contains('d-none');
        contenido.querySelectorAll('.acc-ajuste-row').forEach(function (elemento) {
            elemento.classList.add('d-none');
        });
        contenido.querySelectorAll('.acc-ajuste-toggle').forEach(function (elemento) {
            elemento.setAttribute('aria-expanded', 'false');
        });
        if (abrir) {
            fila.classList.remove('d-none');
            boton.setAttribute('aria-expanded', 'true');
            fila.querySelector('[name="saldo_nuevo"]').focus();
        }
    });

    contenido.addEventListener('submit', function (evento) {
        var formulario = evento.target;
        if (!formulario.classList.contains('acc-ajuste-form')) return;
        evento.preventDefault();
        if (formulario.dataset.guardando === '1') return;

        var boton = formulario.querySelector('button[type="submit"]');
        var error = formulario.querySelector('.acc-ajuste-error');
        formulario.dataset.guardando = '1';
        boton.disabled = true;
        error.textContent = '';
        error.classList.add('d-none');

        fetch(formulario.action, {
            method: 'POST',
            body: new FormData(formulario),
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': formulario.querySelector('[name="_token"]').value
            }
        }).then(function (respuesta) {
            return respuesta.json().catch(function () { return {}; }).then(function (datos) {
                if (!respuesta.ok) {
                    var errores = datos.errors ? Object.values(datos.errors) : [];
                    throw new Error(errores.length ? errores[0][0] : (datos.message || 'No se pudo guardar el ajuste.'));
                }
                return datos;
            });
        }).then(function () {
            recargarAlCerrar = true;
            mostrarConfirmacion = true;
            cargarDetalle();
        }).catch(function (fallo) {
            error.textContent = fallo.message || 'No se pudo guardar el ajuste.';
            error.classList.remove('d-none');
        }).finally(function () {
            formulario.dataset.guardando = '0';
            boton.disabled = false;
        });
    });
});
</script>
@endpush
