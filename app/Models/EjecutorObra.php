<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EjecutorObra extends Model
{
    protected $fillable = [
        'nombre_sigla_entidad',
        'nomenclatura',
        'descripcion_objeto',
        'cui',
        'numero_contrato',
        'fecha_firma_contrato',
        'monto_total',
        'fecha_recepcion',
        'plazo',
        'fecha_inicio',
        'fecha_suspension',
        'fecha_reinicio',
        'fecha_final',
        'porcentaje_participacion',
        'monto_neto',
        'monto_acumulado',
        'liquidado_recepcionado',
        'fecha_entrega_terreno',
        'fecha_recepcion_obra',
        'fecha_aprobacion_liquidacion',
        'tiene_adicional_obra',
        'tiene_deductivo_obra',
        'fecha_adicional_obra',
        'archivo_acta_adicional',
        'monto_adicional',
        'plazo_adicional',
        'fecha_deductivo_obra',
        'archivo_acta_deductivo',
        'monto_deductivo',
        'plazo_deductivo',
        'tiene_aprobacion_acto_resolutivo',
        'fecha_aprobacion_acto_resolutivo',
        'archivo_aprobacion_acto_resolutivo',
        'monto_aprobacion_acto_resolutivo',
        'plazo_aprobacion_acto_resolutivo',
        'archivo_contrato',
        'archivo_acta_recepcion',
        'archivo_acta_inicio',
        'archivo_acta_suspension',
        'archivo_acta_reinicio',
        'archivo_acta_entrega_terreno',
        'archivo_resolucion_liquidacion',
        'user_id',
        'anulado',
        'folder_id',
    ];

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    public function documentos()
    {
        return $this->hasMany(EjecutorObraDocumento::class);
    }

    /** Resoluciones de liquidación (nombre + archivo múltiples) */
    public function documentosLiquidacion()
    {
        return $this->hasMany(EjecutorObraDocumento::class)->where('tipo', 'liquidacion');
    }

    protected $casts = [
        'fecha_firma_contrato' => 'date',
        'fecha_recepcion' => 'date',
        'fecha_inicio' => 'date',
        'fecha_suspension' => 'date',
        'fecha_reinicio' => 'date',
        'fecha_final' => 'date',
        'fecha_entrega_terreno' => 'date',
        'fecha_recepcion_obra' => 'date',
        'fecha_aprobacion_liquidacion' => 'date',
        'fecha_adicional_obra' => 'date',
        'fecha_deductivo_obra' => 'date',
        'fecha_aprobacion_acto_resolutivo' => 'date',
        'liquidado_recepcionado' => 'boolean',
        'anulado' => 'boolean',
    ];

    protected $appends = [
        'archivo_contrato_url',
        'archivo_acta_recepcion_url',
        'archivo_acta_inicio_url',
        'archivo_acta_suspension_url',
        'archivo_acta_reinicio_url',
        'archivo_acta_entrega_terreno_url',
        'archivo_resolucion_liquidacion_url',
        'archivo_acta_adicional_url',
        'archivo_acta_deductivo_url',
        'archivo_aprobacion_acto_resolutivo_url',
    ];

    public function getArchivoContratoUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->archivo_contrato);
    }

    public function getArchivoActaRecepcionUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->archivo_acta_recepcion);
    }

    public function getArchivoActaInicioUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->archivo_acta_inicio);
    }

    public function getArchivoActaSuspensionUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->archivo_acta_suspension);
    }

    public function getArchivoActaReinicioUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->archivo_acta_reinicio);
    }

    public function getArchivoActaEntregaTerrenoUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->archivo_acta_entrega_terreno);
    }

    public function getArchivoResolucionLiquidacionUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->archivo_resolucion_liquidacion);
    }

    public function getArchivoActaAdicionalUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->archivo_acta_adicional);
    }

    public function getArchivoActaDeductivoUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->archivo_acta_deductivo);
    }

    public function getArchivoAprobacionActoResolutivoUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->archivo_aprobacion_acto_resolutivo);
    }

    public function scopeActive($query)
    {
        return $query->where('anulado', false);
    }

    public function scopeForUser($query, $user)
    {
        if ($user->role === 'Administrador') {
            return $query;
        }
        if ($user->role === 'Operador') {
            return $query->where('user_id', $user->id);
        }
        return $query;
    }
}
