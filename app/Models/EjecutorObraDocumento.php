<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EjecutorObraDocumento extends Model
{
    protected $fillable = ['ejecutor_obra_id', 'nombre', 'file_path'];

    public function ejecutorObra()
    {
        return $this->belongsTo(EjecutorObra::class);
    }
}
