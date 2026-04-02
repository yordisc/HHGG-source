<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Certification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuizEligibilityEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_eligibility_endpoint_returns_422_for_incomplete_payload(): void
    {
        $response = $this->postJson(route('quiz.eligibility'), [
            'country_code' => 'CO',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'can_start' => false,
            ]);
    }

    public function test_eligibility_endpoint_returns_can_start_true_for_valid_payload(): void
    {
        Certification::query()->create([
            'slug' => 'hetero',
            'name' => 'Certificado Hetero',
            'active' => true,
            'cooldown_days' => 30,
        ]);

        $response = $this->postJson(route('quiz.eligibility'), [
            'country_code' => 'CO',
            'document_type' => 'CC',
            'document' => 'CC-123456789',
            'cert_type' => 'hetero',
        ]);

        $response->assertOk()
            ->assertJson([
                'can_start' => true,
            ]);
    }

    public function test_eligibility_endpoint_returns_blocked_when_cooldown_is_active(): void
    {
        $certification = Certification::query()->create([
            'slug' => 'hetero',
            'name' => 'Certificado Hetero',
            'active' => true,
            'cooldown_days' => 30,
        ]);

        $countryCode = 'CO';
        $documentType = 'CC';
        $document = 'CC-123456789';

        Certificate::query()->create([
            'serial' => 'CERT-'.date('Y').'-HE-'.Str::upper(Str::random(6)),
            'certification_id' => $certification->id,
            'result_key' => 'hetero_exitoso',
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'country' => 'Colombia',
            'country_code' => $countryCode,
            'document_type' => $documentType,
            'document_hash' => bcrypt($document),
            'doc_lookup_hash' => Certificate::documentLookupHash($document),
            'identity_lookup_hash' => Certificate::identityLookupHash($countryCode, $documentType, $document),
            'doc_partial' => Certificate::documentPartial($document),
            'score_correct' => 20,
            'score_incorrect' => 10,
            'total_questions' => 30,
            'score_numeric' => 66.67,
            'issued_at' => now()->subDays(3),
            'completed_at' => now()->subDays(3),
            'next_available_at' => now()->addDays(27),
            'expires_at' => now()->addYear(),
            'last_attempt_at' => now()->subDays(3),
        ]);

        $response = $this->postJson(route('quiz.eligibility'), [
            'country_code' => $countryCode,
            'document_type' => $documentType,
            'document' => $document,
            'cert_type' => 'hetero',
        ]);

        $response->assertOk()
            ->assertJson([
                'can_start' => false,
            ])
            ->assertJsonStructure([
                'can_start',
                'next_available_at',
                'message',
            ]);
    }
}
