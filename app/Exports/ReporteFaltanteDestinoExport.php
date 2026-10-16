<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReporteFaltanteDestinoExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize
{
    protected $reporte;

    /**
     * Asignaciones REALES de ot_logistica_detalle agrupadas por id_ot.
     * d.sucursal contiene los destinos: SL, Ayala, Modelo Muestra, etc.
     * El vínculo t permite identificar incidencias de trazabilidad, pero
     * un destino explícito en d no desaparece por un vínculo defectuoso.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $destinosPorOt;

    public function __construct(Collection $reporte)
    {
        $this->reporte = $reporte;
        $this->destinosPorOt = collect();

        $idsOt = $reporte->pluck('id_ot')
            ->filter()
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values()
            ->all();

        if (
            empty($idsOt)
            || !Schema::hasTable('ot_logistica_detalle')
            || !Schema::hasTable('ot_trazabilidad')
        ) {
            return;
        }

        /*
         * NO tomar un planificado global, un nombre de local de ENVIOS,
         * ni una clasificación manual. Se lee cada destino de la OT.
         *
         * Se suman sólo cantidades positivas cuyo destino está identificado.
         * Se incluyen expresamente AYALA y MODELO MUESTRA como sucursales
         * válidas; una asignación no necesita tener remisión emitida.
         *
         * El JOIN es sólo de auditoría del proceso y de la FK: si una fila
         * conserva id_ot + sucursal válidos, cuenta como destino asignado
         * incluso si la trazabilidad histórica está mal vinculada. Esas
         * incidencias se muestran en otra columna para revisión.
         */
        $this->destinosPorOt = DB::table('ot_logistica_detalle as d')
            ->leftJoin(
                'ot_trazabilidad as t',
                't.id_trazabilidad',
                '=',
                'd.id_trazabilidad'
            )
            ->whereIn('d.id_ot', $idsOt)
            ->where('d.cantidad', '>', 0)
            ->whereRaw("BTRIM(COALESCE(d.sucursal, '')) <> ''")
            ->whereRaw(
                "UPPER(BTRIM(COALESCE(d.sucursal, ''))) NOT IN (
                    'SIN DESTINO', 'SIN ASIGNAR', 'NO ASIGNADO',
                    'PENDIENTE', 'NINGUNO', 'S/D', '-', 'NULL'
                )"
            )
            ->groupBy('d.id_ot')
            ->select(
                'd.id_ot',
                DB::raw('SUM(d.cantidad) as total_asignado'),
                DB::raw(
                    "SUM(CASE WHEN UPPER(BTRIM(d.sucursal)) LIKE '%AYALA%'
                        THEN d.cantidad ELSE 0 END) as total_ayala"
                ),
                DB::raw(
                    "SUM(CASE WHEN UPPER(BTRIM(d.sucursal)) LIKE '%MODELO%'
                        THEN d.cantidad ELSE 0 END) as total_modelo"
                ),
                DB::raw(
                    "SUM(CASE WHEN t.id_trazabilidad IS NOT NULL
                        AND t.id_ot = d.id_ot
                        AND t.proceso = 'LOGISTICA - LOGISTICA Y DISTRIBUCION'
                        THEN d.cantidad ELSE 0 END) as plan_trazabilidad_valida"
                ),
                DB::raw(
                    "SUM(CASE WHEN t.id_trazabilidad IS NULL
                        OR t.id_ot <> d.id_ot
                        OR t.proceso IS NULL
                        OR t.proceso <> 'LOGISTICA - LOGISTICA Y DISTRIBUCION'
                        THEN d.cantidad ELSE 0 END) as asignado_revisar_traza"
                )
            )
            ->get()
            ->keyBy('id_ot');
    }

    public function collection()
    {
        return $this->reporte;
    }

    public function headings(): array
    {
        return [
            'OT',
            'Código',
            'Descripción',
            'OT original',
            'Ingreso Terminación',
            'Producto Terminado',
            'Plan con destino aplicable a PT',
            'Salida neta de OT',
            'Recibido',
            'Falta Terminación',
            'Sin destino real',
            'Plan Ayala',
            'Plan Modelo Muestra',
            'Sin destino KPI (comparación)',
            'Pendiente de salida total',
            'En tránsito',
            'Estado',
            'Acción requerida',
            'Plan total con sucursal registrada',
            'Plan con trazabilidad válida',
            'Plan con trazabilidad a revisar',
        ];
    }

    public function map($item): array
    {
        $pt = max(0, (int) $item->producto_terminado);
        $remitido = max(0, (int) $item->remitido_original);
        $destinos = $this->destinosPorOt->get((int) $item->id_ot);

        // Fuente: SUM(d.cantidad) POR OT y POR DESTINO, no por remisión.
        // AYALA y MODELO MUESTRA están incluidos en este total.
        $asignadoTotal = max(0, (int) ($destinos->total_asignado ?? 0));
        $asignadoPt = min($pt, $asignadoTotal);

        // Una salida real con remisión también acredita destino, aunque
        // todavía no se haya recuperado su fila de planificación.
        $destinoAcreditado = max($asignadoPt, min($pt, $remitido));
        $sinDestinoReal = max(0, $pt - $destinoAcreditado);

        $ayala = max(0, (int) ($destinos->total_ayala ?? 0));
        $modelo = max(0, (int) ($destinos->total_modelo ?? 0));
        $sinDestinoKpi = max(0, (int) $item->sin_destino);

        // Sólo el Excel se ajusta: la pantalla conserva sus estados,
        // aunque el estado operativo del archivo no debe pedir destino
        // cuando ya existe una sucursal explícitamente asignada.
        $estado = (string) $item->estado_conciliacion;
        $solicitud = (string) $item->solicitud;

        if ($sinDestinoReal > 0 && $estado === 'SIN DESTINO') {
            $solicitud = 'LOGISTICA: ASIGNAR DESTINO ' . $sinDestinoReal;
        } elseif ($sinDestinoReal === 0 && $estado === 'SIN DESTINO') {
            if ((int) $item->pendiente_remitir > 0) {
                $estado = 'PENDIENTE REMITIR';
                $solicitud = 'LOGISTICA: REMITIR '
                    . (int) $item->pendiente_remitir;
            } elseif ((int) $item->en_transito > 0) {
                $estado = 'EN TRANSITO';
                $solicitud = 'LOCAL: CONFIRMAR '
                    . (int) $item->en_transito;
            } else {
                $estado = 'CONFIRMADO / REVISAR';
                $solicitud = 'SIN FALTANTE REAL DE DESTINO';
            }
        }

        return [
            $item->nro_ot,
            $item->codigo,
            $item->descripcion,
            (int) $item->objetivo,
            (int) $item->ingreso_terminacion,
            $pt,
            $asignadoPt,
            $remitido,
            (int) $item->recibido_original,
            (int) $item->falta_terminacion,
            $sinDestinoReal,
            $ayala,
            $modelo,
            $sinDestinoKpi,
            (int) $item->pendiente_remitir,
            (int) $item->en_transito,
            $estado,
            $solicitud,
            $asignadoTotal,
            (int) ($destinos->plan_trazabilidad_valida ?? 0),
            (int) ($destinos->asignado_revisar_traza ?? 0),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        $sheet->getStyle('A1:U1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['ARGB' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['ARGB' => 'FF1F4E78'],
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
        ]);

        $ultimaFila = $sheet->getHighestRow();
        if ($ultimaFila < 2) {
            return [];
        }

        $sheet->getStyle('A2:U' . $ultimaFila)
            ->getAlignment()->setVertical('center');

        $sheet->getStyle('D2:P' . $ultimaFila)
            ->getAlignment()->setHorizontal('center');

        foreach ([
            'J' => 'FFB91C1C', // Falta Terminación
            'K' => 'FFC2410C', // Único faltante REAL de destino
            'L' => 'FF0E7490', // Destino válido: Ayala
            'M' => 'FF0E7490', // Destino válido: Modelo Muestra
            'N' => 'FF64748B', // KPI previo para comparación
            'O' => 'FF0E7490', // Pendiente de salida
            'P' => 'FF1D4ED8', // En tránsito
            'U' => 'FFD97706', // Incidencias de trazabilidad
        ] as $columna => $color) {
            $sheet->getStyle($columna . '2:' . $columna . $ultimaFila)
                ->getFont()->setBold(true)->getColor()->setARGB($color);
        }

        $sheet->getColumnDimension('R')->setAutoSize(false);
        $sheet->getColumnDimension('R')->setWidth(36);
        $sheet->getStyle('R2:R' . $ultimaFila)
            ->getAlignment()->setWrapText(true);

        return [];
    }
}
