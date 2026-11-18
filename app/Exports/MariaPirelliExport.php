<?php

namespace App\Exports;

use DateTimeImmutable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MariaPirelliExport extends DefaultValueBinder implements
    FromCollection, WithMapping, WithHeadings, WithColumnFormatting, WithCustomValueBinder,
    WithStrictNullComparison, WithStyles, ShouldAutoSize
{
    public function __construct(
        private Collection $articulos,
        private array $movimientos,
        private bool $conRemisiones,
        private bool $conVentas
    ) {
    }

    public function collection(): Collection
    {
        return $this->articulos;
    }

    public function headings(): array
    {
        return [
            'Código', 'Descripción', 'Línea', 'Último envío (remisión)',
            'Costo (Gs)', 'Precio de venta (Gs)', 'Enviadas', 'Vendidas netas', 'Venta neta (Gs)',
        ];
    }

    public function map($articulo): array
    {
        $movimiento = $this->movimientos[$articulo->id];
        $fecha = $movimiento['ultimo_envio'] ?? null;

        return [
            (string) $articulo->codigo,
            $articulo->descripcion,
            $articulo->linea,
            $fecha ? Date::dateTimeToExcel(new DateTimeImmutable($fecha)) : null,
            (float) $articulo->costo_unitario,
            (float) $articulo->precio_venta,
            $this->conRemisiones ? (int) $movimiento['enviadas'] : null,
            $this->conVentas ? (int) $movimiento['vendidas'] : null,
            $this->conVentas ? round($movimiento['venta_neta'], 2) : null,
        ];
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        return [
            'D' => 'dd/mm/yyyy', 'E' => '#,##0.00', 'F' => '#,##0.00',
            'G' => '#,##0', 'H' => '#,##0', 'I' => '#,##0.00',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:I' . $sheet->getHighestDataRow());

        return [1 => ['font' => ['bold' => true]]];
    }
}
