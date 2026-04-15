<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('proveedor_biens')) {
            Schema::table('proveedor_biens', function (Blueprint $table) {
                if (! Schema::hasColumn('proveedor_biens', 'fecha_contrato_cp')) {
                    $table->date('fecha_contrato_cp')->nullable()->after('numero_contrato_oc_comprobante');
                }
                if (! Schema::hasColumn('proveedor_biens', 'estado_contrato')) {
                    $table->string('estado_contrato', 32)->nullable()->after('fecha_contrato_cp');
                }
            });
        }
        if (Schema::hasTable('proveedor_servicios')) {
            Schema::table('proveedor_servicios', function (Blueprint $table) {
                if (! Schema::hasColumn('proveedor_servicios', 'fecha_contrato_cp')) {
                    $table->date('fecha_contrato_cp')->nullable()->after('numero_contrato_os_comprobante');
                }
                if (! Schema::hasColumn('proveedor_servicios', 'estado_contrato')) {
                    $table->string('estado_contrato', 32)->nullable()->after('fecha_contrato_cp');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('proveedor_biens')) {
            Schema::table('proveedor_biens', function (Blueprint $table) {
                if (Schema::hasColumn('proveedor_biens', 'estado_contrato')) {
                    $table->dropColumn('estado_contrato');
                }
                if (Schema::hasColumn('proveedor_biens', 'fecha_contrato_cp')) {
                    $table->dropColumn('fecha_contrato_cp');
                }
            });
        }
        if (Schema::hasTable('proveedor_servicios')) {
            Schema::table('proveedor_servicios', function (Blueprint $table) {
                if (Schema::hasColumn('proveedor_servicios', 'estado_contrato')) {
                    $table->dropColumn('estado_contrato');
                }
                if (Schema::hasColumn('proveedor_servicios', 'fecha_contrato_cp')) {
                    $table->dropColumn('fecha_contrato_cp');
                }
            });
        }
    }
};
