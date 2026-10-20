@extends('layouts.app')

@section('content')
<section class="content">
  <div class="container-fluid py-3 mayor-sucursal">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
      <div>
        <h2 class="mb-1"><i class="fas fa-store text-primary mr-2"></i>Remisiones de {{ $origen->nombre }}</h2>
        <p class="text-muted mb-0">
          Origen: <strong>{{ $origen->nombre }}</strong>
          · {{ $origen->tipo }} · Destino: <strong>COMERCIAL MATRIZ (25)</strong>
          · {{ date('d/m/Y',strtotime($desde)) }} al {{ date('d/m/Y',strtotime($hasta)) }}
        </p>
      </div>
      <a class="btn btn-outline-secondary mt-2" href="{{ route('ventas.mayoristas.index',['desde'=>$desde,'hasta'=>$hasta]) }}">
        <i class="fas fa-arrow-left mr-1"></i>Volver a sucursales
      </a>
    </div>

    <div class="row">
      <div class="col-lg-3 col-sm-6 mb-3">
        <div class="may-suc-kpi"><small>Documentos de remisión</small><strong>{{ number_format($totales->documentos,0,',','.') }}</strong><span>{{ number_format($totales->lineas,0,',','.') }} líneas de artículos</span></div>
      </div>
      <div class="col-lg-3 col-sm-6 mb-3">
        <div class="may-suc-kpi"><small>Cantidad remitida</small><strong>{{ number_format($totales->remitidas,0,',','.') }}</strong><span>Prendas enviadas a Matriz</span></div>
      </div>
      <div class="col-lg-3 col-sm-6 mb-3">
        <div class="may-suc-kpi"><small>Con recepción registrada</small><strong>{{ number_format($totales->recibidas,0,',','.') }}</strong><span>Según fecha de recepción importada</span></div>
      </div>
      <div class="col-lg-3 col-sm-6 mb-3">
        <div class="may-suc-kpi"><small>Saldo documental</small><strong>{{ number_format($totales->disponibles,0,',','.') }}</strong><span>{{ number_format($totales->vinculadas,0,',','.') }} vinculadas a facturas</span></div>
      </div>
    </div>

    <div class="card may-suc-card mb-3">
      <div class="card-header bg-white">
        <strong><i class="fas fa-file-alt text-primary mr-2"></i>Números de remisión de {{ $origen->nombre }}</strong>
        <small class="d-block text-muted">
          Cada fila corresponde a un documento identificado por serie, número, fecha, origen y destino.
          Presioná «Ver» para consultar sus artículos y talles.
        </small>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover may-suc-table mb-0">
          <thead>
            <tr>
              <th>Fecha de emisión</th>
              <th>Serie / Nº remisión</th>
              <th class="text-right">Líneas</th>
              <th class="text-right">Prendas remitidas</th>
              <th class="text-right">Recibidas</th>
              <th class="text-right">Vinculadas</th>
              <th class="text-right">Saldo</th>
              <th>Estado de recepción</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse($remisiones as $doc)
              <tr>
                <td>{{ $doc->fecha_remision ? date('d/m/Y',strtotime($doc->fecha_remision)) : 'Sin fecha' }}</td>
                <td class="font-weight-bold">{{ $doc->serie }}-{{ $doc->numero_remision }}</td>
                <td class="text-right">{{ number_format($doc->lineas,0,',','.') }}</td>
                <td class="text-right font-weight-bold">{{ number_format($doc->remitidas,0,',','.') }}</td>
                <td class="text-right">{{ number_format($doc->recibidas,0,',','.') }}</td>
                <td class="text-right">{{ number_format($doc->vinculadas,0,',','.') }}</td>
                <td class="text-right">{{ number_format($doc->disponibles,0,',','.') }}</td>
                <td>
                  @if($doc->lineas_recibidas == $doc->lineas)
                    <span class="badge badge-success">Recibida</span>
                  @elseif($doc->lineas_recibidas > 0)
                    <span class="badge badge-warning">Recepción parcial</span>
                  @else
                    <span class="badge badge-secondary">Sin recepción</span>
                  @endif
                </td>
                <td>
                  <a class="btn btn-sm btn-primary" href="{{ route('ventas.mayoristas.remision',[
                    'id'=>$doc->remision_id,
                    'desde'=>$desde,'hasta'=>$hasta,
                    'codigo'=>$codigo,'nombre'=>$nombre,
                    'sin_origen'=>$sinOrigen ? '1' : null
                  ]) }}">
                    <i class="fas fa-eye mr-1"></i>Ver
                  </a>
                </td>
              </tr>
            @empty
              <tr><td colspan="9" class="text-center text-muted py-3">No hay remisiones para esta sucursal y período.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="card-footer bg-white">{{ $remisiones->links() }}</div>
    </div>

    <p class="small text-muted">
      La cantidad disponible corresponde a la remisión menos los vínculos documentales registrados en facturas.
      No equivale por sí sola a existencias físicas.
    </p>
  </div>
</section>
@endsection

@push('page_css')
<style>
.mayor-sucursal .may-suc-kpi { border:1px solid #dee6ee; border-radius:9px; background:#fff; padding:15px; min-height:110px; }
.mayor-sucursal .may-suc-kpi small { display:block; text-transform:uppercase; color:#64748b; font-weight:700; font-size:.74rem; }
.mayor-sucursal .may-suc-kpi strong { display:block; color:#244565; font-size:1.65rem; margin:5px 0; }
.mayor-sucursal .may-suc-kpi span { color:#718096; font-size:.8rem; }
.mayor-sucursal .may-suc-card { border:1px solid #dee6ee; border-radius:9px; overflow:hidden; }
.mayor-sucursal .may-suc-table { font-size:.84rem; }
.mayor-sucursal .may-suc-table th { white-space:nowrap; color:#40566a; background:#f6f8fb; }
.mayor-sucursal .may-suc-table td,.mayor-sucursal .may-suc-table th { padding:10px; vertical-align:middle; }
</style>
@endpush