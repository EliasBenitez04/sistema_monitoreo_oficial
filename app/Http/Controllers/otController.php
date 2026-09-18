<?php

namespace App\Http\Controllers;

use App\Imports\LogisticaImport;
use App\Models\Ot;
use App\Imports\OtImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Exports\OtLogisticaExport;
use App\Models\OtTrazabilidad;

class OtController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ot index')->only('index');
        $this->middleware('permission:ot create')->only('create', 'store');
        $this->middleware('permission:ot edit')->only('edit', 'update');
        $this->middleware('permission:ot destroy')->only('destroy');
        $this->middleware('permission:ot importar')->only('importar');
        $this->middleware('permission:ot dashboard')->only('dashboard');
        // Reutilizamos el permiso "ot edit" para agregar procesos manualmente.
        // Si tenés un permiso propio (ej. "ot proceso"), cambialo acá.
        // $this->middleware('permission:ot edit')->only('nuevoProceso', 'guardarProceso');
    }

    public function index()
    {

        $ots = Ot::paginate(20);

        return view('ots.index', compact('ots'));
    }

    public function create()
    {
        $ots = Ot::orderBy('nro_ot')
            ->get()
            ->mapWithKeys(function ($ot) {
                return [
                    $ot->id_ot => $ot->nro_ot . ' - ' . $ot->codigo
                ];
            });

        return view('ots.create', compact('ots'));
    }

    public function getOtDetails($id)
    {
        $ot = Ot::where('id_ot', $id)->first();

        if ($ot) {
            return response()->json($ot);
        }

        return response()->json(null);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            // Buscar la OT existente
            $ot = Ot::findOrFail($request->id_ot);

            //==========================
            // DETALLE DE PROCESOS
            //==========================

            if ($request->has('proceso')) {

                foreach ($request->proceso as $key => $proceso) {

                    if (empty($proceso)) {
                        continue;
                    }

                    $ot->trazabilidades()->create([

                        'id_ot'         => $ot->id_ot,
                        'proceso'       => $proceso,
                        'resultado'     => $request->resultado[$key] ?? null,
                        'fecha_proceso' => $request->fecha_proceso[$key],

                    ]);
                }
            }

            DB::commit();

            alert()->success(
                'Éxito',
                'Trazabilidad registrada correctamente.'
            );
        } catch (\Exception $e) {

            DB::rollBack();

            alert()->error(
                'Error',
                $e->getMessage()
            );

            return redirect()
                ->back()
                ->withInput();
        }

        return redirect()->route('ots.index');
    }

    public function show($id)
    {
        $ot = Ot::with('trazabilidades')->findOrFail($id);

        return view('ots.show', compact('ot'));
    }

    public function buscarEditar(Request $request)
    {
        $request->validate([
            'nro_ot' => 'required|integer',
        ]);

        // Buscar la OT por su número
        $ot = Ot::where('nro_ot', $request->nro_ot)->first();

        if (!$ot) {
            return redirect()
                ->route('ots.index')
                ->with('error', 'No se encontró la OT N° ' . $request->nro_ot);
        }

        // Ir directamente al formulario de edición
        return redirect()->route('ots.edit', [
            'ot' => $ot->id_ot
        ]);
    }


    public function edit($ot)
    {
        // Buscar la OT por id_ot
        $ot = Ot::where('id_ot', $ot)->first();

        if (!$ot) {
            return redirect()
                ->route('ots.index')
                ->with('error', 'No se encontró la Orden de Trabajo.');
        }

        return view('ots.edit', compact('ot'));
    }

    public function update(Request $request, $id)
    {
        $ot = Ot::findOrFail($id);

        $request->validate([
            'nro_ot' => 'required|unique:ot,nro_ot,' . $id . ',id_ot',
            'codigo' => 'required',
            'descripcion' => 'required',
            'cantidad_orden' => 'required|numeric'
        ]);

        $ot->update($request->all());

        alert()->success('Éxito', 'OT actualizada correctamente');

        return redirect()->route('ots.index');
    }

    public function buscarOt($nro_ot)
    {
        $ot = Ot::where('nro_ot', $nro_ot)->first();

        if (!$ot) {
            return response()->json([
                'success' => false
            ]);
        }

        return response()->json([
            'success' => true,
            'codigo' => $ot->codigo,
            'descripcion' => $ot->descripcion,
            'cantidad_orden' => $ot->cantidad_orden
        ]);
    }

    public function destroy($id)
    {
        $ot = Ot::findOrFail($id);

        $ot->delete();

        alert()->success('Éxito', 'OT eliminada correctamente');

        return redirect()->route('ots.index');
    }

    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required|mimes:xlsx,xls'
        ]);

        Excel::import(
            new OtImport,
            $request->file('archivo')
        );

        alert()->success('Éxito', 'Importación realizada correctamente');

        return redirect()->route('ots.index');
    }

    public function importarLogistica(Request $request)
    {
        $request->validate([
            'archivo' => 'required|mimes:xlsx,xls',
            'fecha_proceso' => 'required|date',
        ]);

        Excel::import(
            new LogisticaImport($request->fecha_proceso),
            $request->file('archivo')
        );

        alert()->success(
            'Éxito',
            'Importación de logística realizada correctamente'
        );

        return back();
    }

    public function buscar(Request $request)
    {
        $ot = Ot::with('trazabilidades')
            ->where('nro_ot', $request->nro_ot)
            ->first();

        return view('ots.resultado', compact('ot'));
    }

    /**
     * Muestra el formulario para agregar un nuevo proceso (trazabilidad)
     * a una OT existente. Trae todos los datos de la OT ya cargados
     * (nro_ot, codigo, descripcion, cantidad_orden) para que el usuario
     * solo tenga que completar manualmente los datos del proceso
     * (proceso, resultado, fecha_proceso).
     */
    public function nuevoProceso($id)
    {
        $ot = Ot::findOrFail($id);

        return view('ots.proceso-create', compact('ot'));
    }

    /**
     * Guarda el nuevo proceso (trazabilidad) asociado a la OT.
     * El id_ot se toma de la propia OT (no lo maneja el usuario),
     * evitando así inconsistencias.
     */
    public function guardarProceso(Request $request, $id)
    {
        $ot = Ot::findOrFail($id);

        $request->validate([
            'proceso'       => 'required|string|max:100',
            'resultado'     => 'nullable|integer',
            'fecha_proceso' => 'required|date',
        ]);

        // La unique de la tabla (id_ot, proceso, fecha_proceso) evita
        // duplicados exactos; si ya existe, Laravel lanzará un
        // QueryException que se puede capturar más adelante si se
        // quiere mostrar un mensaje personalizado.
        $ot->trazabilidades()->create([
            'proceso'       => $request->proceso,
            'resultado'     => $request->resultado,
            'fecha_proceso' => $request->fecha_proceso,
        ]);

        alert()->success('Éxito', 'Proceso agregado correctamente');

        return redirect()->route('ots.show', $ot->id_ot);
    }

    /**
     * Catálogo de procesos del flujo de producción y el % de avance que
     * representa cada uno cuando es el ÚLTIMO proceso registrado en la OT.
     *
     * ⚠️ El ORDEN en que están declaradas las claves aquí es también el
     * orden en que se mostrarán los procesos en el dashboard (no el orden
     * por fecha). Esto es clave porque algunas OT pueden tener procesos
     * registrados con fechas "desordenadas" o simplemente no tener todos
     * los procesos del flujo.
     */
    private const FLUJO_PROCESOS = [
        'PEDIDO A PRODUCCION'  => 5,
        'ORDEN DE TRABAJO'     => 10,
        'MOLDERIA'             => 15,
        'PROTOTIPO'            => 20,
        'DISEÑO GRAFICO'       => 25,
        'TIZADAS'              => 30,
        'CORTE'                => 40,
        'LOTEO Y DISTRIBUCION' => 45,
        'REVELADO'             => 50,
        'SERIGRAFIA'           => 55,
        'BORDADO'              => 60,
        'COSTURA INTERNA'      => 70,
        'ATRAQUES'             => 75,
        'LAVANDERIA'           => 80,
        'PRETERMINACION'       => 85,
        'INGRESO TERMINACION'  => 90,
        'TERMINACION'          => 95,
        'PRODUCTO TERMINADO'   => 100,
        'LOGISTICA Y DISTRIBUCION'   => 100,
        'REVISION'             => 100,
        'PEDIDO SUSPENDIDO'    => -1, // Estado especial, no representa avance
    ];

    /**
     * Nombre exacto (normalizado) del proceso que representa una OT
     * suspendida. Se trata aparte porque no debe sumar avance ni marcar
     * la OT como finalizada, aunque sí debe listarse en la tabla.
     */
    private const PROCESO_SUSPENDIDO = 'PEDIDO SUSPENDIDO';

    /**
     * Días sin movimiento a partir de los cuales la OT se marca como
     * "Demorada" en el reporte (alerta visual para gerencia).
     */
    private const DIAS_PARA_ALERTA = 30;

    public function dashboard(Request $request)
    {
        $ot = null;
        $resumen = null;
        $procesos = collect();
        $labels = [];
        $duraciones = [];
        $avancesAcumulados = [];
        $mensaje = null;

        $nroOt = trim((string) $request->input('nro_ot'));

        if ($nroOt !== '') {

            // Ya no ordenamos por fecha aquí: el orden final (por flujo)
            // se calcula en construirReporte() para que el dashboard
            // muestre siempre PEDIDO A PRODUCCION -> ... -> PRODUCTO
            // TERMINADO / REVISION, sin importar el orden de las fechas.
            $ot = Ot::where('nro_ot', $nroOt)
                ->with([
                    'trazabilidades',

                    'logisticaDetalle'
                ])
                ->first();

            if (!$ot) {
                $mensaje = "No se encontró ninguna OT con el número \"{$nroOt}\".";
            } elseif ($ot->trazabilidades->isEmpty()) {
                $mensaje = "La OT N° {$ot->nro_ot} no tiene procesos registrados todavía.";
            } else {
                [$procesos, $resumen] = $this->construirReporte($ot->trazabilidades);

                $labels = $procesos->pluck('proceso')->all();
                $duraciones = $procesos->pluck('duracion_dias')->all();
                $avancesAcumulados = $procesos->pluck('avance_acumulado')->all();
            }
        }

        return view('dashboard.ot', [
            'ot'                => $ot,
            'procesos'          => $procesos,
            'resumen'           => $resumen,
            'labels'            => $labels,
            'duraciones'        => $duraciones,
            'avancesAcumulados' => $avancesAcumulados,
            'mensaje'           => $mensaje,
            'nroOtBuscada'      => $nroOt,
        ]);
    }

    /**
     * Construye la línea de tiempo enriquecida (duración real, avance
     * acumulado, etc.) y el resumen ejecutivo (KPIs) a partir de las
     * trazabilidades de una OT.
     *
     * Las filas se muestran SIEMPRE en el orden del flujo de producción
     * (FLUJO_PROCESOS), no en el orden en que fueron registradas. Si la
     * OT no tiene un proceso, simplemente no aparece esa fila (no se
     * inventan procesos faltantes).
     *
     * @param  Collection  $trazas
     * @return array{0: Collection, 1: array}
     */
    private function construirReporte(Collection $trazas): array
    {
        // 1) Ordenar por posición en el flujo real de producción y, como
        // criterio secundario (para procesos repetidos, ej. reprocesos de
        // COSTURA INTERNA), por fecha.
        $trazasOrdenadas = $trazas
            ->sortBy(function ($traza) {
                $orden = str_pad((string) $this->ordenProceso($traza->proceso), 5, '0', STR_PAD_LEFT);
                $fecha = Carbon::parse($traza->fecha_proceso)->format('YmdHis');

                return "{$orden}-{$fecha}";
            })
            ->values();

        $procesos = collect();
        $horasAcumuladas = 0.0;
        $posicion = 0;

        foreach ($trazasOrdenadas as $index => $traza) {

            $posicion++;

            $fecha = Carbon::parse($traza->fecha_proceso);

            // Obtener el siguiente proceso
            $siguienteTraza = $trazasOrdenadas->get($index + 1);

            if ($siguienteTraza) {

                $fechaSiguiente = Carbon::parse($siguienteTraza->fecha_proceso);

                // Tiempo desde este proceso hasta el siguiente
                $duracionHoras = round(
                    $fecha->diffInMinutes($fechaSiguiente) / 60,
                    2
                );
            } else {

                // Último proceso no tiene siguiente proceso
                $duracionHoras = 0.0;
            }

            $horasAcumuladas += $duracionHoras;

            $esSuspendido = $this->normalizarProceso($traza->proceso) === self::PROCESO_SUSPENDIDO;

            $procesos->push([
                'id_trazabilidad' => $traza->id_trazabilidad,
                'proceso'          => $traza->proceso,
                'resultado'        => $traza->resultado,
                'fecha'            => $fecha,
                'duracion_horas'   => $duracionHoras,
                'duracion_dias'    => round($duracionHoras / 24, 2),
                'horas_acumuladas' => round($horasAcumuladas, 2),
                'avance_acumulado' => $this->calcularAvance($traza->proceso, $posicion),
                'es_suspendido'    => $esSuspendido,
            ]);
        }

        $resumen = $this->construirResumen($procesos, $horasAcumuladas);

        return [$procesos, $resumen];
    }

    /**
     * Arma el resumen ejecutivo (KPIs) a partir de la lista de procesos
     * ya ordenada por flujo.
     */
    private function construirResumen(Collection $procesos, float $horasAcumuladas): array
    {
        $primerProceso = $procesos->first();
        $ultimoProceso = $procesos->last();

        $fechaInicio = $primerProceso['fecha'];
        $fechaUltimo = $ultimoProceso['fecha'];

        $estaSuspendida = $ultimoProceso['es_suspendido'];

        // Si el último proceso del flujo presente es "PEDIDO SUSPENDIDO",
        // el avance real de la OT es el del último proceso NO suspendido
        // (para no perder de vista en qué etapa quedó antes de suspenderse).
        if ($estaSuspendida) {
            $ultimoProcesoReal = $procesos
                ->reverse()
                ->first(fn($p) => !$p['es_suspendido']);

            $avance = $ultimoProcesoReal['avance_acumulado'] ?? 0;
            $nombreUltimoProceso = $ultimoProceso['proceso'];
        } else {
            $avance = $ultimoProceso['avance_acumulado'];
            $nombreUltimoProceso = $ultimoProceso['proceso'];
        }

        $finalizada = !$estaSuspendida && $avance >= 100;

        // Días totales que tomó la OT desde el primer registro hasta el último (con precisión decimal).
        $diasTotales = round($fechaInicio->diffInMinutes($fechaUltimo) / 60 / 24, 2);

        // Días transcurridos desde el último movimiento hasta HOY (clave para detectar OTs estancadas).
        $diasDesdeUltimoProceso = round($fechaUltimo->diffInMinutes(Carbon::now()) / 60 / 24, 2);

        // Para promedios/cuellos de botella se descarta el primer registro (su duración siempre es 0).
        $duracionesValidas = $procesos->skip(1)->pluck('duracion_horas');
        $duracionPromedio = $duracionesValidas->isNotEmpty() ? round($duracionesValidas->avg(), 2) : 0.0;

        $procesoMasLento = $procesos->skip(1)->sortByDesc('duracion_horas')->first();
        $procesoMasRapido = $procesos->skip(1)->sortBy('duracion_horas')->first();

        $estaDemorada = !$finalizada && !$estaSuspendida && $diasDesdeUltimoProceso >= self::DIAS_PARA_ALERTA;

        if ($estaSuspendida) {
            $estadoTexto = 'Suspendido';
            $estadoColor = 'secondary';
        } elseif ($finalizada) {
            $estadoTexto = 'Finalizado';
            $estadoColor = 'success';
        } elseif ($estaDemorada) {
            $estadoTexto = 'Demorado';
            $estadoColor = 'danger';
        } else {
            $estadoTexto = 'En Proceso';
            $estadoColor = 'info';
        }

        $avanceColor = $avance >= 100 ? 'success' : ($avance >= 50 ? 'info' : 'warning');

        return [
            'cantidad_procesos'         => $procesos->count(),
            'fecha_inicio'              => $fechaInicio,
            'fecha_ultimo_proceso'      => $fechaUltimo,
            'ultimo_proceso'            => $nombreUltimoProceso,
            'avance'                    => $avance,
            'avance_color'              => $avanceColor,
            'finalizada'                => $finalizada,
            'esta_suspendida'           => $estaSuspendida,
            'estado_texto'              => $estadoTexto,
            'estado_color'              => $estadoColor,
            'tiempo_total_horas'        => round($horasAcumuladas, 2),
            'tiempo_total_dias'         => $diasTotales,
            'dias_desde_ultimo_proceso' => $diasDesdeUltimoProceso,
            'esta_demorada'             => $estaDemorada,
            'duracion_promedio_horas'   => $duracionPromedio,
            'proceso_mas_lento'         => $procesoMasLento,
            'proceso_mas_rapido'        => $procesoMasRapido,
            'fecha_generacion_reporte'  => Carbon::now(),
        ];
    }

    /**
     * Normaliza el nombre de un proceso (mayúsculas + trim) para poder
     * compararlo de forma consistente contra el catálogo FLUJO_PROCESOS.
     */
    private function normalizarProceso(?string $proceso): string
    {
        $valor = (string) $proceso;

        // 1) Reemplazar espacios "especiales" que a veces vienen pegados
        // desde Excel/copiar-pegar y que NO son detectados por trim() ni
        // por \s normal: espacio no separable (\xC2\xA0) y espacio de
        // ancho cero (\xE2\x80\x8B), además de tabs/saltos de línea.
        $valor = str_replace(
            ["\xC2\xA0", "\xE2\x80\x8B", "\t", "\n", "\r"],
            ' ',
            $valor
        );

        $valor = mb_strtoupper($valor, 'UTF-8');

        // 2) Los datos reales vienen con el formato "CATEGORIA - PROCESO
        // REAL", por ejemplo "TERMINACION - PRODUCTO TERMINADO" o
        // "PRODUCCION - CORTE". El catálogo FLUJO_PROCESOS solo conoce el
        // nombre del proceso real (la parte después del último guion), así
        // que nos quedamos con esa parte para poder matchear.
        if (str_contains($valor, '-')) {
            $partes = explode('-', $valor);
            $valor = trim(end($partes));
        }

        // 3) Sacar CUALQUIER carácter que no sea letra (incluye Ñ) o
        // espacio: puntos, comas, paréntesis, números, etc. Esto es lo que
        // resuelve casos como "PRODUCTO TERMINADO.", "PRODUCTO TERMINADO
        // (OK)", etc.
        $valor = preg_replace('/[^A-ZÁÉÍÓÚÑ\s]/u', '', $valor);

        // 4) Colapsar espacios múltiples y quitar los de los extremos.
        $valor = preg_replace('/\s+/u', ' ', $valor);

        return trim($valor);
    }

    /**
     * Devuelve la posición del proceso dentro del flujo de producción
     * (según el orden de declaración de FLUJO_PROCESOS). Los procesos no
     * catalogados se envían al final, conservando el orden relativo entre
     * ellos según la fecha (criterio secundario del sortBy).
     */
    private function ordenProceso(?string $proceso): int
    {
        $clave = $this->normalizarProceso($proceso);
        $claves = array_keys(self::FLUJO_PROCESOS);
        $indice = array_search($clave, $claves, true);

        return $indice === false ? count($claves) : $indice;
    }

    /**
     * Calcula el % de avance de la OT según el nombre del proceso.
     *
     * Si el proceso coincide con el catálogo FLUJO_PROCESOS, se usa ese
     * valor exacto (por eso "PRODUCTO TERMINADO" y "REVISION" siempre dan
     * 100%). Si el proceso no está catalogado, se hace una estimación
     * conservadora por posición que nunca llega a 100% — ese valor queda
     * reservado exclusivamente para los procesos reconocidos como cierre
     * de la OT.
     */
    private function calcularAvance(?string $proceso, int $posicion): int
    {
        if (!$proceso) {
            return 0;
        }

        $clave = $this->normalizarProceso($proceso);

        if (array_key_exists($clave, self::FLUJO_PROCESOS)) {
            return self::FLUJO_PROCESOS[$clave];
        }

        return min($posicion * 10, 90);
    }

    public function destroyTrazabilidad($id)
    {
        try {

            $trazabilidad = DB::table('ot_trazabilidad')
                ->where('id_trazabilidad', $id)
                ->first();

            if (!$trazabilidad) {

                alert()->error(
                    'Error',
                    'El proceso de trazabilidad no existe.'
                );

                return back();
            }

            DB::table('ot_trazabilidad')
                ->where('id_trazabilidad', $id)
                ->delete();

            alert()->success(
                'Éxito',
                'Proceso de trazabilidad eliminado correctamente.'
            );

            return back();
        } catch (\Exception $e) {

            alert()->error(
                'Error',
                'No se pudo eliminar el proceso: ' . $e->getMessage()
            );

            return back();
        }
    }

    public function otAtrasadas(Request $request)
    {
        /*
     * ============================================================
     * VALIDAR FILTRO DE FECHAS
     * ============================================================
     */

        $request->validate([
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
        ]);

        $diasAlerta = self::DIAS_PARA_ALERTA;


        /*
     * ============================================================
     * FECHAS DEL FILTRO
     * ============================================================
     */

        $fechaDesde = $request->filled('fecha_desde')
            ? Carbon::parse($request->fecha_desde)->startOfDay()
            : null;

        $fechaHasta = $request->filled('fecha_hasta')
            ? Carbon::parse($request->fecha_hasta)->endOfDay()
            : null;


        /*
     * ============================================================
     * OBTENER OT CON TRAZABILIDAD
     * ============================================================
     */

        $ots = Ot::with('trazabilidades')
            ->whereHas('trazabilidades')
            ->get();


        /*
     * ============================================================
     * EXCLUIR OT POSTERGADAS
     * ============================================================
     *
     * IMPORTANTE:
     *
     * La exclusión se hace ANTES de recorrer las OT.
     *
     * De esta manera una OT POSTERGADO nunca participa en:
     *
     * - OT atrasadas
     * - Total
     * - Promedio
     * - Mayor atraso
     * - Procesos
     * - Porcentajes
     * - Filtros
     *
     */

        $ots = $ots->filter(function ($ot) {

            $estadoOT = strtoupper(
                trim((string) $ot->estado)
            );

            return $estadoOT !== 'POSTERGADO';
        })->values();


        /*
     * ============================================================
     * COLECCIÓN DE OT ATRASADAS
     * ============================================================
     */

        $otsAtrasadas = collect();


        /*
     * ============================================================
     * RECORRER TODAS LAS OT
     * ============================================================
     */

        foreach ($ots as $ot) {

            /*
         * ========================================================
         * SEGURIDAD EXTRA
         * ========================================================
         *
         * Aunque ya fueron excluidas arriba, dejamos esta
         * validación como segunda barrera.
         *
         */

            $estadoOT = strtoupper(
                trim((string) $ot->estado)
            );

            if ($estadoOT === 'POSTERGADO') {
                continue;
            }


            /*
         * ========================================================
         * ÚLTIMO MOVIMIENTO REAL
         * ========================================================
         *
         * Se busca la trazabilidad con la fecha más reciente.
         *
         * Si existen varios movimientos con la misma fecha,
         * se utiliza el orden del proceso como desempate.
         */

            $ultimoMovimiento = $ot->trazabilidades
                ->sortByDesc(function ($traza) {

                    $fecha = Carbon::parse(
                        $traza->fecha_proceso
                    )->timestamp;

                    $orden = $this->ordenProceso(
                        $traza->proceso
                    );

                    return ($fecha * 1000) + $orden;
                })
                ->first();


            /*
         * ========================================================
         * SEGURIDAD
         * ========================================================
         */

            if (!$ultimoMovimiento) {
                continue;
            }


            /*
         * ========================================================
         * FECHA DEL ÚLTIMO MOVIMIENTO
         * ========================================================
         */

            $fechaUltimoMovimiento = Carbon::parse(
                $ultimoMovimiento->fecha_proceso
            );


            /*
         * ========================================================
         * FILTRO POR RANGO DE FECHAS
         * ========================================================
         *
         * Si se indica fecha_desde:
         *
         * fecha último movimiento >= fecha_desde
         *
         * Si se indica fecha_hasta:
         *
         * fecha último movimiento <= fecha_hasta
         */

            if (
                $fechaDesde &&
                $fechaUltimoMovimiento->lt($fechaDesde)
            ) {
                continue;
            }

            if (
                $fechaHasta &&
                $fechaUltimoMovimiento->gt($fechaHasta)
            ) {
                continue;
            }


            /*
         * ========================================================
         * NORMALIZAR ÚLTIMO PROCESO
         * ========================================================
         *
         * Ejemplo:
         *
         * TERMINACION - PRODUCTO TERMINADO
         *                 ↓
         * PRODUCTO TERMINADO
         *
         * LOGISTICA - LOGISTICA Y DISTRIBUCION
         *                 ↓
         * LOGISTICA Y DISTRIBUCION
         */

            $ultimoProcesoNormalizado = $this->normalizarProceso(
                $ultimoMovimiento->proceso
            );


            /*
         * ========================================================
         * EXCLUIR OT FINALIZADAS
         * ========================================================
         *
         * Estas OT no deben aparecer como atrasadas aunque
         * tengan muchos días sin movimiento.
         */

            if (
                in_array(
                    $ultimoProcesoNormalizado,
                    [
                        'PRODUCTO TERMINADO',
                        'LOGISTICA Y DISTRIBUCION',
                    ],
                    true
                )
            ) {
                continue;
            }


            /*
         * ========================================================
         * DÍAS SIN MOVIMIENTO
         * ========================================================
         */

            $diasSinMovimiento = round(
                $fechaUltimoMovimiento
                    ->diffInMinutes(Carbon::now()) / 60 / 24,
                2
            );


            /*
         * ========================================================
         * VERIFICAR SI ESTÁ ATRASADA
         * ========================================================
         */

            if ($diasSinMovimiento < $diasAlerta) {
                continue;
            }


            /*
         * ========================================================
         * CONSTRUIR TODOS LOS PROCESOS
         * ========================================================
         *
         * Mantiene el orden definido por FLUJO_PROCESOS.
         */

            [$procesos, $resumen] = $this->construirReporte(
                $ot->trazabilidades
            );


            /*
         * ========================================================
         * CALCULAR AVANCE REAL
         * ========================================================
         */

            $ultimoProcesoFlujo = $procesos->last();

            $avance = 0;

            if ($ultimoProcesoFlujo) {

                /*
             * Si el último proceso del flujo es
             * PEDIDO SUSPENDIDO, buscamos el último
             * proceso real anterior.
             */

                if ($ultimoProcesoFlujo['es_suspendido']) {

                    $ultimoProcesoReal = $procesos
                        ->reverse()
                        ->first(
                            fn($p) => !$p['es_suspendido']
                        );

                    $avance =
                        $ultimoProcesoReal['avance_acumulado']
                        ?? 0;
                } else {

                    $avance =
                        $ultimoProcesoFlujo['avance_acumulado']
                        ?? 0;
                }
            }


            /*
         * ========================================================
         * AGREGAR OT A LAS ATRASADAS
         * ========================================================
         */

            $otsAtrasadas->push([

                'ot' => $ot,

                'procesos' => $procesos,

                'resumen' => $resumen,

                'ultimo_movimiento' =>
                $ultimoMovimiento,

                'ultimo_proceso_normalizado' =>
                $ultimoProcesoNormalizado,

                'fecha_ultimo_movimiento' =>
                $fechaUltimoMovimiento,

                'dias_sin_movimiento' =>
                $diasSinMovimiento,

                'avance' =>
                $avance,

                'cantidad_procesos' =>
                $procesos->count(),

            ]);
        }


        /*
     * ================================================================
     * ORDENAR LAS OT MÁS ATRASADAS PRIMERO
     * ================================================================
     */

        $otsAtrasadas = $otsAtrasadas
            ->sortByDesc('dias_sin_movimiento')
            ->values();


        /*
     * ================================================================
     * SEGURIDAD FINAL
     * ================================================================
     *
     * Esta última limpieza garantiza que ninguna OT POSTERGADO
     * llegue a los KPIs aunque en algún momento se agregue otra
     * lógica antes de esta sección.
     */

        $otsAtrasadas = $otsAtrasadas
            ->filter(function ($item) {

                $estadoOT = strtoupper(
                    trim((string) $item['ot']->estado)
                );

                return $estadoOT !== 'POSTERGADO';
            })
            ->values();


        /*
     * ================================================================
     * KPI - TOTAL OT ATRASADAS
     * ================================================================
     */

        $totalAtrasadas = $otsAtrasadas->count();


        /*
     * ================================================================
     * KPI - PROMEDIO DÍAS DE ATRASO
     * ================================================================
     */

        $promedioDiasAtraso = $totalAtrasadas > 0
            ? round(
                $otsAtrasadas->avg('dias_sin_movimiento'),
                2
            )
            : 0;


        /*
     * ================================================================
     * KPI - MAYOR ATRASO
     * ================================================================
     */

        $mayorAtraso = $totalAtrasadas > 0
            ? $otsAtrasadas->max('dias_sin_movimiento')
            : 0;


        /*
     * ================================================================
     * OT ATRASADAS POR PROCESO
     * ================================================================
     *
     * Se utiliza el ÚLTIMO PROCESO de cada OT.
     *
     * Ejemplo:
     *
     * OT 100 -> CORTE
     * OT 101 -> CORTE
     * OT 102 -> COSTURA
     * OT 103 -> CORTE
     *
     * Resultado:
     *
     * CORTE      = 3
     * COSTURA    = 1
     */

        $otsPorProceso = $otsAtrasadas
            ->groupBy(function ($item) {

                return $item['ultimo_proceso_normalizado']
                    ?: 'SIN PROCESO';
            })
            ->map(function ($items) {

                return $items->count();
            })
            ->sortDesc();


        /*
     * ================================================================
     * PORCENTAJE POR PROCESO
     * ================================================================
     */

        $detalleProcesos = $otsPorProceso
            ->map(function ($cantidad, $proceso) use ($totalAtrasadas) {

                $porcentaje = $totalAtrasadas > 0
                    ? round(
                        ($cantidad / $totalAtrasadas) * 100,
                        2
                    )
                    : 0;

                return [

                    'proceso' =>
                    $proceso,

                    'cantidad' =>
                    $cantidad,

                    'porcentaje' =>
                    $porcentaje,

                ];
            })
            ->values();


        /*
     * ================================================================
     * RETORNAR VISTA
     * ================================================================
     */

        return view(
            'dashboard.ot-atrasadas',
            compact(
                'otsAtrasadas',
                'totalAtrasadas',
                'promedioDiasAtraso',
                'mayorAtraso',
                'diasAlerta',
                'otsPorProceso',
                'detalleProcesos',
                'fechaDesde',
                'fechaHasta'
            )
        );
    }

    private function procesosFinales(): array
    {
        return [
            'PRODUCTO TERMINADO',
            'LOGISTICA Y DISTRIBUCION',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZAR PROCESO
    |--------------------------------------------------------------------------
    */

    private function normalizarProcesoDashboard($proceso): string
    {
        if (!$proceso) {
            return 'SIN PROCESO';
        }

        $proceso = strtoupper(trim($proceso));

        /*
         * LOGISTICA - LOGISTICA Y DISTRIBUCION
         * =>
         * LOGISTICA Y DISTRIBUCION
         */

        if (str_contains($proceso, ' - ')) {

            $partes = explode(' - ', $proceso, 2);

            if (isset($partes[1])) {
                $proceso = trim($partes[1]);
            }
        }

        /*
         * TERMINACION - PRODUCTO TERMINADO
         * =>
         * PRODUCTO TERMINADO
         */

        if (str_contains($proceso, 'PRODUCTO TERMINADO')) {
            return 'PRODUCTO TERMINADO';
        }

        if (str_contains($proceso, 'LOGISTICA Y DISTRIBUCION')) {
            return 'LOGISTICA Y DISTRIBUCION';
        }

        return $proceso;
    }


    /*
    |--------------------------------------------------------------------------
    | ORDEN DEL PROCESO
    |--------------------------------------------------------------------------
    |
    | Si ya tenés tu método ordenProceso(), podés utilizarlo.
    |
    */

    private function ordenProcesoDashboard($proceso): int
    {
        $proceso = $this->normalizarProcesoDashboard($proceso);

        $flujo = [
            'PEDIDO',
            'CORTE',
            'CONFECCION',
            'ESTAMPADO',
            'BORDADO',
            'LAVANDERIA',
            'TERMINACION',
            'CONTROL DE CALIDAD',
            'PRODUCTO TERMINADO',
            'LOGISTICA Y DISTRIBUCION',
        ];

        $posicion = array_search($proceso, $flujo);

        return $posicion !== false
            ? $posicion
            : 999;
    }


    /*
    |--------------------------------------------------------------------------
    | DASHBOARD GENERAL DE OT
    |--------------------------------------------------------------------------
    */

    public function dashboardOT(Request $request)
    {
        /*
    |--------------------------------------------------------------------------
    | VALIDACIÓN
    |--------------------------------------------------------------------------
    */

        $request->validate([
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
            'proceso'     => 'nullable|string',
        ]);


        /*
    |--------------------------------------------------------------------------
    | FECHAS
    |--------------------------------------------------------------------------
    */

        $fechaDesde = $request->filled('fecha_desde')
            ? Carbon::parse($request->fecha_desde)->startOfDay()
            : null;

        $fechaHasta = $request->filled('fecha_hasta')
            ? Carbon::parse($request->fecha_hasta)->endOfDay()
            : null;


        /*
    |--------------------------------------------------------------------------
    | CONFIGURACIÓN
    |--------------------------------------------------------------------------
    */

        $diasAlerta = self::DIAS_PARA_ALERTA;

        /*
     * Procesos que significan que la OT llegó a su final.
     *
     * REVISION también se considera finalizada porque en tu flujo
     * representa 100%.
     */
        $procesosFinales = [
            'PRODUCTO TERMINADO',
            'LOGISTICA Y DISTRIBUCION',
            'REVISION',
        ];


        /*
    |--------------------------------------------------------------------------
    | OBTENER OT
    |--------------------------------------------------------------------------
    |
    | Solamente traemos OT que tengan trazabilidad.
    |
    */

        $ots = Ot::with('trazabilidades')
            ->whereHas('trazabilidades')
            ->get();


        /*
    |--------------------------------------------------------------------------
    | COLECCIONES PRINCIPALES
    |--------------------------------------------------------------------------
    */

        $datosOT = collect();

        $procesosTiempo = collect();

        $produccionPorFecha = collect();


        /*
    |--------------------------------------------------------------------------
    | RECORRER OT
    |--------------------------------------------------------------------------
    */

        foreach ($ots as $ot) {

            /*
        |--------------------------------------------------------------------------
        | EXCLUIR POSTERGADAS
        |--------------------------------------------------------------------------
        */

            $estadoOT = strtoupper(
                trim((string) $ot->estado)
            );

            if ($estadoOT === 'POSTERGADO') {
                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | TRAZABILIDADES
        |--------------------------------------------------------------------------
        */

            $trazabilidades = $ot->trazabilidades;

            if ($trazabilidades->isEmpty()) {
                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | NORMALIZAR TRAZABILIDADES
        |--------------------------------------------------------------------------
        |
        | Primero normalizamos todos los procesos para que:
        |
        | PRODUCCION - CORTE
        | CORTE
        | Produccion - Corte.
        |
        | terminen siendo:
        |
        | CORTE
        |
        */

            $trazas = $trazabilidades->map(function ($traza) {

                $traza->proceso_normalizado =
                    $this->normalizarProceso(
                        $traza->proceso
                    );

                $traza->fecha_carbon =
                    Carbon::parse(
                        $traza->fecha_proceso
                    );

                $traza->orden_flujo =
                    $this->ordenProceso(
                        $traza->proceso
                    );

                return $traza;
            });


            /*
        |--------------------------------------------------------------------------
        | ÚLTIMO MOVIMIENTO REAL
        |--------------------------------------------------------------------------
        |
        | IMPORTANTE:
        |
        | Esto NO determina el proceso actual.
        |
        | Solamente determina cuál fue el último movimiento realizado
        | para calcular días sin movimiento.
        |
        */

            $ultimoMovimiento = $trazas
                ->sortByDesc(function ($traza) {

                    return $traza->fecha_carbon->timestamp;
                })
                ->first();


            if (!$ultimoMovimiento) {
                continue;
            }


            $fechaUltimoMovimiento =
                $ultimoMovimiento->fecha_carbon;


            /*
        |--------------------------------------------------------------------------
        | FILTRO DE FECHAS
        |--------------------------------------------------------------------------
        |
        | El filtro se aplica sobre el último movimiento.
        |
        */

            if (
                $fechaDesde &&
                $fechaUltimoMovimiento->lt($fechaDesde)
            ) {
                continue;
            }

            if (
                $fechaHasta &&
                $fechaUltimoMovimiento->gt($fechaHasta)
            ) {
                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | PROCESO ACTUAL SEGÚN EL FLUJO
        |--------------------------------------------------------------------------
        |
        | ESTA ES LA PARTE MÁS IMPORTANTE.
        |
        | No usamos simplemente:
        |
        | $trazas->last()
        |
        | porque eso depende del orden de fecha.
        |
        | En cambio buscamos el proceso MÁS AVANZADO dentro del
        | FLUJO_PROCESOS.
        |
        |
        | Ejemplo:
        |
        | CORTE                -> 40
        | COSTURA INTERNA      -> 70
        | TERMINACION          -> 95
        |
        | Aunque TERMINACION tenga una fecha anterior por algún
        | problema de carga, la OT ya alcanzó TERMINACION.
        |
        */

            $procesoActualTraza = $trazas
                ->sortByDesc(function ($traza) {

                    /*
                 * Primero manda el orden del flujo.
                 *
                 * La fecha queda como desempate cuando hay
                 * procesos repetidos.
                 */

                    return sprintf(
                        '%05d-%010d',
                        $traza->orden_flujo,
                        $traza->fecha_carbon->timestamp
                    );
                })
                ->first();


            if (!$procesoActualTraza) {
                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | PROCESO ACTUAL
        |--------------------------------------------------------------------------
        */

            $procesoActual =
                $procesoActualTraza->proceso_normalizado;


            /*
        |--------------------------------------------------------------------------
        | ORDEN DEL PROCESO
        |--------------------------------------------------------------------------
        */

            $ordenProcesoActual =
                $procesoActualTraza->orden_flujo;


            /*
        |--------------------------------------------------------------------------
        | AVANCE
        |--------------------------------------------------------------------------
        */

            $avance = $this->calcularAvance(
                $procesoActual,
                1
            );


            /*
        |--------------------------------------------------------------------------
        | PROCESO SUSPENDIDO
        |--------------------------------------------------------------------------
        */

            $suspendida =
                $procesoActual === self::PROCESO_SUSPENDIDO;


            /*
        |--------------------------------------------------------------------------
        | SI ESTÁ SUSPENDIDA
        |--------------------------------------------------------------------------
        |
        | Buscamos el último proceso REAL alcanzado antes de la
        | suspensión para saber dónde quedó la OT.
        |
        */

            $procesoAnterior = null;

            $avanceAnterior = 0;


            if ($suspendida) {

                $procesoAnteriorTraza = $trazas
                    ->filter(function ($traza) {

                        return
                            $traza->proceso_normalizado
                            !== self::PROCESO_SUSPENDIDO;
                    })
                    ->sortByDesc(function ($traza) {

                        return sprintf(
                            '%05d-%010d',
                            $traza->orden_flujo,
                            $traza->fecha_carbon->timestamp
                        );
                    })
                    ->first();


                if ($procesoAnteriorTraza) {

                    $procesoAnterior =
                        $procesoAnteriorTraza->proceso_normalizado;

                    $avanceAnterior =
                        $this->calcularAvance(
                            $procesoAnterior,
                            1
                        );
                }

                /*
             * Una OT suspendida no debe tener 100%.
             */

                $avance = $avanceAnterior;
            }


            /*
        |--------------------------------------------------------------------------
        | FINALIZADA
        |--------------------------------------------------------------------------
        */

            $finalizada =
                !$suspendida &&
                in_array(
                    $procesoActual,
                    $procesosFinales,
                    true
                );


            /*
        |--------------------------------------------------------------------------
        | DÍAS SIN MOVIMIENTO
        |--------------------------------------------------------------------------
        */

            $diasSinMovimiento = round(
                $fechaUltimoMovimiento
                    ->diffInMinutes(Carbon::now())
                    / 60
                    / 24,
                2
            );


            /*
        |--------------------------------------------------------------------------
        | ATRASADA
        |--------------------------------------------------------------------------
        |
        | Una OT finalizada NUNCA es atrasada.
        |
        | Una OT suspendida TAMPOCO.
        |
        */

            $atrasada =
                !$finalizada &&
                !$suspendida &&
                $diasSinMovimiento >= $diasAlerta;


            /*
        |--------------------------------------------------------------------------
        | ESTADO GENERAL
        |--------------------------------------------------------------------------
        */

            if ($suspendida) {

                $estado = 'SUSPENDIDO';
                $estadoColor = 'secondary';
            } elseif ($finalizada) {

                $estado = 'FINALIZADO';
                $estadoColor = 'success';
            } elseif ($atrasada) {

                $estado = 'ATRASADO';
                $estadoColor = 'danger';
            } else {

                $estado = 'EN PROCESO';
                $estadoColor = 'info';
            }


            /*
        |--------------------------------------------------------------------------
        | PRIMER MOVIMIENTO
        |--------------------------------------------------------------------------
        */

            $primerMovimiento = $trazas
                ->sortBy(function ($traza) {

                    return $traza->fecha_carbon->timestamp;
                })
                ->first();


            $fechaInicio = $primerMovimiento
                ? $primerMovimiento->fecha_carbon
                : $fechaUltimoMovimiento;


            /*
        |--------------------------------------------------------------------------
        | DURACIÓN TOTAL
        |--------------------------------------------------------------------------
        */

            $diasTotales = round(
                $fechaInicio
                    ->diffInMinutes($fechaUltimoMovimiento)
                    / 60
                    / 24,
                2
            );


            /*
        |--------------------------------------------------------------------------
        | CANTIDAD ORDENADA
        |--------------------------------------------------------------------------
        */

            $cantidadOrdenada =
                (float) $ot->cantidad_orden;


            /*
        |--------------------------------------------------------------------------
        | CANTIDAD PRODUCIDA
        |--------------------------------------------------------------------------
        |
        | Tomamos el máximo resultado registrado para evitar sumar
        | varias veces resultados acumulados.
        |
        */

            $resultadoMaximo = $trazas
                ->filter(function ($traza) {

                    return $traza->resultado !== null;
                })
                ->max(function ($traza) {

                    return (float) $traza->resultado;
                });


            $cantidadProducida =
                $resultadoMaximo !== null
                ? $resultadoMaximo
                : 0;


            /*
        |--------------------------------------------------------------------------
        | CUMPLIMIENTO
        |--------------------------------------------------------------------------
        */

            $cumplimiento =
                $cantidadOrdenada > 0
                ? round(
                    (
                        $cantidadProducida
                        / $cantidadOrdenada
                    ) * 100,
                    2
                )
                : 0;


            /*
        |--------------------------------------------------------------------------
        | NO PERMITIR MÁS DE 100%
        |--------------------------------------------------------------------------
        |
        | Para el KPI visual evitamos mostrar 250%, 300%, etc.
        |
        | El dato original sigue estando en cantidadProducida.
        |
        */

            $cumplimientoVisual =
                min($cumplimiento, 100);


            /*
        |--------------------------------------------------------------------------
        | FILTRO POR PROCESO
        |--------------------------------------------------------------------------
        */

            if ($request->filled('proceso')) {

                $procesoFiltro =
                    $this->normalizarProceso(
                        $request->proceso
                    );

                if ($procesoFiltro !== $procesoActual) {
                    continue;
                }
            }


            /*
        |--------------------------------------------------------------------------
        | GUARDAR INFORMACIÓN DE LA OT
        |--------------------------------------------------------------------------
        */

            $datosOT->push([

                'ot' =>
                $ot,

                'estado' =>
                $estado,

                'estado_color' =>
                $estadoColor,

                'ultimo_proceso' =>
                $procesoActual,

                'proceso_anterior' =>
                $procesoAnterior,

                'orden_proceso' =>
                $ordenProcesoActual,

                'avance' =>
                $avance,

                'ultimo_movimiento' =>
                $ultimoMovimiento,

                'fecha_ultimo_movimiento' =>
                $fechaUltimoMovimiento,

                'fecha_inicio' =>
                $fechaInicio,

                'dias_sin_movimiento' =>
                $diasSinMovimiento,

                'dias_totales' =>
                $diasTotales,

                'finalizada' =>
                $finalizada,

                'suspendida' =>
                $suspendida,

                'atrasada' =>
                $atrasada,

                'cantidad_ordenada' =>
                $cantidadOrdenada,

                'cantidad_producida' =>
                $cantidadProducida,

                'cumplimiento' =>
                $cumplimiento,

                'cumplimiento_visual' =>
                $cumplimientoVisual,

                'cantidad_procesos' =>
                $trazas->count(),

                'trazabilidades' =>
                $trazas,

            ]);


            /*
        |--------------------------------------------------------------------------
        | TIEMPO ENTRE PROCESOS
        |--------------------------------------------------------------------------
        |
        | Acá sí usamos el flujo.
        |
        | Ordenamos por:
        |
        | 1. posición del flujo
        | 2. fecha
        |
        */

            $trazasFlujo = $trazas
                ->sortBy(function ($traza) {

                    return sprintf(
                        '%05d-%010d',
                        $traza->orden_flujo,
                        $traza->fecha_carbon->timestamp
                    );
                })
                ->values();


            /*
        |--------------------------------------------------------------------------
        | CALCULAR TIEMPO ENTRE PROCESOS
        |--------------------------------------------------------------------------
        */

            for (
                $i = 1;
                $i < $trazasFlujo->count();
                $i++
            ) {

                $anterior =
                    $trazasFlujo[$i - 1];

                $actual =
                    $trazasFlujo[$i];


                $fechaAnterior =
                    $anterior->fecha_carbon;

                $fechaActual =
                    $actual->fecha_carbon;


                /*
             * Si por algún motivo las fechas están invertidas,
             * no generamos un tiempo negativo.
             */

                if ($fechaActual->lt($fechaAnterior)) {
                    continue;
                }


                $dias =
                    round(
                        $fechaAnterior
                            ->diffInMinutes($fechaActual)
                            / 60
                            / 24,
                        2
                    );


                $procesoDestino =
                    $actual->proceso_normalizado;


                if (!$procesosTiempo->has($procesoDestino)) {

                    $procesosTiempo->put(
                        $procesoDestino,
                        [
                            'total_dias' => 0,
                            'cantidad'   => 0,
                        ]
                    );
                }


                $info =
                    $procesosTiempo->get(
                        $procesoDestino
                    );


                $info['total_dias'] += $dias;
                $info['cantidad']++;


                $procesosTiempo->put(
                    $procesoDestino,
                    $info
                );
            }


            /*
        |--------------------------------------------------------------------------
        | PRODUCCIÓN POR FECHA
        |--------------------------------------------------------------------------
        |
        | Guardamos el mayor resultado de cada OT por fecha.
        |
        */

            foreach ($trazas as $traza) {

                if ($traza->resultado === null) {
                    continue;
                }


                $fecha =
                    $traza->fecha_carbon
                    ->format('Y-m-d');


                $clave =
                    $fecha . '_' . $ot->id_ot;


                $resultado =
                    (float) $traza->resultado;


                if (
                    !$produccionPorFecha->has($clave)
                    ||
                    $resultado >
                    $produccionPorFecha->get($clave)
                ) {

                    $produccionPorFecha->put(
                        $clave,
                        $resultado
                    );
                }
            }
        }


        /*
    |--------------------------------------------------------------------------
    | KPIs PRINCIPALES
    |--------------------------------------------------------------------------
    */

        $totalOT =
            $datosOT->count();


        $otEnProceso =
            $datosOT
            ->where('estado', 'EN PROCESO')
            ->count();


        $otFinalizadas =
            $datosOT
            ->where('finalizada', true)
            ->count();


        $otAtrasadas =
            $datosOT
            ->where('atrasada', true)
            ->count();


        $otSuspendidas =
            $datosOT
            ->where('suspendida', true)
            ->count();


        /*
    |--------------------------------------------------------------------------
    | CANTIDADES
    |--------------------------------------------------------------------------
    */

        $cantidadOrdenada =
            $datosOT->sum(
                'cantidad_ordenada'
            );


        $cantidadProducida =
            $datosOT->sum(
                'cantidad_producida'
            );


        /*
    |--------------------------------------------------------------------------
    | CUMPLIMIENTO GENERAL
    |--------------------------------------------------------------------------
    |
    | MUY IMPORTANTE:
    |
    | No hacemos promedio de porcentajes.
    |
    | Calculamos:
    |
    | total producido / total ordenado
    |
    */

        $cumplimientoGeneral =
            $cantidadOrdenada > 0
            ? round(
                (
                    $cantidadProducida
                    / $cantidadOrdenada
                ) * 100,
                2
            )
            : 0;


        $cumplimientoGeneralVisual =
            min($cumplimientoGeneral, 100);


        /*
    |--------------------------------------------------------------------------
    | PROMEDIO DE DÍAS
    |--------------------------------------------------------------------------
    */

        $promedioDias =
            $totalOT > 0
            ? round(
                $datosOT->avg(
                    'dias_totales'
                ),
                2
            )
            : 0;


        /*
    |--------------------------------------------------------------------------
    | PROMEDIO DE ATRASO
    |--------------------------------------------------------------------------
    */

        $promedioAtraso =
            $otAtrasadas > 0
            ? round(
                $datosOT
                    ->where(
                        'atrasada',
                        true
                    )
                    ->avg(
                        'dias_sin_movimiento'
                    ),
                2
            )
            : 0;


        /*
    |--------------------------------------------------------------------------
    | MAYOR ATRASO
    |--------------------------------------------------------------------------
    */

        $mayorAtraso =
            $otAtrasadas > 0
            ? $datosOT
            ->where(
                'atrasada',
                true
            )
            ->max(
                'dias_sin_movimiento'
            )
            : 0;


        /*
    |--------------------------------------------------------------------------
    | OT POR PROCESO
    |--------------------------------------------------------------------------
    |
    | Ahora se basa en el PROCESO ACTUAL SEGÚN FLUJO.
    |
    */

        $otsPorProceso =
            $datosOT
            ->groupBy(
                'ultimo_proceso'
            )
            ->map(function ($items) {

                return $items->count();
            })
            ->sortDesc();


        /*
    |--------------------------------------------------------------------------
    | DETALLE DE PROCESOS
    |--------------------------------------------------------------------------
    */

        $detalleProcesos =
            $otsPorProceso
            ->map(
                function (
                    $cantidad,
                    $proceso
                ) use (
                    $totalOT
                ) {

                    return [

                        'proceso' =>
                        $proceso,

                        'cantidad' =>
                        $cantidad,

                        'porcentaje' =>
                        $totalOT > 0
                            ? round(
                                (
                                    $cantidad
                                    / $totalOT
                                ) * 100,
                                2
                            )
                            : 0,
                    ];
                }
            )
            ->values();


        /*
    |--------------------------------------------------------------------------
    | OT POR DESCRIPCIÓN
    |--------------------------------------------------------------------------
    |
    | Esto te permite ver qué productos/descripciones están generando
    | mayor cantidad de OT.
    |
    */

        $porDescripcion =
            $datosOT
            ->groupBy(function ($item) {

                return trim(
                    (string)
                    $item['ot']->descripcion
                );
            })
            ->map(
                function (
                    $items,
                    $descripcion
                ) {

                    $ordenadas =
                        $items->sum(
                            'cantidad_ordenada'
                        );

                    $producidas =
                        $items->sum(
                            'cantidad_producida'
                        );


                    return [

                        'descripcion' =>
                        $descripcion,

                        'cantidad_ot' =>
                        $items->count(),

                        'cantidad_ordenada' =>
                        $ordenadas,

                        'cantidad_producida' =>
                        $producidas,

                        'promedio_atraso' =>
                        round(
                            $items->avg(
                                'dias_sin_movimiento'
                            ),
                            2
                        ),

                        'mayor_atraso' =>
                        $items->max(
                            'dias_sin_movimiento'
                        ),

                        'cumplimiento' =>
                        $ordenadas > 0
                            ? round(
                                (
                                    $producidas
                                    / $ordenadas
                                ) * 100,
                                2
                            )
                            : 0,
                    ];
                }
            )
            ->sortByDesc(
                'cantidad_ordenada'
            )
            ->values();


        /*
    |--------------------------------------------------------------------------
    | RANKING DE OT ATRASADAS
    |--------------------------------------------------------------------------
    */

        $rankingAtrasadas =
            $datosOT
            ->where(
                'atrasada',
                true
            )
            ->sortByDesc(
                'dias_sin_movimiento'
            )
            ->take(20)
            ->values();


        /*
    |--------------------------------------------------------------------------
    | TIEMPO PROMEDIO POR PROCESO
    |--------------------------------------------------------------------------
    */

        $tiempoPorProceso =
            $procesosTiempo
            ->map(
                function (
                    $info,
                    $proceso
                ) {

                    return [

                        'proceso' =>
                        $proceso,

                        'promedio_dias' =>
                        $info['cantidad'] > 0
                            ? round(
                                $info['total_dias']
                                    /
                                    $info['cantidad'],
                                2
                            )
                            : 0,

                        'cantidad' =>
                        $info['cantidad'],
                    ];
                }
            )
            ->sortByDesc(
                'promedio_dias'
            )
            ->values();


        /*
    |--------------------------------------------------------------------------
    | PRODUCCIÓN DIARIA
    |--------------------------------------------------------------------------
    */

        $produccionDiariaFinal =
            collect();


        foreach (
            $produccionPorFecha
            as $clave => $cantidad
        ) {

            /*
         * Como la clave es:
         *
         * YYYY-MM-DD_IDOT
         *
         * obtenemos solamente la fecha.
         */

            $fecha =
                explode(
                    '_',
                    $clave
                )[0];


            if (
                !$produccionDiariaFinal
                    ->has($fecha)
            ) {

                $produccionDiariaFinal->put(
                    $fecha,
                    0
                );
            }


            $produccionDiariaFinal->put(
                $fecha,
                $produccionDiariaFinal->get(
                    $fecha
                ) + $cantidad
            );
        }


        $produccionDiariaFinal =
            $produccionDiariaFinal
            ->sortKeys();


        /*
    |--------------------------------------------------------------------------
    | PROCESOS DISPONIBLES
    |--------------------------------------------------------------------------
    |
    | Los ordenamos según FLUJO_PROCESOS, no alfabéticamente.
    |
    */

        $procesosDisponibles =
            $otsPorProceso
            ->keys()
            ->sortBy(function ($proceso) {

                return $this->ordenProceso(
                    $proceso
                );
            })
            ->values();


        /*
    |--------------------------------------------------------------------------
    | CONTADORES ADICIONALES
    |--------------------------------------------------------------------------
    |
    | Estos te sirven mucho para el dashboard.
    |
    */

        $otCon100Porciento =
            $datosOT
            ->where(
                'cumplimiento',
                '>=',
                100
            )
            ->count();


        $otMenor50Porciento =
            $datosOT
            ->filter(function ($item) {

                return
                    $item['cumplimiento'] < 50;
            })
            ->count();


        $otSinProduccion =
            $datosOT
            ->where(
                'cantidad_producida',
                0
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | RETORNAR VISTA
    |--------------------------------------------------------------------------
    */

        return view(
            'dashboard.ot-dashboard',
            compact(

                'datosOT',

                'totalOT',

                'otEnProceso',

                'otFinalizadas',

                'otAtrasadas',

                'otSuspendidas',

                'cantidadOrdenada',

                'cantidadProducida',

                'cumplimientoGeneral',

                'cumplimientoGeneralVisual',

                'promedioDias',

                'promedioAtraso',

                'mayorAtraso',

                'diasAlerta',

                'otsPorProceso',

                'detalleProcesos',

                'porDescripcion',

                'rankingAtrasadas',

                'tiempoPorProceso',

                'produccionDiariaFinal',

                'procesosDisponibles',

                'otCon100Porciento',

                'otMenor50Porciento',

                'otSinProduccion',

                'fechaDesde',

                'fechaHasta'
            )
        );
    }

    public function dashboardlogistica(Request $request)
    {
        /*
    |--------------------------------------------------------------------------
    | QUERY BASE
    |--------------------------------------------------------------------------
    |
    | Relación:
    |
    | ot_logistica_detalle
    |        ↓
    | ot_trazabilidad
    |        ↓
    | ot
    |
    | La fecha oficial para este dashboard es:
    |
    |     t.fecha_proceso
    |
    */

        $baseQuery = DB::table('ot_logistica_detalle as d')

            ->leftJoin(
                'ot_trazabilidad as t',
                't.id_trazabilidad',
                '=',
                'd.id_trazabilidad'
            )

            ->leftJoin(
                'ot as o',
                'o.id_ot',
                '=',
                'd.id_ot'
            );


        /*
    |--------------------------------------------------------------------------
    | FILTRO FECHA DESDE
    |--------------------------------------------------------------------------
    */

        if ($request->filled('fecha_desde')) {

            $baseQuery->whereDate(
                't.fecha_proceso',
                '>=',
                $request->fecha_desde
            );
        }


        /*
    |--------------------------------------------------------------------------
    | FILTRO FECHA HASTA
    |--------------------------------------------------------------------------
    */

        if ($request->filled('fecha_hasta')) {

            $baseQuery->whereDate(
                't.fecha_proceso',
                '<=',
                $request->fecha_hasta
            );
        }


        /*
    |--------------------------------------------------------------------------
    | FILTRO SUCURSAL
    |--------------------------------------------------------------------------
    */

        if ($request->filled('sucursal')) {

            $sucursalesSeleccionadas = $request->input(
                'sucursal',
                []
            );

            $baseQuery->whereIn(
                'd.sucursal',
                $sucursalesSeleccionadas
            );
        }


        /*
    |--------------------------------------------------------------------------
    | FILTRO N° OT / CÓDIGO
    |--------------------------------------------------------------------------
    */

        if ($request->filled('busqueda')) {

            $busqueda = trim(
                $request->busqueda
            );


            if (!ctype_digit($busqueda)) {

                alert()->warning(
                    'Dato inválido',
                    'Ingrese solo números en el campo N° OT o Código.'
                );

                return redirect()
                    ->back()
                    ->withInput();
            }


            if (strlen($busqueda) > 10) {

                alert()->warning(
                    'N° OT / Código inválido',
                    'El número ingresado es demasiado grande.'
                );

                return redirect()
                    ->back()
                    ->withInput();
            }


            $baseQuery->where(function ($query) use ($busqueda) {

                $query->where(
                    'o.nro_ot',
                    (int) $busqueda
                )

                    ->orWhere(
                        'o.codigo',
                        'ILIKE',
                        $busqueda . '%'
                    );
            });
        }


        /*
    |--------------------------------------------------------------------------
    | DETALLE
    |--------------------------------------------------------------------------
    */

        $detalles = (clone $baseQuery)

            ->select([

                'd.id',
                'd.id_ot',
                'd.id_trazabilidad',
                'd.sucursal',
                'd.cantidad',
                'd.created_at',

                't.proceso',
                't.resultado',
                't.fecha_proceso',

                'o.nro_ot',
                'o.codigo',
                'o.descripcion',
                'o.cantidad_orden',
                'o.estado',
                'o.obs',
            ])

            /*
        |--------------------------------------------------------------------------
        | FECHA
        |--------------------------------------------------------------------------
        */

            ->orderByRaw(
                't.fecha_proceso IS NULL ASC'
            )

            ->orderByDesc(
                't.fecha_proceso'
            )

            /*
        |--------------------------------------------------------------------------
        | N° OT
        |--------------------------------------------------------------------------
        */

            ->orderBy(
                'o.nro_ot',
                'asc'
            )

            /*
        |--------------------------------------------------------------------------
        | CÓDIGO
        |--------------------------------------------------------------------------
        */

            ->orderBy(
                'o.codigo',
                'asc'
            )

            /*
        |--------------------------------------------------------------------------
        | SUCURSAL
        |--------------------------------------------------------------------------
        */

            ->orderBy(
                'd.sucursal',
                'asc'
            )

            /*
        |--------------------------------------------------------------------------
        | TRAZABILIDAD
        |--------------------------------------------------------------------------
        */

            ->orderByDesc(
                'd.id_trazabilidad'
            )

            /*
        |--------------------------------------------------------------------------
        | ID DETALLE
        |--------------------------------------------------------------------------
        */

            ->orderByDesc(
                'd.id'
            )

            ->paginate(30)

            ->withQueryString();


        /*
    |--------------------------------------------------------------------------
    | TOTAL REGISTROS
    |--------------------------------------------------------------------------
    */

        $totalRegistros = (clone $baseQuery)
            ->count('d.id');


        /*
    |--------------------------------------------------------------------------
    | TOTAL OT
    |--------------------------------------------------------------------------
    |
    | Una OT puede tener varias trazabilidades/detalles.
    |
    */

        $totalOT = (clone $baseQuery)
            ->distinct()
            ->count('d.id_ot');


        /*
    |--------------------------------------------------------------------------
    | TOTAL CANTIDAD ENVIADA
    |--------------------------------------------------------------------------
    |
    | La cantidad enviada corresponde a:
    |
    | ot_logistica_detalle.cantidad
    |
    */

        $totalCantidadEnviada = (clone $baseQuery)
            ->sum('d.cantidad');


        /*
    |--------------------------------------------------------------------------
    | TOTAL CANTIDAD
    |--------------------------------------------------------------------------
    |
    | Mantenemos esta variable porque ya la utilizás
    | posiblemente en otras partes del dashboard.
    |
    | Actualmente representa la cantidad de logística.
    |
    */

        $totalCantidad = $totalCantidadEnviada;


        /*
    |--------------------------------------------------------------------------
    | TOTAL CANTIDAD ORDENADA
    |--------------------------------------------------------------------------
    |
    | Tomamos solamente una vez cada OT.
    |
    | Esto evita que una OT con varias trazabilidades
    | sea sumada varias veces.
    |
    */

        $totalCantidadOrdenada = DB::query()

            ->fromSub(

                (clone $baseQuery)

                    ->select([
                        'o.id_ot',
                        'o.cantidad_orden',
                    ])

                    ->distinct(),

                'ots'
            )

            ->sum(
                'cantidad_orden'
            );


        /*
    |--------------------------------------------------------------------------
    | OTs FILTRADAS
    |--------------------------------------------------------------------------
    |
    | Obtenemos las OTs que realmente aparecen después
    | de aplicar los filtros del dashboard.
    |
    */

        $otFiltradas = (clone $baseQuery)

            ->select(
                'o.id_ot'
            )

            ->whereNotNull(
                'o.id_ot'
            )

            ->distinct()

            ->pluck(
                'id_ot'
            );


        /*
    |--------------------------------------------------------------------------
    | TOTAL CANTIDAD CORTADA
    |--------------------------------------------------------------------------
    |
    | La cantidad cortada está en:
    |
    | ot_trazabilidad.resultado
    |
    | solamente para:
    |
    | PRODUCCION - CORTE
    |
    */

        $totalCantidadCortada = 0;


        if ($otFiltradas->isNotEmpty()) {

            $totalCantidadCortada = DB::table(
                'ot_trazabilidad as tc'
            )

                ->whereIn(
                    'tc.id_ot',
                    $otFiltradas
                )

                ->where(
                    'tc.proceso',
                    'PRODUCCION - CORTE'
                )

                ->sum(
                    'tc.resultado'
                );
        }


        /*
    |--------------------------------------------------------------------------
    | DIFERENCIA ORDENADA - CORTADA
    |--------------------------------------------------------------------------
    |
    | Ejemplo:
    |
    | Ordenada = 1000
    | Cortada  = 949
    |
    | Diferencia = 51
    |
    */

        $diferenciaOrdenadaCortada =
            $totalCantidadOrdenada
            -
            $totalCantidadCortada;


        /*
    |--------------------------------------------------------------------------
    | DIFERENCIA CORTADA - ENVIADA
    |--------------------------------------------------------------------------
    |
    | Ejemplo:
    |
    | Cortada = 949
    | Enviada = 949
    |
    | Diferencia = 0
    |
    */

        $diferenciaCortadaEnviada =
            $totalCantidadCortada
            -
            $totalCantidadEnviada;


        /*
    |--------------------------------------------------------------------------
    | TOTAL SUCURSALES
    |--------------------------------------------------------------------------
    */

        $totalSucursales = (clone $baseQuery)

            ->whereNotNull(
                'd.sucursal'
            )

            ->where(
                'd.sucursal',
                '<>',
                ''
            )

            ->distinct()

            ->count(
                'd.sucursal'
            );


        /*
    |--------------------------------------------------------------------------
    | INFORMACIÓN DE HOY
    |--------------------------------------------------------------------------
    */

        $hoyQuery = clone $baseQuery;

        $hoyQuery->whereDate(
            't.fecha_proceso',
            now()->toDateString()
        );


        /*
    |--------------------------------------------------------------------------
    | CANTIDAD DE HOY
    |--------------------------------------------------------------------------
    */

        $cantidadHoy = (clone $hoyQuery)
            ->sum('d.cantidad');


        /*
    |--------------------------------------------------------------------------
    | OTs DE HOY
    |--------------------------------------------------------------------------
    */

        $otHoy = (clone $hoyQuery)

            ->distinct()

            ->count(
                'd.id_ot'
            );


        /*
    |--------------------------------------------------------------------------
    | REGISTROS DE HOY
    |--------------------------------------------------------------------------
    */

        $registrosHoy = (clone $hoyQuery)
            ->count('d.id');


        /*
    |--------------------------------------------------------------------------
    | RESUMEN POR FECHA
    |--------------------------------------------------------------------------
    */

        $porFecha = (clone $baseQuery)

            ->select([

                DB::raw(
                    'DATE(t.fecha_proceso) as fecha_proceso'
                ),

                DB::raw(
                    'COUNT(DISTINCT d.id_ot) as total_ot'
                ),

                DB::raw(
                    'COUNT(d.id) as total_registros'
                ),

                DB::raw(
                    'COALESCE(SUM(d.cantidad), 0) as total_cantidad'
                ),
            ])

            ->whereNotNull(
                't.fecha_proceso'
            )

            ->groupBy(
                DB::raw(
                    'DATE(t.fecha_proceso)'
                )
            )

            ->orderByDesc(
                DB::raw(
                    'DATE(t.fecha_proceso)'
                )
            )

            ->get();


        /*
    |--------------------------------------------------------------------------
    | RESUMEN POR SUCURSAL
    |--------------------------------------------------------------------------
    */

        $porSucursal = (clone $baseQuery)

            ->select([

                'd.sucursal',

                DB::raw(
                    'COUNT(DISTINCT o.nro_ot) as total_ot'
                ),

                DB::raw(
                    'COUNT(d.id) as total_registros'
                ),

                DB::raw(
                    'COALESCE(SUM(d.cantidad), 0) as total_cantidad'
                ),
            ])

            ->whereNotNull(
                'd.sucursal'
            )

            ->where(
                'd.sucursal',
                '<>',
                ''
            )

            ->groupBy(
                'd.sucursal'
            )

            ->orderByDesc(
                'total_cantidad'
            )

            ->get();


        /*
    |--------------------------------------------------------------------------
    | PROMEDIO DE CANTIDAD POR OT
    |--------------------------------------------------------------------------
    */

        $promedioCantidadOT = $totalOT > 0

            ? round(
                $totalCantidad / $totalOT,
                2
            )

            : 0;


        /*
    |--------------------------------------------------------------------------
    | SUCURSALES PARA SELECT2
    |--------------------------------------------------------------------------
    */

        $sucursales = DB::table(
            'ot_logistica_detalle'
        )

            ->select(
                'sucursal'
            )

            ->whereNotNull(
                'sucursal'
            )

            ->where(
                'sucursal',
                '<>',
                ''
            )

            ->distinct()

            ->orderBy(
                'sucursal'
            )

            ->pluck(
                'sucursal'
            );


        /*
    |--------------------------------------------------------------------------
    | RETORNAR VISTA
    |--------------------------------------------------------------------------
    */

        return view(
            'dashboard.ot-logistica',
            compact(
                'detalles',
                'totalRegistros',
                'totalOT',
                'totalCantidad',
                'totalCantidadOrdenada',
                'totalCantidadCortada',
                'totalCantidadEnviada',
                'diferenciaOrdenadaCortada',
                'diferenciaCortadaEnviada',
                'totalSucursales',
                'cantidadHoy',
                'otHoy',
                'registrosHoy',
                'promedioCantidadOT',
                'porFecha',
                'porSucursal',
                'sucursales'
            )
        );
    }


    /**
     * ============================================================
     * EXPORTAR DASHBOARD LOGÍSTICA
     * ============================================================
     */
    public function exportarDashboardLogistica(Request $request)
    {
        /*
    |--------------------------------------------------------------------------
    | NOMBRE DEL ARCHIVO
    |--------------------------------------------------------------------------
    */

        $nombreArchivo =
            'OT_Logistica_' .
            now()->format('Ymd_His') .
            '.xlsx';


        /*
    |--------------------------------------------------------------------------
    | EXPORTAR
    |--------------------------------------------------------------------------
    */

        return Excel::download(

            new OtLogisticaExport(
                $request->fecha_desde,
                $request->fecha_hasta,
                $request->sucursal,
                $request->nro_ot
            ),

            $nombreArchivo
        );
    }

    public function historiaGeneral(Request $request)
    {
        /*
    |--------------------------------------------------------------------------
    | ORDEN REAL DEL FLUJO
    |--------------------------------------------------------------------------
    */

        $ordenProcesos = [

            'DISEÑO - ORDEN DE TRABAJO'            => 10,
            'DISEÑO - MOLDERIA'                    => 15,
            'DISEÑO - PROTOTIPO'                   => 20,
            'DISEÑO - DISEÑO GRAFICO'              => 25,

            'PRODUCCION - TIZADAS'                 => 30,
            'PRODUCCION - CORTE'                   => 40,
            'PRODUCCION - LOTEO Y DISTRIBUCION'    => 45,
            'PRODUCCION - REVELADO'                => 50,
            'PRODUCCION - SERIGRAFIA'              => 55,
            'PRODUCCION - BORDADO'                 => 60,
            'PRODUCCION - COSTURA INTERNA'         => 70,
            'PRODUCCION - ATRAQUES'                => 75,
            'PRODUCCION - LAVANDERIA'              => 80,
            'PRODUCCION - PRETERMINACION'          => 85,

            'TERMINACION - INGRESO TERMINACION'    => 90,
            'TERMINACION - TERMINACION'            => 95,
            'TERMINACION - PRODUCTO TERMINADO'     => 100,

            'LOGISTICA - LOGISTICA Y DISTRIBUCION' => 110,
        ];


        /*
    |--------------------------------------------------------------------------
    | FECHAS
    |--------------------------------------------------------------------------
    */

        $fechaDesde = $request->fecha_desde;
        $fechaHasta = $request->fecha_hasta;


        /*
    |--------------------------------------------------------------------------
    | TRAER TODA LA TRAZABILIDAD
    |--------------------------------------------------------------------------
    |
    | No filtramos las fechas todavía.
    | Necesitamos conocer el movimiento anterior y siguiente de cada OT.
    |
    */

        $trazabilidades = DB::table('ot_trazabilidad as t')
            ->join('ot as o', 'o.id_ot', '=', 't.id_ot')
            ->select(
                't.id_trazabilidad',
                't.id_ot',
                'o.nro_ot',
                'o.codigo',
                'o.descripcion',
                'o.cantidad_orden',
                't.proceso',
                't.resultado',
                't.fecha_proceso'
            )
            ->orderBy('t.id_ot')
            ->orderBy('t.id_trazabilidad')
            ->get();


        /*
    |--------------------------------------------------------------------------
    | AGRUPAR POR OT
    |--------------------------------------------------------------------------
    */

        $trazabilidadesPorOt = $trazabilidades->groupBy('id_ot');


        /*
    |--------------------------------------------------------------------------
    | HISTORIA GENERAL
    |--------------------------------------------------------------------------
    */

        $historiaGeneral = [];


        /*
    |--------------------------------------------------------------------------
    | RECORRER CADA OT
    |--------------------------------------------------------------------------
    */

        foreach ($trazabilidadesPorOt as $idOt => $movimientos) {

            /*
        | Ordenar según el flujo real.
        */

            $movimientos = $movimientos
                ->sortBy(function ($movimiento) use ($ordenProcesos) {

                    return $ordenProcesos[$movimiento->proceso] ?? 999;
                })
                ->values();


            /*
        |--------------------------------------------------------------------------
        | RECORRER LOS MOVIMIENTOS DE ESTA OT
        |--------------------------------------------------------------------------
        */

            foreach ($movimientos as $index => $movimiento) {

                $movimientoAnterior = $movimientos->get($index - 1);

                $movimientoSiguiente = $movimientos->get($index + 1);


                /*
            |--------------------------------------------------------------------------
            | CANTIDAD
            |--------------------------------------------------------------------------
            */

                $resultado = (int) ($movimiento->resultado ?? 0);


                if ($resultado <= 0) {
                    continue;
                }


                /*
            |--------------------------------------------------------------------------
            | ENTRADA
            |--------------------------------------------------------------------------
            |
            | La entrada del proceso actual es la salida
            | del proceso anterior.
            |
            */

                $entrada = 0;

                if ($movimientoAnterior) {

                    $entrada = (int) ($movimientoAnterior->resultado ?? 0);
                }


                /*
            |--------------------------------------------------------------------------
            | SALIDA
            |--------------------------------------------------------------------------
            |
            | Si existe un proceso siguiente, esta cantidad sale
            | hacia ese proceso.
            |
            | Si es el último proceso, no tiene salida.
            |
            */

                $salida = 0;

                if ($movimientoSiguiente) {

                    $salida = $resultado;
                }


                /*
            |--------------------------------------------------------------------------
            | FILTRO DE FECHA
            |--------------------------------------------------------------------------
            */

                if (
                    $fechaDesde &&
                    $movimiento->fecha_proceso < $fechaDesde
                ) {
                    continue;
                }

                if (
                    $fechaHasta &&
                    $movimiento->fecha_proceso > $fechaHasta
                ) {
                    continue;
                }


                /*
            |--------------------------------------------------------------------------
            | CREAR PROCESO
            |--------------------------------------------------------------------------
            */

                if (!isset($historiaGeneral[$movimiento->proceso])) {

                    $historiaGeneral[$movimiento->proceso] = [

                        'proceso' => $movimiento->proceso,

                        'orden' =>
                        $ordenProcesos[$movimiento->proceso] ?? 999,

                        'entrada' => 0,

                        'salida' => 0,

                        'cantidad_ots' => 0,

                        'detalles' => [],
                    ];
                }


                /*
            |--------------------------------------------------------------------------
            | ACUMULAR
            |--------------------------------------------------------------------------
            */

                $historiaGeneral[$movimiento->proceso]['entrada']
                    += $entrada;

                $historiaGeneral[$movimiento->proceso]['salida']
                    += $salida;


                /*
            |--------------------------------------------------------------------------
            | DETALLE
            |--------------------------------------------------------------------------
            */

                $historiaGeneral[$movimiento->proceso]['detalles'][] = [

                    'id_ot' => $idOt,

                    'nro_ot' => $movimiento->nro_ot,

                    'codigo' => $movimiento->codigo,

                    'descripcion' => $movimiento->descripcion,

                    'cantidad_orden' =>
                    $movimiento->cantidad_orden,

                    'fecha' =>
                    $movimiento->fecha_proceso,

                    'entrada' =>
                    $entrada,

                    'salida' =>
                    $salida,

                    'proceso_anterior' =>
                    $movimientoAnterior
                        ? $movimientoAnterior->proceso
                        : null,

                    'proceso_siguiente' =>
                    $movimientoSiguiente
                        ? $movimientoSiguiente->proceso
                        : null,

                    'es_primero' =>
                    !$movimientoAnterior,

                    'es_ultimo' =>
                    !$movimientoSiguiente,
                ];
            }
        }


        /*
    |--------------------------------------------------------------------------
    | CANTIDAD DE OTS
    |--------------------------------------------------------------------------
    |
    | Contamos OTs diferentes por proceso.
    |
    */

        foreach ($historiaGeneral as &$proceso) {

            $proceso['cantidad_ots'] =
                collect($proceso['detalles'])
                ->pluck('nro_ot')
                ->unique()
                ->count();
        }

        unset($proceso);


        /*
    |--------------------------------------------------------------------------
    | ORDEN FINAL
    |--------------------------------------------------------------------------
    */

        $historia = collect($historiaGeneral)
            ->sortBy('orden')
            ->values();


        /*
    |--------------------------------------------------------------------------
    | VISTA
    |--------------------------------------------------------------------------
    */

        return view(
            'ots.historia_general',
            compact(
                'historia',
                'ordenProcesos'
            )
        );
    }
}
