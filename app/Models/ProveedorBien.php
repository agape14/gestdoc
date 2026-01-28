<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProveedorBien extends Model
{
    protected $fillable = [
        'titulo', 'entidad', 'categoria', 'estado', 'costo', 'user_id'
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
