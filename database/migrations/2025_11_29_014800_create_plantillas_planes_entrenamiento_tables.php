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
        Schema::create('plantillas_planes_entrenamiento', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->string('objetivo', 150)->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->enum('trimestre', ['Q1', 'Q2', 'Q3'])->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['estado', 'trimestre']);
        });

        Schema::create('plantilla_plan_entrenamiento_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plantilla_id');
            $table->unsignedBigInteger('ejercicio_id')->nullable();
            $table->tinyInteger('dia_semana')->nullable()->comment('1 = Lunes ... 7 = Domingo');
            $table->integer('series_estandar')->nullable();
            $table->integer('repeticiones_estandar')->nullable();
            $table->integer('tiempo_descanso_estandar_seg')->nullable();
            $table->tinyInteger('orden_ejecucion')->default(1);
            $table->text('notas')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->tinyInteger('mesociclo')->nullable();
            $table->decimal('peso', 8, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('plantilla_id')
                ->references('id')
                ->on('plantillas_planes_entrenamiento')
                ->onDelete('cascade');

            $table->foreign('ejercicio_id')
                ->references('id')
                ->on('ejercicios')
                ->onDelete('set null');

            $table->index(['plantilla_id', 'dia_semana']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantilla_plan_entrenamiento_detalles');
        Schema::dropIfExists('plantillas_planes_entrenamiento');
    }
};
