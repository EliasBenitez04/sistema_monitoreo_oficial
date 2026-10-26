@extends('layouts.app')

@section('content')
<section class="content"><div class="container-fluid py-3 maycliente">
  <div class="maycliente-header d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:14px">
    <div>
      <small class="text-uppercase text-primary font-weight-bold" style="letter-spacing:1px">COMERCIAL MATRIZ · CLIENTES MAYORISTAS</small>
      <h2 class="mt-1 mb-1"><i class="fas fa-user-tag mr-2 text-primary"></i>{{ $nombreCliente !== '' ? $nombreCliente : 'Cliente sin nombre' }}</h2>
      <div class="text-muted">
        {{ $codigoCliente !== '' ? 'Código: '.$codigoCliente.' · ' : 'Sin código de cliente · ' }}
        Facturas del {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }}
        al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
      </div>
    </div>
    <a href="{{ route('ventas.mayoristas.index', ['desde'=>$desde,'hasta'=>$hasta]) }}#top-clientes" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left mr-1"></i>Volver a Top clientes
    </a>
  </div>

  <form method="GET" action="{{ route('ventas.mayoristas.cliente') }}" class="card maycliente-card p-3 mb-3">
    <input type="hidden" name="cliente" value="{{ \App\Support\MayoristaTopClientes::token($codigoCliente, $nombreCliente) }}">
    <div class="d-flex flex-wrap align-items-end" style="gap:12px">
      <div><label for="maycli-desde" class="small font-weight-bold">Desde</label>
        <input id="maycli-desde" type="date" name="desde" value="{{ $desde }}" class="form-control" required></div>
      <div><label for="maycli-hasta" class="small font-weight-bold">Hasta</label>
        <input id="maycli-hasta" type="date" name="hasta" value="{{ $hasta }}" class="form-control" required></div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i>Consultar período</button>
      <a href="{{ route('ventas.mayoristas.cliente', ['cliente'=>\App\Support\MayoristaTopClientes::token($codigoCliente, $nombreCliente),'desde'=>\Carbon\Carbon::parse($hasta)->startOfMonth()->format('Y-m-d'),'hasta'=>$hasta]) }}" class="btn btn-outline-primary">
        <i class="far fa-calendar-alt mr-1"></i>Desde inicio de mes
      </a>
    </div>
    <small class="d-block text-muted mt-2">El control muestra exclusivamente las ventas de este cliente en COMERCIAL MATRIZ. Los tickets corresponden a facturas distintas, no a líneas de productos.</small>
  </form>

  <div class="row">
    <div class="col-lg-3 col-sm-6 mb-3"><div class="maycliente-kpi">
      <small><i class="fas fa-receipt mr-1"></i>Tickets de venta</small>
      <strong>{{ number_format($resumenCliente->tickets,0,',','.') }}</strong>
      <span>Facturas únicas emitidas en el período</span>
    </div></div>
    <div class="col-lg-3 col-sm-6 mb-3"><div class="maycliente-kpi">
      <small><i class="fas fa-shopping-bag mr-1"></i>Unidades netas</small>
      <strong>{{ number_format($resumenCliente->unidades,0,',','.') }}</strong>
      <span>{{ number_format($resumenCliente->brutas,0,',','.') }} brutas · {{ number_format($resumenCliente->devoluciones,0,',','.') }} devueltas</span>
    </div></div>
    <div class="col-lg-3 col-sm-6 mb-3"><div class="maycliente-kpi">
      <small><i class="fas fa-coins mr-1"></i>Venta neta registrada</small>
      <strong class="maycliente-money">Gs {{ number_format($resumenCliente->venta_neta,0,',','.') }}</strong>
      <span>Incluye ajustes y notas de crédito</span>
    </div></div>
    <div class="col-lg-3 col-sm-6 mb-3"><div class="maycliente-kpi">
      <small><i class="fas fa-undo-alt mr-1"></i>Notas de crédito</small>
      <strong>{{ number_format($resumenCliente->notas_credito,0,',','.') }}</strong>
      <span>Documentos separados de los tickets</span>
    </div></div>
  </div>

  <div class="card maycliente-card mb-3">
    <div class="card-header bg-white d-flex align-items-center justify-content-between flex-wrap" style="gap:10px">
      <div>
        <strong><i class="fas fa-file-invoice text-primary mr-2"></i>Facturas y comprobantes del cliente</strong>
        <small class="d-block text-muted">
          {{ number_format($facturasPagina->total(),0,',','.') }} documentos registrados;
          mostrando {{ number_format($facturasPagina->firstItem() ?: 0,0,',','.') }}
          a {{ number_format($facturasPagina->lastItem() ?: 0,0,',','.') }}.
          Se muestran 30 por página.
        </small>
      </div>
      <span class="badge badge-primary p-2">{{ number_format($resumenCliente->tickets,0,',','.') }} tickets de venta</span>
    </div>
    <div class="table-responsive">
      <table class="table table-sm table-hover maycliente-table mb-0">
        <thead><tr>
          <th>Fecha</th>
          <th>Factura / comprobante</th>
          <th>Tipo</th>
          <th class="text-right">Unidades brutas</th>
          <th class="text-right">NC / devoluciones</th>
          <th class="text-right">Unidades netas</th>
          <th class="text-right">Venta neta</th>
          <th class="text-center">Detalle</th>
        </tr></thead>
        <tbody>
          @forelse($facturasPagina as $facturaCliente)
            <tr>
              <td>{{ \Carbon\Carbon::parse($facturaCliente->fecha)->format('d/m/Y') }}</td>
              <td class="font-weight-bold">{{ $facturaCliente->comprobante }}</td>
              <td>
                @if(\App\Support\MayoristaTopClientes::esNotaCredito($facturaCliente))
                  <span class="badge badge-warning">Nota de crédito</span>
                @else
                  <span class="badge badge-info">{{ $facturaCliente->tipo_comprobante ?: 'Venta' }}</span>
                @endif
              </td>
              <td class="text-right">{{ number_format($facturaCliente->brutas,0,',','.') }}</td>
              <td class="text-right">{{ number_format($facturaCliente->devoluciones,0,',','.') }}</td>
              <td class="text-right font-weight-bold">{{ number_format($facturaCliente->netas,0,',','.') }}</td>
              <td class="text-right font-weight-bold {{ $facturaCliente->pvta < 0 ? 'text-danger' : 'text-primary' }}">
                Gs {{ number_format($facturaCliente->pvta,0,',','.') }}
              </td>
              <td class="text-center">
                <a class="btn btn-sm btn-outline-primary" href="{{ route('ventas.mayoristas.factura', [
                  'clave'=>$facturaCliente->clave,
                  'mes'=>substr((string)$facturaCliente->fecha,0,7),
                  'desde'=>$desde,
                  'hasta'=>$hasta
                ]) }}">
                  <i class="fas fa-eye mr-1"></i>Ver factura
                </a>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-muted py-4">
              No se registraron facturas de este cliente entre
              {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} y
              {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}.
            </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap" style="gap:8px">
      <small class="text-muted">«Ver factura» abre los códigos, talles y cantidades de ese comprobante, junto con su seguimiento documental.</small>
      <div>{{ $facturasPagina->links() }}</div>
    </div>
  </div>

  <div class="alert alert-light border small text-muted">
    <i class="fas fa-info-circle text-primary mr-1"></i>
    <strong>Criterio:</strong> se cuentan comprobantes positivos diferentes por fecha, número y tipo; las notas de crédito se registran aparte.
    Las unidades y los importes son netos e incluyen las devoluciones. No se atribuyen transferencias de logística al cliente por estimación.
  </div>
</div></section>
@endsection

@push('page_css')
<style>
.maycliente .maycliente-header h2{font-size:1.65rem;font-weight:800;color:#183d72}
.maycliente .maycliente-card{border:1px solid #dfe8f4;border-radius:10px;box-shadow:0 2px 11px rgba(22,56,101,.035);overflow:hidden}
.maycliente .maycliente-kpi{background:#fff;border:1px solid #dbe6f4;border-radius:10px;padding:16px;min-height:127px}
.maycliente .maycliente-kpi small{display:block;font-size:.76rem;font-weight:800;text-transform:uppercase;color:#667d9b}
.maycliente .maycliente-kpi strong{display:block;margin:11px 0 5px;color:#17477e;font-size:1.75rem;line-height:1.2;font-weight:800}
.maycliente .maycliente-kpi strong.maycliente-money{font-size:1.48rem}
.maycliente .maycliente-kpi span{font-size:.76rem;color:#798aa1}
.maycliente .maycliente-table{font-size:.82rem}
.maycliente .maycliente-table th{background:#f3f7fd;color:#4a6586;padding:11px;white-space:nowrap}
.maycliente .maycliente-table td{padding:11px;vertical-align:middle}
.maycliente .maycliente-table tbody tr:hover{background:#f5f9ff}
.maycliente .maycliente-table .btn{white-space:nowrap}
.maycliente .pagination{margin:0}
</style>
@endpush
