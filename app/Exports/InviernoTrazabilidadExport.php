<?php

namespace App\Exports;

use App\Support\InviernoValorizacion as Valorizacion;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class InviernoTrazabilidadExport extends DefaultValueBinder implements FromCollection, WithHeadings, ShouldAutoSize,
    WithCustomValueBinder, WithStrictNullComparison
{
    private Collection $documentos;

    public function __construct(Collection $documentos)
    {
        $this->documentos = $documentos;
    }

    public function headings(): array
    {
        return ['LOCAL', 'SERIE', 'NRO', 'ESTADO', 'PRENDAS EN REMISIÓN', 'CONFIRMADO CENTRAL',
            'PENDIENTE CENTRAL', 'REMITIDO ASOCIADO', 'RECIBIDO LOCALES', 'EN TRÁNSITO LOCALES',
            'SALDO DOCUMENTAL CENTRAL', 'UNIDADES CON VÍNCULO POR REVISAR', 'INCIDENCIAS',
            ...InviernoValorizacionColumnas::encabezados()];
    }

    public function collection(): Collection
    {
        return $this->documentos->map(function ($d) {
            $valorizacion = new Valorizacion();
            $valorizacion->sumarRemision($d);
            $totales = $valorizacion->totales();
            if ($d->saldo_central === null) {
                $totales['disponible']['costo'] = $totales['disponible']['venta'] = null;
            }
            return [$d->local, $d->serie, $d->numero_remision, $d->estado_texto,
                $d->entradas->isEmpty() ? null : $d->total, $d->confirmado_central, $d->pendiente_central,
                $d->remitido, $d->recibido_local, $d->transito_local, $d->saldo_central,
                $d->por_revisar, implode(' | ', $d->incidencias),
                ...($d->entradas->isEmpty() ? [] : InviernoValorizacionColumnas::totales($totales))];
        });
    }

    public function bindValue(Cell $cell, $value): bool
    {
        // Evitar fórmulas en nombres importados y preservar números/series como texto.
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }
}
