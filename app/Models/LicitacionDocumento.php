<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicitacionDocumento extends Model
{
    protected $fillable = ['licitacion_id', 'nombre', 'file_path'];

    public function licitacion()
    {
        return $this->belongsTo(Licitacion::class);
    }
}
