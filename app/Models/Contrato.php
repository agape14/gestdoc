<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Contrato extends Model
{
    protected $fillable = [
        'folder_id',
        'licitacion_id',
        'client',
        'project_name',
        'contract_object',
        'contract_number',
        'currency',
        'amount',
        'participation_percentage',
        'contract_date',
        'conformity_date',
        'exchange_rate',
        'accumulated_amount',
        'status',
        'file_path',
        'fecha_firma', // Mantener compatibilidad
    ];

    protected $casts = [
        'fecha_firma' => 'date',
        'contract_date' => 'date',
        'conformity_date' => 'date',
        'amount' => 'decimal:2',
        'participation_percentage' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'accumulated_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['calculated_amount'];

    /**
     * Relación con carpeta
     */
    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Relación con licitación (mantener compatibilidad)
     */
    public function licitacion()
    {
        return $this->belongsTo(Licitacion::class);
    }

    /**
     * Get the formatted fecha_firma attribute
     */
    public function getFechaFirmaFormattedAttribute()
    {
        return $this->fecha_firma ? Carbon::parse($this->fecha_firma)->setTimezone('America/Lima')->locale('es')->isoFormat('D [de] MMMM [de] YYYY') : null;
    }

    /**
     * Calcula el monto facturado según el porcentaje de participación
     */
    public function getCalculatedAmountAttribute()
    {
        if ($this->participation_percentage < 100) {
            return round($this->amount * ($this->participation_percentage / 100), 2);
        }

        return $this->amount;
    }

    /**
     * Calcula si el contrato está completo automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($contrato) {
            // Calcular el monto acumulado si el porcentaje es menor a 100
            if ($contrato->participation_percentage < 100) {
                $contrato->accumulated_amount = round($contrato->amount * ($contrato->participation_percentage / 100), 2);
            } else {
                $contrato->accumulated_amount = $contrato->amount;
            }
        });
    }
}
