<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Linea extends Model
{
    protected $table = 'linea';
    protected $primaryKey = 'linea_cod';
    public $timestamps = false;

    protected $fillable = [
        'linea_desc'
    ];
}
