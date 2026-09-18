<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RedistribucionSugerida extends Model
{
    use HasFactory;


    protected $table = 'redistribucion_sugerida';


    public $timestamps = false;


    protected $fillable = [
        'codigo',
        'sucursal_origen',
        'sucursal_destino',
        'cantidad',
        'stock_origen',
        'stock_destino',
        'venta_origen',
        'venta_destino',
        'motivo',
        'estado',
        'fecha_generacion'
    ];


    protected $casts = [
        'fecha_generacion' => 'datetime'
    ];


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

    public function detalleTransferencia()
    {
        return $this->hasOne(
            RedistribucionTransferenciaDetalle::class,
            'sugerencia_id'
        );
    }
}
