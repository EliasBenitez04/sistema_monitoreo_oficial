<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped table-bordered mb-0" id="roles-table">
                <thead class="thead-dark">
                    <tr>
                        <th>Rol</th>
                        <th>Permisos</th>
                        @canany(['roles edit', 'roles destroy'])
                            <th style="width: 150px;">Actiones</th>
                        @endcanany
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr class="text-center align-middle">
                            <td class="text-left">{{ $role->name }}</td>
                            <td class="text-left">{{ $role->guard_name }}</td>

                            @canany(['roles edit', 'roles destroy'])
                                <td>
                                    {!! Form::open(['route' => ['roles.destroy', $role->id], 'method' => 'delete']) !!}
                                    <div class="btn-group btn-group-sg" role="group">
                                        @can('roles edit')
                                            <a href="{{ route('roles.edit', [$role->id]) }}" class="btn btn-primary btn-sg"
                                                title="Editar">
                                                <i class="far fa-edit"></i>
                                            </a>
                                        @endcan
                                        @can('roles destroy')
                                            {!! Form::button('<i class="fas fa-trash"></i>', [
                                                'type' => 'button',
                                                'class' => 'btn btn-danger btn-sg alert-delete',
                                                'title' => 'Eliminar',
                                            ]) !!}
                                        @endcan
                                    </div>
                                    {!! Form::close() !!}
                                </td>
                            @endcanany
                        </tr>
                    @empty
                        <tr>
                            <td colspan="@canany(['roles edit', 'roles destroy']) 3 @else 2 @endcanany"
                                class="text-center text-muted py-4">
                                No existen registros
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer clearfix">
        <div class="float-right">
            @include('adminlte-templates::common.paginate', ['records' => $roles])
        </div>
    </div>
</div>
