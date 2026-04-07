<?php

namespace App\Support;

use App\Models\Certification;
use App\Models\Question;
use App\Models\QuestionTranslation;
use Illuminate\Support\Facades\DB;

class TestQuestionGeneratorService
{
    /**
     * Crea preguntas de prueba en español para una certificacion.
     *
     * @return array{created:int, questions: array<int, int>}
     */
    public function generate(Certification $certification, int $count = 5): array
    {
        $count = max(1, $count);
        $questionIds = [];

        DB::transaction(function () use ($certification, $count, &$questionIds): void {
            for ($index = 1; $index <= $count; $index++) {
                $correctOption = random_int(1, 4);
                $options = [
                    1 => 'Opcion distractora A '.$index,
                    2 => 'Opcion distractora B '.$index,
                    3 => 'Opcion distractora C '.$index,
                    4 => 'Opcion distractora D '.$index,
                ];

                $options[$correctOption] = 'Respuesta correcta '.$index;

                $question = Question::create([
                    'certification_id' => $certification->id,
                    'prompt' => 'Pregunta de prueba '.$index.': selecciona la respuesta correcta.',
                    'option_1' => $options[1],
                    'option_2' => $options[2],
                    'option_3' => $options[3],
                    'option_4' => $options[4],
                    'correct_option' => $correctOption,
                    'active' => true,
                    'is_test_question' => true,
                ]);

                QuestionTranslation::query()->updateOrCreate(
                    [
                        'question_id' => $question->id,
                        'language' => 'es',
                    ],
                    [
                        'prompt' => 'Pregunta de prueba '.$index.': selecciona la respuesta correcta.',
                        'option_1' => $options[1],
                        'option_2' => $options[2],
                        'option_3' => $options[3],
                        'option_4' => $options[4],
                    ]
                );

                $questionIds[] = $question->id;
            }
        });

        return [
            'created' => count($questionIds),
            'questions' => $questionIds,
        ];
    }
}