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
        'module',
        'user_id',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['path', 'contracts_summary'];

    /**
     * Usuario creador (null = carpeta del sistema, visible para todos)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

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
     * Contratos contenidos en esta carpeta (legacy / otros módulos)
     */
    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'folder_id');
    }

    /**
     * Documentos de gestión documental (cartas, oficios, memos)
     */
    public function documents()
    {
        return $this->hasMany(Document::class, 'folder_id');
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
     * Carpetas válidas como destino al mover: misma línea (ancestros + descendientes) o superiores.
     * Devuelve lista plana [['id' => x, 'name' => y], ...]: primero ancestros (raíz → padre), luego descendientes.
     *
     * @param string $module
     * @param \App\Models\User $user
     * @param \App\Models\Folder|null $currentFolder
     * @return \Illuminate\Support\Collection
     */
    public static function getMoveTargetFolders(string $module, $user, ?Folder $currentFolder)
    {
        if (!$currentFolder) {
            return collect();
        }

        $list = collect();

        // Ancestros (línea superior): recorrer parent y cargar cadena completa. Se incluyen todos los de la ruta
        // (si el usuario ve la carpeta actual, puede mover a cualquier carpeta superior de esa ruta).
        $ancestorChain = [];
        $parent = $currentFolder->parent;
        while ($parent) {
            $ancestorChain[] = $parent;
            $parent = $parent->parent;
        }
        foreach (array_reverse($ancestorChain) as $p) {
            $list->push(['id' => $p->id, 'name' => $p->name]);
        }

        // Descendientes (misma línea hacia abajo)
        $queue = $currentFolder->children()->visibleForModuleUser($module, $user)->orderBy('name')->get();
        while ($queue->isNotEmpty()) {
            foreach ($queue as $f) {
                $list->push(['id' => $f->id, 'name' => $f->name]);
            }
            $queue = static::whereIn('parent_id', $queue->pluck('id'))
                ->visibleForModuleUser($module, $user)
                ->orderBy('name')
                ->get();
        }

        return $list;
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
     * Carpeta visible para el usuario efectivo en un módulo.
     * - Operador: carpetas del sistema (user_id null) + propias (user_id = id).
     * - Administrador sin filtro: todas las carpetas del módulo.
     * - Administrador con filtro user_id: carpetas del sistema + de ese usuario.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $module
     * @param int|null $effectiveUserId null = ver todas (solo admin), int = ver sistema + de ese usuario
     */
    public function scopeVisibleForUser($query, $module, $effectiveUserId)
    {
        $q = $query->where('module', $module);
        if ($effectiveUserId === null) {
            return $q;
        }
        return $q->where(function ($q2) use ($effectiveUserId) {
            $q2->whereNull('user_id')->orWhere('user_id', $effectiveUserId);
        });
    }

    /**
     * Filtro solo por usuario efectivo (sin filtrar por module). Útil para hijos y listas ya scoped por module.
     *
     * @param int|null $effectiveUserId null = no filtrar, int = solo sistema o de ese usuario
     */
    public function scopeForEffectiveUser($query, $effectiveUserId)
    {
        if ($effectiveUserId === null) {
            return $query;
        }
        return $query->where(function ($q) use ($effectiveUserId) {
            $q->whereNull('user_id')->orWhere('user_id', $effectiveUserId);
        });
    }

    /**
     * Carpetas visibles por módulo según el usuario (Administrador, Operador o Visualizador).
     * Visualizador: solo las carpetas en allowed_folders[$module].
     */
    public function scopeVisibleForModuleUser($query, $module, $user)
    {
        $query->where('module', $module);
        if ($user->role === 'Administrador') {
            return $query;
        }
        if ($user->role === 'Visualizador') {
            $ids = $user->allowed_folders[$module] ?? [];
            if (empty($ids)) {
                return $query->whereRaw('1 = 0');
            }
            return $query->whereIn('id', $ids);
        }
        return $query->where(function ($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        });
    }

    /**
     * Para gestión documental (module null): carpetas visibles para el usuario.
     * Administrador: todas. Operador: solo propias (user_id = id). Visualizador: solo las de allowed_folders['folders'].
     */
    public function scopeVisibleForGestionDocumental($query, $user)
    {
        if ($user->role === 'Administrador') {
            return $query;
        }
        if ($user->role === 'Visualizador') {
            $ids = $user->allowed_folders['folders'] ?? [];
            if (empty($ids)) {
                return $query->whereRaw('1 = 0');
            }
            return $query->whereIn('id', $ids);
        }
        return $query->where('user_id', $user->id);
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
                $originalSlug = $folder->slug;
                $count = 1;
                while (true) {
                    $q = static::where('slug', $folder->slug);
                    $q = $folder->module !== null ? $q->where('module', $folder->module) : $q->whereNull('module');
                    if (!$q->exists()) {
                        break;
                    }
                    $folder->slug = $originalSlug . '-' . $count;
                    $count++;
                }
            }
        });
    }
}
