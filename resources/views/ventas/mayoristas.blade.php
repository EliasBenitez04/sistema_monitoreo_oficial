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
        <div><label for="buscarMay" class="may-label">Búsqueda general</label><input id="buscarMay" class="form-control" name="buscar" value="{{ $buscar }}" placeholder="Factura / cliente"></div>
        <div><label for="mayFactura" class="may-label">Número factura</label><input id="mayFactura" class="form-control" name="factura" value="{{ $filtroFactura }}" placeholder="Factura"></div>
        <div><label for="mayCliente" class="may-label">Cliente mayorista</label><input id="mayCliente" class="form-control" name="cliente" value="{{ $filtroCliente }}" placeholder="Cliente / CI"></div>
        <div><label for="mayCodigo" class="may-label">Código completo</label><input id="mayCodigo" class="form-control" name="codigo" value="{{ $filtroCodigo }}" placeholder="Código / talle"></div>
        <div><label for="mayRemision" class="may-label">Remisión candidata</label><input id="mayRemision" class="form-control" name="remision" value="{{ $filtroRemision }}" placeholder="Nº remisión"></div>
        <div><label for="mayOt" class="may-label">OT candidata</label><input id="mayOt" class="form-control" name="ot" value="{{ $filtroOt }}" placeholder="Nº OT"></div>
        <div><label for="mayEstado" class="may-label">Conciliación</label><select class="form-control" id="mayEstado" name="estado">
          <option value="">Todos los estados</option>
          @foreach(['CONFIRMADA DOCUMENTALMENTE','PARCIAL','SIN VALIDAR','NOTA DE CRÉDITO'] as $estadoOpcion)
            <option value="{{ $estadoOpcion }}" {{ $filtroEstado===$estadoOpcion ? 'selected' : '' }}>{{ $estadoOpcion }}</option>
          @endforeach
        </select></div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i>Consultar</button>
        <a class="btn btn-outline-secondary" href="{{ route('ventas.mayoristas.index',['mes'=>$mesPrevio]) }}"><i class="fas fa-chevron-left mr-1"></i>Mes anterior</a>
        <a class="btn btn-outline-secondary" href="{{ route('ventas.mayoristas.index',['mes'=>$mesProximo]) }}">Mes siguiente<i class="fas fa-chevron-right ml-1"></i></a>
        <a class="btn btn-outline-secondary" href="{{ route('ventas.mayoristas.index',['mes'=>$mes]) }}">Limpiar filtros</a>
        <a class="btn btn-outline-success ml-auto" href="{{ route('ventas.mayoristas.index',array_merge(request()->query(),['exportar'=>'csv'])) }}"><i class="fas fa-file-csv mr-1"></i>Resumen CSV</a>
        <a class="btn btn-outline-success" href="{{ route('ventas.mayoristas.index',array_merge(request()->query(),['exportar'=>'detalle'])) }}"><i class="fas fa-list-alt mr-1"></i>Detalle factura–remisión CSV</a>
      </form>
    </div>
  </div>

  <div class="card may-card mb-3" id="may-avance">
    <div class="card-header bg-white">
      <strong><i class="fas fa-tasks text-primary mr-2"></i>Avance de auditoría · {{ $mes }}</strong>
      <small class="d-block text-muted">Diferenciamos mercadería documentada, facturación registrada y vínculos realmente verificados.</small>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-lg-4 col-md-6 mb-3">
          <div class="may-metric h-100">
            <small>Facturas mayoristas para auditar</small>
            <strong>{{ $avanceGerencial->facturas_venta }}</strong>
            <span>{{ $avanceGerencial->notas_credito }} notas de crédito analizadas por separado</span>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
          <div class="may-metric h-100">
            <small>Conciliación documental de prendas</small>
            <strong>{{ number_format($resumen->vinculadas,0,',','.') }} / {{ number_format($resumen->brutas,0,',','.') }}</strong>
            <div class="progress mt-2" style="height:8px">
              <div class="progress-bar bg-success" role="progressbar"
                style="width:{{ $avanceGerencial->avance_porcentaje===null ? 0 : $avanceGerencial->avance_porcentaje }}%"
                aria-valuenow="{{ $avanceGerencial->avance_porcentaje===null ? 0 : $avanceGerencial->avance_porcentaje }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <span>{{ $avanceGerencial->avance_porcentaje===null ? 'Sin facturas positivas' : number_format($avanceGerencial->avance_porcentaje,1,',','.').'%' }} confirmado · {{ $avanceGerencial->facturas_confirmadas }} completas y {{ $avanceGerencial->facturas_parciales }} parciales</span>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
          <div class="may-metric h-100">
            <small>Impacto de las dos facturas principales</small>
            <strong>{{ number_format($avanceGerencial->top_dos_prendas,0,',','.') }} prendas</strong>
            <span>{{ number_format($avanceGerencial->top_dos_porcentaje,1,',','.') }}% de todas las unidades facturadas brutas del mes</span>
          </div>
        </div>
      </div>
      <div class="may-section mt-0">
        <div>
          <h3>Prioridades para demostrar avances</h3>
          <p>Empezá por las facturas de mayor volumen; los documentos sugeridos no se considerarán vinculados hasta validarlos.</p>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-sm may-table mb-0">
          <thead><tr>
            <th>Factura</th><th>Cliente</th><th class="text-right">Prendas</th>
            <th class="text-right">Peso en el mes</th>
            <th class="text-right">Documentos recientes</th><th class="text-right">Cobertura reciente</th>
            <th>Estado</th><th></th>
          </tr></thead>
          <tbody>
            @forelse($facturasPrioridad as $prioridad)
              <tr>
                <td><strong>{{ $prioridad->comprobante }}</strong><small class="d-block text-muted">{{ date('d/m/Y',strtotime($prioridad->fecha)) }}</small></td>
                <td>{{ $prioridad->cliente ?: 'Sin cliente' }}</td>
                <td class="text-right font-weight-bold">{{ number_format($prioridad->brutas,0,',','.') }}</td>
                <td class="text-right">{{ $resumen->brutas>0 ? number_format($prioridad->brutas*100/$resumen->brutas,1,',','.').'%' : '—' }}</td>
                <td class="text-right">{{ number_format($prioridad->documentos_recientes,0,',','.') }}</td>
                <td class="text-right">
                  {{ number_format($prioridad->cobertura_reciente,0,',','.') }}
                  <small class="d-block text-muted">de {{ number_format($prioridad->pendientes,0,',','.') }} sin vincular</small>
                </td>
                <td>{{ $prioridad->estado }}<small class="d-block text-muted">{{ $prioridad->situacion_busqueda }}</small></td>
                <td><a class="btn btn-sm btn-primary" href="{{ route('ventas.mayoristas.factura',['clave'=>$prioridad->clave,'mes'=>$mes]) }}">Auditar</a></td>
              </tr>
            @empty
              <tr><td colspan="8" class="text-center text-muted p-3">No hay facturas positivas para priorizar.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="may-example mt-3">
        <strong>Informe breve para presentar:</strong>
        En {{ $mes }} se documentaron {{ number_format($recibidas->prendas,0,',','.') }} prendas recibidas en Comercial Matriz
        y {{ number_format($resumen->brutas,0,',','.') }} unidades facturadas brutas en
        {{ $avanceGerencial->facturas_venta }} facturas.
        Se registraron {{ number_format($resumen->devoluciones,0,',','.') }} unidades en notas de crédito
        y {{ number_format($resumen->vinculadas,0,',','.') }} prendas tienen relación factura–remisión validada.
        La auditoría se concentra primero en las dos facturas de mayor impacto
        ({{ number_format($avanceGerencial->top_dos_porcentaje,1,',','.') }}% del volumen bruto).
        <strong>La conciliación física y el saldo mensual aún requieren evidencia.</strong>
      </div>
    </div>
  </div>

  <div class="card may-card mb-3">
    <div class="card-header bg-white"><strong><i class="fas fa-calendar-alt text-primary mr-2"></i>Panorama por mes · últimos seis meses hasta {{ $mes }}</strong>
      <small class="d-block text-muted">Elegí un mes para examinar facturas y remisiones. Las cantidades son hechos del período y no deben sumarse entre categorías.</small></div>
    <div class="table-responsive">
      <table class="table table-sm may-table mb-0">
        <thead><tr><th>Mes</th><th class="text-right">Separadas con evidencia</th><th class="text-right">Remitidas</th>
        <th class="text-right">Recibidas</th><th class="text-right">Facturadas netas</th><th>Saldo documental al cierre</th><th></th></tr></thead>
        <tbody>
        @foreach($resumenMeses as $filaMes)
          <tr class="{{ $filaMes->es_actual ? 'table-primary' : '' }}">
            <td><strong>{{ $filaMes->mes }}</strong></td>
            <td class="text-right">{{ number_format($filaMes->separadas,0,',','.') }}</td>
            <td class="text-right">{{ number_format($filaMes->emitidas,0,',','.') }}</td>
            <td class="text-right">{{ number_format($filaMes->recibidas,0,',','.') }}</td>
            <td class="text-right">{{ number_format($filaMes->facturadas,0,',','.') }}</td>
            <td>{{ $filaMes->es_actual ? ($filaMes->cierre===null ? 'NO DETERMINADO' : number_format($filaMes->cierre,0,',','.').' (estimado)') : 'Consultar mes' }}</td>
            <td><a href="{{ route('ventas.mayoristas.index',['mes'=>$filaMes->mes]) }}" class="btn btn-sm btn-outline-primary">Ver detalle</a></td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <p class="small text-muted mb-2">
    Los indicadores de arriba corresponden a <strong>todo el mes seleccionado</strong>.
    Los filtros adicionales actúan sobre la lista de facturas y sus exportaciones.
    Buscar por OT o remisión muestra <strong>facturas candidatas por código</strong>, no vínculos comprobados.
  </p>
  <div class="may-section">
    <div><h3>1. Resumen documental de {{ ucfirst(\Carbon\Carbon::parse($mes.'-01')->locale('es')->translatedFormat('F Y')) }}</h3>
      <p>Las fechas corresponden a hechos distintos: separación validada, emisión, recepción y factura. No deben sumarse como si fueran prendas diferentes.</p></div>
  </div>
  <div class="row">
    <div class="col-xl-3 col-md-6 mb-3"><div class="may-kpi may-violet">
      <small>Separadas con fecha validada</small><strong>{{ number_format($separaciones->prendas,0,',','.') }}</strong>
      <span>{{ $separaciones->registros ? $separaciones->registros.' registros con evidencia' : 'No significa que el plan esté vacío: faltan fechas y evidencia de separación' }}</span>
    </div></div>
    <div class="col-xl-3 col-md-6 mb-3"><div class="may-kpi may-blue">
      <small>Remitidas a Comercial Matriz</small><strong>{{ number_format($emitidas->prendas,0,',','.') }}</strong>
      <span><strong>{{ number_format($emitidas->documentos,0,',','.') }} remisiones distintas</strong> · {{ number_format($emitidas->lineas,0,',','.') }} líneas de artículo</span>
    </div></div>
    <div class="col-xl-3 col-md-6 mb-3"><div class="may-kpi may-green">
      <small>Entradas con recepción confirmada</small><strong>{{ number_format($recibidas->prendas,0,',','.') }}</strong>
      <span><strong>{{ number_format($recibidas->documentos,0,',','.') }} remisiones distintas recibidas</strong> · Central {{ number_format($recibidas->central,0,',','.') }} · Locales {{ number_format($recibidas->prendas-$recibidas->central,0,',','.') }}</span>
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
    <p>Separé las <strong>unidades realmente vinculadas</strong> de las <strong>coincidencias posibles</strong> para que puedas priorizar qué facturas auditar.</p></div></div>
  <div class="alert alert-info small mb-3">
    <strong><i class="fas fa-search mr-1"></i>Interpretación de las nuevas columnas:</strong>
    «Remisiones recientes» cuenta <strong>documentos únicos</strong>, no líneas de artículos:
    mismo documento, código completo y fechas de emisión y recepción no posteriores a la factura
    y emitidos como máximo 30 días antes.
    La «Cobertura reciente» indica cuántas prendas aún no vinculadas podrían estar respaldadas
    con esos documentos. «Histórico» se refiere a remisiones de más de 30 días.
    <strong>No son confirmaciones ni stock actual</strong>: una misma remisión puede ser candidata
    para varias facturas, por lo que estas cantidades no se deben sumar entre comprobantes.
    Para confirmar el origen, abrí <strong>Revisar</strong> y asigná cada documento con evidencia.
  </div>
  <div class="card may-card mb-3">
    <div class="table-responsive">
      <table class="table table-sm may-table table-hover mb-0">
        <thead><tr>
          <th>Fecha</th><th>Comprobante</th><th>Cliente mayorista</th>
          <th class="text-right" title="Códigos completos distintos del comprobante; no es la cantidad de prendas">Códigos</th><th class="text-right">Prendas brutas</th>
          <th class="text-right">Devoluciones</th><th class="text-right">Neto</th>
          <th class="text-right">Importe Gs</th><th class="text-right">Vinculadas</th>
          <th class="text-right">Sin vínculo</th>
          <th class="text-right">Remisiones recientes</th>
          <th class="text-right">Cobertura estimada</th>
          <th>Estado documental</th><th></th>
        </tr></thead>
        <tbody>
        @forelse($facturasPaginadas as $factura)
          <tr>
            <td>{{ date('d/m/Y',strtotime($factura->fecha)) }}</td>
            <td><strong>{{ $factura->comprobante }}</strong>
              @if($factura->tipo_comprobante && stripos($factura->comprobante,$factura->tipo_comprobante) === false)
                <small class="d-block text-muted">{{ $factura->tipo_comprobante }}</small>
              @endif
            </td>
            <td>{{ $factura->cliente ?: 'Cliente sin identificar' }}</td>
            <td class="text-right">{{ $factura->articulos }}</td>
            <td class="text-right font-weight-bold">{{ number_format($factura->brutas,0,',','.') }}</td>
            <td class="text-right">{{ number_format($factura->devoluciones,0,',','.') }}</td>
            <td class="text-right">{{ number_format($factura->netas,0,',','.') }}</td>
            <td class="text-right">Gs {{ number_format($factura->pvta,0,',','.') }}</td>
            <td class="text-right text-success">{{ number_format($factura->vinculadas,0,',','.') }}</td>
            <td class="text-right {{ $factura->pendientes > 0 ? 'text-warning font-weight-bold' : '' }}">{{ number_format($factura->pendientes,0,',','.') }}</td>
            <td class="text-right">
              @if($factura->brutas > 0)
                <strong>{{ number_format($factura->documentos_recientes,0,',','.') }}</strong>
                <small class="d-block text-muted">últimos 30 días</small>
                <small class="d-block text-muted">{{ number_format($factura->documentos_historicos,0,',','.') }} históricos</small>
              @else
                —
              @endif
            </td>
            <td class="text-right">
              @if($factura->brutas > 0)
                <strong class="{{ $factura->cobertura_reciente > 0 ? 'text-primary' : 'text-muted' }}">{{ number_format($factura->cobertura_reciente,0,',','.') }}</strong>
                <small class="d-block text-muted">reciente, de {{ number_format($factura->pendientes,0,',','.') }} sin vínculo</small>
                <small class="d-block text-muted">+ {{ number_format($factura->cobertura_historica,0,',','.') }} históricas</small>
                <small class="d-block {{ $factura->pendiente_sin_candidata>0 ? 'text-warning':'text-muted' }}">{{ number_format($factura->pendiente_sin_candidata,0,',','.') }} sin cobertura candidata</small>
              @else
                —
              @endif
            </td>
            <td>
              <span class="badge {{ $factura->estado==='CONFIRMADA DOCUMENTALMENTE' ? 'badge-success' : ($factura->estado==='PARCIAL' ? 'badge-info' : 'badge-warning') }}">{{ $factura->estado }}</span>
              @if($factura->brutas > 0 && $factura->pendientes > 0)
                <small class="d-block mt-1 {{ $factura->cobertura_candidata>0 ? 'text-primary':'text-muted' }}">{{ $factura->situacion_busqueda }}</small>
              @endif
            </td>
            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('ventas.mayoristas.factura',['clave'=>$factura->clave,'mes'=>$mes]) }}">Revisar <i class="fas fa-arrow-right ml-1"></i></a></td>
          </tr>
        @empty
          <tr><td colspan="14" class="text-center text-muted p-4">No hay facturas de COMERCIAL MATRIZ para este mes y filtro.</td></tr>
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
      <div class="card-header bg-white"><strong>Artículos que requieren revisión documental</strong>
        <small class="d-block text-muted">{{ number_format($cantidadDiferencias,0,',','.') }} códigos con entradas del mes distintas de ventas netas; no es faltante físico</small>
        <a href="{{ route('ventas.mayoristas.index',array_merge(request()->query(),['ver_saldos'=>$verTodosSaldos?'prioritarios':'todos'])) }}" class="btn btn-sm btn-outline-primary mt-2">
          {{ $verTodosSaldos ? 'Ver solo prioritarios' : 'Ver todos los códigos ('.number_format($saldos->count(),0,',','.').')' }}
        </a></div>
      <div class="table-responsive may-scroll">
        <table class="table table-sm may-table mb-0">
          <thead><tr><th>Código completo</th><th class="text-right">Apertura</th><th class="text-right">Recibido</th><th class="text-right">Facturado neto</th><th class="text-right">Diferencia del mes</th></tr></thead>
          <tbody>
            @forelse($saldosVisibles as $s)
            <tr><td><strong>{{ $s->codigo }}</strong></td>
              <td class="text-right">{{ $s->inicial===null ? 'SIN DATO' : number_format($s->inicial,0,',','.') }}</td>
              <td class="text-right">{{ number_format($s->entrada,0,',','.') }}</td>
              <td class="text-right">{{ number_format($s->venta,0,',','.') }}</td>
              <td class="text-right {{ $s->entrada != $s->venta ? 'text-warning font-weight-bold':'text-muted' }}">
                {{ ($s->entrada-$s->venta)>0 ? '+' : '' }}{{ number_format($s->entrada-$s->venta,0,',','.') }}
                <small class="d-block text-muted">No es stock</small>
              </td></tr>
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

  <details class="card may-card mb-3">
    <summary class="card-header bg-white" style="cursor:pointer">
      <strong><i class="fas fa-history text-primary mr-2"></i>Mostrar remisiones históricas sin vínculo</strong>
      <small class="d-block text-muted">Hasta {{ $arrastres->count() }} líneas documentales de meses anteriores; no son prendas comprobadas en stock</small>
    </summary>
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
    <div class="card-footer bg-white small text-muted">Esta lista es una muestra de líneas de remisión antiguas. «Sin vincular» no significa necesariamente que sigan físicamente disponibles: pueden existir facturas de períodos anteriores aún no conciliadas.</div>
  </details>

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
  <div class="alert alert-light border small">
    <strong>Plan existente por validar:</strong> se muestran {{ $planes->count() }} asignaciones históricas,
    con {{ number_format($planes->sum('pendiente'),0,',','.') }} prendas pendientes de registrar dentro de esta muestra.
    <strong>No significa que correspondan a octubre.</strong> Se necesita la fecha real para incorporarlas al mes.
  </div>
  <details class="card may-card mb-3" {{ $planBuscar!=='' ? 'open' : '' }}>
    <summary class="card-header bg-white" style="cursor:pointer"><strong><i class="fas fa-boxes mr-2 text-primary"></i>Ver asignaciones y registrar separaciones con evidencia</strong>
      <small class="d-block text-muted">Registro documental opcional; no se presupone la fecha de separación</small>
    </summary>
    <div class="card-body">
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
                    <input type="date" name="fecha_separacion" required title="Ingresá la fecha real documentada, no se presupone el primer día del mes">
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
    </div>
  </details>

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
.may-example{border:1px solid #dbe5ef;background:#f6f9fc;color:#394f66;padding:12px 15px;border-radius:8px;font-size:.83rem}
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
