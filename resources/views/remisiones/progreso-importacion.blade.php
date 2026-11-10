<div class="card lg-card" id="remisiones-import-card">
    <div class="card-body">
        <strong><i class="fas fa-file-upload text-success mr-1"></i>Actualizar remisiones</strong>
        <p class="text-muted small mb-3">
            Importá ENVIOS para actualizar cantidades y recepción. Si una línea ya existe con 5 prendas
            y el archivo trae 4, quedará en 4, conservando sus vínculos. Las líneas ausentes del archivo no se eliminan.
        </p>
        <form id="remisiones-import-form" method="POST" action="{{ route('control.terminacion.importar-remisiones') }}"
              data-progreso-url="{{ route('control.terminacion.remisiones.progreso', ['token' => '__TOKEN__']) }}"
              data-retorno-url="{{ request()->fullUrl() }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="origen" value="dashboard-logistica">
            <input type="hidden" name="fecha_desde" value="{{ $fechaDesde }}">
            <input type="hidden" name="fecha_hasta" value="{{ $fechaHasta }}">
            <input type="hidden" name="import_token" id="remisiones-import-token">
            <div class="row align-items-end">
                <div class="col-md-8 mb-2 mb-md-0">
                    <label for="remisiones-archivo" class="small">Archivo ENVIOS · Excel o CSV · hasta 100 MB</label>
                    <input type="file" id="remisiones-archivo" name="archivo_envios" class="form-control-file"
                           accept=".xlsx,.xls,.csv" required>
                </div>
                <div class="col-md-4 text-md-right">
                    <button class="btn btn-success" id="remisiones-importar" type="submit">
                        <i class="fas fa-upload mr-1"></i>Importar remisiones
                    </button>
                </div>
            </div>
        </form>
        <div id="remisiones-progreso" class="d-none border-top mt-3 pt-3">
            <div class="d-flex justify-content-between align-items-center mb-2" aria-live="polite">
                <strong id="remisiones-mensaje">Preparando archivo...</strong>
                <span class="badge badge-primary ml-2" id="remisiones-estado">PREPARANDO</span>
            </div>
            <div class="progress mb-3" style="height:24px">
                <div id="remisiones-barra" class="progress-bar progress-bar-striped progress-bar-animated"
                     role="progressbar" aria-label="Progreso de importación" aria-valuemin="0" aria-valuemax="100"
                     aria-valuenow="0" style="width:0%">0%</div>
            </div>
            <div class="row text-center">
                @foreach(['leidas' => 'Filas leídas', 'insertadas' => 'Nuevas', 'actualizadas' => 'Actualizadas',
                    'sin_cambios' => 'Sin cambios', 'cantidades_corregidas' => 'Cantidades corregidas', 'omitidas' => 'Omitidas'] as $clave => $etiqueta)
                    <div class="col-lg-2 col-6 mb-3">
                        <div class="bg-light rounded p-2 h-100">
                            <small class="text-muted d-block">{{ $etiqueta }}</small>
                            <strong class="h4 d-block mb-0" id="remisiones-stat-{{ $clave }}">0</strong>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="small text-muted mb-0" id="remisiones-detalle">Esperando lectura del archivo.</p>
            <p class="small text-muted mb-0" id="remisiones-vinculos"></p>
            <small class="text-muted d-block mt-1">Cantidades corregidas está incluido en Actualizadas. Sin cambios no vuelve a escribirse.</small>
            <a class="btn btn-outline-primary btn-sm mt-3 d-none" id="remisiones-actualizar" href="{{ request()->fullUrl() }}">
                <i class="fas fa-sync-alt mr-1"></i>Actualizar tablero
            </a>
        </div>
    </div>
</div>

@push('page_scripts')
<script>
(function () {
    const form = document.getElementById('remisiones-import-form');
    if (!form) return;
    const archivo = document.getElementById('remisiones-archivo');
    const boton = document.getElementById('remisiones-importar');
    const mensaje = document.getElementById('remisiones-mensaje');
    const estado = document.getElementById('remisiones-estado');
    const barra = document.getElementById('remisiones-barra');
    const detalle = document.getElementById('remisiones-detalle');
    const vinculos = document.getElementById('remisiones-vinculos');
    const actualizar = document.getElementById('remisiones-actualizar');
    const campos = ['leidas', 'insertadas', 'actualizadas', 'sin_cambios', 'cantidades_corregidas', 'omitidas'];
    const numero = valor => new Intl.NumberFormat('es-PY').format(Number(valor || 0));
    let activo = false, token = '', polling = null, consultando = false;

    function mostrarBarra(porcentaje, texto) {
        barra.style.width = (porcentaje === null ? 100 : porcentaje) + '%';
        barra.textContent = texto || (porcentaje === null ? 'Procesando...' : porcentaje + '%');
        if (porcentaje === null) barra.removeAttribute('aria-valuenow');
        else barra.setAttribute('aria-valuenow', porcentaje);
    }

    function aplicar(datos) {
        estado.textContent = datos.estado || 'PROCESANDO';
        mensaje.textContent = datos.mensaje || 'Procesando remisiones...';
        campos.forEach(campo => {
            document.getElementById('remisiones-stat-' + campo).textContent = numero(datos[campo]);
        });
        detalle.textContent = 'Documentos: ' + numero(datos.documentos)
            + ' · Recibidos: ' + numero(datos.recibidos) + ' · En tránsito: ' + numero(datos.en_transito)
            + ' · Repetidas en el bloque: ' + numero(datos.duplicadas)
            + (datos.total ? ' · Total ' + (datos.estado === 'COMPLETADO' ? '' : 'estimado ')
                + 'de filas: ' + numero(datos.total) : '')
            + ' · Tiempo: ' + numero(datos.segundos) + ' s';
        vinculos.textContent = 'Vinculadas a OT: ' + numero(datos.vinculadas_ot)
            + ' · A logística: ' + numero(datos.vinculadas_logistica)
            + ' · Sin OT: ' + numero(datos.sin_ot) + ' · Sin detalle logístico: ' + numero(datos.sin_vincular);
        mostrarBarra(datos.porcentaje === undefined ? null : datos.porcentaje);
    }

    function liberar() {
        activo = false;
        clearInterval(polling);
        boton.disabled = false;
        archivo.disabled = false;
        boton.innerHTML = '<i class="fas fa-upload mr-1"></i>Importar remisiones';
        barra.classList.remove('progress-bar-animated');
        actualizar.classList.remove('d-none');
    }

    function fallar(texto, resumen) {
        if (resumen) aplicar(resumen);
        liberar();
        estado.textContent = 'ERROR';
        estado.className = 'badge badge-danger ml-2';
        mensaje.textContent = texto;
        barra.classList.add('bg-danger');
        if (window.Swal) Swal.fire({icon: 'error', title: 'Importación interrumpida', text: texto});
    }

    async function consultar(esteToken) {
        if (!activo || consultando) return;
        consultando = true;
        try {
            const respuesta = await fetch(form.dataset.progresoUrl.replace('__TOKEN__', esteToken), {
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}, cache: 'no-store'
            });
            if (respuesta.ok) {
                const datos = await respuesta.json();
                if (activo && token === esteToken && datos.estado !== 'ESPERANDO') aplicar(datos);
            }
        } catch (error) {
            // La respuesta del POST también devuelve los contadores finales.
        } finally {
            consultando = false;
        }
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (activo || !form.reportValidity()) return;
        activo = true;
        token = 'rem_' + (window.crypto && window.crypto.randomUUID
            ? window.crypto.randomUUID() : Date.now() + '_' + Math.random().toString(36).slice(2));
        document.getElementById('remisiones-import-token').value = token;
        const datos = new FormData(form);
        const esteToken = token;
        boton.disabled = true;
        archivo.disabled = true;
        boton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Importando...';
        document.getElementById('remisiones-progreso').classList.remove('d-none');
        actualizar.classList.add('d-none');
        estado.className = 'badge badge-primary ml-2';
        barra.className = 'progress-bar progress-bar-striped progress-bar-animated';
        aplicar({estado: 'SUBIENDO', mensaje: 'Subiendo archivo...', porcentaje: 0});
        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.upload.onprogress = function (event) {
            if (event.lengthComputable) {
                const porcentaje = Math.round(event.loaded * 100 / event.total);
                mostrarBarra(porcentaje, porcentaje + '% subida');
            }
        };
        xhr.upload.onload = function () {
            estado.textContent = 'PREPARANDO';
            mensaje.textContent = 'Archivo recibido. Preparando remisiones...';
            mostrarBarra(null);
            polling = setInterval(() => consultar(esteToken), 1000);
            consultar(esteToken);
        };
        xhr.onload = function () {
            let respuesta = {};
            try { respuesta = JSON.parse(xhr.responseText || '{}'); } catch (error) {}
            if (xhr.status < 200 || xhr.status >= 300 || !respuesta.success) {
                const errores = respuesta.errors ? Object.values(respuesta.errors).flat().join(' ') : '';
                fallar(errores || respuesta.message || (xhr.status === 419
                    ? 'La sesión venció. Actualizá la página y volvé a importar.'
                    : 'No se pudo completar la carga. Revisá el archivo o la conexión.'), respuesta.resumen);
                return;
            }
            aplicar(Object.assign({}, respuesta.resumen, {estado: 'COMPLETADO', porcentaje: 100,
                mensaje: 'Importación completada. Actualizá el tablero para ver los saldos.'}));
            liberar();
            barra.classList.add('bg-success');
            estado.className = 'badge badge-success ml-2';
            actualizar.href = respuesta.redirect || form.dataset.retornoUrl;
            if (window.Swal) {
                const resumen = respuesta.resumen;
                Swal.fire({
                    icon: 'success', title: 'Importación completada',
                    html: '<strong>' + numero(resumen.insertadas) + '</strong> líneas nuevas.<br>'
                        + '<strong>' + numero(resumen.actualizadas) + '</strong> actualizadas ('
                        + numero(resumen.cantidades_corregidas) + ' con cantidad corregida).<br>'
                        + numero(resumen.sin_cambios) + ' sin cambios · ' + numero(resumen.omitidas) + ' omitidas.<br>'
                        + 'Tiempo: ' + numero(resumen.segundos) + ' s.',
                    confirmButtonText: 'Actualizar tablero', showCancelButton: true, cancelButtonText: 'Ver resumen'
                }).then(resultado => { if (resultado.isConfirmed) window.location.assign(actualizar.href); });
            }
        };
        xhr.onerror = xhr.onabort = function () {
            fallar('Se perdió la conexión. El servidor puede seguir procesando el archivo. Actualizá el tablero para revisar el resultado; podés reimportarlo sin duplicar las líneas.');
        };
        xhr.send(datos);
    });
})();
</script>
@endpush
