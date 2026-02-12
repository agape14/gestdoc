<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained('curricula')->onDelete('cascade');
            $table->string('nombre_archivo', 255)->comment('Nombre para mostrar (ej. CV Principal, Anexo)');
            $table->string('path')->comment('Ruta en storage');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
            $table->index('curriculum_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_files');
    }
};
