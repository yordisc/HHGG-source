<?php

namespace Tests\Feature;

use App\Console\Commands\PurgeExpiredCertificationsCommand;
use App\Models\Certification;
use App\Models\Certificate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
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
    public function purge_command_deletes_expired_certificates(): void
    {
        $certification = Certification::factory()->create([
            'manual_user_data_purge_enabled' => true,
        ]);

        Certificate::factory()->count(3)->create([
            'certification_id' => $certification->id,
            'certification_expires_at' => now()->subDays(1),
        ]);

        Certificate::factory()->count(2)->create([
            'certification_id' => $certification->id,
            'certification_expires_at' => now()->addDays(1),
        ]);

        $this->artisan('certificates:purge-expired', [
            'certification' => $certification->slug,
            '--no-interaction' => true,
        ]);

        $this->assertDatabaseCount('certificates', 2);
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
        $cert1 = Certification::factory()->create([
            'manual_user_data_purge_enabled' => true,
        ]);
        $cert2 = Certification::factory()->create([
            'manual_user_data_purge_enabled' => true,
        ]);

        Certificate::factory()->count(2)->create([
            'certification_id' => $cert1->id,
            'certification_expires_at' => now()->subDays(1),
        ]);

        Certificate::factory()->count(3)->create([
            'certification_id' => $cert2->id,
            'certification_expires_at' => now()->subDays(1),
        ]);

        $this->artisan('certificates:purge-expired', [
            '--all' => true,
            '--no-interaction' => true,
        ]);

        $this->assertDatabaseCount('certificates', 0);
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
    public function purge_respects_download_expiry_window(): void
    {
        $certification = Certification::factory()->create([
            'manual_user_data_purge_enabled' => true,
            'allow_certificate_download_after_deactivation' => false,
        ]);

        // Certificate where both are expired
        Certificate::factory()->create([
            'certification_id' => $certification->id,
            'certification_expires_at' => now()->subDays(1),
            'download_expires_at' => now()->subDays(1),
        ]);

        // Certificate where cert is expired but download is active
        Certificate::factory()->create([
            'certification_id' => $certification->id,
            'certification_expires_at' => now()->subDays(1),
            'download_expires_at' => now()->addDays(1),
        ]);

        $this->artisan('certificates:purge-expired', [
            'certification' => $certification->slug,
            '--no-interaction' => true,
        ]);

        // Both should be deleted based on certification expiry
        $this->assertDatabaseCount('certificates', 0);
    }

    #[Test]
    public function purge_handles_indefinite_expiry(): void
    {
        $certification = Certification::factory()->create([
            'manual_user_data_purge_enabled' => true,
            'expiry_mode' => 'indefinite',
        ]);

        Certificate::factory()->count(5)->create([
            'certification_id' => $certification->id,
            'certification_expires_at' => null,
        ]);

        $this->artisan('certificates:purge-expired', [
            'certification' => $certification->slug,
            '--no-interaction' => true,
        ]);

        // Indefinite should never expire, so none deleted
        $this->assertDatabaseCount('certificates', 5);
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
