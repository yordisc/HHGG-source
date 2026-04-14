<?php

namespace App\Jobs;

use App\Models\Certificate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CleanExpiredCertificatesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $deleted = Certificate::query()
            ->where('expires_at', '<', now())
            ->delete();

        Log::info('CleanExpiredCertificatesJob: expired certificates deleted', [
            'deleted' => $deleted,
        ]);
    }
}
