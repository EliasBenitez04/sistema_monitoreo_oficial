@include('sweetalert::alert')

<div class="import-container">

    {{-- =========================================================
        ENCABEZADO
    ========================================================= --}}
    <div class="import-header">

        <div class="header-content">

            <div class="header-icon">
                <i class="fas fa-boxes"></i>
            </div>

            <div>
                <h2>Importación de Stock</h2>

                <p>
                    Carga y actualización de información de stock por sucursal
                </p>
            </div>

        </div>

        <div class="header-status">
            <span class="status-dot"></span>
            Sistema disponible
        </div>

    </div>


    {{-- =========================================================
        CONTENIDO
    ========================================================= --}}
    <div class="import-body">

        <form id="formImportStock"
            action="{{ route('import.stock') }}"
            method="POST"
            enctype="multipart/form-data">

            @csrf


            {{-- INFORMACIÓN --}}
            <div class="section-title">

                <div class="section-icon">
                    <i class="fas fa-file-excel"></i>
                </div>

                <div>
                    <h4>Archivo de importación</h4>

                    <p>
                        Seleccione el archivo Excel que contiene la información de stock.
                    </p>
                </div>

            </div>


            {{-- =====================================================
                DROPZONE
            ===================================================== --}}
            <div class="upload-zone" id="uploadZone">

                <input type="file"
                    name="archivo"
                    id="archivoInput"
                    accept=".xlsx,.xls"
                    required>

                <div class="upload-content">

                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>

                    <h4 id="uploadTitle">
                        Seleccione su archivo Excel
                    </h4>

                    <p id="uploadDescription">
                        Arrastre el archivo aquí o haga clic para seleccionarlo
                    </p>

                    <span class="upload-button">
                        <i class="fas fa-folder-open mr-2"></i>
                        Buscar archivo
                    </span>

                    <div class="supported-files">

                        <span>
                            <i class="fas fa-check-circle"></i>
                            XLSX
                        </span>

                        <span>
                            <i class="fas fa-check-circle"></i>
                            XLS
                        </span>

                        <span>
                            <i class="fas fa-database"></i>
                            Stock
                        </span>

                    </div>

                </div>

            </div>


            {{-- =====================================================
                ARCHIVO SELECCIONADO
            ===================================================== --}}
            <div id="fileInfo" class="file-info">

                <div class="file-info-icon">
                    <i class="fas fa-file-excel"></i>
                </div>

                <div class="file-details">

                    <strong id="fileName">
                        Archivo seleccionado
                    </strong>

                    <span id="fileSize">
                        —
                    </span>

                </div>

                <button type="button"
                    id="removeFile"
                    class="remove-file"
                    title="Eliminar archivo">

                    <i class="fas fa-times"></i>

                </button>

            </div>


            {{-- =====================================================
                INFORMACIÓN DEL PROCESO
            ===================================================== --}}
            <div class="process-info">

                <div class="info-item">

                    <div class="info-item-icon">
                        <i class="fas fa-boxes"></i>
                    </div>

                    <div>
                        <span>Proceso</span>
                        <strong>Importación de Stock</strong>
                    </div>

                </div>


                <div class="info-item">

                    <div class="info-item-icon">
                        <i class="fas fa-store"></i>
                    </div>

                    <div>
                        <span>Alcance</span>
                        <strong>Por sucursal</strong>
                    </div>

                </div>


                <div class="info-item">

                    <div class="info-item-icon">
                        <i class="fas fa-sync-alt"></i>
                    </div>

                    <div>
                        <span>Actualización</span>
                        <strong>Automática</strong>
                    </div>

                </div>

            </div>


            {{-- =====================================================
                BOTONES
            ===================================================== --}}
            <div class="form-actions">

                <div class="security-message">

                    <i class="fas fa-shield-alt"></i>

                    <span>
                        La información será procesada de forma segura
                    </span>

                </div>


                <button type="submit"
                    id="btnImportar"
                    class="btn-import"
                    disabled>

                    <i class="fas fa-upload"></i>

                    <span>Iniciar importación</span>

                </button>

            </div>

        </form>

    </div>

</div>


{{-- =========================================================
    OVERLAY DE IMPORTACIÓN
========================================================= --}}
<div id="loadingOverlay">

    <div class="loading-box">

        {{-- ICONO --}}
        <div class="loading-icon">

            <div class="loading-icon-inner">

                <i class="fas fa-file-import"></i>

            </div>

        </div>


        {{-- ESTADO --}}
        <div class="loading-status">

            <span class="loading-dot"></span>

            Procesando información

        </div>


        <h3>
            Importando stock
        </h3>


        <p class="loading-description">

            Estamos procesando la información del archivo.
            Este proceso puede tardar unos momentos.

        </p>


        {{-- PROGRESO --}}
        <div class="progress-container">

            <div class="progress-header">

                <span>
                    Progreso
                </span>

                <strong id="progressPercent">
                    0%
                </strong>

            </div>


            <div class="progress-custom">

                <div id="progressBar"></div>

            </div>

        </div>


        {{-- TIEMPO --}}
        <div class="loading-footer">

            <div>

                <i class="far fa-clock"></i>

                Tiempo transcurrido

            </div>

            <strong id="counter">
                0s
            </strong>

        </div>


        <div class="loading-warning">

            <i class="fas fa-info-circle"></i>

            No cierre esta ventana mientras la importación esté en proceso.

        </div>

    </div>

</div>


<style>
    /* =========================================================
   CONTENEDOR
========================================================= */

    .import-container {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        overflow: hidden;
        box-shadow:
            0 4px 6px rgba(0, 0, 0, .03),
            0 10px 30px rgba(0, 0, 0, .05);
    }


    /* =========================================================
   HEADER
========================================================= */

    .import-header {
        min-height: 100px;
        padding: 22px 28px;
        background: #ffffff;
        border-bottom: 1px solid #edf0f2;

        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .header-content {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .header-icon {
        width: 52px;
        height: 52px;
        border-radius: 12px;

        display: flex;
        align-items: center;
        justify-content: center;

        background: #ecfdf3;
        color: #16a34a;
        font-size: 22px;
    }

    .header-content h2 {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        color: #111827;
    }

    .header-content p {
        margin: 5px 0 0;
        color: #6b7280;
        font-size: 14px;
    }

    .header-status {
        display: flex;
        align-items: center;
        gap: 8px;

        padding: 8px 13px;
        border-radius: 20px;

        background: #f0fdf4;
        color: #15803d;

        font-size: 13px;
        font-weight: 600;
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #22c55e;

        box-shadow: 0 0 0 4px #dcfce7;
    }


    /* =========================================================
   BODY
========================================================= */

    .import-body {
        padding: 30px;
    }


    /* =========================================================
   SECTION TITLE
========================================================= */

    .section-title {
        display: flex;
        align-items: center;
        gap: 13px;
        margin-bottom: 20px;
    }

    .section-icon {
        width: 42px;
        height: 42px;

        display: flex;
        align-items: center;
        justify-content: center;

        background: #f3f4f6;
        color: #374151;
        border-radius: 10px;
    }

    .section-title h4 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: #111827;
    }

    .section-title p {
        margin: 3px 0 0;
        font-size: 13px;
        color: #6b7280;
    }


    /* =========================================================
   DROPZONE
========================================================= */

    .upload-zone {
        position: relative;

        min-height: 230px;

        border: 2px dashed #d1d5db;
        border-radius: 12px;

        background: #fafafa;

        cursor: pointer;

        transition: all .25s ease;

        overflow: hidden;
    }

    .upload-zone:hover {
        border-color: #22c55e;
        background: #f8fffa;
    }

    .upload-zone.dragover {
        border-color: #16a34a;
        background: #f0fdf4;
        transform: scale(1.005);
    }

    .upload-zone.selected {
        border-color: #22c55e;
        background: #f8fffa;
    }

    .upload-zone input {
        position: absolute;
        inset: 0;

        width: 100%;
        height: 100%;

        opacity: 0;

        cursor: pointer;

        z-index: 2;
    }

    .upload-content {
        position: relative;
        z-index: 1;

        min-height: 230px;

        display: flex;
        flex-direction: column;

        align-items: center;
        justify-content: center;

        text-align: center;
    }

    .upload-icon {
        width: 64px;
        height: 64px;

        border-radius: 50%;

        background: #ecfdf3;
        color: #16a34a;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 27px;

        margin-bottom: 13px;
    }

    .upload-content h4 {
        margin: 0;

        color: #1f2937;

        font-size: 17px;
        font-weight: 700;
    }

    .upload-content p {
        margin: 7px 0 15px;

        color: #6b7280;

        font-size: 13px;
    }

    .upload-button {
        display: inline-flex;
        align-items: center;

        padding: 9px 17px;

        background: #ffffff;

        border: 1px solid #d1d5db;

        border-radius: 8px;

        color: #374151;

        font-size: 13px;
        font-weight: 600;

        box-shadow: 0 2px 4px rgba(0, 0, 0, .04);
    }

    .supported-files {
        display: flex;
        gap: 18px;

        margin-top: 17px;

        color: #6b7280;

        font-size: 11px;
    }

    .supported-files span {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .supported-files i {
        color: #16a34a;
    }


    /* =========================================================
   ARCHIVO
========================================================= */

    .file-info {
        display: none;

        margin-top: 16px;

        padding: 14px 16px;

        border: 1px solid #bbf7d0;

        background: #f0fdf4;

        border-radius: 10px;

        align-items: center;
    }

    .file-info.show {
        display: flex;
    }

    .file-info-icon {
        width: 42px;
        height: 42px;

        border-radius: 9px;

        background: #ffffff;
        color: #16a34a;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 20px;
    }

    .file-details {
        flex: 1;

        margin-left: 12px;

        display: flex;
        flex-direction: column;

        gap: 3px;
    }

    .file-details strong {
        color: #166534;
        font-size: 14px;

        max-width: 600px;

        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .file-details span {
        font-size: 12px;
        color: #6b7280;
    }

    .remove-file {
        width: 34px;
        height: 34px;

        border: none;

        background: transparent;

        color: #9ca3af;

        border-radius: 7px;

        cursor: pointer;

        transition: .2s;
    }

    .remove-file:hover {
        background: #fee2e2;
        color: #dc2626;
    }


    /* =========================================================
   INFORMACIÓN DEL PROCESO
========================================================= */

    .process-info {
        display: grid;

        grid-template-columns: repeat(3, 1fr);

        gap: 12px;

        margin-top: 22px;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 11px;

        padding: 14px;

        background: #f9fafb;

        border: 1px solid #f0f1f3;

        border-radius: 10px;
    }

    .info-item-icon {
        width: 36px;
        height: 36px;

        border-radius: 8px;

        display: flex;
        align-items: center;
        justify-content: center;

        background: #ffffff;

        color: #6b7280;
    }

    .info-item div:last-child {
        display: flex;
        flex-direction: column;
    }

    .info-item span {
        font-size: 11px;
        color: #9ca3af;
    }

    .info-item strong {
        margin-top: 2px;

        color: #374151;

        font-size: 13px;
    }


    /* =========================================================
   ACCIONES
========================================================= */

    .form-actions {
        margin-top: 25px;

        padding-top: 20px;

        border-top: 1px solid #edf0f2;

        display: flex;
        align-items: center;
        justify-content: space-between;

        gap: 20px;
    }

    .security-message {
        display: flex;
        align-items: center;

        gap: 8px;

        color: #6b7280;

        font-size: 12px;
    }

    .security-message i {
        color: #16a34a;
    }

    .btn-import {
        border: none;

        border-radius: 9px;

        background: #16a34a;

        color: white;

        padding: 12px 22px;

        font-size: 14px;

        font-weight: 600;

        display: inline-flex;

        align-items: center;

        gap: 9px;

        cursor: pointer;

        transition: all .2s ease;

        box-shadow: 0 4px 10px rgba(22, 163, 74, .18);
    }

    .btn-import:hover:not(:disabled) {
        background: #15803d;

        transform: translateY(-1px);

        box-shadow: 0 6px 15px rgba(22, 163, 74, .25);
    }

    .btn-import:disabled {
        background: #d1d5db;

        box-shadow: none;

        cursor: not-allowed;
    }


    /* =========================================================
   OVERLAY
========================================================= */

    #loadingOverlay {
        position: fixed;

        inset: 0;

        z-index: 99999;

        display: none;

        align-items: center;
        justify-content: center;

        background: rgba(17, 24, 39, .72);

        backdrop-filter: blur(5px);
    }

    .loading-box {
        width: 440px;

        max-width: calc(100% - 30px);

        background: #ffffff;

        border-radius: 16px;

        padding: 32px;

        text-align: center;

        box-shadow: 0 25px 60px rgba(0, 0, 0, .25);
    }


    /* =========================================================
   LOADING ICON
========================================================= */

    .loading-icon {
        width: 76px;
        height: 76px;

        margin: 0 auto 15px;

        border-radius: 50%;

        background: #ecfdf3;

        display: flex;
        align-items: center;
        justify-content: center;
    }

    .loading-icon-inner {
        width: 58px;
        height: 58px;

        border-radius: 50%;

        background: #16a34a;

        color: #ffffff;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 24px;

        animation: pulseImport 1.6s infinite;
    }

    @keyframes pulseImport {

        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.08);
        }

        100% {
            transform: scale(1);
        }

    }


    /* =========================================================
   STATUS
========================================================= */

    .loading-status {
        display: inline-flex;

        align-items: center;

        gap: 7px;

        padding: 6px 11px;

        border-radius: 20px;

        background: #f0fdf4;

        color: #15803d;

        font-size: 11px;

        font-weight: 700;

        text-transform: uppercase;

        letter-spacing: .4px;
    }

    .loading-dot {
        width: 7px;
        height: 7px;

        border-radius: 50%;

        background: #22c55e;

        animation: blink 1s infinite;
    }

    @keyframes blink {

        50% {
            opacity: .3;
        }

    }

    .loading-box h3 {
        margin: 15px 0 7px;

        font-size: 19px;

        color: #111827;
    }

    .loading-description {
        margin: 0 auto 25px;

        max-width: 350px;

        color: #6b7280;

        font-size: 13px;

        line-height: 1.6;
    }


    /* =========================================================
   PROGRESO
========================================================= */

    .progress-container {
        text-align: left;
    }

    .progress-header {
        display: flex;

        justify-content: space-between;

        margin-bottom: 8px;

        font-size: 12px;

        color: #6b7280;
    }

    .progress-header strong {
        color: #16a34a;

        font-size: 13px;
    }

    .progress-custom {
        width: 100%;

        height: 9px;

        background: #e5e7eb;

        border-radius: 20px;

        overflow: hidden;
    }

    #progressBar {
        width: 0%;

        height: 100%;

        border-radius: 20px;

        background: linear-gradient(90deg,
                #16a34a,
                #22c55e);

        transition: width .4s ease;
    }


    /* =========================================================
   FOOTER LOADING
========================================================= */

    .loading-footer {
        margin-top: 18px;

        padding-top: 15px;

        border-top: 1px solid #f0f1f3;

        display: flex;

        justify-content: space-between;

        align-items: center;

        color: #6b7280;

        font-size: 12px;
    }

    .loading-footer div {
        display: flex;

        align-items: center;

        gap: 6px;
    }

    .loading-footer strong {
        color: #374151;

        font-size: 13px;
    }


    /* =========================================================
   WARNING
========================================================= */

    .loading-warning {
        margin-top: 16px;

        padding: 9px 11px;

        background: #fffbeb;

        color: #92400e;

        border-radius: 7px;

        font-size: 10px;

        display: flex;

        align-items: center;

        justify-content: center;

        gap: 6px;
    }


    /* =========================================================
   RESPONSIVE
========================================================= */

    @media(max-width: 768px) {

        .import-header {
            align-items: flex-start;

            flex-direction: column;

            gap: 15px;
        }

        .header-status {
            align-self: flex-start;
        }

        .import-body {
            padding: 20px;
        }

        .process-info {
            grid-template-columns: 1fr;
        }

        .form-actions {
            flex-direction: column;

            align-items: stretch;
        }

        .security-message {
            justify-content: center;
        }

        .btn-import {
            justify-content: center;
        }

    }


    @media(max-width: 480px) {

        .header-content h2 {
            font-size: 18px;
        }

        .header-content p {
            font-size: 12px;
        }

        .supported-files {
            flex-direction: column;

            gap: 5px;
        }

        .loading-box {
            padding: 25px 20px;
        }

    }
</style>


<script>
    document.addEventListener('DOMContentLoaded', function() {

        const form = document.getElementById('formImportStock');

        const input = document.getElementById('archivoInput');

        const uploadZone = document.getElementById('uploadZone');

        const fileInfo = document.getElementById('fileInfo');

        const fileName = document.getElementById('fileName');

        const fileSize = document.getElementById('fileSize');

        const removeFile = document.getElementById('removeFile');

        const btnImportar = document.getElementById('btnImportar');

        const uploadTitle = document.getElementById('uploadTitle');

        const uploadDescription =
            document.getElementById('uploadDescription');

        const loadingOverlay =
            document.getElementById('loadingOverlay');

        const progressBar =
            document.getElementById('progressBar');

        const progressPercent =
            document.getElementById('progressPercent');

        const counter =
            document.getElementById('counter');


        let seconds = 0;

        let progress = 0;

        let timer = null;

        let progressTimer = null;


        /* =====================================================
           FORMATO TAMAÑO
        ===================================================== */

        function formatFileSize(bytes) {

            if (bytes === 0) {
                return '0 Bytes';
            }

            const units = [
                'Bytes',
                'KB',
                'MB',
                'GB'
            ];

            const i = Math.floor(
                Math.log(bytes) / Math.log(1024)
            );

            return (
                parseFloat(
                    (bytes / Math.pow(1024, i))
                    .toFixed(2)
                ) +
                ' ' +
                units[i]
            );

        }


        /* =====================================================
           MOSTRAR ARCHIVO
        ===================================================== */

        function showFile(file) {

            if (!file) {

                resetFile();

                return;

            }


            const extension =
                file.name
                .split('.')
                .pop()
                .toLowerCase();


            if (!['xlsx', 'xls'].includes(extension)) {

                input.value = '';

                Swal.fire({

                    icon: 'warning',

                    title: 'Archivo no válido',

                    text: 'Seleccione un archivo Excel en formato XLSX o XLS.',

                    confirmButtonText: 'Entendido',

                    confirmButtonColor: '#16a34a'

                });

                resetFile();

                return;

            }


            fileName.textContent = file.name;

            fileSize.textContent =
                formatFileSize(file.size);


            fileInfo.classList.add('show');

            btnImportar.disabled = false;


            uploadTitle.textContent =
                'Archivo listo para importar';


            uploadDescription.textContent =
                'El archivo ha sido seleccionado correctamente.';


            uploadZone.classList.add('selected');

        }


        /* =====================================================
           RESET
        ===================================================== */

        function resetFile() {

            input.value = '';

            fileInfo.classList.remove('show');

            btnImportar.disabled = true;


            uploadTitle.textContent =
                'Seleccione su archivo Excel';


            uploadDescription.textContent =
                'Arrastre el archivo aquí o haga clic para seleccionarlo';


            uploadZone.classList.remove('selected');

        }


        /* =====================================================
           CAMBIO DE ARCHIVO
        ===================================================== */

        input.addEventListener('change', function() {

            showFile(this.files[0]);

        });


        /* =====================================================
           ELIMINAR ARCHIVO
        ===================================================== */

        removeFile.addEventListener('click', function() {

            resetFile();

        });


        /* =====================================================
           DRAG & DROP
        ===================================================== */

        uploadZone.addEventListener('dragover', function(e) {

            e.preventDefault();

            uploadZone.classList.add('dragover');

        });


        uploadZone.addEventListener('dragleave', function() {

            uploadZone.classList.remove('dragover');

        });


        uploadZone.addEventListener('drop', function(e) {

            e.preventDefault();

            uploadZone.classList.remove('dragover');


            const files = e.dataTransfer.files;


            if (!files.length) {
                return;
            }


            try {

                const dataTransfer =
                    new DataTransfer();

                dataTransfer.items.add(files[0]);

                input.files =
                    dataTransfer.files;

                showFile(files[0]);

            } catch (error) {

                console.error(error);

            }

        });


        /* =====================================================
           SUBMIT
        ===================================================== */

        form.addEventListener('submit', function() {

            loadingOverlay.style.display = 'flex';


            btnImportar.disabled = true;

            btnImportar.innerHTML = `
            <span class="spinner-border spinner-border-sm"></span>
            Procesando...
        `;


            seconds = 0;

            progress = 0;


            counter.textContent = '0s';

            progressBar.style.width = '0%';

            progressPercent.textContent = '0%';


            /* =================================================
               CONTADOR
            ================================================= */

            timer = setInterval(function() {

                seconds++;

                counter.textContent =
                    seconds + 's';

            }, 1000);


            /* =================================================
               PROGRESO VISUAL
            ================================================= */

            progressTimer = setInterval(function() {

                if (progress < 92) {

                    const increment =
                        Math.random() * 5 + 1;


                    progress = Math.min(
                        progress + increment,
                        92
                    );


                    progressBar.style.width =
                        progress + '%';


                    progressPercent.textContent =
                        Math.floor(progress) + '%';

                }

            }, 700);

        });

    });
</script>