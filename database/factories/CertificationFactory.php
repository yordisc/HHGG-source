<?php

namespace Database\Factories;

use App\Models\Certification;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificationFactory extends Factory
{
    protected $model = Certification::class;

    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(2),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'active' => true,
            'questions_required' => 10,
            'pass_score_percentage' => 70.00,
            'cooldown_days' => 0,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => $this->faker->unique()->numberBetween(1, 100),
            'settings' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'active' => false,
        ]);
    }
}
