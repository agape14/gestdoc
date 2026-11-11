<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurriculumCertificadoPeriodo extends Model
{
    protected $table = 'curriculum_certificado_periodos';

    protected $fillable = [
        'curriculum_file_id',
        'tipo',
        'numero',
        'fecha_inicio',
        'fecha_fin',
        'dias',
        'orden',
    ];

    protected $casts = [
        'fecha_inicio' => 'date:Y-m-d',
        'fecha_fin' => 'date:Y-m-d',
        'dias' => 'integer',
        'orden' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function file()
    {
        return $this->belongsTo(CurriculumFile::class, 'curriculum_file_id');
    }
}
