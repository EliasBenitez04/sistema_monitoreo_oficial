{{-- Clasificación manual por OT, con referencia documental obligatoria. --}}
@if(session('clasificacion_ok'))
<div class="alert alert-success">{{ session('clasificacion_ok') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger"><strong>No se guardó:</strong> {{ $errors->first() }}</div>
@endif
<div class="card cc-card mb-3" id="clasificacion-sin-destino">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <strong><i class="fas fa-clipboard-check text-primary mr-1"></i>Clasificación Ayala / Modelo Muestra</strong>
            <div class="cc-sub">Separá por OT las prendas identificadas de las realmente faltantes. No altera PT ni remisiones.</div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary no-print mt-2" data-toggle="collapse" data-target="#clasificarOtDestino">
            <i class="fas fa-pencil-alt mr-1"></i>Clasificar OT
        </button>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 col-6 mb-2"><div class="border rounded p-2"><small class="d-block text-muted">Sin destino calculado</small><strong>{{ number_format($resumen->sin_destino,0,',','.') }}</strong></div></div>
            <div class="col-md-3 col-6 mb-2"><div class="border rounded p-2"><small class="d-block text-muted">Ayala identificado</small><strong>{{ number_format($resumen->sin_destino_ayala,0,',','.') }}</strong></div></div>
            <div class="col-md-3 col-6 mb-2"><div class="border rounded p-2"><small class="d-block text-muted">Modelo Muestra identificado</small><strong>{{ number_format($resumen->sin_destino_modelo,0,',','.') }}</strong></div></div>
            <div class="col-md-3 col-6 mb-2"><div class="border rounded p-2"><small class="d-block text-muted">SIN DESTINO REAL</small><strong class="text-warning">{{ number_format($resumen->sin_destino_real,0,',','.') }}</strong></div></div>
        </div>
        <p class="small mb-2">
            <strong>Cuadre:</strong> {{ number_format($resumen->sin_destino,0,',','.') }}
            − {{ number_format($resumen->sin_destino_ayala,0,',','.') }} Ayala
            − {{ number_format($resumen->sin_destino_modelo,0,',','.') }} Modelo
            = <strong>{{ number_format($resumen->sin_destino_real,0,',','.') }} sin destino real.</strong>
            La clasificación no demuestra despacho físico; sólo documenta el destino interno informado.
        </p>
        @if($resumen->clasificaciones_invalidas > 0)
            <div class="alert alert-warning small">Hay {{ $resumen->clasificaciones_invalidas }} OT con clasificación mayor al saldo actual. No se descontaron: revisá sus registros.</div>
        @endif
        <div class="collapse no-print" id="clasificarOtDestino">
            @if(!$clasificacionDisponible)
                <div class="alert alert-warning small">Falta crear la tabla. Ejecutá <code>php artisan migrate</code> en el servidor.</div>
            @elseif($detalleSinDestinoCalculado->isEmpty())
                <p class="text-muted small">No hay OT con saldo sin destino calculado.</p>
            @else
                <p class="small text-muted">Identificá las cantidades y su respaldo para cada OT. Nunca se descuentan automáticamente 12 y 13 de otro período.</p>
                <div style="max-height:60vh;overflow:auto">
                    @foreach($detalleSinDestinoCalculado as $filaClase)
                    <form method="POST" action="{{ route('control.terminacion.reporte-pendientes-envio.clasificar') }}" class="border rounded bg-light p-2 mb-2">
                        @csrf
                        <input type="hidden" name="id_ot" value="{{ $filaClase->id_ot }}">
                        <input type="hidden" name="fecha_desde" value="{{ $fechaDesde }}">
                        <input type="hidden" name="fecha_hasta" value="{{ $fechaHasta }}">
                        <input type="hidden" name="buscar" value="{{ $buscar }}">
                        @foreach($temporadas as $filtroTemporada)
                        <input type="hidden" name="temporada[]" value="{{ $filtroTemporada }}">
                        @endforeach
                        <div class="row align-items-end">
                            <div class="col-lg-3 col-md-6 mb-2">
                                <strong>OT {{ $filaClase->nro_ot }}</strong>
                                <small class="d-block text-muted">{{ $filaClase->codigo }} · {{ $filaClase->descripcion }}</small>
                                <small>Calculado: {{ $filaClase->sin_destino_calculado }} · Real: {{ $filaClase->sin_destino_real }}</small>
                                @if($filaClase->clasificacion_invalida)
                                <small class="d-block text-danger">CLASIFICACIÓN OBSOLETA: VOLVER A VALIDAR</small>
                                @endif
                            </div>
                            <div class="col-lg-1 col-md-3 col-6 mb-2">
                                <label class="small mb-1">Ayala</label>
                                <input type="number" class="form-control form-control-sm" name="cantidad_ayala" min="0" max="{{ $filaClase->sin_destino_calculado }}" value="{{ $filaClase->sin_destino_ayala }}" required>
                            </div>
                            <div class="col-lg-2 col-md-3 col-6 mb-2">
                                <label class="small mb-1">Modelo Muestra</label>
                                <input type="number" class="form-control form-control-sm" name="cantidad_modelo" min="0" max="{{ $filaClase->sin_destino_calculado }}" value="{{ $filaClase->sin_destino_modelo }}" required>
                            </div>
                            <div class="col-lg-4 col-md-8 mb-2">
                                <label class="small mb-1">Referencia / comprobante</label>
                                <input type="text" class="form-control form-control-sm" name="referencia" minlength="5" maxlength="500" placeholder="Remisión, planilla o comprobante" value="{{ $filaClase->clasificacion_referencia }}" required>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-2">
                                @auth
                                <button type="submit" class="btn btn-sm btn-primary btn-block">Guardar OT</button>
                                @else
                                <small class="text-warning">Ingresá con tu usuario para guardar.</small>
                                @endauth
                            </div>
                        </div>
                    </form>
                    @endforeach
                </div>
            @endif
        </div>
        <small class="text-muted">El Excel usa estas clasificaciones por OT. Hasta registrarlas, el faltante real seguirá igual al calculado.</small>
    </div>
</div>
