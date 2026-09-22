<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReporteSemanalLogisticaExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $detalles;
    protected $totales;

    public function __construct(Collection $detalles, array $totales)
    {
        $this->detalles = $detalles;
        $this->totales = $totales;
    }

    public function collection()
    {
        $filas = $this->detalles->map(function ($item) {
            return [
                $item->fecha_logistica
                    ? date('d/m/Y', strtotime($item->fecha_logistica))
                    : '',
                $item->nro_ot ?? '',
                $item->codigo ?? '',
                $item->descripcion ?? '',
                (int) ($item->cantidad_pt ?? 0),
                (int) ($item->distribucion ?? 0),
                (int) ($item->plan_locales ?? 0),
                (int) ($item->plan_ayala ?? 0),
                (int) ($item->plan_modelo_muestra ?? 0),
                (int) ($item->locales_reales ?? 0),
                (int) ($item->mayorista_real ?? 0),
                (int) ($item->remitido ?? 0),
                (int) ($item->pendiente_remitir ?? 0),
                (int) ($item->recibido ?? 0),
                (int) ($item->en_transito ?? 0),
                (int) ($item->diferencia_pt_distribucion ?? 0),
                $item->estado ?? '',
            ];
        });

        $filas->push([
            'TOTAL GENERAL',
            '',
            '',
            '',
            '',
            $this->totales['distribucion'] ?? 0,
            $this->totales['plan_locales'] ?? 0,
            $this->totales['plan_ayala'] ?? 0,
            $this->totales['plan_modelo_muestra'] ?? 0,
            $this->totales['locales_reales'] ?? 0,
            $this->totales['mayorista_real'] ?? 0,
            $this->totales['remitido'] ?? 0,
            $this->totales['pendiente_remitir'] ?? 0,
            $this->totales['recibido'] ?? 0,
            $this->totales['en_transito'] ?? 0,
            '',
            '',
        ]);

        return $filas;
    }

    public function headings(): array
    {
        return [
            'Fecha Logística',
            'N° OT',
            'Código',
            'Artículo',
            'Producto Terminado',
            'Distribución',
            'Plan Locales',
            'Plan Ayala',
            'Plan Modelo/Muestra',
            'Envío Real Locales',
            'Mayorista / Matriz Real',
            'Remitido Total',
            'Pendiente Remitir',
            'Recibido',
            'En Tránsito',
            'Dif. PT / Distribución',
            'Estado',
        ];
    }
}
