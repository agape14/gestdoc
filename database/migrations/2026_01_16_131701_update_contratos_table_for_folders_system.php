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
        // Primero eliminar la constraint de licitacion_id para evitar problemas
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropForeign(['licitacion_id']);
        });

        Schema::table('contratos', function (Blueprint $table) {
            // Hacer licitacion_id nullable
            $table->unsignedBigInteger('licitacion_id')->nullable()->change();

            // Añadir folder_id
            $table->foreignId('folder_id')->nullable()->after('id')->constrained('folders')->onDelete('cascade');

            // Información del cliente y proyecto
            $table->string('client')->nullable()->after('folder_id');
            $table->string('project_name')->nullable()->after('client');
            $table->text('contract_object')->nullable()->after('project_name');

            // Información del contrato
            $table->string('contract_number')->nullable()->after('contract_object');
            $table->enum('currency', ['PEN', 'USD'])->default('PEN')->after('contract_number');
            $table->decimal('amount', 15, 2)->default(0)->after('currency');
            $table->decimal('participation_percentage', 5, 2)->default(100.00)->after('amount');

            // Fechas
            $table->date('contract_date')->nullable()->after('participation_percentage');
            $table->date('conformity_date')->nullable()->after('contract_date');

            // Montos y tipo de cambio
            $table->decimal('exchange_rate', 10, 4)->nullable()->after('conformity_date');
            $table->decimal('accumulated_amount', 15, 2)->default(0)->after('exchange_rate');

            // Estado
            $table->enum('status', ['completo', 'incompleto'])->default('incompleto')->after('accumulated_amount');

            // Renombrar archivo_path a file_path
            $table->renameColumn('archivo_path', 'file_path');

            $table->index(['folder_id']);
            $table->index(['status']);
        });

        // Restaurar la foreign key de licitacion_id como nullable
        Schema::table('contratos', function (Blueprint $table) {
            $table->foreign('licitacion_id')->references('id')->on('licitacions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropForeign(['folder_id']);
            $table->dropForeign(['licitacion_id']);

            $table->dropColumn([
                'folder_id',
                'client',
                'project_name',
                'contract_object',
                'contract_number',
                'currency',
                'amount',
                'participation_percentage',
                'contract_date',
                'conformity_date',
                'exchange_rate',
                'accumulated_amount',
                'status',
            ]);

            $table->renameColumn('file_path', 'archivo_path');
        });

        Schema::table('contratos', function (Blueprint $table) {
            $table->unsignedBigInteger('licitacion_id')->nullable(false)->change();
            $table->foreign('licitacion_id')->references('id')->on('licitacions')->onDelete('cascade');
        });
    }
};
