<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('curricula') && !Schema::hasColumn('curricula', 'user_id')) {
            Schema::table('curricula', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
                $table->index('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('curricula') && Schema::hasColumn('curricula', 'user_id')) {
            Schema::table('curricula', fn (Blueprint $table) => $table->dropConstrainedForeignId('user_id'));
        }
    }
};
