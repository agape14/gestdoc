<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LicitacionDocumento extends Model
{
    protected $fillable = ['licitacion_id', 'nombre', 'file_path', 'disk'];

    protected $appends = ['url'];

    public function licitacion()
    {
        return $this->belongsTo(Licitacion::class);
    }

    /**
     * URL del archivo según el disco (R2 o local).
     */
    public function getUrlAttribute(): string
    {
        $disk = $this->disk ?? 'public';

        return Storage::disk($disk)->url($this->file_path);
    }
}
