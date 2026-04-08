<?php

namespace App\Console\Commands;

use App\Models\Certification;
use App\Support\CertificationDataRetentionService;
use Illuminate\Console\Command;

class TestPurgeExpiredCertificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'certificates:test-purge {certification : Certification slug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test purge logic to see what would be deleted (dry-run with details)';

    /**
     * Execute the console command.
     */
    public function handle(CertificationDataRetentionService $retentionService): int
    {
        $slug = $this->argument('certification');
        $certification = Certification::where('slug', $slug)->first();

        if (!$certification) {
            $this->error("Certification not found: {$slug}");
            return self::FAILURE;
        }

        $this->line('Testing purge logic');
        $this->info("Testing purge logic for certification: <fg=cyan>{$certification->name}</> ({$slug})\n");

        // Show certification settings
        $this->line("<fg=yellow>Certification Retention Settings:</>");
        $this->line("  Expiry Mode: <fg=cyan>{$certification->expiry_mode}</>");
        if ($certification->expiry_mode === 'fixed') {
            $this->line("  Expiry Days: <fg=cyan>{$certification->expiry_days}</>");
        }
        $this->line("  Manual Purge Enabled: <fg=cyan>".($certification->manual_user_data_purge_enabled ? 'Yes' : 'No')."</>");
        $this->line("  Allow Download After Deactivation: <fg=cyan>".($certification->allow_certificate_download_after_deactivation ? 'Yes' : 'No')."</>");

        // Get statistics
        $stats = $retentionService->getPurgeStatistics($certification);
        
        $this->line("\n<fg=yellow>Purge Statistics:</>");
        $this->line("  Expired Certificates: <fg=cyan>{$stats['expired_certificates']}</>");
        $this->line("  Certificates With Images: <fg=cyan>{$stats['certificates_with_images']}</>");

        if (($stats['expired_certificates'] ?? 0) === 0) {
            $this->info("\n✓ No expired data to purge");
            return self::SUCCESS;
        }

        // Show details of what would be deleted
        if ($this->input->isInteractive() && $this->confirm('Show details of certificates to be deleted?')) {
            $this->showExpiredCertificateDetails($certification, $retentionService);
        }

        $this->line("\n<fg=green>To execute the purge, run:</>");
        $this->line("<fg=blue>  php artisan certificates:purge-expired {$slug}</>");

        return self::SUCCESS;
    }

    /**
     * Show details of expired certificates.
     */
    private function showExpiredCertificateDetails(Certification $certification, CertificationDataRetentionService $retentionService): void
    {
        $this->line("\n<fg=yellow>Expired Certificates Details:</>");
        
        $expiredCertificates = $certification->certificates()
            ->whereNotNull('certification_expires_at')
            ->where('certification_expires_at', '<', now())
            ->latest('certification_expires_at')
            ->limit(10)
            ->get();

        if ($expiredCertificates->isEmpty()) {
            $this->line("  (No expired certificates to show)");
            return;
        }

        foreach ($expiredCertificates as $cert) {
            $this->line("  • Serial: {$cert->serial}, Expired: {$cert->certification_expires_at->format('Y-m-d H:i:s')}");
        }

        if ($expiredCertificates->count() === 10) {
            $this->line("  (... and more)");
        }
    }
}
