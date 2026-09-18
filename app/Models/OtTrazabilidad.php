<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtTrazabilidad extends Model
{
    protected $table = 'ot_trazabilidad';
    protected $primaryKey = 'id_trazabilidad';
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'id_ot',
        'proceso',
        'resultado',
        'fecha_proceso'
    ];

    public function ot(): BelongsTo
    {
        return $this->belongsTo(
            Ot::class,
            'id_ot',
            'id_ot'
        );
    }
}