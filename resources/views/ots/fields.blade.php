<!-- CABECERA -->

<!-- <div class="form-group col-sm-6">
    {!! Form::label('id_ot', 'Seleccionar OT:') !!}
    {!! Form::select('id_ot', $ot, null, [
        'class' => 'form-control select2',
        'placeholder' => 'Seleccione una OT',
        'id' => 'id_ot_select',
        'required',
    ]) !!}
</div> -->

<div class="form-group col-sm-6">
    {!! Form::label('nro_ot', 'Nro OT:') !!}
    {!! Form::number('nro_ot', null, [
    'class' => 'form-control',
    'id' => 'nro_ot',
    'readonly',
    ]) !!}
</div>

<div class="form-group col-sm-6">
    {!! Form::label('codigo', 'Código:') !!}
    {!! Form::text('codigo', null, [
    'class' => 'form-control',
    'id' => 'codigo',
    'readonly',
    ]) !!}
</div>

<div class="form-group col-sm-6">
    {!! Form::label('descripcion', 'Descripción:') !!}
    {!! Form::text('descripcion', null, [
    'class' => 'form-control',
    'id' => 'descripcion',
    'readonly',
    ]) !!}
</div>

<div class="form-group col-sm-6">
    {!! Form::label('cantidad_orden', 'Cantidad Orden:') !!}
    {!! Form::number('cantidad_orden', null, [
    'class' => 'form-control',
    'id' => 'cantidad_orden',
    'readonly',
    ]) !!}
</div>

<div class="form-group col-sm-6">
    {!! Form::label('estado', 'Estado:') !!}

    {!! Form::select('estado', [
    'ACTIVO' => 'ACTIVO',
    'POSTERGADO' => 'POSTERGADO',
    'CANCELADO' => 'CANCELADO',
    ], null, [
    'class' => 'form-control select2',
    'id' => 'estado',
    'style' => 'width: 100%;',
    'placeholder' => 'Seleccione el estado de la OT',
    ]) !!}
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#estado').select2({
            placeholder: 'Seleccione el estado de la OT',
            allowClear: true,
            width: '100%'
        });
    });
</script>
@endpush

<div class="form-group col-sm-12">
    {!! Form::label('obs', 'Observaciones:') !!}
    {!! Form::text('obs', null, [
    'class' => 'form-control',
    'id' => 'obs',
    ]) !!}
</div>


@push('page_scripts')
<script>
    $(document).ready(function() {

        $('#id_ot_select').select2({
            placeholder: 'Seleccione una OT',
            allowClear: true
        });

        $('#id_ot_select').change(function() {

            let id_ot = $(this).val();

            if (id_ot) {

                $.ajax({

                    url: '/get-ot-details/' + id_ot,
                    type: 'GET',

                    success: function(data) {

                        $('#nro_ot').val(data.nro_ot);
                        $('#codigo').val(data.codigo);
                        $('#descripcion').val(data.descripcion);
                        $('#cantidad_orden').val(data.cantidad_orden);
                    },

                    error: function() {

                        alert('No se pudo obtener la información de la OT');

                        $('#nro_ot').val('');
                        $('#codigo').val('');
                        $('#descripcion').val('');
                        $('#cantidad_orden').val('');

                    }

                });

            } else {

                $('#nro_ot').val('');
                $('#codigo').val('');
                $('#descripcion').val('');
                $('#cantidad_orden').val('');
                $('#estado').val('');
                $('#obs').val('');

            }

        });

    });
</script>
@endpush