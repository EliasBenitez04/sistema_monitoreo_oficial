<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InviernoArticulosExport implements WithMultipleSheets
{
    private array $datos;
    private Collection $articulos;

    public function __construct(array $datos, Collection $articulos)
    {
        $this->datos = $datos;
        $this->articulos = $articulos;
    }

    public function sheets(): array
    {
        return [
            new InviernoDisponibilidadHoja('Prendas por remisión', [
                'COD. LOCAL ORIGEN', 'LOCAL ORIGEN', 'SERIE', 'NRO REMISIÓN', 'REMISIÓN COMPLETA',
                'COD. DESTINO', 'DESTINO', 'CÓDIGO COMPLETO', 'DESCRIPCIÓN', 'GRUPO',
                'FECHA REMISIÓN', 'FECHA RECEPCIÓN CENTRAL', 'ESTADO RECEPCIÓN', 'CANTIDAD EN REMISIÓN',
                'RECIBIDO CENTRAL', 'PENDIENTE CENTRAL', 'ENVIADO ASOCIADO', 'RECIBIDO LOCALES',
                'EN TRÁNSITO LOCALES', 'DISPONIBLE CENTRAL', 'SALDO POR REVISAR', 'ESTADO SEGUIMIENTO', 'INCIDENCIAS',
            ], $this->datos['lineas']->sortBy('codigo')->values()->map(fn ($l) => [
                $l->local_codigo, $l->local, $l->serie, $l->numero_remision, $l->serie.' '.$l->numero_remision,
                1, 'CASA CENTRAL', $l->codigo, $l->descripcion, $l->grupo, $l->fecha_remision, $l->fecha_recepcion,
                $l->estado_recepcion, $l->cantidad_remision, $l->central, $l->pendiente, $l->remitido,
                $l->recibido_local, $l->transito_local, $l->disponible, $l->por_revisar, $l->estado_documento_texto,
                implode(' | ', $l->incidencias),
            ])),
            new InviernoDisponibilidadHoja('Artículos acumulados', [
                'CÓDIGO COMPLETO', 'DESCRIPCIÓN', 'GRUPO', 'REMISIONES', 'PRENDAS DOCUMENTADAS',
                'RECIBIDO CENTRAL', 'PENDIENTE CENTRAL', 'ENVIADO ASOCIADO', 'RECIBIDO LOCALES',
                'EN TRÁNSITO LOCALES', 'DISPONIBLE CENTRAL', 'SALDO POR REVISAR', 'LÍNEAS CON DIFERENCIAS',
            ], $this->articulos->map(fn ($a) => [$a->codigo, $a->descripcion, $a->grupo, $a->documentos,
                $a->prendas, $a->central, $a->pendiente, $a->remitido, $a->recibido_local,
                $a->transito_local, $a->disponible, $a->por_revisar, $a->lineas_revision])),
            new InviernoDisponibilidadHoja('Remisiones sin detalle', ['COD. LOCAL ORIGEN', 'LOCAL ORIGEN',
                'SERIE', 'NRO REMISIÓN', 'ESTADO', 'OBSERVACIÓN'], $this->datos['sinDetalle']->map(fn ($d) => [
                (int) $d->cod_sucursal_origen, $d->local, $d->serie, $d->numero_remision, $d->estado_texto,
                'Todavía no tiene detalle en ENVIOS. No se conocen artículos ni cantidades.',
            ])),
        ];
    }
}
