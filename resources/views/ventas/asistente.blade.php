@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1><i class="fas fa-robot mr-2 text-primary"></i>Asistente de Ventas</h1>
            <p class="text-muted">Preguntá sobre ventas, sucursales, vendedores, artículos y tendencias de tus datos importados.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('ventas.index') }}">
            <i class="fas fa-arrow-left mr-1"></i>Volver a Ventas
        </a>
    </div>
</section>
<section class="content">
<div class="container-fluid">
    <div class="card card-outline card-primary shadow-sm">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label for="av-desde">Desde</label>
                    <input id="av-desde" type="date" class="form-control"
                           value="{{ now()->subDays(29)->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label for="av-hasta">Hasta</label>
                    <input id="av-hasta" type="date" class="form-control"
                           value="{{ now()->format('Y-m-d') }}">
                </div>
            </div>
            <small class="d-block mt-2 text-muted">
                Las respuestas se basan en datos de ventas importados dentro del período elegido
                (máximo 366 días). Rankings limitados a 100 registros.
                Se envían métricas agregadas al proveedor de IA, no datos personales de clientes.
            </small>
        </div>
    </div>
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <strong><i class="far fa-comments mr-1 text-primary"></i>Conversación</strong>
        </div>
        <div id="av-mensajes" class="card-body" role="log" aria-live="polite"
             style="height:430px;overflow-y:auto;background:#f8fafc">
            <div class="mb-3 p-3 bg-white rounded border">
                ¡Hola! Podés preguntarme, por ejemplo: ¿cuánto vendimos?,
                ¿qué sucursal vendió más?, ¿cuáles fueron los productos más vendidos?,
                ¿cómo evolucionaron las ventas por día? o ¿qué vendedor tuvo mayores ventas?
            </div>
        </div>
        <div class="card-footer bg-white">
            <form id="av-form">
                @csrf
                <label for="av-pregunta">Tu pregunta</label>
                <div class="input-group">
                    <input id="av-pregunta" class="form-control" maxlength="1200"
                           required autocomplete="off"
                           placeholder="Ej.: ¿Cuál fue el mejor día de ventas?">
                    <div class="input-group-append">
                        <button id="av-enviar" class="btn btn-primary" type="submit">
                            <i class="fas fa-paper-plane mr-1"></i>Enviar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</section>
@endsection

@push('page_scripts')
<script>
(function () {
    'use strict';
    const form = document.getElementById('av-form');
    const input = document.getElementById('av-pregunta');
    const area = document.getElementById('av-mensajes');
    const btn = document.getElementById('av-enviar');

    function mensaje(texto, mio) {
        const div = document.createElement('div');
        div.className = 'mb-3 p-3 rounded border ' +
            (mio ? 'bg-primary text-white ml-auto' : 'bg-white');
        div.style.maxWidth = '94%';
        div.style.whiteSpace = 'pre-wrap';
        div.textContent = texto;
        area.appendChild(div);
        area.scrollTop = area.scrollHeight;
        return div;
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        const pregunta = input.value.trim();
        if (!pregunta || btn.disabled) return;
        const desde = document.getElementById('av-desde').value;
        const hasta = document.getElementById('av-hasta').value;
        if (!desde || !hasta || desde > hasta) {
            mensaje('Elegí un rango de fechas válido.', false);
            return;
        }
        mensaje(pregunta, true);
        input.value = '';
        btn.disabled = true;
        const provisional = mensaje('Analizando ventas...', false);
        try {
            const response = await fetch(@json(route('ventas.asistente.preguntar')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                credentials: 'same-origin',
                body: JSON.stringify({pregunta: pregunta, desde: desde, hasta: hasta})
            });
            const data = await response.json();
            provisional.textContent = response.ok
                ? data.respuesta
                : (data.error || data.message || 'No fue posible procesar la consulta.');
        } catch (e) {
            provisional.textContent = 'Error de conexión. Comprobá la red y volvé a intentar.';
        } finally {
            btn.disabled = false;
            input.focus();
        }
    });
})();
</script>
@endpush
