@extends('layouts.app')

@push('page_css')
    <link rel="stylesheet" href="{{ asset('css/maria-pirelli.css') }}">
@endpush

@section('content')
@php
    $fecha = static fn (?string $valor) => $valor ? \Carbon\Carbon::parse($valor)->format('d/m/Y') : '—';
@endphp
<section class="content mp-panel">
    <div class="container-fluid mp-shell">
        <header class="mp-hero">
            <div class="mp-hero-cabecera">
                <div>
                    <h1>MARIA PIRELLI</h1>
                    <p>Envíos de Casa Central a locales y ventas netas por mes.</p>
                </div>
                @if($configurado && $hayCatalogo)
                    <span class="mp-periodo">
                        <i class="far fa-calendar-alt" aria-hidden="true"></i>
                        {{ $fecha($desde) }} al {{ $fecha($hasta) }}
                    </span>
                @endif
            </div>
            @if($configurado && $hayCatalogo)
                <nav class="mp-acciones" aria-label="Secciones de María Pirelli">
                    <a href="#resumen-mensual">Resumen mensual</a>
                    <a href="#remisiones">Remisiones</a>
                    <a href="#articulos">Artículos</a>
                </nav>
            @endif
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

        @if(!$configurado)
            <div class="alert alert-warning">Falta ejecutar la migración de MARIA PIRELLI.</div>
        @else
            @role('ADMIN')
                <details class="card mp-card mp-import" @if(!$hayCatalogo || $errors->has('archivo') || session('error')) open @endif>
                    <summary class="card-header font-weight-bold">Importar catálogo</summary>
                    <form action="{{ route('accesorios.maria-pirelli.importar') }}" method="post" enctype="multipart/form-data" class="card-body">
                        @csrf
                        <div class="form-group">
                            <label for="archivo">Archivo Excel o CSV</label>
                            <input type="file" class="form-control-file" id="archivo" name="archivo"
                                accept=".xlsx,.xls,.csv,.txt" aria-describedby="ayuda-archivo" required>
                            <small id="ayuda-archivo" class="form-text text-muted">
                                Hasta 10 MB. Guardá como texto los códigos que empiezan con cero.
                            </small>
                        </div>
                        <p class="mb-1">Encabezados en la primera hoja</p>
                        <p class="mb-2"><code>CODIGO · DESCRIPCION · COSTO · PREC. VENT. · FECHA ENVIO · LINEA</code></p>
                        <p class="text-muted small">Usá DD/MM/AAAA para las fechas. Volver a cargar un código actualiza su ficha.</p>
                        <button type="submit" class="btn btn-primary">Importar catálogo</button>
                    </form>
                </details>
            @endrole

            @if(!$hayCatalogo)
                <div class="alert alert-light border">Todavía no hay artículos en el catálogo de MARIA PIRELLI.</div>
            @else
                @php
                    $unidades = static fn ($valor) => number_format((int) $valor, 0, ',', '.');
                    $guaranies = static function ($valor) {
                        $valor = round((float) $valor, 2);
                        return 'Gs ' . number_format($valor, $valor === floor($valor) ? 0 : 2, ',', '.');
                    };
                    $indicadores = [
                        ['Unidades enviadas a locales', $conRemisiones ? $unidades($totales['enviadas']) : '—', 'fa-truck', 'envios'],
                        ['Unidades netas vendidas', $conVentas ? $unidades($totales['vendidas']) : '—', 'fa-shopping-bag', 'ventas'],
                        ['Venta neta', $conVentas ? $guaranies($totales['venta_neta']) : '—', 'fa-coins', 'importe'],
                    ];
                @endphp

                @if(!$conRemisiones || !$conVentas)
                    <div class="alert alert-warning">
                        @if(!$conRemisiones) La fuente de remisiones todavía no está disponible. @endif
                        @if(!$conVentas) La fuente de ventas todavía no está disponible. @endif
                    </div>
                @endif

                <form action="{{ route('accesorios.maria-pirelli.index') }}" method="get" class="card mp-card mp-filtros">
                    <div class="card-body pb-2">
                        <div class="form-row align-items-end">
                            <div class="form-group col-sm-6 col-lg-2">
                                <label for="desde">Desde</label>
                                <input type="date" id="desde" name="desde" value="{{ $desde }}" class="form-control">
                            </div>
                            <div class="form-group col-sm-6 col-lg-2">
                                <label for="hasta">Hasta</label>
                                <input type="date" id="hasta" name="hasta" value="{{ $hasta }}" class="form-control">
                            </div>
                            <div class="form-group col-sm-6 col-lg-3">
                                <label for="sucursal">Sucursal</label>
                                <select id="sucursal" name="sucursal" class="form-control">
                                    <option value="">Todas</option>
                                    @foreach($sucursales as $opcion)
                                        <option value="{{ $opcion }}" @selected($opcion === $sucursal)>{{ $opcion }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-sm-6 col-lg-2">
                                <label for="linea">Línea</label>
                                <select id="linea" name="linea" class="form-control">
                                    <option value="">Todas</option>
                                    @foreach($lineas as $opcion)
                                        <option value="{{ $opcion }}" @selected($opcion === $linea)>{{ $opcion }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-lg-3">
                                <label for="buscar">Código o descripción</label>
                                <input type="search" id="buscar" name="buscar" value="{{ $buscar }}" class="form-control" maxlength="100">
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                            <a href="{{ route('accesorios.maria-pirelli.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                        </div>
                    </div>
                </form>

                <div class="mp-indicadores">
                    @foreach($indicadores as [$titulo, $valor, $icono, $tipo])
                        <div class="mp-indicador mp-indicador-{{ $tipo }}">
                            <span class="mp-indicador-icono"><i class="fas {{ $icono }}" aria-hidden="true"></i></span>
                            <div>
                                <span>{{ $titulo }}</span>
                                <strong>{{ $valor }}</strong>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="card mp-card" id="resumen-mensual">
                    <div class="card-header mp-card-header">
                        <h2>Resumen mensual</h2>
                        <p>Elegí un mes para ver sus remisiones. Las ventas netas descuentan las notas de crédito.</p>
                    </div>
                    <div class="table-responsive mp-tabla-scroll" tabindex="0" role="region" aria-label="Resumen mensual">
                        <table class="table table-hover mp-tabla mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Mes</th>
                                    <th scope="col" class="text-right">Enviadas</th>
                                    <th scope="col" class="text-right">Vendidas netas</th>
                                    <th scope="col" class="text-right">Venta neta</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($meses as $mes => $resumen)
                                    @php
                                        $inicioMes = \Carbon\Carbon::parse($mes . '-01')->locale('es');
                                        $filtroMes = [
                                            'desde' => max($desde, $inicioMes->toDateString()),
                                            'hasta' => min($hasta, $inicioMes->copy()->endOfMonth()->toDateString()),
                                            'sucursal' => $sucursal, 'linea' => $linea, 'buscar' => $buscar,
                                        ];
                                    @endphp
                                    <tr @class(['mp-fila-activa' => substr($desde, 0, 7) === $mes && substr($hasta, 0, 7) === $mes])>
                                        <th scope="row">
                                            <a class="mp-enlace" href="{{ route('accesorios.maria-pirelli.index', $filtroMes) }}#remisiones">
                                                {{ ucfirst($inicioMes->isoFormat('MMMM YYYY')) }}
                                                <i class="fas fa-angle-right ml-1" aria-hidden="true"></i>
                                            </a>
                                        </th>
                                        <td class="text-right">{{ $conRemisiones ? $unidades($resumen['enviadas']) : '—' }}</td>
                                        <td class="text-right">{{ $conVentas ? $unidades($resumen['vendidas']) : '—' }}</td>
                                        <td class="text-right text-nowrap">{{ $conVentas ? $guaranies($resumen['venta_neta']) : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">Sin movimientos registrados para estos filtros.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @include('accesorios.maria_pirelli_remisiones')

                <div class="card mp-card" id="articulos">
                    <div class="card-header mp-card-header mp-card-acciones">
                        <div>
                            <h2>Detalle por artículo</h2>
                            @role('ADMIN')
                                <p class="small text-muted mb-0">El Excel incluye los códigos con envíos o ventas del período.</p>
                            @endrole
                        </div>
                        @role('ADMIN')
                            <a class="btn btn-outline-success btn-sm my-1"
                                href="{{ route('accesorios.maria-pirelli.exportar', compact('desde', 'hasta', 'sucursal', 'linea', 'buscar')) }}">
                                Exportar Excel
                            </a>
                        @endrole
                    </div>
                    <div class="table-responsive mp-tabla-scroll" tabindex="0" role="region" aria-label="Detalle por artículo">
                        <table class="table table-hover mp-tabla mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Código</th>
                                    <th scope="col">Descripción</th>
                                    <th scope="col">Línea</th>
                                    <th scope="col" class="text-nowrap">Último envío (remisión)</th>
                                    <th scope="col" class="text-right">Costo</th>
                                    <th scope="col" class="text-right text-nowrap">Precio de venta</th>
                                    <th scope="col" class="text-right">Enviadas</th>
                                    <th scope="col" class="text-right text-nowrap">Vendidas netas</th>
                                    <th scope="col" class="text-right text-nowrap">Venta neta</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($articulos as $articulo)
                                    @php($movimiento = $porArticulo[$articulo->id] ?? [])
                                    @php($ultimoEnvio = $movimiento['ultimo_envio'] ?? null)
                                    <tr>
                                        <th scope="row" class="mp-codigo text-nowrap">{{ $articulo->codigo }}</th>
                                        <td>{{ $articulo->descripcion }}</td>
                                        <td>{{ $articulo->linea ?? '—' }}</td>
                                        <td class="text-nowrap">{{ $fecha($ultimoEnvio) }}</td>
                                        <td class="text-right text-nowrap">{{ $guaranies($articulo->costo_unitario) }}</td>
                                        <td class="text-right text-nowrap">{{ $guaranies($articulo->precio_venta) }}</td>
                                        <td class="text-right">{{ $conRemisiones ? $unidades($movimiento['enviadas'] ?? 0) : '—' }}</td>
                                        <td class="text-right">{{ $conVentas ? $unidades($movimiento['vendidas'] ?? 0) : '—' }}</td>
                                        <td class="text-right text-nowrap">{{ $conVentas ? $guaranies($movimiento['venta_neta'] ?? 0) : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center text-muted py-3">No se encontraron artículos con esos filtros.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($articulos->hasPages())
                        <div class="card-footer">{{ $articulos->fragment('articulos')->links('pagination::bootstrap-4') }}</div>
                    @endif
                </div>
            @endif
        @endif
    </div>
</section>
@endsection
