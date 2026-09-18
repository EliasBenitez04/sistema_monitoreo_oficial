<!-- Pedido Fecha Field -->
<div class="form-group col-md-4">
    {!! Form::label('nro_pedido', 'N° Pedido:') !!}
    <div class="input-group">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
        </div>
        {!! Form::text('nro_pedido', $pedido->nro_pedido ?? $nroPedidoPreview, [
            'class' => 'form-control',
            'readonly' => true,
        ]) !!}
    </div>
</div>

<div class="form-group col-sm-4">
    {!! Form::label('ped_fecha', 'Fecha:') !!}
    <div class="input-group">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
        </div>
        {!! Form::date('ped_fecha', \Carbon\Carbon::now()->format('Y-m-d'), [
            'class' => 'form-control',
            'id' => 'ped_fecha',
        ]) !!}
    </div>
</div>

<div class="form-group col-sm-4">
    {!! Form::label('user_id', 'Usuario:') !!}
    <div class="input-group">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-user"></i></span>
        </div>
        {!! Form::text('user_id', Auth::user()->name, ['class' => 'form-control', 'readonly' => 'readonly']) !!}
    </div>
</div>

<!-- Cod Suc Field -->
<div class="form-group col-sm-4">
    {!! Form::label('cod_suc', 'Sucursal:') !!}
    <div class="input-group">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-store"></i></span>
        </div>
        {!! Form::select('cod_suc', $sucursal, Auth::user()->cod_suc, [
            'class' => 'form-control',
            'id' => 'cod_suc',
            'disabled' => true,
        ]) !!}
    </div>
    {!! Form::hidden('cod_suc', Auth::user()->cod_suc) !!}
</div>

<div class="form-group col-md-4">

    {!! Form::label('id_cliente', 'Cliente') !!}

    <div class="input-group input-group-sm">

        <div class="input-group-prepend">
            <span class="input-group-text">
                <i class="fas fa-user-tie"></i>
            </span>
        </div>

        <div style="flex:1;">

            {!! Form::select('id_cliente', $clientes, $pedido->id_cliente ?? null, [
                'class' => 'form-control select2',
                'id' => 'id_cliente',
                'placeholder' => 'Seleccione un cliente',
                'required',
                'style' => 'width:100%;',
            ]) !!}

        </div>

        <!-- BOTÓN NUEVO CLIENTE -->

        <div class="input-group-append">

            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalCliente"
                title="Nuevo Cliente">

                <i class="fas fa-user-plus"></i>

            </button>

        </div>

    </div>

</div>
<div class="form-group col-sm-4">
    {!! Form::label('aplica_descuento', '¿Aplicar Descuento?', ['class' => 'font-weight-bold d-block mb-2']) !!}

    <div class="d-flex align-items-center">

        <div class="custom-control custom-radio mt-2">

            {!! Form::radio('aplica_descuento', 'SI', isset($pedido) && $pedido->descuento == 'SI', [
                'id' => 'descuento_si',
                'class' => 'custom-control-input',
            ]) !!}

            <label class="custom-control-label" for="descuento_si">
                Sí
            </label>

        </div>

        <div class="custom-control custom-radio mt-2 ml-4">

            {!! Form::radio('aplica_descuento', 'NO', !isset($pedido) || $pedido->descuento == 'NO', [
                'id' => 'descuento_no',
                'class' => 'custom-control-input',
            ]) !!}

            <label class="custom-control-label" for="descuento_no">
                No
            </label>

        </div>

    </div>
</div>

<div class="form-group col-sm-4" id="div-descuento" style="display:none;">

    {!! Form::label('descuento', 'Descuento (%)') !!}

    <div class="input-group">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-percent"></i></span>
        </div>

        {!! Form::number('descuento', isset($pedido) ? $pedido->descuento : null, [
            'class' => 'form-control',
            'min' => 0,
            'max' => 100,
            'step' => '0.01',
            'id' => 'descuento_input',
        ]) !!}
    </div>

    <small class="text-danger d-none" id="error-descuento">
        El descuento máximo permitido es 100%
    </small>

</div>

<div class="form-group col-sm-4">

    {!! Form::label('condicion', 'Condición Pedido:') !!}

    <div class="input-group">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-credit-card"></i></span>
        </div>

        {!! Form::select('condicion', $condicion, null, [
            'class' => 'form-control',
            'id' => 'condicion',
        ]) !!}
    </div>

</div>

<div class="form-group col-sm-2" id="div-intervalo" style="display:none;">

    {!! Form::label('intervalo', 'Intervalo (días):') !!}

    <div class="input-group">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-clock"></i></span>
        </div>

        {!! Form::number('intervalo', null, [
            'class' => 'form-control',
            'min' => 1,
            'id' => 'intervalo',
        ]) !!}
    </div>

</div>

<div class="form-group col-sm-2" id="div-cantcuotas" style="display:none;">

    {!! Form::label('cant_cuotas', 'Cantidad Cuotas:') !!}

    <div class="input-group">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-list-ol"></i></span>
        </div>

        {!! Form::number('cant_cuotas', null, [
            'class' => 'form-control',
            'min' => 1,
            'id' => 'cant_cuotas',
        ]) !!}
    </div>

</div>

<div class="form-group col-md-4">
    {!! Form::label('obs', 'Observación') !!}

    <div class="input-group input-group-sm">

        <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-comment"></i></span>
        </div>

        <div style="flex:1;">
            {!! Form::textarea('obs', $pedido->obs ?? null, [
                'class' => 'form-control',
                'placeholder' => 'Ingrese una observación',
                'rows' => 1,
                'style' => 'width:100%; resize:none;',
            ]) !!}
        </div>

    </div>
</div>

<!-- DETALLE COMPRAS -->
<div class="form-group col-sm-12">
    <hr>
</div>

<div class="form-group col-sm-12">
    @include('pedido_compras.detalle')
</div>
<!-- Compra Total Field -->
<div class="form-group col-sm-2">
    {!! Form::label('ped_total', 'Total:') !!}
    {!! Form::text('ped_total', isset($pedido) ? number_format($pedido->ped_total, 0, ',', '.') : null, [
        'class' => 'form-control',
        'readonly' => 'readonly',
    ]) !!}
</div>
@include('pedido_compras.modal_producto')
<style>
    .toast-grande {
        font-size: 20px;
        padding: 15px 20px;
        width: 450px !important;
    }
</style>
<!-- Agregar SweetAlert2 -->
@include('sweetalert::alert')
<button id="btnScroll" type="button" class="btn btn-primary" onclick="toggleScroll()">
    <i id="iconScroll" class="fas fa-arrow-up"></i>
</button>

<style>
    #btnScroll {
        position: fixed;
        bottom: 25px;
        right: 65px;
        z-index: 9999;
        width: 70px;
        height: 70px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
        opacity: 0;
        visibility: hidden;
        transform: translateY(20px);
        transition: all 0.3s ease;
    }

    #btnScroll.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .toast-grande {
        font-size: 20px;
        padding: 15px 20px;
        width: 450px !important;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@push('page_scripts')
    <script type="text/javascript">
        let ES_EDIT = {!! isset($pedido) ? 'true' : 'false' !!};

        $(document).ready(function() {

            $("form").keypress(function(e) {
                if (e.which == 13) return false;
            });

            // ================= EDIT =================
            if (ES_EDIT) {
                cargarDetalleEdit();

                // CARGAR DESCUENTO DESDE BD
                let descuento = parseFloat("{{ $pedido->descuento ?? 0 }}");

                if (descuento > 0) {
                    $('#descuento_si').prop('checked', true);
                    $('#div-descuento').show();
                    $('#descuento_input').val(descuento);
                } else {
                    $('#descuento_no').prop('checked', true);
                    $('#div-descuento').hide();
                    $('#descuento_input').val(0);
                }
            }

            // ================= PRODUCTOS =================
            $('#productSearchModalPed').on('show.bs.modal', function() {
                let cod_suc = $("#cod_suc").val();
                let query = $('#productSearchQueryPed').val();
                fetchProductos(query, cod_suc);
            });

            let timeout = null;

            $('#productSearchQueryPed').on('keyup', function() {

                clearTimeout(timeout);

                let query = $(this).val().trim();
                let cod_suc = $("#cod_suc").val();

                timeout = setTimeout(() => {

                    // 🔥 NO BUSCAR SI TIENE MENOS DE 4 LETRAS
                    if (query.length < 4) {

                        document.getElementById('modalResultsPed').innerHTML = `
                <div class="text-center text-muted p-3">
                    Escriba al menos 4 caracteres...
                </div>
            `;

                        return;
                    }

                    fetchProductos(query, cod_suc);

                }, 700);
            });

            // ================= EVENTOS =================
            $('#descuento_si, #descuento_no').on('change', toggleDescuento);
            $('#condicion').on('change', toggleCondicion);
            $('#descuento_input').on('keyup change', calcularTotal);

            toggleDescuento();
            toggleCondicion();
            calcularTotal();
        });


        // ================= FETCH PRODUCTOS =================
        function fetchProductos(query, cod_suc) {
            fetch('{{ url('buscar-productos-ped') }}?query=' + encodeURIComponent(query) + '&cod_suc=' + cod_suc, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.text())
                .then(html => document.getElementById('modalResultsPed').innerHTML = html);
        }


        // ================= FORMATO =================
        function formatearMiles(numero) {
            return Number(numero || 0).toLocaleString('es-PY');
        }


        // ================= DETALLE EDIT =================
        function cargarDetalleEdit() {

            let detalle = @json($detalle ?? []);
            let tabla = document.getElementById('selectedProducts');

            if (!tabla || !detalle.length) return;

            tabla.innerHTML = "";

            detalle.forEach(item => {

                let cantidad = parseInt(item.det_cantidad || 0);
                let precio = parseFloat(item.det_precio || 0);
                let subtotal = parseFloat(item.det_subtotal || (precio * cantidad));

                let row = document.createElement('tr');

                row.innerHTML = `
        <td class="text-center">
            <input name="codigo[]" value="${item.art_codigo}" readonly class="form-control form-control-sm text-center">
        </td>

        <td>
            <input name="producto[]" value="${item.art_descripcion}" readonly class="form-control form-control-sm">
        </td>

        <td class="text-center">
            <input name="cantidad[]" value="${cantidad}" class="form-control form-control-sm text-center cantidad">
        </td>

        <td class="text-center">
            <input type="hidden" class="precio_raw" value="${precio}">
            <input class="form-control form-control-sm text-center" value="${formatearMiles(precio)}" readonly>
        </td>

        <td class="text-center">
            <input class="form-control form-control-sm text-center subtotal"
                   value="${formatearMiles(subtotal)}"
                   data-value="${subtotal}"
                   readonly>
        </td>

        <td class="text-center">
    <button type="button" class="btn btn-sm btn-danger" onclick="confirmarBorrado(this)">
        <i class="far fa-trash-alt"></i>
    </button>
</td>
        `;

                tabla.appendChild(row);

                row.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                row.style.backgroundColor = '#d4edda';

                setTimeout(() => {
                    row.style.transition = 'background-color 0.5s';
                    row.style.backgroundColor = '';
                }, 800);

                if (!ES_EDIT) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Agregado',
                        timer: 800,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false
                    });
                }
            });

            calcularTodo();
        }


        // ================= AGREGAR PRODUCTO =================
        function seleccionarProductoPed(codigo, producto, precio) {

            let tabla = document.getElementById('selectedProducts');
            let cantidadMultiplicador = parseInt(document.getElementById("cantidad_multiplicador").value) || 1;

            // 🔥 VALIDAR DUPLICADO
            let existe = Array.from(tabla.querySelectorAll("input[name='codigo[]']"))
                .some(i => i.value === codigo);

            if (existe) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Producto ya agregado',
                    text: 'Solo puedes modificar la cantidad en la tabla',
                    timer: 1500,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                    customClass: {
                        popup: 'toast-grande'
                    }
                });
                return;
            }

            let subtotal = precio * cantidadMultiplicador;

            let row = document.createElement('tr');

            row.innerHTML = `
    <td class="text-center">
        <input name="codigo[]" value="${codigo}" readonly class="form-control form-control-sm text-center">
    </td>

    <td>
        <input name="producto[]" value="${producto}" readonly class="form-control form-control-sm">
    </td>

    <td class="text-center">
        <input name="cantidad[]" value="${cantidadMultiplicador}"
               class="form-control form-control-sm text-center cantidad"
               min="1">
    </td>

    <td class="text-center">
        <input type="hidden" class="precio_raw" value="${precio}">
        <input value="${formatearMiles(precio)}" readonly class="form-control form-control-sm text-center">
    </td>

    <td class="text-center">
        <input class="form-control form-control-sm text-center subtotal"
               value="${formatearMiles(subtotal)}"
               data-value="${subtotal}"
               readonly>
    </td>

    <td class="text-center">
        <button type="button" class="btn btn-sm btn-danger" onclick="borrarFila(this)">
            <i class="far fa-trash-alt"></i>
        </button>
    </td>
    `;

            tabla.appendChild(row);

            row.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            row.style.backgroundColor = '#d4edda';

            setTimeout(() => {
                row.style.transition = 'background-color 0.5s';
                row.style.backgroundColor = '';
            }, 800);

            Swal.fire({
                icon: 'success',
                title: 'Agregado',
                timer: 800,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                customClass: {
                    popup: 'toast-grande'
                }
            });

            calcularTodo();
        }


        // ================= RECALCULAR =================
        function recalcularFila(row) {

            let cantidad = parseInt(row.querySelector(".cantidad").value) || 0;
            let precio = parseFloat(row.querySelector(".precio_raw").value) || 0;

            if (cantidad < 1) cantidad = 1;

            let subtotal = cantidad * precio;

            let subtotalInput = row.querySelector(".subtotal");
            subtotalInput.dataset.value = subtotal;
            subtotalInput.value = formatearMiles(subtotal);
        }


        // ================= CALCULAR TODO =================
        function calcularTodo() {

            let totalCantidad = 0;

            document.querySelectorAll("#selectedProducts tr").forEach(row => {

                let input = row.querySelector(".cantidad");

                if (!input) return;

                let cantidad = parseInt(input.value) || 0;

                if (cantidad < 1) {
                    input.value = 1;
                    cantidad = 1;
                }

                totalCantidad += cantidad;

                recalcularFila(row);
            });

            document.getElementById("totalCantidad").innerText = totalCantidad;

            // MODAL
            let modalCantidad = document.getElementById("modalCantidadProductos");

            if (modalCantidad) {
                modalCantidad.innerText = totalCantidad;
            }

            calcularTotal();
        }


        // ================= TOTAL =================
        function calcularTotal() {

            let total = 0;

            document.querySelectorAll(".subtotal").forEach(i => {
                total += parseFloat(i.dataset.value || 0);
            });

            if ($('#descuento_si').is(':checked')) {

                let d = parseFloat($('#descuento_input').val()) || 0;

                total -= total * (d / 100);
            }

            // TOTAL DEL FORMULARIO
            document.getElementById("ped_total").value = formatearMiles(total);

            // TOTAL DEL MODAL
            let modalTotal = document.getElementById("modalTotalPedido");

            if (modalTotal) {
                modalTotal.innerText = formatearMiles(total);
            }
        }


        // ================= BORRAR =================
        function borrarFila(btn) {
            btn.closest("tr").remove();
            calcularTodo();
        }


        // ================= DESCUENTO =================
        function toggleDescuento() {
            if ($('#descuento_si').is(':checked')) {
                $('#div-descuento').show();
            } else {
                $('#div-descuento').hide();
                $('#descuento_input').val(0);
            }
            calcularTotal();
        }


        // ================= CONDICION =================
        function toggleCondicion() {
            let v = $("#condicion").val();

            if (v === "CREDITO") {
                $("#div-intervalo, #div-cantcuotas").show();
            } else {
                $("#div-intervalo, #div-cantcuotas").hide();
            }
        }


        // ================= INPUT VALIDATION =================
        document.addEventListener("input", function(e) {

            if (e.target.classList.contains("cantidad")) {

                e.target.value = e.target.value.replace(/[^0-9]/g, '');

                let row = e.target.closest("tr");

                calcularTodo();
            }
        });

        document.addEventListener("DOMContentLoaded", function() {

            const input = document.getElementById("descuento_input");
            const error = document.getElementById("error-descuento");

            if (!input) return;

            input.addEventListener("input", function() {

                let valor = parseFloat(this.value) || 0;

                // 🔥 LIMITE MAXIMO
                if (valor > 100) {
                    this.value = 100;

                    error.classList.remove("d-none");

                    Swal.fire({
                        icon: 'warning',
                        title: 'Límite excedido',
                        text: 'El descuento máximo permitido es 100%',
                        timer: 1200,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });

                } else {
                    error.classList.add("d-none");
                }

                // 🔥 LIMITE MINIMO
                if (valor < 0) {
                    this.value = 0;
                }
            });

        });

        function toggleScroll() {

            if (scrollMode === "top") {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            } else {
                window.scrollTo({
                    top: document.documentElement.scrollHeight,
                    behavior: 'smooth'
                });
            }
        }

        // Detectar posición y cambiar ícono
        window.addEventListener("scroll", function() {

            const btn = document.getElementById("btnScroll");
            const icon = document.getElementById("iconScroll");

            if (!btn || !icon) return;

            let scrollTop = window.scrollY;
            let docHeight = document.body.scrollHeight;
            let windowHeight = window.innerHeight;

            // mostrar botón
            if (scrollTop > 200) {
                btn.classList.add("show");
            } else {
                btn.classList.remove("show");
            }

            // cambiar modo
            if (scrollTop + windowHeight >= docHeight - 50) {
                scrollMode = "top";
                icon.classList.remove("fa-arrow-down");
                icon.classList.add("fa-arrow-up");
            } else {
                scrollMode = "bottom";
                icon.classList.remove("fa-arrow-up");
                icon.classList.add("fa-arrow-down");
            }
        });

        function confirmarBorrado(btn) {

            Swal.fire({
                title: '¿Eliminar producto?',
                text: "Esta acción quitará el producto del detalle",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {

                if (result.isConfirmed) {
                    borrarFila(btn);

                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminado',
                        timer: 800,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end',
                        customClass: {
                            popup: 'toast-grande'
                        }
                    });
                }
            });
        }
    </script>
@endpush
