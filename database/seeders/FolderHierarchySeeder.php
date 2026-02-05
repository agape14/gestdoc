<?php

namespace Database\Seeders;

use App\Models\Folder;
use Illuminate\Database\Seeder;

class FolderHierarchySeeder extends Seeder
{
    /**
     * Jerarquía Públicas/Privadas según especificación: EJECUTOR DE OBRAS, CONSULTORIAS DE OBRA,
     * BIENES, SERVICIOS, OTROS con sus subcarpetas.
     */
    protected const HIERARCHY_PUBLICAS_PRIVADAS = [
        'Públicas' => [
            'EJECUTOR DE OBRAS' => ['RIEGO', 'AGUA DESAGUE', 'COLEGIOS', 'PAVIMENTOS', 'PUENTES', 'LOSA DEPORTIVAS'],
            'CONSULTORIAS DE OBRA' => ['RIEGO', 'AGUA DESAGUE', 'COLEGIOS', 'PAVIMENTOS'],
            'BIENES' => ['RIEGO', 'AGUA DESAGUE'],
            'SERVICIOS' => ['RIEGO', 'AGUA DESAGUE'],
            'OTROS' => [
                'ESPECIALISTA AMBIENTAL',
                'ESPECIALISTA EN GEOTECNIA Y/O MECANICA',
                'ESPECIALISTA EN SEGURIDAD Y SALUD',
                'ESPECIALISTA EN CALIDAD',
            ],
        ],
        'Privadas' => [
            'EJECUTOR DE OBRAS' => ['RIEGO', 'AGUA DESAGUE', 'COLEGIOS', 'PAVIMENTOS', 'PUENTES', 'LOSA DEPORTIVAS'],
            'CONSULTORIAS DE OBRA' => ['RIEGO', 'AGUA DESAGUE', 'COLEGIOS', 'PAVIMENTOS'],
            'BIENES' => ['RIEGO', 'AGUA DESAGUE'],
            'SERVICIOS' => ['RIEGO', 'AGUA DESAGUE'],
            'OTROS' => [
                'ESPECIALISTA AMBIENTAL',
                'ESPECIALISTA EN GEOTECNIA Y/O MECANICA',
                'ESPECIALISTA EN SEGURIDAD Y SALUD',
                'ESPECIALISTA EN CALIDAD',
            ],
        ],
    ];

    protected const DESCRIPTIONS_CONSULTORIAS = [
        'RIEGO' => 'Consultoría en obras de saneamiento y afines',
        'AGUA DESAGUE' => 'Consultoría en obras de represas, irrigaciones y afines',
        'COLEGIOS' => 'Consultoría en obras urbanas edificaciones y afines',
        'PAVIMENTOS' => 'Consultoría en obras viales, puertos y afines',
    ];

    protected const MODULES_WITH_PUBLICAS_PRIVADAS = [
        'licitaciones',
        'consultor-obras',
        'ejecutor-obra',
        'proveedor-servicios',
        'proveedor-bienes',
        'especialistas-ejecucion',
        'especialistas-consultoria',
    ];

    /**
     * Módulos que ya tienen Públicas/Privadas por migración; solo añadir hijos.
     */
    protected const MODULES_PUBLICAS_PRIVADAS_FLAT = [
        'inmobiliaria',
        'topografia',
        'tecnologia',
        'plantillas-ing',
    ];

    /**
     * cvs: Profesionales/Empresas con subcarpetas.
     */
    protected const HIERARCHY_CVS = [
        'Profesionales' => ['Ingeniería', 'Arquitectura', 'Otras especialidades'],
        'Empresas' => ['Constructoras', 'Consultoras', 'Otros rubros'],
    ];

    protected const COLORS = [
        'Públicas' => '#E3F2FD',
        'Privadas' => '#FCE4EC',
        'Profesionales' => '#E8F5E9',
        'Empresas' => '#FFF3E0',
        'EJECUTOR DE OBRAS' => '#E3F2FD',
        'CONSULTORIAS DE OBRA' => '#E8F5E9',
        'BIENES' => '#FFF3E0',
        'SERVICIOS' => '#FFF8E1',
        'OTROS' => '#F3E5F5',
        'Ingeniería' => '#E3F2FD',
        'Arquitectura' => '#F3E5F5',
        'Otras especialidades' => '#FFF8E1',
        'Constructoras' => '#E8F5E9',
        'Consultoras' => '#E3F2FD',
        'Otros rubros' => '#FFF3E0',
    ];

    public function run(): void
    {
        $this->seedModulesWithFullHierarchy();
        $this->seedModulesPublicasPrivadasChildren();
        $this->seedCvsHierarchy();
    }

    /**
     * Crea jerarquía Públicas/Privadas: raíz -> categorías (EJECUTOR DE OBRAS, CONSULTORIAS DE OBRA, etc.)
     * -> subcarpetas (RIEGO, AGUA DESAGUE, etc.).
     */
    protected function seedModulesWithFullHierarchy(): void
    {
        foreach (self::MODULES_WITH_PUBLICAS_PRIVADAS as $module) {
            if (Folder::where('module', $module)->exists()) {
                $this->command->info("Módulo {$module} ya tiene carpetas. Omitiendo.");
                continue;
            }

            foreach (self::HIERARCHY_PUBLICAS_PRIVADAS as $rootName => $categories) {
                $root = Folder::create([
                    'parent_id' => null,
                    'name' => $rootName,
                    'slug' => \Illuminate\Support\Str::slug($rootName . '-' . $module),
                    'color' => self::COLORS[$rootName] ?? '#EAEAEA',
                    'icon' => $rootName === 'Públicas' ? 'Globe' : 'Lock',
                    'description' => null,
                    'is_system' => true,
                    'module' => $module,
                ]);

                foreach ($categories as $categoryName => $subfolders) {
                    $category = Folder::create([
                        'parent_id' => $root->id,
                        'name' => $categoryName,
                        'slug' => \Illuminate\Support\Str::slug($categoryName . '-' . $rootName . '-' . $module),
                        'color' => self::COLORS[$categoryName] ?? '#EAEAEA',
                        'icon' => 'Folder',
                        'description' => null,
                        'is_system' => true,
                        'module' => $module,
                    ]);

                    foreach ($subfolders as $subName) {
                        $desc = ($categoryName === 'CONSULTORIAS DE OBRA' && isset(self::DESCRIPTIONS_CONSULTORIAS[$subName]))
                            ? self::DESCRIPTIONS_CONSULTORIAS[$subName]
                            : null;

                        Folder::create([
                            'parent_id' => $category->id,
                            'name' => $subName,
                            'slug' => \Illuminate\Support\Str::slug($subName . '-' . $categoryName . '-' . $rootName . '-' . $module),
                            'color' => self::COLORS[$subName] ?? '#EAEAEA',
                            'icon' => 'Folder',
                            'description' => $desc,
                            'is_system' => true,
                            'module' => $module,
                        ]);
                    }
                }
            }
            $this->command->info("Jerarquía Públicas/Privadas creada para módulo: {$module}");
        }
    }

    /**
     * Añade Obras como hijo de Públicas y Privadas en inmobiliaria, topografia, etc.
     */
    protected function seedModulesPublicasPrivadasChildren(): void
    {
        $childrenByRoot = [
            'Públicas' => ['Obras'],
            'Privadas' => ['Obras'],
        ];

        foreach (self::MODULES_PUBLICAS_PRIVADAS_FLAT as $module) {
            foreach ($childrenByRoot as $rootName => $children) {
                $root = Folder::where('module', $module)->where('name', $rootName)->first();
                if (!$root) {
                    continue;
                }

                foreach ($children as $childName) {
                    $exists = Folder::where('module', $module)
                        ->where('parent_id', $root->id)
                        ->where('name', $childName)
                        ->exists();

                    if (!$exists) {
                        Folder::create([
                            'parent_id' => $root->id,
                            'name' => $childName,
                            'slug' => \Illuminate\Support\Str::slug($childName . '-' . $rootName . '-' . $module),
                            'color' => self::COLORS[$childName] ?? '#EAEAEA',
                            'icon' => 'Folder',
                            'description' => null,
                            'is_system' => true,
                            'module' => $module,
                        ]);
                    }
                }
            }
            $this->command->info("Subcarpetas añadidas a módulo: {$module}");
        }
    }

    /**
     * Añade subcarpetas a Profesionales/Empresas en cvs.
     */
    protected function seedCvsHierarchy(): void
    {
        $module = 'cvs';
        foreach (self::HIERARCHY_CVS as $rootName => $children) {
            $root = Folder::where('module', $module)->where('name', $rootName)->first();
            if (!$root) {
                continue;
            }

            foreach ($children as $childName) {
                $exists = Folder::where('module', $module)
                    ->where('parent_id', $root->id)
                    ->where('name', $childName)
                    ->exists();

                if (!$exists) {
                    Folder::create([
                        'parent_id' => $root->id,
                        'name' => $childName,
                        'slug' => \Illuminate\Support\Str::slug($childName . '-' . $rootName . '-' . $module),
                        'color' => self::COLORS[$childName] ?? '#EAEAEA',
                        'icon' => 'Folder',
                        'description' => null,
                        'is_system' => true,
                        'module' => $module,
                    ]);
                }
            }
        }
        $this->command->info("Subcarpetas añadidas a módulo: {$module}");
    }
}
