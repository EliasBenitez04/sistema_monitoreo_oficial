@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1><i class="fas fa-file-import mr-2"></i>Importar Maestro de Códigos</h1>
                <p class="text-muted mb-0">
                    Importación masiva optimizada para archivos grandes.
                </p>
            </div>
            <a href="{{ route('maestro-codigos.index') }}"
               class="btn btn-outline-secondary mt-2 mt-md-0">
                <i class="fas fa-list mr-1"></i> Listado de Códigos
            </a>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-xl-9 col-lg-10">

            <div class="card card-outline card-success shadow-sm">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-upload mr-1"></i> Archivo de artículos
                    </h3>
                </div>

                <form id="maestro-import-form"
                      action="{{ route('maestro-codigos.importar') }}"
                      method="POST"
                      enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="import_token" id="import_token">

                    <div class="card-body">
                        <div class="alert alert-light border">
                            <div class="row">
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <strong><i class="fas fa-bolt text-warning mr-1"></i>Modo rápido</strong>
                                    <div class="small text-muted">
                                        XLSX se procesa en streaming, sin cargar toda la planilla en memoria.
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <strong><i class="fas fa-database text-primary mr-1"></i>UPSERT</strong>
                                    <div class="small text-muted">
                                        Si el código ya existe se actualiza; si no existe se inserta.
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <strong><i class="fas fa-broom text-success mr-1"></i>Limpieza</strong>
                                    <div class="small text-muted">
                                        Se eliminan automáticamente ', ’, ‘ y backticks de los datos.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="font-weight-bold">
                                Seleccionar archivo
                            </label>
                            <div class="custom-file">
                                <input type="file"
                                       class="custom-file-input"
                                       id="archivo"
                                       name="archivo"
                                       accept=".xlsx,.csv,.txt"
                                       required>
                                <label class="custom-file-label" for="archivo">
                                    Seleccione XLSX o CSV...
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Recomendado: XLSX. Tamaño máximo de la aplicación: 200 MB.
                                Para más de 140.000 filas no use XLS antiguo.
                            </small>
                        </div>

                        <div class="card bg-light border-0 mb-0">
                            <div class="card-body py-3">
                                <strong class="d-block mb-2">
                                    Columnas reconocidas
                                </strong>
                                <div class="columnas-esperadas">
                                    Cod Proveedor · Proveedor · Cod Articulo · Cod.Articulo ·
                                    Cod.Base · Cod. Imagen · Color · Talle · Artiuclo ·
                                    GrupoPrecio · Familia · Marca · Motivo · Linea · Grupo ·
                                    GRUPO PLAN · GENERICO · TEJIDO · TEMPORADA · ESTADO ·
                                    Fecha Creacion · Temporada · Año · Tipo Stock ·
                                    PrecioVenta · CostoUnitario · TEMP · COMPLEJIDAD · TIPO CODIGO
                                </div>
                                <small class="text-muted d-block mt-2">
                                    Las dos columnas TEMPORADA/Temporada y TEMP se guardan por separado.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('maestro-codigos.index') }}"
                           class="btn btn-light border">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="btn btn-success px-4"
                                id="btn-importar">
                            <i class="fas fa-file-import mr-1"></i> Importar Datos
                        </button>
                    </div>
                </form>
            </div>

            <div id="progreso-card"
                 class="card card-outline card-primary shadow-sm d-none">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-spinner fa-spin mr-1"></i>
                        Importación en proceso
                    </h3>
                    <span class="float-right badge badge-primary"
                          id="progreso-estado">PREPARANDO</span>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <strong id="progreso-mensaje">Preparando archivo...</strong>
                        <strong id="progreso-pct">0%</strong>
                    </div>

                    <div class="progress progress-lg mb-3">
                        <div id="progreso-barra"
                             class="progress-bar progress-bar-striped progress-bar-animated"
                             role="progressbar"
                             style="width:0%">0%</div>
                    </div>

                    <div class="row text-center">
                        <div class="col-md-3 col-6 mb-2">
                            <div class="import-stat">
                                <small>Procesadas</small>
                                <strong id="stat-procesadas">0</strong>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="import-stat">
                                <small>Total estimado</small>
                                <strong id="stat-total">-</strong>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="import-stat">
                                <small>Guardadas</small>
                                <strong id="stat-guardadas">0</strong>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="import-stat">
                                <small>Omitidas</small>
                                <strong id="stat-omitidas">0</strong>
                            </div>
                        </div>
                    </div>

                    <div class="small text-muted text-center mt-2">
                        No cierre esta pestaña mientras el archivo se está procesando.
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
</section>
@endsection

@push('page_css')
<style>
.columnas-esperadas{
    line-height:1.7;
    color:#475569;
    font-size:.82rem;
}
.progress-lg{height:24px;border-radius:12px}
.import-stat{
    border:1px solid #e2e8f0;
    background:#fff;
    border-radius:10px;
    padding:10px;
}
.import-stat small{
    display:block;
    text-transform:uppercase;
    color:#64748b;
    font-size:.66rem;
    font-weight:700;
}
.import-stat strong{
    display:block;
    margin-top:3px;
    font-size:1.15rem;
}
</style>
@endpush

@push('page_scripts')
<script>
(function () {
    const form = document.getElementById('maestro-import-form');
    const archivo = document.getElementById('archivo');
    const tokenInput = document.getElementById('import_token');
    const btn = document.getElementById('btn-importar');
    const progresoCard = document.getElementById('progreso-card');
    const barra = document.getElementById('progreso-barra');
    const pct = document.getElementById('progreso-pct');
    const mensaje = document.getElementById('progreso-mensaje');
    const estado = document.getElementById('progreso-estado');

    const statProcesadas = document.getElementById('stat-procesadas');
    const statTotal = document.getElementById('stat-total');
    const statGuardadas = document.getElementById('stat-guardadas');
    const statOmitidas = document.getElementById('stat-omitidas');

    let polling = null;
    let tokenActual = null;
    let procesamientoIniciado = false;

    archivo.addEventListener('change', function () {
        const nombre = this.files && this.files[0]
            ? this.files[0].name
            : 'Seleccione XLSX o CSV...';

        const label = this.nextElementSibling;

        if (label) {
            label.textContent = nombre;
        }
    });

    function formatoNumero(valor) {
        const numero = Number(valor || 0);

        return new Intl.NumberFormat('es-PY').format(numero);
    }

    function generarToken() {
        if (window.crypto && window.crypto.getRandomValues) {
            const data = new Uint32Array(4);
            window.crypto.getRandomValues(data);

            return 'cod_' + Array.from(data)
                .map(v => v.toString(16))
                .join('');
        }

        return 'cod_' + Date.now() + '_' + Math.random().toString(36).slice(2);
    }

    function ponerBarra(valor, texto) {
        const numero = Math.max(0, Math.min(100, Number(valor || 0)));

        barra.style.width = numero + '%';
        barra.textContent = texto || (numero.toFixed(1) + '%');
        pct.textContent = texto || (numero.toFixed(1) + '%');
    }

    function aplicarProgreso(data) {
        if (!data) {
            return;
        }

        procesamientoIniciado = ['PREPARANDO', 'PROCESANDO', 'COMPLETADO']
            .includes(data.estado);

        estado.textContent = data.estado || 'PROCESANDO';
        mensaje.textContent = data.mensaje || 'Procesando...';

        statProcesadas.textContent = formatoNumero(data.procesadas);
        statGuardadas.textContent = formatoNumero(data.guardadas);
        statOmitidas.textContent = formatoNumero(data.omitidas);
        statTotal.textContent = data.total
            ? formatoNumero(data.total)
            : '-';

        if (data.porcentaje !== null && data.porcentaje !== undefined) {
            ponerBarra(data.porcentaje);
        }

        if (data.estado === 'ERROR') {
            clearInterval(polling);
            btn.disabled = false;
            barra.classList.remove('progress-bar-animated');
            barra.classList.add('bg-danger');
        }

        if (data.estado === 'COMPLETADO') {
            ponerBarra(100, '100%');
            barra.classList.remove('progress-bar-animated');
            barra.classList.add('bg-success');
        }
    }

    function consultarProgreso() {
        if (!tokenActual) {
            return;
        }

        const plantilla = @json(
            route('maestro-codigos.progreso', ['token' => '__TOKEN__'])
        );

        const url = plantilla.replace('__TOKEN__', tokenActual);

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            cache: 'no-store'
        })
            .then(response => response.json())
            .then(aplicarProgreso)
            .catch(() => {});
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        if (!archivo.files || !archivo.files[0]) {
            Swal.fire('Atención', 'Seleccione un archivo para importar.', 'warning');
            return;
        }

        tokenActual = generarToken();
        tokenInput.value = tokenActual;

        btn.disabled = true;
        progresoCard.classList.remove('d-none');

        estado.textContent = 'SUBIENDO';
        mensaje.textContent = 'Subiendo archivo al servidor...';
        ponerBarra(0);

        const xhr = new XMLHttpRequest();
        const data = new FormData(form);

        xhr.open('POST', form.action, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader(
            'X-CSRF-TOKEN',
            document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        );

        xhr.upload.addEventListener('progress', function (e) {
            if (!e.lengthComputable || procesamientoIniciado) {
                return;
            }

            const subida = Math.round((e.loaded / e.total) * 100);

            estado.textContent = 'SUBIENDO';
            mensaje.textContent = 'Subiendo archivo...';
            ponerBarra(subida, subida + '% subida');
        });

        xhr.upload.addEventListener('load', function () {
            estado.textContent = 'PREPARANDO';
            mensaje.textContent = 'Archivo recibido. Preparando importación...';
            ponerBarra(0, 'Procesando');
        });

        polling = setInterval(consultarProgreso, 700);
        consultarProgreso();

        xhr.onload = function () {
            clearInterval(polling);
            consultarProgreso();

            let respuesta = {};

            try {
                respuesta = JSON.parse(xhr.responseText || '{}');
            } catch (e) {
                respuesta = {};
            }

            if (xhr.status >= 200 && xhr.status < 300 && respuesta.success) {
                ponerBarra(100, '100%');
                estado.textContent = 'COMPLETADO';
                mensaje.textContent = respuesta.message || 'Importación completada.';
                barra.classList.remove('progress-bar-animated');
                barra.classList.add('bg-success');

                const resumen = respuesta.resumen || {};

                statProcesadas.textContent = formatoNumero(resumen.procesadas);
                statGuardadas.textContent = formatoNumero(resumen.guardadas);
                statOmitidas.textContent = formatoNumero(resumen.omitidas);
                statTotal.textContent = resumen.total
                    ? formatoNumero(resumen.total)
                    : statTotal.textContent;

                Swal.fire({
                    icon: 'success',
                    title: 'Importación completada',
                    html:
                        '<strong>' + formatoNumero(resumen.guardadas) + '</strong> registros procesados correctamente.' +
                        (Number(resumen.omitidas || 0) > 0
                            ? '<br><span class="text-muted">Omitidas: ' + formatoNumero(resumen.omitidas) + '</span>'
                            : ''),
                    confirmButtonText: 'Ver listado'
                }).then(function () {
                    window.location.href = respuesta.redirect;
                });

                return;
            }

            btn.disabled = false;
            estado.textContent = 'ERROR';
            mensaje.textContent = respuesta.message || 'No se pudo completar la importación.';
            barra.classList.remove('progress-bar-animated');
            barra.classList.add('bg-danger');

            Swal.fire(
                'Error',
                respuesta.message || 'No se pudo completar la importación.',
                'error'
            );
        };

        xhr.onerror = function () {
            clearInterval(polling);
            btn.disabled = false;
            estado.textContent = 'ERROR';
            mensaje.textContent = 'Se perdió la conexión durante la importación.';
            barra.classList.remove('progress-bar-animated');
            barra.classList.add('bg-danger');

            Swal.fire(
                'Error de conexión',
                'No se pudo comunicar con el servidor.',
                'error'
            );
        };

        xhr.send(data);
    });
})();
</script>
@endpush
