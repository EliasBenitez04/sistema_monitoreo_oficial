@extends('layouts.app')

@section('content')
    <style>
        .redistribucion-page {
            --rd-blue: #2563eb;
            --rd-ink: #172b4d;
            --rd-muted: #607086;
            padding-top: 20px;
            padding-bottom: 28px;
            color: var(--rd-ink);
        }

        .redistribucion-page *,
        .rd-group-dropdown * {
            box-sizing: border-box;
        }

        .redistribucion-page .card {
            border: 1px solid #e3eaf3;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(30, 55, 90, .045);
            overflow: visible;
        }

        .redistribucion-page .module-header {
            background: linear-gradient(120deg, #eef4ff, #f8fbff);
            border: 1px solid #dce7fa;
            padding: 24px;
            border-radius: 16px;
            margin-bottom: 22px !important;
        }

        .redistribucion-page .module-header h4 {
            font-size: 24px;
            letter-spacing: -.5px;
            color: #193455;
        }

        .redistribucion-page .module-header .small {
            color: #536b86;
            font-size: 13px;
        }

        .redistribucion-page .module-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            flex-shrink: 0;
            border-radius: 14px;
            background: #fff;
            color: var(--rd-blue);
            box-shadow: 0 3px 12px #dce7f5;
            font-size: 21px;
        }

        .redistribucion-page .card-header {
            border-radius: 16px 16px 0 0 !important;
            border-bottom: 1px solid #e9eef5;
            padding: 20px 24px !important;
        }

        .redistribucion-page .card-body {
            padding: 24px;
        }

        .redistribucion-page .filter-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #334763;
            margin-bottom: 8px;
        }

        .redistribucion-page .filter-label i {
            margin-right: 7px;
        }

        .redistribucion-page .filter-help {
            display: block;
            margin-top: 8px;
            color: var(--rd-muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .redistribucion-page .form-control {
            min-height: 44px;
            border: 1px solid #cbd7e6;
            border-radius: 9px;
            font-size: 14px;
        }

        .redistribucion-page .select2-container {
            width: 100% !important;
            min-width: 0;
        }

        .redistribucion-page .select2-selection--single {
            height: 44px !important;
            border: 1px solid #cbd7e6 !important;
            border-radius: 9px !important;
            background: #fff;
        }

        .redistribucion-page .select2-selection--single .select2-selection__rendered {
            line-height: 42px !important;
            padding-left: 13px !important;
            padding-right: 34px !important;
            color: #243b56 !important;
            font-size: 14px;
        }

        .redistribucion-page .select2-selection--single .select2-selection__arrow {
            height: 42px !important;
            right: 7px !important;
        }

        .redistribucion-page .select2-container--focus .select2-selection,
        .redistribucion-page .select2-container--open .select2-selection {
            border-color: #6c9bf3 !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .10);
        }

        .redistribucion-page .group-panel {
            position: relative;
            min-width: 0;
            background: #f6f9fe;
            border: 1px solid #dce6f3;
            border-radius: 12px;
            padding: 18px;
            margin: 4px 0 20px;
        }

        .redistribucion-page .group-panel-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 10px;
        }

        .redistribucion-page .group-panel-heading .filter-label {
            margin: 0;
            font-size: 14px;
        }

        .redistribucion-page .group-tools {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .redistribucion-page .group-count {
            display: inline-block;
            background: #e6efff;
            color: #234e98;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .redistribucion-page .group-clear {
            border: 0;
            background: transparent;
            color: #355f9a;
            font-size: 12px;
            padding: 5px 3px;
            text-decoration: underline;
            cursor: pointer;
        }

        .redistribucion-page .group-clear:disabled {
            color: #788799;
            cursor: default;
            text-decoration: none;
        }

        .redistribucion-page .group-panel .select2-selection--multiple {
            display: block !important;
            width: 100%;
            min-height: 58px !important;
            height: auto !important;
            padding: 8px !important;
            border: 1px solid #bdcfe5 !important;
            border-radius: 10px !important;
            background: #fff !important;
        }

        .redistribucion-page .group-panel .select2-selection__rendered {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center;
            gap: 7px;
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            overflow: visible !important;
            white-space: normal !important;
        }

        .redistribucion-page .group-panel .select2-selection__choice {
            position: relative;
            float: none !important;
            display: block !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 8px 12px 8px 32px !important;
            border: 1px solid #cdddfa !important;
            border-radius: 8px !important;
            background: #eef4ff !important;
            color: #224779 !important;
            font-size: 13px;
            line-height: 1.45 !important;
            white-space: normal !important;
            overflow-wrap: anywhere;
            text-overflow: clip !important;
        }

        .redistribucion-page .group-panel .select2-selection__choice__display {
            padding: 0 !important;
            white-space: normal !important;
            overflow-wrap: anywhere;
        }

        .redistribucion-page .group-panel .select2-selection__choice__remove {
            position: absolute !important;
            left: 5px !important;
            top: 5px !important;
            bottom: auto !important;
            float: none !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 23px;
            height: 25px;
            line-height: 23px !important;
            text-align: center;
            border: 0 !important;
            border-radius: 5px;
            background: transparent;
            color: #365a8d !important;
            font-size: 18px;
        }

        .redistribucion-page .group-panel .select2-selection__choice__remove:hover {
            background: #dae7fb !important;
            color: #143f7e !important;
        }

        .redistribucion-page .group-panel .select2-search--inline {
            float: none !important;
            flex: 1 1 240px;
            min-width: 0;
            max-width: 100%;
            margin: 0 !important;
        }

        .redistribucion-page .group-panel .select2-search__field {
            width: 100% !important;
            min-width: 0 !important;
            min-height: 30px;
            height: 30px !important;
            margin: 2px 0 !important;
            padding: 4px !important;
            font: inherit !important;
            color: #263e5d;
        }

        .redistribucion-page .group-panel select[multiple]:not(.select2-hidden-accessible) {
            min-height: 180px;
        }

        .rd-group-dropdown .select2-dropdown {
            border: 1px solid #bdcfe5;
            border-radius: 10px;
            box-shadow: 0 12px 30px rgba(22, 48, 83, .16);
            overflow: hidden;
        }

        .rd-group-dropdown .select2-results__options {
            max-height: 300px !important;
        }

        .rd-group-dropdown .select2-results__option {
            padding: 11px 14px;
            font-size: 13px;
            line-height: 1.5;
            white-space: normal !important;
            overflow-wrap: anywhere;
            border-bottom: 1px solid #edf2f8;
        }

        .rd-group-dropdown .select2-results__option[aria-selected=true],
        .rd-group-dropdown .select2-results__option--selected {
            background: #eaf2ff;
            color: #214d87;
            font-weight: 600;
        }

        .rd-group-dropdown .select2-results__option--highlighted[aria-selected],
        .rd-group-dropdown .select2-results__option--highlighted.select2-results__option--selectable {
            background: #2563eb;
            color: #fff;
        }

        .redistribucion-page .action-bar {
            background: #f8fafc;
            border: 1px solid #e4ebf3;
            border-radius: 12px;
            padding: 18px;
        }

        .redistribucion-page .info-box {
            color: #5d6d82;
            font-size: 13px;
            line-height: 1.6;
        }

        .redistribucion-page .btn {
            border-radius: 9px;
            font-weight: 600;
            font-size: 13px;
            padding: 11px 16px;
        }

        .redistribucion-page .btn-primary {
            background: #2563eb;
            border-color: #2563eb;
            box-shadow: 0 3px 8px rgba(37, 99, 235, .15);
        }

        .redistribucion-page .btn-primary:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
        }

        .redistribucion-page button:focus-visible {
            outline: 3px solid #8bb4fc;
            outline-offset: 3px;
        }

        .redistribucion-page .results-header {
            padding: 20px 24px;
            border-radius: 16px 16px 0 0;
        }

        .redistribucion-page .results-title {
            font-size: 17px;
            font-weight: 700;
            color: #203b5b;
            margin: 0;
        }

        .redistribucion-page .results-subtitle {
            font-size: 12px;
            color: var(--rd-muted);
            margin-top: 5px;
        }

        .redistribucion-page .badge-results {
            display: inline-block;
            background: #eef3f9;
            color: #52677f;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .redistribucion-page .results-table-scroll {
            overflow-x: auto;
        }

        .redistribucion-page .results-table-scroll table {
            margin-bottom: 0;
        }

        .redistribucion-page .results-table-scroll thead th {
            background: #f2f6fb;
            color: #3b536e;
            font-size: 12px;
            border-bottom: 1px solid #dde6f1;
            vertical-align: middle;
        }

        .redistribucion-page .results-table-scroll tbody td {
            vertical-align: middle;
            font-size: 13px;
        }

        @media (max-width:767px) {

            .redistribucion-page .module-header,
            .redistribucion-page .card-body {
                padding: 17px;
            }

            .redistribucion-page .module-header h4 {
                font-size: 21px;
            }

            .redistribucion-page .group-panel {
                padding: 13px;
            }

            .redistribucion-page .group-panel .select2-selection__choice {
                width: 100%;
            }

            .redistribucion-page .action-buttons {
                display: flex;
                flex-wrap: wrap;
                gap: 9px;
                margin-top: 10px;
            }

            .redistribucion-page .action-buttons .btn {
                flex: 1 1 180px;
                margin: 0 !important;
            }
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
                        <div class="col-lg-4 col-md-6 mb-3">
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

                        {{-- LINEA --}}
                        <div class="col-lg-4 col-md-6 mb-3">
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
                        <div class="col-lg-4 col-md-6 mb-3">
                            <label for="temporada" class="filter-label">
                                <i class="fas fa-clock text-primary"></i>Temporada
                            </label>
                            <select name="temporada" id="temporada" class="form-control select2">
                                <option value="">Todas las temporadas</option>
                                @foreach ($temporadas as $temporada)
                                    <option value="{{ $temporada }}"
                                        {{ old('temporada') == $temporada ? 'selected' : '' }}>
                                        {{ $temporada }}</option>
                                @endforeach
                            </select>
                            <span class="filter-help">Seleccionar temporada del producto.</span>
                        </div>
                    </div>


                    {{-- GRUPO PLAN: ancho completo y seleccion multiple --}}
                    <div class="group-panel rd-group-dropdown" id="grupoPlanPanel">
                        <div class="group-panel-heading">
                            <label for="grupo_plan" class="filter-label">
                                <i class="fas fa-layer-group text-primary" aria-hidden="true"></i>Grupo Plan
                            </label>
                            <div class="group-tools">
                                <span class="group-count" id="contadorGrupos" aria-live="polite">Todos los grupos</span>
                                <button type="button" class="group-clear" id="quitarGrupos">Quitar selección</button>
                            </div>
                        </div>
                        <select name="grupo_plan[]" id="grupo_plan" class="form-control select2" multiple
                            data-placeholder="Buscá y agregá uno o varios grupos…" data-close-on-select="false"
                            aria-describedby="ayudaGrupoPlan" style="width:100%;">
                            @foreach ($gruposPlan as $grupo)
                                <option value="{{ $grupo }}"
                                    {{ in_array($grupo, (array) old('grupo_plan', []), true) ? 'selected' : '' }}>
                                    {{ $grupo }}</option>
                            @endforeach
                        </select>
                        <span class="filter-help" id="ayudaGrupoPlan">Escribí para buscar. Podés agregar varios grupos y
                            quitar cada uno con la ×. Sin selección se analizan todos.</span>
                        @error('grupo_plan')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
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
                            <div class="col-md-5 text-md-right action-buttons">
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

            <div class="card-body p-0 results-table-scroll">
                @include('Redistribucion_Sugeridas.table')
            </div>
        </div>

    </div>

    <script>
        (function() {
            function iniciarRedistribucion() {
                var form = document.getElementById('formAnalisis');
                if (!form || form.dataset.redistribucionReady) return;
                form.dataset.redistribucionReady = '1';
                var grupo = document.getElementById('grupo_plan');
                var contador = document.getElementById('contadorGrupos');
                var quitar = document.getElementById('quitarGrupos');
                var boton = document.getElementById('btnAnalizar');
                var textoBoton = boton.innerHTML;
                var enviando = false;
                var jq = window.jQuery;

                function actualizarGrupos() {
                    var cantidad = grupo.selectedOptions.length;
                    contador.textContent = cantidad ? cantidad + (cantidad === 1 ? ' grupo seleccionado' :
                        ' grupos seleccionados') : 'Todos los grupos';
                    quitar.disabled = cantidad === 0;
                }

                if (jq && jq.fn.select2) {
                    // Configuracion acotada a esta vista; Grupo Plan tiene su propio placeholder.
                    jq(form).find('select.select2').each(function() {
                        var select = jq(this);
                        if (select.hasClass('select2-hidden-accessible')) select.select2('destroy');
                        var esGrupo = this.id === 'grupo_plan';
                        var opciones = {
                            width: '100%',
                            theme: 'default',
                            allowClear: !esGrupo,
                            placeholder: esGrupo ? 'Buscá y agregá uno o varios grupos…' : select.find(
                                'option[value=""]').first().text(),
                            closeOnSelect: !esGrupo,
                            language: {
                                noResults: function() {
                                    return 'No se encontraron grupos o valores';
                                },
                                searching: function() {
                                    return 'Buscando…';
                                }
                            }
                        };
                        if (esGrupo) opciones.dropdownParent = jq('#grupoPlanPanel');
                        select.select2(opciones);
                    });
                    jq(grupo).on('change.redistribucion', actualizarGrupos);
                }
                grupo.addEventListener('change', actualizarGrupos);

                function notificarCambio(elemento) {
                    if (jq && jq.fn.select2) jq(elemento).trigger('change');
                    else elemento.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }

                function vaciarGrupos() {
                    Array.from(grupo.options).forEach(function(opcion) {
                        opcion.selected = false;
                    });
                    notificarCambio(grupo);
                    actualizarGrupos();
                }
                quitar.addEventListener('click', vaciarGrupos);
                document.getElementById('limpiarFiltros').addEventListener('click', function() {
                    ['periodo', 'linea', 'temporada'].forEach(function(id) {
                        var elemento = document.getElementById(id);
                        elemento.value = '';
                        notificarCambio(elemento);
                    });
                    vaciarGrupos();
                });
                form.addEventListener('submit', function(event) {
                    if (enviando) {
                        event.preventDefault();
                        return;
                    }
                    if (!document.getElementById('periodo').value) {
                        event.preventDefault();
                        if (window.Swal && typeof window.Swal.fire === 'function') {
                            window.Swal.fire({
                                icon: 'warning',
                                title: 'Período requerido',
                                text: 'Seleccioná un período para ejecutar el análisis.',
                                confirmButtonText: 'Entendido'
                            });
                        } else form.reportValidity();
                        return;
                    }
                    enviando = true;
                    boton.disabled = true;
                    boton.setAttribute('aria-busy', 'true');
                    boton.innerHTML =
                        '<i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i>Analizando…';
                });
                window.addEventListener('pageshow', function() {
                    enviando = false;
                    boton.disabled = false;
                    boton.removeAttribute('aria-busy');
                    boton.innerHTML = textoBoton;
                    actualizarGrupos();
                });
                actualizarGrupos();
            }
            // Esperar a que el layout termine de cargar jQuery y Select2.
            if (document.readyState === 'complete') iniciarRedistribucion();
            else window.addEventListener('load', iniciarRedistribucion, {
                once: true
            });
        })();
    </script>
@endsection
