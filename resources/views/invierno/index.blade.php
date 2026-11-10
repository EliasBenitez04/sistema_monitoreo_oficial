@extends('layouts.app')
@include('invierno._estilos')

@section('content')
@php($n = fn ($v) => number_format((int) $v, 0, ',', '.'))
<section class="content invierno">
    <div class="container-fluid">
        <header class="iw-hero iw-split">
            <div>
                <div class="iw-eyebrow">CONTROL DE DEVOLUCIONES Y REDISTRIBUCIÓN</div>
                <h1><i class="fas fa-snowflake mr-2"></i>Prendas de invierno</h1>
                <p>Seguí cada remisión desde el local de origen hasta Central y sus envíos posteriores a los locales.</p>
            </div>
            <a href="{{ route('invierno.exportar', request()->only('local', 'estado', 'buscar')) }}" class="btn btn-light"><i class="fas fa-file-excel mr-1"></i> Exportar Excel</a>
        </header>

        @include('invierno._alertas')

        <div class="row">
            <div class="col-6 col-lg-2"><div class="card iw-metric iw-primary"><span>Remisiones de invierno</span><strong>{{ $n($resumen['documentos']) }}</strong><small>{{ $n($resumen['sin_remision']) }} sin remisión importada</small></div></div>
            <div class="col-6 col-lg-2"><div class="card iw-metric iw-primary"><span>Prendas documentadas</span><strong>{{ $n($resumen['prendas']) }}</strong><small>En remisiones a Central</small></div></div>
            <div class="col-6 col-lg-2"><div class="card iw-metric iw-green"><span>Confirmado en Central</span><strong>{{ $n($resumen['central']) }}</strong><small>{{ $n($resumen['pendiente']) }} pendientes de recepción</small></div></div>
            <div class="col-6 col-lg-2"><div class="card iw-metric iw-primary"><span>Envíos asociados</span><strong>{{ $n($resumen['remitido']) }}</strong><small>{{ $n($resumen['recibido']) }} recibidas en locales</small></div></div>
            <div class="col-6 col-lg-2"><div class="card iw-metric iw-green"><span>Saldo documental Central</span><strong>{{ $n($resumen['saldo']) }}</strong><small>Documentos sin incidencias</small></div></div>
            <div class="col-6 col-lg-2"><div class="card iw-metric iw-orange"><span>Requieren revisión</span><strong>{{ $n($resumen['revisar']) }}</strong><small>Documentos con inconsistencias</small></div></div>
        </div>

        @can('ot importar')
        <div class="card mb-3">
            <div class="card-body">
                <details {{ $documentos->total() === 0 ? 'open' : '' }}>
                    <summary><i class="fas fa-file-import mr-2 text-primary"></i>Registrar remisiones de invierno</summary>
                    <div class="row mt-3">
                        <div class="col-lg-7 border-right">
                            <p class="iw-note">Importá un Excel o CSV con las columnas <strong>LOCAL, SERIE y NRO</strong>. Una remisión ya registrada no se duplica.</p>
                            <form action="{{ route('invierno.importar') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <label for="iw-archivo">Archivo de remisiones</label>
                                <input id="iw-archivo" type="file" name="archivo" accept=".xlsx,.xls,.csv" class="form-control-file mb-3" required>
                                <button class="btn btn-primary" type="submit"><i class="fas fa-upload mr-1"></i> Importar listado</button>
                                <a href="{{ route('invierno.plantilla') }}" class="btn btn-outline-secondary ml-1">Descargar plantilla</a>
                                <small class="d-block text-muted mt-2">Hasta 10 MB y 20.000 documentos por archivo. El detalle de prendas se toma de la importación general de remisiones.</small>
                            </form>
                        </div>
                        <div class="col-lg-5 mt-3 mt-lg-0">
                            <strong class="d-block mb-2">Agregar una remisión</strong>
                            <form action="{{ route('invierno.registrar') }}" method="POST">
                                @csrf
                                <div class="form-group"><label for="iw-local-nuevo">Local de origen</label><select id="iw-local-nuevo" class="form-control" name="local" required><option value="">Seleccionar local…</option>@foreach($locales as $codigo => $nombre)<option value="{{ $codigo }}" {{ old('local') == $codigo ? 'selected' : '' }}>{{ $codigo }} · {{ $nombre }}</option>@endforeach</select></div>
                                <div class="form-row">
                                    <div class="form-group col-4"><label for="iw-serie">Serie</label><input id="iw-serie" name="serie" maxlength="20" value="{{ old('serie') }}" class="form-control" placeholder="F / J1" required></div>
                                    <div class="form-group col-8"><label for="iw-nro">Número</label><input id="iw-nro" name="nro" maxlength="30" pattern="[0-9]+" inputmode="numeric" value="{{ old('nro') }}" class="form-control" placeholder="1188" required></div>
                                </div>
                                <button class="btn btn-outline-primary" type="submit">Registrar remisión</button>
                            </form>
                        </div>
                    </div>
                </details>
            </div>
        </div>
        @endcan

        <div class="card">
            <div class="card-header iw-split"><h2 class="card-title mb-0">Seguimiento por remisión</h2><span class="iw-note">{{ $n($documentos->total()) }} documentos según filtros</span></div>
            <div class="card-body pb-2">
                <form method="GET" action="{{ route('invierno.index') }}" class="form-row align-items-end">
                    <div class="form-group col-md-3"><label for="iw-local">Local de origen</label><select id="iw-local" name="local" class="form-control"><option value="">Todos los locales</option>@foreach($locales as $codigo => $nombre)<option value="{{ $codigo }}" {{ request('local') == $codigo ? 'selected' : '' }}>{{ $nombre }}</option>@endforeach</select></div>
                    <div class="form-group col-md-4"><label for="iw-estado">Estado del seguimiento</label><select id="iw-estado" name="estado" class="form-control"><option value="">Todos los estados</option>@foreach($estados as $clave => $nombre)<option value="{{ $clave }}" {{ request('estado') === $clave ? 'selected' : '' }}>{{ $nombre }}</option>@endforeach</select></div>
                    <div class="form-group col-md-3"><label for="iw-buscar">Buscar remisión</label><input id="iw-buscar" name="buscar" class="form-control" maxlength="100" value="{{ request('buscar') }}" placeholder="Local, serie o número"></div>
                    <div class="form-group col-md-2 d-flex"><button class="btn btn-primary flex-fill mr-1" type="submit">Filtrar</button><a class="btn btn-outline-secondary" href="{{ route('invierno.index') }}" aria-label="Limpiar filtros"><i class="fas fa-undo"></i></a></div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Origen / remisión</th><th>Estado</th><th class="num">Prendas</th><th class="num">Confirmado Central</th><th class="num">Pendiente Central</th><th class="num">Enviado a locales</th><th class="num">Recibido locales</th><th class="num">Saldo Central</th><th></th></tr></thead>
                    <tbody>
                    @forelse($documentos as $documento)
                        <tr>
                            <td><strong>{{ $documento->local }}</strong><div class="iw-code">{{ $documento->serie }} {{ $documento->numero_remision }}</div><small class="text-muted">Destino: CASA CENTRAL · 01</small></td>
                            <td>@include('invierno._estado')@if($documento->incidencias)<small class="d-block text-danger mt-1">{{ count($documento->incidencias) }} incidencias</small>@endif</td>
                            <td class="num">{{ $documento->entradas->isEmpty() ? '—' : $n($documento->total) }}</td>
                            <td class="num">{{ $documento->entradas->isEmpty() ? '—' : $n($documento->confirmado_central) }}</td>
                            <td class="num">{{ $documento->entradas->isEmpty() ? '—' : $n($documento->pendiente_central) }}</td>
                            <td class="num">{{ $n($documento->remitido) }}</td>
                            <td class="num">{{ $n($documento->recibido_local) }}</td>
                            <td class="num font-weight-bold">{{ $documento->saldo_central === null || $documento->entradas->isEmpty() ? '—' : $n($documento->saldo_central) }}</td>
                            <td><a class="btn btn-sm btn-outline-primary text-nowrap" href="{{ route('invierno.show', $documento->id) }}">Ver detalle <i class="fas fa-arrow-right ml-1"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="iw-empty"><i class="fas fa-snowflake"></i><strong>No hay remisiones para estos filtros.</strong>Importá el listado de invierno o ajustá los filtros para empezar el seguimiento.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($documentos->hasPages())<div class="card-footer bg-white">{{ $documentos->links('pagination::bootstrap-4') }}</div>@endif
        </div>
        <p class="iw-note mt-3"><i class="fas fa-info-circle mr-1"></i>Las recepciones y fechas reflejan la última importación general. Las salidas se cuentan después de asociarlas por código completo y cantidad. El saldo documental no sustituye un conteo físico. Los documentos sin remisión importada todavía no aportan cantidades.</p>
    </div>
</section>
@endsection
