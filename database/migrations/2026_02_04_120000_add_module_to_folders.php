<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade columna module a folders. null = Gestión Documental (contratos).
     * Valores: licitaciones, consultor-obras, ejecutor-obra, proveedor-servicios,
     * proveedor-bienes, especialistas-ejecucion, especialistas-consultoria.
     */
    public function up(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->string('module', 50)->nullable()->after('id');
        });

        Schema::table('folders', function (Blueprint $table) {
            $table->dropUnique(['slug']);
        });

        Schema::table('folders', function (Blueprint $table) {
            $table->unique(['module', 'slug']);
        });

        Schema::table('folders', function (Blueprint $table) {
            $table->index('module');
        });
    }

    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->dropUnique(['module', 'slug']);
            $table->dropIndex(['module']);
        });
        Schema::table('folders', function (Blueprint $table) {
            $table->unique('slug');
        });
        Schema::table('folders', function (Blueprint $table) {
            $table->dropColumn('module');
        });
    }
};
