<?php

namespace App\Repositories;

use App\Models\Usuario;
use App\Repositories\BaseRepository;

class UsuarioRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'usu_nick',
        'usu_clave',
        'role_id',
        'usu_nombres',
        'usu_estado'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Usuario::class;
    }
}
