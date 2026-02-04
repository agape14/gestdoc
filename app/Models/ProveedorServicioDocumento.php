<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProveedorServicioDocumento extends Model
{
    protected $fillable = ['proveedor_servicio_id', 'nombre', 'file_path'];

    public function proveedorServicio()
    {
        return $this->belongsTo(ProveedorServicio::class);
    }
}
