<?php

namespace Tests\Unit;

use App\Models\Certification;
use App\Models\Certificate;
use App\Support\CertificationDataRetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CertificationDataRetentionServiceTest extends TestCase
{
    use RefreshDatabase;

    private CertificationDataRetentionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CertificationDataRetentionService::class);
    }

    #[Test]
    public function it_purges_expired_certification_data(): void
    {
        $certification = Certification::factory()->create([
            'expiry_mode' => 'fixed',
            'manual_user_data_purge_enabled' => true,
            'active' => false,
        ]);

        // Create expired certificates
        Certificate::factory()->count(3)->create([
            'certification_id' => $certification->id,
            'certification_expires_at' => now()->subDays(1),
        ]);

        // Create active certificates (should not be deleted)
        Certificate::factory()->count(2)->create([
            'certification_id' => $certification->id,
            'certification_expires_at' => now()->addDays(1),
        ]);

        $stats = $this->service->purgeExpiredCertificationData($certification);

        $this->assertEquals(3, $stats['certificates_deleted']);
    }

    #[Test]
    public function it_does_not_purge_when_disabled(): void
    {
        $certification = Certification::factory()->create([
            'manual_user_data_purge_enabled' => false,
        ]);

        Certificate::factory()->count(3)->create([
            'certification_id' => $certification->id,
            'certification_expires_at' => now()->subDays(1),
        ]);

        $stats = $this->service->purgeExpiredCertificationData($certification);

        $this->assertEquals(0, $stats['certificates_deleted']);
        $this->assertDatabaseCount('certificates', 3);
    }

    #[Test]
    public function it_manually_purges_user_data(): void
    {
        $certification = Certification::factory()->create([
            'manual_user_data_purge_enabled' => true,
        ]);

        Certificate::factory()->count(3)->create([
            'certification_id' => $certification->id,
            'certification_expires_at' => now()->subDays(1),
        ]);

        $stats = $this->service->manuallyPurgeUserData($certification);

        $this->assertEquals(3, $stats['certificates_deleted']);
        $this->assertDatabaseCount('certificates', 0);
    }

    #[Test]
    public function it_gets_purge_statistics(): void
    {
        $certification = Certification::factory()->create([
            'expiry_mode' => 'fixed',
            'expiry_days' => 30,
        ]);

        Certificate::factory()->count(5)->create([
            'certification_id' => $certification->id,
            'certification_expires_at' => now()->subDays(1),
        ]);

        Certificate::factory()->count(3)->create([
            'certification_id' => $certification->id,
            'certification_expires_at' => now()->addDays(1),
        ]);

        $stats = $this->service->getPurgeStatistics($certification);

        $this->assertEquals(5, $stats['expired_certificates']);
    }

    #[Test]
    public function it_handles_indefinite_expiry_correctly(): void
    {
        $certification = Certification::factory()->create([
            'expiry_mode' => 'indefinite',
            'manual_user_data_purge_enabled' => true,
        ]);

        Certificate::factory()->count(5)->create([
            'certification_id' => $certification->id,
            'certification_expires_at' => null,
        ]);

        $stats = $this->service->purgeExpiredCertificationData($certification);

        // Should not delete indefinite certificates
        $this->assertEquals(0, $stats['certificates_deleted']);
    }

    #[Test]
    public function it_respects_download_expiry_window(): void
    {
        $certification = Certification::factory()->create([
            'allow_certificate_download_after_deactivation' => false,
            'manual_user_data_purge_enabled' => true,
        ]);

        // Create certificate with download expired
        Certificate::factory()->create([
            'certification_id' => $certification->id,
            'certification_expires_at' => now()->subDays(1),
            'download_expires_at' => now()->subDays(1),
        ]);

        // Create certificate with download still active
        Certificate::factory()->create([
            'certification_id' => $certification->id,
            'certification_expires_at' => now()->subDays(1),
            'download_expires_at' => now()->addDays(1),
        ]);

        $stats = $this->service->purgeExpiredCertificationData($certification);

        // Should delete based on certification expiry
        $this->assertEquals(0, $stats['certificates_deleted']);
    }
}
