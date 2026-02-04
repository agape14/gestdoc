<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tablesWithClasificacion = [
            'consultor_obras', 'ejecutor_obras', 'proveedor_servicios',
            'proveedor_biens', 'especialista_ejecucions', 'especialista_consultorias',
        ];
        foreach ($tablesWithClasificacion as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'clasificacion')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->string('clasificacion', 500)->nullable()->after('folder_id');
                });
            }
        }

        if (Schema::hasTable('consultor_obras') && !Schema::hasTable('consultor_obra_documentos')) {
            Schema::create('consultor_obra_documentos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('consultor_obra_id')->constrained('consultor_obras')->onDelete('cascade');
                $table->string('nombre');
                $table->string('file_path');
                $table->timestamps();
            });
        }
        if (Schema::hasTable('ejecutor_obras') && !Schema::hasTable('ejecutor_obra_documentos')) {
            Schema::create('ejecutor_obra_documentos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ejecutor_obra_id')->constrained('ejecutor_obras')->onDelete('cascade');
                $table->string('nombre');
                $table->string('file_path');
                $table->timestamps();
            });
        }
        if (Schema::hasTable('proveedor_servicios') && !Schema::hasTable('proveedor_servicio_documentos')) {
            Schema::create('proveedor_servicio_documentos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('proveedor_servicio_id')->constrained('proveedor_servicios')->onDelete('cascade');
                $table->string('nombre');
                $table->string('file_path');
                $table->timestamps();
            });
        }
        if (Schema::hasTable('proveedor_biens') && !Schema::hasTable('proveedor_bien_documentos')) {
            Schema::create('proveedor_bien_documentos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('proveedor_bien_id')->constrained('proveedor_biens')->onDelete('cascade');
                $table->string('nombre');
                $table->string('file_path');
                $table->timestamps();
            });
        }
        if (Schema::hasTable('especialista_ejecucions') && !Schema::hasTable('especialista_ejecucion_documentos')) {
            Schema::create('especialista_ejecucion_documentos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('especialista_ejecucion_id')->constrained('especialista_ejecucions')->onDelete('cascade');
                $table->string('nombre');
                $table->string('file_path');
                $table->timestamps();
            });
        }
        if (Schema::hasTable('especialista_consultorias') && !Schema::hasTable('especialista_consultoria_documentos')) {
            Schema::create('especialista_consultoria_documentos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('especialista_consultoria_id')->constrained('especialista_consultorias')->onDelete('cascade');
                $table->string('nombre');
                $table->string('file_path');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach (['especialista_consultoria_documentos', 'especialista_ejecucion_documentos', 'proveedor_bien_documentos', 'proveedor_servicio_documentos', 'ejecutor_obra_documentos', 'consultor_obra_documentos'] as $table) {
            Schema::dropIfExists($table);
        }
        $tablesWithClasificacion = [
            'consultor_obras', 'ejecutor_obras', 'proveedor_servicios',
            'proveedor_biens', 'especialista_ejecucions', 'especialista_consultorias',
        ];
        foreach ($tablesWithClasificacion as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'clasificacion')) {
                Schema::table($table, fn (Blueprint $b) => $b->dropColumn('clasificacion'));
            }
        }
    }
};
