@if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>No se pudo completar la operación.</strong>
        <ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
@if(session('invierno_errores'))
    <div class="alert alert-warning">
        <details open>
            <summary>Filas rechazadas de la última importación (hasta 50)</summary>
            <ul class="mb-0 mt-2">@foreach(session('invierno_errores') as $error)<li>{{ $error }}</li>@endforeach</ul>
        </details>
    </div>
@endif
