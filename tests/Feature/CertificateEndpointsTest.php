<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Certification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_result_and_certificate_pages_return_ok(): void
    {
        $certificate = $this->createCertificate();

        $this->get(route('result.show', ['serial' => $certificate->serial]))
            ->assertOk();

        $this->get(route('cert.show', ['serial' => $certificate->serial]))
            ->assertOk();
    }

    public function test_certificate_pdf_endpoint_returns_pdf_headers(): void
    {
        $certificate = $this->createCertificate();

        $response = $this->get(route('cert.pdf', ['serial' => $certificate->serial]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition');
    }

    private function createCertificate(): Certificate
    {
        $certification = Certification::query()->create([
            'slug' => 'hetero',
            'name' => 'Certificado Hetero',
            'active' => true,
        ]);

        return Certificate::create([
            'serial' => 'CERT-2026-SE-XYZ987',
            'certification_id' => $certification->id,
            'result_key' => 'hetero_exitoso',
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'country' => 'CO',
            'document_hash' => bcrypt('ABC12345'),
            'doc_lookup_hash' => Certificate::documentLookupHash('ABC12345'),
            'doc_partial' => '2345',
            'score_correct' => 25,
            'score_incorrect' => 5,
            'total_questions' => 30,
            'issued_at' => now(),
            'expires_at' => now()->addYear(),
            'last_attempt_at' => now(),
        ]);
    }
}
