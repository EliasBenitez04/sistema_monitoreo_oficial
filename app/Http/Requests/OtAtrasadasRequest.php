<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OtAtrasadasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'buscar' => ['nullable', 'string', 'max:120'],
            'proceso' => ['nullable', 'string', 'max:120'],
            'nivel' => ['nullable', 'in:critica,grave,riesgo'],
            'dias_desde' => ['nullable', 'integer', 'min:30', 'max:9999'],
            'dias_hasta' => ['nullable', 'integer', 'min:30', 'max:9999', 'gte:dias_desde'],
            'orden' => ['nullable', 'in:atraso_desc,atraso_asc,ot_asc,proceso_asc'],
        ];
    }

    public function attributes(): array
    {
        return [
            'fecha_desde' => 'fecha desde',
            'fecha_hasta' => 'fecha hasta',
            'dias_desde' => 'días desde',
            'dias_hasta' => 'días hasta',
        ];
    }
}
