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
        Schema::create('remisiones_medicas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id')->index();
            $table->unsignedBigInteger('medico_id')->nullable()->index();
            $table->unsignedBigInteger('cie10_id')->nullable()->index();
            $table->text('diagnostico')->nullable();
            $table->string('codigo_cie10')->nullable();
            $table->json('objetivos')->nullable(); // Guardar array de objetivos seleccionados
            $table->string('objetivo_otro')->nullable();
            $table->text('restricciones')->nullable();
            $table->string('vigencia')->default('6_meses'); // 6_meses, 12_meses, otra
            $table->string('vigencia_otra_descripcion')->nullable();
            $table->string('estado')->default('activa'); // activa, vencida, anulada
            $table->date('fecha_registro')->nullable();
            $table->timestamps();

            // Claves foráneas (si existen tablas correspondientes)
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->foreign('medico_id')->references('id')->on('medicos')->onDelete('set null');
            $table->foreign('cie10_id')->references('id')->on('diagnosticos_cie10')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remisiones_medicas');
    }
};
