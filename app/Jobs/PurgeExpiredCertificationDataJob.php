<?php

namespace App\Jobs;

use App\Models\Certification;
use App\Support\CertificationDataRetentionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PurgeExpiredCertificationDataJob implements ShouldQueue
{
    use Queueable;

    private ?int $certificationId;

    public function __construct(?int $certificationId = null)
    {
        $this->certificationId = $certificationId;
    }

    public function certificationId(): ?int
    {
        return $this->certificationId;
    }

    /**
     * Execute the job.
     */
    public function handle(CertificationDataRetentionService $retentionService): void
    {
        try {
            if ($this->certificationId) {
                // Purge expired data for specific certification
                $certification = Certification::find($this->certificationId);
                if (!$certification) {
                    Log::warning('PurgeExpiredCertificationDataJob: Certification not found', [
                        'certification_id' => $this->certificationId,
                    ]);
                    return;
                }

                $stats = $retentionService->purgeExpiredCertificationData($certification);

                Log::info('PurgeExpiredCertificationDataJob: Certification purged', [
                    'certification_id' => $certification->id,
                    'certification_slug' => $certification->slug,
                    'certificates_deleted' => $stats['certificates_deleted'] ?? 0,
                    'images_deleted' => $stats['images_deleted'] ?? 0,
                ]);
            } else {
                // Purge all expired certifications
                $allCertifications = Certification::where('manual_user_data_purge_enabled', true)->get();

                $totalStats = [
                    'certificates_deleted' => 0,
                    'images_deleted' => 0,
                    'certifications_processed' => 0,
                ];

                foreach ($allCertifications as $certification) {
                    $stats = $retentionService->purgeExpiredCertificationData($certification);
                    $totalStats['certificates_deleted'] += $stats['certificates_deleted'] ?? 0;
                    $totalStats['images_deleted'] += $stats['images_deleted'] ?? 0;
                    $totalStats['certifications_processed']++;
                }

                Log::info('PurgeExpiredCertificationDataJob: All certifications purged', $totalStats);
            }
        } catch (\Exception $e) {
            Log::error('PurgeExpiredCertificationDataJob: Error during purge', [
                'error' => $e->getMessage(),
                'certification_id' => $this->certificationId,
            ]);
            throw $e;
        }
    }
}
