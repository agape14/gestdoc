<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultorObra extends Model
{
    protected $fillable = [
        'titulo', 'entidad', 'especialidad', 'tipo_servicio', 'presupuesto',
        'estado', 'duracion', 'modalidad', 'contrato_archivo', 'tdr_archivo',
        'personal_clave', 'producto_tecnico', 'actas_resoluciones',
        'conformidad_tecnica', 'categoria', 'user_id', 'anulado', 'folder_id', 'clasificacion'
    ];

    public function documentos()
    {
        return $this->hasMany(ConsultorObraDocumento::class);
    }

    public function folder()
    {
        return $this->belongsTo(\App\Models\Folder::class);
    }

    protected $casts = [
        'producto_tecnico' => 'array',
        'anulado' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('anulado', false);
    }

    // Global Scope for RBAC can be added here or used in Controller trait
    public function scopeForUser($query, $user)
    {
        if ($user->role === 'Administrador') {
            return $query;
        } elseif ($user->role === 'Operador') {
            return $query->where('user_id', $user->id);
        } else {
            // Visualizador or others see all but read-only (handled in policy/frontend)
            return $query;
        }
    }
}
