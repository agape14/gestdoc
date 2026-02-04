<?php

namespace Database\Seeders;

use App\Models\Folder;
use Illuminate\Database\Seeder;

class FolderSeeder extends Seeder
{
    /**
     * Las carpetas del SGD (Gestión Documental, module=null) se crean por la migración
     * 2026_02_05_170000_seed_sgd_document_type_folders (Cartas, Oficios, Memorandos, Informes).
     * Este seeder ya no crea PRIVADOS/PUBLICAS para el SGD.
     */
    public function run(): void
    {
        if (Folder::whereNull('module')->exists()) {
            $this->command->info('Ya existen carpetas de Gestión Documental (SGD). Omitiendo.');
            return;
        }

        $this->command->info('Ejecuta las migraciones para crear las carpetas por tipo de documento (Cartas, Oficios, Memorandos, Informes) en el SGD.');
    }
}
