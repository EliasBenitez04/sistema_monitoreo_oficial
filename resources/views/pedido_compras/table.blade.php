{{-- =========================================================
    TABLA PEDIDOS - DISEÑO ENTERPRISE
========================================================= --}}

<div class="enterprise-table-card">

    {{-- HEADER --}}
    <div class="enterprise-table-header">

        <div class="enterprise-title">

            <div class="enterprise-title-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>

            <div>
                <h3>Pedidos de mayoristas</h3>
                <span>Gestión y seguimiento de pedidos registrados</span>
            </div>

        </div>

        <div class="enterprise-summary">

            <div class="summary-item">
                <span>Total</span>
                <strong>{{ $pedido_compras->total() }}</strong>
            </div>
            <div class="col-sm-6">
                <a href="{{ route('pedido_compras.create') }}" class="btn btn-primary float-right shadow-sm px-4 py-2"
                    style="border-radius: 8px; font-weight: 600;">
                    Nuevo Pedido
                </a>
            </div>

        </div>

    </div>


    {{-- TABLA --}}
    <div class="enterprise-table-wrapper">

        <table id="pedido_compras-table" class="enterprise-table">

            <thead>

                <tr>

                    <th class="sortable">
                        <span>Nro. Pedido</span>
                    </th>

                    <th class="sortable">
                        <span>Fecha</span>
                    </th>

                    <th class="sortable">
                        <span>Cliente</span>
                    </th>

                    <th class="sortable text-center">
                        <span>Artículos</span>
                    </th>

                    <th class="sortable text-right">
                        <span>Total</span>
                    </th>

                    <th class="sortable">
                        <span>Responsable</span>
                    </th>

                    <th class="sortable text-center">
                        <span>Estado</span>
                    </th>

                    <th>
                        <span>Observación</span>
                    </th>

                    <th class="text-center action-column">
                        <span>Acciones</span>
                    </th>

                </tr>

            </thead>


            <tbody>

                @forelse ($pedido_compras as $pedido)
                    <tr>

                        {{-- PEDIDO --}}
                        <td>

                            <div class="order-number">

                                <div class="order-icon">
                                    <i class="fas fa-file-invoice"></i>
                                </div>

                                <div>
                                    <strong>
                                        #{{ $pedido->nro_pedido }}
                                    </strong>

                                    <small>
                                        ID {{ $pedido->id_pedido }}
                                    </small>
                                </div>

                            </div>

                        </td>


                        {{-- FECHA --}}
                        <td>
                            <div class="date-cell">
                                <strong>
                                    {{ \Carbon\Carbon::parse($pedido->ped_fecha)->format('d/m/Y') }}
                                </strong>
                            </div>
                        </td>


                        {{-- CLIENTE --}}
                        <td>

                            <div class="client-cell">

                                <div class="client-avatar">
                                    <i class="fas fa-user"></i>
                                </div>

                                <div>

                                    <strong>
                                        {{ $pedido->cliente }}
                                    </strong>

                                    <small>
                                        Cliente
                                    </small>

                                </div>

                            </div>

                        </td>


                        {{-- CANTIDAD --}}
                        <td class="text-center">

                            <span class="quantity-badge">

                                <i class="fas fa-boxes"></i>

                                {{ number_format($pedido->total_cantidad, 0, ',', '.') }}

                            </span>

                        </td>


                        {{-- TOTAL --}}
                        <td class="text-right">

                            <div class="amount-cell">

                                <small>Gs.</small>

                                <strong>
                                    {{ number_format($pedido->ped_total, 0, ',', '.') }}
                                </strong>

                            </div>

                        </td>


                        {{-- USUARIO --}}
                        <td>

                            <div class="user-cell">

                                <div class="user-avatar">
                                    <i class="fas fa-user-tie"></i>
                                </div>

                                <span>
                                    {{ $pedido->usuario }}
                                </span>

                            </div>

                        </td>


                        {{-- ESTADO --}}
                        <td class="text-center">

                            @if ($pedido->ped_estado === 'CONFIRMADO')
                                <span class="enterprise-status status-success">
                                    <span></span>
                                    Confirmado
                                </span>
                            @elseif ($pedido->ped_estado === 'ANULADO')
                                <span class="enterprise-status status-danger">
                                    <span></span>
                                    Anulado
                                </span>
                            @else
                                <span class="enterprise-status status-warning">
                                    <span></span>
                                    Pendiente
                                </span>
                            @endif

                        </td>


                        {{-- OBS --}}
                        <td>

                            @if ($pedido->obs)
                                <div class="observation-cell" title="{{ $pedido->obs }}">

                                    <i class="fas fa-comment-alt"></i>

                                    <span>
                                        {{ \Illuminate\Support\Str::limit($pedido->obs, 35) }}
                                    </span>

                                </div>
                            @else
                                <span class="no-observation">
                                    Sin observación
                                </span>
                            @endif

                        </td>


                        {{-- ACCIONES --}}
                        <td class="text-center">

                            <div class="enterprise-actions">


                                {{-- CONFIRMAR --}}
                                @if ($pedido->ped_estado === 'PENDIENTE')
                                    {!! Form::open([
                                        'route' => ['pedido_compras.confirm', $pedido->id_pedido],
                                        'method' => 'patch',
                                        'id' => 'confirm-form-' . $pedido->id_pedido,
                                        'class' => 'd-inline',
                                    ]) !!}

                                    {!! Form::button('<i class="fas fa-check"></i>', [
                                        'type' => 'button',
                                        'class' => 'action-btn action-confirm alert-confirm',
                                        'data-id' => $pedido->id_pedido,
                                        'title' => 'Confirmar pedido',
                                    ]) !!}

                                    {!! Form::close() !!}
                                @endif


                                {{-- IMPRIMIR --}}
                                @if ($pedido->ped_estado === 'CONFIRMADO')
                                    <a href="{{ route('pedido_compras.imprimir', [$pedido->id_pedido]) }}"
                                        class="action-btn action-print" title="Imprimir pedido">

                                        <i class="fas fa-print"></i>

                                    </a>


                                    {{-- EXCEL --}}
                                    <a href="{{ route('pedido.export', [$pedido->id_pedido]) }}"
                                        class="action-btn action-excel" title="Exportar Excel">

                                        <i class="fas fa-file-excel"></i>

                                    </a>
                                @endif


                                {{-- EDITAR --}}
                                @if (!in_array(trim($pedido->ped_estado), ['CONFIRMADO', 'ANULADO']))
                                    <a href="{{ route('pedido_compras.edit', [$pedido->id_pedido]) }}"
                                        class="action-btn action-edit" title="Editar pedido">

                                        <i class="fas fa-pen"></i>

                                    </a>
                                @endif


                                {{-- VER --}}
                                <a href="{{ route('pedido_compras.show', [$pedido->id_pedido]) }}"
                                    class="action-btn action-view" title="Ver detalles">

                                    <i class="fas fa-eye"></i>

                                </a>


                                {{-- ANULAR --}}
                                @if ($pedido->ped_estado !== 'ANULADO')
                                    {!! Form::open([
                                        'route' => ['pedido_compras.destroy', $pedido->id_pedido],
                                        'method' => 'delete',
                                        'class' => 'd-inline',
                                        'id' => 'delete-form-' . $pedido->id_pedido,
                                    ]) !!}

                                    {!! Form::button('<i class="fas fa-trash"></i>', [
                                        'type' => 'button',
                                        'class' => 'action-btn action-delete alert-delete',
                                        'data-id' => $pedido->id_pedido,
                                        'title' => 'Anular pedido',
                                    ]) !!}

                                    {!! Form::close() !!}
                                @endif

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="9">

                            <div class="enterprise-empty">

                                <div class="empty-icon">
                                    <i class="fas fa-inbox"></i>
                                </div>

                                <h4>No hay pedidos registrados</h4>

                                <p>
                                    No se encontraron pedidos para mostrar.
                                </p>

                            </div>

                        </td>

                    </tr>
                @endforelse

            </tbody>

        </table>

    </div>


    {{-- FOOTER --}}
    @if ($pedido_compras->total() > 0)
        <div class="enterprise-table-footer">

            <div class="records-info">

                <i class="fas fa-database"></i>

                <span>
                    Mostrando
                    <strong>{{ $pedido_compras->firstItem() }}</strong>
                    -
                    <strong>{{ $pedido_compras->lastItem() }}</strong>
                    de
                    <strong>{{ $pedido_compras->total() }}</strong>
                    registros
                </span>

            </div>

            <div class="enterprise-pagination">

                {{ $pedido_compras->links() }}

            </div>

        </div>
    @endif

</div>


{{-- =========================================================
    CSS ENTERPRISE
========================================================= --}}

<style>
    /* =========================================================
       CONTENEDOR
    ========================================================= */

    .enterprise-table-card {

        width: 100%;

        background: #ffffff;

        border: 1px solid #e5e7eb;

        border-radius: 14px;

        overflow: hidden;

        box-shadow:
            0 1px 2px rgba(0, 0, 0, .03),
            0 8px 24px rgba(15, 23, 42, .05);

    }


    /* =========================================================
       HEADER
    ========================================================= */

    .enterprise-table-header {

        min-height: 76px;

        padding: 15px 20px;

        display: flex;

        align-items: center;

        justify-content: space-between;

        border-bottom: 1px solid #edf0f3;

        background: #ffffff;

    }


    .enterprise-title {

        display: flex;

        align-items: center;

        gap: 12px;

    }


    .enterprise-title-icon {

        width: 42px;
        height: 42px;

        display: flex;

        align-items: center;
        justify-content: center;

        border-radius: 10px;

        background: #eff6ff;

        color: #2563eb;

        font-size: 17px;

    }


    .enterprise-title h3 {

        margin: 0;

        font-size: 16px;

        font-weight: 700;

        color: #172033;

    }


    .enterprise-title span {

        display: block;

        margin-top: 3px;

        font-size: 11px;

        color: #94a3b8;

    }


    /* =========================================================
       SUMMARY
    ========================================================= */

    .enterprise-summary {

        display: flex;

        align-items: center;

        gap: 8px;

    }


    .summary-item {

        min-width: 75px;

        padding: 7px 11px;

        background: #f8fafc;

        border: 1px solid #e2e8f0;

        border-radius: 8px;

        text-align: center;

    }


    .summary-item span {

        display: block;

        color: #94a3b8;

        font-size: 9px;

        text-transform: uppercase;

        font-weight: 700;

        letter-spacing: .4px;

    }


    .summary-item strong {

        display: block;

        margin-top: 2px;

        color: #334155;

        font-size: 14px;

    }


    /* =========================================================
       WRAPPER
    ========================================================= */

    .enterprise-table-wrapper {

        width: 100%;

        overflow-x: auto;

    }


    /* =========================================================
       TABLE
    ========================================================= */

    .enterprise-table {

        width: 100%;

        min-width: 1200px;

        border-collapse: separate;

        border-spacing: 0;

        margin: 0 !important;

        font-size: 12px;

    }


    /* HEADER */

    .enterprise-table thead th {

        padding: 12px 14px;

        background: #f8fafc;

        color: #64748b;

        border-bottom: 1px solid #e2e8f0;

        font-size: 9px;

        font-weight: 800;

        text-transform: uppercase;

        letter-spacing: .55px;

        white-space: nowrap;

        vertical-align: middle;

    }


    .enterprise-table thead th.sortable {

        cursor: pointer;

        user-select: none;

    }


    .enterprise-table thead th.sortable:hover {

        color: #2563eb;

        background: #f1f5f9;

    }


    /* BODY */

    .enterprise-table tbody td {

        padding: 12px 14px;

        border-bottom: 1px solid #f0f2f5;

        color: #475569;

        vertical-align: middle;

        background: #ffffff;

    }


    .enterprise-table tbody tr {

        transition: all .15s ease;

    }


    .enterprise-table tbody tr:hover td {

        background: #f8fafc;

    }


    .enterprise-table tbody tr:last-child td {

        border-bottom: none;

    }


    /* =========================================================
       PEDIDO
    ========================================================= */

    .order-number {

        display: flex;

        align-items: center;

        gap: 9px;

    }


    .order-icon {

        width: 32px;
        height: 32px;

        display: flex;

        align-items: center;
        justify-content: center;

        border-radius: 8px;

        background: #eff6ff;

        color: #2563eb;

        font-size: 12px;

    }


    .order-number strong {

        display: block;

        color: #1e293b;

        font-size: 12px;

        font-weight: 750;

    }


    .order-number small {

        display: block;

        margin-top: 2px;

        color: #94a3b8;

        font-size: 9px;

    }


    /* =========================================================
       FECHA
    ========================================================= */

    .date-cell strong {

        display: block;

        color: #334155;

        font-size: 11px;

    }


    .date-cell small {

        display: block;

        margin-top: 2px;

        color: #94a3b8;

        font-size: 9px;

    }


    /* =========================================================
       CLIENTE
    ========================================================= */

    .client-cell {

        display: flex;

        align-items: center;

        gap: 9px;

        max-width: 230px;

    }


    .client-avatar,
    .user-avatar {

        width: 30px;
        height: 30px;

        flex-shrink: 0;

        display: flex;

        align-items: center;
        justify-content: center;

        border-radius: 8px;

        background: #f1f5f9;

        color: #64748b;

        font-size: 11px;

    }


    .client-cell strong {

        display: block;

        color: #334155;

        font-size: 11px;

        font-weight: 650;

        overflow: hidden;

        text-overflow: ellipsis;

        white-space: nowrap;

    }


    .client-cell small {

        display: block;

        margin-top: 2px;

        color: #94a3b8;

        font-size: 9px;

    }


    /* =========================================================
       CANTIDAD
    ========================================================= */

    .quantity-badge {

        display: inline-flex;

        align-items: center;

        gap: 5px;

        padding: 5px 8px;

        border-radius: 7px;

        background: #f1f5f9;

        border: 1px solid #e2e8f0;

        color: #334155;

        font-weight: 700;

        font-size: 10px;

    }


    .quantity-badge i {

        color: #64748b;

        font-size: 9px;

    }


    /* =========================================================
       MONTO
    ========================================================= */

    .amount-cell {

        white-space: nowrap;

    }


    .amount-cell small {

        color: #94a3b8;

        font-size: 9px;

    }


    .amount-cell strong {

        color: #172033;

        font-size: 12px;

        font-weight: 750;

    }


    /* =========================================================
       USUARIO
    ========================================================= */

    .user-cell {

        display: flex;

        align-items: center;

        gap: 8px;

        white-space: nowrap;

    }


    .user-cell span {

        color: #475569;

        font-size: 11px;

        font-weight: 600;

    }


    /* =========================================================
       ESTADOS
    ========================================================= */

    .enterprise-status {

        display: inline-flex;

        align-items: center;

        gap: 6px;

        padding: 5px 9px;

        border-radius: 20px;

        font-size: 9px;

        font-weight: 800;

        text-transform: uppercase;

        letter-spacing: .25px;

        white-space: nowrap;

    }


    .enterprise-status>span {

        width: 8px;
        height: 8px;

        border-radius: 50%;

    }


    .status-success {

        color: #166534;

        background: #f0fdf4;

        border: 1px solid #bbf7d0;

    }


    .status-success>span {

        background: #22c55e;

    }


    .status-warning {

        color: #000000;

        background: #fffbeb;

        border: 1px solid #fde68a;

    }


    .status-warning>span {

        background: #f59e0b;

    }


    .status-danger {

        color: #e40d0d;

        background: #fef2f2;

        border: 1px solid #fecaca;

    }


    .status-danger>span {

        background: #ef4444;

    }


    /* =========================================================
       OBSERVACION
    ========================================================= */

    .observation-cell {

        display: flex;

        align-items: center;

        gap: 6px;

        max-width: 190px;

        color: #030303;

        font-size: 10px;

    }


    .observation-cell i {

        color: #94a3b8;

        font-size: 9px;

    }


    .observation-cell span {

        overflow: hidden;

        text-overflow: ellipsis;

        white-space: nowrap;

    }


    .no-observation {

        color: #cbd5e1;

        font-size: 10px;

        font-style: italic;

    }


    /* =========================================================
       ACCIONES
    ========================================================= */

    .enterprise-actions {

        display: inline-flex;

        align-items: center;

        justify-content: center;

        gap: 4px;

        padding: 3px;

        background: #f8fafc;

        border: 1px solid #e2e8f0;

        border-radius: 8px;

    }


    .action-btn {

        width: 32px;
        height: 32px;

        display: inline-flex;

        align-items: center;
        justify-content: center;

        border-radius: 6px;

        border: 1px solid transparent;

        text-decoration: none !important;

        transition: all .15s ease;

        font-size: 10px;

        cursor: pointer;

    }


    .action-btn:hover {

        transform: translateY(-1px);

    }


    .action-view {

        background: #1268d8;

        color: #ffffff;

    }


    .action-view:hover {

        background: #ffffff;

        color: #1268d8;

    }


    .action-edit {

        background: #ffd900;

        color: #000000;

    }


    .action-edit:hover {

        background: #ede9fe;

        color: #ffd900;

    }


    .action-confirm {

        background: #29e46e;

        color: #ffffff;

    }


    .action-confirm:hover {

        background: #dcfce7;

        color: #29e46e;

    }


    .action-print {

        background: #c72121;

        color: #ffffff;

    }


    .action-print:hover {

        background: #ffffff;

        color: #c72121;

    }


    .action-excel {

        background: #18bb54;

        color: #ffffff;

    }


    .action-excel:hover {

        background: #ffffff;

        color: #18bb54;

    }


    .action-delete {

        background: #ff0000;

        color: #ffffff;

    }


    .action-delete:hover {

        background: #ffffff;

        color: #ff0000;

    }


    /* =========================================================
       EMPTY
    ========================================================= */

    .enterprise-empty {

        padding: 55px 20px;

        text-align: center;

    }


    .empty-icon {

        width: 52px;
        height: 52px;

        margin: 0 auto 12px;

        display: flex;

        align-items: center;
        justify-content: center;

        border-radius: 50%;

        background: #f1f5f9;

        color: #94a3b8;

        font-size: 20px;

    }


    .enterprise-empty h4 {

        margin: 0;

        color: #334155;

        font-size: 14px;

        font-weight: 700;

    }


    .enterprise-empty p {

        margin: 5px 0 0;

        color: #94a3b8;

        font-size: 11px;

    }


    /* =========================================================
       FOOTER
    ========================================================= */

    .enterprise-table-footer {

        min-height: 58px;

        padding: 10px 18px;

        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 15px;

        background: #fafbfc;

        border-top: 1px solid #edf0f3;

    }


    .records-info {

        display: flex;

        align-items: center;

        gap: 7px;

        color: #94a3b8;

        font-size: 10px;

        white-space: nowrap;

    }


    .records-info i {

        color: #64748b;

    }


    .records-info strong {

        color: #475569;

    }


    .enterprise-pagination {

        margin-left: auto;

    }


    .enterprise-pagination .pagination {

        margin: 0 !important;

    }


    .enterprise-pagination .page-link {

        border: 1px solid #e2e8f0 !important;

        color: #64748b !important;

        background: #ffffff !important;

        font-size: 12px !important;

        min-width: 30px;

        height: 30px;

        display: flex;

        align-items: center;

        justify-content: center;

    }


    .enterprise-pagination .page-item.active .page-link {

        background: #2563eb !important;

        border-color: #2563eb !important;

        color: #ffffff !important;

    }


    .enterprise-pagination .page-link:hover {

        background: #f1f5f9 !important;

        color: #2563eb !important;

    }


    /* =========================================================
       RESPONSIVE
    ========================================================= */

    @media (max-width: 768px) {

        .enterprise-table-header {

            align-items: flex-start;

            flex-direction: column;

            gap: 12px;

        }


        .enterprise-summary {

            width: 100%;

        }


        .summary-item {

            flex: 1;

        }


        .enterprise-table-footer {

            align-items: flex-start;

            flex-direction: column;

        }


        .enterprise-pagination {

            margin-left: 0;

            width: 100%;

        }

    }
</style>


{{-- =========================================================
    ORDENAMIENTO
========================================================= --}}

<script>
    document.addEventListener("DOMContentLoaded", function() {

        const table = document.getElementById("pedido_compras-table");

        if (!table) return;

        const headers = table.querySelectorAll(
            "thead th.sortable"
        );

        let currentSort = {
            index: null,
            direction: "asc"
        };


        function toNumber(value) {

            return parseFloat(

                (value || "")
                .toString()
                .replace(/[^\d,.-]/g, "")
                .replace(/\./g, "")
                .replace(",", ".")

            ) || 0;

        }


        function toDate(value) {

            if (!value) return 0;

            const parts = value.split("/");

            if (parts.length !== 3) return 0;

            return new Date(
                parts[2],
                parts[1] - 1,
                parts[0]
            ).getTime();

        }


        headers.forEach(function(th, index) {

            const icon = document.createElement("span");

            icon.className = "sort-indicator";

            icon.innerHTML = "↕";

            icon.style.marginLeft = "6px";

            icon.style.opacity = ".5";

            th.appendChild(icon);


            th.addEventListener("click", function() {

                if (currentSort.index === index) {

                    currentSort.direction =
                        currentSort.direction === "asc" ?
                        "desc" :
                        "asc";

                } else {

                    currentSort.index = index;

                    currentSort.direction = "asc";

                }


                sortTable(
                    index,
                    currentSort.direction
                );


                updateIcons(
                    index,
                    currentSort.direction
                );

            });

        });


        function sortTable(columnIndex, direction) {

            const tbody = table.querySelector("tbody");

            const rows = Array.from(
                tbody.querySelectorAll("tr")
            );


            rows.sort(function(a, b) {

                let aText =
                    a.children[columnIndex]?.innerText.trim() || "";

                let bText =
                    b.children[columnIndex]?.innerText.trim() || "";


                /* FECHA */

                if (columnIndex === 1) {

                    aText = toDate(aText);

                    bText = toDate(bText);

                }


                /* NUMEROS */
                else if (
                    columnIndex === 3 ||
                    columnIndex === 4
                ) {

                    aText = toNumber(aText);

                    bText = toNumber(bText);

                } else {

                    aText = aText.toLowerCase();

                    bText = bText.toLowerCase();

                }


                if (aText < bText)
                    return direction === "asc" ? -1 : 1;


                if (aText > bText)
                    return direction === "asc" ? 1 : -1;


                return 0;

            });


            rows.forEach(function(row) {

                tbody.appendChild(row);

            });

        }


        function updateIcons(activeIndex, direction) {

            headers.forEach(function(th, index) {

                const icon =
                    th.querySelector(".sort-indicator");

                if (!icon) return;


                if (index === activeIndex) {

                    icon.innerHTML =
                        direction === "asc" ?
                        "▲" :
                        "▼";

                    icon.style.opacity = "1";

                } else {

                    icon.innerHTML = "↕";

                    icon.style.opacity = ".5";

                }

            });

        }

    });
</script>
