<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ArticuloImagenService
{
    public function normalizar($codigo): string
    {
        return strtoupper(trim((string) $codigo));
    }

    /** Exacto, color y finalmente foto generica; nunca se comparte entre colores. */
    private function claves(string $codigo, $maestro = null): array
    {
        $codigo = $this->normalizar($codigo);
        $claves = [$codigo];
        if (preg_match('/^[0-9]{9}[A-Z]{2}(?:[A-Z0-9]{2})?$/', $codigo)) {
            $claves[] = substr($codigo, 0, 11);
        }
        foreach (['cod_imagen', 'cod_base'] as $campo) {
            $alias = $this->normalizar($maestro->{$campo} ?? '');
            if (preg_match('/^[0-9]{9}(?:[A-Z]{2})?$/', $alias)) $claves[] = $alias;
        }
        if (preg_match('/^[0-9]{9}(?:[A-Z]{2}(?:[A-Z0-9]{2})?)?$/', $codigo)) {
            $claves[] = substr($codigo, 0, 9);
        }
        $claves = array_values(array_unique($claves));
        // Orden estable: las fotos especificas prevalecen sobre las genericas.
        usort($claves, fn ($a, $b) => strlen($b) <=> strlen($a));
        return $claves;
    }

    /** Resuelve todos los nombres del lote contra el listado fijo, por conjuntos. */
    public function permitidos(array $claves): array
    {
        if (!$claves || !Schema::hasTable('maestro_codigos')) return [];
        $permitidos = [];
        foreach (array_chunk(array_values(array_unique($claves)), 150) as $bloque) {
            $modelos = array_values(array_filter($bloque, fn ($c) => strlen($c) === 9));
            $colores = array_values(array_filter($bloque, fn ($c) => strlen($c) === 11));
            $filas = DB::table('maestro_codigos')->where(function ($q) use ($bloque, $modelos, $colores) {
                foreach (['cod_articulo', 'cod_imagen', 'cod_base'] as $campo) {
                    $q->orWhereIn(DB::raw('UPPER(TRIM('.$campo.'))'), $bloque);
                }
                if ($modelos) $q->orWhereIn(DB::raw('SUBSTR(UPPER(TRIM(cod_articulo)), 1, 9)'), $modelos);
                if ($colores) $q->orWhereIn(DB::raw('SUBSTR(UPPER(TRIM(cod_articulo)), 1, 11)'), $colores);
            })->get(['cod_articulo', 'cod_imagen', 'cod_base']);
            foreach ($filas as $fila) {
                foreach ($this->claves($fila->cod_articulo, $fila) as $clave) {
                    if (in_array($clave, $bloque, true)) $permitidos[$clave] = true;
                }
            }
        }
        return $permitidos;
    }

    /** Una lectura para la pagina, sin consultas ni joins por cada prenda. */
    public function paraCodigos(Collection $codigos): Collection
    {
        if ($codigos->isEmpty() || !Schema::hasTable('articulo_imagenes')) return collect();
        $codigos = $codigos->map(fn ($c) => $this->normalizar($c))->unique()->values();
        $maestro = collect();
        if (Schema::hasTable('maestro_codigos')) {
            foreach ($codigos->chunk(500) as $bloque) {
                $maestro = $maestro->concat(DB::table('maestro_codigos')
                    ->whereIn(DB::raw('UPPER(TRIM(cod_articulo))'), $bloque->all())
                    ->get(['cod_articulo', 'cod_imagen', 'cod_base']));
            }
        }
        $maestro = $maestro->groupBy(fn ($m) => $this->normalizar($m->cod_articulo));
        $candidatos = $codigos->mapWithKeys(function ($codigo) use ($maestro) {
            $variantes = $maestro->get($codigo, collect());
            $exacta = $variantes->firstWhere('cod_articulo', $codigo);
            // No elegir un alias al azar si el catalogo tiene claves ambiguas.
            $fila = $exacta ?: ($variantes->count() === 1 ? $variantes->first() : null);
            return [$codigo => $this->claves($codigo, $fila)];
        });
        $fotos = collect();
        foreach ($candidatos->flatten()->unique()->chunk(500) as $bloque) {
            $fotos = $fotos->concat(DB::table('articulo_imagenes')->whereIn('codigo_imagen', $bloque->all())->get());
        }
        $fotos = $fotos->keyBy('codigo_imagen');
        $resuelto = collect();
        foreach ($candidatos as $codigo => $claves) {
            foreach ($claves as $clave) {
                if ($foto = $fotos->get($clave)) {
                    $resuelto->put($codigo, $foto);
                    break;
                }
            }
        }
        return $resuelto;
    }

    public function guardar(string $nombre, string $contenido, array $datos, ?int $usuario): string
    {
        $info = $contenido !== '' ? @getimagesizefromstring($contenido) : false;
        $extensiones = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
        if (!$info || !isset($extensiones[$info[2]])) {
            throw new \DomainException('No contiene una imagen JPG, PNG o WEBP válida.');
        }
        if (strlen($contenido) > 30 * 1024 * 1024) throw new \DomainException('La imagen supera los 30 MB.');
        $codigo = $datos['codigo_base'];
        $ruta = 'articulos-imagenes/'.$codigo.'_'.bin2hex(random_bytes(8)).'.'.$extensiones[$info[2]];
        $disco = Storage::disk('public');
        if (!$disco->put($ruta, $contenido)) throw new \RuntimeException('No se pudo guardar la imagen.');
        try {
            $anterior = DB::transaction(function () use ($codigo, $ruta, $nombre, $datos, $usuario) {
                $anterior = DB::table('articulo_imagenes')->where('codigo_imagen', $codigo)->lockForUpdate()->first();
                DB::table('articulo_imagenes')->upsert([[
                    'codigo_imagen' => $codigo, 'imagen' => $ruta,
                    'nombre_archivo' => mb_substr($nombre, 0, 255),
                    'valor_v' => $datos['valor_v'] === null ? null : mb_substr($datos['valor_v'], 0, 64),
                    'valor_c' => $datos['valor_c'] === null ? null : mb_substr($datos['valor_c'], 0, 64),
                    'ot' => $datos['ot'] === null ? null : mb_substr($datos['ot'], 0, 30),
                    'descripcion_archivo' => $datos['descripcion_archivo'],
                    'fecha_archivo' => $datos['fecha_archivo'], 'user_id' => $usuario,
                    'created_at' => now(), 'updated_at' => now(),
                ]], ['codigo_imagen'], ['imagen', 'nombre_archivo', 'valor_v', 'valor_c', 'ot',
                    'descripcion_archivo', 'fecha_archivo', 'user_id', 'updated_at']);
                return $anterior;
            });
        } catch (\Throwable $e) {
            $disco->delete($ruta);
            throw $e;
        }
        if ($anterior && $this->rutaValida($anterior->imagen)) {
            try {
                $disco->delete($anterior->imagen);
            } catch (\Throwable $e) {
                Log::warning('No se pudo retirar una foto reemplazada.', ['codigo' => $codigo]);
            }
        }
        return $anterior ? 'reemplazadas' : 'nuevas';
    }

    public function rutaValida(string $ruta): bool
    {
        return (bool) preg_match('~^articulos-imagenes/[0-9]{9}(?:[A-Z]{2})?_[a-f0-9]{16}\.(?:jpg|png|webp)$~', $ruta);
    }
}
