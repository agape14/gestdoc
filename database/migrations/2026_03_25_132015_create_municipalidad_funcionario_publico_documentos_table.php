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
        if (Schema::hasTable('municipalidad_funcionario_publico_documentos')) {
            return;
        }
        Schema::create('municipalidad_funcionario_publico_documentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('municipalidad_funcionario_publico_id');
            $table->string('nombre');
            $table->string('file_path');
            $table->timestamps();

            $table->foreign('municipalidad_funcionario_publico_id', 'mfpd_mfpid_fk')
                ->references('id')
                ->on('municipalidad_funcionario_publicos')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('municipalidad_funcionario_publico_documentos');
    }
};
