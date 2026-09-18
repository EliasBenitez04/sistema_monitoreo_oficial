<div class="card-body p-0">

    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover text-center" id="carga_fotos-table">
            <thead class="bg-dark text-white">
                <tr>
                    <th style="width: 10%">Fecha</th>
                    <th style="width: 10%">N° OT</th>
                    <th style="width: 10%">Prec. Costo</th>
                    <th style="width: 10%">Prec. Venta</th>
                    <th style="width: 20%">Artículo</th>
                    <th style="width: 15%">Imagen</th>
                    <th style="width: 15%">Usuario</th>
                    <th style="width: 10%">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($fotos as $value)
                    <tr>
                        <!-- Fecha -->
                        <td class="align-middle text-center">
                            {{ \Carbon\Carbon::parse($value->fot_fecha)->format('d/m/Y') }}
                        </td>

                        <!-- OT -->
                        <td class="align-middle text-center">{{ $value->fot_ot }}</td>

                        <!-- Costo -->
                        <td class="align-middle text-center">
                            {{ number_format($value->fot_costo, 0, ',', '.') }} Gs.
                        </td>

                        <!-- Venta -->
                        <td class="align-middle text-center">
                            {{ number_format($value->fot_venta, 0, ',', '.') }} Gs.
                        </td>

                        <!-- Descripción y Línea concatenadas -->
                        <td class="align-middle text-center">
                            {{ $value->fot_desc }} {{ $value->linea->linea_desc ?? $value->linea_cod }}
                        </td>
                        <!-- Imagen -->
                        <td class="align-middle text-center">
                            @if ($value->fot_img)
                                <img src="{{ Storage::url('fotos/' . $value->fot_img) }}" alt="Imagen"
                                    style="max-width: 80px; height: auto; border-radius: 4px; border: 1px solid #ccc;">
                            @else
                                <span class="text-muted">Sin Imagen</span>
                            @endif
                        </td>

                        <!-- Usuario -->
                        <td class="align-middle text-center">
                            {{ $value->user->name ?? $value->user_id }}
                        </td>

                        <!-- Acciones -->
                        <td class="align-middle text-center" style="width: 140px;">
                            <div class="btn-group">
                                <a href="{{ route('carga_fotos.show', [$value->fot_cod]) }}" class="btn btn-info btn-sg"
                                    title="Ver">
                                    <i class="far fa-eye"></i>
                                </a>

                                @can('carga_fotos edit')
                                    <a href="{{ route('carga_fotos.edit', [$value->fot_cod]) }}"
                                        class="btn btn-warning btn-sg" title="Editar">
                                        <i class="far fa-edit"></i>
                                    </a>
                                @endcan

                                @can('carga_fotos destroy')
                                    {!! Form::open([
                                        'route' => ['carga_fotos.destroy', $value->fot_cod],
                                        'method' => 'delete',
                                        'class' => 'delete-form',
                                        'style' => 'display:inline',
                                    ]) !!}
                                    {!! Form::button('<i class="fas fa-trash"></i>', [
                                        'type' => 'button',
                                        'class' => 'btn btn-danger btn-sg alert-delete',
                                        'data-mensaje' => $value->fot_ot,
                                        'title' => 'Eliminar',
                                    ]) !!}
                                    {!! Form::close() !!}
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            <i class="fas fa-exclamation-circle"></i> No se encontraron resultados
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer clearfix bg-light">
        <div class="float-left text-muted">
            Mostrando {{ $fotos->firstItem() }} -
            {{ $fotos->lastItem() }} de
            {{ $fotos->total() }} registros
        </div>
        <div class="float-right">
            {{ $fotos->links() }}
        </div>
    </div>
</div>
