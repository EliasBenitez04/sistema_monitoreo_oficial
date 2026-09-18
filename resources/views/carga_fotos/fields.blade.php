<div class="row g-3">

    <!-- Fecha de Recepción -->
    <div class="form-group col-md-6">
        {!! Form::label('fot_fecha', 'Fecha de Recepción', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::date('fot_fecha', $fechaActual ?? now()->format('Y-m-d'), [
            'class' => 'form-control',
            'required',
            'disabled' => true,
            'style' => 'background-color: #f5f5f5; cursor: not-allowed;',
        ]) !!}
    </div>

    <!-- Fot OT -->
    <div class="form-group col-md-6">
        {!! Form::label('fot_ot', 'OT', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::text('fot_ot', old('fot_ot') ?? ($foto->fot_ot ?? ''), [
            'class' => 'form-control persist',
            'required',
            'minlength' => 5,
            'maxlength' => 5,
            'pattern' => '[0-9]{5,5}',
            'title' => 'Ingrese solo números, máximo 5 dígitos',
            'placeholder' => 'Ej: 29120',
            'data-key' => 'fot_ot',
        ]) !!}
    </div>

    <!-- Fot Costo -->
    <div class="form-group col-md-6">
        {!! Form::label('fot_costo', 'Costo', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::number('fot_costo', old('fot_costo') ?? ($foto->fot_costo ?? ''), [
            'class' => 'form-control persist',
            'required',
            'min' => 0,
            'placeholder' => 'Ej: 8912',
            'data-key' => 'fot_costo',
        ]) !!}
    </div>

    <!-- Fot Venta -->
    <div class="form-group col-md-6">
        {!! Form::label('fot_venta', 'Venta', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::number('fot_venta', old('fot_venta') ?? ($foto->fot_venta ?? ''), [
            'class' => 'form-control persist',
            'required',
            'min' => 0,
            'placeholder' => 'Ej: 25000',
            'data-key' => 'fot_venta',
        ]) !!}
    </div>

    <!-- Fot Descripción -->
    <div class="form-group col-md-6">
        {!! Form::label('fot_desc', 'Descripción', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::text('fot_desc', old('fot_desc') ?? ($foto->fot_desc ?? ''), [
            'class' => 'form-control persist',
            'required',
            'maxlength' => 120,
            'placeholder' => 'Descripción Del Producto',
            'data-key' => 'fot_desc',
        ]) !!}
    </div>

    <!-- Línea (FK) -->
    <div class="form-group col-md-6">
        {!! Form::label('linea_cod', 'Línea', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::select(
            'linea_cod',
            $lineas->pluck('linea_desc', 'linea_cod'), // opciones
            old('linea_cod') ?? ($foto->linea_cod ?? null), // valor seleccionado
            [
                'class' => 'form-control select2 persist',
                'placeholder' => 'Seleccione Línea',
                'required',
                'data-key' => 'linea_cod',
                'id' => 'linea_cod', // agregamos un id para JS
            ],
        ) !!}
    </div>

    <!-- Imagen -->
    <div class="form-group col-md-6">
        {!! Form::label('fot_img', 'Imagen', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::file('fot_img', [
            'class' => 'form-control',
            isset($foto) ? '' : 'required',
            'accept' => 'image/*',
            'id' => 'fot_img_input',
            'data-current' => isset($foto) ? Storage::url('fotos/' . $foto->fot_img) : '',
        ]) !!}

        <div class="mt-2 text-left" id="imagePreview">
            @if (isset($foto) && $foto->fot_img)
                <img src="{{ Storage::url('fotos/' . $foto->fot_img) }}" alt="Imagen"
                    style="width: 120px; height: auto; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.15); display:block; margin-bottom:5px;">
                <button type="button" class="btn btn-sm btn-danger mt-2" id="removeImageBtn">
                    <i class="fas fa-trash-alt"></i> Eliminar
                </button>
            @endif
        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {

        const form = document.querySelector('form');
        const persistFields = document.querySelectorAll('.persist');
        const fileInput = document.getElementById('fot_img_input');
        const preview = document.getElementById('imagePreview');

        if (!form) return;

        // Detectar si es edición
        const isEdit = form.dataset.edit === "true";

        /* ===============================
           PERSISTENCIA DE CAMPOS
        =============================== */
        persistFields.forEach(field => {
            const key = field.dataset.key;
            if (!key) return;

            // Solo cargar valores de localStorage si NO es edición
            if (!isEdit) {
                const savedValue = localStorage.getItem(key);
                if (savedValue) {
                    if ($(field).hasClass('select2-hidden-accessible')) {
                        $(field).val(savedValue).trigger('change');
                    } else {
                        field.value = savedValue;
                    }
                }
            }

            // Guardar cambios solo si NO es edición
            const saveValue = () => {
                if (!isEdit) {
                    localStorage.setItem(key, field.value);
                }
            };
            field.addEventListener('input', saveValue);
            field.addEventListener('change', saveValue);
        });

        /* ===============================
           IMAGEN
        =============================== */
        function renderImagePreview(imgData) {
            if (!preview) return;
            preview.innerHTML = '';

            const img = document.createElement('img');
            img.src = imgData;
            img.style.width = '120px';
            img.style.borderRadius = '8px';
            img.style.boxShadow = '0 4px 8px rgba(0,0,0,0.15)';
            img.style.display = 'block';
            img.style.marginBottom = '8px';

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-danger';
            removeBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Eliminar';

            removeBtn.addEventListener('click', () => {
                if (fileInput) fileInput.value = '';
                preview.innerHTML = '';
                if (!isEdit) localStorage.removeItem('fot_img');
            });

            preview.appendChild(img);
            preview.appendChild(removeBtn);
        }

        // Imagen inicial
        if (fileInput) {
            const existingImg = fileInput.dataset.current;
            if (isEdit && existingImg) {
                renderImagePreview(existingImg);
            } else {
                const savedImg = localStorage.getItem('fot_img');
                if (savedImg) renderImagePreview(savedImg);
            }
        }

        // Cambiar imagen
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = function(ev) {
                    const imgData = ev.target.result;
                    if (!isEdit) localStorage.setItem('fot_img', imgData);
                    renderImagePreview(imgData);
                };
                reader.readAsDataURL(file);
            });
        }

        // Limpiar storage al guardar
        form.addEventListener('submit', () => {
            if (!isEdit) {
                persistFields.forEach(field => {
                    const key = field.dataset.key;
                    if (key) localStorage.removeItem(key);
                });
                localStorage.removeItem('fot_img');
            }
        });

    });
</script>
