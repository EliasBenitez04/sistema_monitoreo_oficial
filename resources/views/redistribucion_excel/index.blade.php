@extends('layouts.app')

@section('content')
@php
    $origenesSeleccionados = $origenesSeleccionados ?? old('origenes', []);
    $destinosSeleccionados = $destinosSeleccionados ?? old('destinos', []);
    $numero = function ($valor) { return number_format((int) $valor, 0, ',', '.'); };
@endphp

<style>
.re-page{max-width:1600px;margin:0 auto;padding:18px 8px 40px;color:#24364c}.re-header{padding:24px 26px;background:linear-gradient(115deg,#132b4e,#244b78);border-radius:17px;color:white;display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap}.re-header h2{font-size:25px;font-weight:800;margin:0 0 5px}.re-header p{color:#d7e5f5;margin:0;font-size:14px}.re-head-icon{border-radius:13px;padding:15px;background:rgba(255,255,255,.11);font-size:24px}.re-card{background:white;border:1px solid #e2e8f0;border-radius:15px;box-shadow:0 5px 18px rgba(24,45,75,.045);margin-top:20px;overflow:visible}.re-card-head{padding:19px 23px;border-bottom:1px solid #edf1f6;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.re-card-head h5{font-weight:750;margin:0;color:#1f3550;font-size:17px}.re-card-body{padding:24px}.re-label{font-size:13px;font-weight:700;color:#344961;margin:0 0 8px;display:block}.re-hint{color:#697b91;font-size:12px;margin-top:8px}.re-page .form-control{min-height:42px;border-color:#d4dfeb;border-radius:9px}.re-page .select2-container{width:100%!important}.re-page .select2-selection--multiple{min-height:44px!important;border:1px solid #d4dfeb!important;border-radius:9px!important;padding:4px!important}.re-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.re-btn{border-radius:9px!important;padding:10px 20px!important;font-weight:700!important}.re-pill{border-radius:30px;background:#eaf4ff;color:#205384;padding:6px 10px;font-size:12px;font-weight:700}.re-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-top:18px}.re-kpi{background:white;border:1px solid #e2e8f0;border-radius:13px;padding:19px}.re-kpi .value{font-size:27px;font-weight:800;color:#1d385c}.re-kpi .caption{color:#688097;font-size:12px}.re-base{border:1px solid #e4eaf0;border-radius:11px;margin-bottom:15px;overflow:hidden}.re-base>summary{display:flex;gap:11px;align-items:center;justify-content:space-between;cursor:pointer;background:#f6f9fc;padding:14px 18px;color:#284463}.re-table{font-size:12.5px;margin-bottom:0}.re-table thead th{background:#f5f8fc;color:#536985;white-space:nowrap;border-top:0}.re-table td{vertical-align:middle!important}.re-quantity{font-weight:800;color:#166b49;font-size:14px}.re-stock{display:flex;flex-wrap:wrap;gap:5px;padding:8px 0}.re-stock span{border-radius:7px;background:#f2f6fb;padding:4px 7px}.re-stock .re-stock-destino{background:#edf9f4;color:#22664c}.re-muted{color:#6d8197}.re-rule{background:#f7faff;border:1px solid #dce8f5;border-radius:12px;padding:14px 17px;height:100%}.re-priority{background:#fff6df;border-color:#f1d38b;color:#75560c}@media(max-width:900px){.re-grid{grid-template-columns:1fr}.re-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:540px){.re-kpis{grid-template-columns:1fr}}
</style>

<div class="re-page">
<header class="re-header"><div class="d-flex align-items-center" style="gap:17px"><span class="re-head-icon"><i class="fas fa-file-excel"></i></span><div><h2>Redistribución por Excel</h2><p>Busca el stock por código/talle, deja 1 unidad en cada origen y reparte el excedente entre los destinos seleccionados.</p></div></div></header>

@if(!empty($errorTabla))<div class="alert alert-danger mt-3">{{ $errorTabla }}</div>@endif
@if(session('error'))<div class="alert alert-danger mt-3">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger mt-3"><strong>No se pudo consultar el archivo.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="re-card"><div class="re-card-head"><h5><i class="fas fa-sliders-h text-primary mr-2"></i>Configurar redistribución</h5><span class="re-pill">No modifica stock</span></div><div class="re-card-body">
<form method="POST" action="{{ route('redistribucion-excel.consultar') }}" enctype="multipart/form-data">@csrf
<div class="re-grid">
<div><label class="re-label">Sucursales de origen (opcional)</label><select class="form-control select2" name="origenes[]" multiple data-placeholder="Automático: todas las sucursales"><option></option>@foreach($sucursales as $sucursal)<option value="{{ $sucursal }}" {{ in_array($sucursal,$origenesSeleccionados) ? 'selected' : '' }}>{{ $sucursal }}</option>@endforeach</select><p class="re-hint">Si no selecciona origen, analiza todas las sucursales excepto las elegidas como destino. Primero toma los orígenes con mayor stock.</p></div>
<div><label class="re-label">Sucursales de destino *</label><select class="form-control select2" name="destinos[]" multiple required data-placeholder="Seleccione destinos">@foreach($sucursales as $sucursal)<option value="{{ $sucursal }}" {{ in_array($sucursal,$destinosSeleccionados) ? 'selected' : '' }}>{{ $sucursal }}</option>@endforeach</select><p class="re-hint"><strong>SHOPP tiene prioridad.</strong> Cuando el reparto no es exacto, las unidades sobrantes van primero a SHOPP y luego al orden seleccionado.</p></div>
</div>
<div class="re-grid mt-3"><div><label class="re-label">Archivo Excel *</label><input type="file" class="form-control" name="archivo" accept=".xlsx,.xls,.csv" required><p class="re-hint">Debe contener la columna <strong>CODIGO</strong> con el código hasta color, por ejemplo <code>080617588CE</code>. El sistema recupera automáticamente todos sus talles.</p></div><div class="re-grid"><div class="re-rule"><strong><i class="fas fa-shield-alt mr-1"></i>Reserva fija</strong><div class="mt-2">Cada origen conserva <strong>mínimo 1 unidad por código/talle</strong>. Todo lo demás queda disponible para repartir.</div></div><div class="re-rule re-priority"><strong><i class="fas fa-star mr-1"></i>Prioridad SHOPP</strong><div class="mt-2">Ejemplo: si hay 7, deja 1 y con 3 destinos reparte 2 + 2 + 2. Si hay 8, SHOPP recibe primero el sobrante: 3 + 2 + 2.</div></div></div></div>
<div class="d-flex justify-content-end mt-3"><button class="btn btn-primary re-btn" type="submit" {{ !empty($errorTabla) ? 'disabled' : '' }}><i class="fas fa-search mr-2"></i>Analizar y generar propuesta</button></div>
</form></div></div>

@isset($resumen)
<div class="re-kpis"><div class="re-kpi"><div class="caption">Códigos analizados</div><div class="value">{{ $numero($resumen['bases']) }}</div></div><div class="re-kpi"><div class="caption">Talles encontrados</div><div class="value">{{ $numero($resumen['variantes']) }}</div></div><div class="re-kpi"><div class="caption">Unidades a redistribuir</div><div class="value text-success">{{ $numero($resumen['propuesto']) }}</div></div><div class="re-kpi"><div class="caption">Destinos</div><div class="value">{{ $numero($resumen['destinos']) }}</div></div></div>

<div class="re-card"><div class="re-card-head"><h5><i class="fas fa-route text-primary mr-2"></i>Propuesta de redistribución</h5>@if(count($movimientos)>0)<button type="button" id="re-exportar" class="btn btn-success re-btn btn-sm"><i class="fas fa-file-excel mr-1"></i> Exportar a Excel</button>@endif</div><div class="re-card-body">
<p class="re-muted small">Cada origen queda con <strong>1 unidad mínima por talle</strong>. Los sobrantes del reparto se asignan primero a <strong>SHOPP</strong>.</p>
@if($resumen['propuesto']>0)<div class="d-flex flex-wrap mb-3" style="gap:9px">@foreach($resumen['por_destino'] as $sucursal=>$total)<span class="re-pill">{{ $sucursal }}: +{{ $numero($total) }}</span>@endforeach</div>@else<div class="alert alert-info">No existen unidades disponibles para redistribuir manteniendo 1 unidad en cada origen.</div>@endif

@foreach($resultado as $base=>$grupo)
<details class="re-base" {{ $loop->first?'open':'' }}><summary><span><strong>{{ $base }}</strong> · {{ count($grupo['variantes']) }} talle(s)</span><strong class="text-success">{{ $numero($grupo['total']) }} para enviar</strong></summary><div class="p-3">
@if(!count($grupo['variantes']))<div class="alert alert-warning mb-0">El código no tiene talles registrados en stock_sucursales.</div>@else
<h6 class="font-weight-bold">Disponibilidad por talle</h6><div class="table-responsive mb-4"><table class="table table-sm table-striped re-table"><thead><tr><th>Código</th><th>Talle</th><th>Descripción</th><th>Stock por sucursal</th></tr></thead><tbody>@foreach($grupo['variantes'] as $variante)<tr><td><strong>{{ $variante['codigo'] }}</strong></td><td>{{ $variante['talle'] }}</td><td>{{ $variante['descripcion']?:'—' }}</td><td><details><summary style="cursor:pointer;color:#2a669f">Ver disponibilidad</summary><div class="re-stock">@foreach($origenesUtilizados as $suc)<span>{{ $suc }}: <b>{{ $numero($variante['stock'][$suc]??0) }}</b></span>@endforeach @foreach($destinosSeleccionados as $suc)<span class="re-stock-destino">{{ $suc }}: <b>{{ $numero($variante['stock'][$suc]??0) }}</b></span>@endforeach</div></details></td></tr>@endforeach</tbody></table></div>
@endif
<h6 class="font-weight-bold">Envíos sugeridos</h6>
@if(count($grupo['movimientos']))<div class="table-responsive"><table class="table table-sm table-hover re-table"><thead><tr><th>Código base</th><th>Código completo</th><th>Talle</th><th>Descripción</th><th>Origen</th><th>Destino</th><th class="text-right">Stock origen</th><th class="text-right">Stock destino</th><th class="text-right">Reserva</th><th class="text-right">Enviar</th><th class="text-right">Origen después</th><th class="text-right">Destino después</th></tr></thead><tbody>
@foreach($grupo['movimientos'] as $mov)<tr class="re-export-row"><td>{{ $mov['base'] }}</td><td>{{ $mov['codigo'] }}</td><td>{{ $mov['talle'] }}</td><td>{{ $mov['descripcion'] }}</td><td>{{ $mov['origen'] }}</td><td>{{ $mov['destino'] }}</td><td class="text-right">{{ $mov['stock_origen'] }}</td><td class="text-right">{{ $mov['stock_destino'] }}</td><td class="text-right">1</td><td class="text-right re-quantity">{{ $mov['cantidad'] }}</td><td class="text-right">{{ $mov['origen_final'] }}</td><td class="text-right">{{ $mov['destino_final'] }}</td></tr>@endforeach
</tbody></table></div>@else<p class="re-muted small">Sin unidades disponibles para enviar.</p>@endif
</div></details>
@endforeach
</div></div>

@if(count($resumen['sin_coincidencias'])||count($invalidos))<div class="alert alert-warning mt-3">@if(count($resumen['sin_coincidencias']))<p class="mb-1"><strong>Sin coincidencias:</strong> {{ implode(', ',$resumen['sin_coincidencias']) }}</p>@endif @if(count($invalidos))<p class="mb-0"><strong>Códigos inválidos:</strong> @foreach($invalidos as $item) Fila {{ $item['fila'] }}: {{ $item['codigo'] }}{{ !$loop->last?', ':'' }} @endforeach</p>@endif</div>@endif
@endisset
</div>
@endsection

@push('page_scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){
 var boton=document.getElementById('re-exportar'); if(!boton)return;
 boton.addEventListener('click',function(){
  var headers=['CODIGO BASE','CODIGO COMPLETO','TALLE','DESCRIPCION','SUCURSAL ORIGEN','SUCURSAL DESTINO','STOCK ORIGEN INICIAL','STOCK DESTINO INICIAL','RESERVA ORIGEN','CANTIDAD A ENVIAR','ORIGEN DESPUES','DESTINO DESPUES'];
  var rows=Array.from(document.querySelectorAll('.re-export-row')).map(function(tr){return Array.from(tr.children).map(function(td){return td.textContent.trim();});});
  var html='<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="UTF-8"><style>table{border-collapse:collapse}th,td{border:1px solid #999;padding:5px}th{font-weight:bold;background:#eee}</style></head><body><table><thead><tr>'+headers.map(function(h){return '<th>'+h+'</th>';}).join('')+'</tr></thead><tbody>'+rows.map(function(r){return '<tr>'+r.map(function(v){return '<td>'+String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')+'</td>';}).join('')+'</tr>';}).join('')+'</tbody></table></body></html>';
  var blob=new Blob(['\ufeff',html],{type:'application/vnd.ms-excel;charset=utf-8;'}); var url=URL.createObjectURL(blob); var a=document.createElement('a'); a.href=url; a.download='redistribucion_propuesta_'+new Date().toISOString().slice(0,10)+'.xls'; document.body.appendChild(a); a.click(); a.remove(); setTimeout(function(){URL.revokeObjectURL(url);},1000);
 });
});
</script>
@endpush
