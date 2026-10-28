@extends('layouts.app')
@section('content')
<section class="content"><div class="container-fluid py-3 maym-fact">
  <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <div><h2 class="mb-1"><i class="fas fa-file-invoice text-primary mr-2"></i>Detalle de factura mayorista</h2>
      <div class="text-muted">{{ $factura->comprobante }} · {{ date('d/m/Y',strtotime($factura->fecha)) }} · {{ $factura->cliente }} · COMERCIAL MATRIZ</div>
    </div>
    <a href="{{ route('ventas.mayoristas.index',['desde'=>request('desde',$mes.'-01'),'hasta'=>request('hasta',$factura->fecha)]) }}" class="btn btn-outline-secondary mt-2"><i class="fas fa-arrow-left mr-1"></i>Volver a ventas</a>
  </div>
  <div class="alert alert-info small">
    <strong>Consulta documental:</strong> revisá la factura y las remisiones con códigos completos coincidentes.
    Cada código y talle se presenta por separado. Una coincidencia de código no acredita
    que las prendas hayan llegado a COMERCIAL MATRIZ ni establece su procedencia.
  </div>
  <div class="row">
    <div class="col-lg-3 col-sm-6 mb-3"><div class="maym-kpi"><small>Unidades facturadas brutas</small><strong>{{ number_format($factura->brutas,0,',','.') }}</strong><span>Unidades positivas de la factura</span></div></div>
    <div class="col-lg-3 col-sm-6 mb-3"><div class="maym-kpi"><small>Códigos / talles</small><strong>{{ number_format($totales->codigos,0,',','.') }}</strong><span>Códigos completos distintos</span></div></div>
    <div class="col-lg-3 col-sm-6 mb-3"><div class="maym-kpi"><small>Líneas de venta</small><strong>{{ number_format($lineas->count(),0,',','.') }}</strong><span>Renglones importados de la factura</span></div></div>
    {{-- <div class="col-lg-3 col-sm-6 mb-3"><div class="maym-kpi"><small>Documentos con códigos coincidentes</small><strong>{{ number_format($totales->documentos,0,',','.') }}</strong><span>Coincidencia documental, no procedencia acreditada</span></div></div> --}}
  </div>
  <div class="card mb-3 maym-card">
    <div class="card-header bg-white"><strong>1. Códigos y cantidades facturadas</strong><small class="d-block text-muted">Cada talle tiene su propia cantidad; no se compensa un código con prendas de otro talle.</small></div>
    <div class="table-responsive">
      <table class="table table-sm maym-table mb-0"><thead><tr><th>Código completo</th><th>Descripción</th><th class="text-right">Cantidad facturada</th></tr></thead>
        <tbody>@forelse($porCodigo as $item)<tr>
          <td class="font-weight-bold maym-fact-code">{{ $item->codigo }}</td>
          <td class="maym-fact-description">{{ $item->descripcion ?: 'Sin descripción en venta importada' }}</td>
          <td class="text-right font-weight-bold">{{ number_format($item->necesita,0,',','.') }}</td>
        </tr>@empty<tr><td colspan="3" class="text-muted text-center">Esta factura no tiene cantidades positivas; revisá las notas de crédito en el detalle de ventas.</td></tr>@endforelse</tbody>
      </table>
    </div>
  </div>
  <div class="card mb-3 maym-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
      <div><strong>2. Remisiones para consulta por código completo</strong>
        <small class="d-block text-muted">Se muestran documentos de Casa Central, locales y Modelo Muestra con códigos coincidentes. Cada remisión mantiene su origen y destino importados.</small></div>
      <div class="d-flex flex-wrap align-items-center mt-2" style="gap:5px">
        <div class="btn-group btn-group-sm">
          <a class="btn btn-{{ $filtroOrigen==='todos'?'primary':'outline-primary' }}" href="{{ route('ventas.mayoristas.factura',['clave'=>$factura->clave,'mes'=>$mes,'historial'=>$diasHistorial,'origen'=>'todos']) }}">Todas las sucursales</a>
          <a class="btn btn-{{ $filtroOrigen==='locales'?'primary':'outline-primary' }}" href="{{ route('ventas.mayoristas.factura',['clave'=>$factura->clave,'mes'=>$mes,'historial'=>$diasHistorial,'origen'=>'locales']) }}">Solo locales → Matriz</a>
          <a class="btn btn-{{ $filtroOrigen==='central'?'primary':'outline-primary' }}" href="{{ route('ventas.mayoristas.factura',['clave'=>$factura->clave,'mes'=>$mes,'historial'=>$diasHistorial,'origen'=>'central']) }}">Solo Casa Central</a>
        </div>
        <div class="btn-group btn-group-sm">
          <a class="btn btn-{{ $diasHistorial==='90'?'secondary':'outline-secondary' }}" href="{{ route('ventas.mayoristas.factura',['clave'=>$factura->clave,'mes'=>$mes,'historial'=>'90','origen'=>$filtroOrigen]) }}">90 días</a>
          <a class="btn btn-{{ $diasHistorial==='365'?'secondary':'outline-secondary' }}" href="{{ route('ventas.mayoristas.factura',['clave'=>$factura->clave,'mes'=>$mes,'historial'=>'365','origen'=>$filtroOrigen]) }}">1 año</a>
          <a class="btn btn-{{ $diasHistorial==='todo'?'secondary':'outline-secondary' }}" href="{{ route('ventas.mayoristas.factura',['clave'=>$factura->clave,'mes'=>$mes,'historial'=>'todo','origen'=>$filtroOrigen]) }}">Todo</a>
        </div>
      </div>
    </div>
    @if($totales->candidatas_limitadas)
      <div class="alert alert-warning m-2">Existen más de 3.000 líneas candidatas para estos códigos. La vista está limitada y NO permite concluir que se hayan encontrado todas las remisiones; acotá por período o revisá en PostgreSQL.</div>
    @endif
    <div class="card-body">
      <p class="small text-muted mb-3">Documentos históricos con códigos completos presentes en la factura. Una coincidencia no confirma recepción física ni permite atribuir las unidades vendidas a una remisión determinada.</p>
      @forelse($documentos as $doc)
        <details class="maym-documento mb-3" {{ $loop->first ? 'open' : '' }}>
          <summary class="p-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:7px">
              <div>
                <strong>Remisión {{ $doc->serie }}-{{ $doc->numero }}</strong>
                <span class="small text-muted ml-2">{{ date('d/m/Y',strtotime($doc->fecha)) }}</span>
                <small class="d-block text-muted">
                  <strong>Origen: {{ $doc->origen }}</strong>
                  <span class="mx-1">→</span> Destino: {{ $doc->destino ?: 'No informado' }}
                  <span class="badge badge-{{ $doc->tipo_origen==='LOCAL'?'info':($doc->tipo_origen==='CENTRAL'?'secondary':'warning') }} ml-2">{{ $doc->canal }}</span>
                </small>
                @if($doc->origen_discordante)
                  <small class="d-block text-warning"><i class="fas fa-exclamation-triangle"></i> Código y nombre de origen discordantes: revisar «{{ $doc->origen_importado }}»</small>
                @endif
              </div>
              <div class="small">
                <span class="badge badge-light border">{{ $doc->coincidencias }} códigos coincidentes</span>
                @foreach($doc->grupos_de_ot as $grupoOt)
                  <span class="badge badge-info">ID interno OT {{ $grupoOt->id_ot }} · {{ $grupoOt->codigos }} códigos en este documento</span>
                @endforeach
                <span class="badge badge-light">Cantidad remitida {{ number_format($doc->remitidas,0,',','.') }}</span>
              </div>
            </div>
          </summary>
          <div class="table-responsive">
            <table class="table table-sm table-hover maym-table mb-0">
              <thead><tr><th>Código/talle</th><th>Artículo</th><th>Recepción</th><th class="text-right">Cantidad remitida</th></tr></thead>
              <tbody>
                @foreach($doc->lineas as $r)
                  <tr>
                    <td class="font-weight-bold">{{ $r->codigo }}</td>
                    <td>
                      {{ $r->descripcion }}
                      <small class="d-block text-muted">
                        Línea #{{ $r->id }} · origen {{ $r->origen_real }}
                        @if($r->cod_sucursal_salida) (cód. {{ $r->cod_sucursal_salida }}) @endif
                      </small>
                    </td>
                    <td>
                      @if($r->fecha_recepcion)<span class="text-success">{{ date('d/m/Y',strtotime($r->fecha_recepcion)) }}</span>
                      @else<span class="text-warning">Sin recepción</span>@endif
                      @if($r->modelo)<small class="d-block text-primary">Modelo Muestra</small>@endif
                      @if($r->posterior)
                        <small class="d-block text-warning">Emitida después de la factura · solo revisión</small>
                      @elseif($r->recepcion_posterior && !$r->modelo)
                        <small class="d-block text-warning">Recibida después de la factura · solo revisión</small>
                      @endif
                    </td>
                    <td class="text-right font-weight-bold">{{ number_format($r->cantidad,0,',','.') }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="px-3 py-2 small text-muted">Estas cantidades corresponden a documentos de remisión y no deben sumarse directamente como stock físico en Matriz.</div>
        </details>
      @empty
        <p class="text-muted mb-0">No se encontraron remisiones de Modelo Muestra / Matriz que coincidan en código completo dentro del historial seleccionado. Esto no demuestra que no existan documentos anteriores o todavía sin importar.</p>
      @endforelse
    </div>
  </div>
  <details class="card mb-3 maym-card">
    <summary class="card-header bg-white maym-summary"><strong>3. Ventas detalladas de esta factura · artículo, código, unidades y valores</strong></summary>
    <div class="table-responsive"><table class="table table-sm maym-table mb-0">
      <thead><tr><th>Código completo</th><th>Descripción</th><th class="text-right">Cantidad</th><th class="text-right">Lista Gs</th><th class="text-right">Descuento Gs</th><th class="text-right">Venta Gs</th></tr></thead>
      <tbody>@foreach($lineas as $l)<tr><td>{{ $l->codigo }}</td><td>{{ $l->descripcion }}</td><td class="text-right">{{ $l->cantidad }}</td><td class="text-right">{{ number_format($l->p_lista,2,',','.') }}</td><td class="text-right">{{ number_format($l->descuento,2,',','.') }}</td><td class="text-right">{{ number_format($l->p_venta,2,',','.') }}</td></tr>@endforeach</tbody>
    </table></div>
  </details>

</div></section>
@endsection
@push('page_css')
<style>
.maym-fact .maym-card{border:1px solid #dce5ed;border-radius:9px;overflow:hidden;box-shadow:0 3px 12px rgba(18,52,80,.04)}
.maym-fact .maym-kpi{padding:15px;min-height:105px;border:1px solid #dce5ed;background:#fff;border-radius:9px}
.maym-fact .maym-kpi small{display:block;font-size:.73rem;text-transform:uppercase;color:#647a8d;font-weight:700}
.maym-fact .maym-kpi strong{display:block;font-size:1.7rem;margin:6px 0;color:#254767}
.maym-fact .maym-kpi span{color:#6d8092;font-size:.77rem}
.maym-fact .maym-table{font-size:.8rem}
.maym-fact .maym-table td{vertical-align:middle;padding:9px}
.maym-fact .maym-table th{white-space:nowrap;background:#f4f7fa;padding:9px}
.maym-fact .maym-fact-code{color:#1c4d86;white-space:nowrap}
.maym-fact .maym-fact-description{min-width:220px;color:#354f69;font-weight:500;white-space:normal;line-height:1.35}
.maym-fact .maym-documento{border:1px solid #dee7f0;border-radius:8px;background:#fff;overflow:hidden}
.maym-fact .maym-documento>summary{background:#f5f9fc;cursor:pointer}
.maym-fact .maym-documento[open]>summary{border-bottom:1px solid #dee7f0}
.maym-fact .maym-summary{cursor:pointer}
</style>
@endpush
