<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeguimientoPedido extends Model
{
    protected $table = 'seguimiento_pedido';
    protected $fillable = ['nro_pedido', 'fecha_pedido'];

    protected $casts = [
        'fecha_pedido' => 'date',
    ];

    public function detalles()
    {
        return $this->hasMany(SeguimientoPedidoDetalle::class, 'seguimiento_pedido_id');
    }
}
