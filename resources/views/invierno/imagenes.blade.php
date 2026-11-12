@extends('layouts.app')
@include('invierno._estilos')

@section('content')
<section class="content invierno">
    <div class="container-fluid">
        <header class="iw-hero iw-split">
            <div><div class="iw-eyebrow">FOTOS DE LAS PRENDAS · ASIGNACIÓN AUTOMÁTICA POR CÓDIGO</div><h1><i class="fas fa-images mr-2"></i>Importar imágenes</h1><p>Las fotos se reconocen por el nombre y se vinculan con el maestro de códigos.</p></div>
            <a href="{{ route('invierno.articulos') }}" class="btn btn-light"><i class="fas fa-tshirt mr-1"></i> Ver prendas e imágenes</a>
        </header>
        @include('invierno._navegacion')
        @if($errors->any())<div class="alert alert-danger"><strong>No se pudo iniciar la importación.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @unless($preparado)<div class="alert alert-warning">Falta habilitar las imágenes en esta instalación. Aplicá la migración de imágenes antes de importar.</div>@endunless
        @unless($conMaestro)<div class="alert alert-warning">Primero importá el maestro de códigos. Las fotografías se asignan únicamente a prendas de ese listado.</div>@endunless
        @if(session('imagenes_resultado'))
            @php($resultado = session('imagenes_resultado'))
            <div class="card mb-4" role="status">
                <div class="card-header"><h2 class="card-title mb-0"><i class="fas fa-check-circle text-success mr-2"></i>Importación finalizada</h2></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-4"><div class="iw-import-stat"><strong>{{ number_format($resultado['nuevas'], 0, ',', '.') }}</strong><span>imágenes nuevas</span></div></div>
                        <div class="col-sm-4"><div class="iw-import-stat"><strong>{{ number_format($resultado['reemplazadas'], 0, ',', '.') }}</strong><span>imágenes reemplazadas</span></div></div>
                        <div class="col-sm-4"><div class="iw-import-stat"><strong>{{ number_format($resultado['omitidas'], 0, ',', '.') }}</strong><span>archivos omitidos</span></div></div>
                    </div>
                    @if(session('imagenes_errores'))<details class="mt-3"><summary>Ver motivos de los archivos omitidos</summary><ul class="mt-2 mb-0">@foreach(session('imagenes_errores') as $error)<li>{{ $error }}</li>@endforeach</ul><small class="text-muted">Se muestran hasta 50 motivos.</small></details>@endif
                    <a href="{{ route('invierno.articulos') }}" class="btn btn-outline-primary mt-3">Ver las prendas con sus fotos <i class="fas fa-arrow-right ml-1"></i></a>
                </div>
            </div>
        @endif
        <div class="row">
            <div class="col-lg-7">
                <div class="card mb-4">
                    <div class="card-header iw-split"><h2 class="card-title mb-0">Elegí el ZIP o las imágenes</h2><span class="iw-note">{{ number_format($total, 0, ',', '.') }} fotos guardadas</span></div>
                    <div class="card-body">
                        <form action="{{ route('invierno.imagenes.importar') }}" method="POST" enctype="multipart/form-data" id="iw-imagenes-form">
                            @csrf
                            <div class="form-group"><label for="iw-imagenes-zip">Paquete ZIP</label><label class="iw-file-zone" for="iw-imagenes-zip"><i class="fas fa-file-archive"></i><strong>Seleccionar ZIP de fotografías</strong><span>Hasta 200 MB · 500 entradas · no hace falta descomprimirlo</span><input id="iw-imagenes-zip" class="form-control-file mt-3" type="file" name="paquete_zip" accept=".zip,application/zip"></label></div>
                            <div class="form-group mt-4">
                                <label class="iw-file-zone mb-2" id="iw-imagenes-zona" for="iw-imagenes-archivos">
                                    <i class="fas fa-images" aria-hidden="true"></i>
                                    <strong>Arrastrá tus imágenes acá</strong>
                                    <span>o hacé clic para buscar imágenes</span>
                                    <input id="iw-imagenes-archivos" class="sr-only" type="file" name="archivos[]"
                                           accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple
                                           aria-label="Buscar imágenes individuales" aria-describedby="iw-imagenes-formatos">
                                </label>
                                <small id="iw-imagenes-formatos" class="text-muted">Hasta 20 por carga · JPG, PNG o WEBP · 30 MB por imagen.</small>
                            </div>
                            <div id="iw-imagenes-resumen" class="iw-import-summary text-break" aria-live="polite">Todavía no seleccionaste archivos.</div>
                            <p id="iw-imagenes-avisos" class="small text-danger mt-2 mb-0" role="status" hidden></p>
                            <ul id="iw-imagenes-nombres" class="list-group list-group-flush small mb-0"></ul>
                            <button class="btn btn-primary btn-lg mt-2" type="submit" id="iw-imagenes-submit" {{ !$preparado || !$conMaestro ? 'disabled' : '' }}><i class="fas fa-upload mr-2"></i> Importar y asociar imágenes</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card mb-4"><div class="card-body">
                    <h2 class="h5 font-weight-bold mb-3">Los mismos nombres que usás en Ecommerce</h2>
                    <p class="iw-note">El código debe estar al comienzo del nombre. También se reconocen los nombres largos que incluyen V, C, OT, descripción y fecha.</p>
                    <div class="iw-file-example">060617629.jpg</div>
                    <div class="iw-file-example">060617629AZ.png</div>
                    <div class="iw-file-example">060617629AZ01.jpg</div>
                    <div class="iw-file-example">100613678CV V35 C13.388 OT26697 BERMUDA ECO VARÓN 08-10-26.jpg</div>
                    <ul class="iw-note pl-3 mt-3 mb-0">
                        <li class="mb-2"><strong>9 dígitos:</strong> foto genérica del modelo.</li>
                        <li class="mb-2"><strong>11 caracteres:</strong> modelo y color. La comparten sus talles.</li>
                        <li class="mb-2"><strong>13 caracteres:</strong> se reconoce el código completo y la foto se comparte por modelo y color, igual que en Ecommerce.</li>
                        <li class="mb-2">Se revisan el código de artículo, <strong>Cod Imagen</strong> y <strong>Cod Base</strong> del maestro. Los códigos ajenos al listado se omiten con su motivo.</li>
                        <li class="mb-2">La foto del color tiene prioridad sobre la genérica. Al importar otra del mismo código, se reemplaza la anterior.</li>
                        <li>Las imágenes aparecen en <strong>Artículos de invierno</strong>, el detalle por grupo y el detalle de cada remisión. Tocá una foto para verla ampliada.</li>
                    </ul>
                </div></div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('page_scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('iw-imagenes-form');
    const zip = document.getElementById('iw-imagenes-zip');
    const imagenes = document.getElementById('iw-imagenes-archivos');
    const zona = document.getElementById('iw-imagenes-zona');
    const resumen = document.getElementById('iw-imagenes-resumen');
    const avisos = document.getElementById('iw-imagenes-avisos');
    const nombres = document.getElementById('iw-imagenes-nombres');
    const boton = document.getElementById('iw-imagenes-submit');
    const maxImagenes = 20;
    const megabyte = 1024 * 1024;
    let seleccion = null;
    try {
        seleccion = new DataTransfer();
    } catch {
        zona.querySelector('strong').textContent = 'Buscar imágenes';
        zona.querySelector('span').hidden = true;
    }

    function avisar(mensaje) {
        avisos.textContent = mensaje;
        avisos.hidden = !mensaje;
    }

    function actualizar() {
        const archivos = Array.from(imagenes.files);
        const paquete = zip.files[0];
        const partes = [];
        if (archivos.length) {
            partes.push(archivos.length === 1 ? '1 imagen individual' : archivos.length + ' imágenes individuales');
        }
        if (paquete) partes.push('ZIP: ' + paquete.name);
        resumen.textContent = partes.join(' · ') || 'Todavía no seleccionaste archivos.';
        nombres.textContent = '';
        archivos.slice(0, maxImagenes).forEach(function (archivo, indice) {
            const item = document.createElement('li');
            item.className = 'list-group-item d-flex align-items-center justify-content-between px-0 py-2';
            const nombre = document.createElement('span');
            nombre.className = 'text-break mr-2';
            nombre.textContent = archivo.name;
            item.appendChild(nombre);

            if (seleccion) {
                const quitar = document.createElement('button');
                quitar.type = 'button';
                quitar.className = 'btn btn-sm btn-outline-secondary flex-shrink-0';
                quitar.textContent = 'Quitar';
                quitar.setAttribute('aria-label', 'Quitar ' + archivo.name);
                quitar.addEventListener('click', function () {
                    seleccion.items.remove(indice);
                    imagenes.files = seleccion.files;
                    avisar('');
                    actualizar();
                    const siguiente = nombres.children[indice]?.querySelector('button');
                    (siguiente || imagenes).focus();
                });
                item.appendChild(quitar);
            }
            nombres.appendChild(item);
        });
    }

    function agregar(archivos) {
        const omitidos = new Set();
        for (const archivo of Array.from(archivos)) {
            if (!/\.(jpe?g|png|webp)$/i.test(archivo.name)
                && !['image/jpeg', 'image/png', 'image/webp'].includes(archivo.type)) {
                omitidos.add('Seleccioná archivos JPG, PNG o WEBP.');
                continue;
            }
            if (archivo.size > 30 * megabyte) {
                omitidos.add('Se omitieron imágenes que superan 30 MB.');
                continue;
            }
            const repetido = Array.from(seleccion.files).some(function (elegido) {
                return elegido.name === archivo.name && elegido.size === archivo.size
                    && elegido.lastModified === archivo.lastModified;
            });
            if (repetido) continue;
            if (seleccion.items.length >= maxImagenes) {
                omitidos.add('El lote admite hasta 20 imágenes individuales.');
                break;
            }
            seleccion.items.add(archivo);
        }
        imagenes.files = seleccion.files;
        avisar(Array.from(omitidos).join(' '));
        actualizar();
    }

    if (seleccion) {
        zona.addEventListener('dragover', function (event) {
            if (!Array.from(event.dataTransfer.types).includes('Files')) return;
            event.preventDefault();
            event.dataTransfer.dropEffect = 'copy';
            zona.classList.add('iw-arrastrando');
        });
        zona.addEventListener('dragleave', function (event) {
            if (!zona.contains(event.relatedTarget)) zona.classList.remove('iw-arrastrando');
        });
        zona.addEventListener('drop', function (event) {
            event.preventDefault();
            zona.classList.remove('iw-arrastrando');
            agregar(event.dataTransfer.files);
        });
    }
    zip.addEventListener('change', actualizar);
    imagenes.addEventListener('change', function () {
        if (seleccion) agregar(imagenes.files);
        else actualizar();
    });
    form.addEventListener('submit', function (event) {
        let mensaje = '';
        if (!imagenes.files.length && !zip.files.length) {
            mensaje = 'Seleccioná imágenes o un ZIP.';
        } else if (imagenes.files.length > maxImagenes) {
            mensaje = 'Seleccioná hasta 20 imágenes individuales o utilizá un ZIP.';
        } else if (zip.files.length && zip.files[0].size > 200 * megabyte) {
            mensaje = 'El ZIP puede pesar hasta 200 MB.';
        }
        if (mensaje) {
            event.preventDefault();
            avisar(mensaje);
            return;
        }
        avisar('');
        boton.disabled = true;
        boton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Importando y asociando imágenes…';
        resumen.textContent = 'Se están cargando los archivos y reconociendo los códigos. Esperá el resultado de la importación.';
    });
});
</script>
@endpush
