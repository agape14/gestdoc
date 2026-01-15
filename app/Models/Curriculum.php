<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Curriculum extends Model
{
    protected $table = 'curricula'; // Laravel default might be curricula, but let's be safe. Plural of curriculum is curricula.
    protected $fillable = ['nombre_candidato', 'especialidad', 'archivo_cv'];
}
