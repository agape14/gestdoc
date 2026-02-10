<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade user_id a folders para que las carpetas creadas queden asociadas al operador/administrador.
     * Las carpetas del sistema (is_system) tendrán user_id null y son visibles para todos.
     */
    public function up(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('module')->constrained('users')->nullOnDelete();
        });
        Schema::table('folders', function (Blueprint $table) {
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
