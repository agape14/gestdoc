<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade columna anulado (estado) a módulos. Anular no borra de BD.
     */
    public function up(): void
    {
        $tablesWithUserId = [
            'licitacions', 'consultor_obras', 'ejecutor_obras', 'proveedor_servicios',
            'proveedor_biens', 'especialista_ejecucions', 'especialista_consultorias',
            'inmobiliarias', 'topografias', 'tecnologias', 'plantilla_ings',
        ];
        foreach ($tablesWithUserId as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'anulado')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->boolean('anulado')->default(false)->after('user_id');
                });
            }
        }

        if (Schema::hasTable('contratos') && !Schema::hasColumn('contratos', 'anulado')) {
            Schema::table('contratos', function (Blueprint $blueprint) {
                $blueprint->boolean('anulado')->default(false)->after('status');
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'licitacions', 'consultor_obras', 'ejecutor_obras', 'proveedor_servicios',
            'proveedor_biens', 'especialista_ejecucions', 'especialista_consultorias',
            'inmobiliarias', 'topografias', 'tecnologias', 'plantilla_ings',
            'contratos',
        ];
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'anulado')) {
                Schema::table($table, fn (Blueprint $b) => $b->dropColumn('anulado'));
            }
        }
    }
};
