<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped table-bordered mb-0" id="lineas-table">
                <thead class="thead-dark text-center">
                    <tr>
                        <th style="width: 80px;">#</th>
                        <th class="text-left">Descripción</th>
                        @canany(['lineas edit', 'lineas destroy'])
                            <th style="width: 150px;">Operaciones</th>
                        @endcanany
                    </tr>
                </thead>
                <tbody>
                    @forelse ($linea as $value)
                        <tr class="text-center align-middle">
                            <td>{{ $value->linea_cod }}</td>
                            <td class="text-left">{{ $value->linea_desc }}</td>

                            @canany(['lineas edit', 'lineas destroy'])
                                <td>
                                    {!! Form::open(['route' => ['lineas.destroy', $value->linea_cod], 'method' => 'delete']) !!}
                                    <div class="btn-group btn-group-sg" role="group">
                                        @can('lineas edit')
                                            <a href="{{ route('lineas.edit', [$value->linea_cod]) }}"
                                                class="btn btn-primary btn-sg" title="Editar">
                                                <i class="far fa-edit"></i>
                                            </a>
                                        @endcan

                                        @can('lineas destroy')
                                            {!! Form::button('<i class="fas fa-trash"></i>', [
                                                'type' => 'button',
                                                'class' => 'btn btn-danger btn-sg alert-delete',
                                                'data-mensaje' => $value->linea_desc,
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
                            <td colspan="@canany(['lineas edit', 'lineas destroy']) 3 @else 2 @endcanany"
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
            Mostrando {{ $linea->firstItem() }} -
            {{ $linea->lastItem() }} de
            {{ $linea->total() }} registros
        </div>
        <div class="float-right">
            {{ $linea->links() }}
        </div>
    </div>
</div>
