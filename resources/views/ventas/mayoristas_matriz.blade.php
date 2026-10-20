@extends('layouts.app')
@section('content')
<section class="content">
  <div class="container-fluid py-3 mayor-matriz">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
      <div>
        <h2 class="mb-1"><i class="fas fa-file-invoice-dollar text-primary mr-2"></i>Ventas mayoristas · COMERCIAL MATRIZ</h2>
        <p class="text-muted mb-0">Facturas reales, detalle de artículos y conciliación de remisiones de Logística / Modelo Muestra.</p>
      </div>
      <div class="badge badge-primary p-2 mt-2">Sucursal: 25 · COMERCIAL MATRIZ</div>
    </div>
    @if(!empty($sinFuentes))
      <div class="alert alert-warning">No se encontraron las tablas ventas y remisiones. Verificá los importadores y las migraciones antes de continuar.</div>
    @else
      @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
      @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
      <form method="GET" action="{{ route('ventas.mayoristas.index') }}" class="card may2-card p-3 mb-3">
        <div class="d-flex flex-wrap align-items-end" style="gap:12px">
          <div><label class="small font-weight-bold">Desde</label><input type="date" name="desde" class="form-control" value="{{ $desde }}" required></div>
          <div><label class="small font-weight-bold">Hasta</label><input type="date" name="hasta" class="form-control" value="{{ $hasta }}" required></div>
          <div><button class="btn btn-primary"><i class="fas fa-search mr-1"></i>Consultar ventas</button></div>
          <div><a class="btn btn-outline-success" href="{{ route('ventas.mayoristas.index',['desde'=>$desde,'hasta'=>$hasta,'exportar'=>'ventas']) }}"><i class="fas fa-file-csv mr-1"></i>Exportar todos los registros</a></div>
        </div>
        <small class="text-muted mt-2">Sólo datos importados de COMERCIAL MATRIZ. Para contrastar con la planilla original, usá exactamente las mismas fechas.</small>
      </form>
      <div class="row">
        <div class="col-lg-3 col-sm-6 mb-3"><div class="may2-kpi"><small>Unidades netas vendidas</small><strong>{{ number_format($resumen->unidades_netas,0,',','.') }}</strong><span>{{ number_format($resumen->brutas,0,',','.') }} brutas · {{ number_format($resumen->notas_credito,0,',','.') }} notas de crédito</span></div></div>
        <div class="col-lg-3 col-sm-6 mb-3"><div class="may2-kpi"><small>Importe de lista</small><strong>Gs {{ number_format($resumen->importe_lista,0,',','.') }}</strong><span>Descuentos: Gs {{ number_format($resumen->descuentos,0,',','.') }}</span></div></div>
        <div class="col-lg-3 col-sm-6 mb-3"><div class="may2-kpi"><small>Venta neta registrada</small><strong>Gs {{ number_format($resumen->importe_neto,0,',','.') }}</strong><span>{{ number_format($resumen->lineas,0,',','.') }} líneas de venta importadas</span></div></div>
        <div class="col-lg-3 col-sm-6 mb-3"><div class="may2-kpi"><small>Asignación documental</small><strong>{{ number_format($resumen->asignadas,0,',','.') }} / {{ number_format($resumen->brutas,0,',','.') }}</strong><span>{{ number_format($resumen->pendientes,0,',','.') }} unidades brutas sin remisión asignada</span></div></div>
      </div>
      <div class="alert alert-info small">
        <strong>Conciliación:</strong> Las unidades netas provienen de la exportación de ventas; las asignadas son vínculos revisados entre factura y línea de remisión. Una remisión a MODELO MUESTRA documenta origen/canal, pero no certifica stock físico recibido en MATRIZ. La suma neta vendida no se usa para atribuir notas de crédito a remisiones sin evidencia.
      </div>
      <div class="card may2-card mb-3">
        <div class="card-header bg-white"><strong><i class="fas fa-receipt mr-1 text-primary"></i>1. Facturas / comprobantes de COMERCIAL MATRIZ</strong><small class="d-block text-muted">Seleccioná una factura para contrastar sus talles contra remisiones del mismo número y asignar cantidades.</small></div>
        <div class="table-responsive">
          <table class="table table-sm table-hover may2-table mb-0">
            <thead><tr><th>Fecha</th><th>Comprobante</th><th>Cliente</th><th>Tipo</th><th class="text-right">Unidades brutas</th><th class="text-right">Neto unidades</th><th class="text-right">Gs netos</th><th class="text-right">Asignadas</th><th class="text-right">Sin asignar</th><th>Estado</th><th></th></tr></thead>
            <tbody>
              @forelse($facturas as $f)
                <tr>
                  <td>{{ date('d/m/Y',strtotime($f->fecha)) }}</td>
                  <td class="font-weight-bold">{{ $f->comprobante }}</td>
                  <td>{{ $f->cliente ?: $f->cli_cod }}</td>
                  <td>{{ $f->tipo_comprobante }}</td>
                  <td class="text-right">{{ number_format($f->brutas,0,',','.') }}</td>
                  <td class="text-right">{{ number_format($f->netas,0,',','.') }}</td>
                  <td class="text-right">Gs {{ number_format($f->pvta,0,',','.') }}</td>
                  <td class="text-right text-success">{{ number_format($f->asignadas,0,',','.') }}</td>
                  <td class="text-right">{{ number_format($f->por_asignar,0,',','.') }}</td>
                  <td><span class="badge badge-{{ $f->estado_cruce==='ASIGNADA'?'success':($f->estado_cruce==='PARCIAL'?'warning':'secondary') }}">{{ $f->estado_cruce }}</span></td>
                  <td><a class="btn btn-sm btn-primary" href="{{ route('ventas.mayoristas.factura',['clave'=>$f->clave,'mes'=>substr($f->fecha,0,7),'desde'=>$desde,'hasta'=>$hasta]) }}">Cruzar</a></td>
                </tr>
              @empty
                <tr><td colspan="11" class="text-center text-muted py-3">No hay comprobantes de COMERCIAL MATRIZ en estas fechas.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <details class="card may2-card mb-3">
        <summary class="card-header bg-white may2-summary"><strong><i class="fas fa-list mr-1 text-primary"></i>2. Todas las líneas vendidas (detalle de cada talle)</strong><small class="d-block text-muted">Fecha, factura, cliente, vendedor, código completo, descripción, unidades y montos. Se muestran 100 líneas por página; exportación incluye todos los registros.</small></summary>
        <div class="table-responsive" style="max-height:570px;overflow:auto">
          <table class="table table-sm table-striped may2-table mb-0">
            <thead><tr><th>Fecha</th><th>Factura</th><th>Tipo</th><th>Cliente / código</th><th>Vendedor</th><th>Código completo</th><th>Descripción</th><th class="text-right">Cant.</th><th class="text-right">Lista Gs</th><th class="text-right">Descuento Gs</th><th class="text-right">Venta Gs</th></tr></thead>
            <tbody>
              @foreach($ventasDetalle as $v)
              <tr>
                <td>{{ date('d/m/Y',strtotime($v->fecha)) }}</td><td>{{ $v->comprobante }}</td><td>{{ $v->tipo_comprobante }}</td>
                <td>{{ $v->cliente }}<small class="d-block text-muted">{{ $v->cli_cod }}</small></td>
                <td>{{ $v->vendedor }}</td><td class="font-weight-bold">{{ $v->codigo }}</td><td>{{ $v->descripcion }}</td>
                <td class="text-right">{{ number_format($v->cantidad,0,',','.') }}</td>
                <td class="text-right">{{ number_format($v->p_lista,2,',','.') }}</td>
                <td class="text-right">{{ number_format($v->descuento,2,',','.') }}</td>
                <td class="text-right">{{ number_format($v->p_venta,2,',','.') }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="card-footer bg-white">{{ $ventasDetalle->links() }}</div>
      </details>
      <details class="card may2-card mb-4">
        <summary class="card-header bg-white may2-summary"><strong><i class="fas fa-barcode mr-1 text-primary"></i>3. Totales vendidos por código y talle</strong><small class="d-block text-muted">Consolidado de unidades y montos de COMERCIAL MATRIZ. La remisión se asigna desde cada factura, nunca por coincidencia de totales globales.</small></summary>
        <div class="table-responsive" style="max-height:480px;overflow:auto">
          <table class="table table-sm may2-table mb-0"><thead><tr><th>Código completo</th><th>Artículo</th><th class="text-right">Brutas</th><th class="text-right">NC / devoluciones</th><th class="text-right">Netas</th><th class="text-right">Importe neto Gs</th></tr></thead>
            <tbody>@foreach($porCodigo as $c)<tr>
              <td class="font-weight-bold">{{ $c->codigo }}</td><td>{{ $c->descripcion }}</td>
              <td class="text-right">{{ number_format($c->ventas_brutas,0,',','.') }}</td><td class="text-right">{{ number_format($c->devoluciones,0,',','.') }}</td>
              <td class="text-right">{{ number_format($c->ventas_netas,0,',','.') }}</td><td class="text-right">{{ number_format($c->total_neto,2,',','.') }}</td>
            </tr>@endforeach</tbody>
          </table>
        </div>
      </details>
    @endif
  </div>
</section>
@endsection
@push('page_css')
<style>
.mayor-matriz .may2-card{border:1px solid #dee7f0;box-shadow:0 3px 10px rgba(25,45,75,.04);border-radius:9px;overflow:hidden}
.mayor-matriz .may2-kpi{background:#fff;border:1px solid #dae3ef;border-radius:9px;min-height:122px;padding:16px}
.mayor-matriz .may2-kpi small{display:block;color:#65798b;font-weight:700;font-size:.75rem;text-transform:uppercase}
.mayor-matriz .may2-kpi strong{display:block;font-size:1.6rem;font-weight:750;color:#244365;margin:6px 0}
.mayor-matriz .may2-kpi span{color:#67798a;font-size:.78rem}
.mayor-matriz .may2-table{font-size:.81rem}
.mayor-matriz .may2-table th{white-space:nowrap;background:#f6f8fb;color:#425e74;padding:10px}
.mayor-matriz .may2-table td{vertical-align:middle;padding:9px}
.mayor-matriz .may2-summary{cursor:pointer;list-style:none}
.mayor-matriz .may2-summary::-webkit-details-marker{display:none}
.mayor-matriz .may2-summary:after{content:"Abrir";float:right;color:#52749b}
.mayor-matriz details[open] .may2-summary:after{content:"Cerrar"}
</style>
@endpush