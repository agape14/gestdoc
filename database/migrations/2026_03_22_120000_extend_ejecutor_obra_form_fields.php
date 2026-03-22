<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ejecutor_obra_documentos') && !Schema::hasColumn('ejecutor_obra_documentos', 'tipo')) {
            Schema::table('ejecutor_obra_documentos', function (Blueprint $table) {
                $table->string('tipo', 50)->nullable()->after('file_path');
            });
        }

        if (!Schema::hasTable('ejecutor_obras')) {
            return;
        }

        Schema::table('ejecutor_obras', function (Blueprint $table) {
            if (!Schema::hasColumn('ejecutor_obras', 'tiene_adicional_obra')) {
                $table->string('tiene_adicional_obra', 2)->nullable()->after('fecha_inicio');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'tiene_deductivo_obra')) {
                $table->string('tiene_deductivo_obra', 2)->nullable()->after('tiene_adicional_obra');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'fecha_adicional_obra')) {
                $table->date('fecha_adicional_obra')->nullable()->after('tiene_deductivo_obra');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'archivo_acta_adicional')) {
                $table->string('archivo_acta_adicional')->nullable()->after('fecha_adicional_obra');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'monto_adicional')) {
                $table->decimal('monto_adicional', 15, 2)->nullable()->after('archivo_acta_adicional');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'plazo_adicional')) {
                $table->unsignedInteger('plazo_adicional')->nullable()->after('monto_adicional');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'fecha_deductivo_obra')) {
                $table->date('fecha_deductivo_obra')->nullable()->after('plazo_adicional');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'archivo_acta_deductivo')) {
                $table->string('archivo_acta_deductivo')->nullable()->after('fecha_deductivo_obra');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'monto_deductivo')) {
                $table->decimal('monto_deductivo', 15, 2)->nullable()->after('archivo_acta_deductivo');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'plazo_deductivo')) {
                $table->unsignedInteger('plazo_deductivo')->nullable()->after('monto_deductivo');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'tiene_aprobacion_acto_resolutivo')) {
                $table->string('tiene_aprobacion_acto_resolutivo', 2)->nullable()->after('plazo_deductivo');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'fecha_aprobacion_acto_resolutivo')) {
                $table->date('fecha_aprobacion_acto_resolutivo')->nullable()->after('tiene_aprobacion_acto_resolutivo');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'archivo_aprobacion_acto_resolutivo')) {
                $table->string('archivo_aprobacion_acto_resolutivo')->nullable()->after('fecha_aprobacion_acto_resolutivo');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'monto_aprobacion_acto_resolutivo')) {
                $table->decimal('monto_aprobacion_acto_resolutivo', 15, 2)->nullable()->after('archivo_aprobacion_acto_resolutivo');
            }
            if (!Schema::hasColumn('ejecutor_obras', 'plazo_aprobacion_acto_resolutivo')) {
                $table->unsignedInteger('plazo_aprobacion_acto_resolutivo')->nullable()->after('monto_aprobacion_acto_resolutivo');
            }
        });

        // Unificar datos: FECHA DE RECEPCION antigua -> FECHA DE LA RECEPCION DE OBRA si falta
        if (Schema::hasColumn('ejecutor_obras', 'fecha_recepcion') && Schema::hasColumn('ejecutor_obras', 'fecha_recepcion_obra')) {
            DB::table('ejecutor_obras')
                ->whereNotNull('fecha_recepcion')
                ->whereNull('fecha_recepcion_obra')
                ->update(['fecha_recepcion_obra' => DB::raw('fecha_recepcion')]);
        }

        // Pasar resolución de liquidación (archivo único) a documentos con tipo liquidacion
        if (Schema::hasTable('ejecutor_obra_documentos') && Schema::hasColumn('ejecutor_obras', 'archivo_resolucion_liquidacion')) {
            $rows = DB::table('ejecutor_obras')
                ->whereNotNull('archivo_resolucion_liquidacion')
                ->where('archivo_resolucion_liquidacion', '!=', '')
                ->get(['id', 'archivo_resolucion_liquidacion']);

            foreach ($rows as $row) {
                $exists = DB::table('ejecutor_obra_documentos')
                    ->where('ejecutor_obra_id', $row->id)
                    ->where('tipo', 'liquidacion')
                    ->where('file_path', $row->archivo_resolucion_liquidacion)
                    ->exists();
                if (!$exists) {
                    $now = now();
                    DB::table('ejecutor_obra_documentos')->insert([
                        'ejecutor_obra_id' => $row->id,
                        'nombre' => 'Resolución de liquidación',
                        'file_path' => $row->archivo_resolucion_liquidacion,
                        'tipo' => 'liquidacion',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ejecutor_obra_documentos') && Schema::hasColumn('ejecutor_obra_documentos', 'tipo')) {
            Schema::table('ejecutor_obra_documentos', function (Blueprint $table) {
                $table->dropColumn('tipo');
            });
        }

        if (!Schema::hasTable('ejecutor_obras')) {
            return;
        }

        Schema::table('ejecutor_obras', function (Blueprint $table) {
            foreach ([
                'tiene_adicional_obra', 'tiene_deductivo_obra', 'fecha_adicional_obra', 'archivo_acta_adicional',
                'monto_adicional', 'plazo_adicional', 'fecha_deductivo_obra', 'archivo_acta_deductivo',
                'monto_deductivo', 'plazo_deductivo', 'tiene_aprobacion_acto_resolutivo', 'fecha_aprobacion_acto_resolutivo',
                'archivo_aprobacion_acto_resolutivo', 'monto_aprobacion_acto_resolutivo', 'plazo_aprobacion_acto_resolutivo',
            ] as $col) {
                if (Schema::hasColumn('ejecutor_obras', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
