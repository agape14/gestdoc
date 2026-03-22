<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProveedorBienDocumento extends Model
{
    protected $fillable = ['proveedor_bien_id', 'nombre', 'file_path'];
    protected $appends = ['file_url'];

    public function proveedorBien()
    {
        return $this->belongsTo(ProveedorBien::class);
    }

    public function getFileUrlAttribute(): ?string
    {
        return storage_url_for_path($this->file_path);
    }
}
