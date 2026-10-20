@extends('layouts.app')

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start flex-wrap">
      <div>
        <h1 class="mb-1"><i class="fas fa-chart-line text-primary mr-2"></i>Mayoristas · Comercial Matriz</h1>
        <p class="text-muted mb-0">Ventas comprobadas, plan logístico y trazabilidad de MODELO MUESTRA.</p>
      </div>
      <div class="d-flex flex-wrap mt-2 mt-md-0">
        <a href="{{ route('ventas.index') }}" class="btn btn-outline-secondary mr-2 mb-1"><i class="fas fa-arrow-left mr-1"></i>Ventas</a>
        <a href="{{ route('ventas.mayoristas.index', array_merge(request()->query(),['exportar'=>'csv'])) }}" class="btn btn-outline-success mb-1"><i class="fas fa-file-csv mr-1"></i>Exportar conciliación</a>
      </div>
    </div>
  </div>
</section>

<section class="content">
<div class="container-fluid">

  <div class="may-regla mb-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between">
      <div>
        <strong><i class="fas fa-check-circle mr-1 text-success"></i>Canal mayorista confirmado: COMERCIAL MATRIZ</strong>
        <p class="mb-0 mt-1 small">Todas las líneas de <strong>ventas.local = COMERCIAL MATRIZ</strong> se consideran ventas mayoristas, incluidas notas de crédito y devoluciones. Los movimientos que salen de Comercial Matriz también se identifican como parte del canal logístico mayorista.</p>
      </div>
      <span class="may-pill mt-2 mt-lg-0">Regla comercial activa</span>
    </div>
  </div>

  <div class="card card-outline card-primary shadow-sm mb-3">
    <div class="card-header bg-white"><h3 class="card-title font-weight-bold"><i class="fas fa-filter mr-2"></i>Período y criterios de análisis</h3></div>
    <div class="card-body pb-2">
      <form action="{{ route('ventas.mayoristas.index') }}" method="GET">
        <div class="row align-items-end">
          <div class="col-xl-2 col-md-4 col-6 mb-2">
            <label for="mayDesde" class="may-label">Desde</label>
            <input type="date" name="desde" id="mayDesde" class="form-control" value="{{ $desde }}">
          </div>
          <div class="col-xl-2 col-md-4 col-6 mb-2">
            <label for="mayHasta" class="may-label">Hasta</label>
            <input type="date" name="hasta" id="mayHasta" class="form-control" value="{{ $hasta }}">
          </div>
          <div class="col-xl-2 col-md-4 mb-2">
            <label for="mayDestino" class="may-label">Destino logístico</label>
            <select class="form-control" id="mayDestino" name="destino">
              <option value="todos" {{ $tipo === 'todos' ? 'selected' : '' }}>Todos los destinos</option>
              <option value="modelo" {{ $tipo === 'modelo' ? 'selected' : '' }}>MODELO MUESTRA</option>
              <option value="mayorista" {{ $tipo === 'mayorista' ? 'selected' : '' }}>Otros mayoristas</option>
            </select>
          </div>
          <div class="col-xl-2 col-md-4 mb-2">
            <label for="mayFotos" class="may-label">Fotos · estimación por OT</label>
            <select class="form-control" name="muestras_por_ot" id="mayFotos">
              @foreach([0,1,2,3,4,5] as $n)
                <option value="{{ $n }}" {{ $muestrasPorOt === $n ? 'selected' : '' }}>{{ $n }} {{ $n === 1 ? 'prenda' : 'prendas' }}</option>
              @endforeach
              @if($muestrasPorOt > 5)<option value="{{ $muestrasPorOt }}" selected>{{ $muestrasPorOt }} prendas</option>@endif
            </select>
          </div>
          <div class="col-xl-2 col-md-4 mb-2">
            <label for="mayBuscar" class="may-label">OT / Código / Artículo / Cliente</label>
            <input type="text" class="form-control" id="mayBuscar" name="buscar" value="{{ $buscar }}" placeholder="Buscar...">
          </div>
          <div class="col-xl-2 col-md-4 mb-2">
            <button class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i>Analizar</button>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center justify-content-between pt-2">
          <label class="mb-1 small text-muted">
            <input type="checkbox" name="incluir_plan" value="1" {{ $incluirPlan ? 'checked' : '' }}>
            Incluir planes logísticos sin movimientos ni ventas en este período
          </label>
          <small class="text-muted mb-1">El destino filtra Logística. Las ventas siempre corresponden a todo COMERCIAL MATRIZ.</small>
        </div>
      </form>
    </div>
  </div>

  @if(!$tieneVentas)
    <div class="alert alert-warning"><strong>Falta la tabla ventas.</strong> No es posible consultar ventas mayoristas.</div>
  @endif
  @if(!$tablas)
    <div class="alert alert-warning"><strong>Faltan tablas logísticas.</strong> La comparación con el plan no está disponible.</div>
  @endif

  <div class="may-section-heading">
    <h3><i class="fas fa-file-invoice-dollar mr-2 text-primary"></i>1. Ventas reales · Comercial Matriz</h3>
    <small>{{ date('d/m/Y',strtotime($desde)) }} — {{ date('d/m/Y',strtotime($hasta)) }}</small>
  </div>

  <div class="row">
    <div class="col-xl-3 col-md-6 mb-3"><div class="may-kpi may-kpi-blue">
      <small>Prendas vendidas netas</small>
      <strong>{{ number_format($ventasResumen->prendas_netas,0,',','.') }}</strong>
      <span>{{ number_format($ventasResumen->prendas_vendidas,0,',','.') }} vendidas − {{ number_format($ventasResumen->devoluciones,0,',','.') }} devueltas</span>
    </div></div>
    <div class="col-xl-3 col-md-6 mb-3"><div class="may-kpi may-kpi-green">
      <small>Venta neta facturada</small>
      <strong class="may-money">Gs {{ number_format($ventasResumen->importe_neto,0,',','.') }}</strong>
      <span>PLISTA Gs {{ number_format($ventasResumen->venta_lista,0,',','.') }} · DTO Gs {{ number_format($ventasResumen->descuento,0,',','.') }}</span>
    </div></div>
    <div class="col-xl-3 col-md-6 mb-3"><div class="may-kpi">
      <small>Comprobantes de venta</small>
      <strong>{{ number_format($ventasResumen->comprobantes,0,',','.') }}</strong>
      <span>{{ number_format($ventasResumen->codigos,0,',','.') }} artículos distintos</span>
    </div></div>
    <div class="col-xl-3 col-md-6 mb-3"><div class="may-kpi">
      <small>OT identificadas por código</small>
      <strong>{{ number_format($resumen->ot_con_venta,0,',','.') }}</strong>
      <span>Vínculo por código base único · no por factura</span>
    </div></div>
  </div>

  <div class="row">
    <div class="col-lg-6 mb-3"><div class="card may-card h-100">
      <div class="card-header bg-white"><strong><i class="fas fa-chart-area mr-1 text-primary"></i>Evolución de ventas del período</strong></div>
      <div class="card-body">
        @php $escalaVentas = max(1, (float) $ventasDias->max('importe')); @endphp
        @forelse($ventasDias as $dia)
          <div class="may-bar-row">
            <span class="may-bar-date">{{ date('d/m',strtotime($dia->fecha)) }}</span>
            <div class="may-bar-track" title="{{ date('d/m/Y',strtotime($dia->fecha)) }} · Gs {{ number_format($dia->importe,0,',','.') }} · {{ number_format($dia->prendas,0,',','.') }} prendas">
              <span style="width: {{ max(0, min(100, 100 * max(0,(float) $dia->importe) / $escalaVentas)) }}%"></span>
            </div>
            <strong>Gs {{ number_format($dia->importe,0,',','.') }}</strong>
          </div>
        @empty
          <p class="text-muted mb-0">No hay ventas registradas en COMERCIAL MATRIZ durante el período.</p>
        @endforelse
        <p class="small text-muted mb-0 mt-2">Los valores negativos por devoluciones quedan identificados en la cifra; no se convierten en ventas positivas.</p>
      </div>
    </div></div>
    <div class="col-lg-6 mb-3"><div class="card may-card h-100">
      <div class="card-header bg-white"><strong><i class="fas fa-users mr-1 text-primary"></i>Clientes mayoristas principales</strong></div>
      <div class="table-responsive">
        <table class="table table-sm mb-0 may-table">
          <thead><tr><th>Cliente</th><th class="text-right">Prendas netas</th><th class="text-right">Venta neta</th></tr></thead>
          <tbody>
            @forelse($clientes as $cliente)
              <tr><td>{{ $cliente->cliente }}</td><td class="text-right">{{ number_format($cliente->prendas,0,',','.') }}</td><td class="text-right font-weight-bold">Gs {{ number_format($cliente->venta_neta,0,',','.') }}</td></tr>
            @empty
              <tr><td colspan="3" class="text-muted text-center p-3">Sin clientes en el período.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="card-footer bg-white"><small class="text-muted">Datos exclusivos de las facturas de COMERCIAL MATRIZ.</small></div>
    </div></div>
  </div>

  <div class="may-section-heading">
    <h3><i class="fas fa-truck-loading mr-2 text-primary"></i>2. Plan y remisiones mayoristas</h3>
    <small>El plan es histórico; los movimientos corresponden a las fechas seleccionadas.</small>
  </div>
  <div class="row">
    <div class="col-xl-3 col-md-6 mb-3"><div class="may-kpi">
      <small>Plan logístico asociado</small>
      <strong>{{ number_format($resumen->plan,0,',','.') }}</strong>
      <span>{{ number_format($resumen->ot_plan,0,',','.') }} OT con plan mayorista</span>
    </div></div>
    <div class="col-xl-3 col-md-6 mb-3"><div class="may-kpi may-kpi-blue">
      <small>Volumen de remisiones</small>
      <strong>{{ number_format($resumen->remitido,0,',','.') }}</strong>
      <span>{{ number_format($resumen->salida_central,0,',','.') }} salidas Central · {{ number_format($resumen->traslados,0,',','.') }} movimientos no centrales</span>
    </div></div>
    <div class="col-xl-3 col-md-6 mb-3"><div class="may-kpi may-kpi-green">
      <small>Movimientos recibidos</small>
      <strong>{{ number_format($resumen->recibido,0,',','.') }}</strong>
      <span>Remisiones con fecha de recepción</span>
    </div></div>
    <div class="col-xl-3 col-md-6 mb-3"><div class="may-kpi may-kpi-amber">
      <small>Movimientos pendientes</small>
      <strong>{{ number_format($resumen->transito,0,',','.') }}</strong>
      <span>Remitidos sin recepción confirmada</span>
    </div></div>
  </div>
  <div class="alert alert-light border mb-3">
    <strong><i class="fas fa-info-circle text-primary mr-1"></i>No mezclar unidades:</strong>
    las ventas son comprobantes comerciales; el plan es asignación; las remisiones incluyen redistribuciones o movimientos de origen no central. No todos equivalen a nuevas prendas.
    Esas tres magnitudes no se suman como prendas únicas ni la diferencia se declara automáticamente stock faltante.
  </div>

  <div class="may-section-heading">
    <h3><i class="fas fa-camera mr-2 text-primary"></i>3. MODELO MUESTRA · regla fotográfica</h3>
    <small>Estimación configurable, no registro físico de fotografía.</small>
  </div>
  <div class="row">
    <div class="col-xl-4 col-md-6 mb-3"><div class="may-kpi may-kpi-purple">
      <small>Reserva fotográfica estimada</small>
      <strong>{{ number_format($resumen->reserva_foto,0,',','.') }}</strong>
      <span>Hasta {{ $muestrasPorOt }} prendas por OT, sobre lo recibido en MODELO MUESTRA</span>
    </div></div>
    <div class="col-xl-4 col-md-6 mb-3"><div class="may-kpi may-kpi-green">
      <small>Potencial recibido para comercialización</small>
      <strong>{{ number_format($resumen->recibido_venta_estimado,0,',','.') }}</strong>
      <span>Movimiento recibido menos reserva estimada · NO stock actual</span>
    </div></div>
    <div class="col-xl-4 col-md-6 mb-3"><div class="may-kpi may-kpi-amber">
      <small>Ventas sin vínculo seguro con OT</small>
      <strong>{{ number_format($resumen->sin_ot_venta,0,',','.') }}</strong>
      <span>Gs {{ number_format($resumen->sin_ot_venta_importe,0,',','.') }} sin imputar a una OT</span>
    </div></div>
  </div>
  <div class="may-example mb-3">
    <i class="fas fa-lightbulb mr-2 text-primary"></i>
    <strong>Ejemplo:</strong> si llegan <strong>13 prendas</strong> a MODELO MUESTRA y se reserva <strong>1</strong> para fotografías,
    quedan <strong>12 potencialmente comercializables</strong>. Eso no acredita que se hayan vendido 12 prendas. Las ventas reales aparecen arriba con los comprobantes de COMERCIAL MATRIZ.
  </div>

  @if($resumen->sin_ot_venta != 0 || $resumen->sin_ot_movimiento > 0)
    <div class="alert alert-warning">
      <strong><i class="fas fa-exclamation-triangle mr-1"></i>Conciliación pendiente:</strong>
      {{ number_format($resumen->sin_ot_venta,0,',','.') }} prendas netas vendidas y
      {{ number_format($resumen->sin_ot_movimiento,0,',','.') }} prendas remitidas todavía no tienen
      un vínculo único con OT. No se les asigna una OT por similitud de descripción.
    </div>
  @endif

  <div class="may-section-heading">
    <h3><i class="fas fa-search mr-2 text-primary"></i>4. Cruce por OT · Plan / Logística / Ventas</h3>
    <small>Ordenado por incidencias que requieren revisión.</small>
  </div>
  <div class="card may-card mb-3">
    <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between">
      <div><strong>{{ number_format($ordenes->total(),0,',','.') }} registros</strong>
        <small class="d-block text-muted">Las ventas se vinculan por código base solo si identifica una OT única en todo el historial.</small>
      </div>
      <a href="{{ route('ventas.mayoristas.index',array_merge(request()->query(),['exportar'=>'csv'])) }}" class="btn btn-sm btn-outline-success mt-2 mt-md-0"><i class="fas fa-download mr-1"></i>Exportar cruce CSV</a>
    </div>
    <div class="table-responsive">
      <table class="table table-sm table-hover may-table may-cross mb-0">
        <thead><tr>
          <th>OT / Código</th>
          <th>Artículo</th>
          <th class="text-right">Plan<br>Mayorista</th>
          <th class="text-right">De ello<br>Modelo M.</th>
          <th class="text-right">Remisiones<br>período</th>
          <th class="text-right">Diferencia<br>Plan − Mov.</th>
          <th class="text-right">Recibido</th>
          <th class="text-right">Movimientos<br>no centrales</th>
          <th class="text-right">Venta<br>neta unid.</th>
          <th class="text-right">PVTA<br>Gs</th>
          <th>Diagnóstico documental</th>
        </tr></thead>
        <tbody>
          @forelse($ordenes as $item)
            <tr class="{{ $item->status !== 'CON DOCUMENTOS' ? 'may-row-review' : '' }}">
              <td>
                @if($item->nro_ot)
                  <strong>OT {{ $item->nro_ot }}</strong>
                @else
                  <strong class="text-warning">SIN OT SEGURA</strong>
                @endif
                <small class="d-block text-muted">{{ $item->codigo ?: 'Código sin identificar' }}</small>
              </td>
              <td><span class="may-desc">{{ $item->descripcion ?: 'Sin descripción' }}</span><small class="d-block text-muted">{{ number_format($item->comprobantes,0,',','.') }} comprobantes mayoristas</small></td>
              <td class="text-right">{{ number_format($item->plan,0,',','.') }}</td>
              <td class="text-right">{{ number_format($item->plan_modelo,0,',','.') }}</td>
              <td class="text-right font-weight-bold">{{ number_format($item->remitido,0,',','.') }}<small class="d-block text-muted">{{ $item->ultima_remision ? date('d/m/Y',strtotime($item->ultima_remision)) : '—' }}</small></td>
              <td class="text-right {{ $item->brecha_movimientos > 0 ? 'text-warning font-weight-bold' : 'text-muted' }}">{{ $item->brecha_movimientos > 0 ? '+' : '' }}{{ number_format($item->brecha_movimientos,0,',','.') }}<small class="d-block text-muted">No es stock</small></td>
              <td class="text-right {{ $item->transito > 0 ? 'text-warning' : 'text-success' }}">{{ number_format($item->recibido,0,',','.') }}@if($item->transito > 0)<small class="d-block">{{ number_format($item->transito,0,',','.') }} tránsito</small>@endif</td>
              <td class="text-right">{{ number_format($item->traslado,0,',','.') }}</td>
              <td class="text-right font-weight-bold {{ $item->ventas_netas < 0 ? 'text-danger' : 'text-primary' }}">{{ number_format($item->ventas_netas,0,',','.') }}<small class="d-block text-muted">{{ number_format($item->vendidas,0,',','.') }} V · {{ number_format($item->devoluciones,0,',','.') }} D</small></td>
              <td class="text-right font-weight-bold">Gs {{ number_format($item->venta_neta,0,',','.') }}</td>
              <td>
                @php
                  $alertaMayorista = $item->status !== 'CON DOCUMENTOS' && $item->status !== 'SOLO PLAN';
                @endphp
                <span class="badge {{ $alertaMayorista ? 'badge-warning' : ($item->status === 'SOLO PLAN' ? 'badge-secondary' : 'badge-success') }}">{{ $item->status }}</span>
                @if($item->id_ot !== null && $item->comprobantes > 0)
                  <small class="d-block text-muted mt-1">Ventas vinculadas por código base único</small>
                @endif
                @if($item->reserva_foto > 0)
                  <small class="d-block text-muted mt-1">{{ $item->reserva_foto }} foto estimada(s)</small>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="11" class="text-center text-muted p-4">No se encontraron operaciones con estos criterios.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($ordenes->hasPages())<div class="card-footer bg-white">{{ $ordenes->links() }}</div>@endif
  </div>

  <div class="row">
    <div class="col-lg-6 mb-3"><div class="card may-card h-100">
      <div class="card-header bg-white"><strong><i class="fas fa-trophy mr-1 text-primary"></i>Artículos más vendidos · Comercial Matriz</strong></div>
      <div class="table-responsive">
        <table class="table table-sm may-table mb-0">
          <thead><tr><th>Código base / artículo</th><th class="text-right">Prendas netas</th><th class="text-right">Venta neta</th></tr></thead>
          <tbody>
            @forelse($topProductos as $producto)
              <tr><td><strong>{{ $producto->codigo_base ?: 'Sin código' }}</strong><small class="d-block text-muted">{{ $producto->descripcion ?: '' }}</small></td><td class="text-right">{{ number_format($producto->prendas_netas,0,',','.') }}</td><td class="text-right font-weight-bold">Gs {{ number_format($producto->venta_neta,0,',','.') }}</td></tr>
            @empty
              <tr><td colspan="3" class="text-muted text-center p-3">No hay productos para listar.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div></div>
    <div class="col-lg-6 mb-3"><div class="card may-card h-100">
      <div class="card-header bg-white"><strong><i class="fas fa-question-circle mr-1 text-primary"></i>Cómo interpretar las diferencias</strong></div>
      <div class="card-body">
        <p class="may-note-title">Venta sin plan/remisión</p>
        <p class="small text-muted">Hay comprobantes de COMERCIAL MATRIZ asociados al artículo, pero no una remisión o un plan mayorista de esa OT entre los datos comparados. Puede tratarse de stock previo; no se declara un faltante.</p>
        <p class="may-note-title">Recepción pendiente</p>
        <p class="small text-muted">Existen remisiones emitidas sin fecha de recepción. No son ventas ni equivalen a stock disponible.</p>
        <p class="may-note-title">Sin OT segura</p>
        <p class="small text-muted">El código de venta o remisión no corresponde inequívocamente a una sola OT. Se mantiene separado hasta verificar el vínculo.</p>
        <p class="may-note-title">Fecha del plan distinta al período de ventas</p>
        <p class="small text-muted mb-0">El plan se toma histórico, mientras las ventas y remisiones se filtran por fecha. La columna Plan − Movimientos es solo una diferencia documental; no mide faltante ni stock. Una redistribución podría volver a mover prendas ya despachadas.</p>
      </div>
    </div></div>
  </div>

  <div class="may-section-heading">
    <h3><i class="fas fa-receipt mr-2 text-primary"></i>5. Comprobantes reales · Comercial Matriz</h3>
    <small>Listado de ventas, notas de crédito y devoluciones de la fuente de Ventas.</small>
  </div>
  <div class="card may-card mb-3">
    <div class="card-header bg-white">
      <strong>Auditoría comercial</strong>
      <small class="d-block text-muted">Un importe negativo se conserva como devolución; PLISTA, DTO y PVTA ya son totales de la línea importada.</small>
    </div>
    <div class="table-responsive">
      <table class="table table-sm may-table mb-0">
        <thead><tr>
          <th>Fecha</th><th>Comprobante</th><th>Tipo</th><th>Cliente</th><th>Código / Artículo</th>
          <th class="text-right">Cantidad</th><th class="text-right">PLISTA</th>
          <th class="text-right">DTO</th><th class="text-right">PVTA</th>
        </tr></thead>
        <tbody>
          @forelse($ventasDetalle ?? [] as $v)
            <tr class="{{ $v->cantidad < 0 ? 'table-danger' : '' }}">
              <td>{{ date('d/m/Y',strtotime($v->fecha)) }}</td>
              <td><strong>{{ $v->comprobante }}</strong></td>
              <td>{{ $v->tipo_comprobante ?: '—' }}</td>
              <td>{{ $v->cliente ?: 'Sin cliente' }}</td>
              <td>{{ $v->codigo }}<small class="d-block text-muted">{{ $v->descripcion }}</small></td>
              <td class="text-right {{ $v->cantidad < 0 ? 'text-danger' : '' }}">{{ number_format($v->cantidad,0,',','.') }}</td>
              <td class="text-right">Gs {{ number_format($v->p_lista,0,',','.') }}</td>
              <td class="text-right">Gs {{ number_format($v->descuento,0,',','.') }}</td>
              <td class="text-right font-weight-bold {{ $v->p_venta < 0 ? 'text-danger' : 'text-success' }}">Gs {{ number_format($v->p_venta,0,',','.') }}</td>
            </tr>
          @empty
            <tr><td colspan="9" class="text-muted text-center p-4">No hay comprobantes comerciales en el período consultado.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($ventasDetalle && $ventasDetalle->hasPages())
      <div class="card-footer bg-white">{{ $ventasDetalle->links() }}</div>
    @endif
  </div>

  <div class="may-section-heading">
    <h3><i class="fas fa-clipboard-list mr-2 text-primary"></i>6. Documentos de logística · Auditoría</h3>
    <small>Remisiones del período, con origen, destino y comprobante.</small>
  </div>
  <div class="card may-card mb-4">
    <div class="table-responsive">
      <table class="table table-sm table-striped may-table mb-0">
        <thead><tr>
          <th>Fecha</th><th>OT</th><th>Documento</th><th>Origen → Destino</th><th>Canal</th><th class="text-right">Prendas</th><th>Recepción</th>
        </tr></thead>
        <tbody>
          @forelse($remisiones ?? [] as $mov)
            <tr>
              <td>{{ date('d/m/Y',strtotime($mov->fecha_remision)) }}</td>
              <td>{{ $mov->nro_ot ?: 'Sin OT' }}</td>
              <td><strong>{{ $mov->serie }}-{{ $mov->numero_remision }}</strong><small class="d-block text-muted">{{ $mov->codigo }}</small></td>
              <td>{{ $mov->sucursal_salida ?: '—' }} <i class="fas fa-long-arrow-alt-right mx-1 text-muted"></i> {{ $mov->sucursal_logistica ?: $mov->sucursal_destino ?: '—' }}</td>
              <td>{{ $mov->canal }}</td>
              <td class="text-right font-weight-bold">{{ number_format($mov->cantidad,0,',','.') }}</td>
              <td>
                @if($mov->fecha_confirmacion)
                  <span class="badge badge-success">{{ date('d/m/Y',strtotime($mov->fecha_confirmacion)) }}</span>
                @else
                  <span class="badge badge-warning">No confirmada</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted p-4">Sin remisiones en el período.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($remisiones && $remisiones->hasPages())<div class="card-footer bg-white">{{ $remisiones->links() }}</div>@endif
  </div>

</div>
</section>
@endsection

@push('page_css')
<style>
.may-label{font-size:.79rem;font-weight:700;color:#475569;margin-bottom:5px;display:block}
.may-regla{border:1px solid #b7dbca;border-left:4px solid #159767;padding:14px 17px;background:#f4fbf7;border-radius:9px}
.may-pill{border-radius:50px;background:#d9f4e6;color:#12784e;font-weight:700;font-size:.76rem;padding:7px 12px}
.may-section-heading{display:flex;align-items:baseline;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:19px 0 11px}
.may-section-heading h3{margin:0;font-weight:750;font-size:1.1rem;color:#20364b}
.may-section-heading small{color:#718096}
.may-kpi{border:1px solid #dee8f2;border-top:3px solid #94a3b8;border-radius:10px;background:#fff;padding:14px 16px;min-height:116px;height:100%;box-shadow:0 3px 10px rgba(27,46,70,.035)}
.may-kpi-blue{border-top-color:#3c91c1}.may-kpi-green{border-top-color:#17a672}.may-kpi-amber{border-top-color:#edae47}.may-kpi-purple{border-top-color:#8d74c9}
.may-kpi small{display:block;color:#64748b;font-size:.76rem;font-weight:750;margin-bottom:7px}
.may-kpi strong{display:block;color:#21384d;font-size:1.7rem;font-weight:800;line-height:1.18}
.may-kpi strong.may-money{font-size:1.35rem}
.may-kpi span{display:block;color:#6a788a;font-size:.77rem;margin-top:7px}
.may-card{border:1px solid #e1e8f1;border-radius:10px;box-shadow:0 4px 16px rgba(30,54,82,.035);overflow:hidden}
.may-card .card-header{padding:12px 16px}
.may-table{font-size:.8rem}
.may-table th{color:#526274;font-weight:750;font-size:.76rem;white-space:nowrap;padding:10px}
.may-table td{vertical-align:middle;padding:9px 10px}
.may-cross{min-width:1080px}
.may-row-review{background:#fffdf8}
.may-desc{display:block;min-width:135px;max-width:260px}
.may-example{padding:13px 16px;border-radius:9px;border:1px solid #dbe6f2;background:#f4f8fd;color:#34455b}
.may-note-title{font-weight:700;font-size:.84rem;margin-bottom:4px;color:#34495e}
.may-bar-row{display:grid;grid-template-columns:42px 1fr 135px;gap:8px;align-items:center;margin:0 0 9px}
.may-bar-row strong{text-align:right;color:#34495e;font-size:.75rem}
.may-bar-date{font-size:.77rem;color:#64748b}
.may-bar-track{height:11px;overflow:hidden;border-radius:30px;background:#e7eff6}
.may-bar-track span{height:100%;display:block;border-radius:30px;background:#4b96c8}
@media(max-width:767px){.may-kpi strong{font-size:1.38rem}.may-bar-row{grid-template-columns:38px 1fr 92px}.may-section-heading h3{font-size:.99rem}}
</style>
@endpush
