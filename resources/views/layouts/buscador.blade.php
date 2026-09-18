<from id="form-busqueda">
    <div class="input-group mb-3">
        <input type="text" class="form-control buscar" name="buscar" value="{{ request()->get('buscar', null) }}"
            placeholder="Buscar..." data-url="{{ $url }}" aria-describedby="button-addon2">
        <button class="btn btn-outline-secondary" type="submit" id="button-addon2">Buscar</button>

    </div>
</from>

{{-- <!-- Buscador de Carga Fotos -->
<form id="form-busqueda-fotos" method="GET" action="{{ route('carga_fotos.index') }}">
    <div class="input-group mb-3">
        <input type="text" class="form-control" name="search" value="{{ request()->get('search', '') }}"
            placeholder="Buscar por OT, descripción o línea..." aria-describedby="button-buscar-fotos">
        <button class="btn btn-outline-secondary" type="submit" id="button-buscar-fotos">
            Buscar
        </button>
    </div>
</form> --}}
