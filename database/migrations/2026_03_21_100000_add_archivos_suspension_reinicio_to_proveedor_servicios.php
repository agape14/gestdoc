<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('proveedor_servicios')) {
            Schema::table('proveedor_servicios', function (Blueprint $table) {
                if (!Schema::hasColumn('proveedor_servicios', 'archivo_suspension')) {
                    $table->string('archivo_suspension', 500)->nullable()->after('archivo_conformidad_servicio');
                }
                if (!Schema::hasColumn('proveedor_servicios', 'archivo_reinicio')) {
                    $table->string('archivo_reinicio', 500)->nullable()->after('archivo_suspension');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('proveedor_servicios')) {
            Schema::table('proveedor_servicios', function (Blueprint $table) {
                if (Schema::hasColumn('proveedor_servicios', 'archivo_reinicio')) {
                    $table->dropColumn('archivo_reinicio');
                }
                if (Schema::hasColumn('proveedor_servicios', 'archivo_suspension')) {
                    $table->dropColumn('archivo_suspension');
                }
            });
        }
    }
};
