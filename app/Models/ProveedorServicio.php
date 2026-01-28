<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProveedorServicio extends Model
{
    protected $fillable = [
        'titulo', 'entidad', 'especialidad', 'tipo_servicio', 'presupuesto', 
        'estado', 'duracion', 'modalidad', 'contrato_archivo', 'tdr_archivo', 
        'plantel_tecnico_aplica', 'valorizaciones_aplica', 'informes_tecnicos', 
        'cargos', 'liquidacion_aplica', 'actas_resoluciones', 'conformidad_tecnica', 
        'plazo_ejecucion', 'tiempo_culminacion', 'panel_fotografico', 'categoria', 'user_id'
    ];

    protected $casts = [
        'plantel_tecnico_aplica' => 'boolean',
        'valorizaciones_aplica' => 'boolean',
        'liquidacion_aplica' => 'boolean',
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
