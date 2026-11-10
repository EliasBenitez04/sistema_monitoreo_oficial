<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class InviernoDisponibilidadHoja extends DefaultValueBinder implements FromCollection, WithHeadings,
    WithTitle, ShouldAutoSize, WithCustomValueBinder, WithStrictNullComparison
{
    private string $titulo;
    private array $encabezados;
    private Collection $filas;

    public function __construct(string $titulo, array $encabezados, Collection $filas)
    {
        $this->titulo = $titulo;
        $this->encabezados = $encabezados;
        $this->filas = $filas;
    }

    public function title(): string { return $this->titulo; }
    public function headings(): array { return $this->encabezados; }
    public function collection(): Collection { return $this->filas; }

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }
}
