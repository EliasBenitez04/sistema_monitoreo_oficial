@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1>
                    <i class="fas fa-users mr-2"></i>
                    Clientes
                </h1>
                <p class="text-muted mb-0">
                    Frecuencia, valor, recurrencia, comportamiento de compra y detalle de facturación.
                </p>
            </div>

            <a href="{{ route('ventas.index') }}"
               class="btn btn-light border shadow-sm mt-2 mt-md-0">
                <i class="fas fa-arrow-left mr-1"></i>
                Volver a Ventas
            </a>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">

    <div class="card card-outline card-primary shadow-sm mb-3">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-filter mr-1"></i>
                Filtros de clientes
            </h3>
        </div>

        <div class="card-body pb-2">
            <form method="GET" action="{{ route('ventas.clientes.index') }}">
                <div class="row align-items-end">
                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="small font-weight-bold">Desde</label>
                        <input type="date"
                               name="desde"
                               value="{{ $desde }}"
                               class="form-control">
                    </div>

                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="small font-weight-bold">Hasta</label>
                        <input type="date"
                               name="hasta"
                               value="{{ $hasta }}"
                               class="form-control">
                    </div>

                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="small font-weight-bold">Local</label>
                        <select name="local" class="form-control select2">
                            <option value="">Todos</option>
                            @foreach($locales as $item)
                                <option value="{{ $item }}"
                                        {{ $local === $item ? 'selected' : '' }}>
                                    {{ $item }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="small font-weight-bold">Segmento</label>
                        <select name="segmento" class="form-control">
                            <option value="">Todos</option>
                            <option value="VIP" {{ $segmento === 'VIP' ? 'selected' : '' }}>VIP</option>
                            <option value="FRECUENTE" {{ $segmento === 'FRECUENTE' ? 'selected' : '' }}>Frecuente</option>
                            <option value="RECURRENTE" {{ $segmento === 'RECURRENTE' ? 'selected' : '' }}>Recurrente</option>
                            <option value="A RECUPERAR" {{ $segmento === 'A RECUPERAR' ? 'selected' : '' }}>A recuperar</option>
                            <option value="OCASIONAL" {{ $segmento === 'OCASIONAL' ? 'selected' : '' }}>Ocasional</option>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="small font-weight-bold">Ordenar por</label>
                        <select name="orden" class="form-control">
                            <option value="frecuencia" {{ $orden === 'frecuencia' ? 'selected' : '' }}>Frecuencia</option>
                            <option value="valor" {{ $orden === 'valor' ? 'selected' : '' }}>Venta neta</option>
                            <option value="tickets" {{ $orden === 'tickets' ? 'selected' : '' }}>Tickets</option>
                            <option value="ticket" {{ $orden === 'ticket' ? 'selected' : '' }}>Ticket promedio</option>
                            <option value="reciente" {{ $orden === 'reciente' ? 'selected' : '' }}>Última compra</option>
                            <option value="nombre" {{ $orden === 'nombre' ? 'selected' : '' }}>Nombre</option>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4 mb-2">
                        <label class="small font-weight-bold">Dirección</label>
                        <select name="dir" class="form-control">
                            <option value="desc" {{ $direccion === 'desc' ? 'selected' : '' }}>Mayor a menor</option>
                            <option value="asc" {{ $direccion === 'asc' ? 'selected' : '' }}>Menor a mayor</option>
                        </select>
                    </div>
                </div>

                <div class="row align-items-end">
                    <div class="col-lg-8 col-md-8 mb-2">
                        <label class="small font-weight-bold">Buscar cliente</label>
                        <input type="text"
                               name="buscar"
                               value="{{ $buscar }}"
                               class="form-control"
                               placeholder="Nombre o código del cliente">
                    </div>

                    <div class="col-lg-4 col-md-4 mb-2 text-md-right">
                        <a href="{{ route('ventas.clientes.index') }}"
                           class="btn btn-light border mr-1">
                            <i class="fas fa-eraser mr-1"></i>
                            Limpiar
                        </a>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search mr-1"></i>
                            Consultar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="cliente-box cliente-box-primary">
                <small>Clientes identificados</small>
                <strong>{{ number_format($stats->clientes,0,',','.') }}</strong>
                <span>
                    Referencia:
                    {{ $fechaReferencia ? date('d/m/Y', strtotime($fechaReferencia)) : '-' }}
                </span>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="cliente-box cliente-box-info">
                <small>Recurrentes</small>
                <strong>{{ number_format($stats->recurrentes,0,',','.') }}</strong>
                <span>
                    {{ number_format($stats->tasa_recurrencia,1,',','.') }}%
                    compró en 2+ días distintos
                </span>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="cliente-box cliente-box-success">
                <small>Frecuentes</small>
                <strong>{{ number_format($stats->frecuentes,0,',','.') }}</strong>
                <span>4 o más días distintos de compra</span>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="cliente-box cliente-box-danger">
                <small>A recuperar</small>
                <strong>{{ number_format($stats->recuperar,0,',','.') }}</strong>
                <span>2+ visitas y 30+ días sin comprar</span>
            </div>
        </div>
    </div>

    <div class="row mt-1">
        <div class="col-xl-3 col-md-6">
            <div class="cliente-mini">
                <small>Venta neta identificada</small>
                <strong>Gs {{ number_format($stats->venta_neta,0,',','.') }}</strong>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="cliente-mini">
                <small>Valor promedio / cliente</small>
                <strong>Gs {{ number_format($stats->valor_promedio_cliente,0,',','.') }}</strong>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="cliente-mini">
                <small>Ticket promedio</small>
                <strong>Gs {{ number_format($stats->ticket_promedio,0,',','.') }}</strong>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="cliente-mini">
                <small>Activos últimos 30 días</small>
                <strong>{{ number_format($stats->activos_30,0,',','.') }}</strong>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-lg-6 mb-3">
            <div class="cliente-highlight cliente-highlight-info h-100">
                <small>Cliente que más vuelve</small>
                @if($topFrecuente)
                    <strong>{{ $topFrecuente->cliente ?: 'SIN NOMBRE' }}</strong>
                    <span>
                        {{ number_format($topFrecuente->visitas,0,',','.') }} visitas
                        · {{ number_format($topFrecuente->tickets,0,',','.') }} tickets
                        · Gs {{ number_format($topFrecuente->venta_neta,0,',','.') }}
                    </span>
                @else
                    <strong>-</strong>
                    <span>Sin datos</span>
                @endif
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="cliente-highlight cliente-highlight-success h-100">
                <small>Cliente de mayor valor</small>
                @if($topValor)
                    <strong>{{ $topValor->cliente ?: 'SIN NOMBRE' }}</strong>
                    <span>
                        Gs {{ number_format($topValor->venta_neta,0,',','.') }}
                        · {{ number_format($topValor->visitas,0,',','.') }} visitas
                        · {{ number_format($topValor->tickets,0,',','.') }} tickets
                    </span>
                @else
                    <strong>-</strong>
                    <span>Sin datos</span>
                @endif
            </div>
        </div>
    </div>

    <div class="alert alert-light border py-2 mb-3">
        <strong>Cómo leer Clientes:</strong>
        visita = día distinto en que el cliente compró.
        <strong>VIP</strong> = recurrente dentro del 20% de mayor valor del período.
        <strong>A recuperar</strong> = tuvo al menos 2 visitas y lleva 30 días o más sin comprar.
        El detalle usa el código de cliente cuando existe; si no, usa el nombre como respaldo.
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="card-title float-none mb-0">
                    <i class="fas fa-address-book mr-1"></i>
                    Cartera de clientes
                </h3>
                <small class="text-muted">
                    Hacé clic en Ver perfil para consultar facturas, productos, locales y vendedores.
                </small>
            </div>

            <span class="badge badge-light border p-2 mt-2 mt-md-0">
                50 clientes por página
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 ventas-table clientes-main-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th class="text-right">Visitas</th>
                        <th class="text-right">Tickets</th>
                        <th class="text-right">Unid.</th>
                        <th class="text-right">Venta neta</th>
                        <th class="text-right">Ticket prom.</th>
                        <th class="text-right">Frecuencia</th>
                        <th>Primera compra</th>
                        <th>Última compra</th>
                        <th>Segmento</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($clientes as $item)
                        @php
                            $segmentoClase = 'cliente-segmento-ocasional';

                            if ($item->segmento === 'VIP') {
                                $segmentoClase = 'cliente-segmento-vip';
                            } elseif ($item->segmento === 'FRECUENTE') {
                                $segmentoClase = 'cliente-segmento-frecuente';
                            } elseif ($item->segmento === 'RECURRENTE') {
                                $segmentoClase = 'cliente-segmento-recurrente';
                            } elseif ($item->segmento === 'A RECUPERAR') {
                                $segmentoClase = 'cliente-segmento-recuperar';
                            }
                        @endphp

                        <tr>
                            <td>
                                <strong>{{ $item->cliente ?: 'SIN NOMBRE' }}</strong>

                                @if(!empty($item->cli_cod))
                                    <small class="d-block text-muted">
                                        Cod. {{ $item->cli_cod }}
                                    </small>
                                @endif

                                <small class="d-block text-muted">
                                    {{ number_format($item->locales,0,',','.') }} local(es)
                                    · {{ number_format($item->vendedores,0,',','.') }} vendedor(es)
                                    · {{ number_format($item->productos,0,',','.') }} producto(s)
                                </small>
                            </td>

                            <td class="text-right font-weight-bold">
                                {{ number_format($item->visitas,0,',','.') }}
                            </td>

                            <td class="text-right">
                                {{ number_format($item->tickets,0,',','.') }}
                            </td>

                            <td class="text-right">
                                {{ number_format($item->unidades_netas,0,',','.') }}
                            </td>

                            <td class="text-right font-weight-bold text-success">
                                Gs {{ number_format($item->venta_neta,0,',','.') }}

                                <small class="d-block text-muted">
                                    {{ number_format($item->participacion,2,',','.') }}% cartera
                                </small>
                            </td>

                            <td class="text-right">
                                Gs {{ number_format($item->ticket_promedio,0,',','.') }}
                            </td>

                            <td class="text-right">
                                @if($item->dias_promedio_entre_visitas !== null)
                                    {{ number_format($item->dias_promedio_entre_visitas,1,',','.') }} días
                                @else
                                    -
                                @endif

                                <small class="d-block text-muted">
                                    {{ number_format($item->unidades_por_visita,1,',','.') }} unid./visita
                                </small>
                            </td>

                            <td>
                                {{ $item->primera_compra
                                    ? date('d/m/Y', strtotime($item->primera_compra))
                                    : '-' }}
                            </td>

                            <td>
                                <strong>
                                    {{ $item->ultima_compra
                                        ? date('d/m/Y', strtotime($item->ultima_compra))
                                        : '-' }}
                                </strong>

                                <small class="d-block {{ $item->dias_sin_compra >= 30 ? 'text-danger' : 'text-muted' }}">
                                    {{ number_format($item->dias_sin_compra,0,',','.') }}
                                    días sin comprar
                                </small>
                            </td>

                            <td>
                                <span class="cliente-segmento {{ $segmentoClase }}">
                                    {{ $item->segmento }}
                                </span>
                            </td>

                            <td class="text-right">
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary btn-cliente-perfil"
                                        data-cliente-key="{{ $item->cliente_key }}"
                                        data-cliente="{{ $item->cliente }}">
                                    <i class="fas fa-search mr-1"></i>
                                    Ver perfil
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11"
                                class="text-center text-muted py-5">
                                No hay clientes para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($clientes->hasPages())
            <div class="card-footer">
                {{ $clientes->links() }}
            </div>
        @endif
    </div>

</div>
</section>

<div class="modal fade"
     id="clientePerfilModal"
     tabindex="-1"
     role="dialog"
     aria-labelledby="clientePerfilModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="clientePerfilModalLabel">
                        Perfil del cliente
                    </h5>
                    <small class="text-muted" id="clientePerfilSubtitulo">
                        Historial comercial
                    </small>
                </div>

                <button type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div id="clientePerfilLoading"
                 class="text-center text-muted py-5">
                <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                <div>Cargando perfil completo...</div>
            </div>

            <div id="clientePerfilError"
                 class="alert alert-danger m-3 d-none"></div>

            <div id="clientePerfilContenido" class="d-none">
                <div class="p-3 border-bottom bg-light">
                    <div class="row">
                        <div class="col-xl-3 col-md-6 mb-2">
                            <div class="perfil-kpi">
                                <small>Venta neta histórica</small>
                                <strong id="cpVenta">Gs 0</strong>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-2">
                            <div class="perfil-kpi">
                                <small>Visitas / tickets</small>
                                <strong id="cpVisitas">0 / 0</strong>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-2">
                            <div class="perfil-kpi">
                                <small>Ticket promedio</small>
                                <strong id="cpTicketPromedio">Gs 0</strong>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-2">
                            <div class="perfil-kpi">
                                <small>Unidades netas</small>
                                <strong id="cpUnidades">0</strong>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-1">
                        <div class="col-md-3 mb-2">
                            <div class="perfil-meta">
                                <small>Primera compra</small>
                                <strong id="cpPrimera">-</strong>
                            </div>
                        </div>

                        <div class="col-md-3 mb-2">
                            <div class="perfil-meta">
                                <small>Última compra</small>
                                <strong id="cpUltima">-</strong>
                            </div>
                        </div>

                        <div class="col-md-3 mb-2">
                            <div class="perfil-meta">
                                <small>Frecuencia promedio</small>
                                <strong id="cpFrecuencia">-</strong>
                            </div>
                        </div>

                        <div class="col-md-3 mb-2">
                            <div class="perfil-meta">
                                <small>Descuento acumulado</small>
                                <strong id="cpDescuento">Gs 0</strong>
                            </div>
                        </div>
                    </div>

                    <div class="small text-muted mt-1" id="cpMetaGeneral"></div>
                </div>

                <ul class="nav nav-tabs px-3 pt-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active"
                           data-toggle="tab"
                           href="#cpFacturas"
                           role="tab">
                            Facturas / comprobantes
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link"
                           data-toggle="tab"
                           href="#cpProductos"
                           role="tab">
                            Productos
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link"
                           data-toggle="tab"
                           href="#cpLocales"
                           role="tab">
                            Locales y vendedores
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link"
                           data-toggle="tab"
                           href="#cpEvolucion"
                           role="tab">
                            Evolución mensual
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active"
                         id="cpFacturas"
                         role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0 ventas-table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Comprobante</th>
                                        <th>Tipo</th>
                                        <th>Local</th>
                                        <th>Vendedor</th>
                                        <th class="text-right">Productos</th>
                                        <th class="text-right">Unid.</th>
                                        <th class="text-right">DTO</th>
                                        <th class="text-right">Venta neta</th>
                                    </tr>
                                </thead>
                                <tbody id="cpFacturasBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade"
                         id="cpProductos"
                         role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0 ventas-table">
                                <thead>
                                    <tr>
                                        <th>Código / artículo</th>
                                        <th>Grupo</th>
                                        <th>Temporada</th>
                                        <th class="text-right">Unid.</th>
                                        <th class="text-right">Tickets</th>
                                        <th class="text-right">Venta neta</th>
                                        <th>Última compra</th>
                                    </tr>
                                </thead>
                                <tbody id="cpProductosBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade"
                         id="cpLocales"
                         role="tabpanel">
                        <div class="row no-gutters">
                            <div class="col-xl-6 border-right">
                                <div class="px-3 py-2 border-bottom font-weight-bold">
                                    Locales donde compra
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0 ventas-table">
                                        <thead>
                                            <tr>
                                                <th>Local</th>
                                                <th class="text-right">Visitas</th>
                                                <th class="text-right">Tickets</th>
                                                <th class="text-right">Venta neta</th>
                                                <th>Última</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cpLocalesBody"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="col-xl-6">
                                <div class="px-3 py-2 border-bottom font-weight-bold">
                                    Vendedores que lo atendieron
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0 ventas-table">
                                        <thead>
                                            <tr>
                                                <th>Local</th>
                                                <th>Vendedor</th>
                                                <th class="text-right">Tickets</th>
                                                <th class="text-right">Venta neta</th>
                                                <th>Última</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cpVendedoresBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade"
                         id="cpEvolucion"
                         role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0 ventas-table">
                                <thead>
                                    <tr>
                                        <th>Mes</th>
                                        <th class="text-right">Tickets</th>
                                        <th class="text-right">Unidades</th>
                                        <th class="text-right">Venta neta</th>
                                    </tr>
                                </thead>
                                <tbody id="cpMesesBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('page_styles')
<style>
.cliente-box,
.cliente-mini,
.cliente-highlight,
.perfil-kpi,
.perfil-meta{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:10px;
    padding:13px 14px;
    height:100%;
}
.cliente-box{
    border-left-width:4px;
    box-shadow:0 .125rem .25rem rgba(0,0,0,.04);
    margin-bottom:10px;
}
.cliente-box-primary{border-left-color:#007bff}
.cliente-box-info{border-left-color:#17a2b8}
.cliente-box-success{border-left-color:#28a745}
.cliente-box-danger{border-left-color:#dc3545}
.cliente-box small,
.cliente-mini small,
.cliente-highlight small,
.perfil-kpi small,
.perfil-meta small{
    display:block;
    color:#64748b;
    text-transform:uppercase;
    font-size:.67rem;
    font-weight:800;
}
.cliente-box strong{
    display:block;
    margin-top:3px;
    font-size:1.45rem;
}
.cliente-box span,
.cliente-highlight span{
    display:block;
    margin-top:3px;
    color:#64748b;
    font-size:.74rem;
}
.cliente-mini{
    margin-bottom:8px;
}
.cliente-mini strong,
.perfil-kpi strong,
.perfil-meta strong{
    display:block;
    margin-top:3px;
    font-size:1rem;
}
.cliente-highlight{
    border-left-width:4px;
}
.cliente-highlight-info{border-left-color:#17a2b8}
.cliente-highlight-success{border-left-color:#28a745}
.cliente-highlight strong{
    display:block;
    margin-top:4px;
    font-size:1.05rem;
}
.cliente-segmento{
    display:inline-block;
    border-radius:999px;
    padding:.28rem .52rem;
    font-size:.65rem;
    font-weight:800;
    white-space:nowrap;
}
.cliente-segmento-vip{background:#fef3c7;color:#92400e}
.cliente-segmento-frecuente{background:#dcfce7;color:#166534}
.cliente-segmento-recurrente{background:#e0f2fe;color:#075985}
.cliente-segmento-recuperar{background:#fee2e2;color:#991b1b}
.cliente-segmento-ocasional{background:#f1f5f9;color:#475569}
.clientes-main-table td{
    vertical-align:middle;
}
.ventas-table th{
    white-space:nowrap;
    font-size:.72rem;
    text-transform:uppercase;
    color:#64748b;
}
.ventas-table td{
    font-size:.78rem;
}
.perfil-kpi,
.perfil-meta{
    min-height:70px;
}
#clientePerfilModal .modal-xl{
    max-width:96vw;
}
#clientePerfilModal .tab-content{
    min-height:320px;
}
</style>
@endpush

@push('page_scripts')
<script>
(function () {
    const detalleUrl = @json(
        route('ventas.clientes.detalle')
    );

    const nf = new Intl.NumberFormat('es-PY');

    function num(valor) {
        return nf.format(Number(valor || 0));
    }

    function gs(valor) {
        return 'Gs ' + nf.format(
            Math.round(Number(valor || 0))
        );
    }

    function esc(valor) {
        return String(valor ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function vaciarPerfil() {
        [
            'cpFacturasBody',
            'cpProductosBody',
            'cpLocalesBody',
            'cpVendedoresBody',
            'cpMesesBody'
        ].forEach(function (id) {
            document.getElementById(id).innerHTML = '';
        });

        document.getElementById(
            'clientePerfilLoading'
        ).classList.remove('d-none');

        document.getElementById(
            'clientePerfilContenido'
        ).classList.add('d-none');

        document.getElementById(
            'clientePerfilError'
        ).classList.add('d-none');
    }

    function abrirPerfil(clienteKey, clienteNombre) {
        vaciarPerfil();

        document.getElementById(
            'clientePerfilModalLabel'
        ).textContent = clienteNombre || 'Perfil del cliente';

        document.getElementById(
            'clientePerfilSubtitulo'
        ).textContent = 'Cargando historial comercial...';

        $('#clientePerfilModal').modal('show');

        const params = new URLSearchParams();
        params.set('cliente_key', clienteKey);

        fetch(
            detalleUrl + '?' + params.toString(),
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
                            || 'No se pudo cargar el cliente.'
                    );
                }

                return data;
            })
            .then(function (data) {
                const r = data.resumen || {};

                document.getElementById(
                    'clientePerfilModalLabel'
                ).textContent = r.cliente || clienteNombre || 'Cliente';

                document.getElementById(
                    'clientePerfilSubtitulo'
                ).textContent = r.cli_cod
                    ? 'Código de cliente ' + r.cli_cod
                    : 'Cliente identificado por nombre';

                document.getElementById('cpVenta').textContent =
                    gs(r.venta_neta);

                document.getElementById('cpVisitas').textContent =
                    num(r.visitas) + ' / ' + num(r.tickets);

                document.getElementById('cpTicketPromedio').textContent =
                    gs(r.ticket_promedio);

                document.getElementById('cpUnidades').textContent =
                    num(r.unidades_netas);

                document.getElementById('cpPrimera').textContent =
                    r.primera_compra || '-';

                document.getElementById('cpUltima').textContent =
                    r.ultima_compra || '-';

                document.getElementById('cpFrecuencia').textContent =
                    r.dias_promedio_entre_visitas === null
                        ? '-'
                        : num(r.dias_promedio_entre_visitas)
                            + ' días';

                document.getElementById('cpDescuento').textContent =
                    gs(r.descuento);

                document.getElementById('cpMetaGeneral').innerHTML =
                    '<strong>Venta a lista:</strong> '
                    + gs(r.venta_lista)
                    + ' &nbsp;·&nbsp; <strong>DTO:</strong> '
                    + num(r.descuento_porcentaje)
                    + '% &nbsp;·&nbsp; <strong>Devoluciones:</strong> '
                    + gs(r.devoluciones)
                    + ' &nbsp;·&nbsp; <strong>Productos:</strong> '
                    + num(r.productos)
                    + ' &nbsp;·&nbsp; <strong>Locales:</strong> '
                    + num(r.locales)
                    + ' &nbsp;·&nbsp; <strong>Vendedores:</strong> '
                    + num(r.vendedores);

                const comprobantes = Array.isArray(data.comprobantes)
                    ? data.comprobantes
                    : [];

                document.getElementById('cpFacturasBody').innerHTML =
                    comprobantes.length
                        ? comprobantes.map(function (item) {
                            const tipoClase =
                                item.tipo === 'NCR'
                                    ? 'badge-danger'
                                    : 'badge-light border';

                            const ventaClase =
                                Number(item.venta_neta) < 0
                                    ? 'text-danger'
                                    : 'text-success';

                            return ''
                                + '<tr>'
                                + '<td>' + esc(item.fecha) + '</td>'
                                + '<td><strong>'
                                    + esc(item.comprobante)
                                    + '</strong></td>'
                                + '<td><span class="badge '
                                    + tipoClase + '">'
                                    + esc(item.tipo || '-')
                                    + '</span></td>'
                                + '<td>' + esc(item.local || '-') + '</td>'
                                + '<td>' + esc(item.vendedor || '-') + '</td>'
                                + '<td class="text-right">'
                                    + num(item.productos) + '</td>'
                                + '<td class="text-right">'
                                    + num(item.unidades) + '</td>'
                                + '<td class="text-right">'
                                    + gs(item.descuento) + '</td>'
                                + '<td class="text-right font-weight-bold '
                                    + ventaClase + '">'
                                    + gs(item.venta_neta) + '</td>'
                                + '</tr>';
                        }).join('')
                        : '<tr><td colspan="9" class="text-center text-muted py-4">Sin comprobantes.</td></tr>';

                const productos = Array.isArray(data.productos)
                    ? data.productos
                    : [];

                document.getElementById('cpProductosBody').innerHTML =
                    productos.length
                        ? productos.map(function (item) {
                            return ''
                                + '<tr>'
                                + '<td><strong>' + esc(item.codigo)
                                    + '</strong><small class="d-block text-muted">'
                                    + esc(item.descripcion || '-')
                                    + '</small></td>'
                                + '<td>' + esc(item.grupo || '-') + '</td>'
                                + '<td>' + esc(item.temporada || '-') + '</td>'
                                + '<td class="text-right">'
                                    + num(item.unidades) + '</td>'
                                + '<td class="text-right">'
                                    + num(item.tickets) + '</td>'
                                + '<td class="text-right font-weight-bold text-success">'
                                    + gs(item.venta_neta) + '</td>'
                                + '<td>' + esc(item.ultima_compra || '-') + '</td>'
                                + '</tr>';
                        }).join('')
                        : '<tr><td colspan="7" class="text-center text-muted py-4">Sin productos.</td></tr>';

                const locales = Array.isArray(data.locales)
                    ? data.locales
                    : [];

                document.getElementById('cpLocalesBody').innerHTML =
                    locales.length
                        ? locales.map(function (item) {
                            return ''
                                + '<tr>'
                                + '<td><strong>' + esc(item.local) + '</strong></td>'
                                + '<td class="text-right">' + num(item.visitas) + '</td>'
                                + '<td class="text-right">' + num(item.tickets) + '</td>'
                                + '<td class="text-right font-weight-bold text-success">'
                                    + gs(item.venta_neta) + '</td>'
                                + '<td>' + esc(item.ultima_compra || '-') + '</td>'
                                + '</tr>';
                        }).join('')
                        : '<tr><td colspan="5" class="text-center text-muted py-4">Sin locales.</td></tr>';

                const vendedores = Array.isArray(data.vendedores)
                    ? data.vendedores
                    : [];

                document.getElementById('cpVendedoresBody').innerHTML =
                    vendedores.length
                        ? vendedores.map(function (item) {
                            return ''
                                + '<tr>'
                                + '<td>' + esc(item.local || '-') + '</td>'
                                + '<td><strong>' + esc(item.vendedor || '-') + '</strong></td>'
                                + '<td class="text-right">' + num(item.tickets) + '</td>'
                                + '<td class="text-right font-weight-bold text-success">'
                                    + gs(item.venta_neta) + '</td>'
                                + '<td>' + esc(item.ultima_venta || '-') + '</td>'
                                + '</tr>';
                        }).join('')
                        : '<tr><td colspan="5" class="text-center text-muted py-4">Sin vendedores.</td></tr>';

                const meses = Array.isArray(data.por_mes)
                    ? data.por_mes
                    : [];

                document.getElementById('cpMesesBody').innerHTML =
                    meses.length
                        ? meses.map(function (item) {
                            return ''
                                + '<tr>'
                                + '<td><strong>' + esc(item.periodo) + '</strong></td>'
                                + '<td class="text-right">' + num(item.tickets) + '</td>'
                                + '<td class="text-right">' + num(item.unidades) + '</td>'
                                + '<td class="text-right font-weight-bold text-success">'
                                    + gs(item.venta_neta) + '</td>'
                                + '</tr>';
                        }).join('')
                        : '<tr><td colspan="4" class="text-center text-muted py-4">Sin evolución mensual.</td></tr>';

                document.getElementById(
                    'clientePerfilLoading'
                ).classList.add('d-none');

                document.getElementById(
                    'clientePerfilContenido'
                ).classList.remove('d-none');
            })
            .catch(function (error) {
                document.getElementById(
                    'clientePerfilLoading'
                ).classList.add('d-none');

                const caja = document.getElementById(
                    'clientePerfilError'
                );

                caja.textContent =
                    error.message
                    || 'No se pudo cargar el perfil.';

                caja.classList.remove('d-none');
            });
    }

    document.addEventListener('click', function (event) {
        const button = event.target.closest(
            '.btn-cliente-perfil'
        );

        if (!button) {
            return;
        }

        abrirPerfil(
            button.dataset.clienteKey,
            button.dataset.cliente
        );
    });
})();
</script>
@endpush
