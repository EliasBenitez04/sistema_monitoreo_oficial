<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RedistribucionRemision extends Model
{
    use HasFactory;

    protected $table = 'redistribucion_remision';

    protected $fillable = [
        'detalle_id',
        'serie',
        'numero_remision',
        'fecha_remision',
        'fecha_creacion',
        'fecha_recepcion',
        'cantidad_transferida',
    ];

    protected $casts = [
        'fecha_remision' => 'date',
        'fecha_creacion' => 'date',
        'fecha_recepcion' => 'date',
        'cantidad_transferida' => 'integer',
    ];

    /**
     * Relación con el detalle de redistribución.
     */
    public function detalle()
    {
        return $this->belongsTo(
            RedistribucionProcesoDetalle::class,
            'detalle_id'
        );
    }
}
