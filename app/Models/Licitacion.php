<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Licitacion extends Model
{
    protected $fillable = ['titulo', 'entidad', 'presupuesto', 'estado'];
}
