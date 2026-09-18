<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargaFotos extends Model
{
    protected $table = 'carga_fotos';
    protected $primaryKey = 'fot_cod';
    public $timestamps = false;

    public function linea()
    {
        return $this->belongsTo(Linea::class, 'linea_cod', 'linea_cod');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
