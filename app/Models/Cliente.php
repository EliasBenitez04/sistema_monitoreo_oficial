<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    public $table = 'clientes';

    protected $primaryKey = 'id_cliente';

    public $timestamps = false;

    public $fillable = [
        'id_ciudad',
        'id_departamento',
        'cli_ci',
        'cli_nombre',
        'cli_apellido',
        'cli_sexo',
        'cli_fnac',
        'cli_direccion',
        'cli_telefono'
    ];

    protected $casts = [
        'cli_ci' => 'string',
        'cli_nombre' => 'string',
        'cli_apellido' => 'string',
        'cli_sexo' => 'string',
        'cli_fnac' => 'date',
        'cli_direccion' => 'string',
        'cli_telefono' => 'string'
    ];

    public static array $rules = [
        'id_ciudad' => 'nullable',
        'id_departamento' => 'nullable',
        'cli_ci' => 'required|string|max:45',
        'cli_nombre' => 'nullable|string|max:45',
        'cli_apellido' => 'nullable|string|max:45',
        'cli_sexo' => 'nullable|string|max:1',
        'cli_fnac' => 'nullable',
        'cli_direccion' => 'nullable|string|max:100',
        'cli_telefono' => 'nullable|string|max:12'
    ];

    public function idDepartamento(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Departamento::class, 'id_departamento');
    }

    public function idCiudad(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Ciudad::class, 'id_ciudad');
    }

}
