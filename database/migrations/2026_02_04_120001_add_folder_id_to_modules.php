<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade folder_id a los módulos que tendrán carpetas jerárquicas.
     */
    public function up(): void
    {
        $tables = [
            'licitacions' => 'licitacion',
            'consultor_obras' => 'consultor_obra',
            'ejecutor_obras' => 'ejecutor_obra',
            'proveedor_servicios' => 'proveedor_servicio',
            'proveedor_biens' => 'proveedor_bien',
            'especialista_ejecucions' => 'especialista_ejecucion',
            'especialista_consultorias' => 'especialista_consultoria',
        ];

        foreach ($tables as $table => $singular) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'folder_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->foreignId('folder_id')->nullable()->after('user_id')->constrained('folders')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        $tables = ['licitacions', 'consultor_obras', 'ejecutor_obras', 'proveedor_servicios', 'proveedor_biens', 'especialista_ejecucions', 'especialista_consultorias'];
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'folder_id')) {
                Schema::table($table, fn (Blueprint $b) => $b->dropConstrainedForeignId('folder_id'));
            }
        }
    }
};
