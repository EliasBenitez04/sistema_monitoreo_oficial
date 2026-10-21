@extends('layouts.app')

@section('content')
@php
    $origenesSeleccionados = $origenesSeleccionados ?? old('origenes', []);
    $destinosSeleccionados = $destinosSeleccionados ?? old('destinos', []);
    $reservaMinima = $reservaMinima ?? old('reserva_minima', 1);
    $reservaPorcentaje = $reservaPorcentaje ?? old('reserva_porcentaje', 20);
    $numero = function ($valor) {
        return number_format((int) $valor, 0, ',', '.');
    };
@endphp

<style>
    .re-page{max-width:1600px;margin:0 auto;padding:18px 8px 40px;color:#24364c}
    .re-header{padding:24px 26px;background:linear-gradient(115deg,#132b4e,#244b78);border-radius:17px;color:white;display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap}
    .re-header h2{font-size:25px;font-weight:800;margin:0 0 5px}
    .re-header p{color:#d7e5f5;margin:0;font-size:14px}
    .re-head-icon{border-radius:13px;padding:15px;background:rgba(255,255,255,.11);font-size:24px}
    .re-card{background:white;border:1px solid #e2e8f0;border-radius:15px;box-shadow:0 5px 18px rgba(24,45,75,.045);margin-top:20px;overflow:visible}
    .re-card-head{padding:19px 23px;border-bottom:1px solid #edf1f6;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
    .re-card-head h5{font-weight:750;margin:0;color:#1f3550;font-size:17px}
    .re-card-body{padding:24px}
    .re-label{font-size:13px;font-weight:700;color:#344961;margin:0 0 8px;display:block}
    .re-hint{color:#697b91;font-size:12px;margin-top:8px}
    .re-page .form-control{min-height:42px;border-color:#d4dfeb;border-radius:9px}
    .re-page .select2-container{width:100%!important}
    .re-page .select2-selection--multiple{min-height:44px!important;border:1px solid #d4dfeb!important;border-radius:9px!important;padding:4px!important}
    .re-page .select2-container--default .select2-selection--multiple .select2-selection__choice{background:#eaf2ff;color:#204e86;border:1px solid #bed3f0;border-radius:6px}
    .re-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}
    .re-opt{padding:15px 17px;border:1px solid #e1eaf3;background:#f7faff;border-radius:12px}
    .re-options{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .re-btn{border-radius:9px!important;padding:10px 20px!important;font-weight:700!important}
    .re-pill{border-radius:30px;background:#eaf4ff;color:#205384;padding:6px 10px;font-size:12px;font-weight:700}
    .re-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-top:18px}
    .re-kpi{background:white;border:1px solid #e2e8f0;border-radius:13px;padding:19px}
    .re-kpi .value{font-size:27px;font-weight:800;color:#1d385c;line-height:1.3}
    .re-kpi .caption{color:#688097;font-size:12px}
    .re-base{border:1px solid #e4eaf0;border-radius:11px;margin-bottom:15px;overflow:hidden}
    .re-base>summary{display:flex;flex-wrap:wrap;gap:11px;align-items:center;justify-content:space-between;list-style:none;cursor:pointer;background:#f6f9fc;padding:14px 18px;color:#284463}
    .re-base>summary::-webkit-details-marker{display:none}
    .re-base>summary:before{content:'▸';font-weight:bold;margin-right:2px}
    .re-base[open]>summary:before{content:'▾'}
    .re-table{font-size:12.5px;margin-bottom:0}
    .re-table thead th{background:#f5f8fc;color:#536985;white-space:nowrap;border-top:0}
    .re-table td{vertical-align:middle!important}
    .re-quantity{font-weight:800;color:#166b49;font-size:14px}
    .re-small{font-size:11px;color:#718197}
    .re-stock{display:flex;flex-wrap:wrap;gap:5px;padding:8px 0}
    .re-stock span{border-radius:7px;background:#f2f6fb;padding:4px 7px;white-space:nowrap}
    .re-stock .re-stock-destino{background:#edf9f4;color:#22664c}
    .re-export{border:1px solid #c5d4e5;background:white;color:#23517f}
    .re-muted{color:#6d8197}
    @media(max-width:900px){.re-grid,.re-options{grid-template-columns:1fr}.re-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:540px){.re-kpis{grid-template-columns:1fr}.re-card-body{padding:15px}.re-header{padding:19px}.re-header h2{font-size:21px}}
</style>

<div class="re-page">
    <header class="re-header">
        <div class="d-flex align-items-center" style="gap:17px">
            <span class="re-head-icon"><i class="fas fa-file-excel"></i></span>
            <div>
                <h2>Redistribución por Excel</h2>
                <p>Consulta de stock real · Códigos por color · Equilibrio automático entre sucursales</p>
            </div>
        </div>
    </header>

    @if(!empty($errorTabla))
        <div class="alert alert-danger mt-3">{{ $errorTabla }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mt-3">
            <strong>No se pudo consultar el archivo.</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="re-card">
        <div class="re-card-head">
            <h5><i class="fas fa-sliders-h text-primary mr-2"></i>Configurar la redistribución</h5>
            <span class="re-pill">Simulación sin alterar el stock</span>
        </div>
        <div class="re-card-body">
            <form method="POST" action="{{ route('redistribucion-excel.consultar') }}" enctype="multipart/form-data">
                @csrf
                <div class="re-grid">
                    <div>
                        <label class="re-label" for="re-origenes">Sucursales de origen (opcional)</label>
                        <select class="form-control select2" id="re-origenes" name="origenes[]" multiple data-placeholder="Automático: buscar en todas las sucursales">
                            @foreach($sucursales as $sucursal)
                                <option value="{{ $sucursal }}" {{ in_array($sucursal, $origenesSeleccionados) ? 'selected' : '' }}>
                                    {{ $sucursal }}
                                </option>
                            @endforeach
                        </select>
                        <p class="re-hint">Si deja vacío, se buscará en todas las sucursales que no sean destinos. Siempre se prioriza el origen con más stock por talle.</p>
                    </div>
                    <div>
                        <label class="re-label" for="re-destinos">Sucursales de destino <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="re-destinos" name="destinos[]" multiple required data-placeholder="Seleccione una o varias sucursales">
                            @foreach($sucursales as $sucursal)
                                <option value="{{ $sucursal }}" {{ in_array($sucursal, $destinosSeleccionados) ? 'selected' : '' }}>
                                    {{ $sucursal }}
                                </option>
                            @endforeach
                        </select>
                        <p class="re-hint">Ejemplo: SAN LORENZO y SHOPPING. Se priorizan los destinos con menos unidades de cada talle.</p>
                    </div>
                </div>

                <div class="re-grid mt-3">
                    <div>
                        <label class="re-label" for="re-archivo">Importar archivo Excel <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="re-archivo" name="archivo" accept=".xlsx,.xls,.csv" required>
                        <p class="re-hint">Excel con encabezado <strong>CODIGO</strong> y códigos completos hasta el color, sin talle. Por ejemplo <code>080617588CE</code>. Mantenga la columna como texto para conservar los ceros iniciales.</p>
                    </div>
                    <div class="re-options">
                        <div class="re-opt">
                            <label class="re-label" for="re-reserva-minima">Reserva mínima por talle en el origen</label>
                            <input class="form-control" id="re-reserva-minima" type="number" name="reserva_minima" min="1" max="1000" value="{{ $reservaMinima }}" required>
                            <p class="re-hint mb-0">Nunca dejar la sucursal de origen en cero.</p>
                        </div>
                        <div class="re-opt">
                            <label class="re-label" for="re-reserva-porcentaje">Reserva del stock original (%)</label>
                            <input class="form-control" id="re-reserva-porcentaje" type="number" name="reserva_porcentaje" min="0" max="90" value="{{ $reservaPorcentaje }}" required>
                            <p class="re-hint mb-0">Se conserva el mayor entre este porcentaje y el mínimo.</p>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center justify-content-between mt-3" style="gap:12px">
                    <div class="re-muted small"><i class="fas fa-shield-alt mr-1"></i> Solo genera una propuesta: no descuenta unidades ni modifica la redistribución existente.</div>
                    <button class="btn btn-primary re-btn" type="submit" {{ !empty($errorTabla) ? 'disabled' : '' }}>
                        <i class="fas fa-search mr-2"></i>Generar propuesta de redistribución
                    </button>
                </div>
            </form>
        </div>
    </div>

    @isset($resumen)
        <div class="re-kpis">
            <div class="re-kpi"><div class="caption">Códigos por color analizados</div><div class="value">{{ $numero($resumen['bases']) }}</div></div>
            <div class="re-kpi"><div class="caption">Talles encontrados en stock</div><div class="value">{{ $numero($resumen['variantes']) }}</div></div>
            <div class="re-kpi"><div class="caption">Unidades propuestas para redistribuir</div><div class="value text-success">{{ $numero($resumen['propuesto']) }}</div></div>
            <div class="re-kpi"><div class="caption">Sucursales destino</div><div class="value">{{ $numero($resumen['destinos']) }}</div></div>
        </div>

        <div class="re-card">
            <div class="re-card-head">
                <h5><i class="fas fa-route text-primary mr-2"></i>Propuesta de distribución</h5>
                @if(count($movimientos) > 0)
                    <button type="button" id="re-exportar" class="btn re-export re-btn btn-sm">
                        <i class="fas fa-download mr-1"></i> Descargar propuesta CSV
                    </button>
                @endif
            </div>
            <div class="re-card-body">
                <p class="re-muted small">
                    @if($automatica)
                        Orígenes automáticos ({{ count($origenesUtilizados) }} sucursales posibles).
                    @else
                        Orígenes seleccionados: {{ implode(', ', $origenesUtilizados) }}.
                    @endif
                    Destinos: <strong>{{ implode(', ', $destinosSeleccionados) }}</strong>.
                    Reserva: mínimo {{ $reservaMinima }} unidad(es) y {{ $reservaPorcentaje }}% del stock original por talle en origen.
                </p>

                @if($resumen['propuesto'] > 0)
                    <div class="d-flex flex-wrap mb-3" style="gap:9px">
                        @foreach($resumen['por_destino'] as $sucursal => $total)
                            <span class="re-pill">{{ $sucursal }}: +{{ $numero($total) }}</span>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-info">
                        No hay movimientos recomendados: los destinos pueden estar equilibrados,
                        o los orígenes no tienen suficiente stock sin afectar su reserva.
                    </div>
                @endif

                @foreach($resultado as $base => $grupo)
                    <details class="re-base" {{ $loop->first ? 'open' : '' }}>
                        <summary>
                            <span><strong>{{ $base }}</strong> <span class="re-small ml-2">{{ count($grupo['variantes']) }} talle(s) encontrados</span></span>
                            <span><strong class="text-success">{{ $numero($grupo['total']) }} unid. para enviar</strong></span>
                        </summary>
                        <div class="p-3">
                            @if(!count($grupo['variantes']))
                                <div class="alert alert-warning mb-0">El código no tiene talles registrados en stock_sucursales.</div>
                            @else
                                <h6 class="font-weight-bold mb-2">Disponibilidad recuperada por talle</h6>
                                <div class="table-responsive mb-4">
                                    <table class="table table-sm table-striped re-table">
                                        <thead><tr><th>Código completo</th><th>Talle</th><th>Descripción</th><th class="text-right">Total orígenes</th><th class="text-right">Total destinos</th><th>Stock por sucursal</th></tr></thead>
                                        <tbody>
                                        @foreach($grupo['variantes'] as $variante)
                                            <tr>
                                                <td class="font-weight-bold">{{ $variante['codigo'] }}</td>
                                                <td>{{ $variante['talle'] }}</td>
                                                <td>{{ $variante['descripcion'] ?: '—' }}</td>
                                                <td class="text-right">{{ $numero($variante['total_origen']) }}</td>
                                                <td class="text-right">{{ $numero($variante['total_destino']) }}</td>
                                                <td>
                                                    <details>
                                                        <summary style="cursor:pointer;color:#2a669f">Ver disponibilidad</summary>
                                                        <div class="re-stock">
                                                            @foreach($origenesUtilizados as $suc)
                                                                <span>{{ $suc }}: <b>{{ $numero($variante['stock'][$suc] ?? 0) }}</b></span>
                                                            @endforeach
                                                            @foreach($destinosSeleccionados as $suc)
                                                                <span class="re-stock-destino">{{ $suc }}: <b>{{ $numero($variante['stock'][$suc] ?? 0) }}</b></span>
                                                            @endforeach
                                                        </div>
                                                    </details>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            <h6 class="font-weight-bold mb-2">Envíos sugeridos</h6>
                            @if(count($grupo['movimientos']) > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover re-table">
                                        <thead>
                                        <tr>
                                            <th>Código completo</th><th>Talle</th><th>Origen</th><th>Destino</th>
                                            <th class="text-right">Stock origen</th><th class="text-right">Stock destino</th>
                                            <th class="text-right">Reserva</th><th class="text-right">Enviar</th>
                                            <th class="text-right">Origen después</th><th class="text-right">Destino después</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($grupo['movimientos'] as $mov)
                                            <tr class="re-export-row">
                                                <td data-value="{{ $mov['base'] }}" style="display:none">{{ $mov['base'] }}</td>
                                                <td>{{ $mov['codigo'] }}</td>
                                                <td>{{ $mov['talle'] }}</td>
                                                <td>{{ $mov['origen'] }}</td>
                                                <td>{{ $mov['destino'] }}</td>
                                                <td class="text-right" data-value="{{ $mov['stock_origen'] }}">{{ $numero($mov['stock_origen']) }}</td>
                                                <td class="text-right" data-value="{{ $mov['stock_destino'] }}">{{ $numero($mov['stock_destino']) }}</td>
                                                <td class="text-right" data-value="{{ $mov['reserva'] }}">{{ $numero($mov['reserva']) }}</td>
                                                <td class="text-right re-quantity" data-value="{{ $mov['cantidad'] }}">{{ $numero($mov['cantidad']) }}</td>
                                                <td class="text-right" data-value="{{ $mov['origen_final'] }}">{{ $numero($mov['origen_final']) }}</td>
                                                <td class="text-right" data-value="{{ $mov['destino_final'] }}">{{ $numero($mov['destino_final']) }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="re-muted small mb-0">No se necesita o no es posible redistribuir este código manteniendo la reserva.</p>
                            @endif
                        </div>
                    </details>
                @endforeach
                <p class="small re-muted mb-0"><i class="fas fa-info-circle mr-1"></i>Los saldos posteriores son simulados, considerando toda la propuesta para cada talle. No representan una remisión ni una actualización del stock.</p>
            </div>
        </div>

        @if(count($resumen['sin_coincidencias']) || count($invalidos))
            <div class="alert alert-warning mt-3">
                @if(count($resumen['sin_coincidencias']))
                    <p class="mb-1"><strong>Sin coincidencias en stock_sucursales:</strong> {{ implode(', ', $resumen['sin_coincidencias']) }}</p>
                @endif
                @if(count($invalidos))
                    <p class="mb-0"><strong>Códigos inválidos del archivo (máximo 30):</strong>
                        @foreach($invalidos as $item)
                            Fila {{ $item['fila'] }}: {{ $item['codigo'] }}{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    </p>
                @endif
            </div>
        @endif
    @endisset
</div>
@endsection

@push('page_scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var boton = document.getElementById('re-exportar');
    if (!boton) return;
    boton.addEventListener('click', function () {
        var cabecera = ['Codigo base','Codigo completo','Talle','Sucursal origen','Sucursal destino','Stock origen inicial','Stock destino inicial','Reserva origen','Cantidad a enviar','Origen luego','Destino luego'];
        var filas = [cabecera];
        document.querySelectorAll('.re-export-row').forEach(function (fila) {
            filas.push(Array.from(fila.querySelectorAll('td')).map(function (celda) {
                return celda.getAttribute('data-value') || celda.textContent.trim();
            }));
        });
        var contenido = '\uFEFF' + filas.map(function (fila) {
            return fila.map(function (dato) { return '"' + String(dato).replace(/"/g, '""') + '"'; }).join(';');
        }).join('\r\n');
        var url = URL.createObjectURL(new Blob([contenido], { type: 'text/csv;charset=utf-8;' }));
        var enlace = document.createElement('a');
        enlace.href = url;
        enlace.download = 'redistribucion_excel_propuesta.csv';
        document.body.appendChild(enlace);
        enlace.click();
        enlace.remove();
        setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
    });
});
</script>
@endpush
