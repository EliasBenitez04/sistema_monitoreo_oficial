@extends('layouts.app')

@section('content')
<section class="content-header">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1><i class="fas fa-industry mr-2"></i>Pedido {{ $pedido->nro_pedido }}</h1>
            <p class="text-muted mb-0">Seguimiento desde la fecha del pedido hasta TERMINACION - INGRESO TERMINACION.</p>
        </div>
        <a href="{{ route('seguimiento-produccion.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
    </div>
</div>
</section>

<section class="content"><div class="container-fluid">
<div class="row">
    <div class="col-lg-3 col-6"><div class="small-box bg-light border"><div class="inner"><h4>{{ $resumen->ots }}</h4><p>OT</p></div><div class="icon"><i class="fas fa-list-ol"></i></div></div></div>
    <div class="col-lg-3 col-6"><div class="small-box bg-light border"><div class="inner"><h4>{{ number_format($resumen->cantidad,0,',','.') }}</h4><p>Cantidad orden</p></div><div class="icon"><i class="fas fa-boxes"></i></div></div></div>
    <div class="col-lg-3 col-6"><div class="small-box bg-light border"><div class="inner"><h4>{{ $resumen->completas }}/{{ $resumen->ots }}</h4><p>OT en Terminación</p></div><div class="icon"><i class="fas fa-check-circle"></i></div></div></div>
    <div class="col-lg-3 col-6"><div class="small-box {{ $resumen->completo ? 'bg-success' : 'bg-warning' }}"><div class="inner"><h4>{{ $resumen->porcentaje }}%</h4><p>{{ $resumen->completo ? 'COMPLETO' : 'EN PROCESO' }}</p></div><div class="icon"><i class="fas fa-industry"></i></div></div></div>
</div>

<div class="card card-outline card-info mb-4">
    <div class="card-header text-center"><h3 class="card-title float-none mb-0"><i class="fas fa-stopwatch mr-2"></i>Tiempo de atención a Producción</h3></div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-4 mb-2"><small class="text-muted d-block text-uppercase">Fecha pedido</small><strong>{{ $pedido->fecha_pedido ? $pedido->fecha_pedido->format('d/m/Y') : '-' }}</strong></div>
            <div class="col-md-4 mb-2"><small class="text-muted d-block text-uppercase">Último ingreso a Terminación</small><strong>{{ $resumen->ultima_fecha ? date('d/m/Y',strtotime($resumen->ultima_fecha)) : '-' }}</strong></div>
            <div class="col-md-4 mb-2"><small class="text-muted d-block text-uppercase">Tiempo</small>
                @if($resumen->dias !== null)
                    <strong class="text-success">{{ $resumen->dias }} días</strong>
                @elseif($resumen->dias_en_curso !== null)
                    <strong class="text-warning">{{ $resumen->dias_en_curso }} días en curso</strong>
                @else
                    <strong>-</strong>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-list mr-2"></i>Seguimiento por OT</h3></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 text-center">
            <thead>
                <tr>
                    <th>OT</th>
                    <th>Código / Descripción</th>
                    <th>Cantidad</th>
                    <th>Fecha pedido</th>
                    <th>Ingreso Terminación</th>
                    <th>Cantidad ingreso</th>
                    <th>Tiempo</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
            @foreach($ots as $ot)
                <tr>
                    <td><strong>{{ $ot->nro_ot }}</strong></td>
                    <td><strong>{{ $ot->codigo }}</strong><br><small class="text-muted">{{ $ot->descripcion }}</small></td>
                    <td>{{ number_format($ot->cantidad_orden,0,',','.') }}</td>
                    <td>{{ $pedido->fecha_pedido ? $pedido->fecha_pedido->format('d/m/Y') : '-' }}</td>
                    <td>{{ $ot->fecha_ingreso ? date('d/m/Y',strtotime($ot->fecha_ingreso)) : '-' }}</td>
                    <td>{{ number_format($ot->cantidad_ingreso,0,',','.') }}</td>
                    <td>
                        @if($ot->dias !== null)
                            <strong>{{ $ot->dias }} días</strong>
                        @elseif($ot->ingreso_previo)
                            <span class="text-muted">N/A</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($ot->completo)
                            <span class="badge badge-success">COMPLETO</span>
                        @else
                            <span class="badge badge-warning">PENDIENTE INGRESO TERMINACIÓN</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
</div></section>
@endsection
