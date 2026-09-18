<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ot extends Model
{
    protected $table = 'ot';
    protected $primaryKey = 'id_ot';
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'nro_ot',
        'codigo',
        'descripcion',
        'cantidad_orden',
        'estado',
        'obs'
    ];

    public function trazabilidades(): HasMany
    {
        return $this->hasMany(
            OtTrazabilidad::class,
            'id_ot',
            'id_ot'
        );
    }

    public function logisticaDetalle()
    {
        return $this->hasMany(OtLogisticaDetalle::class, 'id_ot', 'id_ot');
    }
}
