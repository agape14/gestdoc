<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EspecialistaEjecucionDocumento extends Model
{
    protected $fillable = ['especialista_ejecucion_id', 'nombre', 'file_path'];

    public function especialistaEjecucion()
    {
        return $this->belongsTo(EspecialistaEjecucion::class);
    }
}
