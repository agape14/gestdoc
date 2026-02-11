<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * allowed_folders: JSON por módulo para Visualizador. Ej: {"folders":[1,2],"cvs":[3,4],"licitaciones":[5]}
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('allowed_folders')->nullable()->after('allowed_menus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('allowed_folders');
        });
    }
};
