@extends('layouts.app')

@section('content')
<section class="content-header pb-2">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <div class="imp-eyebrow">PEDIDOS · DATOS</div>
            <h1 class="imp-title mb-1"><i class="fas fa-file-import mr-2"></i>Importar datos</h1>
            <p class="text-muted mb-0">Un único importador para Terminación, Producción e Ingreso Terminación.</p>
        </div>
        <a href="{{ route('pedidos.index') }}" class="btn btn-outline-secondary mt-2 mt-md-0"><i class="fas fa-arrow-left mr-1"></i> PEDIDOS</a>
    </div>
</div>
</section>

<section class="content">
<div class="container-fluid">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger">
            <strong>No se pudo importar:</strong>
            <ul class="mb-0 pl-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card imp-card">
                <div class="card-header bg-white border-0">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-file-excel mr-2 text-success"></i>Archivo de pedidos</h3>
                </div>
                <form method="POST" action="{{ route('seguimiento-pedidos.importar') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <div class="imp-format mb-4">
                            <div><span>NRO OT</span><strong>30703</strong></div>
                            <div><span>PEDIDO</span><strong>T1 / P1 / IT1</strong></div>
                            <div><span>FECHA PEDIDO</span><strong>29/09/2026</strong></div>
                        </div>
                        <div class="custom-file">
                            <input type="file" name="archivo" class="custom-file-input" id="archivo-pedidos-central" accept=".xlsx,.xls,.csv" required>
                            <label class="custom-file-label" for="archivo-pedidos-central">Seleccionar archivo .xlsx, .xls o .csv</label>
                        </div>
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap">
                        <small class="text-muted">El mismo archivo puede contener T, P e IT.</small>
                        <button type="submit" class="btn btn-primary mt-2 mt-md-0"><i class="fas fa-upload mr-1"></i> Importar datos</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="imp-guide">
                <h4>Destino por prefijo</h4>
                <div class="imp-guide-row"><span class="dot is-t"></span><div><strong>T</strong><small>Terminación → recepción local</small></div></div>
                <div class="imp-guide-row"><span class="dot is-p"></span><div><strong>P</strong><small>Producción → Ingreso Terminación</small></div></div>
                <div class="imp-guide-row"><span class="dot is-it"></span><div><strong>IT</strong><small>Ingreso Terminación → Terminación</small></div></div>
            </div>
        </div>
    </div>
</div>
</section>
@endsection

@push('page_scripts')
<script>
document.getElementById('archivo-pedidos-central').addEventListener('change', function(){
    this.nextElementSibling.textContent = this.files.length ? this.files[0].name : 'Seleccionar archivo .xlsx, .xls o .csv';
});
</script>
@endpush

@push('page_css')
<style>
.imp-eyebrow{font-size:10px;font-weight:900;letter-spacing:.12em;color:#94a3b8}.imp-title{font-size:29px;font-weight:900;color:#0f172a}
.imp-card{border:1px solid #e6ebf1;border-radius:16px;overflow:hidden;box-shadow:0 7px 22px rgba(15,23,42,.05)}
.imp-format{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.imp-format>div{background:#f8fafc;border:1px solid #e2e8f0;border-radius:11px;padding:12px}.imp-format span{display:block;font-size:9px;font-weight:900;letter-spacing:.06em;color:#94a3b8}.imp-format strong{display:block;color:#0f172a;margin-top:5px}
.imp-guide{background:#fff;border:1px solid #e6ebf1;border-radius:16px;padding:20px;box-shadow:0 7px 22px rgba(15,23,42,.04)}.imp-guide h4{font-size:14px;font-weight:900;color:#0f172a;margin-bottom:16px}
.imp-guide-row{display:flex;gap:10px;align-items:center;padding:11px 0;border-bottom:1px solid #f1f5f9}.imp-guide-row:last-child{border-bottom:0}.imp-guide-row strong{display:block;font-size:13px;color:#0f172a}.imp-guide-row small{display:block;color:#64748b}
.dot{width:10px;height:10px;border-radius:50%;flex:0 0 10px}.dot.is-t{background:#16a34a}.dot.is-p{background:#2563eb}.dot.is-it{background:#6366f1}
@media(max-width:767.98px){.imp-format{grid-template-columns:1fr}}
</style>
@endpush
