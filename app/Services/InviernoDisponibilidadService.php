<?php

namespace App\Services;

use App\Support\InviernoDocumento as Documento;
use App\Support\InviernoValorizacion as Valorizacion;
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
        $documentos = $this->trazabilidad->panorama(soloDisponibilidad: true);
        $filasMaestro = $this->filasMaestro($documentos->flatMap->entradas->pluck('codigo'));
        $maestro = $this->clasificarMaestro($filasMaestro);
        $buscar = Documento::texto($filtros['buscar'] ?? '');
        $codigoFiltrado = empty($filtros['codigo']) ? null : Documento::texto($filtros['codigo']);
        $opcionesGrupos = [];
        $lineas = collect();
        $sinDetalle = collect();

        foreach ($documentos as $documento) {
            $coincide = (empty($filtros['local']) || (int) $documento->cod_sucursal_origen === (int) $filtros['local'])
                && (empty($filtros['estado']) || $documento->estado === $filtros['estado']);
            $referencia = $documento->local.' '.$documento->serie.' '.$documento->numero_remision;
            if ($coincide && $documento->entradas->isEmpty()
                && empty($filtros['grupo']) && empty($filtros['codigo'])
                && in_array($filtros['situacion'] ?? 'todos', ['', 'todos'], true)
                && ($buscar === '' || str_contains(Documento::texto($referencia), $buscar))) {
                $sinDetalle->push($documento);
            }
            $revisar = !empty($documento->incidencias);
            foreach ($documento->entradas as $entrada) {
                $codigo = Documento::texto($entrada->codigo);
                $articulo = $maestro->get($codigo);
                $grupo = ($articulo->grupo_disponibilidad ?? '') ?: ($this->nombre($entrada->descripcion) ?: 'SIN CLASIFICAR');
                $opcionesGrupos[$grupo] = $grupo;
                if (!$coincide || (!empty($filtros['grupo']) && $grupo !== $filtros['grupo'])
                    || ($esListado && $codigoFiltrado !== null && $codigo != $codigoFiltrado)) {
                    continue;
                }
                $descripcion = !$esListado || $buscar !== ''
                    ? trim((string) ($entrada->descripcion ?: ($articulo->articulo ?? '')))
                    : '';
                if ($buscar !== '' && !str_contains(Documento::texto($grupo.' '.$codigo.' '.$descripcion.' '.$referencia), $buscar)) {
                    continue;
                }

                $fila = [
                    'documento_id' => (int) $documento->id, 'codigo' => $codigo, 'grupo' => $grupo,
                    'revisar' => $revisar,
                    'costo_centavos' => Valorizacion::centavos($entrada->costo_unitario),
                    'precio_centavos' => Valorizacion::centavos($entrada->precio_venta),
                ] + Documento::cantidadesEntrada($entrada, $revisar);
                if (!$esListado) {
                    $fila += [
                        'entrada_id' => (int) $entrada->id, 'local_codigo' => (int) $documento->cod_sucursal_origen,
                        'local' => $documento->local, 'serie' => $documento->serie, 'numero_remision' => $documento->numero_remision,
                        'descripcion' => $descripcion, 'cantidad_remision' => (int) $entrada->cantidad,
                        'estado_recepcion' => $entrada->estado, 'estado_documento' => $documento->estado,
                        'estado_documento_texto' => $documento->estado_texto, 'fecha_remision' => Documento::fechaSalida($entrada),
                        'fecha_recepcion' => $entrada->fecha_recepcion, 'incidencias' => $documento->incidencias,
                    ];
                }
                $lineas->push((object) $fila);
            }
        }
        $origenes = $esListado ? $documentos->where('cod_sucursal_origen', '>', 1)->pluck('local', 'cod_sucursal_origen') : null;
        // Liberar el panorama antes de agrupar las filas del informe.
        unset($documentos);

        $opcionesGrupos = collect($opcionesGrupos)->unique()->sort()->values();
        $codigos = collect();
        if (!$esListado) {
            $codigos = $lineas->unique('codigo')->sortBy('codigo')->mapWithKeys(fn ($l) => [$l->codigo => $l->descripcion]);
            if ($codigoFiltrado !== null) {
                $lineas = $lineas->where('codigo', $codigoFiltrado)->values();
            }
        }

        $situacion = $filtros['situacion'] ?? 'todos';
        $grupos = $this->agrupar($lineas)->filter(fn ($grupo) => match ($situacion) {
            'disponibles' => $grupo->disponible > 0,
            'pendientes' => $grupo->pendiente > 0,
            'revisar' => $grupo->lineas_revision > 0,
            default => true,
        });
        $orden = $filtros['orden'] ?? 'disponible';
        $grupos = $grupos->sort(function ($a, $b) use ($orden) {
            return ($orden === 'grupo' ? 0 : $b->{$orden} <=> $a->{$orden}) ?: strcmp($a->grupo, $b->grupo);
        })->values();
        $permitidos = array_fill_keys($grupos->pluck('grupo')->all(), true);
        $lineas = $lineas->filter(fn ($l) => isset($permitidos[$l->grupo]))->values();
        $resumen = $this->totales($lineas);
        $resumen['grupos'] = $grupos->count();
        $resumen['sin_detalle'] = $sinDetalle->count();
        if ($esListado) {
            $resumenImagenes = $this->resumenImagenes($lineas, $resumen, $filasMaestro);
            // El registro conserva el nombre de los orígenes fuera del catálogo.
            $catalogo = DB::table('sucursal')->where('cod_suc', '>', 1)->pluck('suc_descri', 'cod_suc');
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
            // Priorizar la coincidencia exacta si el catálogo contiene variantes.
            $articulo = $variantes->firstWhere('cod_articulo', $codigo);
            if (!$articulo) {
                $grupos = $variantes->map(fn ($m) => $this->nombre($m->grupo) ?: $this->nombre($m->grupo_plan))->unique();
                $articulo = $grupos->count() === 1 ? $variantes->first() : null;
            }
            if ($articulo) {
                $articulo->grupo_disponibilidad = $this->nombre($articulo->grupo) ?: $this->nombre($articulo->grupo_plan);
            }
            return $articulo;
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
        $valorizacion = new Valorizacion();
        $documentos = [];
        $codigos = [];
        $revision = 0;
        foreach ($lineas as $linea) {
            foreach (self::CANTIDADES as $columna) {
                $cantidad = $linea->{$columna} ?? 0;
                $totales[$columna] += $cantidad;
                if ($cantidad > 0) {
                    $valorizacion->sumar($columna, $cantidad, $linea->costo_centavos, $linea->precio_centavos);
                }
            }
            $documentos[$linea->documento_id] = true;
            $codigos[] = $linea->codigo;
            $revision += (int) $linea->revisar;
        }
        $totales['documentos'] = count($documentos);
        $totales['codigos'] = count(array_unique($codigos, SORT_REGULAR));
        $totales['lineas_revision'] = $revision;
        $totales['valorizacion'] = $valorizacion->totales();
        return $totales;
    }

    private function agrupar(Collection $lineas): Collection
    {
        return $lineas->groupBy('grupo')->map(fn ($filas, $grupo) =>
            (object) array_merge(['grupo' => $grupo], $this->totales($filas)))->values();
    }
}
