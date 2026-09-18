<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Articulo extends Model
{
    public $table = 'articulos';
    public $timestamps = false;

    protected $fillable = [
        'mar_cod',
        'art_descripcion',
        'art_precio',
        'prec_vent',    // <-- agregado
        'art_iva'
    ];

    protected $casts = [
        'art_descripcion' => 'string',
        'art_precio' => 'decimal:0',
        'prec_vent' => 'decimal:0', // <-- agregado
    ];

    public static array $rules = [
        'art_descripcion' => 'required|string|max:45',
        'art_precio' => 'nullable|numeric',
        'prec_vent' => 'nullable|numeric', // <-- agregado
        'art_iva' => 'nullable'
    ];

    public function sucursals()
    {
        return $this->belongsToMany(\App\Models\Sucursal::class, 'stock');
    }

}
