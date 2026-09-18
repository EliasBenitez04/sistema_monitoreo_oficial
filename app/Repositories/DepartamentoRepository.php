<?php

namespace App\Repositories;

use App\Models\Departamento;
use App\Repositories\BaseRepository;

class DepartamentoRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'dep_descripcion'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Departamento::class;
    }
}
