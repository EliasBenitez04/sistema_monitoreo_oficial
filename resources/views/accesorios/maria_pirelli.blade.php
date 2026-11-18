@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1>MARIA PIRELLI</h1>
        <p class="text-muted mb-0">Envíos de Casa Central a locales y ventas netas por mes.</p>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
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
                <details class="card shadow-sm" @if(!$hayCatalogo || $errors->has('archivo') || session('error')) open @endif>
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
                @endphp

                @if(!$conRemisiones || !$conVentas)
                    <div class="alert alert-warning">
                        @if(!$conRemisiones) La fuente de remisiones todavía no está disponible. @endif
                        @if(!$conVentas) La fuente de ventas todavía no está disponible. @endif
                    </div>
                @endif

                <form action="{{ route('accesorios.maria-pirelli.index') }}" method="get" class="card shadow-sm">
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

                <div class="row">
                    <div class="col-md-4">
                        <div class="card card-outline card-primary shadow-sm">
                            <div class="card-body py-3">
                                <div class="text-muted">Unidades enviadas a locales</div>
                                <strong class="h3 d-block mb-0">{{ $conRemisiones ? $unidades($totales['enviadas']) : '—' }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-outline card-success shadow-sm">
                            <div class="card-body py-3">
                                <div class="text-muted">Unidades netas vendidas</div>
                                <strong class="h3 d-block mb-0">{{ $conVentas ? $unidades($totales['vendidas']) : '—' }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-outline card-success shadow-sm">
                            <div class="card-body py-3">
                                <div class="text-muted">Venta neta</div>
                                <strong class="h3 d-block mb-0">{{ $conVentas ? $guaranies($totales['venta_neta']) : '—' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header">
                        <h2 class="h5 mb-1">Resumen mensual</h2>
                        <p class="small text-muted mb-0">
                            Los envíos se agrupan por fecha de remisión. Las ventas descuentan las notas de crédito. Elegí un mes para ver sus artículos.
                        </p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm mb-0">
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
                                    <tr>
                                        <th scope="row"><a href="{{ route('accesorios.maria-pirelli.index', $filtroMes) }}#articulos">{{ ucfirst($inicioMes->isoFormat('MMMM YYYY')) }}</a></th>
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

                <div class="card shadow-sm" id="articulos">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Detalle por artículo</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm mb-0">
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
                                        <th scope="row" class="text-nowrap">{{ $articulo->codigo }}</th>
                                        <td>{{ $articulo->descripcion }}</td>
                                        <td>{{ $articulo->linea ?? '—' }}</td>
                                        <td class="text-nowrap">{{ $ultimoEnvio ? \Carbon\Carbon::parse($ultimoEnvio)->format('d/m/Y') : '—' }}</td>
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
