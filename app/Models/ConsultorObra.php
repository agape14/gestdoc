<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultorObra extends Model
{
    protected $fillable = [
        'titulo', 'entidad', 'especialidad', 'tipo_servicio', 'presupuesto',
        'estado', 'duracion', 'modalidad', 'contrato_archivo', 'tdr_archivo',
        'personal_clave', 'producto_tecnico', 'actas_resoluciones',
        'conformidad_tecnica', 'categoria', 'user_id', 'anulado', 'folder_id', 'clasificacion',
        'objeto_contrato', 'cui', 'numero_contrato_os_comprobante', 'fecha_contrato_cp',
        'fecha_conformidad', 'experiencia_proveniente_de', 'moneda', 'monto_contratado',
        'consorciado', 'porcentaje_participacion', 'importe', 'tipo_cambio_venta',
        'monto_facturado_acumulado', 'numero_resolucion', 'fecha_aprobacion',
    ];

    public function documentos()
    {
        return $this->hasMany(ConsultorObraDocumento::class);
    }

    public function folder()
    {
        return $this->belongsTo(\App\Models\Folder::class);
    }

    protected $appends = [
        'contrato_archivo_url', 'tdr_archivo_url', 'personal_clave_url',
        'actas_resoluciones_url', 'conformidad_tecnica_url', 'producto_tecnico_urls',
    ];

    protected $casts = [
        'producto_tecnico' => 'array',
        'anulado' => 'boolean',
        'consorciado' => 'boolean',
        'fecha_contrato_cp' => 'date',
        'fecha_conformidad' => 'date',
        'fecha_aprobacion' => 'date',
    ];

    public function getContratoArchivoUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->contrato_archivo);
    }

    public function getTdrArchivoUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->tdr_archivo);
    }

    public function getPersonalClaveUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->personal_clave);
    }

    public function getActasResolucionesUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->actas_resoluciones);
    }

    public function getConformidadTecnicaUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->conformidad_tecnica);
    }

    public function getProductoTecnicoUrlsAttribute(): array
    {
        $paths = is_array($this->producto_tecnico) ? $this->producto_tecnico : [];
        return array_map(fn ($p) => \storage_url_for_path($p), $paths);
    }

    public function scopeActive($query)
    {
        return $query->where('anulado', false);
    }

    // Global Scope for RBAC can be added here or used in Controller trait
    public function scopeForUser($query, $user)
    {
        if ($user->role === 'Administrador') {
            return $query;
        } elseif ($user->role === 'Operador') {
            return $query->where('user_id', $user->id);
        } else {
            // Visualizador or others see all but read-only (handled in policy/frontend)
            return $query;
        }
    }
}
