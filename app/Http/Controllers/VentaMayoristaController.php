<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VentaMayoristaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:pedido_compras index');
    }

    /**
     * Panel de control del destino MODELO MUESTRA y destinos
     * identificados expresamente como MAYORISTA.
     *
     * No convierte una remisión en una venta: una remisión prueba
     * un traslado, y solo un comprobante registrado en ventas
     * prueba una operación comercial.
     */
    public function index(Request $request)
    {
        $tablas = Schema::hasTable('ot_logistica_detalle')
            && Schema::hasTable('ot_logistica_remisiones')
            && Schema::hasTable('ot');

        $ultimaRemision = $tablas
            ? DB::table('ot_logistica_remisiones')->max('fecha_remision')
            : null;
        $hoy = Carbon::today();
        $referencia = $ultimaRemision
            ? Carbon::parse($ultimaRemision) : $hoy;

        try {
            $desde = $request->filled('desde')
                ? Carbon::createFromFormat('Y-m-d', (string) $request->input('desde'))->format('Y-m-d')
                : $referencia->copy()->startOfMonth()->format('Y-m-d');
            $hasta = $request->filled('hasta')
                ? Carbon::createFromFormat('Y-m-d', (string) $request->input('hasta'))->format('Y-m-d')
                : $referencia->copy()->format('Y-m-d');
        } catch (\Exception $e) {
            $desde = $referencia->copy()->startOfMonth()->format('Y-m-d');
            $hasta = $referencia->format('Y-m-d');
        }
        if ($desde > $hasta) {
            $temp = $desde;
            $desde = $hasta;
            $hasta = $temp;
        }

        $tipo = (string) $request->query('destino', 'todos');
        if (!in_array($tipo, ['todos', 'modelo', 'mayorista'], true)) {
            $tipo = 'todos';
        }
        $buscar = trim((string) $request->query('buscar', ''));
        // Regla comercial provisional, nunca presentada como foto efectuada.
        $muestrasPorOt = min(20, max(0, (int) $request->query('muestras_por_ot', 1)));

        $modeloD = "UPPER(TRIM(COALESCE(d.sucursal, ''))) LIKE '%MODELO%MUESTRA%'";
        $modeloR = "(UPPER(TRIM(COALESCE(r.sucursal_logistica, ''))) LIKE '%MODELO%MUESTRA%' OR UPPER(TRIM(COALESCE(r.sucursal_destino, ''))) LIKE '%MODELO%MUESTRA%')";
        $mayoristaD = "UPPER(TRIM(COALESCE(d.sucursal, ''))) LIKE '%MAYORISTA%'";
        $mayoristaR = "(UPPER(TRIM(COALESCE(r.sucursal_logistica, ''))) LIKE '%MAYORISTA%' OR UPPER(TRIM(COALESCE(r.sucursal_destino, ''))) LIKE '%MAYORISTA%')";
        $modelo = "($modeloD OR $modeloR)";
        $mayorista = "($mayoristaD OR $mayoristaR)";
        $clase = "CASE WHEN $modelo THEN 'MODELO MUESTRA' WHEN $mayorista THEN 'MAYORISTA' ELSE 'OTRO' END";

        $planes = collect();
        $remisionesAgrupadas = collect();
        $remisiones = null;
        $filas = collect();
        $sinVinculo = 0;

        if ($tablas) {
            // Plan logístico histórico por OT: no se reinterpreta como
            // despacho ni se filtra por la fecha de remisión.
            $planQuery = DB::table('ot_logistica_detalle as d')
                ->join('ot as o', 'o.id_ot', '=', 'd.id_ot')
                ->where(function ($q) use ($modeloD, $mayoristaD, $tipo) {
                    if ($tipo !== 'mayorista') {
                        $q->orWhereRaw($modeloD);
                    }
                    if ($tipo !== 'modelo') {
                        $q->orWhereRaw($mayoristaD);
                    }
                });
            $planQuery = $this->filtrarOt($planQuery, $buscar, 'o');

            $clasePlan = "CASE WHEN $modeloD THEN 'MODELO MUESTRA' ELSE 'MAYORISTA' END";
            $planes = $planQuery
                ->selectRaw("d.id_ot, $clasePlan as canal, MAX(o.nro_ot) as nro_ot, MAX(o.codigo) as codigo, MAX(o.descripcion) as descripcion, COALESCE(SUM(d.cantidad),0) as asignado")
                ->groupBy('d.id_ot')
                ->groupByRaw($clasePlan)
                ->get();

            $baseRemision = DB::table('ot_logistica_remisiones as r')
                ->leftJoin('ot_logistica_detalle as d', 'd.id', '=', 'r.id_logistica_detalle')
                ->leftJoin('ot as o', function ($join) {
                    $join->on('o.id_ot', '=', DB::raw('COALESCE(r.id_ot, d.id_ot)'));
                })
                ->whereNotNull('r.fecha_remision')
                ->whereBetween('r.fecha_remision', [$desde, $hasta])
                ->where(function ($q) use ($modelo, $mayorista, $tipo) {
                    if ($tipo !== 'mayorista') {
                        $q->orWhereRaw($modelo);
                    }
                    if ($tipo !== 'modelo') {
                        $q->orWhereRaw($mayorista);
                    }
                });

            if ($buscar !== '') {
                $baseRemision->where(function ($q) use ($buscar) {
                    $q->whereRaw('CAST(o.nro_ot AS TEXT) ILIKE ?', ['%' . $buscar . '%'])
                      ->orWhere('o.codigo', 'ILIKE', '%' . $buscar . '%')
                      ->orWhere('r.codigo', 'ILIKE', '%' . $buscar . '%')
                      ->orWhere('r.descripcion', 'ILIKE', '%' . $buscar . '%')
                      ->orWhere('o.descripcion', 'ILIKE', '%' . $buscar . '%');
                });
            }

            // Agrupar en PostgreSQL primero: no cargar decenas de miles
            // de movimientos a PHP para calcular el resumen.
            $idExp = 'COALESCE(r.id_ot, d.id_ot)';
            $claveCodigo = "CASE WHEN $idExp IS NULL THEN r.codigo ELSE '' END";
            $remisionesAgrupadas = (clone $baseRemision)
                ->selectRaw("$idExp as id_ot, $claveCodigo as codigo_sin_vinculo, $clase as canal")
                ->selectRaw("MAX(o.nro_ot) as nro_ot, MAX(o.codigo) as codigo_ot, MAX(o.descripcion) as descripcion_ot, MAX(r.descripcion) as descripcion_remision")
                ->selectRaw("COALESCE(SUM(r.cantidad),0) as remitido, COALESCE(SUM(CASE WHEN r.fecha_recepcion IS NOT NULL THEN r.cantidad ELSE 0 END),0) as recibido")
                ->selectRaw("COALESCE(SUM(CASE WHEN r.cod_sucursal_salida = 1 OR UPPER(TRIM(COALESCE(r.sucursal_salida,''))) IN ('CASA CENTRAL','MATRIZ') THEN r.cantidad ELSE 0 END),0) as salida_central")
                ->selectRaw("COALESCE(SUM(CASE WHEN r.cod_sucursal_salida = 1 OR UPPER(TRIM(COALESCE(r.sucursal_salida,''))) IN ('CASA CENTRAL','MATRIZ') THEN 0 ELSE r.cantidad END),0) as traslado")
                ->selectRaw("COALESCE(SUM(r.cantidad * COALESCE(r.precio_venta,0)),0) as valor_referencial, COALESCE(SUM(r.cantidad * COALESCE(r.costo_unitario,0)),0) as costo_referencial")
                ->selectRaw("MIN(r.fecha_remision) as primera_remision, MAX(r.fecha_remision) as ultima_remision, MAX(r.fecha_recepcion) as ultima_recepcion, COUNT(*) as documentos")
                ->groupByRaw("$idExp, $claveCodigo, $clase")
                ->get();

            $remisiones = (clone $baseRemision)
                ->selectRaw("$idExp as id_ot, $clase as canal")
                ->addSelect(
                    'r.fecha_remision', 'r.fecha_recepcion', 'r.serie', 'r.numero_remision',
                    'r.codigo', 'r.descripcion', 'r.cantidad', 'r.precio_venta',
                    'r.costo_unitario', 'r.sucursal_salida', 'r.sucursal_destino',
                    'r.sucursal_logistica', 'o.nro_ot'
                )
                ->orderBy('r.fecha_remision', 'desc')
                ->orderBy('r.id', 'desc')
                ->paginate(30, ['*'], 'remisiones_page')
                ->appends($request->except('remisiones_page'));

            $mapa = [];
            $asegurar = function ($idOt, $codigoSinVinculo, $canal) use (&$mapa) {
                $clave = ($idOt === null ? 'SIN_OT:' . $codigoSinVinculo : 'OT:' . $idOt) . ':' . $canal;
                if (!isset($mapa[$clave])) {
                    $mapa[$clave] = (object) [
                        'id_ot' => $idOt,
                        'nro_ot' => null, 'codigo' => $codigoSinVinculo,
                        'descripcion' => null, 'canal' => $canal,
                        'asignado' => 0, 'remitido' => 0, 'recibido' => 0,
                        'salida_central' => 0, 'traslado' => 0, 'valor_referencial' => 0,
                        'costo_referencial' => 0, 'documentos' => 0,
                        'primera_remision' => null, 'ultima_remision' => null,
                        'ultima_recepcion' => null,
                    ];
                }
                return $clave;
            };

            foreach ($planes as $p) {
                $clave = $asegurar($p->id_ot, '', $p->canal);
                $mapa[$clave]->nro_ot = $p->nro_ot;
                $mapa[$clave]->codigo = $p->codigo;
                $mapa[$clave]->descripcion = $p->descripcion;
                $mapa[$clave]->asignado += (int) $p->asignado;
            }
            foreach ($remisionesAgrupadas as $p) {
                $clave = $asegurar($p->id_ot, $p->codigo_sin_vinculo, $p->canal);
                $fila = $mapa[$clave];
                $fila->nro_ot = $fila->nro_ot ?: $p->nro_ot;
                $fila->codigo = $fila->codigo ?: $p->codigo_ot;
                $fila->descripcion = $fila->descripcion ?: $p->descripcion_ot ?: $p->descripcion_remision;
                foreach (['remitido', 'recibido', 'salida_central', 'traslado', 'documentos'] as $campo) {
                    $fila->{$campo} += (int) $p->{$campo};
                }
                foreach (['valor_referencial', 'costo_referencial'] as $campo) {
                    $fila->{$campo} += (float) $p->{$campo};
                }
                $fila->primera_remision = $fila->primera_remision ?: $p->primera_remision;
                if (!$fila->ultima_remision || $p->ultima_remision > $fila->ultima_remision) {
                    $fila->ultima_remision = $p->ultima_remision;
                }
                if ($p->ultima_recepcion && (!$fila->ultima_recepcion || $p->ultima_recepcion > $fila->ultima_recepcion)) {
                    $fila->ultima_recepcion = $p->ultima_recepcion;
                }
            }

            $filas = collect(array_values($mapa))->map(function ($fila) use ($muestrasPorOt) {
                $fila->remitido = max(0, (int) $fila->remitido);
                $fila->recibido = min($fila->remitido, max(0, (int) $fila->recibido));
                $fila->en_transito = max(0, $fila->remitido - $fila->recibido);
                $fila->sin_vinculo = $fila->id_ot === null;
                $modelo = $fila->canal === 'MODELO MUESTRA';
                // Nunca declarar "vendido" por una remisión.
                // Si falta el vínculo a la OT, no imputar reserva ni stock comercial.
                $fila->muestra_estimada = $modelo && !$fila->sin_vinculo
                    ? min($muestrasPorOt, $fila->remitido) : 0;
                $fila->potencial_enviado = $fila->sin_vinculo ? 0
                    : max(0, $fila->remitido - $fila->muestra_estimada);
                // Recibido es lo que tiene fecha de recepción. Una prenda
                // todavía en tránsito no se presenta como disponible.
                $muestrasRecibidas = $modelo && !$fila->sin_vinculo
                    ? min($muestrasPorOt, $fila->recibido) : 0;
                $fila->potencial_venta = $fila->sin_vinculo ? 0
                    : max(0, $fila->recibido - $muestrasRecibidas);
                $fila->muestra_asignada = $modelo && !$fila->sin_vinculo
                    ? min($muestrasPorOt, max(0, (int) $fila->asignado)) : 0;
                $fila->sin_remision = max(0, (int) $fila->asignado - $fila->remitido);
                return $fila;
            })->sortByDesc('remitido')->values();
            $sinVinculo = $filas->where('sin_vinculo', true)->count();
        }

        $vinculadas = $filas->where('sin_vinculo', false);
        $resumen = (object) [
            'ot' => $vinculadas->where('remitido', '>', 0)->pluck('id_ot')->unique()->count(),
            'ot_asignadas' => $vinculadas->where('asignado', '>', 0)->pluck('id_ot')->unique()->count(),
            'asignado_historico' => (int) $filas->sum('asignado'),
            'remitido' => (int) $filas->sum('remitido'),
            'recibido' => (int) $filas->sum('recibido'),
            'en_transito' => (int) $filas->sum('en_transito'),
            'modelo_remitido' => (int) $filas->where('canal', 'MODELO MUESTRA')->sum('remitido'),
            'muestra_estimada' => (int) $vinculadas->sum('muestra_estimada'),
            'potencial_enviado' => (int) $vinculadas->sum('potencial_enviado'),
            'potencial_venta' => (int) $vinculadas->sum('potencial_venta'),
            'sin_vinculo' => (int) $filas->where('sin_vinculo', true)->sum('remitido'),
            'valor_referencial' => (float) $filas->sum('valor_referencial'),
            'costo_referencial' => (float) $filas->sum('costo_referencial'),
            'traslados' => (int) $filas->sum('traslado'),
        ];

        // Las ventas solo se reconocen si los movimientos de ventas
        // tienen un LOCAL explícito del canal mayorista; COMERCIAL MATRIZ
        // no se infiere mayorista y tampoco se cruzan códigos ambiguos.
        $ventasMay = null;
        if (Schema::hasTable('ventas')) {
            $query = DB::table('ventas as v')
                ->whereBetween('v.fecha', [$desde, $hasta])
                ->where(function ($q) {
                    $q->whereRaw("UPPER(TRIM(v.local)) LIKE '%MAYORISTA%'")
                      ->orWhereRaw("UPPER(TRIM(v.local)) LIKE '%MODELO%MUESTRA%'");
                });
            if ($buscar !== '') {
                $query->where(function ($q) use ($buscar) {
                    $q->where('v.codigo', 'ILIKE', '%' . $buscar . '%')
                      ->orWhere('v.descripcion', 'ILIKE', '%' . $buscar . '%')
                      ->orWhere('v.cliente', 'ILIKE', '%' . $buscar . '%');
                });
            }
            $ventasMay = $query->selectRaw("COALESCE(SUM(v.cantidad),0) as prendas_netas, COALESCE(SUM(v.p_venta),0) as importe_neto, COUNT(DISTINCT COALESCE(v.local,'') || '|' || COALESCE(v.comprobante,'')) as comprobantes")->first();
        }

        // Exporta el mismo universo filtrado que aparece en el resumen,
        // no solamente la página visible. Texto protegido contra fórmulas CSV.
        if ($request->query('exportar') === 'csv') {
            return response()->streamDownload(function () use ($filas) {
                $salida = fopen('php://output', 'w');
                fwrite($salida, "\xEF\xBB\xBF");
                fputcsv($salida, [
                    'OT', 'Código', 'Descripción', 'Canal',
                    'Asignado histórico', 'Remitido período', 'Recibido',
                    'En tránsito', 'Reserva fotos estimada',
                    'Destinado comercialización estimado',
                    'Recibido para comercialización estimado',
                    'Salida Central', 'Redistribuido', 'Documentos',
                    'Última remisión', 'Valor referencial Gs', 'Costo referencial Gs',
                    'Alerta vínculo',
                ], ';');

                $texto = function ($valor) {
                    $valor = (string) $valor;
                    return preg_match('/^[\s]*[=+\-@]/u', $valor) ? "'" . $valor : $valor;
                };
                foreach ($filas as $fila) {
                    fputcsv($salida, [
                        $texto($fila->nro_ot ?: 'SIN OT'), $texto($fila->codigo),
                        $texto($fila->descripcion), $texto($fila->canal),
                        $fila->asignado, $fila->remitido, $fila->recibido,
                        $fila->en_transito, $fila->sin_vinculo ? '' : $fila->muestra_estimada,
                        $fila->sin_vinculo ? '' : $fila->potencial_enviado,
                        $fila->sin_vinculo ? '' : $fila->potencial_venta,
                        $fila->salida_central, $fila->traslado, $fila->documentos,
                        $fila->ultima_remision ?: '', round($fila->valor_referencial, 2),
                        round($fila->costo_referencial, 2),
                        $fila->sin_vinculo ? 'REVISAR OT' : '',
                    ], ';');
                }
                fclose($salida);
            }, 'mayoristas_' . $desde . '_' . $hasta . '.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $pagina = max(1, (int) $request->query('page', 1));
        $porPagina = 35;
        $ordenes = new LengthAwarePaginator(
            $filas->slice(($pagina - 1) * $porPagina, $porPagina)->values(),
            $filas->count(),
            $porPagina,
            $pagina,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('ventas.mayoristas', compact(
            'desde', 'hasta', 'tipo', 'buscar', 'muestrasPorOt',
            'resumen', 'ordenes', 'remisiones', 'ventasMay',
            'sinVinculo', 'tablas'
        ));
    }

    private function filtrarOt($query, string $buscar, string $aliasOt)
    {
        if ($buscar === '') {
            return $query;
        }

        return $query->where(function ($q) use ($buscar, $aliasOt) {
            $q->whereRaw('CAST(' . $aliasOt . '.nro_ot AS TEXT) ILIKE ?', ['%' . $buscar . '%'])
              ->orWhere($aliasOt . '.codigo', 'ILIKE', '%' . $buscar . '%')
              ->orWhere($aliasOt . '.descripcion', 'ILIKE', '%' . $buscar . '%');
        });
    }
}
