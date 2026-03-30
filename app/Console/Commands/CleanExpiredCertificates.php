<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use Illuminate\Console\Command;

class CleanExpiredCertificates extends Command
{
    protected $signature = 'certificates:clean';

    protected $description = 'Delete expired certificates from the database';

    public function handle(): int
    {
        $deleted = Certificate::query()
            ->where('expires_at', '<', now())
            ->delete();

        $this->info("Expired certificates deleted: {$deleted}");

        return self::SUCCESS;
    }
}
