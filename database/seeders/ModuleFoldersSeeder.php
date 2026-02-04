<?php

namespace Database\Seeders;

use App\Models\Folder;
use Illuminate\Database\Seeder;

class ModuleFoldersSeeder extends Seeder
{
    /**
     * Módulos que tendrán la misma jerarquía de carpetas (PUBLICOS/PRIVADOS y subcarpetas).
     */
    protected const MODULES = [
        'licitaciones',
        'consultor-obras',
        'ejecutor-obra',
        'proveedor-servicios',
        'proveedor-bienes',
        'especialistas-ejecucion',
        'especialistas-consultoria',
    ];

    /**
     * Copia la estructura de carpetas de Gestión Documental (module null) a cada módulo.
     */
    public function run(): void
    {
        $sourceFolders = Folder::whereNull('module')
            ->orderByRaw('parent_id IS NULL DESC')
            ->orderBy('id')
            ->get();

        if ($sourceFolders->isEmpty()) {
            $this->command->warn('No hay carpetas en Gestión Documental. Ejecuta FolderSeeder primero.');
            return;
        }

        foreach (self::MODULES as $module) {
            $existing = Folder::where('module', $module)->count();
            if ($existing > 0) {
                $this->command->info("Módulo {$module} ya tiene carpetas. Omitiendo.");
                continue;
            }

            $idMap = []; // old_id => new_id
            foreach ($sourceFolders as $src) {
                $newParentId = $src->parent_id ? ($idMap[$src->parent_id] ?? null) : null;
                $new = Folder::create([
                    'parent_id' => $newParentId,
                    'name' => $src->name,
                    'slug' => $src->slug,
                    'color' => $src->color,
                    'icon' => $src->icon,
                    'description' => $src->description,
                    'is_system' => $src->is_system,
                    'module' => $module,
                ]);
                $idMap[$src->id] = $new->id;
            }
            $this->command->info("Estructura de carpetas copiada a módulo: {$module}");
        }
    }
}
