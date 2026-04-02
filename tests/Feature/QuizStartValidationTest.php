<?php

namespace Tests\Feature;

use App\Models\Certification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizStartValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_quiz_start_rejects_invalid_document_format_for_country_and_type(): void
    {
        Certification::query()->create([
            'slug' => 'hetero',
            'name' => 'Certificado Hetero',
            'active' => true,
        ]);

        $response = $this->from(route('quiz.register', ['certType' => 'hetero']))
            ->post(route('quiz.start'), [
                'first_name' => 'Ana',
                'last_name' => 'Lopez',
                'country_code' => 'CO',
                'document_type' => 'CC',
                'document' => '123456789',
                'cert_type' => 'hetero',
            ]);

        $response->assertRedirect(route('quiz.register', ['certType' => 'hetero']));
        $response->assertSessionHasErrors(['document']);
    }

    public function test_quiz_start_rejects_inactive_certification(): void
    {
        Certification::query()->create([
            'slug' => 'hetero',
            'name' => 'Certificado Hetero',
            'active' => false,
        ]);

        $response = $this->from(route('quiz.register', ['certType' => 'hetero']))
            ->post(route('quiz.start'), [
                'first_name' => 'Ana',
                'last_name' => 'Lopez',
                'country_code' => 'CO',
                'document_type' => 'CC',
                'document' => 'CC-123456789',
                'cert_type' => 'hetero',
            ]);

        $response->assertRedirect(route('quiz.register', ['certType' => 'hetero']));
        $response->assertSessionHasErrors(['cert_type']);
    }
}
