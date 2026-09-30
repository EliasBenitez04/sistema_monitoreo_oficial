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
            'Cantidad orden',
            'Destino asignado',
            'Falta completar destino',
            'Producto Terminado',
            'Último PT',
            'Última asignación logística',
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
            (int) $item->destino_asignado,
            (int) $item->faltante_destino,
            (int) $item->producto_terminado,
            $item->ultima_fecha_pt
                ? date('d/m/Y', strtotime($item->ultima_fecha_pt))
                : '-',
            $item->ultima_fecha_logistica
                ? date('d/m/Y', strtotime($item->ultima_fecha_logistica))
                : '-',
            $item->solicitud,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['ARGB' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['ARGB' => 'FFB45309'],
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
        ]);

        $ultimaFila = $sheet->getHighestRow();

        if ($ultimaFila >= 2) {
            $sheet->getStyle('A2:J' . $ultimaFila)
                ->getAlignment()
                ->setVertical('center');

            $sheet->getStyle('A2:A' . $ultimaFila)
                ->getAlignment()
                ->setHorizontal('center');

            $sheet->getStyle('D2:I' . $ultimaFila)
                ->getAlignment()
                ->setHorizontal('center');

            for ($fila = 2; $fila <= $ultimaFila; $fila++) {
                $sheet->getStyle('F' . $fila)
                    ->getFont()
                    ->setBold(true)
                    ->getColor()
                    ->setARGB('FFB91C1C');

                $sheet->getStyle('J' . $fila)
                    ->getFont()
                    ->setBold(true);
            }
        }

        return [];
    }
}
