@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1>Inicio</h1>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <div class="card shadow-sm border-0" style="max-width:760px;margin:34px auto;border-radius:15px;overflow:hidden">
            <div class="card-body text-center" style="padding:45px 32px">
                <div style="display:inline-flex;align-items:center;justify-content:center;width:70px;height:70px;border-radius:18px;background:#fff3e8;color:#ae601f;font-size:30px;margin-bottom:22px">
                    <i class="fas fa-lock"></i>
                </div>
                <h2 style="color:#193c73;font-weight:800;font-size:26px;margin-bottom:12px">
                    No tienes permiso para vizualizar está pantalla
                </h2>
                <p class="text-muted" style="font-size:14px;max-width:490px;margin:0 auto 25px;line-height:1.7">
                    Tu cuenta tiene acceso de solo lectura al Monitoreo de Accesorios.
                    Podés consultar sus indicadores, artículos y detalles, pero no modificar datos.
                </p>
                <a href="{{ route('accesorios.index') }}" class="btn btn-primary px-4 py-2" style="border-radius:9px;font-weight:700">
                    <i class="fas fa-chart-line mr-2"></i> Ir a Monitoreo de Accesorios
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
