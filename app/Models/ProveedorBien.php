<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProveedorBien extends Model
{
    protected $fillable = [
        'titulo', 'entidad', 'categoria', 'estado', 'costo', 'user_id',
        'anulado', 'folder_id', 'clasificacion',
        'cliente', 'objeto_del_contrato', 'numero_contrato_oc_comprobante',
        'fecha_inicio', 'fecha_culminacion',
        'total_meses', 'total_dias', 'traslape', 'total_dias_sin_traslape',
        'monto_neto', 'monto_acumulado',
        'archivo_contrato', 'archivo_comprobante_pago', 'archivo_conformidad_servicio',
        'tipo_documento_adjunto',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_culminacion' => 'date',
    ];

    protected $appends = ['archivo_contrato_url', 'archivo_comprobante_pago_url', 'archivo_conformidad_servicio_url'];

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

    public function folder()
    {
        return $this->belongsTo(\App\Models\Folder::class);
    }

    public function documentos()
    {
        return $this->hasMany(ProveedorBienDocumento::class);
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
