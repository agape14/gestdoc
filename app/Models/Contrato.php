<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Contrato extends Model
{
    protected $fillable = ['licitacion_id', 'archivo_path', 'fecha_firma'];

    protected $casts = [
        'fecha_firma' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
}
