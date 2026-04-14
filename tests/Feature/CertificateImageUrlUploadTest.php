<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Certification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CertificateImageUrlUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_certificate_image_from_valid_url(): void
    {
        $certificate = $this->createCertificate();

        Http::fake([
            'https://example.com/image.png' => Http::response('', 200, [
                'Content-Type' => 'image/png',
            ]),
        ]);

        $this->withSession(['admin_authenticated' => true])
            ->postJson(route('cert.image.store', ['serial' => $certificate->serial]), [
                'image_url' => 'https://example.com/image.png',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('image_url', 'https://example.com/image.png');

        $certificate->refresh();
        $this->assertSame('https://example.com/image.png', $certificate->certificate_image_path);
        $this->assertNotNull($certificate->image_updated_at);
    }

    public function test_admin_cannot_store_non_image_url(): void
    {
        $certificate = $this->createCertificate();

        Http::fake([
            'https://example.com/not-image' => Http::response('html', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $this->withSession(['admin_authenticated' => true])
            ->postJson(route('cert.image.store', ['serial' => $certificate->serial]), [
                'image_url' => 'https://example.com/not-image',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    private function createCertificate(): Certificate
    {
        $certification = Certification::query()->create([
            'slug' => 'cert-image',
            'name' => 'Cert Image',
            'active' => true,
        ]);

        return Certificate::query()->create([
            'serial' => 'CERT-2026-IM-AB12CD',
            'certification_id' => $certification->id,
            'result_key' => 'approved',
            'first_name' => 'Ana',
            'last_name' => 'Tester',
            'country' => 'CO',
            'document_hash' => hash('sha256', 'doc-hash'),
            'doc_lookup_hash' => Certificate::documentLookupHash('ABC12345'),
            'doc_partial' => '2345',
            'score_correct' => 9,
            'score_incorrect' => 1,
            'total_questions' => 10,
            'issued_at' => now(),
            'expires_at' => now()->addYear(),
            'last_attempt_at' => now(),
        ]);
    }
}
