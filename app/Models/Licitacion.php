<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Licitacion extends Model
{
    protected $fillable = [
        'titulo', 'entidad', 'presupuesto', 'estado',
        'tipo', 'clasificacion', 'especialidad', 'bases_integradas', 'propuesta_economica',
        'propuesta_tecnica', 'modalidad', 'consorcio', 'nombre_rc',
        'nombre_consorcio', 'consorciados', 'contrato_archivo', 'promesa_consorcio', 'user_id', 'anulado', 'folder_id'
    ];

    public function folder()
    {
        return $this->belongsTo(\App\Models\Folder::class);
    }

    public function documentos()
    {
        return $this->hasMany(LicitacionDocumento::class);
    }

    public function scopeActive($query)
    {
        return $query->where('anulado', false);
    }

    protected $casts = [
        'consorciados' => 'array',
        'consorcio' => 'boolean',
        'anulado' => 'boolean',
    ];
}
