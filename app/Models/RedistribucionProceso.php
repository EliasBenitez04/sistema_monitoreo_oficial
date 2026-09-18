<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RedistribucionProceso extends Model
{
    use HasFactory;

    protected $table = 'redistribucion_proceso';

    public $timestamps = false;

    protected $fillable = [
        'fecha',
        'usuario',
        'total_productos',
        'total_movimientos',
        'observacion',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'total_productos' => 'integer',
        'total_movimientos' => 'integer',
    ];

    public function detalles()
    {
        return $this->hasMany(
            RedistribucionProcesoDetalle::class,
            'proceso_id',
            'id'
        );
    }
}
