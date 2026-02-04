<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade columnas faltantes en consultor_obras (p. ej. si la tabla se creó con otra migración).
     */
    public function up(): void
    {
        if (!Schema::hasTable('consultor_obras')) {
            return;
        }

        $columns = [
            'modalidad' => fn (Blueprint $table) => $table->string('modalidad')->nullable(),
            'duracion' => fn (Blueprint $table) => $table->string('duracion')->nullable(),
            'tipo_servicio' => fn (Blueprint $table) => $table->string('tipo_servicio')->nullable(),
            'presupuesto' => fn (Blueprint $table) => $table->decimal('presupuesto', 15, 2)->nullable(),
            'estado' => fn (Blueprint $table) => $table->string('estado')->default('En Curso'),
            'contrato_archivo' => fn (Blueprint $table) => $table->string('contrato_archivo')->nullable(),
            'tdr_archivo' => fn (Blueprint $table) => $table->string('tdr_archivo')->nullable(),
            'personal_clave' => fn (Blueprint $table) => $table->string('personal_clave')->nullable(),
            'producto_tecnico' => fn (Blueprint $table) => $table->json('producto_tecnico')->nullable(),
            'actas_resoluciones' => fn (Blueprint $table) => $table->string('actas_resoluciones')->nullable(),
            'conformidad_tecnica' => fn (Blueprint $table) => $table->string('conformidad_tecnica')->nullable(),
            'categoria' => fn (Blueprint $table) => $table->string('categoria')->default('Privada'),
        ];

        foreach ($columns as $column => $callback) {
            if (!Schema::hasColumn('consultor_obras', $column)) {
                Schema::table('consultor_obras', $callback);
            }
        }
    }

    public function down(): void
    {
        // No drop columns in down to avoid data loss; migration is additive.
    }
};
