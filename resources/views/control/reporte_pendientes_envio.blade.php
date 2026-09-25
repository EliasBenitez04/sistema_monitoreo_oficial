@extends('layouts.app')

@section('content')
<style>
    .rp-card{border:1px solid #e7ebf0;border-radius:12px;box-shadow:0 5px 18px rgba(15,23,42,.045)}
    .rp-title{font-weight:800;color:#26364a}.rp-sub{color:#7a8796;font-size:13px}
    .rp-table th{font-size:11px;text-transform:uppercase;color:#536273;white-space:nowrap}
    .rp-table td{font-size:13px;vertical-align:middle}
    .rp-pending{font-size:16px;font-weight:800;color:#dc3545}
    @media print{.no-print{display:none!important}.rp-card{box-shadow:none}.container-fluid{padding:0!important}}
</style>
<div class="container-fluid pb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
        <div>
            <h2 class="rp-title mb-1"><i class="fas fa-truck-loading text-warning mr-2"></i>Reporte de Pendientes de Envío</h2>
            <div class="rp-sub">OTs que ya llegaron a Producto Terminado / Logística pero todavía tienen prendas pendientes de remitir.</div>
        </div>
        <div class="no-print mt-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="fas fa-print mr-1"></i>Imprimir / PDF</button>
            <a href="{{ route('control.terminacion', ['fecha_desde'=>$fechaDesde,'fecha_hasta'=>$fechaHasta]) }}" class="btn btn-primary btn-sm">Volver</a>
        </div>
    </div>

    <div class="card rp-card mb-3 no-print"><div class="card-body">
        <form method="GET">
            <div class="row align-items-end">
                <div class="col-md-3 mb-2"><label class="small font-weight-bold">PT desde</label><input type="date" name="fecha_desde" value="{{ $fechaDesde }}" class="form-control"></div>
                <div class="col-md-3 mb-2"><label class="small font-weight-bold">PT hasta</label><input type="date" name="fecha_hasta" value="{{ $fechaHasta }}" class="form-control"></div>
                <div class="col-md-4 mb-2"><label class="small font-weight-bold">OT / código / descripción</label><input type="text" name="buscar" value="{{ $buscar }}" class="form-control"></div>
                <div class="col-md-2 mb-2"><button class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i>Consultar</button></div>
            </div>
        </form>
    </div></div>

    <div class="card rp-card">
        <div class="card-header bg-white d-flex justify-content-between">
            <div><strong>Pendientes para solicitar envío</strong><div class="rp-sub">Período PT: {{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}</div></div>
            <span class="badge badge-warning p-2">{{ $reporte->count() }} OTs</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 rp-table">
                <thead><tr>
                    <th>OT</th><th>Código / Descripción</th><th>Fecha PT / Logística</th>
                    <th class="text-right">PT</th><th class="text-right">Enviado</th><th class="text-right">Falta enviar</th>
                    <th>Primer envío</th><th>Último envío</th><th class="text-right">Días desde PT</th><th class="text-right">Días desde último envío</th><th>Solicitud</th>
                </tr></thead>
                <tbody>
                @forelse($reporte as $item)
                    <tr>
                        <td><strong>{{ $item->nro_ot }}</strong></td>
                        <td><code>{{ $item->codigo }}</code><br><small>{{ $item->descripcion }}</small></td>
                        <td>{{ $item->fecha_pt ? \Carbon\Carbon::parse($item->fecha_pt)->format('d/m/Y') : '—' }}</td>
                        <td class="text-right"><strong>{{ number_format($item->cantidad_pt,0,',','.') }}</strong></td>
                        <td class="text-right">{{ number_format($item->enviado,0,',','.') }}</td>
                        <td class="text-right"><span class="rp-pending">{{ number_format($item->pendiente_envio,0,',','.') }}</span></td>
                        <td>{{ $item->primer_envio ? \Carbon\Carbon::parse($item->primer_envio)->format('d/m/Y') : 'SIN ENVÍO' }}</td>
                        <td>{{ $item->ultimo_envio ? \Carbon\Carbon::parse($item->ultimo_envio)->format('d/m/Y') : '—' }}</td>
                        <td class="text-right">{{ $item->dias_desde_pt }}</td>
                        <td class="text-right">
                            @if($item->dias_desde_ultimo_envio !== null)
                                <strong>{{ $item->dias_desde_ultimo_envio }}</strong>
                            @else
                                <span class="badge badge-danger">SIN ENVÍO</span>
                            @endif
                        </td>
                        <td><strong>Enviar {{ number_format($item->pendiente_envio,0,',','.') }} {{ $item->pendiente_envio == 1 ? 'prenda' : 'prendas' }}</strong></td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center py-5 text-muted"><i class="fas fa-check-circle text-success mr-1"></i>No hay prendas pendientes de envío en el período.</td></tr>
                @endforelse
                </tbody>
                @if($reporte->isNotEmpty())
                <tfoot><tr class="font-weight-bold bg-light">
                    <td colspan="3">TOTAL</td>
                    <td class="text-right">{{ number_format($reporte->sum('cantidad_pt'),0,',','.') }}</td>
                    <td class="text-right">{{ number_format($reporte->sum('enviado'),0,',','.') }}</td>
                    <td class="text-right text-danger">{{ number_format($reporte->sum('pendiente_envio'),0,',','.') }}</td>
                    <td colspan="5"></td>
                </tr></tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
