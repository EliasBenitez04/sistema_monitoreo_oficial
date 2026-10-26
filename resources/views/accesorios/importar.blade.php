@extends('layouts.app')

@section('content')
<section class="content-header"><div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
    <div><h1><i class="fas fa-file-import mr-2"></i>Accesorios · Importar datos</h1><p class="text-muted">Compra de accesorios por FCO y fecha de envío.</p></div>
    <a href="{{ route('accesorios.index') }}" class="btn btn-outline-secondary">Ver seguimiento</a>
</div></section>
<section class="content"><div class="container-fluid">
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header"><h3 class="card-title">Archivo de compras</h3></div>
                <form action="{{ route('accesorios.importar') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <label for="archivo">Seleccionar Excel o CSV</label>
                        <input type="file" name="archivo" id="archivo" accept=".xlsx,.xls,.csv,.txt" class="form-control-file" required>
                        <small class="form-text text-muted">Máximo 10 MB. Reimportar el mismo código + FCO + fecha no duplica las compras.</small>
                        <hr>
                        <strong>Encabezados esperados en la primera fila</strong>
                        <div class="table-responsive mt-2"><table class="table table-bordered table-sm small">
                            <thead><tr><th>CODIGO</th><th>DESCRIPCION</th><th>COSTO</th><th>PREC. VENT.</th><th>FCO NRO</th><th>FECHA ENVIO</th><th>LINEA</th><th>CANT. COMPRA</th></tr></thead>
                            <tbody><tr><td>BOMJUV001</td><td>BOMBACHA JUV</td><td>PYG 9.881</td><td>PYG 25.000</td><td>104</td><td>28/04/2026</td><td>JUV NENA</td><td>12</td></tr></tbody>
                        </table></div>
                        <div class="alert alert-light border mb-0"><i class="fas fa-info-circle text-primary mr-1"></i>
                            El precio y costo se guardan por FCO. Si el mismo código se compró dos veces, ambas compras se conservan. Una remisión se vincula al código del accesorio, sin atribuirla a un FCO específico.
                        </div>
                    </div>
                    <div class="card-footer text-right"><button class="btn btn-primary" type="submit"><i class="fas fa-upload mr-1"></i> Importar y vincular remisiones</button></div>
                </form>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm"><div class="card-header"><h3 class="card-title">Últimas compras</h3></div><div class="card-body p-0">
                <ul class="list-group list-group-flush">
                @forelse($ultimas as $compra)
                    <li class="list-group-item"><strong>{{ $compra->codigo }}</strong> · FCO {{ $compra->fco_nro }}
                        <div class="small text-muted">{{ $compra->fecha_envio }} · {{ number_format($compra->cantidad_compra, 0, ',', '.') }} un.</div></li>
                @empty <li class="list-group-item text-muted">Aún no hay compras importadas.</li> @endforelse
                </ul>
            </div></div>
        </div>
    </div>
</div></section>
@endsection
