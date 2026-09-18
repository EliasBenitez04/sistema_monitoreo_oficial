<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table" id="redistribucion-configs-table">
            <thead>
            <tr>
                <th>Metodo Demanda</th>
                <th>Porcentaje Demanda</th>
                <th>Stock Minimo</th>
                <th>Stock Maximo</th>
                <th>Venta Minima</th>
                <th>Porcentaje Necesidad</th>
                <th>Porcentaje Conservar Origen</th>
                <th>Cantidad Minima</th>
                <th>Cantidad Maxima</th>
                <th>Dias Bloqueo</th>
                <th>Bloquear Pendientes</th>
                <th>Bloquear En Proceso</th>
                <th>Bloquear Finalizados Recientes</th>
                <th>Activo</th>
                <th colspan="3">Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach($redistribucionConfigs as $redistribucionConfig)
                <tr>
                    <td>{{ $redistribucionConfig->metodo_demanda }}</td>
                    <td>{{ $redistribucionConfig->porcentaje_demanda }}</td>
                    <td>{{ $redistribucionConfig->stock_minimo }}</td>
                    <td>{{ $redistribucionConfig->stock_maximo }}</td>
                    <td>{{ $redistribucionConfig->venta_minima }}</td>
                    <td>{{ $redistribucionConfig->porcentaje_necesidad }}</td>
                    <td>{{ $redistribucionConfig->porcentaje_conservar_origen }}</td>
                    <td>{{ $redistribucionConfig->cantidad_minima }}</td>
                    <td>{{ $redistribucionConfig->cantidad_maxima }}</td>
                    <td>{{ $redistribucionConfig->dias_bloqueo }}</td>
                    <td>{{ $redistribucionConfig->bloquear_pendientes }}</td>
                    <td>{{ $redistribucionConfig->bloquear_en_proceso }}</td>
                    <td>{{ $redistribucionConfig->bloquear_finalizados_recientes }}</td>
                    <td>{{ $redistribucionConfig->activo }}</td>
                    <td  style="width: 120px">
                        {!! Form::open(['route' => ['redistribucionConfigs.destroy', $redistribucionConfig->id], 'method' => 'delete']) !!}
                        <div class='btn-group'>
                            <a href="{{ route('redistribucionConfigs.show', [$redistribucionConfig->id]) }}"
                               class='btn btn-default btn-xs'>
                                <i class="far fa-eye"></i>
                            </a>
                            <a href="{{ route('redistribucionConfigs.edit', [$redistribucionConfig->id]) }}"
                               class='btn btn-default btn-xs'>
                                <i class="far fa-edit"></i>
                            </a>
                            {!! Form::button('<i class="far fa-trash-alt"></i>', ['type' => 'submit', 'class' => 'btn btn-danger btn-xs', 'onclick' => "return confirm('Are you sure?')"]) !!}
                        </div>
                        {!! Form::close() !!}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="card-footer clearfix">
        <div class="float-right">
            @include('adminlte-templates::common.paginate', ['records' => $redistribucionConfigs])
        </div>
    </div>
</div>
