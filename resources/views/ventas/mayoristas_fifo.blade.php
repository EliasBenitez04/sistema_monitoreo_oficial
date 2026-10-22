@extends('layouts.app')
@section('content')
<section class="content"><div class="container-fluid py-3">
<h2>Conciliación masiva FIFO · Comercial Matriz</h2>
<p class="text-muted">Propuesta documental por código completo, remisiones de Casa Central y locales, incluso si la remisión o recepción es posterior a la factura. FIFO documental por código y saldo, sin demostrar disponibilidad física en la fecha de venta. No acredita stock físico ni demuestra procedencia individual. Sin modificaciones hasta aprobar.</p>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="GET" action="{{ route('ventas.mayoristas.fifo') }}" class="form-inline mb-3">
<label class="mr-2">Desde</label><input type="date" name="desde" class="form-control mr-3" value="{{ $desde }}" required>
<label class="mr-2">Hasta</label><input type="date" name="hasta" class="form-control mr-3" value="{{ $hasta }}" required>
<button class="btn btn-primary">Calcular propuesta</button>
<a href="{{ route('ventas.mayoristas.index',['desde'=>$desde,'hasta'=>$hasta]) }}" class="btn btn-outline-secondary ml-2">Volver</a>
</form>
<div class="row mb-3">
<div class="col-md-4"><div class="card card-body"><small>Facturas analizadas</small><h3>{{ number_format($resultado['facturas'],0,',','.') }}</h3></div></div>
<div class="col-md-4"><div class="card card-body"><small>Prendas propuestas</small><h3 class="text-success">{{ number_format($resultado['filas']->sum('cantidad'),0,',','.') }}</h3></div></div>
<div class="col-md-4"><div class="card card-body"><small>Excepciones sin cubrir</small><h3 class="text-warning">{{ number_format($resultado['excepciones']->sum('cantidad'),0,',','.') }}</h3></div></div>
</div>
@if($resultado['filas']->isNotEmpty())
<div class="card mb-3"><div class="card-header"><strong>Vista previa FIFO</strong> · {{ $resultado['filas']->count() }} asociaciones propuestas</div>
<div class="table-responsive" style="max-height:500px;overflow:auto"><table class="table table-sm table-striped">
<thead><tr><th>Factura</th><th>Fecha factura</th><th>Código completo</th><th>Remisión</th><th>Origen</th><th>Emisión</th><th>Recepción</th><th class="text-right">Unidades</th></tr></thead><tbody>
@foreach($resultado['filas'] as $fila)
<tr><td>{{ $fila->comprobante }}</td><td>{{ $fila->fecha_factura }}</td><td>{{ $fila->codigo }}</td>
<td>{{ $fila->serie }} {{ $fila->numero }}</td><td>{{ $fila->origen }}</td><td>{{ $fila->fecha_remision }}</td><td>{{ $fila->fecha_recepcion }} @if($fila->posterior)<span class="badge badge-warning">Posterior a la factura</span>@endif</td><td class="text-right">{{ $fila->cantidad }}</td></tr>
@endforeach
</tbody></table></div></div>
<form method="POST" action="{{ route('ventas.mayoristas.fifo.aprobar') }}" onsubmit="return confirm('¿Aprobar masivamente las asociaciones FIFO? El sistema recalculará saldos antes de guardar.');" class="card card-body mb-3">
@csrf<input type="hidden" name="desde" value="{{ $desde }}"><input type="hidden" name="hasta" value="{{ $hasta }}">
<label>Referencia / responsable de aprobación</label><input name="referencia" maxlength="255" minlength="8" class="form-control mb-2" required placeholder="Ej.: Conciliación revisada contra movimientos de Matriz">
<button class="btn btn-success" type="submit">Aprobar asociaciones FIFO y registrar auditoría</button>
</form>
@endif
<div class="card"><div class="card-header"><strong>Excepciones pendientes de investigación</strong></div><div class="table-responsive" style="max-height:400px;overflow:auto"><table class="table table-sm">
<thead><tr><th>Factura</th><th>Fecha</th><th>Código</th><th>Sin cubrir</th><th>Motivo</th></tr></thead><tbody>
@forelse($resultado['excepciones'] as $fila)
<tr><td>{{ $fila->comprobante }}</td><td>{{ $fila->fecha }}</td><td>{{ $fila->codigo }}</td><td>{{ $fila->cantidad }}</td><td>{{ $fila->motivo }}</td></tr>
@empty<tr><td colspan="5">No hay excepciones en el período.</td></tr>@endforelse
</tbody></table></div></div>
</div></section>
@endsection
