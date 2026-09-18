<!-- ========================================================= -->
<!-- MODAL NUEVO CLIENTE - DISEÑO EMPRESARIAL -->
<!-- ========================================================= -->

<style>
    /* =========================================================
       MODAL
    ========================================================= */

    #modalCliente .modal-dialog {
        max-width: 850px;
    }

    #modalCliente .modal-content {
        border: none;
        border-radius: 10px;
        overflow: visible;
        box-shadow: 0 15px 45px rgba(0, 0, 0, .18);
    }


    /* =========================================================
       HEADER
    ========================================================= */

    #modalCliente .modal-header {
        background: #0d6efd;
        color: #fff;
        padding: 18px 24px;
        border: none;
        border-radius: 10px 10px 0 0;
    }

    #modalCliente .modal-header-content {
        display: flex;
        align-items: center;
    }

    #modalCliente .modal-header-icon {
        width: 42px;
        height: 42px;

        border-radius: 8px;

        background: rgba(255, 255, 255, .15);

        display: flex;
        align-items: center;
        justify-content: center;

        margin-right: 13px;

        font-size: 18px;
    }

    #modalCliente .modal-title {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
    }

    #modalCliente .modal-subtitle {
        font-size: 12px;
        opacity: .85;
        margin-top: 2px;
    }

    #modalCliente .close {
        color: white;
        opacity: .85;
        font-size: 24px;
        text-shadow: none;
    }

    #modalCliente .close:hover {
        opacity: 1;
    }


    /* =========================================================
       BODY
    ========================================================= */

    #modalCliente .modal-body {
        padding: 25px 28px 5px 28px;
        background: #fff;
    }


    /* =========================================================
       SECCIONES
    ========================================================= */

    #modalCliente .form-section {
        margin-bottom: 22px;
    }

    #modalCliente .section-title {
        display: flex;
        align-items: center;

        font-size: 13px;
        font-weight: 700;

        color: #343a40;

        padding-bottom: 9px;
        margin-bottom: 17px;

        border-bottom: 1px solid #e9ecef;

        text-transform: uppercase;
        letter-spacing: .4px;
    }

    #modalCliente .section-title i {
        color: #0d6efd;
        margin-right: 8px;
        font-size: 14px;
    }


    /* =========================================================
       LABELS
    ========================================================= */

    #modalCliente label {
        font-size: 13px;
        font-weight: 600;
        color: #495057;

        margin-bottom: 6px;
    }

    #modalCliente .required {
        color: #dc3545;
        margin-left: 2px;
    }


    /* =========================================================
       INPUTS
    ========================================================= */

    #modalCliente .form-control {
        height: 40px;

        border: 1px solid #ced4da;
        border-radius: 6px;

        font-size: 13px;

        padding: 8px 11px;

        transition: all .15s ease;
    }

    #modalCliente .form-control:focus {
        border-color: #80bdff;

        box-shadow: 0 0 0 .15rem rgba(13, 110, 253, .10);
    }

    #modalCliente .form-control::placeholder {
        color: #adb5bd;
    }


    /* =========================================================
       ICONOS INPUT
    ========================================================= */

    #modalCliente .input-group-text {
        background: #f8f9fa;

        border: 1px solid #ced4da;
        border-right: 0;

        color: #6c757d;

        border-radius: 6px 0 0 6px;
    }

    #modalCliente .input-group .form-control {
        border-radius: 0 6px 6px 0;
    }


    /* =========================================================
       SELECT2
    ========================================================= */

    #modalCliente .select2-container {
        width: 100% !important;
    }

    #modalCliente .select2-container--default .select2-selection--single {

        height: 40px;

        border: 1px solid #ced4da;
        border-radius: 6px;

        display: flex;
        align-items: center;

        font-size: 13px;
    }

    #modalCliente .select2-container--default .select2-selection--single .select2-selection__rendered {

        line-height: 38px;

        padding-left: 11px;

        color: #495057;
    }

    #modalCliente .select2-container--default .select2-selection--single .select2-selection__arrow {

        height: 38px;
    }

    #modalCliente .select2-container--default.select2-container--focus .select2-selection--single {

        border-color: #80bdff;

        box-shadow: 0 0 0 .15rem rgba(13, 110, 253, .10);
    }

    .select2-container--open {
        z-index: 999999 !important;
    }


    /* =========================================================
       FOOTER
    ========================================================= */

    #modalCliente .modal-footer {

        padding: 16px 28px;

        background: #f8f9fa;

        border-top: 1px solid #e9ecef;

        border-radius: 0 0 10px 10px;
    }

    #modalCliente .btn {

        height: 39px;

        border-radius: 6px;

        font-size: 13px;

        font-weight: 600;

        padding-left: 18px;
        padding-right: 18px;
    }

    #modalCliente .btn-cancelar {

        background: #fff;

        border: 1px solid #ced4da;

        color: #495057;
    }

    #modalCliente .btn-cancelar:hover {

        background: #f1f3f5;
    }

    #modalCliente .btn-guardar {

        min-width: 145px;
    }


    /* =========================================================
       RESPONSIVE
    ========================================================= */

    @media (max-width: 767px) {

        #modalCliente .modal-body {
            padding: 20px 18px 5px;
        }

        #modalCliente .modal-footer {
            padding: 14px 18px;
        }

        #modalCliente .modal-dialog {
            margin: 10px;
        }

        #modalCliente .modal-header {
            padding: 15px 18px;
        }
    }
</style>


<!-- ========================================================= -->
<!-- MODAL -->
<!-- ========================================================= -->

<div class="modal fade" id="modalCliente" tabindex="-1" role="dialog" aria-labelledby="modalClienteLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-lg" role="document">

        <div class="modal-content">


            <!-- ================================================= -->
            <!-- HEADER -->
            <!-- ================================================= -->

            <div class="modal-header">

                <div class="modal-header-content">

                    <div class="modal-header-icon">

                        <i class="fas fa-user-plus"></i>

                    </div>

                    <div>

                        <div class="modal-title" id="modalClienteLabel">

                            Nuevo Cliente

                        </div>

                        <div class="modal-subtitle">

                            Registre los datos del cliente

                        </div>

                    </div>

                </div>


                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">

                    <span aria-hidden="true">
                        &times;
                    </span>

                </button>

            </div>


            <!-- ================================================= -->
            <!-- FORMULARIO -->
            <!-- ================================================= -->

            <form method="POST" action="{{ route('clientes.store') }}" id="form-cliente-modal">

                @csrf


                <!-- ================================================= -->
                <!-- BODY -->
                <!-- ================================================= -->

                <div class="modal-body">


                    <!-- ============================================= -->
                    <!-- INFORMACIÓN PERSONAL -->
                    <!-- ============================================= -->

                    <div class="form-section">

                        <div class="section-title">

                            <i class="fas fa-user"></i>

                            Información personal

                        </div>


                        <div class="row">


                            <!-- CI -->
                            <div class="form-group col-md-4">

                                <label for="modal_cli_ci">

                                    C.I. / R.U.C.

                                    <span class="required">*</span>

                                </label>


                                <div class="input-group">

                                    <div class="input-group-prepend">

                                        <span class="input-group-text">

                                            <i class="fas fa-id-card"></i>

                                        </span>

                                    </div>


                                    <input type="text" name="cli_ci" id="modal_cli_ci" class="form-control"
                                        placeholder="Ej. 1234567" autocomplete="off" required>

                                </div>

                            </div>


                            <!-- NOMBRE -->
                            <div class="form-group col-md-4">
                                <label for="modal_cli_nombre">
                                    Nombre
                                    <span class="required">*</span>
                                </label>

                                <input type="text" name="cli_nombre" id="modal_cli_nombre" class="form-control"
                                    placeholder="Ingrese el nombre" autocomplete="off" required>
                            </div>
                            <!-- APELLIDO -->
                            <div class="form-group col-md-4">
                                <label for="modal_cli_apellido">
                                    Apellido
                                </label>
                                <input type="text" name="cli_apellido" id="modal_cli_apellido" class="form-control"
                                    placeholder="Ingrese el apellido" autocomplete="off">
                            </div>
                        </div>
                    </div>

                    <!-- ============================================= -->
                    <!-- INFORMACIÓN DE CONTACTO -->
                    <!-- ============================================= -->

                    <div class="form-section">

                        <div class="section-title">

                            <i class="fas fa-address-book"></i>

                            Información de contacto

                        </div>


                        <div class="row">


                            <!-- DIRECCIÓN -->
                            <div class="form-group col-md-8">

                                <label for="modal_cli_direccion">

                                    Dirección

                                    <span class="required">*</span>

                                </label>


                                <div class="input-group">

                                    <div class="input-group-prepend">

                                        <span class="input-group-text">

                                            <i class="fas fa-map-marker-alt"></i>

                                        </span>

                                    </div>


                                    <input type="text" name="cli_direccion" id="modal_cli_direccion"
                                        class="form-control" placeholder="Ingrese la dirección" autocomplete="off"
                                        required>

                                </div>

                            </div>


                            <!-- TELÉFONO -->
                            <div class="form-group col-md-4">

                                <label for="modal_cli_telefono">

                                    Teléfono

                                    <span class="required">*</span>

                                </label>


                                <div class="input-group">

                                    <div class="input-group-prepend">

                                        <span class="input-group-text">

                                            <i class="fas fa-phone"></i>

                                        </span>

                                    </div>


                                    <input type="text" name="cli_telefono" id="modal_cli_telefono"
                                        class="form-control" placeholder="Ej. 0981..." autocomplete="off" required>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- ============================================= -->
                    <!-- UBICACIÓN -->
                    <!-- ============================================= -->

                    <div class="form-section">

                        <div class="section-title">

                            <i class="fas fa-map-marked-alt"></i>

                            Ubicación

                        </div>


                        <div class="row">


                            <!-- DEPARTAMENTO -->
                            <div class="form-group col-md-6">

                                <label for="modal_id_departamento">

                                    Departamento

                                    <span class="required">*</span>

                                </label>


                                <select name="id_departamento" id="modal_id_departamento" class="form-control select2"
                                    style="width: 100%;" required>

                                    <option value="">
                                        Seleccione departamento...
                                    </option>


                                    @foreach ($departamento as $id => $descripcion)
                                        <option value="{{ $id }}">

                                            {{ $descripcion }}

                                        </option>
                                    @endforeach

                                </select>

                            </div>


                            <!-- CIUDAD -->
                            <div class="form-group col-md-6">

                                <label for="modal_id_ciudad">

                                    Ciudad

                                    <span class="required">*</span>

                                </label>


                                <select name="id_ciudad" id="modal_id_ciudad" class="form-control select2"
                                    style="width: 100%;" required>

                                    <option value="">
                                        Seleccione ciudad...
                                    </option>


                                    @foreach ($ciudad as $id => $descripcion)
                                        <option value="{{ $id }}">

                                            {{ $descripcion }}

                                        </option>
                                    @endforeach

                                </select>

                            </div>

                        </div>

                    </div>


                </div>


                <!-- ================================================= -->
                <!-- FOOTER -->
                <!-- ================================================= -->

                <div class="modal-footer">


                    <button type="button" class="btn btn-cancelar" data-dismiss="modal">

                        <i class="fas fa-times mr-1"></i>

                        Cancelar

                    </button>


                    <button type="submit" id="btn-guardar-cliente" class="btn btn-success btn-guardar">

                        <i class="fas fa-save mr-1"></i>

                        Guardar cliente

                    </button>


                </div>


            </form>

        </div>

    </div>

</div>


<!-- ========================================================= -->
<!-- JAVASCRIPT -->
<!-- ========================================================= -->
<script>
    console.log('SCRIPT CLIENTE CARGADO');

    $(document).ready(function() {

        console.log('DOCUMENT READY');

        $(document).on('submit', '#form-cliente-modal', function(e) {

            console.log('SUBMIT MODAL DETECTADO');

            e.preventDefault();
        });
    });

    $(document).ready(function() {

        /* =========================================================
           SELECT2 - DEPARTAMENTO
        ========================================================= */

        $('#modal_id_departamento').select2({
            width: '100%',
            dropdownParent: $('#modalCliente'),
            placeholder: 'Seleccione departamento',
            allowClear: true
        });


        /* =========================================================
           SELECT2 - CIUDAD
        ========================================================= */

        $('#modal_id_ciudad').select2({
            width: '100%',
            dropdownParent: $('#modalCliente'),
            placeholder: 'Seleccione ciudad',
            allowClear: true
        });


        /* =========================================================
           SUBMIT FORMULARIO CLIENTE
        ========================================================= */

        $(document).on('submit', '#form-cliente-modal', function(e) {

            e.preventDefault();

            let form = this;
            let boton = $('#btn-guardar-cliente');


            /* =====================================================
               VALIDACIÓN MANUAL
            ===================================================== */

            let cli_ci = $('#modal_cli_ci').val().trim();
            let cli_nombre = $('#modal_cli_nombre').val().trim();
            let cli_direccion = $('#modal_cli_direccion').val().trim();
            let cli_telefono = $('#modal_cli_telefono').val().trim();

            let id_departamento = $('#modal_id_departamento').val();
            let id_ciudad = $('#modal_id_ciudad').val();


            /* =====================================================
               VALIDAR CI
            ===================================================== */

            if (cli_ci === '') {

                Swal.fire({
                    icon: 'warning',
                    title: 'Campo obligatorio',
                    text: 'Debe ingresar el C.I. / R.U.C.'
                });

                $('#modal_cli_ci').focus();

                return;
            }


            /* =====================================================
               VALIDAR NOMBRE
            ===================================================== */

            if (cli_nombre === '') {

                Swal.fire({
                    icon: 'warning',
                    title: 'Campo obligatorio',
                    text: 'Debe ingresar el nombre del cliente.'
                });

                $('#modal_cli_nombre').focus();

                return;
            }


            /* =====================================================
               VALIDAR DIRECCIÓN
            ===================================================== */

            if (cli_direccion === '') {

                Swal.fire({
                    icon: 'warning',
                    title: 'Campo obligatorio',
                    text: 'Debe ingresar la dirección.'
                });

                $('#modal_cli_direccion').focus();

                return;
            }


            /* =====================================================
               VALIDAR TELÉFONO
            ===================================================== */

            if (cli_telefono === '') {

                Swal.fire({
                    icon: 'warning',
                    title: 'Campo obligatorio',
                    text: 'Debe ingresar el teléfono.'
                });

                $('#modal_cli_telefono').focus();

                return;
            }


            /* =====================================================
               VALIDAR DEPARTAMENTO
            ===================================================== */

            if (!id_departamento) {

                Swal.fire({
                    icon: 'warning',
                    title: 'Campo obligatorio',
                    text: 'Debe seleccionar un departamento.'
                });

                $('#modal_id_departamento').select2('open');

                return;
            }


            /* =====================================================
               VALIDAR CIUDAD
            ===================================================== */

            if (!id_ciudad) {

                Swal.fire({
                    icon: 'warning',
                    title: 'Campo obligatorio',
                    text: 'Debe seleccionar una ciudad.'
                });

                $('#modal_id_ciudad').select2('open');

                return;
            }


            /* =====================================================
               DESHABILITAR BOTÓN
            ===================================================== */

            boton.prop('disabled', true);

            boton.html(
                '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...'
            );


            /* =====================================================
               AJAX
            ===================================================== */

            $.ajax({

                url: $(form).attr('action'),

                type: 'POST',

                data: $(form).serialize(),

                dataType: 'json',

                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },


                /* =================================================
                   ÉXITO
                ================================================= */

                success: function(response) {

                    console.log('RESPUESTA:', response);


                    if (response.success && response.cliente) {

                        let cliente = response.cliente;


                        /* =========================================
                           NOMBRE DEL CLIENTE
                        ========================================= */

                        let nombreCliente =
                            cliente.cli_ci +
                            ' - ' +
                            cliente.cli_nombre +
                            ' ' +
                            (cliente.cli_apellido || '');


                        /* =========================================
                           AGREGAR AL SELECT DE CLIENTES
                        ========================================= */

                        let option = new Option(
                            nombreCliente,
                            cliente.id_cliente,
                            true,
                            true
                        );


                        $('#id_cliente')
                            .append(option)
                            .trigger('change');


                        /* =========================================
                           CERRAR MODAL
                        ========================================= */

                        $('#modalCliente').modal('hide');


                        /* =========================================
                           LIMPIAR FORMULARIO
                        ========================================= */

                        form.reset();


                        $('#modal_id_departamento')
                            .val(null)
                            .trigger('change');


                        $('#modal_id_ciudad')
                            .val(null)
                            .trigger('change');


                        /* =========================================
                           MENSAJE
                        ========================================= */

                        Swal.fire({

                            icon: 'success',

                            title: 'Cliente creado',

                            text: 'El cliente fue registrado correctamente.',

                            timer: 1800,

                            showConfirmButton: false,

                            toast: true,

                            position: 'top-end'

                        });


                    } else {

                        Swal.fire({

                            icon: 'warning',

                            title: 'Atención',

                            text: response.message ||
                                'No fue posible registrar el cliente.'

                        });

                    }

                },


                /* =================================================
                   ERROR
                ================================================= */

                error: function(xhr) {

                    console.log('ERROR AJAX:', xhr);

                    console.log(
                        'RESPUESTA:',
                        xhr.responseText
                    );


                    let mensaje =
                        'Ocurrió un error al guardar el cliente.';


                    /* =============================================
                       ERROR JSON
                    ============================================= */

                    if (
                        xhr.responseJSON &&
                        xhr.responseJSON.message
                    ) {

                        mensaje =
                            xhr.responseJSON.message;

                    }


                    /* =============================================
                       ERROR 422
                    ============================================= */

                    if (
                        xhr.status === 422 &&
                        xhr.responseJSON &&
                        xhr.responseJSON.errors
                    ) {

                        let errores = [];


                        $.each(
                            xhr.responseJSON.errors,
                            function(campo, mensajes) {

                                errores.push(
                                    mensajes[0]
                                );

                            }
                        );


                        mensaje = errores.join('\n');

                    }


                    Swal.fire({

                        icon: 'warning',

                        title: 'Atención',

                        text: mensaje,

                        confirmButtonText: 'Aceptar'

                    });

                },


                /* =================================================
                   FINALIZAR
                ================================================= */

                complete: function() {

                    boton.prop(
                        'disabled',
                        false
                    );

                    boton.html(
                        '<i class="fas fa-save mr-1"></i> Guardar cliente'
                    );

                }

            });

        });


        /* =========================================================
           LIMPIAR AL CERRAR MODAL
        ========================================================= */

        $('#modalCliente').on(
            'hidden.bs.modal',
            function() {

                let form =
                    $('#form-cliente-modal')[0];


                if (form) {

                    form.reset();

                }


                $('#modal_id_departamento')
                    .val(null)
                    .trigger('change');


                $('#modal_id_ciudad')
                    .val(null)
                    .trigger('change');


                $('#btn-guardar-cliente')
                    .prop('disabled', false)
                    .html(
                        '<i class="fas fa-save mr-1"></i> Guardar cliente'
                    );

            }
        );

    });
</script>
