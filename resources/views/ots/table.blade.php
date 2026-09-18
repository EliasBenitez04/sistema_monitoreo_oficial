@include('sweetalert::alert')

<div class="imports-wrapper">

    {{-- =========================================================
         ENCABEZADO GENERAL
    ========================================================== --}}

    <div class="imports-main-header">

        <div class="main-header-content">

            <div class="main-header-icon">
                <i class="fas fa-database"></i>
            </div>

            <div>

                <h2>
                    Importación de Información
                </h2>

                <p>
                    Gestión y actualización de información operativa
                </p>

            </div>

        </div>

        <div class="system-status">

            <span class="system-status-dot"></span>

            Sistema disponible

        </div>

    </div>


    {{-- =========================================================
         IMPORTACIÓN TRAZABILIDAD OT
    ========================================================== --}}

    <div class="import-module">

        <div class="module-header module-header-blue">

            <div class="module-title-area">

                <div class="module-icon blue-icon">

                    <i class="fas fa-project-diagram"></i>

                </div>

                <div>

                    <h3>
                        Trazabilidad de Órdenes de Trabajo
                    </h3>

                    <p>
                        Importación de procesos y registros históricos de OT
                    </p>

                </div>

            </div>

            <span class="module-badge badge-blue">

                <i class="fas fa-industry"></i>

                Producción

            </span>

        </div>


        <div class="module-body">

            <form id="formImportOT"
                action="{{ route('ot.importar') }}"
                method="POST"
                enctype="multipart/form-data">

                @csrf


                <div class="section-label">

                    <i class="fas fa-file-excel"></i>

                    Archivo de trazabilidad

                </div>


                {{-- DROPZONE OT --}}

                <div class="upload-zone upload-zone-blue"
                    id="uploadZoneOT">

                    <input type="file"
                        name="archivo"
                        id="archivoInput"
                        accept=".xlsx,.xls"
                        required>


                    <div class="upload-content">

                        <div class="upload-icon blue-upload-icon">

                            <i class="fas fa-cloud-upload-alt"></i>

                        </div>


                        <h4 id="uploadTitleOT">

                            Seleccione el archivo Excel

                        </h4>


                        <p id="uploadDescriptionOT">

                            Arrastre el archivo aquí o haga clic para seleccionarlo

                        </p>


                        <span class="upload-button">

                            <i class="fas fa-folder-open"></i>

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
                                <i class="fas fa-history"></i>
                                Trazabilidad histórica
                            </span>

                        </div>

                    </div>

                </div>


                {{-- ARCHIVO OT --}}

                <div id="fileInfoOT"
                    class="file-info">

                    <div class="file-info-icon blue-file-icon">

                        <i class="fas fa-file-excel"></i>

                    </div>


                    <div class="file-details">

                        <strong id="fileNameOT">
                            Archivo seleccionado
                        </strong>

                        <span id="fileSizeOT">
                            —
                        </span>

                    </div>


                    <button type="button"
                        id="removeFileOT"
                        class="remove-file">

                        <i class="fas fa-times"></i>

                    </button>

                </div>


                {{-- INFORMACIÓN OT --}}

                <div class="process-info">

                    <div class="info-item">

                        <div class="info-item-icon">

                            <i class="fas fa-clipboard-list"></i>

                        </div>

                        <div>

                            <span>Información</span>

                            <strong>Órdenes de Trabajo</strong>

                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-item-icon">

                            <i class="fas fa-project-diagram"></i>

                        </div>

                        <div>

                            <span>Proceso</span>

                            <strong>Trazabilidad</strong>

                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-item-icon">

                            <i class="fas fa-history"></i>

                        </div>

                        <div>

                            <span>Tipo</span>

                            <strong>Histórico</strong>

                        </div>

                    </div>

                </div>


                {{-- ACCIONES --}}

                <div class="form-actions">

                    <div class="security-message">

                        <i class="fas fa-shield-alt"></i>

                        Información procesada de forma segura

                    </div>


                    <button type="submit"
                        id="btnImportar"
                        class="btn-import btn-import-blue"
                        disabled>

                        <i class="fas fa-upload"></i>

                        <span>Importar trazabilidad</span>

                    </button>

                </div>

            </form>

        </div>

    </div>


    {{-- =========================================================
         IMPORTACIÓN LOGÍSTICA
    ========================================================== --}}

    <div class="import-module">

        <div class="module-header module-header-green">

            <div class="module-title-area">

                <div class="module-icon green-icon">

                    <i class="fas fa-truck"></i>

                </div>

                <div>

                    <h3>
                        Logística y Distribución
                    </h3>

                    <p>
                        Importación de OTs terminadas y movimientos logísticos
                    </p>

                </div>

            </div>


            <span class="module-badge badge-green">

                <i class="fas fa-truck-loading"></i>

                Logística

            </span>

        </div>


        <div class="module-body">

            <form id="formImportLogistica"
                action="{{ route('ot.importar.logistica') }}"
                method="POST"
                enctype="multipart/form-data">

                @csrf


                {{-- FECHA --}}

                <div class="date-section">

                    <div class="date-label">

                        <div class="date-icon">

                            <i class="far fa-calendar-alt"></i>

                        </div>

                        <div>

                            <strong>
                                Fecha del proceso
                            </strong>

                            <span>
                                Fecha asociada a la importación logística
                            </span>

                        </div>

                    </div>


                    <input type="date"
                        name="fecha_proceso"
                        id="fechaProceso"
                        class="date-input"
                        required
                        value="{{ date('Y-m-d') }}">

                </div>


                <div class="section-label">

                    <i class="fas fa-file-excel"></i>

                    Archivo de logística

                </div>


                {{-- DROPZONE LOGÍSTICA --}}

                <div class="upload-zone upload-zone-green"
                    id="uploadZoneLogistica">

                    <input type="file"
                        name="archivo"
                        id="archivoLogistica"
                        accept=".xlsx,.xls"
                        required>


                    <div class="upload-content">

                        <div class="upload-icon green-upload-icon">

                            <i class="fas fa-cloud-upload-alt"></i>

                        </div>


                        <h4 id="uploadTitleLogistica">

                            Seleccione el archivo Excel

                        </h4>


                        <p id="uploadDescriptionLogistica">

                            Arrastre el archivo aquí o haga clic para seleccionarlo

                        </p>


                        <span class="upload-button">

                            <i class="fas fa-folder-open"></i>

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
                                <i class="fas fa-route"></i>
                                Distribución
                            </span>

                        </div>

                    </div>

                </div>


                {{-- ARCHIVO LOGÍSTICA --}}

                <div id="fileInfoLogistica"
                    class="file-info">

                    <div class="file-info-icon green-file-icon">

                        <i class="fas fa-file-excel"></i>

                    </div>


                    <div class="file-details">

                        <strong id="fileNameLogistica">
                            Archivo seleccionado
                        </strong>

                        <span id="fileSizeLogistica">
                            —
                        </span>

                    </div>


                    <button type="button"
                        id="removeFileLogistica"
                        class="remove-file">

                        <i class="fas fa-times"></i>

                    </button>

                </div>


                {{-- INFORMACIÓN LOGÍSTICA --}}

                <div class="process-info">

                    <div class="info-item">

                        <div class="info-item-icon">

                            <i class="fas fa-truck"></i>

                        </div>

                        <div>

                            <span>Área</span>

                            <strong>Logística</strong>

                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-item-icon">

                            <i class="fas fa-boxes"></i>

                        </div>

                        <div>

                            <span>Información</span>

                            <strong>OT terminadas</strong>

                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-item-icon">

                            <i class="fas fa-calendar-check"></i>

                        </div>

                        <div>

                            <span>Fecha</span>

                            <strong id="fechaMostrar">
                                {{ date('d/m/Y') }}
                            </strong>

                        </div>

                    </div>

                </div>


                {{-- ACCIONES --}}

                <div class="form-actions">

                    <div class="security-message">

                        <i class="fas fa-shield-alt"></i>

                        Información procesada de forma segura

                    </div>


                    <button type="submit"
                        id="btnImportarLogistica"
                        class="btn-import btn-import-green"
                        disabled>

                        <i class="fas fa-truck-loading"></i>

                        <span>Importar logística</span>

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


{{-- =========================================================
     MODAL TRAZABILIDAD OT
========================================================= --}}

<div id="loadingOverlay">

    <div class="loading-box">

        <div class="loading-icon loading-icon-blue">

            <div class="loading-icon-inner">

                <i class="fas fa-project-diagram"></i>

            </div>

        </div>


        <div class="loading-status loading-status-blue">

            <span class="loading-dot"></span>

            Procesando información

        </div>


        <h3>
            Importando trazabilidad de OT
        </h3>


        <p class="loading-description">

            Procesando órdenes de trabajo, procesos
            y registros históricos.

        </p>


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

            No cierre esta ventana mientras se procesa la información.

        </div>

    </div>

</div>


{{-- =========================================================
     MODAL LOGÍSTICA
========================================================= --}}

<div id="loadingOverlayLogistica">

    <div class="loading-box">

        <div class="loading-icon loading-icon-green">

            <div class="loading-icon-inner">

                <i class="fas fa-truck"></i>

            </div>

        </div>


        <div class="loading-status loading-status-green">

            <span class="loading-dot"></span>

            Procesando información

        </div>


        <h3>
            Importando logística
        </h3>


        <p class="loading-description">

            Procesando OTs terminadas y generando
            registros de Logística y Distribución.

        </p>


        <div class="progress-container">

            <div class="progress-header">

                <span>
                    Progreso
                </span>

                <strong id="progressPercentLogistica">
                    0%
                </strong>

            </div>


            <div class="progress-custom">

                <div id="progressBarLogistica"></div>

            </div>

        </div>


        <div class="loading-footer">

            <div>

                <i class="far fa-clock"></i>

                Tiempo transcurrido

            </div>

            <strong id="counterLogistica">
                0s
            </strong>

        </div>


        <div class="loading-warning">

            <i class="fas fa-info-circle"></i>

            No cierre esta ventana mientras se procesa la información.

        </div>

    </div>

</div>


<style>
    /* =========================================================
   CONTENEDOR
========================================================= */

    .imports-wrapper {

        display: flex;

        flex-direction: column;

        gap: 22px;

    }


    /* =========================================================
   HEADER GENERAL
========================================================= */

    .imports-main-header {

        background: #ffffff;

        border: 1px solid #e5e7eb;

        border-radius: 14px;

        padding: 22px 28px;

        display: flex;

        align-items: center;

        justify-content: space-between;

        box-shadow:
            0 4px 6px rgba(0, 0, 0, .03),
            0 10px 25px rgba(0, 0, 0, .04);

    }


    .main-header-content {

        display: flex;

        align-items: center;

        gap: 15px;

    }


    .main-header-icon {

        width: 52px;

        height: 52px;

        border-radius: 12px;

        background: #f3f4f6;

        color: #374151;

        display: flex;

        align-items: center;

        justify-content: center;

        font-size: 22px;

    }


    .main-header-content h2 {

        margin: 0;

        font-size: 21px;

        font-weight: 700;

        color: #111827;

    }


    .main-header-content p {

        margin: 4px 0 0;

        color: #6b7280;

        font-size: 13px;

    }


    .system-status {

        display: flex;

        align-items: center;

        gap: 8px;

        padding: 8px 13px;

        border-radius: 20px;

        background: #f0fdf4;

        color: #15803d;

        font-size: 12px;

        font-weight: 600;

    }


    .system-status-dot {

        width: 8px;

        height: 8px;

        background: #22c55e;

        border-radius: 50%;

        box-shadow: 0 0 0 4px #dcfce7;

    }


    /* =========================================================
   MÓDULO
========================================================= */

    .import-module {

        background: #ffffff;

        border: 1px solid #e5e7eb;

        border-radius: 14px;

        overflow: hidden;

        box-shadow:
            0 4px 6px rgba(0, 0, 0, .03),
            0 10px 25px rgba(0, 0, 0, .04);

    }


    /* =========================================================
   MODULE HEADER
========================================================= */

    .module-header {

        min-height: 82px;

        padding: 18px 24px;

        display: flex;

        align-items: center;

        justify-content: space-between;

        border-bottom: 1px solid #edf0f2;

    }


    .module-title-area {

        display: flex;

        align-items: center;

        gap: 13px;

    }


    .module-icon {

        width: 46px;

        height: 46px;

        border-radius: 10px;

        display: flex;

        align-items: center;

        justify-content: center;

        font-size: 20px;

    }


    .blue-icon {

        background: #eff6ff;

        color: #2563eb;

    }


    .green-icon {

        background: #f0fdf4;

        color: #16a34a;

    }


    .module-title-area h3 {

        margin: 0;

        font-size: 17px;

        font-weight: 700;

        color: #111827;

    }


    .module-title-area p {

        margin: 4px 0 0;

        color: #6b7280;

        font-size: 12px;

    }


    .module-badge {

        padding: 7px 11px;

        border-radius: 20px;

        font-size: 11px;

        font-weight: 700;

        display: flex;

        align-items: center;

        gap: 6px;

    }


    .badge-blue {

        color: #1d4ed8;

        background: #eff6ff;

    }


    .badge-green {

        color: #15803d;

        background: #f0fdf4;

    }


    /* =========================================================
   BODY
========================================================= */

    .module-body {

        padding: 25px;

    }


    .section-label {

        display: flex;

        align-items: center;

        gap: 8px;

        color: #374151;

        font-size: 13px;

        font-weight: 700;

        margin-bottom: 11px;

    }


    .section-label i {

        color: #6b7280;

    }


    /* =========================================================
   DROPZONE
========================================================= */

    .upload-zone {

        position: relative;

        min-height: 205px;

        border: 2px dashed #d1d5db;

        border-radius: 11px;

        background: #fafafa;

        cursor: pointer;

        overflow: hidden;

        transition: all .25s ease;

    }


    .upload-zone:hover {

        transform: translateY(-1px);

    }


    .upload-zone-blue:hover,

    .upload-zone-blue.dragover {

        border-color: #3b82f6;

        background: #f8fbff;

    }


    .upload-zone-green:hover,

    .upload-zone-green.dragover {

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

        min-height: 205px;

        display: flex;

        flex-direction: column;

        align-items: center;

        justify-content: center;

        text-align: center;

    }


    .upload-icon {

        width: 58px;

        height: 58px;

        border-radius: 50%;

        display: flex;

        align-items: center;

        justify-content: center;

        font-size: 24px;

        margin-bottom: 11px;

    }


    .blue-upload-icon {

        background: #eff6ff;

        color: #2563eb;

    }


    .green-upload-icon {

        background: #f0fdf4;

        color: #16a34a;

    }


    .upload-content h4 {

        margin: 0;

        font-size: 15px;

        color: #1f2937;

        font-weight: 700;

    }


    .upload-content p {

        margin: 6px 0 13px;

        color: #6b7280;

        font-size: 12px;

    }


    .upload-button {

        display: inline-flex;

        align-items: center;

        gap: 7px;

        padding: 8px 15px;

        background: #ffffff;

        border: 1px solid #d1d5db;

        border-radius: 7px;

        color: #374151;

        font-size: 12px;

        font-weight: 600;

    }


    .supported-files {

        display: flex;

        gap: 16px;

        margin-top: 13px;

        color: #9ca3af;

        font-size: 10px;

    }


    .supported-files span {

        display: flex;

        align-items: center;

        gap: 4px;

    }


    .supported-files i {

        color: #16a34a;

    }


    /* =========================================================
   FILE INFO
========================================================= */

    .file-info {

        display: none;

        align-items: center;

        margin-top: 13px;

        padding: 12px;

        border-radius: 9px;

    }


    .file-info.show {

        display: flex;

    }


    #fileInfoOT {

        background: #eff6ff;

        border: 1px solid #bfdbfe;

    }


    #fileInfoLogistica {

        background: #f0fdf4;

        border: 1px solid #bbf7d0;

    }


    .file-info-icon {

        width: 40px;

        height: 40px;

        background: #ffffff;

        border-radius: 8px;

        display: flex;

        align-items: center;

        justify-content: center;

        font-size: 18px;

    }


    .blue-file-icon {

        color: #2563eb;

    }


    .green-file-icon {

        color: #16a34a;

    }


    .file-details {

        flex: 1;

        margin-left: 11px;

        display: flex;

        flex-direction: column;

        gap: 2px;

    }


    .file-details strong {

        font-size: 13px;

        color: #374151;

        overflow: hidden;

        text-overflow: ellipsis;

        white-space: nowrap;

        max-width: 600px;

    }


    .file-details span {

        font-size: 11px;

        color: #6b7280;

    }


    .remove-file {

        width: 32px;

        height: 32px;

        border: none;

        background: transparent;

        color: #9ca3af;

        border-radius: 6px;

        cursor: pointer;

    }


    .remove-file:hover {

        background: #fee2e2;

        color: #dc2626;

    }


    /* =========================================================
   PROCESS INFO
========================================================= */

    .process-info {

        display: grid;

        grid-template-columns: repeat(3, 1fr);

        gap: 10px;

        margin-top: 17px;

    }


    .info-item {

        display: flex;

        align-items: center;

        gap: 10px;

        padding: 12px;

        background: #f9fafb;

        border: 1px solid #f0f1f3;

        border-radius: 9px;

    }


    .info-item-icon {

        width: 34px;

        height: 34px;

        border-radius: 7px;

        background: #ffffff;

        color: #6b7280;

        display: flex;

        align-items: center;

        justify-content: center;

    }


    .info-item div:last-child {

        display: flex;

        flex-direction: column;

    }


    .info-item span {

        font-size: 10px;

        color: #9ca3af;

    }


    .info-item strong {

        margin-top: 2px;

        color: #374151;

        font-size: 12px;

    }


    /* =========================================================
   FECHA
========================================================= */

    .date-section {

        margin-bottom: 22px;

        padding: 14px;

        border-radius: 10px;

        background: #f9fafb;

        border: 1px solid #edf0f2;

        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 20px;

    }


    .date-label {

        display: flex;

        align-items: center;

        gap: 11px;

    }


    .date-icon {

        width: 40px;

        height: 40px;

        border-radius: 8px;

        background: #f0fdf4;

        color: #16a34a;

        display: flex;

        align-items: center;

        justify-content: center;

    }


    .date-label div:last-child {

        display: flex;

        flex-direction: column;

    }


    .date-label strong {

        font-size: 13px;

        color: #374151;

    }


    .date-label span {

        margin-top: 2px;

        color: #9ca3af;

        font-size: 11px;

    }


    .date-input {

        width: 170px;

        height: 39px;

        border: 1px solid #d1d5db;

        border-radius: 7px;

        background: #ffffff;

        padding: 0 10px;

        color: #374151;

        font-size: 13px;

        outline: none;

    }


    .date-input:focus {

        border-color: #22c55e;

        box-shadow: 0 0 0 3px rgba(34, 197, 94, .10);

    }


    /* =========================================================
   ACTIONS
========================================================= */

    .form-actions {

        margin-top: 20px;

        padding-top: 18px;

        border-top: 1px solid #edf0f2;

        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 20px;

    }


    .security-message {

        display: flex;

        align-items: center;

        gap: 7px;

        color: #6b7280;

        font-size: 11px;

    }


    .security-message i {

        color: #16a34a;

    }


    .btn-import {

        border: none;

        color: #ffffff;

        border-radius: 8px;

        padding: 11px 19px;

        display: inline-flex;

        align-items: center;

        gap: 8px;

        font-size: 13px;

        font-weight: 600;

        cursor: pointer;

        transition: all .2s ease;

    }


    .btn-import:disabled {

        background: #d1d5db !important;

        cursor: not-allowed;

        box-shadow: none !important;

    }


    .btn-import-blue {

        background: #2563eb;

        box-shadow: 0 4px 10px rgba(37, 99, 235, .18);

    }


    .btn-import-blue:hover:not(:disabled) {

        background: #1d4ed8;

        transform: translateY(-1px);

    }


    .btn-import-green {

        background: #16a34a;

        box-shadow: 0 4px 10px rgba(22, 163, 74, .18);

    }


    .btn-import-green:hover:not(:disabled) {

        background: #15803d;

        transform: translateY(-1px);

    }


    /* =========================================================
   OVERLAYS
========================================================= */

    #loadingOverlay,
    #loadingOverlayLogistica {

        position: fixed;

        inset: 0;

        display: none;

        z-index: 99999;

        background: rgba(17, 24, 39, .72);

        backdrop-filter: blur(5px);

        align-items: center;

        justify-content: center;

    }


    /* =========================================================
   LOADING BOX
========================================================= */

    .loading-box {

        width: 440px;

        max-width: calc(100% - 30px);

        background: #ffffff;

        border-radius: 16px;

        padding: 32px;

        text-align: center;

        box-shadow: 0 25px 60px rgba(0, 0, 0, .25);

    }


    .loading-icon {

        width: 74px;

        height: 74px;

        margin: 0 auto 14px;

        border-radius: 50%;

        display: flex;

        align-items: center;

        justify-content: center;

    }


    .loading-icon-blue {

        background: #eff6ff;

    }


    .loading-icon-green {

        background: #f0fdf4;

    }


    .loading-icon-inner {

        width: 56px;

        height: 56px;

        border-radius: 50%;

        color: #ffffff;

        display: flex;

        align-items: center;

        justify-content: center;

        font-size: 23px;

        animation: pulseImport 1.5s infinite;

    }


    .loading-icon-blue .loading-icon-inner {

        background: #2563eb;

    }


    .loading-icon-green .loading-icon-inner {

        background: #16a34a;

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


    .loading-status {

        display: inline-flex;

        align-items: center;

        gap: 6px;

        padding: 6px 10px;

        border-radius: 20px;

        font-size: 10px;

        font-weight: 700;

        text-transform: uppercase;

        letter-spacing: .4px;

    }


    .loading-status-blue {

        background: #eff6ff;

        color: #1d4ed8;

    }


    .loading-status-green {

        background: #f0fdf4;

        color: #15803d;

    }


    .loading-dot {

        width: 7px;

        height: 7px;

        border-radius: 50%;

        background: currentColor;

        animation: blink 1s infinite;

    }


    @keyframes blink {

        50% {
            opacity: .3;
        }

    }


    .loading-box h3 {

        margin: 14px 0 7px;

        color: #111827;

        font-size: 18px;

    }


    .loading-description {

        max-width: 350px;

        margin: 0 auto 24px;

        color: #6b7280;

        font-size: 12px;

        line-height: 1.6;

    }


    /* =========================================================
   PROGRESS
========================================================= */

    .progress-container {

        text-align: left;

    }


    .progress-header {

        display: flex;

        justify-content: space-between;

        margin-bottom: 7px;

        font-size: 11px;

        color: #6b7280;

    }


    .progress-header strong {

        font-size: 12px;

    }


    #loadingOverlay .progress-header strong {

        color: #2563eb;

    }


    #loadingOverlayLogistica .progress-header strong {

        color: #16a34a;

    }


    .progress-custom {

        width: 100%;

        height: 8px;

        background: #e5e7eb;

        border-radius: 20px;

        overflow: hidden;

    }


    #progressBar,
    #progressBarLogistica {

        width: 0%;

        height: 100%;

        border-radius: 20px;

        transition: width .4s ease;

    }


    #progressBar {

        background: linear-gradient(90deg,
                #2563eb,
                #3b82f6);

    }


    #progressBarLogistica {

        background: linear-gradient(90deg,
                #16a34a,
                #22c55e);

    }


    /* =========================================================
   LOADING FOOTER
========================================================= */

    .loading-footer {

        margin-top: 17px;

        padding-top: 14px;

        border-top: 1px solid #f0f1f3;

        display: flex;

        align-items: center;

        justify-content: space-between;

        font-size: 11px;

        color: #6b7280;

    }


    .loading-footer div {

        display: flex;

        align-items: center;

        gap: 6px;

    }


    .loading-footer strong {

        color: #374151;

        font-size: 12px;

    }


    .loading-warning {

        margin-top: 15px;

        padding: 8px 10px;

        border-radius: 7px;

        background: #fffbeb;

        color: #92400e;

        font-size: 9px;

        display: flex;

        align-items: center;

        justify-content: center;

        gap: 5px;

    }


    /* =========================================================
   RESPONSIVE
========================================================= */

    @media(max-width: 768px) {

        .imports-main-header {

            flex-direction: column;

            align-items: flex-start;

            gap: 15px;

        }


        .system-status {

            align-self: flex-start;

        }


        .module-header {

            align-items: flex-start;

            flex-direction: column;

            gap: 12px;

        }


        .module-badge {

            align-self: flex-start;

        }


        .process-info {

            grid-template-columns: 1fr;

        }


        .date-section {

            flex-direction: column;

            align-items: stretch;

        }


        .date-input {

            width: 100%;

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

        .module-body {

            padding: 18px;

        }


        .imports-main-header {

            padding: 18px;

        }


        .main-header-content h2 {

            font-size: 18px;

        }


        .supported-files {

            flex-direction: column;

            gap: 4px;

        }


        .loading-box {

            padding: 25px 20px;

        }

    }
</style>


<script>
    document.addEventListener('DOMContentLoaded', function() {


        /* =====================================================
           FUNCIÓN FORMATO ARCHIVO
        ====================================================== */

        function formatFileSize(bytes) {

            if (!bytes) return '0 Bytes';

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
           CONFIGURAR IMPORTACIÓN
        ====================================================== */

        function configureUpload(config) {

            const input =
                document.getElementById(config.input);

            const zone =
                document.getElementById(config.zone);

            const fileInfo =
                document.getElementById(config.fileInfo);

            const fileName =
                document.getElementById(config.fileName);

            const fileSize =
                document.getElementById(config.fileSize);

            const remove =
                document.getElementById(config.remove);

            const button =
                document.getElementById(config.button);

            const title =
                document.getElementById(config.title);

            const description =
                document.getElementById(config.description);


            function reset() {

                input.value = '';

                fileInfo.classList.remove('show');

                button.disabled = true;

                title.textContent =
                    'Seleccione el archivo Excel';

                description.textContent =
                    'Arrastre el archivo aquí o haga clic para seleccionarlo';

            }


            function processFile(file) {

                if (!file) {

                    reset();

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

                        confirmButtonColor: config.color

                    });

                    reset();

                    return;

                }


                fileName.textContent =
                    file.name;

                fileSize.textContent =
                    formatFileSize(file.size);


                fileInfo.classList.add('show');

                button.disabled = false;


                title.textContent =
                    'Archivo listo para importar';


                description.textContent =
                    'El archivo ha sido seleccionado correctamente.';

            }


            input.addEventListener(
                'change',
                function() {

                    processFile(this.files[0]);

                }
            );


            remove.addEventListener(
                'click',
                function() {

                    reset();

                }
            );


            zone.addEventListener(
                'dragover',
                function(e) {

                    e.preventDefault();

                    zone.classList.add('dragover');

                }
            );


            zone.addEventListener(
                'dragleave',
                function() {

                    zone.classList.remove('dragover');

                }
            );


            zone.addEventListener(
                'drop',
                function(e) {

                    e.preventDefault();

                    zone.classList.remove('dragover');


                    const files =
                        e.dataTransfer.files;


                    if (!files.length) return;


                    try {

                        const dataTransfer =
                            new DataTransfer();

                        dataTransfer.items.add(
                            files[0]
                        );

                        input.files =
                            dataTransfer.files;

                        processFile(files[0]);

                    } catch (error) {

                        console.error(error);

                    }

                }
            );


            return reset;

        }


        /* =====================================================
           OT
        ====================================================== */

        configureUpload({

            input: 'archivoInput',

            zone: 'uploadZoneOT',

            fileInfo: 'fileInfoOT',

            fileName: 'fileNameOT',

            fileSize: 'fileSizeOT',

            remove: 'removeFileOT',

            button: 'btnImportar',

            title: 'uploadTitleOT',

            description: 'uploadDescriptionOT',

            color: '#2563eb'

        });


        /* =====================================================
           LOGÍSTICA
        ====================================================== */

        configureUpload({

            input: 'archivoLogistica',

            zone: 'uploadZoneLogistica',

            fileInfo: 'fileInfoLogistica',

            fileName: 'fileNameLogistica',

            fileSize: 'fileSizeLogistica',

            remove: 'removeFileLogistica',

            button: 'btnImportarLogistica',

            title: 'uploadTitleLogistica',

            description: 'uploadDescriptionLogistica',

            color: '#16a34a'

        });


        /* =====================================================
           FECHA
        ====================================================== */

        const fechaProceso =
            document.getElementById('fechaProceso');

        const fechaMostrar =
            document.getElementById('fechaMostrar');


        function actualizarFecha() {

            if (!fechaProceso.value) return;


            const partes =
                fechaProceso.value.split('-');


            fechaMostrar.textContent =
                partes[2] +
                '/' +
                partes[1] +
                '/' +
                partes[0];

        }


        fechaProceso.addEventListener(
            'change',
            actualizarFecha
        );


        /* =====================================================
           IMPORTACIÓN OT
        ====================================================== */

        document
            .getElementById('formImportOT')
            .addEventListener(
                'submit',
                function() {

                    const overlay =
                        document.getElementById(
                            'loadingOverlay'
                        );

                    const button =
                        document.getElementById(
                            'btnImportar'
                        );

                    const bar =
                        document.getElementById(
                            'progressBar'
                        );

                    const percent =
                        document.getElementById(
                            'progressPercent'
                        );

                    const counter =
                        document.getElementById(
                            'counter'
                        );


                    overlay.style.display =
                        'flex';

                    button.disabled =
                        true;

                    button.innerHTML = `

                    <span class="spinner-border spinner-border-sm"></span>

                    Procesando...

                `;


                    let seconds = 0;

                    let progress = 0;


                    setInterval(function() {

                        seconds++;

                        counter.textContent =
                            seconds + 's';

                    }, 1000);


                    setInterval(function() {

                        if (progress < 92) {

                            progress = Math.min(
                                progress +
                                (Math.random() * 5 + 1),
                                92
                            );


                            bar.style.width =
                                progress + '%';


                            percent.textContent =
                                Math.floor(progress) + '%';

                        }

                    }, 700);

                }
            );


        /* =====================================================
           IMPORTACIÓN LOGÍSTICA
        ====================================================== */

        document
            .getElementById('formImportLogistica')
            .addEventListener(
                'submit',
                function() {

                    const overlay =
                        document.getElementById(
                            'loadingOverlayLogistica'
                        );

                    const button =
                        document.getElementById(
                            'btnImportarLogistica'
                        );

                    const bar =
                        document.getElementById(
                            'progressBarLogistica'
                        );

                    const percent =
                        document.getElementById(
                            'progressPercentLogistica'
                        );

                    const counter =
                        document.getElementById(
                            'counterLogistica'
                        );


                    overlay.style.display =
                        'flex';

                    button.disabled =
                        true;

                    button.innerHTML = `

                    <span class="spinner-border spinner-border-sm"></span>

                    Procesando...

                `;


                    let seconds = 0;

                    let progress = 0;


                    setInterval(function() {

                        seconds++;

                        counter.textContent =
                            seconds + 's';

                    }, 1000);


                    setInterval(function() {

                        if (progress < 92) {

                            progress = Math.min(
                                progress +
                                (Math.random() * 5 + 1),
                                92
                            );


                            bar.style.width =
                                progress + '%';


                            percent.textContent =
                                Math.floor(progress) + '%';

                        }

                    }, 700);

                }
            );

    });
</script>
