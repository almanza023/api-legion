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
        Schema::create('rutinas', function (Blueprint $table) {
            $table->id();

            // Relación con cliente
            $table->unsignedBigInteger('cliente_id');
            $table->foreign('cliente_id')
                  ->references('id')
                  ->on('clientes')
                  ->onDelete('cascade');

            // Relación con entrenador
            $table->unsignedBigInteger('entrenador_id');
            $table->foreign('entrenador_id')
                  ->references('id')
                  ->on('entrenadores')
                  ->onDelete('cascade');

            // Relación con rutina maestra (opcional)
            $table->unsignedBigInteger('rutina_maestra_id')->nullable();
            $table->foreign('rutina_maestra_id')
                  ->references('id')
                  ->on('rutinas_maestras')
                  ->onDelete('set null');

            // Trimestre: Q1, Q2, Q3
            $table->enum('trimestre', ['Q1', 'Q2', 'Q3']);

            // Fechas
            $table->date('fecha_inicio');
            $table->date('fecha_fin');

            // Estado
            $table->enum('estado', ['activa', 'completada', 'cancelada'])->default('activa');

            $table->timestamps();
            $table->softDeletes();

            // Índices para mejorar rendimiento
            $table->index(['cliente_id', 'trimestre']);
            $table->index(['entrenador_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rutinas');
    }
};
