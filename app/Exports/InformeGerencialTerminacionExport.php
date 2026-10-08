<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InformeGerencialTerminacionExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $pendientes;

    public function __construct(Collection $pendientes)
    {
        $this->pendientes = $pendientes;
    }

    public function collection()
    {
        return $this->pendientes;
    }

    public function headings(): array
    {
        return [
            'Prioridad',
            'Pedido',
            'Fecha pedido',
            'OT',
            'Saldo pendiente',
            'Descripción',
            'Etapa actual',
            'Fecha proceso actual',
            'Días desde pedido',
        ];
    }

    public function map($fila): array
    {
        $fechaProceso = null;

        if ($fila->etapa_gerencial === 'LOGISTICA' && $fila->fecha_logistica) {
            $fechaProceso = date('d/m/Y', strtotime($fila->fecha_logistica));
        } elseif ($fila->etapa_gerencial === 'TERMINACION' && $fila->fecha_terminacion) {
            $fechaProceso = date('d/m/Y', strtotime($fila->fecha_terminacion));
        }

        $etapa = $fila->etapa_gerencial === 'LOGISTICA'
            ? 'LOGÍSTICA'
            : ($fila->etapa_gerencial === 'TERMINACION'
                ? 'TERMINACIÓN'
                : 'SIN INICIAR');

        return [
            $fila->urgente ? 'URGENTE' : 'NORMAL',
            $fila->nro_pedido,
            $fila->fecha_pedido ? date('d/m/Y', strtotime($fila->fecha_pedido)) : '-',
            $fila->nro_ot,
            (int) $fila->saldo_pendiente,
            $fila->descripcion ?: '-',
            $etapa,
            $fechaProceso ?: '-',
            $fila->dias_etapa !== null ? (int) $fila->dias_etapa : null,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        $sheet->getStyle('A1:I1')->applyFromArray([
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
            $sheet->getStyle('A2:I' . $ultimaFila)->getAlignment()->setVertical('center');
            $sheet->getStyle('A2:A' . $ultimaFila)->getAlignment()->setHorizontal('center');
            $sheet->getStyle('B2:E' . $ultimaFila)->getAlignment()->setHorizontal('center');
            $sheet->getStyle('G2:I' . $ultimaFila)->getAlignment()->setHorizontal('center');

            for ($fila = 2; $fila <= $ultimaFila; $fila++) {
                if (strtoupper((string) $sheet->getCell('A' . $fila)->getValue()) === 'URGENTE') {
                    $sheet->getStyle('A' . $fila . ':I' . $fila)->applyFromArray([
                        'fill' => [
                            'fillType' => 'solid',
                            'startColor' => ['ARGB' => 'FFFFE5E5'],
                        ],
                    ]);
                    $sheet->getStyle('A' . $fila)->getFont()->setBold(true)->getColor()->setARGB('FFB91C1C');
                }
            }
        }

        return [];
    }
}
