{{-- ==========================================================
     ESTILOS DE LA TABLA
========================================================== --}}

<style>
    /* ----------------------------------------------------------
       CARD / CONTENEDOR
    ---------------------------------------------------------- */

    .clientes-table-card {
        border: none;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
    }


    /* ----------------------------------------------------------
       TABLA
    ---------------------------------------------------------- */

    #clientes-table {
        margin-bottom: 0;
        border: none;
        font-size: 13px;
    }

    #clientes-table th {
        background: #343a40;
        color: #fff;
        border: none;
        padding: 13px 10px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }

    #clientes-table td {
        vertical-align: middle;
        padding: 11px 10px;
        border-top: 1px solid #edf0f2;
        color: #495057;
    }


    /* ----------------------------------------------------------
       HOVER
    ---------------------------------------------------------- */

    #clientes-table tbody tr {
        transition: all 0.18s ease;
    }

    #clientes-table tbody tr:hover {
        background-color: #f8f9fc;
    }


    /* ----------------------------------------------------------
       ID
    ---------------------------------------------------------- */

    .cliente-id {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 30px;
        height: 30px;
        padding: 0 8px;
        border-radius: 7px;
        background: #f1f3f5;
        color: #495057;
        font-size: 12px;
        font-weight: 700;
    }


    /* ----------------------------------------------------------
       DOCUMENTO
    ---------------------------------------------------------- */

    .cliente-documento {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
        color: #343a40;
        white-space: nowrap;
    }

    .cliente-documento i {
        color: #6c757d;
        font-size: 12px;
    }


    /* ----------------------------------------------------------
       CLIENTE
    ---------------------------------------------------------- */

    .cliente-nombre {
        font-weight: 600;
        color: #343a40;
        line-height: 1.3;
    }

    .cliente-nombre i {
        color: #6c757d;
        margin-right: 5px;
        font-size: 12px;
    }


    /* ----------------------------------------------------------
       DIRECCIÓN
    ---------------------------------------------------------- */

    .cliente-direccion {
        color: #6c757d;
        line-height: 1.4;
    }

    .cliente-direccion i {
        color: #adb5bd;
        margin-right: 5px;
        font-size: 11px;
    }


    /* ----------------------------------------------------------
       TELÉFONO
    ---------------------------------------------------------- */

    .cliente-telefono {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        white-space: nowrap;
        color: #495057;
    }

    .cliente-telefono i {
        color: #6c757d;
        font-size: 11px;
    }


    /* ----------------------------------------------------------
       UBICACIÓN
    ---------------------------------------------------------- */

    .cliente-ubicacion {
        font-size: 12px;
        color: #495057;
    }

    .cliente-ubicacion i {
        color: #6c757d;
        margin-right: 4px;
    }


    /* ----------------------------------------------------------
       OPERACIONES
    ---------------------------------------------------------- */

    .cliente-actions {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .cliente-actions .btn {
        width: 34px;
        height: 34px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 7px;
        transition: all 0.2s ease;
    }

    .cliente-actions .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 3px 7px rgba(0, 0, 0, 0.15);
    }

    .cliente-actions .btn i {
        font-size: 13px;
    }


    /* ----------------------------------------------------------
       FOOTER
    ---------------------------------------------------------- */

    .clientes-footer {
        padding: 14px 18px;
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
    }

    .clientes-footer-info {
        font-size: 12px;
        color: #6c757d;
        display: flex;
        align-items: center;
        min-height: 38px;
    }

    .clientes-footer-info strong {
        color: #343a40;
        margin: 0 3px;
    }

    .clientes-pagination {
        display: flex;
        justify-content: flex-end;
    }

    .clientes-pagination .pagination {
        margin-bottom: 0;
    }


    /* ----------------------------------------------------------
       RESPONSIVE
    ---------------------------------------------------------- */

    @media (max-width: 768px) {

        #clientes-table {
            font-size: 12px;
        }

        #clientes-table th,
        #clientes-table td {
            padding: 9px 8px;
        }

        .clientes-footer {
            display: block;
        }

        .clientes-footer-info {
            justify-content: center;
            margin-bottom: 10px;
        }

        .clientes-pagination {
            justify-content: center;
        }

    }
</style>


{{-- ==========================================================
     TABLA
========================================================== --}}

<div class="card-body p-0 clientes-table-card">

    <div class="table-responsive">

        <table class="table table-hover table-striped mb-0" id="clientes-table">

            <thead class="text-center">

                <tr>

                    <th style="width: 55px;">
                        #
                    </th>

                    <th style="width: 130px;">
                        Nro. Documento
                    </th>

                    <th style="width: 230px;">
                        Cliente
                    </th>

                    <th style="min-width: 220px;">
                        Dirección
                    </th>

                    <th style="width: 130px;">
                        Teléfono
                    </th>

                    <th style="width: 150px;">
                        Departamento
                    </th>

                    <th style="width: 150px;">
                        Ciudad
                    </th>

                    <th style="width: 110px;">
                        Operaciones
                    </th>

                </tr>

            </thead>


            <tbody>

                @foreach ($clientes as $cliente)
                    <tr>

                        {{-- ID --}}
                        <td class="text-center">

                            <span class="cliente-id">
                                {{ $cliente->id_cliente }}
                            </span>

                        </td>


                        {{-- DOCUMENTO --}}
                        <td class="text-center">

                            <span class="cliente-documento">

                                <i class="fas fa-id-card"></i>

                                {{ $cliente->cli_ci }}

                            </span>

                        </td>


                        {{-- CLIENTE --}}
                        <td>

                            <div class="cliente-nombre">

                                <i class="fas fa-user"></i>

                                {{ $cliente->cli_nombre }}

                                @if ($cliente->cli_apellido)
                                    {{ $cliente->cli_apellido }}
                                @endif

                            </div>

                        </td>


                        {{-- DIRECCIÓN --}}
                        <td>

                            <div class="cliente-direccion">

                                @if ($cliente->cli_direccion)
                                    <i class="fas fa-map-marker-alt"></i>

                                    {{ $cliente->cli_direccion }}
                                @else
                                    <span class="text-muted">
                                        Sin dirección
                                    </span>
                                @endif

                            </div>

                        </td>


                        {{-- TELÉFONO --}}
                        <td class="text-center">

                            @if ($cliente->cli_telefono)
                                <span class="cliente-telefono">

                                    <i class="fas fa-phone"></i>

                                    {{ $cliente->cli_telefono }}

                                </span>
                            @else
                                <span class="text-muted">
                                    —
                                </span>
                            @endif

                        </td>


                        {{-- DEPARTAMENTO --}}
                        <td class="text-center">

                            <span class="cliente-ubicacion">

                                <i class="fas fa-map"></i>

                                {{ $cliente->dep_descripcion ?? '—' }}

                            </span>

                        </td>


                        {{-- CIUDAD --}}
                        <td class="text-center">

                            <span class="cliente-ubicacion">

                                <i class="fas fa-city"></i>

                                {{ $cliente->ciu_descripcion ?? '—' }}

                            </span>

                        </td>


                        {{-- OPERACIONES --}}
                        <td>

                            {!! Form::open([
                                'route' => ['clientes.destroy', $cliente->id_cliente],
                                'method' => 'delete',
                            ]) !!}

                            <div class="cliente-actions">

                                @can('clientes edit')
                                    <a href="{{ route('clientes.edit', [$cliente->id_cliente]) }}" class="btn btn-primary"
                                        data-toggle="tooltip" title="Editar cliente">

                                        <i class="far fa-edit"></i>

                                    </a>
                                @endcan


                                @can('clientes destroy')
                                    {!! Form::button('<i class="far fa-trash-alt"></i>', [
                                        'type' => 'submit',
                                        'class' => 'btn btn-danger alert-delete',
                                        'data-toggle' => 'tooltip',
                                        'title' => 'Eliminar cliente',
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


    {{-- ==========================================================
         FOOTER / PAGINACIÓN
    =========================================================== --}}

    <div class="clientes-footer clearfix">

        <div class="float-left clientes-footer-info">

            Mostrando

            <strong>
                {{ $clientes->firstItem() ?? 0 }}
            </strong>

            -

            <strong>
                {{ $clientes->lastItem() ?? 0 }}
            </strong>

            de

            <strong>
                {{ $clientes->total() }}
            </strong>

            registros

        </div>


        <div class="float-right clientes-pagination">

            {{ $clientes->links() }}

        </div>

    </div>

</div>
