@extends('layouts.app')
@section('content')
<section class="content-header"><div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
<div><h1>Administrar pedido {{ $pedido->nro_pedido }}</h1><p class="text-muted">Editar número de pedido, reemplazar o anular una OT vinculada.</p></div>
<a href="{{ route('pedidos.administrar.index') }}" class="btn btn-outline-secondary">Volver al listado</a></div></section>
<section class="content"><div class="container-fluid">
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><strong>No se realizó ningún cambio:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="card"><div class="card-header"><h3 class="card-title">Modificar número del pedido</h3></div>
<div class="card-body"><form method="POST" action="{{ route('pedidos.administrar.renombrar',$pedido->id) }}">
@csrf<div class="row align-items-end">
<div class="col-md-3"><label>Nuevo número de pedido</label><input required class="form-control" name="nro_pedido" value="{{ old('nro_pedido',$pedido->nro_pedido) }}"></div>
<div class="col-md-6"><label>Motivo del cambio (obligatorio)</label><input required minlength="5" maxlength="1000" class="form-control" name="motivo" placeholder="Ej.: corrección de numeración"></div>
<div class="col-md-3"><button class="btn btn-primary btn-block" onclick="return confirm('¿Confirmás el cambio de número de pedido?')">Guardar cambio</button></div></div></form></div></div>
<div class="card"><div class="card-header"><h3 class="card-title">OT vinculadas ({{ $ots->count() }})</h3></div>
<div class="table-responsive"><table class="table table-striped table-hover mb-0">
<thead><tr><th>Nº OT</th><th>Código</th><th>Descripción</th><th>Cantidad</th><th>Gestionar OT</th></tr></thead>
<tbody>@forelse($ots as $ot)
<tr><td><strong>{{ $ot->nro_ot }}</strong></td><td>{{ $ot->codigo }}</td><td>{{ $ot->descripcion }}</td><td>{{ number_format($ot->cantidad_orden,0,',','.') }}</td>
<td style="min-width:340px">
<form method="POST" action="{{ route('pedidos.administrar.ot',[$pedido->id,$ot->id]) }}" onsubmit="return confirm('¿Confirmás esta modificación? Quedará registrada en el historial.')">
@csrf
<div class="form-row">
<div class="col-md-4 mb-1"><select name="accion" class="form-control form-control-sm accion-ot"><option value="REEMPLAZAR">Reemplazar OT</option><option value="ANULAR">Anular vínculo</option></select></div>
<div class="col-md-4 mb-1"><input name="nro_ot" class="form-control form-control-sm" placeholder="Nueva Nº OT" title="Obligatorio al reemplazar"></div>
<div class="col-md-4 mb-1"><button class="btn btn-sm btn-warning btn-block" type="submit">Aplicar cambio</button></div>
</div><input name="motivo" required minlength="5" maxlength="1000" class="form-control form-control-sm" placeholder="Motivo obligatorio (mín. 5 caracteres)">
</form></td></tr>
@empty<tr><td colspan="5" class="text-center text-muted">Este pedido no tiene OT vinculadas actualmente.</td></tr>@endforelse
</tbody></table></div></div>
<div class="card"><div class="card-header"><h3 class="card-title">Historial de modificaciones (últimas 50)</h3></div>
<div class="table-responsive"><table class="table table-sm mb-0">
<thead><tr><th>Fecha</th><th>Acción</th><th>Pedido anterior → nuevo</th><th>OT anterior → nueva</th><th>Motivo</th><th>Usuario ID</th></tr></thead>
<tbody>@forelse($historial as $cambio)<tr><td>{{ $cambio->created_at }}</td><td>{{ $cambio->accion }}</td><td>{{ $cambio->pedido_anterior }} → {{ $cambio->pedido_nuevo }}</td><td>{{ $cambio->ot_anterior ?: '-' }} → {{ $cambio->ot_nueva ?: '(anulada)' }}</td><td>{{ $cambio->motivo }}</td><td>{{ $cambio->usuario_id ?: '-' }}</td></tr>
@empty<tr><td colspan="6" class="text-center text-muted">Todavía no existen modificaciones registradas.</td></tr>@endforelse
</tbody></table></div></div>
<div class="alert alert-info"><strong>Importante:</strong> anular una OT aquí la desvincula del pedido, pero no borra la orden de producción ni su trazabilidad. Reemplazar una OT actualiza el vínculo y deja registro de cuál fue reemplazada.</div>
</div></section>
@endsection
