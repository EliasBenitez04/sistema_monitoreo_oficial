@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col">
                <h1><i class="fas fa-file-import mr-2"></i>Importación de Remisiones</h1>
                <p class="text-muted mb-1">Un único archivo actualiza OT/Logística y Redistribución.</p>
                <div class="small">
                    <i class="far fa-clock mr-1 text-primary"></i>
                    <strong>Última vez actualizado:</strong>
                    @if($ultimaActualizacion)
                        {{ \Carbon\Carbon::parse($ultimaActualizacion)->format('d/m/Y H:i:s') }}
                    @else
                        <span class="text-muted">Aún no hay importaciones registradas</span>
                    @endif
                </div>
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
            <form id="form-importar-remisiones" method="POST" action="{{ route('remisiones.importar') }}" enctype="multipart/form-data">
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
                <div class="card-footer">
                    <div id="estado-importacion" class="alert alert-light border mb-3 d-none">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div>
                                <i class="fas fa-spinner fa-spin text-primary mr-2"></i>
                                <strong>Importando remisiones...</strong>
                                <div class="text-muted small mt-1">Procesando OT/Logística y Redistribución. No cierre esta pantalla.</div>
                            </div>
                            <div class="text-right mt-2 mt-md-0">
                                <div class="text-muted small">Tiempo transcurrido</div>
                                <div id="contador-importacion" class="h4 mb-0 font-weight-bold">00:00</div>
                            </div>
                        </div>
                    </div>

                    <div class="text-right">
                        <button id="btn-importar-remisiones" type="submit" class="btn btn-primary">
                            <i class="fas fa-upload mr-1"></i> Importar y actualizar todo
                        </button>
                    </div>
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

(function () {
    var form = document.getElementById('form-importar-remisiones');
    var boton = document.getElementById('btn-importar-remisiones');
    var estado = document.getElementById('estado-importacion');
    var contador = document.getElementById('contador-importacion');
    var intervalo = null;

    form.addEventListener('submit', function () {
        var inicio = Date.now();

        boton.disabled = true;
        boton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Procesando...';
        estado.classList.remove('d-none');

        function actualizarContador() {
            var total = Math.floor((Date.now() - inicio) / 1000);
            var horas = Math.floor(total / 3600);
            var minutos = Math.floor((total % 3600) / 60);
            var segundos = total % 60;

            var texto = String(minutos).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');

            if (horas > 0) {
                texto = String(horas).padStart(2, '0') + ':' + texto;
            }

            contador.textContent = texto;
        }

        actualizarContador();
        intervalo = setInterval(actualizarContador, 1000);
    });
})();
</script>
@endpush
