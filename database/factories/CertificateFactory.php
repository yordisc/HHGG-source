<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Certification;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        return [
            'serial' => $this->faker->uuid(),
            'certification_id' => Certification::factory(),
            'result_key' => $this->faker->sha1(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'country' => $this->faker->country(),
            'country_code' => $this->faker->countryCode(),
            'document_type' => 'passport',
            'document_hash' => $this->faker->sha256(),
            'doc_lookup_hash' => $this->faker->sha256(),
            'identity_lookup_hash' => $this->faker->sha256(),
            'doc_partial' => $this->faker->numerify('####'),
            'score_correct' => $this->faker->numberBetween(5, 15),
            'score_incorrect' => $this->faker->numberBetween(0, 5),
            'total_questions' => 20,
            'score_numeric' => $this->faker->randomFloat(2, 0, 100),
            'issued_at' => now(),
            'completed_at' => now(),
            'next_available_at' => now()->addDays(7),
            'expires_at' => now()->addDays(365),
            'last_attempt_at' => now(),
            'certificate_image_path' => null,
            'image_updated_at' => null,
            'certification_expires_at' => now()->addDays(365),
            'download_expires_at' => now()->addDays(30),
            'result_decision_source' => 'manual',
            'result_decision_reason' => null,
        ];
    }
}
