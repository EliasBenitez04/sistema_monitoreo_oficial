@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1><i class="fas fa-route mr-2"></i>Seguimiento de Pedidos</h1>
                <p class="text-muted mb-0">Seguimiento desde Terminación hasta la recepción confirmada por los locales.</p>
            </div>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-file-excel mr-2 text-success"></i>Importar OT por pedido</h3></div>
        <form method="POST" action="{{ route('seguimiento-pedidos.importar') }}" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <div class="alert alert-info mb-3">
                    El Excel debe contener <strong>NRO OT</strong> y <strong>NRO PEDIDO</strong> (también se aceptan OT y PEDIDO). Ejemplo: 30703 / T1.
                    Solo se guarda la relación Pedido–OT; la trazabilidad se obtiene de los datos reales del sistema.
                </div>
                <div class="custom-file">
                    <input type="file" name="archivo" class="custom-file-input" id="archivo-pedidos" accept=".xlsx,.xls,.csv" required>
                    <label class="custom-file-label" for="archivo-pedidos">Seleccionar archivo...</label>
                </div>
            </div>
            <div class="card-footer text-right">
                <button class="btn btn-primary"><i class="fas fa-upload mr-1"></i> Importar pedidos</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <form class="form-inline float-right">
                <input class="form-control form-control-sm mr-2" name="buscar" value="{{ $buscar }}" placeholder="Buscar T1, T2...">
                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
            </form>
            <h3 class="card-title"><i class="fas fa-list-alt mr-2"></i>Pedidos registrados</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Pedido</th><th>OT asociadas</th><th>Registrado</th><th class="text-right">Acción</th></tr></thead>
                <tbody>
                @forelse($pedidos as $pedido)
                    <tr>
                        <td><strong>{{ $pedido->nro_pedido }}</strong></td>
                        <td><span class="badge badge-primary">{{ $pedido->detalles_count }} OT</span></td>
                        <td>{{ optional($pedido->created_at)->format('d/m/Y H:i') }}</td>
                        <td class="text-right"><a href="{{ route('seguimiento-pedidos.show', $pedido->id) }}" class="btn btn-sm btn-primary"><i class="fas fa-route mr-1"></i> Ver seguimiento</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">Todavía no hay pedidos importados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $pedidos->links() }}</div>
    </div>
</div>
</section>
@endsection

@push('page_scripts')
<script>
document.getElementById('archivo-pedidos').addEventListener('change', function () {
    this.nextElementSibling.textContent = this.files.length ? this.files[0].name : 'Seleccionar archivo...';
});
</script>
@endpush
