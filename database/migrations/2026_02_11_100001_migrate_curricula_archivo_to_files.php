<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('curricula')->whereNotNull('archivo_cv')->where('archivo_cv', '!=', '')->get();
        foreach ($rows as $row) {
            DB::table('curriculum_files')->insert([
                'curriculum_id' => $row->id,
                'nombre_archivo' => $row->nombre_candidato ?: 'CV',
                'path' => $row->archivo_cv,
                'orden' => 0,
                'created_at' => $row->updated_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ]);
        }
    }

    public function down(): void
    {
        // No revert - curriculum_files would be dropped by previous migration
    }
};
