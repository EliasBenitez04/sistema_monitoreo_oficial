<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RedistribucionTransferencia extends Model
{
    use HasFactory;

    protected $table = 'redistribucion_transferencia';

    public $timestamps = false;

    protected $fillable = [
        'lote_id',
        'sucursal_origen',
        'sucursal_destino',
        'total_productos',
        'total_unidades',
        'estado',
        'fecha_ejecucion',
        'observacion'
    ];

    protected $casts = [
        'fecha_ejecucion' => 'datetime'
    ];

    public function lote()
    {
        return $this->belongsTo(
            RedistribucionLote::class,
            'lote_id'
        );
    }

    public function origen()
    {
        return $this->belongsTo(
            Sucursal::class,
            'sucursal_origen',
            'cod_suc'
        );
    }

    public function destino()
    {
        return $this->belongsTo(
            Sucursal::class,
            'sucursal_destino',
            'cod_suc'
        );
    }

    public function detalles()
    {
        return $this->hasMany(
            RedistribucionTransferenciaDetalle::class,
            'transferencia_id'
        );
    }
}
