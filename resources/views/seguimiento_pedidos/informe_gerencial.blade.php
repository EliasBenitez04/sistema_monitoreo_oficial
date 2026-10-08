@extends('layouts.app')
@section('content')
<section class="content-header"><div class="container-fluid"><div class="d-flex justify-content-between align-items-center"><div><h1><i class="fas fa-briefcase mr-2"></i>Informe Gerencial de Terminación</h1><p class="text-muted mb-0">Pedidos T: pendientes que requieren seguimiento desde Terminación hasta la confirmación local.</p></div><div><a href="{{ route('pedidos.index') }}" class="btn btn-dark mr-1"><i class="fas fa-th-large mr-1"></i> PEDIDOS</a><a href="{{ route('seguimiento-terminacion.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i> Terminación</a></div></div></div></section>
<section class="content"><div class="container-fluid">
<div class="row">
<div class="col-lg-3 col-6"><div class="small-box bg-white border-left border-danger shadow-sm"><div class="inner"><h3>{{ $resumen->urgentes }}</h3><p>OT con saldo urgente</p><small class="text-muted">{{ number_format($resumen->prendas_urgentes,0,',','.') }} prendas pendientes reales</small></div><div class="icon"><i class="fas fa-exclamation-triangle text-danger"></i></div></div></div>
<div class="col-lg-3 col-6"><div class="small-box bg-white border-left border-primary shadow-sm"><div class="inner"><h3>{{ $resumen->pedidos }}</h3><p>Pedidos con pendientes</p></div><div class="icon"><i class="fas fa-clipboard-list text-primary"></i></div></div></div>
<div class="col-lg-3 col-6"><div class="small-box bg-white border-left border-warning shadow-sm"><div class="inner"><h3>{{ $resumen->en_terminacion }}</h3><p>OT en Terminación</p></div><div class="icon"><i class="fas fa-industry text-warning"></i></div></div></div>
<div class="col-lg-3 col-6"><div class="small-box bg-white border-left border-info shadow-sm"><div class="inner"><h3>{{ $resumen->en_logistica + $resumen->recepcion_parcial }}</h3><p>OT en Logística / Recepción</p><small class="text-muted">{{ $resumen->recepcion_parcial }} con recepción parcial</small></div><div class="icon"><i class="fas fa-truck-loading text-info"></i></div></div></div>
</div>
<div class="alert alert-light border shadow-sm"><strong>{{ number_format($resumen->prendas,0,',','.') }} prendas de saldo pendiente real</strong> en {{ $resumen->ots }} OT con diferencia. <span class="text-danger ml-2"><i class="fas fa-circle mr-1"></i>URGENTE</span> = 2 días o más desde la fecha del pedido y saldo pendiente mayor a 0. <span class="text-muted ml-2">La cantidad original de la OT ya no se usa como pendiente.</span></div>
<div class="card card-outline card-danger"><div class="card-header d-flex justify-content-between align-items-center flex-wrap"><div><h3 class="card-title float-none mb-0"><i class="fas fa-bullseye mr-2"></i>Prioridades para decisión</h3><small class="text-muted">Ordenado por urgencia y antigüedad</small></div><a href="{{ route('seguimiento-terminacion.informe-gerencial.excel') }}" class="btn btn-success btn-sm shadow-sm"><i class="fas fa-file-excel mr-1"></i> Exportar Excel</a></div>
<div class="table-responsive"><table class="table table-hover table-sm mb-0 text-center informe-gerencial"><thead><tr><th>Prioridad</th><th>Pedido</th><th>Fecha pedido</th><th>OT</th><th>Saldo pendiente</th><th class="text-left">Descripción</th><th>Etapa actual</th><th>Fecha proceso actual</th><th>Días desde pedido</th></tr></thead><tbody>
@forelse($pendientes as $fila)

<tr class="{{ $fila->urgente ? 'fila-urgente' : '' }}">
<td>@if($fila->urgente)<span class="badge badge-danger px-2 py-2"><i class="fas fa-exclamation-triangle mr-1"></i>URGENTE</span>@else<span class="badge badge-secondary">NORMAL</span>@endif</td>
<td><strong>{{ $fila->nro_pedido }}</strong></td><td>{{ $fila->fecha_pedido ? date('d/m/Y',strtotime($fila->fecha_pedido)) : '-' }}</td><td><strong>{{ $fila->nro_ot }}</strong></td><td><strong class="{{ $fila->saldo_pendiente > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($fila->saldo_pendiente,0,',','.') }}</strong>@if($fila->falta_terminacion_real > 0)<small class="d-block text-muted">Falta Term. {{ number_format($fila->falta_terminacion_real,0,',','.') }}</small>@endif@if($fila->producto_terminado_real > $fila->recibido_real)<small class="d-block text-muted">PT no recibido {{ number_format($fila->producto_terminado_real - $fila->recibido_real,0,',','.') }}</small>@endif</td><td class="text-left">{{ $fila->descripcion ?: '-' }}</td>
<td>@if($fila->etapa_gerencial==='RECEPCION PARCIAL')<span class="badge badge-primary">RECEPCIÓN PARCIAL</span>@elseif($fila->etapa_gerencial==='LOGISTICA')<span class="badge badge-info">LOGÍSTICA</span>@elseif($fila->etapa_gerencial==='TERMINACION')<span class="badge badge-warning">TERMINACIÓN</span>@else<span class="badge badge-secondary">SIN INICIAR</span>@endif</td>
<td>
@if($fila->etapa_gerencial==='RECEPCION PARCIAL' && $fila->fecha_recepcion)
{{ date('d/m/Y',strtotime($fila->fecha_recepcion)) }}
@elseif($fila->etapa_gerencial==='LOGISTICA' && $fila->fecha_logistica)
{{ date('d/m/Y',strtotime($fila->fecha_logistica)) }}
@elseif($fila->etapa_gerencial==='TERMINACION' && $fila->fecha_terminacion)
{{ date('d/m/Y',strtotime($fila->fecha_terminacion)) }}
@else
-
@endif
</td><td>@if($fila->dias_etapa!==null)<strong class="{{ $fila->urgente ? 'text-danger' : '' }}">{{ $fila->dias_etapa }} días</strong>@else-@endif</td>
</tr>
@empty<tr><td colspan="9" class="text-success py-5"><i class="fas fa-check-circle mr-2"></i>No hay OT pendientes.</td></tr>@endforelse
</tbody></table></div></div>
</div></section>
@endsection
@push('page_css')<style>.informe-gerencial th{font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;background:#f8fafc;color:#6c757d;white-space:nowrap;vertical-align:middle!important}.informe-gerencial td{vertical-align:middle!important}.fila-urgente{background:#fff5f5}.small-box.bg-white .icon{top:8px;font-size:46px;opacity:.15}.border-left{border-left-width:4px!important}</style>@endpush