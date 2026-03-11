<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroExpediente extends Model
{
    protected $table = 'registro_expedientes';

    protected $fillable = [
        'folder_id',
        'user_id',
        'tipo_inversion',
        'numero',
        'etiqueta',
        'proyecto',
        'cui',
        'descripcion',
        'numero_folio',
        'tomos',
        'anio',
        'tipo_unidad_conservacion',
        'resolucion',
        'fecha_aprobacion',
        'monto_o',
        'monto_p',
        'monto_r',
        'monto_s',
    ];

    protected $casts = [
        'fecha_aprobacion' => 'date',
        'monto_o' => 'decimal:2',
        'monto_p' => 'decimal:2',
        'monto_r' => 'decimal:2',
        'monto_s' => 'decimal:2',
        'anio' => 'integer',
    ];

    protected $appends = ['monto_total'];

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Total de montos = monto_o + monto_p + monto_r + monto_s (según fórmula =R4+S4+O4+P4).
     */
    public function getMontoTotalAttribute(): float
    {
        $o = (float) ($this->attributes['monto_o'] ?? 0);
        $p = (float) ($this->attributes['monto_p'] ?? 0);
        $r = (float) ($this->attributes['monto_r'] ?? 0);
        $s = (float) ($this->attributes['monto_s'] ?? 0);
        return round($o + $p + $r + $s, 2);
    }

    /**
     * Opciones para tipo de unidad de conservación (según cabecera del registro).
     */
    public static function opcionesTipoUnidadConservacion(): array
    {
        return [
            'Archivadores de Palanca',
            'Paquetes',
            'Empastados',
            'Folderes',
            'Cajas Archivadoras',
            'Archivo Digital',
            'Otro',
        ];
    }
}
