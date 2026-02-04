<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProveedorBienDocumento extends Model
{
    protected $fillable = ['proveedor_bien_id', 'nombre', 'file_path'];

    public function proveedorBien()
    {
        return $this->belongsTo(ProveedorBien::class);
    }
}
