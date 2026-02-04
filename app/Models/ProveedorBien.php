<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProveedorBien extends Model
{
    protected $fillable = [
        'titulo', 'entidad', 'categoria', 'estado', 'costo', 'user_id',
        'anulado', 'folder_id', 'clasificacion'
    ];

    public function folder()
    {
        return $this->belongsTo(\App\Models\Folder::class);
    }

    public function documentos()
    {
        return $this->hasMany(ProveedorBienDocumento::class);
    }

    public function scopeActive($query)
    {
        return $query->where('anulado', false);
    }

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
