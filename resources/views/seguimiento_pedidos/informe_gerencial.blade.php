@extends('layouts.app')
@section('content')
<section class="content-header"><div class="container-fluid"><div class="d-flex justify-content-between align-items-center"><div><h1><i class="fas fa-briefcase mr-2"></i>Informe Gerencial de Pedidos</h1><p class="text-muted mb-0">Pendientes que requieren seguimiento en Terminación y Logística.</p></div><a href="{{ route('seguimiento-pedidos.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i> Seguimiento</a></div></div></section>
<section class="content"><div class="container-fluid">
<div class="row">
<div class="col-lg-3 col-6"><div class="small-box bg-white border-left border-danger shadow-sm"><div class="inner"><h3>{{ $resumen->urgentes }}</h3><p>OT urgentes</p></div><div class="icon"><i class="fas fa-exclamation-triangle text-danger"></i></div></div></div>
<div class="col-lg-3 col-6"><div class="small-box bg-white border-left border-primary shadow-sm"><div class="inner"><h3>{{ $resumen->pedidos }}</h3><p>Pedidos con pendientes</p></div><div class="icon"><i class="fas fa-clipboard-list text-primary"></i></div></div></div>
<div class="col-lg-3 col-6"><div class="small-box bg-white border-left border-warning shadow-sm"><div class="inner"><h3>{{ $resumen->en_terminacion }}</h3><p>OT en Terminación</p></div><div class="icon"><i class="fas fa-industry text-warning"></i></div></div></div>
<div class="col-lg-3 col-6"><div class="small-box bg-white border-left border-info shadow-sm"><div class="inner"><h3>{{ $resumen->en_logistica }}</h3><p>OT en Logística</p></div><div class="icon"><i class="fas fa-truck-loading text-info"></i></div></div></div>
</div>
<div class="alert alert-light border shadow-sm"><strong>{{ number_format($resumen->prendas,0,',','.') }} prendas pendientes</strong> en {{ $resumen->ots }} OT. <span class="text-danger ml-2"><i class="fas fa-circle mr-1"></i>URGENTE</span> = 2 días o más en la etapa actual.</div>
<div class="card card-outline card-danger"><div class="card-header"><h3 class="card-title"><i class="fas fa-bullseye mr-2"></i>Prioridades para decisión</h3><span class="float-right text-muted small">Ordenado por urgencia y antigüedad</span></div>
<div class="table-responsive"><table class="table table-hover table-sm mb-0 text-center informe-gerencial"><thead><tr><th>Prioridad</th><th>Pedido</th><th>Fecha pedido</th><th>OT</th><th>Cantidad</th><th class="text-left">Descripción</th><th>Etapa actual</th><th>Desde</th><th>Días en etapa</th></tr></thead><tbody>
@forelse($pendientes as $fila)
@php $fechaEtapa=$fila->etapa_gerencial==='LOGISTICA'?$fila->fecha_logistica:($fila->etapa_gerencial==='TERMINACION'?$fila->fecha_terminacion:$fila->fecha_pedido); @endphp
<tr class="{{ $fila->urgente ? 'fila-urgente' : '' }}">
<td>@if($fila->urgente)<span class="badge badge-danger px-2 py-2"><i class="fas fa-exclamation-triangle mr-1"></i>URGENTE</span>@else<span class="badge badge-secondary">NORMAL</span>@endif</td>
<td><strong>{{ $fila->nro_pedido }}</strong></td><td>{{ $fila->fecha_pedido ? date('d/m/Y',strtotime($fila->fecha_pedido)) : '-' }}</td><td><strong>{{ $fila->nro_ot }}</strong></td><td><strong>{{ number_format($fila->cantidad_orden,0,',','.') }}</strong></td><td class="text-left">{{ $fila->descripcion ?: '-' }}</td>
<td>@if($fila->etapa_gerencial==='LOGISTICA')<span class="badge badge-info">LOGÍSTICA</span>@elseif($fila->etapa_gerencial==='TERMINACION')<span class="badge badge-warning">TERMINACIÓN</span>@else<span class="badge badge-secondary">SIN INICIAR</span>@endif</td>
<td>{{ $fechaEtapa ? date('d/m/Y',strtotime($fechaEtapa)) : '-' }}</td><td>@if($fila->dias_etapa!==null)<strong class="{{ $fila->urgente ? 'text-danger' : '' }}">{{ $fila->dias_etapa }} días</strong>@else-@endif</td>
</tr>
@empty<tr><td colspan="9" class="text-success py-5"><i class="fas fa-check-circle mr-2"></i>No hay OT pendientes.</td></tr>@endforelse
</tbody></table></div></div>
</div></section>
@endsection
@push('page_css')<style>.informe-gerencial th{font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;background:#f8fafc;color:#6c757d;white-space:nowrap;vertical-align:middle!important}.informe-gerencial td{vertical-align:middle!important}.fila-urgente{background:#fff5f5}.small-box.bg-white .icon{top:8px;font-size:46px;opacity:.15}.border-left{border-left-width:4px!important}</style>@endpush