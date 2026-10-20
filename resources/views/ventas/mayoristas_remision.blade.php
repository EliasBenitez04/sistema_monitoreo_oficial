@extends('layouts.app')

@section('content')
<section class="content">
  <div class="container-fluid py-3 may-rem">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
      <div>
        <h2 class="mb-1"><i class="fas fa-file-alt text-primary mr-2"></i>Detalle de remisión {{ $cabecera->serie }}-{{ $cabecera->numero_remision }}</h2>
        <p class="text-muted mb-0">
          {{ $cabecera->fecha_remision ? date('d/m/Y',strtotime($cabecera->fecha_remision)) : 'Fecha no informada' }}
          · Origen: <strong>{{ $cabecera->origen_real }}</strong>
          · Destino: <strong>COMERCIAL MATRIZ (25)</strong>
        </p>
      </div>
      <div class="d-flex flex-wrap mt-2" style="gap:7px">
        <a class="btn btn-outline-secondary" href="{{ route('ventas.mayoristas.sucursal', [
            'codigo'=>$cabecera->cod_sucursal_salida ?: null,
            'nombre'=>$cabecera->sucursal_salida ?: null,
            'sin_origen'=>!$cabecera->cod_sucursal_salida && !$cabecera->sucursal_salida ? '1' : null,
            'desde'=>request('desde',date('Y-m-01',strtotime($cabecera->fecha_remision))),
            'hasta'=>request('hasta',$cabecera->fecha_remision)
        ]) }}"><i class="fas fa-arrow-left mr-1"></i>Remisiones de {{ $cabecera->origen_real }}</a>
        <a class="btn btn-outline-primary" href="{{ route('ventas.mayoristas.index',[
            'desde'=>request('desde',date('Y-m-01',strtotime($cabecera->fecha_remision))),
            'hasta'=>request('hasta',$cabecera->fecha_remision)
        ]) }}">Volver a Mayoristas</a>
      </div>
    </div>
    @if($cabecera->origen_discordante)
      <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        El código y el nombre de origen no coinciden: código {{ $cabecera->cod_sucursal_salida }},
        nombre importado «{{ $cabecera->sucursal_salida }}». Verificar el documento original.
      </div>
    @endif

    <div class="row">
      <div class="col-lg-3 col-sm-6 mb-3">
        <div class="may-rem-kpi"><small>Prendas remitidas</small><strong>{{ number_format($totales->remitidas,0,',','.') }}</strong></div>
      </div>
      <div class="col-lg-3 col-sm-6 mb-3">
        <div class="may-rem-kpi"><small>Prendas con recepción</small><strong>{{ number_format($totales->recibidas,0,',','.') }}</strong></div>
      </div>
      <div class="col-lg-3 col-sm-6 mb-3">
        <div class="may-rem-kpi"><small>Vinculadas a facturas</small><strong>{{ number_format($totales->vinculadas,0,',','.') }}</strong></div>
      </div>
      <div class="col-lg-3 col-sm-6 mb-3">
        <div class="may-rem-kpi"><small>Saldo documental</small><strong>{{ number_format($totales->disponibles,0,',','.') }}</strong></div>
      </div>
    </div>

    <div class="card may-rem-card mb-3">
      <div class="card-header bg-white">
        <strong><i class="fas fa-barcode text-primary mr-2"></i>Artículos y talles de esta remisión</strong>
        <small class="d-block text-muted">
          {{ number_format($totales->lineas,0,',','.') }} líneas de artículos.
          Las cantidades están agrupadas únicamente bajo esta serie/número, origen, destino y fecha.
        </small>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover may-rem-table mb-0">
          <thead>
            <tr>
              <th>OT</th><th>Código completo / talle</th><th>Descripción</th>
              <th class="text-right">Remitida</th><th class="text-right">Vinculada a facturas</th>
              <th class="text-right">Saldo</th>
              <th>Recepción</th><th>Estado</th>
              <th class="text-right">Precio venta</th><th class="text-right">Costo unitario</th>
            </tr>
          </thead>
          <tbody>
            @forelse($lineas as $linea)
              <tr>
                <td>{{ $linea->nro_ot ?: 'Sin OT' }}</td>
                <td>
                  <strong>{{ $linea->codigo }}</strong>
                  <small class="d-block text-muted">Línea remisión #{{ $linea->id }}</small>
                </td>
                <td>{{ $linea->descripcion ?: 'Sin descripción' }}</td>
                <td class="text-right font-weight-bold">{{ number_format($linea->cantidad,0,',','.') }}</td>
                <td class="text-right">{{ number_format($linea->vinculadas,0,',','.') }}</td>
                <td class="text-right font-weight-bold {{ $linea->disponibles > 0 ? 'text-primary':'' }}">{{ number_format($linea->disponibles,0,',','.') }}</td>
                <td>
                  @if($linea->fecha_recepcion)
                    {{ date('d/m/Y',strtotime($linea->fecha_recepcion)) }}
                  @else
                    <span class="text-warning">Sin recepción</span>
                  @endif
                </td>
                <td>{{ $linea->estado ?: 'No informado' }}</td>
                <td class="text-right">{{ $linea->precio_venta !== null ? number_format($linea->precio_venta,2,',','.') : '—' }}</td>
                <td class="text-right">{{ $linea->costo_unitario !== null ? number_format($linea->costo_unitario,2,',','.') : '—' }}</td>
              </tr>
            @empty
              <tr><td colspan="10" class="text-center text-muted py-3">Este documento no contiene líneas disponibles en los datos importados.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="card-footer bg-white">{{ $lineas->links() }}</div>
    </div>
    <div class="alert alert-info small">
      <strong>Control documental:</strong> Remitida – vinculada a facturas = saldo disponible por línea.
      La recepción se identifica por su fecha importada. El saldo no confirma existencias físicas
      ni representa ventas de Matriz si todavía no existe un vínculo de factura auditado.
    </div>
  </div>
</section>
@endsection

@push('page_css')
<style>
.may-rem .may-rem-kpi { border:1px solid #dfe7ef; background:#fff; border-radius:9px; padding:15px; }
.may-rem .may-rem-kpi small { display:block; text-transform:uppercase; font-size:.75rem; font-weight:700; color:#657a90; }
.may-rem .may-rem-kpi strong { display:block; font-size:1.7rem; color:#254764; margin-top:6px; }
.may-rem .may-rem-card { border:1px solid #dfe7ef; border-radius:9px; overflow:hidden; }
.may-rem .may-rem-table { font-size:.82rem; }
.may-rem .may-rem-table th { background:#f6f8fb; white-space:nowrap; color:#40566c; }
.may-rem .may-rem-table td,.may-rem .may-rem-table th { padding:10px; vertical-align:middle; }
</style>
@endpush