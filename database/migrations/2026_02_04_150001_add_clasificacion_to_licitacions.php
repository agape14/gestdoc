<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clasificación según carpeta (miga de pan).
     */
    public function up(): void
    {
        Schema::table('licitacions', function (Blueprint $table) {
            $table->string('clasificacion', 500)->nullable()->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('licitacions', function (Blueprint $table) {
            $table->dropColumn('clasificacion');
        });
    }
};
