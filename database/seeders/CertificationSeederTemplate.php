<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder Template para crear nuevas certificaciones con sus preguntas
 *
 * INSTRUCCIONES:
 * 1. Copia este archivo y renómbralo: NewCertificationSeeder.php
 * 2. Modifica el nombre de la clase: class NewCertificationSeeder
 * 3. Completa los datos abajo (slug, nombre, descripción, preguntas)
 * 4. Ejecuta: php artisan db:seed --class=NewCertificationSeeder
 *
 * EJEMPLO COMPLETO:
 *
 * php artisan db:seed --class="Database\Seeders\NewCertificationSeeder"
 */
class CertificationSeederTemplate extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // PASO 1: Configura los datos básicos de la certificación
        $certification = $this->createCertification([
            'slug' => 'marketing_101',           // Identificador único (letras, números, guiones)
            'name' => 'Marketing 101',           // Nombre visible
            'description' => 'Aprende los fundamentos del marketing digital',
            'questions_required' => 30,          // Cuántas preguntas necesita responder
            'pass_score_percentage' => 66.67,    // Porcentaje para aprobar
            'cooldown_days' => 30,               // Días antes de poder intentar de novo
            'active' => true,                    // Visible en el home
            'home_order' => 100,                 // Orden en el home (menor = más arriba)
        ]);

        // PASO 2: Define las preguntas
        $questions = [
            [
                'prompt' => '¿Cuál es el objetivo principal del marketing?',
                'correct' => 1,  // Número de opción correcta (1-4)
            ],
            [
                'prompt' => '¿Qué significa ROI en marketing?',
                'correct' => 2,
            ],
            [
                'prompt' => '¿Cuál es la red social más usada para B2B?',
                'correct' => 3,
            ],
            // ... Agrega todas las preguntas que necesites
            // Se requieren al menos 30 preguntas para que funcione correctamente
        ];

        // PASO 3: Las opciones estándar son: Siempre, A veces, Raramente, Nunca
        // Si deseas opciones personalizadas, descomenta abajo:
        // $options = ['Opción A', 'Opción B', 'Opción C', 'Opción D'];

        // PASO 4: Enlaza las preguntas a la certificación
        QuestionSeederHelper::seedQuestionsForCertification(
            certificationSlug: $certification->slug,
            questions: $questions,
        );

        $this->command->info("Certificación '{$certification->name}' creada con " . count($questions) . ' preguntas.');
    }

    /**
     * Método auxiliar para crear la certificación
     */
    private function createCertification(array $data): \App\Models\Certification
    {
        $slug = (string) ($data['slug'] ?? '');

        return \App\Models\Certification::query()->updateOrCreate(
            ['slug' => $slug],
            $data
        );
    }
}
