<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InviernoDisponibilidadExport implements WithMultipleSheets
{
    private array $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function sheets(): array
    {
        return [
            new InviernoDisponibilidadHoja('Resumen por grupo', [
                'GRUPO', 'REMISIONES', 'CÓDIGOS', 'PRENDAS DOCUMENTADAS', 'RECIBIDO CENTRAL',
                'PENDIENTE CENTRAL', 'ENVIADO ASOCIADO', 'RECIBIDO LOCALES', 'EN TRÁNSITO LOCALES',
                'DISPONIBLE CENTRAL', 'SALDO CONFIRMADO POR REVISAR', 'LÍNEAS CON DIFERENCIAS',
                ...InviernoValorizacionColumnas::encabezados(),
            ], $this->datos['grupos']->map(fn ($g) => [$g->grupo, $g->documentos, $g->codigos, $g->prendas,
                $g->central, $g->pendiente, $g->remitido, $g->recibido_local, $g->transito_local,
                $g->disponible, $g->por_revisar, $g->lineas_revision, ...InviernoValorizacionColumnas::totales($g->valorizacion)])),
            new InviernoDisponibilidadHoja('Detalle de remisiones', [
                'GRUPO', 'LOCAL DE ORIGEN', 'SERIE', 'NRO', 'CÓDIGO COMPLETO', 'DESCRIPCIÓN',
                'FECHA REMISIÓN', 'FECHA RECEPCIÓN CENTRAL', 'PRENDAS DOCUMENTADAS', 'RECIBIDO CENTRAL',
                'PENDIENTE CENTRAL', 'ENVIADO ASOCIADO', 'RECIBIDO LOCALES', 'EN TRÁNSITO LOCALES',
                'DISPONIBLE CENTRAL', 'SALDO CONFIRMADO POR REVISAR', 'INCIDENCIAS',
                ...InviernoValorizacionColumnas::encabezados(unitarios: true),
            ], $this->datos['lineas']->map(fn ($l) => [$l->grupo, $l->local, $l->serie, $l->numero_remision,
                $l->codigo, $l->descripcion, $l->fecha_remision, $l->fecha_recepcion, $l->prendas, $l->central,
                $l->pendiente, $l->remitido, $l->recibido_local, $l->transito_local, $l->disponible,
                $l->por_revisar, implode(' | ', $l->incidencias), ...InviernoValorizacionColumnas::linea($l)])),
        ];
    }
}
