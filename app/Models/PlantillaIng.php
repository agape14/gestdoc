<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantillaIng extends Model
{
    protected $fillable = [
        'titulo', 'especialidad', 'archivo', 'user_id', 'folder_id'
    ];

    public function folder()
    {
        return $this->belongsTo(Folder::class);
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
