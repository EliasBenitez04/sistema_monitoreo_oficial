@extends('layouts.app')

@section('content')
<section class="content-header pb-2">
<div class="container-fluid">
    <div>
        <div class="pd-eyebrow">MÓDULO CENTRAL</div>
        <h1 class="pd-title mb-1"><i class="fas fa-clipboard-list mr-2"></i>PEDIDOS</h1>
        <p class="text-muted mb-0">Acceso centralizado a importación, seguimiento operativo e informes gerenciales.</p>
    </div>
</div>
</section>

<section class="content">
<div class="container-fluid">
    <div class="pd-grid">
        <a href="{{ route('pedidos.importar') }}" class="pd-card is-import">
            <div class="pd-icon"><i class="fas fa-file-import"></i></div>
            <div class="pd-card-body">
                <div class="pd-tag">DATOS</div>
                <h3>Importar datos</h3>
                <p>Carga única para pedidos T, P e IT usando NRO OT, PEDIDO y FECHA PEDIDO.</p>
                <span>Ir al importador <i class="fas fa-arrow-right ml-1"></i></span>
            </div>
        </a>

        <a href="{{ route('seguimiento-produccion.index') }}" class="pd-card is-prod">
            <div class="pd-icon"><i class="fas fa-industry"></i></div>
            <div class="pd-card-body">
                <div class="pd-tag">P1, P2...</div>
                <h3>Producción</h3>
                <p>Completa cuando todas las OT alcanzan TERMINACION - INGRESO TERMINACION.</p>
                <span>Ver seguimiento <i class="fas fa-arrow-right ml-1"></i></span>
            </div>
        </a>

        <a href="{{ route('seguimiento-ingreso-terminacion.index') }}" class="pd-card is-it">
            <div class="pd-icon"><i class="fas fa-sign-in-alt"></i></div>
            <div class="pd-card-body">
                <div class="pd-tag">IT1, IT2...</div>
                <h3>Ingreso Terminación</h3>
                <p>Completa cuando todas las OT alcanzan TERMINACION - TERMINACION.</p>
                <span>Ver seguimiento <i class="fas fa-arrow-right ml-1"></i></span>
            </div>
        </a>

        <a href="{{ route('seguimiento-terminacion.index') }}" class="pd-card is-term">
            <div class="pd-icon"><i class="fas fa-route"></i></div>
            <div class="pd-card-body">
                <div class="pd-tag">T1, T2...</div>
                <h3>Terminación</h3>
                <p>Seguimiento de pedidos T desde Terminación hasta la confirmación de los locales.</p>
                <span>Ver seguimiento <i class="fas fa-arrow-right ml-1"></i></span>
            </div>
        </a>
    </div>

    <div class="pd-info mt-4">
        <div><i class="fas fa-info-circle mr-2"></i><strong>Un solo origen de datos.</strong> El importador reconoce T, P e IT y cada seguimiento muestra únicamente su prefijo.</div>
    </div>
</div>
</section>
@endsection

@push('page_css')
<style>
.pd-eyebrow{font-size:10px;font-weight:900;letter-spacing:.12em;color:#94a3b8}
.pd-title{font-size:30px;font-weight:900;color:#0f172a}
.pd-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}
.pd-card{
    min-height:245px;background:#fff;border:1px solid #e6ebf1;border-radius:17px;
    padding:22px;text-decoration:none!important;color:inherit!important;
    box-shadow:0 7px 22px rgba(15,23,42,.05);position:relative;overflow:hidden;
    transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease
}
.pd-card:hover{transform:translateY(-3px);box-shadow:0 14px 30px rgba(15,23,42,.09);border-color:#cbd5e1}
.pd-card:before{content:'';position:absolute;left:0;right:0;top:0;height:5px;background:#64748b}
.pd-card.is-import:before{background:#0f172a}.pd-card.is-prod:before{background:#2563eb}.pd-card.is-it:before{background:#6366f1}.pd-card.is-term:before{background:#16a34a}
.pd-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;background:#f8fafc;color:#334155;font-size:20px;margin-bottom:22px}
.pd-card.is-prod .pd-icon{background:#eff6ff;color:#2563eb}.pd-card.is-it .pd-icon{background:#eef2ff;color:#4f46e5}.pd-card.is-term .pd-icon{background:#f0fdf4;color:#15803d}
.pd-tag{font-size:9px;font-weight:900;letter-spacing:.1em;color:#94a3b8;margin-bottom:5px}
.pd-card h3{font-size:19px;font-weight:900;color:#0f172a;margin-bottom:8px}
.pd-card p{font-size:12px;line-height:1.6;color:#64748b;min-height:58px}
.pd-card span{font-size:11px;font-weight:850;color:#334155}
.pd-info{background:#f8fafc;border:1px solid #e2e8f0;border-radius:13px;padding:14px 16px;color:#64748b;font-size:12px}
@media(max-width:1199.98px){.pd-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:575.98px){.pd-grid{grid-template-columns:1fr}.pd-title{font-size:25px}}
</style>
@endpush
