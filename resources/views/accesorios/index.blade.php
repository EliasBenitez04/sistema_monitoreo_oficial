@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1><i class="fas fa-socks mr-2"></i>Accesorios · Artículos</h1>
            <p class="text-muted">Seguimiento de compras, remisiones, recepciones y ventas por código.</p>
        </div>
        <a href="{{ route('accesorios.index') }}" class="btn btn-outline-primary mr-2"><i class="fas fa-chart-line mr-1"></i>Panel de monitoreo</a>
        <a href="{{ route('accesorios.importar.form') }}" class="btn btn-primary"><i class="fas fa-file-import mr-1"></i>Importar datos</a>
    </div>
</section>
<section class="content"><div class="container-fluid">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="row">
        <div class="col-md-4"><div class="small-box bg-info"><div class="inner"><h3>{{ number_format($totalArticulos, 0, ',', '.') }}</h3><p>Códigos de accesorios</p></div><div class="icon"><i class="fas fa-tags"></i></div></div></div>
        <div class="col-md-4"><div class="small-box bg-primary"><div class="inner"><h3>{{ number_format($resumen->compras, 0, ',', '.') }}</h3><p>Registros de compra / FCO</p></div><div class="icon"><i class="fas fa-file-invoice"></i></div></div></div>
        <div class="col-md-4"><div class="small-box bg-success"><div class="inner"><h3>{{ number_format($resumen->unidades, 0, ',', '.') }}</h3><p>Unidades compradas registradas</p></div><div class="icon"><i class="fas fa-box"></i></div></div></div>
    </div>
    <div class="card shadow-sm">
        <div class="card-header">
            <form method="GET" action="{{ route('accesorios.listado') }}" class="form-inline">
                <input class="form-control mr-2 mb-2" name="buscar" value="{{ $buscar }}" placeholder="Código, descripción o línea" style="min-width:270px">
                <button class="btn btn-outline-primary mb-2" type="submit"><i class="fas fa-search"></i> Buscar</button>
                <a href="{{ route('accesorios.listado') }}" class="btn btn-light mb-2 ml-2">Limpiar</a>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped table-sm mb-0">
                <thead class="thead-light"><tr>
                    <th>Código</th><th>Descripción / Línea</th>
                    <th class="text-right">Compradas</th><th class="text-right">Remitidas*</th>
                    <th class="text-right">Recibidas*</th><th class="text-right">Ventas netas</th><th class="text-right">Stock calculado</th><th></th>
                </tr></thead><tbody>
                @forelse($articulos as $articulo)
                    <tr>
                        <td class="font-weight-bold">{{ $articulo->codigo }}</td>
                        <td>{{ $articulo->descripcion }}<div class="text-muted small">{{ $articulo->linea }}</div></td>
                        <td class="text-right">{{ number_format($articulo->comprado, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($articulo->remitido, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($articulo->recibido, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($articulo->vendido, 0, ',', '.') }}</td>
                        <td class="text-right">
                            @if($articulo->stock_calculado === null)
                                <span class="text-muted">Sin movimientos</span>
                            @else
                                <span class="badge badge-{{ $articulo->stock_calculado < 0 ? 'danger' : ($articulo->stock_calculado === 0 ? 'secondary' : 'success') }}" style="font-size:12px">{{ number_format($articulo->stock_calculado, 0, ',', '.') }}</span>
                            @endif
                        </td>
                        <td class="text-right"><a href="{{ route('accesorios.show', $articulo->id) }}" class="btn btn-outline-primary btn-sm">Ver seguimiento</a></td>
                    </tr>
                @empty <tr><td colspan="8" class="text-center text-muted p-4">Sin accesorios cargados.</td></tr> @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $articulos->links() }}</div>
    </div>
    <p class="small text-muted">* Remitidas y recibidas son movimientos documentales: pueden incluir varias transferencias de las mismas prendas. El <strong>stock calculado</strong> sí descuenta salidas y ventas por sucursal e incorpora únicamente recepciones confirmadas; corte al día de hoy. Los saldos negativos se mantienen para revisión. No es un inventario físico ni una conciliación por FCO.</p>
</div></section>
@endsection
