<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EjecutorObraDocumento extends Model
{
    protected $fillable = ['ejecutor_obra_id', 'nombre', 'file_path'];

    protected $appends = ['url'];

    public function ejecutorObra()
    {
        return $this->belongsTo(EjecutorObra::class);
    }

    public function getUrlAttribute(): string
    {
        return storage_url_for_path($this->file_path) ?? '';
    }
}
