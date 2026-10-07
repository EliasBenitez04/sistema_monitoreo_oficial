@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1><i class="fas fa-barcode mr-2"></i>Listado de Códigos</h1>
                <p class="text-muted mb-0">
                    Maestro consolidado de artículos, variantes, temporada, costo y precio.
                </p>
            </div>
            <a href="{{ route('maestro-codigos.importar.form') }}"
               class="btn btn-success shadow-sm mt-2 mt-md-0">
                <i class="fas fa-file-import mr-1"></i> Importar Datos
            </a>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-white border-left border-primary shadow-sm">
                <div class="inner">
                    <h3>{{ number_format($resumen->total, 0, ',', '.') }}</h3>
                    <p>Códigos / variantes</p>
                </div>
                <div class="icon"><i class="fas fa-barcode text-primary"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-white border-left border-info shadow-sm">
                <div class="inner">
                    <h3>{{ number_format($resumen->bases, 0, ',', '.') }}</h3>
                    <p>Códigos base</p>
                </div>
                <div class="icon"><i class="fas fa-layer-group text-info"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-white border-left border-success shadow-sm">
                <div class="inner">
                    <h3>{{ number_format($resumen->imagenes, 0, ',', '.') }}</h3>
                    <p>Códigos de imagen</p>
                </div>
                <div class="icon"><i class="fas fa-image text-success"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-white border-left border-warning shadow-sm">
                <div class="inner">
                    <h3>{{ number_format($resumen->temporadas, 0, ',', '.') }}</h3>
                    <p>Temporadas distintas</p>
                </div>
                <div class="icon"><i class="fas fa-calendar-alt text-warning"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary shadow-sm mb-3">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-filter mr-1"></i> Filtros
            </h3>
        </div>
        <div class="card-body pb-2">
            <form method="GET" action="{{ route('maestro-codigos.index') }}">
                <div class="row align-items-end">
                    <div class="col-lg-4 col-md-6 mb-2">
                        <label class="small font-weight-bold">Buscar</label>
                        <input type="text"
                               name="buscar"
                               value="{{ $buscar }}"
                               class="form-control"
                               placeholder="Código, base, imagen, artículo, grupo...">
                    </div>

                    <div class="col-lg-2 col-md-3 mb-2">
                        <label class="small font-weight-bold">Temporada</label>
                        <select name="temporada" class="form-control select2">
                            <option value="">Todas</option>
                            @foreach($temporadas as $item)
                                <option value="{{ $item }}" {{ $temporada === $item ? 'selected' : '' }}>
                                    {{ $item }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-3 mb-2">
                        <label class="small font-weight-bold">Línea</label>
                        <select name="linea" class="form-control select2">
                            <option value="">Todas</option>
                            @foreach($lineas as $item)
                                <option value="{{ $item }}" {{ $linea === $item ? 'selected' : '' }}>
                                    {{ $item }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-3 mb-2">
                        <label class="small font-weight-bold">Año</label>
                        <select name="anio" class="form-control">
                            <option value="">Todos</option>
                            @foreach($anios as $item)
                                <option value="{{ $item }}" {{ (string) $anio === (string) $item ? 'selected' : '' }}>
                                    {{ $item }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-3 mb-2">
                        <label class="small font-weight-bold">Tipo código</label>
                        <select name="tipo_codigo" class="form-control">
                            <option value="">Todos</option>
                            @foreach($tiposCodigo as $item)
                                <option value="{{ $item }}" {{ $tipoCodigo === $item ? 'selected' : '' }}>
                                    {{ $item }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-1">
                    <a href="{{ route('maestro-codigos.index') }}"
                       class="btn btn-light border mr-2">
                        <i class="fas fa-eraser mr-1"></i> Limpiar
                    </a>
                    <button class="btn btn-primary">
                        <i class="fas fa-search mr-1"></i> Consultar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="card-title float-none mb-0">
                    <i class="fas fa-list mr-1"></i> Maestro de códigos
                </h3>
                <small class="text-muted">
                    {{ number_format($codigos->total(), 0, ',', '.') }} registros encontrados
                </small>
            </div>
            <span class="badge badge-light border px-2 py-2">
                Página {{ $codigos->currentPage() }} de {{ max(1, $codigos->lastPage()) }}
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 maestro-table">
                <thead>
                    <tr>
                        <th>Código artículo</th>
                        <th>Base</th>
                        <th>Img.</th>
                        <th>Color</th>
                        <th>Talle</th>
                        <th>Artículo</th>
                        <th>Línea</th>
                        <th>Grupo</th>
                        <th>Grupo Plan</th>
                        <th>Temporada</th>
                        <th>Temp.</th>
                        <th>Año</th>
                        <th class="text-right">Precio</th>
                        <th class="text-right">Costo</th>
                        <th>Complejidad</th>
                        <th>Tipo código</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($codigos as $codigo)
                        <tr>
                            <td>
                                <strong>{{ $codigo->cod_articulo }}</strong>
                                @if($codigo->cod_articulo_origen && $codigo->cod_articulo_origen !== $codigo->cod_articulo)
                                    <small class="d-block text-muted">
                                        Origen: {{ $codigo->cod_articulo_origen }}
                                    </small>
                                @endif
                            </td>
                            <td>{{ $codigo->cod_base ?: '-' }}</td>
                            <td>{{ $codigo->cod_imagen ?: '-' }}</td>
                            <td>{{ $codigo->color ?: '-' }}</td>
                            <td>{{ $codigo->talle ?: '-' }}</td>
                            <td>
                                <strong>{{ $codigo->articulo ?: '-' }}</strong>
                                @if($codigo->motivo)
                                    <small class="d-block text-muted">{{ $codigo->motivo }}</small>
                                @endif
                            </td>
                            <td>{{ $codigo->linea ?: '-' }}</td>
                            <td>{{ $codigo->grupo ?: '-' }}</td>
                            <td>{{ $codigo->grupo_plan ?: '-' }}</td>
                            <td>
                                @if($codigo->temporada)
                                    <span class="badge badge-info">{{ $codigo->temporada }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $codigo->temporada_codigo ?: '-' }}</td>
                            <td>{{ $codigo->anio ?: '-' }}</td>
                            <td class="text-right text-nowrap">
                                {{ $codigo->precio_venta !== null ? 'Gs ' . number_format($codigo->precio_venta, 0, ',', '.') : '-' }}
                            </td>
                            <td class="text-right text-nowrap">
                                {{ $codigo->costo_unitario !== null ? 'Gs ' . number_format($codigo->costo_unitario, 0, ',', '.') : '-' }}
                            </td>
                            <td>{{ $codigo->complejidad ?: '-' }}</td>
                            <td>{{ $codigo->tipo_codigo ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="16" class="text-center text-muted py-5">
                                <i class="fas fa-search mr-1"></i>
                                No hay códigos con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($codigos->hasPages())
            <div class="card-footer">
                {{ $codigos->links() }}
            </div>
        @endif
    </div>
</div>
</section>
@endsection

@push('page_css')
<style>
.maestro-table th{
    background:#f8fafc;
    color:#64748b;
    font-size:.68rem;
    text-transform:uppercase;
    letter-spacing:.025em;
    white-space:nowrap;
    vertical-align:middle!important;
}
.maestro-table td{
    font-size:.79rem;
    vertical-align:middle!important;
    white-space:nowrap;
}
.maestro-table td:nth-child(6),
.maestro-table td:nth-child(8),
.maestro-table td:nth-child(9){
    white-space:normal;
    min-width:180px;
}
.border-left{border-left-width:4px!important}
.small-box.bg-white .icon{top:8px;font-size:44px;opacity:.14}
</style>
@endpush
