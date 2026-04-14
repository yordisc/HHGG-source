<?php

namespace Tests\Unit;

use App\Actions\CreateCertificateAction;
use App\Models\Certificate;
use App\Models\Certification;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
