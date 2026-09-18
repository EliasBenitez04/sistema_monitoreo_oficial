<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RedistribucionTransferenciaDetalle extends Model
{
    use HasFactory;

    protected $table = 'redistribucion_transferencia_detalle';

    public $timestamps = false;

    protected $fillable = [
        'transferencia_id',
        'sugerencia_id',
        'codigo',
        'cantidad',
        'stock_origen',
        'stock_destino',
        'venta_origen',
        'venta_destino',
        'motivo'
    ];

    public function transferencia()
    {
        return $this->belongsTo(
            RedistribucionTransferencia::class,
            'transferencia_id'
        );
    }

    public function sugerencia()
    {
        return $this->belongsTo(
            RedistribucionSugerida::class,
            'sugerencia_id'
        );
    }
}
