<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade folder_id a INMOBILIARIA, TOPOGRAFIA, TECNOLOGIA, PLANTILLAS DE ING y BANCO DE CVs.
     */
    public function up(): void
    {
        $withUserId = ['inmobiliarias', 'topografias', 'tecnologias', 'plantilla_ings'];
        foreach ($withUserId as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'folder_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->foreignId('folder_id')->nullable()->after('user_id')->constrained('folders')->nullOnDelete();
                });
            }
        }

        if (Schema::hasTable('curricula') && !Schema::hasColumn('curricula', 'folder_id')) {
            Schema::table('curricula', function (Blueprint $table) {
                $table->foreignId('folder_id')->nullable()->after('id')->constrained('folders')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['inmobiliarias', 'topografias', 'tecnologias', 'plantilla_ings'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'folder_id')) {
                Schema::table($table, fn (Blueprint $b) => $b->dropConstrainedForeignId('folder_id'));
            }
        }
        if (Schema::hasTable('curricula') && Schema::hasColumn('curricula', 'folder_id')) {
            Schema::table('curricula', fn (Blueprint $b) => $b->dropConstrainedForeignId('folder_id'));
        }
    }
};
