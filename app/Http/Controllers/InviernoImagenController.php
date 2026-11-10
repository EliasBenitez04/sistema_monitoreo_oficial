<?php

namespace App\Http\Controllers;

use App\Services\ArticuloImagenService;
use App\Support\ArticuloImagenNombre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InviernoImagenController extends Controller
{
    public function index()
    {
        $preparado = Schema::hasTable('articulo_imagenes');
        $conMaestro = Schema::hasTable('maestro_codigos') && DB::table('maestro_codigos')->exists();
        $total = $preparado ? DB::table('articulo_imagenes')->count() : 0;
        return view('invierno.imagenes', compact('preparado', 'conMaestro', 'total'));
    }

    public function importar(Request $request, ArticuloImagenService $service)
    {
        $request->validate([
            'archivos' => 'nullable|array|max:20',
            'archivos.*' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:30720',
            'paquete_zip' => 'nullable|file|mimes:zip|max:204800',
        ], [
            'archivos.max' => 'Seleccioná hasta 20 imágenes individuales o utilizá un ZIP.',
            'archivos.*.max' => 'Cada imagen puede pesar hasta 30 MB.',
            'paquete_zip.max' => 'El ZIP puede pesar hasta 200 MB.',
        ]);
        if (!Schema::hasTable('articulo_imagenes')) {
            throw ValidationException::withMessages(['archivos' => 'Falta habilitar la tabla de imágenes. Ejecutá la migración indicada en la documentación de invierno.']);
        }
        if (!Schema::hasTable('maestro_codigos') || !DB::table('maestro_codigos')->exists()) {
            throw ValidationException::withMessages(['archivos' => 'Primero importá el maestro de códigos para poder reconocer las prendas.']);
        }
        $archivos = $request->file('archivos', []);
        $paquete = $request->file('paquete_zip');
        if (!$archivos && !$paquete) {
            throw ValidationException::withMessages(['archivos' => 'Seleccioná imágenes o un ZIP para importar.']);
        }

        @set_time_limit(300);
        $zip = null;
        $fuentes = [];
        $bytes = 0;
        $resultado = ['nuevas' => 0, 'reemplazadas' => 0, 'omitidas' => 0];
        $errores = [];
        try {
            foreach ($archivos as $archivo) {
                $fuentes[] = ['nombre' => $archivo->getClientOriginalName(), 'archivo' => $archivo, 'indice' => null];
                $bytes += $archivo->getSize();
            }
            if ($bytes > 500 * 1024 * 1024) {
                throw ValidationException::withMessages(['archivos' => 'Las imágenes no pueden superar 500 MB por carga. Dividí el lote.']);
            }
            if ($paquete) {
                if (!class_exists(\ZipArchive::class)) {
                    throw ValidationException::withMessages(['paquete_zip' => 'La extensión ZIP de PHP no está habilitada en este servidor.']);
                }
                $zip = new \ZipArchive();
                if ($zip->open($paquete->getRealPath()) !== true) {
                    $zip = null;
                    throw ValidationException::withMessages(['paquete_zip' => 'No se pudo abrir el ZIP.']);
                }
                if ($zip->numFiles > 500) {
                    throw ValidationException::withMessages(['paquete_zip' => 'El ZIP admite hasta 500 entradas por carga.']);
                }
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $stat = $zip->statIndex($i);
                    $nombre = str_replace('\\', '/', $stat['name'] ?? '');
                    if ($nombre === '' || str_ends_with($nombre, '/') || str_contains($nombre, '__MACOSX/')
                        || str_starts_with(basename($nombre), '._')) continue;
                    $nombre = basename($nombre);
                    if (!preg_match('/\.(jpe?g|png|webp)$/i', $nombre)) {
                        $this->omitir($resultado, $errores, $nombre, 'Formato no admitido; usá JPG, PNG o WEBP.');
                        continue;
                    }
                    $tamano = (int) ($stat['size'] ?? 0);
                    $bytes += $tamano;
                    if ($bytes > 500 * 1024 * 1024) {
                        throw ValidationException::withMessages(['paquete_zip' => 'Las imágenes descomprimidas no pueden superar 500 MB por carga. Dividí el lote en varios ZIP.']);
                    }
                    if ($tamano > 30 * 1024 * 1024) {
                        $this->omitir($resultado, $errores, $nombre, 'La imagen supera los 30 MB.');
                        continue;
                    }
                    $fuentes[] = ['nombre' => $nombre, 'archivo' => null, 'indice' => $i];
                }
            }
            // Solo se consultan los codigos presentes en este lote, una vez por conjunto.
            $claves = [];
            foreach ($fuentes as &$fuente) {
                $fuente['datos'] = ArticuloImagenNombre::leer($fuente['nombre']);
                if ($fuente['datos']) $claves[] = $fuente['datos']['codigo_base'];
            }
            unset($fuente);
            $permitidos = $service->permitidos($claves);
            foreach ($fuentes as $fuente) {
                try {
                    $datos = $fuente['datos'];
                    if (!$datos) throw new \DomainException('Nombre no reconocido. Debe comenzar con un código de 9, 11 o 13 caracteres; revisá también la fecha del nombre.');
                    if (!isset($permitidos[$datos['codigo_base']])) {
                        throw new \DomainException('El código no figura en el maestro de códigos, Cod Imagen ni Cod Base.');
                    }
                    // Nunca extraer el ZIP al disco usando las rutas de sus entradas.
                    $contenido = $fuente['archivo']
                        ? file_get_contents($fuente['archivo']->getRealPath())
                        : $zip->getFromIndex($fuente['indice'], 30 * 1024 * 1024 + 1);
                    $tipo = $service->guardar($fuente['nombre'], $contenido === false ? '' : $contenido,
                        $datos, $request->user()?->id);
                    $resultado[$tipo]++;
                } catch (\DomainException $e) {
                    $this->omitir($resultado, $errores, $fuente['nombre'], $e->getMessage());
                } catch (\Throwable $e) {
                    report($e);
                    $this->omitir($resultado, $errores, $fuente['nombre'], 'No se pudo guardar la imagen.');
                }
            }
        } finally {
            if ($zip) $zip->close();
        }
        return redirect()->route('invierno.imagenes.index')->with('imagenes_resultado', $resultado)
            ->with('imagenes_errores', $errores);
    }

    private function omitir(array &$resultado, array &$errores, string $nombre, string $motivo): void
    {
        $resultado['omitidas']++;
        if (count($errores) < 50) $errores[] = mb_substr($nombre, 0, 100).': '.$motivo;
    }

    /** Entrega autenticada, compatible con Windows/XAMPP sin storage:link. */
    public function foto(string $codigo, ArticuloImagenService $service)
    {
        abort_unless(preg_match('/^[0-9]{9}(?:[A-Z]{2})?$/', $codigo), 404);
        abort_unless(Schema::hasTable('articulo_imagenes'), 404);
        $imagen = DB::table('articulo_imagenes')->where('codigo_imagen', $codigo)->value('imagen');
        abort_unless($imagen && $service->rutaValida($imagen), 404);
        $disco = Storage::disk('public');
        abort_unless($disco->exists($imagen), 404);
        $ext = pathinfo($imagen, PATHINFO_EXTENSION);
        $mime = $ext === 'jpg' ? 'image/jpeg' : ($ext === 'png' ? 'image/png' : 'image/webp');
        return $disco->response($imagen, null, ['Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
    }
}
