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
            'OT original',
            'Ingreso Terminación',
            'Producto Terminado',
            'Plan aplicable a PT',
            'Salida neta de OT',
            'Recibido',
            'Falta Terminación',
            'Sin destino real',
            'Ayala clasificado',
            'Modelo Muestra clasificado',
            'Sin destino calculado (auditoría)',
            'Pendiente de salida total',
            'En tránsito',
            'Estado',
            'Acción requerida',
            'Referencia clasificación Ayala / Modelo',
        ];
    }

    public function map($item): array
    {
        return [
            $item->nro_ot,
            $item->codigo,
            $item->descripcion,
            (int) $item->objetivo,
            (int) $item->ingreso_terminacion,
            (int) $item->producto_terminado,
            (int) $item->plan_detallado,
            (int) $item->remitido_original,
            (int) $item->recibido_original,
            (int) $item->falta_terminacion,
            (int) ($item->sin_destino_real ?? $item->sin_destino),
            (int) ($item->sin_destino_ayala ?? 0),
            (int) ($item->sin_destino_modelo ?? 0),
            (int) $item->sin_destino,
            (int) $item->pendiente_remitir,
            (int) $item->en_transito,
            $item->estado_conciliacion,
            $item->solicitud,
            $item->clasificacion_referencia ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        $sheet->getStyle('A1:S1')->applyFromArray([
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

        $sheet->getStyle('A2:S' . $ultimaFila)
            ->getAlignment()->setVertical('center');
        $sheet->getStyle('A2:A' . $ultimaFila)
            ->getAlignment()->setHorizontal('center');
        $sheet->getStyle('D2:P' . $ultimaFila)
            ->getAlignment()->setHorizontal('center');

        foreach ([
            'J' => 'FFB91C1C', // Falta de producción
            'K' => 'FFC2410C', // Sin destino real
            'L' => 'FF0E7490', // Identificado Ayala
            'M' => 'FF0E7490', // Identificado Modelo Muestra
            'N' => 'FF64748B', // Sin destino bruto, sólo auditoría
            'O' => 'FF0E7490', // Pendiente de salida total
            'P' => 'FF1D4ED8', // En tránsito
        ] as $columna => $color) {
            $sheet->getStyle($columna . '2:' . $columna . $ultimaFila)
                ->getFont()->setBold(true)->getColor()->setARGB($color);
        }

        $sheet->getStyle('R2:R' . $ultimaFila)
            ->getFont()->setBold(true);

        $sheet->getColumnDimension('S')->setAutoSize(false);
        $sheet->getColumnDimension('S')->setWidth(45);
        $sheet->getStyle('S2:S' . $ultimaFila)
            ->getAlignment()->setWrapText(true);

        return [];
    }
}
