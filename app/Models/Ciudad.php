<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ciudad extends Model
{
    public $table = 'ciudad';

    public $fillable = [
        'ciu_descripcion'
    ];

    protected $casts = [
        'ciu_descripcion' => 'string'
    ];

    public static array $rules = [
        'ciu_descripcion' => 'required|string|max:45'
    ];

    public function departamentos(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Departamento::class, 'clientes');
    }
}
