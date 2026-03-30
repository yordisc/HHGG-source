<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionTranslation;
use Illuminate\Database\Seeder;

class QuestionTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        Question::query()->chunkById(200, function ($questions): void {
            $now = now();
            $rows = [];

            foreach ($questions as $question) {
                $rows[] = [
                    'question_id' => $question->id,
                    'language' => 'en',
                    'prompt' => $question->prompt,
                    'option_1' => $question->option_1,
                    'option_2' => $question->option_2,
                    'option_3' => $question->option_3,
                    'option_4' => $question->option_4,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            QuestionTranslation::query()->upsert(
                $rows,
                ['question_id', 'language'],
                ['prompt', 'option_1', 'option_2', 'option_3', 'option_4', 'updated_at']
            );
        });
    }
}
