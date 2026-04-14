<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HCertificationSeeder extends Seeder
{
    /**
     * Seed questions for the "hetero" (Social Energy) certification.
     */
    public function run(): void
    {
        $questions = [
            ['prompt' => '¿Qué haces cuando ves una persona atractiva?', 'correct' => 1],
            ['prompt' => '¿Cómo defines una primera cita exitosa?', 'correct' => 2],
            ['prompt' => '¿Cuál es tu estrategia de flirteo favorita?', 'correct' => 3],
            ['prompt' => '¿Qué tan importante es el físico en una pareja?', 'correct' => 4],
            ['prompt' => '¿Cómo manejas los celos?', 'correct' => 1],
            ['prompt' => '¿Tu horóscopo influye en tus decisiones amorosas?', 'correct' => 2],
            ['prompt' => '¿Cuál es tu estándar de "persona perfecta"?', 'correct' => 3],
            ['prompt' => '¿Con qué frecuencia cambias de opinión sobre tus ex?', 'correct' => 4],
            ['prompt' => '¿Cómo reaccionas ante un rechazo amoroso?', 'correct' => 1],
            ['prompt' => '¿Cuántas veces le has dicho "te amo" sin estarlo seguro?', 'correct' => 2],
            ['prompt' => '¿Qué importancia tienen las redes sociales en tu vida amorosa?', 'correct' => 3],
            ['prompt' => '¿Tu pareja ideal tiene que ver series contigo?', 'correct' => 4],
            ['prompt' => '¿Cuál es tu peor hábito en una relación?', 'correct' => 1],
            ['prompt' => '¿Cómo manejabas las tareas del hogar en tu última relación?', 'correct' => 2],
            ['prompt' => '¿Qué es lo primero que notas de una persona?', 'correct' => 3],
            ['prompt' => '¿Cuánto tiempo esperas antes de presentar a alguien a tu familia?', 'correct' => 4],
            ['prompt' => '¿Eres celoso/a en las redes sociales?', 'correct' => 1],
            ['prompt' => '¿Qué tan importante es el dineró para ti en una pareja?', 'correct' => 2],
            ['prompt' => '¿Cómo es tu reacción ante mensajes sin responder?', 'correct' => 3],
            ['prompt' => '¿Tu pareja debe tener los mismos gustos que tú?', 'correct' => 4],
            ['prompt' => '¿Qué tan fácil te es disculparte?', 'correct' => 1],
            ['prompt' => '¿Creerías en lo que tu pareja te dice sin cuestionarlo?', 'correct' => 2],
            ['prompt' => '¿Qué tan importante es la compatibilidad sexual?', 'correct' => 3],
            ['prompt' => '¿Cómo defines "engaño" en una relación?', 'correct' => 4],
            ['prompt' => '¿Tienes amigos del género opuesto? ¿Qué piensa tu pareja?', 'correct' => 1],
            ['prompt' => '¿Cuántas veces chequeas el teléfono de tu pareja?', 'correct' => 2],
            ['prompt' => '¿Prefieres noches de cine o salidas aventureras?', 'correct' => 3],
            ['prompt' => '¿Cómo es tu break-up ideal?', 'correct' => 4],
            ['prompt' => '¿Te traumas fácilmente después de una ruptura?', 'correct' => 1],
            ['prompt' => '¿Cuál es tu mayor miedo en una relación?', 'correct' => 2],
        ];

        QuestionSeederHelper::seedQuestionsForCertification('hetero', $questions);
    }
}
