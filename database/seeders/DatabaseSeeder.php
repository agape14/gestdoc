<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Usuarios demo (Admin, Operador, Visualizador) - contraseña: password
        $this->call([
            DemoUsersSeeder::class,
        ]);

        // Ejecutar seeders de carpetas: jerarquía inicial (Públicas/Privadas -> Obras, etc.)
        $this->call([
            FolderSeeder::class,
            FolderHierarchySeeder::class,
        ]);
    }
}
