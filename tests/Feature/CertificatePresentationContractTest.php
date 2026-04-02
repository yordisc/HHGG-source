<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Certification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificatePresentationContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_result_page_contains_linkedin_contract_parameters(): void
    {
        $certificate = $this->createCertificate();

        $response = $this->get(route('result.show', ['serial' => $certificate->serial]));

        $response->assertOk();
        $response->assertSee('https://www.linkedin.com/profile/add?', false);
        $response->assertSee('startTask=CERTIFICATION_NAME', false);
        $response->assertSee('certId='.$certificate->serial, false);

        $organizationId = trim((string) env('LINKEDIN_ORG_ID', ''));
        if ($organizationId === '') {
            $response->assertDontSee('organizationId=', false);
            return;
        }

        $response->assertSee('organizationId='.$organizationId, false);
    }

    public function test_certificate_pdf_has_expected_filename_and_pdf_signature(): void
    {
        $certificate = $this->createCertificate();

        $response = $this->get(route('cert.pdf', ['serial' => $certificate->serial]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="'.$certificate->serial.'.pdf"');

        $raw = (string) $response->getContent();
        $this->assertStringStartsWith('%PDF', $raw);
    }

    private function createCertificate(): Certificate
    {
        $certification = Certification::query()->create([
            'slug' => 'hetero',
            'name' => 'Certificado Hetero',
            'active' => true,
        ]);

        return Certificate::query()->create([
            'serial' => 'CERT-2026-SE-ABC999',
            'certification_id' => $certification->id,
            'result_key' => 'hetero_exitoso',
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'country' => 'CO',
            'document_hash' => bcrypt('CC-123456789'),
            'doc_lookup_hash' => Certificate::documentLookupHash('CC-123456789'),
            'doc_partial' => '6789',
            'score_correct' => 25,
            'score_incorrect' => 5,
            'total_questions' => 30,
            'issued_at' => now(),
            'expires_at' => now()->addYear(),
            'last_attempt_at' => now(),
        ]);
    }
}
