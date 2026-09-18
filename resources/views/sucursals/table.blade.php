<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered mb-0" id="sucursals-table">
            <thead class="thead-dark">
                <tr class="text-center">
                    <th>#</th>
                    <th>Descripción</th>
                    <th>Dirección</th>
                    <th>Teléfono</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sucursal as $sucursales)
                    <tr class="align-middle">
                        <td class="text-center">{{ $sucursales->cod_suc }}</td>
                        <td>{{ $sucursales->suc_descri }}</td>
                        <td>{{ $sucursales->suc_direccion }}</td>
                        <td class="text-center">{{ $sucursales->suc_telefono }}</td>
                        <td class="text-center" style="width: 150px">
                            {!! Form::open([
                                'route' => ['sucursal.destroy', $sucursales->cod_suc],
                                'method' => 'delete',
                                'class' => 'd-inline',
                            ]) !!}
                            <div class="btn-group">
                                @can('sucursal edit')
                                    <a href="{{ route('sucursal.edit', [$sucursales->cod_suc]) }}"
                                        class="btn btn-primary btn-sg" title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                @endcan
                                @can('sucursal destroy')
                                    {!! Form::button('<i class="fas fa-trash-alt"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-danger btn-sg alert-delete',
                                        'data-mensaje' => "la sucursal: {$sucursales->suc_descri}",
                                        'title' => 'Eliminar',
                                    ]) !!}
                                @endcan
                            </div>
                            {!! Form::close() !!}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="card-footer clearfix bg-light">
        <div class="float-left text-muted">
            Mostrando {{ $sucursal->firstItem() }} -
            {{ $sucursal->lastItem() }} de
            {{ $sucursal->total() }} registros
        </div>
        <div class="float-right">
            {{ $sucursal->links() }}
        </div>
    </div>
</div>
