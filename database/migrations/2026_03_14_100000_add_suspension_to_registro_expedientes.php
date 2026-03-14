<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registro_expedientes', function (Blueprint $table) {
            $table->string('tuvo_suspension', 10)->nullable()->after('resolucion_archivo');
            $table->date('fecha_suspension')->nullable()->after('tuvo_suspension');
            $table->string('acta_suspension', 500)->nullable()->after('fecha_suspension');
            $table->date('fecha_reinicio')->nullable()->after('acta_suspension');
            $table->string('acta_reinicio', 500)->nullable()->after('fecha_reinicio');
        });
    }

    public function down(): void
    {
        Schema::table('registro_expedientes', function (Blueprint $table) {
            $table->dropColumn([
                'tuvo_suspension',
                'fecha_suspension',
                'acta_suspension',
                'fecha_reinicio',
                'acta_reinicio',
            ]);
        });
    }
};
