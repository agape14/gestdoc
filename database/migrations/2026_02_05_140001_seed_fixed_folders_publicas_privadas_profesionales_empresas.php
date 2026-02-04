<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Crea las carpetas fijas: Públicas y Privadas para inmobiliaria, topografia, tecnologia, plantillas-ing;
     * Profesionales y Empresas para cvs.
     */
    public function up(): void
    {
        $modulesPublicasPrivadas = ['inmobiliaria', 'topografia', 'tecnologia', 'plantillas-ing'];
        foreach ($modulesPublicasPrivadas as $module) {
            foreach (['Públicas', 'Privadas'] as $name) {
                if (DB::table('folders')->where('module', $module)->where('name', $name)->exists()) {
                    continue;
                }
                DB::table('folders')->insert([
                    'parent_id' => null,
                    'name' => $name,
                    'slug' => \Illuminate\Support\Str::slug($name . '-' . $module),
                    'color' => $name === 'Públicas' ? '#E3F2FD' : '#FCE4EC',
                    'icon' => $name === 'Públicas' ? 'Globe' : 'Lock',
                    'description' => null,
                    'is_system' => true,
                    'module' => $module,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        foreach (['Profesionales', 'Empresas'] as $name) {
            if (DB::table('folders')->where('module', 'cvs')->where('name', $name)->exists()) {
                continue;
            }
            DB::table('folders')->insert([
                'parent_id' => null,
                'name' => $name,
                'slug' => \Illuminate\Support\Str::slug($name . '-cvs'),
                'color' => $name === 'Profesionales' ? '#E8F5E9' : '#FFF3E0',
                'icon' => $name === 'Profesionales' ? 'Building' : 'Building',
                'description' => null,
                'is_system' => true,
                'module' => 'cvs',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('folders')->whereIn('module', ['inmobiliaria', 'topografia', 'tecnologia', 'plantillas-ing', 'cvs'])->delete();
    }
};
