<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultorObraDocumento extends Model
{
    protected $fillable = ['consultor_obra_id', 'nombre', 'file_path'];

    public function consultorObra()
    {
        return $this->belongsTo(ConsultorObra::class);
    }
}
