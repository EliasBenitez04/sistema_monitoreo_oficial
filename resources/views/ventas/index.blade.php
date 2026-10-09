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
                            data-toggle="modal"
                            data-target="#todosVendedoresModal">
                        <i class="fas fa-list mr-1"></i>
                        Ver todos
                        <span class="badge badge-light border ml-1">
                            {{ number_format($porVendedorTodos->count(),0,',','.') }}
                        </span>
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
                    <table class="table table-sm table-hover mb-0 ventas-table">
                        <thead>
                            <tr>
                                <th>Código / artículo</th>
                                <th>Grupo</th>
                                <th>Temporada</th>
                                <th class="text-right">Unid. netas</th>
                                <th class="text-right">Venta neta</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($porProducto as $item)
                                <tr class="venta-producto-row"
                                    data-codigo="{{ $item->codigo }}"
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
                                            <span class="badge badge-info">{{ $item->temporada }}</span>
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
            <div class="card shadow-sm h-100">
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
                            @forelse($porDia as $item)
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
                    {{ number_format($ventas->total(),0,',','.') }} líneas con los filtros actuales
                </small>
            </div>
            <span class="badge badge-light border p-2">
                100 por página
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
                        <tbody>
                            @forelse($porVendedorTodos as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="badge badge-light border">
                                            {{ $item->local }}
                                        </span>
                                    </td>
                                    <td><strong>{{ $item->vendedor }}</strong></td>
                                    <td class="text-right">
                                        {{ number_format($item->unidades_netas,0,',','.') }}
                                    </td>
                                    <td class="text-right">
                                        {{ number_format($item->tickets,0,',','.') }}
                                    </td>
                                    <td class="text-right font-weight-bold text-success">
                                        Gs {{ number_format($item->venta_neta,0,',','.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"
                                        class="text-center text-muted py-4">
                                        Sin vendedores para los filtros actuales.
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
.venta-producto-row{cursor:pointer}
.venta-producto-row:hover{background:#f8fbff}
.venta-producto-detalle{text-decoration:none!important;line-height:1.2}
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
