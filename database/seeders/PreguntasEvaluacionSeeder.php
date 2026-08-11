<?php

namespace Database\Seeders;

use App\Models\PreguntaEvaluacion;
use Illuminate\Database\Seeder;

class PreguntasEvaluacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $preguntas = [
            [
                'titulo' => '¿Le ha dicho su médico alguna vez que padece una enfermedad cardiaca y que sólo debe hacer aquella actividad física que le aconseje un médico?',
                'descripcion' => 'Evaluación de antecedentes cardiacos',
                'orden' => 1,
            ],
            [
                'titulo' => '¿Tiene dolor en el pecho cuando hace actividad física?',
                'descripcion' => 'Evaluación de dolor torácico durante ejercicio',
                'orden' => 2,
            ],
            [
                'titulo' => 'En el último mes, ¿ha tenido dolor en el pecho cuando no hacía actividad física?',
                'descripcion' => 'Evaluación de dolor torácico en reposo',
                'orden' => 3,
            ],
            [
                'titulo' => '¿Pierde el equilibrio debido a mareos o se ha desmayado alguna vez?',
                'descripcion' => 'Evaluación de mareos y síncopes',
                'orden' => 4,
            ],
            [
                'titulo' => '¿Tiene problemas en huesos o articulaciones (por ejemplo, espalda, rodilla o cadera) que puedan empeorar si aumenta la actividad física?',
                'descripcion' => 'Evaluación de problemas osteoarticulares',
                'orden' => 5,
            ],
            [
                'titulo' => '¿Le receta su médico algún medicamento para la tensión arterial o un problema cardíaco?',
                'descripcion' => 'Evaluación de medicamentos cardiovasculares',
                'orden' => 6,
            ],
            [
                'titulo' => '¿Conoce alguna razón por la cual no debería realizar actividad física?',
                'descripcion' => 'Evaluación general de contraindicaciones',
                'orden' => 7,
            ],
        ];

        foreach ($preguntas as $pregunta) {
            PreguntaEvaluacion::create([
                'titulo' => $pregunta['titulo'],
                'descripcion' => $pregunta['descripcion'],
                'orden' => $pregunta['orden'],
                'activa' => true,
            ]);
        }
    }
}
