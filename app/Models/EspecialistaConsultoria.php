<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EspecialistaConsultoria extends Model
{
    protected $fillable = [
        'nombre', 'especialidad', 'tipo', 'documento', 'estado', 'user_id',
        'anulado', 'folder_id', 'clasificacion',
        'cliente', 'objeto_del_contrato', 'cui', 'numero_contrato_os_comprobante',
        'fecha_inicio', 'fecha_suspension', 'fecha_reinicio', 'fecha_culminacion',
        'total_meses', 'total_dias', 'traslape', 'total_dias_sin_traslape',
        'monto_neto', 'monto_acumulado',
        'archivo_contrato', 'archivo_comprobante_pago', 'archivo_conformidad_servicio',
        'archivo_suspension', 'archivo_reinicio',
        'tipo_documento_adjunto',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_suspension' => 'date',
        'fecha_reinicio' => 'date',
        'fecha_culminacion' => 'date',
    ];

    protected $appends = [
        'documento_url',
        'archivo_contrato_url',
        'archivo_comprobante_pago_url',
        'archivo_conformidad_servicio_url',
        'archivo_suspension_url',
        'archivo_reinicio_url',
    ];

    public function getDocumentoUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->documento);
    }

    public function getArchivoContratoUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->archivo_contrato);
    }

    public function getArchivoComprobantePagoUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->archivo_comprobante_pago);
    }

    public function getArchivoConformidadServicioUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->archivo_conformidad_servicio);
    }

    public function getArchivoSuspensionUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->archivo_suspension ?? null);
    }

    public function getArchivoReinicioUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->archivo_reinicio ?? null);
    }

    public function folder()
    {
        return $this->belongsTo(\App\Models\Folder::class);
    }

    public function documentos()
    {
        return $this->hasMany(EspecialistaConsultoriaDocumento::class);
    }

    public function scopeActive($query)
    {
        return $query->where('anulado', false);
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
