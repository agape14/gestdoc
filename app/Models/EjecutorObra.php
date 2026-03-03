<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EjecutorObra extends Model
{
    protected $fillable = [
        'titulo', 'entidad', 'especialidad', 'tipo_obra', 'presupuesto',
        'estado', 'modalidad', 'contrato_archivo', 'tdr_archivo',
        'plazo_ejecucion', 'tiempo_culminacion', 'plantel_tecnico',
        'valorizaciones', 'informes_tecnicos', 'cargos', 'liquidacion',
        'panel_fotografico', 'expediente_tecnico', 'actas_resoluciones',
        'conformidad_tecnica', 'categoria', 'user_id', 'anulado', 'folder_id', 'clasificacion'
    ];

    public function folder()
    {
        return $this->belongsTo(\App\Models\Folder::class);
    }

    public function documentos()
    {
        return $this->hasMany(EjecutorObraDocumento::class);
    }

    public function scopeActive($query)
    {
        return $query->where('anulado', false);
    }

    protected $appends = [
        'contrato_archivo_url', 'tdr_archivo_url', 'liquidacion_url', 'panel_fotografico_url',
        'expediente_tecnico_url', 'actas_resoluciones_url', 'conformidad_tecnica_url',
        'valorizaciones_urls', 'informes_tecnicos_urls',
    ];

    protected $casts = [
        'valorizaciones' => 'array',
        'informes_tecnicos' => 'array',
        'cargos' => 'array',
    ];

    public function getContratoArchivoUrlAttribute(): ?string
    {
        return storage_url_for_path($this->contrato_archivo);
    }

    public function getTdrArchivoUrlAttribute(): ?string
    {
        return storage_url_for_path($this->tdr_archivo);
    }

    public function getLiquidacionUrlAttribute(): ?string
    {
        return storage_url_for_path($this->liquidacion);
    }

    public function getPanelFotograficoUrlAttribute(): ?string
    {
        return storage_url_for_path($this->panel_fotografico);
    }

    public function getExpedienteTecnicoUrlAttribute(): ?string
    {
        return storage_url_for_path($this->expediente_tecnico);
    }

    public function getActasResolucionesUrlAttribute(): ?string
    {
        return storage_url_for_path($this->actas_resoluciones);
    }

    public function getConformidadTecnicaUrlAttribute(): ?string
    {
        return storage_url_for_path($this->conformidad_tecnica);
    }

    public function getValorizacionesUrlsAttribute(): array
    {
        $paths = is_array($this->valorizaciones) ? $this->valorizaciones : [];
        return array_map(fn ($p) => storage_url_for_path($p), $paths);
    }

    public function getInformesTecnicosUrlsAttribute(): array
    {
        $paths = is_array($this->informes_tecnicos) ? $this->informes_tecnicos : [];
        return array_map(fn ($p) => storage_url_for_path($p), $paths);
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
