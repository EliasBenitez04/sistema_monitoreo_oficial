@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1><i class="fas fa-file-import mr-2"></i>Importar ventas</h1>
                <p class="text-muted mb-0">
                    Carga diaria de ventas, devoluciones y comprobantes.
                </p>
            </div>
            <a href="{{ route('ventas.index') }}"
               class="btn btn-outline-secondary mt-2 mt-md-0">
                <i class="fas fa-chart-line mr-1"></i> Ver ventas
            </a>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-8">
            <div class="card card-outline card-success shadow-sm">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-upload mr-1"></i> Archivo de ventas
                    </h3>
                </div>

                <form id="ventas-import-form"
                      action="{{ route('ventas.importar') }}"
                      method="POST"
                      enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="import_token" id="import_token">

                    <div class="card-body">
                        <div class="alert alert-light border">
                            <div class="row">
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <strong>
                                        <i class="fas fa-copy text-primary mr-1"></i>
                                        Sin duplicados
                                    </strong>
                                    <div class="small text-muted">
                                        Reimportar el mismo archivo no duplica las líneas ya cargadas.
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <strong>
                                        <i class="fas fa-undo text-danger mr-1"></i>
                                        Devoluciones
                                    </strong>
                                    <div class="small text-muted">
                                        Cantidades negativas/NCR se conservan y restan de la venta neta.
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <strong>
                                        <i class="fas fa-link text-success mr-1"></i>
                                        Maestro
                                    </strong>
                                    <div class="small text-muted">
                                        Los códigos se enriquecen con grupo, temporada y línea cuando existen en maestro_codigos.
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
                                XLSX recomendado. Máximo 200 MB.
                            </small>
                        </div>

                        <div class="card bg-light border-0 mb-0">
                            <div class="card-body py-3">
                                <strong class="d-block mb-2">Columnas esperadas</strong>
                                <div class="columnas-esperadas">
                                    LOCAL · CODIGO · DESCRIPCION · CLI COD · CLIENTE ·
                                    VENDEDOR · PLISTA · DTO · PVTA · FECHA · CANTIDAD · COMPROBANTE
                                </div>
                                <small class="text-muted d-block mt-2">
                                    La estructura corresponde al archivo VTA que revisamos.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('ventas.index') }}"
                           class="btn btn-light border">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="btn btn-success px-4"
                                id="btn-importar">
                            <i class="fas fa-file-import mr-1"></i> Importar ventas
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
                          id="progreso-estado">
                        PREPARANDO
                    </span>
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
                             style="width:0%">
                            0%
                        </div>
                    </div>

                    <div class="row text-center">
                        <div class="col-lg-2 col-4 mb-2">
                            <div class="import-stat">
                                <small>Procesadas</small>
                                <strong id="stat-procesadas">0</strong>
                            </div>
                        </div>
                        <div class="col-lg-2 col-4 mb-2">
                            <div class="import-stat">
                                <small>Total</small>
                                <strong id="stat-total">-</strong>
                            </div>
                        </div>
                        <div class="col-lg-2 col-4 mb-2">
                            <div class="import-stat">
                                <small>Nuevas</small>
                                <strong class="text-success" id="stat-insertadas">0</strong>
                            </div>
                        </div>
                        <div class="col-lg-2 col-4 mb-2">
                            <div class="import-stat">
                                <small>Duplicadas</small>
                                <strong class="text-warning" id="stat-duplicadas">0</strong>
                            </div>
                        </div>
                        <div class="col-lg-2 col-4 mb-2">
                            <div class="import-stat">
                                <small>Inválidas</small>
                                <strong class="text-danger" id="stat-invalidas">0</strong>
                            </div>
                        </div>
                        <div class="col-lg-2 col-4 mb-2">
                            <div class="import-stat">
                                <small>Omitidas total</small>
                                <strong id="stat-omitidas">0</strong>
                            </div>
                        </div>
                    </div>

                    <div class="small text-muted text-center mt-2">
                        Omitidas total = duplicadas + inválidas. Cada caso queda guardado en auditoría y en laravel.log.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history mr-1"></i> Últimas importaciones
                    </h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 ventas-import-table">
                        <thead>
                            <tr>
                                <th>Archivo</th>
                                <th class="text-right">Nuevas</th>
                                <th class="text-right">Duplic.</th>
                                <th class="text-right">Invál.</th>
                                <th>Auditoría</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ultimasImportaciones as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->nombre_archivo }}</strong>
                                        <small class="d-block text-muted">
                                            {{ date('d/m/Y H:i', strtotime($item->created_at)) }}
                                        </small>
                                    </td>
                                    <td class="text-right text-success font-weight-bold">
                                        {{ number_format($item->filas_insertadas,0,',','.') }}
                                    </td>
                                    <td class="text-right text-warning font-weight-bold">
                                        {{ number_format($item->filas_duplicadas ?? 0,0,',','.') }}
                                    </td>
                                    <td class="text-right text-danger font-weight-bold">
                                        {{ number_format($item->filas_invalidas ?? 0,0,',','.') }}
                                    </td>
                                    <td class="text-nowrap">
                                        @php
                                            $clasificadas = (int) ($item->filas_duplicadas ?? 0)
                                                + (int) ($item->filas_invalidas ?? 0);
                                            $sinClasificar = max(
                                                0,
                                                (int) $item->filas_omitidas - $clasificadas
                                            );
                                        @endphp

                                        @if($item->filas_omitidas > 0)
                                            <button type="button"
                                                    class="btn btn-xs btn-outline-secondary ver-omitidas"
                                                    data-id="{{ $item->id }}"
                                                    data-archivo="{{ $item->nombre_archivo }}">
                                                <i class="fas fa-search mr-1"></i>
                                                Ver {{ number_format($item->filas_omitidas,0,',','.') }}
                                            </button>
                                            @if($sinClasificar > 0)
                                                <small class="d-block text-muted mt-1">
                                                    {{ number_format($sinClasificar,0,',','.') }} históricas sin clasificar
                                                </small>
                                            @endif
                                        @else
                                            <span class="badge badge-success">Sin omisiones</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Todavía no hay importaciones.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade"
     id="omitidasModal"
     tabindex="-1"
     role="dialog"
     aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Auditoría de filas omitidas</h5>
                    <small class="text-muted" id="omitidasArchivo">-</small>
                </div>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div id="omitidasLoading" class="text-center py-5 text-muted">
                    <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                    <div>Cargando auditoría...</div>
                </div>

                <div id="omitidasContenido" class="d-none">
                    <div class="row text-center mb-3">
                        <div class="col-md-3 col-6 mb-2">
                            <div class="import-stat">
                                <small>Procesadas</small>
                                <strong id="auditProcesadas">0</strong>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="import-stat">
                                <small>Nuevas</small>
                                <strong class="text-success" id="auditNuevas">0</strong>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="import-stat">
                                <small>Duplicadas</small>
                                <strong class="text-warning" id="auditDuplicadas">0</strong>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="import-stat">
                                <small>Inválidas</small>
                                <strong class="text-danger" id="auditInvalidas">0</strong>
                            </div>
                        </div>
                    </div>

                    <div id="omitidasHistoricas"
                         class="alert alert-warning py-2 d-none"></div>

                    <div class="table-responsive border rounded">
                        <table class="table table-sm table-hover mb-0 ventas-import-table">
                            <thead>
                                <tr>
                                    <th>Fila</th>
                                    <th>Tipo</th>
                                    <th>Local</th>
                                    <th>Código</th>
                                    <th>Comprobante</th>
                                    <th>Motivo</th>
                                </tr>
                            </thead>
                            <tbody id="omitidasBody"></tbody>
                        </table>
                    </div>
                </div>

                <div id="omitidasError"
                     class="alert alert-danger d-none mb-0"></div>
            </div>
        </div>
    </div>
</div>
</section>
@endsection

@push('page_css')
<style>
.columnas-esperadas{
    line-height:1.8;
    color:#475569;
    font-size:.82rem;
}
.progress-lg{
    height:24px;
    border-radius:12px;
}
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
.ventas-import-table th{
    font-size:.68rem;
    text-transform:uppercase;
    color:#64748b;
    white-space:nowrap;
}
.ventas-import-table td{
    font-size:.78rem;
    vertical-align:middle!important;
}
</style>
@endpush

@push('page_scripts')
<script>
(function () {
    const form = document.getElementById('ventas-import-form');
    const archivo = document.getElementById('archivo');
    const tokenInput = document.getElementById('import_token');
    const btn = document.getElementById('btn-importar');
    const card = document.getElementById('progreso-card');
    const barra = document.getElementById('progreso-barra');
    const pct = document.getElementById('progreso-pct');
    const mensaje = document.getElementById('progreso-mensaje');
    const estado = document.getElementById('progreso-estado');

    const procesadas = document.getElementById('stat-procesadas');
    const total = document.getElementById('stat-total');
    const insertadas = document.getElementById('stat-insertadas');
    const duplicadas = document.getElementById('stat-duplicadas');
    const invalidas = document.getElementById('stat-invalidas');
    const omitidas = document.getElementById('stat-omitidas');

    let tokenActual = null;
    let polling = null;
    let procesamiento = false;

    archivo.addEventListener('change', function () {
        const label = this.nextElementSibling;
        const nombre = this.files && this.files[0]
            ? this.files[0].name
            : 'Seleccione XLSX o CSV...';

        if (label) {
            label.textContent = nombre;
        }
    });

    function numero(valor) {
        return new Intl.NumberFormat('es-PY').format(
            Number(valor || 0)
        );
    }

    function generarToken() {
        if (window.crypto && window.crypto.getRandomValues) {
            const datos = new Uint32Array(4);
            window.crypto.getRandomValues(datos);

            return 'vta_' + Array.from(datos)
                .map(v => v.toString(16))
                .join('');
        }

        return 'vta_' + Date.now() + '_'
            + Math.random().toString(36).slice(2);
    }

    function setBarra(valor, texto) {
        const n = Math.max(
            0,
            Math.min(100, Number(valor || 0))
        );

        barra.style.width = n + '%';
        barra.textContent = texto || n.toFixed(1) + '%';
        pct.textContent = texto || n.toFixed(1) + '%';
    }

    function aplicar(data) {
        if (!data) {
            return;
        }

        procesamiento = [
            'PREPARANDO',
            'PROCESANDO',
            'COMPLETADO'
        ].includes(data.estado);

        estado.textContent = data.estado || 'PROCESANDO';
        mensaje.textContent = data.mensaje || 'Procesando...';

        procesadas.textContent = numero(data.procesadas);
        insertadas.textContent = numero(data.insertadas);
        duplicadas.textContent = numero(data.duplicadas);
        invalidas.textContent = numero(data.invalidas);
        omitidas.textContent = numero(data.omitidas);
        total.textContent = data.total
            ? numero(data.total)
            : '-';

        if (data.porcentaje !== null
            && data.porcentaje !== undefined) {
            setBarra(data.porcentaje);
        }

        if (data.estado === 'COMPLETADO') {
            setBarra(100, '100%');
            barra.classList.remove('progress-bar-animated');
            barra.classList.add('bg-success');
        }

        if (data.estado === 'ERROR') {
            barra.classList.remove('progress-bar-animated');
            barra.classList.add('bg-danger');
        }
    }

    function consultar() {
        if (!tokenActual) {
            return;
        }

        const plantilla = @json(
            route(
                'ventas.progreso',
                ['token' => '__TOKEN__']
            )
        );

        fetch(
            plantilla.replace('__TOKEN__', tokenActual),
            {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-store'
            }
        )
            .then(r => r.json())
            .then(aplicar)
            .catch(() => {});
    }

    const omitidasUrl = @json(
        route(
            'ventas.importaciones.omitidas',
            ['id' => '__ID__']
        )
    );

    function esc(valor) {
        return String(valor ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function abrirOmitidas(id, archivoNombre) {
        document.getElementById('omitidasArchivo').textContent =
            archivoNombre || ('Importación #' + id);

        document.getElementById('omitidasLoading')
            .classList.remove('d-none');
        document.getElementById('omitidasContenido')
            .classList.add('d-none');
        document.getElementById('omitidasError')
            .classList.add('d-none');
        document.getElementById('omitidasHistoricas')
            .classList.add('d-none');

        $('#omitidasModal').modal('show');

        fetch(
            omitidasUrl.replace('__ID__', id),
            {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-store'
            }
        )
            .then(async function (response) {
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(
                        data.message || 'No se pudo cargar la auditoría.'
                    );
                }

                return data;
            })
            .then(function (data) {
                const imp = data.importacion || {};
                const filas = Array.isArray(data.omitidas)
                    ? data.omitidas
                    : [];

                document.getElementById('auditProcesadas')
                    .textContent = numero(imp.procesadas);
                document.getElementById('auditNuevas')
                    .textContent = numero(imp.insertadas);
                document.getElementById('auditDuplicadas')
                    .textContent = numero(imp.duplicadas);
                document.getElementById('auditInvalidas')
                    .textContent = numero(imp.invalidas);

                const historicas = document.getElementById(
                    'omitidasHistoricas'
                );

                if (Number(imp.sin_clasificar || 0) > 0) {
                    historicas.textContent =
                        numero(imp.sin_clasificar)
                        + ' omisiones pertenecen a una importación anterior a esta mejora y no tienen motivo individual guardado.';
                    historicas.classList.remove('d-none');
                }

                document.getElementById('omitidasBody').innerHTML =
                    filas.length
                        ? filas.map(function (item) {
                            const badge = item.tipo === 'DUPLICADA'
                                ? 'badge-warning'
                                : 'badge-danger';

                            return ''
                                + '<tr>'
                                + '<td>' + esc(item.fila || '-') + '</td>'
                                + '<td><span class="badge ' + badge + '">'
                                    + esc(item.tipo) + '</span></td>'
                                + '<td>' + esc(item.local || '-') + '</td>'
                                + '<td><strong>' + esc(item.codigo || '-') + '</strong></td>'
                                + '<td>' + esc(item.comprobante || '-') + '</td>'
                                + '<td>' + esc(item.motivo || '-') + '</td>'
                                + '</tr>';
                        }).join('')
                        : '<tr><td colspan="6" class="text-center text-muted py-4">'
                            + 'No hay detalle individual guardado para esta importación.'
                            + '</td></tr>';

                document.getElementById('omitidasLoading')
                    .classList.add('d-none');
                document.getElementById('omitidasContenido')
                    .classList.remove('d-none');
            })
            .catch(function (error) {
                document.getElementById('omitidasLoading')
                    .classList.add('d-none');

                const caja = document.getElementById('omitidasError');
                caja.textContent =
                    error.message || 'No se pudo cargar la auditoría.';
                caja.classList.remove('d-none');
            });
    }

    document.addEventListener('click', function (event) {
        const boton = event.target.closest('.ver-omitidas');

        if (!boton) {
            return;
        }

        abrirOmitidas(
            boton.dataset.id,
            boton.dataset.archivo
        );
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        if (!archivo.files || !archivo.files[0]) {
            Swal.fire(
                'Atención',
                'Seleccione un archivo de ventas.',
                'warning'
            );
            return;
        }

        tokenActual = generarToken();
        tokenInput.value = tokenActual;

        btn.disabled = true;
        card.classList.remove('d-none');

        estado.textContent = 'SUBIENDO';
        mensaje.textContent = 'Subiendo archivo...';
        setBarra(0);

        const xhr = new XMLHttpRequest();
        const data = new FormData(form);

        xhr.open('POST', form.action, true);
        xhr.setRequestHeader(
            'X-Requested-With',
            'XMLHttpRequest'
        );
        xhr.setRequestHeader(
            'X-CSRF-TOKEN',
            document.querySelector(
                'meta[name="csrf-token"]'
            ).getAttribute('content')
        );

        xhr.upload.addEventListener('progress', function (e) {
            if (!e.lengthComputable || procesamiento) {
                return;
            }

            const subida = Math.round(
                (e.loaded / e.total) * 100
            );

            estado.textContent = 'SUBIENDO';
            mensaje.textContent = 'Subiendo archivo...';
            setBarra(subida, subida + '% subida');
        });

        xhr.upload.addEventListener('load', function () {
            estado.textContent = 'PREPARANDO';
            mensaje.textContent =
                'Archivo recibido. Preparando importación...';
            setBarra(0, 'Procesando');
        });

        polling = setInterval(consultar, 700);
        consultar();

        xhr.onload = function () {
            clearInterval(polling);
            consultar();

            let respuesta = {};

            try {
                respuesta = JSON.parse(
                    xhr.responseText || '{}'
                );
            } catch (e) {}

            if (
                xhr.status >= 200
                && xhr.status < 300
                && respuesta.success
            ) {
                aplicar({
                    estado: 'COMPLETADO',
                    mensaje: respuesta.message,
                    procesadas:
                        respuesta.resumen.procesadas,
                    insertadas:
                        respuesta.resumen.insertadas,
                    duplicadas:
                        respuesta.resumen.duplicadas,
                    invalidas:
                        respuesta.resumen.invalidas,
                    omitidas:
                        respuesta.resumen.omitidas,
                    total:
                        respuesta.resumen.total,
                    porcentaje: 100
                });

                Swal.fire({
                    icon: 'success',
                    title: 'Importación completada',
                    html:
                        '<strong>'
                        + numero(respuesta.resumen.insertadas)
                        + '</strong> líneas nuevas.<br>'
                        + '<span class="text-warning">'
                        + numero(respuesta.resumen.duplicadas)
                        + ' duplicadas</span> · '
                        + '<span class="text-danger">'
                        + numero(respuesta.resumen.invalidas)
                        + ' inválidas</span>',
                    confirmButtonText: 'Ver ventas'
                }).then(function () {
                    window.location.href =
                        respuesta.redirect;
                });

                return;
            }

            btn.disabled = false;
            estado.textContent = 'ERROR';
            mensaje.textContent =
                respuesta.message
                || 'No se pudo completar la importación.';
            barra.classList.remove(
                'progress-bar-animated'
            );
            barra.classList.add('bg-danger');

            Swal.fire(
                'Error',
                respuesta.message
                    || 'No se pudo completar la importación.',
                'error'
            );
        };

        xhr.onerror = function () {
            clearInterval(polling);
            btn.disabled = false;
            estado.textContent = 'ERROR';
            mensaje.textContent =
                'Se perdió la conexión con el servidor.';
            barra.classList.remove(
                'progress-bar-animated'
            );
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
