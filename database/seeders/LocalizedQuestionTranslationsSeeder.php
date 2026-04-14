<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionTranslation;
use Illuminate\Database\Seeder;

class LocalizedQuestionTranslationsSeeder extends Seeder
{
    /**
     * Seed localized question translations for non-English locales.
     */
    public function run(): void
    {
        $locales = ['es', 'pt', 'fr', 'zh', 'hi', 'ar'];

        Question::query()->chunkById(200, function ($questions) use ($locales): void {
            $rows = [];
            $now = now();

            foreach ($questions as $question) {
                foreach ($locales as $locale) {
                    [$prompt, $option1, $option2, $option3, $option4] = $this->translateQuestion(
                        locale: $locale,
                        basePrompt: (string) $question->prompt,
                        option1: (string) $question->option_1,
                        option2: (string) $question->option_2,
                        option3: $question->option_3,
                        option4: $question->option_4,
                    );

                    $rows[] = [
                        'question_id' => $question->id,
                        'language' => $locale,
                        'prompt' => $prompt,
                        'option_1' => $option1,
                        'option_2' => $option2,
                        'option_3' => $option3,
                        'option_4' => $option4,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            QuestionTranslation::query()->upsert(
                $rows,
                ['question_id', 'language'],
                ['prompt', 'option_1', 'option_2', 'option_3', 'option_4', 'updated_at']
            );
        });
    }

    /**
     * @return array{0:string,1:string,2:string,3:string,4:string}
     */
    private function translateQuestion(
        string $locale,
        string $basePrompt,
        string $option1,
        string $option2,
        ?string $option3,
        ?string $option4
    ): array {
        return [
            $basePrompt,
            $this->translateOption($locale, $option1),
            $this->translateOption($locale, $option2),
            $option3 !== null ? $this->translateOption($locale, $option3) : '',
            $option4 !== null ? $this->translateOption($locale, $option4) : '',
        ];
    }

    private function translateOption(string $locale, string $option): string
    {
        $normalized = trim($option);
        if ($normalized === '') {
            return $normalized;
        }

        $map = [
            'es' => [
                'siempre' => 'Siempre',
                'a veces' => 'A veces',
                'raramente' => 'Raramente',
                'nunca' => 'Nunca',
            ],
            'pt' => [
                'siempre' => 'Sempre',
                'a veces' => 'As vezes',
                'raramente' => 'Raramente',
                'nunca' => 'Nunca',
            ],
            'fr' => [
                'siempre' => 'Toujours',
                'a veces' => 'Parfois',
                'raramente' => 'Rarement',
                'nunca' => 'Jamais',
            ],
            'zh' => [
                'siempre' => '总是',
                'a veces' => '有时',
                'raramente' => '很少',
                'nunca' => '从不',
            ],
            'hi' => [
                'siempre' => 'Hamesha',
                'a veces' => 'Kabhi kabhi',
                'raramente' => 'Kam hi',
                'nunca' => 'Kabhi nahi',
            ],
            'ar' => [
                'siempre' => 'دائما',
                'a veces' => 'أحيانا',
                'raramente' => 'نادرا',
                'nunca' => 'أبدا',
            ],
        ];

        $key = mb_strtolower($normalized);
        return $map[$locale][$key] ?? $option;
    }
}
