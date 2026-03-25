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
        Schema::create('municipalidad_funcionario_publicos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->nullable();
            $table->string('especialidad')->nullable();
            $table->string('tipo')->nullable();
            $table->string('documento')->nullable();
            $table->string('estado')->default('EN CURSO');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('anulado')->default(false);
            $table->foreignId('folder_id')->nullable()->constrained('folders')->nullOnDelete();
            $table->string('clasificacion')->nullable();

            $table->string('cliente')->nullable();
            $table->text('objeto_del_contrato')->nullable();
            $table->string('cui')->nullable();
            $table->string('numero_contrato_os_comprobante')->nullable();
            $table->date('fecha_contrato_cp')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_suspension')->nullable();
            $table->date('fecha_reinicio')->nullable();
            $table->date('fecha_culminacion')->nullable();
            $table->decimal('total_meses', 10, 2)->nullable();
            $table->unsignedInteger('total_dias')->nullable();
            $table->decimal('traslape', 10, 2)->default(0);
            $table->unsignedInteger('total_dias_sin_traslape')->nullable();
            $table->decimal('monto_neto', 15, 2)->nullable();
            $table->decimal('monto_acumulado', 15, 2)->nullable();
            $table->string('archivo_contrato')->nullable();
            $table->string('archivo_comprobante_pago')->nullable();
            $table->string('archivo_conformidad_servicio')->nullable();
            $table->string('archivo_suspension')->nullable();
            $table->string('archivo_reinicio')->nullable();
            $table->string('tipo_documento_adjunto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('municipalidad_funcionario_publicos');
    }
};
