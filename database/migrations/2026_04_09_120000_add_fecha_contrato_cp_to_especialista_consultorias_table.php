<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('especialista_consultorias')) {
            return;
        }

        Schema::table('especialista_consultorias', function (Blueprint $table) {
            if (!Schema::hasColumn('especialista_consultorias', 'fecha_contrato_cp')) {
                $table->date('fecha_contrato_cp')->nullable()->after('numero_contrato_os_comprobante');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('especialista_consultorias')) {
            return;
        }

        Schema::table('especialista_consultorias', function (Blueprint $table) {
            if (Schema::hasColumn('especialista_consultorias', 'fecha_contrato_cp')) {
                $table->dropColumn('fecha_contrato_cp');
            }
        });
    }
};
