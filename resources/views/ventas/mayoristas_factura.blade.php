@extends('layouts.app')

@section('content')
<section class="content-header"><div class="container-fluid">
  <div class="d-flex align-items-start justify-content-between flex-wrap">
    <div><h1 class="mb-1"><i class="fas fa-file-invoice-dollar text-primary mr-2"></i>Conciliación de factura mayorista</h1>
      <p class="text-muted mb-0">Comprobante {{ $factura->comprobante }} · {{ date('d/m/Y',strtotime($factura->fecha)) }} · {{ $factura->cliente ?: 'Cliente sin identificar' }}</p></div>
    <a class="btn btn-outline-secondary mt-2" href="{{ route('ventas.mayoristas.index',['mes'=>$mes]) }}"><i class="fas fa-arrow-left mr-1"></i>Volver al mes {{ $mes }}</a>
  </div>
</div></section>

<section class="content"><div class="container-fluid">
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if($errors->any())
    <div class="alert alert-danger"><strong>No se guardó el vínculo:</strong> {{ implode(' · ',$errors->all()) }}</div>
  @endif
  <div class="may-fact-banner mb-3">
    <strong><i class="fas fa-search mr-1"></i>La sugerencia no confirma la relación.</strong>
    El sistema busca remisiones de entrada a COMERCIAL MATRIZ que coincidan en el código completo.
    La coincidencia de 720 unidades no basta si difieren artículos o si la remisión ya fue asignada.
    Una asociación solo se confirma al ingresar cantidad y evidencia.
  </div>

  <div class="row">
    <div class="col-xl-3 col-md-6 mb-3"><div class="may-fact-kpi"><small>Unidades facturadas</small><strong>{{ number_format($factura->brutas,0,',','.') }}</strong><span>{{ $factura->articulos }} códigos distintos</span></div></div>
    <div class="col-xl-3 col-md-6 mb-3"><div class="may-fact-kpi"><small>Devoluciones / crédito</small><strong>{{ number_format($factura->devoluciones,0,',','.') }}</strong><span>Neto: {{ number_format($factura->netas,0,',','.') }} prendas</span></div></div>
    <div class="col-xl-3 col-md-6 mb-3"><div class="may-fact-kpi may-succ"><small>Vinculadas a remisión</small><strong>{{ number_format(array_sum($confirmadas),0,',','.') }}</strong><span>Revisadas manualmente, sin uso doble</span></div></div>
    <div class="col-xl-3 col-md-6 mb-3"><div class="may-fact-kpi may-warn"><small>Estado documental</small><strong class="may-status">{{ $estado }}</strong><span>Facturación no equivale a entrega física</span></div></div>
  </div>

  <div class="card may-card mb-3">
    <div class="card-header bg-white"><strong>1. Artículos de la factura</strong></div>
    <div class="table-responsive">
      <table class="table table-sm may-table mb-0">
        <thead><tr><th>Código completo</th><th>Descripción</th><th class="text-right">Cantidad</th><th class="text-right">PLISTA Gs</th><th class="text-right">DTO Gs</th><th class="text-right">PVTA Gs</th></tr></thead>
        <tbody>
          @foreach($lineas as $linea)
            <tr class="{{ $linea->cantidad < 0 ? 'table-danger' : '' }}">
              <td><strong>{{ $linea->codigo }}</strong></td>
              <td>{{ $linea->descripcion }}</td>
              <td class="text-right">{{ number_format($linea->cantidad,0,',','.') }}</td>
              <td class="text-right">{{ number_format($linea->p_lista,0,',','.') }}</td>
              <td class="text-right">{{ number_format($linea->descuento,0,',','.') }}</td>
              <td class="text-right font-weight-bold">{{ number_format($linea->p_venta,0,',','.') }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="card may-card mb-3">
    <div class="card-header bg-white"><strong>2. Conciliación por código · cantidad de unidades</strong></div>
    <div class="table-responsive">
      <table class="table table-sm may-table mb-0">
        <thead><tr><th>Código completo</th><th class="text-right">Requeridas</th><th class="text-right">Vinculadas</th><th class="text-right">Pendientes</th><th>Avance documental</th></tr></thead>
        <tbody>
          @forelse($porCodigo as $linea)
          <tr>
            <td><strong>{{ $linea->codigo }}</strong></td><td class="text-right">{{ number_format($linea->necesita,0,',','.') }}</td>
            <td class="text-right text-success">{{ number_format($linea->validado,0,',','.') }}</td>
            <td class="text-right {{ $linea->pendiente ? 'text-warning font-weight-bold' : '' }}">{{ number_format($linea->pendiente,0,',','.') }}</td>
            <td><div class="progress" style="min-width:130px;height:9px"><div class="progress-bar bg-success" style="width:{{ $linea->necesita ? (100*$linea->validado/$linea->necesita) : 0 }}%"></div></div></td>
          </tr>
          @empty
          <tr><td colspan="5" class="text-center text-muted p-3">No hay unidades positivas para vincular. Las notas de crédito se conservan como operaciones de devolución, sin atribuir automáticamente una entrada física.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="card may-card mb-3">
    <div class="card-header bg-white">
      <strong><i class="fas fa-clipboard-list text-primary mr-1"></i>3. Carpeta de documentos sugeridos · últimos 30 días</strong>
      <small class="d-block text-muted">Agrupados por número de remisión, fecha y origen. Ordenados por las unidades del documento que coinciden en código completo con esta factura.</small>
    </div>
    <div class="table-responsive">
      <table class="table table-sm may-table mb-0">
        <thead><tr>
          <th>Remisión</th><th>Fecha</th><th>Origen</th>
          <th class="text-right">Códigos coincidentes</th>
          <th class="text-right">Aporte potencial a esta factura</th><th>Validación</th>
        </tr></thead>
        <tbody>
          @forelse($documentosSugeridos as $doc)
            <tr>
              <td><strong>{{ $doc->serie }}-{{ $doc->numero }}</strong><small class="d-block text-muted">{{ $doc->destino ?: 'COMERCIAL MATRIZ' }}</small></td>
              <td>{{ date('d/m/Y',strtotime($doc->fecha)) }}</td>
              <td>{{ $doc->origen ?: 'Sin origen' }}</td>
              <td class="text-right">{{ $doc->codigos }}</td>
              <td class="text-right font-weight-bold">{{ number_format($doc->aporte,0,',','.') }} de {{ number_format($factura->brutas,0,',','.') }}</td>
              <td><small class="text-warning">Candidato, no validado</small></td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-muted text-center p-3">No se encontraron documentos de los 30 días previos que hayan sido recibidos y coincidan en código. Consultá el historial completo más abajo.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer bg-white">
      <small class="text-muted">Cada aporte es independiente, no sumable sin una asignación: varios documentos pueden ofrecer los mismos códigos y también servir a otras facturas. Los documentos históricos se pueden revisar en el detalle.</small>
    </div>
  </div>

  <details class="card may-card mb-3">
    <summary class="card-header bg-white" style="cursor:pointer">
      <strong><i class="fas fa-search mr-1 text-primary"></i>4. Abrir líneas de remisión y vincular con evidencia</strong>
      <small class="d-block text-muted">Vista ampliada por código, talle, OT, fecha y unidades disponibles. Las remisiones antiguas también se encuentran aquí.</small>
    </summary><small class="d-block text-muted mt-1">
      Pueden ser de CASA CENTRAL o de uno o varios locales, de este mes o de meses anteriores.
      Incluye remisiones hasta tres días posteriores a la factura como excepción para revisión;
      solo las recibidas y no posteriores permiten vinculación directa.</small></div>
    @if($candidatasLimitadas)
      <div class="alert alert-warning m-3">Hay más de 300 líneas de remisión candidatas para estos códigos. Se muestran las 300 líneas más recientes; revisá el historial antes de concluir que no existen documentos adicionales.</div>
    @endif
    <div class="table-responsive">
      <table class="table table-sm table-hover may-table mb-0">
        <thead><tr>
          <th>Remisión</th><th>Fecha</th><th>Origen → destino</th><th>Código completo</th>
          <th class="text-right">Remitido</th><th class="text-right">Disponible sin asignar</th>
          <th>Recepción</th><th>Vincular cantidad y evidencia</th>
        </tr></thead>
        <tbody>
        @forelse($candidatas as $r)
          @php
            $pendienteFactura=isset($porCodigo[$r->codigo_normalizado]) ? $porCodigo[$r->codigo_normalizado]->pendiente : 0;
            $maximoAsignable=min($pendienteFactura,$r->disponible);
          @endphp
          <tr>
            <td><strong>{{ $r->serie }}-{{ $r->numero_remision }}</strong>
               <small class="d-block text-muted">ID {{ $r->id }} · {{ $r->nro_ot ? 'OT '.$r->nro_ot : 'Sin OT vinculada' }}</small></td>
            <td>{{ date('d/m/Y',strtotime($r->fecha_remision)) }}</td>
            <td>{{ $r->sucursal_salida ?: '—' }} <i class="fas fa-long-arrow-alt-right text-muted"></i> {{ $r->sucursal_destino ?: $r->sucursal_logistica ?: '—' }}</td>
            <td><strong>{{ $r->codigo }}</strong><small class="d-block text-muted">{{ $r->descripcion }}</small></td>
            <td class="text-right">{{ number_format($r->cantidad,0,',','.') }}</td>
            <td class="text-right {{ $r->disponible ? 'text-success font-weight-bold':'text-muted' }}">{{ number_format($r->disponible,0,',','.') }}</td>
            <td>
              @if($r->fecha_recepcion)
                <span class="badge badge-success">{{ date('d/m/Y',strtotime($r->fecha_recepcion)) }}</span>
              @else
                <span class="badge badge-warning">Sin confirmar</span>
              @endif
              @if($r->tardia)<small class="d-block text-warning">Remisión posterior</small>@endif
            </td>
            <td>
              @if($r->fecha_recepcion && !$r->tardia && $maximoAsignable>0)
                <form class="may-inline" method="POST" action="{{ route('ventas.mayoristas.vincular',['clave'=>$factura->clave]) }}">
                  @csrf
                  <input type="hidden" name="mes" value="{{ $mes }}">
                  <input type="hidden" name="remision_id" value="{{ $r->id }}">
                  <input type="number" class="form-control form-control-sm" name="cantidad" min="1" max="{{ $maximoAsignable }}" value="{{ $maximoAsignable }}" required title="Cantidad">
                  <input class="form-control form-control-sm" name="referencia" maxlength="255" minlength="5" required placeholder="Evidencia / motivo">
                  <button class="btn btn-sm btn-outline-success" onclick="return confirm('¿Confirmar este vínculo y reservar las unidades?')">Vincular</button>
                </form>
              @else
                <small class="text-muted">{{ !$r->fecha_recepcion ? 'Requiere recepción' : ($r->tardia ? 'Posterior a factura: investigar' : ($r->disponible<=0 ? 'Sin saldo documental' : 'Código facturado completo')) }}</small>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-center text-muted p-4">No hay remisiones candidatas por código completo hasta la fecha de esta factura. Puede tratarse de mercadería de otra fuente o de documentos todavía no importados.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </details>

  <div class="card may-card mb-3">
    <div class="card-header bg-white"><strong>5. Remisiones vinculadas y confirmadas por un usuario</strong></div>
    <div class="table-responsive">
      <table class="table table-sm may-table mb-0">
        <thead><tr><th>Remisión</th><th>Fecha</th><th>Origen</th><th>Código</th><th class="text-right">Unidades utilizadas</th><th>Responsable / Referencia</th><th>Revertir con motivo</th></tr></thead>
        <tbody>
        @forelse($enlaces as $v)
          <tr>
            <td><strong>{{ $v->serie }}-{{ $v->numero_remision }}</strong></td>
            <td>{{ date('d/m/Y',strtotime($v->fecha_remision)) }}</td><td>{{ $v->sucursal_salida }}</td>
            <td>{{ $v->codigo }}</td><td class="text-right font-weight-bold">{{ $v->cantidad }}</td>
            <td><small>Usuario #{{ $v->usuario_id }}</small><span class="d-block small text-muted">{{ $v->observacion }}</span></td>
            <td>
              <form method="POST" action="{{ route('ventas.mayoristas.desvincular',['clave'=>$factura->clave,'id'=>$v->id]) }}" class="may-inline">
                @csrf
                <input type="hidden" name="mes" value="{{ $mes }}">
                <input class="form-control form-control-sm" type="text" name="motivo" minlength="5" maxlength="255" required placeholder="Motivo">
                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Liberar estas unidades? Quedará auditado.')">Desvincular</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-center text-muted p-3">Todavía no existen vínculos confirmados para esta factura.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  @if(isset($auditoria) && $auditoria->isNotEmpty())
  <div class="card may-card mb-4">
    <div class="card-header bg-white"><strong><i class="fas fa-history mr-1 text-primary"></i>6. Historial de auditoría de esta factura</strong></div>
    <div class="table-responsive">
      <table class="table table-sm may-table mb-0">
        <thead><tr><th>Fecha / hora</th><th>Acción</th><th>Usuario</th><th>Cantidad</th><th>Observación</th></tr></thead>
        <tbody>
        @foreach($auditoria as $a)
          <tr><td>{{ $a->created_at }}</td><td>{{ $a->accion }}</td><td>{{ $a->usuario_id }}</td><td>{{ $a->cantidad }}</td><td>{{ $a->detalle }}</td></tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

</div></section>
@endsection

@push('page_css')
<style>
.may-card{border:1px solid #e0e8f1;box-shadow:0 3px 12px rgba(29,48,72,.04);border-radius:9px;overflow:hidden}
.may-fact-banner{background:#eff7fd;border-left:4px solid #4799ca;border-radius:6px;padding:13px 17px;font-size:.83rem;color:#395671}
.may-fact-kpi{height:100%;min-height:115px;padding:15px;background:#fff;border:1px solid #dfe8f2;border-top:3px solid #609ac3;border-radius:8px}
.may-fact-kpi small{display:block;font-weight:700;font-size:.76rem;color:#698096;margin-bottom:7px}
.may-fact-kpi strong{display:block;font-size:1.65rem;color:#273d52}
.may-fact-kpi .may-status{font-size:1.0rem;margin-top:9px}
.may-fact-kpi span{font-size:.76rem;color:#697c8e;display:block;margin-top:8px}
.may-succ{border-top-color:#32a47f}.may-warn{border-top-color:#efae54}
.may-table{font-size:.80rem}.may-table th{background:#f7f9fc;color:#53687a;white-space:nowrap;padding:9px}.may-table td{vertical-align:middle;padding:9px}
.may-inline{display:flex;align-items:center;gap:6px;min-width:230px}
.may-inline input[type=number]{width:78px}.may-inline input[type=text]{min-width:120px;max-width:280px}
</style>
@endpush
