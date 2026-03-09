<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Experiencia en la especialidad: campos para Especialistas (Estructura 1),
     * Proveedor Servicios (Estructura 2) y Proveedor Bienes (Estructura 3).
     */
    public function up(): void
    {
        $expColsEstructura1 = function (Blueprint $table) {
            $table->string('cliente', 500)->nullable()->after('clasificacion');
            $table->text('objeto_del_contrato')->nullable()->after('cliente');
            $table->string('cui', 100)->nullable()->after('objeto_del_contrato');
            $table->string('numero_contrato_os_comprobante', 255)->nullable()->after('cui');
            $table->date('fecha_inicio')->nullable()->after('numero_contrato_os_comprobante');
            $table->date('fecha_suspension')->nullable()->after('fecha_inicio');
            $table->date('fecha_reinicio')->nullable()->after('fecha_suspension');
            $table->date('fecha_culminacion')->nullable()->after('fecha_reinicio');
            $table->decimal('total_meses', 10, 2)->nullable()->after('fecha_culminacion');
            $table->unsignedInteger('total_dias')->nullable()->after('total_meses');
            $table->decimal('traslape', 10, 2)->default(0)->after('total_dias');
            $table->unsignedInteger('total_dias_sin_traslape')->nullable()->after('traslape');
            $table->decimal('monto_neto', 15, 2)->nullable()->after('total_dias_sin_traslape');
            $table->decimal('monto_acumulado', 15, 2)->nullable()->after('monto_neto');
            $table->string('archivo_contrato')->nullable()->after('monto_acumulado');
            $table->string('archivo_comprobante_pago')->nullable()->after('archivo_contrato');
            $table->string('archivo_conformidad_servicio')->nullable()->after('archivo_comprobante_pago');
        };

        $expColsEstructura2 = function (Blueprint $table) {
            $table->string('cliente', 500)->nullable()->after('clasificacion');
            $table->text('objeto_del_contrato')->nullable()->after('cliente');
            $table->string('numero_contrato_os_comprobante', 255)->nullable()->after('objeto_del_contrato');
            $table->date('fecha_inicio')->nullable()->after('numero_contrato_os_comprobante');
            $table->date('fecha_suspension')->nullable()->after('fecha_inicio');
            $table->date('fecha_reinicio')->nullable()->after('fecha_suspension');
            $table->date('fecha_culminacion')->nullable()->after('fecha_reinicio');
            $table->decimal('total_meses', 10, 2)->nullable()->after('fecha_culminacion');
            $table->unsignedInteger('total_dias')->nullable()->after('total_meses');
            $table->decimal('traslape', 10, 2)->default(0)->after('total_dias');
            $table->unsignedInteger('total_dias_sin_traslape')->nullable()->after('traslape');
            $table->decimal('monto_neto', 15, 2)->nullable()->after('total_dias_sin_traslape');
            $table->decimal('monto_acumulado', 15, 2)->nullable()->after('monto_neto');
            $table->string('archivo_contrato')->nullable()->after('monto_acumulado');
            $table->string('archivo_comprobante_pago')->nullable()->after('archivo_contrato');
            $table->string('archivo_conformidad_servicio')->nullable()->after('archivo_comprobante_pago');
        };

        $expColsEstructura3 = function (Blueprint $table) {
            $table->string('cliente', 500)->nullable()->after('clasificacion');
            $table->text('objeto_del_contrato')->nullable()->after('cliente');
            $table->string('numero_contrato_oc_comprobante', 255)->nullable()->after('objeto_del_contrato');
            $table->date('fecha_inicio')->nullable()->after('numero_contrato_oc_comprobante');
            $table->date('fecha_culminacion')->nullable()->after('fecha_inicio');
            $table->decimal('total_meses', 10, 2)->nullable()->after('fecha_culminacion');
            $table->unsignedInteger('total_dias')->nullable()->after('total_meses');
            $table->decimal('traslape', 10, 2)->default(0)->after('total_dias');
            $table->unsignedInteger('total_dias_sin_traslape')->nullable()->after('traslape');
            $table->decimal('monto_neto', 15, 2)->nullable()->after('total_dias_sin_traslape');
            $table->decimal('monto_acumulado', 15, 2)->nullable()->after('monto_neto');
            $table->string('archivo_contrato')->nullable()->after('monto_acumulado');
            $table->string('archivo_comprobante_pago')->nullable()->after('archivo_contrato');
            $table->string('archivo_conformidad_servicio')->nullable()->after('archivo_comprobante_pago');
        };

        if (Schema::hasTable('especialista_ejecucions')) {
            Schema::table('especialista_ejecucions', function (Blueprint $table) use ($expColsEstructura1) {
                if (!Schema::hasColumn('especialista_ejecucions', 'cliente')) {
                    $expColsEstructura1($table);
                }
            });
        }

        if (Schema::hasTable('especialista_consultorias')) {
            Schema::table('especialista_consultorias', function (Blueprint $table) use ($expColsEstructura1) {
                if (!Schema::hasColumn('especialista_consultorias', 'cliente')) {
                    $expColsEstructura1($table);
                }
            });
        }

        if (Schema::hasTable('proveedor_servicios')) {
            Schema::table('proveedor_servicios', function (Blueprint $table) use ($expColsEstructura2) {
                if (!Schema::hasColumn('proveedor_servicios', 'cliente')) {
                    $expColsEstructura2($table);
                }
            });
        }

        if (Schema::hasTable('proveedor_biens')) {
            Schema::table('proveedor_biens', function (Blueprint $table) use ($expColsEstructura3) {
                if (!Schema::hasColumn('proveedor_biens', 'cliente')) {
                    $expColsEstructura3($table);
                }
            });
        }
    }

    public function down(): void
    {
        $dropEstructura1 = [
            'cliente', 'objeto_del_contrato', 'cui', 'numero_contrato_os_comprobante',
            'fecha_inicio', 'fecha_suspension', 'fecha_reinicio', 'fecha_culminacion',
            'total_meses', 'total_dias', 'traslape', 'total_dias_sin_traslape',
            'monto_neto', 'monto_acumulado',
            'archivo_contrato', 'archivo_comprobante_pago', 'archivo_conformidad_servicio',
        ];
        $dropEstructura2 = [
            'cliente', 'objeto_del_contrato', 'numero_contrato_os_comprobante',
            'fecha_inicio', 'fecha_suspension', 'fecha_reinicio', 'fecha_culminacion',
            'total_meses', 'total_dias', 'traslape', 'total_dias_sin_traslape',
            'monto_neto', 'monto_acumulado',
            'archivo_contrato', 'archivo_comprobante_pago', 'archivo_conformidad_servicio',
        ];
        $dropEstructura3 = [
            'cliente', 'objeto_del_contrato', 'numero_contrato_oc_comprobante',
            'fecha_inicio', 'fecha_culminacion', 'total_meses', 'total_dias',
            'traslape', 'total_dias_sin_traslape', 'monto_neto', 'monto_acumulado',
            'archivo_contrato', 'archivo_comprobante_pago', 'archivo_conformidad_servicio',
        ];

        if (Schema::hasTable('especialista_ejecucions')) {
            Schema::table('especialista_ejecucions', fn (Blueprint $t) => $t->dropColumn($dropEstructura1));
        }
        if (Schema::hasTable('especialista_consultorias')) {
            Schema::table('especialista_consultorias', fn (Blueprint $t) => $t->dropColumn($dropEstructura1));
        }
        if (Schema::hasTable('proveedor_servicios')) {
            Schema::table('proveedor_servicios', fn (Blueprint $t) => $t->dropColumn($dropEstructura2));
        }
        if (Schema::hasTable('proveedor_biens')) {
            Schema::table('proveedor_biens', fn (Blueprint $t) => $t->dropColumn($dropEstructura3));
        }
    }
};
