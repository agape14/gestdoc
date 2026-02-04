<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    /**
     * Tres usuarios de prueba (Administrador, Operador, Visualizador).
     * Contraseña para todos: password
     */
    public function run(): void
    {
        $menusOperador = [
            'dashboard', 'licitaciones', 'consultor-obras', 'ejecutor-obra', 'proveedor-servicios',
            'proveedor-bienes', 'especialistas-ejecucion', 'especialistas-consultoria',
            'inmobiliaria', 'topografia', 'tecnologia', 'plantillas-ing', 'cvs', 'folders',
        ];

        $users = [
            [
                'name' => 'Administrador Demo',
                'email' => 'admin@gestdoc.com',
                'password' => Hash::make('password'),
                'role' => 'Administrador',
                'allowed_menus' => null,
            ],
            [
                'name' => 'Operador Demo',
                'email' => 'operador@gestdoc.com',
                'password' => Hash::make('password'),
                'role' => 'Operador',
                'allowed_menus' => $menusOperador,
            ],
            [
                'name' => 'Visualizador Demo',
                'email' => 'visualizador@gestdoc.com',
                'password' => Hash::make('password'),
                'role' => 'Visualizador',
                'allowed_menus' => $menusOperador,
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                $data
            );
        }

        $this->command->info('Usuarios demo creados. Contraseña para todos: password');
    }
}
