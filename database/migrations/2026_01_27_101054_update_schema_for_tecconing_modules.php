<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update Licitacions Table
        if (Schema::hasTable('licitacions')) {
            Schema::table('licitacions', function (Blueprint $table) {
                $table->string('tipo')->nullable(); // Publica / Privada
                $table->string('especialidad')->nullable();
                $table->string('bases_integradas')->nullable();
                $table->string('propuesta_economica')->nullable();
                $table->string('propuesta_tecnica')->nullable();
                $table->string('modalidad')->nullable();
                $table->boolean('consorcio')->default(false);
                $table->string('nombre_rc')->nullable();
                $table->string('nombre_consorcio')->nullable();
                $table->json('consorciados')->nullable();
                $table->string('contrato_archivo')->nullable();
                $table->string('promesa_consorcio')->nullable();
            });
        }

        // Create Consultor Obras Table
        Schema::create('consultor_obras', function (Blueprint $table) {
            $table->id();
            $table->string('titulo'); // Proyecto
            $table->string('entidad');
            $table->string('especialidad')->nullable();
            $table->string('tipo_servicio')->nullable(); // Evaluacion, Expediente, etc
            $table->decimal('presupuesto', 15, 2)->nullable();
            $table->string('estado')->default('En Curso');
            $table->string('duracion')->nullable();
            $table->string('modalidad')->nullable();
            $table->string('contrato_archivo')->nullable();
            $table->string('tdr_archivo')->nullable();
            $table->string('personal_clave')->nullable(); // Image path
            $table->json('producto_tecnico')->nullable(); // Files
            $table->string('actas_resoluciones')->nullable();
            $table->string('conformidad_tecnica')->nullable();
            $table->string('categoria')->default('Privada'); // Publica / Privada
            $table->timestamps();
        });

        // Create Ejecutor Obras Table
        Schema::create('ejecutor_obras', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('entidad');
            $table->string('especialidad')->nullable();
            $table->string('tipo_obra')->nullable();
            $table->decimal('presupuesto', 15, 2)->nullable();
            $table->string('estado')->default('En Curso');
            $table->string('modalidad')->nullable();
            $table->string('contrato_archivo')->nullable();
            $table->string('tdr_archivo')->nullable();
            $table->string('plazo_ejecucion')->nullable();
            $table->string('tiempo_culminacion')->nullable();
            $table->string('plantel_tecnico')->nullable();
            $table->json('valorizaciones')->nullable();
            $table->json('informes_tecnicos')->nullable();
            $table->json('cargos')->nullable();
            $table->string('liquidacion')->nullable();
            $table->string('panel_fotografico')->nullable();
            $table->string('expediente_tecnico')->nullable();
            $table->string('actas_resoluciones')->nullable();
            $table->string('conformidad_tecnica')->nullable();
            $table->string('categoria')->default('Privada'); // Publica / Privada
            $table->timestamps();
        });

        // Create Proveedor Servicios Table
        Schema::create('proveedor_servicios', function (Blueprint $table) {
            $table->id();
            $table->string('titulo'); // Servicio
            $table->string('entidad');
            $table->string('especialidad')->nullable();
            $table->string('tipo_servicio')->nullable();
            $table->string('modalidad')->nullable();
            $table->decimal('presupuesto', 15, 2)->nullable();
            $table->string('estado')->default('En Curso');
            $table->string('duracion')->nullable();
            $table->string('contrato_archivo')->nullable();
            $table->string('tdr_archivo')->nullable();
            $table->boolean('plantel_tecnico_aplica')->default(true); // "corresponde"
            $table->boolean('valorizaciones_aplica')->default(true);
            $table->string('informes_tecnicos')->nullable();
            $table->json('cargos')->nullable();
            $table->boolean('liquidacion_aplica')->default(true);
            $table->string('actas_resoluciones')->nullable();
            $table->string('conformidad_tecnica')->nullable();
            $table->string('plazo_ejecucion')->nullable();
            $table->string('tiempo_culminacion')->nullable();
            $table->string('panel_fotografico')->nullable();
            $table->string('categoria')->default('Privada');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedor_servicios');
        Schema::dropIfExists('ejecutor_obras');
        Schema::dropIfExists('consultor_obras');

        if (Schema::hasTable('licitacions')) {
            Schema::table('licitacions', function (Blueprint $table) {
                $table->dropColumn([
                    'tipo', 'especialidad', 'bases_integradas', 'propuesta_economica', 
                    'propuesta_tecnica', 'modalidad', 'consorcio', 'nombre_rc', 
                    'nombre_consorcio', 'consorciados', 'contrato_archivo', 'promesa_consorcio'
                ]);
            });
        }
    }
};
