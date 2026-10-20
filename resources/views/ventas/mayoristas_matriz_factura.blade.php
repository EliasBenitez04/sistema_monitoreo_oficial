@extends('layouts.app')
@section('content')
<section class="content"><div class="container-fluid py-3 maym-fact">
  <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <div><h2 class="mb-1"><i class="fas fa-file-invoice text-primary mr-2"></i>Cruce de factura con remisiones</h2>
      <div class="text-muted">{{ $factura->comprobante }} · {{ date('d/m/Y',strtotime($factura->fecha)) }} · {{ $factura->cliente }} · COMERCIAL MATRIZ</div>
    </div>
    <a href="{{ route('ventas.mayoristas.index',['desde'=>request('desde',$mes.'-01'),'hasta'=>request('hasta',$factura->fecha)]) }}" class="btn btn-outline-secondary mt-2"><i class="fas fa-arrow-left mr-1"></i>Volver a ventas</a>
  </div>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
  <div class="alert alert-info small">
    <strong>Procedimiento:</strong> 1. Verificá los códigos completos y las cantidades facturadas.
    2. Compará talles dentro de una MISMA serie y número de remisión.
    3. Confirmá manualmente qué cantidad de cada línea corresponde a esta factura.
    4. El saldo disponible de esa remisión disminuirá sin duplicar unidades en otras facturas.
    <strong>Remisión disponible ≠ stock físico acreditado en COMERCIAL MATRIZ.</strong>
  </div>
  <div class="row">
    <div class="col-lg-3 col-sm-6 mb-3"><div class="maym-kpi"><small>Unidades facturadas brutas</small><strong>{{ number_format($factura->brutas,0,',','.') }}</strong><span>{{ $totales->codigos }} códigos / talles</span></div></div>
    <div class="col-lg-3 col-sm-6 mb-3"><div class="maym-kpi"><small>Vinculadas con evidencia</small><strong class="text-success">{{ number_format($totales->asignadas,0,',','.') }}</strong><span>Descontadas de las remisiones</span></div></div>
    <div class="col-lg-3 col-sm-6 mb-3"><div class="maym-kpi"><small>Faltan vincular</small><strong class="text-warning">{{ number_format(max(0,$totales->pendientes),0,',','.') }}</strong><span>Sin atribuir automáticamente</span></div></div>
    <div class="col-lg-3 col-sm-6 mb-3"><div class="maym-kpi"><small>Remisiones candidatas</small><strong>{{ number_format($totales->documentos,0,',','.') }}</strong><span>{{ $totales->documentos_completos }} contienen todos los códigos</span></div></div>
  </div>
  <div class="card mb-3 maym-card">
    <div class="card-header bg-white"><strong>1. Facturado, asignado y pendiente por código completo</strong><small class="d-block text-muted">Cada talle tiene su propia cantidad; no se compensa un código con prendas de otro talle.</small></div>
    <div class="table-responsive">
      <table class="table table-sm maym-table mb-0"><thead><tr><th>Código completo</th><th class="text-right">Factura</th><th class="text-right">Asignadas</th><th class="text-right">Faltan asignar</th></tr></thead>
        <tbody>@forelse($porCodigo as $item)<tr>
          <td class="font-weight-bold">{{ $item->codigo }}</td>
          <td class="text-right">{{ number_format($item->necesita,0,',','.') }}</td>
          <td class="text-right text-success">{{ number_format($item->validado,0,',','.') }}</td>
          <td class="text-right {{ $item->pendiente ? 'text-warning font-weight-bold':'' }}">{{ number_format($item->pendiente,0,',','.') }}</td>
        </tr>@empty<tr><td colspan="4" class="text-muted text-center">Esta operación no tiene unidades positivas para asignación; revisar las notas de crédito separadamente.</td></tr>@endforelse</tbody>
      </table>
    </div>
  </div>
  <div class="card mb-3 maym-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
      <div><strong>2. Remisiones hacia Matriz desde TODAS las sucursales y Modelo Muestra</strong>
        <small class="d-block text-muted">Verificá los códigos y talles dentro del mismo documento. Cada remisión conserva su origen real (Multiplaza, Luque, Central, etc.). Las remisiones posteriores a la factura se muestran para investigar, pero no se pueden asignar.</small></div>
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
                <span class="badge badge-{{ $doc->completo ? 'success':'secondary' }}">{{ $doc->coincidencias }} de {{ $totales->codigos }} códigos de la factura</span>
                @foreach($doc->grupos_de_ot as $grupoOt)
                  <span class="badge badge-info">ID interno OT {{ $grupoOt->id_ot }} · {{ $grupoOt->codigos }} códigos en este documento</span>
                @endforeach
                <span class="badge badge-light">Remitido {{ number_format($doc->remitidas,0,',','.') }}</span>
                <span class="badge badge-light">Ya vinculado {{ number_format($doc->usadas,0,',','.') }}</span>
                <span class="badge badge-primary">Saldo {{ number_format($doc->disponibles,0,',','.') }}</span>
              </div>
            </div>
          </summary>
          <div class="table-responsive">
            <table class="table table-sm table-hover maym-table mb-0">
              <thead><tr><th>Código/talle</th><th>Artículo</th><th>Recepción</th><th class="text-right">En esta remisión</th><th class="text-right">Ya usado</th><th class="text-right">Saldo</th><th class="text-right">Falta en factura</th><th>Asignar a esta factura</th></tr></thead>
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
                    <td class="text-right">{{ number_format($r->cantidad,0,',','.') }}</td>
                    <td class="text-right">{{ number_format($r->usadas,0,',','.') }}</td>
                    <td class="text-right font-weight-bold">{{ number_format($r->disponible,0,',','.') }}</td>
                    <td class="text-right">{{ number_format($r->pendiente_factura,0,',','.') }}</td>
                    <td>
                      @if($r->maximo > 0 && ($r->fecha_recepcion || $r->modelo))
                        <form method="POST" action="{{ route('ventas.mayoristas.vincular',['clave'=>$factura->clave]) }}" class="maym-form">
                          @csrf
                          <input type="hidden" name="mes" value="{{ $mes }}">
                          <input type="hidden" name="remision_id" value="{{ $r->id }}">
                          <input type="number" min="1" max="{{ $r->maximo }}" name="cantidad" value="{{ $r->maximo }}" class="form-control form-control-sm" required title="Prendas a asociar">
                          <input type="text" name="referencia" class="form-control form-control-sm" maxlength="255" minlength="5" required placeholder="Evidencia / responsable">
                          @if(!$r->fecha_recepcion)
                            <label class="small text-warning m-0">
                              <input type="checkbox" name="confirmar_modelo" value="1" required> Confirmo cruce documental de Modelo Muestra, sin afirmar recepción física
                            </label>
                          @endif
                          <button class="btn btn-sm btn-outline-success" onclick="return confirm('¿Confirmar cantidad de esta remisión para esta factura?')">Asignar</button>
                        </form>
                      @else
                        <small class="text-muted">{{ $r->posterior ? 'Remisión posterior a factura' : (($r->recepcion_posterior && !$r->modelo) ? 'Recepción posterior a factura' : ($r->disponible <= 0 ? 'Sin saldo' : 'Factura cubierta o recepción pendiente')) }}</small>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="px-3 py-2 small text-muted">Ejemplo de saldo por código: 8 documentadas − 2 vinculadas a factura = 6 disponibles en ESTA línea de remisión; no es saldo físico global del local.</div>
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
  <details class="card mb-3 maym-card">
    <summary class="card-header bg-white maym-summary"><strong>4. Remisiones asignadas a esta factura · trazabilidad y reversión</strong></summary>
    <div class="table-responsive"><table class="table table-sm maym-table mb-0">
      <thead><tr><th>Remisión</th><th>Fecha</th><th>Origen real</th><th>Código</th><th class="text-right">Asignadas</th><th>Evidencia</th><th>Revertir</th></tr></thead><tbody>
      @forelse($enlaces as $v)<tr>
        <td>{{ $v->serie }}-{{ $v->numero_remision }}</td><td>{{ $v->fecha_remision }}</td>
        <td><strong>{{ $v->origen_real }}</strong><small class="d-block text-muted">{{ $v->tipo_origen }}</small>
          @if($v->origen_discordante)<small class="d-block text-warning">Revisar origen importado: {{ $v->sucursal_salida }}</small>@endif
        </td><td>{{ $v->codigo }}</td>
        <td class="text-right font-weight-bold">{{ $v->cantidad }}</td><td>{{ $v->observacion }}</td>
        <td><form method="POST" class="maym-form" action="{{ route('ventas.mayoristas.desvincular',['clave'=>$factura->clave,'id'=>$v->id]) }}">
          @csrf<input type="hidden" name="mes" value="{{ $mes }}"><input name="motivo" class="form-control form-control-sm" minlength="5" maxlength="255" required placeholder="Motivo de reversión">
          <button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Revertir vínculo con auditoría?')">Desvincular</button>
        </form></td>
      </tr>@empty<tr><td colspan="7" class="text-muted text-center">Todavía no hay asignaciones confirmadas.</td></tr>@endforelse
      </tbody></table></div>
    @if($auditoria->isNotEmpty())
      <div class="px-3 py-2 small text-muted">Últimos movimientos de auditoría:</div>
      <div class="table-responsive" style="max-height:200px;overflow:auto"><table class="table table-sm maym-table mb-0"><thead><tr><th>Fecha</th><th>Acción</th><th>Unidades</th><th>Usuario</th><th>Motivo/evidencia</th></tr></thead><tbody>@foreach($auditoria as $a)<tr><td>{{ $a->created_at }}</td><td>{{ $a->accion }}</td><td>{{ $a->cantidad }}</td><td>{{ $a->usuario_id }}</td><td>{{ $a->detalle }}</td></tr>@endforeach</tbody></table></div>
    @endif
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
.maym-fact .maym-documento{border:1px solid #dee7f0;border-radius:8px;background:#fff;overflow:hidden}
.maym-fact .maym-documento>summary{background:#f5f9fc;cursor:pointer}
.maym-fact .maym-documento[open]>summary{border-bottom:1px solid #dee7f0}
.maym-fact .maym-form{display:flex;align-items:center;gap:5px;flex-wrap:wrap;min-width:220px}
.maym-fact .maym-form input[type=number]{width:70px}
.maym-fact .maym-form input[type=text]{width:180px}
.maym-fact .maym-summary{cursor:pointer}
</style>
@endpush