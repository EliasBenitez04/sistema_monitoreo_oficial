<div class="row">

    {{-- ==========================================================
         DATOS DEL CLIENTE
    =========================================================== --}}

    <div class="col-12 mb-4">
        <div class="form-section-title">
            <div class="section-icon">
                <i class="fas fa-user"></i>
            </div>

            <div>
                <h6 class="mb-0">Datos del Cliente</h6>
                <small>Información principal del cliente</small>
            </div>
        </div>
    </div>


    {{-- ==========================================================
         CI / RUC
    =========================================================== --}}

    <div class="col-md-12 mb-3">
        <label for="cli_ci" class="form-label fw-bold">
            Nro de CI / R.U.C.
            <span class="text-danger">*</span>
        </label>

        <div class="input-group modern-input">

            <span class="input-group-text">
                <i class="fas fa-id-card"></i>
            </span>

            <input type="text" name="cli_ci" id="cli_ci" class="form-control" required pattern="\d+(-\d+)?"
                maxlength="10" placeholder="Ej: 1234567-8" title="Ingrese solo números o números con guion"
                value="{{ old('cli_ci', $cliente->cli_ci ?? '') }}">

        </div>

        <small class="form-text text-muted">
            Ingrese el número de documento o R.U.C.
        </small>
    </div>


    {{-- ==========================================================
         RAZÓN SOCIAL
    =========================================================== --}}

    <div class="col-md-6 mb-3">

        <label for="cli_nombre" class="form-label fw-bold">
            Razón Social
            <span class="text-danger">*</span>
        </label>

        <div class="input-group modern-input">

            <span class="input-group-text">
                <i class="fas fa-building"></i>
            </span>

            <input type="text" name="cli_nombre" id="cli_nombre" class="form-control" required
                placeholder="Ingrese razón social" value="{{ old('cli_nombre', $cliente->cli_nombre ?? '') }}">

        </div>

    </div>


    {{-- ==========================================================
         CLIENTE
    =========================================================== --}}

    <div class="col-md-6 mb-3">

        <label for="cli_apellido" class="form-label fw-bold">
            Cliente
        </label>

        <div class="input-group modern-input">

            <span class="input-group-text">
                <i class="fas fa-user-tag"></i>
            </span>

            <input type="text" name="cli_apellido" id="cli_apellido" class="form-control"
                placeholder="Ingrese nombre del cliente"
                value="{{ old('cli_apellido', $cliente->cli_apellido ?? '') }}">

        </div>

    </div>


    {{-- ==========================================================
         DIRECCIÓN
    =========================================================== --}}

    <div class="col-md-6 mb-3">

        <label for="cli_direccion" class="form-label fw-bold">
            Dirección
        </label>

        <div class="input-group modern-input">

            <span class="input-group-text">
                <i class="fas fa-map-marker-alt"></i>
            </span>

            <input type="text" name="cli_direccion" id="cli_direccion" class="form-control"
                placeholder="Ingrese dirección" value="{{ old('cli_direccion', $cliente->cli_direccion ?? '') }}">

        </div>

    </div>


    {{-- ==========================================================
         TELÉFONO
    =========================================================== --}}

    <div class="col-md-6 mb-3">

        <label for="cli_telefono" class="form-label fw-bold">
            Teléfono
        </label>

        <div class="input-group modern-input">

            <span class="input-group-text">
                <i class="fas fa-phone"></i>
            </span>

            <input type="text" name="cli_telefono" id="cli_telefono" class="form-control"
                placeholder="Ej: 0981 123456" value="{{ old('cli_telefono', $cliente->cli_telefono ?? '') }}">

        </div>

    </div>


    {{-- ==========================================================
         UBICACIÓN
    =========================================================== --}}

    <div class="col-12 mt-2 mb-3">

        <div class="form-section-title">

            <div class="section-icon">
                <i class="fas fa-map-marked-alt"></i>
            </div>

            <div>
                <h6 class="mb-0">Ubicación</h6>
                <small>Departamento y ciudad del cliente</small>
            </div>

        </div>

    </div>


    {{-- ==========================================================
         DEPARTAMENTO
    =========================================================== --}}

    <div class="col-md-6 mb-3">

        <label for="departamento_id" class="form-label fw-bold">
            Departamento
        </label>

        <div class="input-group modern-input">

            <span class="input-group-text">
                <i class="fas fa-map"></i>
            </span>

            {!! Form::select('id_departamento', $departamento, null, [
                'class' => 'form-control select2',
                'placeholder' => 'Seleccione un departamento',
                'id' => 'departamento_id',
            ]) !!}

        </div>

    </div>


    {{-- ==========================================================
         CIUDAD
    =========================================================== --}}

    <div class="col-md-6 mb-3">

        <label for="ciudad_id" class="form-label fw-bold">
            Ciudad
        </label>

        <div class="input-group modern-input">

            <span class="input-group-text">
                <i class="fas fa-city"></i>
            </span>

            {!! Form::select('id_ciudad', $ciudad, null, [
                'class' => 'form-control select2',
                'placeholder' => 'Seleccione una ciudad',
                'id' => 'ciudad_id',
            ]) !!}

        </div>

    </div>

</div>


{{-- ==========================================================
     ESTILOS
========================================================== --}}

<style>
    /* ----------------------------------------------------------
       TÍTULOS DE SECCIÓN
    ---------------------------------------------------------- */

    .form-section-title {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 15px;
        margin-bottom: 5px;
        border-left: 4px solid #4e73df;
        background: #f8f9fc;
        border-radius: 0 8px 8px 0;
    }

    .form-section-title h6 {
        font-weight: 700;
        color: #343a40;
    }

    .form-section-title small {
        color: #858796;
        font-size: 12px;
    }

    .section-icon {
        width: 38px;
        height: 38px;
        min-width: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #4e73df;
        color: white;
        border-radius: 8px;
        font-size: 15px;
    }


    /* ----------------------------------------------------------
       LABELS
    ---------------------------------------------------------- */

    .form-label {
        color: #343a40;
        font-size: 13px;
        margin-bottom: 6px;
    }

    .text-danger {
        font-size: 12px;
    }


    /* ----------------------------------------------------------
       INPUTS
    ---------------------------------------------------------- */

    .modern-input {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
        transition: all 0.2s ease;
    }

    .modern-input .input-group-text {
        width: 45px;
        justify-content: center;
        background: #f8f9fc;
        border: 1px solid #d9dce1;
        border-right: 0;
        color: #6c757d;
    }

    .modern-input .form-control {
        height: 44px;
        border: 1px solid #d9dce1;
        border-left: 0;
        font-size: 14px;
        padding: 8px 12px;
    }

    .modern-input .form-control:focus {
        box-shadow: none;
        border-color: #4e73df;
    }

    .modern-input:focus-within {
        box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.12);
    }

    .modern-input:focus-within .input-group-text {
        color: #4e73df;
        border-color: #4e73df;
    }


    /* ----------------------------------------------------------
       SELECT2
    ---------------------------------------------------------- */

    .modern-input .select2-container {
        flex: 1;
        width: auto !important;
    }

    .modern-input .select2-container .select2-selection--single {
        height: 44px;
        border: 1px solid #d9dce1;
        border-left: 0;
        border-radius: 0;
    }

    .modern-input .select2-container .select2-selection__rendered {
        line-height: 42px;
        padding-left: 12px;
        font-size: 14px;
        color: #495057;
    }

    .modern-input .select2-container .select2-selection__arrow {
        height: 42px;
    }


    /* ----------------------------------------------------------
       TEXTO DE AYUDA
    ---------------------------------------------------------- */

    .form-text {
        margin-top: 5px;
        font-size: 11px;
    }


    /* ----------------------------------------------------------
       RESPONSIVE
    ---------------------------------------------------------- */

    @media (max-width: 767px) {

        .form-section-title {
            margin-top: 5px;
        }

        .modern-input .form-control,
        .modern-input .select2-container .select2-selection--single {
            height: 42px;
        }

    }
</style>


{{-- ==========================================================
     VALIDACIÓN CI / RUC
========================================================== --}}

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const ciInput = document.querySelector('[name="cli_ci"]');

        if (ciInput) {

            ciInput.addEventListener('input', function() {

                if (this.value.length > 10) {
                    this.value = this.value.slice(0, 10);
                }

            });

        }

    });
</script>
