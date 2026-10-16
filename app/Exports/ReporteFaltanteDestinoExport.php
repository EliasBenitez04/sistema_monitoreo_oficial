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
     * Clasificaciones documentadas por OT si la tabla de auditoría
     * ya existe. Nunca se descuentan 12/13 automáticamente del total.
     * No depende del controlador ni modifica la interfaz.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $clasificaciones;

    public function __construct(Collection $reporte)
    {
        $this->reporte = $reporte;
        $this->clasificaciones = collect();

        if (!Schema::hasTable('ot_sin_destino_clasificaciones')) {
            return;
        }

        $idsOt = $reporte->pluck('id_ot')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!empty($idsOt)) {
            $this->clasificaciones = DB::table(
                'ot_sin_destino_clasificaciones'
            )->whereIn('id_ot', $idsOt)->get()->keyBy('id_ot');
        }
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
        $bruto = max(0, (int) ($item->sin_destino ?? 0));
        $registro = $this->clasificaciones->get((int) $item->id_ot);
        $ayala = max(0, (int) ($registro->cantidad_ayala ?? 0));
        $modelo = max(0, (int) ($registro->cantidad_modelo ?? 0));

        // Una clasificación antigua nunca puede descontar más que el saldo
        // actual de su OT después de una nueva importación.
        $invalida = $ayala + $modelo > $bruto;
        if ($invalida) {
            $ayala = 0;
            $modelo = 0;
        }

        $sinDestinoReal = $bruto - $ayala - $modelo;
        $referencia = $invalida
            ? 'CLASIFICACIÓN SUPERIOR AL SALDO ACTUAL - REVISAR'
            : (string) ($registro->referencia ?? '');

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
            $sinDestinoReal,
            $ayala,
            $modelo,
            $bruto,
            (int) $item->pendiente_remitir,
            (int) $item->en_transito,
            $item->estado_conciliacion,
            $item->solicitud,
            $referencia,
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
