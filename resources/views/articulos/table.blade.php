<!DOCTYPE html>

<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artículos</title>
    <style>
        th,
        td {
            text-align: center;
            vertical-align: middle;
        }

        td.producto,
        th.producto {
            text-align: center;
            vertical-align: middle;
        }

        td.producto_descri {
            text-align: left;
            vertical-align: middle;
        }

        .btn-group {
            display: flex;
            justify-content: center;
        }

        .btn-group .btn {
            margin: 0;
        }

        .btn-info,
        .btn-danger {
            transition: background-color 0.3s ease;
        }

        .btn-info:hover,
        .btn-danger:hover {
            transform: scale(1.1);
        }

        .loader-box {
            width: 100px;
            height: 100px;
            background: #fff;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
        }

        #articulos-table {
            font-size: 13px;
            width: 100%;
        }

        #articulos-table th,
        #articulos-table td {
            padding: 4px 6px !important;
            vertical-align: middle;
        }

        #articulos-table .btn {
            padding: 2px 6px;
            font-size: 18px;
        }

        #articulos-table th {
            white-space: nowrap;
        }

        #articulos-table td {
            white-space: nowrap;
        }
    </style>
</head>

<body>

    <div class="card-body p-0">
        <div class="table-responsive">
            <div class="card-header">
                <div class="row align-items-center">

                    <!-- Formulario de Filtro por Precio -->
                    <div class="table-responsive">
                        <div class="card-header">
                            <div class="row align-items-center">

                                <!-- FILTRO ORDEN PRECIO -->
                                <div class="col-md-4 mb-2">
                                    <form method="GET" action="{{ route('articulos.index') }}">
                                        <label>Ordenar por Precio:</label>

                                        <div class="d-flex">
                                            <select name="ordenar" class="form-control mr-2">
                                                <option value="">Seleccionar</option>

                                                <option value="asc"
                                                    {{ request('ordenar') == 'asc' ? 'selected' : '' }}>
                                                    Menor a Mayor
                                                </option>

                                                <option value="desc"
                                                    {{ request('ordenar') == 'desc' ? 'selected' : '' }}>
                                                    Mayor a Menor
                                                </option>
                                            </select>

                                            <button type="submit" class="btn btn-primary">
                                                Filtrar
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <!-- BUSCADOR PRODUCTOS -->
                                <div class="col-md-4 mb-2">
                                    <form method="GET" action="{{ route('articulos.index') }}">
                                        <label>Buscar Producto:</label>

                                        <div class="d-flex">
                                            <input type="text" name="buscar" class="form-control mr-2"
                                                placeholder="Código o descripción..." value="{{ request('buscar') }}">

                                            <button type="submit" class="btn btn-primary">
                                                Buscar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                @can('stocks importar')
                                    <!-- IMPORTAR EXCEL -->
                                    <div class="col-md-4 text-md-right">
                                        <form id="import-form" enctype="multipart/form-data">
                                            @csrf
                                            <input type="file" name="archivo" id="file" class="form-control mb-2"
                                                required>
                                            <button type="button" id="btn-import" class="btn btn-success">
                                                <i class="fas fa-file-excel"></i> Importar Excel
                                            </button>

                                            <input type="file" id="excelFile" accept=".xlsx,.xls,.csv"
                                                style="display:none;">
                                        </form>
                                    </div>
                                @endcan

                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <table class="table table-striped table-bordered table-hover" id="articulos-table">
                <thead class="thead-dark">
                    <tr>
                        <th class="producto text-center" style="width:5%;">#</th>
                        <th class="producto text-center" style="width:9%;">Código</th>
                        <th class="producto text-left" style="width:33%;">Descripción</th>
                        <th class="producto text-center" style="width:12%;">Costo</th>
                        <th class="producto text-center" style="width:12%;">Venta</th>
                        <th class="producto text-center" style="width:5%;">IVA</th>
                        <th colspan="3" class="text-center" style="width:5%;">Operaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($articulos as $articulo)
                        <tr>
                            <td class="producto">{{ $articulo->id_articulo }}</td>
                            <td class="producto_descri">{{ $articulo->art_codigo }}</td>
                            <td class="producto_descri">{{ $articulo->art_descripcion }}</td>
                            <td class="producto">{{ number_format($articulo->art_precio, 0, ',', '.') }}</td>
                            <td class="producto">{{ number_format($articulo->prec_vent, 0, ',', '.') }}</td>
                            <td class="producto">{{ $articulo->art_iva }}%</td>
                            <td style="width: 150px" class="text-center">
                                {!! Form::open([
                                    'route' => ['articulos.destroy', $articulo->id_articulo],
                                    'method' => 'delete',
                                    'class' => 'd-inline',
                                ]) !!}
                                <div class='btn-group'>
                                    @can('articulos edit')
                                        <a href="{{ route('articulos.edit', [$articulo->id_articulo]) }}"
                                            class='btn btn-info btn-sx' title="Editar">
                                            <i class="far fa-edit"></i>
                                        </a>
                                    @endcan
                                    @can('articulos destroy')
                                        {!! Form::button('<i class="far fa-trash-alt"></i>', [
                                            'type' => 'submit',
                                            'class' => 'btn btn-danger btn-sx alert-delete',
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
                Mostrando {{ $articulos->firstItem() }} -
                {{ $articulos->lastItem() }} de
                {{ $articulos->total() }} registros
            </div>
            <div class="float-right">
                {{ $articulos->links() }}
            </div>
        </div>
    </div>

    <div id="loadingOverlayArticulos">
        <div class="loading-box">

            <div class="icon-circle">
                <i class="fas fa-file-excel"></i>
            </div>

            <h4>Importando Artículos</h4>
            <p>Procesando archivo Excel...</p>

            <div class="progress-custom">
                <div id="progressBarArticulos"></div>
            </div>

            <div id="counterArticulos">0s</div>

        </div>
    </div>

    <style>
        th,
        td {
            text-align: center;
            vertical-align: middle;
        }

        td.producto,
        th.producto {
            text-align: center;
            vertical-align: middle;
        }

        td.producto_descri {
            text-align: left;
            vertical-align: middle;
        }

        .btn-group {
            display: flex;
            justify-content: center;
        }

        .btn-group .btn {
            margin: 0;
        }

        .btn-info,
        .btn-danger {
            transition: all 0.3s ease;
        }

        .btn-info:hover,
        .btn-danger:hover {
            transform: scale(1.08);
        }

        #articulos-table {
            font-size: 13px;
            width: 100%;
        }

        #articulos-table th,
        #articulos-table td {
            padding: 3px 5px !important;
            white-space: nowrap;
        }

        /* ================= LOADER ================= */
        #loadingOverlayArticulos {
            position: fixed;
            inset: 0;
            display: none;
            z-index: 99999;
            background: rgba(10, 10, 10, .65);
            backdrop-filter: blur(8px);
            justify-content: center;
            align-items: center;
        }

        .loading-box {
            width: 380px;
            background: #fff;
            border-radius: 18px;
            padding: 35px 30px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, .25);
            animation: fadeUp .3s ease;
        }

        .icon-circle {
            width: 70px;
            height: 70px;
            margin: 0 auto 15px;
            border-radius: 50%;
            background: linear-gradient(135deg, #16a34a, #22c55e);
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
            font-size: 26px;
        }

        .loading-box h4 {
            font-weight: 700;
            color: #111;
        }

        .loading-box p {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 18px;
        }

        .progress-custom {
            width: 100%;
            height: 10px;
            background: #e5e7eb;
            border-radius: 30px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        #progressBarArticulos {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, #16a34a, #22c55e, #4ade80);
            transition: width .4s ease;
        }

        #counterArticulos {
            font-size: 18px;
            font-weight: 700;
            color: #16a34a;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</body>

</html>
<!-- REEMPLAZÁ TODO TU SCRIPT POR ESTE -->

<script>
    document.addEventListener("DOMContentLoaded", function() {

        const btnImport = document.getElementById('btn-import');
        const fileInput = document.getElementById('file');

        const loader = document.getElementById('loadingOverlayArticulos');
        const counter = document.getElementById('counterArticulos');
        const progressBar = document.getElementById('progressBarArticulos');

        let seconds = 0;
        let interval;
        let fakeProgress;

        function resetUI() {
            clearInterval(interval);
            clearInterval(fakeProgress);

            btnImport.disabled = false;
            btnImport.innerHTML = `<i class="fas fa-file-excel"></i> Importar Excel`;

            progressBar.style.width = "0%";
            loader.style.display = "none";
        }

        btnImport.addEventListener('click', async function() {

            // VALIDACIÓN
            if (!fileInput.files.length) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Seleccione un archivo',
                    text: 'Debe elegir un Excel para importar'
                });
                return;
            }

            // SHOW LOADER
            loader.style.display = 'flex';

            btnImport.disabled = true;
            btnImport.innerHTML =
                `<span class="spinner-border spinner-border-sm"></span> Importando...`;

            // contador
            seconds = 0;
            counter.innerText = "0s";

            interval = setInterval(() => {
                seconds++;
                counter.innerText = seconds + "s";
            }, 1000);

            // fake progress
            let progreso = 0;
            fakeProgress = setInterval(() => {
                if (progreso < 90) {
                    progreso += Math.random() * 6;
                    progressBar.style.width = progreso + "%";
                }
            }, 400);

            let formData = new FormData();
            formData.append('archivo', fileInput.files[0]);
            formData.append('_token', '{{ csrf_token() }}');

            try {

                let response = await fetch("{{ route('articulos.importar') }}", {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                let data = await response.json();

                clearInterval(interval);
                clearInterval(fakeProgress);

                progressBar.style.width = "100%";

                setTimeout(() => {

                    resetUI();

                    if (data.success) {

                        Swal.fire({
                            icon: 'success',
                            title: 'Importación completada',
                            text: data.message
                        }).then(() => location.reload());

                    } else {

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            html: data.message
                        });

                    }

                }, 500);

            } catch (error) {

                resetUI();

                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo completar la importación'
                });
            }

        });

    });
</script>
