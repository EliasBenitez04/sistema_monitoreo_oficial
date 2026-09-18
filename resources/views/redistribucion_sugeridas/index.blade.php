@extends('layouts.app')

@section('content')
    <style>
        .redistribucion-page .card {
            border: 0;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .06)
        }

        .redistribucion-page .card-header {
            border-radius: 10px 10px 0 0 !important
        }

        .redistribucion-page .module-header {
            color: #000;
            padding: 18px 20px;
            border-radius: 10px 10px 0 0
        }

        .redistribucion-page .module-icon {
            width: 44px;
            height: 44px;
            border-radius: 9px;
            background: rgba(255, 255, 255, .15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px
        }

        .redistribucion-page .filter-card {
            background: #fff
        }

        .redistribucion-page .filter-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .3px;
            font-weight: 700;
            color: #495057;
            margin-bottom: 6px
        }

        .redistribucion-page .filter-label i {
            margin-right: 5px
        }

        .redistribucion-page .filter-help {
            font-size: 11px;
            color: #8a94a6;
            margin-top: 5px;
            display: block
        }

        .redistribucion-page .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: 6px
        }

        .redistribucion-page .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            font-size: 13px;
            color: #495057
        }

        .redistribucion-page .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px
        }

        .redistribucion-page .action-bar {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 15px
        }

        .redistribucion-page .info-box {
            font-size: 12px;
            color: #6c757d
        }

        .redistribucion-page .btn {
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px
        }

        .redistribucion-page .results-header {
            padding: 16px 20px
        }

        .redistribucion-page .results-title {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
            color: #343a40
        }

        .redistribucion-page .results-subtitle {
            font-size: 11px;
            color: #8a94a6;
            margin-top: 3px
        }

        .redistribucion-page .badge-results {
            background: #f1f5f9;
            color: #495057;
            border: 1px solid #dee2e6;
            padding: 7px 12px;
            border-radius: 6px;
            font-size: 11px
        }
    </style>

    <div class="content px-3 redistribucion-page">
        @include('sweetalert::alert')

        {{-- CABECERA --}}
        <div class="module-header mb-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <div class="d-flex align-items-center">
                    <div class="module-icon mr-3"><i class="fas fa-random"></i></div>
                    <div>
                        <h4 class="mb-1 font-weight-bold">Redistribución de Stock</h4>
                        <div class="small" style="opacity:.85">Análisis de demanda, stock y movimientos entre sucursales
                        </div>
                    </div>
                </div>
                <div class="mt-2 mt-md-0">
                    <span class="badge badge-light text-primary px-3 py-2"><i class="fas fa-cogs mr-1"></i> Análisis
                        inteligente</span>
                </div>
            </div>
        </div>

        {{-- FILTROS --}}
        <div class="card filter-card mb-3">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center">
                    <div class="mr-2 text-primary"><i class="fas fa-filter"></i></div>
                    <div>
                        <div class="font-weight-bold">Parámetros de análisis</div>
                        <div class="text-muted small">Seleccione los criterios para generar las sugerencias de
                            redistribución.</div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <form action="{{ route('RedistribucionSugeridas.analizar') }}" method="POST" id="formAnalisis">
                    @csrf

                    <div class="row">
                        {{-- PERIODO --}}
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="periodo" class="filter-label">
                                <i class="far fa-calendar-alt text-primary"></i>Período
                            </label>
                            <select name="periodo" id="periodo" class="form-control select2" required>
                                <option value="">Seleccione un período</option>
                                @foreach ($periodos as $periodo)
                                    <option value="{{ $periodo }}" {{ old('periodo') == $periodo ? 'selected' : '' }}>
                                        {{ $periodo }}</option>
                                @endforeach
                            </select>
                            <span class="filter-help">Período de ventas y stock a analizar.</span>
                        </div>

                        {{-- GRUPO PLAN --}}
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="grupo_plan" class="filter-label">
                                <i class="fas fa-layer-group text-primary"></i>Grupo Plan
                            </label>
                            <select name="grupo_plan" id="grupo_plan" class="form-control select2">
                                <option value="">Todos los grupos</option>
                                @foreach ($gruposPlan as $grupo)
                                    <option value="{{ $grupo }}" {{ old('grupo_plan') == $grupo ? 'selected' : '' }}>
                                        {{ $grupo }}</option>
                                @endforeach
                            </select>
                            <span class="filter-help">Filtrar por grupo de planificación.</span>
                        </div>

                        {{-- LINEA --}}
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="linea" class="filter-label">
                                <i class="fas fa-tags text-primary"></i>Línea
                            </label>
                            <select name="linea" id="linea" class="form-control select2">
                                <option value="">Todas las líneas</option>
                                @foreach ($lineas as $linea)
                                    <option value="{{ $linea }}" {{ old('linea') == $linea ? 'selected' : '' }}>
                                        {{ $linea }}</option>
                                @endforeach
                            </select>
                            <span class="filter-help">Limitar el análisis a una línea.</span>
                        </div>

                        {{-- TEMPORADA --}}
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="temporada" class="filter-label">
                                <i class="fas fa-clock text-primary"></i>Temporada
                            </label>
                            <select name="temporada" id="temporada" class="form-control select2">
                                <option value="">Todas las temporadas</option>
                                @foreach ($temporadas as $temporada)
                                    <option value="{{ $temporada }}" {{ old('temporada') == $temporada ? 'selected' : '' }}>
                                        {{ $temporada }}</option>
                                @endforeach
                            </select>
                            <span class="filter-help">Seleccionar temporada del producto.</span>
                        </div>
                    </div>

                    <div class="action-bar mt-2">
                        <div class="row align-items-center">
                            <div class="col-md-7 mb-2 mb-md-0">
                                <div class="info-box">
                                    <i class="fas fa-info-circle text-primary mr-1"></i>
                                    El sistema analizará las ventas y el stock disponible para determinar posibles
                                    transferencias.
                                </div>
                            </div>
                            <div class="col-md-5 text-md-right">
                                <button type="button" class="btn btn-light border mr-2" id="limpiarFiltros">
                                    <i class="fas fa-eraser mr-1"></i>Limpiar filtros
                                </button>
                                <button type="submit" class="btn btn-primary px-4" id="btnAnalizar">
                                    <i class="fas fa-play mr-1"></i>Ejecutar análisis
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- RESULTADOS --}}
        <div class="card">
            <div class="results-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="text-primary mr-3" style="font-size:20px"><i class="fas fa-exchange-alt"></i></div>
                        <div>
                            <h3 class="results-title">Sugerencias de Redistribución</h3>
                            <div class="results-subtitle">Movimientos recomendados según demanda y disponibilidad de stock.
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <span class="badge-results"><i class="fas fa-database mr-1"></i>Resultados generados</span>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                @include('Redistribucion_Sugeridas.table')
            </div>
        </div>

    </div>

    <script>
        $(document).ready(function() {

            $('.select2').select2({
                width: '100%',
                allowClear: true,
                placeholder: function() {
                    return $(this).find('option:first').text();
                }
            });

            $('#limpiarFiltros').on('click', function() {

                $('#periodo').val('').trigger('change');
                $('#grupo_plan').val('').trigger('change');
                $('#linea').val('').trigger('change');
                $('#temporada').val('').trigger('change');

            });

            $('#formAnalisis').on('submit', function() {

                const periodo = $('#periodo').val();

                if (!periodo) {

                    Swal.fire({
                        icon: 'warning',
                        title: 'Período requerido',
                        text: 'Debe seleccionar un período para ejecutar el análisis.',
                        confirmButtonText: 'Entendido'
                    });

                    return false;
                }

                const btn = $('#btnAnalizar');

                btn.prop('disabled', true);
                btn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Analizando...');

            });

        });
    </script>
@endsection
