<?php

namespace App\Providers;

use App\Models\Certification;
use App\Observers\CertificationObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        Certification::observe(CertificationObserver::class);

        $forwardedHost = request()->headers->get('x-forwarded-host');

        if (! empty($forwardedHost)) {
            $host = trim(explode(',', $forwardedHost)[0]);
            $proto = trim(explode(',', (string) request()->headers->get('x-forwarded-proto', 'https'))[0]);

            // En entornos con proxy (ej. Codespaces), evitar que los enlaces apunten a localhost interno.
            URL::forceRootUrl($proto.'://'.$host);
            URL::forceScheme($proto);
        }
    }
}
