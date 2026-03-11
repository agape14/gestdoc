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
        Schema::table('registro_expedientes', function (Blueprint $table) {
            $table->foreignId('folder_id')->nullable()->after('id')->constrained('folders')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->after('folder_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registro_expedientes', function (Blueprint $table) {
            $table->dropForeign(['folder_id']);
            $table->dropForeign(['user_id']);
        });
    }
};
