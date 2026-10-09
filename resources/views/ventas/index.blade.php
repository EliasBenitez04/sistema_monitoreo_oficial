@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1><i class="fas fa-chart-line mr-2"></i>Ventas</h1>
                <p class="text-muted mb-0">
                    Resumen comercial, devoluciones, locales, productos y vendedores.
                </p>
            </div>
            <a href="{{ route('ventas.importar.form') }}"
               class="btn btn-success shadow-sm mt-2 mt-md-0">
                <i class="fas fa-file-import mr-1"></i> Importar ventas
            </a>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">

    @if($ultimaImportacion)
        <div class="alert alert-light border py-2 mb-3">
            <i class="fas fa-database text-info mr-1"></i>
            <strong>Última importación:</strong>
            {{ $ultimaImportacion->nombre_archivo }}
            · {{ $ultimaImportacion->fecha_desde ? date('d/m/Y', strtotime($ultimaImportacion->fecha_desde)) : '-' }}
            @if($ultimaImportacion->fecha_hasta && $ultimaImportacion->fecha_hasta !== $ultimaImportacion->fecha_desde)
                al {{ date('d/m/Y', strtotime($ultimaImportacion->fecha_hasta)) }}
            @endif
            · {{ number_format($ultimaImportacion->filas_insertadas,0,',','.') }} nuevas
            · {{ number_format($ultimaImportacion->filas_duplicadas ?? 0,0,',','.') }} duplicadas
            · {{ number_format($ultimaImportacion->filas_invalidas ?? 0,0,',','.') }} inválidas
            @php
                $sinClasificarUltima = max(
                    0,
                    (int) $ultimaImportacion->filas_omitidas
                        - (int) ($ultimaImportacion->filas_duplicadas ?? 0)
                        - (int) ($ultimaImportacion->filas_invalidas ?? 0)
                );
            @endphp
            @if($sinClasificarUltima > 0)
                · {{ number_format($sinClasificarUltima,0,',','.') }} omitidas históricas sin clasificar
            @endif
        </div>
    @endif

    <div class="card card-outline card-primary shadow-sm mb-3">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-filter mr-1"></i> Filtros
            </h3>
        </div>
        <div class="card-body pb-2">
            <form method="GET" action="{{ route('ventas.index') }}">
                <div class="row align-items-end">
                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="small font-weight-bold">Desde</label>
                        <input type="date" name="desde" value="{{ $desde }}" class="form-control">
                    </div>
                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="small font-weight-bold">Hasta</label>
                        <input type="date" name="hasta" value="{{ $hasta }}" class="form-control">
                    </div>
                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="small font-weight-bold">Local</label>
                        <select name="local" class="form-control select2">
                            <option value="">Todos</option>
                            @foreach($locales as $item)
                                <option value="{{ $item }}" {{ $local === $item ? 'selected' : '' }}>
                                    {{ $item }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="small font-weight-bold">Vendedor</label>
                        <select name="vendedor" class="form-control select2">
                            <option value="">Todos</option>
                            @foreach($vendedores as $item)
                                <option value="{{ $item }}" {{ $vendedor === $item ? 'selected' : '' }}>
                                    {{ $item }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-1 col-md-4 mb-2">
                        <label class="small font-weight-bold">Tipo</label>
                        <select name="tipo" class="form-control">
                            <option value="">Todos</option>
                            @foreach($tipos as $item)
                                <option value="{{ $item }}" {{ $tipo === $item ? 'selected' : '' }}>
                                    {{ $item }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-8 mb-2">
                        <label class="small font-weight-bold">Buscar</label>
                        <input type="text"
                               name="buscar"
                               value="{{ $buscar }}"
                               class="form-control"
                               placeholder="Código, artículo, cliente o comprobante">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap mt-1">
                    <div class="small text-muted mb-2 mb-md-0">
                        @if(!$todo && $desde && $hasta && $desde === $hasta)
                            Mostrando el día {{ date('d/m/Y', strtotime($desde)) }}.
                        @elseif($todo)
                            Mostrando todo el histórico.
                        @endif
                    </div>
                    <div>
                        <a href="{{ route('ventas.index', ['todo' => 1]) }}"
                           class="btn btn-light border mr-1">
                            <i class="fas fa-history mr-1"></i> Todo
                        </a>
                        <a href="{{ route('ventas.index') }}"
                           class="btn btn-light border mr-1">
                            <i class="fas fa-calendar-day mr-1"></i> Último día
                        </a>
                        <button class="btn btn-primary">
                            <i class="fas fa-search mr-1"></i> Consultar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="small-box bg-white border-left border-success shadow-sm">
                <div class="inner">
                    <h3>Gs {{ number_format($resumen->venta_neta,0,',','.') }}</h3>
                    <p>Venta neta (PVTA)</p>
                    <small>
                        Total final del reporte · NCR ya descontadas
                    </small>
                </div>
                <div class="icon"><i class="fas fa-cash-register text-success"></i></div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="small-box bg-white border-left border-primary shadow-sm">
                <div class="inner">
                    <h3>{{ number_format($resumen->unidades_netas,0,',','.') }}</h3>
                    <p>Unidades netas</p>
                    <small>
                        {{ number_format($resumen->unidades_vendidas,0,',','.') }} vendidas
                        · {{ number_format($resumen->unidades_devueltas,0,',','.') }} devueltas
                    </small>
                </div>
                <div class="icon"><i class="fas fa-tshirt text-primary"></i></div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="small-box bg-white border-left border-info shadow-sm">
                <div class="inner">
                    <h3>{{ number_format($resumen->tickets,0,',','.') }}</h3>
                    <p>Tickets de venta</p>
                    <small>
                        Ticket promedio Gs {{ number_format($resumen->ticket_promedio,0,',','.') }}
                    </small>
                </div>
                <div class="icon"><i class="fas fa-receipt text-info"></i></div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="small-box bg-white border-left border-warning shadow-sm">
                <div class="inner">
                    <h3>Gs {{ number_format($resumen->descuento_otorgado,0,',','.') }}</h3>
                    <p>Descuento neto (DTO)</p>
                    <small>
                        {{ number_format($resumen->porcentaje_descuento,1,',','.') }}% sobre PLISTA neta
                    </small>
                </div>
                <div class="icon"><i class="fas fa-tags text-warning"></i></div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-lg-3 col-6">
            <div class="ct-mini h-100">
                <small class="text-muted text-uppercase font-weight-bold">Venta a lista</small>
                <div class="h5 mb-0 font-weight-bold">
                    Gs {{ number_format($resumen->venta_lista,0,',','.') }}
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="ct-mini h-100">
                <small class="text-muted text-uppercase font-weight-bold">Precio promedio/unidad</small>
                <div class="h5 mb-0 font-weight-bold">
                    Gs {{ number_format($resumen->precio_promedio_unidad,0,',','.') }}
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-4 mt-2 mt-lg-0">
            <div class="ct-mini h-100 text-center">
                <small class="text-muted text-uppercase font-weight-bold">Locales</small>
                <div class="h5 mb-0 font-weight-bold">{{ number_format($resumen->locales,0,',','.') }}</div>
            </div>
        </div>
        <div class="col-lg-2 col-4 mt-2 mt-lg-0">
            <div class="ct-mini h-100 text-center">
                <small class="text-muted text-uppercase font-weight-bold">Códigos</small>
                <div class="h5 mb-0 font-weight-bold">{{ number_format($resumen->codigos,0,',','.') }}</div>
            </div>
        </div>
        <div class="col-lg-2 col-4 mt-2 mt-lg-0">
            <div class="ct-mini h-100 text-center">
                <small class="text-muted text-uppercase font-weight-bold">Vendedores</small>
                <div class="h5 mb-0 font-weight-bold">{{ number_format($resumen->vendedores,0,',','.') }}</div>
            </div>
        </div>
    </div>

    <div class="alert alert-light border py-2 mb-3">
        <strong>Lectura del export:</strong>
        PLISTA, DTO y PVTA ya vienen como <strong>totales de cada línea</strong>.
        Se suman directamente y no se vuelven a multiplicar por CANTIDAD.
        Las NCR ya vienen con importes negativos y se descuentan automáticamente.
    </div>

    <div class="row">
        <div class="col-xl-7">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-store mr-1"></i> Resultado por local
                    </h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 ventas-table">
                        <thead>
                            <tr>
                                <th>Local</th>
                                <th class="text-right">Cantidad</th>
                                <th class="text-right">PLISTA</th>
                                <th class="text-right">DTO</th>
                                <th class="text-right">PVTA</th>
                                <th class="text-right">Tickets</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($porLocal as $item)
                                <tr>
                                    <td><strong>{{ $item->local }}</strong></td>
                                    <td class="text-right font-weight-bold">
                                        {{ number_format($item->unidades_netas,0,',','.') }}
                                    </td>
                                    <td class="text-right">
                                        Gs {{ number_format($item->venta_lista,0,',','.') }}
                                    </td>
                                    <td class="text-right">
                                        Gs {{ number_format($item->descuento,0,',','.') }}
                                    </td>
                                    <td class="text-right font-weight-bold text-success">
                                        Gs {{ number_format($item->venta_neta,0,',','.') }}
                                    </td>
                                    <td class="text-right">
                                        {{ number_format($item->tickets,0,',','.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">Sin datos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5 mt-3 mt-xl-0">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-tie mr-1"></i> Top vendedores
                    </h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 ventas-table">
                        <thead>
                            <tr>
                                <th>Sucursal</th>
                                <th>Vendedor</th>
                                <th class="text-right">Unid.</th>
                                <th class="text-right">Tickets</th>
                                <th class="text-right">Venta neta</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($porVendedor as $item)
                                <tr>
                                    <td>
                                        <span class="badge badge-light border">
                                            {{ $item->local }}
                                        </span>
                                    </td>
                                    <td><strong>{{ $item->vendedor }}</strong></td>
                                    <td class="text-right">{{ number_format($item->unidades_netas,0,',','.') }}</td>
                                    <td class="text-right">{{ number_format($item->tickets,0,',','.') }}</td>
                                    <td class="text-right font-weight-bold">
                                        Gs {{ number_format($item->venta_neta,0,',','.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">Sin datos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer text-center bg-white">
                    <button type="button"
                            class="btn btn-sm btn-outline-primary"
                            id="btnTodosVendedores">
                        <i class="fas fa-list mr-1"></i>
                        Ver todos
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-xl-8">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-award mr-1"></i> Top productos
                    </h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 ventas-table"
                           id="topProductosTable">
                        <thead>
                            <tr>
                                <th>Código / artículo</th>
                                <th>Grupo</th>
                                <th>Temporada</th>
                                <th class="text-right">
                                    <button type="button"
                                            class="top-productos-sort"
                                            data-sort="unidades"
                                            title="Ordenar por unidades netas">
                                        Unid. netas
                                        <i class="fas fa-sort ml-1 sort-icon"></i>
                                    </button>
                                </th>
                                <th class="text-right">
                                    <button type="button"
                                            class="top-productos-sort"
                                            data-sort="venta"
                                            title="Ordenar por venta neta">
                                        Venta neta
                                        <i class="fas fa-sort ml-1 sort-icon"></i>
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="topProductosBody">
                            @forelse($porProducto as $item)
                                <tr class="venta-producto-row"
                                    data-codigo="{{ $item->codigo }}"
                                    data-unidades="{{ (float) $item->unidades_netas }}"
                                    data-venta="{{ (float) $item->venta_neta }}"
                                    title="Ver sucursales y vendedores">
                                    <td>
                                        <button type="button"
                                                class="btn btn-link p-0 text-left venta-producto-detalle"
                                                data-codigo="{{ $item->codigo }}">
                                            <strong>{{ $item->codigo }}</strong><br>
                                            <small class="text-muted">{{ $item->descripcion }}</small>
                                            <small class="d-block text-primary mt-1">
                                                <i class="fas fa-search mr-1"></i>
                                                Ver quién lo vendió
                                            </small>
                                        </button>
                                    </td>
                                    <td>{{ $item->grupo ?? '-' }}</td>
                                    <td>
                                        @if(!empty($item->temporada))
                                            @php
                                                $temporadaProducto = strtoupper(
                                                    trim((string) $item->temporada)
                                                );

                                                $claseTemporada = 'temporada-otro';

                                                if ($temporadaProducto === 'VERANO') {
                                                    $claseTemporada = 'temporada-verano';
                                                } elseif ($temporadaProducto === 'INVIERNO') {
                                                    $claseTemporada = 'temporada-invierno';
                                                } elseif ($temporadaProducto === 'AMBOS') {
                                                    $claseTemporada = 'temporada-ambos';
                                                }
                                            @endphp

                                            <span class="badge temporada-badge {{ $claseTemporada }}">
                                                {{ $item->temporada }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-right">{{ number_format($item->unidades_netas,0,',','.') }}</td>
                                    <td class="text-right font-weight-bold">
                                        Gs {{ number_format($item->venta_neta,0,',','.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">Sin datos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4 mt-3 mt-xl-0">
            <div class="card shadow-sm h-100 resumen-dia-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-calendar-alt mr-1"></i> Resumen por día
                    </h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 ventas-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th class="text-right">Unid.</th>
                                <th class="text-right">Venta neta</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($porDia->sortByDesc('fecha')->take(15) as $item)
                                <tr>
                                    <td>{{ date('d/m/Y', strtotime($item->fecha)) }}</td>
                                    <td class="text-right">{{ number_format($item->unidades_netas,0,',','.') }}</td>
                                    <td class="text-right font-weight-bold">
                                        Gs {{ number_format($item->venta_neta,0,',','.') }}
                                        @if($item->devoluciones > 0)
                                            <small class="d-block text-danger">
                                                Dev. Gs {{ number_format($item->devoluciones,0,',','.') }}
                                            </small>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">Sin datos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer text-center bg-white mt-auto">
                    <button type="button"
                            class="btn btn-sm btn-outline-primary"
                            data-toggle="modal"
                            data-target="#todosDiasModal">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        Ver todo
                        <span class="badge badge-light border ml-1">
                            {{ number_format($porDia->count(),0,',','.') }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-3" id="clientesInteligencia">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="card-title float-none mb-0">
                    <i class="fas fa-users mr-1"></i>
                    Inteligencia de clientes
                </h3>
                <small class="text-muted">
                    Frecuencia, valor, recurrencia y clientes a recuperar.
                </small>
            </div>

            <div class="btn-group btn-group-sm mt-2 mt-md-0"
                 role="group"
                 aria-label="Modo análisis clientes">
                <button type="button"
                        class="btn btn-primary cliente-modo-btn active"
                        data-modo="historico">
                    Histórico
                </button>

                <button type="button"
                        class="btn btn-outline-primary cliente-modo-btn"
                        data-modo="periodo">
                    Período filtrado
                </button>
            </div>
        </div>

        <div id="clientesLoading" class="text-center text-muted py-5">
            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
            <div>Analizando comportamiento de clientes...</div>
        </div>

        <div id="clientesError"
             class="alert alert-danger m-3 d-none"></div>

        <div id="clientesContenido" class="d-none">
            <div class="p-3 border-bottom bg-light">
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-2">
                        <div class="cliente-kpi h-100">
                            <small>Clientes únicos</small>
                            <strong id="cliTotal">0</strong>
                            <span id="cliFechaRef" class="text-muted"></span>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-2">
                        <div class="cliente-kpi h-100">
                            <small>Clientes recurrentes</small>
                            <strong id="cliRecurrentes">0</strong>
                            <span>2 o más días de compra</span>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-2">
                        <div class="cliente-kpi h-100">
                            <small>Clientes frecuentes</small>
                            <strong id="cliFrecuentes">0</strong>
                            <span>4 o más días de compra</span>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-2">
                        <div class="cliente-kpi h-100">
                            <small>Venta de recurrentes</small>
                            <strong id="cliVentaRecurrente">Gs 0</strong>
                            <span id="cliPorcRecurrente">0% de la venta de clientes</span>
                        </div>
                    </div>
                </div>

                <div class="row mt-1">
                    <div class="col-lg-6 mb-2">
                        <div class="cliente-destacado cliente-destacado-frecuencia">
                            <small>Cliente que más vuelve</small>
                            <strong id="cliMasFrecuente">-</strong>
                            <span id="cliMasFrecuenteMeta">Sin datos</span>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-2">
                        <div class="cliente-destacado cliente-destacado-valor">
                            <small>Cliente de mayor valor</small>
                            <strong id="cliMayorValor">-</strong>
                            <span id="cliMayorValorMeta">Sin datos</span>
                        </div>
                    </div>
                </div>

                <div class="small text-muted mt-1">
                    <strong>Lectura:</strong>
                    visita = día distinto con compra.
                    VIP = cliente recurrente dentro del 20% de mayor valor.
                    A recuperar = 2+ visitas y 30+ días sin comprar.
                    En modo Histórico se ignoran las fechas del filtro superior;
                    los demás filtros sí se respetan.
                </div>
            </div>

            <div class="row no-gutters">
                <div class="col-xl-8 border-right">
                    <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                        <strong>
                            <i class="fas fa-user-clock mr-1"></i>
                            Clientes por frecuencia
                        </strong>

                        <small class="text-muted">
                            Top 25 · primero quien más vuelve
                        </small>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 ventas-table clientes-table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th class="text-right">Visitas</th>
                                    <th class="text-right">Tickets</th>
                                    <th class="text-right">Unid.</th>
                                    <th class="text-right">Venta neta</th>
                                    <th class="text-right">Ticket prom.</th>
                                    <th>Última compra</th>
                                    <th>Segmento</th>
                                </tr>
                            </thead>
                            <tbody id="clientesRankingBody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="px-3 py-2 border-bottom">
                        <strong>
                            <i class="fas fa-user-plus mr-1"></i>
                            Oportunidad de recuperación
                        </strong>
                        <small class="d-block text-muted">
                            Clientes que ya compraban y dejaron de volver.
                        </small>
                    </div>

                    <div id="clientesRecuperar"
                         class="clientes-recuperar-list"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="card-title float-none mb-0">
                    <i class="fas fa-list mr-1"></i> Detalle de ventas
                </h3>
                <small class="text-muted">
                    Mostrando hasta 50 líneas por página para una carga más rápida
                </small>
            </div>
            <span class="badge badge-light border p-2">
                50 por página
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 ventas-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Local</th>
                        <th>Código / descripción</th>
                        <th>Cliente</th>
                        <th>Vendedor</th>
                        <th>Comprobante</th>
                        <th class="text-right">PLISTA</th>
                        <th class="text-right">DTO</th>
                        <th class="text-right">PVTA</th>
                        <th class="text-right">Cant.</th>
                        <th class="text-right">Total línea</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ventas as $item)
                        @php
                            // PVTA ya es el importe total de la línea del export.
                            $importe = (float) $item->p_venta;
                        @endphp
                        <tr class="{{ $item->cantidad < 0 ? 'table-danger' : '' }}">
                            <td>{{ date('d/m/Y', strtotime($item->fecha)) }}</td>
                            <td><strong>{{ $item->local }}</strong></td>
                            <td>
                                <strong>{{ $item->codigo }}</strong><br>
                                <small class="text-muted">{{ $item->descripcion }}</small>
                                @if(!empty($item->grupo))
                                    <small class="d-block text-info">
                                        {{ $item->grupo }}{{ !empty($item->temporada) ? ' · '.$item->temporada : '' }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                {{ $item->cliente ?: '-' }}
                                @if($item->cli_cod)
                                    <small class="d-block text-muted">Cod. {{ $item->cli_cod }}</small>
                                @endif
                            </td>
                            <td>{{ $item->vendedor ?: '-' }}</td>
                            <td>
                                <span class="badge {{ $item->tipo_comprobante === 'NCR' ? 'badge-danger' : 'badge-light border' }}">
                                    {{ $item->comprobante }}
                                </span>
                            </td>
                            <td class="text-right">Gs {{ number_format($item->p_lista,0,',','.') }}</td>
                            <td class="text-right">Gs {{ number_format($item->descuento,0,',','.') }}</td>
                            <td class="text-right">Gs {{ number_format($item->p_venta,0,',','.') }}</td>
                            <td class="text-right font-weight-bold {{ $item->cantidad < 0 ? 'text-danger' : '' }}">
                                {{ number_format($item->cantidad,0,',','.') }}
                            </td>
                            <td class="text-right font-weight-bold {{ $importe < 0 ? 'text-danger' : 'text-success' }}">
                                Gs {{ number_format($importe,0,',','.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-5">
                                No hay ventas para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($ventas->hasPages())
            <div class="card-footer">
                {{ $ventas->links() }}
            </div>
        @endif
    </div>

</div>

<div class="modal fade"
     id="todosDiasModal"
     tabindex="-1"
     role="dialog"
     aria-labelledby="todosDiasModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="todosDiasModalLabel">
                        Resumen completo por día
                    </h5>
                    <small class="text-muted">
                        Respeta los filtros activos de la pantalla.
                    </small>
                </div>

                <button type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 ventas-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th class="text-right">Unid.</th>
                                <th class="text-right">Tickets</th>
                                <th class="text-right">Devoluciones</th>
                                <th class="text-right">Venta neta</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($porDia->sortByDesc('fecha') as $item)
                                <tr>
                                    <td>
                                        <strong>
                                            {{ date('d/m/Y', strtotime($item->fecha)) }}
                                        </strong>
                                    </td>
                                    <td class="text-right">
                                        {{ number_format($item->unidades_netas,0,',','.') }}
                                    </td>
                                    <td class="text-right">
                                        {{ number_format($item->tickets,0,',','.') }}
                                    </td>
                                    <td class="text-right">
                                        @if($item->devoluciones > 0)
                                            <span class="text-danger">
                                                Gs {{ number_format($item->devoluciones,0,',','.') }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-right font-weight-bold text-success">
                                        Gs {{ number_format($item->venta_neta,0,',','.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5"
                                        class="text-center text-muted py-4">
                                        Sin datos diarios para los filtros actuales.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade"
     id="todosVendedoresModal"
     tabindex="-1"
     role="dialog"
     aria-labelledby="todosVendedoresModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="todosVendedoresModalLabel">
                        Ranking completo de vendedores
                    </h5>
                    <small class="text-muted">
                        Respeta los filtros activos de la pantalla.
                    </small>
                </div>

                <button type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body p-0">
                <div id="todosVendedoresLoading"
                     class="text-center text-muted py-5">
                    <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                    <div>Cargando ranking completo...</div>
                </div>

                <div id="todosVendedoresContenido" class="d-none">
                    <div class="px-3 py-2 border-bottom bg-light">
                        <small class="text-muted">
                            <span id="todosVendedoresTotal">0</span>
                            combinaciones sucursal + vendedor
                        </small>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 ventas-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Sucursal</th>
                                    <th>Vendedor</th>
                                    <th class="text-right">Unid.</th>
                                    <th class="text-right">Tickets</th>
                                    <th class="text-right">Venta neta</th>
                                </tr>
                            </thead>
                            <tbody id="todosVendedoresBody"></tbody>
                        </table>
                    </div>
                </div>

                <div id="todosVendedoresError"
                     class="alert alert-danger m-3 d-none"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade"
     id="ventaProductoModal"
     tabindex="-1"
     role="dialog"
     aria-labelledby="ventaProductoModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0" id="ventaProductoModalLabel">
                        Detalle del producto
                    </h5>
                    <small class="text-muted" id="ventaProductoSubtitulo">-</small>
                </div>
                <button type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div id="ventaProductoLoading"
                     class="text-center text-muted py-5">
                    <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                    <div>Cargando detalle...</div>
                </div>

                <div id="ventaProductoContenido" class="d-none">
                    <div class="row mb-3">
                        <div class="col-lg-3 col-6 mb-2">
                            <div class="vp-kpi">
                                <small>Cantidad neta</small>
                                <strong id="vpCantidad">0</strong>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6 mb-2">
                            <div class="vp-kpi">
                                <small>PLISTA</small>
                                <strong id="vpLista">Gs 0</strong>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6 mb-2">
                            <div class="vp-kpi">
                                <small>DTO</small>
                                <strong id="vpDto">Gs 0</strong>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6 mb-2">
                            <div class="vp-kpi">
                                <small>PVTA</small>
                                <strong class="text-success" id="vpVenta">Gs 0</strong>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-light border py-2 small mb-3"
                         id="vpMeta"></div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>
                            <i class="fas fa-store mr-1"></i>
                            Sucursal y vendedor
                        </strong>
                        <span class="badge badge-light border" id="vpCantidadFilas">0 registros</span>
                    </div>

                    <div class="table-responsive border rounded">
                        <table class="table table-sm table-hover mb-0 ventas-table">
                            <thead>
                                <tr>
                                    <th>Sucursal</th>
                                    <th>Vendedor</th>
                                    <th class="text-right">Cantidad</th>
                                    <th class="text-right">PLISTA</th>
                                    <th class="text-right">DTO</th>
                                    <th class="text-right">PVTA</th>
                                    <th class="text-right">Tickets</th>
                                </tr>
                            </thead>
                            <tbody id="vpDetalleBody"></tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-sm btn-light border"
                                type="button"
                                data-toggle="collapse"
                                data-target="#vpComprobantesCollapse"
                                aria-expanded="false">
                            <i class="fas fa-receipt mr-1"></i>
                            Ver comprobantes
                        </button>

                        <div class="collapse mt-2" id="vpComprobantesCollapse">
                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-hover mb-0 ventas-table">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Sucursal</th>
                                            <th>Vendedor</th>
                                            <th>Comprobante</th>
                                            <th class="text-right">Cantidad</th>
                                            <th class="text-right">PLISTA</th>
                                            <th class="text-right">DTO</th>
                                            <th class="text-right">PVTA</th>
                                        </tr>
                                    </thead>
                                    <tbody id="vpComprobantesBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="ventaProductoError"
                     class="alert alert-danger d-none mb-0"></div>
            </div>
        </div>
    </div>
</div>
</section>
@endsection

@push('page_css')
<style>
.border-left{border-left-width:4px!important}
.small-box.bg-white .icon{top:8px;font-size:44px;opacity:.14}
.small-box .inner h3{font-size:1.55rem}
.ct-mini{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:10px;
    padding:12px;
    box-shadow:0 2px 8px rgba(15,23,42,.04);
}
.ventas-table th{
    background:#f8fafc;
    color:#64748b;
    font-size:.68rem;
    text-transform:uppercase;
    letter-spacing:.025em;
    white-space:nowrap;
    vertical-align:middle!important;
}
.ventas-table td{
    font-size:.79rem;
    vertical-align:middle!important;
}
.resumen-dia-card{
    display:flex;
    flex-direction:column;
}
.resumen-dia-card .table-responsive{
    flex:1 1 auto;
}
.venta-producto-row{cursor:pointer}
.venta-producto-row:hover{background:#f8fbff}
.venta-producto-detalle{text-decoration:none!important;line-height:1.2}
.top-productos-sort{
    border:0;
    padding:0;
    margin:0;
    background:transparent;
    color:inherit;
    font:inherit;
    font-weight:700;
    text-transform:inherit;
    letter-spacing:inherit;
    cursor:pointer;
    white-space:nowrap;
}
.top-productos-sort:hover,
.top-productos-sort:focus{
    color:#2563eb;
    outline:none;
}
.top-productos-sort.is-active{
    color:#2563eb;
}
.top-productos-sort .sort-icon{
    font-size:.68rem;
}
.temporada-badge{
    padding:.35rem .55rem;
    border-radius:999px;
    font-weight:700;
    letter-spacing:.02em;
}
.temporada-verano{
    background:#dcfce7;
    color:#166534;
    border:1px solid #bbf7d0;
}
.temporada-invierno{
    background:#e0f2fe;
    color:#075985;
    border:1px solid #bae6fd;
}
.temporada-ambos{
    background:#ffedd5;
    color:#9a3412;
    border:1px solid #fed7aa;
}
.temporada-otro{
    background:#f1f5f9;
    color:#475569;
    border:1px solid #e2e8f0;
}
.vp-kpi{
    height:100%;
    border:1px solid #e2e8f0;
    border-radius:10px;
    background:#fff;
    padding:12px;
}
.vp-kpi small{
    display:block;
    color:#64748b;
    font-size:.68rem;
    font-weight:700;
    text-transform:uppercase;
}
.vp-kpi strong{
    display:block;
    margin-top:3px;
    font-size:1.15rem;
}
.cliente-kpi{
    border:1px solid #e2e8f0;
    border-radius:10px;
    background:#fff;
    padding:12px 14px;
}
.cliente-kpi small{
    display:block;
    color:#64748b;
    font-size:.68rem;
    font-weight:700;
    text-transform:uppercase;
}
.cliente-kpi strong{
    display:block;
    margin-top:2px;
    font-size:1.35rem;
    line-height:1.2;
}
.cliente-kpi span{
    display:block;
    margin-top:3px;
    font-size:.72rem;
    color:#64748b;
}
.cliente-destacado{
    height:100%;
    border-radius:10px;
    padding:12px 14px;
    border:1px solid #e2e8f0;
    background:#fff;
}
.cliente-destacado small{
    display:block;
    font-size:.68rem;
    font-weight:700;
    text-transform:uppercase;
    color:#64748b;
}
.cliente-destacado strong{
    display:block;
    margin-top:3px;
    font-size:1rem;
}
.cliente-destacado span{
    display:block;
    margin-top:3px;
    font-size:.76rem;
    color:#64748b;
}
.cliente-destacado-frecuencia{
    border-left:4px solid #17a2b8;
}
.cliente-destacado-valor{
    border-left:4px solid #28a745;
}
.cliente-segmento{
    display:inline-block;
    border-radius:999px;
    padding:.25rem .5rem;
    font-size:.65rem;
    font-weight:800;
    white-space:nowrap;
}
.cliente-segmento-vip{
    background:#fef3c7;
    color:#92400e;
}
.cliente-segmento-frecuente{
    background:#dcfce7;
    color:#166534;
}
.cliente-segmento-recurrente{
    background:#e0f2fe;
    color:#075985;
}
.cliente-segmento-recuperar{
    background:#fee2e2;
    color:#991b1b;
}
.cliente-segmento-ocasional{
    background:#f1f5f9;
    color:#475569;
}
.clientes-recuperar-list{
    max-height:520px;
    overflow:auto;
}
.cliente-recuperar-item{
    padding:10px 14px;
    border-bottom:1px solid #edf2f7;
}
.cliente-recuperar-item:last-child{
    border-bottom:0;
}
.cliente-recuperar-item strong{
    display:block;
    font-size:.84rem;
}
.cliente-recuperar-item span{
    display:block;
    margin-top:2px;
    font-size:.72rem;
    color:#64748b;
}
</style>
@endpush

@push('page_scripts')
<script>
(function () {
    const urlPlantilla = @json(
        route(
            'ventas.producto.detalle',
            ['codigo' => '__CODIGO__']
        )
    );

    const urlVendedoresTodos = @json(
        route('ventas.vendedores.todos')
    );

    const urlClientesResumen = @json(
        route('ventas.clientes.resumen')
    );

    const filtrosActuales = {
        desde: @json($desde),
        hasta: @json($hasta),
        local: @json($local),
        vendedor: @json($vendedor),
        tipo: @json($tipo),
        buscar: @json($buscar)
    };

    const nf = new Intl.NumberFormat('es-PY');

    function gs(valor) {
        return 'Gs ' + nf.format(
            Math.round(Number(valor || 0))
        );
    }

    function num(valor) {
        return nf.format(Number(valor || 0));
    }

    function esc(valor) {
        return String(valor ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function construirUrl(codigo) {
        let url = urlPlantilla.replace(
            '__CODIGO__',
            encodeURIComponent(codigo)
        );

        const params = new URLSearchParams();

        Object.entries(filtrosActuales).forEach(
            function (entrada) {
                const clave = entrada[0];
                const valor = entrada[1];

                if (
                    valor !== null
                    && valor !== undefined
                    && valor !== ''
                ) {
                    params.set(clave, valor);
                }
            }
        );

        const qs = params.toString();

        return qs ? url + '?' + qs : url;
    }

    function construirUrlVendedores() {
        const params = new URLSearchParams();

        Object.entries(filtrosActuales).forEach(
            function (entrada) {
                const clave = entrada[0];
                const valor = entrada[1];

                if (
                    valor !== null
                    && valor !== undefined
                    && valor !== ''
                ) {
                    params.set(clave, valor);
                }
            }
        );

        const qs = params.toString();

        return qs
            ? urlVendedoresTodos + '?' + qs
            : urlVendedoresTodos;
    }

    function abrirTodosVendedores() {
        document.getElementById(
            'todosVendedoresLoading'
        ).classList.remove('d-none');

        document.getElementById(
            'todosVendedoresContenido'
        ).classList.add('d-none');

        document.getElementById(
            'todosVendedoresError'
        ).classList.add('d-none');

        document.getElementById(
            'todosVendedoresBody'
        ).innerHTML = '';

        $('#todosVendedoresModal').modal('show');

        fetch(
            construirUrlVendedores(),
            {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-store'
            }
        )
            .then(async function (response) {
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(
                        data.message
                            || 'No se pudo cargar el ranking.'
                    );
                }

                return data;
            })
            .then(function (data) {
                const vendedores =
                    Array.isArray(data.vendedores)
                        ? data.vendedores
                        : [];

                document.getElementById(
                    'todosVendedoresTotal'
                ).textContent = num(data.total || vendedores.length);

                document.getElementById(
                    'todosVendedoresBody'
                ).innerHTML = vendedores.length
                    ? vendedores.map(
                        function (item, indice) {
                            return ''
                                + '<tr>'
                                + '<td>' + num(indice + 1) + '</td>'
                                + '<td><span class="badge badge-light border">'
                                    + esc(item.local)
                                    + '</span></td>'
                                + '<td><strong>'
                                    + esc(item.vendedor)
                                    + '</strong></td>'
                                + '<td class="text-right">'
                                    + num(item.unidades_netas)
                                    + '</td>'
                                + '<td class="text-right">'
                                    + num(item.tickets)
                                    + '</td>'
                                + '<td class="text-right font-weight-bold text-success">'
                                    + gs(item.venta_neta)
                                    + '</td>'
                                + '</tr>';
                        }
                    ).join('')
                    : '<tr><td colspan="6" '
                        + 'class="text-center text-muted py-4">'
                        + 'Sin vendedores para los filtros actuales.'
                        + '</td></tr>';

                document.getElementById(
                    'todosVendedoresLoading'
                ).classList.add('d-none');

                document.getElementById(
                    'todosVendedoresContenido'
                ).classList.remove('d-none');
            })
            .catch(function (error) {
                document.getElementById(
                    'todosVendedoresLoading'
                ).classList.add('d-none');

                const caja = document.getElementById(
                    'todosVendedoresError'
                );

                caja.textContent =
                    error.message
                    || 'No se pudo cargar el ranking.';

                caja.classList.remove('d-none');
            });
    }

    const btnTodosVendedores = document.getElementById(
        'btnTodosVendedores'
    );

    if (btnTodosVendedores) {
        btnTodosVendedores.addEventListener(
            'click',
            abrirTodosVendedores
        );
    }

    function construirUrlClientes(modo) {
        const params = new URLSearchParams();

        Object.entries(filtrosActuales).forEach(
            function (entrada) {
                const clave = entrada[0];
                const valor = entrada[1];

                if (
                    valor !== null
                    && valor !== undefined
                    && valor !== ''
                ) {
                    params.set(clave, valor);
                }
            }
        );

        params.set('modo', modo || 'historico');

        return urlClientesResumen + '?' + params.toString();
    }

    function claseSegmento(segmento) {
        switch (segmento) {
            case 'VIP':
                return 'cliente-segmento-vip';
            case 'FRECUENTE':
                return 'cliente-segmento-frecuente';
            case 'RECURRENTE':
                return 'cliente-segmento-recurrente';
            case 'A RECUPERAR':
                return 'cliente-segmento-recuperar';
            default:
                return 'cliente-segmento-ocasional';
        }
    }

    function filaCliente(item) {
        const codigo = item.cli_cod
            ? '<small class="d-block text-muted">Cod. '
                + esc(item.cli_cod)
                + '</small>'
            : '';

        const dias = item.dias_sin_compra === null
            || item.dias_sin_compra === undefined
                ? ''
                : '<small class="d-block text-muted">'
                    + num(item.dias_sin_compra)
                    + ' día'
                    + (Number(item.dias_sin_compra) === 1 ? '' : 's')
                    + ' sin comprar</small>';

        return ''
            + '<tr>'
            + '<td><strong>' + esc(item.cliente) + '</strong>'
                + codigo + '</td>'
            + '<td class="text-right font-weight-bold">'
                + num(item.visitas) + '</td>'
            + '<td class="text-right">' + num(item.tickets) + '</td>'
            + '<td class="text-right">' + num(item.unidades_netas) + '</td>'
            + '<td class="text-right font-weight-bold text-success">'
                + gs(item.venta_neta) + '</td>'
            + '<td class="text-right">' + gs(item.ticket_promedio) + '</td>'
            + '<td>' + esc(item.ultima_compra || '-') + dias + '</td>'
            + '<td><span class="cliente-segmento '
                + claseSegmento(item.segmento) + '">'
                + esc(item.segmento) + '</span></td>'
            + '</tr>';
    }

    function itemRecuperar(item) {
        return ''
            + '<div class="cliente-recuperar-item">'
            + '<strong>' + esc(item.cliente) + '</strong>'
            + '<span>'
                + num(item.visitas) + ' visitas · '
                + num(item.dias_sin_compra) + ' días sin comprar'
                + '</span>'
            + '<span>'
                + 'Valor histórico ' + gs(item.venta_neta)
                + ' · Ticket prom. ' + gs(item.ticket_promedio)
                + '</span>'
            + '</div>';
    }

    let modoClientesActual = 'historico';
    let clientesCargados = false;
    let clientesCargando = false;

    function activarModoClientes(modo) {
        document.querySelectorAll('.cliente-modo-btn').forEach(
            function (button) {
                const activo = button.dataset.modo === modo;

                button.classList.toggle('btn-primary', activo);
                button.classList.toggle('active', activo);
                button.classList.toggle('btn-outline-primary', !activo);
            }
        );
    }

    function cargarClientes(modo) {
        if (clientesCargando) {
            return;
        }

        modoClientesActual = modo || 'historico';
        activarModoClientes(modoClientesActual);

        const loading = document.getElementById('clientesLoading');
        const contenido = document.getElementById('clientesContenido');
        const errorBox = document.getElementById('clientesError');

        if (!loading || !contenido || !errorBox) {
            return;
        }

        clientesCargando = true;
        clientesCargados = true;

        loading.classList.remove('d-none');
        contenido.classList.add('d-none');
        errorBox.classList.add('d-none');

        fetch(
            construirUrlClientes(modoClientesActual),
            {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-store'
            }
        )
            .then(async function (response) {
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(
                        data.message
                            || 'No se pudo cargar el análisis de clientes.'
                    );
                }

                return data;
            })
            .then(function (data) {
                const resumen = data.resumen || {};
                const clientes = Array.isArray(data.clientes)
                    ? data.clientes
                    : [];
                const recuperar = Array.isArray(data.recuperar)
                    ? data.recuperar
                    : [];

                document.getElementById('cliTotal').textContent =
                    num(resumen.clientes);

                document.getElementById('cliRecurrentes').textContent =
                    num(resumen.recurrentes);

                document.getElementById('cliFrecuentes').textContent =
                    num(resumen.frecuentes);

                document.getElementById('cliVentaRecurrente').textContent =
                    gs(resumen.venta_recurrente);

                document.getElementById('cliPorcRecurrente').textContent =
                    num(resumen.porcentaje_venta_recurrente)
                    + '% de la venta de clientes';

                document.getElementById('cliFechaRef').textContent =
                    resumen.fecha_referencia
                        ? 'Referencia ' + resumen.fecha_referencia
                        : 'Sin fecha de referencia';

                const frecuente = resumen.cliente_mas_frecuente;
                const mayorValor = resumen.cliente_mayor_valor;

                document.getElementById('cliMasFrecuente').textContent =
                    frecuente ? frecuente.cliente : '-';

                document.getElementById('cliMasFrecuenteMeta').textContent =
                    frecuente
                        ? num(frecuente.visitas)
                            + ' visitas · '
                            + num(frecuente.tickets)
                            + ' tickets · '
                            + gs(frecuente.venta_neta)
                        : 'Sin datos';

                document.getElementById('cliMayorValor').textContent =
                    mayorValor ? mayorValor.cliente : '-';

                document.getElementById('cliMayorValorMeta').textContent =
                    mayorValor
                        ? gs(mayorValor.venta_neta)
                            + ' · '
                            + num(mayorValor.visitas)
                            + ' visitas · ticket prom. '
                            + gs(mayorValor.ticket_promedio)
                        : 'Sin datos';

                document.getElementById('clientesRankingBody').innerHTML =
                    clientes.length
                        ? clientes.map(filaCliente).join('')
                        : '<tr><td colspan="8" '
                            + 'class="text-center text-muted py-4">'
                            + 'No hay clientes para este análisis.'
                            + '</td></tr>';

                document.getElementById('clientesRecuperar').innerHTML =
                    recuperar.length
                        ? recuperar.map(itemRecuperar).join('')
                        : '<div class="text-center text-muted py-4 px-3">'
                            + 'No hay clientes a recuperar con este criterio.'
                            + '</div>';

                loading.classList.add('d-none');
                contenido.classList.remove('d-none');
            })
            .catch(function (error) {
                loading.classList.add('d-none');
                errorBox.textContent =
                    error.message
                    || 'No se pudo cargar el análisis de clientes.';
                errorBox.classList.remove('d-none');
            })
            .finally(function () {
                clientesCargando = false;
            });
    }

    document.querySelectorAll('.cliente-modo-btn').forEach(
        function (button) {
            button.addEventListener('click', function () {
                cargarClientes(button.dataset.modo || 'historico');
            });
        }
    );

    const clientesSection = document.getElementById(
        'clientesInteligencia'
    );

    if (clientesSection) {
        if ('IntersectionObserver' in window) {
            const clientesObserver = new IntersectionObserver(
                function (entries, observer) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting && !clientesCargados) {
                            cargarClientes(modoClientesActual);
                            observer.disconnect();
                        }
                    });
                },
                {
                    rootMargin: '250px 0px'
                }
            );

            clientesObserver.observe(clientesSection);
        } else {
            cargarClientes(modoClientesActual);
        }
    }

    function limpiarModal() {
        document.getElementById(
            'ventaProductoLoading'
        ).classList.remove('d-none');

        document.getElementById(
            'ventaProductoContenido'
        ).classList.add('d-none');

        document.getElementById(
            'ventaProductoError'
        ).classList.add('d-none');

        document.getElementById(
            'vpDetalleBody'
        ).innerHTML = '';

        document.getElementById(
            'vpComprobantesBody'
        ).innerHTML = '';

        $('#vpComprobantesCollapse').collapse('hide');
    }

    function filaDetalle(item) {
        const claseVenta =
            Number(item.p_venta) < 0
                ? 'text-danger'
                : 'text-success';

        return ''
            + '<tr>'
            + '<td><strong>' + esc(item.local) + '</strong></td>'
            + '<td>' + esc(item.vendedor) + '</td>'
            + '<td class="text-right font-weight-bold">'
                + num(item.cantidad)
                + '</td>'
            + '<td class="text-right">' + gs(item.p_lista) + '</td>'
            + '<td class="text-right">' + gs(item.descuento) + '</td>'
            + '<td class="text-right font-weight-bold '
                + claseVenta + '">'
                + gs(item.p_venta)
                + '</td>'
            + '<td class="text-right">' + num(item.tickets) + '</td>'
            + '</tr>';
    }

    function filaComprobante(item) {
        const claseVenta =
            Number(item.p_venta) < 0
                ? 'text-danger'
                : '';

        return ''
            + '<tr>'
            + '<td>' + esc(item.fecha) + '</td>'
            + '<td>' + esc(item.local) + '</td>'
            + '<td>' + esc(item.vendedor) + '</td>'
            + '<td><strong>'
                + esc(item.comprobante)
                + '</strong></td>'
            + '<td class="text-right">'
                + num(item.cantidad)
                + '</td>'
            + '<td class="text-right">' + gs(item.p_lista) + '</td>'
            + '<td class="text-right">' + gs(item.descuento) + '</td>'
            + '<td class="text-right font-weight-bold '
                + claseVenta + '">'
                + gs(item.p_venta)
                + '</td>'
            + '</tr>';
    }

    function abrirDetalle(codigo) {
        limpiarModal();

        document.getElementById(
            'ventaProductoModalLabel'
        ).textContent = 'Detalle del producto';

        document.getElementById(
            'ventaProductoSubtitulo'
        ).textContent = codigo;

        $('#ventaProductoModal').modal('show');

        fetch(
            construirUrl(codigo),
            {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-store'
            }
        )
            .then(async function (response) {
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(
                        data.message
                            || 'No se pudo cargar el detalle.'
                    );
                }

                return data;
            })
            .then(function (data) {
                const p = data.producto || {};

                document.getElementById(
                    'ventaProductoModalLabel'
                ).textContent = p.codigo || codigo;

                document.getElementById(
                    'ventaProductoSubtitulo'
                ).textContent = p.descripcion || '-';

                document.getElementById(
                    'vpCantidad'
                ).textContent = num(p.cantidad);

                document.getElementById(
                    'vpLista'
                ).textContent = gs(p.p_lista);

                document.getElementById(
                    'vpDto'
                ).textContent = gs(p.descuento);

                document.getElementById(
                    'vpVenta'
                ).textContent = gs(p.p_venta);

                const meta = [];

                if (p.grupo) {
                    meta.push(
                        '<strong>Grupo:</strong> '
                        + esc(p.grupo)
                    );
                }

                if (p.temporada) {
                    meta.push(
                        '<strong>Temporada:</strong> '
                        + esc(p.temporada)
                    );
                }

                if (p.linea) {
                    meta.push(
                        '<strong>Línea:</strong> '
                        + esc(p.linea)
                    );
                }

                meta.push(
                    '<strong>Tickets:</strong> '
                    + num(p.tickets)
                );

                document.getElementById(
                    'vpMeta'
                ).innerHTML = meta.join(
                    ' &nbsp;·&nbsp; '
                );

                const detalle = Array.isArray(data.detalle)
                    ? data.detalle
                    : [];

                document.getElementById(
                    'vpCantidadFilas'
                ).textContent =
                    detalle.length
                    + ' combinación'
                    + (detalle.length === 1 ? '' : 'es');

                document.getElementById(
                    'vpDetalleBody'
                ).innerHTML = detalle.length
                    ? detalle.map(filaDetalle).join('')
                    : '<tr><td colspan="7" '
                        + 'class="text-center text-muted py-3">'
                        + 'Sin detalle para los filtros actuales.'
                        + '</td></tr>';

                const comprobantes =
                    Array.isArray(data.comprobantes)
                        ? data.comprobantes
                        : [];

                document.getElementById(
                    'vpComprobantesBody'
                ).innerHTML = comprobantes.length
                    ? comprobantes.map(
                        filaComprobante
                    ).join('')
                    : '<tr><td colspan="8" '
                        + 'class="text-center text-muted py-3">'
                        + 'Sin comprobantes.'
                        + '</td></tr>';

                document.getElementById(
                    'ventaProductoLoading'
                ).classList.add('d-none');

                document.getElementById(
                    'ventaProductoContenido'
                ).classList.remove('d-none');
            })
            .catch(function (error) {
                document.getElementById(
                    'ventaProductoLoading'
                ).classList.add('d-none');

                const caja = document.getElementById(
                    'ventaProductoError'
                );

                caja.textContent =
                    error.message
                    || 'No se pudo cargar el detalle.';

                caja.classList.remove('d-none');
            });
    }

    const topProductosBody = document.getElementById(
        'topProductosBody'
    );

    const topProductosSortButtons = document.querySelectorAll(
        '.top-productos-sort'
    );

    const topProductosSortState = {
        campo: null,
        direccion: null
    };

    function actualizarIconosTopProductos() {
        topProductosSortButtons.forEach(function (button) {
            const icon = button.querySelector('.sort-icon');
            const activo =
                button.dataset.sort === topProductosSortState.campo;

            button.classList.toggle('is-active', activo);

            if (!icon) {
                return;
            }

            icon.classList.remove(
                'fa-sort',
                'fa-sort-up',
                'fa-sort-down'
            );

            if (!activo) {
                icon.classList.add('fa-sort');
                return;
            }

            icon.classList.add(
                topProductosSortState.direccion === 'desc'
                    ? 'fa-sort-down'
                    : 'fa-sort-up'
            );
        });
    }

    function ordenarTopProductos(campo) {
        if (!topProductosBody) {
            return;
        }

        if (topProductosSortState.campo === campo) {
            topProductosSortState.direccion =
                topProductosSortState.direccion === 'desc'
                    ? 'asc'
                    : 'desc';
        } else {
            topProductosSortState.campo = campo;
            topProductosSortState.direccion = 'desc';
        }

        const atributo =
            campo === 'unidades'
                ? 'unidades'
                : 'venta';

        const filas = Array.from(
            topProductosBody.querySelectorAll(
                '.venta-producto-row'
            )
        );

        filas.sort(function (a, b) {
            const valorA = Number(
                a.dataset[atributo] || 0
            );

            const valorB = Number(
                b.dataset[atributo] || 0
            );

            if (valorA === valorB) {
                return 0;
            }

            if (topProductosSortState.direccion === 'desc') {
                return valorB - valorA;
            }

            return valorA - valorB;
        });

        filas.forEach(function (fila) {
            topProductosBody.appendChild(fila);
        });

        actualizarIconosTopProductos();
    }

    topProductosSortButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            ordenarTopProductos(
                button.dataset.sort
            );
        });
    });

    document.addEventListener(
        'click',
        function (event) {
            const boton = event.target.closest(
                '.venta-producto-detalle'
            );

            if (boton) {
                event.preventDefault();
                event.stopPropagation();

                abrirDetalle(
                    boton.dataset.codigo
                );

                return;
            }

            const fila = event.target.closest(
                '.venta-producto-row'
            );

            if (fila) {
                abrirDetalle(
                    fila.dataset.codigo
                );
            }
        }
    );
})();
</script>
@endpush
