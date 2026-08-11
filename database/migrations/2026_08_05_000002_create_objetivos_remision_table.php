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
        Schema::create('objetivos_remision', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Insertar valores catálogo por defecto
        DB::table('objetivos_remision')->insert([
            ['nombre' => 'Recuperación Funcional', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Fortalecimiento muscular', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Control de peso', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Mejoramiento capacidad física', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Prevención de enfermedad', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('objetivos_remision');
    }
};
