<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentFile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'document_id',
        'nombre_archivo',
        'path',
        'orden',
    ];

    protected $appends = ['url'];

    protected $casts = [
        'orden' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function getUrlAttribute(): string
    {
        return \storage_url_for_path($this->path) ?? '';
    }
}
