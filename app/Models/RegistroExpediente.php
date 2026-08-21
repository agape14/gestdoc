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
        'tiene_actualizacion_precios',
        'tiene_reformulacion',
        'monto_o',
        'monto_p',
        'monto_r',
        'monto_s',
        'monto_supervision',
        'contrato',
        'resolucion_archivo',
        'tuvo_suspension',
        'fecha_suspension',
        'acta_suspension',
        'fecha_reinicio',
        'acta_reinicio',
        'tipo_accion',
        'estado',
    ];

    protected $casts = [
        'fecha_aprobacion' => 'date',
        'fecha_suspension' => 'date',
        'fecha_reinicio' => 'date',
        'monto_o' => 'decimal:2',
        'monto_p' => 'decimal:2',
        'monto_r' => 'decimal:2',
        'monto_s' => 'decimal:2',
        'monto_supervision' => 'decimal:2',
        'anio' => 'integer',
    ];

    public const ESTADOS = [
        'EN CURSO',
        'SOLO EXPEDIENTE',
        'PROCESO DE EJECUCION',
        'ARCHIVADO',
    ];

    protected $appends = ['monto_total', 'contrato_url', 'resolucion_archivo_url'];

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Total = EXPEDIENTE TECNICO + EVAL. + PPTO DE OBRA + SUPERVISIÓN (sin columna reformulación en formulario).
     */
    public function getMontoTotalAttribute(): float
    {
        $o = (float) ($this->attributes['monto_o'] ?? 0);
        $p = (float) ($this->attributes['monto_p'] ?? 0);
        $s = (float) ($this->attributes['monto_s'] ?? 0);
        $sup = (float) ($this->attributes['monto_supervision'] ?? 0);
        return round($o + $p + $s + $sup, 2);
    }

    public function getContratoUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->contrato);
    }

    public function getResolucionArchivoUrlAttribute(): ?string
    {
        return \storage_url_for_path($this->resolucion_archivo);
    }

    /**
     * Catálogo de estados del expediente (formularios, validación y badges).
     */
    public static function opcionesEstado(): array
    {
        return self::ESTADOS;
    }

    /**
     * Orden natural/numérico de etiqueta (04, 07, 10, 100) en MySQL 8+.
     */
    public static function etiquetaNaturalOrderSql(string $direction = 'asc'): string
    {
        $dir = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

        return 'CAST(REGEXP_SUBSTR(COALESCE(etiqueta, \'\'), \'^[0-9]+\') AS UNSIGNED) ' . $dir
            . ', COALESCE(etiqueta, \'\') ' . $dir;
    }

    public function scopeOrderByEtiquetaNatural($query, string $direction = 'asc')
    {
        return $query->orderByRaw(self::etiquetaNaturalOrderSql($direction));
    }

    /**
     * Opciones para tipo de inversión.
     */
    public static function opcionesTipoInversion(): array
    {
        return [
            'PERFIL Y/O FICHA',
            'IOARR',
            'PROYECTO DE INVERSION',
            'ACTIVIDAD',
        ];
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
