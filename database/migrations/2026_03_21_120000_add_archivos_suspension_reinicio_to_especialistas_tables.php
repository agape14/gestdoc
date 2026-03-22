<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['especialista_ejecucions', 'especialista_consultorias'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::hasColumn($table, 'archivo_suspension')) {
                    $blueprint->string('archivo_suspension')->nullable();
                }
                if (! Schema::hasColumn($table, 'archivo_reinicio')) {
                    $blueprint->string('archivo_reinicio')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['especialista_ejecucions', 'especialista_consultorias'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (Schema::hasColumn($table, 'archivo_reinicio')) {
                    $blueprint->dropColumn('archivo_reinicio');
                }
                if (Schema::hasColumn($table, 'archivo_suspension')) {
                    $blueprint->dropColumn('archivo_suspension');
                }
            });
        }
    }
};
