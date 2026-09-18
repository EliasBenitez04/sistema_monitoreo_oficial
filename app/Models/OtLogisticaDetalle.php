<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtLogisticaDetalle extends Model
{
    protected $table = 'ot_logistica_detalle';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id_ot',
        'id_trazabilidad',
        'sucursal',
        'cantidad',
    ];

    public function ot()
    {
        return $this->belongsTo(
            Ot::class,
            'id_ot',
            'id_ot'
        );
    }

    public function trazabilidad()
    {
        return $this->belongsTo(
            OtTrazabilidad::class,
            'id_trazabilidad',
            'id_trazabilidad'
        );
    }
}
