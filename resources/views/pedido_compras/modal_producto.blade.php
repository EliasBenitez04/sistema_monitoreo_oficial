<!-- =========================================================
     MODAL BUSCAR PRODUCTOS PEDIDOS
========================================================= -->
<div class="modal fade" id="productSearchModalPed" tabindex="-1" role="dialog" aria-labelledby="productSearchModalPedLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-xl" role="document">

        <div class="modal-content shadow-lg border-0">

            <!-- =====================================================
                 HEADER
            ====================================================== -->
            <div class="modal-header modal-productos-header">

                <div class="d-flex align-items-center">

                    <div class="modal-header-icon">
                        <i class="fas fa-search"></i>
                    </div>

                    <div>
                        <h5 class="modal-title font-weight-bold mb-0" id="productSearchModalPedLabel">

                            Buscar Productos

                        </h5>

                        <small>
                            Seleccione un producto para agregar al pedido
                        </small>
                    </div>

                </div>

                <button type="button" class="close modal-close-btn" data-dismiss="modal" aria-label="Cerrar">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>


            <!-- =====================================================
                 BODY
            ====================================================== -->
            <div class="modal-body modal-productos-body">

                <div class="row">

                    <!-- =================================================
                         COLUMNA IZQUIERDA
                    ================================================== -->
                    <div class="col-md-8">

                        <!-- BUSCADOR -->
                        <div class="card buscador-card border-0 shadow-sm mb-3">

                            <div class="card-body p-3">

                                <label class="buscador-label mb-2">
                                    <i class="fas fa-search mr-1"></i>
                                    Buscar producto
                                </label>

                                <div class="input-group input-group-lg buscador-input">

                                    <div class="input-group-prepend">

                                        <span class="input-group-text">

                                            <i class="fas fa-search"></i>

                                        </span>

                                    </div>

                                    <input type="text" id="productSearchQueryPed" class="form-control"
                                        placeholder="Buscar por código o descripción..." autocomplete="off">

                                </div>

                                <small class="text-muted buscador-ayuda">

                                    Escriba al menos 4 caracteres para realizar la búsqueda.

                                </small>

                            </div>

                        </div>


                        <!-- =================================================
                             TABLA PRODUCTOS
                        ================================================== -->
                        <div class="card productos-card border-0 shadow-sm">

                            <div class="card-header productos-card-header">

                                <div class="d-flex align-items-center">

                                    <div class="productos-header-icon">
                                        <i class="fas fa-boxes"></i>
                                    </div>

                                    <div>

                                        <strong>
                                            Productos disponibles
                                        </strong>

                                        <small class="d-block text-muted">
                                            Seleccione el producto que desea agregar
                                        </small>

                                    </div>

                                </div>

                            </div>

                            <div class="card-body p-0">

                                <div id="modalResultsPed" class="table-responsive">

                                    <table class="table table-hover table-striped mb-0">

                                        <thead class="bg-dark text-white">
                                        </thead>

                                        <tbody>

                                            @include('pedido_compras.buscar_producto')

                                        </tbody>

                                    </table>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         COLUMNA DERECHA
                    ================================================== -->
                    <div class="col-md-4">

                        <div class="card border-0 shadow-sm resumen-pedido-card">

                            <!-- HEADER RESUMEN -->
                            <div class="resumen-header">

                                <div class="d-flex align-items-center">

                                    <div class="resumen-header-icon">

                                        <i class="fas fa-shopping-cart"></i>

                                    </div>

                                    <div>

                                        <h5 class="mb-0 font-weight-bold">
                                            Resumen del pedido
                                        </h5>

                                        <small>
                                            Configure la cantidad antes de agregar
                                        </small>

                                    </div>

                                </div>

                            </div>


                            <div class="card-body p-3">


                                <!-- =================================================
                                     CANTIDAD DE INSERCIÓN
                                ================================================== -->
                                <div class="cantidad-insercion-box">

                                    <div class="cantidad-insercion-header">

                                        <div>

                                            <span class="cantidad-insercion-label">

                                                <i class="fas fa-layer-group mr-1"></i>

                                                Cantidad de inserción

                                            </span>

                                            <small class="d-block text-muted mt-1">

                                                Cantidad que se agregará por producto.

                                            </small>

                                        </div>

                                        <span class="badge badge-primary cantidad-badge">

                                            <i class="fas fa-bolt mr-1"></i>

                                            Rápido

                                        </span>

                                    </div>


                                    <!-- CONTROL CANTIDAD -->
                                    <div class="cantidad-input-wrapper">

                                        <button type="button" class="btn cantidad-btn"
                                            onclick="cambiarCantidadInsercion(-1)">

                                            <i class="fas fa-minus"></i>

                                        </button>


                                        <input type="number" id="cantidad_multiplicador"
                                            class="cantidad-insercion-input" value="1" min="1"
                                            autocomplete="off">


                                        <button type="button" class="btn cantidad-btn"
                                            onclick="cambiarCantidadInsercion(1)">

                                            <i class="fas fa-plus"></i>

                                        </button>

                                    </div>


                                    <div class="cantidad-info">

                                        <i class="fas fa-info-circle"></i>

                                        <span>
                                            Esta cantidad se aplicará al producto seleccionado.
                                        </span>

                                    </div>

                                </div>


                                <!-- =================================================
                                     INDICADORES
                                ================================================== -->
                                <div class="row mt-3">

                                    <!-- PRODUCTOS -->
                                    <div class="col-6 pr-1">

                                        <div class="resumen-stat productos-stat">

                                            <div class="resumen-stat-icon">

                                                <i class="fas fa-boxes"></i>

                                            </div>

                                            <div class="resumen-stat-info">

                                                <span>
                                                    Productos
                                                </span>

                                                <strong id="modalCantidadProductos">
                                                    0
                                                </strong>

                                            </div>

                                        </div>

                                    </div>


                                    <!-- TOTAL -->
                                    <div class="col-6 pl-1">

                                        <div class="resumen-stat total-stat">

                                            <div class="resumen-stat-icon">

                                                <i class="fas fa-dollar-sign"></i>

                                            </div>

                                            <div class="resumen-stat-info">

                                                <span>
                                                    Total
                                                </span>

                                                <strong id="modalTotalPedido">
                                                    0
                                                </strong>

                                            </div>

                                        </div>

                                    </div>

                                </div>


                                <!-- =================================================
                                     AYUDA
                                ================================================== -->
                                <div class="resumen-ayuda mt-3">

                                    <div class="resumen-ayuda-icon">

                                        <i class="fas fa-lightbulb"></i>

                                    </div>

                                    <div>

                                        <strong>
                                            Consejo
                                        </strong>

                                        <span>
                                            Ajuste la cantidad antes de seleccionar
                                            el producto para cargarla automáticamente.
                                        </span>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     ESTILOS
========================================================= -->
<style>
    /* =========================================================
       MODAL GENERAL
    ========================================================= */

    #productSearchModalPed .modal-dialog {
        max-width: 1200px;
    }

    #productSearchModalPed .modal-content {
        border-radius: 16px;
        overflow: hidden;
        border: none;
    }

    #productSearchModalPed .modal-body {
        padding: 20px;
    }


    /* =========================================================
       HEADER MODAL
    ========================================================= */

    #productSearchModalPed .modal-productos-header {

        background: linear-gradient(135deg,
                #007bff 0%,
                #0056b3 100%);

        color: white;

        border-bottom: none;

        padding: 16px 20px;

    }


    #productSearchModalPed .modal-productos-header h5 {

        font-size: 18px;
        letter-spacing: .2px;

    }


    #productSearchModalPed .modal-productos-header small {

        opacity: .85;
        font-size: 12px;

    }


    .modal-header-icon {

        width: 44px;
        height: 44px;

        border-radius: 12px;

        background: rgba(255, 255, 255, .16);

        display: flex;
        align-items: center;
        justify-content: center;

        margin-right: 12px;

        font-size: 19px;

    }


    .modal-close-btn {

        color: white !important;

        opacity: .9;

        font-size: 30px;

        line-height: 1;

        transition: all .15s ease;

    }


    .modal-close-btn:hover {

        opacity: 1;

        transform: rotate(90deg);

    }


    /* =========================================================
       BODY
    ========================================================= */

    #productSearchModalPed .modal-productos-body {

        background: #f4f6f9;

    }


    /* =========================================================
       BUSCADOR
    ========================================================= */

    #productSearchModalPed .buscador-card {

        border-radius: 14px;

        background: #ffffff;

    }


    .buscador-label {

        display: block;

        font-weight: 700;

        color: #343a40;

        font-size: 13px;

    }


    .buscador-input {

        border-radius: 10px;

        overflow: hidden;

        border: 2px solid #e1e7ef;

        transition: all .2s ease;

    }


    .buscador-input:focus-within {

        border-color: #007bff;

        box-shadow: 0 0 0 3px rgba(0, 123, 255, .10);

    }


    .buscador-input .input-group-text {

        background: white;

        border: none;

        color: #007bff;

        padding-left: 15px;

        padding-right: 10px;

    }


    .buscador-input .form-control {

        border: none;

        box-shadow: none;

        font-size: 15px;

    }


    .buscador-input .form-control:focus {

        box-shadow: none;

    }


    .buscador-ayuda {

        display: block;

        margin-top: 7px;

        font-size: 11px;

    }


    /* =========================================================
       TABLA PRODUCTOS
    ========================================================= */

    #productSearchModalPed .productos-card {

        border-radius: 14px;

        overflow: hidden;

    }


    .productos-card-header {

        background: #ffffff;

        border-bottom: 1px solid #e9ecef;

        padding: 13px 16px;

    }


    .productos-header-icon {

        width: 38px;
        height: 38px;

        border-radius: 10px;

        background: #eaf3ff;

        color: #007bff;

        display: flex;
        align-items: center;
        justify-content: center;

        margin-right: 10px;

    }


    .productos-card-header strong {

        font-size: 14px;

        color: #343a40;

    }


    .productos-card-header small {

        font-size: 11px;

    }


    #productSearchModalPed .table {

        margin-bottom: 0;

    }


    #productSearchModalPed .table td,
    #productSearchModalPed .table th {

        vertical-align: middle;

        font-size: 13px;

        padding: 10px 12px;

    }


    #productSearchModalPed .table thead th {

        font-size: 12px;

        text-transform: uppercase;

        letter-spacing: .3px;

    }


    #productSearchModalPed .table tbody tr {

        transition: all .15s ease;

    }


    #productSearchModalPed .table tbody tr:hover {

        background: #eef5ff;

    }


    /* =========================================================
       IMPORTANTE:
       SIN SCROLL INTERNO
    ========================================================= */

    #modalResultsPed {

        max-height: none !important;

        height: auto !important;

        overflow-y: visible !important;

        overflow-x: auto;

    }


    /* =========================================================
       TARJETA RESUMEN
    ========================================================= */

    .resumen-pedido-card {

        border-radius: 14px;

        overflow: hidden;

        background: #ffffff;

    }


    /* =========================================================
       HEADER RESUMEN
    ========================================================= */

    .resumen-header {

        padding: 16px 17px;

        background: linear-gradient(135deg,
                #007bff 0%,
                #0056b3 100%);

        color: white;

    }


    .resumen-header h5 {

        font-size: 16px;

    }


    .resumen-header small {

        display: block;

        margin-top: 2px;

        font-size: 11px;

        opacity: .85;

    }


    .resumen-header-icon {

        width: 42px;
        height: 42px;

        border-radius: 11px;

        background: rgba(255, 255, 255, .17);

        display: flex;

        align-items: center;

        justify-content: center;

        margin-right: 11px;

        font-size: 18px;

    }


    /* =========================================================
       CANTIDAD INSERCIÓN
    ========================================================= */

    .cantidad-insercion-box {

        border: 1px solid #dfe5ec;

        border-radius: 13px;

        padding: 15px;

        background: #f8fafc;

    }


    .cantidad-insercion-header {

        display: flex;

        justify-content: space-between;

        align-items: flex-start;

        margin-bottom: 13px;

    }


    .cantidad-insercion-label {

        font-weight: 700;

        color: #343a40;

        font-size: 14px;

    }


    .cantidad-insercion-header small {

        font-size: 11px;

        line-height: 1.3;

    }


    .cantidad-badge {

        font-size: 10px;

        padding: 5px 8px;

        border-radius: 20px;

        white-space: nowrap;

    }


    /* =========================================================
       CONTROL CANTIDAD
    ========================================================= */

    .cantidad-input-wrapper {

        display: flex;

        align-items: center;

        justify-content: center;

        background: white;

        border: 2px solid #007bff;

        border-radius: 11px;

        overflow: hidden;

        height: 54px;

        box-shadow: 0 2px 5px rgba(0, 0, 0, .04);

    }


    .cantidad-btn {

        width: 50px;

        height: 100%;

        border: none;

        border-radius: 0;

        background: #f1f5f9;

        color: #007bff;

        font-size: 13px;

        transition: all .15s ease;

    }


    .cantidad-btn:hover {

        background: #007bff;

        color: white;

    }


    .cantidad-btn:focus {

        box-shadow: none;

    }


    .cantidad-insercion-input {

        flex: 1;

        height: 100%;

        min-width: 0;

        border: none;

        outline: none;

        text-align: center;

        font-size: 25px;

        font-weight: 700;

        color: #007bff;

        background: white;

    }


    .cantidad-insercion-input:focus {

        box-shadow: inset 0 0 0 2px rgba(0, 123, 255, .08);

    }


    /* Quitar flechas */

    .cantidad-insercion-input::-webkit-outer-spin-button,
    .cantidad-insercion-input::-webkit-inner-spin-button {

        -webkit-appearance: none;

        margin: 0;

    }


    .cantidad-insercion-input[type=number] {

        -moz-appearance: textfield;

    }


    /* =========================================================
       INFORMACIÓN CANTIDAD
    ========================================================= */

    .cantidad-info {

        display: flex;

        align-items: flex-start;

        margin-top: 9px;

        padding: 8px 9px;

        border-radius: 8px;

        background: #eef6ff;

        color: #5f6b78;

        font-size: 10px;

        line-height: 1.35;

    }


    .cantidad-info i {

        color: #007bff;

        margin-right: 6px;

        margin-top: 1px;

    }


    /* =========================================================
       ESTADÍSTICAS
    ========================================================= */

    .resumen-stat {

        min-height: 72px;

        border-radius: 11px;

        padding: 10px;

        display: flex;

        align-items: center;

        border: 1px solid #e5e9ef;

        background: white;

        transition: all .15s ease;

    }


    .resumen-stat:hover {

        transform: translateY(-1px);

        box-shadow: 0 4px 10px rgba(0, 0, 0, .05);

    }


    .resumen-stat-icon {

        width: 37px;

        height: 37px;

        border-radius: 9px;

        display: flex;

        align-items: center;

        justify-content: center;

        margin-right: 8px;

        flex-shrink: 0;

    }


    .productos-stat .resumen-stat-icon {

        background: #e8f3ff;

        color: #007bff;

    }


    .total-stat .resumen-stat-icon {

        background: #e8f8ef;

        color: #28a745;

    }


    .resumen-stat-info {

        min-width: 0;

    }


    .resumen-stat-info span {

        display: block;

        font-size: 10px;

        color: #6c757d;

        margin-bottom: 2px;

    }


    .resumen-stat-info strong {

        display: block;

        font-size: 15px;

        color: #343a40;

        white-space: nowrap;

        overflow: hidden;

        text-overflow: ellipsis;

    }


    /* =========================================================
       AYUDA
    ========================================================= */

    .resumen-ayuda {

        display: flex;

        align-items: flex-start;

        padding: 10px;

        border-radius: 9px;

        background: #f8f9fa;

        border: 1px solid #e9ecef;

        color: #66717d;

        font-size: 10px;

        line-height: 1.4;

    }


    .resumen-ayuda-icon {

        width: 25px;

        height: 25px;

        min-width: 25px;

        border-radius: 7px;

        background: #eaf3ff;

        color: #007bff;

        display: flex;

        align-items: center;

        justify-content: center;

        margin-right: 7px;

    }


    .resumen-ayuda strong {

        display: block;

        color: #343a40;

        font-size: 11px;

        margin-bottom: 1px;

    }


    .resumen-ayuda span {

        display: block;

    }


    /* =========================================================
       RESPONSIVE
    ========================================================= */

    @media (max-width: 991px) {

        #productSearchModalPed .col-md-4 {

            margin-top: 18px;

        }

    }


    @media (max-width: 767px) {

        #productSearchModalPed .modal-body {

            padding: 12px;

        }

        .cantidad-insercion-input {

            font-size: 22px;

        }

    }
</style>


@push('page_scripts')
    <script>
        /* =========================================================
           BUSCADOR DE PRODUCTOS
        ========================================================= */

        document.getElementById('productSearchQueryPed')
            .addEventListener('keyup', function() {

                let query = this.value;

                fetch(
                        '{{ url('buscar-productos-ped') }}?query=' +
                        encodeURIComponent(query) +
                        '&cod_suc=' +
                        $("#cod_suc").val()
                    )

                    .then(response => response.text())

                    .then(html => {

                        document.getElementById('modalResultsPed').innerHTML = html;

                    });

            });


        /* =========================================================
           CANTIDAD DE INSERCIÓN
        ========================================================= */

        function cambiarCantidadInsercion(valor) {

            const input = document.getElementById(
                'cantidad_multiplicador'
            );

            if (!input) return;

            let cantidad = parseInt(input.value) || 1;

            cantidad += valor;

            if (cantidad < 1) {

                cantidad = 1;

            }

            input.value = cantidad;

        }


        $('#cantidad_multiplicador').on('change', function() {

            let cantidad = parseInt(this.value) || 1;

            if (cantidad < 1) {

                cantidad = 1;

            }

            this.value = cantidad;

        });


        /* =========================================================
           SELECCIONAR PRODUCTO
        ========================================================= */

        function seleccionarProductoPed(
            codigo,
            producto,
            stock,
            precio
        ) {

            let tabla = document.getElementById(
                'selectedProducts'
            );

            if (!tabla) return;


            /* -----------------------------------------------------
               CANTIDAD DE INSERCIÓN
            ----------------------------------------------------- */

            let cantidadMultiplicador =
                parseInt(
                    document.getElementById(
                        "cantidad_multiplicador"
                    ).value
                ) || 1;


            /* -----------------------------------------------------
               EVITAR DUPLICADOS
            ----------------------------------------------------- */

            let filas =
                tabla.getElementsByTagName('tr');

            for (
                let i = 0; i < filas.length; i++
            ) {

                let inputCodigo =
                    filas[i].querySelector(
                        'input[name="codigo[]"]'
                    );

                if (
                    inputCodigo &&
                    inputCodigo.value === codigo
                ) {

                    alert(
                        'El producto ya fue agregado.'
                    );

                    return;

                }

            }


            /* -----------------------------------------------------
               CALCULAR SUBTOTAL
            ----------------------------------------------------- */

            let subtotal =
                precio * cantidadMultiplicador;


            /* -----------------------------------------------------
               CREAR FILA
            ----------------------------------------------------- */

            let row =
                document.createElement('tr');


            row.innerHTML = `

            <td class="text-center">

                <input
                    type="text"
                    name="codigo[]"
                    class="form-control text-center"
                    value="${codigo}"
                    readonly
                >

            </td>


            <td>

                <input
                    type="text"
                    name="producto[]"
                    class="form-control"
                    value="${producto}"
                    readonly
                >

            </td>


            <td>

                <input
                    type="number"
                    name="cantidad[]"
                    class="form-control text-center cantidad"
                    value="${cantidadMultiplicador}"
                    min="1"
                    max="${stock}"
                >

            </td>


            <td class="text-center">

                <input
                    type="number"
                    name="precio[]"
                    class="form-control text-center precio"
                    value="${precio}"
                >

            </td>


            <td class="text-center">

                <input
                    type="text"
                    name="subtotal[]"
                    class="form-control text-center subtotal"
                    value="${subtotal}"
                    readonly
                >

            </td>


            <td class="text-center">

                <button
                    type="button"
                    class="btn btn-danger"
                    onclick="borrarFila(this)"
                >

                    <i class="far fa-trash-alt"></i>

                </button>

            </td>

        `;


            tabla.appendChild(row);


            /* -----------------------------------------------------
               CERRAR MODAL
            ----------------------------------------------------- */

            $('#productSearchModalPed').modal('hide');


            /* -----------------------------------------------------
               RECALCULAR
            ----------------------------------------------------- */

            calcularTodo();

        }


        /* =========================================================
           RECALCULAR SUBTOTAL
        ========================================================= */

        $(document).on(
            "keyup change",
            ".cantidad, .precio",
            function() {

                let fila =
                    $(this).closest("tr");

                let cant =
                    parseFloat(
                        fila.find(".cantidad").val()
                    ) || 0;

                let precio =
                    parseFloat(
                        fila.find(".precio").val()
                    ) || 0;

                let subtotal =
                    cant * precio;

                fila.find(".subtotal")
                    .val(subtotal);

                calcularTotal();

            }
        );


        /* =========================================================
           CALCULAR TOTAL
        ========================================================= */

        function calcularTotal() {

            let total = 0;

            $(".subtotal").each(function() {

                total +=
                    parseFloat(
                        $(this).val()
                    ) || 0;

            });

            $("#ped_total").val(
                total.toLocaleString('es-PY')
            );

        }


        /* =========================================================
           BORRAR FILA
        ========================================================= */

        function borrarFila(btn) {

            let fila =
                btn.closest('tr');

            if (fila) {

                fila.remove();

            }

            calcularTodo();

        }


        /* =========================================================
           BORRAR PEDIDO
        ========================================================= */

        function borrarPed(button) {

            let row =
                button.closest('tr');

            if (row) {

                row.remove();

            }

            calcularTodo();

        }
    </script>
@endpush
