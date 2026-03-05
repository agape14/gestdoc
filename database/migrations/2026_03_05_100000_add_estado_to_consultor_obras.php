<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultor_obras', function (Blueprint $table) {
            $table->string('estado_registro', 20)->default('activo')->after('anulado');
        });

        DB::table('consultor_obras')->where('anulado', true)->update(['estado_registro' => 'anulado']);
        DB::table('consultor_obras')->where('anulado', false)->update(['estado_registro' => 'activo']);
    }

    public function down(): void
    {
        Schema::table('consultor_obras', function (Blueprint $table) {
            $table->dropColumn('estado_registro');
        });
    }
};
