@php($fotoPrenda = ($imagenes ?? collect())->get(strtoupper(trim((string) $imagenCodigo))))
@if($fotoPrenda)
    @php($fotoPrendaUrl = route('invierno.imagenes.foto', ['codigo' => $fotoPrenda->codigo_imagen, 'v' => substr(sha1($fotoPrenda->imagen), 0, 12)]))
    <a href="{{ $fotoPrendaUrl }}" target="_blank" rel="noopener" class="iw-photo" aria-label="Ampliar imagen de {{ $imagenCodigo }}"><img src="{{ $fotoPrendaUrl }}" alt="Prenda {{ $imagenCodigo }}" loading="lazy" width="72" height="82"></a>
@else
    <span class="iw-photo iw-photo-empty" aria-label="Sin imagen para {{ $imagenCodigo }}"><i class="fas fa-tshirt" aria-hidden="true"></i><small>Sin foto</small></span>
@endif
