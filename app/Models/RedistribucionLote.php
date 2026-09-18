<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RedistribucionLote extends Model
{
    use HasFactory;

    protected $table = 'redistribucion_lote';

    public $timestamps = false;

    protected $fillable = [
        'proceso_id',
        'numero_lote',
        'fecha_generacion',
        'usuario',

        'total_movimientos',
        'total_unidades',
        'total_transferencias',
        'total_productos',

        'estado',
        'observacion',

        'usuario_generacion',
        'usuario_ejecucion',

        'fecha_ejecucion',
        'fecha_finalizacion',
    ];

    protected $casts = [
        'fecha_generacion' => 'datetime',
        'fecha_ejecucion' => 'datetime',
        'fecha_finalizacion' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | PROCESO
    |--------------------------------------------------------------------------
    */

    public function proceso()
    {
        return $this->belongsTo(
            RedistribucionProceso::class,
            'proceso_id',
            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DETALLES
    |--------------------------------------------------------------------------
    */

    public function detalles()
    {
        return $this->hasMany(
            RedistribucionProcesoDetalle::class,
            'lote_id',
            'id'
        );
    }
}
