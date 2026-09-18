<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    public $table = 'roles';

    public $fillable = [
        'gru_nombre'
    ];

    protected $casts = [
        'gru_nombre' => 'string'
    ];

    public static array $rules = [
        'gru_nombre' => 'required|string|max:40'
    ];

    public function usuarios(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Usuario::class, 'role_id');
    }

    public function permisos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Permiso::class, 'role_id');
    }
}
