<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Documentos de gestión documental (cartas, oficios, memos) por carpeta/tipo.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('folders')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('numero', 100)->nullable()->comment('Número de documento');
            $table->date('fecha_documento')->nullable();
            $table->string('asunto', 500)->nullable();
            $table->string('remitente', 255)->nullable();
            $table->string('destinatario', 255)->nullable();
            $table->string('referencia', 255)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['folder_id', 'fecha_documento']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
