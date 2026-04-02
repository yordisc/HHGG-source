<?php

namespace Tests\Feature;

use App\Models\Certification;
use App\Models\RateLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_quiz_start_is_blocked_on_immediate_second_attempt(): void
    {
        Certification::query()->create([
            'slug' => 'hetero',
            'name' => 'Certificado Hetero',
            'active' => true,
            'cooldown_days' => 30,
        ]);

        $payload = [
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'country_code' => 'CO',
            'document_type' => 'CC',
            'document' => 'CC-123456789',
            'cert_type' => 'hetero',
        ];

        $first = $this->post(route('quiz.start'), $payload);
        $first->assertRedirect(route('quiz.show', ['certType' => 'hetero']));

        $second = $this->from(route('quiz.register', ['certType' => 'hetero']))
            ->post(route('quiz.start'), $payload);

        $second->assertRedirect(route('quiz.register', ['certType' => 'hetero']));
        $second->assertSessionHasErrors(['rate_limit']);

        $this->assertSame(2, RateLimit::query()->count());
    }
}
