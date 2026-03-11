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
        Schema::create('registro_expedientes', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_inversion', 255)->nullable();
            $table->string('numero', 50)->nullable();
            $table->string('etiqueta', 50)->nullable();
            $table->text('proyecto')->nullable();
            $table->string('cui', 50)->nullable();
            $table->string('descripcion', 500)->nullable();
            $table->string('numero_folio', 100)->nullable();
            $table->string('tomos', 100)->nullable();
            $table->unsignedSmallInteger('anio')->nullable();
            $table->string('tipo_unidad_conservacion', 255)->nullable();
            $table->string('resolucion', 100)->nullable();
            $table->date('fecha_aprobacion')->nullable();
            $table->decimal('monto_o', 15, 2)->nullable();
            $table->decimal('monto_p', 15, 2)->nullable();
            $table->decimal('monto_r', 15, 2)->nullable();
            $table->decimal('monto_s', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_expedientes');
    }
};
