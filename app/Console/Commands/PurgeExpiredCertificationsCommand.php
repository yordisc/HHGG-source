<?php

namespace App\Console\Commands;

use App\Jobs\PurgeExpiredCertificationDataJob;
use App\Models\Certification;
use App\Support\CertificationDataRetentionService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class PurgeExpiredCertificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'certificates:purge-expired {certification? : Optional certification slug}
                                                       {--all : Purge all enabled certifications}
                                                       {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge expired certificate data according to retention settings';

    /**
     * Execute the console command.
     */
    public function handle(CertificationDataRetentionService $retentionService): int
    {
        $dryRun = $this->option('dry-run');
        $all = $this->option('all');
        $certificationSlug = $this->argument('certification');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No data will be deleted');
        }

        if ($all) {
            return $this->purgeAll($retentionService, $dryRun);
        } elseif ($certificationSlug) {
            return $this->purgeCertification($retentionService, $certificationSlug, $dryRun);
        } else {
            return $this->promptForCertification($retentionService, $dryRun);
        }
    }

    /**
     * Purge all enabled certifications.
     */
    private function purgeAll(CertificationDataRetentionService $retentionService, bool $dryRun): int
    {
        $certifications = Certification::where('manual_user_data_purge_enabled', true)
            ->orderBy('name')
            ->get();

        if ($certifications->isEmpty()) {
            $this->warn('No certifications found with manual_user_data_purge_enabled = true');
            return self::FAILURE;
        }

        if (!$dryRun) {
            foreach ($certifications as $certification) {
                dispatch(new PurgeExpiredCertificationDataJob($certification->id));
            }

            $this->info("Queued purge jobs for {$certifications->count()} certifications.");

            return self::SUCCESS;
        }

        $this->info("Found {$certifications->count()} certifications to process...\n");

        $totalStats = [
            'certificates_deleted' => 0,
            'images_deleted' => 0,
            'certifications_processed' => 0,
        ];

        foreach ($certifications as $certification) {
            $stats = $this->showPurgeSummary($certification, $retentionService, $dryRun);
            $totalStats['certificates_deleted'] += $stats['certificates_deleted'] ?? 0;
            $totalStats['images_deleted'] += $stats['images_deleted'] ?? 0;
            $totalStats['certifications_processed']++;
        }

        $this->printFinalSummary($totalStats, $dryRun);
        return self::SUCCESS;
    }

    /**
     * Purge specific certification by slug.
     */
    private function purgeCertification(CertificationDataRetentionService $retentionService, string $slug, bool $dryRun): int
    {
        $certification = Certification::where('slug', $slug)->first();

        if (!$certification) {
            $this->error("Certification not found: {$slug}");
            return self::FAILURE;
        }

        if (!$certification->manual_user_data_purge_enabled) {
            $this->warn("Certification '{$slug}' has manual_user_data_purge_enabled = false");
            if (!$this->input->isInteractive()) {
                return self::FAILURE;
            }

            if (!$this->confirm('Continue anyway?')) {
                return self::FAILURE;
            }
        }

        if (!$dryRun) {
            dispatch(new PurgeExpiredCertificationDataJob($certification->id));

            $this->info("Queued purge job for certification '{$slug}'.");

            return self::SUCCESS;
        }

        $stats = $this->showPurgeSummary($certification, $retentionService, $dryRun);
        $this->printFinalSummary($stats, $dryRun);

        return self::SUCCESS;
    }

    /**
     * Prompt user to select certification.
     */
    private function promptForCertification(CertificationDataRetentionService $retentionService, bool $dryRun): int
    {
        $certifications = Certification::where('manual_user_data_purge_enabled', true)
            ->orderBy('name')
            ->get();

        if ($certifications->isEmpty()) {
            $this->warn('No certifications found with manual_user_data_purge_enabled = true');
            return self::FAILURE;
        }

        $choices = $certifications->map(fn($c) => "{$c->name} ({$c->slug})")->toArray();
        $selected = $this->choice('Select certification to purge', $choices);

        // Extract slug from choice
        preg_match('/\(([^)]+)\)/', $selected, $matches);
        $slug = $matches[1] ?? null;

        if (!$slug) {
            $this->error('Failed to extract certification slug');
            return self::FAILURE;
        }

        return $this->purgeCertification($retentionService, $slug, $dryRun);
    }

    /**
     * Show purge summary and execute if not dry-run.
     */
    private function showPurgeSummary(Certification $certification, CertificationDataRetentionService $retentionService, bool $dryRun): array
    {
        $this->line("\n📋 <fg=cyan;options=bold>{$certification->name}</> ({$certification->slug})");

        // Get statistics
        $stats = $retentionService->getPurgeStatistics($certification);

        if (($stats['expired_certificates'] ?? 0) === 0) {
            $this->info('  ✓ No expired data to purge');
            return ['certificates_deleted' => 0, 'images_deleted' => 0];
        }

        $this->line("  Expiry Mode: <fg=yellow>{$certification->expiry_mode}</>");
        $this->line("  Expired Certificates: <fg=yellow>{$stats['expired_certificates']}</>");

        $shouldProceed = $dryRun || !$this->input->isInteractive() || $this->confirm('  Proceed with deletion?', false);

        if (!$dryRun && $shouldProceed) {
            $purgeStats = $retentionService->purgeExpiredCertificationData($certification, true);
            $this->info("  ✓ <fg=green>Purged {$purgeStats['certificates_deleted']} certificates</> and <fg=green>{$purgeStats['images_deleted']} images</>");
            return $purgeStats;
        } elseif ($dryRun) {
            $this->info("  ✓ Would purge {$stats['expired_certificates']} expired certificates");
            return ['certificates_deleted' => $stats['expired_certificates'], 'images_deleted' => 0];
        }

        return ['certificates_deleted' => 0, 'images_deleted' => 0];
    }

    /**
     * Print final summary.
     */
    private function printFinalSummary(array $stats, bool $dryRun): void
    {
        $this->line("\n<fg=cyan>════════════════════════════════════════</>");

        if ($dryRun) {
            $this->line("<fg=yellow>DRY RUN SUMMARY:</>");
            $this->line("  Certificates that would be deleted: <fg=yellow>{$stats['certificates_deleted']}</>");
        } else {
            $this->line("<fg=green>PURGE COMPLETED:</>");
            $this->line("  Certificates deleted: <fg=green>{$stats['certificates_deleted']}</>");
            $this->line("  Images deleted: <fg=green>{$stats['images_deleted']}</>");
        }

        if (isset($stats['certifications_processed'])) {
            $this->line("  Certifications processed: <fg=blue>{$stats['certifications_processed']}</>");
        }

        $this->line("<fg=cyan>════════════════════════════════════════</>\n");
    }
}
