<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DiagnosticoCie10;
use Illuminate\Support\Facades\DB;

class DiagnosticoCie10Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $diagnosticos = [
            // Dorsalgias y Patologías de Columna
            ['codigo' => 'M54.5', 'descripcion' => 'Lumbago no especificado / Lumbalgia aguda o crónica', 'activo' => true],
            ['codigo' => 'M54.4', 'descripcion' => 'Lumbago con ciática', 'activo' => true],
            ['codigo' => 'M54.2', 'descripcion' => 'Cervicalgia / Dolor de cuello', 'activo' => true],
            ['codigo' => 'M54.3', 'descripcion' => 'Ciática', 'activo' => true],
            ['codigo' => 'M51.1', 'descripcion' => 'Trastornos de disco lumbar con radiculopatía (Hernia discal lumbar)', 'activo' => true],
            ['codigo' => 'M51.2', 'descripcion' => 'Otros desplazamientos especificados de disco intervertebral', 'activo' => true],

            // Nutricionales y Metabólicos
            ['codigo' => 'E66.9', 'descripcion' => 'Obesidad, no especificada', 'activo' => true],
            ['codigo' => 'E66.0', 'descripcion' => 'Obesidad debida a exceso de calorías', 'activo' => true],
            ['codigo' => 'E66.8', 'descripcion' => 'Otros tipos de obesidad', 'activo' => true],
            ['codigo' => 'E66.2', 'descripcion' => 'Obesidad extrema con hipoventilación alveolar', 'activo' => true],
            ['codigo' => 'E67.8', 'descripcion' => 'Sobrepeso u otros tipos de hiperalimentación especificados', 'activo' => true],
            ['codigo' => 'E11.9', 'descripcion' => 'Diabetes mellitus tipo 2 sin complicaciones', 'activo' => true],
            ['codigo' => 'E78.0', 'descripcion' => 'Hipercolesterolemia pura', 'activo' => true],
            ['codigo' => 'E78.1', 'descripcion' => 'Hipertriglicertidemia pura', 'activo' => true],
            ['codigo' => 'E78.2', 'descripcion' => 'Hiperlipidemia mixta / Dislipidemia', 'activo' => true],

            // Cardiovasculares
            ['codigo' => 'I10', 'descripcion' => 'Hipertensión esencial (primaria)', 'activo' => true],
            ['codigo' => 'I11.9', 'descripcion' => 'Enfermedad cardíaca hipertensiva sin insuficiencia cardíaca', 'activo' => true],

            // Articulares y Musculoesqueléticos
            ['codigo' => 'M25.5', 'descripcion' => 'Dolor en articulación / Artralgia no especificada', 'activo' => true],
            ['codigo' => 'M17.9', 'descripcion' => 'Gonartrosis [artrosis de la rodilla], no especificada', 'activo' => true],
            ['codigo' => 'M16.9', 'descripcion' => 'Coxartrosis [artrosis de la cadera], no especificada', 'activo' => true],
            ['codigo' => 'M75.1', 'descripcion' => 'Síndrome del manguito rotatorio (Hombro)', 'activo' => true],
            ['codigo' => 'M77.1', 'descripcion' => 'Epicondilitis lateral (Codo de tenista)', 'activo' => true],
            ['codigo' => 'M77.0', 'descripcion' => 'Epicondilitis medial (Codo de golfista)', 'activo' => true],
            ['codigo' => 'M79.7', 'descripcion' => 'Fibromialgia', 'activo' => true],
            ['codigo' => 'M81.0', 'descripcion' => 'Osteoporosis posmenopáusica', 'activo' => true],
            ['codigo' => 'M81.9', 'descripcion' => 'Osteoporosis no especificada', 'activo' => true],
            ['codigo' => 'M62.8', 'descripcion' => 'Espasmo o contractura muscular', 'activo' => true],

            // Traumatología Deportiva y Lesiones
            ['codigo' => 'S83.2', 'descripcion' => 'Desgarro de menisco presente o antiguo (Rodilla)', 'activo' => true],
            ['codigo' => 'S83.5', 'descripcion' => 'Esguince o distensión del ligamento cruzado de la rodilla', 'activo' => true],
            ['codigo' => 'S93.4', 'descripcion' => 'Esguince y torcedura del tobillo', 'activo' => true],

            // Preventivo y Aptitud Física
            ['codigo' => 'Z02.5', 'descripcion' => 'Examen médico para la práctica del deporte / Acondicionamiento físico', 'activo' => true],
            ['codigo' => 'Z71.8', 'descripcion' => 'Asesoramiento para ejercicio y prescripción de actividad física', 'activo' => true],
            ['codigo' => 'R53', 'descripcion' => 'Astenia / Mala gana y fatiga generalizada', 'activo' => true]
        ];

        foreach ($diagnosticos as $diag) {
            DiagnosticoCie10::updateOrCreate(
                ['codigo' => $diag['codigo']],
                [
                    'descripcion' => $diag['descripcion'],
                    'activo' => $diag['activo']
                ]
            );
        }
    }
}
