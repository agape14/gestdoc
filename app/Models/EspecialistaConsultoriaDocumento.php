<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EspecialistaConsultoriaDocumento extends Model
{
    protected $fillable = ['especialista_consultoria_id', 'nombre', 'file_path'];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return \storage_url_for_path($this->file_path) ?? '';
    }

    public function especialistaConsultoria()
    {
        return $this->belongsTo(EspecialistaConsultoria::class);
    }
}
