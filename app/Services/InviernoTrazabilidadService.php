<?php

namespace App\Services;

use App\Support\InviernoDocumento as Documento;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InviernoTrazabilidadService
{
    public const ESTADOS = [
        'SIN_REMISION' => 'Sin remisión importada',
        'PENDIENTE_RECEPCION' => 'Pendiente en Central',
        'RECEPCION_PARCIAL' => 'Recepción parcial en Central',
        'EN_CENTRAL' => 'En Central · sin salida asociada',
        'ENVIO_PARCIAL' => 'Envío parcial a locales',
        'EN_TRANSITO_LOCALES' => 'En tránsito a locales',
        'COMPLETO' => 'Recibido en locales',
        'REVISAR' => 'Requiere revisión',
    ];

    public function locales(): Collection
    {
        $catalogo = DB::table('sucursal')->where('cod_suc', '>', 0)->where('cod_suc', '<>', 1)
            ->pluck('suc_descri', 'cod_suc');
        $observados = DB::table('ot_logistica_remisiones')->where('cod_sucursal_salida', '>', 0)
            ->where('cod_sucursal_salida', '<>', 1)->whereNotNull('sucursal_salida')
            ->select('cod_sucursal_salida', 'sucursal_salida')->distinct()->get();
        foreach ($observados as $fila) {
            if (!$catalogo->has($fila->cod_sucursal_salida)) {
                $catalogo->put($fila->cod_sucursal_salida, $fila->sucursal_salida);
            }
        }
        return $catalogo->sort();
    }

    public function mapaLocales(): array
    {
        $mapa = [];
        $catalogo = DB::table('sucursal')->where('cod_suc', '>', 0)->where('cod_suc', '<>', 1)
            ->pluck('suc_descri', 'cod_suc');
        foreach ($catalogo as $codigo => $nombre) {
            $clave = Documento::local($nombre);
            if ($clave !== '' && !ctype_digit($clave)) {
                $mapa[$clave][(int) $codigo] = true;
            }
            $mapa[(string) $codigo] = [(int) $codigo => true];
        }
        // El catálogo prevalece: una etiqueta histórica errónea en una remisión
        // no debe volver ambiguo un local ya identificado en el maestro.
        $observados = [];
        foreach (DB::table('ot_logistica_remisiones')->where('cod_sucursal_salida', '>', 0)
            ->where('cod_sucursal_salida', '<>', 1)
            ->select('cod_sucursal_salida', 'sucursal_salida')->distinct()->get() as $fila) {
            $codigo = (int) $fila->cod_sucursal_salida;
            $mapa[(string) $codigo] = [$codigo => true];
            $clave = Documento::local($fila->sucursal_salida);
            if ($clave !== '' && !ctype_digit($clave) && !isset($mapa[$clave])) {
                $observados[$clave][$codigo] = true;
            }
        }
        return $mapa + $observados;
    }

    public function registrar(array $fila, ?int $usuario, ?array $mapa = null, ?Collection $locales = null): bool
    {
        $mapa = $mapa ?? $this->mapaLocales();
        $locales = $locales ?? $this->locales();
        $local = Documento::local($fila['local'] ?? '');
        $codigos = array_keys($mapa[$local] ?? []);
        $serie = Documento::texto($fila['serie'] ?? '');
        $numero = Documento::numero($fila['nro'] ?? null);
        $nombreIngresado = Documento::texto($fila['local'] ?? '');
        if (count($codigos) === 0) {
            $this->error('Local «'.$nombreIngresado.'» no reconocido. Usá un nombre del catálogo o su código de sucursal.');
        }
        if (count($codigos) > 1) {
            $opciones = collect($codigos)->map(fn ($codigo) => $codigo.' · '.$locales->get($codigo))->implode('; ');
            $this->error('Local «'.$nombreIngresado.'» coincide con varias sucursales: '.$opciones.'. Indicá el código de la sucursal correcta.');
        }
        if ($serie === '' || mb_strlen($serie) > 20 || $numero === null) {
            $this->error('Completá SERIE (hasta 20 caracteres) y NRO (hasta 30 dígitos, sin decimales).');
        }
        return DB::table('invierno_remisiones')->insertOrIgnore([
            'cod_sucursal_origen' => $codigos[0],
            'local' => mb_substr(Documento::texto($locales->get($codigos[0])), 0, 100),
            'serie' => $serie,
            'numero_remision' => $numero,
            'creado_por' => $usuario,
            'created_at' => now(),
            'updated_at' => now(),
        ]) === 1;
    }

    public function entradasDocumento(object $documento): Collection
    {
        return DB::table('ot_logistica_remisiones as r')
            ->where('r.cod_sucursal_salida', $documento->cod_sucursal_origen)
            ->whereRaw('r.cod_sucursal_destino = 1')
            ->whereRaw('UPPER(TRIM(r.serie)) = ?', [$documento->serie])
            ->whereRaw(Documento::numeroSql('r').' = ?', [$documento->numero_remision])
            ->orderBy('r.codigo')->orderBy('r.id')->get();
    }

    /**
     * La lectura usa los datos vivos del importador general. Nunca copia estados
     * ni cantidades: una reimportación también vuelve a auditar los vínculos.
     * Las consultas son por conjunto, sin una consulta por cada documento.
     */
    public function panorama(bool $soloDisponibilidad = false): Collection
    {
        $documentos = DB::table('invierno_remisiones')->orderByDesc('id')->get()->keyBy('id');
        if ($documentos->isEmpty()) return $documentos;

        $consultaEntradas = DB::table('invierno_remisiones as i')
            ->join('ot_logistica_remisiones as r', function ($join) {
                $join->on('r.cod_sucursal_salida', '=', 'i.cod_sucursal_origen')
                    // Predicado fijo del índice parcial de devoluciones a Central.
                    ->whereRaw('r.cod_sucursal_destino = 1')
                    ->whereRaw('UPPER(TRIM(r.serie)) = i.serie')
                    ->whereRaw(Documento::numeroSql('r').' = i.numero_remision');
            });
        $columnas = $soloDisponibilidad ? [
            'r.id', 'r.cod_sucursal_salida', 'r.cod_sucursal_destino', 'r.serie', 'r.numero_remision',
            'r.codigo', 'r.descripcion', 'r.cantidad', 'r.estado', 'r.fecha_remision', 'r.fecha_creacion', 'r.fecha_recepcion',
        ] : ['r.*'];
        $entradas = $consultaEntradas->get([...$columnas, 'i.id as invierno_remision_id']);
        $activas = DB::table('invierno_asignaciones')->whereNull('anulada_at')->get();
        $movimientos = $entradas->keyBy('id');
        $faltantes = $activas->pluck('remision_entrada_id')->merge($activas->pluck('remision_salida_id'))
            ->unique()->diff($movimientos->keys());
        foreach ($faltantes->chunk(2000) as $ids) {
            foreach (DB::table('ot_logistica_remisiones as r')->whereIn('r.id', $ids)->get($columnas) as $fila) {
                $movimientos->put($fila->id, $fila);
            }
        }
        $sumasEntrada = $activas->groupBy('remision_entrada_id')->map->sum('cantidad');
        $sumasSalida = $activas->groupBy('remision_salida_id')->map->sum('cantidad');
        $porDocumento = $entradas->groupBy('invierno_remision_id');
        $vinculosDocumento = $activas->groupBy('invierno_remision_id');
        $hoy = now()->toDateString();

        foreach ($documentos as $documento) {
            $lineas = $porDocumento->get($documento->id, collect());
            $incidencias = [];
            $total = 0;
            $confirmado = 0;
            $codigos = [];
            $duplicadas = false;
            foreach ($lineas as $linea) {
                $problema = Documento::problema($linea, $hoy);
                if ($problema) {
                    $incidencias[] = 'Entrada #'.$linea->id.': '.$problema;
                }
                $total += max(0, (int) $linea->cantidad);
                $linea->recibida = !$problema && Documento::recibido($linea, $hoy);
                if ($linea->recibida) {
                    $confirmado += (int) $linea->cantidad;
                }
                $linea->asignada = (int) $sumasEntrada->get($linea->id, 0);
                $linea->disponible = $linea->recibida ? (int) $linea->cantidad - $linea->asignada : 0;
                $linea->problema = $problema;
                $codigo = Documento::texto($linea->codigo);
                $duplicadas = $duplicadas || isset($codigos[$codigo]);
                $codigos[$codigo] = true;
            }
            if ($duplicadas) {
                $incidencias[] = 'Hay líneas duplicadas para el mismo código completo. Revisá las remisiones importadas.';
            }
            $remitido = 0;
            $recibidoLocal = 0;
            $porRevisar = 0;
            foreach ($vinculosDocumento->get($documento->id, collect()) as $vinculo) {
                $entrada = $movimientos->get($vinculo->remision_entrada_id);
                $salida = $movimientos->get($vinculo->remision_salida_id);
                $problema = $this->problemaVinculo($documento, $entrada, $salida, $hoy);
                if (!$problema && (Documento::texto($entrada->codigo) !== $vinculo->codigo
                    || Documento::texto($salida->serie) !== $vinculo->serie_salida
                    || Documento::numero($salida->numero_remision) !== $vinculo->numero_salida
                    || (int) $salida->cod_sucursal_destino !== (int) $vinculo->cod_sucursal_destino)) {
                    $problema = 'La identidad del artículo o de la remisión de salida cambió después de asociar.';
                }
                if ((int) $vinculo->cantidad <= 0) {
                    $problema = 'Cantidad asociada inválida.';
                } elseif ($entrada && $sumasEntrada->get($entrada->id, 0) > (int) $entrada->cantidad) {
                    $problema = 'Las asociaciones superan la cantidad actual de entrada.';
                } elseif ($salida && $sumasSalida->get($salida->id, 0) > (int) $salida->cantidad) {
                    $problema = 'Las asociaciones superan la cantidad actual de salida.';
                }
                $vinculo->entrada = $entrada;
                $vinculo->salida = $salida;
                $vinculo->problema = $problema;
                $vinculo->recibida = !$problema && Documento::recibido($salida, $hoy);
                if ($problema) {
                    $incidencias[] = 'Asociación #'.$vinculo->id.': '.$problema;
                    $porRevisar += (int) $vinculo->cantidad;
                    continue;
                }
                $remitido += (int) $vinculo->cantidad;
                if ($vinculo->recibida) {
                    $recibidoLocal += (int) $vinculo->cantidad;
                }
            }
            $documento->entradas = $lineas;
            $documento->asignaciones = $vinculosDocumento->get($documento->id, collect());
            $documento->incidencias = array_values(array_unique($incidencias));
            $documento->total = $total;
            $documento->confirmado_central = $confirmado;
            $documento->pendiente_central = max(0, $total - $confirmado);
            $documento->remitido = $remitido;
            $documento->recibido_local = $recibidoLocal;
            $documento->transito_local = $remitido - $recibidoLocal;
            $documento->por_revisar = $porRevisar;
            $documento->saldo_central = $incidencias ? null : $confirmado - $remitido;
            $documento->estado = $this->estado($documento);
            $documento->estado_texto = self::ESTADOS[$documento->estado];
        }
        return $documentos->values();
    }

    private function estado(object $d): string
    {
        if ($d->incidencias) return 'REVISAR';
        if ($d->entradas->isEmpty()) return 'SIN_REMISION';
        if ($d->pendiente_central > 0) return $d->confirmado_central > 0 ? 'RECEPCION_PARCIAL' : 'PENDIENTE_RECEPCION';
        if ($d->remitido === 0) return 'EN_CENTRAL';
        if ($d->remitido < $d->confirmado_central) return 'ENVIO_PARCIAL';
        return $d->transito_local > 0 ? 'EN_TRANSITO_LOCALES' : 'COMPLETO';
    }

    public function problemaVinculo(object $documento, ?object $entrada, ?object $salida, ?string $hoy = null): ?string
    {
        if (!$entrada || !$salida || !Documento::coincideEntrada($documento, $entrada)) {
            return 'La entrada ya no corresponde al documento registrado.';
        }
        if (Documento::problema($entrada, $hoy) || !Documento::recibido($entrada, $hoy)) {
            return 'La entrada no tiene una recepción válida y confirmada en Central.';
        }
        if ((int) $salida->cod_sucursal_salida !== 1 || (int) $salida->cod_sucursal_destino <= 0
            || (int) $salida->cod_sucursal_destino === 1) {
            return 'La salida debe ser desde Casa Central hacia otra sucursal.';
        }
        if (Documento::texto($entrada->codigo) !== Documento::texto($salida->codigo)) {
            return 'El código completo (incluye color y talle) no coincide.';
        }
        if ($problema = Documento::problema($salida, $hoy)) {
            return 'Salida: '.$problema;
        }
        if (Documento::fechaSalida($salida) < $entrada->fecha_recepcion) {
            return 'La salida es anterior a la recepción de estas prendas en Central.';
        }
        return null;
    }

    public function asociar(int $documentoId, int $entradaId, int $salidaId, int $cantidad, ?int $usuario, ?string $observacion): void
    {
        DB::transaction(function () use ($documentoId, $entradaId, $salidaId, $cantidad, $usuario, $observacion) {
            $documento = DB::table('invierno_remisiones')->where('id', $documentoId)->lockForUpdate()->first();
            abort_unless($documento, 404);
            // Todos los escritores bloquean las mismas líneas en orden estable.
            // También se comparte el saldo de salida entre documentos distintos.
            $movimientos = DB::table('ot_logistica_remisiones')->whereIn('id', [$entradaId, $salidaId])
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $entrada = $movimientos->get($entradaId);
            $salida = $movimientos->get($salidaId);
            if ($problema = $this->problemaVinculo($documento, $entrada, $salida)) {
                $this->error($problema);
            }
            $entradas = $this->entradasDocumento($documento);
            if ($entradas->filter(fn ($r) => Documento::texto($r->codigo) === Documento::texto($entrada->codigo))->count() !== 1) {
                $this->error('Hay entradas duplicadas para este código. Corregí la importación general antes de asociar.');
            }
            $activas = DB::table('invierno_asignaciones')->whereNull('anulada_at');
            if ((clone $activas)->where('remision_entrada_id', $entradaId)->where('remision_salida_id', $salidaId)->exists()) {
                $this->error('Este vínculo ya existe. Para corregirlo, anulalo con su motivo y volvé a asociar.');
            }
            $usadaEntrada = (int) (clone $activas)->where('remision_entrada_id', $entradaId)->sum('cantidad');
            $usadaSalida = (int) (clone $activas)->where('remision_salida_id', $salidaId)->sum('cantidad');
            $disponible = min((int) $entrada->cantidad - $usadaEntrada, (int) $salida->cantidad - $usadaSalida);
            if ($cantidad <= 0 || $cantidad > $disponible) {
                $this->error('La cantidad supera el saldo disponible de entrada o de salida (máximo '.$disponible.').');
            }
            DB::table('invierno_asignaciones')->insert([
                'invierno_remision_id' => $documentoId,
                'remision_entrada_id' => $entradaId,
                'remision_salida_id' => $salidaId,
                'cantidad' => $cantidad,
                'codigo' => Documento::texto($entrada->codigo),
                'serie_salida' => Documento::texto($salida->serie),
                'numero_salida' => Documento::numero($salida->numero_remision),
                'cod_sucursal_destino' => $salida->cod_sucursal_destino,
                'sucursal_destino' => $salida->sucursal_destino,
                'observacion' => $observacion,
                'creado_por' => $usuario,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, 3);
    }

    public function anular(int $documentoId, int $asignacionId, ?int $usuario, string $motivo): void
    {
        DB::transaction(function () use ($documentoId, $asignacionId, $usuario, $motivo) {
            $documento = DB::table('invierno_remisiones')->where('id', $documentoId)->lockForUpdate()->first();
            abort_unless($documento, 404);
            $asignacion = DB::table('invierno_asignaciones')->where('id', $asignacionId)
                ->where('invierno_remision_id', $documentoId)->first();
            abort_unless($asignacion, 404);
            DB::table('ot_logistica_remisiones')
                ->whereIn('id', [$asignacion->remision_entrada_id, $asignacion->remision_salida_id])
                ->orderBy('id')->lockForUpdate()->get();
            DB::table('invierno_asignaciones')->where('id', $asignacionId)->whereNull('anulada_at')->update([
                'anulada_at' => now(), 'anulada_por' => $usuario,
                'motivo_anulacion' => $motivo, 'updated_at' => now(),
            ]);
        }, 3);
    }

    private function error(string $mensaje): void
    {
        throw ValidationException::withMessages(['documento' => $mensaje]);
    }
}
