<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Certification;
use App\Support\CertificateIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CertificatePresentationContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_result_page_contains_linkedin_contract_parameters(): void
    {
        $certificate = $this->createCertificate();
        $verificationUrl = app(CertificateIntegrityService::class)->verificationUrl($certificate);
        $certificatePdfUrl = route('cert.pdf', ['serial' => $certificate->serial]);

        $response = $this->get(route('result.show', ['serial' => $certificate->serial]));

        $response->assertOk();
        $response->assertSee('https://www.linkedin.com/profile/add?', false);
        $response->assertSee('startTask=CERTIFICATION_NAME', false);
        $response->assertSee('certId=' . $certificate->serial, false);
        $response->assertSee($verificationUrl, false);
        $response->assertSee($certificatePdfUrl, false);

        $organizationId = trim((string) env('LINKEDIN_ORG_ID', ''));
        if ($organizationId === '') {
            $response->assertDontSee('organizationId=', false);
            return;
        }

        $response->assertSee('organizationId=' . $organizationId, false);
    }

    public function test_certificate_pdf_has_expected_filename_and_pdf_signature(): void
    {
        $this->fakeQrDownload();

        $certificate = $this->createCertificate();

        $response = $this->get(route('cert.pdf', ['serial' => $certificate->serial]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="' . $certificate->serial . '.pdf"');

        $raw = (string) $response->getContent();
        $this->assertStringStartsWith('%PDF', $raw);
    }

    public function test_signed_verification_route_accepts_valid_token(): void
    {
        $certificate = $this->createCertificate();
        $token = app(CertificateIntegrityService::class)->verificationToken($certificate);

        $response = $this->get(route('cert.verify', [
            'serial' => $certificate->serial,
            'token' => $token,
        ]));

        $response->assertOk();
        $response->assertSee('Verificación firmada válida.', false);
    }

    public function test_signed_verification_route_rejects_invalid_token(): void
    {
        $certificate = $this->createCertificate();

        $response = $this->get(route('cert.verify', [
            'serial' => $certificate->serial,
            'token' => 'token-invalido',
        ]));

        $response->assertForbidden();
    }

    public function test_signed_verification_route_returns_gone_for_revoked_certificate(): void
    {
        $certificate = $this->createCertificate();
        $certificate->update([
            'revoked_at' => now(),
            'revoked_reason' => 'Revocación de prueba',
        ]);

        $token = app(CertificateIntegrityService::class)->verificationToken($certificate);

        $response = $this->get(route('cert.verify', [
            'serial' => $certificate->serial,
            'token' => $token,
        ]));

        $response->assertStatus(410);
    }

    public function test_signed_verification_route_returns_structured_json_when_requested(): void
    {
        $certificate = $this->createCertificate();
        $token = app(CertificateIntegrityService::class)->verificationToken($certificate);

        $response = $this->getJson(route('cert.verify', [
            'serial' => $certificate->serial,
            'token' => $token,
            'format' => 'json',
        ]));

        $response->assertOk();
        $response->assertJson([
            'verified' => true,
            'serial' => $certificate->serial,
            'status' => 'active',
        ]);
        $response->assertJsonStructure([
            'verification_checked_at',
            'integrity_hash',
            'certificate' => ['full_name', 'result_key', 'issued_at', 'expires_at'],
        ]);
    }

    public function test_signed_verification_json_includes_revocation_reason(): void
    {
        $certificate = $this->createCertificate();
        $certificate->update([
            'revoked_at' => now(),
            'revoked_reason' => 'Fraude documental detectado',
        ]);

        $token = app(CertificateIntegrityService::class)->verificationToken($certificate);

        $response = $this->getJson(route('cert.verify', [
            'serial' => $certificate->serial,
            'token' => $token,
            'format' => 'json',
        ]));

        $response->assertStatus(410);
        $response->assertJson([
            'verified' => false,
            'error' => 'certificate_revoked',
            'revoked_reason' => 'Fraude documental detectado',
            'status' => 'revoked',
        ]);
    }

    private function fakeQrDownload(): void
    {
        Http::fake([
            'https://quickchart.io/qr*' => Http::response(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO5qZ4kAAAAASUVORK5CYII='), 200, [
                'Content-Type' => 'image/png',
            ]),
        ]);
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
