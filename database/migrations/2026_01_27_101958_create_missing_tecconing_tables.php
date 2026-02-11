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
        // Proveedor Bienes
        Schema::create('proveedor_biens', function (Blueprint $table) {
            $table->id();
            $table->string('titulo'); // Item/Bien
            $table->string('entidad')->nullable();
            $table->string('categoria')->default('Privada'); // Publica / Privada
            $table->string('estado')->default('En Stock');
            $table->decimal('costo', 15, 2)->nullable();
            $table->timestamps();
        });

        // Especialista Ejecucion (Profesionales / Empresas)
        Schema::create('especialista_ejecucions', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Nombre o Razon Social
            $table->string('especialidad')->nullable();
            $table->string('tipo')->default('Profesional'); // Profesional / Empresa
            $table->string('documento')->nullable(); // CV or Brochure
            $table->string('estado')->default('Activo');
            $table->timestamps();
        });

        // Especialista Consultoria (Profesionales / Empresas)
        Schema::create('especialista_consultorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); 
            $table->string('especialidad')->nullable();
            $table->string('tipo')->default('Profesional'); // Profesional / Empresa
            $table->string('documento')->nullable();
            $table->string('estado')->default('Activo');
            $table->timestamps();
        });

        // Inmobiliaria
        Schema::create('inmobiliarias', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('ubicacion')->nullable();
            $table->decimal('precio', 15, 2)->nullable();
            $table->string('estado')->default('Disponible');
            $table->string('imagen')->nullable();
            $table->timestamps();
        });

        // Topografia
        Schema::create('topografias', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('archivo')->nullable();
            $table->timestamps();
        });

        // Tecnologia
        Schema::create('tecnologias', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('archivo')->nullable();
            $table->timestamps();
        });

        // Plantillas Ing
        Schema::create('plantilla_ings', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('especialidad')->nullable();
            $table->string('archivo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantilla_ings');
        Schema::dropIfExists('tecnologias');
        Schema::dropIfExists('topografias');
        Schema::dropIfExists('inmobiliarias');
        Schema::dropIfExists('especialista_consultorias');
        Schema::dropIfExists('especialista_ejecucions');
        Schema::dropIfExists('proveedor_biens');
    }
};
