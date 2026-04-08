<?php

namespace Tests\Unit;

use App\Models\Certification;
use App\Models\Certificate;
use App\Support\CertificationExpirationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificationExpirationServiceTest extends TestCase
{
    use RefreshDatabase;

    private CertificationExpirationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CertificationExpirationService::class);
    }

    /** @test */
    public function it_calculates_indefinite_expiry_as_null(): void
    {
        $certification = Certification::factory()->create([
            'expiry_mode' => 'indefinite',
            'expiry_days' => null,
        ]);

        $expiry = $this->service->calculateCertificationExpiryDate($certification);

        $this->assertNull($expiry);
    }

    /** @test */
    public function it_calculates_fixed_expiry_date(): void
    {
        $certification = Certification::factory()->create([
            'expiry_mode' => 'fixed',
            'expiry_days' => 30,
        ]);

        $issuedAt = Carbon::parse('2026-01-01 10:00:00');

        $expiry = $this->service->calculateCertificationExpiryDate($certification, $issuedAt);

        $this->assertNotNull($expiry);
        $this->assertEquals(
            $issuedAt->copy()->addDays(30)->toDateTimeString(),
            $expiry->toDateTimeString()
        );
    }

    /** @test */
    public function it_checks_if_certificate_is_expired(): void
    {
        $certification = Certification::factory()->create([
            'expiry_mode' => 'fixed',
            'expiry_days' => 30,
        ]);

        $expiredCert = Certificate::factory()->create([
            'certification_id' => $certification->id,
            'certification_expires_at' => now()->subDays(1),
        ]);

        $activeCert = Certificate::factory()->create([
            'certification_id' => $certification->id,
            'certification_expires_at' => now()->addDays(1),
        ]);

        $this->assertTrue($this->service->isCertificateExpired($expiredCert));
        $this->assertFalse($this->service->isCertificateExpired($activeCert));
    }

    /** @test */
    public function it_checks_if_download_is_expired(): void
    {
        $certification = Certification::factory()->create([
            'allow_certificate_download_after_deactivation' => false,
        ]);

        $expiredCert = Certificate::factory()->create([
            'certification_id' => $certification->id,
            'download_expires_at' => now()->subDays(1),
        ]);

        $this->assertTrue($this->service->isDownloadExpired($expiredCert));
    }

    /** @test */
    public function it_gets_days_until_expiry(): void
    {
        $certification = Certification::factory()->create([
            'expiry_mode' => 'fixed',
            'expiry_days' => 30,
        ]);

        $certificate = Certificate::factory()->create([
            'certification_id' => $certification->id,
            'certification_expires_at' => now()->addDays(15),
        ]);

        $days = $this->service->getDaysUntilExpiry($certificate);

        $this->assertIsInt($days);
        $this->assertGreaterThan(0, $days);
        $this->assertLessThanOrEqual(15, $days);
    }

    /** @test */
    public function it_returns_null_for_days_until_expiry_when_no_expiry(): void
    {
        $certificate = Certificate::factory()->create([
            'certification_expires_at' => null,
        ]);

        $days = $this->service->getDaysUntilExpiry($certificate);

        $this->assertNull($days);
    }

    /** @test */
    public function it_allows_historical_download(): void
    {
        $certification = Certification::factory()->create([
            'allow_certificate_download_after_deactivation' => true,
            'active' => false,
        ]);

        $this->assertTrue($this->service->allowsHistoricalDownload($certification));
    }

    /** @test */
    public function it_denies_historical_download_when_disabled(): void
    {
        $certification = Certification::factory()->create([
            'allow_certificate_download_after_deactivation' => false,
            'active' => false,
        ]);

        $this->assertFalse($this->service->allowsHistoricalDownload($certification));
    }
}
