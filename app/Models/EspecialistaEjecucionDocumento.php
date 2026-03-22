<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EspecialistaEjecucionDocumento extends Model
{
    protected $fillable = ['especialista_ejecucion_id', 'nombre', 'file_path'];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return \storage_url_for_path($this->file_path) ?? '';
    }

    public function especialistaEjecucion()
    {
        return $this->belongsTo(EspecialistaEjecucion::class);
    }
}
