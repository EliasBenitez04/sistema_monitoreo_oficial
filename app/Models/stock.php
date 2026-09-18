<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class stock extends Model
{
    public $table = 'stock';

    public $fillable = [
        'cod_suc',
        'id_articulo',
        'cantidad'
    ];

    protected $casts = [];

    public static array $rules = [
        'cod_suc' => 'required',
        'id_articulo' => 'required',
        'cantidad' => 'nullable'
    ];

    public function idArticulo(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Articulo::class, 'id_articulo');
    }

    public function codSuc(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Sucursal::class, 'cod_suc');
    }
}
