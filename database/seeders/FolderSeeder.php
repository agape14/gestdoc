<?php

namespace Database\Seeders;

use App\Models\Folder;
use Illuminate\Database\Seeder;

class FolderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar carpetas existentes
        Folder::truncate();

        // NIVEL 1: Carpetas principales
        $privados = Folder::create([
            'name' => 'PRIVADOS',
            'slug' => 'privados',
            'color' => '#FFE5E5',
            'icon' => 'Lock',
            'description' => 'Documentos privados y confidenciales',
            'is_system' => true,
        ]);

        $publicas = Folder::create([
            'name' => 'PUBLICAS',
            'slug' => 'publicas',
            'color' => '#E5F5FF',
            'icon' => 'Globe',
            'description' => 'Documentos públicos y de acceso general',
            'is_system' => true,
        ]);

        // NIVEL 2: Categorías dentro de PRIVADOS
        $categorias = [
            [
                'name' => 'EJECUTOR DE OBRAS',
                'slug' => 'ejecutor-de-obras',
                'color' => '#FFEAA7',
                'icon' => 'HardHat',
                'description' => 'Contratos como ejecutor de obras de construcción',
            ],
            [
                'name' => 'CONSULTORIAS DE OBRA',
                'slug' => 'consultorias-de-obra',
                'color' => '#FDCB6E',
                'icon' => 'Briefcase',
                'description' => 'Contratos de consultoría y supervisión de obras',
            ],
            [
                'name' => 'BIENES',
                'slug' => 'bienes',
                'color' => '#74B9FF',
                'icon' => 'Package',
                'description' => 'Contratos de adquisición de bienes',
            ],
            [
                'name' => 'SERVICIOS',
                'slug' => 'servicios',
                'color' => '#A29BFE',
                'icon' => 'Settings',
                'description' => 'Contratos de prestación de servicios',
            ],
            [
                'name' => 'OTROS',
                'slug' => 'otros',
                'color' => '#DFE6E9',
                'icon' => 'MoreHorizontal',
                'description' => 'Otros tipos de contratos',
            ],
        ];

        foreach ($categorias as $categoria) {
            // Crear en PRIVADOS
            $privadoCategoria = Folder::create(array_merge($categoria, [
                'parent_id' => $privados->id,
                'is_system' => true,
            ]));

            // Crear en PUBLICAS
            $publicaCategoria = Folder::create(array_merge($categoria, [
                'parent_id' => $publicas->id,
                'slug' => $categoria['slug'] . '-publicas',
                'is_system' => true,
            ]));

            // NIVEL 3: Subcategorías solo para CONSULTORIAS DE OBRA
            if ($categoria['name'] === 'CONSULTORIAS DE OBRA') {
                $subcategorias = [
                    [
                        'name' => 'RIEGO',
                        'slug' => 'riego',
                        'color' => '#55EFC4',
                        'icon' => 'Droplets',
                        'description' => 'Consultorías en obras de riego',
                    ],
                    [
                        'name' => 'AGUA Y DESAGUE',
                        'slug' => 'agua-y-desague',
                        'color' => '#00B894',
                        'icon' => 'Waves',
                        'description' => 'Consultorías en represas, irrigaciones y afines',
                    ],
                    [
                        'name' => 'COLEGIOS',
                        'slug' => 'colegios',
                        'color' => '#FAB1A0',
                        'icon' => 'School',
                        'description' => 'Consultorías en obras urbanas, edificaciones y afines',
                    ],
                    [
                        'name' => 'PAVIMENTOS',
                        'slug' => 'pavimentos',
                        'color' => '#636E72',
                        'icon' => 'Road',
                        'description' => 'Consultorías en obras viales, puertos y afines',
                    ],
                    [
                        'name' => 'PUENTES',
                        'slug' => 'puentes',
                        'color' => '#6C5CE7',
                        'icon' => 'Bridge',
                        'description' => 'Consultorías en puentes',
                    ],
                    [
                        'name' => 'LOSAS DEPORTIVAS',
                        'slug' => 'losas-deportivas',
                        'color' => '#00CEC9',
                        'icon' => 'Trophy',
                        'description' => 'Consultorías en losas deportivas',
                    ],
                ];

                foreach ($subcategorias as $sub) {
                    // Crear subcategoría en PRIVADOS -> CONSULTORIAS DE OBRA
                    Folder::create(array_merge($sub, [
                        'parent_id' => $privadoCategoria->id,
                        'is_system' => false, // Las subcategorías pueden ser modificadas
                    ]));

                    // Crear subcategoría en PUBLICAS -> CONSULTORIAS DE OBRA
                    Folder::create(array_merge($sub, [
                        'parent_id' => $publicaCategoria->id,
                        'slug' => $sub['slug'] . '-publicas',
                        'is_system' => false,
                    ]));
                }
            }
        }

        $this->command->info('Estructura de carpetas creada exitosamente.');
    }
}
