<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade campos del Excel/Consultoría de Obras: cliente, objeto contrato, CUI,
     * número contrato/O/S/CP, fechas, moneda, importe, consorciado, % participación, etc.
     */
    public function up(): void
    {
        if (!Schema::hasTable('consultor_obras')) {
            return;
        }

        Schema::table('consultor_obras', function (Blueprint $table) {
            if (!Schema::hasColumn('consultor_obras', 'objeto_contrato')) {
                $table->string('objeto_contrato', 500)->nullable();
            }
            if (!Schema::hasColumn('consultor_obras', 'cui')) {
                $table->string('cui', 50)->nullable();
            }
            if (!Schema::hasColumn('consultor_obras', 'numero_contrato_os_comprobante')) {
                $table->string('numero_contrato_os_comprobante', 100)->nullable();
            }
            if (!Schema::hasColumn('consultor_obras', 'fecha_contrato_cp')) {
                $table->date('fecha_contrato_cp')->nullable();
            }
            if (!Schema::hasColumn('consultor_obras', 'fecha_conformidad')) {
                $table->date('fecha_conformidad')->nullable();
            }
            if (!Schema::hasColumn('consultor_obras', 'experiencia_proveniente_de')) {
                $table->string('experiencia_proveniente_de', 255)->nullable();
            }
            if (!Schema::hasColumn('consultor_obras', 'moneda')) {
                $table->string('moneda', 20)->nullable();
            }
            if (!Schema::hasColumn('consultor_obras', 'monto_contratado')) {
                $table->decimal('monto_contratado', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('consultor_obras', 'consorciado')) {
                $table->boolean('consorciado')->default(false);
            }
            if (!Schema::hasColumn('consultor_obras', 'porcentaje_participacion')) {
                $table->decimal('porcentaje_participacion', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('consultor_obras', 'importe')) {
                $table->decimal('importe', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('consultor_obras', 'tipo_cambio_venta')) {
                $table->decimal('tipo_cambio_venta', 12, 4)->nullable();
            }
            if (!Schema::hasColumn('consultor_obras', 'monto_facturado_acumulado')) {
                $table->decimal('monto_facturado_acumulado', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('consultor_obras', 'numero_resolucion')) {
                $table->string('numero_resolucion', 50)->nullable();
            }
            if (!Schema::hasColumn('consultor_obras', 'fecha_aprobacion')) {
                $table->date('fecha_aprobacion')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('consultor_obras')) {
            return;
        }
        Schema::table('consultor_obras', function (Blueprint $table) {
            $drop = [
                'objeto_contrato', 'cui', 'numero_contrato_os_comprobante',
                'fecha_contrato_cp', 'fecha_conformidad', 'experiencia_proveniente_de',
                'moneda', 'monto_contratado', 'consorciado', 'porcentaje_participacion',
                'importe', 'tipo_cambio_venta', 'monto_facturado_acumulado',
                'numero_resolucion', 'fecha_aprobacion',
            ];
            foreach ($drop as $col) {
                if (Schema::hasColumn('consultor_obras', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
