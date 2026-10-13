@extends('layouts.app')
@section('content')
<section class="content-header"><div class="container-fluid d-flex justify-content-between align-items-center">
    <div><h1>Administrar pedidos y OT</h1><p class="text-muted">Consultar pedidos P, T e IT y gestionar sus OT vinculadas.</p></div>
    <a href="{{ route('pedidos.index') }}" class="btn btn-outline-secondary">Volver a pedidos</a>
</div></section>
<section class="content"><div class="container-fluid">
<div class="card">
<div class="card-header"><form class="form-inline" method="GET">
<input class="form-control mr-2" type="search" name="buscar" value="{{ $buscar }}" placeholder="Nº de pedido o Nº de OT">
<button class="btn btn-primary" type="submit">Buscar</button></form></div>
<div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Pedido</th><th>Fecha</th><th>OT vinculadas</th><th>Acción</th></tr></thead><tbody>
@forelse($pedidos as $pedido)
<tr><td><strong>{{ $pedido->nro_pedido }}</strong></td>
<td>{{ $pedido->fecha_pedido ? date('d/m/Y', strtotime($pedido->fecha_pedido)) : '-' }}</td>
<td>{{ number_format($pedido->total_ot,0,',','.') }}</td>
<td><a class="btn btn-sm btn-outline-primary" href="{{ route('pedidos.administrar.show',$pedido->id) }}">Ver y administrar OT</a></td></tr>
@empty<tr><td colspan="4" class="text-center text-muted">No hay pedidos que coincidan.</td></tr>@endforelse
</tbody></table></div><div class="card-footer">{{ $pedidos->links() }}</div></div>
</div></section>
@endsection
