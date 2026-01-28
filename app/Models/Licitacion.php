<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Licitacion extends Model
{
    protected $fillable = [
        'titulo', 'entidad', 'presupuesto', 'estado', 
        'tipo', 'especialidad', 'bases_integradas', 'propuesta_economica', 
        'propuesta_tecnica', 'modalidad', 'consorcio', 'nombre_rc', 
        'nombre_consorcio', 'consorciados', 'contrato_archivo', 'promesa_consorcio', 'user_id'
    ];

    protected $casts = [
        'consorciados' => 'array',
        'consorcio' => 'boolean',
    ];
}
