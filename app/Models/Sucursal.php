<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    protected $table = 'sucursal';

    public $fillable = [
        'suc_descri',
        'suc_direccion',
        'suc_telefono'
    ];

    protected $casts = [
        'suc_descri' => 'string',
        'suc_direccion' => 'string',
        'suc_telefono' => 'string'
    ];

    public static array $rules = [
        'suc_descri' => 'required|string',
        'suc_direccion' => 'nullable|string|max:100',
        'suc_telefono' => 'nullable|string|max:45'
    ];

    use HasFactory;

    public function users()
    {
        return $this->hasMany(User::class, 'cod_suc', 'cod_suc');
    }

    protected $primaryKey = 'cod_suc'; // Cambia a tu clave primaria real

    // Asegúrate de definir la tabla y las propiedades si es necesario

    public function articulos(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Articulo::class, 'stock');
    }
}
