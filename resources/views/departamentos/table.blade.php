<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped table-bordered mb-0" id="departamentos-table">
                <thead class="thead-dark text-center">
                    <tr>
                        <th style="width: 80px;">#</th>
                        <th class="text-left">Descripción</th>
                        @canany(['departamentos edit', 'departamentos destroy'])
                            <th style="width: 150px;">Operaciones</th>
                        @endcanany
                    </tr>
                </thead>
                <tbody>
                    @forelse ($departamento as $value)
                        <tr class="text-center align-middle">
                            <td>{{ $value->id_departamento }}</td>
                            <td class="text-left">{{ $value->dep_descripcion }}</td>

                            @canany(['departamentos edit', 'departamentos destroy'])
                                <td>
                                    {!! Form::open(['route' => ['Departamentos.destroy', $value->id_departamento], 'method' => 'delete']) !!}
                                    <div class="btn-group btn-group-sg" role="group">
                                        @can('departamentos edit')
                                            <a href="{{ route('Departamentos.edit', [$value->id_departamento]) }}"
                                                class="btn btn-primary btn-sg" title="Editar">
                                                <i class="far fa-edit"></i>
                                            </a>
                                        @endcan

                                        @can('departamentos destroy')
                                            {!! Form::button('<i class="fas fa-trash"></i>', [
                                                'type' => 'button',
                                                'class' => 'btn btn-danger btn-sg alert-delete',
                                                'data-mensaje' => $value->dep_descripcion,
                                                'title' => 'Inactivar',
                                            ]) !!}
                                        @endcan
                                    </div>
                                    {!! Form::close() !!}
                                </td>
                            @endcanany
                        </tr>
                    @empty
                        <tr>
                            <td colspan="@canany(['departamentos edit', 'departamentos destroy']) 3 @else 2 @endcanany"
                                class="text-center text-muted py-4">
                                No existen registros
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer clearfix bg-light">
        <div class="float-left text-muted">
            Mostrando {{ $departamento->firstItem() }} -
            {{ $departamento->lastItem() }} de
            {{ $departamento->total() }} registros
        </div>
        <div class="float-right">
            {{ $departamento->links() }}
        </div>
    </div>
</div>
