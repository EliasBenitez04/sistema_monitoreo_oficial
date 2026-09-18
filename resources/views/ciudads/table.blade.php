<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped table-bordered mb-0" id="ciudades-table">
                <thead class="thead-dark text-center">
                    <tr>
                        <th style="width: 80px;">#</th>
                        <th class="text-left">Descripción</th>
                        @canany(['ciudades edit', 'ciudades destroy'])
                            <th style="width: 150px;">Operaciones</th>
                        @endcanany
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ciudad as $value)
                        <tr class="text-center align-middle">
                            <td>{{ $value->id_ciudad }}</td>
                            <td class="text-left">{{ $value->ciu_descripcion }}</td>

                            @canany(['ciudades edit', 'ciudades destroy'])
                                <td>
                                    {!! Form::open(['route' => ['ciudades.destroy', $value->id_ciudad], 'method' => 'delete']) !!}
                                    <div class="btn-group btn-group-sg" role="group">
                                        @can('ciudades edit')
                                            <a href="{{ route('ciudades.edit', [$value->id_ciudad]) }}"
                                                class="btn btn-primary btn-sg" title="Editar">
                                                <i class="far fa-edit"></i>
                                            </a>
                                        @endcan

                                        @can('ciudades destroy')
                                            {!! Form::button('<i class="fas fa-trash"></i>', [
                                                'type' => 'button',
                                                'class' => 'btn btn-danger btn-sg alert-delete',
                                                'data-mensaje' => $value->ciu_descripcion,
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
                            <td colspan="@canany(['ciudades edit', 'ciudades destroy']) 3 @else 2 @endcanany"
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
            Mostrando {{ $ciudad->firstItem() }} -
            {{ $ciudad->lastItem() }} de
            {{ $ciudad->total() }} registros
        </div>
        <div class="float-right">
            {{ $ciudad->links() }}
        </div>
    </div>
</div>
