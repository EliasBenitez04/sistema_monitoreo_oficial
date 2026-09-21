@extends('layouts.app')

@section('content')
    <style>
        .rd-config-wrap { max-width: 1180px; margin: 0 auto; }
        .rd-config-hero {
            border: 0;
            border-radius: 16px;
            background: linear-gradient(135deg, #1f4e79 0%, #2878bd 100%);
            color: #fff;
            box-shadow: 0 10px 30px rgba(31, 78, 121, .18);
        }
        .rd-config-card {
            border: 1px solid #e8edf3;
            border-radius: 14px;
            box-shadow: 0 6px 20px rgba(15, 23, 42, .05);
        }
        .rd-config-card .card-header {
            border-bottom: 1px solid #edf1f5;
            background: #fff;
            border-radius: 14px 14px 0 0;
        }
        .rd-setting {
            height: 100%;
            padding: 18px;
            border: 1px solid #e7edf4;
            border-radius: 12px;
            background: #fbfcfe;
        }
        .rd-setting-title { font-weight: 700; color: #24364b; margin-bottom: 4px; }
        .rd-setting-help { color: #748397; font-size: 12px; line-height: 1.45; min-height: 35px; }
        .rd-value-addon { min-width: 56px; justify-content: center; font-weight: 600; }
        .rd-switch-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 0;
            border-bottom: 1px solid #edf1f5;
        }
        .rd-switch-row:last-child { border-bottom: 0; }
        .rd-switch-row .custom-control { min-width: 48px; }
        .rd-formula {
            padding: 14px 16px;
            border-left: 4px solid #17a2b8;
            border-radius: 8px;
            background: #f2fbfd;
            color: #36505b;
        }
        .rd-sticky-actions {
            position: sticky;
            bottom: 0;
            z-index: 5;
            background: rgba(255,255,255,.97);
            border-top: 1px solid #e9edf2;
            padding: 14px 0 4px;
        }
    </style>

    <section class="content-header">
        <div class="container-fluid rd-config-wrap">
            <div class="card rd-config-hero">
                <div class="card-body py-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h3 class="mb-1"><i class="fas fa-sliders-h mr-2"></i>Configuración de Redistribución</h3>
                            <div style="opacity:.88">
                                Parámetros centrales utilizados por el método <strong>analizar</strong>.
                            </div>
                        </div>
                        <a href="{{ route('RedistribucionSugeridas.index') }}" class="btn btn-light mt-3 mt-md-0">
                            <i class="fas fa-arrow-left mr-1"></i> Volver al análisis
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3 pb-4">
        <div class="rd-config-wrap">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <div class="font-weight-bold mb-1">Revise los datos ingresados:</div>
                    <ul class="mb-0 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (!$esOperativa)
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-1"></i>
                    Todavía no existe una configuración del motor actual. Se muestran los valores por defecto que ya usa
                    el análisis. Al guardar, esta configuración pasará a ser la activa.
                </div>
            @endif

            <form method="POST" action="{{ route('redistribucion-configs.store') }}" id="formRedistribucionConfig">
                @csrf

                <div class="card rd-config-card mb-3">
                    <div class="card-header py-3">
                        <h5 class="mb-0"><i class="fas fa-chart-line text-primary mr-2"></i>Demanda y cobertura</h5>
                    </div>
                    <div class="card-body">
                        <div class="rd-formula mb-4">
                            <strong>Cómo calcula el objetivo:</strong>
                            venta diaria × días de cobertura × (1 + margen de seguridad), respetando siempre el stock
                            mínimo de reserva.
                        </div>

                        <div class="row">
                            <div class="col-lg-6 mb-3">
                                <div class="rd-setting">
                                    <label class="rd-setting-title" for="metodo_demanda">Método de cobertura</label>
                                    <div class="rd-setting-help mb-2">
                                        Automática cubre hasta fin de mes; si el mes ya cerró, usa 7 días. Fija usa
                                        siempre la cantidad indicada.
                                    </div>
                                    <select class="form-control" name="metodo_demanda" id="metodo_demanda">
                                        <option value="COBERTURA_AUTO"
                                            {{ old('metodo_demanda', $valores['metodo_demanda']) === 'COBERTURA_AUTO' ? 'selected' : '' }}>
                                            Automática según período
                                        </option>
                                        <option value="COBERTURA_FIJA"
                                            {{ old('metodo_demanda', $valores['metodo_demanda']) === 'COBERTURA_FIJA' ? 'selected' : '' }}>
                                            Cobertura fija
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-6 mb-3">
                                <div class="rd-setting">
                                    <label class="rd-setting-title" for="dias_cobertura">Días de cobertura fija</label>
                                    <div class="rd-setting-help mb-2">
                                        Horizonte que se desea cubrir cuando el método está en modo fijo.
                                    </div>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="dias_cobertura"
                                            id="dias_cobertura" min="1" max="90"
                                            value="{{ old('dias_cobertura', $valores['dias_cobertura']) }}">
                                        <div class="input-group-append">
                                            <span class="input-group-text rd-value-addon">días</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 mb-3">
                                <div class="rd-setting">
                                    <label class="rd-setting-title" for="seguridad_porcentaje">Margen de seguridad</label>
                                    <div class="rd-setting-help mb-2">
                                        Incrementa la demanda proyectada para reducir faltantes.
                                    </div>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="seguridad_porcentaje"
                                            id="seguridad_porcentaje" min="0" max="100"
                                            value="{{ old('seguridad_porcentaje', $valores['seguridad_porcentaje']) }}" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text rd-value-addon">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 mb-3">
                                <div class="rd-setting">
                                    <label class="rd-setting-title" for="stock_minimo_origen">Reserva mínima</label>
                                    <div class="rd-setting-help mb-2">
                                        Stock mínimo que el cálculo debe conservar antes de liberar excedentes.
                                    </div>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="stock_minimo_origen"
                                            id="stock_minimo_origen" min="1" max="100"
                                            value="{{ old('stock_minimo_origen', $valores['stock_minimo_origen']) }}" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text rd-value-addon">un.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 mb-3">
                                <div class="rd-setting">
                                    <label class="rd-setting-title" for="venta_minima">Venta mínima destino</label>
                                    <div class="rd-setting-help mb-2">
                                        Un local necesita alcanzar al menos esta venta para recibir mercadería.
                                    </div>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="venta_minima"
                                            id="venta_minima" min="1"
                                            value="{{ old('venta_minima', $valores['venta_minima']) }}" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text rd-value-addon">un.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card rd-config-card mb-3">
                    <div class="card-header py-3">
                        <h5 class="mb-0"><i class="fas fa-lock text-warning mr-2"></i>Bloqueos y seguridad operativa</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-5 mb-3 mb-lg-0">
                                <div class="rd-setting">
                                    <label class="rd-setting-title" for="dias_bloqueo">Días de bloqueo</label>
                                    <div class="rd-setting-help mb-2">
                                        Evita volver a mover el mismo código/local después de una redistribución
                                        finalizada recientemente. Use 0 para no bloquear finalizados por fecha.
                                    </div>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="dias_bloqueo"
                                            id="dias_bloqueo" min="0" max="365"
                                            value="{{ old('dias_bloqueo', $valores['dias_bloqueo']) }}" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text rd-value-addon">días</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-7">
                                <div class="rd-setting">
                                    <div class="rd-switch-row">
                                        <div>
                                            <div class="rd-setting-title">Bloquear pendientes y aprobadas</div>
                                            <div class="rd-setting-help">No reutiliza código/local mientras exista una sugerencia abierta.</div>
                                        </div>
                                        <div class="custom-control custom-switch">
                                            <input type="hidden" name="bloquear_pendientes" value="0">
                                            <input type="checkbox" class="custom-control-input" id="bloquear_pendientes"
                                                name="bloquear_pendientes" value="1"
                                                {{ old('bloquear_pendientes', $valores['bloquear_pendientes']) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="bloquear_pendientes"></label>
                                        </div>
                                    </div>

                                    <div class="rd-switch-row">
                                        <div>
                                            <div class="rd-setting-title">Bloquear movimientos en proceso</div>
                                            <div class="rd-setting-help">Evita generar otra transferencia mientras la anterior sigue operativa.</div>
                                        </div>
                                        <div class="custom-control custom-switch">
                                            <input type="hidden" name="bloquear_en_proceso" value="0">
                                            <input type="checkbox" class="custom-control-input" id="bloquear_en_proceso"
                                                name="bloquear_en_proceso" value="1"
                                                {{ old('bloquear_en_proceso', $valores['bloquear_en_proceso']) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="bloquear_en_proceso"></label>
                                        </div>
                                    </div>

                                    <div class="rd-switch-row">
                                        <div>
                                            <div class="rd-setting-title">Bloquear finalizados recientes</div>
                                            <div class="rd-setting-help">Aplica el período definido en “Días de bloqueo”.</div>
                                        </div>
                                        <div class="custom-control custom-switch">
                                            <input type="hidden" name="bloquear_finalizados_recientes" value="0">
                                            <input type="checkbox" class="custom-control-input"
                                                id="bloquear_finalizados_recientes" name="bloquear_finalizados_recientes"
                                                value="1"
                                                {{ old('bloquear_finalizados_recientes', $valores['bloquear_finalizados_recientes']) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="bloquear_finalizados_recientes"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @can('redistribucionsugerencia create')
                    <div class="rd-sticky-actions text-right">
                        <a href="{{ route('RedistribucionSugeridas.index') }}" class="btn btn-light border mr-2">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary px-4" id="btnGuardarConfig">
                            <i class="fas fa-save mr-1"></i>Guardar configuración
                        </button>
                    </div>
                @endcan
            </form>
        </div>
    </div>

    <script>
        (function () {
            var metodo = document.getElementById('metodo_demanda');
            var dias = document.getElementById('dias_cobertura');
            var form = document.getElementById('formRedistribucionConfig');
            var btn = document.getElementById('btnGuardarConfig');

            function actualizarCobertura() {
                var fija = metodo.value === 'COBERTURA_FIJA';
                dias.disabled = !fija;
                dias.required = fija;
                dias.closest('.rd-setting').style.opacity = fija ? '1' : '.62';
            }

            metodo.addEventListener('change', actualizarCobertura);
            actualizarCobertura();

            if (form && btn) {
                form.addEventListener('submit', function () {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Guardando…';
                });
            }
        })();
    </script>
@endsection
