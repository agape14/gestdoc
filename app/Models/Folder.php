<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Folder extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'color',
        'icon',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['path', 'contracts_summary'];

    /**
     * Relación padre (carpeta contenedora)
     */
    public function parent()
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    /**
     * Relación hijos (subcarpetas)
     */
    public function children()
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    /**
     * Contratos contenidos en esta carpeta
     */
    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'folder_id');
    }

    /**
     * Obtiene el breadcrumb/path completo de la carpeta
     */
    public function getPathAttribute()
    {
        $path = collect([$this]);
        $parent = $this->parent;

        while ($parent) {
            $path->prepend($parent);
            $parent = $parent->parent;
        }

        return $path->map(function ($folder) {
            return [
                'id' => $folder->id,
                'name' => $folder->name,
                'slug' => $folder->slug,
            ];
        })->toArray();
    }

    /**
     * Obtiene resumen de contratos (completos/totales)
     */
    public function getContractsSummaryAttribute()
    {
        $total = $this->contratos()->count();
        $complete = $this->contratos()->where('status', 'completo')->count();

        return [
            'total' => $total,
            'complete' => $complete,
            'incomplete' => $total - $complete,
            'percentage' => $total > 0 ? round(($complete / $total) * 100) : 0,
        ];
    }

    /**
     * Genera slug automáticamente al crear
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($folder) {
            if (empty($folder->slug)) {
                $folder->slug = Str::slug($folder->name);
                
                // Asegurar unicidad del slug
                $originalSlug = $folder->slug;
                $count = 1;
                
                while (static::where('slug', $folder->slug)->exists()) {
                    $folder->slug = $originalSlug . '-' . $count;
                    $count++;
                }
            }
        });
    }
}
