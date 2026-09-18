<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RedistribucionConfig extends Model
{
    public $table = 'redistribucion_config';

    public $fillable = [
        'metodo_demanda',
        'porcentaje_demanda',
        'stock_minimo',
        'stock_maximo',
        'venta_minima',
        'porcentaje_necesidad',
        'porcentaje_conservar_origen',
        'cantidad_minima',
        'cantidad_maxima',
        'dias_bloqueo',
        'bloquear_pendientes',
        'bloquear_en_proceso',
        'bloquear_finalizados_recientes',
        'activo'
    ];

    protected $casts = [
        'metodo_demanda' => 'string',
        'porcentaje_demanda' => 'decimal:2',
        'porcentaje_necesidad' => 'decimal:2',
        'porcentaje_conservar_origen' => 'decimal:2',
        'bloquear_pendientes' => 'boolean',
        'bloquear_en_proceso' => 'boolean',
        'bloquear_finalizados_recientes' => 'boolean',
        'activo' => 'boolean'
    ];

    public static array $rules = [
        'metodo_demanda' => 'required|string|max:20',
        'porcentaje_demanda' => 'required|numeric',
        'stock_minimo' => 'required',
        'stock_maximo' => 'required',
        'venta_minima' => 'required',
        'porcentaje_necesidad' => 'required|numeric',
        'porcentaje_conservar_origen' => 'required|numeric',
        'cantidad_minima' => 'required',
        'cantidad_maxima' => 'required',
        'dias_bloqueo' => 'required',
        'bloquear_pendientes' => 'required|boolean',
        'bloquear_en_proceso' => 'required|boolean',
        'bloquear_finalizados_recientes' => 'required|boolean',
        'activo' => 'required|boolean',
        'created_at' => 'nullable',
        'updated_at' => 'nullable'
    ];

    
}
