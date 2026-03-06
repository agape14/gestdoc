<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hacer nullable columnas del esquema antiguo de ejecutor_obras
     * para que el nuevo formulario (sin titulo/entidad) pueda insertar.
     */
    public function up(): void
    {
        Schema::table('ejecutor_obras', function (Blueprint $table) {
            if (Schema::hasColumn('ejecutor_obras', 'titulo')) {
                $table->string('titulo')->nullable()->change();
            }
            if (Schema::hasColumn('ejecutor_obras', 'entidad')) {
                $table->string('entidad')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ejecutor_obras', function (Blueprint $table) {
            if (Schema::hasColumn('ejecutor_obras', 'titulo')) {
                $table->string('titulo')->nullable(false)->change();
            }
            if (Schema::hasColumn('ejecutor_obras', 'entidad')) {
                $table->string('entidad')->nullable(false)->change();
            }
        });
    }
};
