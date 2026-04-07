<?php

namespace App\Observers;

use App\Models\Certification;
use App\Support\CertificationVersioningService;
use Illuminate\Support\Facades\Log;

class CertificationObserver
{
    protected CertificationVersioningService $versioningService;

    public function __construct(CertificationVersioningService $versioningService)
    {
        $this->versioningService = $versioningService;
    }

    public function updated(Certification $certification): void
    {
        // Create version snapshot after update so dirty data is available.
        try {
            $changes = array_keys($certification->getChanges());
            if (empty($changes)) {
                return;
            }

            $reason = 'Cambios en la certificación: ' . implode(', ', $changes);
            
            $this->versioningService->createVersion($certification, $reason);
            
            Log::info('certification.version.created', [
                'certification_id' => $certification->id,
                'changes' => $changes,
            ]);
        } catch (\Exception $e) {
            Log::error('certification.version.failed', [
                'certification_id' => $certification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function deleting(Certification $certification): void
    {
        try {
            $this->versioningService->createVersion(
                $certification,
                'Certificación eliminada'
            );

            Log::info('certification.version.deleted', [
                'certification_id' => $certification->id,
            ]);
        } catch (\Exception $e) {
            Log::error('certification.version.delete.failed', [
                'certification_id' => $certification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
