@extends('layouts.app')
@section('content')
<section class="content">
  <div class="container-fluid py-3 mayor-matriz">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
      <div>
        <h2 class="mb-1"><i class="fas fa-file-invoice-dollar text-primary mr-2"></i>Ventas mayoristas · COMERCIAL MATRIZ</h2>
        <p class="text-muted mb-0">Facturas reales y transferencias recibidas desde locales y Casa Central. Distribución referencial sin conciliación.</p>
      </div>
      <div class="d-flex align-items-center mt-2">
        <div class="badge badge-primary p-2 mt-2">Sucursal: 25 · COMERCIAL MATRIZ</div>
      </div>
    </div>
    @if(!empty($sinFuentes))
      <div class="alert alert-warning">No se encontraron las tablas ventas y remisiones. Verificá los importadores y las migraciones antes de continuar.</div>
    @else
      @php
        $clienteFiltro = trim((string) request('cliente', ''));
        // Mantener clientes diferentes aun cuando el código se repita o falte.
        // Las fechas nunca limitan las opciones disponibles.
        $clientesMatriz = \App\Http\Controllers\VentaMayoristaController::ventas()
            ->selectRaw("BTRIM(COALESCE(v.cli_cod,'')) as codigo")
            ->selectRaw("BTRIM(COALESCE(v.cliente,'')) as nombre")
            ->distinct()->get()
            ->filter(function ($c) { return $c->codigo !== '' || $c->nombre !== ''; })
            ->map(function ($c) {
                $c->valor = 'CLIENTE:'.base64_encode(json_encode([$c->codigo, $c->nombre]));
                return $c;
            })->sortBy(function ($c) { return mb_strtoupper($c->nombre.' '.$c->codigo); })->values();

        // Compatibilidad con enlaces previos que pasaban el código sin prefijo.
        $clienteCodigo = null;
        $clienteNombre = null;
        if (strpos($clienteFiltro, 'CLIENTE:') === 0) {
            $partes = json_decode(base64_decode(substr($clienteFiltro, 8), true) ?: '', true);
            if (is_array($partes) && count($partes) === 2 && is_string($partes[0]) && is_string($partes[1])) {
                $clienteCodigo = trim($partes[0]);
                $clienteNombre = trim($partes[1]);
            } else {
                $clienteFiltro = '';
            }
        } elseif ($clienteFiltro !== '') {
            $clienteCodigo = $clienteFiltro;
        }

        if ($clienteFiltro !== '') {
            $facturas = $facturas->filter(function ($f) use ($clienteCodigo, $clienteNombre) {
                $codigo = trim((string) $f->cli_cod);
                $nombre = trim((string) $f->cliente);
                return $clienteNombre === null
                    ? ($codigo === $clienteCodigo || ($codigo === '' && $nombre === $clienteCodigo))
                    : ($codigo === $clienteCodigo && $nombre === $clienteNombre);
            })->values();

            $ventasCliente = \App\Http\Controllers\VentaMayoristaController::ventas()
                ->whereBetween('v.fecha', [$desde, $hasta])
                ->where(function ($q) use ($clienteCodigo, $clienteNombre) {
                    if ($clienteNombre !== null) {
                        $q->whereRaw("BTRIM(COALESCE(v.cli_cod,'')) = ?", [$clienteCodigo])
                          ->whereRaw("BTRIM(COALESCE(v.cliente,'')) = ?", [$clienteNombre]);
                    } else {
                        $q->whereRaw("BTRIM(COALESCE(v.cli_cod,'')) = ?", [$clienteCodigo])
                          ->orWhere(function ($sinCodigo) use ($clienteCodigo) {
                              $sinCodigo->whereRaw("BTRIM(COALESCE(v.cli_cod,'')) = ''")
                                  ->whereRaw("BTRIM(COALESCE(v.cliente,'')) = ?", [$clienteCodigo]);
                          });
                    }
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
          {{-- <div class="d-flex align-items-center flex-wrap" style="gap:7px">
            <a class="btn btn-success" href="{{ route('ventas.mayoristas.index',['desde'=>$desde,'hasta'=>$hasta,'exportar'=>'ventas']) }}"
               title="Descargar las 12 columnas originales compatibles con Importar ventas">
              <i class="fas fa-file-csv mr-1"></i>Exportar formato de importación
            </a>
            <a class="btn btn-outline-success" href="{{ route('ventas.mayoristas.index',['exportar'=>'historico']) }}"
               title="Todas las ventas históricas de COMERCIAL MATRIZ, sin límite de fechas">
              <i class="fas fa-history mr-1"></i>Exportar histórico completo
            </a>
            <a class="btn btn-outline-secondary" href="{{ route('ventas.mayoristas.index',['desde'=>$desde,'hasta'=>$hasta,'exportar'=>'auditoria']) }}"
               title="Archivo anterior con ID, hash, fechas de importación y otros datos técnicos">
              <i class="fas fa-clipboard-list mr-1"></i>CSV auditoría técnica
            </a>
          </div> --}}
        </div>
        {{-- <small class="text-muted mt-2">
          El selector incluye todos los clientes históricos de COMERCIAL MATRIZ; las fechas y el cliente elegido filtran los resultados.
          <strong>Exportar formato de importación</strong> descarga todas las líneas del período de la sucursal 25 (sin limitarse a la paginación ni al cliente del selector).
          El CSV utiliza exactamente: LOCAL, CODIGO, DESCRIPCION, CLI COD, CLIENTE, VENDEDOR, PLISTA, DTO, PVTA, FECHA, CANTIDAD y COMPROBANTE.
          Los importes negativos de notas de crédito se conservan. <strong>Histórico completo</strong> no aplica límite de fechas.
        </small> --}}
      </form>
      <div class="row">
        <div class="col-lg-3 col-sm-6 mb-3"><div class="may2-kpi"><small>Unidades netas vendidas</small><strong>{{ number_format($resumen->unidades_netas,0,',','.') }}</strong><span>{{ number_format($resumen->brutas,0,',','.') }} brutas · {{ number_format($resumen->notas_credito,0,',','.') }} notas de crédito</span></div></div>
        <div class="col-lg-3 col-sm-6 mb-3"><div class="may2-kpi"><small>Importe de lista</small><strong>Gs {{ number_format($resumen->importe_lista,0,',','.') }}</strong><span>Descuentos: Gs {{ number_format($resumen->descuentos,0,',','.') }}</span></div></div>
        <div class="col-lg-3 col-sm-6 mb-3"><div class="may2-kpi"><small>Venta neta registrada</small><strong>Gs {{ number_format($resumen->importe_neto,0,',','.') }}</strong><span>{{ number_format($resumen->lineas,0,',','.') }} líneas de venta importadas</span></div></div>
        {{-- <div class="col-lg-3 col-sm-6 mb-3"><div class="may2-kpi"><small>Transferencias a Matriz (reales)</small><strong>{{ number_format($transferenciasResumen->remitidas,0,',','.') }}</strong><span>Locales {{ number_format($transferenciasResumen->locales,0,',','.') }} · Central {{ number_format($transferenciasResumen->central,0,',','.') }}</span></div></div> --}}
      </div>
        @php
          $actividadClientes = in_array(request('actividad_clientes'), ['todos','periodo','sin_periodo','sin_recientes'], true)
              ? request('actividad_clientes') : 'todos';
          $topClientesFiltrados = $topClientes->filter(function ($c) use ($actividadClientes) {
              if ($actividadClientes === 'periodo') return $c->con_movimientos_periodo;
              if ($actividadClientes === 'sin_periodo') return !$c->con_movimientos_periodo;
              if ($actividadClientes === 'sin_recientes') return $c->estado_frecuencia === 'SIN COMPRAS RECIENTES';
              return true;
          })->values();
        @endphp
      <div class="card may2-card may2-client-ranking mb-3" id="top-clientes">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center" style="gap:12px">
          <div>
            <strong><i class="fas fa-users text-primary mr-2"></i>Top clientes · COMERCIAL MATRIZ</strong>
            <small class="d-block text-muted">Importes del período seleccionado; última compra y frecuencia según el historial disponible hasta el corte.</small>
          </div>
          <div class="d-flex flex-wrap align-items-center" style="gap:8px">
            <span class="badge badge-light border px-2 py-2">{{ number_format($topClientes->count(),0,',','.') }} clientes históricos</span>
            <span class="badge badge-light border px-2 py-2">{{ number_format($topClientes->where('con_movimientos_periodo', true)->count(),0,',','.') }} con movimientos en el período</span>
            <span class="badge badge-light border px-2 py-2">{{ number_format($topClientes->sum('tickets'),0,',','.') }} tickets del período</span>
            <span class="badge badge-light border px-2 py-2">{{ number_format($topClientes->sum('tickets_historicos'),0,',','.') }} tickets históricos</span>
            @if($topClientesFiltrados->count() > 20)
              <a class="btn btn-sm btn-outline-primary" href="{{ route('ventas.mayoristas.index', array_merge(request()->except(['page','remisiones_page','top_clientes']), ['desde'=>$desde,'hasta'=>$hasta,'top_clientes'=>request('top_clientes')==='todos' ? '20' : 'todos'])) }}#top-clientes">
                <i class="fas fa-list mr-1"></i>{{ request('top_clientes')==='todos' ? 'Mostrar top 20' : 'Ver todos los clientes' }}
              </a>
            @endif
          </div>
        </div>
        <div class="may2-clients-hint px-3 py-2">
          <i class="fas fa-calendar-alt text-primary mr-1"></i>
          <strong>Venta y tickets:</strong> del {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} al
          {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}, netos de devoluciones.
          <strong>Última factura y frecuencia:</strong> historial disponible hasta {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }},
          independientemente del filtro de cliente. Un cliente histórico sin movimientos en este período muestra cero en sus indicadores del período.
        </div>

        <div class="may2-client-filters px-3 py-2 d-flex flex-wrap" style="gap:6px">
          <a class="btn btn-sm btn-{{ $actividadClientes === 'todos' ? 'primary' : 'outline-primary' }}" href="{{ route('ventas.mayoristas.index', array_merge(request()->except(['actividad_clientes','top_clientes','page']), ['desde'=>$desde, 'hasta'=>$hasta, 'actividad_clientes'=>'todos'])) }}#top-clientes">Todos los históricos ({{ $topClientes->count() }})</a>
          <a class="btn btn-sm btn-{{ $actividadClientes === 'periodo' ? 'primary' : 'outline-primary' }}" href="{{ route('ventas.mayoristas.index', array_merge(request()->except(['actividad_clientes','top_clientes','page']), ['desde'=>$desde, 'hasta'=>$hasta, 'actividad_clientes'=>'periodo'])) }}#top-clientes">Con movimientos en período ({{ $topClientes->where('con_movimientos_periodo', true)->count() }})</a>
          <a class="btn btn-sm btn-{{ $actividadClientes === 'sin_periodo' ? 'primary' : 'outline-primary' }}" href="{{ route('ventas.mayoristas.index', array_merge(request()->except(['actividad_clientes','top_clientes','page']), ['desde'=>$desde, 'hasta'=>$hasta, 'actividad_clientes'=>'sin_periodo'])) }}#top-clientes">Sin movimientos en período ({{ $topClientes->where('con_movimientos_periodo', false)->count() }})</a>
          <a class="btn btn-sm btn-{{ $actividadClientes === 'sin_recientes' ? 'primary' : 'outline-primary' }}" href="{{ route('ventas.mayoristas.index', array_merge(request()->except(['actividad_clientes','top_clientes','page']), ['desde'=>$desde, 'hasta'=>$hasta, 'actividad_clientes'=>'sin_recientes'])) }}#top-clientes">Sin compras recientes ({{ $topClientes->where('estado_frecuencia','SIN COMPRAS RECIENTES')->count() }})</a>
        </div>
        <div class="table-responsive may2-client-scroll">
          <table class="table table-sm table-hover may2-table may2-client-table mb-0">
            <thead><tr>
              <th class="text-center">#</th>
              <th>Sucursal</th>
              <th>Cliente</th>
              <th class="text-right">Unid. netas (período)</th>
              <th class="text-right">Tickets (período)</th>
              <th>Última factura</th>
              <th>Frecuencia de compra</th>
              <th class="text-right">Venta neta (período)</th>
              <th class="text-center">Acción</th>
            </tr></thead>
            <tbody>
              @forelse(request('top_clientes') === 'todos' ? $topClientesFiltrados : $topClientesFiltrados->take(20) as $clienteTop)
                <tr>
                  <td class="text-center text-muted">{{ $loop->iteration }}</td>
                  <td><span class="badge badge-light border">COMERCIAL MATRIZ</span></td>
                  <td>
                    <strong>{{ $clienteTop->nombre !== '' ? $clienteTop->nombre : 'SIN NOMBRE REGISTRADO' }}</strong>
                    <small class="d-block text-muted">
                      {{ $clienteTop->codigo !== '' ? 'Código: '.$clienteTop->codigo : 'Sin código de cliente' }}
                      @if($clienteTop->notas_credito > 0)
                        · {{ $clienteTop->notas_credito }} NC del período
                      @endif
                    </small>
                    @if(!$clienteTop->con_movimientos_periodo)
                      <small class="d-block may2-no-period"><i class="far fa-calendar-times mr-1"></i>Sin movimientos en el período</small>
                    @endif
                  </td>
                  <td class="text-right {{ $clienteTop->unidades < 0 ? 'text-danger' : '' }}">{{ number_format($clienteTop->unidades,0,',','.') }}</td>
                  <td class="text-right font-weight-bold">{{ number_format($clienteTop->tickets,0,',','.') }}<small class="d-block text-muted" title="Facturas históricas hasta el corte">{{ number_format($clienteTop->tickets_historicos,0,',','.') }} históricas</small></td>
                  <td class="may2-last-invoice">
                    @if($clienteTop->ultima_fecha)
                      <strong>{{ \Carbon\Carbon::parse($clienteTop->ultima_fecha)->format('d/m/Y') }}</strong>
                      <small class="d-block text-muted">
                        @if($clienteTop->ultima_clave !== '')
                          <a href="{{ route('ventas.mayoristas.factura', ['clave'=>$clienteTop->ultima_clave,'mes'=>substr($clienteTop->ultima_fecha,0,7),'desde'=>$desde,'hasta'=>$hasta]) }}" title="Abrir la última factura del cliente">{{ $clienteTop->ultimo_comprobante }}</a>
                        @else
                          {{ $clienteTop->ultimo_comprobante }}
                        @endif
                      </small>
                    @else
                      <span class="text-muted">Sin factura de venta</span>
                    @endif
                  </td>
                  <td>
                    <span class="may2-frequency may2-frequency-{{ $clienteTop->estado_frecuencia === 'RECURRENTE' ? 'regular' : ($clienteTop->estado_frecuencia === 'SIN COMPRAS RECIENTES' ? 'inactive' : 'occasional') }}">
                      @if($clienteTop->estado_frecuencia === 'RECURRENTE')
                        <i class="fas fa-sync-alt mr-1"></i> Recurrente
                      @elseif($clienteTop->estado_frecuencia === 'SIN COMPRAS RECIENTES')
                        <i class="fas fa-clock mr-1"></i> Sin compras recientes
                      @elseif($clienteTop->estado_frecuencia === 'OCASIONAL')
                        <i class="fas fa-user-clock mr-1"></i> Ocasional
                      @else
                        Sin compras
                      @endif
                    </span>
                    @if($clienteTop->dias_sin_compra !== null)
                      <small class="d-block may2-last-days">Hace {{ number_format($clienteTop->dias_sin_compra,0,',','.') }} días · {{ $clienteTop->fechas_compra_recientes }} días de compra en los últimos 90</small>
                    @endif
                  </td>
                  <td class="text-right font-weight-bold {{ $clienteTop->venta_neta < 0 ? 'text-danger' : 'text-primary' }}">Gs {{ number_format($clienteTop->venta_neta,0,',','.') }}</td>
                  <td class="text-center">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('ventas.mayoristas.cliente', ['cliente'=>$clienteTop->token,'desde'=>$desde,'hasta'=>$hasta]) }}" title="Ver todas las facturas y tickets del cliente">
                      <i class="fas fa-eye mr-1"></i>Ver
                    </a>
                  </td>
                </tr>
              @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No hay clientes para este filtro. Probá «Todos los históricos» o ampliá las fechas.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="card-footer bg-white small text-muted">
          <i class="fas fa-info-circle mr-1"></i>
          Los tickets se cuentan por fecha, número y tipo de comprobante de cada cliente. Cada factura cuenta una sola vez, aunque contenga varios códigos o talles.
          <strong>Frecuencia al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}:</strong>
          recurrente = compras en 2 o más fechas distintas dentro de los últimos 90 días;
          ocasional = compró recientemente sin repetir en otra fecha;
          sin compras recientes = 90 días o más sin factura de venta.
          La recurrencia toma todas las facturas importadas hasta el cierre del filtro, incluso las anteriores a la fecha inicial. Las notas de crédito no son compras.
          «Sin compras recientes» no confirma que el cliente haya dejado de comprar definitivamente.
          El número de la última factura abre su detalle y <strong>Ver</strong> muestra los comprobantes del cliente.
        </div>
      </div>
      @php
        // Regla comercial: FAMILY GROUP se atribuye a Casa Central.
        // Ninguna otra venta obtiene origen por simple prorrateo.
        $consultaOrigen = \App\Http\Controllers\VentaMayoristaController::ventas()
            ->whereBetween('v.fecha', [$desde, $hasta]);
        if ($clienteFiltro !== '') {
            $consultaOrigen->where(function ($q) use ($clienteCodigo, $clienteNombre) {
                if ($clienteNombre !== null) {
                    $q->whereRaw("BTRIM(COALESCE(v.cli_cod,'')) = ?", [$clienteCodigo])
                      ->whereRaw("BTRIM(COALESCE(v.cliente,'')) = ?", [$clienteNombre]);
                } else {
                    $q->whereRaw("BTRIM(COALESCE(v.cli_cod,'')) = ?", [$clienteCodigo])
                      ->orWhere(function ($sinCodigo) use ($clienteCodigo) {
                          $sinCodigo->whereRaw("BTRIM(COALESCE(v.cli_cod,'')) = ''")
                              ->whereRaw("BTRIM(COALESCE(v.cliente,'')) = ?", [$clienteCodigo]);
                      });
                }
            });
        }
        $familiaResumen = (clone $consultaOrigen)
            ->whereRaw("UPPER(BTRIM(COALESCE(v.cliente,''))) LIKE 'FAMILY GROUP%'")
            ->selectRaw("COALESCE(SUM(CASE WHEN v.cantidad > 0 THEN v.cantidad ELSE 0 END),0) as brutas")
            ->selectRaw("COALESCE(SUM(CASE WHEN v.cantidad < 0 THEN -v.cantidad ELSE 0 END),0) as devoluciones")
            ->first();
        $familiaBrutas = (int) ($familiaResumen->brutas ?? 0);
        $familiaDevoluciones = (int) ($familiaResumen->devoluciones ?? 0);
        $otrasBrutas = max(0, (int) $resumen->brutas - $familiaBrutas);
      @endphp
      {{-- <div class="card may2-card mb-3">
        <div class="card-header bg-white"><strong><i class="fas fa-chart-pie mr-1 text-primary"></i>Origen comercial de ventas (reglas declaradas)</strong></div>
        <div class="card-body">
          <p class="small text-muted mb-3">Las ventas de FAMILY GROUP se clasifican como provenientes de Casa Central → Matriz, según la regla comercial indicada. Las ventas de otros clientes quedan sin origen determinado hasta contar con otra regla o evidencia. Este informe no asocia facturas a remisiones ni modifica registros.</p>
          <div class="row">
            <div class="col-md-4 mb-2"><small class="text-muted d-block">FAMILY GROUP · Casa Central (regla)</small><strong>{{ number_format($familiaBrutas,0,',','.') }}</strong><small class="text-muted d-block">NC del cliente: {{ number_format($familiaDevoluciones,0,',','.') }} · Neto: {{ number_format($familiaBrutas - $familiaDevoluciones,0,',','.') }}</small></div>
            <div class="col-md-4 mb-2"><small class="text-muted d-block">Otros clientes · Origen no determinado</small><strong>{{ number_format($otrasBrutas,0,',','.') }}</strong></div>
            <div class="col-md-4 mb-2"><small class="text-muted d-block">Total ventas brutas</small><strong>{{ number_format($familiaBrutas + $otrasBrutas,0,',','.') }}</strong></div>
          </div>
          <small class="text-muted">Las transferencias documentadas ({{ number_format($transferenciasResumen->remitidas,0,',','.') }} unidades) no se restan de las ventas como si fueran inventario disponible: pueden corresponder a fechas y operaciones distintas.</small>
        </div>
      </div> --}}
      <div class="card may2-card mb-3">
        <div class="card-header bg-white"><strong><i class="fas fa-exchange-alt mr-1 text-primary"></i>1. Asignaciones de Logística hacia COMERCIAL MATRIZ</strong><small class="d-block text-muted">Seleccioná una sucursal para consultar sus números de remisión y cantidades; después presioná <strong>Ver</strong> para consultar los códigos y talles de cada documento. Destino: código 25 / MATRIZ.</small></div>
        <div class="row no-gutters may2-transfer-kpis">
          <div class="col-md-4"><div class="p-3 border-right"><small class="text-muted d-block">Desde locales → Matriz</small><strong>{{ number_format($transferenciasResumen->locales,0,',','.') }} unidades</strong></div></div>
          <div class="col-md-4"><div class="p-3 border-right"><small class="text-muted d-block">Desde Casa Central → Matriz</small><strong>{{ number_format($transferenciasResumen->central,0,',','.') }} unidades</strong></div></div>
          <div class="col-md-4"><div class="p-3"><small class="text-muted d-block">Con recepción registrada (todos los orígenes)</small><strong>{{ number_format($transferenciasResumen->recibidas,0,',','.') }} de {{ number_format($transferenciasResumen->remitidas,0,',','.') }}</strong></div></div>
        </div>
        @if($transferenciasResumen->sin_origen > 0)<div class="alert alert-warning mx-3 mt-2 mb-1 small">{{ number_format($transferenciasResumen->sin_origen,0,',','.') }} unidades no tienen origen identificable en los datos importados. Se mantienen aparte; no se atribuyen a Central.</div>@endif
        <div class="table-responsive"><table class="table table-sm table-hover may2-table mb-0"><thead><tr><th>Sucursal de origen real</th><th>Tipo</th><th class="text-right">Documentos</th><th class="text-right">Remitidas</th><th class="text-right">Con recepción</th></tr></thead><tbody>
        @forelse($transferenciasOrigen as $origen)<tr><td class="font-weight-bold"><a class="may2-sucursal-link" href="{{ route('ventas.mayoristas.sucursal',['codigo'=>$origen->codigo_origen ?: null,'nombre'=>$origen->nombre_importado ?: null,'sin_origen'=>!$origen->codigo_origen && !$origen->nombre_importado ? '1' : null,'desde'=>$desde,'hasta'=>$hasta]) }}"><i class="fas fa-folder-open mr-1"></i>{{ $origen->nombre }}<i class="fas fa-chevron-right ml-1 small"></i></a></td><td><span class="badge badge-{{ $origen->tipo==='LOCAL'?'info':($origen->tipo==='CENTRAL'?'secondary':'warning') }}">{{ $origen->tipo }}</span></td><td class="text-right">{{ number_format($origen->documentos,0,',','.') }}</td><td class="text-right">{{ number_format($origen->unidades,0,',','.') }}</td><td class="text-right">{{ number_format($origen->recibidas,0,',','.') }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-3">No se encontraron remisiones con destino 25 / COMERCIAL MATRIZ emitidas entre {{ $desde }} y {{ $hasta }}. Verificá el período importado.</td></tr>@endforelse
        </tbody></table></div>
        <details class="may2-subdetails border-top"><summary class="px-3 py-2 font-weight-bold">Ver números de remisión, origen y cantidades por documento</summary><div class="table-responsive"><table class="table table-sm may2-table mb-0"><thead><tr><th>Fecha</th><th>Serie / Nº remisión</th><th>Origen</th><th>Destino</th><th class="text-right">Líneas</th><th class="text-right">Remitidas</th><th>Recepción</th><th></th></tr></thead><tbody>
        @forelse($remisionesDocumentos as $doc)<tr><td>{{ date('d/m/Y',strtotime($doc->fecha_remision)) }}</td><td class="font-weight-bold">{{ $doc->serie }}-{{ $doc->numero_remision }}</td><td>{{ $doc->origen_real }}<small class="d-block text-muted">{{ $doc->tipo_origen }} · origen #{{ $doc->cod_sucursal_salida ?: 'sin código' }}</small>@if($doc->origen_discordante)<small class="d-block text-warning"><i class="fas fa-exclamation-triangle"></i> Revisar origen: el nombre importado dice «{{ $doc->sucursal_salida }}»</small>@endif</td><td>COMERCIAL MATRIZ</td><td class="text-right">{{ number_format($doc->lineas,0,',','.') }}</td><td class="text-right">{{ number_format($doc->unidades,0,',','.') }}</td><td>{{ $doc->lineas_recibidas==$doc->lineas ? 'Recibida':'Parcial / sin confirmar' }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('ventas.mayoristas.remision',['id'=>$doc->remision_id,'desde'=>$desde,'hasta'=>$hasta]) }}">Ver</a></td></tr>@empty<tr><td colspan="8" class="text-center text-muted">Sin documentos en el período.</td></tr>@endforelse
        </tbody></table></div><div class="card-footer bg-white">{{ $remisionesDocumentos->appends(['cliente'=>$clienteFiltro])->links() }}</div></details>
        <div class="card-footer bg-white text-muted small"><strong>Importante:</strong> las cantidades por sucursal y remisión son transferencias reales hacia MATRIZ. No prueban a qué factura pertenece cada prenda ni representan stock físico disponible.</div>
      </div>
      <div class="card may2-card mb-3"><div class="card-header bg-white"><strong><i class="fas fa-receipt mr-1 text-primary"></i>2. Facturas / comprobantes de COMERCIAL MATRIZ</strong><small class="d-block text-muted">Comprobantes reales del período. Consultá las cantidades y los importes; no se efectúa conciliación documental.</small></div><div class="table-responsive"><table class="table table-sm table-hover may2-table mb-0"><thead><tr><th>Fecha</th><th>Comprobante</th><th>Cliente</th><th>Tipo</th><th class="text-right">Unidades brutas</th><th class="text-right">Neto unidades</th><th class="text-right">Gs netos</th></tr></thead><tbody>
        @forelse($facturas as $f)<tr><td>{{ date('d/m/Y',strtotime($f->fecha)) }}</td><td class="font-weight-bold">{{ $f->comprobante }}</td><td>{{ $f->cliente ?: $f->cli_cod }}</td><td>{{ $f->tipo_comprobante }}</td><td class="text-right">{{ number_format($f->brutas,0,',','.') }}</td><td class="text-right">{{ number_format($f->netas,0,',','.') }}</td><td class="text-right">Gs {{ number_format($f->pvta,0,',','.') }}</td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-3">No hay comprobantes de COMERCIAL MATRIZ para el cliente y período seleccionados.</td></tr>@endforelse
        </tbody></table></div></div>
      <details class="card may2-card mb-3"><summary class="card-header bg-white may2-summary"><strong><i class="fas fa-list mr-1 text-primary"></i>3. Todas las líneas vendidas (detalle de cada talle)</strong><small class="d-block text-muted">Fecha, factura, cliente, vendedor, código completo, descripción, unidades y montos. Se muestran 100 líneas por página; exportación incluye todos los registros.</small></summary><div class="table-responsive" style="max-height:570px;overflow:auto"><table class="table table-sm table-striped may2-table mb-0"><thead><tr><th>Fecha</th><th>Factura</th><th>Tipo</th><th>Cliente / código</th><th>Vendedor</th><th>Código completo</th><th>Descripción</th><th class="text-right">Cant.</th><th class="text-right">Lista Gs</th><th class="text-right">Descuento Gs</th><th class="text-right">Venta Gs</th></tr></thead><tbody>
        @foreach($ventasDetalle as $v)<tr><td>{{ date('d/m/Y',strtotime($v->fecha)) }}</td><td>{{ $v->comprobante }}</td><td>{{ $v->tipo_comprobante }}</td><td>{{ $v->cliente }}<small class="d-block text-muted">{{ $v->cli_cod }}</small></td><td>{{ $v->vendedor }}</td><td class="font-weight-bold">{{ $v->codigo }}</td><td>{{ $v->descripcion }}</td><td class="text-right">{{ number_format($v->cantidad,0,',','.') }}</td><td class="text-right">{{ number_format($v->p_lista,2,',','.') }}</td><td class="text-right">{{ number_format($v->descuento,2,',','.') }}</td><td class="text-right">{{ number_format($v->p_venta,2,',','.') }}</td></tr>@endforeach
        </tbody></table></div><div class="card-footer bg-white">{{ $ventasDetalle->appends(['cliente'=>$clienteFiltro])->links() }}</div></details>
      <details class="card may2-card mb-4"><summary class="card-header bg-white may2-summary"><strong><i class="fas fa-barcode mr-1 text-primary"></i>4. Totales vendidos por código y talle</strong><small class="d-block text-muted">Consolidado de unidades y montos de COMERCIAL MATRIZ. Los totales son ventas reales; la distribución por origen mostrada arriba es solo una estimación.</small></summary><div class="table-responsive" style="max-height:480px;overflow:auto"><table class="table table-sm may2-table mb-0"><thead><tr><th>Código completo</th><th>Artículo</th><th class="text-right">Brutas</th><th class="text-right">NC / devoluciones</th><th class="text-right">Netas</th><th class="text-right">Importe neto Gs</th></tr></thead><tbody>@foreach($porCodigo as $c)<tr><td class="font-weight-bold">{{ $c->codigo }}</td><td>{{ $c->descripcion }}</td><td class="text-right">{{ number_format($c->ventas_brutas,0,',','.') }}</td><td class="text-right">{{ number_format($c->devoluciones,0,',','.') }}</td><td class="text-right">{{ number_format($c->ventas_netas,0,',','.') }}</td><td class="text-right">{{ number_format($c->total_neto,2,',','.') }}</td></tr>@endforeach</tbody></table></div></details>
    @endif
  </div>
</section>
@endsection
@push('page_css')
<style>
.mayor-matriz .may2-card{border:1px solid #dee7f0;box-shadow:0 3px 10px rgba(25,45,75,.04);border-radius:9px;overflow:hidden}
.mayor-matriz .may2-client-ranking{border-color:#d6e5f6}
.mayor-matriz .may2-client-ranking .card-header strong{font-size:1.02rem;color:#214671}
.mayor-matriz .may2-clients-hint{font-size:.78rem;color:#536c89;background:#f5f9fe;border-bottom:1px solid #e8eef7}
.mayor-matriz .may2-client-scroll{max-height:600px;overflow:auto}
.mayor-matriz .may2-client-table td{padding:11px 10px}
.mayor-matriz .may2-client-table tbody tr:nth-child(even){background:#fafcff}
.mayor-matriz .may2-client-table tbody tr:hover{background:#edf5ff}
.mayor-matriz .may2-client-table .badge{font-size:.71rem;font-weight:700;color:#60738d}
.mayor-matriz .may2-client-table .btn{white-space:nowrap}
.mayor-matriz .may2-client-filters{background:#fff;border-bottom:1px solid #e0eaf6}
.mayor-matriz .may2-client-filters .btn{font-weight:700}
.mayor-matriz .may2-no-period{color:#a06423;font-weight:650;margin-top:4px}
.mayor-matriz .may2-client-table th{position:sticky;top:0;z-index:2}
.mayor-matriz .may2-last-invoice{white-space:nowrap}
.mayor-matriz .may2-last-invoice strong{color:#234d7e}
.mayor-matriz .may2-last-invoice a{color:#1f61af;font-weight:650}
.mayor-matriz .may2-frequency{display:inline-flex;align-items:center;border-radius:6px;padding:5px 8px;white-space:nowrap;font-size:.7rem;font-weight:800}
.mayor-matriz .may2-frequency-regular{background:#e1f7e8;color:#12663a;border:1px solid #9cddaf}
.mayor-matriz .may2-frequency-occasional{background:#ebf3ff;color:#2457a0;border:1px solid #bed5f7}
.mayor-matriz .may2-frequency-inactive{background:#fff0d8;color:#8c5410;border:1px solid #f2d397}
.mayor-matriz .may2-last-days{color:#647d99;font-size:.7rem;white-space:normal;min-width:145px;margin-top:5px}

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
