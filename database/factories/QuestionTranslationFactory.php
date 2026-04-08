<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionTranslationFactory extends Factory
{
    protected $model = QuestionTranslation::class;

    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'language' => $this->faker->randomElement(['es', 'en', 'fr', 'pt', 'hi', 'zh', 'ar']),
            'prompt' => $this->faker->sentence(),
            'option_1' => $this->faker->word(),
            'option_2' => $this->faker->word(),
            'option_3' => $this->faker->word(),
            'option_4' => $this->faker->word(),
        ];
    }
}
