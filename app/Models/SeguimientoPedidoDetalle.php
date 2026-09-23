<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeguimientoPedidoDetalle extends Model
{
    protected $table = 'seguimiento_pedido_detalle';
    protected $fillable = ['seguimiento_pedido_id', 'id_ot'];

    public function pedido()
    {
        return $this->belongsTo(SeguimientoPedido::class, 'seguimiento_pedido_id');
    }

    public function ot()
    {
        return $this->belongsTo(Ot::class, 'id_ot', 'id_ot');
    }
}
