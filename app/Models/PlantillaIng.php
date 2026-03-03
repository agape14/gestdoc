<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantillaIng extends Model
{
    protected $fillable = [
        'titulo', 'especialidad', 'archivo', 'user_id', 'anulado', 'folder_id'
    ];

    protected $appends = ['archivo_url'];

    public function getArchivoUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->archivo);
    }

    protected function casts(): array
    {
        return ['anulado' => 'boolean'];
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    /** Solo registros no anulados. */
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
