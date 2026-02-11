<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inmobiliaria extends Model
{
    protected $fillable = [
        'titulo', 'ubicacion', 'precio', 'estado', 'imagen', 'user_id', 'anulado', 'folder_id'
    ];

    protected function casts(): array
    {
        return ['anulado' => 'boolean'];
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    /** Solo registros no anulados (anulado = 0 o null). */
    public function scopeActivo($query)
    {
        return $query->whereRaw('COALESCE(anulado, 0) = 0');
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
