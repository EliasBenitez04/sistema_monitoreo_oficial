<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class RedistribucionExcelPropuestaExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    private $movimientos;

    public function __construct(array $movimientos)
    {
        $this->movimientos = $movimientos;
    }

    public function collection()
    {
        return collect($this->movimientos)->map(function ($movimiento) {
            return [
                $movimiento['base'] ?? '',
                $movimiento['codigo'] ?? '',
                $movimiento['talle'] ?? '',
                $movimiento['descripcion'] ?? '',
                $movimiento['origen'] ?? '',
                $movimiento['destino'] ?? '',
                (int) ($movimiento['stock_origen'] ?? 0),
                (int) ($movimiento['stock_destino'] ?? 0),
                1,
                (int) ($movimiento['cantidad'] ?? 0),
                (int) ($movimiento['origen_final'] ?? 0),
                (int) ($movimiento['destino_final'] ?? 0),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'CODIGO BASE',
            'CODIGO COMPLETO',
            'TALLE',
            'DESCRIPCION',
            'SUCURSAL ORIGEN',
            'SUCURSAL DESTINO',
            'STOCK ORIGEN INICIAL',
            'STOCK DESTINO INICIAL',
            'RESERVA ORIGEN',
            'CANTIDAD A ENVIAR',
            'ORIGEN DESPUES',
            'DESTINO DESPUES',
        ];
    }
}
