<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InviernoCodigosSinImagenExport implements WithMultipleSheets
{
    public function __construct(private Collection $claves)
    {
    }

    public function sheets(): array
    {
        $bases = $this->claves->filter()->unique()->sort(SORT_STRING)->values()
            ->map(fn (string $codigo) => [$codigo]);
        $hojas = [new InviernoDisponibilidadHoja('Códigos base', ['CÓDIGO BASE PARA IMAGEN'], $bases)];

        $sinClave = $this->claves->filter(fn ($clave) => $clave === null)->keys()->sort(SORT_STRING)->values();
        if ($sinClave->isNotEmpty()) {
            $hojas[] = new InviernoDisponibilidadHoja('Revisar maestro', ['CÓDIGO COMPLETO'],
                $sinClave->map(fn ($codigo) => [(string) $codigo]));
        }

        return $hojas;
    }
}
