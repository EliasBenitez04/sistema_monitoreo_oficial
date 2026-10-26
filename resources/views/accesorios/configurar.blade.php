@extends('layouts.app')

@section('content')
<section class="content-header"><div class="container-fluid">
    <h1><i class="fas fa-chart-line text-primary mr-2"></i>Monitoreo de Accesorios</h1>
    <p class="text-muted">Panel de indicadores, distribución, ventas y decisiones.</p>
</div></section>
<section class="content"><div class="container-fluid">
    <div class="card card-outline card-warning shadow-sm" style="max-width:850px">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-exclamation-triangle mr-2"></i>Falta preparar la base de datos</h3></div>
        <div class="card-body">
            <p>La base de datos todavía no tiene todas las tablas o columnas de Accesorios. Antes de utilizar el panel, ejecutá <strong>únicamente</strong> la migración de Accesorios desde <code>C:\xampp\htdocs\lp2</code>:</p>
            <pre class="bg-light border p-3">php artisan migrate --path=database/migrations/2026_10_26_090000_create_accesorios_tracking_tables.php</pre>
            <p class="small text-muted">Este comando no ejecuta las migraciones antiguas de permisos que pueden estar pendientes. No utilices <code>migrate:fresh</code> ni borres las tablas existentes.</p>
            <a href="{{ route('accesorios.index') }}" class="btn btn-primary"><i class="fas fa-sync-alt mr-1"></i>Comprobar nuevamente</a>
        </div>
    </div>
</div></section>
@endsection
