<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Permite referencias largas (ej. muchas líneas o 15+ archivos citados).
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE documents MODIFY referencia TEXT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE documents MODIFY referencia VARCHAR(255) NULL');
    }
};
