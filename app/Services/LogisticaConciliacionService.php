<?php

namespace App\Services;

use App\Models\Ot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LogisticaConciliacionService
{
    private const PROCESO_INGRESO_TERMINACION =
        'TERMINACION - INGRESO TERMINACION';

    private const PROCESO_TERMINACION =
        'TERMINACION - TERMINACION';

    private const PROCESO_PRODUCTO_TERMINADO =
        'TERMINACION - PRODUCTO TERMINADO';

    private const PROCESO_LOGISTICA =
        'LOGISTICA - LOGISTICA Y DISTRIBUCION';

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
                self::PROCESO_TERMINACION,
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

        // Retornos físicos LOCAL -> CASA CENTRAL importados desde ENVIOS.
        // No se acredita stock hasta existir fecha_recepcion en Casa Central.
        $devolucionesPorOt = $this->devolucionesPorOt($idsOt);

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
                $devolucionesPorOt,
                $coberturaPlanPorOt
            ) {
                $ot = $ots->get($idOt);

                if (!$ot) {
                    return [];
                }

                $movimientos = collect($trazabilidad->get($idOt, collect()));

                $preIngresoTerminacion = $movimientos->firstWhere(
                    'proceso',
                    self::PROCESO_INGRESO_TERMINACION
                );

                /*
                 * Este es el hito físico usado por la conciliación:
                 * TERMINACION - TERMINACION (95).
                 *
                 * Aunque el proceso 90 se llame INGRESO TERMINACION, en el
                 * flujo real es una etapa previa y no sustituye al 95.
                 */
                $ingreso = $movimientos->firstWhere(
                    'proceso',
                    self::PROCESO_TERMINACION
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
                $devolucion = $devolucionesPorOt->get($idOt);
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
                $preIngresoRaw = max(
                    0,
                    (int) ($preIngresoTerminacion->cantidad ?? 0)
                );

                /*
                 * Ingreso físico a Terminación = proceso 95.
                 */
                $ingresoRaw = max(
                    0,
                    (int) ($ingreso->cantidad ?? 0)
                );

                $ptRaw = max(
                    0,
                    (int) ($pt->cantidad ?? 0)
                );

                /*
                 * Los registros de trazabilidad pueden ser:
                 *
                 * - incrementales: 1.397 + 1 reparada = 1.398
                 * - repetición completa del mismo volumen: 1.398 + 1.398
                 *
                 * La cantidad de la OT es el techo físico. De esta manera una
                 * repetición completa no convierte una OT de 1.398 en 2.796,
                 * pero los incrementos reales sí pueden completar el objetivo.
                 */
                $preIngresoEfectivo = $objetivo > 0
                    ? min($objetivo, $preIngresoRaw)
                    : $preIngresoRaw;

                $ingresoEfectivo = $objetivo > 0
                    ? min($objetivo, $ingresoRaw)
                    : $ingresoRaw;

                $ptEfectivo = $objetivo > 0
                    ? min($objetivo, $ptRaw)
                    : $ptRaw;

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

                /*
                 * La OT no fabrica prendas nuevas al recibir una devolución.
                 * SALIDA NETA = remisiones desde Central - devoluciones
                 * efectivamente RECIBIDAS en Central.
                 *
                 * Una devolución con fecha_remision pero SIN fecha_recepcion
                 * continúa en tránsito de retorno y NO repone stock Central.
                 * Ej.: PT=360; 359 salidas; 4 regresan; 5 reenvíos:
                 * bruto=364, retorno recibido=4, neto=360.
                 */
                $devolucionesEmitidas = max(
                    0, (int) ($devolucion->emitidas ?? 0)
                );
                $devolucionesRecibidas = max(
                    0, (int) ($devolucion->recibidas ?? 0)
                );
                $devolucionesEnTransito = max(
                    0, $devolucionesEmitidas - $devolucionesRecibidas
                );

                // Impedir que un retorno mal atribuido cree stock negativo
                // o prendas adicionales; la inconsistencia queda auditable.
                $retornoAplicado = min(
                    $remitidoCentralRaw, $devolucionesRecibidas
                );
                $remitidoCentralNeto = max(
                    0, $remitidoCentralRaw - $retornoAplicado
                );
                $recibidoCentralNeto = max(
                    0, $recibidoCentralRaw - min(
                        $recibidoCentralRaw, $retornoAplicado
                    )
                );

                /*
                 * Un movimiento local->local puede completar un detalle
                 * histórico, pero un retorno hacia Central NUNCA cuenta
                 * como una nueva salida ni como cierre del plan.
                 */
                $saldoDespachoCentral = max(
                    0, $ptEfectivo - $remitidoCentralNeto
                );

                $remitidoCierre = $remitidoCentralNeto > 0
                    ? min(
                        $saldoDespachoCentral,
                        max(
                            0,
                            (int) ($coberturaPlan->remitido_cierre ?? 0)
                        )
                    )
                    : 0;

                $recibidoCierre = min(
                    $remitidoCierre,
                    max(
                        0,
                        (int) ($coberturaPlan->recibido_cierre ?? 0)
                    )
                );

                $remitidoRaw =
                    $remitidoCentralNeto + $remitidoCierre;

                $recibidoRaw =
                    $recibidoCentralNeto + $recibidoCierre;

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

                        // Etapa 90: informativa / previa.
                        'pre_ingreso_terminacion_raw' => $preIngresoRaw,
                        'pre_ingreso_terminacion' => $preIngresoEfectivo,

                        // Etapa 95: ingreso físico real usado por conciliación.
                        'ingreso_terminacion_raw' => $ingresoRaw,
                        'ingreso_terminacion' => $ingresoEfectivo,
                        'terminacion_raw' => $ingresoRaw,
                        'terminacion' => $ingresoEfectivo,

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

                        /*
                         * PRODUCTO TERMINADO manda.
                         * El plan efectivo nunca puede superar el PT.
                         * Cualquier detalle adicional queda sólo como auditoría.
                         */
                        'plan_detallado_raw' => $planRaw,
                        'plan_detallado' => $planDisponible,
                        'plan_disponible' => $planDisponible,
                        'exceso_plan_auditoria' => max(
                            0,
                            $planRaw - $ptEfectivo
                        ),
                        'sin_asignar_plan' => max(
                            0,
                            $ptEfectivo - $planDisponible
                        ),
                        'destinos' => (int) ($plan->destinos ?? 0),

                        // *_raw conserva el despacho central puro para
                        // auditoría; *_efectivo_raw suma el cierre reconocido.
                        'remitido_original_raw' => $remitidoCentralRaw,
                        'remitido_central_bruto' => $remitidoCentralRaw,
                        'recibido_central_bruto' => $recibidoCentralRaw,
                        'devoluciones_emitidas' => $devolucionesEmitidas,
                        'devoluciones_recibidas' => $devolucionesRecibidas,
                        'devoluciones_en_transito' => $devolucionesEnTransito,
                        'devoluciones_aplicadas' => $retornoAplicado,
                        'devoluciones_sin_salida' => max(
                            0, $devolucionesRecibidas - $retornoAplicado
                        ),
                        'remitido_central_neto' => $remitidoCentralNeto,
                        'stock_central_disponible' => max(
                            0, $ptEfectivo - $remitidoEfectivo
                        ),
                        'remitido_efectivo_raw' => $remitidoRaw,
                        'remitido_cierre' => $remitidoCierre,
                        'remitido_original' => $remitidoEfectivo,
                        // Remisiones emitidas recuperadas sin FK id_ot,
                        // identificadas por código de OT inequívoco.
                        'remitido_sin_vinculo_recuperado' =>
                            (int) ($remision->remitido_sin_vinculo ?? 0),

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

                        'primera_fecha_pre_ingreso' =>
                            $preIngresoTerminacion->primera_fecha ?? null,
                        'ultima_fecha_pre_ingreso' =>
                            $preIngresoTerminacion->ultima_fecha ?? null,

                        // Fechas físicas de Terminación (proceso 95).
                        'primera_fecha_ingreso' =>
                            $ingreso->primera_fecha ?? null,
                        'ultima_fecha_ingreso' =>
                            $ingreso->ultima_fecha ?? null,
                        'primera_fecha_terminacion' =>
                            $ingreso->primera_fecha ?? null,
                        'ultima_fecha_terminacion' =>
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
                        'total_distribuido' => $planDisponible,
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
         * REGLA ÚNICA DE CONCILIACIÓN:
         *
         * REMITIDO = existe una línea de remisión/salida. NO requiere
         * fecha_recepcion.
         *
         * RECIBIDO/CONFIRMADO = esa remisión ya tiene fecha_recepcion.
         *
         * EN TRÁNSITO = REMITIDO - RECIBIDO.
         *
         * Distribución original:
         * CASA CENTRAL / MATRIZ -> destino.
         *
         * COMERCIAL MATRIZ NO se considera origen central.
         * Los movimientos Local -> Local tampoco se vuelven a sumar.
         */
        $vinculadas = DB::table('ot_logistica_remisiones')
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

        /*
         * Los ENVIOS se importan aunque todavía no estén confirmados.
         * Si en la importación no pudo resolverse id_ot (por no existir
         * todavía el plan de distribución), la remisión real permanece en BD.
         *
         * Recuperación SOLO de lectura y SOLO por código base inequívoco:
         * evita mostrar como "pendiente remitir" mercadería ya despachada,
         * sin asignar arbitrariamente un mismo código a varias OT.
         *
         * No suma remisiones que YA tienen id_ot, ni movimientos locales.
         */
        $sinOt = $this->remisionesEmitidasSinOtUnica($idsOt);

        foreach ($sinOt as $idOt => $faltantesVinculo) {
            $actual = $vinculadas->get($idOt);

            if (!$actual) {
                $actual = (object) [
                    'id_ot' => (int) $idOt,
                    'remitido_original' => 0,
                    'recibido_original' => 0,
                    'remitido_sin_vinculo' => 0,
                    'primera_remision' => null,
                    'ultima_remision' => null,
                    'ultima_recepcion' => null,
                ];
            }

            $actual->remitido_sin_vinculo =
                (int) ($actual->remitido_sin_vinculo ?? 0)
                + (int) $faltantesVinculo->remitido_original;

            $actual->remitido_original +=
                (int) $faltantesVinculo->remitido_original;
            $actual->recibido_original +=
                (int) $faltantesVinculo->recibido_original;

            foreach (['primera_remision', 'ultima_remision', 'ultima_recepcion'] as $campo) {
                $fecha = $faltantesVinculo->{$campo};
                if (!$fecha) {
                    continue;
                }

                if (!$actual->{$campo}) {
                    $actual->{$campo} = $fecha;
                } elseif ($campo === 'primera_remision') {
                    $actual->{$campo} = min($actual->{$campo}, $fecha);
                } else {
                    $actual->{$campo} = max($actual->{$campo}, $fecha);
                }
            }

            $vinculadas->put((int) $idOt, $actual);
        }

        return $vinculadas;
    }

    /**
     * Recupera remisiones centrales SIN id_ot únicamente cuando el código
     * de artículo (sus primeros nueve dígitos) pertenece a una OT inequívoca
     * en TODA la tabla ot. No modifica los vínculos almacenados.
     *
     * Si dos OT comparten código base, no se imputa cantidad: se requiere
     * resolver el documento y la OT de origen antes de descontar saldos.
     */
    private function remisionesEmitidasSinOtUnica(array $idsOt): Collection
    {
        $basesSeleccionadas = DB::table('ot')
            ->whereIn('id_ot', $idsOt)
            ->pluck('codigo')
            ->map(function ($codigo) {
                return $this->codigoBaseRemision($codigo);
            })
            ->filter()
            ->unique()
            ->values();

        if ($basesSeleccionadas->isEmpty()) {
            return collect();
        }

        /*
         * Comparar también contra las OT fuera del filtro: un código que
         * pertenece a dos OT NO se considera inequívoco aunque el usuario
         * esté viendo sólo una de ellas.
         */
        $codigoOtSql = "CASE WHEN REGEXP_REPLACE(UPPER(BTRIM(COALESCE(codigo, ''))), '[^0-9A-Z]', '', 'g') ~ '^[0-9]{8}$'
            THEN LPAD(REGEXP_REPLACE(UPPER(BTRIM(COALESCE(codigo, ''))), '[^0-9A-Z]', '', 'g'), 9, '0')
            ELSE LEFT(REGEXP_REPLACE(UPPER(BTRIM(COALESCE(codigo, ''))), '[^0-9A-Z]', '', 'g'), 9) END";

        $candidatos = DB::table('ot')
            ->whereIn(DB::raw($codigoOtSql), $basesSeleccionadas->all())
            ->select('id_ot', 'codigo')
            ->get()
            ->groupBy(function ($ot) {
                return $this->codigoBaseRemision($ot->codigo);
            });

        $otPorBaseUnica = $candidatos
            ->filter(function ($grupo) {
                return $grupo->pluck('id_ot')->unique()->count() === 1;
            })
            ->map(function ($grupo) {
                return (int) $grupo->first()->id_ot;
            });

        $idsSeleccionados = array_fill_keys($idsOt, true);
        $otPorBaseUnica = $otPorBaseUnica
            ->filter(function ($idOt) use ($idsSeleccionados) {
                return isset($idsSeleccionados[$idOt]);
            });

        if ($otPorBaseUnica->isEmpty()) {
            return collect();
        }

        $bases = $otPorBaseUnica->keys()->all();

        // Usar el código BASE de los artículos de remisión, no el talle/color.
        $codigoRemisionSql = "CASE WHEN REGEXP_REPLACE(UPPER(BTRIM(COALESCE(codigo, ''))), '[^0-9A-Z]', '', 'g') ~ '^[0-9]{8}$'
            THEN LPAD(REGEXP_REPLACE(UPPER(BTRIM(COALESCE(codigo, ''))), '[^0-9A-Z]', '', 'g'), 9, '0')
            ELSE LEFT(REGEXP_REPLACE(UPPER(BTRIM(COALESCE(codigo, ''))), '[^0-9A-Z]', '', 'g'), 9) END";

        $filas = DB::table('ot_logistica_remisiones')
            ->whereNull('id_ot')
            ->whereNotNull('fecha_remision')
            ->whereIn(DB::raw($codigoRemisionSql), $bases)
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
            ->select('codigo', 'cantidad', 'fecha_remision', 'fecha_recepcion')
            ->get();

        return $filas->groupBy(function ($fila) use ($otPorBaseUnica) {
            return $otPorBaseUnica->get(
                $this->codigoBaseRemision($fila->codigo)
            );
        })->map(function ($grupo, $idOt) {
            return (object) [
                'id_ot' => (int) $idOt,
                'remitido_original' => (int) $grupo->sum('cantidad'),
                'recibido_original' => (int) $grupo
                    ->filter(function ($fila) {
                        return !empty($fila->fecha_recepcion);
                    })->sum('cantidad'),
                'primera_remision' => $grupo->min('fecha_remision'),
                'ultima_remision' => $grupo->max('fecha_remision'),
                'ultima_recepcion' => $grupo->max('fecha_recepcion'),
            ];
        });
    }

    private function codigoBaseRemision($codigo): string
    {
        $texto = strtoupper(trim((string) $codigo));
        $texto = preg_replace('/[^0-9A-Z]/', '', $texto);

        if (preg_match('/^[0-9]{8}$/', $texto)) {
            return str_pad($texto, 9, '0', STR_PAD_LEFT);
        }

        return preg_match('/^[0-9]{9}/', $texto)
            ? substr($texto, 0, 9)
            : '';
    }

    /**
     * Movimientos de devolución LOCAL -> CASA CENTRAL en el mismo ENVIOS.
     * Reconoce el movimiento emitido y acredita stock solo al confirmarse
     * fecha_recepcion. No altera documentos ni inventa PT.
     *
     * Para líneas históricas sin id_ot, sólo asocia un código base si
     * pertenece a una ÚNICA OT de toda la base, no sólo del filtro actual.
     */
    private function devolucionesPorOt(array $idsOt): Collection
    {
        if (empty($idsOt) || !Schema::hasTable('ot_logistica_remisiones')) {
            return collect();
        }

        $ids = array_fill_keys(array_map('intval', $idsOt), true);

        $filas = DB::table('ot_logistica_remisiones as r')
            ->whereIn('r.id_ot', $idsOt)
            ->where('r.cod_sucursal_destino', 1)
            ->where('r.cod_sucursal_salida', '<>', 1)
            ->whereNotNull('r.fecha_remision')
            ->select(
                'r.id_ot', 'r.serie', 'r.numero_remision', 'r.codigo',
                'r.cantidad', 'r.fecha_remision', 'r.fecha_recepcion',
                'r.cod_sucursal_salida', 'r.sucursal_salida',
                'r.sucursal_destino'
            )
            ->get();

        // Remisiones de retorno sin vínculo: resolver SOLO código base único.
        $codigoOtSql = "CASE WHEN REGEXP_REPLACE(UPPER(BTRIM(COALESCE(codigo, ''))), '[^0-9A-Z]', '', 'g') ~ '^[0-9]{8}$'
            THEN LPAD(REGEXP_REPLACE(UPPER(BTRIM(COALESCE(codigo, ''))), '[^0-9A-Z]', '', 'g'), 9, '0')
            ELSE LEFT(REGEXP_REPLACE(UPPER(BTRIM(COALESCE(codigo, ''))), '[^0-9A-Z]', '', 'g'), 9) END";

        $bases = DB::table('ot')
            ->whereIn('id_ot', $idsOt)
            ->pluck('codigo')
            ->map(function ($codigo) {
                return $this->codigoBaseRemision($codigo);
            })->filter()->unique()->values();

        if ($bases->isNotEmpty()) {
            $unicas = DB::table('ot')
                ->whereIn(DB::raw($codigoOtSql), $bases->all())
                ->select('id_ot', 'codigo')
                ->get()
                ->groupBy(function ($ot) {
                    return $this->codigoBaseRemision($ot->codigo);
                })
                ->filter(function ($grupo) {
                    return $grupo->pluck('id_ot')->unique()->count() === 1;
                })
                ->map(function ($grupo) {
                    return (int) $grupo->first()->id_ot;
                });

            $codigoRemisionSql = str_replace('codigo', 'r.codigo', $codigoOtSql);

            $sinVinculo = DB::table('ot_logistica_remisiones as r')
                ->whereNull('r.id_ot')
                ->where('r.cod_sucursal_destino', 1)
                ->where('r.cod_sucursal_salida', '<>', 1)
                ->whereNotNull('r.fecha_remision')
                ->whereIn(DB::raw($codigoRemisionSql), $unicas->keys()->all())
                ->select(
                    'r.id_ot', 'r.serie', 'r.numero_remision', 'r.codigo',
                    'r.cantidad', 'r.fecha_remision', 'r.fecha_recepcion',
                    'r.cod_sucursal_salida', 'r.sucursal_salida',
                    'r.sucursal_destino'
                )->get();

            foreach ($sinVinculo as $fila) {
                $idOt = $unicas->get(
                    $this->codigoBaseRemision($fila->codigo)
                );
                if ($idOt && isset($ids[$idOt])) {
                    $fila->id_ot = $idOt;
                    $filas->push($fila);
                }
            }
        }

        return $filas->groupBy('id_ot')->map(function ($movimientos) {
            $emitidas = (int) $movimientos->sum('cantidad');
            $recibidas = (int) $movimientos
                ->filter(function ($m) {
                    return !empty($m->fecha_recepcion);
                })->sum('cantidad');

            return (object) [
                'emitidas' => $emitidas,
                'recibidas' => $recibidas,
                'documentos' => $movimientos->sortBy('fecha_remision')
                    ->values(),
            ];
        });
    }

    /**
     * Documentos de devolución para el detalle auditable de una OT.
     * Son los mismos movimientos usados en la conciliación general.
     */
    public function devolucionesDetallePorOt(int $idOt): Collection
    {
        $devolucion = $this->devolucionesPorOt([$idOt])->get($idOt);
        return $devolucion ? $devolucion->documentos : collect();
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
                    ->on('r.id_ot', '=', 'd.id_ot')
                    // Las devoluciones a Casa Central son entradas de stock.
                    // Nunca se usan como "remitido_cierre".
                    ->where(function ($q) {
                        $q->whereNull('r.cod_sucursal_destino')
                          ->orWhere('r.cod_sucursal_destino', '<>', 1);
                    })
                    ->whereRaw(
                        "UPPER(TRIM(COALESCE(r.sucursal_destino, ''))) NOT IN ('CASA CENTRAL', 'MATRIZ')"
                    );
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
