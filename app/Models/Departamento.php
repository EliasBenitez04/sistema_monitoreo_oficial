<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    public $table = 'departamento';

    public $fillable = [
        'dep_descripcion'
    ];

    protected $casts = [
        'dep_descripcion' => 'string'
    ];

    public static array $rules = [
        'dep_descripcion' => 'required|string|max:45'
    ];

    public function ciudads(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Ciudad::class, 'clientes');
    }
}
