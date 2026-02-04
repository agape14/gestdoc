<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Carpetas por tipo de documento para el SGD (Gestión Documental).
     * Crea Cartas, Oficios, Memorandos, Informes como carpetas iniciales (module=null).
     * Reemplaza la estructura antigua PRIVADOS/PUBLICAS: mueve documentos a "Cartas" y elimina esas carpetas.
     */
    public function up(): void
    {
        $defaultSgdFolders = [
            [
                'name' => 'Cartas',
                'slug' => 'cartas',
                'color' => '#E3F2FD',
                'icon' => 'FileText',
                'description' => 'Cartas y comunicaciones formales',
            ],
            [
                'name' => 'Oficios',
                'slug' => 'oficios',
                'color' => '#E8F5E9',
                'icon' => 'ClipboardCheck',
                'description' => 'Oficios y documentos oficiales',
            ],
            [
                'name' => 'Memorandos',
                'slug' => 'memorandos',
                'color' => '#FFF3E0',
                'icon' => 'Archive',
                'description' => 'Memorandos internos',
            ],
            [
                'name' => 'Informes',
                'slug' => 'informes',
                'color' => '#FCE4EC',
                'icon' => 'Diagram',
                'description' => 'Informes y reportes',
            ],
        ];

        // Crear carpetas por tipo de documento (solo si no existen)
        foreach ($defaultSgdFolders as $folder) {
            $exists = DB::table('folders')
                ->whereNull('module')
                ->where('slug', $folder['slug'])
                ->exists();
            if (!$exists) {
                DB::table('folders')->insert([
                    'parent_id' => null,
                    'name' => $folder['name'],
                    'slug' => $folder['slug'],
                    'color' => $folder['color'],
                    'icon' => $folder['icon'],
                    'description' => $folder['description'],
                    'is_system' => true,
                    'module' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Obtener IDs de la estructura antigua (PRIVADOS, PUBLICAS y todos sus hijos con module=null)
        $oldRootIds = DB::table('folders')
            ->whereNull('module')
            ->whereIn('name', ['PRIVADOS', 'PUBLICAS'])
            ->pluck('id')
            ->toArray();

        if (empty($oldRootIds)) {
            return;
        }

        $oldFolderIds = $this->collectDescendantIds($oldRootIds);
        $oldFolderIds = array_unique(array_merge($oldRootIds, $oldFolderIds));

        // ID de la carpeta "Cartas" para reasignar documentos
        $cartasId = DB::table('folders')
            ->whereNull('module')
            ->where('slug', 'cartas')
            ->value('id');

        if ($cartasId && !empty($oldFolderIds)) {
            DB::table('documents')
                ->whereIn('folder_id', $oldFolderIds)
                ->update(['folder_id' => $cartasId]);
        }

        // Quitar parent_id de carpetas que apuntan a otras que vamos a borrar (evitar FK)
        DB::table('folders')
            ->whereIn('parent_id', $oldFolderIds)
            ->update(['parent_id' => null]);

        // Eliminar contratos de esas carpetas (opcional: o mover a un folder por defecto si se usan)
        DB::table('contratos')->whereIn('folder_id', $oldFolderIds)->update(['folder_id' => $cartasId ?? null]);

        // Borrar la estructura antigua (hijos primero no necesario si ya nullificamos parent_id)
        DB::table('folders')->whereIn('id', $oldFolderIds)->delete();
    }

    private function collectDescendantIds(array $parentIds): array
    {
        $all = [];
        $current = $parentIds;
        while (!empty($current)) {
            $children = DB::table('folders')
                ->whereIn('parent_id', $current)
                ->pluck('id')
                ->toArray();
            $all = array_merge($all, $children);
            $current = $children;
        }
        return $all;
    }

    public function down(): void
    {
        DB::table('folders')
            ->whereNull('module')
            ->whereIn('slug', ['cartas', 'oficios', 'memorandos', 'informes'])
            ->delete();
    }
};
