<?php

namespace Database\Seeders;

use App\Models\Certification;
use App\Models\Question;
use Illuminate\Database\Seeder;

class SandboxCertificationSeeder extends Seeder
{
    public function run(): void
    {
        $certification = Certification::query()->updateOrCreate(
            ['slug' => 'sandbox_system_test'],
            [
                'name' => '[TEST] Sandbox Sistema Completo',
                'description' => 'Certificacion de prueba para validar flujos end-to-end sin mezclar datos reales.',
                'active' => true,
                'questions_required' => 30,
                'pass_score_percentage' => 66.67,
                'cooldown_days' => 0,
                'result_mode' => 'binary_threshold',
                'pdf_view' => 'pdf.certificate',
                'home_order' => 999,
                'settings' => [
                    'is_sandbox' => true,
                    'test_only' => true,
                    'note' => 'Puedes desactivar esta certificacion desde el panel admin.',
                ],
            ]
        );

        for ($i = 1; $i <= 30; $i++) {
            Question::query()->updateOrCreate(
                [
                    'certification_id' => $certification->id,
                    'prompt' => '[TEST] Pregunta sandbox #' . $i,
                ],
                [
                    'option_1' => 'Siempre',
                    'option_2' => 'A veces',
                    'option_3' => 'Raramente',
                    'option_4' => 'Nunca',
                    'correct_option' => (($i - 1) % 4) + 1,
                    'type' => 'mcq_4',
                    'weight' => 1,
                    'sudden_death_mode' => 'none',
                    'is_test_question' => true,
                    'active' => true,
                ]
            );
        }
    }
}
