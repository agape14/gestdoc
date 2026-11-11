<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('curriculum_files', 'es_certificado')) {
            Schema::table('curriculum_files', function (Blueprint $table) {
                $table->boolean('es_certificado')->default(false)->after('orden');
            });
        }

        if (!Schema::hasTable('curriculum_certificado_periodos')) {
            Schema::create('curriculum_certificado_periodos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('curriculum_file_id');
                $table->string('tipo', 30)->default('valorizacion');
                $table->string('numero', 50)->nullable();
                $table->date('fecha_inicio')->nullable();
                $table->date('fecha_fin')->nullable();
                $table->integer('dias')->nullable();
                $table->integer('orden')->default(0);
                $table->timestamps();

                $table->foreign('curriculum_file_id', 'ccp_cfid_fk')
                    ->references('id')
                    ->on('curriculum_files')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_certificado_periodos');

        if (Schema::hasColumn('curriculum_files', 'es_certificado')) {
            Schema::table('curriculum_files', function (Blueprint $table) {
                $table->dropColumn('es_certificado');
            });
        }
    }
};
