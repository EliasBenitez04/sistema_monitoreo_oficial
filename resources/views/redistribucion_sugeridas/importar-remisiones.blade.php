{{-- ================================================================
     MODAL - IMPORTAR REMISIONES
================================================================ --}}

<div class="modal fade" id="modalImportarRemisiones" tabindex="-1" role="dialog"
    aria-labelledby="modalImportarRemisionesLabel" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered modal-md" role="document">

        <div class="modal-content import-modal">

            {{-- ====================================================
                 HEADER
            ===================================================== --}}
            <div class="modal-header import-modal-header">

                <div class="d-flex align-items-center">

                    {{-- Icono --}}
                    <div class="import-header-icon">
                        <i class="fas fa-file-import"></i>
                    </div>

                    {{-- Título --}}
                    <div class="ml-3">

                        <h5 class="modal-title import-title" id="modalImportarRemisionesLabel">

                            Importar remisiones

                        </h5>

                        <div class="import-subtitle">
                            Actualización de movimientos de redistribución
                        </div>

                    </div>

                </div>

                {{-- Cerrar --}}
                <button type="button" class="close import-close" data-dismiss="modal" aria-label="Cerrar">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>


            {{-- ====================================================
                 FORMULARIO
            ===================================================== --}}
            <form action="{{ route('RedistribucionSugeridas.importarRemisiones') }}" method="POST"
                enctype="multipart/form-data" id="formImportarRemisiones">

                @csrf

                {{-- =================================================
                     BODY
                ================================================== --}}
                <div class="modal-body import-modal-body">

                    {{-- =============================================
                         INFORMACIÓN PRINCIPAL
                    ============================================== --}}
                    <div class="import-info-card">

                        <div class="d-flex align-items-start">

                            <div class="import-info-icon">
                                <i class="fas fa-info"></i>
                            </div>

                            <div class="flex-grow-1">

                                <div class="import-info-title">
                                    ¿Qué realizará el sistema?
                                </div>

                                <div class="import-info-text">
                                    El archivo será procesado y las remisiones
                                    serán asociadas automáticamente con las
                                    redistribuciones existentes.
                                </div>

                            </div>

                        </div>


                        {{-- =========================================
                             CRITERIOS DE COINCIDENCIA
                        ========================================== --}}
                        <div class="import-match-list">

                            <div class="match-item">

                                <span class="match-icon">
                                    <i class="fas fa-check"></i>
                                </span>

                                <span>
                                    Sucursal de origen
                                </span>

                            </div>


                            <div class="match-item">

                                <span class="match-icon">
                                    <i class="fas fa-check"></i>
                                </span>

                                <span>
                                    Sucursal de destino
                                </span>

                            </div>


                            <div class="match-item">

                                <span class="match-icon">
                                    <i class="fas fa-check"></i>
                                </span>

                                <span>
                                    Código de artículo
                                </span>

                            </div>

                        </div>

                    </div>


                    {{-- =============================================
                         SECCIÓN ARCHIVO
                    ============================================== --}}
                    <div class="import-file-section">

                        <label for="archivoRemisiones" class="import-label">

                            Archivo de remisiones

                            <span class="required-mark">*</span>

                        </label>


                        {{-- Contenedor --}}
                        <div class="import-file-wrapper">

                            <div class="custom-file">

                                <input type="file" class="custom-file-input" id="archivoRemisiones" name="archivo"
                                    accept=".xlsx,.xls" required>

                                <label class="custom-file-label" for="archivoRemisiones" id="archivoRemisionesLabel">

                                    <span class="file-placeholder">

                                        <i class="fas fa-folder-open mr-2"></i>

                                        Seleccionar archivo Excel...

                                    </span>

                                </label>

                            </div>

                        </div>


                        {{-- Información del archivo --}}
                        <div class="import-file-help">

                            <span>
                                <i class="fas fa-file-excel mr-1"></i>
                                Formatos permitidos:
                                <strong>XLSX</strong> y <strong>XLS</strong>
                            </span>

                            <span class="file-size-help">
                                Archivo Excel
                            </span>

                        </div>

                    </div>


                    {{-- =============================================
                         ARCHIVO SELECCIONADO
                    ============================================== --}}
                    <div id="archivoSeleccionado" class="selected-file-card d-none">

                        <div class="selected-file-icon">

                            <i class="fas fa-file-excel"></i>

                        </div>

                        <div class="selected-file-info">

                            <div class="selected-file-title">
                                Archivo seleccionado
                            </div>

                            <div id="nombreArchivo" class="selected-file-name">

                                --

                            </div>

                        </div>

                        <div class="selected-file-check">

                            <i class="fas fa-check-circle"></i>

                        </div>

                    </div>


                    {{-- =============================================
                         AVISO
                    ============================================== --}}
                    <div class="import-warning">

                        <div class="warning-icon">

                            <i class="fas fa-shield-alt"></i>

                        </div>

                        <div class="warning-content">

                            <div class="warning-title">
                                Importación segura
                            </div>

                            <div class="warning-text">

                                Los registros coincidentes serán actualizados
                                automáticamente. Los movimientos finalizados
                                no serán modificados.

                            </div>

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     FOOTER
                ================================================== --}}
                <div class="modal-footer import-modal-footer">

                    <button type="button" class="btn btn-light import-btn-cancel" data-dismiss="modal">

                        <i class="fas fa-times mr-1"></i>

                        Cancelar

                    </button>


                    <button type="submit" class="btn btn-primary import-btn-submit" id="btnImportarRemisiones">

                        <i class="fas fa-cloud-upload-alt mr-1"></i>

                        Importar archivo

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


{{-- ================================================================
     ESTILOS
================================================================ --}}

<style>
    /* =============================================================
       CONTENEDOR PRINCIPAL
    ============================================================= */

    #modalImportarRemisiones .import-modal {

        border: 0;
        border-radius: 14px;
        overflow: hidden;

        background: #ffffff;

        box-shadow:
            0 20px 50px rgba(15, 23, 42, .15),
            0 5px 15px rgba(15, 23, 42, .08);

    }


    /* =============================================================
       HEADER
    ============================================================= */

    #modalImportarRemisiones .import-modal-header {

        padding: 20px 24px;

        background: #ffffff;

        border-bottom: 1px solid #eef0f3;

    }


    #modalImportarRemisiones .import-header-icon {

        width: 46px;
        height: 46px;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 11px;

        background: #eff6ff;

        color: #2563eb;

        font-size: 18px;

    }


    #modalImportarRemisiones .import-title {

        margin: 0;

        color: #111827;

        font-size: 17px;

        font-weight: 700;

        line-height: 1.3;

    }


    #modalImportarRemisiones .import-subtitle {

        margin-top: 3px;

        color: #6b7280;

        font-size: 12px;

        line-height: 1.4;

    }


    #modalImportarRemisiones .import-close {

        margin: 0;

        padding: 4px 8px;

        color: #9ca3af;

        opacity: 1;

        font-size: 24px;

        font-weight: 400;

        line-height: 1;

        transition: all .2s ease;

    }


    #modalImportarRemisiones .import-close:hover {

        color: #374151;

        transform: rotate(90deg);

    }


    /* =============================================================
       BODY
    ============================================================= */

    #modalImportarRemisiones .import-modal-body {

        padding: 22px 24px 24px;

    }


    /* =============================================================
       CARD INFORMACIÓN
    ============================================================= */

    #modalImportarRemisiones .import-info-card {

        padding: 16px;

        background: #f8fafc;

        border: 1px solid #e5e7eb;

        border-radius: 10px;

    }


    #modalImportarRemisiones .import-info-icon {

        width: 28px;
        height: 28px;

        flex-shrink: 0;

        display: flex;
        align-items: center;
        justify-content: center;

        margin-right: 10px;

        border-radius: 50%;

        background: #dbeafe;

        color: #2563eb;

        font-size: 12px;

    }


    #modalImportarRemisiones .import-info-title {

        margin-bottom: 4px;

        color: #1f2937;

        font-size: 13px;

        font-weight: 700;

    }


    #modalImportarRemisiones .import-info-text {

        color: #6b7280;

        font-size: 12px;

        line-height: 1.55;

    }


    /* =============================================================
       LISTA DE COINCIDENCIAS
    ============================================================= */

    #modalImportarRemisiones .import-match-list {

        display: flex;

        flex-wrap: wrap;

        gap: 7px;

        margin-top: 14px;

        padding-top: 13px;

        border-top: 1px solid #e5e7eb;

    }


    #modalImportarRemisiones .match-item {

        display: inline-flex;

        align-items: center;

        padding: 6px 9px;

        background: #ffffff;

        border: 1px solid #e5e7eb;

        border-radius: 6px;

        color: #4b5563;

        font-size: 11px;

        font-weight: 500;

        transition: all .2s ease;

    }


    #modalImportarRemisiones .match-item:hover {

        border-color: #bfdbfe;

        background: #eff6ff;

    }


    #modalImportarRemisiones .match-icon {

        display: inline-flex;

        align-items: center;

        justify-content: center;

        width: 16px;
        height: 16px;

        margin-right: 5px;

        border-radius: 50%;

        background: #dcfce7;

        color: #16a34a;

        font-size: 8px;

    }


    /* =============================================================
       SECCIÓN ARCHIVO
    ============================================================= */

    #modalImportarRemisiones .import-file-section {

        margin-top: 22px;

    }


    #modalImportarRemisiones .import-label {

        display: block;

        margin-bottom: 8px;

        color: #374151;

        font-size: 13px;

        font-weight: 700;

    }


    #modalImportarRemisiones .required-mark {

        color: #dc2626;

    }


    /* =============================================================
       INPUT FILE
    ============================================================= */

    #modalImportarRemisiones .import-file-wrapper {

        position: relative;

    }


    #modalImportarRemisiones .custom-file {

        height: 44px;

    }


    #modalImportarRemisiones .custom-file-input {

        height: 44px;

        cursor: pointer;

    }


    #modalImportarRemisiones .custom-file-label {

        height: 44px;

        display: flex;

        align-items: center;

        margin: 0;

        padding: 0 12px;

        border: 1px solid #d1d5db;

        border-radius: 8px;

        background: #ffffff;

        color: #6b7280;

        font-size: 13px;

        line-height: 42px;

        overflow: hidden;

        transition: all .2s ease;

    }


    #modalImportarRemisiones .custom-file-label::after {

        height: 42px;

        display: flex;

        align-items: center;

        padding: 0 15px;

        border-left: 1px solid #e5e7eb;

        background: #f8fafc;

        color: #374151;

        content: "Examinar";

        font-size: 12px;

        font-weight: 600;

    }


    #modalImportarRemisiones .custom-file:hover .custom-file-label {

        border-color: #93c5fd;

    }


    #modalImportarRemisiones .custom-file-input:focus~.custom-file-label {

        border-color: #2563eb;

        box-shadow: 0 0 0 3px rgba(37, 99, 235, .10);

    }


    #modalImportarRemisiones .file-placeholder {

        display: flex;

        align-items: center;

        white-space: nowrap;

        overflow: hidden;

        text-overflow: ellipsis;

    }


    #modalImportarRemisiones .file-placeholder i {

        color: #9ca3af;

    }


    /* =============================================================
       AYUDA ARCHIVO
    ============================================================= */

    #modalImportarRemisiones .import-file-help {

        display: flex;

        justify-content: space-between;

        align-items: center;

        margin-top: 7px;

        color: #9ca3af;

        font-size: 10px;

    }


    #modalImportarRemisiones .import-file-help i {

        color: #16a34a;

    }


    #modalImportarRemisiones .file-size-help {

        color: #9ca3af;

    }


    /* =============================================================
       ARCHIVO SELECCIONADO
    ============================================================= */

    #modalImportarRemisiones .selected-file-card {

        display: flex;

        align-items: center;

        margin-top: 12px;

        padding: 11px 12px;

        background: #f0fdf4;

        border: 1px solid #bbf7d0;

        border-radius: 8px;

    }


    #modalImportarRemisiones .selected-file-icon {

        width: 34px;
        height: 34px;

        display: flex;
        align-items: center;
        justify-content: center;

        flex-shrink: 0;

        border-radius: 7px;

        background: #dcfce7;

        color: #16a34a;

        font-size: 15px;

    }


    #modalImportarRemisiones .selected-file-info {

        min-width: 0;

        flex: 1;

        margin-left: 10px;

    }


    #modalImportarRemisiones .selected-file-title {

        color: #166534;

        font-size: 10px;

        font-weight: 600;

        text-transform: uppercase;

        letter-spacing: .3px;

    }


    #modalImportarRemisiones .selected-file-name {

        margin-top: 2px;

        color: #374151;

        font-size: 12px;

        font-weight: 600;

        white-space: nowrap;

        overflow: hidden;

        text-overflow: ellipsis;

    }


    #modalImportarRemisiones .selected-file-check {

        margin-left: 10px;

        color: #16a34a;

        font-size: 16px;

    }


    /* =============================================================
       AVISO
    ============================================================= */

    #modalImportarRemisiones .import-warning {

        display: flex;

        align-items: flex-start;

        margin-top: 18px;

        padding: 12px 13px;

        background: #f8fafc;

        border: 1px solid #e5e7eb;

        border-left: 3px solid #2563eb;

        border-radius: 7px;

    }


    #modalImportarRemisiones .warning-icon {

        width: 24px;

        flex-shrink: 0;

        margin-top: 1px;

        color: #2563eb;

        font-size: 13px;

    }


    #modalImportarRemisiones .warning-content {

        flex: 1;

    }


    #modalImportarRemisiones .warning-title {

        margin-bottom: 2px;

        color: #374151;

        font-size: 11px;

        font-weight: 700;

    }


    #modalImportarRemisiones .warning-text {

        color: #6b7280;

        font-size: 10px;

        line-height: 1.5;

    }


    /* =============================================================
       FOOTER
    ============================================================= */

    #modalImportarRemisiones .import-modal-footer {

        display: flex;

        justify-content: flex-end;

        gap: 8px;

        padding: 14px 24px;

        background: #f9fafb;

        border-top: 1px solid #eef0f3;

    }


    /* =============================================================
       BOTONES
    ============================================================= */

    #modalImportarRemisiones .import-modal-footer .btn {

        height: 38px;

        padding: 0 16px;

        display: inline-flex;

        align-items: center;

        justify-content: center;

        border-radius: 7px;

        font-size: 12px;

        font-weight: 600;

        transition: all .2s ease;

    }


    /* Cancelar */

    #modalImportarRemisiones .import-btn-cancel {

        color: #4b5563;

        background: #ffffff;

        border: 1px solid #d1d5db;

    }


    #modalImportarRemisiones .import-btn-cancel:hover {

        background: #f3f4f6;

        border-color: #c7cbd1;

    }


    /* Importar */

    #modalImportarRemisiones .import-btn-submit {

        min-width: 145px;

        background: #2563eb;

        border-color: #2563eb;

        box-shadow: 0 2px 5px rgba(37, 99, 235, .18);

    }


    #modalImportarRemisiones .import-btn-submit:hover {

        background: #1d4ed8;

        border-color: #1d4ed8;

        transform: translateY(-1px);

        box-shadow: 0 4px 8px rgba(37, 99, 235, .22);

    }


    #modalImportarRemisiones .import-btn-submit:active {

        transform: translateY(0);

    }


    #modalImportarRemisiones .import-btn-submit:disabled {

        cursor: not-allowed;

        opacity: .7;

        transform: none;

    }


    /* =============================================================
       RESPONSIVE
    ============================================================= */

    @media (max-width: 576px) {

        #modalImportarRemisiones .modal-dialog {

            margin: 10px;

        }


        #modalImportarRemisiones .import-modal-header {

            padding: 17px 18px;

        }


        #modalImportarRemisiones .import-modal-body {

            padding: 18px;

        }


        #modalImportarRemisiones .import-modal-footer {

            padding: 12px 18px;

        }


        #modalImportarRemisiones .import-match-list {

            flex-direction: column;

        }


        #modalImportarRemisiones .match-item {

            width: 100%;

        }

    }
</style>


{{-- ================================================================
     JAVASCRIPT
================================================================ --}}

<script>
    $(document).ready(function() {

        const inputArchivo = $('#archivoRemisiones');
        const labelArchivo = $('#archivoRemisionesLabel');
        const archivoSeleccionado = $('#archivoSeleccionado');
        const nombreArchivo = $('#nombreArchivo');
        const form = $('#formImportarRemisiones');
        const btnImportar = $('#btnImportarRemisiones');


        /* =========================================================
           SELECCIÓN DEL ARCHIVO
        ========================================================== */

        inputArchivo.on('change', function() {

            const archivo = this.files && this.files.length ?
                this.files[0] :
                null;


            /* -----------------------------------------------------
               Si no hay archivo
            ----------------------------------------------------- */

            if (!archivo) {

                labelArchivo.html(`
                    <span class="file-placeholder">
                        <i class="fas fa-folder-open mr-2"></i>
                        Seleccionar archivo Excel...
                    </span>
                `);

                archivoSeleccionado.addClass('d-none');

                return;
            }


            /* -----------------------------------------------------
               Validar extensión
            ----------------------------------------------------- */

            const nombre = archivo.name;

            const extension = nombre
                .split('.')
                .pop()
                .toLowerCase();


            const extensionesPermitidas = [
                'xlsx',
                'xls'
            ];


            if (!extensionesPermitidas.includes(extension)) {

                inputArchivo.val('');

                archivoSeleccionado.addClass('d-none');

                labelArchivo.html(`
                    <span class="file-placeholder text-danger">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        Seleccionar archivo Excel...
                    </span>
                `);

                alert('El archivo seleccionado no tiene un formato válido. Utilice XLSX o XLS.');

                return;
            }


            /* -----------------------------------------------------
               Mostrar nombre en input
            ----------------------------------------------------- */

            labelArchivo.html(`
                <span class="file-placeholder">
                    <i class="fas fa-file-excel mr-2 text-success"></i>
                    ${escapeHtml(nombre)}
                </span>
            `);


            /* -----------------------------------------------------
               Mostrar tarjeta de archivo seleccionado
            ----------------------------------------------------- */

            nombreArchivo.text(nombre);

            archivoSeleccionado
                .removeClass('d-none')
                .hide()
                .fadeIn(180);

        });


        /* =========================================================
           SUBMIT
        ========================================================== */

        form.on('submit', function() {

            if (!inputArchivo.val()) {

                return;

            }


            /* -----------------------------------------------------
               Evitar doble envío
            ----------------------------------------------------- */

            btnImportar
                .prop('disabled', true)
                .html(`
                    <span class="spinner-border spinner-border-sm mr-2"
                          role="status"
                          aria-hidden="true"></span>

                    Procesando...
                `);

        });


        /* =========================================================
           LIMPIAR AL CERRAR MODAL
        ========================================================== */

        $('#modalImportarRemisiones').on('hidden.bs.modal', function() {

            form[0].reset();

            archivoSeleccionado.addClass('d-none');

            labelArchivo.html(`
                <span class="file-placeholder">
                    <i class="fas fa-folder-open mr-2"></i>
                    Seleccionar archivo Excel...
                </span>
            `);


            btnImportar
                .prop('disabled', false)
                .html(`
                    <i class="fas fa-cloud-upload-alt mr-1"></i>
                    Importar archivo
                `);

        });


        /* =========================================================
           ESCAPAR HTML
           Evita insertar directamente el nombre del archivo
        ========================================================== */

        function escapeHtml(text) {

            return $('<div>')
                .text(text)
                .html();

        }

    });
</script>
