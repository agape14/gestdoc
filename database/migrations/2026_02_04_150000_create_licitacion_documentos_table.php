<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Documentos adjuntos de licitación: nombre + archivo.
     */
    public function up(): void
    {
        Schema::create('licitacion_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('licitacion_id')->constrained('licitacions')->onDelete('cascade');
            $table->string('nombre');
            $table->string('file_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licitacion_documentos');
    }
};
