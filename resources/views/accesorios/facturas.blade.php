@extends('layouts.app')

@section('content')
@php
    $n = function ($valor) { return number_format((float) $valor, 0, ',', '.'); };
    $dinero = function ($valor) { return 'Gs ' . number_format((float) $valor, 2, ',', '.'); };
    $filtrosActivos = $buscar !== '' || $fco !== '' || $linea !== '' || $desde !== '' || $hasta !== '';
@endphp

<div id="accfacturas">
    <section class="acf-hero">
        <div>
            <div class="acf-eyebrow"><i class="fas fa-file-invoice mr-1"></i> ACCESORIOS · GESTIÓN DE DOCUMENTOS</div>
            <h1>Compras · Facturas FCO</h1>
            <p>Consultá y corregí las líneas de compra importadas, con sus cantidades y valores originales.</p>
        </div>
        <div class="acf-actions">
            <a href="{{ route('accesorios.index') }}" class="acf-btn acf-ghost"><i class="fas fa-chart-line"></i> Monitoreo</a>
            <a href="{{ route('accesorios.listado') }}" class="acf-btn acf-ghost"><i class="fas fa-list"></i> Artículos</a>
            <a href="{{ route('accesorios.importar.form') }}" class="acf-btn acf-light"><i class="fas fa-file-import"></i> Importar compras</a>
        </div>
    </section>

    @if(session('success'))
        <div class="alert alert-success acf-notice"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger acf-notice">
            <strong><i class="fas fa-exclamation-circle mr-1"></i> No se guardaron los cambios.</strong>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    <section class="acf-filter-panel">
        <div class="acf-section-title"><i class="fas fa-filter"></i> Consultar facturas</div>
        <form action="{{ route('accesorios.facturas.index') }}" method="GET" class="acf-filter-grid">
            <div>
                <label for="acf-buscar">Código o descripción</label>
                <input type="text" name="buscar" id="acf-buscar" class="form-control" maxlength="100" placeholder="Buscar artículo..." value="{{ $buscar }}">
            </div>
            <div>
                <label for="acf-fco">Número FCO</label>
                <input type="text" name="fco" id="acf-fco" class="form-control" maxlength="50" list="acf-fcos" placeholder="Todos los FCO" value="{{ $fco }}">
                <datalist id="acf-fcos">@foreach($opcionesFco as $numero)<option value="{{ $numero }}"></option>@endforeach</datalist>
            </div>
            <div>
                <label for="acf-linea">Línea</label>
                <select name="linea" id="acf-linea" class="form-control">
                    <option value="">Todas las líneas</option>
                    @foreach($opcionesLineas as $opcion)
                        <option value="{{ $opcion }}" {{ $opcion === $linea ? 'selected' : '' }}>{{ $opcion }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="acf-desde">Fecha desde</label>
                <input type="date" name="desde" id="acf-desde" class="form-control" value="{{ $desde }}">
            </div>
            <div>
                <label for="acf-hasta">Fecha hasta</label>
                <input type="date" name="hasta" id="acf-hasta" class="form-control" value="{{ $hasta }}">
            </div>
            <div class="acf-filter-actions">
                <button type="submit" class="acf-btn acf-search"><i class="fas fa-search"></i> Buscar</button>
                <a href="{{ route('accesorios.facturas.index') }}" class="acf-btn acf-reset"><i class="fas fa-undo"></i> Limpiar</a>
            </div>
        </form>
        @if($filtrosActivos)
            <div class="acf-filter-summary"><i class="fas fa-info-circle mr-1"></i> Los subtotales muestran únicamente las líneas que coinciden con los filtros.</div>
        @endif
    </section>

    <section class="acf-summary" aria-label="Resumen de documentos de esta página">
        <div class="acf-metric"><span><i class="fas fa-file-invoice"></i> Facturas en esta página</span><strong>{{ $n($documentos->count()) }}</strong></div>
        <div class="acf-metric"><span><i class="fas fa-barcode"></i> Líneas de compra</span><strong>{{ $n($documentos->sum('renglones')) }}</strong></div>
        <div class="acf-metric"><span><i class="fas fa-boxes"></i> Unidades compradas</span><strong>{{ $n($documentos->sum('unidades')) }}</strong></div>
        <div class="acf-metric acf-metric-money"><span><i class="fas fa-coins"></i> Costo total</span><strong>{{ $dinero($documentos->sum('costo_total')) }}</strong></div>
    </section>

    <div id="facturas">
        <div class="acf-list-heading">
            <div><h2>Detalle de facturas</h2><small>{{ $n($documentos->total()) }} documentos encontrados · 8 documentos por página</small></div>
            <span class="acf-pill"><i class="fas fa-lock mr-1"></i> Solo ADMIN</span>
        </div>

        @forelse($documentos as $documento)
            @php
                $claveDocumento = $documento->fco_nro . '|' . substr((string) $documento->fecha_envio, 0, 10);
                $lineasDocumento = $lineasPorDocumento->get($claveDocumento, collect());
            @endphp
            <section class="acf-document">
                <div class="acf-document-head">
                    <div class="acf-invoice-info">
                        <div class="acf-invoice-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                        <div>
                            <div class="acf-caption">FACTURA DE COMPRA · FCO</div>
                            <h3>N.º {{ $documento->fco_nro }}</h3>
                            <span class="acf-invoice-date"><i class="far fa-calendar-alt"></i> {{ \Carbon\Carbon::parse($documento->fecha_envio)->format('d/m/Y') }}</span>
                        </div>
                    </div>
                    <div class="acf-document-summary">
                        <span>{{ $n($documento->renglones) }} líneas</span>
                        <span>{{ $n($documento->unidades) }} unidades</span>
                        <strong>{{ $dinero($documento->costo_total) }}</strong>
                    </div>
                </div>

                <div class="acf-table-scroll">
                    <table class="acf-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Línea</th>
                                <th class="acf-number">Cantidad</th>
                                <th class="acf-number">Costo unitario</th>
                                <th class="acf-number">Precio venta</th>
                                <th class="acf-number">Costo total</th>
                                <th class="acf-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lineasDocumento as $fila)
                                <tr>
                                    <td><a href="{{ route('accesorios.show', $fila->articulo_id) }}" class="acf-code">{{ $fila->codigo }}</a></td>
                                    <td class="acf-desc">{{ $fila->descripcion }}</td>
                                    <td><span class="acf-line-badge">{{ $fila->linea ?: '—' }}</span></td>
                                    <td class="acf-number acf-qty">{{ $n($fila->cantidad_compra) }}</td>
                                    <td class="acf-number">{{ $dinero($fila->costo_unitario) }}</td>
                                    <td class="acf-number">{{ $dinero($fila->precio_venta) }}</td>
                                    <td class="acf-number acf-cost">{{ $dinero($fila->cantidad_compra * $fila->costo_unitario) }}</td>
                                    <td class="acf-center">
                                        <button type="button" class="acf-edit-button" data-toggle="modal" data-target="#acfEditor"
                                            data-action="{{ route('accesorios.facturas.actualizar', $fila->compra_id) }}"
                                            data-codigo="{{ $fila->codigo }}"
                                            data-descripcion="{{ $fila->descripcion }}"
                                            data-linea="{{ $fila->linea }}"
                                            data-fco="{{ $fila->fco_nro }}"
                                            data-fecha="{{ substr((string) $fila->fecha_envio, 0, 10) }}"
                                            data-cantidad="{{ $fila->cantidad_compra }}"
                                            data-costo="{{ $fila->costo_unitario }}"
                                            data-precio="{{ $fila->precio_venta }}">
                                            <i class="fas fa-pen"></i> Editar
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="acf-foot-label">SUBTOTAL FCO {{ $documento->fco_nro }}</td>
                                <td class="acf-number">{{ $n($documento->unidades) }}</td>
                                <td colspan="2"></td>
                                <td class="acf-number acf-foot-total">{{ $dinero($documento->costo_total) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        @empty
            <section class="acf-empty">
                <i class="fas fa-search"></i>
                <h3>Sin facturas para estos filtros</h3>
                <p>Consultá otro FCO, fecha o artículo, o importá un archivo de compras.</p>
                <a href="{{ route('accesorios.facturas.index') }}" class="acf-btn acf-search">Ver todas las facturas</a>
            </section>
        @endforelse
        <div class="acf-pagination">{{ $documentos->links() }}</div>
    </div>
    <p class="acf-disclaimer"><i class="fas fa-info-circle mr-1"></i>
        Este módulo muestra <strong>compras importadas</strong>, no facturas fiscales completas:
        el archivo no incluye IVA, descuentos ni otros campos de facturación. El importe mostrado es
        <strong>cantidad × costo unitario</strong>. Los cambios en cantidad y costo actualizan las métricas de compra y rentabilidad;
        no generan movimientos nuevos de stock ni modifican comprobantes de venta o remisión.
    </p>
</div>

<div class="modal fade" id="acfEditor" tabindex="-1" role="dialog" aria-labelledby="acfEditorTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content acf-editor-content">
            <div class="modal-header acf-editor-head">
                <div><h5 class="modal-title" id="acfEditorTitulo"><i class="fas fa-edit mr-2"></i>Editar línea de compra</h5><small>Modificá los datos importados de este artículo y FCO</small></div>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="acfEditorForm" method="POST" action="">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="acf-editor-grid">
                        <div class="acf-field"><label for="acfEditCodigo">Código <span>*</span></label><input type="text" id="acfEditCodigo" name="codigo" class="form-control" maxlength="50" required></div>
                        <div class="acf-field acf-field-wide"><label for="acfEditDescripcion">Descripción <span>*</span></label><input type="text" id="acfEditDescripcion" name="descripcion" class="form-control" maxlength="200" required></div>
                        <div class="acf-field"><label for="acfEditLinea">Línea</label><input type="text" id="acfEditLinea" name="linea" class="form-control" maxlength="100"></div>
                        <div class="acf-field"><label for="acfEditFco">N.º FCO <span>*</span></label><input type="text" id="acfEditFco" name="fco_nro" class="form-control" maxlength="50" required></div>
                        <div class="acf-field"><label for="acfEditFecha">Fecha de envío <span>*</span></label><input type="date" id="acfEditFecha" name="fecha_envio" class="form-control" required></div>
                        <div class="acf-field"><label for="acfEditCantidad">Cantidad <span>*</span></label><input type="number" id="acfEditCantidad" name="cantidad_compra" min="1" max="2147483647" step="1" class="form-control" required></div>
                        <div class="acf-field"><label for="acfEditCosto">Costo unitario (Gs) <span>*</span></label><input type="number" id="acfEditCosto" name="costo_unitario" min="0" max="9999999999.99" step="0.01" class="form-control" required></div>
                        <div class="acf-field"><label for="acfEditPrecio">Precio venta (Gs) <span>*</span></label><input type="number" id="acfEditPrecio" name="precio_venta" min="0" max="9999999999.99" step="0.01" class="form-control" required></div>
                    </div>
                    <div class="acf-editor-total">
                        <div><span>COSTO TOTAL CALCULADO</span><small>Se actualiza al modificar cantidad o costo unitario</small></div>
                        <strong id="acfEditTotal">Gs 0</strong>
                    </div>
                    <div class="acf-editor-warning">
                        <i class="fas fa-shield-alt"></i>
                        <p><strong>Protección de datos:</strong> cambiar el código mueve solo esta línea de compra al artículo indicado, sin alterar ventas ni remisiones. La descripción y línea corresponden al catálogo del código y se comparten con sus otras compras FCO. No se permiten duplicados de código + FCO + fecha.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" id="acfSave" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('page_css')
<style>
#accfacturas{font-family:Roboto,Arial,sans-serif;max-width:100%;min-width:0;overflow-x:clip;background:#f4f7fc;color:#203956;padding:16px 18px 28px}
#accfacturas *{box-sizing:border-box}
#accfacturas .acf-hero{background:linear-gradient(115deg,#0d2c64,#1b57ad);padding:24px 28px;border-radius:13px;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:17px}
#accfacturas .acf-eyebrow{font-size:10px;letter-spacing:1.5px;font-weight:900;color:#c6dfff;margin-bottom:7px}
#accfacturas .acf-hero h1{font-weight:900;font-size:28px;margin:0 0 7px}
#accfacturas .acf-hero p{margin:0;font-size:12px;color:#dceaff}
#accfacturas .acf-actions{display:flex;gap:8px;flex-wrap:wrap}
#accfacturas .acf-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;border:1px solid transparent;border-radius:8px;padding:9px 13px;font-size:12px;font-weight:800;text-decoration:none!important;white-space:nowrap;cursor:pointer;min-height:38px}
#accfacturas .acf-ghost{border-color:#779aca;background:#ffffff19;color:white}
#accfacturas .acf-ghost:hover{background:#ffffff36;color:white}
#accfacturas .acf-light{background:#fff;color:#124c99}
#accfacturas .acf-filter-panel{background:#fff;border:1px solid #d9e4f1;border-radius:11px;padding:17px 19px;margin-bottom:15px}
#accfacturas .acf-section-title{font-weight:900;color:#1d4a83;font-size:14px;margin-bottom:12px}
#accfacturas .acf-section-title i{margin-right:5px}
#accfacturas .acf-filter-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:11px;align-items:end}
#accfacturas .acf-filter-grid>div:first-child{grid-column:span 2}
#accfacturas .acf-filter-grid label{font-size:11px;color:#536b8a;font-weight:800;display:block;margin-bottom:5px}
#accfacturas .acf-filter-grid .form-control{height:37px;border-color:#d0deef;color:#21436d;font-size:12px;border-radius:7px}
#accfacturas .acf-filter-actions{display:flex;gap:6px}
#accfacturas .acf-search{background:#1b5bb3;color:#fff}
#accfacturas .acf-reset{background:#edf3fc;color:#34649f;border-color:#d9e5f6}
#accfacturas .acf-filter-summary{color:#476c9d;font-size:11px;margin-top:12px}
#accfacturas .acf-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px;margin-bottom:19px}
#accfacturas .acf-metric{background:#fff;border:1px solid #d9e5f4;border-radius:10px;padding:16px;box-shadow:0 3px 8px #142b4710}
#accfacturas .acf-metric span{display:block;color:#567094;font-size:11px;font-weight:750}
#accfacturas .acf-metric span i{color:#2569bc;margin-right:5px}
#accfacturas .acf-metric strong{display:block;font-weight:900;color:#163e78;font-size:24px;margin-top:8px}
#accfacturas .acf-metric-money{border-top:3px solid #17814e}
#accfacturas .acf-metric-money strong{color:#147349}
#accfacturas .acf-list-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}
#accfacturas .acf-list-heading h2{font-size:17px;font-weight:900;color:#173a6b;margin:0 0 3px}
#accfacturas .acf-list-heading small{color:#6e819a}
#accfacturas .acf-pill{background:#e4effc;color:#1f568f;border:1px solid #c9ddf6;padding:7px 10px;font-size:10px;border-radius:20px}
#accfacturas .acf-document{background:white;border:1px solid #cddff2;border-radius:11px;overflow:hidden;margin-bottom:15px;box-shadow:0 3px 9px #19395c0e}
#accfacturas .acf-document-head{background:linear-gradient(90deg,#e4f0ff,#f8fbff);padding:14px 17px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;border-bottom:2px solid #bcd6f5}
#accfacturas .acf-invoice-info{display:flex;gap:12px;align-items:center}
#accfacturas .acf-invoice-icon{background:#1b5aaa;color:#fff;width:43px;height:43px;display:grid;place-items:center;border-radius:10px;font-size:17px}
#accfacturas .acf-caption{font-weight:900;color:#426b9c;font-size:9px;letter-spacing:1px}
#accfacturas .acf-invoice-info h3{font-size:18px;font-weight:900;margin:3px 0;color:#123e81}
#accfacturas .acf-invoice-date{font-size:11px;color:#567399}
#accfacturas .acf-document-summary{display:flex;align-items:center;flex-wrap:wrap;gap:11px;font-weight:800;color:#52719a;font-size:11px}
#accfacturas .acf-document-summary>span{background:#fff;padding:7px 9px;border:1px solid #d7e5f5;border-radius:6px}
#accfacturas .acf-document-summary strong{background:#d8f4e3;color:#126d3d;border:1px solid #9adeb6;border-radius:7px;padding:8px 11px;font-size:15px}
#accfacturas .acf-table-scroll{width:100%;overflow-x:auto}
#accfacturas .acf-table{border-collapse:collapse;width:100%;font-size:12px;min-width:950px}
#accfacturas .acf-table th{background:#f1f6fc;text-transform:uppercase;font-size:10px;letter-spacing:.2px;font-weight:900;color:#375980;padding:12px 11px;border-bottom:1px solid #d2e0ef;white-space:nowrap}
#accfacturas .acf-table td{border-top:1px solid #e5edf6;padding:12px 11px;vertical-align:middle}
#accfacturas .acf-table tbody tr:hover{background:#f1f8ff}
#accfacturas .acf-code{color:#1555a5;text-decoration:none;font-weight:900}
#accfacturas .acf-desc{font-weight:650;color:#253e5f;min-width:200px}
#accfacturas .acf-line-badge{display:inline-block;background:#e9f1fb;color:#3e6591;padding:5px 8px;border-radius:5px;font-size:10px;font-weight:750}
#accfacturas .acf-number{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}
#accfacturas .acf-qty{font-weight:900;color:#153b6e}
#accfacturas .acf-cost{font-weight:900;color:#147444}
#accfacturas .acf-center{text-align:center}
#accfacturas .acf-edit-button{display:inline-flex;align-items:center;gap:6px;border:1px solid #9fc4f2;border-radius:6px;background:#edf5ff;color:#1557a6;font-size:11px;font-weight:900;padding:7px 11px;cursor:pointer}
#accfacturas .acf-edit-button:hover{color:#fff;background:#1b5bb3}
#accfacturas .acf-table tfoot td{background:#dfedfd;border-top:2px solid #b8d5f7;color:#154479;font-weight:900}
#accfacturas .acf-foot-label{text-align:right;font-size:11px}
#accfacturas .acf-foot-total{font-size:14px;color:#0c7641!important}
#accfacturas .acf-pagination{margin:17px 0}
#accfacturas .acf-pagination .pagination{justify-content:center}
#accfacturas .acf-empty{border:1px dashed #bcd2ed;background:white;border-radius:11px;text-align:center;padding:35px}
#accfacturas .acf-empty i{font-size:27px;color:#5a87bd}
#accfacturas .acf-empty h3{font-weight:900;font-size:17px;color:#184d88;margin-top:10px}
#accfacturas .acf-empty p{color:#6c819e;font-size:12px}
#accfacturas .acf-disclaimer{background:#ecf3fc;border:1px solid #d4e3f5;border-radius:10px;color:#55708f;font-size:11px;line-height:1.6;padding:14px}
#accfacturas .acf-notice{font-size:13px}
#acfEditor .modal-dialog{max-width:760px}
#acfEditor .acf-editor-content{border:0;border-radius:12px;overflow:hidden}
#acfEditor .acf-editor-head{background:linear-gradient(110deg,#0f3775,#1f67b5);color:white;padding:19px 22px}
#acfEditor .acf-editor-head h5{font-size:17px;font-weight:900;margin:0}
#acfEditor .acf-editor-head small{font-size:11px;color:#cfe1fa}
#acfEditor .modal-body{padding:20px 23px;max-height:72vh;overflow-y:auto}
#acfEditor .acf-editor-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
#acfEditor .acf-field-wide{grid-column:span 2}
#acfEditor .acf-field label{display:block;font-size:12px;font-weight:850;color:#315079;margin-bottom:6px}
#acfEditor .acf-field label span{color:#d1434d}
#acfEditor .acf-field input{height:40px;border:1px solid #bfd1e7;border-radius:7px;color:#173c6b}
#acfEditor .acf-editor-total{display:flex;align-items:center;justify-content:space-between;gap:10px;background:#e8f5ed;border:1px solid #abdcc1;padding:15px;border-radius:9px;margin-top:18px;flex-wrap:wrap}
#acfEditor .acf-editor-total span{display:block;color:#2b7150;font-size:11px;font-weight:900;letter-spacing:.3px}
#acfEditor .acf-editor-total small{display:block;color:#628675;font-size:10px}
#acfEditor .acf-editor-total strong{font-size:24px;color:#12693a}
#acfEditor .acf-editor-warning{display:flex;gap:10px;background:#f0f5ff;border:1px solid #d4e3fa;border-radius:8px;padding:12px 14px;margin-top:13px;color:#4a6590;font-size:11px}
#acfEditor .acf-editor-warning i{color:#2b69b5;margin-top:2px}
#acfEditor .acf-editor-warning p{margin:0;line-height:1.5}
@media(max-width:1150px){#accfacturas .acf-filter-grid{grid-template-columns:repeat(3,minmax(0,1fr))}#accfacturas .acf-filter-grid>div:first-child{grid-column:span 1}}
@media(max-width:760px){#accfacturas{padding:12px 9px}#accfacturas .acf-summary{grid-template-columns:repeat(2,minmax(0,1fr))}#accfacturas .acf-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}#acfEditor .acf-editor-grid{grid-template-columns:1fr}#acfEditor .acf-field-wide{grid-column:span 1}#accfacturas .acf-hero{padding:19px}#accfacturas .acf-hero h1{font-size:23px}}
@media(max-width:450px){#accfacturas .acf-filter-grid,#accfacturas .acf-summary{grid-template-columns:1fr}#accfacturas .acf-filter-actions{justify-content:space-between}}
</style>
@endpush

@push('page_scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('acfEditor');
    if (!modal) return;

    var form = document.getElementById('acfEditorForm');
    var guardar = document.getElementById('acfSave');
    var nombres = {
        codigo:'acfEditCodigo', descripcion:'acfEditDescripcion', linea:'acfEditLinea',
        fco_nro:'acfEditFco', fecha_envio:'acfEditFecha', cantidad_compra:'acfEditCantidad',
        costo_unitario:'acfEditCosto', precio_venta:'acfEditPrecio'
    };
    var cantidad = document.getElementById('acfEditCantidad');
    var costo = document.getElementById('acfEditCosto');
    var total = document.getElementById('acfEditTotal');
    var formatear = new Intl.NumberFormat('es-PY', {minimumFractionDigits:0,maximumFractionDigits:2});

    function calcular() {
        var q = Number(cantidad.value);
        var c = Number(costo.value);
        if (!Number.isFinite(q) || !Number.isFinite(c) || q <= 0 || c < 0 || cantidad.value === '' || costo.value === '') {
            total.textContent = '—';
            return;
        }
        total.textContent = 'Gs ' + formatear.format(Math.round(q * c * 100) / 100);
    }

    $(modal).on('show.bs.modal', function (evento) {
        var boton = $(evento.relatedTarget);
        if (!boton.length) return;
        form.action = boton.attr('data-action');
        Object.keys(nombres).forEach(function (clave) {
            var atributo = 'data-' + (clave === 'fco_nro' ? 'fco' : clave === 'fecha_envio' ? 'fecha'
                : clave === 'cantidad_compra' ? 'cantidad' : clave === 'costo_unitario' ? 'costo'
                : clave === 'precio_venta' ? 'precio' : clave);
            document.getElementById(nombres[clave]).value = boton.attr(atributo) || '';
        });
        guardar.disabled = false;
        calcular();
    });
    [cantidad,costo].forEach(function (item) {item.addEventListener('input',calcular);});
    form.addEventListener('submit', function (evento) {
        if (!form.checkValidity()) return;
        if (!window.confirm('¿Confirmás la modificación de esta línea de compra FCO? Se actualizarán los totales y los indicadores relacionados.')) {
            evento.preventDefault();
            return;
        }
        guardar.disabled = true;
    });
});
</script>
@endpush
