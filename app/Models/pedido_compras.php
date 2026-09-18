<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pedido_compras extends Model
{
    use HasFactory;

    protected $table = 'pedido_compras';

    protected $primaryKey = 'id_pedido';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'cod_suc',
        'ped_fecha',
        'ped_estado',
        'confirmado_por',
        'id_cliente',
        'condicion',
        'intervalo',
        'cant_cuotas',
        'ped_total',
        'nro_pedido',
        'descuento',
        'obs',
    ];

    /**
     * Sucursal que realiza el pedido
     */
    public function sucursal()
    {
        return $this->belongsTo(
            sucursal::class,
            'cod_suc',
            'cod_suc'
        );
    }
}
