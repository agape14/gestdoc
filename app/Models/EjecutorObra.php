<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EjecutorObra extends Model
{
    protected $fillable = [
        'titulo', 'entidad', 'especialidad', 'tipo_obra', 'presupuesto', 
        'estado', 'modalidad', 'contrato_archivo', 'tdr_archivo', 
        'plazo_ejecucion', 'tiempo_culminacion', 'plantel_tecnico', 
        'valorizaciones', 'informes_tecnicos', 'cargos', 'liquidacion', 
        'panel_fotografico', 'expediente_tecnico', 'actas_resoluciones', 
        'conformidad_tecnica', 'categoria', 'user_id'
    ];

    protected $casts = [
        'valorizaciones' => 'array',
        'informes_tecnicos' => 'array',
        'cargos' => 'array',
    ];

     public function scopeForUser($query, $user)
    {
        if ($user->role === 'Administrador') {
            return $query;
        } elseif ($user->role === 'Operador') {
             return $query->where('user_id', $user->id);
        }
        return $query;
    }
}
