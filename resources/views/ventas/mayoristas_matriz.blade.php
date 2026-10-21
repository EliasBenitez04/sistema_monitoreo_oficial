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
      @php
        $clienteFiltro = trim((string) request('cliente', ''));
        // Catálogo histórico completo: las fechas solo restringen los resultados,
        // no los clientes disponibles para seleccionar.
        $clientesMatriz = \App\Http\Controllers\VentaMayoristaController::ventas()
            ->where(function ($q) {
                $q->whereRaw("BTRIM(COALESCE(v.cli_cod,'')) <> ''")
                  ->orWhereRaw("BTRIM(COALESCE(v.cliente,'')) <> ''");
            })
            ->selectRaw("BTRIM(COALESCE(v.cli_cod,'')) as codigo")
            ->selectRaw("BTRIM(COALESCE(v.cliente,'')) as nombre")
            ->distinct()
            ->orderBy('nombre')
            ->orderBy('codigo')
            ->get()
            ->map(function ($c) {
                $c->valor = $c->codigo !== '' ? $c->codigo : $c->nombre;
                return $c;
            })->unique('valor')->values();

        if ($clienteFiltro !== '') {
            $facturas = $facturas->filter(function ($f) use ($clienteFiltro) {
                $codigo = trim((string) $f->cli_cod);
                $nombre = trim((string) $f->cliente);
                return ($codigo !== '' && $codigo === $clienteFiltro)
                    || ($codigo === '' && $nombre === $clienteFiltro);
            })->values();

            $ventasCliente = \App\Http\Controllers\VentaMayoristaController::ventas()
                ->whereBetween('v.fecha', [$desde, $hasta])
                ->where(function ($q) use ($clienteFiltro) {
                    $q->whereRaw("BTRIM(COALESCE(v.cli_cod,'')) = ?", [$clienteFiltro])
                      ->orWhere(function ($sinCodigo) use ($clienteFiltro) {
                          $sinCodigo->whereRaw("BTRIM(COALESCE(v.cli_cod,'')) = ''")
                              ->whereRaw("BTRIM(COALESCE(v.cliente,'')) = ?", [$clienteFiltro]);
                      });
                });

            $resumen = (clone $ventasCliente)
                ->selectRaw('COUNT(*) as lineas, COUNT(DISTINCT v.comprobante) as comprobantes')
                ->selectRaw('COALESCE(SUM(CASE WHEN v.cantidad>0 THEN v.cantidad ELSE 0 END),0) as brutas')
                ->selectRaw('COALESCE(SUM(CASE WHEN v.cantidad<0 THEN -v.cantidad ELSE 0 END),0) as notas_credito')
                ->selectRaw('COALESCE(SUM(v.cantidad),0) as unidades_netas')
                ->selectRaw('COALESCE(SUM(v.p_lista),0) as importe_lista')
                ->selectRaw('COALESCE(SUM(v.descuento),0) as descuentos')
                ->selectRaw('COALESCE(SUM(v.p_venta),0) as importe_neto')->first();
            $resumen->asignadas = (int) $facturas->sum('asignadas');
            $resumen->pendientes = (int) $facturas->sum('por_asignar');

            $ventasDetalle = (clone $ventasCliente)
                ->select('v.id','v.fecha','v.comprobante','v.tipo_comprobante','v.cli_cod','v.cliente','v.vendedor','v.codigo','v.descripcion','v.cantidad','v.p_lista','v.descuento','v.p_venta')
                ->orderBy('v.fecha','desc')->orderBy('v.comprobante','desc')->orderBy('v.codigo')->orderBy('v.id')
                ->paginate(100)->appends(request()->only(['desde','hasta','cliente']));

            $porCodigo = (clone $ventasCliente)
                ->selectRaw('UPPER(BTRIM(v.codigo)) as codigo')
                ->selectRaw('MAX(v.descripcion) as descripcion')
                ->selectRaw('SUM(CASE WHEN v.cantidad>0 THEN v.cantidad ELSE 0 END) as ventas_brutas')
                ->selectRaw('SUM(CASE WHEN v.cantidad<0 THEN -v.cantidad ELSE 0 END) as devoluciones')
                ->selectRaw('SUM(v.cantidad) as ventas_netas')
                ->selectRaw('SUM(v.p_venta) as total_neto')
                ->groupByRaw('UPPER(BTRIM(v.codigo))')->orderByRaw('SUM(v.cantidad) DESC')->get();
        }
      @endphp
      @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
      @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
      <form method="GET" action="{{ route('ventas.mayoristas.index') }}" class="card may2-card p-3 mb-3">
        <div class="d-flex flex-wrap align-items-end" style="gap:12px">
          <div><label class="small font-weight-bold">Desde</label><input type="date" name="desde" class="form-control" value="{{ $desde }}" required></div>
          <div><label class="small font-weight-bold">Hasta</label><input type="date" name="hasta" class="form-control" value="{{ $hasta }}" required></div>
          <div style="min-width:280px">
            <label class="small font-weight-bold">Cliente</label>
            <select name="cliente" class="form-control">
              <option value="">Todos los clientes</option>
              @foreach($clientesMatriz as $clienteMatriz)
                <option value="{{ $clienteMatriz->valor }}" {{ $clienteFiltro === $clienteMatriz->valor ? 'selected' : '' }}>
                  {{ $clienteMatriz->nombre !== '' ? $clienteMatriz->nombre : 'Sin nombre' }}{{ $clienteMatriz->codigo !== '' ? ' · '.$clienteMatriz->codigo : '' }}
                </option>
              @endforeach
            </select>
          </div>
          <div><button class="btn btn-primary"><i class="fas fa-search mr-1"></i>Consultar ventas</button></div>
          <div><a class="btn btn-outline-success" href="{{ route('ventas.mayoristas.index',['desde'=>$desde,'hasta'=>$hasta,'exportar'=>'ventas']) }}"><i class="fas fa-file-csv mr-1"></i>Exportar todos los registros</a></div>
        </div>
        <small class="text-muted mt-2">El selector incluye todos los clientes históricos de COMERCIAL MATRIZ; las fechas y el cliente elegido filtran los resultados.</small>
      </form>
      <div class="row">
        <div class="col-lg-3 col-sm-6 mb-3"><div class="may2-kpi"><small>Unidades netas vendidas</small><strong>{{ number_format($resumen->unidades_netas,0,',','.') }}</strong><span>{{ number_format($resumen->brutas,0,',','.') }} brutas · {{ number_format($resumen->notas_credito,0,',','.') }} notas de crédito</span></div></div>
        <div class="col-lg-3 col-sm-6 mb-3"><div class="may2-kpi"><small>Importe de lista</small><strong>Gs {{ number_format($resumen->importe_lista,0,',','.') }}</strong><span>Descuentos: Gs {{ number_format($resumen->descuentos,0,',','.') }}</span></div></div>
        <div class="col-lg-3 col-sm-6 mb-3"><div class="may2-kpi"><small>Venta neta registrada</small><strong>Gs {{ number_format($resumen->importe_neto,0,',','.') }}</strong><span>{{ number_format($resumen->lineas,0,',','.') }} líneas de venta importadas</span></div></div>
        <div class="col-lg-3 col-sm-6 mb-3"><div class="may2-kpi"><small>Asignación documental</small><strong>{{ number_format($resumen->asignadas,0,',','.') }} / {{ number_format($resumen->brutas,0,',','.') }}</strong><span>{{ number_format($resumen->pendientes,0,',','.') }} unidades brutas sin remisión asignada</span></div></div>
      </div>
      <div class="alert alert-info small"><strong>Conciliación:</strong> Las unidades netas provienen de la exportación de ventas; las asignadas son vínculos revisados entre factura y línea de remisión. Una remisión a MODELO MUESTRA documenta origen/canal, pero no certifica stock físico recibido en MATRIZ. La suma neta vendida no se usa para atribuir notas de crédito a remisiones sin evidencia.</div>
      <div class="card may2-card mb-3">
        <div class="card-header bg-white"><strong><i class="fas fa-exchange-alt mr-1 text-primary"></i>1. Asignaciones de Logística hacia COMERCIAL MATRIZ</strong><small class="d-block text-muted">Seleccioná una sucursal para consultar sus números de remisión y cantidades; después presioná <strong>Ver</strong> para consultar los códigos y talles de cada documento. Destino: código 25 / MATRIZ.</small></div>
        <div class="row no-gutters may2-transfer-kpis">
          <div class="col-md-4"><div class="p-3 border-right"><small class="text-muted d-block">Desde locales → Matriz</small><strong>{{ number_format($transferenciasResumen->locales,0,',','.') }} unidades</strong></div></div>
          <div class="col-md-4"><div class="p-3 border-right"><small class="text-muted d-block">Desde Casa Central → Matriz</small><strong>{{ number_format($transferenciasResumen->central,0,',','.') }} unidades</strong></div></div>
          <div class="col-md-4"><div class="p-3"><small class="text-muted d-block">Con recepción registrada (todos los orígenes)</small><strong>{{ number_format($transferenciasResumen->recibidas,0,',','.') }} de {{ number_format($transferenciasResumen->remitidas,0,',','.') }}</strong></div></div>
        </div>
        @if($transferenciasResumen->sin_origen > 0)<div class="alert alert-warning mx-3 mt-2 mb-1 small">{{ number_format($transferenciasResumen->sin_origen,0,',','.') }} unidades no tienen origen identificable en los datos importados. Se mantienen aparte; no se atribuyen a Central.</div>@endif
        <div class="table-responsive"><table class="table table-sm table-hover may2-table mb-0"><thead><tr><th>Sucursal de origen real</th><th>Tipo</th><th class="text-right">Documentos</th><th class="text-right">Remitidas</th><th class="text-right">Con recepción</th><th class="text-right">Vinculadas a facturas</th><th class="text-right">Disponibles documentalmente</th></tr></thead><tbody>
        @forelse($transferenciasOrigen as $origen)<tr><td class="font-weight-bold"><a class="may2-sucursal-link" href="{{ route('ventas.mayoristas.sucursal',['codigo'=>$origen->codigo_origen ?: null,'nombre'=>$origen->nombre_importado ?: null,'sin_origen'=>!$origen->codigo_origen && !$origen->nombre_importado ? '1' : null,'desde'=>$desde,'hasta'=>$hasta]) }}"><i class="fas fa-folder-open mr-1"></i>{{ $origen->nombre }}<i class="fas fa-chevron-right ml-1 small"></i></a></td><td><span class="badge badge-{{ $origen->tipo==='LOCAL'?'info':($origen->tipo==='CENTRAL'?'secondary':'warning') }}">{{ $origen->tipo }}</span></td><td class="text-right">{{ number_format($origen->documentos,0,',','.') }}</td><td class="text-right">{{ number_format($origen->unidades,0,',','.') }}</td><td class="text-right">{{ number_format($origen->recibidas,0,',','.') }}</td><td class="text-right">{{ number_format($origen->vinculadas,0,',','.') }}</td><td class="text-right font-weight-bold">{{ number_format($origen->saldo,0,',','.') }}</td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-3">No se encontraron remisiones con destino 25 / COMERCIAL MATRIZ emitidas entre {{ $desde }} y {{ $hasta }}. Verificá el período importado.</td></tr>@endforelse
        </tbody></table></div>
        <details class="may2-subdetails border-top"><summary class="px-3 py-2 font-weight-bold">Ver números de remisión, origen y cantidades por documento</summary><div class="table-responsive"><table class="table table-sm may2-table mb-0"><thead><tr><th>Fecha</th><th>Serie / Nº remisión</th><th>Origen</th><th>Destino</th><th class="text-right">Líneas</th><th class="text-right">Remitidas</th><th class="text-right">Ya vinculadas</th><th class="text-right">Saldo</th><th>Recepción</th><th></th></tr></thead><tbody>
        @forelse($remisionesDocumentos as $doc)<tr><td>{{ date('d/m/Y',strtotime($doc->fecha_remision)) }}</td><td class="font-weight-bold">{{ $doc->serie }}-{{ $doc->numero_remision }}</td><td>{{ $doc->origen_real }}<small class="d-block text-muted">{{ $doc->tipo_origen }} · origen #{{ $doc->cod_sucursal_salida ?: 'sin código' }}</small>@if($doc->origen_discordante)<small class="d-block text-warning"><i class="fas fa-exclamation-triangle"></i> Revisar origen: el nombre importado dice «{{ $doc->sucursal_salida }}»</small>@endif</td><td>COMERCIAL MATRIZ</td><td class="text-right">{{ number_format($doc->lineas,0,',','.') }}</td><td class="text-right">{{ number_format($doc->unidades,0,',','.') }}</td><td class="text-right">{{ number_format($doc->vinculadas,0,',','.') }}</td><td class="text-right font-weight-bold">{{ number_format($doc->saldo,0,',','.') }}</td><td>{{ $doc->lineas_recibidas==$doc->lineas ? 'Recibida':'Parcial / sin confirmar' }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('ventas.mayoristas.remision',['id'=>$doc->remision_id,'desde'=>$desde,'hasta'=>$hasta]) }}">Ver</a></td></tr>@empty<tr><td colspan="10" class="text-center text-muted">Sin documentos en el período.</td></tr>@endforelse
        </tbody></table></div><div class="card-footer bg-white">{{ $remisionesDocumentos->appends(['cliente'=>$clienteFiltro])->links() }}</div></details>
        <div class="card-footer bg-white text-muted small"><strong>Importante:</strong> asignación logística = traslado registrado de una sucursal hacia Matriz. Asignación a factura = cantidad vinculada manualmente a una línea de remisión con evidencia. Las cantidades disponibles son saldos documentales por remisión, no stock físico del local. Para cruzar los códigos y talles, abrí «Cruzar» en la factura correspondiente.</div>
      </div>
      <div class="card may2-card mb-3"><div class="card-header bg-white"><strong><i class="fas fa-receipt mr-1 text-primary"></i>2. Facturas / comprobantes de COMERCIAL MATRIZ</strong><small class="d-block text-muted">Seleccioná una factura para contrastar sus talles contra remisiones del mismo número y asignar cantidades.</small></div><div class="table-responsive"><table class="table table-sm table-hover may2-table mb-0"><thead><tr><th>Fecha</th><th>Comprobante</th><th>Cliente</th><th>Tipo</th><th class="text-right">Unidades brutas</th><th class="text-right">Neto unidades</th><th class="text-right">Gs netos</th><th class="text-right">Asignadas</th><th class="text-right">Sin asignar</th><th>Estado</th><th></th></tr></thead><tbody>
        @forelse($facturas as $f)<tr><td>{{ date('d/m/Y',strtotime($f->fecha)) }}</td><td class="font-weight-bold">{{ $f->comprobante }}</td><td>{{ $f->cliente ?: $f->cli_cod }}</td><td>{{ $f->tipo_comprobante }}</td><td class="text-right">{{ number_format($f->brutas,0,',','.') }}</td><td class="text-right">{{ number_format($f->netas,0,',','.') }}</td><td class="text-right">Gs {{ number_format($f->pvta,0,',','.') }}</td><td class="text-right text-success">{{ number_format($f->asignadas,0,',','.') }}</td><td class="text-right">{{ number_format($f->por_asignar,0,',','.') }}</td><td><span class="badge badge-{{ $f->estado_cruce==='ASIGNADA'?'success':($f->estado_cruce==='PARCIAL'?'warning':'secondary') }}">{{ $f->estado_cruce }}</span></td><td><a class="btn btn-sm btn-primary" href="{{ route('ventas.mayoristas.factura',['clave'=>$f->clave,'mes'=>substr($f->fecha,0,7),'desde'=>$desde,'hasta'=>$hasta]) }}">Cruzar</a></td></tr>@empty<tr><td colspan="11" class="text-center text-muted py-3">No hay comprobantes de COMERCIAL MATRIZ para el cliente y período seleccionados.</td></tr>@endforelse
        </tbody></table></div></div>
      <details class="card may2-card mb-3"><summary class="card-header bg-white may2-summary"><strong><i class="fas fa-list mr-1 text-primary"></i>3. Todas las líneas vendidas (detalle de cada talle)</strong><small class="d-block text-muted">Fecha, factura, cliente, vendedor, código completo, descripción, unidades y montos. Se muestran 100 líneas por página; exportación incluye todos los registros.</small></summary><div class="table-responsive" style="max-height:570px;overflow:auto"><table class="table table-sm table-striped may2-table mb-0"><thead><tr><th>Fecha</th><th>Factura</th><th>Tipo</th><th>Cliente / código</th><th>Vendedor</th><th>Código completo</th><th>Descripción</th><th class="text-right">Cant.</th><th class="text-right">Lista Gs</th><th class="text-right">Descuento Gs</th><th class="text-right">Venta Gs</th></tr></thead><tbody>
        @foreach($ventasDetalle as $v)<tr><td>{{ date('d/m/Y',strtotime($v->fecha)) }}</td><td>{{ $v->comprobante }}</td><td>{{ $v->tipo_comprobante }}</td><td>{{ $v->cliente }}<small class="d-block text-muted">{{ $v->cli_cod }}</small></td><td>{{ $v->vendedor }}</td><td class="font-weight-bold">{{ $v->codigo }}</td><td>{{ $v->descripcion }}</td><td class="text-right">{{ number_format($v->cantidad,0,',','.') }}</td><td class="text-right">{{ number_format($v->p_lista,2,',','.') }}</td><td class="text-right">{{ number_format($v->descuento,2,',','.') }}</td><td class="text-right">{{ number_format($v->p_venta,2,',','.') }}</td></tr>@endforeach
        </tbody></table></div><div class="card-footer bg-white">{{ $ventasDetalle->appends(['cliente'=>$clienteFiltro])->links() }}</div></details>
      <details class="card may2-card mb-4"><summary class="card-header bg-white may2-summary"><strong><i class="fas fa-barcode mr-1 text-primary"></i>4. Totales vendidos por código y talle</strong><small class="d-block text-muted">Consolidado de unidades y montos de COMERCIAL MATRIZ. La remisión se asigna desde cada factura, nunca por coincidencia de totales globales.</small></summary><div class="table-responsive" style="max-height:480px;overflow:auto"><table class="table table-sm may2-table mb-0"><thead><tr><th>Código completo</th><th>Artículo</th><th class="text-right">Brutas</th><th class="text-right">NC / devoluciones</th><th class="text-right">Netas</th><th class="text-right">Importe neto Gs</th></tr></thead><tbody>@foreach($porCodigo as $c)<tr><td class="font-weight-bold">{{ $c->codigo }}</td><td>{{ $c->descripcion }}</td><td class="text-right">{{ number_format($c->ventas_brutas,0,',','.') }}</td><td class="text-right">{{ number_format($c->devoluciones,0,',','.') }}</td><td class="text-right">{{ number_format($c->ventas_netas,0,',','.') }}</td><td class="text-right">{{ number_format($c->total_neto,2,',','.') }}</td></tr>@endforeach</tbody></table></div></details>
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
.mayor-matriz .may2-transfer-kpis{background:#f7fbff}
.mayor-matriz .may2-transfer-kpis strong{font-size:1.12rem;color:#244365}
.mayor-matriz .may2-subdetails>summary{cursor:pointer;list-style:none;background:#f7faff}
.mayor-matriz .may2-sucursal-link{color:#1d5f9a;text-decoration:none}
.mayor-matriz .may2-sucursal-link:hover{text-decoration:underline}
.mayor-matriz .may2-summary{cursor:pointer;list-style:none}
.mayor-matriz .may2-summary::-webkit-details-marker{display:none}
.mayor-matriz .may2-summary:after{content:"Abrir";float:right;color:#52749b}
.mayor-matriz details[open] .may2-summary:after{content:"Cerrar"}
</style>
@endpush