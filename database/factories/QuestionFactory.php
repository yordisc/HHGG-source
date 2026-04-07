<?php

namespace Database\Factories;

use App\Models\Certification;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'certification_id' => Certification::factory(),
            'prompt' => $this->faker->sentence(),
            'type' => 'mcq_4',
            'option_1' => 'Option A',
            'option_2' => 'Option B',
            'option_3' => 'Option C',
            'option_4' => 'Option D',
            'correct_option' => 1,
            'explanation' => $this->faker->sentence(),
            'active' => true,
            'is_test_question' => false,
        ];
    }
}
