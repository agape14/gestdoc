<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'folder_id',
        'user_id',
        'numero',
        'fecha_documento',
        'asunto',
        'remitente',
        'destinatario',
        'referencia',
        'observaciones',
        'folios',
    ];

    protected $casts = [
        'fecha_documento' => 'date',
        'folios' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function files()
    {
        return $this->hasMany(DocumentFile::class, 'document_id')->orderBy('orden');
    }
}
