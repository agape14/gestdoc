<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campos del módulo EJECUTOR DE OBRA según especificación.
     */
    public function up(): void
    {
        Schema::table('ejecutor_obras', function (Blueprint $table) {
            if (!Schema::hasColumn('ejecutor_obras', 'nombre_sigla_entidad')) {
                $table->string('nombre_sigla_entidad', 255)->nullable()->after('id');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'nomenclatura')) {
                $table->string('nomenclatura', 255)->nullable()->after('nombre_sigla_entidad');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'descripcion_objeto')) {
                $table->text('descripcion_objeto')->nullable()->after('nomenclatura');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'cui')) {
                $table->string('cui', 50)->nullable()->after('descripcion_objeto');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'numero_contrato')) {
                $table->string('numero_contrato', 100)->nullable()->after('cui');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'fecha_firma_contrato')) {
                $table->date('fecha_firma_contrato')->nullable()->after('numero_contrato');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'monto_total')) {
                $table->decimal('monto_total', 15, 2)->nullable()->after('fecha_firma_contrato');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'fecha_recepcion')) {
                $table->date('fecha_recepcion')->nullable()->after('monto_total');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'plazo')) {
                $table->integer('plazo')->nullable()->after('fecha_recepcion');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'fecha_inicio')) {
                $table->date('fecha_inicio')->nullable()->after('plazo');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'fecha_suspension')) {
                $table->date('fecha_suspension')->nullable()->after('fecha_inicio');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'fecha_reinicio')) {
                $table->date('fecha_reinicio')->nullable()->after('fecha_suspension');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'fecha_final')) {
                $table->date('fecha_final')->nullable()->after('fecha_reinicio');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'porcentaje_participacion')) {
                $table->decimal('porcentaje_participacion', 5, 2)->nullable()->after('fecha_final');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'monto_neto')) {
                $table->decimal('monto_neto', 15, 2)->nullable()->after('porcentaje_participacion');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'monto_acumulado')) {
                $table->decimal('monto_acumulado', 15, 2)->nullable()->after('monto_neto');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'liquidado_recepcionado')) {
                $table->boolean('liquidado_recepcionado')->default(false)->after('monto_acumulado');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'fecha_entrega_terreno')) {
                $table->date('fecha_entrega_terreno')->nullable()->after('liquidado_recepcionado');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'fecha_recepcion_obra')) {
                $table->date('fecha_recepcion_obra')->nullable()->after('fecha_entrega_terreno');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'fecha_aprobacion_liquidacion')) {
                $table->date('fecha_aprobacion_liquidacion')->nullable()->after('fecha_recepcion_obra');
            }
            // Archivos PDF
            if (!Schema::hasColumn('ejecutor_obras', 'archivo_contrato')) {
                $table->string('archivo_contrato')->nullable()->after('fecha_aprobacion_liquidacion');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'archivo_acta_recepcion')) {
                $table->string('archivo_acta_recepcion')->nullable()->after('archivo_contrato');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'archivo_acta_inicio')) {
                $table->string('archivo_acta_inicio')->nullable()->after('archivo_acta_recepcion');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'archivo_acta_suspension')) {
                $table->string('archivo_acta_suspension')->nullable()->after('archivo_acta_inicio');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'archivo_acta_reinicio')) {
                $table->string('archivo_acta_reinicio')->nullable()->after('archivo_acta_suspension');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'archivo_acta_entrega_terreno')) {
                $table->string('archivo_acta_entrega_terreno')->nullable()->after('archivo_acta_reinicio');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'archivo_resolucion_liquidacion')) {
                $table->string('archivo_resolucion_liquidacion')->nullable()->after('archivo_acta_entrega_terreno');
            }
        });

        // Índice único para CUI
        Schema::table('ejecutor_obras', function (Blueprint $table) {
            if (Schema::hasColumn('ejecutor_obras', 'cui')) {
                $table->unique('cui');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ejecutor_obras', function (Blueprint $table) {
            if (Schema::hasColumn('ejecutor_obras', 'cui')) {
                $table->dropUnique(['cui']);
            }
        });
        Schema::table('ejecutor_obras', function (Blueprint $table) {
            $cols = [
                'nombre_sigla_entidad', 'nomenclatura', 'descripcion_objeto', 'cui', 'numero_contrato',
                'fecha_firma_contrato', 'monto_total', 'fecha_recepcion', 'plazo', 'fecha_inicio',
                'fecha_suspension', 'fecha_reinicio', 'fecha_final', 'porcentaje_participacion',
                'monto_neto', 'monto_acumulado', 'liquidado_recepcionado', 'fecha_entrega_terreno',
                'fecha_recepcion_obra', 'fecha_aprobacion_liquidacion',
                'archivo_contrato', 'archivo_acta_recepcion', 'archivo_acta_inicio',
                'archivo_acta_suspension', 'archivo_acta_reinicio', 'archivo_acta_entrega_terreno',
                'archivo_resolucion_liquidacion',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('ejecutor_obras', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
