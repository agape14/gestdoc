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
        Schema::table('especialista_ejecucions', function (Blueprint $table) {
            if (!Schema::hasColumn('especialista_ejecucions', 'fecha_contrato_cp')) {
                $table->date('fecha_contrato_cp')->nullable()->after('numero_contrato_os_comprobante');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('especialista_ejecucions', function (Blueprint $table) {
            if (Schema::hasColumn('especialista_ejecucions', 'fecha_contrato_cp')) {
                $table->dropColumn('fecha_contrato_cp');
            }
        });
    }
};
