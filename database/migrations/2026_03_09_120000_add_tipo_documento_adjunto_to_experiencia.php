<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['especialista_ejecucions', 'especialista_consultorias', 'proveedor_servicios', 'proveedor_biens'];
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'tipo_documento_adjunto')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->string('tipo_documento_adjunto', 50)->nullable()->after('archivo_conformidad_servicio');
                });
            }
        }
    }

    public function down(): void
    {
        $tables = ['especialista_ejecucions', 'especialista_consultorias', 'proveedor_servicios', 'proveedor_biens'];
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'tipo_documento_adjunto')) {
                Schema::table($table, fn (Blueprint $b) => $b->dropColumn('tipo_documento_adjunto'));
            }
        }
    }
};
