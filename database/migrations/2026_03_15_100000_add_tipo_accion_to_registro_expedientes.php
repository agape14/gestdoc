<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registro_expedientes', function (Blueprint $table) {
            $table->string('tipo_accion', 80)->nullable()->after('tiene_reformulacion');
        });
    }

    public function down(): void
    {
        Schema::table('registro_expedientes', function (Blueprint $table) {
            $table->dropColumn('tipo_accion');
        });
    }
};
