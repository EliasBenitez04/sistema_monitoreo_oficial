<?php

namespace App\Services;

use App\Models\Ot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LogisticaConciliacionService
{
    private const PROCESO_INGRESO_TERMINACION = 'TERMINACION - TERMINACION';
    private const PROCESO_PRODUCTO_TERMINADO = 'TERMINACION - PRODUCTO TERMINADO';
    private const PROCESO_LOGISTICA = 'LOGISTICA - LOGISTICA Y DISTRIBUCION';

    /**
     * Concilia PLAN vs MOVIMIENTO REAL para las OTs indicadas.
     *
     * Fuente de verdad por dimensión:
     * - objetivo: ot.cantidad_orden
     * - producción: ot_trazabilidad
     * - plan: ot_logistica_detalle
     * - movimiento real: ot_logistica_remisiones
     *
     * No modifica datos; solo calcula.
     */
    public function conciliarPorIds(array $idsOt): Collection
    {
        $idsOt = collect($idsOt)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($idsOt)) {
            return collect();
        }

        $ots = Ot::query()
            ->whereIn('id_ot', $idsOt)
            ->get([
                'id_ot',
                'nro_ot',
                'codigo',
                'descripcion',
                'cantidad_orden',
                'estado',
            ])
            ->keyBy('id_ot');

        $trazabilidad = DB::table('ot_trazabilidad')
            ->whereIn('id_ot', $idsOt)
            ->whereIn('proceso', [
                self::PROCESO_INGRESO_TERMINACION,
                self::PROCESO_PRODUCTO_TERMINADO,
                self::PROCESO_LOGISTICA,
            ])
            ->groupBy('id_ot', 'proceso')
            ->select(
                'id_ot',
                'proceso',
                DB::raw('SUM(resultado) as cantidad'),
                DB::raw('MIN(fecha_proceso) as primera_fecha'),
                DB::raw('MAX(fecha_proceso) as ultima_fecha')
            )
            ->get()
            ->groupBy('id_ot');

        $planPorOt = collect();

        if (Schema::hasTable('ot_logistica_detalle')) {
            $planPorOt = DB::table('ot_logistica_detalle as d')
                ->join(
                    'ot_trazabilidad as t',
                    't.id_trazabilidad',
                    '=',
                    'd.id_trazabilidad'
                )
                ->whereIn('d.id_ot', $idsOt)
                ->where('t.proceso', self::PROCESO_LOGISTICA)
                ->groupBy('d.id_ot')
                ->select(
                    'd.id_ot',
                    DB::raw('SUM(d.cantidad) as planificado'),
                    DB::raw('COUNT(DISTINCT d.sucursal) as destinos'),
                    DB::raw('MIN(t.fecha_proceso) as primera_fecha_plan'),
                    DB::raw('MAX(t.fecha_proceso) as ultima_fecha_plan')
                )
                ->get()
                ->keyBy('id_ot');
        }

        $remisionesPorOt = $this->remisionesOriginalesPorOt($idsOt);

        /*
         * Complementos/reenvíos que cierran saldos reales del plan.
         * Se calculan por detalle logístico y se capan por la cantidad
         * planificada para no convertir redistribuciones en prendas nuevas.
         */
        $coberturaPlanPorOt = $this->coberturaPlanPorOt($idsOt);

        return collect($idsOt)
            ->mapWithKeys(function (int $idOt) use (
                $ots,
                $trazabilidad,
                $planPorOt,
                $remisionesPorOt,
                $coberturaPlanPorOt
            ) {
                $ot = $ots->get($idOt);

                if (!$ot) {
                    return [];
                }

                $movimientos = collect($trazabilidad->get($idOt, collect()));

                $ingreso = $movimientos->firstWhere(
                    'proceso',
                    self::PROCESO_INGRESO_TERMINACION
                );

                $pt = $movimientos->firstWhere(
                    'proceso',
                    self::PROCESO_PRODUCTO_TERMINADO
                );

                $logistica = $movimientos->firstWhere(
                    'proceso',
                    self::PROCESO_LOGISTICA
                );

                $plan = $planPorOt->get($idOt);
                $remision = $remisionesPorOt->get($idOt);
                $coberturaPlan = $coberturaPlanPorOt->get($idOt);

                $objetivo = max(0, (int) $ot->cantidad_orden);

                /*
                 * La cantidad original de la OT es una referencia histórica,
                 * NO la obligación de cada proceso posterior.
                 *
                 * Los procesos continúan con la cantidad real recibida del
                 * proceso anterior. Ejemplo:
                 * OT original 360 -> Ingreso Terminación 320 -> PT 320.
                 * En ese caso Terminación está COMPLETA: no faltan 40.
                 *
                 * Los movimientos pueden ser incrementales:
                 * 319 + 1 reparada = 320.
                 */
                $ingresoRaw = max(0, (int) ($ingreso->cantidad ?? 0));
                $ptRaw = max(0, (int) ($pt->cantidad ?? 0));

                $ingresoEfectivo = $ingresoRaw;
                $ptEfectivo = $ptRaw;

                $planRaw = max(0, (int) ($plan->planificado ?? 0));

                /*
                 * El plan se conserva completo para mostrar lo importado.
                 * Para pendientes físicos solo se considera la porción del plan
                 * que ya puede existir porque llegó a Producto Terminado.
                 */
                $planDisponible = min($ptEfectivo, $planRaw);

                $remitidoCentralRaw = max(
                    0,
                    (int) ($remision->remitido_original ?? 0)
                );

                $recibidoCentralRaw = max(
                    0,
                    (int) ($remision->recibido_original ?? 0)
                );

                $remitidoCierre = max(
                    0,
                    (int) ($coberturaPlan->remitido_cierre ?? 0)
                );

                $recibidoCierre = max(
                    0,
                    (int) ($coberturaPlan->recibido_cierre ?? 0)
                );

                $remitidoRaw =
                    $remitidoCentralRaw + $remitidoCierre;

                $recibidoRaw =
                    $recibidoCentralRaw + $recibidoCierre;

                $remitidoEfectivo = min($ptEfectivo, $remitidoRaw);
                $recibidoEfectivo = min(
                    $remitidoEfectivo,
                    $recibidoRaw
                );

                /*
                 * Terminación se mide contra lo que realmente ingresó al área,
                 * no contra cantidad_orden.
                 */
                $faltaTerminacion = max(
                    0,
                    $ingresoEfectivo - $ptEfectivo
                );

                /*
                 * PLAN puro: cuánto PT todavía no está explicado por el plan.
                 * Se conserva como diagnóstico aunque ENVIOS ya haya cubierto
                 * físicamente esas prendas.
                 */
                $sinDestinoPlan = max(
                    0,
                    $ptEfectivo - $planDisponible
                );

                /*
                 * Pendiente operativo de destino:
                 * una remisión original ya demuestra que la prenda tuvo destino,
                 * aunque ot_logistica_detalle esté incompleto.
                 */
                $asignadoEfectivo = max(
                    $planDisponible,
                    $remitidoEfectivo
                );

                $sinDestino = max(
                    0,
                    $ptEfectivo - $asignadoEfectivo
                );

                /*
                 * El objetivo de Logística es TODO Producto Terminado.
                 * El detalle por sucursal puede quedar corto (ej. 277 de 280),
                 * pero eso no reduce lo que debe salir.
                 */
                $pendienteRemitirPlan = max(
                    0,
                    $planDisponible - $remitidoEfectivo
                );

                $pendienteRealSalida = max(
                    0,
                    $ptEfectivo - $remitidoEfectivo
                );

                // "Pendiente remitir" operativo = todo PT todavía sin salida.
                $pendienteRemitir = $pendienteRealSalida;

                $enTransito = max(
                    0,
                    $remitidoEfectivo - $recibidoEfectivo
                );

                $huecoPlanVsReal = max(
                    0,
                    $remitidoEfectivo - $planDisponible
                );

                $estado = $this->resolverEstado(
                    $faltaTerminacion,
                    $sinDestino,
                    $pendienteRemitir,
                    $enTransito,
                    $ptEfectivo,
                    $recibidoEfectivo
                );

                return [
                    $idOt => (object) [
                        'id_ot' => $idOt,
                        'nro_ot' => $ot->nro_ot,
                        'codigo' => $ot->codigo,
                        'descripcion' => $ot->descripcion,
                        'estado_ot' => $ot->estado,

                        'objetivo' => $objetivo,

                        'ingreso_terminacion_raw' => $ingresoRaw,
                        'ingreso_terminacion' => $ingresoEfectivo,

                        'producto_terminado_raw' => $ptRaw,
                        'producto_terminado' => $ptEfectivo,
                        'exceso_producto_terminado' => max(
                            0,
                            $ptRaw - $ingresoRaw
                        ),

                        /*
                         * Plan logística = objetivo de salida desde PT.
                         * El reparto cargado por sucursal queda separado.
                         */
                        'planificado_raw' => $planRaw,
                        'planificado' => $ptEfectivo,
                        'plan_objetivo' => $ptEfectivo,
                        'plan_detallado' => $planRaw,
                        'plan_disponible' => $planDisponible,
                        'sin_asignar_plan' => max(
                            0,
                            $ptEfectivo - $planDisponible
                        ),
                        'destinos' => (int) ($plan->destinos ?? 0),

                        // *_raw conserva el despacho central puro para
                        // auditoría; *_efectivo_raw suma el cierre reconocido.
                        'remitido_original_raw' => $remitidoCentralRaw,
                        'remitido_efectivo_raw' => $remitidoRaw,
                        'remitido_cierre' => $remitidoCierre,
                        'remitido_original' => $remitidoEfectivo,

                        'recibido_original_raw' => $recibidoCentralRaw,
                        'recibido_efectivo_raw' => $recibidoRaw,
                        'recibido_cierre' => $recibidoCierre,
                        'recibido_original' => $recibidoEfectivo,

                        'falta_terminacion' => $faltaTerminacion,
                        'sin_destino_plan' => $sinDestinoPlan,
                        'sin_destino' => $sinDestino,
                        'pendiente_remitir' => $pendienteRemitir,
                        'pendiente_remitir_plan' => $pendienteRemitirPlan,
                        'pendiente_real_salida' => $pendienteRealSalida,
                        'en_transito' => $enTransito,
                        'pendiente_confirmar' => $enTransito,

                        'asignado_efectivo' => $asignadoEfectivo,
                        'hueco_plan_vs_real' => $huecoPlanVsReal,

                        'estado_conciliacion' => $estado,

                        'primera_fecha_ingreso' =>
                            $ingreso->primera_fecha ?? null,
                        'ultima_fecha_ingreso' =>
                            $ingreso->ultima_fecha ?? null,

                        'primera_fecha_pt' =>
                            $pt->primera_fecha ?? null,
                        'ultima_fecha_pt' =>
                            $pt->ultima_fecha ?? null,

                        'primera_fecha_plan' =>
                            $plan->primera_fecha_plan
                            ?? $logistica->primera_fecha
                            ?? null,
                        'ultima_fecha_plan' =>
                            $plan->ultima_fecha_plan
                            ?? $logistica->ultima_fecha
                            ?? null,

                        'primera_remision' =>
                            $remision->primera_remision ?? null,
                        'ultima_remision' =>
                            $remision->ultima_remision ?? null,
                        'ultima_recepcion' =>
                            $remision->ultima_recepcion ?? null,

                        /*
                         * Alias temporales para no romper las vistas existentes.
                         * Después podremos limpiar estos nombres progresivamente.
                         */
                        'total_distribuido' => $planRaw,
                        'asignado_efectivo_legacy' => $asignadoEfectivo,
                        'faltante' => $sinDestino,
                        'hueco_detalle' => $huecoPlanVsReal,
                    ],
                ];
            });
    }

    public function totales(Collection $conciliaciones): object
    {
        return (object) [
            'ots' => $conciliaciones->count(),
            'objetivo' => (int) $conciliaciones->sum('objetivo'),
            'ingreso_terminacion' => (int) $conciliaciones
                ->sum('ingreso_terminacion'),
            'producto_terminado' => (int) $conciliaciones
                ->sum('producto_terminado'),
            'planificado' => (int) $conciliaciones
                ->sum('planificado'),
            'plan_detallado' => (int) $conciliaciones
                ->sum('plan_detallado'),
            'sin_asignar_plan' => (int) $conciliaciones
                ->sum('sin_asignar_plan'),
            'remitido' => (int) $conciliaciones
                ->sum('remitido_original'),
            'recibido' => (int) $conciliaciones
                ->sum('recibido_original'),
            'falta_terminacion' => (int) $conciliaciones
                ->sum('falta_terminacion'),
            'sin_destino' => (int) $conciliaciones
                ->sum('sin_destino'),
            'sin_destino_plan' => (int) $conciliaciones
                ->sum('sin_destino_plan'),
            'pendiente_remitir' => (int) $conciliaciones
                ->sum('pendiente_remitir'),
            'pendiente_real_salida' => (int) $conciliaciones
                ->sum('pendiente_real_salida'),
            'en_transito' => (int) $conciliaciones
                ->sum('en_transito'),
            'hueco_plan_vs_real' => (int) $conciliaciones
                ->sum('hueco_plan_vs_real'),

            'ots_falta_terminacion' => $conciliaciones
                ->where('falta_terminacion', '>', 0)
                ->count(),
            'ots_sin_destino' => $conciliaciones
                ->where('sin_destino', '>', 0)
                ->count(),
            'ots_pendiente_remitir' => $conciliaciones
                ->where('pendiente_remitir', '>', 0)
                ->count(),
            'ots_en_transito' => $conciliaciones
                ->where('en_transito', '>', 0)
                ->count(),
        ];
    }

    private function remisionesOriginalesPorOt(array $idsOt): Collection
    {
        if (
            empty($idsOt)
            || !Schema::hasTable('ot_logistica_remisiones')
        ) {
            return collect();
        }

        /*
         * Distribución original:
         * CASA CENTRAL / MATRIZ -> destino.
         *
         * COMERCIAL MATRIZ NO se considera origen central.
         * Los movimientos Local -> Local tampoco se vuelven a sumar.
         */
        return DB::table('ot_logistica_remisiones')
            ->whereIn('id_ot', $idsOt)
            ->where(function ($q) {
                $q->where('cod_sucursal_salida', 1)
                    ->orWhereRaw(
                        "UPPER(TRIM(COALESCE(sucursal_salida, ''))) = 'CASA CENTRAL'"
                    )
                    ->orWhereRaw(
                        "UPPER(TRIM(COALESCE(sucursal_salida, ''))) = 'MATRIZ'"
                    );
            })
            ->where(function ($q) {
                $q->whereNull('cod_sucursal_destino')
                    ->orWhere('cod_sucursal_destino', '<>', 1);
            })
            ->whereRaw(
                "UPPER(COALESCE(NULLIF(TRIM(sucursal_destino), ''), NULLIF(TRIM(sucursal_logistica), ''), '')) NOT IN ('', 'CASA CENTRAL', 'MATRIZ')"
            )
            ->groupBy('id_ot')
            ->select(
                'id_ot',
                DB::raw('SUM(cantidad) as remitido_original'),
                DB::raw(
                    'SUM(CASE WHEN fecha_recepcion IS NOT NULL THEN cantidad ELSE 0 END) as recibido_original'
                ),
                DB::raw('MIN(fecha_remision) as primera_remision'),
                DB::raw('MAX(fecha_remision) as ultima_remision'),
                DB::raw('MAX(fecha_recepcion) as ultima_recepcion')
            )
            ->get()
            ->keyBy('id_ot');
    }

    /**
     * Devuelve solamente el complemento que una remisión vinculada aporta
     * por encima del despacho central ya contado.
     *
     * Ejemplo:
     *   plan SL = 30
     *   Central -> SL = 29
     *   Comercial Matriz -> SL = 1
     *   cobertura efectiva = 30
     *
     * Si luego existe otra redistribución de 5 hacia SL, el detalle sigue
     * cubierto en 30 porque cada id_logistica_detalle se limita a d.cantidad.
     */
    private function coberturaPlanPorOt(array $idsOt): Collection
    {
        if (
            empty($idsOt)
            || !Schema::hasTable('ot_logistica_detalle')
            || !Schema::hasTable('ot_logistica_remisiones')
        ) {
            return collect();
        }

        return DB::table('ot_logistica_detalle as d')
            ->leftJoin('ot_logistica_remisiones as r', function ($join) {
                $join->on('r.id_logistica_detalle', '=', 'd.id')
                    ->on('r.id_ot', '=', 'd.id_ot');
            })
            ->whereIn('d.id_ot', $idsOt)
            ->groupBy('d.id_ot', 'd.id', 'd.cantidad')
            ->select(
                'd.id_ot',
                'd.id',
                'd.cantidad as plan',
                DB::raw(
                    'COALESCE(SUM(r.cantidad), 0) as movido_total'
                ),
                DB::raw(
                    "COALESCE(SUM(CASE WHEN r.cod_sucursal_salida = 1 OR UPPER(TRIM(COALESCE(r.sucursal_salida, ''))) IN ('CASA CENTRAL', 'MATRIZ') THEN r.cantidad ELSE 0 END), 0) as movido_central"
                ),
                DB::raw(
                    'COALESCE(SUM(CASE WHEN r.fecha_recepcion IS NOT NULL THEN r.cantidad ELSE 0 END), 0) as recibido_total'
                ),
                DB::raw(
                    "COALESCE(SUM(CASE WHEN r.fecha_recepcion IS NOT NULL AND (r.cod_sucursal_salida = 1 OR UPPER(TRIM(COALESCE(r.sucursal_salida, ''))) IN ('CASA CENTRAL', 'MATRIZ')) THEN r.cantidad ELSE 0 END), 0) as recibido_central"
                )
            )
            ->get()
            ->groupBy('id_ot')
            ->map(function ($filas) {
                $coberturaTotal = 0;
                $coberturaCentral = 0;
                $recibidoTotal = 0;
                $recibidoCentral = 0;

                foreach ($filas as $fila) {
                    $plan = max(0, (int) $fila->plan);

                    $cubierto = min(
                        $plan,
                        max(0, (int) $fila->movido_total)
                    );

                    $cubiertoCentral = min(
                        $plan,
                        max(0, (int) $fila->movido_central)
                    );

                    $confirmado = min(
                        $cubierto,
                        max(0, (int) $fila->recibido_total)
                    );

                    $confirmadoCentral = min(
                        $cubiertoCentral,
                        max(0, (int) $fila->recibido_central)
                    );

                    $coberturaTotal += $cubierto;
                    $coberturaCentral += $cubiertoCentral;
                    $recibidoTotal += $confirmado;
                    $recibidoCentral += $confirmadoCentral;
                }

                return (object) [
                    'remitido_cierre' => max(
                        0,
                        $coberturaTotal - $coberturaCentral
                    ),
                    'recibido_cierre' => max(
                        0,
                        $recibidoTotal - $recibidoCentral
                    ),
                ];
            });
    }

    private function resolverEstado(
        int $faltaTerminacion,
        int $sinDestino,
        int $pendienteRemitir,
        int $enTransito,
        int $productoTerminado,
        int $recibido
    ): string {
        if ($faltaTerminacion > 0) {
            return 'FALTA TERMINACION';
        }

        if ($sinDestino > 0) {
            return 'SIN DESTINO';
        }

        if ($pendienteRemitir > 0) {
            return 'PENDIENTE REMITIR';
        }

        if ($enTransito > 0) {
            return 'EN TRANSITO';
        }

        if ($productoTerminado > 0 && $recibido >= $productoTerminado) {
            return 'CONFIRMADO';
        }

        if ($productoTerminado > 0) {
            return 'PENDIENTE SALIDA';
        }

        return 'SIN PRODUCTO TERMINADO';
    }
}
