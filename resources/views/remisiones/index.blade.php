@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col">
                <h1><i class="fas fa-file-import mr-2"></i>Importación de Remisiones</h1>
                <p class="text-muted mb-0">Un único archivo actualiza OT/Logística y Redistribución.</p>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-excel text-success mr-2"></i>Archivo ENVIOS / Remisiones</h3>
            </div>
            <form method="POST" action="{{ route('remisiones.importar') }}" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="alert alert-info">
                        Esta es la única importación de remisiones del sistema. El archivo se procesa una sola vez y alimenta automáticamente los datos de OT/Logística y los movimientos de Redistribución que tengan coincidencia.
                    </div>

                    <div class="form-group">
                        <label for="archivo_envios">Seleccionar archivo</label>
                        <div class="custom-file">
                            <input type="file" name="archivo_envios" id="archivo_envios"
                                class="custom-file-input @error('archivo_envios') is-invalid @enderror"
                                accept=".xlsx,.xls,.csv" required>
                            <label class="custom-file-label" for="archivo_envios">Seleccionar XLSX, XLS o CSV...</label>
                        </div>
                        @error('archivo_envios')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <strong><i class="fas fa-truck mr-2 text-primary"></i>OT / Logística</strong>
                                <div class="text-muted small mt-2">Guarda y actualiza remisiones, fechas, cantidades y vínculos con OT, trazabilidad y detalle logístico.</div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <strong><i class="fas fa-random mr-2 text-warning"></i>Redistribución</strong>
                                <div class="text-muted small mt-2">Asocia las mismas remisiones a los movimientos de redistribución y recalcula sus estados.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload mr-1"></i> Importar y actualizar todo
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

@push('page_scripts')
<script>
document.getElementById('archivo_envios').addEventListener('change', function () {
    var nombre = this.files.length ? this.files[0].name : 'Seleccionar XLSX, XLS o CSV...';
    this.nextElementSibling.textContent = nombre;
});
</script>
@endpush
