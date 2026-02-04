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
        'conformidad_tecnica', 'categoria', 'user_id', 'anulado', 'folder_id', 'clasificacion'
    ];

    public function folder()
    {
        return $this->belongsTo(\App\Models\Folder::class);
    }

    public function documentos()
    {
        return $this->hasMany(EjecutorObraDocumento::class);
    }

    public function scopeActive($query)
    {
        return $query->where('anulado', false);
    }

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
