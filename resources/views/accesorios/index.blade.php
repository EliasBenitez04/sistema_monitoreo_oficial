@extends('layouts.app')

@section('content')
@php
    $n = function ($v) { return number_format((int) $v, 0, ',', '.'); };
    $hayFiltros = $buscar !== '' || $linea !== '' || $orden !== 'codigo';
    $desdeResultado = $articulos->firstItem() ?: 0;
    $hastaResultado = $articulos->lastItem() ?: 0;
@endphp

<div id="acclist">
    <section class="accl-hero">
        <div class="accl-hero-main">
            <div class="accl-hero-title">
                <div class="accl-icon"><i class="fas fa-box-open"></i></div>
                <div>
                    <div class="accl-eyebrow"><i class="fas fa-circle"></i> GESTIÓN DE INVENTARIO · ACCESORIOS</div>
                    <h1>Catálogo y seguimiento</h1>
                    <p>Consultá compras, movimientos, ventas y saldos de cada artículo desde un mismo lugar.</p>
                </div>
            </div>
            <div class="accl-hero-actions">
                <a href="{{ route('accesorios.index') }}" class="accl-button accl-button-ghost">
                    <i class="fas fa-chart-pie"></i> Panel de monitoreo
                </a>
                <a href="{{ route('accesorios.facturas.index') }}" class="accl-button accl-button-light"><i class="fas fa-file-invoice-dollar"></i> Compras · Facturas</a>
                <a href="{{ route('accesorios.importar.form') }}" class="accl-button accl-button-light">
                    <i class="fas fa-file-import"></i> Importar accesorios
                </a>
            </div>
        </div>
        <div class="accl-hero-bottom">
            <span><i class="far fa-calendar-check"></i> Saldo documental hasta {{ now()->format('d/m/Y') }}</span>
            <span><i class="fas fa-lock"></i> Administración de Accesorios</span>
            <span><i class="fas fa-sync-alt"></i> Cálculo actualizado al consultar</span>
        </div>
    </section>

    @if(session('success'))
        <div class="alert alert-success accl-notification" role="status">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger accl-notification" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    <section class="accl-metrics">
        <div class="accl-metric">
            <div class="accl-metric-heading"><span>Artículos registrados</span><i class="fas fa-barcode accl-icon-blue"></i></div>
            <strong>{{ $n($totalArticulos) }}</strong>
            <small>Códigos únicos en el catálogo</small>
        </div>
        <div class="accl-metric">
            <div class="accl-metric-heading"><span>Compras registradas</span><i class="fas fa-file-invoice accl-icon-violet"></i></div>
            <strong>{{ $n($resumen->compras) }}</strong>
            <small>Registros de compra / FCO</small>
        </div>
        <div class="accl-metric">
            <div class="accl-metric-heading"><span>Unidades compradas</span><i class="fas fa-cubes accl-icon-teal"></i></div>
            <strong>{{ $n($resumen->unidades) }}</strong>
            <small>Acumuladas de todos los FCO</small>
        </div>
        <div class="accl-metric">
            <div class="accl-metric-heading"><span>Resultados encontrados</span><i class="fas fa-search accl-icon-gold"></i></div>
            <strong>{{ $n($articulos->total()) }}</strong>
            <small>{{ $hayFiltros ? 'Según los filtros seleccionados' : 'Sin filtros activos' }}</small>
        </div>
    </section>

    <section class="accl-panel accl-filter-panel">
        <div class="accl-heading">
            <div>
                <h2><i class="fas fa-sliders-h"></i> Buscar y organizar artículos</h2>
                <p>Filtrá por código, descripción o línea. Los resultados conservan su paginación.</p>
            </div>
            @if($hayFiltros)
                <a class="accl-reset-link" href="{{ route('accesorios.listado') }}"><i class="fas fa-times-circle"></i> Quitar filtros</a>
            @endif
        </div>
        <form action="{{ route('accesorios.listado') }}" method="GET" class="accl-form">
            <div class="accl-field accl-field-search">
                <label for="accl-buscar">Buscar artículo</label>
                <div class="accl-input-icon">
                    <i class="fas fa-search"></i>
                    <input id="accl-buscar" class="form-control" name="buscar" value="{{ $buscar }}" maxlength="100"
                        placeholder="Ej.: MEDNI, medias, bombacha..." autocomplete="off">
                </div>
            </div>
            <div class="accl-field">
                <label for="accl-linea">Línea de producto</label>
                <select id="accl-linea" name="linea" class="form-control">
                    <option value="">Todas las líneas</option>
                    @foreach($lineas as $opcion)
                        <option value="{{ $opcion }}" {{ $linea === (string) $opcion ? 'selected' : '' }}>{{ $opcion }}</option>
                    @endforeach
                </select>
            </div>
            <div class="accl-field">
                <label for="accl-orden">Ordenar por</label>
                <select id="accl-orden" name="orden" class="form-control">
                    <option value="codigo" {{ $orden === 'codigo' ? 'selected' : '' }}>Código · A–Z</option>
                    <option value="descripcion" {{ $orden === 'descripcion' ? 'selected' : '' }}>Descripción · A–Z</option>
                    <option value="linea" {{ $orden === 'linea' ? 'selected' : '' }}>Línea · A–Z</option>
                </select>
            </div>
            <div class="accl-filter-actions">
                <button type="submit" class="accl-button accl-button-primary"><i class="fas fa-filter"></i> Aplicar filtros</button>
                <a href="{{ route('accesorios.listado') }}" class="accl-button accl-button-muted" aria-label="Limpiar filtros"><i class="fas fa-redo-alt"></i> Limpiar</a>
            </div>
        </form>
        @if($hayFiltros)
            <div class="accl-active-filters">
                <span><i class="fas fa-check-circle"></i> Filtros aplicados:</span>
                @if($buscar !== '')<span class="accl-filter-pill">Búsqueda: {{ $buscar }}</span>@endif
                @if($linea !== '')<span class="accl-filter-pill">Línea: {{ $linea }}</span>@endif
                @if($orden !== 'codigo')<span class="accl-filter-pill">Orden: {{ $orden === 'descripcion' ? 'Descripción' : 'Línea' }}</span>@endif
            </div>
        @endif
    </section>

    <section class="accl-page-status" aria-label="Indicadores de la página actual">
        <div class="accl-page-tag">
            <i class="fas fa-layer-group"></i>
            <div><span>Indicadores de esta página</span><small>{{ $n($articulos->count()) }} artículos visibles · No son totales del catálogo</small></div>
        </div>
        <div class="accl-page-stat"><span>Compradas</span><b>{{ $n($indicadoresPagina['unidades_compradas']) }}</b></div>
        <div class="accl-page-stat"><span>Ventas netas</span><b>{{ $n($indicadoresPagina['ventas_netas']) }}</b></div>
        <div class="accl-page-stat"><span>Stock conocido</span><b>{{ $n($indicadoresPagina['stock_conocido']) }}</b></div>
        <div class="accl-page-stat accl-page-warning"><span>Saldos negativos</span><b>{{ $n($indicadoresPagina['saldo_negativo']) }}</b></div>
        <div class="accl-page-stat"><span>Sin historial</span><b>{{ $n($indicadoresPagina['sin_datos']) }}</b></div>
    </section>

    <section class="accl-panel accl-table-panel">
        <div class="accl-list-header">
            <div>
                <h2><i class="fas fa-list-ul"></i> Listado de accesorios</h2>
                <p>
                    Mostrando <strong>{{ $n($desdeResultado) }}–{{ $n($hastaResultado) }}</strong>
                    de <strong>{{ $n($articulos->total()) }}</strong> resultados
                </p>
            </div>
            <div class="accl-list-actions">
                <span class="accl-data-date"><i class="far fa-clock"></i> {{ now()->format('d/m/Y') }}</span>
                <a href="{{ route('accesorios.stock.exportar', ['buscar' => $buscar, 'linea' => $linea]) }}"
                   class="accl-button accl-button-export" title="Exportar stock calculado por código y sucursal con los filtros actuales">
                    <i class="fas fa-file-csv"></i> Exportar stock
                </a>
            </div>
        </div>

        <div class="accl-table-scroll">
            <table class="accl-table">
                <thead>
                    <tr>
                        <th style="width:38px" class="accl-center">#</th>
                        <th>Artículo</th>
                        <th>Línea</th>
                        <th class="accl-num" title="Unidades compradas por todos los FCO">Compradas</th>
                        <th class="accl-num" title="Recepciones confirmadas de Depósito hacia Casa Central">Depósito → Central</th>
                        <th class="accl-num" title="Unidades enviadas desde Casa Central hacia los locales (sin incluir el ingreso de Depósito)">Central → locales</th>
                        <th class="accl-num" title="De lo distribuido desde Central, cuántas unidades tienen recepción registrada en locales">Recibidas en locales</th>
                        <th class="accl-num" title="Ventas netas acumuladas de los archivos importados">Ventas netas</th>
                        <th class="accl-num" title="Entradas recibidas menos salidas remitidas y ventas netas hasta hoy">Stock calculado</th>
                        <th class="accl-right">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($articulos as $articulo)
                        @php
                            $stock = $articulo->stock_calculado;
                            $tieneStock = $stock !== null;
                            $estado = !$tieneStock ? 'sin-datos' : ($stock < 0 ? 'negativo' : ($stock === 0 ? 'agotado' : 'disponible'));
                        @endphp
                        <tr>
                            <td class="accl-center accl-row-number">{{ $desdeResultado + $loop->index }}</td>
                            <td>
                                <div class="accl-product">
                                    <div class="accl-product-icon"><i class="fas fa-socks"></i></div>
                                    <div class="accl-product-info">
                                        <a class="accl-product-code" href="{{ route('accesorios.show', $articulo->id) }}">{{ $articulo->codigo }}</a>
                                        <span class="accl-product-name" title="{{ $articulo->descripcion }}">{{ $articulo->descripcion }}</span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="accl-line-chip">{{ $articulo->linea ?: 'Sin línea' }}</span></td>
                            <td class="accl-num accl-strong">{{ $n($articulo->comprado) }}</td>
                            <td class="accl-num">{{ $n($articulo->ingreso_deposito) }}</td>
                            <td class="accl-num accl-strong">{{ $n($articulo->remitido) }}</td>
                            <td class="accl-num">{{ $n($articulo->recibido) }}</td>
                            <td class="accl-num accl-strong">{{ $n($articulo->vendido) }}</td>
                            <td class="accl-num">
                                <div class="accl-stock">
                                    @if(!$tieneStock)
                                        <span class="accl-stock-badge accl-stock-unknown"><i class="far fa-question-circle"></i> Sin movimientos</span>
                                    @elseif($stock < 0)
                                        <span class="accl-stock-badge accl-stock-danger"><i class="fas fa-exclamation-triangle"></i> {{ $n($stock) }}</span>
                                        <small class="accl-stock-caption">Revisar diferencia</small>
                                    @elseif($stock === 0)
                                        <span class="accl-stock-badge accl-stock-zero"><i class="fas fa-minus-circle"></i> 0</span>
                                        <small class="accl-stock-caption">Sin saldo</small>
                                    @else
                                        <span class="accl-stock-badge accl-stock-positive"><i class="fas fa-check-circle"></i> {{ $n($stock) }}</span>
                                        <small class="accl-stock-caption">Saldo registrado</small>
                                    @endif
                                </div>
                            </td>
                            <td class="accl-right">
                                <a class="accl-view" href="{{ route('accesorios.show', $articulo->id) }}"
                                   aria-label="Ver seguimiento de {{ $articulo->codigo }}">
                                    Ver detalle <i class="fas fa-arrow-right"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="accl-empty">
                                    <div class="accl-empty-icon"><i class="fas fa-search"></i></div>
                                    <h3>{{ $totalArticulos ? 'No encontramos artículos con esos filtros' : 'Todavía no hay accesorios cargados' }}</h3>
                                    <p>{{ $totalArticulos ? 'Probá con otro código o quitá los filtros de búsqueda.' : 'Importá las compras de accesorios para comenzar su seguimiento.' }}</p>
                                    <a href="{{ $totalArticulos ? route('accesorios.listado') : route('accesorios.importar.form') }}"
                                       class="accl-button accl-button-primary">
                                        <i class="fas fa-{{ $totalArticulos ? 'redo' : 'file-import' }}"></i>
                                        {{ $totalArticulos ? 'Mostrar todos' : 'Importar accesorios' }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="accl-table-footer">
            <span><i class="fas fa-info-circle"></i> Los saldos negativos se muestran para auditoría; no se ajustan automáticamente.</span>
            <div class="accl-pages">{{ $articulos->links() }}</div>
        </div>
    </section>

    <div class="accl-footnote">
        <i class="fas fa-shield-alt"></i>
        <div>
            <strong>Cómo se interpreta este control</strong>
            <p>El <b>stock calculado</b> se obtiene por código y sucursal:
            <b>entradas con recepción confirmada − salidas remitidas − ventas netas acumuladas + ajustes manuales de ADMIN</b>.
            Las devoluciones registradas en ventas se descuentan con su signo y pueden aumentar el saldo.
            <b>Depósito → Central</b> es la entrada inicial recibida;
            <b>Central → locales</b> es la distribución posterior; y
            <b>Recibidas en locales</b> son únicamente las unidades de esa distribución cuya recepción ya fue confirmada.
            <b>Son etapas, no cantidades para sumar</b>: si entran 7 y después salen esas mismas 7, se informan 7 por etapa, nunca 14 prendas.
            Los traslados entre locales se reflejan en el stock por sucursal y pueden consultarse en el detalle.
            Las compras por FCO son registros de adquisición y no se agregan directamente al stock.
            El saldo refleja el historial importado más las correcciones manuales auditadas; no sustituye un conteo físico.</p>
        </div>
    </div>
</div>
@endsection

@push('page_css')
<style>
    /* Misma contención que el dashboard /accesorios: no desplazar navbar/AdminLTE. */
    #acclist{--navy:#122c63;--blue:#2060b3;--ink:#20345b;--muted:#7284a1;--border:#e0e8f3;--back:#f4f7fc;box-sizing:border-box;max-width:100%;width:100%;min-width:0;overflow-x:clip;font-family:Roboto,Arial,sans-serif;background:var(--back);color:var(--ink);padding:16px 15px 28px;margin:0}
    #acclist *,#acclist *::before,#acclist *::after{box-sizing:border-box}
    #acclist .accl-hero{background:linear-gradient(118deg,#0d285f 0%,#1550a2 78%,#2674d8 100%);color:#fff;padding:23px 25px 15px;border-radius:16px;margin-bottom:15px;box-shadow:0 10px 28px rgba(31,76,154,.16)}
    #acclist .accl-hero-main{display:flex;gap:20px;align-items:flex-start;justify-content:space-between;flex-wrap:wrap}
    #acclist .accl-hero-title{display:flex;gap:15px;align-items:center}
    #acclist .accl-icon{width:58px;height:58px;display:flex;justify-content:center;align-items:center;flex-shrink:0;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.13);border-radius:15px;font-size:22px}
    #acclist .accl-eyebrow{font-weight:800;font-size:10px;letter-spacing:1.7px;color:#bbd9ff;margin-bottom:6px}
    #acclist .accl-eyebrow i{font-size:7px;vertical-align:middle;margin-right:7px;color:#78c3ff}
    #acclist h1{font-size:clamp(23px,2.2vw,33px);font-weight:800;line-height:1.15;margin:0;letter-spacing:-.55px}
    #acclist .accl-hero p{font-size:12px;color:#d6e7ff;margin:9px 0 0;line-height:1.5}
    #acclist .accl-hero-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    #acclist .accl-button{display:inline-flex;gap:8px;align-items:center;justify-content:center;font-size:12px;font-weight:800;border:1px solid transparent;border-radius:8px;padding:10px 14px;min-height:38px;text-align:center;text-decoration:none!important;white-space:nowrap;cursor:pointer;transition:background .14s,border-color .14s}
    #acclist .accl-button-light{background:#fff;color:#164f9e}
    #acclist .accl-button-light:hover{color:#0d3675;background:#edf5ff}
    #acclist .accl-button-ghost{border-color:rgba(255,255,255,.43);background:rgba(255,255,255,.08);color:#fff}
    #acclist .accl-button-ghost:hover{background:rgba(255,255,255,.2);color:#fff}
    #acclist .accl-button-primary{background:#1756ab;color:#fff}
    #acclist .accl-button-primary:hover{background:#0b438f;color:#fff}
    #acclist .accl-button-muted{background:#eef2f9;color:#526782;border-color:#e1e9f5}
    #acclist .accl-button-muted:hover{background:#dde9f8;color:#244970}
    #acclist .accl-button-export{background:#eaf3ff;border-color:#d8e7fa;color:#205b9f}
    #acclist .accl-button-export:hover{background:#dbeaff;color:#174677}
    #acclist .accl-hero-bottom{display:flex;gap:12px 24px;align-items:center;flex-wrap:wrap;margin-top:20px;padding-top:13px;border-top:1px solid rgba(255,255,255,.22);font-size:11px;color:#d8e7fa}
    #acclist .accl-hero-bottom i{margin-right:6px;color:#b9d8ff}
    #acclist .accl-notification{margin:14px 0;border-radius:10px;font-size:13px}
    #acclist .accl-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px;margin-bottom:15px}
    #acclist .accl-metric{background:#fff;border:1px solid var(--border);border-radius:12px;padding:17px 18px;box-shadow:0 2px 8px rgba(25,59,100,.035);min-width:0}
    #acclist .accl-metric-heading{display:flex;align-items:center;justify-content:space-between;gap:8px;color:#687b99;font-size:12px;font-weight:700}
    #acclist .accl-metric-heading i{padding:10px;border-radius:9px;font-size:16px}
    #acclist .accl-icon-blue{background:#e9f2ff;color:#1b65c2}
    #acclist .accl-icon-violet{background:#f0ecff;color:#7154ba}
    #acclist .accl-icon-teal{background:#e6f9f4;color:#258673}
    #acclist .accl-icon-gold{background:#fff3e0;color:#ac7726}
    #acclist .accl-metric strong{display:block;margin-top:12px;font-size:30px;font-weight:900;color:#153d7a;line-height:1.15;font-variant-numeric:tabular-nums}
    #acclist .accl-metric small{display:block;color:#8493ab;font-size:11px;margin-top:8px}
    #acclist .accl-panel{background:#fff;border:1px solid var(--border);border-radius:13px;box-shadow:0 2px 9px rgba(25,59,100,.035);overflow:hidden;margin-bottom:15px}
    #acclist .accl-filter-panel{padding:19px 20px}
    #acclist .accl-heading,#acclist .accl-list-header{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
    #acclist .accl-heading h2,#acclist .accl-list-header h2{font-size:15px;font-weight:800;margin:0;color:#183f7b}
    #acclist .accl-heading h2 i,#acclist .accl-list-header h2 i{color:#286dc1;margin-right:8px}
    #acclist .accl-heading p,#acclist .accl-list-header p{font-size:11px;color:#8593a9;margin:6px 0 0}
    #acclist .accl-reset-link{font-size:11px;color:#2262b1;font-weight:800;text-decoration:none}
    #acclist .accl-reset-link i{margin-right:5px}
    #acclist .accl-form{display:grid;grid-template-columns:minmax(190px,2fr) minmax(150px,1fr) minmax(150px,1fr) auto;gap:13px;align-items:end;margin-top:18px}
    #acclist .accl-field{min-width:0}
    #acclist .accl-field label{display:block;font-weight:800;font-size:11px;color:#486080;margin-bottom:7px}
    #acclist .accl-field .form-control{height:40px;border:1px solid #d8e2ef;border-radius:8px;font-size:12px;color:#2b4772;width:100%;background:#fff}
    #acclist .accl-field .form-control:focus{border-color:#4085db;box-shadow:0 0 0 3px rgba(30,106,208,.11)}
    #acclist .accl-input-icon{position:relative}
    #acclist .accl-input-icon>i{position:absolute;top:14px;left:13px;font-size:12px;color:#91a1b9;pointer-events:none}
    #acclist .accl-input-icon input{padding-left:35px}
    #acclist .accl-filter-actions{display:flex;gap:8px;align-items:center}
    #acclist .accl-active-filters{margin-top:14px;border-top:1px solid #edf2f8;padding-top:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;font-size:11px;color:#627b9b}
    #acclist .accl-active-filters>span:first-child{font-weight:700}
    #acclist .accl-active-filters>span:first-child i{color:#2786b5}
    #acclist .accl-filter-pill{padding:5px 9px;background:#ebf3ff;color:#1c5aaa;border:1px solid #d7e7fd;border-radius:6px;font-size:11px}
    #acclist .accl-page-status{display:flex;align-items:center;gap:0;flex-wrap:wrap;border:1px solid #dce7f6;background:#ebf3fe;border-radius:11px;margin-bottom:15px;padding:10px 12px}
    #acclist .accl-page-tag{display:flex;align-items:center;gap:10px;flex:1 1 250px;padding:7px 10px}
    #acclist .accl-page-tag>i{background:#d7e9ff;color:#2669bb;border-radius:9px;padding:11px}
    #acclist .accl-page-tag span{display:block;font-size:11px;font-weight:800;color:#234f8d}
    #acclist .accl-page-tag small{display:block;font-size:10px;color:#7190b3;margin-top:3px}
    #acclist .accl-page-stat{min-width:113px;padding:7px 14px;border-left:1px solid #d0e0f5}
    #acclist .accl-page-stat span{display:block;font-size:10px;color:#6d86a8}
    #acclist .accl-page-stat b{display:block;font-size:18px;color:#1d4f90;font-weight:900;font-variant-numeric:tabular-nums;margin-top:3px}
    #acclist .accl-page-warning b{color:#be4e3d}
    #acclist .accl-list-header{padding:17px 19px;border-bottom:1px solid #e7edf6}
    #acclist .accl-list-actions{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
    #acclist .accl-data-date{color:#8294ae;font-size:11px}
    #acclist .accl-data-date i{margin-right:5px}
    #acclist .accl-table-scroll{width:100%;overflow-x:auto}
    #acclist .accl-table{width:100%;border-collapse:separate;border-spacing:0;font-size:12px;white-space:nowrap}
    #acclist .accl-table thead{background:#f2f6fc}
    #acclist .accl-table thead th{padding:13px 12px;border-bottom:1px solid #e0e9f3;font-size:10px;letter-spacing:.35px;font-weight:800;text-transform:uppercase;color:#6e7f9d}
    #acclist .accl-table td{padding:13px 12px;border-bottom:1px solid #edf2f7;color:#365070;vertical-align:middle}
    #acclist .accl-table tbody tr:hover{background:#f8fbff}
    #acclist .accl-table tbody tr:last-child td{border-bottom:0}
    #acclist .accl-center{text-align:center}
    #acclist .accl-right{text-align:right}
    #acclist .accl-num{text-align:right;font-variant-numeric:tabular-nums}
    #acclist .accl-strong{font-weight:800;color:#1d406e!important}
    #acclist .accl-row-number{color:#9ba9ba!important;font-size:11px}
    #acclist .accl-product{display:flex;align-items:center;gap:11px;min-width:195px}
    #acclist .accl-product-icon{flex-shrink:0;width:35px;height:35px;display:grid;place-items:center;color:#3370ba;background:#eaf3ff;border-radius:9px;font-size:13px}
    #acclist .accl-product-info{display:flex;flex-direction:column;gap:4px;min-width:0}
    #acclist .accl-product-code{font-size:12px;font-weight:900;color:#175eb1}
    #acclist .accl-product-code:hover{color:#0b3a81;text-decoration:underline}
    #acclist .accl-product-name{display:block;max-width:210px;overflow:hidden;text-overflow:ellipsis;font-size:11px;color:#71829c}
    #acclist .accl-line-chip{display:inline-flex;align-items:center;max-width:140px;overflow:hidden;text-overflow:ellipsis;vertical-align:middle;border:1px solid #e2eaf6;color:#567296;background:#f4f8fe;padding:6px 8px;border-radius:6px;font-size:10px;font-weight:800}
    #acclist .accl-stock{display:flex;align-items:flex-end;flex-direction:column;gap:5px}
    #acclist .accl-stock-badge{display:inline-flex;gap:6px;align-items:center;justify-content:flex-end;border-radius:6px;padding:6px 9px;font-size:12px;font-weight:900;white-space:nowrap}
    #acclist .accl-stock-badge i{font-size:10px}
    #acclist .accl-stock-positive{background:#e4f6ed;color:#19815b}
    #acclist .accl-stock-zero{background:#fff2db;color:#a16a1c}
    #acclist .accl-stock-danger{background:#ffe8e9;color:#b12b39}
    #acclist .accl-stock-unknown{background:#edf1f7;color:#77869c;font-size:10px}
    #acclist .accl-stock-caption{color:#98a4b6;font-size:9px}
    #acclist .accl-view{display:inline-flex;align-items:center;gap:8px;border:1px solid #ccdef4;background:#f1f7ff;color:#205ca5;border-radius:7px;padding:9px 10px;font-weight:800;font-size:11px;white-space:nowrap;text-decoration:none!important}
    #acclist .accl-view:hover{background:#e1eeff;border-color:#9fbfe9;color:#12467f}
    #acclist .accl-view i{font-size:10px}
    #acclist .accl-empty{text-align:center;white-space:normal;padding:47px 15px;max-width:450px;margin:auto}
    #acclist .accl-empty-icon{width:64px;height:64px;border-radius:50%;background:#edf4fe;color:#3f7bbd;display:grid;place-items:center;font-size:24px;margin:0 auto 12px}
    #acclist .accl-empty h3{font-size:16px;font-weight:800;color:#294e85;margin:0 0 8px}
    #acclist .accl-empty p{font-size:12px;color:#8291a9;line-height:1.55;margin-bottom:18px}
    #acclist .accl-table-footer{border-top:1px solid #e4eaf4;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;padding:14px 18px;font-size:10px;color:#8a97ab}
    #acclist .accl-table-footer i{color:#4f84c3;margin-right:4px}
    #acclist .accl-pages .pagination{margin:0}
    #acclist .accl-pages .page-link{border-color:#e0e8f3;color:#2d62a6;font-size:11px;padding:6px 10px}
    #acclist .accl-pages .page-item.active .page-link{background:#1f5eb7;border-color:#1f5eb7;color:#fff}
    #acclist .accl-footnote{display:flex;align-items:flex-start;gap:13px;border:1px solid #dce8f7;background:#eaf3fc;border-radius:10px;padding:15px 17px;font-size:11px;color:#5c7595;line-height:1.6}
    #acclist .accl-footnote>i{margin-top:3px;color:#2b6bb7;font-size:16px}
    #acclist .accl-footnote strong{font-size:12px;color:#2a588f}
    #acclist .accl-footnote p{margin:5px 0 0}
    @media(max-width:1350px){
        #acclist .accl-page-stat{min-width:100px;padding:7px 10px}
        #acclist .accl-form{grid-template-columns:minmax(190px,1.8fr) minmax(150px,1fr) minmax(150px,1fr)}
        #acclist .accl-filter-actions{grid-column:1/-1}
    }
    @media(max-width:950px){
        #acclist .accl-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}
        #acclist .accl-form{grid-template-columns:repeat(2,minmax(0,1fr))}
        #acclist .accl-field-search{grid-column:1/-1}
        #acclist .accl-page-tag{flex-basis:100%}
        #acclist .accl-page-stat{border-left:0}
    }
    @media(max-width:575px){
        #acclist{padding:12px 10px 22px;margin:0}
        #acclist .accl-hero{padding:20px 17px 13px}
        #acclist .accl-icon{display:none}
        #acclist .accl-hero-actions{width:100%}
        #acclist .accl-hero-actions .accl-button{flex:1}
        #acclist .accl-metrics{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
        #acclist .accl-metric{padding:13px}
        #acclist .accl-metric strong{font-size:24px}
        #acclist .accl-metric-heading{font-size:10px}
        #acclist .accl-filter-panel{padding:15px}
        #acclist .accl-form{grid-template-columns:1fr}
        #acclist .accl-field-search{grid-column:auto}
        #acclist .accl-filter-actions{grid-column:auto}
        #acclist .accl-filter-actions .accl-button{flex:1}
        #acclist .accl-page-stat{flex:1 1 32%;min-width:90px}
        #acclist .accl-list-header{padding:14px}
        #acclist .accl-list-actions{width:100%;justify-content:space-between}
        #acclist .accl-table-footer{padding:12px}
        #acclist .accl-footnote{padding:12px}
    }
</style>
@endpush
