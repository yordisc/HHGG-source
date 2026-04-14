<?php

namespace Tests\Feature;

use App\Jobs\CleanExpiredCertificatesJob;
use App\Jobs\PurgeExpiredCertificationDataJob;
use App\Models\Certification;
use App\Models\Certificate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CertificationRetentionCommandTest extends TestCase
{
    use RefreshDatabase;
    use WithoutMiddleware;

    #[Test]
    public function purge_command_can_be_called(): void
    {
        $certification = Certification::factory()->create([
            'manual_user_data_purge_enabled' => true,
        ]);

        $this->artisan('certificates:purge-expired', [
            'certification' => $certification->slug,
            '--dry-run' => true,
            '--no-interaction' => true,
        ])
            ->assertExitCode(0);
    }

    #[Test]
    public function purge_command_queues_expired_certificates(): void
    {
        Queue::fake();

        $certification = Certification::factory()->create([
            'manual_user_data_purge_enabled' => true,
        ]);

        $this->artisan('certificates:purge-expired', [
            'certification' => $certification->slug,
            '--no-interaction' => true,
        ]);

        Queue::assertPushed(PurgeExpiredCertificationDataJob::class, function (PurgeExpiredCertificationDataJob $job) use ($certification): bool {
            return $job->certificationId() === $certification->id;
        });
    }

    #[Test]
    public function purge_command_respects_disabled_purge(): void
    {
        $certification = Certification::factory()->create([
            'manual_user_data_purge_enabled' => false,
        ]);

        Certificate::factory()->count(3)->create([
            'certification_id' => $certification->id,
            'certification_expires_at' => now()->subDays(1),
        ]);

        $this->artisan('certificates:purge-expired', [
            'certification' => $certification->slug,
            '--no-interaction' => true,
        ]);

        // Should not delete when disabled
        $this->assertDatabaseCount('certificates', 3);
    }

    #[Test]
    public function dry_run_does_not_delete(): void
    {
        $certification = Certification::factory()->create([
            'manual_user_data_purge_enabled' => true,
        ]);

        Certificate::factory()->count(3)->create([
            'certification_id' => $certification->id,
            'certification_expires_at' => now()->subDays(1),
        ]);

        $this->artisan('certificates:purge-expired', [
            'certification' => $certification->slug,
            '--dry-run' => true,
            '--no-interaction' => true,
        ]);

        // Dry run should not delete
        $this->assertDatabaseCount('certificates', 3);
    }

    #[Test]
    public function purge_all_handles_multiple_certifications(): void
    {
        Queue::fake();

        $cert1 = Certification::factory()->create([
            'manual_user_data_purge_enabled' => true,
        ]);
        $cert2 = Certification::factory()->create([
            'manual_user_data_purge_enabled' => true,
        ]);

        $this->artisan('certificates:purge-expired', [
            '--all' => true,
            '--no-interaction' => true,
        ]);

        Queue::assertPushed(PurgeExpiredCertificationDataJob::class, 2);
    }

    #[Test]
    public function test_purge_command_shows_statistics(): void
    {
        $certification = Certification::factory()->create([
            'manual_user_data_purge_enabled' => true,
            'expiry_mode' => 'fixed',
            'expiry_days' => 30,
        ]);

        Certificate::factory()->count(5)->create([
            'certification_id' => $certification->id,
            'certification_expires_at' => now()->subDays(1),
        ]);

        $this->artisan('certificates:test-purge', [
            'certification' => $certification->slug,
            '--no-interaction' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutput('Testing purge logic');
    }

    #[Test]
    public function purge_command_queues_job_for_any_certification_state(): void
    {
        Queue::fake();

        $certification = Certification::factory()->create([
            'manual_user_data_purge_enabled' => true,
            'allow_certificate_download_after_deactivation' => false,
        ]);

        $this->artisan('certificates:purge-expired', [
            'certification' => $certification->slug,
            '--no-interaction' => true,
        ]);

        Queue::assertPushed(PurgeExpiredCertificationDataJob::class, function (PurgeExpiredCertificationDataJob $job) use ($certification): bool {
            return $job->certificationId() === $certification->id;
        });
    }

    #[Test]
    public function purge_command_queues_job_for_indefinite_expiry(): void
    {
        Queue::fake();

        $certification = Certification::factory()->create([
            'manual_user_data_purge_enabled' => true,
            'expiry_mode' => 'indefinite',
        ]);

        $this->artisan('certificates:purge-expired', [
            'certification' => $certification->slug,
            '--no-interaction' => true,
        ]);

        Queue::assertPushed(PurgeExpiredCertificationDataJob::class, function (PurgeExpiredCertificationDataJob $job) use ($certification): bool {
            return $job->certificationId() === $certification->id;
        });
    }

    #[Test]
    public function clean_command_queues_job(): void
    {
        Queue::fake();

        $this->artisan('certificates:clean')
            ->assertExitCode(0);

        Queue::assertPushed(CleanExpiredCertificatesJob::class);
    }

    #[Test]
    public function purge_fails_with_invalid_certification(): void
    {
        $this->artisan('certificates:purge-expired', [
            'certification' => 'nonexistent-slug',
            '--no-interaction' => true,
        ])
            ->assertFailed();
    }
}
