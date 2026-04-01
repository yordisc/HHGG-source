<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Seeder;

class LifeStyleQuestionsSeeder extends Seeder
{
    public function run(): void
    {
        if (Question::where('cert_type', 'good_girl')->count() >= 30) {
            return;
        }

        $questions = [
            ['prompt' => '¿Con qué frecuencia dices "perdón" innecesariamente?', 'correct' => 1],
            ['prompt' => '¿Cómo manejas los cambios de planes de último minuto?', 'correct' => 2],
            ['prompt' => '¿Qué tan rápido satisfaces los caprichos de otros?', 'correct' => 3],
            ['prompt' => '¿Tu opinión es importante en tu grupo de amigos?', 'correct' => 4],
            ['prompt' => '¿Dices "no" fácilmente?', 'correct' => 1],
            ['prompt' => '¿Con qué frecuencia sacrificas tu tiempo por otros?', 'correct' => 2],
            ['prompt' => '¿Cómo expresas tu inconformidad?', 'correct' => 3],
            ['prompt' => '¿Qué haces si alguien toma crédito por tu trabajo?', 'correct' => 4],
            ['prompt' => '¿Cuál es tu reacción ante una crítica?', 'correct' => 1],
            ['prompt' => '¿Guardas rencor después de una pelea?', 'correct' => 2],
            ['prompt' => '¿Cuántas veces repites que "está bien" cuando no lo está?', 'correct' => 3],
            ['prompt' => '¿Prefieres mantener la paz o expresar lo que sientes?', 'correct' => 4],
            ['prompt' => '¿Mientes para evitar herir sentimientos?', 'correct' => 1],
            ['prompt' => '¿Cómo manejas ser la persona a quien todos recurren?', 'correct' => 2],
            ['prompt' => '¿Establecer límites es tu fortaleza?', 'correct' => 3],
            ['prompt' => '¿Qué tanto influyen las expectativas de otros en ti?', 'correct' => 4],
            ['prompt' => '¿Te consideras una persona conflictiva?', 'correct' => 1],
            ['prompt' => '¿Con qué frecuencia antepones las necesidades ajenas?', 'correct' => 2],
            ['prompt' => '¿Cuál es tu mayor arrepentimiento?', 'correct' => 3],
            ['prompt' => '¿Qué tan difícil es pedir ayuda?', 'correct' => 4],
            ['prompt' => '¿Cómo reaccionas ante un cumplido?', 'correct' => 1],
            ['prompt' => '¿Tu voz es escuchada en decisiones importantes?', 'correct' => 2],
            ['prompt' => '¿Cambias tu comportamiento según quién te rodea?', 'correct' => 3],
            ['prompt' => '¿Qué tan importante es que los demás piensen bien de ti?', 'correct' => 4],
            ['prompt' => '¿Eres honesta en situaciones incómodas?', 'correct' => 1],
            ['prompt' => '¿Con qué frecuencia haces cosas que no quieres hacer?', 'correct' => 2],
            ['prompt' => '¿Tu bienestar emocional es prioridad?', 'correct' => 3],
            ['prompt' => '¿Cómo defines ser una "buena persona"?', 'correct' => 4],
            ['prompt' => '¿Permites que otros pisen tus límites?', 'correct' => 1],
            ['prompt' => '¿Cuál de estas te describe mejor?', 'correct' => 2],
        ];

        $rows = [];
        foreach ($questions as $q) {
            $rows[] = [
                'cert_type' => 'good_girl',
                'prompt' => $q['prompt'],
                'option_1' => 'Siempre',
                'option_2' => 'A veces',
                'option_3' => 'Raramente',
                'option_4' => 'Nunca',
                'correct_option' => $q['correct'],
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Question::insert($rows);
    }
}
