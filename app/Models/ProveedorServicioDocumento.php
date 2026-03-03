<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProveedorServicioDocumento extends Model
{
    protected $fillable = ['proveedor_servicio_id', 'nombre', 'file_path'];

    protected $appends = ['url'];

    public function proveedorServicio()
    {
        return $this->belongsTo(ProveedorServicio::class);
    }

    public function getUrlAttribute(): string
    {
        return storage_url_for_path($this->file_path) ?? '';
    }
}
