<?php

namespace App\Exports;

use Illuminate\Support\Collection;
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
            'Objetivo OT',
            'Producto Terminado',
            'Plan logístico',
            'Remitido real',
            'Recibido',
            'Falta Terminación',
            'Sin destino',
            'Pendiente remitir',
            'En tránsito',
            'Estado',
            'Acción requerida',
        ];
    }

    public function map($item): array
    {
        return [
            $item->nro_ot,
            $item->codigo,
            $item->descripcion,
            (int) $item->objetivo,
            (int) $item->producto_terminado,
            (int) $item->planificado,
            (int) $item->remitido_original,
            (int) $item->recibido_original,
            (int) $item->falta_terminacion,
            (int) $item->sin_destino,
            (int) $item->pendiente_remitir,
            (int) $item->en_transito,
            $item->estado_conciliacion,
            $item->solicitud,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        $sheet->getStyle('A1:N1')->applyFromArray([
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

        if ($ultimaFila >= 2) {
            $sheet->getStyle('A2:N' . $ultimaFila)
                ->getAlignment()
                ->setVertical('center');

            $sheet->getStyle('A2:A' . $ultimaFila)
                ->getAlignment()
                ->setHorizontal('center');

            $sheet->getStyle('D2:M' . $ultimaFila)
                ->getAlignment()
                ->setHorizontal('center');

            for ($fila = 2; $fila <= $ultimaFila; $fila++) {
                $sheet->getStyle('I' . $fila)
                    ->getFont()
                    ->setBold(true)
                    ->getColor()
                    ->setARGB('FFB91C1C');

                $sheet->getStyle('J' . $fila)
                    ->getFont()
                    ->setBold(true)
                    ->getColor()
                    ->setARGB('FFC2410C');

                $sheet->getStyle('K' . $fila)
                    ->getFont()
                    ->setBold(true)
                    ->getColor()
                    ->setARGB('FF0E7490');

                $sheet->getStyle('L' . $fila)
                    ->getFont()
                    ->setBold(true)
                    ->getColor()
                    ->setARGB('FF1D4ED8');

                $sheet->getStyle('N' . $fila)
                    ->getFont()
                    ->setBold(true);
            }
        }

        return [];
    }
}
