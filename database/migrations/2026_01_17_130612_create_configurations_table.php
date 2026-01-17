<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('text'); // text, image, boolean, json
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insertar configuración por defecto para la imagen 360
        DB::table('configurations')->insert([
            'key' => 'dashboard_360_image',
            'value' => '/images/360/default-panorama.jpg',
            'type' => 'image',
            'description' => 'Imagen panorámica 360° que se muestra en el dashboard',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configurations');
    }
};
