<nav class="mb-3 d-flex flex-wrap" aria-label="Seguimiento de invierno">
    <a class="btn {{ request()->routeIs('invierno.disponibilidad*') ? 'btn-primary' : 'btn-outline-primary' }} mr-2 mb-1"
       href="{{ route('invierno.disponibilidad') }}"><i class="fas fa-layer-group mr-1"></i> Disponibilidad por grupo</a>
    <a class="btn {{ request()->routeIs('invierno.articulos*') ? 'btn-primary' : 'btn-outline-primary' }} mr-2 mb-1"
       href="{{ route('invierno.articulos') }}"><i class="fas fa-tshirt mr-1"></i> Artículos de invierno</a>
    <a class="btn {{ !request()->routeIs('invierno.disponibilidad*', 'invierno.articulos*') ? 'btn-primary' : 'btn-outline-primary' }} mb-1"
       href="{{ route('invierno.index') }}"><i class="fas fa-truck mr-1"></i> Remisiones de invierno</a>
</nav>
