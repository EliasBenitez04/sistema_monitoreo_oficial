<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Vista exportable del mismo KPI Pendiente remitir del Control Terminación.
 * No consulta movimientos ni aplica fórmulas de conciliación adicionales.
 */
class PendienteRemitirTerminacionExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnFormatting
{
    private Collection $ots;
    private string $fechaDesde;
    private string $fechaHasta;

    public function __construct(Collection $ots, string $fechaDesde, string $fechaHasta)
    {
        $this->ots = $ots;
        $this->fechaDesde = $fechaDesde;
        $this->fechaHasta = $fechaHasta;
    }

    public function collection()
    {
        return $this->ots;
    }

    public function headings(): array
    {
        return [
            ['PENDIENTES DE REMITIR | CONTROL TERMINACIÓN'],
            ['Período de Producto Terminado: ' . $this->fechaDesde . ' al ' . $this->fechaHasta
                . ' | Exportado: ' . now()->format('d/m/Y H:i')],
            ['OT pendientes: ' . $this->ots->count()
                . ' | Prendas pendientes: ' . (int) $this->ots->sum('pendiente_remitir_plan')],
            ['Las OT se ordenan por antigüedad desde su primer Producto Terminado. '
                . 'Remitido = documento emitido/importado; recibido = recepción confirmada. '
                . 'Sin destino es parte del pendiente, no debe sumarse nuevamente.'],
            [
                'Prioridad',
                'OT',
                'Código',
                'Descripción',
                'Primer PT',
                'Días desde PT',
                'Cantidad OT',
                'Producto terminado',
                'Plan asignado',
                'Remitido real',
                'Recibido',
                'En tránsito',
                'Sin destino',
                'Pendiente remitir',
                'Última remisión',
                'Acción requerida',
            ],
        ];
    }

    public function map($ot): array
    {
        $dias = $ot->dias_desde_pt !== null ? (int) $ot->dias_desde_pt : null;
        $sinDestino = (int) ($ot->sin_destino ?? 0);

        if ($dias === null) {
            $prioridad = 'SIN FECHA';
        } elseif ($dias >= 30) {
            $prioridad = 'URGENTE';
        } elseif ($dias >= 15) {
            $prioridad = 'ALTA';
        } else {
            $prioridad = 'NORMAL';
        }

        return [
            $prioridad,
            (int) $ot->nro_ot,
            (string) $ot->codigo,
            (string) $ot->descripcion,
            $ot->primera_fecha_pt_historica
                ? date('d/m/Y', strtotime($ot->primera_fecha_pt_historica))
                : '',
            $dias,
            (int) $ot->cantidad_orden,
            (int) $ot->cantidad_terminada,
            (int) $ot->cantidad_logistica,
            (int) $ot->remitido_efectivo,
            (int) $ot->recibido_efectivo,
            (int) $ot->en_transito,
            $sinDestino,
            (int) $ot->pendiente_remitir_plan,
            $ot->ultima_remision ? date('d/m/Y', strtotime($ot->ultima_remision)) : '',
            $sinDestino > 0 ? 'COMPLETAR DESTINO Y REMITIR' : 'EMITIR REMISIÓN',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_TEXT,
            'O' => NumberFormat::FORMAT_TEXT,
            'F' => NumberFormat::FORMAT_NUMBER,
            'G' => NumberFormat::FORMAT_NUMBER,
            'H' => NumberFormat::FORMAT_NUMBER,
            'I' => NumberFormat::FORMAT_NUMBER,
            'J' => NumberFormat::FORMAT_NUMBER,
            'K' => NumberFormat::FORMAT_NUMBER,
            'L' => NumberFormat::FORMAT_NUMBER,
            'M' => NumberFormat::FORMAT_NUMBER,
            'N' => NumberFormat::FORMAT_NUMBER,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        foreach ([1, 2, 3, 4] as $fila) {
            $sheet->mergeCells('A' . $fila . ':P' . $fila);
        }

        $sheet->getStyle('A1:P1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['ARGB' => 'FFFFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['ARGB' => 'FF17365D']],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getStyle('A2:P4')->getFont()->setSize(10);
        $sheet->getStyle('A3:P3')->getFont()->setBold(true);
        $sheet->getStyle('A4:P4')->getFont()->getColor()->setARGB('FF64748B');
        $sheet->getStyle('A5:P5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['ARGB' => 'FFFFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['ARGB' => 'FF1F4E78']],
            'alignment' => ['wrapText' => true, 'vertical' => 'center'],
        ]);
        $sheet->getRowDimension(5)->setRowHeight(32);
        $sheet->freezePane('D6');

        $totalOts = $this->ots->count();
        $ultimaFila = 5 + $totalOts;

        $sheet->setAutoFilter('A5:P' . max(5, $ultimaFila));
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(13);
        $sheet->getColumnDimension('C')->setWidth(19);
        $sheet->getColumnDimension('D')->setWidth(47);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(19);
        $sheet->getColumnDimension('I')->setWidth(17);
        $sheet->getColumnDimension('J')->setWidth(17);
        $sheet->getColumnDimension('K')->setWidth(13);
        $sheet->getColumnDimension('L')->setWidth(15);
        $sheet->getColumnDimension('M')->setWidth(15);
        $sheet->getColumnDimension('N')->setWidth(19);
        $sheet->getColumnDimension('O')->setWidth(17);
        $sheet->getColumnDimension('P')->setWidth(33);

        if ($totalOts > 0) {
            $sheet->getStyle('A6:P' . $ultimaFila)->getAlignment()->setVertical('center');
            $sheet->getStyle('F6:N' . $ultimaFila)
                ->getAlignment()->setHorizontal('right');
            $sheet->getStyle('N6:N' . $ultimaFila)
                ->getFont()->setBold(true)->getColor()->setARGB('FFB91C1C');

            // El orden de las filas es exactamente el del modal del KPI.
            foreach ($this->ots->values() as $i => $ot) {
                $fila = $i + 6;
                $dias = $ot->dias_desde_pt;
                if ($dias !== null && (int) $dias >= 30) {
                    $color = 'FFFFE4E6';
                } elseif ($dias !== null && (int) $dias >= 15) {
                    $color = 'FFFFF4D6';
                } else {
                    continue;
                }
                $sheet->getStyle('A' . $fila . ':P' . $fila)->applyFromArray([
                    'fill' => ['fillType' => 'solid', 'startColor' => ['ARGB' => $color]],
                ]);
            }
        }

        $filaTotal = $ultimaFila + 1;
        $sheet->mergeCells('A' . $filaTotal . ':M' . $filaTotal);
        $sheet->setCellValue('A' . $filaTotal, 'TOTAL | ' . $totalOts . ' OT pendientes');
        $sheet->setCellValue('N' . $filaTotal, (int) $this->ots->sum('pendiente_remitir_plan'));
        $sheet->getStyle('A' . $filaTotal . ':P' . $filaTotal)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['ARGB' => 'FFFFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['ARGB' => 'FF17365D']],
        ]);

        return [];
    }
}
