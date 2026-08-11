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
        Schema::create('respuestas_preguntas_cliente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('pregunta_id')->constrained('preguntas_evaluacion')->onDelete('cascade');
            $table->boolean('respuesta');
            $table->dateTime('fecha_respuesta')->nullable();
            $table->softDeletes('deleted_at', precision: 0);
            $table->timestamps();
            
            // Índice único para evitar respuestas duplicadas
            $table->unique(['cliente_id', 'pregunta_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('respuestas_preguntas_cliente');
    }
};
