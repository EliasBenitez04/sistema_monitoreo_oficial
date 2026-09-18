<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0" id="usuarios-table">
            <thead class="thead-dark">
                <tr class="text-center">
                    <th style="width: 2%">#</th>
                    <th class="text-center" style="width: 10%">Usuario</th>
                    <th class="text-left" style="width: 20%">Correo</th>
                    <th style="width: 10%">CI</th>
                    <th class="text-left">Dirección</th>
                    <th style="width: 10%">Teléfono</th>
                    <th style="width: 5%">Rol</th>
                    <th style="width: 5%">Estado</th>
                    <th style="width: 130px">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($usuarios as $usuario)
                    <tr class="align-middle text-center">
                        <td>{{ $usuario->id }}</td>

                        <td class="text-center">
                            <i class="fas fa-user text-secondary mr-1"></i>
                            {{ $usuario->name }}
                        </td>

                        <td class="text-left">
                            <i class="fas fa-envelope text-secondary mr-1"></i>
                            {{ $usuario->email }}
                        </td>

                        <td>{{ $usuario->ci }}</td>

                        <td class="text-left">
                            {{ $usuario->direccion ?? '—' }}
                        </td>

                        <td>
                            <i class="fas fa-phone-alt text-secondary mr-1"></i>
                            {{ $usuario->telefono ?? '—' }}
                        </td>

                        <td>
                            <span class="badge badge-primary">
                                {{ $usuario->roles->first()->name ?? 'Sin rol' }}
                            </span>
                        </td>

                        <td>
                            @if ($usuario->estado === 'ACTIVO')
                                <span class="badge badge-success">
                                    <i class="fas fa-check-circle"></i> Activo
                                </span>
                            @else
                                <span class="badge badge-secondary">
                                    <i class="fas fa-ban"></i> Inactivo
                                </span>
                            @endif
                        </td>

                        <td>
                            {!! Form::open([
                                'route' => ['usuarios.destroy', $usuario->id],
                                'method' => 'delete',
                                'class' => 'd-inline',
                            ]) !!}
                            <div class="btn-group btn-group-sg">

                                @can('usuarios edit')
                                    <a href="{{ route('usuarios.edit', $usuario->id) }}" class="btn btn-primary"
                                        title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endcan

                                @can('usuarios destroy')
                                    {!! Form::button('<i class="fas fa-user-slash"></i>', [
                                        'type' => 'button',
                                        'class' => 'btn btn-danger alert-delete',
                                        'title' => 'Inactivar',
                                        'data-mensaje' => 'el usuario',
                                        'data-accion' => 'inactivar',
                                    ]) !!}
                                @endcan



                            </div>
                            {!! Form::close() !!}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-info-circle"></i>
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
        @include('adminlte-templates::common.paginate', ['records' => $usuarios])
    </div>
</div>
