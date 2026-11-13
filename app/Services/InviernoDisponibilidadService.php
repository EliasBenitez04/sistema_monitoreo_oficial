<?php

namespace App\Services;

use App\Support\InviernoDocumento as Documento;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InviernoDisponibilidadService
{
    public const CANTIDADES = ['prendas', 'central', 'pendiente', 'remitido', 'recibido_local',
        'transito_local', 'disponible', 'por_revisar'];

    public function __construct(
        private InviernoTrazabilidadService $trazabilidad,
        private ArticuloImagenService $imagenes
    ) {
    }

    /** Consulta viva de las remisiones registradas; no crea nuevas asociaciones. */
    public function consultar(array $filtros = []): array
    {
        return $this->calcular($filtros, esListado: false);
    }

    public function listado(array $filtros): array
    {
        return $this->calcular($filtros, esListado: true);
    }

    private function calcular(array $filtros, bool $esListado): array
    {
        $documentos = $this->trazabilidad->panorama();
        $filasMaestro = $this->filasMaestro($documentos->flatMap->entradas->pluck('codigo'));
        $maestro = $this->clasificarMaestro($filasMaestro);
        $lineas = collect();
        foreach ($documentos as $documento) {
            $asignaciones = $documento->asignaciones->whereNull('problema')->groupBy('remision_entrada_id');
            foreach ($documento->entradas as $entrada) {
                $codigo = Documento::texto($entrada->codigo);
                $articulo = $maestro->get($codigo);
                $grupo = $this->nombre($articulo->grupo ?? '') ?: $this->nombre($articulo->grupo_plan ?? '');
                $grupo = $grupo ?: ($this->nombre($entrada->descripcion) ?: 'SIN CLASIFICAR');
                $vinculos = $asignaciones->get($entrada->id);
                $prendas = max(0, (int) $entrada->cantidad);
                $central = $entrada->recibida ? $prendas : 0;
                $remitido = (int) ($vinculos?->sum('cantidad') ?? 0);
                $recibido = (int) ($vinculos?->where('recibida', true)->sum('cantidad') ?? 0);
                // Mismo criterio que el seguimiento por remisión: si el documento
                // tiene incidencias, su saldo queda retenido hasta revisarlo.
                $revisar = !empty($documento->incidencias);
                $saldo = max(0, $central - $remitido);
                $lineas->push((object) [
                    'documento_id' => (int) $documento->id, 'entrada_id' => (int) $entrada->id,
                    'local_codigo' => (int) $documento->cod_sucursal_origen, 'local' => $documento->local,
                    'serie' => $documento->serie, 'numero_remision' => $documento->numero_remision,
                    'codigo' => $codigo, 'grupo' => $grupo,
                    'descripcion' => trim((string) ($entrada->descripcion ?: ($articulo->articulo ?? ''))),
                    'cantidad_remision' => (int) $entrada->cantidad, 'estado_recepcion' => $entrada->estado,
                    'estado_documento' => $documento->estado, 'estado_documento_texto' => $documento->estado_texto,
                    'fecha_remision' => Documento::fechaSalida($entrada),
                    'fecha_recepcion' => $entrada->fecha_recepcion,
                    'prendas' => $prendas, 'central' => $central, 'pendiente' => $prendas - $central,
                    'remitido' => $remitido, 'recibido_local' => $recibido, 'transito_local' => $remitido - $recibido,
                    'disponible' => $revisar ? null : $saldo, 'por_revisar' => $revisar ? $saldo : 0,
                    'revisar' => $revisar, 'incidencias' => $documento->incidencias,
                ]);
            }
        }

        $opcionesGrupos = $lineas->pluck('grupo')->unique()->sort()->values();
        $buscar = Documento::texto($filtros['buscar'] ?? '');
        $lineas = $lineas->filter(function ($linea) use ($filtros, $buscar) {
            return (empty($filtros['local']) || $linea->local_codigo === (int) $filtros['local'])
                && (empty($filtros['grupo']) || $linea->grupo === $filtros['grupo'])
                && (empty($filtros['estado']) || $linea->estado_documento === $filtros['estado'])
                && ($buscar === '' || str_contains(Documento::texto($linea->grupo.' '.$linea->codigo.' '
                    .$linea->descripcion.' '.$linea->local.' '.$linea->serie.' '.$linea->numero_remision), $buscar));
        })->values();
        $codigos = $esListado
            ? collect()
            : $lineas->unique('codigo')->sortBy('codigo')->mapWithKeys(fn ($l) => [$l->codigo => $l->descripcion]);
        if (!empty($filtros['codigo'])) {
            $lineas = $lineas->where('codigo', Documento::texto($filtros['codigo']))->values();
        }
        $grupos = $this->agrupar($lineas)->filter(function ($grupo) use ($filtros) {
            switch ($filtros['situacion'] ?? 'todos') {
                case 'disponibles': return $grupo->disponible > 0;
                case 'pendientes': return $grupo->pendiente > 0;
                case 'revisar': return $grupo->lineas_revision > 0;
                default: return true;
            }
        });
        $orden = $filtros['orden'] ?? 'disponible';
        $grupos = $grupos->sort(function ($a, $b) use ($orden) {
            return ($orden === 'grupo' ? 0 : $b->{$orden} <=> $a->{$orden}) ?: strcmp($a->grupo, $b->grupo);
        })->values();
        $permitidos = array_fill_keys($grupos->pluck('grupo')->all(), true);
        $lineas = $lineas->filter(fn ($l) => isset($permitidos[$l->grupo]))->values();
        $resumen = $this->totales($lineas);
        $resumen['grupos'] = $grupos->count();
        $sinDetalle = $documentos->filter(fn ($d) => $d->entradas->isEmpty()
            && (empty($filtros['local']) || (int) $d->cod_sucursal_origen === (int) $filtros['local'])
            && (empty($filtros['estado']) || $d->estado === $filtros['estado'])
            && empty($filtros['grupo']) && empty($filtros['codigo'])
            && in_array($filtros['situacion'] ?? 'todos', ['', 'todos'], true)
            && ($buscar === '' || str_contains(Documento::texto($d->local.' '.$d->serie.' '.$d->numero_remision), $buscar)))->values();
        $resumen['sin_detalle'] = $sinDetalle->count();
        if ($esListado) {
            $resumenImagenes = $this->resumenImagenes($lineas, $resumen, $filasMaestro);
            // El registro conserva el nombre de los orígenes fuera del catálogo.
            $catalogo = DB::table('sucursal')->where('cod_suc', '>', 1)->pluck('suc_descri', 'cod_suc');
            $origenes = $documentos->where('cod_sucursal_origen', '>', 1)->pluck('local', 'cod_sucursal_origen');
            $locales = $catalogo->union($origenes)->sort();
            return compact('grupos', 'resumen', 'opcionesGrupos', 'resumenImagenes', 'locales');
        }
        return compact('grupos', 'lineas', 'resumen', 'opcionesGrupos', 'codigos', 'sinDetalle');
    }

    public function articulos(Collection $lineas, string $orden = 'disponible'): Collection
    {
        return $lineas->groupBy('codigo')->map(function ($filas, $codigo) {
            return (object) array_merge($this->totales($filas), [
                'codigo' => (string) $codigo,
                'descripcion' => $filas->pluck('descripcion')->filter()->unique()->sort()->implode(' | '),
                'grupo' => $filas->pluck('grupo')->unique()->sort()->implode(' | '),
            ]);
        })->sort(function ($a, $b) use ($orden) {
            return ($orden === 'codigo' ? 0 : $b->{$orden} <=> $a->{$orden}) ?: strcmp($a->codigo, $b->codigo);
        })->values();
    }

    private function nombre($valor): string
    {
        return trim(preg_replace('/[\p{Z}\s]+/u', ' ', Documento::texto($valor)));
    }

    private function filasMaestro(Collection $codigos): Collection
    {
        if ($codigos->isEmpty() || !Schema::hasTable('maestro_codigos')) return collect();
        $claves = array_unique($codigos->map(fn ($c) => Documento::texto($c))->all());
        $filas = collect();
        // Sin una consulta por prenda y sin joins que multipliquen cantidades.
        foreach (array_chunk($claves, 1000) as $bloque) {
            $filas = $filas->concat(DB::table('maestro_codigos')
                ->whereIn(DB::raw('UPPER(TRIM(cod_articulo))'), $bloque)
                ->get(['cod_articulo', 'articulo', 'grupo', 'grupo_plan', 'cod_imagen', 'cod_base']));
        }
        return $filas;
    }

    private function clasificarMaestro(Collection $filas): Collection
    {
        return $filas->groupBy(fn ($m) => Documento::texto($m->cod_articulo))->map(function ($variantes, $codigo) {
            // Preferir la coincidencia exacta; un catálogo con claves que solo
            // difieren por mayúsculas no debe elegir al azar grupos distintos.
            $exacta = $variantes->firstWhere('cod_articulo', $codigo);
            if ($exacta) return $exacta;
            $grupos = $variantes->map(fn ($m) => $this->nombre($m->grupo) ?: $this->nombre($m->grupo_plan))->unique();
            return $grupos->count() === 1 ? $variantes->first() : null;
        })->filter();
    }

    private function resumenImagenes(Collection $lineas, array $resumen, Collection $filasMaestro): array
    {
        $codigos = $lineas->pluck('codigo')->unique();
        $sinImagen = $codigos->diff($this->imagenes->paraCodigos($codigos, $filasMaestro)->keys())->flip();
        $prendasSinImagen = (int) $lineas->sum(fn ($linea) => $sinImagen->has($linea->codigo) ? $linea->prendas : 0);
        return [
            'prendas_con_imagen' => $resumen['prendas'] - $prendasSinImagen,
            'prendas_sin_imagen' => $prendasSinImagen,
            'codigos_con_imagen' => $resumen['codigos'] - $sinImagen->count(),
            'codigos_sin_imagen' => $sinImagen->count(),
        ];
    }

    private function totales(Collection $lineas): array
    {
        $totales = array_fill_keys(self::CANTIDADES, 0);
        $documentos = [];
        $codigos = [];
        $revision = 0;
        foreach ($lineas as $linea) {
            foreach (self::CANTIDADES as $columna) {
                $totales[$columna] += $linea->{$columna} ?? 0;
            }
            $documentos[$linea->documento_id] = true;
            $codigos[] = $linea->codigo;
            $revision += (int) $linea->revisar;
        }
        $totales['documentos'] = count($documentos);
        $totales['codigos'] = count(array_unique($codigos, SORT_REGULAR));
        $totales['lineas_revision'] = $revision;
        return $totales;
    }

    private function agrupar(Collection $lineas): Collection
    {
        return $lineas->groupBy('grupo')->map(fn ($filas, $grupo) =>
            (object) array_merge(['grupo' => $grupo], $this->totales($filas)))->values();
    }
}
