<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Certification;
use App\Support\CertificateIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Phase 3: Error Flow Validation Tests
 *
 * These tests validate that there are no unexpected 404, 403, or 500 errors
 * in certificate verification and display flows.
 *
 * Requirements:
 * - Confirm cert.show, cert.verify, cert.pdf don't crash on missing certificates
 * - Confirm proper HTTP status codes are returned for error cases
 * - Confirm fallback mechanisms work when external services fail
 * - Confirm JSON responses include proper error information
 */
class CertificatePhase3ErrorFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cert_show_returns_404_for_unknown_serial_with_no_exceptions(): void
    {
        $response = $this->get(route('cert.show', ['serial' => 'UNKNOWN-CERT']));

        $response->assertNotFound();
        $response->assertStatus(404);
    }

    public function test_cert_verify_returns_404_for_unknown_serial_with_no_exceptions(): void
    {
        $response = $this->get(route('cert.verify', [
            'serial' => 'UNKNOWN-CERT',
            'token' => 'any-token',
        ]));

        $response->assertNotFound();
        $response->assertStatus(404);
    }

    public function test_cert_pdf_returns_404_for_unknown_serial_with_no_exceptions(): void
    {
        $response = $this->get(route('cert.pdf', ['serial' => 'UNKNOWN-CERT']));

        $response->assertNotFound();
        $response->assertStatus(404);
    }

    public function test_cert_verify_unknown_serial_returns_404_even_with_valid_token_format(): void
    {
        $certificate = $this->createCertificate();
        $validToken = app(CertificateIntegrityService::class)->verificationToken($certificate);

        // Try to verify with correct token but wrong serial
        $response = $this->get(route('cert.verify', [
            'serial' => 'WRONG-SERIAL-' . $certificate->serial,
            'token' => $validToken,
        ]));

        $response->assertNotFound();
    }

    public function test_cert_pdf_works_when_qr_service_fails(): void
    {
        // Simulate QuickChart service failure
        Http::fake([
            'https://quickchart.io/qr*' => Http::response('Service Unavailable', 503),
        ]);

        $certificate = $this->createCertificate();

        $response = $this->get(route('cert.pdf', ['serial' => $certificate->serial]));

        // Should still return OK 200 with PDF even if QR fails
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');

        $raw = (string) $response->getContent();
        $this->assertStringStartsWith('%PDF', $raw, 'Response should be a valid PDF even if QR generation fails');
    }

    public function test_cert_pdf_works_when_qr_service_times_out(): void
    {
        // Simulate timeout by returning 500
        Http::fake([
            'https://quickchart.io/qr*' => Http::response('Internal Server Error', 500),
        ]);

        $certificate = $this->createCertificate();

        $response = $this->get(route('cert.pdf', ['serial' => $certificate->serial]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_cert_verify_json_response_for_invalid_serial(): void
    {
        $response = $this->getJson(route('cert.verify', [
            'serial' => 'UNKNOWN-CERT',
            'token' => 'any-token',
            'format' => 'json',
        ]));

        $response->assertNotFound();
    }

    public function test_cert_verify_json_response_includes_all_required_fields_on_success(): void
    {
        $certificate = $this->createCertificate();
        $token = app(CertificateIntegrityService::class)->verificationToken($certificate);

        $response = $this->getJson(route('cert.verify', [
            'serial' => $certificate->serial,
            'token' => $token,
            'format' => 'json',
        ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'verified',
            'serial',
            'status',
            'integrity_hash',
            'certificate' => [
                'full_name',
                'result_key',
            ],
        ]);
    }

    public function test_cert_verify_json_response_includes_error_on_invalid_token(): void
    {
        $certificate = $this->createCertificate();

        $response = $this->getJson(route('cert.verify', [
            'serial' => $certificate->serial,
            'token' => 'invalid-token-xyz',
            'format' => 'json',
        ]));

        $response->assertForbidden();
        $response->assertJson([
            'verified' => false,
            'error' => 'invalid_token',
            'serial' => $certificate->serial,
        ]);
    }

    public function test_cert_verify_json_response_includes_error_on_revoked(): void
    {
        $certificate = $this->createCertificate();
        $certificate->update([
            'revoked_at' => now(),
            'revoked_reason' => 'Test revocation',
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
            'status' => 'revoked',
            'revoked_reason' => 'Test revocation',
        ]);
    }

    public function test_cert_show_displays_revocation_status(): void
    {
        $certificate = $this->createCertificate();
        $certificate->update([
            'revoked_at' => now(),
            'revoked_reason' => 'Documento falsificado',
        ]);

        $response = $this->get(route('cert.show', ['serial' => $certificate->serial]));

        $response->assertOk();
        // Verify-url verification is still available on show page
        $response->assertSee($certificate->serial, false);
    }

    public function test_result_page_handles_missing_certificate(): void
    {
        $response = $this->get(route('result.show', ['serial' => 'MISSING-CERT']));

        $response->assertNotFound();
    }

    public function test_search_with_missing_certificate_serial_returns_redirect(): void
    {
        $response = $this->from(route('home'))
            ->post(route('search'), ['query' => 'SERIAL-NOT-EXISTS']);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('search_message');
    }

    public function test_integrity_metadata_is_ensured_before_rendering(): void
    {
        $certificate = $this->createCertificate();

        // Test that cert.show calls ensureIntegrityMetadata
        $response = $this->get(route('cert.show', ['serial' => $certificate->serial]));

        // If it crashes, test fails
        $response->assertOk();
        // Should have verification URL available
        $verificationUrl = app(CertificateIntegrityService::class)->verificationUrl($certificate);
        $response->assertSee($verificationUrl, false);
    }

    public function test_certificate_with_far_future_expiry_displays_without_error(): void
    {
        $certificate = $this->createCertificate();
        $certificate->update(['expires_at' => now()->addYears(10)]);

        $response = $this->get(route('cert.show', ['serial' => $certificate->serial]));

        $response->assertOk();
    }

    public function test_certificate_with_past_issued_at_displays_without_error(): void
    {
        $certificate = $this->createCertificate();
        $certificate->update(['issued_at' => now()->subYears(5)]);

        $response = $this->get(route('cert.show', ['serial' => $certificate->serial]));

        $response->assertOk();
    }

    public function test_pdf_generation_does_not_crash_with_special_characters_in_name(): void
    {
        $this->fakeQrDownload();

        $certificate = $this->createCertificate();
        $certificate->update([
            'first_name' => 'José María',
            'last_name' => "O'Connor-São Paulo",
        ]);

        $response = $this->get(route('cert.pdf', ['serial' => $certificate->serial]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_verify_route_with_malformed_token_returns_403(): void
    {
        $certificate = $this->createCertificate();

        $response = $this->get(route('cert.verify', [
            'serial' => $certificate->serial,
            'token' => 'x',
        ]));

        $response->assertForbidden();
    }

    public function test_verify_route_with_whitespace_token_returns_403(): void
    {
        $certificate = $this->createCertificate();

        $response = $this->get(route('cert.verify', [
            'serial' => $certificate->serial,
            'token' => '   ',
        ]));

        $response->assertForbidden();
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
            'slug' => 'phase3-test',
            'name' => 'Phase 3 Test Certification',
            'active' => true,
        ]);

        return Certificate::query()->create([
            'serial' => 'CERT-PHASE3-' . now()->timestamp,
            'certification_id' => $certification->id,
            'result_key' => 'test_result',
            'first_name' => 'Test',
            'last_name' => 'User',
            'country' => 'US',
            'document_hash' => bcrypt('DOC-123'),
            'doc_lookup_hash' => Certificate::documentLookupHash('DOC-123'),
            'doc_partial' => '123',
            'score_correct' => 20,
            'score_incorrect' => 10,
            'total_questions' => 30,
            'issued_at' => now(),
            'expires_at' => now()->addYear(),
        ]);
    }
}
