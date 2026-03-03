<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProveedorServicio extends Model
{
    protected $fillable = [
        'titulo', 'entidad', 'especialidad', 'tipo_servicio', 'presupuesto',
        'estado', 'duracion', 'modalidad', 'contrato_archivo', 'tdr_archivo',
        'plantel_tecnico_aplica', 'valorizaciones_aplica', 'informes_tecnicos',
        'cargos', 'liquidacion_aplica', 'actas_resoluciones', 'conformidad_tecnica',
        'plazo_ejecucion', 'tiempo_culminacion', 'panel_fotografico', 'categoria', 'user_id',
        'anulado', 'folder_id', 'clasificacion'
    ];

    public function folder()
    {
        return $this->belongsTo(\App\Models\Folder::class);
    }

    public function documentos()
    {
        return $this->hasMany(ProveedorServicioDocumento::class);
    }

    public function scopeActive($query)
    {
        return $query->where('anulado', false);
    }

    protected $appends = [
        'contrato_archivo_url', 'tdr_archivo_url', 'panel_fotografico_url',
        'actas_resoluciones_url', 'conformidad_tecnica_url', 'informes_tecnicos_urls',
    ];

    protected $casts = [
        'plantel_tecnico_aplica' => 'boolean',
        'valorizaciones_aplica' => 'boolean',
        'liquidacion_aplica' => 'boolean',
        'cargos' => 'array',
    ];

    public function getContratoArchivoUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->contrato_archivo);
    }

    public function getTdrArchivoUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->tdr_archivo);
    }

    public function getPanelFotograficoUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->panel_fotografico);
    }

    public function getActasResolucionesUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->actas_resoluciones);
    }

    public function getConformidadTecnicaUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->conformidad_tecnica);
    }

    public function getInformesTecnicosUrlsAttribute(): array
    {
        $paths = is_array($this->informes_tecnicos) ? $this->informes_tecnicos : [];
        return array_map(fn ($p) => \storage_url_for_path($p), $paths);
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
