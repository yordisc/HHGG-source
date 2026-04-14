<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Certification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCertificatesIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_certificates_index_with_filters(): void
    {
        $certA = Certification::query()->create([
            'slug' => 'cert-a',
            'name' => 'Cert A',
            'active' => true,
        ]);

        $certB = Certification::query()->create([
            'slug' => 'cert-b',
            'name' => 'Cert B',
            'active' => true,
        ]);

        $active = $this->createCertificate($certA, 'CERT-2026-AA-111111', 'Ana');
        $revoked = $this->createCertificate($certB, 'CERT-2026-BB-222222', 'Luis');
        $revoked->update([
            'revoked_at' => now(),
            'revoked_reason' => 'Prueba',
        ]);

        $this->asAdmin()
            ->get(route('admin.certificates.index', ['status' => 'revoked']))
            ->assertOk()
            ->assertSee($revoked->serial)
            ->assertDontSee($active->serial);

        $this->asAdmin()
            ->get(route('admin.certificates.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee($active->serial)
            ->assertDontSee($revoked->serial);

        $this->asAdmin()
            ->get(route('admin.certificates.index', ['certification_id' => $certA->id]))
            ->assertOk()
            ->assertSee($active->serial)
            ->assertDontSee($revoked->serial);
    }

    public function test_admin_can_export_filtered_certificates_csv(): void
    {
        $certification = Certification::query()->create([
            'slug' => 'cert-csv',
            'name' => 'Cert CSV',
            'active' => true,
        ]);

        $active = $this->createCertificate($certification, 'CERT-2026-CSV-111111', 'Mario');
        $revoked = $this->createCertificate($certification, 'CERT-2026-CSV-222222', 'Julia');
        $revoked->update([
            'revoked_at' => now(),
            'revoked_reason' => 'Caso CSV',
        ]);

        $response = $this->asAdmin()
            ->get(route('admin.certificates.export.csv', ['status' => 'revoked']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString($revoked->serial, $csv);
        $this->assertStringNotContainsString($active->serial, $csv);
    }

    public function test_admin_can_get_certificates_api_json_with_filters(): void
    {
        $certA = Certification::query()->create([
            'slug' => 'cert-api-a',
            'name' => 'Cert API A',
            'active' => true,
        ]);

        $certB = Certification::query()->create([
            'slug' => 'cert-api-b',
            'name' => 'Cert API B',
            'active' => true,
        ]);

        $this->createCertificate($certA, 'CERT-2026-API-111111', 'Rosa');
        $revoked = $this->createCertificate($certB, 'CERT-2026-API-222222', 'Pepe');
        $revoked->update([
            'revoked_at' => now(),
            'revoked_reason' => 'Caso API',
        ]);

        $response = $this->asAdmin()
            ->getJson(route('admin.certificates.api.index', ['status' => 'revoked', 'per_page' => 10]));

        $response->assertOk();
        $response->assertJsonPath('filters.status', 'revoked');
        $response->assertJsonPath('pagination.per_page', 10);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.serial', $revoked->serial);
    }

    private function createCertificate(Certification $certification, string $serial, string $firstName): Certificate
    {
        return Certificate::query()->create([
            'serial' => $serial,
            'certification_id' => $certification->id,
            'result_key' => 'approved',
            'first_name' => $firstName,
            'last_name' => 'Tester',
            'country' => 'CO',
            'document_hash' => hash('sha256', $serial),
            'doc_lookup_hash' => Certificate::documentLookupHash($serial),
            'doc_partial' => '1234',
            'score_correct' => 8,
            'score_incorrect' => 2,
            'total_questions' => 10,
            'issued_at' => now(),
            'expires_at' => now()->addYear(),
            'last_attempt_at' => now(),
        ]);
    }
}
