<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registro_expedientes', function (Blueprint $table) {
            $table->string('tiene_actualizacion_precios', 10)->nullable()->after('fecha_aprobacion');
            $table->string('tiene_reformulacion', 10)->nullable()->after('tiene_actualizacion_precios');
            $table->decimal('monto_supervision', 15, 2)->nullable()->after('monto_s');
            $table->string('contrato', 500)->nullable()->after('monto_supervision');
            $table->string('resolucion_archivo', 500)->nullable()->after('contrato');
        });
    }

    public function down(): void
    {
        Schema::table('registro_expedientes', function (Blueprint $table) {
            $table->dropColumn([
                'tiene_actualizacion_precios',
                'tiene_reformulacion',
                'monto_supervision',
                'contrato',
                'resolucion_archivo',
            ]);
        });
    }
};
