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
        Schema::create('valoraciones_v2', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->unsignedBigInteger('entrenador_id')->nullable();
            $table->unsignedBigInteger('imc_clasificacion_id')->nullable();

            // Header extra
            $table->boolean('convenio')->default(false);
            $table->string('nombre_empresa')->nullable();

            // Perímetros (medición antropométrica)
            $table->decimal('estatura', 8, 2)->nullable();
            $table->decimal('peso', 8, 2)->nullable();
            $table->integer('edad')->nullable();
            $table->string('sexo', 20)->default('Hombre');
            $table->decimal('pecho', 8, 2)->default(0.00);
            $table->decimal('brazo_izquierdo', 8, 2)->default(0.00);
            $table->decimal('brazo_derecho', 8, 2)->default(0.00);
            $table->decimal('perimetro_abdominal', 8, 2)->default(0.00);
            $table->decimal('pierna_izquierda', 8, 2)->default(0.00);
            $table->decimal('pierna_derecha', 8, 2)->default(0.00);

            // Frecuencia Cardíaca
            $table->integer('frecuencia1')->nullable();
            $table->integer('frecuencia2')->nullable();
            $table->integer('frecuencia3')->nullable();

            // Test Físico
            $table->integer('push_up')->nullable();
            $table->integer('abdominales')->nullable();

            // Composición Corporal
            $table->decimal('imc', 8, 2)->nullable();
            $table->decimal('porcentaje_grasa', 8, 2)->nullable();
            $table->decimal('porcentaje_musculo', 8, 2)->nullable();
            $table->decimal('grasa_visceral', 8, 2)->nullable();
            $table->decimal('masa_musculo_esqueletica', 8, 2)->nullable();

            // Antecedentes
            $table->boolean('hipertension')->default(false);
            $table->boolean('fuma')->default(false);
            $table->boolean('lesion')->default(false);
            $table->string('tension_arterial', 50)->nullable();
            $table->boolean('antecedentes_cardio_personales')->default(false);
            $table->boolean('antecedentes_cardio_familiares')->default(false);
            $table->boolean('colesterol_alto')->default(false);
            $table->boolean('trigliceridos_altos')->default(false);
            $table->boolean('toma_medicamento')->default(false);
            $table->boolean('toma_foto')->default(false);
            $table->text('descripcion_lesion')->nullable();

            // Observaciones
            $table->text('observaciones')->nullable();

            // Plan de entrenamiento
            $table->string('plan_tipo')->nullable();
            $table->boolean('plan_hombre_bp')->default(false);
            $table->boolean('plan_hombre_gm')->default(false);
            $table->boolean('plan_mujer_bp')->default(false);
            $table->boolean('plan_mujer_gm')->default(false);

            // Cuestionario PAR-Q (7 preguntas)
            $table->boolean('parq_q1')->default(false);
            $table->boolean('parq_q2')->default(false);
            $table->boolean('parq_q3')->default(false);
            $table->boolean('parq_q4')->default(false);
            $table->boolean('parq_q5')->default(false);
            $table->boolean('parq_q6')->default(false);
            $table->boolean('parq_q7')->default(false);

            // Fechas y trimestre
            $table->dateTime('fecha_registro')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->unsignedTinyInteger('trimestre')->nullable();
            $table->string('tipo_valoracion', 50)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valoraciones_v2');
    }
};
