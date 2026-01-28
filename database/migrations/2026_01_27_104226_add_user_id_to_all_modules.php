<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'licitacions', 'consultor_obras', 'ejecutor_obras', 'proveedor_servicios',
            'proveedor_biens', 'especialista_ejecucions', 'especialista_consultorias',
            'inmobiliarias', 'topografias', 'tecnologias', 'plantilla_ings'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'user_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip reverse strictly as dropping foreign columns is tedious and dangerous on production
    }
};
