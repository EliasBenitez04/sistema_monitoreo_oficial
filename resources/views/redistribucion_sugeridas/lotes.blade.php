@include('redistribucion_sugeridas.importar-remisiones')
<style>
    :root {
        --corporate-primary: #2563eb;
        --corporate-primary-dark: #1d4ed8;
        --corporate-primary-soft: #eff6ff;

        --corporate-success: #16a34a;
        --corporate-success-soft: #f0fdf4;

        --corporate-warning: #d97706;
        --corporate-warning-soft: #fff7ed;

        --corporate-info: #0891b2;
        --corporate-info-soft: #ecfeff;

        --corporate-danger: #dc2626;
        --corporate-danger-soft: #fef2f2;

        --corporate-dark: #172033;
        --corporate-text: #334155;
        --corporate-muted: #64748b;

        --corporate-border: #e2e8f0;
        --corporate-border-light: #edf1f5;

        --corporate-bg: #f5f7fb;
        --corporate-white: #ffffff;

        --radius-sm: 8px;
        --radius-md: 12px;
        --radius-lg: 16px;

        --shadow-sm: 0 2px 8px rgba(15, 23, 42, .04);
        --shadow-md: 0 5px 18px rgba(15, 23, 42, .06);
        --shadow-lg: 0 10px 30px rgba(15, 23, 42, .09);
    }


    /* =========================================================
       ESTRUCTURA GENERAL
       ========================================================= */

    .content-wrapper {
        background: var(--corporate-bg) !important;
    }

    /* =========================================================
       NAVBAR - NO TOCAR
       Compatible con AdminLTE
       ========================================================= */

    .main-header {
        z-index: 1035 !important;
    }

    .main-header .nav-link {
        cursor: pointer;
    }

    .main-header .dropdown-menu {
        z-index: 99999 !important;
    }

    .main-header.navbar-fixed {
        position: fixed !important;
        top: 0;
        left: 0;
        right: 0;
        width: 100%;
        z-index: 1035 !important;
    }

    .main-header.navbar-fixed+.content-wrapper {
        padding-top: 57px;
    }


    /* =========================================================
       CONTENEDOR PRINCIPAL
       ========================================================= */

    .content {
        padding-bottom: 35px;
    }


    /* =========================================================
       ENCABEZADO
       ========================================================= */

    .content-header {
        padding: 24px 0 20px;
    }

    .page-heading {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .page-heading-icon {
        width: 48px;
        height: 48px;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 13px;

        background: linear-gradient(135deg,
                #2563eb 0%,
                #1d4ed8 100%);

        color: #fff;
        font-size: 20px;

        box-shadow:
            0 7px 18px rgba(37, 99, 235, .20);

        flex-shrink: 0;
    }

    .page-heading h1 {
        margin: 0;

        color: var(--corporate-dark);

        font-size: 24px;
        font-weight: 750;
        line-height: 1.2;

        letter-spacing: -.55px;
    }

    .page-heading small {
        display: block;

        margin-top: 5px;

        color: var(--corporate-muted);

        font-size: 12px;
        font-weight: 500;
        line-height: 1.4;
    }


    /* =========================================================
       BREADCRUMB
       ========================================================= */

    .content-header .breadcrumb {
        background: transparent !important;

        margin: 9px 0 0 !important;
        padding: 0 !important;

        font-size: 12px;
    }

    .content-header .breadcrumb-item {
        color: var(--corporate-muted);
    }

    .content-header .breadcrumb-item a {
        color: var(--corporate-primary);

        font-weight: 600;

        transition: color .2s ease;
    }

    .content-header .breadcrumb-item a:hover {
        color: var(--corporate-primary-dark);
        text-decoration: none;
    }

    .content-header .breadcrumb-item.active {
        color: #94a3b8;
    }


    /* =========================================================
       TARJETAS DE ESTADÍSTICAS
       ========================================================= */

    .dashboard-stat {
        position: relative;

        min-height: 122px;

        border: 1px solid var(--corporate-border);
        border-radius: var(--radius-md) !important;

        background: #fff !important;

        box-shadow: var(--shadow-md);

        overflow: hidden;

        transition:
            transform .25s ease,
            box-shadow .25s ease,
            border-color .25s ease;
    }

    .dashboard-stat::after {
        content: "";

        position: absolute;

        left: 0;
        bottom: 0;

        width: 100%;
        height: 3px;

        background: linear-gradient(90deg,
                transparent,
                rgba(37, 99, 235, .08),
                transparent);
    }

    .dashboard-stat:hover {
        transform: translateY(-4px);

        box-shadow: var(--shadow-lg);

        border-color: #d7dee9;
    }

    .dashboard-stat .inner {
        padding: 21px 20px;
    }

    .dashboard-stat h3 {
        margin: 0 0 6px;

        color: var(--corporate-dark);

        font-size: 29px;
        font-weight: 750;
        line-height: 1;

        letter-spacing: -.7px;
    }

    .dashboard-stat p {
        margin: 0;

        color: var(--corporate-muted);

        font-size: 12px;
        font-weight: 650;

        line-height: 1.4;
    }

    .dashboard-stat .stat-label {
        display: inline-flex;
        align-items: center;
        gap: 6px;

        margin-top: 13px;

        padding: 4px 8px;

        border-radius: 6px;

        font-size: 9px;
        font-weight: 750;

        text-transform: uppercase;
        letter-spacing: .55px;
    }

    .dashboard-stat .icon {
        top: 18px !important;
        right: 18px !important;

        font-size: 47px !important;

        opacity: .065 !important;

        color: var(--corporate-dark) !important;

        transition:
            transform .25s ease,
            opacity .25s ease;
    }

    .dashboard-stat:hover .icon {
        transform: scale(1.08);
        opacity: .09 !important;
    }


    /* =========================================================
       ESTADÍSTICAS - COLORES
       ========================================================= */

    .stat-warning {
        border-top: 3px solid #f59e0b !important;
    }

    .stat-warning .stat-label {
        color: #b45309;
        background: #fff7ed;
    }


    .stat-info {
        border-top: 3px solid #0891b2 !important;
    }

    .stat-info .stat-label {
        color: #0e7490;
        background: #ecfeff;
    }


    .stat-primary {
        border-top: 3px solid #2563eb !important;
    }

    .stat-primary .stat-label {
        color: #1d4ed8;
        background: #eff6ff;
    }


    .stat-success {
        border-top: 3px solid #16a34a !important;
    }

    .stat-success .stat-label {
        color: #15803d;
        background: #f0fdf4;
    }


    /* =========================================================
       TARJETAS PRINCIPALES
       ========================================================= */

    .dashboard-card {
        position: relative;

        border: 1px solid var(--corporate-border) !important;

        border-radius: var(--radius-lg) !important;

        background: #fff;

        box-shadow: var(--shadow-md) !important;

        overflow: hidden;

        margin-bottom: 24px;

        transition:
            box-shadow .25s ease,
            border-color .25s ease;
    }

    .dashboard-card:hover {
        border-color: #d9e1ec !important;

        box-shadow:
            0 7px 24px rgba(15, 23, 42, .075) !important;
    }


    /* =========================================================
       HEADER DE TARJETAS
       ========================================================= */

    .dashboard-card-header {
        min-height: 66px;

        display: flex;
        align-items: center;
        justify-content: space-between;

        padding: 14px 19px;

        background: #fff;

        border-bottom: 1px solid var(--corporate-border);
    }

    .dashboard-card-title {
        display: flex;
        align-items: center;
        gap: 11px;

        margin: 0;

        color: var(--corporate-dark);

        font-size: 14px;
        font-weight: 750;

        line-height: 1.3;
    }

    .section-icon {
        width: 36px;
        height: 36px;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 10px;

        font-size: 14px;

        flex-shrink: 0;

        box-shadow: inset 0 0 0 1px rgba(15, 23, 42, .025);
    }

    .section-icon.warning {
        background: #fff7ed;
        color: #d97706;
    }

    .section-icon.info {
        background: #ecfeff;
        color: #0891b2;
    }

    .section-icon.primary {
        background: #eff6ff;
        color: #2563eb;
    }

    .section-icon.success {
        background: #f0fdf4;
        color: #16a34a;
    }

    .dashboard-card-subtitle {
        display: block;

        margin-top: 3px;

        color: #94a3b8;

        font-size: 10px;
        font-weight: 500;

        line-height: 1.4;
    }


    /* =========================================================
       ACCIONES DEL HEADER
       ========================================================= */

    .header-actions {
        display: flex;
        align-items: center;

        gap: 7px;

        flex-wrap: wrap;
    }

    .status-counter {
        display: inline-flex;
        align-items: center;
        gap: 5px;

        padding: 5px 10px;

        border-radius: 20px;

        font-size: 10px;
        font-weight: 700;

        background: #f8fafc;
        color: #64748b;

        border: 1px solid #e2e8f0;

        white-space: nowrap;
    }

    .status-counter i {
        font-size: 9px;
    }


    /* =========================================================
       PROCESOS
       ========================================================= */

    .process-card {
        border: 1px solid #e2e8f0 !important;

        border-radius: 13px !important;

        overflow: hidden;

        margin-bottom: 15px;

        background: #fff;

        box-shadow:
            0 2px 8px rgba(15, 23, 42, .025) !important;

        transition:
            border-color .2s ease,
            box-shadow .2s ease,
            transform .2s ease;
    }

    .process-card:hover {
        border-color: #d4dce7 !important;

        box-shadow:
            0 5px 15px rgba(15, 23, 42, .055) !important;
    }


    /* =========================================================
       HEADER PROCESO
       ========================================================= */

    .process-header {
        padding: 14px 17px !important;

        background:
            linear-gradient(90deg,
                #fffdf9 0%,
                #fbfcfe 100%) !important;

        border-bottom: 1px solid #e8edf3 !important;

        border-left: 4px solid #f59e0b !important;
    }

    .process-title {
        display: flex;
        align-items: center;

        gap: 9px;

        margin: 0;

        color: #1e293b;

        font-size: 13px;
        font-weight: 750;

        line-height: 1.3;
    }

    .process-title i {
        color: #94a3b8;
        font-size: 12px;
    }

    .process-id {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        padding: 4px 8px;

        border-radius: 6px;

        background: #fff7ed;
        color: #b45309;

        font-size: 10px;
        font-weight: 750;

        border: 1px solid #fed7aa;
    }

    .process-date {
        color: #94a3b8;

        font-size: 11px;
        font-weight: 500;
    }

    .process-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;

        gap: 7px;

        flex-wrap: wrap;
    }


    /* =========================================================
       BADGE DE MOVIMIENTO
       ========================================================= */

    .movement-badge {
        display: inline-flex;
        align-items: center;

        gap: 5px;

        padding: 6px 10px;

        border-radius: 7px;

        background: #fff7ed;
        color: #b45309;

        border: 1px solid #fed7aa;

        font-size: 10px;
        font-weight: 700;

        white-space: nowrap;
    }

    .movement-badge i {
        font-size: 9px;
    }


    /* =========================================================
       TABLAS
       IMPORTANTE:
       Solamente afecta las tablas de este módulo
       ========================================================= */

    .dashboard-card .table,
    .process-card .table {
        margin: 0 !important;

        border-collapse: separate;
        border-spacing: 0;
    }


    /* =========================================================
       CABECERA TABLA
       ========================================================= */

    .dashboard-card .table thead th,
    .process-card .table thead th {
        padding: 12px 14px !important;

        background:
            linear-gradient(180deg,
                #fafbfc 0%,
                #f6f8fa 100%) !important;

        border-top: 0 !important;

        border-bottom: 1px solid #e2e8f0 !important;

        color: #64748b !important;

        font-size: 9px !important;

        font-weight: 750 !important;

        text-transform: uppercase;

        letter-spacing: .65px;

        vertical-align: middle !important;

        white-space: nowrap;
    }

    .dashboard-card .table thead th:first-child,
    .process-card .table thead th:first-child {
        padding-left: 17px !important;
    }


    /* =========================================================
       CELDAS
       ========================================================= */

    .dashboard-card .table tbody td,
    .process-card .table tbody td {
        padding: 12px 14px !important;

        color: #475569;

        border-color: #edf1f5 !important;

        font-size: 11px;

        vertical-align: middle !important;

        line-height: 1.35;
    }

    .dashboard-card .table tbody td:first-child,
    .process-card .table tbody td:first-child {
        padding-left: 17px !important;
    }


    /* =========================================================
       FILAS
       ========================================================= */

    .dashboard-card .table tbody tr,
    .process-card .table tbody tr {
        transition:
            background .15s ease,
            box-shadow .15s ease;
    }

    .dashboard-card .table tbody tr:hover,
    .process-card .table tbody tr:hover {
        background: #f8fafc !important;
    }

    .dashboard-card .table tbody tr:last-child td,
    .process-card .table tbody tr:last-child td {
        border-bottom: 0 !important;
    }


    /* =========================================================
       PRODUCTOS
       ========================================================= */

    .product-code {
        display: block;

        color: #1e293b;

        font-weight: 750;
        font-size: 11px;

        line-height: 1.3;
    }

    .product-name {
        display: block;

        margin-top: 4px;

        color: #94a3b8;

        font-size: 10px;
        font-weight: 500;

        line-height: 1.3;
    }


    /* =========================================================
       UBICACIÓN
       ========================================================= */

    .location-cell {
        display: inline-flex;
        align-items: center;

        gap: 6px;

        color: #475569;

        font-weight: 600;

        white-space: nowrap;
    }

    .location-cell i {
        color: #94a3b8;

        font-size: 10px;

        width: 12px;

        text-align: center;
    }


    /* =========================================================
       CANTIDADES
       ========================================================= */

    .quantity {
        display: inline-flex;

        min-width: 36px;

        justify-content: center;
        align-items: center;

        padding: 5px 9px;

        border-radius: 7px;

        background: #f1f5f9;

        color: #1e293b;

        font-weight: 750;

        font-size: 10px;

        border: 1px solid #e2e8f0;
    }


    /* =========================================================
       ESTADOS
       ========================================================= */

    .status-badge {
        display: inline-flex;
        align-items: center;

        gap: 5px;

        padding: 5px 9px;

        border-radius: 20px;

        font-size: 9px;
        font-weight: 750;

        text-transform: uppercase;

        letter-spacing: .3px;

        white-space: nowrap;
    }

    .status-badge i {
        font-size: 8px;
    }

    .status-badge.warning {
        color: #92400e;

        background: #fef3c7;

        border: 1px solid #fde68a;
    }

    .status-badge.info {
        color: #075985;

        background: #e0f2fe;

        border: 1px solid #bae6fd;
    }

    .status-badge.primary {
        color: #1e40af;

        background: #dbeafe;

        border: 1px solid #bfdbfe;
    }

    .status-badge.success {
        color: #166534;

        background: #dcfce7;

        border: 1px solid #bbf7d0;
    }


    /* =========================================================
       BOTONES DEL MÓDULO
       NO MODIFICA LOS BOTONES GLOBALES
       ========================================================= */

    .dashboard-card .btn,
    .process-card .btn,
    .action-group .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        border-radius: 8px !important;

        font-size: 10px !important;
        font-weight: 700 !important;

        padding: 7px 11px !important;

        min-height: 32px;

        border-width: 1px !important;

        box-shadow: none !important;

        transition:
            transform .18s ease,
            background-color .18s ease,
            border-color .18s ease,
            box-shadow .18s ease !important;
    }

    .dashboard-card .btn:hover,
    .process-card .btn:hover,
    .action-group .btn:hover {
        transform: translateY(-1px);
    }

    .dashboard-card .btn:active,
    .process-card .btn:active,
    .action-group .btn:active {
        transform: translateY(0);
    }

    .dashboard-card .btn i,
    .process-card .btn i,
    .action-group .btn i {
        margin-right: 5px;
    }


    /* =========================================================
       BOTÓN GENERAR
       ========================================================= */

    .btn-generate {
        background: #2563eb !important;
        border-color: #2563eb !important;
        color: #fff !important;

        box-shadow:
            0 3px 8px rgba(37, 99, 235, .15) !important;
    }

    .btn-generate:hover,
    .btn-generate:focus {
        background: #1d4ed8 !important;
        border-color: #1d4ed8 !important;
        color: #fff !important;

        box-shadow:
            0 5px 12px rgba(37, 99, 235, .20) !important;
    }


    /* =========================================================
       BOTÓN VER
       ========================================================= */

    .btn-view {
        background: #fff !important;

        border-color: #cbd5e1 !important;

        color: #475569 !important;
    }

    .btn-view:hover,
    .btn-view:focus {
        background: #f8fafc !important;

        border-color: #94a3b8 !important;

        color: #334155 !important;
    }


    /* =========================================================
       BOTÓN INICIAR
       ========================================================= */

    .btn-start {
        background: #16a34a !important;

        border-color: #16a34a !important;

        color: #fff !important;

        box-shadow:
            0 3px 8px rgba(22, 163, 74, .15) !important;
    }

    .btn-start:hover,
    .btn-start:focus {
        background: #15803d !important;

        border-color: #15803d !important;

        color: #fff !important;

        box-shadow:
            0 5px 12px rgba(22, 163, 74, .20) !important;
    }


    /* =========================================================
       BOTÓN FINALIZAR
       ========================================================= */

    .btn-finish {
        background: #16a34a !important;

        border-color: #16a34a !important;

        color: #fff !important;

        box-shadow:
            0 3px 8px rgba(22, 163, 74, .15) !important;
    }

    .btn-finish:hover,
    .btn-finish:focus {
        background: #15803d !important;

        border-color: #15803d !important;

        color: #fff !important;

        box-shadow:
            0 5px 12px rgba(22, 163, 74, .20) !important;
    }


    /* =========================================================
       BOTÓN CONTINUAR
       ========================================================= */

    .btn-continue {
        background: #2563eb !important;

        border-color: #2563eb !important;

        color: #fff !important;

        box-shadow:
            0 3px 8px rgba(37, 99, 235, .15) !important;
    }

    .btn-continue:hover,
    .btn-continue:focus {
        background: #1d4ed8 !important;

        border-color: #1d4ed8 !important;

        color: #fff !important;

        box-shadow:
            0 5px 12px rgba(37, 99, 235, .20) !important;
    }


    /* =========================================================
       BOTÓN SECUNDARIO
       ========================================================= */

    .dashboard-card .btn-secondary,
    .process-card .btn-secondary,
    .action-group .btn-secondary {
        background: #f8fafc !important;

        border-color: #cbd5e1 !important;

        color: #475569 !important;
    }

    .dashboard-card .btn-secondary:hover,
    .process-card .btn-secondary:hover,
    .action-group .btn-secondary:hover {
        background: #f1f5f9 !important;

        border-color: #94a3b8 !important;

        color: #334155 !important;
    }


    /* =========================================================
       GRUPO DE ACCIONES
       ========================================================= */

    .action-group {
        display: flex;

        justify-content: center;
        align-items: center;

        gap: 5px;

        flex-wrap: wrap;
    }


    /* =========================================================
       BOTÓN PDF
       ========================================================= */

    .btn-pdf {
        background-color: #dc2626 !important;

        background-image: none !important;

        border: 1px solid #dc2626 !important;

        color: #fff !important;

        box-shadow:
            0 3px 8px rgba(220, 38, 38, .15) !important;
    }

    .btn-pdf:hover,
    .btn-pdf:focus,
    .btn-pdf:active,
    .btn-pdf:visited {
        background-color: #b91c1c !important;

        background-image: none !important;

        border-color: #b91c1c !important;

        color: #fff !important;

        box-shadow:
            0 5px 12px rgba(220, 38, 38, .20) !important;
    }

    .btn-pdf i {
        color: #fff !important;
    }


    /* =========================================================
       ESTADO VACÍO
       ========================================================= */

    .empty-state {
        padding: 42px 20px;

        text-align: center;

        background:
            linear-gradient(180deg,
                #ffffff 0%,
                #fbfcfd 100%);
    }

    .empty-icon {
        width: 54px;
        height: 54px;

        margin: 0 auto 14px;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 50%;

        background: #f0fdf4;

        color: #16a34a;

        font-size: 19px;

        box-shadow:
            0 0 0 6px rgba(22, 163, 74, .045);
    }

    .empty-state h5 {
        margin: 0 0 6px;

        color: #334155;

        font-size: 13px;
        font-weight: 750;
    }

    .empty-state p {
        margin: 0;

        color: #94a3b8;

        font-size: 11px;
    }


    /* =========================================================
       SCROLLBAR TABLAS
       ========================================================= */

    .dashboard-card .table-responsive,
    .process-card .table-responsive {
        scrollbar-width: thin;

        scrollbar-color: #cbd5e1 #f8fafc;
    }

    .dashboard-card .table-responsive::-webkit-scrollbar,
    .process-card .table-responsive::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .dashboard-card .table-responsive::-webkit-scrollbar-track,
    .process-card .table-responsive::-webkit-scrollbar-track {
        background: #f8fafc;
    }

    .dashboard-card .table-responsive::-webkit-scrollbar-thumb,
    .process-card .table-responsive::-webkit-scrollbar-thumb {
        background: #cbd5e1;

        border-radius: 10px;
    }

    .dashboard-card .table-responsive::-webkit-scrollbar-thumb:hover,
    .process-card .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }


    /* =========================================================
       SWEETALERT
       ========================================================= */

    .swal2-popup {
        border-radius: 16px !important;

        padding: 25px !important;

        box-shadow:
            0 18px 45px rgba(15, 23, 42, .18) !important;
    }

    .swal2-title {
        color: #172033 !important;

        font-size: 20px !important;

        font-weight: 750 !important;
    }

    .swal2-html-container {
        color: #64748b !important;

        font-size: 13px !important;

        line-height: 1.5 !important;
    }

    .swal2-confirm,
    .swal2-cancel {
        border-radius: 8px !important;

        font-weight: 700 !important;

        padding: 9px 18px !important;
    }


    /* =========================================================
       FOCUS
       Mejora accesibilidad sin modificar navbar
       ========================================================= */

    .dashboard-card .btn:focus-visible,
    .process-card .btn:focus-visible,
    .action-group .btn:focus-visible {
        outline: none !important;

        box-shadow:
            0 0 0 3px rgba(37, 99, 235, .15) !important;
    }


    /* =========================================================
       RESPONSIVE - TABLET
       ========================================================= */

    @media (max-width: 991.98px) {

        .content-header {
            padding: 20px 0 17px;
        }

        .page-heading h1 {
            font-size: 22px;
        }

        .dashboard-stat {
            min-height: 114px;
        }

        .dashboard-stat .inner {
            padding: 18px;
        }

        .dashboard-stat h3 {
            font-size: 26px;
        }

        .dashboard-card-header {
            padding: 13px 16px;
        }

        .process-header {
            padding: 13px 15px !important;
        }
    }


    /* =========================================================
       RESPONSIVE - MÓVIL
       ========================================================= */

    @media (max-width: 768px) {

        .content-header {
            padding: 17px 0 15px;
        }

        .page-heading {
            gap: 11px;
        }

        .page-heading h1 {
            font-size: 20px;
        }

        .page-heading small {
            font-size: 11px;
        }

        .page-heading-icon {
            width: 42px;
            height: 42px;

            border-radius: 11px;

            font-size: 17px;
        }

        .content-header .breadcrumb {
            display: none;
        }

        .dashboard-stat {
            min-height: 108px;
        }

        .dashboard-stat .inner {
            padding: 17px;
        }

        .dashboard-stat h3 {
            font-size: 24px;
        }

        .dashboard-stat p {
            font-size: 11px;
        }

        .dashboard-card {
            border-radius: 13px !important;
        }

        .dashboard-card-header {
            align-items: flex-start;

            flex-direction: column;

            gap: 10px;

            padding: 13px 14px;
        }

        .header-actions {
            width: 100%;

            flex-wrap: wrap;
        }

        .process-header .row {
            gap: 8px;
        }

        .process-actions {
            justify-content: flex-start;
        }

        /*
         * La tabla conserva su tamaño y permite
         * desplazamiento horizontal.
         */
        .dashboard-card .table,
        .process-card .table {
            min-width: 700px;
        }

        .dashboard-card .table-responsive,
        .process-card .table-responsive {
            overflow-x: auto;

            -webkit-overflow-scrolling: touch;
        }

        .action-group {
            justify-content: flex-start;
        }
    }


    /* =========================================================
       RESPONSIVE - TELÉFONOS PEQUEÑOS
       ========================================================= */

    @media (max-width: 576px) {

        .content-header {
            padding: 15px 0;
        }

        .page-heading {
            gap: 9px;
        }

        .page-heading-icon {
            width: 37px;
            height: 37px;

            border-radius: 9px;

            font-size: 15px;
        }

        .page-heading h1 {
            font-size: 18px;
        }

        .page-heading small {
            font-size: 10px;
        }

        .dashboard-stat {
            min-height: 103px;

            border-radius: 11px !important;
        }

        .dashboard-stat .inner {
            padding: 15px;
        }

        .dashboard-stat h3 {
            font-size: 23px;
        }

        .dashboard-stat .icon {
            font-size: 40px !important;
        }

        .dashboard-card {
            border-radius: 11px !important;

            margin-bottom: 18px;
        }

        .dashboard-card-header {
            padding: 12px;
        }

        .dashboard-card-title {
            font-size: 13px;
        }

        .section-icon {
            width: 33px;
            height: 33px;

            border-radius: 9px;
        }

        .process-header {
            padding: 11px 12px !important;
        }

        .process-title {
            font-size: 12px;
        }

        .action-group {
            width: 100%;

            justify-content: flex-start;
        }

        .dashboard-card .btn,
        .process-card .btn,
        .action-group .btn {
            min-height: 31px;

            padding: 6px 9px !important;
        }
    }


    /* =========================================================
       REDUCCIÓN DE MOVIMIENTO
       ========================================================= */

    @media (prefers-reduced-motion: reduce) {

        .dashboard-stat,
        .dashboard-card,
        .process-card,
        .dashboard-card .btn,
        .process-card .btn,
        .action-group .btn,
        .dashboard-stat .icon {
            transition: none !important;
        }

    }

    .main-sidebar {
        min-height: 250vh !important;
    }
</style>

@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row align-items-center">

                {{-- TÍTULO --}}
                <div class="col-md-7">
                    <div class="page-heading">

                        <div class="page-heading-icon">
                            <i class="fas fa-layer-group"></i>
                        </div>

                        <div>
                            <h1>Gestión de Lotes</h1>

                            <small>
                                Control y seguimiento de movimientos de redistribución
                            </small>
                        </div>

                    </div>
                </div>


                {{-- ACCIONES + BREADCRUMB --}}
                <div class="col-md-5">

                    {{-- BOTÓN IMPORTAR --}}
                    @can('redistribucionsugerencia importarRemisiones')
                        <div class="text-md-right mb-3">

                            <button type="button" class="btn btn-import-remisiones" data-toggle="modal"
                                data-target="#modalImportarRemisiones">

                                <span class="import-btn-icon">
                                    <i class="fas fa-file-import"></i>
                                </span>

                                <span>Importar remisiones</span>

                            </button>
                        </div>
                    @endcan
                    <style>
                        .btn-import-remisiones {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            gap: 9px;

                            height: 40px;
                            padding: 0 16px;

                            background: #2563eb;
                            border: 1px solid #2563eb;
                            border-radius: 7px;

                            color: #ffffff;

                            font-size: 12px;
                            font-weight: 600;

                            box-shadow: 0 2px 5px rgba(37, 99, 235, .18);

                            transition: all .2s ease;
                        }

                        .btn-import-remisiones:hover {
                            color: #ffffff;

                            background: #1d4ed8;
                            border-color: #1d4ed8;

                            box-shadow: 0 4px 9px rgba(37, 99, 235, .24);

                            transform: translateY(-1px);
                        }

                        .btn-import-remisiones:active {
                            color: #ffffff;

                            transform: translateY(0);

                            box-shadow: 0 2px 4px rgba(37, 99, 235, .15);
                        }

                        .btn-import-remisiones:focus {
                            color: #ffffff;
                            outline: none;

                            box-shadow:
                                0 0 0 3px rgba(37, 99, 235, .12),
                                0 2px 5px rgba(37, 99, 235, .18);
                        }

                        .import-btn-icon {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;

                            width: 22px;
                            height: 22px;

                            border-radius: 5px;

                            background: rgba(255, 255, 255, .14);

                            font-size: 11px;
                        }
                    </style>


                    {{-- BREADCRUMB --}}
                    <ol class="breadcrumb float-md-right mb-0">

                        <li class="breadcrumb-item">
                            <a href="{{ route('RedistribucionSugeridas.index') }}">
                                Redistribución Sugerida
                            </a>
                        </li>

                        <li class="breadcrumb-item active">
                            Gestión de Lotes
                        </li>

                    </ol>

                </div>

            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="small-box dashboard-stat stat-warning">
                        <div class="inner">
                            <h3>{{ $procesosPendientes->sum(function ($proceso) {return $proceso->detalles->count();}) }}
                            </h3>
                            <p>Movimientos pendientes</p>
                            <div class="stat-label"><i class="fas fa-clock"></i> Requieren procesamiento</div>
                        </div>
                        <div class="icon"><i class="fas fa-clock"></i></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="small-box dashboard-stat stat-info">
                        <div class="inner">
                            <h3>{{ $lotesGenerados->count() }}</h3>
                            <p>Lotes generados</p>
                            <div class="stat-label"><i class="fas fa-box"></i> Pendientes de iniciar</div>
                        </div>
                        <div class="icon"><i class="fas fa-box"></i></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="small-box dashboard-stat stat-primary">
                        <div class="inner">
                            <h3>{{ $lotesEnProceso->count() }}</h3>
                            <p>Lotes en proceso</p>
                            <div class="stat-label"><i class="fas fa-cogs"></i> Operaciones activas</div>
                        </div>
                        <div class="icon"><i class="fas fa-cogs"></i></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="small-box dashboard-stat stat-success">
                        <div class="inner">
                            <h3>{{ $lotesFinalizados->count() }}</h3>
                            <p>Lotes finalizados</p>
                            <div class="stat-label"><i class="fas fa-check-circle"></i> Procesos completados</div>
                        </div>
                        <div class="icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div>
                        <h3 class="dashboard-card-title">
                            <span class="section-icon warning"><i class="fas fa-clock"></i></span>
                            Movimientos pendientes de generar lote
                        </h3>
                        <span class="dashboard-card-subtitle">Procesos disponibles para consolidar en un nuevo
                            lote</span>
                    </div>
                    <div class="header-actions">
                        <span class="status-counter"><i class="fas fa-layer-group"></i>
                            {{ $procesosPendientes->count() }} procesos</span>
                    </div>
                </div>
                <div class="card-body">
                    @if ($procesosPendientes->count() == 0)
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-check"></i></div>
                            <h5>Todo está al día</h5>
                            <p>No existen movimientos pendientes para generar lotes.</p>
                        </div>
                    @else
                        @foreach ($procesosPendientes as $proceso)
                            <div class="card process-card">
                                <div class="card-header process-header">
                                    <div class="row align-items-center">
                                        <div class="col-md-7">
                                            <h3 class="process-title">
                                                <span class="process-id">PROCESO #{{ $proceso->id }}</span>
                                                @if (isset($proceso->fecha))
                                                    <span class="process-date"><i
                                                            class="far fa-calendar-alt mr-1"></i>{{ \Carbon\Carbon::parse($proceso->fecha)->format('d/m/Y') }}</span>
                                                @endif
                                            </h3>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="process-actions">
                                                <span class="movement-badge"><i
                                                        class="fas fa-exchange-alt"></i>{{ $proceso->detalles->count() }}
                                                    movimientos</span>
                                                <form action="{{ route('RedistribucionSugeridas.generarLote') }}"
                                                    method="POST" class="form-generar-lote m-0">
                                                    @csrf
                                                    <input type="hidden" name="proceso_id" value="{{ $proceso->id }}">
                                                    @can('redistribucionsugerencia generarLote')
                                                        <button type="submit" class="btn btn-generate"><i
                                                                class="fas fa-layer-group"></i> Generar lote</button>
                                                    @endcan
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th width="55">#</th>
                                                    <th>Producto</th>
                                                    <th>Origen</th>
                                                    <th>Destino</th>
                                                    <th class="text-center">Cantidad</th>
                                                    <th class="text-center">Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($proceso->detalles as $detalle)
                                                    <tr>
                                                        <td><span class="text-muted">#{{ $detalle->id }}</span></td>
                                                        <td>
                                                            <span class="product-code">{{ $detalle->codigo ?? '-' }}</span>
                                                            @if (isset($detalle->producto))
                                                                <span class="product-name">{{ $detalle->producto }}</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="location-cell"><i
                                                                    class="fas fa-store"></i>{{ $detalle->origen->suc_descri ?? '-' }}</span>
                                                        </td>
                                                        <td>
                                                            <span class="location-cell"><i
                                                                    class="fas fa-store"></i>{{ $detalle->destino->suc_descri ?? '-' }}</span>
                                                        </td>
                                                        <td class="text-center"><span
                                                                class="quantity">{{ $detalle->cantidad ?? 0 }}</span>
                                                        </td>
                                                        <td class="text-center"><span class="status-badge warning"><i
                                                                    class="fas fa-clock"></i>{{ $detalle->estado }}</span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div>
                        <h3 class="dashboard-card-title">
                            <span class="section-icon info"><i class="fas fa-box"></i></span>
                            Lotes generados
                        </h3>
                        <span class="dashboard-card-subtitle">Lotes creados y pendientes de iniciar</span>
                    </div>
                    <div class="header-actions">
                        <span class="status-counter"><i class="fas fa-box"></i> {{ $lotesGenerados->count() }}
                            lotes</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if ($lotesGenerados->count() == 0)
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-box-open"></i></div>
                            <h5>No hay lotes pendientes</h5>
                            <p>Los nuevos lotes generados aparecerán aquí.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Lote</th>
                                        <th>Fecha generación</th>
                                        <th class="text-center">Movimientos</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lotesGenerados as $lote)
                                        <tr>
                                            <td>
                                                <strong class="product-code">
                                                    LOTE #{{ $lote->id }}
                                                </strong>
                                            </td>

                                            <td>
                                                <span class="location-cell">
                                                    <i class="far fa-calendar-alt"></i>
                                                    {{ \Carbon\Carbon::parse($lote->fecha_generacion)->format('d/m/Y H:i') }}
                                                </span>
                                            </td>

                                            <td class="text-center">
                                                <span class="quantity">
                                                    {{ $lote->detalles->count() }}
                                                </span>
                                            </td>

                                            <td class="text-center">
                                                <span class="status-badge info">
                                                    <i class="fas fa-box"></i>
                                                    {{ $lote->estado }}
                                                </span>
                                            </td>

                                            <td>
                                                <div class="action-group">

                                                    <div class="action-group">

                                                        {{-- VER LOTE --}}
                                                        <a href="{{ route('RedistribucionSugeridas.lote', ['id' => $lote->id]) }}"
                                                            class="btn btn-continue">
                                                            <i class="fas fa-eye"></i>
                                                            Ver lote
                                                        </a>

                                                        {{-- EXPORTAR PDF --}}
                                                        <a href="{{ route('RedistribucionSugeridas.lote.pdf', ['id' => $lote->id]) }}"
                                                            class="btn btn-pdf" target="_blank">
                                                            <i class="fas fa-file-pdf"></i> PDF
                                                        </a>

                                                        <a href="{{ route('RedistribucionSugeridas.lote.excel', ['id' => $lote->id]) }}"
                                                            class="btn btn-success">
                                                            <i class="fas fa-file-excel"></i> Excel
                                                        </a>

                                                        {{-- INICIAR --}}
                                                        @can('redistribucionsugerencia procesarLote')
                                                            <button type="button" class="btn btn-start"
                                                                onclick="iniciarLote({{ $lote->id }})">
                                                                <i class="fas fa-play"></i>
                                                                Iniciar
                                                            </button>
                                                        @endcan

                                                    </div>

                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="p-3 d-flex justify-content-end">
                                {{ $lotesGenerados->links() }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div>
                        <h3 class="dashboard-card-title">
                            <span class="section-icon primary"><i class="fas fa-cogs"></i></span>
                            Lotes en proceso
                        </h3>
                        <span class="dashboard-card-subtitle">Operaciones actualmente en ejecución</span>
                    </div>
                    <div class="header-actions">
                        <span class="status-counter"><i class="fas fa-spinner"></i> {{ $lotesEnProceso->count() }}
                            activos</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if ($lotesEnProceso->count() == 0)
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-check"></i></div>
                            <h5>No hay lotes en proceso</h5>
                            <p>No existen operaciones actualmente en ejecución.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Lote</th>
                                        <th>Fecha generación</th>
                                        <th class="text-center">Movimientos</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lotesEnProceso as $lote)
                                        <tr>
                                            <td><strong class="product-code">LOTE #{{ $lote->id }}</strong></td>
                                            <td><span class="location-cell"><i
                                                        class="far fa-calendar-alt"></i>{{ \Carbon\Carbon::parse($lote->fecha_generacion)->format('d/m/Y H:i') }}</span>
                                            </td>
                                            <td class="text-center"><span
                                                    class="quantity">{{ $lote->detalles->count() }}</span></td>
                                            <td class="text-center"><span class="status-badge primary"><i
                                                        class="fas fa-cogs"></i>{{ $lote->estado }}</span></td>
                                            <td>
                                                <div class="action-group">
                                                    <div class="action-group">
                                                        <a href="{{ route('RedistribucionSugeridas.lote', ['id' => $lote->id]) }}"
                                                            class="btn btn-continue"><i class="fas fa-eye"></i>
                                                            Ver Lote</a>
                                                        {{-- EXPORTAR PDF --}}
                                                        <a href="{{ route('RedistribucionSugeridas.lote.pdf', ['id' => $lote->id]) }}"
                                                            class="btn btn-pdf" target="_blank">
                                                            <i class="fas fa-file-pdf"></i> PDF
                                                        </a>

                                                        <a href="{{ route('RedistribucionSugeridas.lote.excel', ['id' => $lote->id]) }}"
                                                            class="btn btn-success">
                                                            <i class="fas fa-file-excel"></i> Excel
                                                        </a>
                                                        {{-- @can('redistribucionsugerencia finalizarLote')
                                                            <button type="button" class="btn btn-finish"
                                                                onclick="finalizarLote({{ $lote->id }})"><i
                                                                    class="fas fa-check"></i> Finalizar</button>
                                                        @endcan --}}
                                                    </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="p-3 d-flex justify-content-end">
                                {{ $lotesEnProceso->links() }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div>
                        <h3 class="dashboard-card-title">
                            <span class="section-icon success"><i class="fas fa-check-circle"></i></span>
                            Lotes finalizados
                        </h3>
                        <span class="dashboard-card-subtitle">Historial de operaciones completadas</span>
                    </div>
                    <div class="header-actions">
                        <span class="status-counter"><i class="fas fa-check"></i> {{ $lotesFinalizados->total() }}
                            completados</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if ($lotesFinalizados->isEmpty())
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-history"></i></div>
                            <h5>Sin historial disponible</h5>
                            <p>Los lotes finalizados aparecerán en esta sección.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Lote</th>
                                        <th>Fecha generación</th>
                                        <th class="text-center">Movimientos</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lotesFinalizados as $lote)
                                        <tr>
                                            {{-- LOTE --}}
                                            <td>
                                                <strong class="product-code">
                                                    LOTE #{{ $lote->id }}
                                                </strong>
                                            </td>

                                            {{-- FECHA --}}
                                            <td>
                                                <span class="location-cell">
                                                    <i class="far fa-calendar-alt"></i>
                                                    {{ \Carbon\Carbon::parse($lote->fecha_generacion)->format('d/m/Y H:i') }}
                                                </span>
                                            </td>

                                            {{-- MOVIMIENTOS --}}
                                            <td class="text-center">
                                                <span class="quantity">
                                                    {{ $lote->detalles->count() }}
                                                </span>
                                            </td>

                                            {{-- ESTADO --}}
                                            <td class="text-center">
                                                <span class="status-badge success">
                                                    <i class="fas fa-check-circle"></i>
                                                    {{ $lote->estado }}
                                                </span>
                                            </td>

                                            {{-- ACCIONES --}}
                                            <td>
                                                <div class="action-group">

                                                    {{-- VER LOTE --}}
                                                    <a href="{{ route('RedistribucionSugeridas.lote', ['id' => $lote->id]) }}"
                                                        class="btn btn-continue">
                                                        <i class="fas fa-eye"></i>
                                                        Ver lote
                                                    </a>

                                                    {{-- EXPORTAR PDF --}}
                                                    <a href="{{ route('RedistribucionSugeridas.lote.pdf', ['id' => $lote->id]) }}"
                                                        class="btn btn-pdf" target="_blank">
                                                        <i class="fas fa-file-pdf"></i>
                                                        PDF
                                                    </a>

                                                    {{-- EXPORTAR EXCEL --}}
                                                    <a href="{{ route('RedistribucionSugeridas.lote.excel', ['id' => $lote->id]) }}"
                                                        class="btn btn-success">
                                                        <i class="fas fa-file-excel"></i>
                                                        Excel
                                                    </a>

                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="p-3 d-flex justify-content-end">
                                {{ $lotesFinalizados->links() }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.form-generar-lote').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: '¿Generar lote?',
                        text: 'Los movimientos pendientes de este proceso serán asignados a un nuevo lote.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, generar lote',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            Swal.fire({
                                title: 'Generando lote...',
                                text: 'Por favor espere.',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                didOpen: function() {
                                    Swal.showLoading();
                                }
                            });
                            form.submit();
                        }
                    });
                });
            });
        });

        function iniciarLote(loteId) {

            Swal.fire({
                title: '¿Iniciar lote?',
                text: 'El lote pasará de GENERADO a EN PROCESO.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, iniciar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then(function(result) {

                if (!result.isConfirmed) {
                    return;
                }

                Swal.fire({
                    title: 'Procesando lote...',
                    text: 'Por favor espere.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: function() {
                        Swal.showLoading();
                    }
                });

                const csrfToken = document.querySelector(
                    'meta[name="csrf-token"]'
                );

                if (!csrfToken) {

                    Swal.fire({
                        title: 'Error de configuración',
                        text: 'No se encontró el token CSRF.',
                        icon: 'error'
                    });

                    console.error('NO EXISTE META CSRF');

                    return;
                }

                fetch(
                        "{{ url('/redistribucion-sugeridas/lote') }}/" +
                        loteId +
                        "/procesar", {
                            method: 'POST',

                            headers: {
                                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },

                            body: JSON.stringify({})
                        }
                    )

                    .then(async function(response) {

                        const texto = await response.text();

                        console.log('========== RESPUESTA SERVIDOR ==========');
                        console.log('STATUS:', response.status);
                        console.log('URL:', response.url);
                        console.log('BODY:', texto);
                        console.log('=========================================');

                        let data;

                        try {

                            data = JSON.parse(texto);

                        } catch (error) {

                            console.error(
                                'Laravel NO devolvió JSON:',
                                texto
                            );

                            throw new Error(
                                'El servidor devolvió HTML. Revisa la consola y storage/logs/laravel.log.'
                            );
                        }

                        if (!response.ok) {

                            throw new Error(
                                data.message ||
                                'Error HTTP ' + response.status
                            );
                        }

                        return data;
                    })

                    .then(function(data) {

                        console.log('JSON RECIBIDO:', data);

                        if (!data.success) {

                            throw new Error(
                                data.message ||
                                'El lote no pudo procesarse.'
                            );
                        }

                        Swal.fire({
                            title: '¡Lote iniciado!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(function() {

                            window.location.href =
                                "{{ route('RedistribucionSugeridas.lotes') }}";

                        });

                    })

                    .catch(function(error) {

                        console.error('ERROR FINAL:', error);

                        Swal.fire({
                            title: 'Error',
                            text: error.message ||
                                'Ocurrió un error al procesar el lote.',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });

                    });

            });
        }

        function finalizarLote(loteId) {
            Swal.fire({
                title: '¿Finalizar lote?',
                text: 'Una vez finalizado, el lote quedará registrado como FINALIZADO.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, finalizar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then(function(result) {
                if (!result.isConfirmed) return;
                Swal.fire({
                    title: 'Finalizando lote...',
                    text: 'Por favor espere.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: function() {
                        Swal.showLoading();
                    }
                });
                fetch("{{ url('/redistribucion-sugeridas/lote') }}/" + loteId + "/finalizar", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content'),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({})
                    })
                    .then(async function(response) {
                        const texto = await response.text();

                        console.log('RESPUESTA REAL DEL SERVIDOR:', texto);

                        let data;

                        try {
                            data = JSON.parse(texto);
                        } catch (error) {
                            console.error('NO ES JSON:', texto);

                            throw new Error('El servidor devolvió HTML en lugar de JSON.');
                        }

                        if (!response.ok) {
                            throw new Error(data.message || 'Error al procesar el lote.');
                        }

                        return data;
                    })
                    .then(function(data) {
                        Swal.fire({
                            title: '¡Lote finalizado!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(function() {
                            window.location.href = "{{ route('RedistribucionSugeridas.lotes') }}";
                        });
                    })
                    .catch(function(error) {
                        console.error(error);
                        Swal.fire({
                            title: 'Error',
                            text: error.message || 'Ocurrió un error al finalizar el lote.',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    });
            });
        }

        @if (session('success'))
            Swal.fire({
                title: '¡Éxito!',
                text: @json(session('success')),
                icon: 'success',
                confirmButtonText: 'Aceptar'
            });
        @endif

        @if (session('error'))
            Swal.fire({
                title: 'Error',
                text: @json(session('error')),
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        @endif

        @if (session('warning'))
            Swal.fire({
                title: 'Atención',
                text: @json(session('warning')),
                icon: 'warning',
                confirmButtonText: 'Aceptar'
            });
        @endif
    </script>
@endsection
