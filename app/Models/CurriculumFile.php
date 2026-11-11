<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurriculumFile extends Model
{
    protected $table = 'curriculum_files';

    protected $fillable = [
        'curriculum_id',
        'nombre_archivo',
        'path',
        'orden',
        'es_certificado',
    ];

    protected $appends = ['url'];

    protected $casts = [
        'orden' => 'integer',
        'es_certificado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function periodos()
    {
        return $this->hasMany(CurriculumCertificadoPeriodo::class, 'curriculum_file_id')->orderBy('orden')->orderBy('id');
    }

    public function getUrlAttribute(): string
    {
        return \storage_url_for_path($this->path) ?? '';
    }
}
