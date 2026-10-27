@extends('layouts.app')

@section('content')
<section class="content-header"><div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
    <div><h1>{{ $articulo->codigo }} <small class="text-muted">{{ $articulo->descripcion }}</small></h1><p class="text-muted">Línea: {{ $articulo->linea ?: 'Sin línea' }}</p></div>
    <a class="btn btn-outline-secondary" href="{{ route('accesorios.index') }}"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
</div></section>
<section class="content"><div class="container-fluid">
    <div class="row">
        @foreach([
            ['Compradas', $totalComprado, 'primary'],
            ['Entrada Depósito → Central', $etapasRemision['ingreso_deposito'], 'primary'],
            ['Distribuidas desde Central', $etapasRemision['distribuido_central'], 'info'],
            ['Recibidas en locales desde Central', $etapasRemision['recibido_locales'], 'success'],
            ['Ventas netas registradas', $totalVendido, 'secondary'],
            ['Stock calculado', $stockCalculado, ($stockCalculado !== null && $stockCalculado < 0 ? 'danger' : 'success')]
        ] as $kpi)
            <div class="col-md-3 col-6"><div class="card border-{{ $kpi[2] }} shadow-sm"><div class="card-body">
                <div class="text-muted small">{{ $kpi[0] }}</div><h3 class="mb-0">{{ $kpi[1] === null ? 'Sin datos' : number_format($kpi[1], 0, ',', '.') }}</h3>
            </div></div></div>
        @endforeach
    </div>
    <div class="alert alert-light border small">
        <i class="fas fa-info-circle text-primary mr-1"></i>
        <strong>Seguimiento por etapas:</strong> las unidades que ingresan de Depósito a Casa Central
        y después salen de Casa Central hacia los locales <strong>son las mismas prendas</strong>.
        Cada cifra corresponde a una etapa diferente y <strong>no se suma entre etapas</strong>.
        Las transferencias entre locales se presentan aparte:
        <strong>{{ number_format($etapasRemision['traslado_locales'] ?? 0, 0, ',', '.') }} unidades movidas</strong>.
        No representan prendas nuevas.
    </div>
    <div class="card card-outline card-success shadow-sm">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-balance-scale mr-2"></i>Stock calculado por sucursal</h3></div>
        <div class="px-3 py-2 bg-light small text-muted">
            Corte: {{ now()->format('d/m/Y') }}. Fórmula por sucursal:
            <strong>remisiones recibidas − remisiones enviadas − ventas netas históricas</strong>.
            Las devoluciones (notas de crédito negativas) incrementan el saldo. Las remisiones sin recepción no suman al destino.
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover table-sm mb-0">
                <thead><tr>
                    <th>Sucursal</th>
                    <th class="text-right">Entradas recibidas</th>
                    <th class="text-right">Salidas remitidas</th>
                    <th class="text-right">Ventas netas</th>
                    <th class="text-right">En tránsito hacia local</th>
                    <th class="text-right">Stock calculado</th>
                </tr></thead>
                <tbody>
                @forelse($saldosStock as $fila)
                    <tr>
                        <td class="font-weight-bold">{{ $fila->sucursal }}</td>
                        <td class="text-right text-success">+{{ number_format($fila->entradas, 0, ',', '.') }}</td>
                        <td class="text-right text-danger">−{{ number_format($fila->salidas, 0, ',', '.') }}</td>
                        <td class="text-right">−{{ number_format($fila->ventas, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($fila->en_transito, 0, ',', '.') }}</td>
                        <td class="text-right">
                            <span class="badge badge-{{ $fila->saldo < 0 ? 'danger' : ($fila->saldo === 0 ? 'secondary' : 'success') }}" style="font-size:13px">
                                {{ number_format($fila->saldo, 0, ',', '.') }}
                            </span>
                        </td>
                    </tr>
                @empty <tr><td colspan="6" class="text-center text-muted p-3">No hay movimientos ni ventas vinculadas para calcular el saldo.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer small">
            <strong>Atención:</strong> un saldo negativo no se oculta: puede indicar una venta anterior a las remisiones importadas, una recepción faltante o una discrepancia de sucursal.
            El saldo supone que el historial documental importado está completo.
        </div>
    </div>
    <div class="card card-outline card-primary shadow-sm" id="historial-compras">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
            <h3 class="card-title font-weight-bold mb-1 mb-sm-0"><i class="fas fa-file-invoice-dollar text-primary mr-2"></i>Historial de compras · FCO</h3>
            <span class="badge badge-light border text-primary p-2">{{ number_format($compras->count(), 0, ',', '.') }} registros</span>
        </div>
        <div class="px-3 py-2 border-bottom bg-light small text-muted">
            <i class="fas fa-info-circle text-primary mr-1"></i>
            El costo total es <strong>cantidad × costo unitario</strong> y se actualiza automáticamente.
            La edición modifica la compra FCO seleccionada, no las ventas ni las remisiones.
            <strong>Atención:</strong> si volvés a importar el mismo FCO, los datos del archivo pueden reemplazar la edición manual.
        </div>
        @if(session('success'))
            <div class="alert alert-success mx-3 mt-3 mb-0"><i class="fas fa-check-circle mr-1"></i>{{ session('success') }}</div>
        @endif
        @if($errors->has('cantidad_compra') || $errors->has('costo_unitario') || $errors->has('precio_venta'))
            <div class="alert alert-danger mx-3 mt-3 mb-0">
                <strong>No se pudo guardar la compra:</strong>
                {{ $errors->first('cantidad_compra') ?: ($errors->first('costo_unitario') ?: $errors->first('precio_venta')) }}
            </div>
        @endif
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover table-sm mb-0 acc-fco-table">
                <thead class="thead-light">
                    <tr>
                        <th>FCO</th><th>Fecha envío</th><th class="text-right">Cantidad</th>
                        <th class="text-right">Costo unitario</th><th class="text-right">Precio venta</th>
                        <th class="text-right">Costo total</th><th class="text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($compras as $compra)
                        <tr>
                            <td class="font-weight-bold text-primary">{{ $compra->fco_nro }}</td>
                            <td>{{ \Carbon\Carbon::parse($compra->fecha_envio)->format('d/m/Y') }}</td>
                            <td class="text-right font-weight-bold">{{ number_format($compra->cantidad_compra, 0, ',', '.') }}</td>
                            <td class="text-right">Gs {{ number_format($compra->costo_unitario, 2, ',', '.') }}</td>
                            <td class="text-right">Gs {{ number_format($compra->precio_venta, 2, ',', '.') }}</td>
                            <td class="text-right font-weight-bold text-success">Gs {{ number_format($compra->cantidad_compra * $compra->costo_unitario, 2, ',', '.') }}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary acc-fco-edit"
                                    data-toggle="modal" data-target="#accEditarCompraModal"
                                    data-url="{{ route('accesorios.compras.actualizar', [$articulo->id, $compra->id]) }}"
                                    data-fco="{{ $compra->fco_nro }}"
                                    data-fecha="{{ \Carbon\Carbon::parse($compra->fecha_envio)->format('d/m/Y') }}"
                                    data-cantidad="{{ $compra->cantidad_compra }}"
                                    data-costo="{{ $compra->costo_unitario }}"
                                    data-precio="{{ $compra->precio_venta }}">
                                    <i class="fas fa-edit mr-1"></i>Editar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted text-center p-3">Sin compras registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title">Historial documental de remisiones · movimientos de las mismas unidades</h3>
        </div>
        <div class="px-3 py-2 bg-light border-bottom small">
            <i class="fas fa-warehouse text-primary mr-1"></i><strong>1. Depósito → Casa Central</strong>
            <span class="mx-2 text-muted">→</span>
            <i class="fas fa-truck text-info mr-1"></i><strong>2. Casa Central → Locales</strong>
            <span class="mx-2 text-muted">→</span>
            <span class="text-muted">3. Otros movimientos</span>
            <div class="text-muted mt-1">
                Dentro de cada etapa: fecha de remisión ascendente y número de remisión.
                Una misma prenda puede figurar en dos o más remisiones; las cantidades de esta tabla
                <strong>no deben sumarse para obtener stock ni compras.</strong>
            </div>
        </div>
        <div class="card-body table-responsive p-0"><table class="table table-striped table-sm mb-0">
            <thead><tr><th>Remisión</th><th>Fecha</th><th>Origen</th><th>Destino</th><th class="text-right">Cantidad</th><th>Recepción</th><th>Estado</th></tr></thead><tbody>
            @forelse($remisiones as $remision)
                <tr><td>{{ $remision->serie }} - {{ $remision->numero_remision }}</td>
                    <td>{{ $remision->fecha_remision ? \Carbon\Carbon::parse($remision->fecha_remision)->format('d/m/Y') : '—' }}</td>
                    <td>{{ $remision->sucursal_salida ?: $remision->cod_sucursal_salida }}</td>
                    <td>{{ $remision->sucursal_destino ?: $remision->cod_sucursal_destino }}</td>
                    <td class="text-right">{{ number_format($remision->cantidad, 0, ',', '.') }}</td>
                    <td>{{ $remision->fecha_recepcion ? \Carbon\Carbon::parse($remision->fecha_recepcion)->format('d/m/Y') : 'Pendiente' }}</td>
                    <td><span class="badge badge-{{ $remision->fecha_recepcion ? 'success' : 'warning' }}">{{ $remision->fecha_recepcion ? 'RECIBIDO' : 'EN TRÁNSITO' }}</span></td></tr>
            @empty <tr><td colspan="7" class="text-center text-muted p-3">Sin remisiones vinculadas.</td></tr> @endforelse
            </tbody></table></div>
        <div class="card-footer">{{ $remisiones->links() }}</div>
    </div>
    <div class="card card-outline card-success">
        <div class="card-header"><h3 class="card-title">Ventas netas por local · código completo</h3></div>
        <div class="card-body table-responsive p-0"><table class="table table-sm table-striped mb-0">
            <thead><tr><th>Local</th><th class="text-right">Unidades netas</th></tr></thead><tbody>
            @forelse($ventasPorLocal as $venta)
                <tr><td>{{ $venta->local }}</td><td class="text-right">{{ number_format($venta->cantidad, 0, ',', '.') }}</td></tr>
            @empty <tr><td colspan="2" class="text-muted text-center p-3">Sin ventas importadas.</td></tr> @endforelse
            </tbody></table></div>
    </div>
    <div class="alert alert-light border small"><strong>Alcance:</strong> las compras por FCO se conservan como referencia, pero no se suman directamente al stock. El saldo se calcula una sola vez en cada sucursal, a partir de las recepciones, los envíos y las ventas netas acumuladas. Es un <strong>stock documental estimado</strong>, no un conteo físico. Las unidades en tránsito quedan fuera de las existencias del destino hasta su recepción.</div>
</div></section>

<div class="modal fade" id="accEditarCompraModal" tabindex="-1" role="dialog" aria-labelledby="accEditarCompraTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content acc-fco-modal">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold" id="accEditarCompraTitulo"><i class="fas fa-edit mr-2"></i>Editar compra FCO</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="accEditarCompraForm" method="POST" action="">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="alert alert-light border mb-3 small">
                        <strong>Accesorio:</strong> {{ $articulo->codigo }} · {{ $articulo->descripcion }}
                        <div class="mt-1">FCO <strong id="accEditarFco"></strong> · Envío <strong id="accEditarFecha"></strong></div>
                    </div>
                    <div class="form-group">
                        <label for="accEditarCantidad" class="font-weight-bold">Cantidad comprada <span class="text-danger">*</span></label>
                        <input id="accEditarCantidad" name="cantidad_compra" type="number" min="1" max="2147483647" step="1" required class="form-control">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-sm-6">
                            <label for="accEditarCosto" class="font-weight-bold">Costo unitario (Gs) <span class="text-danger">*</span></label>
                            <input id="accEditarCosto" name="costo_unitario" type="number" min="0" max="9999999999.99" step="0.01" required class="form-control">
                        </div>
                        <div class="form-group col-sm-6">
                            <label for="accEditarPrecio" class="font-weight-bold">Precio de venta (Gs) <span class="text-danger">*</span></label>
                            <input id="accEditarPrecio" name="precio_venta" type="number" min="0" max="9999999999.99" step="0.01" required class="form-control">
                        </div>
                    </div>
                    <div class="acc-fco-total">
                        <span>Costo total calculado</span>
                        <strong id="accEditarTotal" aria-live="polite">Gs 0</strong>
                        <small>Cantidad × costo unitario. Este campo no se guarda por separado.</small>
                    </div>
                    <p class="small text-muted mt-3 mb-0"><i class="fas fa-info-circle mr-1"></i>Al guardar se actualizarán las métricas de compras, costo promedio, inversión y ganancia estimada al volver a consultar el monitoreo.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="accEditarGuardar"><i class="fas fa-save mr-1"></i>Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('page_css')
<style>
    .acc-fco-table th{font-size:12px;font-weight:800;white-space:nowrap;color:#284c7c}
    .acc-fco-table td{vertical-align:middle!important;font-size:13px;padding:11px!important}
    .acc-fco-modal{border:0;border-radius:12px;overflow:hidden;box-shadow:0 18px 48px rgba(19,51,95,.24)}
    .acc-fco-modal .modal-header{border:0;padding:16px 20px}
    .acc-fco-modal .modal-body{padding:20px}
    .acc-fco-modal .form-control{border:1px solid #c4d4e7;border-radius:7px;height:40px}
    .acc-fco-total{background:#eaf4ff;border:1px solid #b8d5f5;border-radius:10px;padding:15px 18px;color:#1e4b85}
    .acc-fco-total span{display:block;font-size:12px;font-weight:800}
    .acc-fco-total strong{display:block;font-size:24px;font-weight:900;color:#123f7f;margin:5px 0}
    .acc-fco-total small{display:block;font-size:11px;color:#517098}
</style>
@endpush

@push('page_scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('accEditarCompraModal');
    if (!modal) return;
    var form = document.getElementById('accEditarCompraForm');
    var cantidad = document.getElementById('accEditarCantidad');
    var costo = document.getElementById('accEditarCosto');
    var precio = document.getElementById('accEditarPrecio');
    var total = document.getElementById('accEditarTotal');
    var guardar = document.getElementById('accEditarGuardar');
    var moneda = new Intl.NumberFormat('es-PY', {minimumFractionDigits:0,maximumFractionDigits:2});

    function calcular() {
        var unidades = Number(cantidad.value);
        var importe = Number(costo.value);
        if (cantidad.value === '' || costo.value === '' || !Number.isFinite(unidades) || !Number.isFinite(importe) || unidades <= 0 || importe < 0) {
            total.textContent = '—';
            return;
        }
        total.textContent = 'Gs ' + moneda.format(Math.round(unidades * importe * 100) / 100);
    }

    $(modal).on('show.bs.modal', function (evento) {
        var boton = $(evento.relatedTarget);
        if (!boton.length) return;
        form.action = boton.attr('data-url');
        document.getElementById('accEditarFco').textContent = boton.attr('data-fco') || '';
        document.getElementById('accEditarFecha').textContent = boton.attr('data-fecha') || '';
        cantidad.value = boton.attr('data-cantidad');
        costo.value = boton.attr('data-costo');
        precio.value = boton.attr('data-precio');
        guardar.disabled = false;
        calcular();
    });

    [cantidad, costo, precio].forEach(function (campo) {
        campo.addEventListener('input', calcular);
    });

    form.addEventListener('submit', function (evento) {
        if (!form.checkValidity()) return;
        if (!window.confirm('¿Guardar los cambios de esta compra FCO? Se actualizarán los indicadores calculados.')) {
            evento.preventDefault();
            return;
        }
        guardar.disabled = true;
    });
});
</script>
@endpush
