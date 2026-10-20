@extends('layouts.app')

@section('content')
<section class="content-header">
<div class="container-fluid">
  <div class="d-flex align-items-start justify-content-between flex-wrap">
    <div>
      <h1 class="mb-1"><i class="fas fa-clipboard-check text-primary mr-2"></i>Conciliación mensual · Mayoristas</h1>
      <p class="text-muted mb-0">Control documental de mercadería separada, recibida en COMERCIAL MATRIZ y facturada.</p>
    </div>
    <a href="{{ route('ventas.index') }}" class="btn btn-outline-secondary mt-2"><i class="fas fa-arrow-left mr-1"></i>Volver a Ventas</a>
  </div>
</div>
</section>

<section class="content"><div class="container-fluid">
@if(!empty($sinFuentes))
  <div class="alert alert-warning">Faltan las tablas de Ventas o Remisiones para generar el control.</div>
@else
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if($errors->any())
    <div class="alert alert-danger"><strong>No se guardaron los cambios:</strong>
      {{ implode(' · ', $errors->all()) }}
    </div>
  @endif

  @php
    $mesPrevio=\Carbon\Carbon::parse($mes.'-01')->subMonth()->format('Y-m');
    $mesProximo=\Carbon\Carbon::parse($mes.'-01')->addMonth()->format('Y-m');
  @endphp
  <div class="card may-card mb-3">
    <div class="card-body py-3">
      <form method="GET" action="{{ route('ventas.mayoristas.index') }}" class="d-flex align-items-end flex-wrap may-filter">
        <div><label for="mesMay" class="may-label">Mes de consulta</label><input id="mesMay" type="month" class="form-control" name="mes" value="{{ $mes }}" required></div>
        <div><label for="buscarMay" class="may-label">Buscar factura o cliente</label><input id="buscarMay" class="form-control" name="buscar" value="{{ $buscar }}" placeholder="Comprobante / cliente"></div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i>Consultar</button>
        <a class="btn btn-outline-secondary" href="{{ route('ventas.mayoristas.index',['mes'=>$mesPrevio]) }}"><i class="fas fa-chevron-left mr-1"></i>Mes anterior</a>
        <a class="btn btn-outline-secondary" href="{{ route('ventas.mayoristas.index',['mes'=>$mesProximo]) }}">Mes siguiente<i class="fas fa-chevron-right ml-1"></i></a>
        <a class="btn btn-outline-success ml-auto" href="{{ route('ventas.mayoristas.index',array_merge(request()->query(),['exportar'=>'csv'])) }}"><i class="fas fa-file-csv mr-1"></i>Exportar CSV</a>
      </form>
    </div>
  </div>

  <div class="may-section">
    <div><h3>1. Resumen documental de {{ ucfirst(\Carbon\Carbon::parse($mes.'-01')->locale('es')->translatedFormat('F Y')) }}</h3>
      <p>Las fechas corresponden a hechos distintos: separación validada, emisión, recepción y factura. No deben sumarse como si fueran prendas diferentes.</p></div>
  </div>
  <div class="row">
    <div class="col-xl-3 col-md-6 mb-3"><div class="may-kpi may-violet">
      <small>Separadas con fecha validada</small><strong>{{ number_format($separaciones->prendas,0,',','.') }}</strong>
      <span>{{ $separaciones->registros ? $separaciones->registros.' registros con evidencia' : 'Sin separaciones verificadas todavía' }}</span>
    </div></div>
    <div class="col-xl-3 col-md-6 mb-3"><div class="may-kpi may-blue">
      <small>Remitidas a Comercial Matriz</small><strong>{{ number_format($emitidas->prendas,0,',','.') }}</strong>
      <span>{{ number_format($emitidas->documentos,0,',','.') }} líneas emitidas durante el mes</span>
    </div></div>
    <div class="col-xl-3 col-md-6 mb-3"><div class="may-kpi may-green">
      <small>Entradas con recepción confirmada</small><strong>{{ number_format($recibidas->prendas,0,',','.') }}</strong>
      <span>Central: {{ number_format($recibidas->central,0,',','.') }} · Locales: {{ number_format($recibidas->prendas-$recibidas->central,0,',','.') }}</span>
    </div></div>
    <div class="col-xl-3 col-md-6 mb-3"><div class="may-kpi may-orange">
      <small>Facturación mayorista neta</small><strong>{{ number_format($resumen->netas,0,',','.') }}</strong>
      <span>{{ number_format($resumen->brutas,0,',','.') }} facturadas menos {{ number_format($resumen->devoluciones,0,',','.') }} devueltas</span>
    </div></div>
  </div>

  <div class="row">
    <div class="col-lg-4 mb-3"><div class="may-metric"><small>Facturas y notas del mes</small><strong>{{ number_format($resumen->facturas,0,',','.') }}</strong><span>Importe neto: <b>Gs {{ number_format($resumen->pvta,0,',','.') }}</b></span></div></div>
    <div class="col-lg-4 mb-3"><div class="may-metric"><small>Unidades de factura documentalmente vinculadas</small><strong>{{ number_format($resumen->vinculadas,0,',','.') }}</strong><span>Confirmadas por usuario contra remisiones específicas</span></div></div>
    <div class="col-lg-4 mb-3"><div class="may-metric"><small>Pendientes de vincular a remisión</small><strong class="text-warning">{{ number_format($resumen->pendientes,0,',','.') }}</strong><span>{{ $resumen->facturas_pendientes }} comprobantes requieren revisión</span></div></div>
  </div>
  <div class="alert alert-light border small">
    <strong><i class="fas fa-info-circle text-primary mr-1"></i>Importante:</strong>
    una remisión de 720 y una factura de 720 son una <b>coincidencia candidata</b>, no una relación confirmada.
    Una factura puede utilizar varias remisiones y una remisión puede distribuirse entre varias facturas.
    Se valida por <b>código completo, fecha, cantidad disponible y documento</b>.
    No se descuentan automáticamente redistribuciones como nuevas prendas ni se presume que la facturación acredita entrega física.
  </div>

  <div class="may-section"><div><h3>2. Facturas mayoristas del mes</h3>
    <p>La factura es el punto de entrada de la auditoría. Seleccioná <strong>Revisar</strong> para ver artículos, remisiones candidatas y vínculos confirmados.</p></div></div>
  <div class="card may-card mb-3">
    <div class="table-responsive">
      <table class="table table-sm may-table table-hover mb-0">
        <thead><tr>
          <th>Fecha</th><th>Comprobante</th><th>Cliente mayorista</th>
          <th class="text-right">Artículos</th><th class="text-right">Prendas brutas</th>
          <th class="text-right">Devoluciones</th><th class="text-right">Neto</th>
          <th class="text-right">Importe Gs</th><th class="text-right">Vinculadas</th>
          <th class="text-right">Pendientes</th><th>Estado</th><th></th>
        </tr></thead>
        <tbody>
        @forelse($facturasPaginadas as $factura)
          <tr>
            <td>{{ date('d/m/Y',strtotime($factura->fecha)) }}</td>
            <td><strong>{{ $factura->comprobante }}</strong><small class="d-block text-muted">{{ $factura->tipo_comprobante }}</small></td>
            <td>{{ $factura->cliente ?: 'Cliente sin identificar' }}</td>
            <td class="text-right">{{ $factura->articulos }}</td>
            <td class="text-right font-weight-bold">{{ number_format($factura->brutas,0,',','.') }}</td>
            <td class="text-right">{{ number_format($factura->devoluciones,0,',','.') }}</td>
            <td class="text-right">{{ number_format($factura->netas,0,',','.') }}</td>
            <td class="text-right">Gs {{ number_format($factura->pvta,0,',','.') }}</td>
            <td class="text-right text-success">{{ number_format($factura->vinculadas,0,',','.') }}</td>
            <td class="text-right {{ $factura->pendientes > 0 ? 'text-warning font-weight-bold' : '' }}">{{ number_format($factura->pendientes,0,',','.') }}</td>
            <td><span class="badge {{ $factura->estado==='CONFIRMADA DOCUMENTALMENTE' ? 'badge-success' : ($factura->estado==='PARCIAL' ? 'badge-info' : 'badge-warning') }}">{{ $factura->estado }}</span></td>
            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('ventas.mayoristas.factura',['clave'=>$factura->clave,'mes'=>$mes]) }}">Revisar <i class="fas fa-arrow-right ml-1"></i></a></td>
          </tr>
        @empty
          <tr><td colspan="12" class="text-center text-muted p-4">No hay facturas de COMERCIAL MATRIZ para este mes y filtro.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    @if($facturasPaginadas->hasPages())<div class="card-footer bg-white">{{ $facturasPaginadas->links() }}</div>@endif
  </div>

  <div class="may-section"><div><h3>3. Saldos y mercadería de otros meses</h3>
    <p>El saldo inicial necesita una declaración respaldada por cada código. Sin esa evidencia el cierre <b>no está determinado</b>.</p></div></div>
  <div class="row">
    <div class="col-xl-4 col-md-6 mb-3"><div class="may-kpi"><small>Códigos sin saldo inicial declarado</small><strong>{{ $sinSaldo }}</strong><span>Es necesario registrar incluso un cero, cuando esté documentado</span></div></div>
    <div class="col-xl-4 col-md-6 mb-3"><div class="may-kpi may-blue"><small>Saldo documental estimado al cierre</small><strong>{{ $cierre===null ? 'NO DETERMINADO' : number_format($cierre,0,',','.') }}</strong><span>Saldo inicial + entradas recibidas − ventas netas · no inventario físico</span></div></div>
    <div class="col-xl-4 col-md-6 mb-3"><div class="may-kpi may-orange"><small>Remisiones antiguas sin vínculo</small><strong>{{ $arrastres->count() }}</strong><span>Remisiones sin asignación dentro de las 100 más recientes del historial; no es un total de stock físico</span></div></div>
  </div>

  <div class="row">
    <div class="col-lg-7 mb-3"><div class="card may-card h-100">
      <div class="card-header bg-white"><strong>Saldo por código · prueba documental</strong></div>
      <div class="table-responsive may-scroll">
        <table class="table table-sm may-table mb-0">
          <thead><tr><th>Código completo</th><th class="text-right">Saldo inicial</th><th class="text-right">Recibido</th><th class="text-right">Facturado neto</th><th class="text-right">Saldo teórico</th></tr></thead>
          <tbody>
            @forelse($saldos as $s)
            <tr><td><strong>{{ $s->codigo }}</strong></td>
              <td class="text-right">{{ $s->inicial===null ? 'SIN DATO' : number_format($s->inicial,0,',','.') }}</td>
              <td class="text-right">{{ number_format($s->entrada,0,',','.') }}</td>
              <td class="text-right">{{ number_format($s->venta,0,',','.') }}</td>
              <td class="text-right">{{ $s->cierre===null ? 'NO DETERMINADO' : number_format($s->cierre,0,',','.') }}</td></tr>
            @empty
              <tr><td colspan="5" class="text-center text-muted p-3">Sin movimientos para calcular.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div></div>
    <div class="col-lg-5 mb-3"><div class="card may-card h-100">
      <div class="card-header bg-white"><strong>Registrar saldo inicial respaldado</strong></div>
      <div class="card-body">
        <p class="small text-muted">Registrá solamente un saldo confirmado por inventario o documento. El sistema conserva la evidencia y los cambios. No completa automáticamente con cero los meses sin información.</p>
        <form method="POST" action="{{ route('ventas.mayoristas.saldo') }}">
          @csrf
          <input type="hidden" name="mes" value="{{ $mes }}">
          <div class="form-group"><label>Código completo</label><input name="codigo" class="form-control" maxlength="80" required placeholder="Ej. 060617424AZ01"></div>
          <div class="form-group"><label>Saldo al inicio de {{ $mes }}</label><input type="number" min="0" name="cantidad" class="form-control" required></div>
          <div class="form-group"><label>Documento / evidencia del arqueo</label><input name="referencia" class="form-control" maxlength="255" required placeholder="Nº inventario, planilla, responsable"></div>
          <button class="btn btn-outline-primary btn-block">Guardar saldo declarado</button>
        </form>
      </div>
    </div></div>
  </div>

  <div class="card may-card mb-3">
    <div class="card-header bg-white"><strong>Documentos anteriores con cantidades aún no vinculadas a facturas</strong></div>
    <div class="table-responsive may-scroll">
      <table class="table table-sm may-table mb-0">
        <thead><tr><th>Remisión</th><th>Mes original</th><th>OT / Origen</th><th>Código completo</th><th class="text-right">Prendas sin vincular</th></tr></thead>
        <tbody>
          @forelse($arrastres as $r)
            <tr><td>{{ $r->serie }}-{{ $r->numero_remision }}</td>
              <td>{{ date('m/Y',strtotime($r->fecha_remision)) }}<small class="d-block text-muted">{{ date('d/m',strtotime($r->fecha_remision)) }}</small></td>
              <td>{{ $r->nro_ot ? 'OT '.$r->nro_ot : 'Sin OT' }}<small class="d-block text-muted">{{ $r->sucursal_salida }}</small></td>
              <td>{{ $r->codigo }}</td><td class="text-right font-weight-bold">{{ number_format($r->sin_vincular,0,',','.') }}</td></tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted p-3">No hay remisiones pendientes en esta muestra.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer bg-white small text-muted">Esta lista es una muestra de remisiones antiguas. «Sin vincular» no significa necesariamente que sigan físicamente disponibles: pueden existir facturas de períodos anteriores aún no conciliadas.</div>
  </div>

  <div class="alert alert-light border small">
    <strong>MODELO MUESTRA · fotografía:</strong>
    @if($separaciones->registros)
      reserva orientativa de <b>{{ number_format($reservaFotoEstimada,0,',','.') }} prendas</b>,
      suponiendo hasta {{ $muestrasPorOt }} por OT separada este mes.
    @else
      todavía no hay separaciones verificadas para estimar la reserva de fotografías.
    @endif
    Las muestras no desaparecen del inventario y no se consideran ventas.
    <form method="GET" class="d-inline-flex align-items-center ml-2">
      <input type="hidden" name="mes" value="{{ $mes }}">
      <label for="mayFoto" class="mb-0 mr-1">Reserva por OT</label>
      <select id="mayFoto" name="muestras_por_ot" class="form-control form-control-sm mr-2" style="width:65px">
        @foreach([0,1,2,3,4,5] as $n)
          <option value="{{ $n }}" {{ $n===$muestrasPorOt ? 'selected' : '' }}>{{ $n }}</option>
        @endforeach
        @if($muestrasPorOt > 5)
          <option value="{{ $muestrasPorOt }}" selected>{{ $muestrasPorOt }}</option>
        @endif
      </select>
      <button class="btn btn-sm btn-outline-primary">Aplicar</button>
    </form>
  </div>

  <div class="may-section"><div><h3>4. Separaciones del plan mayorista</h3>
    <p>El plan logístico muestra lo asignado, pero su fecha de creación no demuestra cuándo se separaron las prendas. Confirmá la fecha efectiva contra un documento.</p></div></div>
  <div class="row">
    <div class="col-xl-6 mb-3"><div class="card may-card h-100">
      <div class="card-header bg-white">
        <strong>Planes pendientes de documentar · hasta 60 resultados</strong>
        <form method="GET" action="{{ route('ventas.mayoristas.index') }}" class="d-flex mt-2">
          <input type="hidden" name="mes" value="{{ $mes }}">
          <input name="ot_plan" class="form-control form-control-sm mr-2" value="{{ $planBuscar }}" placeholder="Buscar OT o código antiguo" aria-label="Buscar plan por OT o código">
          <button class="btn btn-sm btn-outline-primary">Buscar plan</button>
        </form>
      </div>
      <div class="table-responsive may-scroll">
        <table class="table table-sm may-table mb-0">
          <thead><tr><th>OT</th><th>Destino</th><th class="text-right">Pendiente registrar</th><th>Validar separación</th></tr></thead>
          <tbody>
          @forelse($planes as $p)
            <tr>
              <td><strong>{{ $p->nro_ot }}</strong><small class="d-block text-muted">{{ $p->codigo }}</small></td>
              <td>{{ $p->sucursal }}</td><td class="text-right">{{ $p->pendiente }}</td>
              <td>
                @if($p->pendiente>0)
                  <form method="POST" action="{{ route('ventas.mayoristas.separar') }}" class="may-inline-form">
                    @csrf
                    <input type="hidden" name="logistica_detalle_id" value="{{ $p->id }}">
                    <input type="date" name="fecha_separacion" value="{{ $desde }}" required title="Fecha efectiva de separación">
                    <input type="number" name="cantidad" value="{{ $p->pendiente }}" min="1" max="{{ $p->pendiente }}" required title="Prendas">
                    <input type="text" name="referencia" maxlength="255" required placeholder="Evidencia">
                    <button class="btn btn-sm btn-outline-success" title="Confirmar separación"><i class="fas fa-check"></i></button>
                  </form>
                @else <span class="text-success small">Plan ya documentado</span> @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="text-center text-muted p-3">No se encontraron planes MODELO MUESTRA / MAYORISTA.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div></div>
    <div class="col-xl-6 mb-3"><div class="card may-card h-100">
      <div class="card-header bg-white"><strong>Separaciones validadas en {{ $mes }}</strong></div>
      <div class="table-responsive may-scroll">
        <table class="table table-sm may-table mb-0">
          <thead><tr><th>Fecha</th><th>OT / Código</th><th class="text-right">Cantidad</th><th>Referencia</th><th>Revertir</th></tr></thead>
          <tbody>
          @forelse($separacionesDetalle as $s)
            <tr>
              <td>{{ date('d/m/Y',strtotime($s->fecha_separacion)) }}</td>
              <td>{{ $s->nro_ot }}<small class="d-block text-muted">{{ $s->codigo }}</small></td>
              <td class="text-right font-weight-bold">{{ $s->cantidad }}</td>
              <td>{{ $s->referencia }}</td>
              <td>
                <form method="POST" action="{{ route('ventas.mayoristas.anular_sep',['id'=>$s->id]) }}" class="d-flex may-inline-form">
                  @csrf
                  <input type="text" name="motivo" required minlength="5" maxlength="255" placeholder="Motivo de anulación" aria-label="Motivo">
                  <button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Anular separación? Quedará auditada.')">Anular</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted p-3">Aún no hay separaciones documentalmente validadas para este mes.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div></div>
  </div>

  <div class="card may-card mb-4">
    <div class="card-header bg-white"><strong><i class="fas fa-clipboard-list mr-2 text-primary"></i>5. Estado del cierre mensual</strong></div>
    <div class="card-body">
      <p class="mb-2"><strong>Cierre documental estimado</strong></p>
      <div class="may-check"><i class="fas {{ $resumen->facturas_pendientes ? 'fa-exclamation-triangle text-warning':'fa-check-circle text-success' }}"></i>
        {{ $resumen->facturas_pendientes }} facturas con unidades sin vinculación documental</div>
      <div class="may-check"><i class="fas {{ $sinSaldo ? 'fa-exclamation-triangle text-warning':'fa-check-circle text-success' }}"></i>
        {{ $sinSaldo }} códigos sin apertura respaldada</div>
      <div class="may-check"><i class="fas {{ $separaciones->registros ? 'fa-check-circle text-success':'fa-exclamation-triangle text-warning' }}"></i>
        {{ $separaciones->registros }} separaciones fechadas y justificadas en el mes</div>
      <p class="small text-muted mt-2 mb-0">Aunque no haya pendientes, el saldo documental calculado no equivale a un arqueo físico ni acredita una entrega al comprador. El módulo no declara «CERRADO» automáticamente.</p>
    </div>
  </div>
@endif
</div></section>
@endsection

@push('page_css')
<style>
.may-filter{gap:8px}.may-filter>div{min-width:150px}.may-label{display:block;font-size:.75rem;color:#5a7085;font-weight:750;margin-bottom:5px}
.may-card{border:1px solid #e0e9f2;border-radius:10px;box-shadow:0 3px 15px rgba(22,43,70,.045);overflow:hidden}
.may-section{display:flex;justify-content:space-between;align-items:end;margin:20px 0 12px}
.may-section h3{margin:0 0 3px;font-size:1.12rem;font-weight:750;color:#1f354d}
.may-section p{color:#697c8e;font-size:.81rem;margin:0}
.may-kpi,.may-metric{background:#fff;border:1px solid #e0e8f1;border-top:3px solid #95a8ba;border-radius:10px;padding:15px;min-height:112px;height:100%}
.may-blue{border-top-color:#4795c9}.may-green{border-top-color:#2ca777}.may-orange{border-top-color:#edac4e}.may-violet{border-top-color:#917bd5}
.may-kpi small,.may-metric small{font-size:.76rem;font-weight:700;color:#688096;display:block;margin-bottom:8px}
.may-kpi strong,.may-metric strong{font-size:1.65rem;color:#20374d;font-weight:800;display:block;line-height:1.2}
.may-kpi span,.may-metric span{font-size:.77rem;color:#6c7b88;display:block;margin-top:7px}
.may-table{font-size:.8rem}.may-table th{font-size:.74rem;color:#53667b;background:#f7f9fc;white-space:nowrap;padding:11px 9px}.may-table td{vertical-align:middle;padding:9px}
.may-scroll{max-height:410px;overflow:auto}.may-inline-form{display:flex;gap:5px;align-items:center;min-width:280px}
.may-inline-form input{border:1px solid #cbd5e1;border-radius:4px;font-size:.76rem;padding:4px;min-width:0}
.may-inline-form input[type="date"]{width:112px}.may-inline-form input[type="number"]{width:58px}.may-inline-form input[type="text"]{flex:1;min-width:100px}
.may-check{font-size:.88rem;color:#43566a;padding:6px 0}.may-check i{margin-right:7px}
@media(max-width:767px){.may-kpi strong{font-size:1.3rem}.may-filter>*{flex:1 1 150px}}
</style>
@endpush
