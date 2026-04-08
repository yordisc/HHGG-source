<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('certificates:clean')->daily();

// Phase 3: Data Retention & Purging
// Run purge expired certificates every night at 2 AM
Schedule::command('certificates:purge-expired --all')
    ->dailyAt('02:00')
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Daily certificate purge job completed successfully');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Daily certificate purge job failed');
    });
