<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Certification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCertificateRevocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_revoke_certificate_with_reason(): void
    {
        [$certification, $certificate] = $this->seedCertificate();

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.certifications.certificates.revoke', [$certification, $certificate]), [
                'reason' => 'Datos inconsistentes detectados en revisión manual.',
            ])
            ->assertRedirect(route('admin.certifications.edit', $certification));

        $certificate->refresh();

        $this->assertNotNull($certificate->revoked_at);
        $this->assertSame('Datos inconsistentes detectados en revisión manual.', $certificate->revoked_reason);
    }

    public function test_admin_can_restore_revoked_certificate(): void
    {
        [$certification, $certificate] = $this->seedCertificate();
        $certificate->update([
            'revoked_at' => now(),
            'revoked_reason' => 'Motivo inicial',
        ]);

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.certifications.certificates.restore', [$certification, $certificate]))
            ->assertRedirect(route('admin.certifications.edit', $certification));

        $certificate->refresh();

        $this->assertNull($certificate->revoked_at);
        $this->assertNull($certificate->revoked_reason);
    }

    private function seedCertificate(): array
    {
        $certification = Certification::query()->create([
            'slug' => 'cert-revocable',
            'name' => 'Certificación Revocable',
            'active' => true,
            'questions_required' => 10,
            'pass_score_percentage' => 70,
            'cooldown_days' => 30,
            'result_mode' => 'binary_threshold',
            'pdf_view' => 'pdf.certificate',
            'home_order' => 1,
        ]);

        $certificate = Certificate::query()->create([
            'serial' => 'CERT-2026-RV-ABC123',
            'certification_id' => $certification->id,
            'result_key' => 'approved',
            'first_name' => 'Lucia',
            'last_name' => 'Perez',
            'country' => 'CO',
            'document_hash' => hash('sha256', 'CC-11111111'),
            'doc_lookup_hash' => Certificate::documentLookupHash('CC-11111111'),
            'doc_partial' => '1111',
            'score_correct' => 9,
            'score_incorrect' => 1,
            'total_questions' => 10,
            'issued_at' => now(),
            'expires_at' => now()->addYear(),
            'last_attempt_at' => now(),
        ]);

        return [$certification, $certificate];
    }
}
