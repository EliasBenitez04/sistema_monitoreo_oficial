@extends('layouts.app')

@push('page_css')
    <link rel="stylesheet" href="{{ asset('css/maria-pirelli.css') }}">
@endpush

@section('content')
@php
    $corte = $stockActual['corte'];
    $unidades = static fn ($valor) => number_format((int) $valor, 0, ',', '.');
    $guaranies = static function ($valor) {
        $valor = round((float) $valor, 2);
        return 'Gs ' . number_format($valor, $valor === floor($valor) ? 0 : 2, ',', '.');
    };
@endphp
<section class="content mp-panel">
    <div class="container-fluid mp-shell">
        <header class="mp-hero">
            <div class="mp-hero-cabecera">
                <div>
                    <h1>Stock actual</h1>
                    <p>María Pirelli</p>
                </div>
                @if($corte)
                    <span class="mp-periodo">
                        <i class="far fa-calendar-alt" aria-hidden="true"></i>
                        Inventario del {{ \Carbon\Carbon::parse($corte->fecha_corte)->format('d/m/Y') }}
                    </span>
                @endif
            </div>
            <nav class="mp-acciones" aria-label="Navegación del inventario">
                <a href="{{ route('maria-pirelli.index') }}#stock-actual">Volver a María Pirelli</a>
                @role('ADMIN')
                    @if($stockActual['configurado'])
                        <a href="#importar-stock">Importar stock</a>
                    @endif
                @endrole
            </nav>
        </header>

        @if(session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        @if(session('error') || $errors->any())
            <div class="alert alert-danger" role="alert">
                @if(session('error')) {{ session('error') }} @endif
                @if($errors->any())
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        @if(!$stockActual['configurado'])
            <div class="alert alert-warning">Falta ejecutar la migración de stock de María Pirelli.</div>
        @elseif(!$corte)
            <div class="alert alert-light border">Todavía no hay un inventario cargado.</div>
        @else
            @include('maria_pirelli.stock_indicadores')

            <section class="card mp-card" id="stock-sucursales" aria-labelledby="mp-sucursales-titulo">
                <header class="card-header mp-card-header">
                    <h2 id="mp-sucursales-titulo">Stock por sucursal</h2>
                    <p>{{ $unidades($stockActual['sucursales']->count()) }} sucursales. Elegí una para ver sus artículos.</p>
                </header>
                <div class="table-responsive mp-tabla-scroll" tabindex="0" role="region" aria-labelledby="mp-sucursales-titulo">
                    <table class="table mp-tabla mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Sucursal</th>
                                <th scope="col" class="text-right">Códigos</th>
                                <th scope="col" class="text-right">Stock neto</th>
                                <th scope="col" class="text-right">Costo del stock</th>
                                <th scope="col" class="text-right">Valor de venta potencial</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stockActual['sucursales'] as $local)
                                <tr @class(['mp-fila-activa' => $sucursal && $sucursal->sucursal_clave === $local->sucursal_clave])>
                                    <th scope="row">
                                        <a class="mp-enlace" href="{{ route('maria-pirelli.stock', ['sucursal' => $local->sucursal_clave]) }}#stock-sucursal">
                                            {{ $local->sucursal }}
                                        </a>
                                    </th>
                                    <td class="text-right">{{ $unidades($local->codigos) }}</td>
                                    <td class="text-right">
                                        <strong>{{ $unidades($local->unidades) }}</strong>
                                        @if($local->negativas > 0)
                                            <small class="d-block text-danger text-nowrap">{{ $unidades($local->negativas) }} negativas</small>
                                        @endif
                                    </td>
                                    <td class="text-right text-nowrap">{{ $guaranies($local->costo) }}</td>
                                    <td class="text-right text-nowrap">
                                        <strong>{{ $guaranies($local->venta_potencial) }}</strong>
                                        @if($local->negativas > 0)
                                            <small class="d-block text-muted">Neto {{ $guaranies($local->valor_neto) }}</small>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            @if($sucursal)
                <section class="card mp-card" id="stock-sucursal" aria-labelledby="mp-sucursal-titulo">
                    <header class="card-header mp-card-header mp-card-acciones">
                        <div>
                            <h2 id="mp-sucursal-titulo">{{ $sucursal->sucursal }}</h2>
                            <p>{{ $unidades($sucursal->unidades) }} unidades netas · {{ $unidades($sucursal->codigos) }} códigos</p>
                        </div>
                        <a href="{{ route('maria-pirelli.stock') }}#stock-sucursales" class="btn btn-outline-secondary btn-sm">Volver a sucursales</a>
                    </header>
                    <form class="card-body mp-filtros py-3" action="{{ route('maria-pirelli.stock') }}#stock-sucursal" method="get">
                        <input type="hidden" name="sucursal" value="{{ $sucursal->sucursal_clave }}">
                        <label for="buscar-stock">Código o descripción</label>
                        <div class="d-flex flex-wrap align-items-center mp-stock-acciones">
                            <input class="form-control form-control-sm mp-stock-buscar" type="search" id="buscar-stock" name="buscar" value="{{ $buscar }}" maxlength="100">
                            <button class="btn btn-primary btn-sm" type="submit">Buscar</button>
                            @if($buscar !== '')
                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('maria-pirelli.stock', ['sucursal' => $sucursal->sucursal_clave]) }}#stock-sucursal">Limpiar</a>
                            @endif
                        </div>
                    </form>
                    <div class="table-responsive mp-tabla-scroll" tabindex="0" role="region" aria-labelledby="mp-sucursal-titulo">
                        <table class="table mp-tabla mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Código</th>
                                    <th scope="col">Descripción</th>
                                    <th scope="col" class="text-right">Stock</th>
                                    <th scope="col" class="text-right">Costo unitario</th>
                                    <th scope="col" class="text-right">Precio unitario</th>
                                    <th scope="col" class="text-right">Costo del stock</th>
                                    <th scope="col" class="text-right">Valor de venta neto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($articulos as $articulo)
                                    <tr>
                                        <th scope="row" class="mp-codigo text-nowrap">{{ $articulo->codigo }}</th>
                                        <td>{{ $articulo->descripcion }}</td>
                                        <td @class(['text-right', 'font-weight-bold', 'text-danger' => $articulo->cantidad < 0])>{{ $unidades($articulo->cantidad) }}</td>
                                        <td class="text-right text-nowrap">{{ $guaranies($articulo->costo_unitario) }}</td>
                                        <td class="text-right text-nowrap">{{ $guaranies($articulo->precio_venta) }}</td>
                                        <td class="text-right text-nowrap">{{ $guaranies($articulo->costo) }}</td>
                                        <td class="text-right text-nowrap">{{ $guaranies($articulo->valor_neto) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="mp-vacio">No encontramos artículos con esa búsqueda.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($articulos->hasPages())
                        <footer class="card-footer">{{ $articulos->links('pagination::bootstrap-4') }}</footer>
                    @endif
                </section>
            @endif
        @endif

        @role('ADMIN')
            @if($stockActual['configurado'])
                <section class="card mp-card" id="importar-stock" aria-labelledby="mp-importar-stock-titulo">
                    <header class="card-header mp-card-header">
                        <h2 id="mp-importar-stock-titulo">Importar stock</h2>
                        <p>Subí el inventario completo de las sucursales. Una nueva carga del mismo día reemplaza ese corte.</p>
                    </header>
                    <form action="{{ route('maria-pirelli.stock.importar') }}" method="post" enctype="multipart/form-data" class="card-body mp-filtros">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="archivo-stock">Archivo Excel (.xlsx)</label>
                                <input type="file" class="form-control-file" id="archivo-stock" name="archivo" accept=".xlsx" aria-describedby="ayuda-archivo-stock" required>
                                <small class="form-text text-muted" id="ayuda-archivo-stock">Hasta 10 MB. Solo se importan códigos del catálogo de María Pirelli.</small>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="fecha-corte">Fecha del stock</label>
                                <input type="date" class="form-control mp-stock-buscar" id="fecha-corte" name="fecha_corte" value="{{ old('fecha_corte') }}" aria-describedby="ayuda-fecha-stock">
                                <small class="form-text text-muted" id="ayuda-fecha-stock">Completala si el Excel no trae el año en FECHA STOCK o en el nombre (DDMMAAAA).</small>
                            </div>
                        </div>
                        <p class="small text-muted mb-3">Encabezados requeridos en la primera hoja<br><code>SUCURSAL · CODIGO · STOCK ACTUAL · COSTO UNITARIO · PVTA UNI</code></p>
                        <button class="btn btn-primary" type="submit">Importar stock</button>
                    </form>
                </section>
            @endif
        @endrole
    </div>
</section>
@endsection
