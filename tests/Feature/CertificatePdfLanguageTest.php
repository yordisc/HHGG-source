<?php

namespace Tests\Feature;

use App\Models\Certificate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificatePdfLanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_is_always_generated_in_english_regardless_of_session_locale(): void
    {
        $certificate = Certificate::create([
            'serial' => 'TEST-2026-XX-ABCDEF',
            'certification_id' => null,
            'result_key' => 'passed',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'country' => 'Mexico',
            'country_code' => 'MX',
            'document_type' => 'passport',
            'document_hash' => 'hash123',
            'doc_lookup_hash' => 'lookup123',
            'identity_lookup_hash' => 'identity123',
            'doc_partial' => '1234',
            'score_correct' => 25,
            'score_incorrect' => 5,
            'total_questions' => 30,
            'score_numeric' => 83.33,
            'issued_at' => now(),
            'completed_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        // Test with Spanish locale
        session(['locale' => 'es']);

        $response = $this->get(route('cert.pdf', $certificate->serial));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');

        // Check that response contains PDF binary content (rough check)
        $content = $response->getContent();
        $this->assertStringContainsString('%PDF', $content);

        // Test with French locale
        session(['locale' => 'fr']);

        $response = $this->get(route('cert.pdf', $certificate->serial));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('%PDF', $response->getContent());

        // Test with English locale (should be same as Spanish/French)
        session(['locale' => 'en']);

        $response = $this->get(route('cert.pdf', $certificate->serial));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('%PDF', $response->getContent());
    }
}
