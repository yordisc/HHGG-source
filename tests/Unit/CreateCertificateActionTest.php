<?php

namespace Tests\Unit;

use App\Actions\CreateCertificateAction;
use App\Models\Certificate;
use App\Models\Certification;
use App\Support\CertificateIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CreateCertificateActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_certificate_for_passed_attempt(): void
    {
        $certification = Certification::factory()->create([
            'slug' => 'hetero',
            'active' => true,
            'result_mode' => 'binary_threshold',
        ]);

        $certificate = $this->executeAction($certification, false, 92.5, 'scoring', 'Weighted score');

        $this->assertDatabaseCount('certificates', 1);
        $this->assertSame('hetero_exitoso', $certificate->result_key);
        $this->assertSame('scoring', $certificate->result_decision_source);
        $this->assertSame('Weighted score', $certificate->result_decision_reason);
        $this->assertSame('92.50', $certificate->score_numeric);
    }

    public function test_it_creates_certificate_for_failed_attempt(): void
    {
        $certification = Certification::factory()->create([
            'slug' => 'hetero',
            'active' => true,
            'result_mode' => 'binary_threshold',
        ]);

        $certificate = $this->executeAction($certification, true, 0.0, 'scoring', 'Weighted score');

        $this->assertSame('hetero_rebeldon', $certificate->result_key);
        $this->assertSame('0.00', $certificate->score_numeric);
        $this->assertNotNull($certificate->content_hash);
        $this->assertNotNull($certificate->verification_token_hash);
    }

    public function test_it_creates_certificate_for_sudden_death_attempt(): void
    {
        $certification = Certification::factory()->create([
            'slug' => 'hetero',
            'active' => true,
            'result_mode' => 'binary_threshold',
        ]);

        $certificate = $this->executeAction($certification, true, 0.0, 'sudden_death', 'Pregunta crítica fallida');

        $this->assertSame('sudden_death', $certificate->result_decision_source);
        $this->assertSame('Pregunta crítica fallida', $certificate->result_decision_reason);
        $this->assertSame('hetero_rebeldon', $certificate->result_key);
    }

    public function test_it_rolls_back_transaction_when_integrity_fails(): void
    {
        $certification = Certification::factory()->create([
            'slug' => 'hetero',
            'active' => true,
            'result_mode' => 'binary_threshold',
        ]);

        $this->mock(CertificateIntegrityService::class, function ($mock): void {
            $mock->shouldReceive('contentHash')
                ->once()
                ->andThrow(new \RuntimeException('Integrity service failed.'));
        });

        try {
            $this->executeAction($certification, false, 90.0, 'scoring', 'Rollback test');
            $this->fail('Se esperaba excepcion del servicio de integridad.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Integrity service failed.', $exception->getMessage());
        }

        $this->assertDatabaseEmpty('certificates');
    }

    public function test_it_retries_when_serial_collides(): void
    {
        $certification = Certification::factory()->create([
            'slug' => 'hetero',
            'active' => true,
            'result_mode' => 'binary_threshold',
        ]);

        $prefix = 'CERT-' . date('Y') . '-HE-';

        Certificate::query()->create([
            'serial' => $prefix . 'ABCDEF',
            'certification_id' => $certification->id,
            'result_key' => 'hetero_exitoso',
            'first_name' => 'Existing',
            'last_name' => 'Record',
            'country' => 'Colombia',
            'country_code' => 'CO',
            'document_type' => 'CC',
            'document_hash' => 'doc-existing',
            'doc_lookup_hash' => 'lookup-existing',
            'identity_lookup_hash' => 'identity-existing',
            'doc_partial' => '0001',
            'score_correct' => 1,
            'score_incorrect' => 0,
            'total_questions' => 1,
            'score_numeric' => 100,
            'issued_at' => now(),
            'completed_at' => now(),
            'next_available_at' => now()->addDays(30),
            'expires_at' => now()->addYear(),
            'last_attempt_at' => now(),
            'result_decision_source' => 'seed',
            'result_decision_reason' => 'seed',
        ]);

        Str::createRandomStringsUsingSequence(['ABCDEF', 'UVWXYZ']);

        try {
            $certificate = $this->executeAction($certification, false, 92.5, 'scoring', 'Collision retry');
        } finally {
            Str::createRandomStringsNormally();
        }

        $this->assertSame($prefix . 'UVWXYZ', $certificate->serial);
        $this->assertDatabaseCount('certificates', 2);
    }

    private function executeAction(Certification $certification, bool $failed, float $scoreNumeric, string $source, string $reason): Certificate
    {
        return app(CreateCertificateAction::class)->execute(
            $certification,
            [
                'first_name' => 'Ana',
                'last_name' => 'Lopez',
                'country' => 'Colombia',
                'country_code' => 'CO',
                'document_type' => 'CC',
                'document_hash' => 'doc-hash',
                'doc_lookup_hash' => 'lookup-hash',
                'identity_lookup_hash' => 'identity-hash',
                'doc_partial' => '6789',
            ],
            'hetero',
            'binary_threshold',
            [],
            $failed,
            $scoreNumeric,
            8,
            2,
            10,
            now(),
            30,
            $source,
            $reason,
        );
    }
}
