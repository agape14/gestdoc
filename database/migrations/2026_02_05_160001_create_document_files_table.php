<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Archivos PDF (varios por documento) con nombre para mostrar.
     */
    public function up(): void
    {
        Schema::create('document_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->string('nombre_archivo', 255)->comment('Nombre para mostrar (ej. Carta 001, Anexo)');
            $table->string('path')->comment('Ruta en storage');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->index('document_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_files');
    }
};
