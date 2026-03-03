<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultorObraDocumento extends Model
{
    protected $fillable = ['consultor_obra_id', 'nombre', 'file_path'];

    protected $appends = ['url'];

    public function consultorObra()
    {
        return $this->belongsTo(ConsultorObra::class);
    }

    public function getUrlAttribute(): string
    {
        return storage_url_for_path($this->file_path) ?? '';
    }
}
