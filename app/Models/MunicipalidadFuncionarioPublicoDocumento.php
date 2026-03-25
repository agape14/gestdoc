<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MunicipalidadFuncionarioPublicoDocumento extends Model
{
    protected $fillable = ['municipalidad_funcionario_publico_id', 'nombre', 'file_path'];

    protected $appends = ['url'];

    public function municipalidadFuncionarioPublico()
    {
        return $this->belongsTo(MunicipalidadFuncionarioPublico::class);
    }

    public function getUrlAttribute(): string
    {
        return \storage_url_for_path($this->file_path) ?? '';
    }
}
