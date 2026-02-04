<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Compartir registros entre operadores: un operador comparte un registro con otro por un tiempo; el receptor solo puede ver (no editar).
     */
    public function up(): void
    {
        Schema::create('record_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // quien comparte
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete(); // con quien se comparte
            $table->string('shareable_type', 100); // ej: App\Models\ProveedorServicio
            $table->unsignedBigInteger('shareable_id');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('can_edit')->default(false);
            $table->timestamps();
            $table->unique(['target_user_id', 'shareable_type', 'shareable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_shares');
    }
};
