<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('licitacions') && !Schema::hasColumn('licitacions', 'promesa_consorcio')) {
            Schema::table('licitacions', function (Blueprint $table) {
                $table->string('promesa_consorcio')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('licitacions') && Schema::hasColumn('licitacions', 'promesa_consorcio')) {
            Schema::table('licitacions', fn (Blueprint $b) => $b->dropColumn('promesa_consorcio'));
        }
    }
};
