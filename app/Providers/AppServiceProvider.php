<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Http\Middleware\TrustProxies;
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
        $trustedProxies = trim((string) config('app.trusted_proxies', ''));
        if ($trustedProxies !== '') {
            TrustProxies::at(
                in_array($trustedProxies, ['*', '**'], true)
                    ? $trustedProxies
                    : array_values(array_filter(array_map('trim', explode(',', $trustedProxies))))
            );
        }

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        date_default_timezone_set((string) config('app.timezone', 'Asia/Jakarta'));
        Carbon::setLocale((string) config('app.locale', 'id'));
    }
}
