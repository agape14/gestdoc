<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('curricula') && !Schema::hasColumn('curricula', 'anulado')) {
            Schema::table('curricula', function (Blueprint $table) {
                $table->boolean('anulado')->default(false)->after('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('curricula') && Schema::hasColumn('curricula', 'anulado')) {
            Schema::table('curricula', fn (Blueprint $table) => $table->dropColumn('anulado'));
        }
    }
};
