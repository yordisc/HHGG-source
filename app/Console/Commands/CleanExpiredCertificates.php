<?php

namespace App\Console\Commands;

use App\Jobs\CleanExpiredCertificatesJob;
use Illuminate\Console\Command;

class CleanExpiredCertificates extends Command
{
    protected $signature = 'certificates:clean';

    protected $description = 'Delete expired certificates from the database';

    public function handle(): int
    {
        dispatch(new CleanExpiredCertificatesJob());

        $this->info('Expired certificates cleanup queued.');

        return self::SUCCESS;
    }
}
