<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReporteCancelacionTerminacionExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $reporte;

    public function __construct(Collection $reporte)
    {
        $this->reporte = $reporte;
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
            'Cantidad orden',
            'Producto Terminado acumulado',
            'Falta cancelar',
            'Plan logística',
            'Remitido',
            'Pendiente remitir',
            'Último PT',
            'Solicitud',
        ];
    }

    public function map($item): array
    {
        return [
            $item->nro_ot,
            $item->codigo,
            $item->descripcion,
            (int) $item->cantidad_orden,
            (int) $item->cantidad_pt_efectiva,
            (int) $item->pendiente_cancelar,
            (int) $item->plan_logistica,
            (int) $item->remitido,
            (int) $item->pendiente_remitir,
            $item->ultima_fecha_pt ? date('d/m/Y', strtotime($item->ultima_fecha_pt)) : '-',
            $item->accion,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['ARGB' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['ARGB' => 'FF9C2F2F'],
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
        ]);

        $ultimaFila = $sheet->getHighestRow();

        if ($ultimaFila >= 2) {
            $sheet->getStyle('A2:K' . $ultimaFila)->getAlignment()->setVertical('center');
            $sheet->getStyle('A2:A' . $ultimaFila)->getAlignment()->setHorizontal('center');
            $sheet->getStyle('D2:J' . $ultimaFila)->getAlignment()->setHorizontal('center');

            for ($fila = 2; $fila <= $ultimaFila; $fila++) {
                $sheet->getStyle('F' . $fila)->getFont()->setBold(true)->getColor()->setARGB('FFB91C1C');
                $sheet->getStyle('K' . $fila)->getFont()->setBold(true);
            }
        }

        return [];
    }
}
