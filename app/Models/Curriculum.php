<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Curriculum extends Model
{
    protected $table = 'curricula'; // Laravel default might be curricula, but let's be safe. Plural of curriculum is curricula.
    protected $fillable = ['user_id', 'nombre_candidato', 'especialidad', 'archivo_cv', 'anulado', 'folder_id'];

    protected $appends = ['archivo_cv_url'];

    public function getArchivoCvUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->archivo_cv);
    }

    protected function casts(): array
    {
        return ['anulado' => 'boolean'];
    }

    public function files()
    {
        return $this->hasMany(CurriculumFile::class, 'curriculum_id')->orderBy('orden');
    }

    /** Solo registros no anulados. */
    public function scopeActivo($query)
    {
        return $query->whereRaw('COALESCE(anulado, 0) = 0');
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }
}
