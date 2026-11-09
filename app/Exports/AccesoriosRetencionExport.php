<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AccesoriosRetencionExport extends DefaultValueBinder implements
    FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize,
    WithColumnFormatting, WithColumnWidths, WithCustomValueBinder,
    WithStrictNullComparison, WithTitle
{
    private Collection $filas;
    private string $corte;

    public function __construct(Collection $filas, string $corte)
    {
        $this->filas = $filas;
        $this->corte = $corte;
    }

    public function collection()
    {
        return $this->filas;
    }

    public function title(): string
    {
        return 'Menor movimiento';
    }

    public function headings(): array
    {
        return [
            'Sucursal', 'Código', 'Descripción', 'Cantidad disponible',
            'Llegada al local', 'Última recepción', 'Última factura',
            'Fecha última venta', 'Días en local', 'Días retenidos',
            'Conteo desde', 'Fecha de corte',
        ];
    }

    public function map($fila): array
    {
        return [
            (string) $fila->sucursal,
            (string) $fila->codigo,
            (string) $fila->descripcion,
            (int) $fila->cantidad,
            $this->fecha($fila->fecha_llegada),
            $this->fecha($fila->ultima_recepcion),
            $fila->ultima_venta ? ($fila->ultima_factura ?: 'Sin comprobante informado') : 'Sin ventas registradas',
            $this->fecha($fila->ultima_venta),
            $fila->dias_local,
            $fila->dias_retenidos,
            $fila->dias_retenidos === null ? 'Sin datos' : ($fila->desde_llegada ? 'Llegada al local' : 'Última venta'),
            $this->fecha($this->corte),
        ];
    }

    private function fecha(?string $valor): ?float
    {
        return $valor ? Date::dateTimeToExcel(Carbon::parse($valor)->startOfDay()) : null;
    }

    public function bindValue(Cell $cell, $value)
    {
        // Conservar códigos con ceros iniciales y texto literal de catálogo.
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        return [
            'B' => '@', 'D' => '0', 'E' => 'dd/mm/yyyy', 'F' => 'dd/mm/yyyy',
            'H' => 'dd/mm/yyyy', 'I' => '0', 'J' => '0', 'L' => 'dd/mm/yyyy',
        ];
    }

    public function columnWidths(): array
    {
        return ['C' => 45];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:L' . max(1, $sheet->getHighestRow()));
        $sheet->getStyle('C1:C' . $sheet->getHighestRow())->getAlignment()->setWrapText(true);
        $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()->setVertical('center');
        $sheet->getRowDimension(1)->setRowHeight(28);

        return [1 => [
            'font' => ['bold' => true, 'color' => ['ARGB' => 'FFFFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['ARGB' => 'FF153D73']],
        ]];
    }
}
