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
        Schema::create('licitacions', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('entidad');
            $table->decimal('presupuesto', 15, 2);
            $table->string('estado')->default('En Curso');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licitacions');
    }
};
