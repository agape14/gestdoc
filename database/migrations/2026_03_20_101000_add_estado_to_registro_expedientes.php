<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registro_expedientes', function (Blueprint $table) {
            $table->string('estado', 20)->nullable()->after('tipo_accion');
        });

        // Backfill inicial desde tipo_accion para no perder semántica previa.
        DB::statement("
            UPDATE registro_expedientes
            SET estado = CASE
                WHEN tipo_accion = 'LIQUIDACION' THEN 'ARCHIVADO'
                ELSE 'EN CURSO'
            END
            WHERE estado IS NULL OR estado = ''
        ");
    }

    public function down(): void
    {
        Schema::table('registro_expedientes', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
