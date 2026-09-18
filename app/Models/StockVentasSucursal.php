<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockVentasSucursal extends Model
{
    use HasFactory;

    protected $table = 'stock_ventas_sucursales';

    protected $primaryKey = 'id';

    public $timestamps = false;


    protected $fillable = [
        'sucursal_id',
        'linea',
        'grupo_plan',
        'talle',
        'color',
        'tejido',
        'codigo',
        'temporada',
        'cant_vta',
        'stock_actual',
        'periodo',
        'fecha_importacion'
    ];


    protected $casts = [
        'cant_vta' => 'integer',
        'stock_actual' => 'integer',
        'fecha_importacion' => 'datetime'
    ];


    public function sucursal()
    {
        return $this->belongsTo(
            Sucursal::class,
            'sucursal_id',
            'cod_suc'
        );
    }
}
